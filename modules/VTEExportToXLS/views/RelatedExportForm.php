<?php

class VTEExportToXLS_RelatedExportForm_View extends Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $source_module = $request->get("source_module");
        $related_module = $request->get("related_module");
        $record = $request->get("record");
        $module = $request->getModule();
        $action = $request->get("action");
        $viewer->assign("SOURCE_MODULE", $source_module);
        $viewer->assign("RELATED_MODULE", $related_module);
        $viewer->assign("RECORD", $record);
        $viewer->assign("MODULE", $module);
        $viewer->assign("ACTION", $action);
        $viewer->view("RelatedExportForm.tpl", $module);
    }
}

?>