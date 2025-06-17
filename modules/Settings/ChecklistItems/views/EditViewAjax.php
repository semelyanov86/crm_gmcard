<?php

class Settings_ChecklistItems_EditViewAjax_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }
    public function checkPermission(Vtiger_Request $request)
    {
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

    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $qualifiedModuleName = $request->getModule(false);
        $record = $request->get("record", 0);
        $viewer = $this->getViewer($request);
        $settingModel = new Settings_ChecklistItems_EditViewAjax_Model();
        $entity = $settingModel->getData($record);
        $active_module = $entity["modulename"];
        $listModules = $settingModel->getEntityModules();
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModuleName);
        $viewer->assign("MODULE_NAME", $moduleName);
        $viewer->assign("ENTITY", $entity);
        $viewer->assign("LIST_MODULES", $listModules);
        $viewer->assign("RECORD_ID", $record);
        $viewer->assign("ACTIVE_MODULE", $active_module);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $viewer->view("EditViewAjax.tpl", $qualifiedModuleName);
    }
}

?>