<?php

class ChecklistItems_Module_Model extends Vtiger_Module_Model
{
    public $user;

    public $db;

    public function __construct()
    {
        global $current_user;
        $this->user = $current_user;
        $this->db = PearDatabase::getInstance();
    }

    /**
     * Function to get Settings links for admin user.
     * @return array
     */
    public function getSettingLinks()
    {
        $settingsLinks = parent::getSettingLinks();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if ($currentUserModel->isAdminUser()) {
            $settingsLinks[] = ['linktype' => 'LISTVIEWSETTING', 'linklabel' => 'Settings', 'linkurl' => 'index.php?module=ChecklistItems&view=Settings&parent=Settings', 'linkicon' => ''];
            $settingsLinks[] = ['linktype' => 'LISTVIEWSETTING', 'linklabel' => 'Uninstall', 'linkurl' => 'index.php?module=ChecklistItems&view=Uninstall&parent=Settings', 'linkicon' => ''];
        }

        return $settingsLinks;
    }

    public function getChecklist($moduleName)
    {
        $data = [];
        $query = 'SELECT * FROM vtiger_checklistitems_settings WHERE modulename = ? AND `status` = ? ORDER BY ordering';
        $params = [$moduleName, 'Active'];
        $result = $this->db->pquery($query, $params);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $data[] = $row;
            }
        }

        return $data;
    }

    public function getChecklistItemsSetting($checklistid)
    {
        $checklist_items = [];
        $query = "SELECT a.*, b.checklistname\n                FROM vtiger_checklistitems_settings_items a\n                LEFT JOIN vtiger_checklistitems_settings b ON b.checklistid = a.checklistid\n                WHERE a.checklistid = ?\n                ORDER BY a.itemid, a.category";
        $result = $this->db->pquery($query, [$checklistid]);
        if ($this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $checklist_items[] = $row;
            }
        }

        return $checklist_items;
    }

    public function getChecklistItems($source_record, $checklistid)
    {
        $checklist_items = [];
        $query = 'SELECT * FROM vtiger_checklistitems_settings WHERE checklistid = ?';
        $params = [$checklistid];
        $result = $this->db->pquery($query, $params);
        $checklist_name = $this->db->query_result($result, 0, 'checklistname');
        $sql = "SELECT * FROM vtiger_checklistitems\n                INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_checklistitems.checklistitemsid\n                WHERE vtiger_crmentity.deleted = 0\n                AND vtiger_checklistitems.checklistname = ?\n                AND vtiger_checklistitems.parent_id = ?\n                ORDER BY vtiger_crmentity.crmid\n                ";
        $result1 = $this->db->pquery($sql, [$checklist_name, $source_record]);
        if ($this->db->num_rows($result1)) {
            while ($row = $this->db->fetchByAssoc($result1)) {
                $category = $row['category'];
                if (!empty($row['status_date']) && !empty($row['status_time'])) {
                    $status_date_time = new DateTimeField($row['status_date'] . ' ' . $row['status_time']);
                    $row['status_date_display'] = $status_date_time->getDisplayDate($this->user);
                    $row['status_time_display'] = $status_date_time->getDisplayTime($this->user);
                } else {
                    $row['status_date_display'] = '';
                    $row['status_time_display'] = '';
                }
                $row['description'] = decode_html($row['description']);
                $comments = $this->getComments($row['checklistitemsid']);
                $row['comments'] = $comments;
                $row['count_comment'] = count($comments);
                $documents = $this->getDocuments($row['checklistitemsid']);
                $row['documents'] = $documents;
                $row['count_document'] = count($documents);
                $checklist_items[$category][] = $row;
            }
        }

        return $checklist_items;
    }

    public function GetExistingChecklistItemID($source_record, $checklistid, $settings_item_id = 0, $tile = '')
    {
        $checklistitemsid = 0;
        $query = 'SELECT * FROM vtiger_checklistitems_settings WHERE checklistid = ?';
        $params = [$checklistid];
        $result = $this->db->pquery($query, $params);
        $checklist_name = $this->db->query_result($result, 0, 'checklistname');
        if ($settings_item_id > 0) {
            $sql = "SELECT vtiger_checklistitems.checklistitemsid FROM vtiger_checklistitems\n                INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_checklistitems.checklistitemsid\n                WHERE vtiger_crmentity.deleted = 0\n                AND vtiger_checklistitems.checklistname = ?\n                AND vtiger_checklistitems.parent_id = ?\n                AND  vtiger_checklistitems.settings_item_id = ?\n                ORDER BY vtiger_crmentity.crmid DESC \n                LIMIT 1";
            $result1 = $this->db->pquery($sql, [$checklist_name, $source_record, $settings_item_id]);
        } else {
            $sql = "SELECT vtiger_checklistitems.checklistitemsid FROM vtiger_checklistitems\n                INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_checklistitems.checklistitemsid\n                WHERE vtiger_crmentity.deleted = 0\n                AND vtiger_checklistitems.checklistname = ?\n                AND vtiger_checklistitems.parent_id = ?\n                AND  vtiger_checklistitems.title = ?\n                ORDER BY vtiger_crmentity.crmid DESC \n                LIMIT 1";
            $result1 = $this->db->pquery($sql, [$checklist_name, $source_record, $tile]);
        }
        if ($this->db->num_rows($result1)) {
            $checklistitemsid = $this->db->query_result($result1, 0, 'checklistitemsid');
        }

        return $checklistitemsid;
    }

    public function getChecklistDetails($soruce_record, $checklistid)
    {
        $checklist_detail = [];
        $query = 'SELECT * FROM vtiger_checklistitems_settings WHERE checklistid = ?';
        $params = [$checklistid];
        $result = $this->db->pquery($query, $params);
        $checklist_name = $this->db->query_result($result, 0, 'checklistname');
        $sql = "SELECT * FROM vtiger_checklistitems\n                INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_checklistitems.checklistitemsid\n                WHERE vtiger_crmentity.deleted = 0\n                AND vtiger_checklistitems.checklistname = ?\n                AND vtiger_checklistitems.parent_id = ?\n                ORDER BY vtiger_checklistitems.status_date DESC \n                LIMIT 1";
        $result1 = $this->db->pquery($sql, [$checklist_name, $soruce_record]);
        if ($this->db->num_rows($result1)) {
            while ($row = $this->db->fetchByAssoc($result1)) {
                if (!empty($row['status_date']) && !empty($row['status_time'])) {
                    $status_date_time = new DateTimeField($row['status_date'] . ' ' . $row['status_time']);
                    $row['status_date_display'] = $status_date_time->getDisplayDate($this->user);
                    $row['status_time_display'] = $status_date_time->getDisplayTime($this->user);
                } else {
                    $row['status_date_display'] = '';
                    $row['status_time_display'] = '';
                }
                $checklist_detail = $row;
            }
        }

        return $checklist_detail;
    }

    public function addRelation($source_module, $source_record, $record)
    {
        if (empty($source_module) || empty($source_record) || empty($record)) {
            return false;
        }
        $params = [$source_record, $source_module, $record, 'ChecklistItems'];
        $this->db->pquery('INSERT INTO vtiger_crmentityrel SET `crmid` = ?, `module` = ?, `relcrmid` = ?, `relmodule` = ?', $params);

        return true;
    }

    public function getComments($checklistitemsid = false, $user = false, $dateFilter = '')
    {
        $comments = [];
        $query = "SELECT *\n                    FROM vtiger_modcomments\n                    INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_modcomments.modcommentsid\n                    WHERE vtiger_crmentity.deleted = 0 AND vtiger_modcomments.related_to = ?\n                    ORDER BY vtiger_crmentity.createdtime DESC";
        $parmas = [$checklistitemsid];
        $result = $this->db->pquery($query, $parmas);
        if ($this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $user = Users_Record_Model::getInstanceById($row['userid'], 'Users');
                $row['displayUserName'] = $user->getDisplayName();
                $createdtime = new DateTimeField($row['createdtime']);
                $row['displayDateTime'] = $createdtime->getDisplayDateTimeValue($this->user);
                $comments[] = $row;
            }
        }

        return $comments;
    }

    public function getDocuments($checklistitemsid)
    {
        $documents = [];
        $query = "SELECT DISTINCT vtiger_crmentity.crmid,vtiger_notes.title, vtiger_seattachmentsrel.attachmentsid, vtiger_notes.folderid, vtiger_crmentity.smownerid, vtiger_crmentity.modifiedtime, vtiger_notes.filename\n                  FROM vtiger_notes\n                  INNER JOIN vtiger_senotesrel ON vtiger_senotesrel.notesid= vtiger_notes.notesid\n                  LEFT JOIN vtiger_notescf ON vtiger_notescf.notesid= vtiger_notes.notesid\n                  INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid= vtiger_notes.notesid AND vtiger_crmentity.deleted=0\n                  INNER JOIN vtiger_crmentity crm2 ON crm2.crmid=vtiger_senotesrel.crmid\n                  LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid\n                  LEFT JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.crmid =vtiger_notes.notesid\n                  LEFT JOIN vtiger_attachments ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid\n                  LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid= vtiger_users.id\n                  WHERE crm2.crmid=? AND vtiger_notes.filestatus = 1\n                    ORDER BY vtiger_crmentity.createdtime DESC";
        $parmas = [$checklistitemsid];
        $result = $this->db->pquery($query, $parmas);
        if ($this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $documents[] = $row;
            }
        }

        return $documents;
    }
}
