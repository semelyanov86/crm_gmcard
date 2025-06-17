<?php

require_once "data/CRMEntity.php";
require_once "data/Tracker.php";
require_once "vtlib/Vtiger/Module.php";

class VTEConditionalAlerts extends CRMEntity
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
            self::resetValid();
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
                                self::resetValid();
                            }
                        }
                    }
                }
            }
        }
    }
    public static function resetValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("VTEConditionalAlerts"));
        $adb->pquery("INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);", array("VTEConditionalAlerts", "0"));
    }
    public static function removeValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("VTEConditionalAlerts"));
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
        $widgetName = "VTEConditionalAlertsJs";
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
        } else {
            $template_folder = "layouts/v7";
        }
        $link = $template_folder . "/modules/VTEConditionalAlerts/resources/VTEConditionalAlerts.js";
        include_once "vtlib/Vtiger/Module.php";
        $moduleNames = array("VTEConditionalAlerts");
        foreach ($moduleNames as $moduleName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->addLink($widgetType, $widgetName, $link);
            }
        }
        $max_id = $adb->getUniqueID("vtiger_settings_field");
        $result = $adb->pquery("SELECT * FROM vtiger_settings_field WHERE `name` = ?)", array("Conditional Alerts/Popups"));
        $numRows = $adb->num_rows($result);
        if (0 >= $numRows) {
            $blockid = 4;
            $res = $adb->pquery("SELECT blockid FROM `vtiger_settings_blocks` WHERE label='LBL_OTHER_SETTINGS'", array());
            if (0 < $adb->num_rows($res)) {
                while ($row = $adb->fetch_row($res)) {
                    $blockid = $row["blockid"];
                }
            }
            $adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES (?, ?, ?, ?, ?, ?)", array($max_id, $blockid, "Conditional Alerts/Popups", "Settings area for Conditional Alerts/Popups", "index.php?module=VTEConditionalAlerts&parent=Settings&view=ListAll&mode=listAll", $max_id));
        }
    }
    public static function removeWidgetTo()
    {
        global $adb;
        global $vtiger_current_version;
        $widgetType = "HEADERSCRIPT";
        $widgetName = "VTEConditionalAlertsJs";
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
            $vtVersion = "vt6";
            $linkVT6 = $template_folder . "/modules/VTEConditionalAlerts/resources/VTEConditionalAlerts.js";
        } else {
            $template_folder = "layouts/v7";
            $vtVersion = "vt7";
        }
        $link = $template_folder . "/modules/VTEConditionalAlerts/resources/VTEConditionalAlerts.js";
        include_once "vtlib/Vtiger/Module.php";
        $moduleNames = array("VTEConditionalAlerts");
        foreach ($moduleNames as $moduleName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->deleteLink($widgetType, $widgetName, $link);
                if ($vtVersion != "vt6") {
                    $module->deleteLink($widgetType, $widgetName, $linkVT6);
                }
            }
        }
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("Conditional Alerts/Popups"));
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $needDeleteFolder = "layouts/v7";
        } else {
            $needDeleteFolder = "layouts/vlayout";
        }
        if (is_dir($needDeleteFolder)) {
            rmdir($needDeleteFolder);
        }
    }
}

?>