<?php

class VReports_Module_Model extends Vtiger_Module_Model
{
    /**
     * Function deletes report
     * @param Reports_Record_Model $reportModel
     */
    public static function getPicklistColorByValue($fieldName, $recordModel)
    {
        $db = PearDatabase::getInstance();
        $fieldValue = $recordModel->getRaw($fieldName);
        $tableName = "vtiger_" . $fieldName;
        if (Vtiger_Utils::CheckTable($tableName)) {
            $colums = $db->getColumnNames($tableName);
            $fieldValue = decode_html($fieldValue);
            if (in_array("color", $colums)) {
                $query = "SELECT color FROM " . $tableName . " WHERE " . $fieldName . " = ?";
                $result = $db->pquery($query, array($fieldValue));
                if (0 < $db->num_rows($result)) {
                    $color = $db->query_result($result, 0, "color");
                }
            }
        }
        return $color;
    }
    public function deleteRecord($reportModel)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $subOrdinateUsers = $currentUser->getSubordinateUsers();
        $subOrdinates = array();
        foreach ($subOrdinateUsers as $id => $name) {
            $subOrdinates[] = $id;
        }
        $owner = $reportModel->get("owner");
        if ($currentUser->isAdminUser() || in_array($owner, $subOrdinates) || $owner == $currentUser->getId()) {
            $reportId = $reportModel->getId();
            $db = PearDatabase::getInstance();
            $db->pquery("DELETE FROM vtiger_selectquery WHERE queryid = ?", array($reportId));
            $db->pquery("DELETE FROM vtiger_vreport WHERE reportid = ?", array($reportId));
            $db->pquery("DELETE FROM vtiger_schedulevreports WHERE reportid = ?", array($reportId));
            $db->pquery("DELETE FROM vtiger_vreporttype WHERE reportid = ?", array($reportId));
            $result = $db->pquery("SELECT * FROM vtiger_homevreportchart WHERE reportid = ?", array($reportId));
            $numOfRows = $db->num_rows($result);
            for ($i = 0; $i < $numOfRows; $i++) {
                $homePageChartIdsList[] = $adb->query_result($result, $i, "stuffid");
            }
            if ($homePageChartIdsList) {
                $deleteQuery = "DELETE FROM vtiger_homestuff WHERE stuffid IN (" . implode(",", $homePageChartIdsList) . ")";
                $db->pquery($deleteQuery, array());
            }
            if ($reportModel->get("reporttype") == "chart") {
                VReports_Widget_Model::deleteChartReportWidgets($reportId);
            }
            return true;
        }
        return false;
    }
    public function getSettingLinks()
    {
        $vTELicense = new VReports_VTELicense_Model("VTEEmailMarketing");
        if ($vTELicense->validate()) {
            $settingsLinks = parent::getSettingLinks();
        }
        $settingsLinks[] = array("linktype" => "MODULESETTING", "linklabel" => "Settings", "linkurl" => "index.php?module=VReports&parent=Settings&view=Settings", "linkicon" => "");
        $settingsLinks[] = array("linktype" => "MODULESETTING", "linklabel" => "Uninstall", "linkurl" => "index.php?module=VReports&parent=Settings&view=Uninstall", "linkicon" => "");
        return $settingsLinks;
    }
    /**
     * Function returns quick links for the module
     * @return <Array of Vtiger_Link_Model>
     */
    public function getSideBarLinks($linkParams)
    {
        $quickLinks = array(array("linktype" => "SIDEBARLINK", "linklabel" => "LBL_VREPORTS", "linkurl" => $this->getListViewUrl(), "linkicon" => ""));
        foreach ($quickLinks as $quickLink) {
            $links["SIDEBARLINK"][] = Vtiger_Link_Model::getInstanceFromValues($quickLink);
        }
        $quickWidgets = array(array("linktype" => "SIDEBARWIDGET", "linklabel" => "LBL_RECENTLY_MODIFIED", "linkurl" => "module=" . $this->get("name") . "&view=IndexAjax&mode=showActiveRecords", "linkicon" => ""));
        foreach ($quickWidgets as $quickWidget) {
            $links["SIDEBARWIDGET"][] = Vtiger_Link_Model::getInstanceFromValues($quickWidget);
        }
        return $links;
    }
    /**
     * Function returns the recent created reports
     * @param <Number> $limit
     * @return <Array of Reports_Record_Model>
     */
    public function getRecentRecords($limit = 10)
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT * FROM vtiger_vreport \n\t\t\t\t\t\tINNER JOIN vtiger_vreportmodules ON vtiger_vreportmodules.reportmodulesid = vtiger_vreport.reportid\n\t\t\t\t\t\tINNER JOIN vtiger_tab ON vtiger_tab.name = vtiger_vreportmodules.primarymodule AND presence = 0\n\t\t\t\t\t\tORDER BY reportid DESC LIMIT ?", array($limit));
        $rows = $db->num_rows($result);
        $recentRecords = array();
        for ($i = 0; $i < $rows; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            $recentRecords[$row["reportid"]] = $this->getRecordFromArray($row);
        }
        return $recentRecords;
    }
    /**
     * Function returns the report folders
     * @return <Array of Reports_Folder_Model>
     */
    public function getFolders()
    {
        return VReports_Folder_Model::getAll();
    }
    /**
     * Function to get the url for add folder from list view of the module
     * @return <string> - url
     */
    public function getAddFolderUrl()
    {
        return "index.php?module=" . $this->get("name") . "&view=EditFolder";
    }
    /**
     * Function to check if the extension module is permitted for utility action
     * @return <boolean> true
     */
    public function isUtilityActionEnabled()
    {
        return true;
    }
    /**
     * Function is a callback from Vtiger_Link model to check permission for the links
     * @param type $linkData
     */
    public function checkLinkAccess($linkData)
    {
        $privileges = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $reportModuleModel = Vtiger_Module_Model::getInstance("VReports");
        return $privileges->hasModulePermission($reportModuleModel->getId());
    }
    public function getUtilityActionsNames()
    {
        return array("Export");
    }
}

?>