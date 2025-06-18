<?php

class VTEEmailDesigner_ListAjax_View extends VTEEmailDesigner_List_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('getRecordsCount');
        $this->exposeMethod('getPageCount');
        $this->exposeMethod('previewTemplate');
    }

    public function preProcess(Vtiger_Request $request)
    {
        return true;
    }

    public function postProcess(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    public function previewTemplate(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $recordId = $request->get('record');
        $templateRecordModel = VTEEmailDesigner_Record_Model::getInstanceById($recordId);
        $viewer->assign('RECORD_MODEL', $templateRecordModel);
        $viewer->view('PreviewEmailTemplate.tpl', $moduleName);
    }
}
