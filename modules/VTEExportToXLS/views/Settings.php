<?php

ini_set('display_errors', '0');

class VTEExportToXLS_Settings_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
    }

    public function preProcess(Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request);
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign('QUALIFIED_MODULE', $module);
    }

    public function process(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $adb = PearDatabase::getInstance();
        $mode = $request->getMode();
        if ($mode) {
            $this->{$mode}($request);
        } else {
            $this->renderSettingsUI($request);
        }
    }

    public function step2(Vtiger_Request $request)
    {
        global $site_URL;
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign('SITE_URL', $site_URL);
        $viewer->view('Step2.tpl', $module);
    }

    public function step3(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view('Step3.tpl', $module);
    }

    public function renderSettingsUI(Vtiger_Request $request)
    {
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $rs = $adb->pquery('SELECT `enable`,custom_filename, file_name, download_to_server FROM `vteexport_to_xls_settings`;', []);
        $enable = $adb->query_result($rs, 0, 'enable');
        $custom_filename = $adb->query_result($rs, 0, 'custom_filename');
        $file_name = $adb->query_result($rs, 0, 'file_name');
        $download_to_server = $adb->query_result($rs, 0, 'download_to_server');
        $viewer->assign('ENABLE', $enable);
        $viewer->assign('CUSTOM_FILENAME', $custom_filename);
        $viewer->assign('FILE_NAME', $file_name);
        $viewer->assign('DOWNLOAD_TO_SERVER', $download_to_server);
        $viewer->assign('LBL_DOWNLOAD_TOOLTIP', vtranslate('LBL_DOWNLOAD_TOOLTIP', $module));
        echo $viewer->view('Settings.tpl', $module, true);
    }

    /**
     * Function to get the list of Script models to be included.
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = ['~/libraries/jquery/bootstrapswitch/js/bootstrap-switch.min.js', 'modules.VTEExportToXLS.resources.Settings'];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = ['~/libraries/jquery/bootstrapswitch/css/bootstrap3/bootstrap-switch.min.css'];
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);

        return $headerCssInstances;
    }
}
