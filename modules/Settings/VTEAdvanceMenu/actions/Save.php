<?php

class Settings_VTEAdvanceMenu_Save_Action extends Settings_Vtiger_Index_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
        parent::checkPermission($request);
        $moduleModel = Vtiger_Module_Model::getInstance($request->getModule());
        $currentUserPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPrivilegesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    public function process(Vtiger_Request $request)
    {
        $returnUrl = "index.php?module=VTEAdvanceMenu&view=Settings&parent=Settings";
        header("Location: " . $returnUrl);
    }
    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}

?>