<?php

class VTEButtons_SaveAjax_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
    }
    public function process(Vtiger_Request $request)
    {
        global $adb;
        $module = $request->get("module");
        $record = $request->get("record");
        $custom_module = $request->get("custom_module");
        $header = $request->get("header");
        $color = $request->get("color");
        $icon = $request->get("icon");
        $sequence = $request->get("sequence");
        $members = "";
        if ($request->get("members")) {
            $members = json_encode($request->get("members"));
        }
        $update_type = $request->get("update_type");
        $automated_update_picklist_field = $request->get("automated_update_picklist_field");
        $automated_update_picklist_value = $request->get("automated_update_picklist_value");
        $show_in_mobile = $request->get("show_in_mobile");
        $active_val = 0;
        $active = $request->get("active");
        if ($active == "Active") {
            $active_val = 1;
        }
        $field_name = $request->get("strfieldslist");
        $conditions = $request->get("advfilterlist");
        $conditions_count = count($conditions[1]["columns"]) + count($conditions[2]["columns"]);
        $VTEModuleModel = Vtiger_Module_Model::getInstance($module);
        $redirectUrl = $VTEModuleModel->getSettingURL();
        if (!empty($custom_module)) {
            if (0 < $record) {
                $sql = "UPDATE `vte_buttons_settings`\r\nSET module=?,header=?,icon=?,color=?,active=?,sequence=?,field_name=?,conditions=?,\r\nconditions_count=?,\r\nupdate_type=?,\r\nautomated_update_field=?,\r\nautomated_update_value=?,\r\nshow_in_mobile=?,\r\nmembers=?\r\nWHERE id=" . $record;
            } else {
                $sql = "INSERT INTO vte_buttons_settings (module,header,icon,color,active,sequence,field_name,conditions,\r\nconditions_count,\r\nupdate_type,\r\nautomated_update_field,\r\nautomated_update_value,\r\nshow_in_mobile,\r\nmembers\r\n)\r\nVALUES (?, ?, ?, ?, ?, ?,?,?,?,?,?,?,?,?)";
            }
        }
        $adb->pquery($sql, array($custom_module, $header, $icon, $color, $active_val, $sequence, json_encode($field_name), json_encode($conditions), $conditions_count, $update_type, $automated_update_picklist_field, $automated_update_picklist_value, $show_in_mobile, $members));
        header("Location: " . $redirectUrl);
    }
}

?>