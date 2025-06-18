<?php

class VTEConditionalAlerts_EditTask_View extends Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $selected_moduleName = $request->get('selected_module');
        $qualifiedModuleName = 'VTEConditionalAlerts';
        $task_id = $request->get('task_id');
        $cat_id = $request->get('cat_id');
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT * FROM vte_conditional_alerts_task\n\t\t\t\t\t\t\t\tWHERE id = ? LIMIT 0,1", [$task_id]);
        $noOfTask = $db->num_rows($result);
        $task_info = [];
        if ($noOfTask > 0) {
            $task_info['action_title'] = $db->query_result($result, 0, 'action_title');
            $task_info['alert_while_edit'] = $db->query_result($result, 0, 'alert_while_edit');
            $task_info['alert_when_open'] = $db->query_result($result, 0, 'alert_when_open');
            $task_info['alert_on_save'] = $db->query_result($result, 0, 'alert_on_save');
            $task_info['donot_allow_to_save'] = $db->query_result($result, 0, 'donot_allow_to_save');
            $task_info['description'] = $db->query_result($result, 0, 'description');
        }
        $selectedModuleModel = Vtiger_Module_Model::getInstance($selected_moduleName);
        $tmpModel = VTEConditionalAlerts_Record_Model::getCleanInstance($selected_moduleName);
        $recordStructureInstance = Settings_Workflows_RecordStructure_Model::getInstanceForWorkFlowModule($tmpModel, Settings_Workflows_RecordStructure_Model::RECORD_STRUCTURE_MODE_FILTER);
        $viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
        $recordStructure = $recordStructureInstance->getStructure();
        if (in_array($selected_moduleName, getInventoryModules())) {
            $itemsBlock = 'LBL_ITEM_DETAILS';
            unset($recordStructure[$itemsBlock]);
        }
        $viewer->assign('RECORD_STRUCTURE', $recordStructure);
        $viewer->assign('TASK_INFO', $task_info);
        $viewer->assign('SELECTED_MODULE', $selected_moduleName);
        $viewer->assign('SELECTED_MODULE_MODEL', $selectedModuleModel);
        $viewer->assign('CAT_ID', $cat_id);
        $viewer->assign('TASK_ID', $task_id);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->view('EditTask.tpl', $qualifiedModuleName);
    }
}
