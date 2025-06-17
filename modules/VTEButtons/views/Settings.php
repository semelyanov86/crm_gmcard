<?php

class VTEButtons_Settings_View extends Settings_Vtiger_Index_View
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
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request);
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign("QUALIFIED_MODULE", $module);
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
        $viewer->assign("SITE_URL", $site_URL);
        $viewer->view("Step2.tpl", $module);
    }
    public function step3(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view("Step3.tpl", $module);
    }
    public function renderSettingsUI(Vtiger_Request $request)
    {
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $module_model = Vtiger_Module_Model::getInstance($module);
        $listViewEntries = $module_model->getlistViewEntries();
        $viewer = $this->getViewer($request);
        $rs = $adb->pquery("SELECT `active` FROM `vte_buttons_settings`;", array());
        $enable = $adb->query_result($rs, 0, "active");
        $viewer->assign("ENABLE", $enable);
        $viewer->assign("MODULE", $module);
        $viewer->assign("MODULE_MODEL", $module_model);
        $viewer->assign("LISTVIEW_ENTRIES", $listViewEntries);
        $moduleModels = Vtiger_Module_Model::getEntityModules();
        $entityModuleModels = array();
        $record_numb = array();
        $module_numbs = 0;
        foreach ($moduleModels as $tabId => $moduleModel) {
            $entityModuleModels[$tabId] = $moduleModel;
            $moduleName = $moduleModel->name;
            $re = $adb->pquery("SELECT * FROM `vte_buttons_settings` WHERE module='" . $moduleName . "'AND active='1';", array());
            $count_of_record = $adb->num_rows($re);
            $record_numb[$moduleName] = $count_of_record;
            $module_numb = $module_numb + $count_of_record;
        }
        $viewer->assign("ALL_MODULES", $entityModuleModels);
        $viewer->assign("record_numb", $record_numb);
        $viewer->assign("MODULE_NUMB", $module_numb);
        echo $viewer->view("Settings.tpl", $module, true);
    }
    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = array("~layouts/v7/modules/VTEButtons/resources/style.css", "~/libraries/jquery/bootstrapswitch/css/bootstrap3/bootstrap-switch.min.css");
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);
        return $headerCssInstances;
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
        $jsFileNames = array("modules.VTEButtons.resources.Settings", "~/libraries/jquery/bootstrapswitch/js/bootstrap-switch.min.js");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        unset($headerScriptInstances["modules.VTEButtons.resources.Edit"]);
        return $headerScriptInstances;
    }
}

?>