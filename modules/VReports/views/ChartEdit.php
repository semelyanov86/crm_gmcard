<?php

class VReports_ChartEdit_View extends Vtiger_Edit_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('step1');
        $this->exposeMethod('step2');
        $this->exposeMethod('step3');
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        $record = $request->get('record');
        if ($record) {
            $reportModel = VReports_Record_Model::getCleanInstance($record);
            if (!$reportModel->isEditable()) {
                throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
            }
        }
    }

    public function preProcess(Vtiger_Request $request, $display = true)
    {
        $viewer = $this->getViewer($request);
        $record = $request->get('record');
        $moduleName = $request->getModule();
        $reportModel = VReports_Record_Model::getCleanInstance($record);
        $primaryModule = $reportModel->getPrimaryModule();
        $primaryModuleModel = Vtiger_Module_Model::getInstance($primaryModule);
        if ($primaryModuleModel) {
            $currentUser = Users_Record_Model::getCurrentUserModel();
            $userPrivilegesModel = Users_Privileges_Model::getInstanceById($currentUser->getId());
            $permission = $userPrivilegesModel->hasModulePermission($primaryModuleModel->getId());
            if (!$permission) {
                $viewer->assign('MODULE', $primaryModule);
                $viewer->assign('MESSAGE', vtranslate('LBL_PERMISSION_DENIED'));
                $viewer->view('OperationNotPermitted.tpl', $primaryModule);
                exit;
            }
        }
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('RECORD_ID', $record);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('VIEW', 'ChartEdit');
        $viewer->assign('REPORT_TYPE', 'ChartEdit');
        $viewer->assign('RECORD_MODE', $request->getMode());
        parent::preProcess($request);
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        if (!empty($mode)) {
            echo $this->invokeExposedMethod($mode, $request);
            exit;
        }
        $this->step1($request);
    }

    public function step1(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $reportModel = VReports_Record_Model::getCleanInstance($record);
        if (!$reportModel->has('folderid')) {
            $reportModel->set('folderid', $request->get('folder'));
        }
        $data = $request->getAll();
        foreach ($data as $name => $value) {
            $reportModel->set($name, $value);
        }
        $modulesList = $reportModel->getModulesList();
        if (!empty($record)) {
            $viewer->assign('MODE', 'edit');
        } else {
            $firstModuleName = '';
            if ($firstModuleName) {
                $reportModel->setPrimaryModule($firstModuleName);
            }
            $viewer->assign('MODE', '');
        }
        $reportModuleModel = $reportModel->getModule();
        $reportFolderModels = $reportModuleModel->getFolders();
        $relatedModules = $reportModel->getVReportRelatedModules();
        foreach ($relatedModules as $primaryModule => $relatedModuleList) {
            $translatedRelatedModules = [];
            foreach ($relatedModuleList as $relatedModuleName) {
                $translatedRelatedModules[$relatedModuleName] = htmlentities(vtranslate($relatedModuleName, $relatedModuleName), ENT_QUOTES);
            }
            $relatedModules[$primaryModule] = $translatedRelatedModules;
        }
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $viewer->assign('SCHEDULEDREPORTS', $reportModel->getScheduledVReport());
        $viewer->assign('MODULELIST', $modulesList);
        $viewer->assign('RELATED_MODULES', $relatedModules);
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('REPORT_FOLDERS', $reportFolderModels);
        $viewer->assign('RECORD_ID', $record);
        $viewer->assign('CURRENT_USER', $currentUserModel);
        $viewer->assign('ROLES', Settings_Roles_Record_Model::getAll());
        $admin = Users::getActiveAdminUser();
        $viewer->assign('ACTIVE_ADMIN', $admin);
        $viewer->assign('TYPE', 'Chart');
        $viewer->assign('REPORT_TYPE', $request->get('view'));
        $sharedMembers = $reportModel->getMembers();
        $viewer->assign('SELECTED_MEMBERS_GROUP', $sharedMembers);
        $viewer->assign('MEMBER_GROUPS', Settings_Groups_Member_Model::getAll());
        if (!$record) {
            $shareAll = 1;
        } else {
            if ($reportModel->get('is_shareall')) {
                $shareAll = $reportModel->get('is_shareall');
            } else {
                $shareAll = 0;
            }
        }
        $viewer->assign('SELECTED_MEMBERS_SHARE_ALL', $shareAll);
        if ($request->get('isDuplicate')) {
            $viewer->assign('IS_DUPLICATE', true);
        }
        $viewer->view('ChartEditStep1.tpl', $moduleName);
    }

    public function step2(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $reportModel = VReports_Record_Model::getCleanInstance($record);
        if (!empty($record)) {
            $viewer->assign('SELECTED_STANDARD_FILTER_FIELDS', $reportModel->getSelectedStandardFilter());
            $viewer->assign('SELECTED_ADVANCED_FILTER_FIELDS', $reportModel->transformToNewAdvancedFilter());
        }
        $data = $request->getAll();
        foreach ($data as $name => $value) {
            if (($name == 'schdayoftheweek' || $name == 'schdayofthemonth' || $name == 'schannualdates' || $name == 'recipients') && is_string($value)) {
                $value = [$value];
            }
            if ($name == 'reportname') {
                $value = htmlentities($value);
            }
            $reportModel->set($name, $value);
        }
        $primaryModule = $request->get('primary_module');
        $secondaryModules = $request->get('secondary_modules');
        $reportModel->setPrimaryModule($primaryModule);
        if (!empty($secondaryModules)) {
            $secondaryModules = implode(':', $secondaryModules);
            $reportModel->setSecondaryModule($secondaryModules);
            $secondaryModules = explode(':', $secondaryModules);
        } else {
            $secondaryModules = [];
        }
        $viewer->assign('RECORD_ID', $record);
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('PRIMARY_MODULE', $primaryModule);
        $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($reportModel);
        $primaryModuleRecordStructure = $recordStructureInstance->getPrimaryModuleRecordStructure();
        if ($secondaryModules) {
            $secondaryModuleRecordStructures = $recordStructureInstance->getSecondaryModuleRecordStructure();
        }
        $viewer->assign('SECONDARY_MODULES', $secondaryModules);
        $viewer->assign('PRIMARY_MODULE_RECORD_STRUCTURE', $primaryModuleRecordStructure);
        $viewer->assign('SECONDARY_MODULE_RECORD_STRUCTURES', $secondaryModuleRecordStructures);
        $dateFilters = Vtiger_Field_Model::getDateFilterTypes();
        foreach ($dateFilters as $comparatorKey => $comparatorInfo) {
            $comparatorInfo['startdate'] = DateTimeField::convertToUserFormat($comparatorInfo['startdate']);
            $comparatorInfo['enddate'] = DateTimeField::convertToUserFormat($comparatorInfo['enddate']);
            $comparatorInfo['label'] = vtranslate($comparatorInfo['label'], $moduleName);
            $dateFilters[$comparatorKey] = $comparatorInfo;
        }
        $viewer->assign('DATE_FILTERS', $dateFilters);
        if ($primaryModule == 'Calendar' || in_array('Calendar', $secondaryModules)) {
            $advanceFilterOpsByFieldType = Calendar_Field_Model::getAdvancedFilterOpsByFieldType();
        } else {
            $advanceFilterOpsByFieldType = Vtiger_Field_Model::getAdvancedFilterOpsByFieldType();
        }
        $viewer->assign('ADVANCED_FILTER_OPTIONS', Vtiger_Field_Model::getAdvancedFilterOptions());
        $viewer->assign('ADVANCED_FILTER_OPTIONS_BY_TYPE', $advanceFilterOpsByFieldType);
        $viewer->assign('MODULE', $moduleName);
        $calculationFields = $reportModel->get('calculation_fields');
        if ($calculationFields) {
            $calculationFields = Zend_Json::decode($calculationFields);
            $viewer->assign('LINEITEM_FIELD_IN_CALCULATION', $reportModel->showLineItemFieldsInFilter($calculationFields));
        }
        if ($request->get('isDuplicate')) {
            $viewer->assign('IS_DUPLICATE', true);
        }
        $viewer->view('ChartEditStep2.tpl', $moduleName);
    }

    public function step3(Vtiger_request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $reportModel = VReports_Record_Model::getCleanInstance($record);
        if (!empty($record)) {
            $viewer->assign('SELECTED_STANDARD_FILTER_FIELDS', $reportModel->getSelectedStandardFilter());
            $viewer->assign('SELECTED_ADVANCED_FILTER_FIELDS', $reportModel->transformToNewAdvancedFilter());
        }
        $data = $request->getAll();
        foreach ($data as $name => $value) {
            if ($name == 'schdayoftheweek' || $name == 'schdayofthemonth' || $name == 'schannualdates' || $name == 'recipients' || $name == 'members') {
                $value = Zend_Json::decode($value);
                if (!is_array($value)) {
                    $value = [$value];
                }
            }
            if ($name == 'reportname') {
                $value = htmlentities($value);
            }
            $reportModel->set($name, $value);
        }
        $primaryModule = $request->get('primary_module');
        $secondaryModules = $request->get('secondary_modules');
        $reportModel->setPrimaryModule($primaryModule);
        if (!empty($secondaryModules)) {
            $secondaryModules = implode(':', $secondaryModules);
            $reportModel->setSecondaryModule($secondaryModules);
            $secondaryModules = explode(':', $secondaryModules);
        } else {
            $secondaryModules = [];
            $reportModel->setSecondaryModule('');
        }
        $chartModel = VReports_Chart_Model::getInstanceById($reportModel);
        $viewer->assign('CHART_MODEL', $chartModel);
        $viewer->assign('ADVANCED_FILTERS', $request->get('advanced_filter'));
        $viewer->assign('PRIMARY_MODULE_FIELDS', $reportModel->getPrimaryModuleFieldsForAdvancedReporting());
        $viewer->assign('SECONDARY_MODULE_FIELDS', $reportModel->getSecondaryModuleFieldsForAdvancedReporting());
        $viewer->assign('CALCULATION_FIELDS', $reportModel->getModuleCalculationFieldsForReport());
        if ($request->get('isDuplicate')) {
            $viewer->assign('IS_DUPLICATE', true);
        }
        $viewer->assign('RECORD_ID', $record);
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('PRIMARY_MODULE', $primaryModule);
        $viewer->assign('SECONDARY_MODULES', $secondaryModules);
        $viewer->assign('MODULE', $moduleName);
        $viewer->view('ChartEditStep3.tpl', $moduleName);
    }

    /**
     * Function to get the list of Script models to be included.
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = ['~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.js', 'modules.VReports.resources.Edit', 'modules.VReports.resources.Edit1', 'modules.VReports.resources.Edit2', 'modules.VReports.resources.Edit3', 'modules.' . $moduleName . '.resources.ChartEdit', 'modules.' . $moduleName . '.resources.ChartEdit1', 'modules.' . $moduleName . '.resources.ChartEdit2', 'modules.' . $moduleName . '.resources.ChartEdit3', 'modules.VReports.resources.CkEditor'];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $moduleName = $request->getModule();
        $cssFileNames = ['~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.css', '~layouts/v7/modules/VReports/resources/styleVReport.css'];
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($cssInstances, $headerCssInstances);

        return $headerCssInstances;
    }
}
