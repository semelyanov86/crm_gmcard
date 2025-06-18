<?php

class VReports_DashboardTab_View extends Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('showDashBoardAddTabForm');
        $this->exposeMethod('showBoardEditForm');
        $this->exposeMethod('getTabContents');
        $this->exposeMethod('showDashBoardTabList');
        $this->exposeMethod('showDynamicFiltersEditForm');
        $this->exposeMethod('showRenameTabForm');
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        if (!empty($mode)) {
            echo $this->invokeExposedMethod($mode, $request);
        }
    }

    public function showDashBoardAddTabForm($request)
    {
        $moduleName = $request->getModule();
        $dashBoardModel = new VReports_DashBoard_Model();
        $dashBoardBoards = $dashBoardModel->getBoardsByUser();
        $viewer = $this->getViewer($request);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('BOARDID', $request->get('boardid'));
        $viewer->assign('TYPE_ACTION', $request->get('type'));
        $viewer->assign('DASHBOARD_BOARD', $dashBoardBoards);
        if ($request->get('tabName')) {
            $viewer->assign('TAB_NAME', $request->get('tabName'));
        }
        echo $viewer->view('AddDashBoardTabForm.tpl', $moduleName, true);
    }

    public function showBoardEditForm($request)
    {
        global $current_user;
        $moduleName = $request->getModule();
        $dashBoardModel = new VReports_DashBoard_Model();
        $viewer = $this->getViewer($request);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('ALL_BOARDS', $dashBoardModel->getBoardsByUser());
        $viewer->assign('IS_ADMIN', $current_user->is_admin);
        $viewer->assign('MEMBER_GROUPS', Settings_Groups_Member_Model::getAll());
        if ($request->get('viewmode') == 'addnew') {
            $viewer->assign('EDITVIEW', true);
        } else {
            if ($request->get('viewmode') == 'delete') {
                $viewer->assign('DELETE', true);
            }
        }
        echo $viewer->view('EditBoardForm.tpl', $moduleName, true);
    }

    public function showDynamicFiltersEditForm($request)
    {
        global $adb;
        $moduleName = $request->getModule();
        $dashboardId = $request->get('dashboardId');
        $result = $adb->pquery("SELECT vtiger_crmentity.label as 'dynamic_filter_account_display', \r\n                                        dynamic_filter_account, \r\n                                        vtiger_crmentity.deleted AS 'record_deleted',\r\n                                        dynamic_filter_assignedto, \r\n                                        dynamic_filter_date,dynamic_filter_type_date ,dynamic_filter_createdby\r\n                                        FROM vtiger_vreportdashboard_tabs \r\n                                        LEFT JOIN vtiger_crmentity ON vtiger_vreportdashboard_tabs.dynamic_filter_account = vtiger_crmentity.crmid\r\n                                        WHERE id = ? \r\n                                        AND (dynamic_filter_account IS NOT NULL OR dynamic_filter_assignedto IS NOT NULL)\r\n                                         LIMIT 1", [$dashboardId]);
        $recordData = [];

        while ($rowData = $adb->fetchByAssoc($result)) {
            if ($rowData['record_deleted'] == '0') {
                $recordData['dynamic_filter_account'] = $rowData['dynamic_filter_account'];
                $recordData['dynamic_filter_account_display'] = $rowData['dynamic_filter_account_display'];
            } else {
                if ($rowData['record_deleted'] == '1') {
                    $recordData['dynamic_filter_account_display'] = 'Record has been deleted';
                }
            }
            $recordData['dynamic_filter_assignedto'] = $rowData['dynamic_filter_assignedto'];
            $recordData['dynamic_filter_date'] = $rowData['dynamic_filter_date'];
            $recordData['dynamic_filter_type_date'] = $rowData['dynamic_filter_type_date'];
            $recordData['dynamic_filter_createdby'] = $rowData['dynamic_filter_createdby'];
        }
        $dateFilters = VReports_Field_Model::getDateFilterTypes();
        foreach ($dateFilters as $comparatorKey => $comparatorInfo) {
            $comparatorInfo['startdate'] = DateTimeField::convertToUserFormat($comparatorInfo['startdate']);
            $comparatorInfo['enddate'] = DateTimeField::convertToUserFormat($comparatorInfo['enddate']);
            $comparatorInfo['label'] = vtranslate($comparatorInfo['label'], $moduleName);
            $dateFilters[$comparatorKey] = $comparatorInfo;
        }
        $currentModel = Users_Privileges_Model::getCurrentUserModel();
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $checkAccountUrl = explode('=', end(explode('&', $_SERVER['HTTP_REFERER'])));
        $checkAccountUrl = $checkAccountUrl[0];
        if ($checkAccountUrl == 'organization') {
            $accountId = explode('=', end(explode('&', $_SERVER['HTTP_REFERER'])));
            $accountId = $accountId[1];
            $queryGetAccount = $adb->pquery('SELECT * FROM `vtiger_account` WHERE accountid = ?', [$accountId]);
            $dynamicFilterAccount = $adb->query_result($queryGetAccount, 0, 'accountid');
            $dynamicFilterAccountLabel = $adb->query_result($queryGetAccount, 0, 'accountname');
            $viewer->assign('ACCOUNT_ID', $dynamicFilterAccount);
            $viewer->assign('ACCOUNT_DISPLAY', $dynamicFilterAccountLabel);
        }
        $viewer->assign('DATE_FILTERS', $dateFilters);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('USER_MODEL', $currentModel);
        $viewer->assign('RECORD_DATA', $recordData);
        $viewer->assign('DASHBOARD_ID', $dashboardId);
        echo $viewer->view('EditDynamicFilters.tpl', $moduleName, true);
    }

    public function showRenameTabForm($request)
    {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('TAB_ID', $request->get('tabid'));
        $viewer->assign('TAB_NAME', $request->get('tabname'));
        echo $viewer->view('RenameTab.tpl', $moduleName, true);
    }

    public function getTabContents($request)
    {
        $moduleName = $request->getModule();
        $tabId = $request->get('tabid');
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        $dashBoardModel->set('tabid', $tabId);
        $widgets = $dashBoardModel->getDashboards($moduleName);
        $selectableWidgets = $dashBoardModel->getSelectableDashboard();
        $dashBoardTabInfo = $dashBoardModel->getTabInfo($tabId);
        $notificationDynamic = $dashBoardModel->checkNotificationDynamic($tabId);
        $viewer = $this->getViewer($request);
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('NOTIFICATION_DYNAMIC', $notificationDynamic);
        $viewer->assign('WIDGETS', $widgets);
        $viewer->assign('SELECTABLE_WIDGETS', $selectableWidgets);
        $viewer->assign('TABID', $tabId);
        $viewer->assign('CURRENT_USER', Users_Record_Model::getCurrentUserModel());
        echo $viewer->view('dashboards/DashBoardTabContents.tpl', $moduleName, true);
    }

    public function showDashBoardTabList(Vtiger_Request $request)
    {
        $viewer = $this->getViwer($request);
        $moduleName = $this->getModule();
        $dashBoardModel = new Vtiger_DashBoard_Model();
        $dashBoardTabs = $dashBoardModel->getActiveTabs();
        $viewer->assign('DASHBOARD_TABS', $dashBoardTabs);
        $viewer->assign('MODULE', $moduleName);
        $viewer->view('DashBoardTabList.tpl', $moduleName);
    }
}
