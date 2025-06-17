<?php

class Settings_ChecklistItems_Settings_Model extends Vtiger_Base_Model
{
    public $user = NULL;
    public $db = NULL;
    public function __construct()
    {
        global $current_user;
        $this->user = $current_user;
        $this->db = PearDatabase::getInstance();
    }
    public function getData()
    {
        $data = array();
        $query = "SELECT * FROM vtiger_checklistitems_settings ORDER BY modulename ASC, ordering ASC";
        $reuslt = $this->db->pquery($query, array());
        if ($this->db->num_rows($reuslt)) {
            while ($row = $this->db->fetchByAssoc($reuslt)) {
                $createdDate = new DateTimeField($row["createddate"]);
                $row["createddate"] = $createdDate->getDisplayDateTimeValue();
                $data[] = $row;
            }
        }
        return $data;
    }
    public function getEntityModules()
    {
        $result = $this->db->pquery("SELECT *\n                                    FROM vtiger_tab\n                                    WHERE vtiger_tab.isentitytype = 1 AND vtiger_tab.presence = 0\n                                        AND vtiger_tab.parent IS NOT NULL\n\t                                    AND vtiger_tab.parent != ''\n\t                                    AND vtiger_tab.name NOT LIKE 'ChecklistItems'\n                                    ORDER BY vtiger_tab.name", array());
        $arr = array();
        if ($this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $row["tablabel"] = vtranslate($row["name"], $row["name"]);
                $arr[] = $row;
            }
        }
        return $arr;
    }
    public function saveSetting($data)
    {
        if (trim($data->get("checklistname")) == "" || trim($data->get("modulename")) == "") {
            return false;
        }
        if (0 < $data->get("checklistid")) {
            $this->updateChecklist($data);
        } else {
            $this->createChecklist($data);
        }
        $this->updateWidget($data->get("modulename"));
        $this->updateRelatedTo();
        return true;
    }
    public function updateRelatedTo()
    {
        $tabid = getTabid("ChecklistItems");
        $result = $this->db->pquery("SELECT * FROM vtiger_field WHERE tabid = ? AND fieldname = ?", array($tabid, "parent_id"));
        $fieldId = $this->db->query_result($result, 0, "fieldid");
        $entityModules = $this->getEntityModules();
        foreach ($entityModules as $module) {
            $result1 = $this->db->pquery("SELECT * FROM `vtiger_fieldmodulerel` WHERE `fieldid` = ? AND `module` = ? AND `relmodule` = ?", array($fieldId, "ChecklistItems", $module["name"]));
            if ($this->db->num_rows($result1) == 0) {
                $this->db->pquery("INSERT INTO `vtiger_fieldmodulerel`(`fieldid`, `module`, `relmodule`) VALUES(?, ?, ?)", array($fieldId, "ChecklistItems", $module["name"]));
            }
        }
    }
    public function updateWidget($moduleName)
    {
        global $vtiger_current_version;
        if (empty($moduleName)) {
            return false;
        }
        $moduleInstance = Vtiger_Module::getInstance($moduleName);
        $query = "SELECT * FROM vtiger_checklistitems_settings WHERE modulename=? AND `status`=?";
        $result = $this->db->pquery($query, array($moduleName, "Active"));
        $tabid = getTabid($moduleName);
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            if ($this->db->num_rows($result) == 0) {
                $moduleInstance->deleteLink("DETAILVIEWSIDEBARWIDGET", "Checklists", "module=ChecklistItems&view=Widget");
            } else {
                $resultChk = $this->db->pquery("SELECT * FROM vtiger_links WHERE `linktype`=? AND `linklabel`=? AND `linkurl`=? AND tabid=?", array("DETAILVIEWSIDEBARWIDGET", "Checklist Items", "module=ChecklistItems&view=Widget", $tabid));
                if ($this->db->num_rows($resultChk) == 0) {
                    $moduleInstance->addLink("DETAILVIEWSIDEBARWIDGET", "Checklists", "module=ChecklistItems&view=Widget");
                }
            }
        } else {
            if ($this->db->num_rows($result) == 0) {
                $this->db->pquery("DELETE FROM vtiger_links WHERE `linktype`=? AND `linklabel`=? AND `linkurl`=? AND `tabid`=?", array("DETAILVIEWBASIC", "Checklists", "javascript:Vtiger_ChecklistItems_Js.showChecklistItems(\"module=ChecklistItems&view=Widget\")", $tabid));
            } else {
                $resultChk = $this->db->pquery("SELECT * FROM vtiger_links WHERE `linktype`=? AND `linklabel`=? AND `linkurl`=? AND tabid=?", array("DETAILVIEWBASIC", "Checklists", "javascript:Vtiger_ChecklistItems_Js.showChecklistItems(\"module=ChecklistItems&view=Widget\")", $tabid));
                if ($this->db->num_rows($resultChk) == 0) {
                    $moduleInstance = Vtiger_Module::getInstance($moduleName);
                    $moduleInstance->addLink("DETAILVIEWBASIC", "Checklists", "javascript:Vtiger_ChecklistItems_Js.showChecklistItems(\"module=ChecklistItems&view=Widget\")");
                }
            }
            $query1 = "SELECT * FROM vtiger_checklistitems_settings WHERE `status`=?";
            $result1 = $this->db->pquery($query1, array("Active"));
            if ($this->db->num_rows($result) == 0) {
                $this->db->pquery("DELETE FROM `vtiger_links`  WHERE `linktype`=? AND `linklabel`=?", array("HEADERSCRIPT", "ChecklistItemsJS"));
            } else {
                $this->db->pquery("DELETE FROM `vtiger_links`  WHERE `linktype`=? AND `linklabel`=?", array("HEADERSCRIPT", "ChecklistItemsJS"));
                $moduleInstance->addLink("HEADERSCRIPT", "ChecklistItemsJS", "layouts/v7/modules/ChecklistItems/resources/ChecklistItems.js");
            }
        }
    }
    public function updateChecklist($data)
    {
        $createdDate = date("Y-m-d");
        $this->db->pquery("UPDATE vtiger_checklistitems_settings SET checklistname = ? , modulename = ? WHERE checklistid = ?", array($data->get("checklistname"), $data->get("modulename"), $data->get("checklistid")));
        $this->db->pquery("DELETE FROM vtiger_checklistitems_settings_items WHERE checklistid = ?", array($data->get("checklistid")));
        $checklist_item_title = $data->get("title");
        $checklist_item_category = $data->get("category");
        $checklist_item_date = $data->get("date");
        $checklist_item_description = $data->getRaw("description");
        $checklist_item_allow_upload = $data->get("allow_upload");
        $checklist_item_allow_note = $data->get("allow_note");
        $checklist_item_ids = $data->get("itemid");
        if (!empty($checklist_item_title)) {
            foreach ($checklist_item_title as $k => $title) {
                $created_date = $checklist_item_date[$k];
                if ($created_date == "") {
                    $created_date_insert = $createdDate;
                } else {
                    $created_date_obj = new DateTimeField($created_date);
                    $created_date_insert = $created_date_obj->getDBInsertDateValue($this->user);
                }
                if (0 < $checklist_item_ids[$k]) {
                    $query = "INSERT INTO vtiger_checklistitems_settings_items(`itemid`, `title`, `category`, `createddate`, `description`, `allow_upload`, `allow_note`, `checklistid`) VALUES(?,?,?,?,?,?,?,?)";
                    $this->db->pquery($query, array($checklist_item_ids[$k], $title, $checklist_item_category[$k], $created_date_insert, $checklist_item_description[$k], $checklist_item_allow_upload[$k], $checklist_item_allow_note[$k], $data->get("checklistid")));
                } else {
                    $query = "INSERT INTO vtiger_checklistitems_settings_items(`title`, `category`, `createddate`, `description`, `allow_upload`, `allow_note`, `checklistid`) VALUES(?,?,?,?,?,?,?)";
                    $this->db->pquery($query, array($title, $checklist_item_category[$k], $created_date_insert, $checklist_item_description[$k], $checklist_item_allow_upload[$k], $checklist_item_allow_note[$k], $data->get("checklistid")));
                }
            }
        }
    }
    public function createChecklist($data)
    {
        $createdDate = date("Y-m-d");
        $last_ordering = $this->getLastOrdering();
        $next_ordering = (int) $last_ordering + 1;
        $this->db->pquery("INSERT INTO vtiger_checklistitems_settings(`checklistname`, `modulename`, `createddate`, `status`, `ordering`) VALUES(?,?,?,?,?)", array($data->get("checklistname"), $data->get("modulename"), $createdDate, "Active", $next_ordering));
        $checklistid = $this->db->getLastInsertID();
        if (!$checklistid) {
            return false;
        }
        $checklist_item_title = $data->get("title");
        $checklist_item_category = $data->get("category");
        $checklist_item_date = $data->get("date");
        $checklist_item_description = $data->getRaw("description");
        $checklist_item_allow_upload = $data->get("allow_upload");
        $checklist_item_allow_note = $data->get("allow_note");
        if (!empty($checklist_item_title)) {
            foreach ($checklist_item_title as $k => $title) {
                $created_date = $checklist_item_date[$k];
                if ($created_date == "") {
                    $created_date_insert = $createdDate;
                } else {
                    $created_date_obj = new DateTimeField($created_date);
                    $created_date_insert = $created_date_obj->getDBInsertDateValue($this->user);
                }
                $query = "INSERT INTO vtiger_checklistitems_settings_items(`title`, `category`, `createddate`, `description`, `allow_upload`, `allow_note`, `checklistid`) VALUES(?,?,?,?,?,?,?)";
                $this->db->pquery($query, array($title, $checklist_item_category[$k], $created_date_insert, $checklist_item_description[$k], $checklist_item_allow_upload[$k], $checklist_item_allow_note[$k], $checklistid));
            }
        }
    }
    public function getLastOrdering()
    {
        $last_ordering = 0;
        $result = $this->db->pquery("SELECT MAX(ordering) AS 'last_ordering' FROM `vtiger_checklistitems_settings`", array());
        if ($this->db->num_rows($result)) {
            $last_ordering = $this->db->query_result($result, 0, "last_ordering");
        }
        return $last_ordering;
    }
    public function updateOrdering($data)
    {
        $records = $data->get("records");
        if (!empty($records)) {
            foreach ($records as $k => $record) {
                $this->db->pquery("UPDATE vtiger_checklistitems_settings SET vtiger_checklistitems_settings.ordering = ? WHERE vtiger_checklistitems_settings.checklistid=?", array($k + 1, $record));
            }
        }
        return true;
    }
    public function ChangeStatus($data)
    {
        $record = $data->get("record");
        $status = $data->get("status");
        if (!empty($record)) {
            $this->db->pquery("UPDATE vtiger_checklistitems_settings SET vtiger_checklistitems_settings.status = ? WHERE vtiger_checklistitems_settings.checklistid=?", array($status, $record));
        }
        $result = $this->db->pquery("SELECT * FROM vtiger_checklistitems_settings WHERE checklistid=?", array($record));
        $moduleName = $this->db->query_result($result, 0, "modulename");
        $this->updateWidget($moduleName);
        return true;
    }
    public function deleteRecord($request)
    {
        $recordId = $request->get("record", 0);
        $moduleName = $request->get("modulename");
        if ($recordId == 0) {
            return false;
        }
        $this->db->pquery("DELETE FROM vtiger_checklistitems_settings WHERE checklistid=?", array($recordId));
        $this->db->pquery("DELETE FROM vtiger_checklistitems_settings_items WHERE checklistid=?", array($recordId));
        $this->updateWidget($moduleName);
        return true;
    }
    public function SaveUserPermissions($data)
    {
        $permissions = $data->get("permissions", 0);
        $result = $this->db->pquery("SELECT * FROM vtiger_checklistitems_permissions LIMIT 1", array());
        if ($this->db->num_rows($result)) {
            $this->db->pquery("UPDATE vtiger_checklistitems_permissions SET permissions = ?", array($permissions));
        } else {
            $this->db->pquery("INSERT INTO vtiger_checklistitems_permissions VALUES(?)", array($permissions));
        }
        if ($permissions == 1) {
            $this->db->pquery("UPDATE vtiger_tab SET `parent` = ? WHERE `name` = ?", array("Marketing", "ChecklistItems"));
        } else {
            $this->db->pquery("UPDATE vtiger_tab SET `parent` = ? WHERE `name` = ?", array("", "ChecklistItems"));
        }
        return true;
    }
    public function getPermissions()
    {
        $permissions = 0;
        $result = $this->db->pquery("SELECT * FROM vtiger_checklistitems_permissions LIMIT 1", array());
        if ($this->db->num_rows($result)) {
            $permissions = $this->db->query_result($result, 0, "permissions");
        }
        return $permissions;
    }
}

?>