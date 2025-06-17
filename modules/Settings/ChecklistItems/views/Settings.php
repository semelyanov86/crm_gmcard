<?php

ini_set("display_errors", "0");

class Settings_ChecklistItems_Settings_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request);
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
        $viewer->assign("VTELICENSE", $vTELicense);
        $viewer->assign("SITE_URL", $site_URL);
        $viewer->view("Step2.tpl", $module);
    }
    public function step3(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view("Step3.tpl", $module);
    }
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $settingModel = new Settings_ChecklistItems_Settings_Model();
        $permissions = $settingModel->getPermissions();
        if ($permissions) {
            return true;
        }
        if (!$currentUserModel->isAdminUser()) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED", "Vtiger"));
        }
    }
    public function renderSettingsUI(Vtiger_Request $request)
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $qualifiedModuleName = $request->getModule(false);
        $ajax = $request->get("ajax");
        $viewer = $this->getViewer($request);
        $settingModel = new Settings_ChecklistItems_Settings_Model();
        $entities = $settingModel->getData();
        $permissions = $settingModel->getPermissions();
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModuleName);
        $viewer->assign("ENTITIES", $entities);
        $viewer->assign("COUNT_ENTITY", count($entities));
        $viewer->assign("CURRENT_USER", $currentUserModel);
        $viewer->assign("USER_PERMISSION", $permissions);
        if ($ajax) {
            $viewer->view("SettingsAjax.tpl", $qualifiedModuleName);
        } else {
            $viewer->view("Settings.tpl", $qualifiedModuleName);
        }
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
        $jsFileNames = array("modules.Vtiger.resources.Vtiger", "modules.Settings.Vtiger.resources.Vtiger", "modules.Settings.Vtiger.resources.Edit", "modules.Settings." . $moduleName . ".resources.Settings", "~libraries/jquery/ckeditor/ckeditor.js", "modules.Vtiger.resources.CkEditor", "~layouts/v7/lib/jquery/webui-popover/dist/jquery.webui-popover.min.js");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = array("~/layouts/v7/modules/Settings/ChecklistItems/resources/ChecklistItems.css", "~/layouts/v7/lib/jquery/webui-popover/dist/jquery.webui-popover.min.css");
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);
        return $headerCssInstances;
    }
}

?>