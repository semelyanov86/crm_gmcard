<?php

class VTEAdvanceMenu_GetMenu_View extends Vtiger_Basic_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function preProcess(Vtiger_Request $request, $display = true)
    {
        return true;
    }

    public function postProcess(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        global $current_language;
        include 'languages/' . $current_language . '/VTEAdvanceMenu.php';
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $menu = $moduleModel->getMenu(1);
        $newEntityModules = $moduleModel->getNewEntityModules(1);
        $vteStoreModuleModel = Vtiger_Module_Model::getInstance('VTEStore');
        $vteStoreModuleIsActive = false;
        if ($vteStoreModuleModel && $vteStoreModuleModel->isActive()) {
            $vteStoreModuleIsActive = true;
        }
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('MENU_SETTING', $menu);
        $viewer->assign('VTE_STORE_MODULE_IS_ACTIVE', $vteStoreModuleIsActive);
        $viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('NEW_ENTITY_MODULES', $newEntityModules);
        $viewer->assign('NUMBER_NEW_ENTITY_MODULES', count($newEntityModules));
        $viewer->assign('MENU_ID', 1);
        echo $viewer->view('Menu.tpl', $moduleName, true);
    }
}
