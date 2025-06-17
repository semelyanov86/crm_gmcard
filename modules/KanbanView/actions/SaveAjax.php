<?php

class KanbanView_SaveAjax_Action extends Vtiger_BasicAjax_Action
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("saveKanbanViewSetting");
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function saveKanbanViewSetting(Vtiger_Request $request)
    {
        global $adb;
        $kabanviewModel = new KanbanView_Module_Model();
        $value = $kabanviewModel->saveKanbanViewSetting($request);
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($value);
        $response->emit();
    }
}

?>