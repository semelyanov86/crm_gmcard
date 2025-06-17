<?php

include_once "modules/Vtiger/CRMEntity.php";
include_once "include/utils/utils.php";

/**
 * Class ModuleLinkCreator
 */
class ModuleLinkCreator extends Vtiger_CRMEntity
{
    public $table_name = "vte_module_link_creator";
    public $table_index = "id";
    /**
     * Mandatory for Saving, Include tables related to this module.
     */
    public $tab_name = array("vte_module_link_creator");
    /**
     * Mandatory for Saving, Include tablename and tablekey columnname here.
     */
    public $tab_name_index = array("vte_module_link_creator" => "id");
    /**
     * Mandatory for Listing (Related listview)
     */
    public $list_fields = array("Status" => array("vte_module_link_creator", "status"), "Created" => array("vte_module_link_creator", "created"), "Updated" => array("vte_module_link_creator", "updated"), "Module Id" => array("vte_module_link_creator", "module_id"), "Module Name" => array("vte_module_link_creator", "module_name"), "Module Label" => array("vte_module_link_creator", "module_label"), "Module Type" => array("vte_module_link_creator", "module_type"), "Module Fields" => array("vte_module_link_creator", "module_fields"), "Module List View Filter Fields" => array("vte_module_link_creator", "module_list_view_filter_fields"), "Module Summary Fields" => array("vte_module_link_creator", "module_summary_fields"), "Module Quick Create Fields" => array("vte_module_link_creator", "module_quick_create_fields"), "Module Links" => array("vte_module_link_creator", "module_links"), "Description" => array("vte_module_link_creator", "description"));
    public $list_fields_name = array("Status" => "status", "Created" => "created", "Updated" => "updated", "Module Id" => "module_id", "Module Name" => "module_name", "Module Label" => "module_label", "Module Type" => "module_type", "Module Fields" => "module_fields", "Module List View Filter Fields" => "module_list_view_filter_fields", "Module Summary Fields" => "module_summary_fields", "Module Quick Create Fields" => "module_quick_create_fields", "Module Links" => "module_links", "Description" => "description");
    public $def_detailview_recname = "module_name";
    public $mandatory_fields = array("module_name", "module_label", "module_type", "assigned_user_id");
    public $default_order_by = "module_name";
    public $default_sort_order = "ASC";
    /**
     * @param string $moduleName
     */
    public static function addWidgetTo($moduleName)
    {
        global $adb;
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
        } else {
            $template_folder = "layouts/v7";
        }
        $module = Vtiger_Module::getInstance($moduleName);
        $widgetName = "Custom Module Builder";
        if ($module) {
            $css_widgetType = "HEADERCSS";
            $css_widgetLabel = $widgetName;
            $css_link = (string) $template_folder . "/modules/" . $moduleName . "/resources/" . $moduleName . "CSS.css";
            $js_widgetType = "HEADERSCRIPT";
            $js_widgetLabel = $widgetName;
            $js_link = (string) $template_folder . "/modules/" . $moduleName . "/resources/" . $moduleName . "JS.js";
            $js_link_2 = (string) $template_folder . "/modules/" . $moduleName . "/resources/" . $moduleName . "Utils.js";
            $module->addLink($css_widgetType, $css_widgetLabel, $css_link);
            $module->addLink($js_widgetType, $js_widgetLabel, $js_link);
            $module->addLink($js_widgetType, $js_widgetLabel, $js_link_2);
        }
        $rs = $adb->pquery("SELECT * FROM `vtiger_ws_entity` WHERE `name` = ?", array($moduleName));
        if ($adb->num_rows($rs) == 0) {
            $adb->pquery("INSERT INTO `vtiger_ws_entity` (`name`, `handler_path`, `handler_class`, `ismodule`)\r\n            VALUES (?, 'include/Webservices/VtigerModuleOperation.php', 'VtigerModuleOperation', '1');", array($moduleName));
            $adb->pquery("UPDATE vtiger_ws_entity_seq SET id=(SELECT MAX(id) FROM vtiger_ws_entity)", array());
        }
    }
    /**
     * @param string $moduleName
     */
    public static function removeWidgetFrom($moduleName)
    {
        global $adb;
        $module = Vtiger_Module::getInstance($moduleName);
        $widgetName = "Custom Module Builder";
        $template_folders = array("vlayout", "v7");
        if ($module) {
            foreach ($template_folders as $folder) {
                $css_widgetType = "HEADERCSS";
                $css_widgetLabel = $widgetName;
                $css_link = "layouts/" . $folder . "/modules/" . $moduleName . "/resources/" . $moduleName . "CSS.css";
                $js_widgetType = "HEADERSCRIPT";
                $js_widgetLabel = $widgetName;
                $js_link = "layouts/" . $folder . "/modules/" . $moduleName . "/resources/" . $moduleName . "JS.js";
                $js_link_2 = "layouts/" . $folder . "/modules/" . $moduleName . "/resources/" . $moduleName . "Utils.js";
                $module->deleteLink($css_widgetType, $css_widgetLabel, $css_link);
                $module->deleteLink($js_widgetType, $js_widgetLabel, $js_link);
                $module->deleteLink($js_widgetType, $js_widgetLabel, $js_link_2);
            }
        }
        $adb->pquery("DELETE FROM `vtiger_ws_entity` WHERE `name` = ?", array($moduleName));
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("Custom Module Builder"));
    }
    /**
     * Invoked when special actions are performed on the module.
     * @param String Module name
     * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    public function vtlib_handler($modulename, $event_type)
    {
        if ($event_type == "module.postinstall") {
            self::addWidgetTo($modulename);
            self::resetValid($modulename);
            self::switchToTheSettingModule($modulename);
            self::updateCollation();
        } else {
            if ($event_type == "module.disabled") {
                self::removeWidgetFrom($modulename);
                self::removeHandle($modulename);
            } else {
                if ($event_type == "module.enabled") {
                    self::switchToTheSettingModule($modulename);
                    self::addWidgetTo($modulename);
                } else {
                    if ($event_type == "module.preuninstall") {
                        self::removeValid($modulename);
                        self::removeHandle($modulename);
                    } else {
                        if ($event_type == "module.preupdate") {
                            self::removeWidgetFrom($modulename);
                        } else {
                            if ($event_type == "module.postupdate") {
                                self::addWidgetTo($modulename);
                                self::resetValid($modulename);
                                self::switchToTheSettingModule($modulename);
                                self::removeHandle($modulename);
                                self::updateCollation();
                            }
                        }
                    }
                }
            }
        }
    }
    /**
     * @param $moduleName
     */
    public static function resetValid($moduleName)
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array($moduleName));
        $adb->pquery("INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);", array($moduleName, "0"));
    }
    /**
     * @param $moduleName
     */
    public static function removeValid($moduleName)
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array($moduleName));
    }
    /**
     * @link http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public function startsWith($haystack, $needle)
    {
        return $needle === "" || strrpos($haystack, $needle, 0 - strlen($haystack)) !== false;
    }
    /**
     * @link http://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public function endsWith($haystack, $needle)
    {
        return $needle === "" || 0 <= ($temp = strlen($haystack) - strlen($needle)) && strpos($haystack, $needle, $temp) !== false;
    }
    /**
     * @link http://stackoverflow.com/questions/6743554/problem-slash-with-json-encode-why-and-how-solve-it
     * JSON_UNESCAPED_SLASHES only support PHP version >= 5.4
     *
     * prevent json_encode() escaping forward slashes
     * @link http://stackoverflow.com/questions/10210338/json-encode-escaping-forward-slashes
     *
     * @param $jsonString
     * @return mixed
     */
    public static function jsonUnescapedSlashes($jsonString)
    {
        return str_replace("\\/", "/", $jsonString);
    }
    public static function getConfig()
    {
        global $site_URL;
        global $current_user;
        $data = array();
        $data["base"] = $site_URL;
        $data["date_format"] = $current_user->date_format;
        $data["hour_format"] = $current_user->hour_format;
        $data["start_hour"] = $current_user->start_hour;
        $data["end_hour"] = $current_user->end_hour;
        $data["time_zone"] = $current_user->time_zone;
        $data["dayoftheweek"] = $current_user->dayoftheweek;
        return $data;
    }
    public static function switchToTheSettingModule($modulename)
    {
        global $adb;
        $sqlUpdateTab = "UPDATE vtiger_tab SET parent = ? WHERE name = ?";
        $adb->pquery($sqlUpdateTab, array("", $modulename));
        $queryBlockSetting = "SELECT * FROM vtiger_settings_blocks WHERE label = ?";
        $pqueryBs = $adb->pquery($queryBlockSetting, array("LBL_OTHER_SETTINGS"));
        $blockModel = $adb->fetchByAssoc($pqueryBs);
        $blockid = $blockModel["blockid"];
        $sequenceField = "SELECT MAX(sequence) as sequence FROM vtiger_settings_field WHERE blockid = ?";
        $pquerySq = $adb->pquery($sequenceField, array($blockid));
        $row1 = $adb->fetchByAssoc($pquerySq);
        $sequence = $row1["sequence"] + 1;
        $rs = $adb->pquery("SELECT * FROM vtiger_settings_field WHERE name = ? OR name = ?", array("Module & Link Creator", "Custom Module Builder"));
        if ($adb->num_rows($rs) == 0) {
            $max_id = $adb->getUniqueID("vtiger_settings_field");
            $adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES (?, ?, ?, ?, ?, ?)", array($max_id, $blockid, "Custom Module Builder", vtranslate("Settings area for Module - Link Creator", $modulename), "index.php?module=ModuleLinkCreator&view=List", $sequence));
        } else {
            $id = "";
            while ($array = $adb->fetchByAssoc($rs)) {
                $id = $array["fieldid"];
            }
            if ($id) {
                $adb->pquery("UPDATE `vtiger_settings_field` SET `name` = ? WHERE fieldid = ?", array("Custom Module Builder", $id));
            }
        }
    }
    private function createHandle($moduleName)
    {
        include_once "include/events/VTEventsManager.inc";
        global $adb;
        $em = new VTEventsManager($adb);
        $em->setModuleForHandler($moduleName, (string) $moduleName . "Handler.php");
        $em->registerHandler("vtiger.entity.aftersave", "modules/" . $moduleName . "/" . $moduleName . "Handler.php", (string) $moduleName . "Handler");
        $em->registerHandler("vtiger.entity.beforedelete", "modules/" . $moduleName . "/" . $moduleName . "Handler.php", (string) $moduleName . "Handler");
    }
    /**
     * @param string $moduleName
     */
    private function removeHandle($moduleName)
    {
        include_once "include/events/VTEventsManager.inc";
        global $adb;
        $em = new VTEventsManager($adb);
        $em->unregisterHandler((string) $moduleName . "Handler");
    }
    public static function updateCollation()
    {
        global $adb;
        $sql = "SHOW FULL COLUMNS FROM vtiger_parenttab WHERE Field IN ('parenttab_label')";
        $res = $adb->pquery($sql, array());
        while ($row = $adb->fetchByAssoc($res)) {
            $vtiger_parenttab_collation = $row["collation"];
        }
        if ($vtiger_parenttab_collation != "utf8_general_ci") {
            $sql = "ALTER TABLE `vtiger_parenttab` MODIFY COLUMN `parenttab_label`  varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
            $adb->pquery($sql, array());
        }
    }
}

?>