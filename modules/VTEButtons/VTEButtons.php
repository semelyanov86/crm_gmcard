<?php

require_once "data/CRMEntity.php";
require_once "data/Tracker.php";
require_once "vtlib/Vtiger/Module.php";

class VTEButtons extends CRMEntity
{
    /**
     * Invoked when special actions are performed on the module.
     * @param String Module name
     * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    public function vtlib_handler($modulename, $event_type)
    {
        if ($event_type == "module.postinstall") {
            self::addWidgetTo();
            self::iniData();
            self::resetValid();
            self::updateModule($modulename);
        } else {
            if ($event_type == "module.disabled") {
                self::removeWidgetTo();
            } else {
                if ($event_type == "module.enabled") {
                    self::addWidgetTo();
                } else {
                    if ($event_type == "module.preuninstall") {
                        self::removeWidgetTo();
                        self::removeValid();
                    } else {
                        if ($event_type != "module.preupdate") {
                            if ($event_type == "module.postupdate") {
                                self::removeWidgetTo();
                                self::addWidgetTo();
                                self::iniData();
                                self::resetValid();
                                self::updateModule($modulename);
                                self::addTables();
                            }
                        }
                    }
                }
            }
        }
    }
    public static function iniData()
    {
        global $adb;
    }
    /**
     * Add header script to other module.
     * @return unknown_type
     */
    public static function addWidgetTo()
    {
        global $adb;
        global $vtiger_current_version;
        $widgetType = "HEADERSCRIPT";
        $widgetName = "VTEButtonsJs";
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
        } else {
            $template_folder = "layouts/v7";
        }
        $link = $template_folder . "/modules/VTEButtons/resources/VTEButtons.js";
        include_once "vtlib/Vtiger/Module.php";
        $moduleNames = array("VTEButtons");
        foreach ($moduleNames as $moduleName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->addLink($widgetType, $widgetName, $link);
            }
        }
        $rs = $adb->pquery("SELECT * FROM `vtiger_settings_field` WHERE `name` = ?", array("Buttons"));
        if ($adb->num_rows($rs) == 0) {
            $max_id = $adb->getUniqueID("vtiger_settings_field");
            $blockid = 4;
            $res = $adb->pquery("SELECT blockid FROM `vtiger_settings_blocks` WHERE label='LBL_OTHER_SETTINGS'", array());
            if (0 < $adb->num_rows($res)) {
                while ($row = $adb->fetch_row($res)) {
                    $blockid = $row["blockid"];
                }
            }
            $adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES (?, ?, ?, ?, ?, ?)", array($max_id, $blockid, "Buttons", "Settings area for VTE Buttons", "index.php?module=VTEButtons&parent=Settings&view=Settings", $max_id));
        }
        $rs = $adb->pquery("SELECT * FROM `vtiger_ws_entity` WHERE `name` = ?", array("VTEButtons"));
        if ($adb->num_rows($rs) == 0) {
            $adb->pquery("INSERT INTO `vtiger_ws_entity` (`name`, `handler_path`, `handler_class`, `ismodule`)\r\n            VALUES (?, 'include/Webservices/VtigerModuleOperation.php', 'VtigerModuleOperation', '1');", array("VTEButtons"));
            $adb->pquery("UPDATE vtiger_ws_entity_seq SET id=(SELECT MAX(id) FROM vtiger_ws_entity)", array());
        }
    }
    public static function removeWidgetTo()
    {
        global $adb;
        global $vtiger_current_version;
        $widgetType = "HEADERSCRIPT";
        $widgetName = "VTEButtonsJs";
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
        } else {
            $template_folder = "layouts/v7";
        }
        $link = $template_folder . "/modules/VTEButtons/resources/VTEButtons.js";
        include_once "vtlib/Vtiger/Module.php";
        $moduleNames = array("VTEButtons");
        foreach ($moduleNames as $moduleName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->deleteLink($widgetType, $widgetName, $link);
            }
        }
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("Buttons"));
        $adb->pquery("DELETE FROM vtiger_ws_entity WHERE `name` = ? AND `handler_path` = ?", array("VTEButtons", "include/Webservices/VtigerModuleOperation.php"));
    }
    public static function removeValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("VTEButtons"));
    }
    public static function resetValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("VTEButtons"));
        $adb->pquery("INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);", array("VTEButtons", "0"));
    }
    public static function updateModule($moduleName)
    {
        require_once "modules/VTEButtons/scripts/add_settings_column_20190129.php";
    }
    public static function checkTableExist($tableName)
    {
        global $adb;
        $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = ? AND table_name = ?";
        $res = $adb->pquery($sql, array($adb->dbName, $tableName));
        if (0 < $adb->num_rows($res)) {
            return true;
        }
        return false;
    }
    public static function checkColumnExist($tableName, $columnName)
    {
        global $adb;
        $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = ? AND table_name = ? AND column_name = ?";
        $res = $adb->pquery($sql, array($adb->dbName, $tableName, $columnName));
        if (0 < $adb->num_rows($res)) {
            return true;
        }
        return false;
    }
    public static function addTables()
    {
        global $adb;
        if (!self::checkTableExist("vte_buttons_customjs")) {
            $sql = "CREATE TABLE `vte_buttons_customjs` (\r\n\t\t\t\t\t  `is_active` int(1) DEFAULT '0',\r\n\t\t\t\t\t  `custom_script` text\r\n\t\t\t\t\t  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $adb->pquery($sql, array());
        }
    }
}

?>