<?php

class VReports_ListViewQuickPreview_View extends Vtiger_ListViewQuickPreview_View
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $record = $request->get("record");
        $reportModel = VReports_Record_Model::getInstanceById($record);
        $reportChartModel = VReports_Chart_Model::getInstanceById($reportModel);
        $secondaryModules = $reportModel->getSecondaryModules();
        if (!$secondaryModules) {
            $viewer->assign("CLICK_THROUGH", true);
        } else {
            $viewer->assign("CLICK_THROUGH", false);
        }
        $data = $reportChartModel->getData();
        $viewer->assign("CHART_TYPE", $reportChartModel->getChartType());
        $viewer->assign("DATA", $data);
        $viewer->assign("REPORT_MODEL", $reportModel);
        $viewer->assign("RECORD_ID", $record);
        $viewer->assign("MODULE", $moduleName);
        $viewer->view("ListViewQuickPreview.tpl", $moduleName);
    }
}

?>