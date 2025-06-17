<?php

define("DS", DIRECTORY_SEPARATOR);

class ChecklistItems_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        global $adb;
        echo "<div class=\"container-fluid\">\r\n                <div class=\"widget_header row-fluid\">\r\n                    <h3>VTE Check List</h3>\r\n                </div>\r\n                <hr>";
        $module = Vtiger_Module::getInstance("ChecklistItems");
        if ($module) {
            $module->delete();
        }
        $message = $this->removeData();
        echo $message;
        $res_template = $this->delete_folder("layouts/v7/modules/Settings/ChecklistItems");
        $res_template = $this->delete_folder("layouts/vlayout/modules/Settings/ChecklistItems");
        $res_template = $this->delete_folder("layouts/v7/modules/ChecklistItems");
        $res_template = $this->delete_folder("layouts/vlayout/modules/ChecklistItems");
        echo "&nbsp;&nbsp;- Delete VTE Check List template folder";
        if ($res_template) {
            echo " - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>";
        $res_module = $this->delete_folder("modules/Settings/ChecklistItems");
        $res_module = $this->delete_folder("modules/ChecklistItems");
        echo "&nbsp;&nbsp;- Delete VTE Check List module folder";
        if ($res_module) {
            echo " - DONE";
        } else {
            echo " - <b>ERROR</b>";
        }
        echo "<br>Module was Uninstalled.</div>";
    }
    public function delete_folder($tmp_path)
    {
        if (!is_writeable($tmp_path) && is_dir($tmp_path) && isFileAccessible($tmp_path)) {
            chmod($tmp_path, 511);
        }
        $handle = opendir($tmp_path);
        if ($handle) {
            while ($tmp = readdir($handle)) {
                if ($tmp != ".." && $tmp != "." && $tmp != "") {
                    if (is_writeable($tmp_path . DS . $tmp) && is_file($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                        unlink($tmp_path . DS . $tmp);
                    } else {
                        if (!is_writeable($tmp_path . DS . $tmp) && is_file($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                            chmod($tmp_path . DS . $tmp, 438);
                            unlink($tmp_path . DS . $tmp);
                        }
                    }
                    if (is_writeable($tmp_path . DS . $tmp) && is_dir($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                        $this->delete_folder($tmp_path . DS . $tmp);
                    } else {
                        if (!is_writeable($tmp_path . DS . $tmp) && is_dir($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                            chmod($tmp_path . DS . $tmp, 511);
                            $this->delete_folder($tmp_path . DS . $tmp);
                        }
                    }
                }
            }
            closedir($handle);
        }
        rmdir($tmp_path);
        if (!is_dir($tmp_path)) {
            return true;
        }
        return false;
    }
    public function removeData()
    {
        global $adb;
        $message = "";
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("VTE Check List"));
        $adb->pquery("DELETE FROM `vtiger_links` WHERE linklabel = 'Checklists' AND linkurl LIKE '%javascript:Vtiger_ChecklistItems_Js.showChecklistItems%';", array());
        $adb->pquery("DELETE FROM `vtiger_links` WHERE linklabel = 'ChecklistItemsJS' AND linkurl = 'layouts/v7/modules/ChecklistItems/resources/ChecklistItems.js';", array());
        $sql = "DROP TABLE `vtiger_checklistitems`,vtiger_checklistitemscf,vtiger_checklistitems_settings,vtiger_checklistitems_settings_items,vtiger_checklistitems_permissions,vtiger_checklistitems_user_field,vtiger_checklistitem_status,vtiger_checklistitem_status_seq;";
        $result = $adb->pquery($sql, array());
        $message .= "&nbsp;&nbsp;- Delete VTE Check List tables";
        if ($result) {
            $message .= " - DONE";
        } else {
            $message .= " - <b>ERROR</b>";
        }
        $message .= "<br>";
        return $message;
    }
}

?>