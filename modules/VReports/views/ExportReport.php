<?php

class VReports_ExportReport_View extends Vtiger_View_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("GetPrintReport");
        $this->exposeMethod("GetPrintReportV2");
        $this->exposeMethod("GetXLS");
        $this->exposeMethod("GetCSV");
    }
    
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $record = $request->get("record");
        $reportModel = VReports_Record_Model::getCleanInstance($record);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        return false;
    }
    public function postProcess(Vtiger_Request $request)
    {
        return false;
    }
    public function process(Vtiger_request $request)
    {
        $mode = $request->getMode();
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    /**
     * Function exports the report in a Excel sheet
     * @param Vtiger_Request $request
     */
    public function GetXLS(Vtiger_Request $request)
    {
        $recordId = $request->get("record");
        $reportModel = VReports_Record_Model::getInstanceById($recordId);
        $reportModel->set("advancedFilter", $request->get("advanced_filter"));
        $reportModel->getReportXLS($request->get("source"));
    }
    /**
     * Function exports report in a CSV file
     * @param Vtiger_Request $request
     */
    public function GetCSV(Vtiger_Request $request)
    {
        $recordId = $request->get("record");
        $reportModel = VReports_Record_Model::getInstanceById($recordId);
        $reportModel->set("advancedFilter", $request->get("advanced_filter"));
        $reportModel->getReportCSV($request->get("source"));
    }
    /**
     * Function displays the report in printable format
     * @param Vtiger_Request $request
     */
    public function GetPrintReport(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $recordId = $request->get("record");
        $reportModel = VReports_Record_Model::getInstanceById($recordId);
        $reportModel->set("advancedFilter", $request->get("advanced_filter"));
        $printData = $reportModel->getReportPrint();
        $viewer->assign("REPORT_NAME", $reportModel->getName());
        $viewer->assign("PRINT_DATA", $printData["data"][0]);
        $viewer->assign("TOTAL", $printData["total"]);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("ROW", $printData["data"][1]);
        $viewer->view("PrintReport.tpl", $moduleName);
    }
    public function GetPrintReportV2(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $recordId = $request->get("record");
        $reportModel = VReports_Record_Model::getInstanceById($recordId);
        $reportModel->set("advancedFilter", $request->get("advanced_filter"));
        $printData = $reportModel->getReportPrint();
        $viewer->assign("REPORT_NAME", $reportModel->getName());
        $viewer->assign("PRINT_DATA", $printData["data"][0]);
        $viewer->assign("TOTAL", $printData["total"]);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("ROW", $printData["data"][1]);
        $viewer->view("PrintReportV2.tpl", $moduleName);
    }
}

?>