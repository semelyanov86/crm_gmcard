<?php

class VTEConditionalAlerts_Record_Model extends Vtiger_Record_Model
{
    public function getTasks()
    {
        $adb = PearDatabase::getInstance();
        $sql = 'SELECT * FROM vte_conditional_alerts_task WHERE cat_id = ?';
        $result = $adb->pquery($sql, [$this->getId()]);
        $taskList = [];
        $noOfFields = $adb->num_rows($result);
        if ($noOfFields > 0) {
            for ($i = 0; $i < $noOfFields; ++$i) {
                $taskId = $adb->query_result($result, $i, 'id');
                $clf_id = $adb->query_result($result, $i, 'cat_id');
                $active = $adb->query_result($result, $i, 'active');
                $title = $adb->query_result($result, $i, 'action_title');
                $actions = $adb->query_result($result, $i, 'actions');
                $active_link = '?module=VTEConditionalAlerts&parent=Settings&action=TaskAjax&mode=ChangeStatus&task_id=' . $taskId . '&active=' . $active;
                $remove_link = '?module=VTEConditionalAlerts&parent=Settings&action=TaskAjax&mode=Delete&task_id=' . $taskId;
                $taskList[$i] = ['id' => $taskId, 'active' => $active, 'action_title' => $title, 'cat_id' => $clf_id, 'actions' => $actions, 'active_url' => $active_link, 'remove_link' => $remove_link];
            }
        }

        return $taskList;
    }

    public function getInfo()
    {
        $adb = PearDatabase::getInstance();
        $sql = 'SELECT * FROM vte_conditional_alerts WHERE id = ? LIMIT 0,1';
        $result = $adb->pquery($sql, [$this->getId()]);
        $clf_info = [];
        if ($noOfFields = $adb->num_rows($result) > 0) {
            $clf_info['id'] = $adb->query_result($result, 0, 'id');
            $clf_info['module'] = $adb->query_result($result, 0, 'module');
            $clf_info['description'] = $adb->query_result($result, 0, 'description');
            $clf_info['condition'] = $adb->query_result($result, 0, 'condition');
        }

        return $clf_info;
    }
}
