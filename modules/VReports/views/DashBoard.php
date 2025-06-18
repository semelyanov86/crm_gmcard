<?php

class VReports_DashBoard_View extends Vtiger_Index_View
{
    protected static $selectable_dashboards;

    public function __construct()
    {
        parent::__construct();
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        if (!Users_Privileges_Model::isPermitted($moduleName, 'index')) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
    }

    public function preProcess(Vtiger_Request $request, $display = true)
    {
        global $site_URL;
        $site_URL = VReports_Util_Helper::reFormatSiteUrl($site_URL);
        parent::preProcess($request, false);
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        $moduleModel = Vtiger_Module_Model::getInstance('VReports');
        $userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());
        if ($permission) {
            $boardid = false;
            if (!$dashBoardModel->checkTabExist(0, 0, 'check')) {
                $dashBoardModel->addTabDefault();
            }
            if (!$request->get('boardid')) {
                $data = $dashBoardModel->loadDefaultBoard();
                $boardid = $data['boardid'];
                $tabid = $data['tabid'];
                if ($boardid && $tabid) {
                    $url = $site_URL . '/index.php?module=VReports&view=DashBoard&boardid=' . $boardid . '&tabid=' . $tabid;
                    $url = VReports_Util_Helper::reFormatSiteUrl($url);
                    header('Location: ' . $url);
                }
            }
            if ($request->get('boardid')) {
                $boardid = $request->get('boardid');
            }
            $dashboardTabs = $dashBoardModel->getActiveTabs($boardid);
            $dashboardBoards = $dashBoardModel->getAllBoards($mode = 'getAll');
            if ($request->get('tabid')) {
                $tabid = $request->get('tabid');
            } else {
                $tabid = $dashboardTabs[0]['id'];
            }
            $orgDynamicFilter = $dashBoardModel->getAccountDynamicFilter($tabid);
            if (!isset($_REQUEST['organization']) && $orgDynamicFilter != '') {
                if (!isset($_REQUEST['module'])) {
                    $url = $site_URL . '/index.php?module=VReports&view=DashBoard&organization=' . $orgDynamicFilter;
                } else {
                    $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
                    $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '&organization=' . $orgDynamicFilter;
                }
                $url = VReports_Util_Helper::reFormatSiteUrl($url);
                header('Location: ' . $url);
            }
            $dashBoardModel->set('tabid', $tabid);
        }
        $viewer->assign('MODULE_PERMISSION', $permission);
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('DASHBOARD_BOARDS', $dashboardBoards);
        $viewer->assign('BOARDID', $boardid);
        if ($display) {
            $this->preProcessDisplay($request);
        }
    }

    public function preProcessTplName(Vtiger_Request $request)
    {
        return 'dashboards/DashBoardPreProcess.tpl';
    }

    public function process(Vtiger_Request $request)
    {
        global $current_user;
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());
        if ($permission) {
            $boardid = 1;
            if ($request->get('boardid') && $dashBoardModel->getAllBoards($mode = 'getExist', $request->get('boardid'))) {
                $boardid = $request->get('boardid');
                $viewer->assign('IS_SHARED', $dashBoardModel->checkBoardIsShared($boardid));
            }
            $dashboardTabs = $dashBoardModel->getActiveTabs($boardid);
            if ($request->get('tabid')) {
                $tabid = $request->get('tabid');
            } else {
                $tabid = $dashboardTabs[0]['id'];
            }
            $dashBoardModel->set('tabid', $tabid);
            $widgets = $dashBoardModel->getDashboards($moduleName);
            $notificationDynamic = $dashBoardModel->checkNotificationDynamic($tabid);
            $viewer->assign('MODULE_NAME', $moduleName);
            $viewer->assign('WIDGETS', $widgets);
            $viewer->assign('NOTIFICATION_DYNAMIC', $notificationDynamic);
            $viewer->assign('DASHBOARD_TABS', $dashboardTabs);
            $viewer->assign('DASHBOARD_TABS_LIMIT', $dashBoardModel->dashboardTabLimit);
            $viewer->assign('SELECTED_TAB', $tabid);
            $viewer->assign('SELECTED_BOARD', $boardid);
            $viewer->assign('CURRENT_USER', Users_Record_Model::getCurrentUserModel());
            $viewer->assign('TABID', $tabid);
            $viewer->view('dashboards/DashBoardContents.tpl', $moduleName);
        } else {
            return null;
        }
    }

    public function postProcess(Vtiger_Request $request)
    {
        parent::postProcess($request);
    }

    /**
     * Function to get the list of Script models to be included.
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = ['~/layouts/v7/modules/VReports/resources/chartjs/Chart.bundle.min.js', '~/layouts/v7/modules/VReports/resources/chartjs/Chart.BarFunnel.min.js', '~/layouts/v7/modules/VReports/resources/chartjs/Chart.Funnel.bundle.min.js', '~/layouts/v7/modules/VReports/resources/chartjs/utils.js', '~/layouts/v7/modules/VReports/resources/chartjs/chartjs-piecelabel.js', '~/layouts/v7/modules/VReports/resources/gridstack/lodash.min.js', '~/layouts/v7/modules/VReports/resources/gridstack/gridstack.min.js', '~/layouts/v7/modules/VReports/resources/gridstack/gridstack.jQueryUI.min.js', '~/layouts/v7/modules/VReports/resources/jbPivot/jbPivot.min.js', '~/layouts/v7/modules/VReports/resources/VReportsDashBoard.js', '~/layouts/v7/modules/VReports/resources/VReportsButtonDashBoard.js', '~/layouts/v7/modules/VReports/resources/perfect-scrollbar/js/perfect-scrollbar.jquery.js', '~/libraries/jquery/colorpicker/js/colorpicker.js'];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    /**
     * Function to get the list of Css models to be included.
     * @return <Array> - List of Vtiger_CssScript_Model instances
     */
    public function getHeaderCss(Vtiger_Request $request)
    {
        $parentHeaderCssScriptInstances = parent::getHeaderCss($request);
        $headerCss = ['~/layouts/v7/modules/VReports/resources/gridstack/gridstack.min.css', '~/layouts/v7/modules/VReports/resources/gridstack/gridstack-extra.min.css', '~/layouts/v7/modules/VReports/resources/jbPivot/jbPivot.css', '~/layouts/v7/modules/VReports/resources/StyleDashboard.css', '~libraries/jquery/colorpicker/css/colorpicker.css', '~/layouts/v7/modules/VReports/resources/perfect-scrollbar/css/perfect-scrollbar.css'];
        $cssScripts = $this->checkAndConvertCssStyles($headerCss);
        $headerCssScriptInstances = array_merge($parentHeaderCssScriptInstances, $cssScripts);

        return $headerCssScriptInstances;
    }
}
