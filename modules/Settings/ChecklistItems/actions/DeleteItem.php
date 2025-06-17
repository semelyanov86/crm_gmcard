<?php

class Settings_ChecklistItems_DeleteItem_Action extends Vtiger_Action_Controller
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
        global $adb;
        $itemid = $request->get("itemid");
        $sql = "UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid IN (SELECT checklistitemsid FROM vtiger_checklistitems WHERE settings_item_id = ?);";
        $adb->pquery($sql, array($itemid));
        $response = new Vtiger_Response();
        $response->setResult(true);
        $response->emit();
    }
}

?>