<?php

require_once "modules/VReports/ReportUtils.php";

class VReports_Folder_Model extends Vtiger_Base_Model
{
    /**
     * Function to get the id of the folder
     * @return <Number>
     */
    public function getId()
    {
        return $this->get("folderid");
    }
    /**
     * Function to set the if for the folder
     * @param <Number>
     */
    public function setId($value)
    {
        $this->set("folderid", $value);
    }
    /**
     * Function to get the name of the folder
     * @return <String>
     */
    public function getName()
    {
        return $this->get("foldername");
    }
    /**
     * Function returns the instance of Folder model
     * @return <Reports_Folder_Model>
     */
    public static function getInstance()
    {
        return new self();
    }
    /**
     * Function saves the folder
     */
    public function save()
    {
        $db = PearDatabase::getInstance();
        $folderId = $this->getId();
        if (!empty($folderId)) {
            $db->pquery("UPDATE vtiger_vreportfolder SET foldername = ?, description = ? WHERE folderid = ?", array($this->getName(), $this->getDescription(), $folderId));
        } else {
            $result = $db->pquery("SELECT MAX(folderid) AS folderid FROM vtiger_vreportfolder", array());
            $folderId = (int) $db->query_result($result, 0, "folderid") + 1;
            $db->pquery("INSERT INTO vtiger_vreportfolder(folderid, foldername, description, state) VALUES(?, ?, ?, ?)", array($folderId, $this->getName(), $this->getDescription(), "CUSTOMIZED"));
            $this->set("folderid", $folderId);
        }
    }
    /**
     * Function deletes the folder
     */
    public function delete()
    {
        $db = PearDatabase::getInstance();
        $db->pquery("DELETE FROM vtiger_vreportfolder WHERE folderid = ?", array($this->getId()));
    }
    /**
     * Function returns Report Models for the folder
     * @param <Vtiger_Paging_Model> $pagingModel
     * @return <Reports_Record_Model>
     */
    public function getVReports($pagingModel)
    {
        $paramsList = array("startIndex" => $pagingModel->getStartIndex(), "pageLimit" => $pagingModel->getPageLimit(), "orderBy" => $this->get("orderby"), "sortBy" => $this->get("sortby"));
        $reportClassInstance = Vtiger_Module_Model::getClassInstance("VReports");
        $fldrId = $this->getId();
        if ($fldrId == "All" || $fldrId == "Public") {
            $paramsList = array("startIndex" => $pagingModel->getStartIndex(), "pageLimit" => $pagingModel->getPageLimit(), "orderBy" => $this->get("orderby"), "sortBy" => $this->get("sortby"));
        }
        $paramsList["searchParams"] = $this->get("search_params");
        $reportsList = $reportClassInstance->sgetRptsforFldr($fldrId, $paramsList);
        $reportsCount = count($reportsList);
        $pageLimit = $pagingModel->getPageLimit();
        if ($pageLimit < $reportsCount) {
            array_pop($reportsList);
            $pagingModel->set("nextPageExists", true);
        } else {
            $pagingModel->set("nextPageExists", false);
        }
        $reportModuleModel = Vtiger_Module_Model::getInstance("VReports");
        if ($fldrId == "All" || $fldrId == "shared" || $fldrId == "Public") {
            return $this->getAllVReportModels($reportsList, $reportModuleModel);
        }
        $reportModels = array();
        for ($i = 0; $i < count($reportsList); $i++) {
            $reportModel = new VReports_Record_Model();
            $reportModel->setData($reportsList[$i])->setModuleFromInstance($reportModuleModel);
            $reportModels[] = $reportModel;
            unset($reportModel);
        }
        return $reportModels;
    }
    /**
     * Function to get the description of the folder
     * @return <String>
     */
    public function getDescription()
    {
        return $this->get("description");
    }
    /**
     * Function to get the url for edit folder from list view of the module
     * @return <string> - url
     */
    public function getEditUrl()
    {
        return "index.php?module=VReports&view=EditFolder&folderid=" . $this->getId();
    }
    /**
     * Function to get the url for delete folder from list view of the module
     * @return <string> - url
     */
    public function getDeleteUrl()
    {
        return "index.php?module=VReports&action=Folder&mode=delete&folderid=" . $this->getId();
    }
    /**
     * Function returns the instance of Folder model
     * @param FolderId
     * @return <Reports_Folder_Model>
     */
    public static function getInstanceById($folderId)
    {
        $folderModel = Vtiger_Cache::get("vreportsFolder", $folderId);
        if (!$folderModel) {
            $db = PearDatabase::getInstance();
            $folderModel = VReports_Folder_Model::getInstance();
            $result = $db->pquery("SELECT * FROM vtiger_vreportfolder WHERE folderid = ?", array($folderId));
            if (0 < $db->num_rows($result)) {
                $values = $db->query_result_rowdata($result, 0);
                $folderModel->setData($values);
            }
            Vtiger_Cache::set("vreportsFolder", $folderId, $folderModel);
        }
        return $folderModel;
    }
    /**
     * Function returns the instance of Folder model
     * @return <Reports_Folder_Model>
     */
    public static function getAll()
    {
        $db = PearDatabase::getInstance();
        $folders = Vtiger_Cache::get("VReports", "folders");
        if (!$folders) {
            $folders = array();
            $result = $db->pquery("SELECT * FROM vtiger_vreportfolder ORDER BY foldername ASC", array());
            $noOfFolders = $db->num_rows($result);
            if (0 < $noOfFolders) {
                for ($i = 0; $i < $noOfFolders; $i++) {
                    $folderModel = VReports_Folder_Model::getInstance();
                    $values = $db->query_result_rowdata($result, $i);
                    $folders[$values["folderid"]] = $folderModel->setData($values);
                    Vtiger_Cache::set("vreportsFolder", $values["folderid"], $folderModel);
                }
            }
            Vtiger_Cache::set("VReports", "folders", $folders);
        }
        return $folders;
    }
    /**
     * Function returns duplicate record status of the module
     * @return true if duplicate records exists else false
     */
    public function checkDuplicate()
    {
        $db = PearDatabase::getInstance();
        $query = "SELECT 1 FROM vtiger_vreportfolder WHERE foldername = ?";
        $params = array($this->getName());
        $folderId = $this->getId();
        if ($folderId) {
            $query .= " AND folderid != ?";
            array_push($params, $folderId);
        }
        $folderName = $this->getName();
        $result = $db->pquery($query, $params);
        if (0 < $db->num_rows($result) || $folderName == "Shared With Me" || $folderName == "All Reports") {
            return true;
        }
        return false;
    }
    /**
     * Function returns whether reports are exist or not in this folder
     * @return true if exists else false
     */
    public function hasVReports()
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT 1 FROM vtiger_vreport WHERE folderid = ?", array($this->getId()));
        if (0 < $db->num_rows($result)) {
            return true;
        }
        return false;
    }
    /**
     * Function returns whether folder is Default or not
     * @return true if it is read only else false
     */
    public function isDefault()
    {
        if ($this->get("state") == "SAVED") {
            return true;
        }
        return false;
    }
    /**
     * Function to get info array while saving a folder
     * @return Array  info array
     */
    public function getInfoArray()
    {
        return array("folderId" => $this->getId(), "folderName" => $this->getName(), "editURL" => $this->getEditUrl(), "deleteURL" => $this->getDeleteUrl(), "isEditable" => $this->isEditable(), "isDeletable" => $this->isDeletable());
    }
    /**
     * Function to check whether folder is editable or not
     * @return <boolean>
     */
    public function isEditable()
    {
        if ($this->isDefault()) {
            return false;
        }
        return true;
    }
    /**
     * Function to get check whether folder is deletable or not
     * @return <boolean>
     */
    public function isDeletable()
    {
        if ($this->isDefault()) {
            return false;
        }
        return true;
    }
    /**
     * Function to calculate number of reports in this folder
     * @return <Integer>
     */
    public function getVReportsCount()
    {
        $db = PearDatabase::getInstance();
        $params = array();
        global $current_user;
        $query = "SELECT reportmodulesid, primarymodule from vtiger_vreportmodules";
        $result = $db->pquery($query, array());
        $noOfRows = $db->num_rows($result);
        $allowedReportIds = array();
        for ($i = 0; $i < $noOfRows; $i++) {
            $primaryModule = $db->query_result($result, $i, "primarymodule");
            $reportid = $db->query_result($result, $i, "reportmodulesid");
            if (isPermitted($primaryModule, "index") == "yes") {
                $allowedReportIds[] = $reportid;
            }
        }
        $userNameSql = getSqlForNameInDisplayFormat(array("first_name" => "vtiger_users.first_name", "last_name" => "vtiger_users.last_name"), "Users");
        $sql = "SELECT count(*) AS count FROM vtiger_vreport \n\t\t\tLEFT JOIN vtiger_users ON vtiger_vreport.owner = vtiger_users.id\n\t\t\tINNER JOIN vtiger_vreportmodules ON vtiger_vreportmodules.reportmodulesid = vtiger_vreport.reportid\n\t\t\tINNER JOIN vtiger_tab ON vtiger_tab.name = vtiger_vreportmodules.primarymodule AND vtiger_tab.presence = 0\n\t\t\tLEFT JOIN vtiger_vreport_shareall ON vtiger_vreport.reportid = vtiger_vreport_shareall.reportid\n\t\t\tINNER JOIN vtiger_vreportfolder ON vtiger_vreportfolder.folderid = vtiger_vreport.folderid AND \n\t\t\t\tvtiger_vreport.reportid in (" . implode(",", $allowedReportIds) . ")";
        $fldrId = $this->getId();
        if ($fldrId == "shared") {
            $sql .= " WHERE vtiger_vreport.sharingtype=? AND vtiger_vreport.owner != ?";
            array_push($params, "Private");
            array_push($params, $current_user->id);
        }
        if ($fldrId == "All" || $fldrId == "Public" || $fldrId == "shared") {
            $fldrId = false;
        }
        if ($fldrId !== false) {
            $sql .= " WHERE vtiger_vreportfolder.folderid=?";
            array_push($params, $fldrId);
        }
        $searchParams = $this->get("searchParams");
        $searchCondition = getVReportSearchCondition($searchParams, $fldrId);
        if ($searchCondition) {
            $sql .= $searchCondition;
        }
        $currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserModel->isAdminUser()) {
            $currentUserId = $currentUserModel->getId();
            $userGroups = new GetUserGroups();
            $userGroups->getAllUserGroups($currentUserId);
            $groups = $userGroups->user_groups;
            $userRole = fetchUserRole($currentUserId);
            $parentRoles = getParentRole($userRole);
            $parentRolelist = array();
            foreach ($parentRoles as $par_rol_id) {
                array_push($parentRolelist, $par_rol_id);
            }
            array_push($parentRolelist, $userRole);
            $groupId = implode(",", $currentUserModel->get("groups"));
            if ($groupId) {
                $groupQuery = "(SELECT reportid from vtiger_vreportsharing WHERE shareid IN (" . $groupId . ") AND setype = 'groups') OR ";
            }
            $sql .= " AND (vtiger_vreport.reportid IN (SELECT reportid from vtiger_vreportsharing WHERE " . $groupQuery . " shareid = ? AND setype = 'users')\n\t\t\t\t\t\tOR vtiger_vreport.sharingtype = 'Public'\n\t\t\t\t\t\tOR vtiger_vreport.owner = ?\n\t\t\t\t\t\tOR vtiger_vreport.owner IN (SELECT vtiger_user2role.userid FROM vtiger_user2role\n\t\t\t\t\t\t\t\t\t\t\t\t\tINNER JOIN vtiger_users ON vtiger_users.id = vtiger_user2role.userid\n\t\t\t\t\t\t\t\t\t\t\t\t\tINNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid\n\t\t\t\t\t\t\t\t\t\t\t\t\tWHERE vtiger_role.parentrole LIKE ?))\n\t\t\t\t\t\tOR vtiger_vreport.reportid IN (SELECT vtiger_vreport_shareusers.reportid FROM vtiger_vreport_shareusers WHERE vtiger_vreport_shareusers.userid=?)";
            $parentRoleSeq = $currentUserModel->get("parent_role_seq") . "::%";
            array_push($params, $currentUserId, $currentUserId, $parentRoleSeq, $currentUserId);
            if (!empty($groups)) {
                $sql .= " OR vtiger_vreport.reportid IN (SELECT vtiger_vreport_sharegroups.reportid FROM vtiger_vreport_sharegroups WHERE vtiger_vreport_sharegroups.groupid IN (" . generateQuestionMarks($groups) . "))";
                $params = array_merge($params, $groups);
            }
            $sql .= " OR vtiger_vreport.reportid IN (SELECT vtiger_vreport_sharerole.reportid FROM vtiger_vreport_sharerole WHERE vtiger_vreport_sharerole.roleid =?)";
            array_push($params, $userRole);
            if (!empty($parentRolelist)) {
                $sql .= " OR vtiger_vreport.reportid IN (SELECT vtiger_vreport_sharers.reportid FROM vtiger_vreport_sharers WHERE vtiger_vreport_sharers.rsid IN (" . generateQuestionMarks($parentRolelist) . "))";
                $params = array_merge($params, $parentRolelist);
            }
        }
        $result = $db->pquery($sql, $params);
        return $db->query_result($result, 0, "count");
    }
    /**
     * Function to get all Report Record Models
     * @param <Array> $allReportsList
     * @param <Vtiger_Module_Model> - Reports Module Model
     * @return <Array> Reports Record Models
     */
    public function getAllVReportModels($allReportsList, $reportModuleModel)
    {
        $allReportModels = array();
        $folders = self::getAll();
        foreach ($allReportsList as $key => $reportsList) {
            $reportModel = new VReports_Record_Model();
            $reportModel->setData($reportsList)->setModuleFromInstance($reportModuleModel);
            $reportModel->set("foldername", $folders[$reportsList["folderid"]]->getName());
            $allReportModels[] = $reportModel;
            unset($reportModel);
        }
        return $allReportModels;
    }
    /**
     * Function which provides the records for the current view
     * @param <Boolean> $skipRecords - List of the RecordIds to be skipped
     * @return <Array> List of RecordsIds
     */
    public function getRecordIds($skipRecords = false, $module, $searchParams = array())
    {
        $db = PearDatabase::getInstance();
        $baseTableName = "vtiger_vreport";
        $baseTableId = "reportid";
        $folderId = $this->getId();
        $listQuery = $this->getListViewQuery($folderId, $searchParams);
        if ($skipRecords && !empty($skipRecords) && is_array($skipRecords) && 0 < count($skipRecords)) {
            $listQuery .= " AND " . $baseTableName . "." . $baseTableId . " NOT IN (" . implode(",", $skipRecords) . ")";
        }
        $result = $db->query($listQuery);
        $noOfRecords = $db->num_rows($result);
        $recordIds = array();
        for ($i = 0; $i < $noOfRecords; $i++) {
            $recordIds[] = $db->query_result($result, $i, $baseTableId);
        }
        return $recordIds;
    }
    /**
     * Function returns Report Models for the folder
     * @return <Reports_Record_Model>
     */
    public function getListViewQuery($folderId, $searchParams = array())
    {
        $sql = "select vtiger_vreport.*, vtiger_vreportmodules.*, vtiger_vreportfolder.folderid from vtiger_vreport \n\t\t\t\tinner join vtiger_vreportfolder on vtiger_vreportfolder.folderid = vtiger_vreport.folderid \n\t\t\t\tinner join vtiger_vreportmodules on vtiger_vreportmodules.reportmodulesid = vtiger_vreport.reportid ";
        if ($folderId != "All") {
            $sql = $sql . " where vtiger_vreportfolder.folderid = " . $folderId;
        }
        $searchCondition = getVReportSearchCondition($searchParams, $folderId);
        if ($searchCondition) {
            $sql .= $searchCondition;
        }
        return $sql;
    }
}

?>