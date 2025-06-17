<?php

class Settings_UserLogin_DeleteAjax_Action extends Vtiger_Action_Controller
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
        $result = $settingModel->deleteRecord($request);
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
    }
}

?>