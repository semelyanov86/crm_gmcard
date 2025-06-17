<?php

class VTEAdvanceMenu_MenuAjax_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $current_user = Users_Record_Model::getCurrentUserModel();
        if (!$current_user->isAdminUser()) {
            throw new AppException(vtranslate($moduleName, $moduleName) . " " . vtranslate("LBL_NOT_ACCESSIBLE"));
        }
    }
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("addNewModulesToTools");
        $this->exposeMethod("doNotAddNewModules");
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function addNewModulesToTools(Vtiger_Request $request)
    {
        global $adb;
        $menu_id = $request->get("menu_id");
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $moduleModel->addNewModulesToTools($menu_id);
        $response = new Vtiger_Response();
        $response->setResult(true);
        $response->emit();
    }
    public function doNotAddNewModules(Vtiger_Request $request)
    {
        $menu_id = $request->get("menu_id");
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $moduleModel->updateLastModuleId($menu_id);
        $response = new Vtiger_Response();
        $response->setResult(true);
        $response->emit();
    }
}

?>