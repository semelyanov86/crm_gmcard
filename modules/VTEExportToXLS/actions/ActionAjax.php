<?php

class VTEExportToXLS_ActionAjax_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("enableModule");
        $this->exposeMethod("checkEnable");
        $this->exposeMethod("saveValue");
        $this->exposeMethod("enableDownload");
    }
    
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function enableModule(Vtiger_Request $request)
    {
        global $adb;
        $value = $request->get("value");
        $adb->pquery("UPDATE `vteexport_to_xls_settings` SET `enable`=?", array($value));
        include_once "modules/VTEExportToXLS/VTEExportToXLS.php";
        if ($value == 0) {
            VTEExportToXLS::removeWidgetTo();
        } else {
            VTEExportToXLS::addWidgetTo();
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("result" => "success"));
        $response->emit();
    }
    public function checkEnable(Vtiger_Request $request)
    {
        global $adb;
        $rs = $adb->pquery("SELECT `enable` FROM `vteexport_to_xls_settings`;", array());
        $enable = $adb->query_result($rs, 0, "enable");
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("enable" => $enable));
        $response->emit();
    }
    public function saveValue(Vtiger_Request $request)
    {
        VTEExportToXLS_Module_Model::saveValue($request);
        $response = new Vtiger_Response();
        $response->setResult(array("success" => 1));
        $response->emit();
    }
    public function enableDownload(Vtiger_Request $request)
    {
        $result = VTEExportToXLS_Module_Model::enableDownload($request);
        $response = new Vtiger_Response();
        if ($result["success"]) {
            $response->setResult($result);
        } else {
            $response->setError($result["message"]);
        }
        $response->emit();
    }
}

?>