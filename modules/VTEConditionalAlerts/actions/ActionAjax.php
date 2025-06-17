<?php

class VTEConditionalAlerts_ActionAjax_Action extends Vtiger_IndexAjax_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("getConditionAlertForModule");
        $this->exposeMethod("getFieldValueOnConditionAlert");
    }
    
    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    public function getConditionAlertForModule(Vtiger_Request $request)
    {
        $current_module = $request->get("current_module");
        $record_id = $request->get("record_id");
        if (!empty($current_module)) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery("SELECT * FROM vte_conditional_alerts c\n                                    INNER JOIN vte_conditional_alerts_task t ON t.cat_id = c.id\n                                    WHERE c.module = ?", array($current_module));
            $noOfrecord = $db->num_rows($result);
            $clf_info = array();
            $record_info = array();
            $list_fields = array();
            if (0 < $noOfrecord) {
                for ($i = 0; $i < $noOfrecord; $i++) {
                    $condition = $db->query_result($result, $i, "condition");
                    $condition = json_decode(html_entity_decode($condition));
                    $description = html_entity_decode($db->query_result($result, $i, "description"));
                    if (preg_match_all("/\\\$(.*?)\\\$/", $description, $match)) {
                        $list_field_included = $match[1];
                    }
                    foreach ($list_field_included as $field_name) {
                        $field_name_extracts = explode(":", $field_name);
                        $field_name_child = $field_name_extracts[2];
                        $module_name_extracts = explode("_", $field_name_extracts[3]);
                        $module_name = $module_name_extracts[0];
                        if ($module_name == $current_module) {
                            if (!empty($record_id)) {
                                $current_record_model = Vtiger_Record_Model::getInstanceById($record_id);
                                $field_value = $current_record_model->getDisplayValue($field_name_child, $record_id);
                            }
                        } else {
                            if (!empty($record_id)) {
                                $related_module = $module_name;
                                $related_module_model = CRMEntity::getInstance($related_module);
                                $related_table_index = $related_module_model->table_index;
                                if (substr($related_table_index, -3) !== "_id") {
                                    $related_table_index = substr($related_table_index, 0, -2) . "_id";
                                }
                                if ($current_module == "HelpDesk" && $related_module == "Accounts") {
                                    $related_table_index = "parent_id";
                                }
                                if ($current_module == "Potentials" && $related_module == "Accounts") {
                                    $related_table_index = "related_to";
                                }
                                $current_record_model = Vtiger_Record_Model::getInstanceById($record_id);
                                $current_model_data = $current_record_model->getData();
                                $related_field_value = $current_model_data[$related_table_index];
                                if (is_null($related_field_value)) {
                                    $sourceRecordModel = Vtiger_Record_Model::getInstanceById($record_id, $current_module);
                                    $targetModel = Vtiger_RelationListView_Model::getInstance($sourceRecordModel, $related_module);
                                    $sql = $targetModel->getRelationQuery();
                                    $related_result = $db->pquery($sql, array());
                                    $related_field_value = $db->query_result($related_result, 0, "crmid");
                                }
                                if (!is_null($related_field_value)) {
                                    $related_record_model = Vtiger_Record_Model::getInstanceById($related_field_value);
                                    $field_value = $related_record_model->getDisplayValue($field_name_child, $related_field_value);
                                }
                            }
                        }
                        if ($field_value) {
                            $description = str_replace("\$" . $field_name . "\$", $field_value, $description);
                        } else {
                            $description = str_replace("\$" . $field_name . "\$", "", $description);
                        }
                    }
                    $actions = array("action_title" => $db->query_result($result, $i, "action_title"), "alert_while_edit" => $db->query_result($result, $i, "alert_while_edit"), "alert_when_open" => $db->query_result($result, $i, "alert_when_open"), "alert_on_save" => $db->query_result($result, $i, "alert_on_save"), "donot_allow_to_save" => $db->query_result($result, $i, "donot_allow_to_save"), "description" => $description);
                    $clf_info[] = array("condition" => $this->splitCondition($condition), "actions" => $actions);
                    $list_fields = array_merge($list_fields, $this->getAllFieldOnCondition($condition));
                }
                if (!empty($record_id)) {
                    $current_record_model = Vtiger_Record_Model::getInstanceById($record_id);
                    $record_info = $current_record_model->getData();
                    foreach ($list_fields as $key => $field_name) {
                        $split_item = explode(":", $field_name);
                        $not_existed_field_name = $split_item[1];
                        if (!$this->in_array_r($not_existed_field_name, $record_info)) {
                            list($related_table_module, $related_module) = $split_item;
                            $moduleModel = CRMEntity::getInstance($current_module);
                            $module_table_name = $moduleModel->table_name;
                            $module_table_index = $moduleModel->table_index;
                            $result = $db->pquery("SELECT f.columnname as join_field\n                                                    FROM vtiger_field f\n                                                    INNER JOIN vtiger_fieldmodulerel r ON r.fieldid = f.fieldid\n                                                    WHERE r.module = ? AND r.relmodule = ? LIMIT 1", array($current_module, $related_module));
                            $join_field = $db->query_result($result, 0, "join_field");
                            if ($join_field) {
                                $relatedModuleModel = CRMEntity::getInstance($related_module);
                                $related_table_index = $relatedModuleModel->table_index;
                                $sql = "SELECT " . $not_existed_field_name . " FROM " . $related_table_module . " INNER JOIN " . $module_table_name . " ON " . $related_table_module . "." . $related_table_index . "=" . $module_table_name . "." . $join_field . " WHERE " . $module_table_name . "." . $module_table_index . " = " . $record_id;
                                $result = $db->pquery($sql, array());
                                if (0 < $db->num_rows($result)) {
                                    $not_existed_field_value = $db->query_result($result, 0, $not_existed_field_name);
                                    $record_info[$not_existed_field_name] = $not_existed_field_value;
                                }
                            }
                        }
                    }
                } else {
                    foreach ($list_fields as $key => $field_name) {
                        $split_item = explode(":", $field_name);
                        $not_existed_field_name = $split_item[1];
                        $record_info[$not_existed_field_name] = "";
                    }
                }
            }
            $response = new Vtiger_Response();
            $response->setResult(array("clf_info" => $clf_info, "record_info" => $record_info));
            $response->emit();
        }
    }
    public function getFieldValueOnConditionAlert(Vtiger_Request $request)
    {
        $return_value = array();
        $current_module = $request->get("current_module");
        $related_module = $request->get("related_module");
        $record_id = $request->get("record_id");
        $current_record_model = Vtiger_Record_Model::getInstanceById($record_id, $related_module);
        $record_info = $current_record_model->getData();
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT * FROM vte_conditional_alerts c\n                                    INNER JOIN vte_conditional_alerts_task t ON t.cat_id = c.id\n                                    WHERE c.module = ?", array($current_module));
        $noOfrecord = $db->num_rows($result);
        $list_fields = array();
        if (0 < $noOfrecord) {
            for ($i = 0; $i < $noOfrecord; $i++) {
                $condition = $db->query_result($result, $i, "condition");
                $condition = json_decode(html_entity_decode($condition));
                $list_fields = array_merge($list_fields, $this->getAllFieldOnCondition($condition));
            }
        }
        foreach ($list_fields as $key => $field_name) {
            $split_item = explode(":", $field_name);
            $existed_field_name = $split_item[1];
            if ($this->in_array_r($existed_field_name, $record_info)) {
                $return_value[$existed_field_name] = $record_info[$existed_field_name];
            }
        }
        $response = new Vtiger_Response();
        $response->setResult($return_value);
        $response->emit();
    }
    public function splitCondition($conditions)
    {
        $allConditions = array();
        $anyConditions = array();
        if (!empty($conditions)) {
            foreach ($conditions as $p_index => $p_info) {
                foreach ($p_info->columns as $index => $info) {
                    $columnname = $info->columnname;
                    $columnname = explode(":", $columnname);
                    if ($info->groupid == 0) {
                        $allConditions[] = array("columnname" => $columnname[2], "comparator" => $info->comparator, "value" => $info->value);
                    } else {
                        $anyConditions[] = array("columnname" => $columnname[2], "comparator" => $info->comparator, "value" => $info->value);
                    }
                }
            }
        }
        return array("all" => $allConditions, "any" => $anyConditions);
    }
    public function in_array_r($item, $array)
    {
        return preg_match("/\"" . $item . "\"/i", json_encode($array));
    }
    public function getAllFieldOnCondition($conditions)
    {
        $list_field = array();
        if (!empty($conditions)) {
            foreach ($conditions as $p_index => $p_info) {
                foreach ($p_info->columns as $index => $info) {
                    $columnname = $info->columnname;
                    $columnname = explode(":", $columnname);
                    $modulename = explode("_", $columnname[3]);
                    $list_field[] = $columnname[0] . ":" . $columnname[1] . ":" . $modulename[0];
                }
            }
        }
        return $list_field;
    }
}

?>