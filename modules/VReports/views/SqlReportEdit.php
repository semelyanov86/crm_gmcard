<?php

class VReports_SqlReportEdit_View extends Vtiger_Edit_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('step1');
        $this->exposeMethod('step2');
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $userName = $currentUserPriviligesModel->user_name;
        if ($userName != 'admin') {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
    }

    public function preProcess(Vtiger_Request $request, $display = true)
    {
        $viewer = $this->getViewer($request);
        $record = $request->get('record');
        $moduleName = $request->getModule();
        $reportModuleModel = Vtiger_Module_Model::getInstance($moduleName);
        $folders = $reportModuleModel->getFolders();
        $reportModel = VReports_Record_Model::getCleanInstance($record);
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('RECORD_ID', $record);
        $viewer->assign('VIEW', 'Edit');
        $viewer->assign('RECORD_MODE', $request->getMode());
        $viewer->assign('QUALIFIED_MODULE', $moduleName);
        $viewer->assign('FOLDERS', $folders);
        $viewer->assign('REPORT_TYPE', $request->get('view'));
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
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('CURRENT_USER', $currentUserModel);
        $viewer->assign('ROLES', Settings_Roles_Record_Model::getAll());
        $admin = Users::getActiveAdminUser();
        $viewer->assign('ACTIVE_ADMIN', $admin);
        $viewer->assign('TYPE', 'sql');
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
        $viewer->view('Step1.tpl', $moduleName);
    }

    public function step2(Vtiger_request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $reportModel = VReports_Record_Model::getCleanInstance($record);
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
        $viewer->assign('RECORD_ID', $record);
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('CALCULATION_FIELDS', $reportModel->getCalculationFields());
        $viewer->assign('MODULE', $moduleName);
        if ($request->get('isDuplicate')) {
            $viewer->assign('IS_DUPLICATE', true);
        }
        $viewer->view('SqlReportStep2.tpl', $moduleName);
    }

    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = ['modules.VReports.resources.Edit', 'modules.VReports.resources.Edit1', 'modules.VReports.resources.Edit2', 'modules.' . $moduleName . '.resources.SqlReportEdit', 'modules.' . $moduleName . '.resources.SqlReportEdit1', 'modules.' . $moduleName . '.resources.SqlReportEdit2', '~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.js', 'modules.VReports.resources.CkEditor'];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = ['~libraries/jquery/jquery.datepick.package-4.1.0/jquery.datepick.css', '~layouts/v7/modules/VReports/resources/styleVReport.css'];
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($cssInstances, $headerCssInstances);

        return $headerCssInstances;
    }
}
