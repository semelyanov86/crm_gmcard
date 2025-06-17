<?php

class VTEConditionalAlerts_TaskAjax_Action extends Vtiger_IndexAjax_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("Delete");
        $this->exposeMethod("ChangeStatus");
        $this->exposeMethod("Save");
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function Delete(Vtiger_Request $request)
    {
        $record = $request->get("task_id");
        if (!empty($record)) {
            $taskRecordModel = new VTEConditionalAlerts_TaskRecord_Model();
            $taskRecordModel->delete();
            $response = new Vtiger_Response();
            $response->setResult(array("ok"));
            $response->emit();
        }
    }
    public function ChangeStatus(Vtiger_Request $request)
    {
        $record = $request->get("task_id");
        if (!empty($record)) {
            VTEConditionalAlerts_TaskRecord_Model::active($request);
            $response = new Vtiger_Response();
            $response->setResult(array("ok"));
            $response->emit();
        }
    }
    public function Save(Vtiger_Request $request)
    {
        $clfId = $request->get("for_cap");
        if (!empty($clfId)) {
            $taskRecordModel = new VTEConditionalAlerts_TaskRecord_Model();
            $taskRecordModel->save();
            $response = new Vtiger_Response();
            $response->setResult(array("for_cap" => $clfId));
            $response->emit();
        }
    }
    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}

?>