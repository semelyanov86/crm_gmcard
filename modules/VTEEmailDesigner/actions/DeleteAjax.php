<?php

class VTEEmailDesigner_DeleteAjax_Action extends Vtiger_Delete_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $record = $request->get("record");
        $currentUserPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPrivilegesModel->isPermitted($moduleName, "Delete", $record)) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $recordId = $request->get("record");
        $recordModel = VTEEmailDesigner_Record_Model::getInstanceById($recordId);
        $recordModel->setModule($moduleName);
        $recordModel->delete();
        $cvId = $request->get("viewname");
        $response = new Vtiger_Response();
        $response->setResult(array("viewname" => $cvId, "module" => $moduleName));
        if ($recordModel->isSystemTemplate()) {
            $response->setError("502", vtranslate("LBL_NO_PERMISSIONS_TO_DELETE_SYSTEM_TEMPLATE", $moduleName));
        }
        $response->emit();
    }
}

?>