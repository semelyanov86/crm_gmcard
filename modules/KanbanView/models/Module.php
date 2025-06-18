<?php

require_once 'modules/KanbanView/KanbanView.php';

class KanbanView_Module_Model extends Vtiger_Module_Model
{
    public function getSettingLinks()
    {
        $settingsLinks[] = ['linktype' => 'MODULESETTING', 'linklabel' => 'Settings', 'linkurl' => 'index.php?module=KanbanView&parent=Settings&view=Settings', 'linkicon' => ''];
        $settingsLinks[] = ['linktype' => 'MODULESETTING', 'linklabel' => 'Uninstall', 'linkurl' => 'index.php?module=KanbanView&parent=Settings&view=Uninstall', 'linkicon' => ''];

        return $settingsLinks;
    }

    public function getPrimaryFields($module)
    {
        global $adb;
        $primaryFields = [];
        $sql = "SELECT fieldid,fieldlabel,fieldname FROM vtiger_field\r\n                INNER JOIN vtiger_tab ON vtiger_field.tabid = vtiger_tab.tabid\r\n                WHERE uitype IN (15,16) AND vtiger_tab.`name` = ? AND (vtiger_field.presence = 0 OR vtiger_field.presence = 2) AND vtiger_field.block > 0";
        $rs = $adb->pquery($sql, [$module]);
        if ($adb->num_rows($rs) > 0) {
            for ($i = 0; $i < $adb->num_rows($rs); ++$i) {
                $primaryFields[$i]['fieldid'] = $adb->query_result($rs, $i, 'fieldid');
                $primaryFields[$i]['fieldlabel'] = $adb->query_result($rs, $i, 'fieldlabel');
                $primaryFields[$i]['fieldname'] = $adb->query_result($rs, $i, 'fieldname');
            }
        }

        return $primaryFields;
    }

    public function saveKanbanViewSetting($request)
    {
        global $adb;
        $userModel = Users_Record_Model::getCurrentUserModel();
        $userName = $userModel->get('user_name');
        if (!empty($userName)) {
            $module = $request->get('source_module');
            $primary_field = $request->get('primaryField');
            $primary_value = serialize($request->get('primaryFieldValue'));
            $other_field = serialize($request->get('otherField'));
            $isDefaultPage = $request->get('isDefaultPage');
            $moduleModel = Vtiger_Module_Model::getInstance($module);
            $colorField = Vtiger_Field_Model::getInstance('kanban_color', $moduleModel);
            if (!$colorField) {
                KanbanView::createFields($module);
            }
            if ($this->isUpdate($module, $userName)) {
                $sql = 'UPDATE kanbanview_setting SET primary_field = ?, primary_value = ?, other_field = ?, is_default_page = ? WHERE username = ? AND module = ?';
                $adb->pquery($sql, [$primary_field, $primary_value, $other_field, $isDefaultPage, $userName, $module]);

                return true;
            }
            $sql = 'INSERT INTO kanbanview_setting (primary_field,primary_value,other_field,module,username,is_default_page) VALUES (?,?,?,?,?,?)';
            $adb->pquery($sql, [$primary_field, $primary_value, $other_field, $module, $userName, $isDefaultPage]);

            return true;
        }

        return false;
    }

    public function isUpdate($module, $userName)
    {
        global $adb;
        $sql = 'SELECT * FROM kanbanview_setting WHERE kanbanview_setting.module = ? AND username = ?';
        $rs = $adb->pquery($sql, [$module, $userName]);
        if ($adb->num_rows($rs) > 0) {
            return true;
        }

        return false;
    }

    public function getKanbanviewSetting($module)
    {
        global $adb;
        $primaryFieldSettings = [];
        $userModel = Users_Record_Model::getCurrentUserModel();
        $username = $userModel->get('user_name');
        if (!empty($username)) {
            $sql = 'SELECT * FROM kanbanview_setting WHERE kanbanview_setting.module = ? AND username = ?';
            $rs = $adb->pquery($sql, [$module, $username]);
            if ($adb->num_rows($rs) > 0) {
                $primaryFieldSettings['primary_field'] = $adb->query_result($rs, 0, 'primary_field');
                $primaryFieldSettings['primary_value_setting'] = unserialize(decode_html($adb->query_result($rs, 0, 'primary_value')));
                $primaryFieldSettings['other_field'] = unserialize(decode_html($adb->query_result($rs, 0, 'other_field')));
                $primaryFieldSettings['is_default_page'] = $adb->query_result($rs, 0, 'is_default_page');
                if (!$primaryFieldSettings['primary_value_setting']) {
                    $primaryFieldSettings['primary_value_setting'] = [];
                }
                if (!$primaryFieldSettings['other_field']) {
                    $primaryFieldSettings['other_field'] = [];
                }
            }
        }

        return $primaryFieldSettings;
    }

    public function getRecordIdSequence($listRecordId, $module)
    {
        global $adb;
        $listRecordIdSeq = [];
        $resultMaxId = $adb->pquery('SELECT MAX(sequence) as max_id FROM kanban_sequence WHERE module =?', [$module]);
        $maxId = 1;
        if ($adb->num_rows($resultMaxId) > 0) {
            $maxId = $adb->query_result($resultMaxId, 0, 'max_id');
        }
        foreach ($listRecordId as $recordId) {
            $sql = 'SELECT crmid FROM kanban_sequence WHERE crmid = ? ';
            $rs = $adb->pquery($sql, [$recordId]);
            if ($adb->num_rows($rs) == 0) {
                ++$maxId;
                $adb->pquery('INSERT INTO kanban_sequence(crmid,module,sequence) VALUES(?,?,?);', [$recordId, $module, $maxId]);
            }
        }
        $recordCondition = implode(',', $listRecordId);
        $rsSequence = $adb->pquery('SELECT crmid FROM kanban_sequence WHERE module = ? AND crmid IN (' . $recordCondition . ') ORDER BY sequence ASC', [$module]);
        if ($adb->num_rows($rsSequence) > 0) {
            for ($i = 0; $i < $adb->num_rows($rsSequence); ++$i) {
                $listRecordIdSeq[] = $adb->query_result($rsSequence, $i, 'crmid');
            }
        }

        return $listRecordIdSeq;
    }

    public function getSequence($crmId, $module, $primary_field, $primary_value)
    {
        global $adb;
        $userModel = Users_Record_Model::getCurrentUserModel();
        $username = $userModel->get('user_name');
        $rsRecord = $adb->pquery('SELECT * FROM kanban_sequence WHERE crmid = ' . $crmId . ' AND username = ?', [$username]);
        if ($adb->num_rows($rsRecord) > 0) {
            return $adb->query_result($rsRecord, 0, 'sequence');
        }
        $seqNum = 1;
        $rsMaxSeq = $adb->pquery('SELECT MAX(sequence) as max_id FROM kanban_sequence WHERE module =? AND username = ?', [$module, $username]);
        $maxSeq = $adb->query_result($rsMaxSeq, 0, 'max_id');
        if ($maxSeq) {
            $seqNum = $maxSeq + 1;
        }
        $adb->pquery('INSERT INTO kanban_sequence(crmid,module,sequence,`primary_field_id`,`primary_field_value`,`username`) VALUES (?,?,?,?,?,?);', [$crmId, $module, $seqNum, $primary_field, $primary_value, $username]);

        return $seqNum;
    }

    public function getCurrentSequence($crmId, $username)
    {
        global $adb;
        $seqNum = -1;
        $rs = $adb->pquery('SELECT * FROM kanban_sequence WHERE crmid = ? AND username = ?', [$crmId, $username]);
        if ($adb->num_rows($rs) > 0) {
            $seqNum = $adb->query_result($rs, 0, 'sequence');
        }

        return $seqNum;
    }

    public function setFontColor($recordModel)
    {
        $groupColor1 = ['Red', 'Green', 'Teal', 'Blue', 'Purple', 'Olive'];
        $groupColor2 = ['Yellow', 'Orange', 'Peru', 'Silver'];
        $bgColorCard = $recordModel->get('kanban_color');
        if (in_array($bgColorCard, $groupColor1)) {
            $recordModel->set('font_color', 'White');
        } else {
            if (in_array($bgColorCard, $groupColor2)) {
                $recordModel->set('font_color', 'Black');
            }
        }

        return $recordModel;
    }
}
