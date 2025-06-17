<?php

class Settings_UserLogin_EditImageSettings_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $qualifiedModuleName = $request->getModule(false);
        $viewer = $this->getViewer($request);
        $settingModel = new Settings_UserLogin_Settings_Model();
        $imageSettings = $settingModel->getImageSettings();
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModuleName);
        $viewer->assign("MODULE_NAME", $moduleName);
        $viewer->assign("IMAGE_SETTINGS", $imageSettings);
        echo $viewer->view("EditImageSettings.tpl", $qualifiedModuleName, false);
    }
}

?>