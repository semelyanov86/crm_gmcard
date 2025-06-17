<?php

class VTEButtons_ActionAjax_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("enableModule");
        $this->exposeMethod("checkEnable");
        $this->exposeMethod("updateSequence");
        $this->exposeMethod("selectModule");
        $this->exposeMethod("UpdateStatus");
        $this->exposeMethod("DeleteRecord");
        $this->exposeMethod("doUpdateFields");
        $this->exposeMethod("getPicklistValues");
        $this->exposeMethod("getPicklists");
        $this->exposeMethod("get_fields_update");
        $this->exposeMethod("autoUpdate");
        $this->exposeMethod("doAddCustomScript");
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
        $adb->pquery("UPDATE `vte_buttons_settings` SET `active`=?", array($value));
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("result" => "success"));
        $response->emit();
    }
    public function checkEnable(Vtiger_Request $request)
    {
        global $adb;
        $rs = $adb->pquery("SELECT `enable` FROM `vte_buttons_settings`;", array());
        $enable = $adb->query_result($rs, 0, "active");
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("enable" => $enable));
        $response->emit();
    }
    public function autoUpdate(Vtiger_Request $request)
    {
        global $adb;
        $vtebuttonid = $request->get("vtebuttons_id");
        $record = $request->get("record");
        $moduleName = $request->get("source_module");
        $automated_update_field = $automated_update_value = "";
        $sql = "SELECT * FROM `vte_buttons_settings` WHERE id = ?;";
        $re = $adb->pquery($sql, array($vtebuttonid));
        if (0 < $adb->num_rows($re)) {
            $update_type = $adb->query_result($re, 0, "update_type");
            $automated_update_field = $adb->query_result($re, 0, "automated_update_field");
            $automated_update_value = $adb->query_result($re, 0, "automated_update_value");
            if ($automated_update_field && $automated_update_value) {
                $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
                $record_model = Vtiger_Record_Model::getInstanceById($record, $moduleModel);
                $_REQUEST["ajxaction"] = "DETAILVIEW";
                $record_model->set("id", $record);
                $record_model->set("mode", "edit");
                $record_model->set($automated_update_field, $automated_update_value);
                $_REQUEST["ajxaction"] = "DETAILVIEW";
                $record_model->save();
                $module_model = $record_model->getModule();
                $module_name = $record_model->getModuleName();
                $field_model = Vtiger_Field_Model::getInstance($automated_update_field, $module_model);
                if (!empty($field_model)) {
                    $field_label = $field_model->get("label");
                }
            }
        }
        $mode = $request->get("mode");
        if ($mode == "autoUpdate" && $automated_update_field) {
            $response = new Vtiger_Response();
            $response->setEmitType(Vtiger_Response::$EMIT_JSON);
            $response->setResult(array("label" => vtranslate($field_label, $module_name), "value" => vtranslate($automated_update_value, $module_name)));
            $response->emit();
        }
    }
    public function get_fields_update(Vtiger_Request $request)
    {
        global $adb;
        $module = $request->get("source_module");
        $vtebuttonid = $request->get("vtebuttonid");
        $sql = "SELECT field_name,automated_update_field FROM `vte_buttons_settings` WHERE id = ?;";
        $re = $adb->pquery($sql, array($vtebuttonid));
        if (0 < $adb->num_rows($re)) {
            $field_name = $adb->query_result($re, 0, "field_name");
            $field_name = html_entity_decode($field_name);
            $field_name = str_replace("\"", "", $field_name);
            $automated_update_field = $adb->query_result($re, 0, "automated_update_field");
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(array("field_name" => $field_name, "automated_update_field" => $automated_update_field));
        $response->emit();
    }
    public function getPicklists(Vtiger_Request $request)
    {
        global $adb;
        $module = $request->get("source_module");
        $selectd_module = Vtiger_Module_Model::getInstance($module);
        $picklist_fields = array();
        $selected_module_fields = $selectd_module->getFields();
        foreach ($selected_module_fields as $field_obj) {
            $field_type = $field_obj->getFieldDataType();
            $field_name = $field_obj->get("name");
            $field_label = $field_obj->get("label");
            if ($field_type == "picklist") {
                $picklist_fields[] = array("value" => $field_name, "display" => vtranslate($field_label, $module));
            }
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($picklist_fields);
        $response->emit();
    }
    public function getPicklistValues(Vtiger_Request $request)
    {
        global $adb;
        $field = $request->get("field");
        $module = $request->get("source_module");
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        $field_model = Vtiger_Field_Model::getInstance($field, $moduleModel);
        $result = array();
        $values = $field_model->getPicklistValues();
        foreach ($values as $val => $display) {
            $result[] = array("value" => $val, "display" => $display);
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($result);
        $response->emit();
    }
    public function selectModule(Vtiger_Request $request)
    {
        $moduleSelected = $request->get("moduleSelected");
        $module = $request->get("module");
        $moduleModel = Vtiger_Module_Model::getInstance($moduleSelected);
        $recordStructureModel = Vtiger_RecordStructure_Model::getInstanceForModule($moduleModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_FILTER);
        $recordStructure = $recordStructureModel->getStructure();
        foreach ($recordStructure as $blocks) {
            foreach ($blocks as $fieldLabel => $fieldValue) {
                $fieldModels[$fieldLabel] = $fieldValue;
            }
        }
        $data = array();
        if ($moduleSelected) {
            $workflows = VTEButtons_Module_Model::getAllWorkflowsForModule($moduleSelected);
        }
        $data = array("fieldmodels" => $fieldModels, "workflows" => $workflows);
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        $response = new Vtiger_Response();
        $response->setResult($data);
        $response->emit();
    }
    public function UpdateStatus(Vtiger_Request $request)
    {
        global $adb;
        $record = $request->get("record");
        $status = $request->get("status");
        $status = $status == "off" ? "0" : "1";
        $sql = "UPDATE `vte_buttons_settings` SET active=? WHERE id=?";
        $adb->pquery($sql, array($status, $record));
        $response = new Vtiger_Response();
        $response->setResult("success");
        $response->emit();
    }
    public function DeleteRecord(Vtiger_Request $request)
    {
        global $adb;
        $record = $request->get("record");
        $sql = "DELETE FROM `vte_buttons_settings`  WHERE id=?";
        $adb->pquery($sql, array($record));
        $response = new Vtiger_Response();
        $response->setResult("success");
        $response->emit();
    }
    public function doUpdateFields(Vtiger_Request $request)
    {
        global $adb;
        $recordId = $request->get("record");
        $moduleName = $request->get("source_module");
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleModel);
        $recordModel->set("id", $recordId);
        $recordModel->set("mode", "edit");
        $_REQUEST["ajxaction"] = "DETAILVIEW";
        $fieldModelList = $moduleModel->getFields();
        foreach ($fieldModelList as $fieldName => $fieldModel) {
            $uiType = $fieldModel->get("uitype");
            if ($uiType == 70) {
                $fieldValue = $recordModel->get($fieldName);
            } else {
                $fieldValue = $fieldModel->getUITypeModel()->getUserRequestValue($recordModel->get($fieldName));
            }
            if ($request->has($fieldName)) {
                $fieldValue = $request->get($fieldName, NULL);
            } else {
                if ($fieldName === $request->get("field")) {
                    $fieldValue = $request->get("value");
                }
            }
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
            $recordModel->set($fieldName, $fieldValue);
            if ($fieldName === "contact_id" && isRecordExists($fieldValue)) {
                $contactRecord = Vtiger_Record_Model::getInstanceById($fieldValue, "Contacts");
                $recordModel->set("relatedContact", $contactRecord);
            }
        }
        $recordModel->save();
        $response = new Vtiger_Response();
        $response->setResult("success");
        $response->emit();
    }
    public function doAddCustomScript(Vtiger_Request $request)
    {
        global $adb;
        global $root_directory;
        $moduleName = $request->getModule(false);
        $params = $request->get("params");
        $isActive = $params["isactive"];
        $icustom_script = decode_html($params["custom_script"]);
        $success = 1;
        $result = $adb->pquery("SELECT * FROM vte_buttons_customjs", array());
        if (0 < $adb->num_rows($result)) {
            $sql = "UPDATE vte_buttons_customjs SET is_active = ?, custom_script = ?";
            $adb->pquery($sql, array($isActive, $icustom_script));
        } else {
            $sql = "INSERT INTO vte_buttons_customjs (is_active, custom_script) VALUES (?, ?)";
            $adb->pquery($sql, array($isActive, $icustom_script));
        }
        $widgetType = "HEADERSCRIPT";
        $widgetName = $moduleName . "CustomJs";
        $link = "layouts/v7/modules/VTEButtons/resources/" . $moduleName . "Custom.js";
        $module = Vtiger_Module::getInstance($moduleName);
        if (!empty($isActive) && $isActive == "1") {
            if ($module) {
                $module->addLink($widgetType, $widgetName, $link);
            }
        } else {
            if ($module) {
                $module->deleteLink($widgetType, $widgetName, $link);
            }
        }
        $rootDirectory = str_replace("\\", "/", $root_directory);
        $rootDirectory = rtrim($rootDirectory, "/");
        $file = $rootDirectory . "/" . $link;
        file_put_contents($file, $icustom_script);
        $response = new Vtiger_Response();
        $response->setResult(array("success" => $success, "message" => vtranslate("Saved changes", $moduleName)));
        $response->emit();
    }
}

?>