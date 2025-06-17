<?php

require_once "data/CRMEntity.php";
require_once "data/Tracker.php";
require_once "vtlib/Vtiger/Module.php";

class UserLogin extends CRMEntity
{
    /**
     * Invoked when special actions are performed on the module.
     * @param String Module name
     * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    public function vtlib_handler($modulename, $event_type)
    {
        if ($event_type == "module.postinstall") {
            self::addSettingLink();
            self::resetValid();
        } else {
            if ($event_type == "module.disabled") {
                self::removeSettingLink();
            } else {
                if ($event_type == "module.enabled") {
                    self::addSettingLink();
                } else {
                    if ($event_type == "module.preuninstall") {
                        self::removeValid();
                    } else {
                        if ($event_type != "module.preupdate") {
                            if ($event_type == "module.postupdate") {
                                self::removeSettingLink();
                                self::addSettingLink();
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
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("UserLogin"));
        $adb->pquery("INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);", array("UserLogin", "0"));
    }
    public static function removeValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("UserLogin"));
    }
    /**
     * Add header script to other module.
     * @return unknown_type
     */
    public static function addSettingLink()
    {
        global $adb;
        $blockid = 4;
        $res = $adb->pquery("SELECT blockid FROM `vtiger_settings_blocks` WHERE label='LBL_OTHER_SETTINGS'", array());
        if (0 < $adb->num_rows($res)) {
            while ($row = $adb->fetch_row($res)) {
                $blockid = $row["blockid"];
            }
        }
        $adb->pquery("UPDATE vtiger_settings_field_seq SET id=(SELECT MAX(fieldid) FROM vtiger_settings_field)", array());
        $max_id = $adb->getUniqueID("vtiger_settings_field");
        $adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES(?, ?, ?, ?, ?, ?)", array($max_id, $blockid, "Custom Login Page", "Custom Login Page", "index.php?module=UserLogin&view=Settings&parent=Settings", "999"));
    }
    public static function removeSettingLink()
    {
        global $adb;
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` LIKE ?", array("Custom Login Page"));
    }
}

?>