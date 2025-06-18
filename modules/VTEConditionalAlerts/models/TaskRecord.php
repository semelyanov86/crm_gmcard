<?php

class VTEConditionalAlerts_TaskRecord_Model extends Vtiger_Record_Model
{
    public const TASK_STATUS_ACTIVE = 1;

    public function getId()
    {
        return $this->get('id');
    }

    public function getName()
    {
        return $this->get('name');
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setControlLayoutField($clf_id)
    {
        $this->cat_id = $clf_id;
    }

    public function setActive($active)
    {
        $this->active = $active;
    }

    public function getAction()
    {
        return $this->get('actions');
    }

    public function setAction($action)
    {
        $this->actions = $action;
    }

    public function isActive()
    {
        return $this->get('active') == self::TASK_STATUS_ACTIVE;
    }

    public function getControlLayoutFieldId()
    {
        return $this->get('cat_id');
    }

    public function getEditViewUrl()
    {
        return 'index.php?module=VTEConditionalAlerts&parent=Settings&view=EditTask&task_id=' . $this->getId() . '&for_cap=' . $this->getControlLayoutFieldId();
    }

    public function getDeleteActionUrl()
    {
        return 'index.php?module=VTEConditionalAlerts&parent=Settings&action=TaskAjax&mode=Delete&task_id=' . $this->getId();
    }

    public function getChangeStatusUrl()
    {
        return 'index.php?module=VTEConditionalAlerts&parent=Settings&action=TaskAjax&mode=ChangeStatus&task_id=' . $this->getId();
    }

    /**
     * Function deletes clf task.
     */
    public function delete()
    {
        $adb = PearDatabase::getInstance();
        $recordId = $_REQUEST['task_id'];
        if (!empty($recordId)) {
            $sql = 'DELETE FROM `vte_conditional_alerts_task` WHERE `id`=?';
            $adb->pquery($sql, [$recordId]);
            $sql = 'DELETE FROM `vte_conditional_alerts_task`  WHERE cat_id = ?';
            $adb->pquery($sql, [$recordId]);
        }

        return true;
    }

    /**
     * Function saves clf task.
     */
    public function save()
    {
        $adb = PearDatabase::getInstance();
        $json = new Zend_Json();
        $recordId = $_REQUEST['task_id'];
        $action_title = $_REQUEST['action_title'];
        $alert_while_edit = $_REQUEST['alert_while_edit'];
        $alert_when_open = $_REQUEST['alert_when_open'];
        $alert_on_save = 0;
        $donot_allow_to_save = 0;
        $description = $_REQUEST['description'];
        if (empty($recordId)) {
            $sql = 'INSERT INTO `vte_conditional_alerts_task` (`cat_id`, `action_title`,`alert_while_edit`,`alert_when_open`,`alert_on_save`,`donot_allow_to_save`,`description`,`active`) VALUES (?, ?, ? ,?, ?, ?, ?,?)';
            $adb->pquery($sql, [$_REQUEST['for_cap'], $action_title, $alert_while_edit, $alert_when_open, $alert_on_save, $donot_allow_to_save, $description, 1]);
            $recordId = $adb->getLastInsertID();
        } else {
            $sql = 'UPDATE `vte_conditional_alerts_task` SET `cat_id` = ?, `action_title` = ?,`alert_while_edit` = ?,`alert_when_open` = ?,`alert_on_save` = ?,`donot_allow_to_save` = ? ,`description` = ? WHERE `id`=?';
            $adb->pquery($sql, [$_REQUEST['for_cap'], $action_title, $alert_while_edit, $alert_when_open, $alert_on_save, $donot_allow_to_save, $description, $recordId]);
        }

        return $recordId;
    }

    public static function active($request)
    {
        $adb = PearDatabase::getInstance();
        $recordId = $request->get('task_id');
        $active = $request->get('active') == '1' ? 0 : 1;
        if (!empty($recordId)) {
            $sql = 'UPDATE `vte_conditional_alerts_task` SET `active`=? WHERE `id`=?';
            $adb->pquery($sql, [$active, $recordId]);
        }

        return $recordId;
    }
}
