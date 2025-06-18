<?php

class ChecklistItems_Widget_View extends Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function process(Vtiger_Request $request)
    {
        $soruce_module = $request->get('source_module');
        $source_record = $request->get('source_record');
        $moduleModel = ChecklistItems_Module_Model::getInstance('ChecklistItems');
        $CheckList = $moduleModel->getChecklist($soruce_module);
        $viewer = $this->getViewer($request);
        $viewer->assign('SOURCE_MODULE', $soruce_module);
        $viewer->assign('SOURCE_RECORD', $source_record);
        $viewer->assign('CHECKLISTS', $CheckList);
        $viewer->assign('COUNT_CHECKLIST', count($CheckList));
        $viewer->assign('MODULE_MODEL', $moduleModel);
        $viewer->view('Widget.tpl', 'ChecklistItems');
    }
}
