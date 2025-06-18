<?php

class Settings_UserLogin_Settings_View extends Settings_Vtiger_Index_View
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
        global $vtiger_current_version;
        parent::preProcess($request, false);
        $viewer = $this->getViewer($request);
        $jsStrings = $this->getJSLanguageStrings($request);
        if (version_compare($vtiger_current_version, '7.0.0', '>=')) {
            $jsStrings = str_replace(['vlayout', 'Login.Custom.tpl'], ['v7', 'Login.tpl'], $jsStrings);
        }
        $viewer->assign('LANGUAGE_STRINGS', $jsStrings);
        $this->preProcessDisplay($request);
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
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

    public function step2(Vtiger_Request $request, $vTELicense)
    {
        global $site_URL;
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign('VTELICENSE', $vTELicense);
        $viewer->assign('SITE_URL', $site_URL);
        $viewer->view('Step2.tpl', $module);
    }

    public function step3(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view('Step3.tpl', $module);
    }

    private function renderSettingsUI(Vtiger_Request $request)
    {
        $qualifiedModuleName = $request->getModule(false);
        $viewer = $this->getViewer($request);
        $settingModel = new Settings_UserLogin_Settings_Model();
        $entities = $settingModel->getData();
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->assign('ENTITIES', $entities);
        $viewer->assign('COUNT_ENTITY', count($entities));
        $viewer->view('Settings.tpl', $qualifiedModuleName);
    }

    /**
     * Function to get the list of Script models to be included.
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = ['modules.Vtiger.resources.Vtiger', 'modules.Settings.Vtiger.resources.Vtiger', 'modules.Settings.Vtiger.resources.Edit', 'modules.Settings.' . $moduleName . '.resources.Settings'];
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $temp_folder = 'vlayout';
        } else {
            $temp_folder = 'v7';
        }
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = ['~/layouts/' . $temp_folder . '/modules/Settings/UserLogin/resources/UserLogin.css'];
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);

        return $headerCssInstances;
    }
}
