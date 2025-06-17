<?php

require_once "include/database/PearDatabase.php";
require_once "data/CRMEntity.php";
require_once "include/utils/UserInfoUtil.php";
require_once "modules/VReports/ReportUtils.php";
global $calpath;
global $app_strings;
global $mod_strings;
global $app_list_strings;
global $modules;
global $blocks;
global $adv_filter_options;
global $log;
global $report_modules;
global $related_modules;
global $old_related_modules;
$adv_filter_options = array("e" => "equals", "n" => "not equal to", "s" => "starts with", "ew" => "ends with", "c" => "contains", "k" => "does not contain", "l" => "less than", "g" => "greater than", "m" => "less or equal", "h" => "greater or equal", "bw" => "between", "a" => "after", "b" => "before", "y" => "is empty");
$old_related_modules = array("Accounts" => array("Potentials", "Contacts", "Products", "Quotes", "Invoice"), "Contacts" => array("Accounts", "Potentials", "Quotes", "PurchaseOrder"), "Potentials" => array("Accounts", "Contacts", "Quotes"), "Calendar" => array("Leads", "Accounts", "Contacts", "Potentials"), "Products" => array("Accounts", "Contacts"), "HelpDesk" => array("Products"), "Quotes" => array("Accounts", "Contacts", "Potentials"), "PurchaseOrder" => array("Contacts"), "Invoice" => array("Accounts"), "Campaigns" => array("Products"));
$related_modules = array();
class VReports extends CRMEntity
{
    /**
     * This class has the informations for Reports and inherits class CRMEntity and
     * has the variables required to generate,save,restore vtiger_vreports
     * and also the required functions for the same
     * Contributor(s): ______________________________________..
     */
    public $srptfldridjs = NULL;
    public $column_fields = array();
    public $sort_fields = array();
    public $sort_values = array();
    public $id = NULL;
    public $mode = NULL;
    public $mcount = NULL;
    public $startdate = NULL;
    public $enddate = NULL;
    public $ascdescorder = NULL;
    public $stdselectedfilter = NULL;
    public $stdselectedcolumn = NULL;
    public $primodule = NULL;
    public $secmodule = NULL;
    public $columnssummary = NULL;
    public $is_editable = NULL;
    public $reporttype = NULL;
    public $reportname = NULL;
    public $reportdescription = NULL;
    public $folderid = NULL;
    public $module_blocks = NULL;
    public $pri_module_columnslist = NULL;
    public $sec_module_columnslist = NULL;
    public $advft_criteria = NULL;
    public $adv_rel_fields = array();
    public $module_list = array();
    public function vtlib_handler($moduleName, $eventType)
    {
        global $adb;
        if ($eventType == "module.postinstall") {
            $this->initInstallVReport($moduleName);
            $this->addSettingDefault();
            $this->addDefaultCss();
            $this->resetValid();
            $this->updateModule($moduleName);
        } else {
            if ($eventType == "module.disabled") {
                $this->initInstallVReport($moduleName);
                $this->removeSettingDefault();
            } else {
                if ($eventType == "module.enabled") {
                    $this->addSettingDefault();
                } else {
                    if ($eventType == "module.preuninstall") {
                        $this->removeValid();
                        $this->removeSettingDefault();
                        $this->removeScheduleVreports();
                    } else {
                        if ($eventType == "module.preupdate") {
                            $this->removeSettingDefault();
                        } else {
                            if ($eventType == "module.postupdate") {
                                $this->initInstallVReport($moduleName);
                                $this->resetValid();
                                $this->addDefaultCss();
                                $this->addSettingDefault();
                                $this->updateModule($moduleName);
                                $this->updateTable();
                            }
                        }
                    }
                }
            }
        }
    }
    public function resetValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("VReports"));
        $adb->pquery("INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);", array("VReports", "0"));
    }
    public function removeValid()
    {
        global $adb;
        $adb->pquery("DELETE FROM `vte_modules` WHERE module=?;", array("VReports"));
        $adb->pquery("DELETE FROM `vtiger_ws_entity` WHERE name=?;", array("VReports"));
    }
    public function addSettingDefault()
    {
        global $adb;
        $adb->pquery("INSERT INTO vtevreports_settings(`enable`) VALUES (1)", array());
        $sql = "SELECT * FROM `vtiger_settings_field` WHERE `name`=?";
        $res = $adb->pquery($sql, array("Reports & Dashboards"));
        if ($adb->num_rows($res) == 0) {
            $fieldid = $adb->getUniqueID("vtiger_settings_field");
            $blockid = getSettingsBlockId("LBL_OTHER_SETTINGS");
            $seq_res = $adb->pquery("SELECT max(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?", array($blockid));
            if (0 < $adb->num_rows($seq_res)) {
                $cur_seq = $adb->query_result($seq_res, 0, "max_seq");
                if ($cur_seq != NULL) {
                    $seq = $cur_seq + 1;
                }
            }
            $adb->pquery("INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence)\n                VALUES (?,?,?,?,?,?,?)", array($fieldid, $blockid, "Reports & Dashboards", "", "", "index.php?module=VReports&parent=Settings&view=Settings", $seq));
        }
        $rs = $adb->pquery("SELECT * FROM `vtiger_ws_entity` WHERE `name` = ?", array("VReports"));
        if ($adb->num_rows($rs) == 0) {
            $adb->pquery("INSERT INTO `vtiger_ws_entity` (`name`, `handler_path`, `handler_class`, `ismodule`)            VALUES (?, 'include/Webservices/VtigerModuleOperation.php', 'VtigerModuleOperation', '1');", array("VReports"));
            $adb->pquery("UPDATE vtiger_ws_entity_seq SET id=(SELECT MAX(id) FROM vtiger_ws_entity)", array());
        }
    }
    public function addDefaultCss()
    {
        global $adb;
        $adb->pquery("INSERT INTO vtiger_vreports_css_defaults (widgetId, type, description)\n\t\t\t\t\t\t\tSELECT * FROM (SELECT '0', 'minilisttable_table', 'white-space : nowrap;') AS tmp\n\t\t\t\t\t\t\tWHERE NOT EXISTS ( SELECT widgetId FROM vtiger_vreports_css_defaults WHERE widgetId = '0' AND type = 'minilisttable_table') LIMIT 1;", array());
        $adb->pquery("INSERT INTO vtiger_vreports_css_defaults (widgetId, type, description)\n\t\t\t\t\t\t\t\tSELECT * FROM (SELECT '0', 'minilisttable_tr', 'padding:5px;') AS tmp\n\t\t\t\t\t\t\t\tWHERE NOT EXISTS ( SELECT widgetId FROM vtiger_vreports_css_defaults WHERE widgetId = '0' AND type = 'minilisttable_tr') LIMIT 1;", array());
        $adb->pquery("UPDATE `vtiger_vreports_css_defaults` SET `description` = '' WHERE `type` = 'minilisttable_td'", array());
    }
    public function updateTable()
    {
        global $adb;
        $adb->pquery("ALTER TABLE vtiger_module_vreportdashboard_widgets MODIFY COLUMN sizeWidth int(11)", array());
        $adb->pquery("ALTER TABLE vtiger_module_vreportdashboard_widgets MODIFY COLUMN sizeHeight int(11)", array());
        $adb->pquery("ALTER TABLE vtiger_vreporttype MODIFY COLUMN rename_field TEXT DEFAULT NULL", array());
        $adb->pquery("ALTER TABLE vtiger_vreporttype MODIFY COLUMN rename_field_chart TEXT DEFAULT NULL", array());
        $adb->pquery("ALTER TABLE vtiger_vreporttype MODIFY COLUMN sort_by varchar(500)", array());
    }
    public function removeSettingDefault()
    {
        global $adb;
        $adb->pquery("DELETE FROM vtevreports_settings");
        $adb->pquery("DELETE FROM vtiger_settings_field WHERE `name` = ?", array("Reports & Dashboards"));
    }
    public function removeScheduleVreports()
    {
        global $adb;
        $adb->pquery("DELETE FROM vtiger_cron_task WHERE `name` = ?", array("Schedule VReports"));
    }
    public function initInstallVReport($moduleName)
    {
        global $adb;
        global $vtiger_current_version;
        $tabId = getTabid($moduleName);
        $vreportActions = array("LBL_ADD_RECORD", "LBL_DETAIL_REPORT", "LBL_CHARTS", "LBL_ADD_FOLDER", "LBL_PIVOT", "Icon VReport", "LBL_SQL_REPORT");
        foreach ($vreportActions as $key => $vreportAction) {
            $checkLink = $adb->pquery("SELECT `tabid` FROM `vtiger_links` WHERE `tabid`=? AND `linklabel` =?", array($tabId, $vreportAction));
            if ($adb->num_rows($checkLink) == 0) {
                switch ($vreportAction) {
                    case "LBL_ADD_RECORD":
                        $type = "LISTVIEWBASIC";
                        $label = $vreportAction;
                        Vtiger_Link::addLink($tabId, $type, $label, "");
                        break;
                    case "LBL_DETAIL_REPORT":
                        $type = "LISTVIEWBASIC";
                        $label = $vreportAction;
                        $url = "javascript:VReports_List_Js.addReport(\"index.php?module=VReports&view=Edit\")";
                        $handlerInfo["path"] = "modules/VReports/models/Module.php";
                        $handlerInfo["class"] = "VReports_Module_Model";
                        $handlerInfo["method"] = "checkLinkAccess";
                        Vtiger_Link::addLink($tabId, $type, $label, $url, 0, $handlerInfo);
                        break;
                    case "LBL_CHARTS":
                        $type = "LISTVIEWBASIC";
                        $label = $vreportAction;
                        $url = "javascript:VReports_List_Js.addReport(\"index.php?module=VReports&view=ChartEdit\")";
                        $handlerInfo["path"] = "modules/VReports/models/Module.php";
                        $handlerInfo["class"] = "VReports_Module_Model";
                        $handlerInfo["method"] = "checkLinkAccess";
                        Vtiger_Link::addLink($tabId, $type, $label, $url, 0, $handlerInfo);
                        break;
                    case "LBL_PIVOT":
                        $type = "LISTVIEWBASIC";
                        $label = $vreportAction;
                        $url = "javascript:VReports_List_Js.addReport(\"index.php?module=VReports&view=PivotEdit\")";
                        $handlerInfo["path"] = "modules/VReports/models/Module.php";
                        $handlerInfo["class"] = "VReports_Module_Model";
                        $handlerInfo["method"] = "checkLinkAccess";
                        Vtiger_Link::addLink($tabId, $type, $label, $url, 0, $handlerInfo);
                        break;
                    case "LBL_SQL_REPORT":
                        $type = "LISTVIEWBASIC";
                        $label = $vreportAction;
                        $url = "javascript:VReports_List_Js.addReport(\"index.php?module=VReports&view=SqlReportEdit\")";
                        $handlerInfo["path"] = "modules/VReports/models/Module.php";
                        $handlerInfo["class"] = "VReports_Module_Model";
                        $handlerInfo["method"] = "checkLinkAccess";
                        Vtiger_Link::addLink($tabId, $type, $label, $url, 0);
                        break;
                    case "LBL_ADD_FOLDER":
                        $type = "LISTVIEWBASIC";
                        $label = $vreportAction;
                        $url = "javascript:VReports_List_Js.triggerAddFolder(\"index.php?module=VReports&view=EditFolder\")";
                        $handlerInfo["path"] = "modules/VReports/models/Module.php";
                        $handlerInfo["class"] = "VReports_Module_Model";
                        $handlerInfo["method"] = "checkLinkAccess";
                        Vtiger_Link::addLink($tabId, $type, $label, $url, 0, $handlerInfo);
                        break;
                    case "Icon VReport":
                        $type = "HEADERCSS";
                        $label = $vreportAction;
                        $url = "layouts/v7/modules/VReports/resources/ModuleIcon.css";
                        Vtiger_Link::addLink($tabId, $type, $label, $url, 0);
                        break;
                    default:
                        break;
                }
            }
        }
        $adb->pquery("UPDATE vtiger_links ,( SELECT linkid FROM vtiger_links links WHERE links.`tabid` = " . $tabId . " AND links.`linklabel` = 'LBL_ADD_RECORD') a \n\t\t\tSET vtiger_links.parent_link = a.linkid \n\t\t\tWHERE vtiger_links.`tabid` = " . $tabId . " \n\t\t\tAND vtiger_links.`linklabel` IN( 'LBL_DETAIL_REPORT' , 'LBL_CHARTS', 'LBL_PIVOT', 'LBL_SQL_REPORT')", array());
        $checkFolderDefault = $adb->pquery("SELECT 1 FROM vtiger_vreportfolder WHERE foldername = ?", array("Default"));
        if ($adb->num_rows($checkFolderDefault) == 0) {
            $adb->pquery("INSERT INTO vtiger_vreportfolder(foldername,description,state) VALUES(?,?,?)", array("Default", "", "CUSTOMIZED"));
        }
        if (stripos($vtiger_current_version, "7.1") !== false) {
            $adb->pquery("UPDATE vtiger_tab set source = '' WHERE name =?", array("Vreports"));
        }
        $this->addBoards();
        $this->addDashboardDefault();
        $rsMiniListLinks = $adb->pquery("SELECT 1 FROM vtiger_links WHERE tabid = ? AND linktype =? AND linklabel = ?", array($tabId, "DASHBOARDWIDGET", "Mini List"));
        if ($adb->num_rows($rsMiniListLinks) == 0) {
            Vtiger_Link::addLink($tabId, "DASHBOARDWIDGET", "Mini List VReports", "index.php?module=VReports&view=ShowWidget&name=MiniList", 0);
            Vtiger_Link::addLink($tabId, "DASHBOARDWIDGET", "Key Metrics", "index.php?module=VReports&view=ShowWidget&name=KeyMetrics", 0);
            Vtiger_Link::addLink($tabId, "DASHBOARDWIDGET", "History", "index.php?module=VReports&view=ShowWidget&name=History", 0);
            Vtiger_Link::addLink($tabId, "DASHBOARDWIDGET", "Gauge", "index.php?module=VReports&view=ShowWidget&name=Gauge", 0);
        }
        $moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
        $moduleInstance->enableTools("Export");
    }
    public function addDashboardDefault()
    {
        global $adb;
        $rsTabDashboard = $adb->pquery("SELECT 1 FROM vtiger_vreportdashboard_tabs WHERE tabname = ?", array("Default"));
        if ($adb->num_rows($rsTabDashboard) == 0) {
            $adb->pquery("INSERT INTO vtiger_vreportdashboard_tabs(`tabname`,`isdefault`,`sequence`,`userid`,`boardid`) VALUES(?,?,?,?,(select id from vtiger_vreportdashboard_boards where boardname = \"Default\"))", array("Default", 1, 1, 0));
        } else {
            $adb->pquery("UPDATE vtiger_vreportdashboard_tabs SET userid = 0 WHERE tabname = ?", array("Default"));
            $adb->pquery("UPDATE vtiger_vreportdashboard_tabs SET boardid = (select id from vtiger_vreportdashboard_boards where boardname = \"Default\") WHERE boardid IS NULL");
        }
    }
    public function addBoards()
    {
        global $adb;
        $rsBoard = $adb->pquery("select * from vtiger_vreportdashboard_boards WHERE boardname ='Default'");
        if ($adb->num_rows($rsBoard) == 0) {
            $adb->pquery("INSERT INTO vtiger_vreportdashboard_boards(`boardname`,`userid`) VALUES(?,0)", array("Default"));
        }
    }
    /** Function to set primodule,secmodule,reporttype,reportname,reportdescription,folderid for given vtiger_vreportid
     *  This function accepts the vtiger_vreportid as argument
     *  It sets primodule,secmodule,reporttype,reportname,reportdescription,folderid for the given vtiger_vreportid
     */
    public function __construct($reportid = "")
    {
        global $adb;
        global $current_user;
        global $theme;
        global $mod_strings;
        $this->initListOfModules();
        if ($reportid != "") {
            $cachedInfo = VTCacheUtils::lookupReport_Info($current_user->id, $reportid);
            $subordinate_users = VTCacheUtils::lookupReport_SubordinateUsers($reportid);
            $reportModel = VReports_Record_Model::getCleanInstance($reportid);
            $sharingType = $reportModel->get("sharingtype");
            if ($cachedInfo === false) {
                $ssql = "select vtiger_vreportmodules.*,vtiger_vreport.* from vtiger_vreport inner join vtiger_vreportmodules on vtiger_vreport.reportid = vtiger_vreportmodules.reportmodulesid";
                $ssql .= " where vtiger_vreport.reportid = ?";
                $params = array($reportid);
                require_once "include/utils/GetUserGroups.php";
                require "user_privileges/user_privileges_" . $current_user->id . ".php";
                $userGroups = new GetUserGroups();
                $userGroups->getAllUserGroups($current_user->id);
                $user_groups = $userGroups->user_groups;
                if (!empty($user_groups) && $sharingType == "Private") {
                    $user_group_query = " (shareid IN (" . generateQuestionMarks($user_groups) . ") AND setype='groups') OR";
                    array_push($params, $user_groups);
                }
                $non_admin_query = " vtiger_vreport.reportid IN (SELECT reportid from vtiger_vreportsharing WHERE " . $user_group_query . " (shareid=? AND setype='users'))";
                if ($sharingType == "Private") {
                    $ssql .= " and (( (" . $non_admin_query . ") or vtiger_vreport.sharingtype='Public' or vtiger_vreport.owner = ? or vtiger_vreport.owner in(select vtiger_user2role.userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%'))";
                    array_push($params, $current_user->id);
                    array_push($params, $current_user->id);
                }
                $query = $adb->pquery("select userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%'", array());
                $subordinate_users = array();
                for ($i = 0; $i < $adb->num_rows($query); $i++) {
                    $subordinate_users[] = $adb->query_result($query, $i, "userid");
                }
                VTCacheUtils::updateReport_SubordinateUsers($reportid, $subordinate_users);
                $queryObj = new stdClass();
                $queryObj->query = $ssql;
                $queryObj->queryParams = $params;
                $queryObj = self::getVReportSharingQuery($queryObj, $sharingType);
                $result = $adb->pquery($queryObj->query, $queryObj->queryParams);
                if ($result && $adb->num_rows($result)) {
                    $reportmodulesrow = $adb->fetch_array($result);
                    VTCacheUtils::updateReport_Info($current_user->id, $reportid, $reportmodulesrow["primarymodule"], $reportmodulesrow["secondarymodules"], $reportmodulesrow["reporttype"], $reportmodulesrow["reportname"], $reportmodulesrow["description"], $reportmodulesrow["folderid"], $reportmodulesrow["owner"]);
                }
                $cachedInfo = VTCacheUtils::lookupReport_Info($current_user->id, $reportid);
            }
            if ($cachedInfo) {
                $this->primodule = $cachedInfo["primarymodule"];
                $this->secmodule = $cachedInfo["secondarymodules"];
                $this->reporttype = $cachedInfo["reporttype"];
                $this->reportname = decode_html($cachedInfo["reportname"]);
                $this->reportdescription = decode_html($cachedInfo["description"]);
                $this->folderid = $cachedInfo["folderid"];
                if ($is_admin == true || in_array($cachedInfo["owner"], $subordinate_users) || $cachedInfo["owner"] == $current_user->id) {
                    $this->is_editable = "true";
                } else {
                    $this->is_editable = "false";
                }
            }
        }
    }
    public function updateModuleList($module)
    {
        global $adb;
        if (!isset($module)) {
            return NULL;
        }
        require_once "include/utils/utils.php";
        $tabid = getTabid($module);
        if ($module == "Calendar") {
            $tabid = array(9, 16);
        }
        $sql = "SELECT blockid, blocklabel FROM vtiger_blocks WHERE tabid IN (" . generateQuestionMarks($tabid) . ")";
        $res = $adb->pquery($sql, array($tabid));
        $noOfRows = $adb->num_rows($res);
        if ($noOfRows <= 0) {
            return NULL;
        }
        for ($index = 0; $index < $noOfRows; $index++) {
            $blockid = $adb->query_result($res, $index, "blockid");
            if (in_array($blockid, $this->module_list[$module])) {
                continue;
            }
            $blockid_list[] = $blockid;
            $blocklabel = $adb->query_result($res, $index, "blocklabel");
            $this->module_list[$module][$blocklabel] = $blockid;
        }
    }
    public function initListOfModules()
    {
        global $adb;
        global $current_user;
        global $old_related_modules;
        $restricted_modules = array("Events", "Webmails");
        $restricted_blocks = array("LBL_COMMENTS", "LBL_COMMENT_INFORMATION");
        $this->module_id = array();
        $this->module_list = array();
        $modulerows = vtlib_prefetchModuleActiveInfo(false);
        $cachedInfo = VTCacheUtils::lookupReport_ListofModuleInfos();
        if ($cachedInfo !== false) {
            $this->module_list = $cachedInfo["module_list"];
            $this->related_modules = $cachedInfo["related_modules"];
        } else {
            if ($modulerows) {
                foreach ($modulerows as $resultrow) {
                    if ($resultrow["presence"] == "1") {
                        continue;
                    }
                    if ($resultrow["isentitytype"] != "1") {
                        continue;
                    }
                    if (in_array($resultrow["name"], $restricted_modules)) {
                        continue;
                    }
                    if ($resultrow["name"] != "Calendar") {
                        $this->module_id[$resultrow["tabid"]] = $resultrow["name"];
                    } else {
                        $this->module_id[9] = $resultrow["name"];
                        $this->module_id[16] = $resultrow["name"];
                    }
                    $this->module_list[$resultrow["name"]] = array();
                }
                $moduleids = array_keys($this->module_id);
                $reportblocks = $adb->pquery("SELECT blockid, blocklabel, tabid FROM vtiger_blocks WHERE tabid IN (" . generateQuestionMarks($moduleids) . ")", array($moduleids));
                $prev_block_label = "";
                if ($adb->num_rows($reportblocks)) {
                    while ($resultrow = $adb->fetch_array($reportblocks)) {
                        $blockid = $resultrow["blockid"];
                        $blocklabel = $resultrow["blocklabel"];
                        $module = $this->module_id[$resultrow["tabid"]];
                        if (in_array($blocklabel, $restricted_blocks) || in_array($blockid, $this->module_list[$module]) || isset($this->module_list[$module][getTranslatedString($blocklabel, $module)])) {
                            continue;
                        }
                        if (!empty($blocklabel)) {
                            if ($module == "Calendar" && $blocklabel == "LBL_CUSTOM_INFORMATION") {
                                $this->module_list[$module][$blockid] = getTranslatedString($blocklabel, $module);
                            } else {
                                $this->module_list[$module][$blockid] = getTranslatedString($blocklabel, $module);
                            }
                            $prev_block_label = $blocklabel;
                        } else {
                            $this->module_list[$module][$blockid] = getTranslatedString($prev_block_label, $module);
                        }
                    }
                }
                $relatedmodules = $adb->pquery("SELECT vtiger_tab.name, vtiger_relatedlists.tabid FROM vtiger_tab\n\t\t\t\t\tINNER JOIN vtiger_relatedlists on vtiger_tab.tabid=vtiger_relatedlists.related_tabid\n\t\t\t\t\tWHERE vtiger_tab.isentitytype=1\n\t\t\t\t\tAND vtiger_tab.name NOT IN(" . generateQuestionMarks($restricted_modules) . ")\n\t\t\t\t\tAND vtiger_tab.presence = 0 AND vtiger_relatedlists.label!='Activity History'\n\t\t\t\t\tUNION\n\t\t\t\t\tSELECT vtiger_tab.name, vtiger_relatedlists.related_tabid FROM vtiger_tab\n\t\t\t\t\tINNER JOIN vtiger_relatedlists on vtiger_tab.tabid=vtiger_relatedlists.tabid\n\t\t\t\t\tWHERE vtiger_tab.isentitytype=1\n\t\t\t\t\tAND vtiger_tab.name NOT IN('Events','Webmails')\n\t\t\t\t\tAND vtiger_tab.presence = 0 AND vtiger_relatedlists.label!='Activity History'\n\t\t\t\t\tUNION\n\t\t\t\t\tSELECT module, vtiger_tab.tabid FROM vtiger_fieldmodulerel\n\t\t\t\t\tINNER JOIN vtiger_tab on vtiger_tab.name = vtiger_fieldmodulerel.relmodule AND vtiger_tab.presence = 0\n\t\t\t\t\tINNER JOIN vtiger_tab AS vtiger_tabrel ON vtiger_tabrel.name = vtiger_fieldmodulerel.module AND vtiger_tabrel.presence = 0\n                    INNER JOIN vtiger_field ON vtiger_field.fieldid = vtiger_fieldmodulerel.fieldid\n\t\t\t\t\tWHERE vtiger_tab.isentitytype = 1\n\t\t\t\t\tAND vtiger_tab.name NOT IN(" . generateQuestionMarks($restricted_modules) . ")\n\t\t\t\t\tAND vtiger_tab.presence = 0\n                    AND vtiger_field.fieldname NOT LIKE ?\n                    UNION\n\t\t\t\t\tSELECT relmodule, vtiger_tab.tabid FROM vtiger_fieldmodulerel\n\t\t\t\t\tINNER JOIN vtiger_tab on vtiger_tab.name = vtiger_fieldmodulerel.module AND vtiger_tab.presence = 0\n\t\t\t\t\tINNER JOIN vtiger_tab AS vtiger_tabrel ON vtiger_tabrel.name = vtiger_fieldmodulerel.relmodule AND vtiger_tabrel.presence = 0\n\t\t\t\t\tINNER JOIN vtiger_field ON vtiger_field.fieldid = vtiger_fieldmodulerel.fieldid\n\t\t\t\t\tWHERE vtiger_tab.isentitytype = 1\n\t\t\t\t\tAND vtiger_tab.name NOT IN('Events','Webmails')\n\t\t\t\t\tAND vtiger_tab.presence = 0\n\t\t\t\t\tAND vtiger_field.fieldname NOT LIKE ?", array($restricted_modules, $restricted_modules, "cf_%", "cf_%"));
                if ($adb->num_rows($relatedmodules)) {
                    while ($resultrow = $adb->fetch_array($relatedmodules)) {
                        $module = $this->module_id[$resultrow["tabid"]];
                        if (!isset($this->related_modules[$module])) {
                            $this->related_modules[$module] = array();
                        }
                        if ($module != $resultrow["name"]) {
                            $this->related_modules[$module][] = $resultrow["name"];
                        }
                        if (isset($old_related_modules[$module])) {
                            $rel_mod = array();
                            foreach ($old_related_modules[$module] as $key => $name) {
                                if (vtlib_isModuleActive($name) && isPermitted($name, "index", "")) {
                                    $rel_mod[] = $name;
                                }
                            }
                            if (!empty($rel_mod)) {
                                $this->related_modules[$module] = array_merge($this->related_modules[$module], $rel_mod);
                                $this->related_modules[$module] = array_unique($this->related_modules[$module]);
                            }
                        }
                    }
                }
                foreach ($this->related_modules as $module => $related_modules) {
                    if ($module == "Emails") {
                        $this->related_modules[$module] = getEmailRelatedModules();
                    }
                }
                VTCacheUtils::updateReport_ListofModuleInfos($this->module_list, $this->related_modules);
            }
        }
    }
    /** Function to get the Listview of Reports
     *  This function accepts no argument
     *  This generate the Reports view page and returns a string
     *  contains HTML
     */
    public function sgetRptFldr($mode = "")
    {
        global $adb;
        global $log;
        global $mod_strings;
        $returndata = array();
        $sql = "select * from vtiger_vreportfolder order by folderid";
        $result = $adb->pquery($sql, array());
        $reportfldrow = $adb->fetch_array($result);
        if ($mode != "") {
            $reportsInAllFolders = $this->sgetRptsforFldr(false);
            do {
                if ($reportfldrow["state"] == $mode) {
                    $details = array();
                    $details["state"] = $reportfldrow["state"];
                    $details["id"] = $reportfldrow["folderid"];
                    $details["name"] = $mod_strings[$reportfldrow["foldername"]] == "" ? $reportfldrow["foldername"] : $mod_strings[$reportfldrow["foldername"]];
                    $details["description"] = $reportfldrow["description"];
                    $details["fname"] = popup_decode_html($details["name"]);
                    $details["fdescription"] = popup_decode_html($reportfldrow["description"]);
                    $details["details"] = $reportsInAllFolders[$reportfldrow["folderid"]];
                    $returndata[] = $details;
                }
            } while ($reportfldrow = $adb->fetch_array($result));
        } else {
            do {
                $details = array();
                $details["state"] = $reportfldrow["state"];
                $details["id"] = $reportfldrow["folderid"];
                $details["name"] = $mod_strings[$reportfldrow["foldername"]] == "" ? $reportfldrow["foldername"] : $mod_strings[$reportfldrow["foldername"]];
                $details["description"] = $reportfldrow["description"];
                $details["fname"] = popup_decode_html($details["name"]);
                $details["fdescription"] = popup_decode_html($reportfldrow["description"]);
                $returndata[] = $details;
            } while ($reportfldrow = $adb->fetch_array($result));
        }
        $log->info("Reports :: ListView->Successfully returned vtiger_vreport folder HTML");
        return $returndata;
    }
    /**
     * Function returns the query object after joining necessary shared tables (users,groups,roles,rs)
     * for a non admin user
     * @param type $queryObj
     * @return type
     */
    public static function getVReportSharingQuery($queryObj, $rpt_fldr_id = false)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userPrivilegeModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $sql = $queryObj->query;
        $params = $queryObj->queryParams;
        if ($rpt_fldr_id != "" || $rpt_fldr_id == "shared" || $rpt_fldr_id == "Private" || $rpt_fldr_id == "All" || $rpt_fldr_id == "Public") {
            $userId = $currentUser->getId();
            $userGroups = new GetUserGroups();
            $userGroups->getAllUserGroups($userId);
            $groups = $userGroups->user_groups;
            $userRole = fetchUserRole($userId);
            $parentRoles = getParentRole($userRole);
            $parentRolelist = array();
            foreach ($parentRoles as $par_rol_id) {
                array_push($parentRolelist, $par_rol_id);
            }
            array_push($parentRolelist, $userRole);
            $userParentRoleSeq = $userPrivilegeModel->get("parent_role_seq");
            $sql .= " OR ( vtiger_vreport.sharingtype='Public' OR " . $userId . " IN (\n\t\t\t\t\t\t\t\tSELECT vtiger_user2role.userid FROM vtiger_user2role\n\t\t\t\t\t\t\t\t\tINNER JOIN vtiger_users ON vtiger_users.id = vtiger_user2role.userid\n\t\t\t\t\t\t\t\t\tINNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid\n\t\t\t\t\t\t\t\tWHERE vtiger_role.parentrole LIKE '" . $userParentRoleSeq . "::%') \n                            OR vtiger_vreport.reportid IN (SELECT vtiger_vreport_shareusers.reportid FROM vtiger_vreport_shareusers WHERE vtiger_vreport_shareusers.userid=?)";
            $params[] = $userId;
            if (!empty($groups)) {
                $sql .= " OR vtiger_vreport.reportid IN (SELECT vtiger_vreport_sharegroups.reportid FROM vtiger_vreport_sharegroups WHERE vtiger_vreport_sharegroups.groupid IN (" . generateQuestionMarks($groups) . "))";
                $params = array_merge($params, $groups);
            }
            $sql .= " OR vtiger_vreport.reportid IN (SELECT vtiger_vreport_sharerole.reportid FROM vtiger_vreport_sharerole WHERE vtiger_vreport_sharerole.roleid =?)";
            $params[] = $userRole;
            if (!empty($parentRolelist)) {
                $sql .= " OR vtiger_vreport.reportid IN (SELECT vtiger_vreport_sharers.reportid FROM vtiger_vreport_sharers WHERE vtiger_vreport_sharers.rsid IN (" . generateQuestionMarks($parentRolelist) . "))";
                $params = array_merge($params, $parentRolelist);
            }
            $sql .= ")) ";
        }
        $queryObj->query = $sql;
        $queryObj->queryParams = $params;
        return $queryObj;
    }
    /** Function to get the Reports inside each modules
     *  This function accepts the folderid
     *  This Generates the Reports under each Reports module
     *  This Returns a HTML sring
     */
    public function sgetRptsforFldr($rpt_fldr_id, $paramsList = false)
    {
        $srptdetails = "";
        global $adb;
        global $log;
        global $mod_strings;
        global $current_user;
        $returndata = array();
        require_once "include/utils/UserInfoUtil.php";
        $userNameSql = getSqlForNameInDisplayFormat(array("first_name" => "vtiger_users.first_name", "last_name" => "vtiger_users.last_name"), "Users");
        $sql = "SELECT vtiger_vreport.*, vtiger_vreportmodules.*, vtiger_vreportfolder.folderid, vtiger_vreportfolder.foldername,\n\t\t\tCASE WHEN (vtiger_users.user_name NOT LIKE '') THEN " . $userNameSql . " END AS ownername FROM vtiger_vreport \n\t\t\tLEFT JOIN vtiger_users ON vtiger_vreport.owner = vtiger_users.id\n\t\t\tINNER JOIN vtiger_vreportfolder ON vtiger_vreportfolder.folderid = vtiger_vreport.folderid\n\t\t\tINNER JOIN vtiger_vreportmodules ON vtiger_vreportmodules.reportmodulesid = vtiger_vreport.reportid\n\t\t\tLEFT JOIN vtiger_tab ON vtiger_tab.name = vtiger_vreportmodules.primarymodule AND vtiger_tab.presence = 0\n\t\t\tLEFT JOIN vtiger_vreport_shareall ON vtiger_vreport.reportid = vtiger_vreport_shareall.reportid\n\t\t\tWHERE (( vtiger_vreport.reporttype != 'sql' AND \n\t\t\t(SELECT presence FROM vtiger_tab WHERE vtiger_tab. NAME = vtiger_vreportmodules.primarymodule LIMIT 1 ) = 0)\n\t\t\tOR vtiger_vreport.reporttype = 'sql')";
        $params = array();
        if ($rpt_fldr_id !== false && $rpt_fldr_id !== "shared" && $rpt_fldr_id !== "All" && $rpt_fldr_id !== "Public") {
            $sql .= " AND vtiger_vreportfolder.folderid=?";
            $params[] = $rpt_fldr_id;
        }
        if ($rpt_fldr_id == "shared") {
            $sql .= " AND vtiger_vreport.sharingtype=? AND vtiger_vreport.owner != ?";
            $params[] = "Private";
            $params[] = $current_user->id;
        }
        $searchCondition = getVReportSearchCondition($paramsList["searchParams"], $rpt_fldr_id);
        if ($searchCondition) {
            $sql .= $searchCondition;
        }
        if (strtolower($current_user->is_admin) != "on") {
            require "user_privileges/user_privileges_" . $current_user->id . ".php";
            require_once "include/utils/GetUserGroups.php";
            $userGroups = new GetUserGroups();
            $userGroups->getAllUserGroups($current_user->id);
            $user_groups = $userGroups->user_groups;
            if (!empty($user_groups) && ($rpt_fldr_id == "shared" || $rpt_fldr_id == "All" || $rpt_fldr_id == "Public")) {
                $user_group_query = " (shareid IN (" . generateQuestionMarks($user_groups) . ") AND setype='groups') OR";
                $non_admin_query = " vtiger_vreport.reportid IN (SELECT reportid FROM vtiger_vreportsharing WHERE " . $user_group_query . " (shareid=? AND setype='users'))";
                foreach ($user_groups as $userGroup) {
                    array_push($params, $userGroup);
                }
                array_push($params, $current_user->id);
            }
            if ($rpt_fldr_id != "" || $rpt_fldr_id == "shared" || $rpt_fldr_id == "All" || $rpt_fldr_id == "Public") {
                if ($non_admin_query) {
                    $non_admin_query = "( " . $non_admin_query . " ) OR ";
                }
                if (strpos($sql, " WHERE ") !== false) {
                    $sql .= " AND ( (" . $non_admin_query . " vtiger_vreport.sharingtype='Public' OR " . "vtiger_vreport.owner = ? OR vtiger_vreport.owner IN (SELECT vtiger_user2role.userid " . "FROM vtiger_user2role INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid " . "INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid " . "WHERE vtiger_role.parentrole LIKE '" . $current_user_parent_role_seq . "::%'))";
                    array_push($params, $current_user->id);
                } else {
                    $sql .= " WHERE ( (" . $non_admin_query . " vtiger_vreport.sharingtype='Public' OR " . "vtiger_vreport.owner = ? OR vtiger_vreport.owner IN (SELECT vtiger_user2role.userid " . "FROM vtiger_user2role INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid " . "INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid " . "WHERE vtiger_role.parentrole LIKE '" . $current_user_parent_role_seq . "::%'))";
                    array_push($params, $current_user->id);
                }
            }
            $queryObj = new stdClass();
            $queryObj->query = $sql;
            $queryObj->queryParams = $params;
            $queryObj = self::getVReportSharingQuery($queryObj, $rpt_fldr_id);
            $sql = $queryObj->query;
            $params = $queryObj->queryParams;
        }
        if ($paramsList) {
            $startIndex = $paramsList["startIndex"];
            $pageLimit = $paramsList["pageLimit"];
            $orderBy = $paramsList["orderBy"];
            $sortBy = $paramsList["sortBy"];
            if ($orderBy) {
                $sql .= " ORDER BY " . $orderBy . " " . $sortBy;
            }
            $sql .= " LIMIT " . $startIndex . "," . ($pageLimit + 1);
        }
        $query = $adb->pquery("SELECT userid FROM vtiger_user2role INNER JOIN vtiger_users " . "ON vtiger_users.id=vtiger_user2role.userid INNER JOIN vtiger_role " . "ON vtiger_role.roleid=vtiger_user2role.roleid WHERE vtiger_role.parentrole LIKE '" . $current_user_parent_role_seq . "::%'", array());
        $subordinate_users = array();
        for ($i = 0; $i < $adb->num_rows($query); $i++) {
            $subordinate_users[] = $adb->query_result($query, $i, "userid");
        }
        $result = $adb->pquery($sql, $params);
        $report = $adb->fetch_array($result);
        if (is_array($report) && 0 < count($report)) {
            do {
                $report_details = array();
                $report_details["customizable"] = $report["customizable"];
                $report_details["reportid"] = $report["reportid"];
                $report_details["primarymodule"] = $report["primarymodule"];
                $report_details["secondarymodules"] = $report["secondarymodules"];
                $report_details["state"] = $report["state"];
                $report_details["description"] = $report["description"];
                $report_details["reportname"] = textlength_check(html_entity_decode($report["reportname"]));
                $report_details["reporttype"] = $report["reporttype"];
                $report_details["sharingtype"] = $report["sharingtype"];
                $report_details["foldername"] = $report["foldername"];
                $report_details["modifiedtime"] = Vtiger_Util_Helper::convertDateTimeIntoUsersDisplayFormat($report["modifiedtime"]);
                $report_details["pinned"] = $this->getTabPinnedDashboard($report["reportid"]);
                $report_details["owner"] = html_entity_decode($report["ownername"]);
                $report_details["folderid"] = $report["folderid"];
                if ($is_admin == true || in_array($report["owner"], $subordinate_users) || $report["owner"] == $current_user->id) {
                    $report_details["editable"] = "true";
                } else {
                    $report_details["editable"] = "false";
                }
                if (isPermitted($report["primarymodule"], "index") == "yes" || $report["reporttype"] == "sql") {
                    if ($rpt_fldr_id == false || $rpt_fldr_id == "shared" || $rpt_fldr_id == "All" || $rpt_fldr_id == "Public") {
                        $returndata[] = $report_details;
                    } else {
                        $returndata[$report["folderid"]][] = $report_details;
                    }
                }
            } while ($report = $adb->fetch_array($result));
        }
        if ($rpt_fldr_id !== false && $rpt_fldr_id !== "shared" && $rpt_fldr_id !== "All" && $rpt_fldr_id !== "Public") {
            $returndata = $returndata[$rpt_fldr_id];
        }
        $log->info("Reports :: ListView->Successfully returned vtiger_vreport details HTML");
        return $returndata;
    }
    public function getTabPinnedDashboard($reportId)
    {
        global $adb;
        global $current_user;
        $tabPinned = array();
        $result = $adb->pquery("SELECT dashboardtabid FROM vtiger_module_vreportdashboard_widgets WHERE reportid = ? AND userid = ?", array($reportId, $current_user->id));
        if ($adb->num_rows($result)) {
            for ($i = 0; $i < $adb->num_rows($result); $i++) {
                $tabPinned[] = $adb->query_result($result, $i, "dashboardtabid");
            }
        }
        return $tabPinned;
    }
    /** Function to get the array of ids
     *  This function forms the array for the ExpandCollapse
     *  Javascript
     *  It returns the array of ids
     *  Array('1RptFldr','2RptFldr',........,'9RptFldr','10RptFldr')
     */
    public function sgetJsRptFldr()
    {
        $srptfldr_js = "var ReportListArray=new Array(" . $this->srptfldridjs . ")\n\t\t\tsetExpandCollapse()";
        return $srptfldr_js;
    }
    /** Function to set the Primary module vtiger_fields for the given Report
     *  This function sets the primary module columns for the given Report
     *  It accepts the Primary module as the argument and set the vtiger_fields of the module
     *  to the varialbe pri_module_columnslist and returns true if sucess
     */
    public function getPriModuleColumnsList($module)
    {
        $arr_keys = array();
        if (is_array($this->module_list[$module])) {
            $arr_keys = array_keys($this->module_list[$module]);
        }
        $allColumnsListByBlocks =& $this->getColumnsListbyBlock($module, $arr_keys, true);
        foreach ($this->module_list[$module] as $key => $value) {
            $temp = $allColumnsListByBlocks[$key];
            if (!empty($ret_module_list[$module][$value])) {
                if (!empty($temp)) {
                    $ret_module_list[$module][$value] = array_merge($ret_module_list[$module][$value], $temp);
                }
            } else {
                $ret_module_list[$module][$value] = $temp;
            }
        }
        if ($module == "Emails") {
            foreach ($ret_module_list[$module] as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    if ($key1 == "vtiger_activity:time_start:Emails_Time_Start:time_start:T") {
                        unset($ret_module_list[$module][$key][$key1]);
                    }
                }
            }
        }
        $this->pri_module_columnslist = $ret_module_list;
        return true;
    }
    /** Function to set the Secondary module fileds for the given Report
     *  This function sets the secondary module columns for the given module
     *  It accepts the module as the argument and set the vtiger_fields of the module
     *  to the varialbe sec_module_columnslist and returns true if sucess
     */
    public function getSecModuleColumnsList($module)
    {
        if ($module != "") {
            $secmodule = explode(":", $module);
            for ($i = 0; $i < count($secmodule); $i++) {
                if ($this->module_list[$secmodule[$i]]) {
                    $this->sec_module_columnslist[$secmodule[$i]] = $this->getModuleFieldList($secmodule[$i]);
                    if ($this->module_list[$secmodule[$i]] == "Calendar" && $this->module_list["Events"]) {
                        $this->sec_module_columnslist["Events"] = $this->getModuleFieldList("Events");
                    }
                }
            }
            if ($module == "Emails") {
                foreach ($this->sec_module_columnslist[$module] as $key => $value) {
                    foreach ($value as $key1 => $value1) {
                        if ($key1 == "vtiger_activity:time_start:Emails_Time_Start:time_start:T") {
                            unset($this->sec_module_columnslist[$module][$key][$key1]);
                        }
                    }
                }
            }
        }
        return true;
    }
    /**
     *
     * @param String $module
     * @param type $blockIdList
     * @param Array $currentFieldList
     * @return Array
     */
    public function getBlockFieldList($module, $blockIdList, $currentFieldList, $allColumnsListByBlocks)
    {
        $temp = $allColumnsListByBlocks[$blockIdList];
        if (!empty($currentFieldList)) {
            if (!empty($temp)) {
                $currentFieldList = array_merge($currentFieldList, $temp);
            }
        } else {
            $currentFieldList = $temp;
        }
        return $currentFieldList;
    }
    public function getModuleFieldList($module)
    {
        $allColumnsListByBlocks =& $this->getColumnsListbyBlock($module, array_keys($this->module_list[$module]), true);
        foreach ($this->module_list[$module] as $key => $value) {
            $ret_module_list[$module][$value] = $this->getBlockFieldList($module, $key, $ret_module_list[$module][$value], $allColumnsListByBlocks);
        }
        return $ret_module_list[$module];
    }
    /** Function to get vtiger_fields for the given module and block
     *  This function gets the vtiger_fields for the given module
     *  It accepts the module and the block as arguments and
     *  returns the array column lists
     *  Array module_columnlist[ vtiger_fieldtablename:fieldcolname:module_fieldlabel1:fieldname:fieldtypeofdata]=fieldlabel
     */
    public function getColumnsListbyBlock($module, $block, $group_res_by_block = false)
    {
        global $adb;
        global $log;
        global $current_user;
        if (is_string($block)) {
            $block = explode(",", $block);
        }
        $skipTalbes = array("vtiger_attachments");
        $tabid = getTabid($module);
        if ($module == "Calendar") {
            $tabid = array("9", "16");
        }
        $params = array($tabid, $block);
        require "user_privileges/user_privileges_" . $current_user->id . ".php";
        if ($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0) {
            $sql = "select * from vtiger_field where vtiger_field.tabid in (" . generateQuestionMarks($tabid) . ") and vtiger_field.block in (" . generateQuestionMarks($block) . ") and vtiger_field.displaytype in (1,2,3,5) and vtiger_field.presence in (0,2) AND tablename NOT IN (" . generateQuestionMarks($skipTalbes) . ") ";
            if ($module == "Calendar") {
                $sql .= " group by vtiger_field.fieldlabel order by sequence";
            } else {
                $sql .= " order by sequence";
            }
        } else {
            $profileList = getCurrentUserProfileList();
            $sql = "select * from vtiger_field inner join vtiger_profile2field on vtiger_profile2field.fieldid=vtiger_field.fieldid inner join vtiger_def_org_field on vtiger_def_org_field.fieldid=vtiger_field.fieldid where vtiger_field.tabid in (" . generateQuestionMarks($tabid) . ")  and vtiger_field.block in (" . generateQuestionMarks($block) . ") and vtiger_field.displaytype in (1,2,3,5) and vtiger_profile2field.visible=0 and vtiger_def_org_field.visible=0 and vtiger_field.presence in (0,2)";
            if (0 < count($profileList)) {
                $sql .= " and vtiger_profile2field.profileid in (" . generateQuestionMarks($profileList) . ")";
                array_push($params, $profileList);
            }
            $sql .= " and tablename NOT IN (" . generateQuestionMarks($skipTalbes) . ") ";
            if ($module == "Calendar") {
                $sql .= " group by vtiger_field.fieldlabel order by sequence";
            } else {
                $sql .= " group by vtiger_field.fieldid order by sequence";
            }
        }
        array_push($params, $skipTalbes);
        $result = $adb->pquery($sql, $params);
        $noofrows = $adb->num_rows($result);
        for ($i = 0; $i < $noofrows; $i++) {
            $fieldtablename = $adb->query_result($result, $i, "tablename");
            $fieldcolname = $adb->query_result($result, $i, "columnname");
            $fieldname = $adb->query_result($result, $i, "fieldname");
            $fieldtype = $adb->query_result($result, $i, "typeofdata");
            $uitype = $adb->query_result($result, $i, "uitype");
            $fieldtype = explode("~", $fieldtype);
            $fieldtypeofdata = $fieldtype[0];
            $blockid = $adb->query_result($result, $i, "block");
            if ($module == "HelpDesk" && $fieldname == "filename" || $fieldtablename == "vtiger_inventoryproductrel" && $fieldname == "image") {
                continue;
            }
            $fieldtypeofdata = ChangeTypeOfData_Filter($fieldtablename, $fieldcolname, $fieldtypeofdata);
            if ($uitype == 68 || $uitype == 59 || $uitype == 10) {
                $fieldtypeofdata = "V";
            }
            if ($fieldtablename == "vtiger_crmentity") {
                $fieldtablename = $fieldtablename . $module;
            }
            if ($fieldname == "assigned_user_id") {
                $fieldtablename = "vtiger_users" . $module;
                $fieldcolname = "user_name";
            }
            if ($fieldname == "assigned_user_id1") {
                $fieldtablename = "vtiger_usersRel1";
                $fieldcolname = "user_name";
            }
            $fieldlabel = $adb->query_result($result, $i, "fieldlabel");
            if ($module == "Emails" && $fieldlabel == "Date & Time Sent") {
                $fieldlabel = "Date Sent";
                $fieldtypeofdata = "D";
            }
            $fieldlabel1 = str_replace(" ", "_", $fieldlabel);
            $optionvalue = $fieldtablename . ":" . $fieldcolname . ":" . $module . "_" . $fieldlabel1 . ":" . $fieldname . ":" . $fieldtypeofdata;
            $adv_rel_field_tod_value = "\$" . $module . "#" . $fieldname . "\$" . "::" . getTranslatedString($module, $module) . " " . getTranslatedString($fieldlabel, $module);
            if (!is_array($this->adv_rel_fields[$fieldtypeofdata]) || !in_array($adv_rel_field_tod_value, $this->adv_rel_fields[$fieldtypeofdata])) {
                $this->adv_rel_fields[$fieldtypeofdata][] = $adv_rel_field_tod_value;
            }
            if (is_string($block) || $group_res_by_block == false) {
                $module_columnlist[$optionvalue] = $fieldlabel;
            } else {
                $module_columnlist[$blockid][$optionvalue] = $fieldlabel;
            }
        }
        $primaryModule = $this->primodule;
        if ($primaryModule == "PriceBooks") {
            if ($module == "Products") {
                $module_columnlist[$blockid]["vtiger_pricebookproductrel:listprice:Products_List_Price:listprice:V"] = "List Price";
            }
            if ($module == "Services") {
                $module_columnlist[$blockid]["vtiger_pricebookproductrel:listprice:Services_List_Price:listprice:V"] = "List Price";
            }
        }
        return $module_columnlist;
    }
    public function fixGetColumnsListbyBlockForInventory($module, $blockid, &$module_columnlist)
    {
        global $log;
        $blockname = getBlockName($blockid);
        if ($blockname == "LBL_RELATED_PRODUCTS" && ($module == "PurchaseOrder" || $module == "SalesOrder" || $module == "Quotes" || $module == "Invoice")) {
            $fieldtablename = "vtiger_inventoryproductrel";
            $fields = array("productid" => getTranslatedString("Product Name", $module), "serviceid" => getTranslatedString("Service Name", $module), "listprice" => getTranslatedString("List Price", $module), "discount_amount" => getTranslatedString("Discount", $module), "quantity" => getTranslatedString("Quantity", $module), "comment" => getTranslatedString("Comments", $module));
            $fields_datatype = array("productid" => "V", "serviceid" => "V", "listprice" => "I", "discount_amount" => "I", "quantity" => "I", "comment" => "V");
            foreach ($fields as $fieldcolname => $label) {
                $column_name = str_replace(" ", "_", $label);
                $fieldtypeofdata = $fields_datatype[$fieldcolname];
                $optionvalue = $fieldtablename . ":" . $fieldcolname . ":" . $module . "_" . $column_name . ":" . $fieldcolname . ":" . $fieldtypeofdata;
                $module_columnlist[$optionvalue] = $label;
            }
        }
        $log->info("Reports :: FieldColumns->Successfully returned ColumnslistbyBlock" . $module . $block);
        return $module_columnlist;
    }
    /** Function to set the standard filter vtiger_fields for the given vtiger_vreport
     *  This function gets the standard filter vtiger_fields for the given vtiger_vreport
     *  and set the values to the corresponding variables
     *  It accepts the repordid as argument
     */
    public function getSelectedStandardCriteria($reportid)
    {
        global $adb;
        $sSQL = "select vtiger_vreportdatefilter.* from vtiger_vreportdatefilter inner join vtiger_vreport on vtiger_vreport.reportid = vtiger_vreportdatefilter.datefilterid where vtiger_vreport.reportid=?";
        $result = $adb->pquery($sSQL, array($reportid));
        $selectedstdfilter = $adb->fetch_array($result);
        $this->stdselectedcolumn = $selectedstdfilter["datecolumnname"];
        $this->stdselectedfilter = $selectedstdfilter["datefilter"];
        if ($selectedstdfilter["datefilter"] == "custom") {
            if ($selectedstdfilter["startdate"] != "0000-00-00") {
                $startDateTime = new DateTimeField($selectedstdfilter["startdate"] . " " . date("H:i:s"));
                $this->startdate = $startDateTime->getDisplayDate();
            }
            if ($selectedstdfilter["enddate"] != "0000-00-00") {
                $endDateTime = new DateTimeField($selectedstdfilter["enddate"] . " " . date("H:i:s"));
                $this->enddate = $endDateTime->getDisplayDate();
            }
        }
    }
    /** Function to get the combo values for the standard filter
     *  This function get the combo values for the standard filter for the given vtiger_vreport
     *  and return a HTML string
     */
    public function getSelectedStdFilterCriteria($selecteddatefilter = "")
    {
        global $mod_strings;
        $datefiltervalue = array("custom", "prevfy", "thisfy", "nextfy", "prevfq", "thisfq", "nextfq", "yesterday", "today", "tomorrow", "lastweek", "thisweek", "nextweek", "lastmonth", "thismonth", "nextmonth", "last7days", "last14days", "last30days", "last60days", "last90days", "last120days", "next30days", "next60days", "next90days", "next120days");
        $datefilterdisplay = array("Custom", "Previous FY", "Current FY", "Next FY", "Previous FQ", "Current FQ", "Next FQ", "Yesterday", "Today", "Tomorrow", "Last Week", "Current Week", "Next Week", "Last Month", "Current Month", "Next Month", "Last 7 Days", "Last 30 Days", "Last 60 Days", "Last 90 Days", "Last 120 Days", "Next 7 Days", "Next 30 Days", "Next 60 Days", "Next 90 Days", "Next 120 Days");
        for ($i = 0; $i < count($datefiltervalue); $i++) {
            if ($selecteddatefilter == $datefiltervalue[$i]) {
                $sshtml .= "<option selected value='" . $datefiltervalue[$i] . "'>" . $mod_strings[$datefilterdisplay[$i]] . "</option>";
            } else {
                $sshtml .= "<option value='" . $datefiltervalue[$i] . "'>" . $mod_strings[$datefilterdisplay[$i]] . "</option>";
            }
        }
        return $sshtml;
    }
    /** Function to get the selected standard filter columns
     *  This function returns the selected standard filter criteria
     *  which is selected for vtiger_vreports as an array
     *  Array stdcriteria_list[fieldtablename:fieldcolname:module_fieldlabel1]=fieldlabel
     */
    public function getStdCriteriaByModule($module)
    {
        global $adb;
        global $log;
        global $current_user;
        require "user_privileges/user_privileges_" . $current_user->id . ".php";
        $tabid = getTabid($module);
        foreach ($this->module_list[$module] as $key => $blockid) {
            $blockids[] = $blockid;
        }
        $blockids = implode(",", $blockids);
        $params = array($tabid, $blockids);
        if ($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0) {
            $sql = "select * from vtiger_field where vtiger_field.tabid=? and (vtiger_field.uitype =5 or vtiger_field.uitype = 6 or vtiger_field.uitype = 23 or vtiger_field.displaytype=2) and vtiger_field.block in (" . generateQuestionMarks($block) . ") and vtiger_field.presence in (0,2) order by vtiger_field.sequence";
        } else {
            $profileList = getCurrentUserProfileList();
            $sql = "select * from vtiger_field inner join vtiger_tab on vtiger_tab.tabid = vtiger_field.tabid inner join vtiger_profile2field on vtiger_profile2field.fieldid=vtiger_field.fieldid inner join vtiger_def_org_field on vtiger_def_org_field.fieldid=vtiger_field.fieldid  where vtiger_field.tabid=? and (vtiger_field.uitype =5 or vtiger_field.displaytype=2) and vtiger_profile2field.visible=0 and vtiger_def_org_field.visible=0 and vtiger_field.block in (" . generateQuestionMarks($block) . ") and vtiger_field.presence in (0,2)";
            if (0 < count($profileList)) {
                $sql .= " and vtiger_profile2field.profileid in (" . generateQuestionMarks($profileList) . ")";
                array_push($params, $profileList);
            }
            $sql .= " order by vtiger_field.sequence";
        }
        $result = $adb->pquery($sql, $params);
        while ($criteriatyperow = $adb->fetch_array($result)) {
            $fieldtablename = $criteriatyperow["tablename"];
            $fieldcolname = $criteriatyperow["columnname"];
            $fieldlabel = $criteriatyperow["fieldlabel"];
            if ($fieldtablename == "vtiger_crmentity") {
                $fieldtablename = $fieldtablename . $module;
            }
            $fieldlabel1 = str_replace(" ", "_", $fieldlabel);
            $optionvalue = $fieldtablename . ":" . $fieldcolname . ":" . $module . "_" . $fieldlabel1;
            $stdcriteria_list[$optionvalue] = $fieldlabel;
        }
        $log->info("Reports :: StdfilterColumns->Successfully returned Stdfilter for" . $module);
        return $stdcriteria_list;
    }
    /** Function to form a javascript to determine the start date and end date for a standard filter
     *  This function is to form a javascript to determine
     *  the start date and End date from the value selected in the combo lists
     */
    public function getCriteriaJS()
    {
        $todayDateTime = new DateTimeField(date("Y-m-d H:i:s"));
        $tomorrow = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")));
        $tomorrowDateTime = new DateTimeField($tomorrow . " " . date("H:i:s"));
        $yesterday = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y")));
        $yesterdayDateTime = new DateTimeField($yesterday . " " . date("H:i:s"));
        $currentmonth0 = date("Y-m-d", mktime(0, 0, 0, date("m"), "01", date("Y")));
        $currentMonthStartDateTime = new DateTimeField($currentmonth0 . " " . date("H:i:s"));
        $currentmonth1 = date("Y-m-t");
        $currentMonthEndDateTime = new DateTimeField($currentmonth1 . " " . date("H:i:s"));
        $lastmonth0 = date("Y-m-d", mktime(0, 0, 0, date("m") - 1, "01", date("Y")));
        $lastMonthStartDateTime = new DateTimeField($lastmonth0 . " " . date("H:i:s"));
        $lastmonth1 = date("Y-m-t", strtotime("-1 Month"));
        $lastMonthEndDateTime = new DateTimeField($lastmonth1 . " " . date("H:i:s"));
        $nextmonth0 = date("Y-m-d", mktime(0, 0, 0, date("m") + 1, "01", date("Y")));
        $nextMonthStartDateTime = new DateTimeField($nextmonth0 . " " . date("H:i:s"));
        $nextmonth1 = date("Y-m-t", strtotime("+1 Month"));
        $nextMonthEndDateTime = new DateTimeField($nextmonth1 . " " . date("H:i:s"));
        $lastweek0 = date("Y-m-d", strtotime("-2 week Monday"));
        $lastWeekStartDateTime = new DateTimeField($lastweek0 . " " . date("H:i:s"));
        $lastweek1 = date("Y-m-d", strtotime("-1 week Sunday"));
        $lastWeekEndDateTime = new DateTimeField($lastweek1 . " " . date("H:i:s"));
        $thisweek0 = date("Y-m-d", strtotime("-1 week Monday"));
        $thisWeekStartDateTime = new DateTimeField($thisweek0 . " " . date("H:i:s"));
        $thisweek1 = date("Y-m-d", strtotime("this Sunday"));
        $thisWeekEndDateTime = new DateTimeField($thisweek1 . " " . date("H:i:s"));
        $nextweek0 = date("Y-m-d", strtotime("this Monday"));
        $nextWeekStartDateTime = new DateTimeField($nextweek0 . " " . date("H:i:s"));
        $nextweek1 = date("Y-m-d", strtotime("+1 week Sunday"));
        $nextWeekEndDateTime = new DateTimeField($nextweek1 . " " . date("H:i:s"));
        $next7days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 6, date("Y")));
        $next7DaysDateTime = new DateTimeField($next7days . " " . date("H:i:s"));
        $next30days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 29, date("Y")));
        $next30DaysDateTime = new DateTimeField($next30days . " " . date("H:i:s"));
        $next60days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 59, date("Y")));
        $next60DaysDateTime = new DateTimeField($next60days . " " . date("H:i:s"));
        $next90days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 89, date("Y")));
        $next90DaysDateTime = new DateTimeField($next90days . " " . date("H:i:s"));
        $next120days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 119, date("Y")));
        $next120DaysDateTime = new DateTimeField($next120days . " " . date("H:i:s"));
        $last7days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 6, date("Y")));
        $last7DaysDateTime = new DateTimeField($last7days . " " . date("H:i:s"));
        $last14days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 13, date("Y")));
        $last14DaysDateTime = new DateTimeField($last14days . " " . date("H:i:s"));
        $last30days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 29, date("Y")));
        $last30DaysDateTime = new DateTimeField($last30days . " " . date("H:i:s"));
        $last60days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 59, date("Y")));
        $last60DaysDateTime = new DateTimeField($last60days . " " . date("H:i:s"));
        $last90days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 89, date("Y")));
        $last90DaysDateTime = new DateTimeField($last90days . " " . date("H:i:s"));
        $last120days = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 119, date("Y")));
        $last120DaysDateTime = new DateTimeField($last120days . " " . date("H:i:s"));
        $currentFY0 = date("Y-m-d", mktime(0, 0, 0, "01", "01", date("Y")));
        $currentFYStartDateTime = new DateTimeField($currentFY0 . " " . date("H:i:s"));
        $currentFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y")));
        $currentFYEndDateTime = new DateTimeField($currentFY1 . " " . date("H:i:s"));
        $lastFY0 = date("Y-m-d", mktime(0, 0, 0, "01", "01", date("Y") - 1));
        $lastFYStartDateTime = new DateTimeField($lastFY0 . " " . date("H:i:s"));
        $lastFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y") - 1));
        $lastFYEndDateTime = new DateTimeField($lastFY1 . " " . date("H:i:s"));
        $nextFY0 = date("Y-m-d", mktime(0, 0, 0, "01", "01", date("Y") + 1));
        $nextFYStartDateTime = new DateTimeField($nextFY0 . " " . date("H:i:s"));
        $nextFY1 = date("Y-m-t", mktime(0, 0, 0, "12", date("d"), date("Y") + 1));
        $nextFYEndDateTime = new DateTimeField($nextFY1 . " " . date("H:i:s"));
        if (date("m") <= 3) {
            $cFq = date("Y-m-d", mktime(0, 0, 0, "01", "01", date("Y")));
            $cFqStartDateTime = new DateTimeField($cFq . " " . date("H:i:s"));
            $cFq1 = date("Y-m-d", mktime(0, 0, 0, "03", "31", date("Y")));
            $cFqEndDateTime = new DateTimeField($cFq1 . " " . date("H:i:s"));
            $nFq = date("Y-m-d", mktime(0, 0, 0, "04", "01", date("Y")));
            $nFqStartDateTime = new DateTimeField($nFq . " " . date("H:i:s"));
            $nFq1 = date("Y-m-d", mktime(0, 0, 0, "06", "30", date("Y")));
            $nFqEndDateTime = new DateTimeField($nFq1 . " " . date("H:i:s"));
            $pFq = date("Y-m-d", mktime(0, 0, 0, "10", "01", date("Y") - 1));
            $pFqStartDateTime = new DateTimeField($pFq . " " . date("H:i:s"));
            $pFq1 = date("Y-m-d", mktime(0, 0, 0, "12", "31", date("Y") - 1));
            $pFqEndDateTime = new DateTimeField($pFq1 . " " . date("H:i:s"));
        } else {
            if (3 < date("m") && date("m") <= 6) {
                $pFq = date("Y-m-d", mktime(0, 0, 0, "01", "01", date("Y")));
                $pFqStartDateTime = new DateTimeField($pFq . " " . date("H:i:s"));
                $pFq1 = date("Y-m-d", mktime(0, 0, 0, "03", "31", date("Y")));
                $pFqEndDateTime = new DateTimeField($pFq1 . " " . date("H:i:s"));
                $cFq = date("Y-m-d", mktime(0, 0, 0, "04", "01", date("Y")));
                $cFqStartDateTime = new DateTimeField($cFq . " " . date("H:i:s"));
                $cFq1 = date("Y-m-d", mktime(0, 0, 0, "06", "30", date("Y")));
                $cFqEndDateTime = new DateTimeField($cFq1 . " " . date("H:i:s"));
                $nFq = date("Y-m-d", mktime(0, 0, 0, "07", "01", date("Y")));
                $nFqStartDateTime = new DateTimeField($nFq . " " . date("H:i:s"));
                $nFq1 = date("Y-m-d", mktime(0, 0, 0, "09", "30", date("Y")));
                $nFqEndDateTime = new DateTimeField($nFq1 . " " . date("H:i:s"));
            } else {
                if (6 < date("m") && date("m") <= 9) {
                    $nFq = date("Y-m-d", mktime(0, 0, 0, "10", "01", date("Y")));
                    $nFqStartDateTime = new DateTimeField($nFq . " " . date("H:i:s"));
                    $nFq1 = date("Y-m-d", mktime(0, 0, 0, "12", "31", date("Y")));
                    $nFqEndDateTime = new DateTimeField($nFq1 . " " . date("H:i:s"));
                    $pFq = date("Y-m-d", mktime(0, 0, 0, "04", "01", date("Y")));
                    $pFqStartDateTime = new DateTimeField($pFq . " " . date("H:i:s"));
                    $pFq1 = date("Y-m-d", mktime(0, 0, 0, "06", "30", date("Y")));
                    $pFqEndDateTime = new DateTimeField($pFq1 . " " . date("H:i:s"));
                    $cFq = date("Y-m-d", mktime(0, 0, 0, "07", "01", date("Y")));
                    $cFqStartDateTime = new DateTimeField($cFq . " " . date("H:i:s"));
                    $cFq1 = date("Y-m-d", mktime(0, 0, 0, "09", "30", date("Y")));
                    $cFqEndDateTime = new DateTimeField($cFq1 . " " . date("H:i:s"));
                } else {
                    if (9 < date("m") && date("m") <= 12) {
                        $nFq = date("Y-m-d", mktime(0, 0, 0, "01", "01", date("Y") + 1));
                        $nFqStartDateTime = new DateTimeField($nFq . " " . date("H:i:s"));
                        $nFq1 = date("Y-m-d", mktime(0, 0, 0, "03", "31", date("Y") + 1));
                        $nFqEndDateTime = new DateTimeField($nFq1 . " " . date("H:i:s"));
                        $pFq = date("Y-m-d", mktime(0, 0, 0, "07", "01", date("Y")));
                        $pFqStartDateTime = new DateTimeField($pFq . " " . date("H:i:s"));
                        $pFq1 = date("Y-m-d", mktime(0, 0, 0, "09", "30", date("Y")));
                        $pFqEndDateTime = new DateTimeField($pFq1 . " " . date("H:i:s"));
                        $cFq = date("Y-m-d", mktime(0, 0, 0, "10", "01", date("Y")));
                        $cFqStartDateTime = new DateTimeField($cFq . " " . date("H:i:s"));
                        $cFq1 = date("Y-m-d", mktime(0, 0, 0, "12", "31", date("Y")));
                        $cFqEndDateTime = new DateTimeField($cFq1 . " " . date("H:i:s"));
                    }
                }
            }
        }
        $sjsStr = "<script language=\"JavaScript\" type=\"text/javaScript\">\n\t\t\tfunction showDateRange( type ) {\n\t\t\t\tif (type!=\"custom\") {\n\t\t\t\t\tdocument.NewReport.startdate.readOnly=true;\n\t\t\t\t\tdocument.NewReport.enddate.readOnly=true;\n\t\t\t\t\tgetObj(\"jscal_trigger_date_start\").style.visibility=\"hidden\";\n\t\t\t\t\tgetObj(\"jscal_trigger_date_end\").style.visibility=\"hidden\"\n\t\t\t\t} else {\n\t\t\t\t\tdocument.NewReport.startdate.readOnly=false;\n\t\t\t\t\tdocument.NewReport.enddate.readOnly=false;\n\t\t\t\t\tgetObj(\"jscal_trigger_date_start\").style.visibility=\"visible\";\n\t\t\t\t\tgetObj(\"jscal_trigger_date_end\").style.visibility=\"visible\"\n\t\t\t\t}\n\t\t\t\tif( type == \"today\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"yesterday\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $yesterdayDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $yesterdayDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"tomorrow\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $tomorrowDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $tomorrowDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"thisweek\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $thisWeekStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $thisWeekEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"lastweek\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $lastWeekStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $lastWeekEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"nextweek\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $nextWeekStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $nextWeekEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"thismonth\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $currentMonthStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $currentMonthEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"lastmonth\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $lastMonthStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $lastMonthEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"nextmonth\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $nextMonthStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $nextMonthEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"next7days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $next7DaysDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"next30days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $next30DaysDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"next60days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $next60DaysDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"next90days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $next90DaysDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"next120days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $next120DaysDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"last7days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $last7DaysDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value =  \"" . $todayDateTime->getDisplayDate() . "\";\n                                            \n                } else if( type == \"last14days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $last14DaysDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value =  \"" . $todayDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"last30days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $last30DaysDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"last60days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $last60DaysDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"last90days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $last90DaysDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"last120days\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $last120DaysDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $todayDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"thisfy\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $currentFYStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $currentFYEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"prevfy\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $lastFYStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $lastFYEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"nextfy\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $nextFYStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $nextFYEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"nextfq\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $nFqStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $nFqEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"prevfq\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $pFqStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $pFqEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else if( type == \"thisfq\" ) {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"" . $cFqStartDateTime->getDisplayDate() . "\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"" . $cFqEndDateTime->getDisplayDate() . "\";\n\n\t\t\t\t} else {\n\t\t\t\t\tdocument.NewReport.startdate.value = \"\";\n\t\t\t\t\tdocument.NewReport.enddate.value = \"\";\n\t\t\t\t}\n\t\t\t}\n\t\t</script>";
        return $sjsStr;
    }
    public function getEscapedColumns($selectedfields)
    {
        $fieldname = $selectedfields[3];
        if ($fieldname == "parent_id") {
            if ($this->primarymodule == "HelpDesk" && $selectedfields[0] == "vtiger_crmentityRelHelpDesk") {
                $querycolumn = "case vtiger_crmentityRelHelpDesk.setype when 'Accounts' then vtiger_accountRelHelpDesk.accountname when 'Contacts' then vtiger_contactdetailsRelHelpDesk.lastname End" . " '" . $selectedfields[2] . "', vtiger_crmentityRelHelpDesk.setype 'Entity_type'";
                return $querycolumn;
            }
            if ($this->primarymodule == "Products" || $this->secondarymodule == "Products") {
                $querycolumn = "case vtiger_crmentityRelProducts.setype when 'Accounts' then vtiger_accountRelProducts.accountname when 'Leads' then vtiger_leaddetailsRelProducts.lastname when 'Potentials' then vtiger_potentialRelProducts.potentialname End" . " '" . $selectedfields[2] . "', vtiger_crmentityRelProducts.setype 'Entity_type'";
            }
            if ($this->primarymodule == "Calendar" || $this->secondarymodule == "Calendar") {
                $querycolumn = "case vtiger_crmentityRelCalendar.setype when 'Accounts' then vtiger_accountRelCalendar.accountname when 'Leads' then vtiger_leaddetailsRelCalendar.lastname when 'Potentials' then vtiger_potentialRelCalendar.potentialname when 'Quotes' then vtiger_quotesRelCalendar.subject when 'PurchaseOrder' then vtiger_purchaseorderRelCalendar.subject when 'Invoice' then vtiger_invoiceRelCalendar.subject End" . " '" . $selectedfields[2] . "', vtiger_crmentityRelCalendar.setype 'Entity_type'";
            }
        }
        return $querycolumn;
    }
    public function getaccesfield($module)
    {
        global $current_user;
        global $adb;
        $access_fields = array();
        $profileList = getCurrentUserProfileList();
        $query = "select vtiger_field.fieldname from vtiger_field inner join vtiger_profile2field on vtiger_profile2field.fieldid=vtiger_field.fieldid inner join vtiger_def_org_field on vtiger_def_org_field.fieldid=vtiger_field.fieldid where";
        $params = array();
        if ($module == "Calendar") {
            $query .= " vtiger_field.tabid in (9,16) and vtiger_field.displaytype in (1,2,3) and vtiger_profile2field.visible=0 and vtiger_def_org_field.visible=0 and vtiger_field.presence in (0,2)";
            if (0 < count($profileList)) {
                $query .= " and vtiger_profile2field.profileid in (" . generateQuestionMarks($profileList) . ")";
                array_push($params, $profileList);
            }
            $query .= " group by vtiger_field.fieldid order by block,sequence";
        } else {
            array_push($params, $this->primodule, $this->secmodule);
            $query .= " vtiger_field.tabid in (select tabid from vtiger_tab where vtiger_tab.name in (?,?)) and vtiger_field.displaytype in (1,2,3) and vtiger_profile2field.visible=0 and vtiger_def_org_field.visible=0 and vtiger_field.presence in (0,2)";
            if (0 < count($profileList)) {
                $query .= " and vtiger_profile2field.profileid in (" . generateQuestionMarks($profileList) . ")";
                array_push($params, $profileList);
            }
            $query .= " group by vtiger_field.fieldid order by block,sequence";
        }
        $result = $adb->pquery($query, $params);
        while ($collistrow = $adb->fetch_array($result)) {
            $access_fields[] = $collistrow["fieldname"];
        }
        return $access_fields;
    }
    /** Function to set the order of grouping and to find the columns responsible
     *  to the grouping
     *  This function accepts the vtiger_vreportid as variable,sets the variable ascdescorder[] to the sort order and
     *  returns the array array_list which has the column responsible for the grouping
     *  Array array_list[0]=columnname
     */
    public function getSelctedSortingColumns($reportid)
    {
        global $adb;
        global $log;
        $sreportsortsql = "select vtiger_vreportsortcol.* from vtiger_vreport";
        $sreportsortsql .= " inner join vtiger_vreportsortcol on vtiger_vreport.reportid = vtiger_vreportsortcol.reportid";
        $sreportsortsql .= " where vtiger_vreport.reportid =? order by vtiger_vreportsortcol.sortcolid";
        $result = $adb->pquery($sreportsortsql, array($reportid));
        $noofrows = $adb->num_rows($result);
        for ($i = 0; $i < $noofrows; $i++) {
            $fieldcolname = $adb->query_result($result, $i, "columnname");
            $sort_values = $adb->query_result($result, $i, "sortorder");
            $this->ascdescorder[] = $sort_values;
            $array_list[] = $fieldcolname;
        }
        $log->info("Reports :: Successfully returned getSelctedSortingColumns");
        return $array_list;
    }
    /** Function to get the selected columns list for a selected vtiger_vreport
     *  This function accepts the vtiger_vreportid as the argument and get the selected columns
     *  for the given vtiger_vreportid and it forms a combo lists and returns
     *  HTML of the combo values
     */
    public function getSelectedColumnsList($reportid)
    {
        global $adb;
        global $modules;
        global $log;
        global $current_user;
        $ssql = "select vtiger_selectcolumn.* from vtiger_vreport inner join vtiger_selectquery on vtiger_selectquery.queryid = vtiger_vreport.queryid";
        $ssql .= " left join vtiger_selectcolumn on vtiger_selectcolumn.queryid = vtiger_selectquery.queryid";
        $ssql .= " where vtiger_vreport.reportid = ?";
        $ssql .= " order by vtiger_selectcolumn.columnindex";
        $result = $adb->pquery($ssql, array($reportid));
        $permitted_fields = array();
        $selected_mod = explode(":", $this->secmodule);
        array_push($selected_mod, $this->primodule);
        $inventoryModules = getInventoryModules();
        while ($columnslistrow = $adb->fetch_array($result)) {
            $fieldname = "";
            $fieldcolname = $columnslistrow["columnname"];
            $selmod_field_disabled = true;
            foreach ($selected_mod as $smod) {
                if (-1 < stripos($fieldcolname, ":" . $smod . "_") && vtlib_isModuleActive($smod)) {
                    $selmod_field_disabled = false;
                    break;
                }
            }
            if ($selmod_field_disabled == false) {
                list($tablename, $colname, $module_field, $fieldname, $single) = explode(":", $fieldcolname);
                require "user_privileges/user_privileges_" . $current_user->id . ".php";
                list($module, $field) = explode("_", $module_field);
                if (sizeof($permitted_fields) == 0 && $is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1) {
                    $permitted_fields = $this->getaccesfield($module);
                }
                $querycolumns = $this->getEscapedColumns($selectedfields);
                $fieldlabel = trim(str_replace($module, " ", $module_field));
                $mod_arr = explode("_", $fieldlabel);
                $mod = $mod_arr[0] == "" ? $module : $mod_arr[0];
                $fieldlabel = trim(str_replace("_", " ", $fieldlabel));
                $mod_lbl = getTranslatedString($mod, $module);
                $fld_lbl = getTranslatedString($fieldlabel, $module);
                $fieldlabel = $mod_lbl . " " . $fld_lbl;
                if (in_array($mod, $inventoryModules) && $fieldname == "serviceid") {
                    $shtml .= "<option permission='yes' value=\"" . $fieldcolname . "\">" . $fieldlabel . "</option>";
                } else {
                    if (CheckFieldPermission($fieldname, $mod) != "true" && $colname != "crmid") {
                        $shtml .= "<option permission='no' value=\"" . $fieldcolname . "\" disabled = 'true'>" . $fieldlabel . "</option>";
                    } else {
                        $shtml .= "<option permission='yes' value=\"" . $fieldcolname . "\">" . $fieldlabel . "</option>";
                    }
                }
            }
        }
        $log->info("ReportRun :: Successfully returned getQueryColumnsList" . $reportid);
        return $shtml;
    }
    public function getAdvancedFilterList($reportid)
    {
        global $adb;
        global $modules;
        global $log;
        global $current_user;
        $advft_criteria = array();
        $sqlgroupparent = "SELECT * FROM vtiger_vreport_relcriteria_grouping_parent WHERE queryid = ? ORDER BY groupparentid";
        $groupparentresult = $adb->pquery($sqlgroupparent, array($reportid));
        for ($groupParentIndex = 1; $rowGroupParent = $adb->fetchByAssoc($groupparentresult); $groupParentIndex++) {
            $groupParentId = $rowGroupParent["groupparentid"];
            $groupParentCondition = $rowGroupParent["group_parent_condition"];
            $sql = "SELECT groupid,group_condition FROM vtiger_vreport_relcriteria_grouping WHERE queryid = ? AND groupparentid = ? ORDER BY groupid";
            $groupsresult = $adb->pquery($sql, array($reportid, $groupParentId));
            $i = 1;
            for ($j = 0; $relcriteriagroup = $adb->fetch_array($groupsresult); $i++) {
                $groupId = $relcriteriagroup["groupid"];
                $groupCondition = $relcriteriagroup["group_condition"];
                $ssql = "select vtiger_vreport_relcriteria.* from vtiger_vreport\n\t\t\t\t\t\t\tinner join vtiger_vreport_relcriteria on vtiger_vreport_relcriteria.queryid = vtiger_vreport.queryid\n\t\t\t\t\t\t\tleft join vtiger_vreport_relcriteria_grouping on vtiger_vreport_relcriteria.queryid = vtiger_vreport_relcriteria_grouping.queryid AND vtiger_vreport_relcriteria_grouping.groupparentid = " . $groupParentId . "\n\t\t\t\t\t\t\t\t\tand vtiger_vreport_relcriteria.groupid = vtiger_vreport_relcriteria_grouping.groupid";
                $ssql .= " where vtiger_vreport.reportid = ? AND vtiger_vreport_relcriteria.groupid = ? AND vtiger_vreport_relcriteria.groupparentid = " . $groupParentId . " order by vtiger_vreport_relcriteria.columnindex";
                $result = $adb->pquery($ssql, array($reportid, $groupId));
                $noOfColumns = $adb->num_rows($result);
                if ($noOfColumns <= 0) {
                    continue;
                }
                while ($relcriteriarow = $adb->fetch_array($result)) {
                    $columnIndex = $relcriteriarow["columnindex"];
                    $criteria = array();
                    $criteria["columnname"] = $relcriteriarow["columnname"];
                    $criteria["comparator"] = $relcriteriarow["comparator"];
                    $advfilterval = $relcriteriarow["value"];
                    $col = explode(":", $relcriteriarow["columnname"]);
                    list($moduleFieldLabel, $fieldName) = $col;
                    list($module, $fieldLabel) = explode("_", $moduleFieldLabel, 2);
                    $fieldInfo = getFieldByVReportLabel($module, $fieldLabel);
                    $fieldType = NULL;
                    if (!empty($fieldInfo)) {
                        $field = WebserviceField::fromArray($adb, $fieldInfo);
                        $fieldType = $field->getFieldDataType();
                    }
                    if ($fieldType == "currency") {
                        if ($field->getUIType() == "71") {
                            $advfilterval = CurrencyField::convertToUserFormat($advfilterval, $current_user);
                        } else {
                            if ($field->getUIType() == "72") {
                                $advfilterval = CurrencyField::convertToUserFormat($advfilterval, $current_user, true);
                            }
                        }
                    }
                    $specialDateConditions = Vtiger_Functions::getSpecialDateTimeCondtions();
                    $temp_val = explode(",", $relcriteriarow["value"]);
                    if (($col[4] == "D" || $col[4] == "T" && $col[1] != "time_start" && $col[1] != "time_end" || $col[4] == "DT") && !in_array($criteria["comparator"], $specialDateConditions)) {
                        $val = array();
                        for ($x = 0; $x < count($temp_val); $x++) {
                            if ($col[4] == "D") {
                                $date = new DateTimeField(trim($temp_val[$x]));
                                $val[$x] = $date->getDisplayDate();
                            } else {
                                if ($col[4] == "DT") {
                                    $date = new DateTimeField(trim($temp_val[$x]));
                                    $val[$x] = $date->getDisplayDateTimeValue();
                                } else {
                                    if ($fieldType == "time") {
                                        $val[$x] = Vtiger_Time_UIType::getTimeValueWithSeconds($temp_val[$x]);
                                    } else {
                                        $date = new DateTimeField(trim($temp_val[$x]));
                                        $val[$x] = $date->getDisplayTime();
                                    }
                                }
                            }
                        }
                        $advfilterval = implode(",", $val);
                    }
                    $criteria["value"] = Vtiger_Util_Helper::toSafeHTML(decode_html($advfilterval));
                    $criteria["column_condition"] = $relcriteriarow["column_condition"];
                    $advft_criteria[$groupParentIndex][$relcriteriarow["groupid"]]["columns"][$j] = $criteria;
                    $advft_criteria[$groupParentIndex][$relcriteriarow["groupid"]]["condition"] = $groupCondition;
                    $j++;
                }
            }
            if (!empty($advft_criteria[$groupParentIndex][$i - 1]["condition"])) {
                $advft_criteria[$groupParentIndex][$i - 1]["condition"] = "";
            }
            $advft_criteria[$groupParentIndex]["groupParentCondition"] = $groupParentCondition;
        }
        $this->advft_criteria = $advft_criteria;
        $log->info("Reports :: Successfully returned getAdvancedFilterList");
        return true;
    }
    /** Function to get the list of vtiger_vreport folders when Save and run  the vtiger_vreport
     *  This function gets the vtiger_vreport folders from database and form
     *  a combo values of the folders and return
     *  HTML of the combo values
     */
    public function sgetRptFldrSaveReport()
    {
        global $adb;
        global $log;
        $sql = "select * from vtiger_vreportfolder order by folderid";
        $result = $adb->pquery($sql, array());
        $reportfldrow = $adb->fetch_array($result);
        $x = 0;
        do {
            $shtml .= "<option value='" . $reportfldrow["folderid"] . "'>" . $reportfldrow["foldername"] . "</option>";
        } while ($reportfldrow = $adb->fetch_array($result));
        $log->info("Reports :: Successfully returned sgetRptFldrSaveReport");
        return $shtml;
    }
    /** Function to get the column to total vtiger_fields in Reports
     *  This function gets columns to total vtiger_field
     *  and generated the html for that vtiger_fields
     *  It returns the HTML of the vtiger_fields along with the check boxes
     */
    public function sgetColumntoTotal($primarymodule, $secondarymodule)
    {
        $options = array();
        $options[] = $this->sgetColumnstoTotalHTML($primarymodule, 0);
        if (!empty($secondarymodule)) {
            for ($i = 0; $i < count($secondarymodule); $i++) {
                $options[] = $this->sgetColumnstoTotalHTML($secondarymodule[$i], $i + 1);
            }
        }
        return $options;
    }
    /** Function to get the selected columns of total vtiger_fields in Reports
     *  This function gets selected columns of total vtiger_field
     *  and generated the html for that vtiger_fields
     *  It returns the HTML of the vtiger_fields along with the check boxes
     */
    public function sgetColumntoTotalSelected($primarymodule, $secondarymodule, $reportid)
    {
        global $adb;
        global $log;
        $options = array();
        if ($reportid != "") {
            $ssql = "select vtiger_vreportsummary.* from vtiger_vreportsummary inner join vtiger_vreport on vtiger_vreport.reportid = vtiger_vreportsummary.reportsummaryid where vtiger_vreport.reportid=?";
            $result = $adb->pquery($ssql, array($reportid));
            if ($result) {
                $reportsummaryrow = $adb->fetch_array($result);
                do {
                    $this->columnssummary[] = $reportsummaryrow["columnname"];
                } while ($reportsummaryrow = $adb->fetch_array($result));
            }
        }
        $options[] = $this->sgetColumnstoTotalHTML($primarymodule, 0);
        if ($secondarymodule != "") {
            $secondarymodule = explode(":", $secondarymodule);
            for ($i = 0; $i < count($secondarymodule); $i++) {
                $options[] = $this->sgetColumnstoTotalHTML($secondarymodule[$i], $i + 1);
            }
        }
        $log->info("Reports :: Successfully returned sgetColumntoTotalSelected");
        return $options;
    }
    /** Function to form the HTML for columns to total
     *  This function formulates the HTML format of the
     *  vtiger_fields along with four checkboxes
     *  It returns the HTML of the vtiger_fields along with the check boxes
     */
    public function sgetColumnstoTotalHTML($module)
    {
        global $adb;
        global $log;
        global $current_user;
        require "user_privileges/user_privileges_" . $current_user->id . ".php";
        $tabid = getTabid($module);
        $escapedchars = array("_SUM", "_AVG", "_MIN", "_MAX");
        $sparams = array($tabid);
        if ($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0) {
            $ssql = "select * from vtiger_field inner join vtiger_tab on vtiger_tab.tabid = vtiger_field.tabid where vtiger_field.uitype != 50 and vtiger_field.tabid=? and vtiger_field.displaytype in (1,2,3) and vtiger_field.presence in (0,2) ";
        } else {
            $profileList = getCurrentUserProfileList();
            $ssql = "select * from vtiger_field inner join vtiger_tab on vtiger_tab.tabid = vtiger_field.tabid inner join vtiger_def_org_field on vtiger_def_org_field.fieldid=vtiger_field.fieldid inner join vtiger_profile2field on vtiger_profile2field.fieldid=vtiger_field.fieldid  where vtiger_field.uitype != 50 and vtiger_field.tabid=? and vtiger_field.displaytype in (1,2,3) and vtiger_def_org_field.visible=0 and vtiger_profile2field.visible=0 and vtiger_field.presence in (0,2)";
            if (0 < count($profileList)) {
                $ssql .= " and vtiger_profile2field.profileid in (" . generateQuestionMarks($profileList) . ")";
                array_push($sparams, $profileList);
            }
        }
        switch ($tabid) {
            case 2:
                $ssql .= " and vtiger_field.fieldname not in ('campaignid')";
                break;
            case 4:
                $ssql .= " and vtiger_field.fieldname not in ('account_id')";
                break;
            case 6:
                $ssql .= " and vtiger_field.fieldname not in ('account_id')";
                break;
            case 9:
                $ssql .= " and vtiger_field.fieldname not in ('parent_id','contact_id')";
                break;
            case 13:
                $ssql .= " and vtiger_field.fieldname not in ('parent_id','product_id')";
                break;
            case 14:
                $ssql .= " and vtiger_field.fieldname not in ('vendor_id','product_id')";
                break;
            case 20:
                $ssql .= " and vtiger_field.fieldname not in ('potential_id','assigned_user_id1','account_id','currency_id')";
                break;
            case 21:
                $ssql .= " and vtiger_field.fieldname not in ('contact_id','vendor_id','currency_id')";
                break;
            case 22:
                $ssql .= " and vtiger_field.fieldname not in ('potential_id','account_id','contact_id','quote_id','currency_id')";
                break;
            case 23:
                $ssql .= " and vtiger_field.fieldname not in ('salesorder_id','contact_id','account_id','currency_id')";
                break;
            case 26:
                $ssql .= " and vtiger_field.fieldname not in ('product_id')";
                break;
        }
        $ssql .= " order by sequence";
        $result = $adb->pquery($ssql, $sparams);
        $columntototalrow = $adb->fetch_array($result);
        $options_list = array();
        do {
            $typeofdata = explode("~", $columntototalrow["typeofdata"]);
            if ($typeofdata[0] == "N" || $typeofdata[0] == "I" || $typeofdata[0] == "NN" && !empty($typeofdata[2])) {
                $options = array();
                if (isset($this->columnssummary)) {
                    $selectedcolumn = "";
                    $selectedcolumn1 = "";
                    for ($i = 0; $i < count($this->columnssummary); $i++) {
                        $selectedcolumnarray = explode(":", $this->columnssummary[$i]);
                        $selectedcolumn = $selectedcolumnarray[1] . ":" . $selectedcolumnarray[2] . ":" . str_replace($escapedchars, "", $selectedcolumnarray[3]);
                        if ($selectedcolumn != $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . str_replace(" ", "_", $columntototalrow["fieldlabel"])) {
                            $selectedcolumn = "";
                        } else {
                            $selectedcolumn1[$selectedcolumnarray[4]] = $this->columnssummary[$i];
                        }
                    }
                    if (isset($_REQUEST["record"]) && $_REQUEST["record"] != "") {
                        $options["label"][] = getTranslatedString($columntototalrow["tablabel"], $columntototalrow["tablabel"]) . " -" . getTranslatedString($columntototalrow["fieldlabel"], $columntototalrow["tablabel"]);
                    }
                    $columntototalrow["fieldlabel"] = str_replace(" ", "_", $columntototalrow["fieldlabel"]);
                    $options[] = getTranslatedString($columntototalrow["tablabel"], $columntototalrow["tablabel"]) . " - " . getTranslatedString($columntototalrow["fieldlabel"], $columntototalrow["tablabel"]);
                    if ($selectedcolumn1[2] == "cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_SUM:2") {
                        $options[] = "<input checked name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_SUM:2\" type=\"checkbox\" value=\"\">";
                    } else {
                        $options[] = "<input name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_SUM:2\" type=\"checkbox\" value=\"\">";
                    }
                    if ($selectedcolumn1[3] == "cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_AVG:3") {
                        $options[] = "<input checked name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_AVG:3\" type=\"checkbox\" value=\"\">";
                    } else {
                        $options[] = "<input name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_AVG:3\" type=\"checkbox\" value=\"\">";
                    }
                    if ($selectedcolumn1[4] == "cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_MIN:4") {
                        $options[] = "<input checked name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_MIN:4\" type=\"checkbox\" value=\"\">";
                    } else {
                        $options[] = "<input name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_MIN:4\" type=\"checkbox\" value=\"\">";
                    }
                    if ($selectedcolumn1[5] == "cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_MAX:5") {
                        $options[] = "<input checked name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_MAX:5\" type=\"checkbox\" value=\"\">";
                    } else {
                        $options[] = "<input name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_MAX:5\" type=\"checkbox\" value=\"\">";
                    }
                } else {
                    $options[] = getTranslatedString($columntototalrow["tablabel"], $columntototalrow["tablabel"]) . " - " . getTranslatedString($columntototalrow["fieldlabel"], $columntototalrow["tablabel"]);
                    $options[] = "<input name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_SUM:2\" type=\"checkbox\" value=\"\">";
                    $options[] = "<input name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_AVG:3\" type=\"checkbox\" value=\"\" >";
                    $options[] = "<input name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_MIN:4\"type=\"checkbox\" value=\"\" >";
                    $options[] = "<input name=\"cb:" . $columntototalrow["tablename"] . ":" . $columntototalrow["columnname"] . ":" . $columntototalrow["fieldlabel"] . "_MAX:5\" type=\"checkbox\" value=\"\" >";
                }
                $options_list[] = $options;
            }
        } while ($columntototalrow = $adb->fetch_array($result));
        $log->info("Reports :: Successfully returned sgetColumnstoTotalHTML");
        return $options_list;
    }
    /** Function to get the  advanced filter criteria for an option
     *  This function accepts The option in the advenced filter as an argument
     *  This generate filter criteria for the advanced filter
     *  It returns a HTML string of combo values
     */
    public static function getAdvCriteriaHTML($selected = "")
    {
        global $adv_filter_options;
        foreach ($adv_filter_options as $key => $value) {
            if ($selected == $key) {
                $shtml .= "<option selected value=\"" . $key . "\">" . $value . "</option>";
            } else {
                $shtml .= "<option value=\"" . $key . "\">" . $value . "</option>";
            }
        }
        return $shtml;
    }
    protected static function updateModule($moduleName)
    {
    }
    public function createFileForSQLReport()
    {
        global $root_directory;
        mkdir((string) $root_directory . "/test/vreports/", 511, true);
        $fileName = $root_directory . "/test/vreports/vreports_sql.conf";
        fopen($fileName, "wb");
        $fileConfigName = $root_directory . "/test/vreports/config.vreports.php";
        $configFile = fopen($fileConfigName, "wb");
        $txt = "<?php\n const loadVTEButtonOnList = true; \n ?>";
        fwrite($configFile, $txt);
        fclose($configFile);
    }
    public function checkPermissionReports($recordId)
    {
        $reportModel = VReports_Record_Model::getCleanInstance($recordId);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $owner = $reportModel->get("owner");
        $this->ownerReport = $owner;
        $sharingType = $reportModel->get("sharingtype");
        $isRecordShared = true;
        if ($currentUserPriviligesModel->id != $owner && $sharingType == "Private") {
            $isRecordShared = $reportModel->isRecordHasViewAccess($sharingType);
        }
        return $isRecordShared;
    }
}
/** Function to get the primary module list in vtiger_vreports
 *  This function generates the list of primary modules in vtiger_vreports
 *  and returns an array of permitted modules
 */
function getVReportsModuleList($focus)
{
    global $adb;
    global $app_list_strings;
    global $mod_strings;
    $modules = array();
    foreach ($focus->module_list as $key => $value) {
        if (isPermitted($key, "index") == "yes") {
            $count_flag = 1;
            $modules[$key] = getTranslatedString($key, $key);
        }
    }
    asort($modules);
    return $modules;
}
/** Function to get the Related module list in vtiger_vreports
 *  This function generates the list of secondary modules in vtiger_vreports
 *  and returns the related module as an Array
 */
function getVReportRelatedModules($module, $focus)
{
    global $app_list_strings;
    global $related_modules;
    global $mod_strings;
    $optionhtml = array();
    if (vtlib_isModuleActive($module) && !empty($focus->related_modules[$module])) {
        foreach ($focus->related_modules[$module] as $rel_modules) {
            if (isPermitted($rel_modules, "index") == "yes") {
                $optionhtml[] = $rel_modules;
            }
        }
    }
    return $optionhtml;
}
function updateAdvancedCriteriaVReport($reportid, $advft_criteria, $advft_criteria_groups)
{
    global $adb;
    global $log;
    $idelrelcriteriasql = "delete from vtiger_relcriteria where queryid=?";
    $idelrelcriteriasqlresult = $adb->pquery($idelrelcriteriasql, array($reportid));
    $idelrelcriteriagroupsql = "delete from vtiger_relcriteria_grouping where queryid=?";
    $idelrelcriteriagroupsqlresult = $adb->pquery($idelrelcriteriagroupsql, array($reportid));
    if (empty($advft_criteria)) {
        return NULL;
    }
    foreach ($advft_criteria as $column_index => $column_condition) {
        if (empty($column_condition)) {
            continue;
        }
        $adv_filter_column = $column_condition["columnname"];
        $adv_filter_comparator = $column_condition["comparator"];
        $adv_filter_value = $column_condition["value"];
        $adv_filter_column_condition = $column_condition["columncondition"];
        $adv_filter_groupid = $column_condition["groupid"];
        $column_info = explode(":", $adv_filter_column);
        list($moduleFieldLabel, $fieldName) = $column_info;
        list($module, $fieldLabel) = explode("_", $moduleFieldLabel, 2);
        $fieldInfo = getFieldByVReportLabel($module, $fieldLabel);
        $fieldType = NULL;
        if (!empty($fieldInfo)) {
            $field = WebserviceField::fromArray($adb, $fieldInfo);
            $fieldType = $field->getFieldDataType();
        }
        if ($fieldType == "currency") {
            if ($field->getUIType() == "72") {
                $adv_filter_value = CurrencyField::convertToDBFormat($adv_filter_value, NULL, true);
            } else {
                $adv_filter_value = CurrencyField::convertToDBFormat($adv_filter_value);
            }
        }
        $temp_val = explode(",", $adv_filter_value);
        if (($column_info[4] == "D" || $column_info[4] == "T" && $column_info[1] != "time_start" && $column_info[1] != "time_end" || $column_info[4] == "DT") && $column_info[4] != "" && $adv_filter_value != "") {
            $val = array();
            for ($x = 0; $x < count($temp_val); $x++) {
                if (trim($temp_val[$x]) != "") {
                    $date = new DateTimeField(trim($temp_val[$x]));
                    if ($column_info[4] == "D") {
                        $val[$x] = DateTimeField::convertToUserFormat(trim($temp_val[$x]));
                    } else {
                        if ($column_info[4] == "DT") {
                            $val[$x] = $date->getDBInsertDateTimeValue();
                        } else {
                            $val[$x] = $date->getDBInsertTimeValue();
                        }
                    }
                }
            }
            $adv_filter_value = implode(",", $val);
        }
        $irelcriteriasql = "insert into vtiger_relcriteria(QUERYID,COLUMNINDEX,COLUMNNAME,COMPARATOR,VALUE,GROUPID,COLUMN_CONDITION) values (?,?,?,?,?,?,?)";
        $irelcriteriaresult = $adb->pquery($irelcriteriasql, array($reportid, $column_index, $adv_filter_column, $adv_filter_comparator, $adv_filter_value, $adv_filter_groupid, $adv_filter_column_condition));
        $groupConditionExpression = "";
        if (!empty($advft_criteria_groups[$adv_filter_groupid]["conditionexpression"])) {
            $groupConditionExpression = $advft_criteria_groups[$adv_filter_groupid]["conditionexpression"];
        }
        $groupConditionExpression = $groupConditionExpression . " " . $column_index . " " . $adv_filter_column_condition;
        $advft_criteria_groups[$adv_filter_groupid]["conditionexpression"] = $groupConditionExpression;
    }
    foreach ($advft_criteria_groups as $group_index => $group_condition_info) {
        if (empty($group_condition_info)) {
            continue;
        }
        if (empty($group_condition_info["conditionexpression"])) {
            continue;
        }
        $irelcriteriagroupsql = "insert into vtiger_relcriteria_grouping(GROUPID,QUERYID,GROUP_CONDITION,CONDITION_EXPRESSION) values (?,?,?,?)";
        $irelcriteriagroupresult = $adb->pquery($irelcriteriagroupsql, array($group_index, $reportid, $group_condition_info["groupcondition"], $group_condition_info["conditionexpression"]));
    }
}

?>