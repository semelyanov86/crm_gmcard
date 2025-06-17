<?php

class VTEPayments_SaveAjax_Action extends Vtiger_SaveAjax_Action
{
    public function __construct()
    {
        parent::__construct();
    }

    public function process(Vtiger_Request $request)
    {
        return parent::process($request);
    }
    /**
     * Function to get the record model based on the request parameters
     * @param Vtiger_Request $request
     * @return Vtiger_Record_Model or Module specific Record Model instance
     */
    public function getRecordModelFromRequest(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $recordId = $request->get("record");
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if (!empty($recordId)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
            $recordModel->set("id", $recordId);
            $recordModel->set("mode", "edit");
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $recordModel->set("mode", "");
        }
        $fieldModelList = $moduleModel->getFields();
        foreach ($fieldModelList as $fieldName => $fieldModel) {
            $fieldValue = $request->get($fieldName, NULL);
            $fieldDataType = $fieldModel->getFieldDataType();
            if ($fieldDataType == "time") {
                $fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
            }
            if ($fieldValue !== NULL) {
                if (!is_array($fieldValue)) {
                    $fieldValue = trim($fieldValue);
                }
                $recordModel->set($fieldName, $fieldValue);
            }
            if ($fieldName == "date" && empty($fieldValue)) {
                $recordModel->set("date", date("Y-m-d H:i:s"));
            }
        }
        return $recordModel;
    }
}

?>