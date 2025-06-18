<?php

class ChecklistItems_ChecklistDetails_View extends Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function process(Vtiger_Request $request)
    {
        $user = Users_Record_Model::getCurrentUserModel();
        $moduleName = $request->getModule();
        $source_module = $request->get('source_module');
        $source_record = $request->get('source_record');
        $checklistid = $request->get('checklistid');
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $checklist_items = $moduleModel->getChecklistItems($source_record, $checklistid);
        $currDate = new DateTimeField('');
        $viewer = $this->getViewer($request);
        $viewer->assign('SOURCE_MODULE', $source_module);
        $viewer->assign('SOURCE_RECORD', $source_record);
        $viewer->assign('CHECKLISTID', $checklistid);
        $viewer->assign('CHECKLIST_ITEMS', $checklist_items);
        $viewer->assign('COUNT_ITEM', count($checklist_items));
        $viewer->assign('CURR_DATE', $currDate->getDisplayDate($user));
        $viewer->assign('CURR_TIME', $currDate->getDisplayTime($user));
        $viewer->assign('CURR_USER_MODEL', $user);
        echo $viewer->view('ChecklistDetails.tpl', 'ChecklistItems', true);
    }
}
