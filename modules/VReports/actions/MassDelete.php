<?php

class VReports_MassDelete_Action extends Vtiger_Mass_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    public function preProcess(Vtiger_Request $request)
    {
        return true;
    }
    public function postProcess(Vtiger_Request $request)
    {
        return true;
    }
    public function process(Vtiger_Request $request)
    {
        global $current_user;
        $parentModule = "VReports";
        $recordIds = VReports_Record_Model::getRecordsListFromRequest($request);
        $reportsDeleteDenied = array();
        foreach ($recordIds as $recordId) {
            $recordModel = VReports_Record_Model::getInstanceById($recordId);
            if (!$recordModel->isDefault() && $recordModel->isEditable() && $recordModel->isEditableBySharing() || $current_user->is_admin) {
                $success = $recordModel->delete();
                if (!$success) {
                    $reportsDeleteDenied[] = vtranslate($recordModel->getName(), $parentModule);
                }
            } else {
                $reportsDeleteDenied[] = vtranslate($recordModel->getName(), $parentModule);
            }
        }
        $response = new Vtiger_Response();
        if (empty($reportsDeleteDenied)) {
            $response->setResult(array(vtranslate("LBL_REPORTS_DELETED_SUCCESSFULLY", $parentModule)));
        } else {
            $response->setError($reportsDeleteDenied, vtranslate("LBL_DENIED_REPORTS", $parentModule));
        }
        $response->emit();
    }
}

?>