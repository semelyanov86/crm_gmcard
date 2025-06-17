<?php

class KanbanView_ActionAjax_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("getPrimaryValues");
        $this->exposeMethod("checkKanbanViewEnable");
        $this->exposeMethod("updatePrimaryFieldValue");
        $this->exposeMethod("enableModule");
        $this->exposeMethod("checkEnable");
    }
    
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function getPrimaryValues(Vtiger_Request $request)
    {
        $primaryFieldSelect = $request->get("primaryField");
        $source_module = $request->get("source_module");
        $recordModel = Vtiger_Record_Model::getCleanInstance($source_module);
        $recordStructureModel = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel);
        foreach ($recordStructureModel->getStructure() as $block) {
            foreach ($block as $field) {
                if ($field->getId() == $primaryFieldSelect) {
                    $primaryFieldValues = $field->getPicklistValues();
                    break;
                }
            }
        }
        $response = new Vtiger_Response();
        $response->setResult($primaryFieldValues);
        $response->emit();
    }
    public function checkKanbanViewEnable(Vtiger_Request $request)
    {
        global $adb;
        $targetModule = $request->get("source_module");
        $allModules = array_keys(Vtiger_Module_Model::getSearchableModules());
        $sql = "SELECT fieldid,fieldlabel,fieldname FROM vtiger_field\r\n                INNER JOIN vtiger_tab ON vtiger_field.tabid = vtiger_tab.tabid\r\n                WHERE uitype IN (15,16) AND vtiger_tab.name = ? and block > 0";
        $rs = $adb->pquery($sql, array($targetModule));
        $numRow = $adb->num_rows($rs);
        $isConfig = false;
        $isDefaultPage = 0;
        if ($this->checkEnable() && in_array($targetModule, $allModules) && 0 < $numRow) {
            $isEnable = true;
            $userModel = Users_Record_Model::getCurrentUserModel();
            $username = $userModel->get("user_name");
            $resultSetting = $adb->pquery("SELECT is_default_page FROM kanbanview_setting WHERE module = ? AND username = ?", array($targetModule, $username));
            if (0 < $adb->num_rows($resultSetting)) {
                $isConfig = true;
                $isDefaultPage = $adb->query_result($resultSetting, 0, "is_default_page");
            }
        } else {
            $isEnable = false;
        }
        $result = array("isConfig" => $isConfig, "isEnable" => $isEnable, "isDefaultPage" => $isDefaultPage);
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
    }
    public function updatePrimaryFieldValue(Vtiger_Request $request)
    {
        global $adb;
        $kanbanModel = new KanbanView_Module_Model();
        $recordId = $request->get("recordId");
        $primaryFieldName = $request->get("primaryFieldName");
        $primaryFieldId = $request->get("primaryFieldId");
        $primaryValue = $request->get("primaryValue");
        $nextRecordId = $request->get("nextRecordId");
        $prevRecordId = $request->get("prevRecordId");
        $source_module = $request->get("source_module");
        $userModel = Users_Record_Model::getCurrentUserModel();
        $username = $userModel->get("user_name");
        $curentNumSeq = $kanbanModel->getCurrentSequence($recordId, $username);
        if (0 < $nextRecordId) {
            echo $curentNumSeq;
            $nextSeq = $kanbanModel->getCurrentSequence($nextRecordId, $username);
            if ($nextSeq < $curentNumSeq) {
                echo "1";
                $adb->pquery("update kanban_sequence set sequence = (sequence + 1) where module = ? AND  sequence < " . $curentNumSeq . " AND sequence >= " . $nextSeq . " AND username = ?", array($source_module, $username));
                $adb->pquery("update kanban_sequence set sequence = ?, primary_field_value = ? where crmid = ? AND username = ?", array($nextSeq, $primaryValue, $recordId, $username));
            } else {
                if ($curentNumSeq < $nextSeq) {
                    echo "2";
                    $adb->pquery("update kanban_sequence set sequence = (sequence - 1) where module = ? AND  sequence > " . $curentNumSeq . " AND sequence < " . $nextSeq . " AND username = ?", array($source_module, $username));
                    $adb->pquery("update kanban_sequence set sequence = ?,primary_field_value = ? where crmid = ? AND username = ?", array($nextSeq - 1, $primaryValue, $recordId, $username));
                }
            }
        } else {
            if ($nextRecordId = -1 && 0 < $prevRecordId) {
                $prevSeq = $kanbanModel->getCurrentSequence($prevRecordId, $username);
                if ($prevSeq < $curentNumSeq) {
                    $adb->pquery("update kanban_sequence set sequence = (sequence + 1) where module = ? AND  sequence < " . $curentNumSeq . " AND sequence > " . $prevSeq . " AND username = ?", array($source_module, $username));
                    $adb->pquery("update kanban_sequence set sequence = ?,primary_field_value = ? where crmid = ? AND username = ?", array($prevSeq + 1, $primaryValue, $recordId, $username));
                } else {
                    if ($curentNumSeq < $prevSeq) {
                        $adb->pquery("update kanban_sequence set sequence = (sequence - 1) where module = ? AND  sequence > " . $curentNumSeq . " AND sequence <= " . $prevSeq . " AND username = ?", array($source_module, $username));
                        $adb->pquery("update kanban_sequence set sequence = ?,primary_field_value = ? where crmid = ? AND username = ?", array($prevSeq, $primaryValue, $recordId, $username));
                    }
                }
            } else {
                if ($nextRecordId = -1 && ($prevRecordId = -1)) {
                    $rsMaxSeqColumn = $adb->pquery("SELECT MAX(sequence) as max_id FROM kanban_sequence WHERE module =? AND primary_field_id = ? AND primary_field_value = ? AND username = ?", array($source_module, $primaryFieldId, $primaryValue, $username));
                    $maxSeqComumNum = $adb->query_result($rsMaxSeqColumn, 0, "max_id");
                    if (0 < $maxSeqComumNum) {
                        if ($maxSeqComumNum < $curentNumSeq) {
                            $adb->pquery("update kanban_sequence set sequence = (sequence + 1) where module = ? AND  sequence < " . $curentNumSeq . " AND sequence > " . $maxSeqComumNum . " AND username = ?", array($source_module, $username));
                            $adb->pquery("update kanban_sequence set sequence = ?, primary_field_value = ? where crmid = ? AND username = ?", array($maxSeqComumNum + 1, $primaryValue, $recordId, $username));
                        } else {
                            $adb->pquery("update kanban_sequence set sequence = (sequence - 1) where module = ? AND  sequence > " . $curentNumSeq . " AND sequence <= " . $maxSeqComumNum . " AND username = ?", array($source_module, $username));
                            $adb->pquery("update kanban_sequence set sequence = ?, primary_field_value = ? where crmid = ? AND username = ?", array($maxSeqComumNum, $primaryValue, $recordId, $username));
                        }
                    } else {
                        $rsMaxSeqModule = $adb->pquery("SELECT MAX(sequence) as max_id FROM kanban_sequence WHERE module =? AND username = ?", array($source_module, $username));
                        $maxSeqModuleNum = $adb->query_result($rsMaxSeqModule, 0, "max_id");
                        $adb->pquery("update kanban_sequence set sequence = (sequence - 1) where module = ? AND  sequence > " . $curentNumSeq . " AND sequence <= " . $maxSeqModuleNum . " AND username = ?", array($source_module, $username));
                        $adb->pquery("update kanban_sequence set sequence = ?, primary_field_value = ? where crmid = ? AND username = ?", array($maxSeqModuleNum, $primaryValue, $recordId, $username));
                    }
                }
            }
        }
        $recordModel = Vtiger_Record_Model::getInstanceById($recordId);
        $modelData = $recordModel->getData();
        $recordModel->set("id", $recordId);
        $recordModel->set("mode", "edit");
        $recordModel->set($primaryFieldName, $primaryValue);
        $_REQUEST["ajxaction"] = "DETAILVIEW";
        $_REQUEST["action"] = "SaveAjax";
        $result = $recordModel->save();
        $_REQUEST["action"] = "ActionAjax";
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
    }
    public function enableModule(Vtiger_Request $request)
    {
        global $adb;
        $value = $request->get("value");
        $sql = "SELECT * FROM kanban_view_settings";
        $res = $adb->pquery($sql, array());
        if (0 < $adb->num_rows($res)) {
            $adb->pquery("UPDATE `kanban_view_settings` SET `enable`=?", array($value));
        } else {
            $adb->pquery("INSERT INTO `kanban_view_settings`(`enable`) VALUES (?)", array($value));
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("result" => "success"));
        $response->emit();
    }
    public function checkEnable()
    {
        global $adb;
        $rs = $adb->pquery("SELECT `enable` FROM `kanban_view_settings`;", array());
        $enable = $adb->query_result($rs, 0, "enable");
        return $enable;
    }
}

?>