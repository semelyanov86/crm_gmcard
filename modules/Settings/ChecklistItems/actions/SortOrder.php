<?php

class Settings_ChecklistItems_SortOrder_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $record = $request->get("record");
        $settingModel = new Settings_ChecklistItems_Settings_Model();
        $permissions = $settingModel->getPermissions();
        if ($permissions) {
            return true;
        }
        if (!Users_Privileges_Model::isPermitted($moduleName, "Save", $record)) {
            throw new AppException("LBL_PERMISSION_DENIED");
        }
    }
    public function process(Vtiger_Request $request)
    {
        $settingModel = new Settings_ChecklistItems_Settings_Model();
        $result = $settingModel->updateOrdering($request);
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
    }
}

?>