<?php

class VReports_PivotDetail_View extends Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $record = $request->get('record');
        $reportModel = VReports_Record_Model::getCleanInstance($record);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $owner = $reportModel->get('owner');
        $sharingType = $reportModel->get('sharingtype');
        $isRecordShared = true;
        if ($currentUserPriviligesModel->id != $owner && $sharingType == 'Private') {
            $currentUserModel = Users_Record_Model::getCurrentUserModel();
            if (!$currentUserModel->isAdminUser()) {
                $isRecordShared = $reportModel->isRecordHasViewAccess($sharingType);
            }
        }
        if (!$isRecordShared || !$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
    }

    public function preProcess(Vtiger_Request $request, $display = true)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $recordId = $request->get('record');
        $this->record = $detailViewModel = VReports_DetailView_Model::getInstance($moduleName, $recordId);
        $reportModel = $detailViewModel->getRecord();
        $viewer->assign('REPORT_NAME', $reportModel->getName());
        parent::preProcess($request);
        $reportModel->setModule('VReports');
        $primaryModule = $reportModel->getPrimaryModule();
        $secondaryModules = $reportModel->getSecondaryModules();
        $primaryModuleModel = Vtiger_Module_Model::getInstance($primaryModule);
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userPrivilegesModel = Users_Privileges_Model::getInstanceById($currentUser->getId());
        $permission = $userPrivilegesModel->hasModulePermission($primaryModuleModel->getId());
        if (!$permission) {
            $viewer->assign('MODULE', $primaryModule);
            $viewer->assign('MESSAGE', vtranslate('LBL_PERMISSION_DENIED'));
            $viewer->view('OperationNotPermitted.tpl', $primaryModule);
            exit;
        }
        $viewer->assign('SELECTED_ADVANCED_FILTER_FIELDS', $reportModel->transformToNewAdvancedFilter());
        $viewer->assign('PRIMARY_MODULE', $primaryModule);
        $viewer->assign('SECONDARY_MODULES', $reportModel->getSecondaryModules());
        $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($reportModel);
        $primaryModuleRecordStructure = $recordStructureInstance->getPrimaryModuleRecordStructure();
        $secondaryModuleRecordStructures = $recordStructureInstance->getSecondaryModuleRecordStructure();
        $viewer->assign('PRIMARY_MODULE_RECORD_STRUCTURE', $primaryModuleRecordStructure);
        $viewer->assign('SECONDARY_MODULE_RECORD_STRUCTURES', $secondaryModuleRecordStructures);
        $secondaryModuleIsCalendar = strpos($secondaryModules, 'Calendar');
        if ($primaryModule == 'Calendar' || $secondaryModuleIsCalendar !== false) {
            $advanceFilterOpsByFieldType = Calendar_Field_Model::getAdvancedFilterOpsByFieldType();
        } else {
            $advanceFilterOpsByFieldType = Vtiger_Field_Model::getAdvancedFilterOpsByFieldType();
        }
        $viewer->assign('ADVANCED_FILTER_OPTIONS', Vtiger_Field_Model::getAdvancedFilterOptions());
        $viewer->assign('ADVANCED_FILTER_OPTIONS_BY_TYPE', $advanceFilterOpsByFieldType);
        $dateFilters = Vtiger_Field_Model::getDateFilterTypes();
        foreach ($dateFilters as $comparatorKey => $comparatorInfo) {
            $comparatorInfo['startdate'] = DateTimeField::convertToUserFormat($comparatorInfo['startdate']);
            $comparatorInfo['enddate'] = DateTimeField::convertToUserFormat($comparatorInfo['enddate']);
            $comparatorInfo['label'] = vtranslate($comparatorInfo['label'], $moduleName);
            $dateFilters[$comparatorKey] = $comparatorInfo;
        }
        $reportPivotModel = VReports_Pivot_Model::getInstanceById($reportModel);
        $renameFields = $reportPivotModel->getRenameField($recordId);
        $viewer->assign('PRIMARY_MODULE_FIELDS', $reportModel->getPrimaryModuleFieldsForAdvancedReporting());
        $viewer->assign('SECONDARY_MODULE_FIELDS', $reportModel->getSecondaryModuleFieldsForAdvancedReporting());
        $viewer->assign('CALCULATION_FIELDS', $reportModel->getModuleCalculationFieldsForReport());
        $viewer->assign('SORT_BY', $reportPivotModel->sort);
        $viewer->assign('LIMIT', $reportPivotModel->limit);
        $viewer->assign('ORDER_BY', $reportPivotModel->order);
        $viewer->assign('DATE_FILTERS', $dateFilters);
        $viewer->assign('DETAILVIEW_ACTIONS', $detailViewModel->getDetailViewActions());
        $viewer->assign('DETAILVIEW_LINKS', $detailViewModel->getDetailViewLinks());
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('IS_ADMIN', $currentUser->isAdminUser());
        $viewer->assign('RECORD', $recordId);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('PIVOT_MODEL', $reportPivotModel);
        $viewer->assign('RENAME_FIELDS', $renameFields);
        $dashBoardModel = new VReports_DashBoard_Model();
        $activeTabs = $dashBoardModel->getActiveTabs();
        foreach ($activeTabs as $index => $tabInfo) {
            if (!empty($tabInfo['appname'])) {
                unset($activeTabs[$index]);
            }
        }
        $viewer->assign('DASHBOARD_TABS', $activeTabs);
        $viewer->view('PivotReportHeader.tpl', $moduleName);
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
        global $site_URL;
        global $current_user;
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $reportModel = VReports_Record_Model::getInstanceById($record);
        $reportPivotModel = VReports_Pivot_Model::getInstanceById($reportModel);
        $reportChartModel = VReports_Chart_Model::getInstanceById($reportModel);
        $secondaryModules = $reportModel->getSecondaryModules();
        if (empty($secondaryModules)) {
            $viewer->assign('CLICK_THROUGH', true);
        }
        $viewer->assign('ADVANCED_FILTERS', $request->get('advanced_filter'));
        $viewer->assign('PRIMARY_MODULE_FIELDS', $reportModel->getPrimaryModuleFields());
        $viewer->assign('SECONDARY_MODULE_FIELDS', $reportModel->getSecondaryModuleFields());
        $viewer->assign('CALCULATION_FIELDS', $reportModel->getModuleCalculationFieldsForReport());
        $data = $reportPivotModel->getData();
        $tempVal = $reportChartModel->get('datafields');
        if ($tempVal[0] != 'null' && $reportChartModel->get('type')) {
            $reportChartModel->set('datafields', $reportChartModel->get('datafields-chart'));
            $dataChart = $reportChartModel->getData();
            $isPercentExist = false;
            $selectedDataFields = $reportChartModel->get('datafields');
            foreach ($selectedDataFields as $dataField) {
                [$tableName, $columnName, $moduleField, $fieldName, $single] = explode(':', $dataField);
                [$relModuleName, $fieldLabel] = explode('_', $moduleField);
                $relModuleModel = Vtiger_Module_Model::getInstance($relModuleName);
                $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $relModuleModel);
                if ($fieldModel && $fieldModel->getFieldDataType() != 'currency') {
                    $isPercentExist = true;
                    break;
                }
                if (!$fieldModel) {
                    $isPercentExist = true;
                }
            }
            $yAxisFieldDataType = !$isPercentExist ? 'currency' : '';
            $viewer->assign('YAXIS_FIELD_TYPE', $yAxisFieldDataType);
            if ($dataChart) {
                $dataChartCheck = true;
            } else {
                $dataChartCheck = false;
            }
            $viewer->assign('CHART_TYPE', $reportChartModel->getChartType());
            $viewer->assign('DATA_CHART', $dataChart);
            $viewer->assign('DATA_CHART_CHECK', $dataChartCheck);
        }
        $viewer->assign('DATA', $data);
        $viewer->assign('RECORD_ID', $record);
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('SECONDARY_MODULES', $secondaryModules);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('SITE_URL', $site_URL);
        $viewer->assign('CURRENT_USER', $current_user);
        $viewer->view('PivotReportContents.tpl', $moduleName);
    }

    /**
     * Function to get the list of Script models to be included.
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = ['modules.Vtiger.resources.Detail', 'modules.' . $moduleName . '.resources.Detail', 'modules.' . $moduleName . '.resources.Edit', 'modules.' . $moduleName . '.resources.Edit1', 'modules.' . $moduleName . '.resources.Edit3', 'modules.' . $moduleName . '.resources.PivotEdit', 'modules.' . $moduleName . '.resources.PivotEdit2', 'modules.' . $moduleName . '.resources.PivotEdit3', 'modules.' . $moduleName . '.resources.PivotDetail', 'modules.' . $moduleName . '.resources.ChartDetail'];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = ['~/layouts/v7/modules/VReports/resources/jbPivot/jbPivot.css', '~/layouts/v7/modules/VReports/resources/exportPivot/orb.css', '~layouts/v7/modules/VReports/resources/styleVReport.css'];
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);

        return $headerCssInstances;
    }
}
