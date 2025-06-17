<?php

/**
 * Vtiger Widget Model Class
 */
class VReports_Widget_Model extends Vtiger_Base_Model
{
    public function getWidth()
    {
        $width = $this->get("sizewidth");
        if (empty($width)) {
            $this->set("sizewidth", "4");
        }
        return $this->get("sizewidth");
    }
    public function getHeight()
    {
        $height = $this->get("sizeheight");
        if (empty($height)) {
            $this->set("sizeheight", "5");
        }
        return $this->get("sizeheight");
    }
    public function getPositionX()
    {
        $position = $this->get("position");
        if ($position) {
            $position = Zend_Json::decode(decode_html($position));
            $positionX = intval($position["x"]);
            if (0 < $positionX) {
                return $positionX;
            }
        }
        return 0;
    }
    public function getPositionY()
    {
        $position = $this->get("position");
        if ($position) {
            $position = Zend_Json::decode(decode_html($position));
            $positionY = intval($position["y"]);
            if (0 < $positionY) {
                return $positionY;
            }
        }
        return 0;
    }
    public function getPositionRow($default = 0)
    {
        $position = $this->get("position");
        if ($position) {
            $position = Zend_Json::decode(decode_html($position));
            return intval($position["row"]);
        }
        return $default;
    }
    public function getPositionCol($default = 0)
    {
        $position = $this->get("position");
        if ($position) {
            $position = Zend_Json::decode(decode_html($position));
            return intval($position["col"]);
        }
        return $default;
    }
    public function getSizeX()
    {
        $size = $this->get("size");
        if ($size) {
            $size = Zend_Json::decode(decode_html($size));
            $width = intval($size["sizex"]);
            $this->set("width", $width);
            return $width;
        }
        return $this->getWidth();
    }
    public function getSizeY()
    {
        $size = $this->get("size");
        if ($size) {
            $size = Zend_Json::decode(decode_html($size));
            $height = intval($size["sizey"]);
            $this->set("height", $height);
            return $height;
        }
        return $this->getHeight();
    }
    /**
     * Function to get the url of the widget
     * @return <String>
     */
    public function getTypeWidget()
    {
        $db = PearDatabase::getInstance();
        $reportId = $this->get("id");
        $reportLinkId = $this->get("linkid");
        $result = $db->pquery("SELECT vtiger_module_vreportdashboard_widgets.title,vtiger_links.linkurl FROM vtiger_module_vreportdashboard_widgets \n                                      INNER JOIN vtiger_links ON vtiger_module_vreportdashboard_widgets.linkid = vtiger_links.linkid WHERE \n                                      vtiger_module_vreportdashboard_widgets.id = ? AND vtiger_module_vreportdashboard_widgets.linkid = ?", array($reportId, $reportLinkId));
        $reportTitle = $db->query_result($result, 0, "title");
        $reportUrl = $db->query_result($result, 0, "linkurl");
        preg_match("/.+ReportWidget/", $reportTitle, $matches);
        $typeWidget = $matches[0];
        if (!$typeWidget) {
            $tempVal1 = explode("&", $reportUrl);
            $tempVal2 = explode("=", $tempVal1[1]);
            $typeWidget = str_replace("Actions", "ReportWidget", $tempVal2[1]);
        }
        return $typeWidget;
    }
    public function getUrl()
    {
        $db = PearDatabase::getInstance();
        $url = decode_html($this->get("linkurl")) . "&linkid=" . $this->get("linkid");
        if ($this->get("linklabel") != "Mini List VReports") {
            $url = decode_html($this->get("linkurl"));
        }
        if ($this->get("reportid")) {
            $title = $this->getTitle();
            preg_match("/.+ReportWidget/", $title, $matches);
            $typeWidget = $matches[0];
            if (!$typeWidget) {
                $typeWidget = $this->getTypeWidget();
            }
            if ($typeWidget == "PivotReportWidget") {
                $pivotReportLinkUrl = "index.php?module=VReports&view=ShowWidget&name=PivotReportWidget&reportid=" . $this->get("reportid");
                $url = decode_html($pivotReportLinkUrl);
            } else {
                if ($typeWidget == "TabularReportWidget") {
                    $tabularReportLinkUrl = "index.php?module=VReports&view=ShowWidget&name=TabularReportWidget&reportid=" . $this->get("reportid");
                    $url = decode_html($tabularReportLinkUrl);
                } else {
                    if ($typeWidget == "SqlReportWidget") {
                        $tabularReportLinkUrl = "index.php?module=VReports&view=ShowWidget&name=SqlReportWidget&reportid=" . $this->get("reportid");
                        $url = decode_html($tabularReportLinkUrl);
                    } else {
                        if ($typeWidget == "ChartReportWidget") {
                            $chartReportLinkUrl = "index.php?module=VReports&view=ShowWidget&name=ChartReportWidget&reportid=" . $this->get("reportid");
                            $url = decode_html($chartReportLinkUrl);
                        }
                    }
                }
            }
        }
        $modeWidget = $_REQUEST["modeWidget"];
        $typeWiget = $_REQUEST["name"];
        if ($modeWidget == "add" && ($typeWiget == "History" || $typeWiget == "KeyMetrics" || $typeWiget == "Gauge")) {
            $moduleId = getTabid("VReports");
            $tabDashboardCurrentActive = $_REQUEST["tabid"];
            if ($typeWiget == "KeyMetrics") {
                $typeWiget = "Key Metrics";
            }
            $resultWidgetId = $db->pquery("SELECT vw.id, l.linkurl FROM vtiger_module_vreportdashboard_widgets vw\n                                                INNER JOIN vtiger_links l ON l.linkid =  vw.linkid \n                                                WHERE l.tabid = ? AND l.linklabel = ? AND vw.dashboardtabid = ?", array($moduleId, $typeWiget, $tabDashboardCurrentActive));
            if (0 < $db->num_rows($resultWidgetId)) {
                $url = html_entity_decode($db->query_result($resultWidgetId, 0, "linkurl"));
                $this->set("widgetid", $db->query_result($resultWidgetId, 0, "id"));
            }
        } else {
            $query = $db->pquery("SELECT vtiger_links.linklabel,vtiger_links.linkurl FROM vtiger_module_vreportdashboard_widgets INNER JOIN vtiger_links\n                              ON vtiger_module_vreportdashboard_widgets.linkid = vtiger_links.linkid WHERE vtiger_module_vreportdashboard_widgets.id = ?", array($this->get("id")));
            $linklabel = $db->query_result($query, 0, "linklabel");
            if ($linklabel == "Key Metrics" || $linklabel == "History") {
                $url = $db->query_result($query, 0, "linkurl");
            }
        }
        $widgetid = $this->has("widgetid") ? $this->get("widgetid") : $this->get("id");
        if ($widgetid) {
            $url .= "&widgetid=" . $widgetid;
        }
        return $url;
    }
    public function getUrlReportDetail()
    {
        $url = decode_html($this->get("linkurl")) . "&linkid=" . $this->get("linkid");
        if ($this->get("reportid")) {
            $title = $this->getTitle();
            preg_match("/.+ReportWidget/", $title, $matches);
            $typeWidget = $matches[0];
            if (!$typeWidget) {
                $typeWidget = $this->getTypeWidget();
            }
            if ($typeWidget == "PivotReportWidget") {
                $pivotReportLinkUrl = "index.php?module=VReports&view=PivotDetail&record=" . $this->get("reportid");
                $url = decode_html($pivotReportLinkUrl);
            } else {
                if ($typeWidget == "TabularReportWidget") {
                    $tabularReportLinkUrl = "index.php?module=VReports&view=Detail&record=" . $this->get("reportid");
                    $url = decode_html($tabularReportLinkUrl);
                } else {
                    if ($typeWidget == "SqlReportWidget") {
                        $tabularReportLinkUrl = "index.php?module=VReports&view=SqlReportDetail&record=" . $this->get("reportid");
                        $url = decode_html($tabularReportLinkUrl);
                    } else {
                        if ($typeWidget == "ChartReportWidget") {
                            $chartReportLinkUrl = "index.php?module=VReports&view=ChartDetail&record=" . $this->get("reportid");
                            $url = decode_html($chartReportLinkUrl);
                        }
                    }
                }
            }
        }
        return $url;
    }
    public function getUrlReportEdit()
    {
        $url = decode_html($this->get("linkurl")) . "&linkid=" . $this->get("linkid");
        if ($this->get("reportid")) {
            $title = $this->getTitle();
            preg_match("/.+ReportWidget/", $title, $matches);
            $typeWidget = $matches[0];
            if (!$typeWidget) {
                $typeWidget = $this->getTypeWidget();
            }
            if ($typeWidget == "PivotReportWidget") {
                $pivotReportLinkUrl = "index.php?module=VReports&view=PivotEdit&record=" . $this->get("reportid");
                $url = decode_html($pivotReportLinkUrl);
            } else {
                if ($typeWidget == "TabularReportWidget") {
                    $tabularReportLinkUrl = "index.php?module=VReports&view=Edit&record=" . $this->get("reportid");
                    $url = decode_html($tabularReportLinkUrl);
                } else {
                    if ($typeWidget == "SqlReportWidget") {
                        $tabularReportLinkUrl = "index.php?module=VReports&view=SqlReportEdit&record=" . $this->get("reportid");
                        $url = decode_html($tabularReportLinkUrl);
                    } else {
                        if ($typeWidget == "ChartReportWidget") {
                            $chartReportLinkUrl = "index.php?module=VReports&view=ChartEdit&record=" . $this->get("reportid");
                            $url = decode_html($chartReportLinkUrl);
                        }
                    }
                }
            }
        }
        return $url;
    }
    public function getUrlDeleteWidgetReport()
    {
        $url = decode_html($this->get("linkurl")) . "&linkid=" . $this->get("linkid");
        if ($this->get("reportid")) {
            $title = $this->getTitle();
            preg_match("/.+ReportWidget/", $title, $matches);
            $typeWidget = $matches[0];
            if (!$typeWidget) {
                $typeWidget = $this->getTypeWidget();
            }
            if ($typeWidget == "PivotReportWidget") {
                $pivotReportLinkUrl = "index.php?module=VReports&action=PivotActions&mode=unpinChartFromDashboard&reportid=" . $this->get("reportid");
                $url = decode_html($pivotReportLinkUrl);
            } else {
                if ($typeWidget == "TabularReportWidget") {
                    $tabularReportLinkUrl = "index.php?module=VReports&action=TabularActions&mode=unpinChartFromDashboard&reportid=" . $this->get("reportid");
                    $url = decode_html($tabularReportLinkUrl);
                } else {
                    if ($typeWidget == "SqlReportWidget") {
                        $tabularReportLinkUrl = "index.php?module=VReports&action=SqlReportActions&mode=unpinChartFromDashboard&reportid=" . $this->get("reportid");
                        $url = decode_html($tabularReportLinkUrl);
                    } else {
                        if ($typeWidget == "ChartReportWidget") {
                            $chartReportLinkUrl = "index.php?module=VReports&action=ChartActions&mode=unpinChartFromDashboard&reportid=" . $this->get("reportid");
                            $url = decode_html($chartReportLinkUrl);
                        } else {
                            $chartReportLinkUrl = "index.php?module=VReports&action=ChartActions&mode=unpinChartFromDashboard&reportid=" . $this->get("reportid");
                            $url = decode_html($chartReportLinkUrl);
                        }
                    }
                }
            }
        }
        return $url;
    }
    /**
     *  Function to get the Title of the widget
     */
    public function getTitle()
    {
        $title = $this->get("title");
        if (!$title) {
            $title = $this->get("linklabel");
        }
        return $title;
    }
    public function getName()
    {
        $widgetName = $this->get("name");
        if (empty($widgetName)) {
            $linkUrl = decode_html($this->getUrl());
            preg_match("/name=[a-zA-Z]+/", $linkUrl, $matches);
            $matches = explode("=", $matches[0]);
            $widgetName = $matches[1];
            $this->set("name", $widgetName);
        }
        return $widgetName;
    }
    /**
     * Function to get the instance of Vtiger Widget Model from the given array of key-value mapping
     * @param <Array> $valueMap
     * @return VReports_Widget_Model instance
     */
    public static function getInstanceFromValues($valueMap, $typeReport = "")
    {
        $self = new self();
        $self->setData($valueMap);
        if ($typeReport) {
            $self->set("report_type", $typeReport);
        }
        return $self;
    }
    public static function getInstanceKeyMetrics($linkId, $widgetid)
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT * FROM vtiger_module_vreportdashboard_widgets\n\t\t\tINNER JOIN vtiger_links ON vtiger_links.linkid = vtiger_module_vreportdashboard_widgets.linkid\n\t\t\tWHERE linktype = ? AND vtiger_links.linkid = ? AND id = ?", array("DASHBOARDWIDGET", $linkId, $widgetid));
        $self = new self();
        if ($db->num_rows($result)) {
            $row = $db->query_result_rowdata($result, 0);
            $self->setData($row);
        }
        return $self;
    }
    public static function getHistoryType($widgetid, $linkId)
    {
        $db = PearDatabase::getInstance();
        $query = $db->pquery("SELECT `history_type` FROM `vtiger_module_vreportdashboard_widgets` WHERE id = ? AND linkid = ?", array($widgetid, $linkId));
        return $db->query_result($query, 0, 0);
    }
    public static function getInstance($linkId, $userId)
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT * FROM vtiger_module_vreportdashboard_widgets\n\t\t\tINNER JOIN vtiger_links ON vtiger_links.linkid = vtiger_module_vreportdashboard_widgets.linkid\n\t\t\tWHERE linktype = ? AND vtiger_links.linkid = ? AND userid = ?", array("DASHBOARDWIDGET", $linkId, $userId));
        $self = new self();
        if ($db->num_rows($result)) {
            $row = $db->query_result_rowdata($result, 0);
            $self->setData($row);
        }
        return $self;
    }
    public static function getHistoryWidget($linkId, $userId, $widgetId)
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT * FROM vtiger_module_vreportdashboard_widgets\n\t\t\tINNER JOIN vtiger_links ON vtiger_links.linkid = vtiger_module_vreportdashboard_widgets.linkid\n\t\t\tWHERE linktype = ? AND vtiger_links.linkid = ? AND id = ?", array("DASHBOARDWIDGET", $linkId, $widgetId));
        $self = new self();
        if ($db->num_rows($result)) {
            $row = $db->query_result_rowdata($result, 0);
            $self->setData($row);
        }
        return $self;
    }
    public static function updateWidgetPosition($position, $linkId, $widgetId, $userId)
    {
        if (!$linkId && !$widgetId) {
            return NULL;
        }
        $db = PearDatabase::getInstance();
        $sql = "UPDATE vtiger_module_vreportdashboard_widgets SET position=? WHERE userid=?";
        $params = array($position, $userId);
        if ($linkId) {
            $sql .= " AND linkid = ?";
            $params[] = $linkId;
        } else {
            if ($widgetId) {
                $sql .= " AND id = ?";
                $params[] = $widgetId;
            }
        }
        $db->pquery($sql, $params);
    }
    public static function updateWidgetSize($size, $linkId, $widgetId, $userId, $tabId)
    {
        if ($linkId || $widgetId) {
            $db = PearDatabase::getInstance();
            $sql = "UPDATE vtiger_module_vreportdashboard_widgets SET size=? WHERE userid=?";
            $params = array($size, $userId);
            if ($linkId) {
                $sql .= " AND linkid=?";
                $params[] = $linkId;
            } else {
                if ($widgetId) {
                    $sql .= " AND id=?";
                    $params[] = $widgetId;
                }
            }
            $sql .= " AND dashboardtabid=?";
            $params[] = $tabId;
            $db->pquery($sql, $params);
        }
    }
    public static function getInstanceWithWidgetId($widgetId, $userId)
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT * FROM vtiger_module_vreportdashboard_widgets\n\t\t\tINNER JOIN vtiger_links ON vtiger_links.linkid = vtiger_module_vreportdashboard_widgets.linkid\n\t\t\tWHERE linktype = ? AND vtiger_module_vreportdashboard_widgets.id = ?", array("DASHBOARDWIDGET", $widgetId));
        $self = new self();
        if ($db->num_rows($result)) {
            $row = $db->query_result_rowdata($result, 0);
            if ($userId != $row["userid"]) {
                $row["shared"] = true;
            }
            $self->setData($row);
        }
        return $self;
    }
    public static function getInstanceWithReportId($reportId, $tabId)
    {
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $result = $db->pquery("SELECT vrw.*,vtiger_vreport.reportname\n        FROM vtiger_module_vreportdashboard_widgets vrw\n        INNER JOIN vtiger_vreport ON vtiger_vreport.reportid = vrw.reportid\n        WHERE vrw.reportid = ? AND dashboardtabid = ?", array($reportId, $tabId));
        $self = new self();
        if ($db->num_rows($result)) {
            $row = $db->query_result_rowdata($result, 0);
            $row["widgetid"] = $row["id"];
            $row["report"] = true;
            $row["nonOwner"] = false;
            if ($row["userid"] != $currentUser->getId() && !$currentUser->isAdminUser()) {
                $row["nonOwner"] = true;
            }
            $self->setData($row);
        }
        return $self;
    }
    public static function updateSettingWidget($widgetRecord, $selectedColor, $timeRefresh, $widgetData, $showEmptyVal, $titleWidget, $minHeight = 0, $maxHeight = 0)
    {
        $db = PearDatabase::getInstance();
        $sql = "UPDATE `vtiger_module_vreportdashboard_widgets` SET ";
        $value = array();
        if ($selectedColor) {
            $sql .= " pick_color = ? ,";
            array_push($value, $selectedColor);
        }
        if ($showEmptyVal != "") {
            $sql .= " km_show_empty_val = ? ,";
            array_push($value, $showEmptyVal);
        }
        if ($timeRefresh != "") {
            $sql .= " refresh_time = ? ,";
            array_push($value, $timeRefresh);
        }
        if ($widgetData != "\"\"") {
            $sql .= " data = ? ,";
            array_push($value, $widgetData);
        }
        if ($titleWidget != "") {
            $sql .= " title = ? ,";
            array_push($value, $titleWidget);
        }
        if ($minHeight == "") {
            $minHeight = NULL;
        }
        if ($maxHeight == "") {
            $maxHeight = NULL;
        }
        $sql .= " min_height = ? ,";
        array_push($value, $minHeight);
        $sql .= " max_height = ? ,";
        array_push($value, $maxHeight);
        $sql = rtrim($sql, ",");
        $sql .= " WHERE id = ?";
        array_push($value, $widgetRecord);
        $db->pquery($sql, $value);
    }
    /**
     * Function to add a widget from the Users Dashboard
     */
    public static function getLinkId($moduleName, $componentName)
    {
        $db = PearDatabase::getInstance();
        $moduleId = getTabid($moduleName);
        if ($componentName == "KeyMetrics") {
            $componentName = "Key Metrics";
        }
        $result = $db->pquery("SELECT linkid FROM `vtiger_links` WHERE tabid = ? AND linktype =? AND linklabel = ?", array($moduleId, "DASHBOARDWIDGET", $componentName));
        return $db->query_result($result, 0, 0);
    }
    public function setHistoryType($type, $historyType, $widgetid, $tabid, $sortandgroup = "")
    {
        $db = PearDatabase::getInstance();
        if ($sortandgroup == "") {
            $sortandgroup = 0;
        }
        $db->pquery("UPDATE `vtiger_module_vreportdashboard_widgets` SET history_type = ?,history_type_radio=?,group_and_sort=? WHERE id = ? AND dashboardtabid = ?", array($historyType, $type, $sortandgroup, $widgetid, $tabid));
    }
    public function add($modeWidget = "")
    {
        $current_user = Users_Privileges_Model::getCurrentUserModel();
        $db = PearDatabase::getInstance();
        $tabid = 1;
        if ($this->get("tabid")) {
            $tabid = $this->get("tabid");
        }
        if ($this->get("widget_name") == "History") {
            $this->set("color", "#212121");
        }
        $sql = "SELECT id FROM vtiger_module_vreportdashboard_widgets WHERE linkid = ? AND dashboardtabid=?";
        $params = array($this->get("linkid"), $tabid);
        $filterid = $this->get("filterid");
        if (!empty($filterid)) {
            $sql .= " AND filterid = ?";
            $params[] = $this->get("filterid");
        }
        $sql .= " ORDER BY id DESC";
        $result = $db->pquery($sql, $params);
        $avoidInsertDupicateWidget = array("KeyMetrics", "Gauge");
        if (!$db->num_rows($result) || $this->get("data") && !in_array($this->get("widget_name"), $avoidInsertDupicateWidget)) {
            $db->pquery("INSERT INTO vtiger_module_vreportdashboard_widgets(linkid, userid, filterid, title, data, dashboardtabid, pick_color, refresh_time) VALUES(?,?,?,?,?,?,?,?)", array($this->get("linkid"), $this->get("userid"), $this->get("filterid"), $this->get("title"), Zend_Json::encode($this->get("data")), $tabid, $this->get("color"), $this->get("refresh_time")));
            if ($db->getLastInsertID() == 0) {
                $this->set("id", $this->getLastInsertId());
            } else {
                $this->set("id", $db->getLastInsertID());
            }
        } else {
            if ($modeWidget) {
                $db->pquery("INSERT INTO vtiger_module_vreportdashboard_widgets(linkid, userid, filterid, title, data, dashboardtabid, pick_color, refresh_time) VALUES(?,?,?,?,?,?,?,?)", array($this->get("linkid"), $this->get("userid"), $this->get("filterid"), $this->get("title"), Zend_Json::encode($this->get("data")), $tabid, $this->get("color"), $this->get("refresh_time")));
                if ($db->getLastInsertID() == 0) {
                    $this->set("id", $this->getLastInsertId());
                } else {
                    $this->set("id", $db->getLastInsertID());
                }
            } else {
                $this->set("id", $db->query_result($result, 0, "id"));
            }
        }
    }
    public function getLastInsertId()
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT id FROM `vtiger_module_vreportdashboard_widgets` ORDER BY id DESC LIMIT 1", array());
        return $db->query_result($result, 0, 0);
    }
    /**
     * Function to remove the widget from the Users Dashboard
     */
    public function remove()
    {
        $db = PearDatabase::getInstance();
        $db->pquery("DELETE FROM vtiger_module_vreportdashboard_widgets WHERE id = ? AND userid = ?", array($this->get("id"), $this->get("userid")));
    }
    /**
     * Function deletes all dashboard widgets with the reportId
     * @param type $reportId
     */
    public static function deleteChartReportWidgets($reportId)
    {
        $db = PearDatabase::getInstance();
        $db->pquery("DELETE FROM vtiger_module_vreportdashboard_widgets WHERE reportid = ?", array($reportId));
    }
    /**
     * Function returns URL that will remove a widget for a User
     * @return <String>
     */
    public function getDeleteUrl()
    {
        $url = "index.php?module=VReports&action=RemoveWidget&linkid=" . $this->get("linkid");
        $widgetid = $this->has("widgetid") ? $this->get("widgetid") : $this->get("id");
        if ($widgetid) {
            $url .= "&widgetid=" . $widgetid;
        }
        if ($this->get("reportid")) {
            $url .= "&reportid=" . $this->get("reportid");
        }
        return $url;
    }
    /**
     * Function to check the Widget is Default widget or not
     * @return <boolean> true/false
     */
    public function isDefault()
    {
        $defaultWidgets = $this->getDefaultWidgets();
        $widgetName = $this->getName();
        if (in_array($widgetName, $defaultWidgets)) {
            return true;
        }
        return false;
    }
    /**
     * Function to get Default widget Names
     * @return <type>
     */
    public function getDefaultWidgets()
    {
        return array();
    }
    public static function getTextColor($hexcolor)
    {
        $hexcolor = str_replace("#", "", $hexcolor);
        $r = intval(substr($hexcolor, 0, 2), 16);
        $g = intval(substr($hexcolor, 2, 2), 16);
        $b = intval(substr($hexcolor, 4, 2), 16);
        $yiq = ($r * 299 + $g * 587 + $b * 114) / 1000;
        return 128 <= $yiq ? "black" : "white";
    }
    public static function saveAutoRefreshLog($widgetid)
    {
        $db = PearDatabase::getInstance();
        $db->pquery("INSERT INTO `vreports_autorefresh_logs`(`widget_id`, `refreshed_time`, `result`) VALUES ('" . $widgetid . "', NOW(), 'false')");
        return $db->getLastInsertID();
    }
    public static function updateAutoRefreshLog($widgetid, $autorefresh_id)
    {
        $db = PearDatabase::getInstance();
        $db->pquery("UPDATE `vreports_autorefresh_logs` SET `result` = 'true', `refreshed_time` = NOW() WHERE `id` = '" . $autorefresh_id . "'");
        $db->pquery("UPDATE `vtiger_module_vreportdashboard_widgets` SET `last_refreshed_time` = NOW() WHERE `id` = '" . $widgetid . "'");
    }
}

?>