<?php

class Settings_VTEAdvanceMenu_Module_Model extends Settings_Vtiger_Module_Model
{
    public $db = NULL;
    public $user = NULL;
    public function __construct()
    {
        $this->db = PearDatabase::getInstance();
        $this->user = Users_Record_Model::getCurrentUserModel();
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
                $group["items"] = $this->getMenuItems($group["groupid"], $menu_id);
                $menu[$group["group_name"]] = $group;
            }
        }
        return $menu;
    }
    public function getGroups($menu_id)
    {
        $groups = array();
        $query = "SELECT vte_advance_menu_settings_groups.*, vte_advance_menu_settings_menu_groups_rel.menuid\n                  FROM vte_advance_menu_settings_groups\n                  INNER JOIN vte_advance_menu_settings_menu_groups_rel ON vte_advance_menu_settings_menu_groups_rel.groupid = vte_advance_menu_settings_groups.groupid\n                  INNER JOIN vte_advance_menu_settings_menu ON vte_advance_menu_settings_menu.menuid = vte_advance_menu_settings_menu_groups_rel.menuid\n                  WHERE vte_advance_menu_settings_menu.active = ? AND vte_advance_menu_settings_groups.active = ? AND vte_advance_menu_settings_menu.menuid = ?";
        $result = $this->db->pquery($query, array(1, 1, $menu_id));
        if (0 < $this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $groups[] = $row;
            }
        }
        return $groups;
    }
    public function getMenuItems($group_id = false, $menu_id = false)
    {
        $menu_items = array();
        if (empty($group_id) || empty($menu_id)) {
            return $menu_items;
        }
        $query = "SELECT * FROM vte_advance_menu_settings_menu_items\n                    WHERE vte_advance_menu_settings_menu_items.active = 1 \n                    AND vte_advance_menu_settings_menu_items.groupid = ?\n                    AND vte_advance_menu_settings_menu_items.menuid = ?\n                    ORDER BY vte_advance_menu_settings_menu_items.sequence ";
        $result = $this->db->pquery($query, array($group_id, $menu_id));
        if ($this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $menu_items[] = $row;
            }
        }
        return $menu_items;
    }
    public static function getAllVisibleModules()
    {
        $modules = array();
        $presence = array("0", "2");
        $db = PearDatabase::getInstance();
        $restrictedModulesList = array("Emails", "ModComments", "Integration", "PBXManager", "Dashboard", "Home", "Events", "Rooms", "RoomsMessages", "RoomsUsers", "VTEMailConverter", "ChecklistItems", "VTEQBOLogs", "VTEQBOQueues", "VTEQBOLinks", "VTEPayments", "ANPaymentProfile", "ANCustomers", "ANTransactions", "VTESLALog", "VTEItems", "Office365Links", "Office365Queues", "Office365Logs", "VTEDeposits");
        $result = $db->pquery("SELECT * FROM vtiger_tab WHERE presence IN(0, 2) AND isentitytype = 1 ORDER BY tabid", array());
        $count = $db->num_rows($result);
        $userPrivModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (0 < $count) {
            for ($i = 0; $i < $count; $i++) {
                $appname = $db->query_result($result, $i, "appname");
                $tabid = $db->query_result($result, $i, "tabid");
                $sequence = $db->query_result($result, $i, "sequence");
                $moduleName = getTabModuleName($tabid);
                if ($moduleName === "Transactions") {
                    continue;
                }
                $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
                if (empty($moduleModel) || $moduleName == "VTEAdvanceMenu") {
                    continue;
                }
                if (($userPrivModel->isAdminUser() || $userPrivModel->hasGlobalReadPermission() || $userPrivModel->hasModulePermission($moduleModel->getId())) && in_array($moduleModel->get("presence"), $presence) && !in_array($moduleName, $restrictedModulesList)) {
                    $modules[$moduleName] = $moduleName;
                }
            }
        }
        return $modules;
    }
    public function getModuleFilter($source_module)
    {
        $filters = array();
        if (empty($source_module)) {
            return $filters;
        }
        $query = "SELECT * FROM `vtiger_customview` WHERE `entitytype` LIKE ? ORDER BY `cvid`";
        $result = $this->db->pquery($query, array($source_module));
        if ($this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $filters[] = $row;
            }
        }
        return $filters;
    }
    public function getGroupDetails($group_id)
    {
        $group_info = array();
        if (empty($group_id)) {
            return $group_info;
        }
        $icon_class_default = array("ESSENTIALS" => "fa fa-diamond", "MARKETING" => "app-icon-list fa fa-users", "SALES" => "fa fa-dot-circle-o", "SUPPORT" => "fa fa-life-ring", "INVENTORY" => "fa vicon-inventory", "PROJECTS" => "fa fa-briefcase", "TOOLS" => "fa fa-wrench");
        $query = "SELECT * FROM `vte_advance_menu_settings_groups` WHERE `groupid` = ? ";
        $result = $this->db->pquery($query, array($group_id));
        if ($this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                if (empty($row["label"])) {
                    $row["label"] = $row["group_name"];
                }
                if (empty($row["icon_class"])) {
                    $row["icon_class"] = $icon_class_default[$row["group_name"]];
                }
                $group_info = $row;
            }
        }
        return $group_info;
    }
}

?>