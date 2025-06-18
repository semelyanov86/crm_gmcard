<?php

class VReports_DeleteAjax_Action extends Vtiger_DeleteAjax_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $currentUserPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPrivilegesModel->isPermitted($moduleName, 'Delete', $record)) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        global $current_user;
        $moduleName = $request->getModule();
        $recordId = $request->get('record');
        $response = new Vtiger_Response();
        $recordModel = VReports_Record_Model::getInstanceById($recordId, $moduleName);
        if (!$recordModel->isDefault() && $recordModel->isEditable() && $recordModel->isEditableBySharing() || $current_user->is_admin) {
            $recordModel->delete();
            $response->setResult([vtranslate('LBL_REPORTS_DELETED_SUCCESSFULLY', $parentModule)]);
        } else {
            $response->setError(vtranslate('LBL_REPORT_DELETE_DENIED', $moduleName));
        }
        $response->emit();
    }
}
