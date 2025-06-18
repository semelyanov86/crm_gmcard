<?php

class VReports_SqlReportDetail_View extends Vtiger_Index_View
{
    protected $reportData;

    protected $calculationFields;

    protected $count;

    protected $fieldsList;

    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('step1');
        $this->exposeMethod('step2');
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

    public function preProcess(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $recordId = $request->get('record');
        $sqlReportViewModel = VReports_DetailView_Model::getInstance($moduleName, $recordId);
        $reportModel = $sqlReportViewModel->getRecord();
        $viewer->assign('REPORT_NAME', $reportModel->getName());
        parent::preProcess($request);
        $page = $request->get('page');
        $reportModel->setModule('VReports');
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set('page', $page);
        $reportData = $reportModel->getReportData($pagingModel);
        $this->reportData = $reportData['data'];
        $this->fieldsList = $reportData['fields_list'];
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $detailViewLinks = $sqlReportViewModel->getDetailViewLinks();
        $viewer->assign('LINEITEM_FIELD_IN_CALCULATION', $reportModel->showLineItemFieldsInFilter(false));
        $viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);
        $viewer->assign('DETAILVIEW_ACTIONS', $sqlReportViewModel->getDetailViewActions());
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('IS_ADMIN', $currentUser->isAdminUser());
        $viewer->assign('RECORD_ID', $recordId);
        $viewer->assign('COUNT', $this->count);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('REPORT_TYPE', 'sql');
        $dashBoardModel = new VReports_DashBoard_Model();
        $activeTabs = $dashBoardModel->getActiveTabs();
        foreach ($activeTabs as $index => $tabInfo) {
            if (!empty($tabInfo['appname'])) {
                unset($activeTabs[$index]);
            }
        }
        $viewer->assign('DASHBOARD_TABS', $activeTabs);
        $viewer->view('ReportHeader.tpl', $moduleName);
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
        $record = $request->get('record');
        $page = $request->get('page');
        $data = $this->reportData;
        $fieldsList = $this->fieldsList;
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set('page', $page);
        if (empty($data)) {
            $reportModel = VReports_Record_Model::getInstanceById($record);
            $reportModel->setModule('VReports');
            $reportData = $reportModel->getReportData($pagingModel);
            $data = $reportData['data'];
        }
        $viewer->assign('DATA', $data);
        $viewer->assign('HEADER_FIELDS_LIST', $fieldsList);
        $viewer->assign('RECORD_ID', $record);
        $viewer->assign('PAGING_MODEL', $pagingModel);
        $viewer->assign('COUNT', $this->count);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('REPORT_RUN_INSTANCE', VReportRun::getInstance($record));
        $viewer->view('ReportContents.tpl', $moduleName);
    }

    /**
     * Function to get the list of Script models to be included.
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = ['modules.Vtiger.resources.Detail', 'modules.' . $moduleName . '.resources.SqlReportDetail', '~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.js'];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = ['~layouts/v7/modules/VReports/resources/styleVReport.css'];
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);

        return $headerCssInstances;
    }
}
