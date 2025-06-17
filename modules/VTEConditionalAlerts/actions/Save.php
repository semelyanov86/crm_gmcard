<?php

class VTEConditionalAlerts_Save_Action extends Vtiger_Save_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function process(Vtiger_Request $request)
    {
        $recordId = $request->get("record");
        $moduleName = $request->get("selected_module");
        $conditions = $request->get("conditions");
        $descriptions = $request->get("descriptions");
        $response = new Vtiger_Response();
        $adb = PearDatabase::getInstance();
        $json = new Zend_Json();
        if (empty($recordId)) {
            $sql = "INSERT INTO `vte_conditional_alerts` (`module`, `description`,`condition`) VALUES (?, ?, ?)";
            $adb->pquery($sql, array($moduleName, $descriptions, $json->encode($conditions)));
            $recordId = $adb->getLastInsertID();
        } else {
            $sql = "UPDATE `vte_conditional_alerts` SET `module`=?, `description`=?,`condition`=?  WHERE `id`=?";
            $adb->pquery($sql, array($moduleName, $descriptions, $json->encode($conditions), $recordId));
        }
        $response->setResult(array("id" => $recordId, "selected_module" => $moduleName));
        $response->emit();
    }
    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}

?>