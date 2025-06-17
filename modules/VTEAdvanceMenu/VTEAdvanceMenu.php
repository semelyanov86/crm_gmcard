<?php

require_once "data/CRMEntity.php";
require_once "data/Tracker.php";
require_once "vtlib/Vtiger/Module.php";

class VTEAdvanceMenu extends CRMEntity
{
    /**
     * Invoked when special actions are performed on the module.
     * @param String Module name
     * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    public function vtlib_handler($modulename, $event_type)
    {
        if ($event_type == "module.postinstall") {
            self::addHeaderScript();
            self::addSettingLink();
            self::resetValid();
            self::initData();
        } else {
            if ($event_type == "module.disabled") {
                self::removeSettingLink();
                self::removeHeaderScript();
            } else {
                if ($event_type == "module.enabled") {
                    self::addSettingLink();
                    self::addHeaderScript();
                } else {
                    if ($event_type == "module.preuninstall") {
                        self::removeValid();
                        self::removeSettingLink();
                        self::removeHeaderScript();
                    } else {
                        if ($event_type != "module.preupdate") {
                            if ($event_type == "module.postupdate") {
                                self::removeSettingLink();
                                self::addSettingLink();
                                self::removeHeaderScript();
                                self::addHeaderScript();
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
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("VTEAdvanceMenu"));
        $adb->pquery("INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);", array("VTEAdvanceMenu", "0"));
    }
    public static function removeValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("VTEAdvanceMenu"));
    }
    /**
     * Add header script to other module.
     * @return unknown_type
     */
    public static function addHeaderScript()
    {
        global $adb;
        $widgetType = "HEADERSCRIPT";
        $widgetName = "VTEAdvanceMenu";
        $link = "layouts/v7/modules/VTEAdvanceMenu/resources/VTEAdvanceMenu.js";
        $module = Vtiger_Module::getInstance("Accounts");
        $module->addLink($widgetType, $widgetName, $link);
        $adb->pquery("UPDATE vtiger_links SET tabid=0 WHERE linktype=? AND linklabel = ? ", array($widgetType, $widgetName));
    }
    public static function removeHeaderScript()
    {
        global $adb;
        $widgetType = "HEADERSCRIPT";
        $widgetName = "VTEAdvanceMenu";
        $adb->pquery("DELETE FROM vtiger_links WHERE linktype=? AND linklabel = ?", array($widgetType, $widgetName));
    }
    /**
     * Add Link to other Setting menu.
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
        $adb->pquery("INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES(?, ?, ?, ?, ?, ?)", array($max_id, $blockid, "Advanced Menu Manager", "Advanced Menu Manager", "index.php?module=VTEAdvanceMenu&view=Settings&parent=Settings", $max_id));
    }
    public static function removeSettingLink()
    {
        global $adb;
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` LIKE ?", array("Advanced Menu Manager"));
    }
    public static function initData()
    {
        global $adb;
        $adb->pquery("TRUNCATE TABLE vte_advance_menu_settings_menu");
        $adb->pquery("TRUNCATE TABLE vte_advance_menu_settings_groups");
        $adb->pquery("TRUNCATE TABLE vte_advance_menu_settings_menu_groups_rel");
        $adb->pquery("TRUNCATE TABLE vte_advance_menu_settings_menu_items");
        $menu_id = 1;
        $last_module_id = 1;
        $result_last_module = $adb->pquery("SELECT MAX(tabid) 'last_module_id' FROM vtiger_tab", array());
        $last_module_id = $adb->query_result($result_last_module, 0, "last_module_id");
        $adb->pquery("INSERT INTO vte_advance_menu_settings_menu(`menuid`, `creator`, `modified_by`, `active`, `last_module_id`) VALUES(?, ?, ?, ?, ?)", array(1, 1, 1, 1, $last_module_id));
        if (0 < $menu_id) {
            $groups = array("ESSENTIALS", "MARKETING", "SALES", "SUPPORT", "INVENTORY", "PROJECTS", "TOOLS");
            foreach ($groups as $k => $group) {
                $group_id = $k + 1;
                $params = array($group_id, 1, 1, 1, $group);
                $adb->pquery("INSERT INTO vte_advance_menu_settings_groups(`groupid`,`creator`,`modified_by`, `active`, `group_name`) VALUES(?, ?, ?, ?, ?)", $params);
                $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_groups_rel(`menuid`,`groupid`) VALUES(?, ?)", array($menu_id, $group_id));
                if ($group == "ESSENTIALS") {
                    $sequence = 1;
                    $link = "index.php?module=Home&view=DashBoard";
                    $label = vtranslate("Dashboard", "Dashboard");
                    $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(1, 1, 1, $group_id, $menu_id, "Module", "Dashboard", $link, $label, NULL, $sequence));
                    $sequence++;
                    $link = "index.php?module=Reports&view=List";
                    $label = vtranslate("Reports", "Reports");
                    $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(1, 1, 1, $group_id, $menu_id, "Module", "Reports", $link, $label, NULL, $sequence));
                    $sequence++;
                    $link = "index.php?module=MailManager&view=List";
                    $label = vtranslate("MailManager", "MailManager");
                    $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(1, 1, 1, $group_id, $menu_id, "Module", "MailManager", $link, $label, NULL, $sequence));
                    $sequence++;
                    $link = "";
                    $label = "Separator";
                    $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(1, 1, 1, $group_id, $menu_id, "Separator", "Separator", $link, $label, NULL, $sequence));
                    $sequence++;
                    $link = "index.php?module=Contacts&view=List";
                    $label = vtranslate("Contacts", "Contacts");
                    $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(1, 1, 1, $group_id, $menu_id, "Module", "Contacts", $link, $label, NULL, $sequence));
                    $sequence++;
                    $link = "index.php?module=Accounts&view=List";
                    $label = vtranslate("Accounts", "Accounts");
                    $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(1, 1, 1, $group_id, $menu_id, "Module", "Accounts", $link, $label, NULL, $sequence));
                    $sequence++;
                    $link = "";
                    $label = "Separator";
                    $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(1, 1, 1, $group_id, $menu_id, "Separator", "Separator", $link, $label, NULL, $sequence));
                    $sequence++;
                    $link = "index.php?module=Calendar&view=Calendar";
                    $label = vtranslate("Calendar", "Calendar");
                    $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(1, 1, 1, $group_id, $menu_id, "Module", "Calendar", $link, $label, NULL, $sequence));
                    $sequence++;
                    $link = "index.php?module=Documents&view=List";
                    $label = vtranslate("Documents", "Documents");
                    $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", array(1, 1, 1, $group_id, $menu_id, "Module", "Documents", $link, $label, NULL, $sequence));
                } else {
                    if ($group == "TOOLS") {
                        $item_res = $adb->pquery("SELECT * FROM vtiger_tab WHERE presence IN(0, 2) AND `parent` = 'Tools'", array());
                        $ignore_modules = array("Emails", "Calendar", "MailManager", "ModComments");
                        if ($adb->num_rows($item_res)) {
                            $sequence = 1;
                            while ($row = $adb->fetchByAssoc($item_res)) {
                                if (!in_array($row["name"], $ignore_modules)) {
                                    $link = "index.php?module=" . $row["name"] . "&view=List";
                                    $label = vtranslate($row["name"], $row["name"]);
                                    $item_params = array(1, 1, 1, $group_id, $menu_id, "Module", $row["name"], $link, $label, NULL, $sequence);
                                    $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $item_params);
                                    $sequence++;
                                }
                            }
                        }
                    } else {
                        if ($group == "PROJECTS") {
                            $params = array("PROJECT");
                        } else {
                            $params = array($group);
                        }
                        $item_res = $adb->pquery("SELECT vtiger_tab.* \r\n                                                    FROM vtiger_app2tab\r\n                                                    INNER JOIN vtiger_tab ON vtiger_tab.tabid = vtiger_app2tab.tabid\r\n                                                    WHERE vtiger_tab.presence IN(0, 2) AND vtiger_app2tab.appname = ?", $params);
                        if ($adb->num_rows($item_res)) {
                            for ($sequence = 1; $row = $adb->fetchByAssoc($item_res); $sequence++) {
                                $link = "index.php?module=" . $row["name"] . "&view=List";
                                $label = vtranslate($row["name"], $row["name"]);
                                $item_params = array(1, 1, 1, $group_id, $menu_id, "Module", $row["name"], $link, $label, NULL, $sequence);
                                $adb->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`, `active`, `groupid`, `menuid`, `type`, `module`, `link`, `label`, `filter`, `sequence`) \r\n                                              VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", $item_params);
                            }
                        }
                    }
                }
            }
        }
    }
}

?>