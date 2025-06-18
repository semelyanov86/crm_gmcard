<?php

class Settings_ChecklistItems_UserPermissions_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        $settingModel = new Settings_ChecklistItems_Settings_Model();
        $result = $settingModel->SaveUserPermissions($request);
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
    }
}
