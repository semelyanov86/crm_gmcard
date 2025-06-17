<?php

class VReports_Detail_View extends Vtiger_Index_View
{
    protected $reportData = NULL;
    protected $calculationFields = NULL;
    protected $count = NULL;
    protected $fieldsList = NULL;
    const REPORT_LIMIT = 500;
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("step1");
        $this->exposeMethod("step2");
        $this->exposeMethod("step3");
    }
    
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $record = $request->get("record");
        $reportModel = VReports_Record_Model::getCleanInstance($record);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $owner = $reportModel->get("owner");
        $sharingType = $reportModel->get("sharingtype");
        $isRecordShared = true;
        if ($currentUserPriviligesModel->id != $owner && $sharingType == "Private") {
            $currentUserModel = Users_Record_Model::getCurrentUserModel();
            if (!$currentUserModel->isAdminUser()) {
                $isRecordShared = $reportModel->isRecordHasViewAccess($sharingType);
            }
        }
        if (!$isRecordShared || !$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $recordId = $request->get("record");
        $detailViewModel = VReports_DetailView_Model::getInstance($moduleName, $recordId);
        $reportModel = $detailViewModel->getRecord();
        $viewer->assign("REPORT_NAME", $reportModel->getName());
        parent::preProcess($request);
        $page = $request->get("page");
        $reportModel->setModule("VReports");
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set("page", $page);
        $pagingModel->set("limit", self::REPORT_LIMIT);
        $reportData = $reportModel->getReportData($pagingModel);
        $this->reportData = $reportData["data"];
        $this->calculationFields = $reportModel->getReportCalulationData();
        $this->count = $reportData["count"];
        $this->fieldsList = $reportData["fields_list"];
        $primaryModule = $reportModel->getPrimaryModule();
        $secondaryModules = $reportModel->getSecondaryModules();
        $primaryModuleModel = Vtiger_Module_Model::getInstance($primaryModule);
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userPrivilegesModel = Users_Privileges_Model::getInstanceById($currentUser->getId());
        $permission = $userPrivilegesModel->hasModulePermission($primaryModuleModel->getId());
        if (!$permission) {
            $viewer->assign("MODULE", $primaryModule);
            $viewer->assign("MESSAGE", vtranslate("LBL_PERMISSION_DENIED"));
            $viewer->view("OperationNotPermitted.tpl", $primaryModule);
            exit;
        }
        $detailViewLinks = $detailViewModel->getDetailViewLinks();
        $viewer->assign("SELECTED_ADVANCED_FILTER_FIELDS", $reportModel->transformToNewAdvancedFilter());
        $viewer->assign("PRIMARY_MODULE", $primaryModule);
        $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($reportModel);
        $primaryModuleRecordStructure = $recordStructureInstance->getPrimaryModuleRecordStructure();
        $secondaryModuleRecordStructures = $recordStructureInstance->getSecondaryModuleRecordStructure();
        if ($primaryModule == "HelpDesk") {
            foreach ($primaryModuleRecordStructure as $blockLabel => $blockFields) {
                foreach ($blockFields as $field => $object) {
                    if ($field == "update_log") {
                        unset($primaryModuleRecordStructure[$blockLabel][$field]);
                    }
                }
            }
        }
        if (!empty($secondaryModuleRecordStructures)) {
            foreach ($secondaryModuleRecordStructures as $module => $structure) {
                if ($module == "HelpDesk") {
                    foreach ($structure as $blockLabel => $blockFields) {
                        foreach ($blockFields as $field => $object) {
                            if ($field == "update_log") {
                                unset($secondaryModuleRecordStructures[$module][$blockLabel][$field]);
                            }
                        }
                    }
                }
            }
        }
        $viewer->assign("PRIMARY_MODULE_RECORD_STRUCTURE", $primaryModuleRecordStructure);
        $viewer->assign("SECONDARY_MODULE_RECORD_STRUCTURES", $secondaryModuleRecordStructures);
        $secondaryModuleIsCalendar = strpos($secondaryModules, "Calendar");
        if ($primaryModule == "Calendar" || $secondaryModuleIsCalendar !== false) {
            $advanceFilterOpsByFieldType = Calendar_Field_Model::getAdvancedFilterOpsByFieldType();
        } else {
            $advanceFilterOpsByFieldType = Vtiger_Field_Model::getAdvancedFilterOpsByFieldType();
        }
        $viewer->assign("ADVANCED_FILTER_OPTIONS", Vtiger_Field_Model::getAdvancedFilterOptions());
        $viewer->assign("ADVANCED_FILTER_OPTIONS_BY_TYPE", $advanceFilterOpsByFieldType);
        $dateFilters = Vtiger_Field_Model::getDateFilterTypes();
        foreach ($dateFilters as $comparatorKey => $comparatorInfo) {
            $comparatorInfo["startdate"] = DateTimeField::convertToUserFormat($comparatorInfo["startdate"]);
            $comparatorInfo["enddate"] = DateTimeField::convertToUserFormat($comparatorInfo["enddate"]);
            $comparatorInfo["label"] = vtranslate($comparatorInfo["label"], $module);
            $dateFilters[$comparatorKey] = $comparatorInfo;
        }
        $viewer->assign("DATE_FILTERS", $dateFilters);
        $viewer->assign("LINEITEM_FIELD_IN_CALCULATION", $reportModel->showLineItemFieldsInFilter(false));
        $viewer->assign("DETAILVIEW_LINKS", $detailViewLinks);
        $viewer->assign("DETAILVIEW_ACTIONS", $detailViewModel->getDetailViewActions());
        $viewer->assign("REPORT_MODEL", $reportModel);
        $viewer->assign("IS_ADMIN", $currentUser->isAdminUser());
        $viewer->assign("RECORD_ID", $recordId);
        $viewer->assign("COUNT", $this->count);
        $viewer->assign("REPORT_LIMIT", self::REPORT_LIMIT);
        $viewer->assign("MODULE", $moduleName);
        $dashBoardModel = new VReports_DashBoard_Model();
        $activeTabs = $dashBoardModel->getActiveTabs();
        foreach ($activeTabs as $index => $tabInfo) {
            if (!empty($tabInfo["appname"])) {
                unset($activeTabs[$index]);
            }
        }
        $viewer->assign("DASHBOARD_TABS", $activeTabs);
        $viewer->view("ReportHeader.tpl", $moduleName);
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        } else {
            echo $this->getReport($request);
        }
    }
    public function getReport(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $record = $request->get("record");
        $page = $request->get("page");
        $data = $this->reportData;
        $fieldsList = $this->fieldsList;
        $calculation = $this->calculationFields;
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set("page", $page);
        $pagingModel->set("limit", self::REPORT_LIMIT + 1);
        if (empty($data)) {
            $reportModel = VReports_Record_Model::getInstanceById($record);
            $reportModel->setModule("VReports");
            $reportType = $reportModel->get("reporttype");
            $reportData = $reportModel->getReportData($pagingModel);
            $data = $reportData["data"];
            $this->count = $reportData["count"];
            $calculation = $reportModel->getReportCalulationData();
        }
        $viewer->assign("CALCULATION_FIELDS", $calculation);
        $viewer->assign("DATA", $data);
        $viewer->assign("HEADER_FIELDS_LIST", $fieldsList);
        $viewer->assign("RECORD_ID", $record);
        $viewer->assign("PAGING_MODEL", $pagingModel);
        $viewer->assign("COUNT", $this->count);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("REPORT_RUN_INSTANCE", VReportRun::getInstance($record));
        $count_data = 0;
        if (is_array($data)) {
            $count_data = count($data);
        }
        if (self::REPORT_LIMIT < $count_data) {
            $viewer->assign("LIMIT_EXCEEDED", true);
        }
        $viewer->view("ReportContents.tpl", $moduleName);
    }
    /**
     * Function to get the list of Script models to be included
     * @param Vtiger_Request $request
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = array("modules.Vtiger.resources.Detail", "modules." . $moduleName . ".resources.Detail");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = array("~layouts/v7/modules/VReports/resources/styleVReport.css");
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);
        return $headerCssInstances;
    }
}

?>