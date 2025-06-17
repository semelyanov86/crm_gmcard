<?php

class Settings_VTEAdvanceMenu_SaveAjax_Action extends Settings_Vtiger_IndexAjax_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("removeMenuItem");
        $this->exposeMethod("moveMenuItem");
        $this->exposeMethod("addModule");
        $this->exposeMethod("addLink");
        $this->exposeMethod("addFilter");
        $this->exposeMethod("addSeparator");
        $this->exposeMethod("saveSequence");
        $this->exposeMethod("UpdateGroupMenu");
        $this->exposeMethod("saveGroupDetail");
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
            if ($request->has("menu_id")) {
                $menu_id = $request->get("menu_id");
                if (0 < (int) $menu_id) {
                    $this->updateLastModuleId($menu_id);
                }
            }
        }
    }
    public function removeMenuItem(Vtiger_Request $request)
    {
        $item_id = $request->get("item_id");
        $db = PearDatabase::getInstance();
        $db->pquery("DELETE FROM vte_advance_menu_settings_menu_items WHERE itemid = ?", array($item_id));
        $response = new Vtiger_Response();
        $response->setResult(array("success" => true));
        $response->emit();
    }
    public function addModule(Vtiger_Request $request)
    {
        $current_user = Users_Record_Model::getCurrentUserModel();
        $userId = $current_user->getId();
        $sourceModules = array();
        if ($request->has("sourceModules")) {
            $sourceModules = $request->get("sourceModules");
        }
        $menu_id = $request->get("menu_id");
        $group_id = $request->get("group_id");
        if (!empty($sourceModules) && !empty($menu_id) && !empty($group_id)) {
            $db = PearDatabase::getInstance();
            $max_sequence = 1;
            $query = "SELECT max(`sequence`) as 'max_sequence'\r\n                    FROM vte_advance_menu_settings_menu_items\r\n                    WHERE `groupid` = ? AND `menuid` = ?";
            $result = $db->pquery($query, array($group_id, $menu_id));
            if ($db->num_rows($result)) {
                $max_sequence = (int) $db->query_result($result, 0, "max_sequence");
                $max_sequence++;
            }
            foreach ($sourceModules as $sourceModule) {
                if ($sourceModule == "VTETimesheets") {
                    $link = "index.php?module=" . $sourceModule . "&view=Listview";
                } else {
                    $link = "index.php?module=" . $sourceModule . "&view=List";
                }
                $label = vtranslate($sourceModule, $sourceModule);
                $params = array($userId, $userId, 1, $group_id, $menu_id, "Module", $sourceModule, $link, $label, NULL, $max_sequence);
                $db->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`,`active`,`groupid`,`menuid`,`type`,`module`,`link`,`label`,`filter`,`sequence`) \r\n                                VALUES(?,?,?,?,?,?,?,?,?,?,?)", array($params));
                $max_sequence++;
            }
        }
        $response = new Vtiger_Response();
        $response->setResult(array("success" => true));
        $response->emit();
    }
    public function addLink(Vtiger_Request $request)
    {
        $current_user = Users_Record_Model::getCurrentUserModel();
        $userId = $current_user->getId();
        $menu_id = $request->get("menu_id");
        $group_id = $request->get("group_id");
        $label = $request->get("menu_label");
        $link = $request->get("menu_link");
        if (!empty($label) && !empty($link) && !empty($menu_id) && !empty($group_id)) {
            $db = PearDatabase::getInstance();
            $max_sequence = 1;
            $query = "SELECT max(`sequence`) as 'max_sequence'\r\n                    FROM vte_advance_menu_settings_menu_items\r\n                    WHERE `groupid` = ? AND `menuid` = ?";
            $result = $db->pquery($query, array($group_id, $menu_id));
            if ($db->num_rows($result)) {
                $max_sequence = (int) $db->query_result($result, 0, "max_sequence");
                $max_sequence++;
            }
            $params = array($userId, $userId, 1, $group_id, $menu_id, "Link", "Link", $link, $label, NULL, $max_sequence);
            $db->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`,`active`,`groupid`,`menuid`,`type`,`module`,`link`,`label`,`filter`,`sequence`) \r\n                                VALUES(?,?,?,?,?,?,?,?,?,?,?)", array($params));
        }
        $response = new Vtiger_Response();
        $response->setResult(array("success" => true));
        $response->emit();
    }
    public function addFilter(Vtiger_Request $request)
    {
        $db = PearDatabase::getInstance();
        $current_user = Users_Record_Model::getCurrentUserModel();
        $userId = $current_user->getId();
        $menu_id = $request->get("menu_id");
        $group_id = $request->get("group_id");
        $source_module = $request->get("source_module");
        $filter = $request->get("filter");
        $res = $db->pquery("SELECT * FROM `vtiger_customview` WHERE `cvid` = ?", array($filter));
        $filter_name = "";
        if ($db->num_rows($res)) {
            $filter_name = $db->query_result($res, 0, "viewname");
        }
        if (!empty($source_module) && !empty($filter_name) && !empty($menu_id) && !empty($group_id)) {
            $max_sequence = 1;
            $query = "SELECT max(`sequence`) as 'max_sequence'\r\n                    FROM vte_advance_menu_settings_menu_items\r\n                    WHERE `groupid` = ? AND `menuid` = ?";
            $result = $db->pquery($query, array($group_id, $menu_id));
            if ($db->num_rows($result)) {
                $max_sequence = (int) $db->query_result($result, 0, "max_sequence");
                $max_sequence++;
            }
            $label = $filter_name;
            $link = "index.php?module=" . $source_module . "&view=List&viewname=" . $filter;
            $params = array($userId, $userId, 1, $group_id, $menu_id, "Filter", $source_module, $link, $label, $filter, $max_sequence);
            $db->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`,`active`,`groupid`,`menuid`,`type`,`module`,`link`,`label`,`filter`,`sequence`) \r\n                                VALUES(?,?,?,?,?,?,?,?,?,?,?)", array($params));
        }
        $response = new Vtiger_Response();
        $response->setResult(array("success" => true));
        $response->emit();
    }
    public function addSeparator(Vtiger_Request $request)
    {
        $current_user = Users_Record_Model::getCurrentUserModel();
        $userId = $current_user->getId();
        $menu_id = $request->get("menu_id");
        $group_id = $request->get("group_id");
        if (!empty($menu_id) && !empty($group_id)) {
            $db = PearDatabase::getInstance();
            $max_sequence = 1;
            $query = "SELECT max(`sequence`) as 'max_sequence'\r\n                    FROM vte_advance_menu_settings_menu_items\r\n                    WHERE `groupid` = ? AND `menuid` = ?";
            $result = $db->pquery($query, array($group_id, $menu_id));
            if ($db->num_rows($result)) {
                $max_sequence = (int) $db->query_result($result, 0, "max_sequence");
                $max_sequence++;
            }
            $label = "Separator";
            $link = "";
            $params = array($userId, $userId, 1, $group_id, $menu_id, "Separator", "Separator", $link, $label, NULL, $max_sequence);
            $db->pquery("INSERT INTO vte_advance_menu_settings_menu_items(`creator`,`modified_by`,`active`,`groupid`,`menuid`,`type`,`module`,`link`,`label`,`filter`,`sequence`) \r\n                                VALUES(?,?,?,?,?,?,?,?,?,?,?)", array($params));
        }
        $response = new Vtiger_Response();
        $response->setResult(array("success" => true));
        $response->emit();
    }
    public function UpdateGroupMenu(Vtiger_Request $request)
    {
        $current_user = Users_Record_Model::getCurrentUserModel();
        $userId = $current_user->getId();
        $menu_id = $request->get("menu_id");
        $group_id = $request->get("group_id");
        $menu_item_ids = $request->get("item_ids");
        $item_number = count($menu_item_ids);
        if (!empty($menu_id) && !empty($group_id) && !empty($menu_item_ids) && 0 < $item_number) {
            $db = PearDatabase::getInstance();
            foreach ($menu_item_ids as $k => $item_id) {
                $sequence = $k + 1;
                $db->pquery("UPDATE vte_advance_menu_settings_menu_items SET `menuid`=?, `groupid`=?, `sequence`=?, `modified_by`=? WHERE `itemid`=?", array($menu_id, $group_id, $sequence, $userId, $item_id));
            }
        }
        $response = new Vtiger_Response();
        $response->setResult(array("success" => true));
        $response->emit();
    }
    public function saveGroupDetail(Vtiger_Request $request)
    {
        $current_user = Users_Record_Model::getCurrentUserModel();
        $userId = $current_user->getId();
        $group_id = $request->get("group_id");
        $label = $request->get("label");
        $icon_class = $request->get("icon_class");
        if (!empty($group_id)) {
            $db = PearDatabase::getInstance();
            $db->pquery("UPDATE vte_advance_menu_settings_groups SET `label`=?, `icon_class`=?, `modified_by`=? WHERE `groupid`=?", array($label, $icon_class, $userId, $group_id));
        }
        $response = new Vtiger_Response();
        $response->setResult(array("success" => true));
        $response->emit();
    }
    public function updateLastModuleId($menu_id)
    {
        if (0 < (int) $menu_id) {
            $db = PearDatabase::getInstance();
            $db->pquery("UPDATE vte_advance_menu_settings_menu\r\n                                SET last_module_id = (\r\n                                    SELECT\r\n                                        MAX(tabid)\r\n                                    FROM\r\n                                        vtiger_tab\r\n                                )\r\n                                WHERE menuid = ? ", array($menu_id));
        }
    }
}

?>