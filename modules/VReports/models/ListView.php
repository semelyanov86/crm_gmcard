<?php

/**
 * Reports ListView Model Class
 */
class VReports_ListView_Model extends Vtiger_ListView_Model
{
    /**
     * Function to get the list of listview links for the module
     * @return <Array> - Associate array of Link Type to List of Vtiger_Link_Model instances
     */
    public function getListViewLinks($linkParams = false)
    {
        $moduleModel = $this->getModule();
        $links = Vtiger_Link_Model::getAllByType($moduleModel->getId(), array("LISTVIEWBASIC"));
        return $links;
    }
    /**
     * Function to get the list of Mass actions for the module
     * @param <Array> $linkParams
     * @return <Array> - Associative array of Link type to List of  Vtiger_Link_Model instances for Mass Actions
     */
    public function getListViewMassActions($linkParams = false)
    {
        $currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $massActionLinks = array();
        if ($currentUserModel->hasModulePermission($this->getModule()->getId())) {
            $massActionLinks[] = array("linktype" => "LISTVIEWMASSACTION", "linklabel" => "LBL_DELETE", "linkurl" => "javascript:VReports_List_Js.massDelete(\"index.php?module=" . $this->getModule()->get("name") . "&action=MassDelete\");", "linkicon" => "");
            $massActionLinks[] = array("linktype" => "LISTVIEWMASSACTION", "linklabel" => "LBL_MOVE_REPORT", "linkurl" => "javascript:VReports_List_Js.massMove(\"index.php?module=" . $this->getModule()->get("name") . "&view=MoveReports\");", "linkicon" => "");
        }
        foreach ($massActionLinks as $massActionLink) {
            $links[] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
        }
        return $links;
    }
    public function getListViewHeadersForVtiger7($folderId)
    {
        $headers = array("reporttype" => array("label" => "Report Type", "type" => "picklist"), "reportname" => array("label" => "Report Name", "type" => "string"), "primarymodule" => array("label" => "Primary Module", "type" => "picklist"), "foldername" => array("label" => "LBL_FOLDER_NAME", "type" => "picklist"), "modifiedtime" => array("label" => "Modified Time", "type" => "picklist"), "sharingtype" => array("label" => "Sharing Type"), "schedule" => array("label" => "Schedule"));
        if ($folderId == "shared") {
            unset($headers["foldername"]);
        }
        return $headers;
    }
    /**
     * Function to get the list view header
     * @return <Array> - List of Vtiger_Field_Model instances
     */
    public function getListViewHeaders($folderId = false)
    {
        $headers = array("reportname" => array("label" => "LBL_REPORT_NAME", "type" => "string"), "description" => array("label" => "LBL_DESCRIPTION", "type" => "string"), "reporttype" => array("label" => "Report Type", "type" => "picklist"), "sharingtype" => array("label" => "Sharing Type"), "schedule" => array("label" => "Schedule"));
        if ($folderId == "All" || $folderId == "Public") {
            $headers["foldername"] = array("label" => "LBL_FOLDER_NAME", "type" => "picklist");
        }
        return $headers;
    }
    /**
     * Function to get the list view entries
     * @param Vtiger_Paging_Model $pagingModel
     * @return <Array> - Associative array of record id mapped to Vtiger_Record_Model instance.
     */
    public function getListViewEntries($pagingModel)
    {
        $reportFolderModel = VReports_Folder_Model::getInstance();
        $reportFolderModel->set("folderid", $this->get("folderid"));
        $orderBy = $this->get("orderby");
        if (!empty($orderBy) && $orderBy === "smownerid") {
            $fieldModel = Vtiger_Field_Model::getInstance("assigned_user_id", $this->get("module"));
            if ($fieldModel->getFieldDataType() == "owner") {
                $orderBy = "COALESCE(CONCAT(vtiger_users.first_name,vtiger_users.last_name),vtiger_groups.groupname)";
            }
        }
        if (!empty($orderBy)) {
            $reportFolderModel->set("orderby", $orderBy);
            $reportFolderModel->set("sortby", $this->get("sortorder"));
        }
        $reportFolderModel->set("search_params", $this->get("search_params"));
        $reportRecordModels = $reportFolderModel->getVReports($pagingModel);
        $nextPageExists = $pagingModel->get("nextPageExists");
        $pagingModel->calculatePageRange($reportRecordModels);
        $pagingModel->set("nextPageExists", $nextPageExists);
        return $reportRecordModels;
    }
    /**
     * Function to get the list view entries count
     * @return <Integer>
     */
    public function getListViewCount()
    {
        $reportFolderModel = VReports_Folder_Model::getInstance();
        $reportFolderModel->set("folderid", $this->get("folderid"));
        $reportFolderModel->set("searchParams", $this->get("search_params"));
        return $reportFolderModel->getVReportsCount();
    }
    public function getCreateRecordUrl()
    {
        return "javascript:VReports_List_Js.addReport(\"" . $this->getModule()->getCreateRecordUrl() . "\")";
    }
}

?>