<?php

class KanbanView_QuickAjax_Action extends Vtiger_Save_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->get("source_module");
        $record = $request->get("record");
        if (!Users_Privileges_Model::isPermitted($moduleName, "Save", $record)) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
        if ($record) {
            $recordEntityName = getSalesEntityType($record);
            if ($recordEntityName !== $moduleName) {
                throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
            }
        }
    }
    public function process(Vtiger_Request $request)
    {
        $recordModel = $this->saveRecord($request);
        $fieldModelList = $recordModel->getModule()->getFields();
        $result = array();
        foreach ($fieldModelList as $fieldName => $fieldModel) {
            $recordFieldValue = $recordModel->get($fieldName);
            if (is_array($recordFieldValue) && $fieldModel->getFieldDataType() == "multipicklist") {
                $recordFieldValue = implode(" |##| ", $recordFieldValue);
            }
            $fieldValue = $displayValue = Vtiger_Util_Helper::toSafeHTML($recordFieldValue);
            if ($fieldModel->getFieldDataType() !== "currency" && $fieldModel->getFieldDataType() !== "datetime" && $fieldModel->getFieldDataType() !== "date") {
                $displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId());
            }
            $result[$fieldName] = array("value" => $fieldValue, "display_value" => $displayValue);
        }
        $result["_recordLabel"] = $recordModel->getName();
        $result["_recordId"] = $recordModel->getId();
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($result);
        $response->emit();
    }
    protected function getRecordModelFromRequest(Vtiger_Request $request)
    {
        $moduleName = $request->get("source_module");
        $recordId = $request->get("record");
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if (!empty($recordId)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
            $modelData = $recordModel->getData();
            $recordModel->set("id", $recordId);
            $recordModel->set("mode", "edit");
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $modelData = $recordModel->getData();
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
        }
        return $recordModel;
    }
}

?>