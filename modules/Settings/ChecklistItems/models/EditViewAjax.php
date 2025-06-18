<?php

class Settings_ChecklistItems_EditViewAjax_Model extends Vtiger_Base_Model
{
    public $user;

    public $db;

    public function __construct()
    {
        global $current_user;
        $this->user = $current_user;
        $this->db = PearDatabase::getInstance();
    }

    public function getData($record = false)
    {
        $data = [];
        if ($record) {
            $result = $this->db->pquery("SELECT * FROM vtiger_checklistitems_settings\n                                    WHERE vtiger_checklistitems_settings.checklistid = ?\n                                    LIMIT 0, 1", [$record]);
            if ($this->db->num_rows($result) > 0) {
                $data = $this->db->fetchByAssoc($result);
                $data['items'] = $this->getItems($record);
                $data['count_items'] = count($data['items']);
            }
        }

        return $data;
    }

    public function getItems($record)
    {
        $data = [];
        if ($record) {
            $result = $this->db->pquery("SELECT * FROM vtiger_checklistitems_settings_items\n                                    WHERE vtiger_checklistitems_settings_items.checklistid = ?", [$record]);
            if ($this->db->num_rows($result) > 0) {
                while ($row = $this->db->fetchByAssoc($result)) {
                    $createdDate = new DateTimeField($row['createddate']);
                    $row['createddate'] = $createdDate->getDisplayDate($this->user);
                    $data[] = $row;
                }
            }
        }

        return $data;
    }

    public function getEntityModules()
    {
        $result = $this->db->pquery("SELECT *\n                                    FROM vtiger_tab\n                                    WHERE vtiger_tab.isentitytype = 1 AND vtiger_tab.presence = 0\n                                        AND vtiger_tab.parent IS NOT NULL\n\t                                    AND vtiger_tab.parent != ''\n\t                                    AND vtiger_tab.name NOT LIKE 'ChecklistItems'\n                                    ORDER BY vtiger_tab.name", []);
        $arr = [];
        if ($this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $row['tablabel'] = vtranslate($row['name'], $row['name']);
                $arr[] = $row;
            }
        }

        return $arr;
    }
}
