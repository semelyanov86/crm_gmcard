<?php

class VTEAdvanceMenu_Module_Model extends Vtiger_Module_Model
{
    public $user = NULL;
    public $db = NULL;
    public function __construct()
    {
        $this->user = Users_Record_Model::getCurrentUserModel();
        $this->db = PearDatabase::getInstance();
    }
    /**
     * Function to get Settings links for admin user
     * @return Array
     */
    public function getSettingLinks()
    {
        $settingsLinks = parent::getSettingLinks();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if ($currentUserModel->isAdminUser()) {
            $settingsLinks[] = array("linktype" => "LISTVIEWSETTING", "linklabel" => vtranslate("LBL_LINK_SETTINGS", $this->getName()), "linkurl" => "index.php?module=VTEAdvanceMenu&view=Settings&parent=Settings", "linkicon" => "");
        }
        if ($currentUserModel->isAdminUser()) {
            $settingsLinks[] = array("linktype" => "LISTVIEWSETTING", "linklabel" => vtranslate("LBL_LINK_UNINSTALL", $this->getName()), "linkurl" => "index.php?module=VTEAdvanceMenu&view=Uninstall&parent=Settings", "linkicon" => "");
        }
        return $settingsLinks;
    }
    public function getNewEntityModules($menu_id)
    {
        $new_entity_modules = array();
        if (!$this->user->isAdminUser()) {
            return $new_entity_modules;
        }
        $result = $this->db->pquery("SELECT * FROM `vte_advance_menu_settings_menu` WHERE menuid = ?", array($menu_id));
        if ($this->db->num_rows($result)) {
            $last_module_id = $this->db->query_result($result, 0, "last_module_id");
            if (0 < (int) $last_module_id) {
                $restrictedModulesList = array("Emails", "ModComments", "Integration", "PBXManager", "Dashboard", "Home", "Events", "Rooms", "RoomsMessages", "RoomsUsers", "VTEMailConverter", "ChecklistItems", "VTEQBOLogs", "VTEQBOQueues", "VTEQBOLinks", "VTEPayments", "ANPaymentProfile", "ANCustomers", "ANTransactions", "VTESLALog", "VTEItems", "Office365Links", "Office365Queues", "Office365Logs", "VTEDeposits");
                $result2 = $this->db->pquery("SELECT `tabid`, `name`, `tablabel`\r\n                                                FROM vtiger_tab \r\n                                                WHERE \r\n                                                   `presence` IN(0, 2) \r\n                                                   AND `isentitytype` = 1\r\n                                                   AND  `tabid` > ?\r\n                                                   AND `name` NOT IN ('" . implode("','", $restrictedModulesList) . "') \r\n                                                ORDER BY `tabid` ", array($last_module_id));
                if ($this->db->num_rows($result2)) {
                    while ($row = $this->db->fetchByAssoc($result2)) {
                        $new_entity_modules[$row["name"]] = $row;
                    }
                }
            }
        }
        return $new_entity_modules;
    }
    public function addNewModulesToTools($menu_id)
    {
        $new_modules = $this->getNewEntityModules($menu_id);
        if (!empty($new_modules) && $this->user->isAdminUser()) {
            $tools_result = $this->db->pquery("SELECT\r\n                                                        g.groupid\r\n                                                    FROM\r\n                                                        vte_advance_menu_settings_groups g\r\n                                                    INNER JOIN vte_advance_menu_settings_menu_groups_rel gr ON gr.groupid = g.groupid\r\n                                                    WHERE\r\n                                                        gr.menuid = 1\r\n                                                    AND g.group_name = 'TOOLS' LIMIT 1");
            if ($this->db->num_rows($tools_result)) {
                $group_id = $this->db->query_result($tools_result, 0, "groupid");
                $max_sequence = 1;
                $query = "SELECT max(`sequence`) as 'max_sequence'\r\n                    FROM vte_advance_menu_settings_menu_items\r\n                    WHERE `groupid` = ? AND `menuid` = ?";
                $result = $this->db->pquery($query, array($group_id, $menu_id));
                if ($this->db->num_rows($result)) {
                    $max_sequence = (int) $this->db->query_result($result, 0, "max_sequence");
                    $max_sequence++;
                }
                foreach ($new_modules as $moduleName => $newModuleInfo) {
                    if ($moduleName == "Events") {
                        $moduleName = "Calendar";
                    }
                    if ($moduleName == "Calendar") {
                        $link = "index.php?module=" . $moduleName . "&view=Calendar";
                    } else {
                        if ($moduleName == "VTETimesheets") {
                            $link = "index.php?module=" . $moduleName . "&view=Listview";
                        } else {
                            $link = "index.php?module=" . $moduleName . "&view=List";
                        }
                    }
                    $label = vtranslate($moduleName, $moduleName);
                    $params = array($this->user->getId(), $this->user->getId(), 1, (int) $group_id, $menu_id, "Module", $moduleName, $link, $label, "", $max_sequence);
                    $this->db->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`,`active`,`groupid`,`menuid`,`type`,`module`,`link`,`label`,`filter`,`sequence`) \r\n                                VALUES(?,?,?,?,?,?,?,?,?,?,?)", array($params));
                    $max_sequence++;
                }
                $this->updateLastModuleId($menu_id);
            }
        }
    }
    public function getMenu($menu_id)
    {
        $menu = array();
        $groups = $this->getGroups($menu_id);
        $icon_class_default = array("ESSENTIALS" => "fa fa-diamond", "MARKETING" => "app-icon-list fa fa-users", "SALES" => "fa fa-dot-circle-o", "SUPPORT" => "fa fa-life-ring", "INVENTORY" => "fa vicon-inventory", "PROJECTS" => "fa fa-briefcase", "TOOLS" => "fa fa-wrench");
        if (!empty($groups)) {
            foreach ($groups as $group) {
                if (trim($group["label"]) == "") {
                    $group["label"] = $group["group_name"];
                }
                if (trim($group["icon_class"]) == "") {
                    $group["icon_class"] = $icon_class_default[$group["group_name"]];
                }
                $group["items"] = $this->getMenuItems($group["groupid"], $menu_id, $group["group_name"]);
                $menu[$group["group_name"]] = $group;
            }
        }
        return $menu;
    }
    public function getGroups($menu_id)
    {
        $groups = array();
        $query = "SELECT vte_advance_menu_settings_groups.*, vte_advance_menu_settings_menu_groups_rel.menuid\r\n                  FROM vte_advance_menu_settings_groups\r\n                  INNER JOIN vte_advance_menu_settings_menu_groups_rel ON vte_advance_menu_settings_menu_groups_rel.groupid = vte_advance_menu_settings_groups.groupid\r\n                  INNER JOIN vte_advance_menu_settings_menu ON vte_advance_menu_settings_menu.menuid = vte_advance_menu_settings_menu_groups_rel.menuid\r\n                  WHERE vte_advance_menu_settings_menu.active = ? AND vte_advance_menu_settings_groups.active = ? AND vte_advance_menu_settings_menu.menuid = ?";
        $result = $this->db->pquery($query, array(1, 1, $menu_id));
        if (0 < $this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $groups[] = $row;
            }
        }
        return $groups;
    }
    public function getMenuItems($group_id, $menu_id, $group_name = "")
    {
        $USER_PRIVILEGES_MODEL = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $menu_items = array();
        if (empty($group_id) || empty($menu_id)) {
            return $menu_items;
        }
        $query = "SELECT * FROM vte_advance_menu_settings_menu_items\r\n                    WHERE vte_advance_menu_settings_menu_items.active = 1 \r\n                    AND vte_advance_menu_settings_menu_items.groupid = ?\r\n                    AND vte_advance_menu_settings_menu_items.menuid = ?\r\n                    ORDER BY vte_advance_menu_settings_menu_items.sequence ";
        $result = $this->db->pquery($query, array($group_id, $menu_id));
        if ($this->db->num_rows($result)) {
            $customView = new CustomView();
            while ($row = $this->db->fetchByAssoc($result)) {
                if ($row["type"] == "Module" || $row["type"] == "Filter") {
                    $moduleName = $row["module"];
                    $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
                    $customViews = CustomView_Record_Model::getAll($moduleName);
                    $customViewAvailableIds = array();
                    if (!empty($customViews)) {
                        foreach ($customViews as $k => $v) {
                            $customViewAvailableIds[] = $v->get("cvid");
                        }
                    }
                    if (!is_bool($moduleModel) && $USER_PRIVILEGES_MODEL->hasModulePermission($moduleModel->getId()) && $moduleModel->isActive()) {
                        if ($row["type"] == "Filter") {
                            $viewId = $row["filter"];
                            if ((int) $viewId && ($customView->isPermittedCustomView($viewId, "List", $moduleName) == "yes" || in_array($viewId, $customViewAvailableIds))) {
                                $menu_items[] = $row;
                            }
                        } else {
                            $menu_items[] = $row;
                        }
                    }
                } else {
                    $menu_items[] = $row;
                }
            }
        }
        return $menu_items;
    }
    public function updateLastModuleId($menu_id)
    {
        if (0 < (int) $menu_id) {
            $this->db->pquery("UPDATE vte_advance_menu_settings_menu\r\n                                SET last_module_id = (\r\n                                    SELECT\r\n                                        MAX(tabid)\r\n                                    FROM\r\n                                        vtiger_tab\r\n                                )\r\n                                WHERE menuid = ? ", array($menu_id));
        }
    }
}

?>