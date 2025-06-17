<?php

class Settings_UserLogin_Save_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $record = $request->get("record");
        if (!Users_Privileges_Model::isPermitted($moduleName, "Save", $record)) {
            throw new AppException("LBL_PERMISSION_DENIED");
        }
    }
    public function process(Vtiger_Request $request)
    {
        $settingModel = new Settings_UserLogin_Settings_Model();
        $settingModel->save($request);
        header("Location: index.php?module=UserLogin&view=Settings&parent=Settings");
    }
}

?>