<?php

class VReports_SaveAjax_View extends Vtiger_IndexAjax_View
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function checkPermission(Vtiger_Request $request)
    {
        $record = $request->get("record");
        if (!$record) {
            throw new AppException("LBL_PERMISSION_DENIED");
        }
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $reportModel = VReports_Record_Model::getCleanInstance($record);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $record = $request->get("record");
        $reportModel = VReports_Record_Model::getInstanceById($record);
        $reportModel->setModule("VReports");
        $reportModel->set("advancedFilter", $request->get("advanced_filter"));
        $page = $request->get("page");
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set("page", $page);
        $pagingModel->set("limit", VReports_Detail_View::REPORT_LIMIT);
        if ($mode === "save") {
            $reportModel->saveAdvancedFilters();
            $reportData = $reportModel->getReportData($pagingModel);
            $data = $reportData["data"];
        } else {
            if ($mode === "generate") {
                $reportData = $reportModel->generateData($pagingModel);
                $data = $reportData["data"];
            }
        }
        $calculation = $reportModel->generateCalculationData();
        $viewer->assign("PRIMARY_MODULE", $reportModel->getPrimaryModule());
        $viewer->assign("CALCULATION_FIELDS", $calculation);
        $viewer->assign("DATA", $data);
        $viewer->assign("RECORD_ID", $record);
        $viewer->assign("PAGING_MODEL", $pagingModel);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("NEW_COUNT", $reportData["count"]);
        $viewer->assign("REPORT_RUN_INSTANCE", VReportRun::getInstance($record));
        $viewer->assign("REPORT_MODEL", $reportModel);
        $viewer->assign("HEADER_FIELDS_LIST", $reportData["fields_list"]);
        $viewer->view("ReportContents.tpl", $moduleName);
    }
    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}

?>