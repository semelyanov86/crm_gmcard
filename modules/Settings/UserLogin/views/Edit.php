<?php

class Settings_UserLogin_Edit_View extends Settings_Vtiger_Index_View
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
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $qualifiedModuleName = $request->getModule(false);
        $record = $request->get("record", 0);
        $viewer = $this->getViewer($request);
        $settingModel = new Settings_UserLogin_Settings_Model();
        $entity = $settingModel->getDataDetails($record);
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModuleName);
        $viewer->assign("MODULE_NAME", $moduleName);
        $viewer->assign("ENTITY", $entity);
        $viewer->assign("RECORD_ID", $record);
        $viewer->assign("CURRENT_USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $viewer->view("Edit.tpl", $qualifiedModuleName);
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
        $jsFileNames = array("modules.Vtiger.resources.Vtiger", "modules.Settings.Vtiger.resources.Vtiger", "modules.Settings.Vtiger.resources.Edit", "modules.Settings." . $moduleName . ".resources.Edit", "modules.Settings." . $moduleName . ".resources.jquery_form");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = array("~/layouts/vlayout/modules/Settings/UserLogin/resources/UserLogin.css");
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);
        return $headerCssInstances;
    }
}

?>