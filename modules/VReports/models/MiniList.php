<?php

error_reporting(0);
class VReports_MiniList_Model extends Vtiger_MiniList_Model
{
    protected $dynamicFilterAssignedTo = "";
    protected $dynamicFilterCreatedBy = "";
    public function getTitle($prefix = "")
    {
        $this->initListViewController();
        $db = PearDatabase::getInstance();
        $suffix = "";
        $customviewrs = $db->pquery("SELECT viewname FROM vtiger_customview WHERE cvid=?", array($this->widgetModel->get("filterid")));
        if ($db->num_rows($customviewrs)) {
            $customview = $db->fetch_array($customviewrs);
            $suffix = $customview["viewname"];
        }
        $titleWidget = $this->widgetModel->get("title");
        if ($titleWidget != "") {
            $suffix = $this->widgetModel->get("title");
        }
        return $prefix . $suffix;
    }
    public static function getStyleForTable($widgetId)
    {
        global $adb;
        $query = $adb->pquery("SELECT * FROM `vtiger_vreports_css_defaults` WHERE widgetId= ? UNION ALL\r\n                                    SELECT * FROM `vtiger_vreports_css_defaults` WHERE widgetId = 0\r\n                                    AND NOT EXISTS(SELECT 1 FROM `vtiger_vreports_css_defaults` WHERE widgetId= ?);", array($widgetId, $widgetId));
        $result = array();
        if (0 < $adb->num_rows($query)) {
            while ($resultrow = $adb->fetch_array($query)) {
                $result[$resultrow["type"]] = $resultrow["description"];
            }
        }
        return $result;
    }
    public function getRecords($mode = "", $limit = "")
    {
        global $adb;
        global $root_directory;
        $this->initListViewController();
        if (!$this->listviewRecords || $mode) {
            $db = PearDatabase::getInstance();
            $query = $this->queryGenerator->getQuery();
            $data = $this->extraData;
            if ((0 < count($this->dynamicFilterAssignedTo) || 0 < count($this->dynamicFilterCreatedBy)) && $data["orderField"] == "" && $data["orderField1"] == "") {
                if (0 < count($this->dynamicFilterAssignedTo)) {
                    if ($this->isDeletedRecord($this->dynamicFilterAssignedTo["recordId"])) {
                        return false;
                    }
                    $query .= $this->dynamicFilterAssignedTo["query"];
                }
                if (0 < count($this->dynamicFilterCreatedBy)) {
                    if ($this->isDeletedRecord($this->dynamicFilterCreatedBy["recordId"])) {
                        return false;
                    }
                    $query .= $this->dynamicFilterCreatedBy["query"];
                }
            } else {
                if ((0 < count($this->dynamicFilterAssignedTo) || 0 < count($this->dynamicFilterCreatedBy)) && ($data["orderField"] || $data["orderField1"])) {
                    if (0 < count($this->dynamicFilterAssignedTo)) {
                        if ($this->isDeletedRecord($this->dynamicFilterAssignedTo["recordId"])) {
                            return false;
                        }
                        $query .= $this->dynamicFilterAssignedTo["query"];
                    }
                    if (0 < count($this->dynamicFilterCreatedBy)) {
                        if ($this->isDeletedRecord($this->dynamicFilterCreatedBy["recordId"])) {
                            return false;
                        }
                        $query .= $this->dynamicFilterCreatedBy["query"];
                    }
                    $fieldModels = $this->queryGenerator->getModuleFields();
                    $orderByFieldModel = $fieldModels[$data["orderField"]];
                    if ($orderByFieldModel && ($orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE || $orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE)) {
                        $this->queryGenerator->addWhereField($data["orderField"]);
                    }
                    $orderByFieldModel1 = $fieldModels[$data["orderField1"]];
                    if ($orderByFieldModel1 && ($orderByFieldModel1->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE || $orderByFieldModel1->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE)) {
                        $this->queryGenerator->addWhereField($data["orderField1"]);
                    }
                    if ($data["orderField"]) {
                        if (!$data["orderKeyword"]) {
                            $sortOrder = "ASC";
                        } else {
                            $sortOrder = $data["orderKeyword"];
                        }
                        $order = $this->queryGenerator->getOrderByColumn($data["orderField"]) . " " . $sortOrder;
                    } else {
                        $order = "";
                    }
                    if ($data["orderField1"]) {
                        if (!$data["orderKeyword1"]) {
                            $sortOrder1 = "ASC";
                        } else {
                            $sortOrder1 = $data["orderKeyword1"];
                        }
                        if ($data["orderField"]) {
                            $order1 = ",";
                        } else {
                            $order1 = "";
                        }
                        $order1 .= $this->queryGenerator->getOrderByColumn($data["orderField1"]) . " " . $sortOrder1;
                    } else {
                        $order1 = "";
                    }
                    $query .= " ORDER BY " . $order . $order1;
                } else {
                    $query .= " ORDER BY vtiger_crmentity.modifiedtime DESC";
                }
            }
            if (!$mode) {
                if ($limit) {
                    $query .= " LIMIT 0," . $limit;
                } else {
                    $query .= " LIMIT " . $this->getStartIndex() . "," . $this->getRecordLimit();
                }
            }
            $query = str_replace(" FROM ", ",vtiger_crmentity.crmid as id FROM ", $query);
            if ($this->getTargetModule() == "Calendar") {
                $query = str_replace(" WHERE ", " WHERE vtiger_crmentity.setype = 'Calendar' AND ", $query);
            }
            $result = $db->pquery($query, array());
            if ($mode) {
                $counts = $db->num_rows($result);
                return $counts;
            }
            $targetModuleName = $this->getTargetModule();
            $targetModuleFocus = CRMEntity::getInstance($targetModuleName);
            $entries = $this->listviewController->getListViewRecords($targetModuleFocus, $targetModuleName, $result);
            if ($data["showLineOnRow"] == "true" && $data["showLineOnRow1"] == "true") {
                $entries = $this->initializeListviewRecords($entries, $data["orderField"], $data["orderField1"]);
            } else {
                if ($data["showLineOnRow"] == "true") {
                    $entries = $this->initializeListviewRecords($entries, $data["orderField"], "");
                } else {
                    if ($data["showLineOnRow1"] == "true") {
                        $entries = $this->initializeListviewRecords($entries, "", $data["orderField1"]);
                    }
                }
            }
            $this->listviewRecords = array();
            $index = 0;
            include_once (string) $root_directory . "/test/vreports/config.vreports.php";
            if (file_exists((string) $root_directory . "/modules/VTEButtons/VTEButtons.php")) {
                require_once (string) $root_directory . "/modules/VTEButtons/VTEButtons.php";
            }
            foreach ($entries as $id => $record) {
                $rawData = $db->query_result_rowdata($result, $index++);
                $record["id"] = $id;
                $this->listviewRecords[$id] = $this->getTargetModuleModel()->getRecordFromArray($record, $rawData);
                if (class_exists("VTEButtons")) {
                    $sql = "SELECT * FROM `vte_buttons_settings` WHERE module='" . $targetModuleName . "' AND active = 1 ORDER BY sequence";
                    $results = $adb->pquery($sql, array());
                    $header_array = array();
                    if (loadVTEButtonOnList) {
                        while ($results && ($row = $adb->fetchByAssoc($results))) {
                            $moduleName = $row["module"];
                            $vtebuttonsId = $row["id"];
                            $can_view = true;
                            $moduleModel = new VTEButtons_Module_Model();
                            $conditions = $moduleModel->getConditionalShowButtons($vtebuttonsId, $moduleName);
                            if (!empty($conditions)) {
                                foreach ($conditions as $condition) {
                                    $can_view = $moduleModel->getRecordsByCondition($condition, $id);
                                    if (!empty($can_view)) {
                                        $header_array[] = array("vtebuttonsid" => $row["id"], "module" => $row["module"], "header" => $row["header"], "icon" => $row["icon"], "color" => $row["color"], "sequence" => $row["sequence"]);
                                    }
                                }
                            }
                        }
                        $this->listviewRecords[$id]->set("VTEButton", $header_array);
                    }
                }
            }
        }
        return $this->listviewRecords;
    }
    public function initializeListviewRecords($listview, $order = "", $order1 = "")
    {
        $recognizeHr = "";
        $recognizeHr1 = "";
        foreach ($listview as $item => $value) {
            if ($order && $order1) {
                if ($value[$order] != $recognizeHr || $value[$order] == "") {
                    $value["hrOnRow"] = "true";
                    $recognizeHr = $value[$order];
                    $recognizeHr1 = $value[$order1];
                } else {
                    if ($value[$order1] != $recognizeHr1 || $value[$order1] == "") {
                        $value["hrOnRow"] = "true";
                        $recognizeHr = $value[$order];
                        $recognizeHr1 = $value[$order1];
                    }
                }
            } else {
                if ($order) {
                    if ($value[$order] != $recognizeHr) {
                        $value["hrOnRow"] = "true";
                        $recognizeHr = $value[$order];
                    }
                } else {
                    if ($order1 && $value[$order1] != $recognizeHr1) {
                        $value["hrOnRow"] = "true";
                        $recognizeHr1 = $value[$order1];
                    }
                }
            }
            $entries[$item] = $value;
        }
        return $entries;
    }
    public function transferListSearchParamsToFilterCondition($listSearchParams, $moduleModel)
    {
        return Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($listSearchParams, $moduleModel);
    }
    public function moreRecordExists()
    {
        global $adb;
        $currentUser = Users_Privileges_Model::getCurrentUserModel();
        $this->initListViewController();
        $data = $this->extraData;
        $widgetFilterAssignedto = $data["filterAssignedto"];
        $widgetFilterCreatedby = $data["filterCreatedby"];
        $resultDynamicFilter = $adb->pquery("SELECT vtiger_crmentity.label,\r\n                                                    vtiger_crmentity.deleted,\r\n                                                    vtiger_vreportdashboard_tabs.dynamic_filter_account,\r\n                                                    dynamic_filter_assignedto,dynamic_filter_date,dynamic_filter_type_date,dynamic_filter_createdby\r\n                                                  FROM vtiger_vreportdashboard_tabs \r\n                                                  LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_vreportdashboard_tabs.dynamic_filter_account\r\n                                                  WHERE id = ?\r\n                                                  AND ((dynamic_filter_account > 0 AND dynamic_filter_account IS NOT NULL)\r\n                                                  OR dynamic_filter_assignedto IS NOT NULL\r\n                                                  OR (dynamic_filter_date IS NOT NULL)) LIMIT 1", array($_REQUEST["tabid"]));
        $checkAccountUrl = explode("=", end(explode("&", $_SERVER["HTTP_REFERER"])));
        $checkAccountUrl = $checkAccountUrl[0];
        if (0 < $adb->num_rows($resultDynamicFilter) || $widgetFilterAssignedto != "" && $widgetFilterAssignedto != NULL || $checkAccountUrl == "organization") {
            $this->queryGenerator->reset();
            $searchParams = array();
            $record_deleted = $adb->query_result($resultDynamicFilter, 0, "deleted");
            if ($record_deleted == 1) {
                return false;
            }
            if ($checkAccountUrl == "organization") {
                $accountId = explode("=", end(explode("&", $_SERVER["HTTP_REFERER"])));
                $accountId = $accountId[1];
                $queryGetAccount = $adb->pquery("SELECT * FROM `vtiger_account` WHERE accountid = ?", array($accountId));
            }
            if (0 < $adb->num_rows($queryGetAccount)) {
                $dynamicFilterAccount = $adb->query_result($queryGetAccount, 0, "accountid");
                $dynamicFilterAccountLabel = $adb->query_result($queryGetAccount, 0, "accountname");
            } else {
                $dynamicFilterAccount = $adb->query_result($resultDynamicFilter, 0, "dynamic_filter_account");
                $dynamicFilterAccountLabel = $adb->query_result($resultDynamicFilter, 0, "label");
            }
            if ($widgetFilterAssignedto != "" && $widgetFilterAssignedto != NULL) {
                $dynamicFilterAssignedTo = $widgetFilterAssignedto;
            } else {
                $dynamicFilterAssignedTo = $adb->query_result($resultDynamicFilter, 0, "dynamic_filter_assignedto");
            }
            if ($widgetFilterCreatedby != "" && $widgetFilterCreatedby != NULL) {
                $dynamicFilterCreatedBy = $widgetFilterCreatedby;
            } else {
                $dynamicFilterCreatedBy = $adb->query_result($resultDynamicFilter, 0, "dynamic_filter_createdby");
            }
            $dynamicFilterAssignedTo = $dynamicFilterAssignedTo == 0 && is_numeric($dynamicFilterAssignedTo) ? $currentUser->getId() : $dynamicFilterAssignedTo;
            $dynamicFilterCreatedBy = $dynamicFilterCreatedBy == 0 && is_numeric($dynamicFilterCreatedBy) ? $currentUser->getId() : $dynamicFilterCreatedBy;
            $dynamicFilterDate = $adb->query_result($resultDynamicFilter, "0", "dynamic_filter_date");
            $dynamicFilterValueTypeDate = $adb->query_result($resultDynamicFilter, "0", "dynamic_filter_type_date");
            if ($dynamicFilterAccount) {
                foreach ($this->queryGenerator->getReferenceFieldInfoList() as $keyField => $valueField) {
                    $referenceParentFieldName = $keyField;
                    if ($valueField[0] == "Accounts") {
                        $searchParams[0][0][] = $referenceParentFieldName;
                        $searchParams[0][0][] = "e";
                        $searchParams[0][0][] = $dynamicFilterAccountLabel;
                        break;
                    }
                }
                $this->dynamicFilterAssignedTo["recordId"] = $dynamicFilterAssignedTo;
            } else {
                if ($dynamicFilterAssignedTo || $dynamicFilterCreatedBy) {
                    if ($dynamicFilterAssignedTo) {
                        $this->dynamicFilterAssignedTo["recordId"] = $dynamicFilterAssignedTo;
                    }
                    if ($dynamicFilterCreatedBy) {
                        $this->dynamicFilterCreatedBy["recordId"] = $dynamicFilterCreatedBy;
                    }
                }
            }
            $transformedSearchParams = $this->transferListSearchParamsToFilterCondition($searchParams, Vtiger_Module_Model::getInstance($this->queryGenerator->getModule()));
            if (empty($searchParams)) {
                $searchParams = array();
            } else {
                $searchParams = $transformedSearchParams;
            }
            $glue = "";
            if (0 < count($this->queryGenerator->getWhereFields()) && 0 < count($searchParams)) {
                $glue = QueryGenerator::$AND;
            }
            $this->queryGenerator->parseAdvFilterList($searchParams, $glue);
        }
        if ($this->extraData["orderField"]) {
            $this->queryGenerator->addWhereField($this->extraData["orderField"]);
        }
        if ($this->extraData["orderField1"]) {
            $this->queryGenerator->addWhereField($this->extraData["orderField1"]);
        }
        $query = $this->queryGenerator->getQuery();
        $customViewModel = CustomView_Record_Model::getInstanceById($this->widgetModel->get("filterid"));
        $condition = $customViewModel->transformToNewAdvancedFilter();
        $assign_to = array();
        foreach ($condition as $key => $value) {
            foreach ($value as $index => $item) {
                foreach ($item as $filterName) {
                    $assign_to[] = $filterName["columnname"];
                }
            }
        }
        $moduleName = $this->queryGenerator->getModule();
        if ($dynamicFilterAssignedTo) {
            $tempVal = $this->queryGenerator->getOwnerFieldList();
            $ownerFieldModel = Vtiger_Field_Model::getInstance($tempVal[0], Vtiger_Module_Model::getInstance($this->queryGenerator->getModule()));
            $fieldTableName = $ownerFieldModel->get("table");
            $fieldColumnName = $ownerFieldModel->get("column");
            $fieldLabel = str_replace(" ", "_", $ownerFieldModel->get("label"));
            $fieldName = $ownerFieldModel->getName();
            $fieldTypeData = explode("~", $ownerFieldModel->get("typeofdata"));
            $fieldTypeData = $fieldTypeData[0];
            $conditionDynamicFilterAssignedTo = $fieldTableName . ":" . $fieldColumnName . ":" . $fieldName . ":" . $moduleName . "_" . $fieldLabel . ":" . $fieldTypeData;
            if (!in_array($conditionDynamicFilterAssignedTo, $assign_to)) {
                $this->dynamicFilterAssignedTo = array("query" => " AND vtiger_crmentity.smownerid = " . $dynamicFilterAssignedTo);
                $query .= $this->dynamicFilterAssignedTo["query"];
            }
        }
        if ($dynamicFilterCreatedBy) {
            $checkDynamicFilter = false;
            foreach ($assign_to as $assign) {
                if (0 < strpos($assign, "smcreatorid")) {
                    $checkDynamicFilter = true;
                }
            }
            if (!$checkDynamicFilter) {
                $this->dynamicFilterCreatedBy = array("query" => " AND vtiger_crmentity.smcreatorid = " . $dynamicFilterCreatedBy);
                $query .= $this->dynamicFilterCreatedBy["query"];
            }
        }
        if ($dynamicFilterDate) {
            $currentUserModel = Users_Record_Model::getCurrentUserModel();
            $userPeferredDayOfTheWeek = $currentUserModel->get("dayoftheweek");
            $dateValues = self::getDateForStdFilterBytype($dynamicFilterDate, $userPeferredDayOfTheWeek);
            list($startDate, $endDate) = $dateValues;
            $advfiltergroupsql = "";
            if ($startDate != "0000-00-00" && $endDate != "0000-00-00" && $startDate != "" && $endDate != "") {
                $startDateTime = new DateTimeField($startDate . " " . date("H:i:s"));
                $endDateTime = new DateTimeField($endDate . " " . date("H:i:s"));
                $userStartDate = $startDateTime->getDisplayDate() . " 00:00:00";
                $userEndDate = $endDateTime->getDisplayDate() . " 23:59:59";
                $startDateTime = getValidDBInsertDateTimeValue($userStartDate);
                $endDateTime = getValidDBInsertDateTimeValue($userEndDate);
                $tableColumnSql = "vtiger_crmentity." . $dynamicFilterValueTypeDate;
                $startDateTime = "'" . $startDateTime . "'";
                $endDateTime = "'" . $endDateTime . "'";
                $advfiltergroupsql .= (string) $tableColumnSql . " BETWEEN " . $startDateTime . " AND " . $endDateTime;
            }
            $this->dynamicFilterAssignedTo .= array("query" => " AND " . $advfiltergroupsql);
        }
        $startIndex = $this->getStartIndex() + $this->getRecordLimit();
        $query .= " LIMIT " . $startIndex . "," . $this->getRecordLimit();
        if ($this->getTargetModule() == "Calendar") {
            $query = str_replace(" WHERE ", " WHERE vtiger_crmentity.setype = 'Calendar' AND ", $query);
        }
        $result = $adb->pquery($query, array());
        if (0 < $adb->num_rows($result)) {
            return true;
        }
        return false;
    }
    protected static function getDateForStdFilterBytype($type, $userPeferredDayOfTheWeek = false)
    {
        $date = DateTimeField::convertToUserTimeZone(date("Y-m-d H:i:s"));
        $d = $date->format("d");
        $m = $date->format("m");
        $y = $date->format("Y");
        $today = date("Y-m-d", mktime(0, 0, 0, $m, $d, $y));
        $todayName = date("l", strtotime($today));
        $tomorrow = date("Y-m-d", mktime(0, 0, 0, $m, $d + 1, $y));
        $yesterday = date("Y-m-d", mktime(0, 0, 0, $m, $d - 1, $y));
        $currentmonth0 = date("Y-m-d", mktime(0, 0, 0, $m, "01", $y));
        $currentmonth1 = $date->format("Y-m-t");
        $lastmonth0 = date("Y-m-d", mktime(0, 0, 0, $m - 1, "01", $y));
        $lastmonth1 = date("Y-m-t", strtotime($lastmonth0));
        $nextmonth0 = date("Y-m-d", mktime(0, 0, 0, $m + 1, "01", $y));
        $nextmonth1 = date("Y-m-t", strtotime($nextmonth0));
        if (!$userPeferredDayOfTheWeek) {
            $userPeferredDayOfTheWeek = "Sunday";
        }
        if ($todayName == $userPeferredDayOfTheWeek) {
            $lastweek0 = date("Y-m-d", strtotime("-1 week " . $userPeferredDayOfTheWeek));
        } else {
            $lastweek0 = date("Y-m-d", strtotime("-2 week " . $userPeferredDayOfTheWeek));
        }
        $prvDay = date("l", strtotime(date("Y-m-d", strtotime("-1 day", strtotime($lastweek0)))));
        $lastweek1 = date("Y-m-d", strtotime("-1 week " . $prvDay));
        if ($todayName == $userPeferredDayOfTheWeek) {
            $thisweek0 = date("Y-m-d", strtotime("-0 week " . $userPeferredDayOfTheWeek));
        } else {
            $thisweek0 = date("Y-m-d", strtotime("-1 week " . $userPeferredDayOfTheWeek));
        }
        $prvDay = date("l", strtotime(date("Y-m-d", strtotime("-1 day", strtotime($thisweek0)))));
        $thisweek1 = date("Y-m-d", strtotime("this " . $prvDay));
        if ($todayName == $userPeferredDayOfTheWeek) {
            $nextweek0 = date("Y-m-d", strtotime("+1 week " . $userPeferredDayOfTheWeek));
        } else {
            $nextweek0 = date("Y-m-d", strtotime("this " . $userPeferredDayOfTheWeek));
        }
        $prvDay = date("l", strtotime(date("Y-m-d", strtotime("-1 day", strtotime($nextweek0)))));
        $nextweek1 = date("Y-m-d", strtotime("+1 week " . $prvDay));
        $next7days = date("Y-m-d", mktime(0, 0, 0, $m, $d + 6, $y));
        $next30days = date("Y-m-d", mktime(0, 0, 0, $m, $d + 29, $y));
        $next60days = date("Y-m-d", mktime(0, 0, 0, $m, $d + 59, $y));
        $next90days = date("Y-m-d", mktime(0, 0, 0, $m, $d + 89, $y));
        $next120days = date("Y-m-d", mktime(0, 0, 0, $m, $d + 119, $y));
        $last7days = date("Y-m-d", mktime(0, 0, 0, $m, $d - 6, $y));
        $last14days = date("Y-m-d", mktime(0, 0, 0, $m, $d - 13, $y));
        $last30days = date("Y-m-d", mktime(0, 0, 0, $m, $d - 29, $y));
        $last60days = date("Y-m-d", mktime(0, 0, 0, $m, $d - 59, $y));
        $last90days = date("Y-m-d", mktime(0, 0, 0, $m, $d - 89, $y));
        $last120days = date("Y-m-d", mktime(0, 0, 0, $m, $d - 119, $y));
        $currentFY0 = date("Y-m-d", mktime(0, 0, 0, "01", "01", $y));
        $currentFY1 = date("Y-m-t", mktime(0, 0, 0, "12", $d, $y));
        $lastFY0 = date("Y-m-d", mktime(0, 0, 0, "01", "01", $y - 1));
        $lastFY1 = date("Y-m-t", mktime(0, 0, 0, "12", $d, $y - 1));
        $nextFY0 = date("Y-m-d", mktime(0, 0, 0, "01", "01", $y + 1));
        $nextFY1 = date("Y-m-t", mktime(0, 0, 0, "12", $d, $y + 1));
        if ($m <= 3) {
            $cFq = date("Y-m-d", mktime(0, 0, 0, "01", "01", $y));
            $cFq1 = date("Y-m-d", mktime(0, 0, 0, "03", "31", $y));
            $nFq = date("Y-m-d", mktime(0, 0, 0, "04", "01", $y));
            $nFq1 = date("Y-m-d", mktime(0, 0, 0, "06", "30", $y));
            $pFq = date("Y-m-d", mktime(0, 0, 0, "10", "01", $y - 1));
            $pFq1 = date("Y-m-d", mktime(0, 0, 0, "12", "31", $y - 1));
        } else {
            if (3 < $m && $m <= 6) {
                $cFq = date("Y-m-d", mktime(0, 0, 0, "04", "01", $y));
                $cFq1 = date("Y-m-d", mktime(0, 0, 0, "06", "30", $y));
                $nFq = date("Y-m-d", mktime(0, 0, 0, "07", "01", $y));
                $nFq1 = date("Y-m-d", mktime(0, 0, 0, "09", "30", $y));
                $pFq = date("Y-m-d", mktime(0, 0, 0, "01", "01", $y));
                $pFq1 = date("Y-m-d", mktime(0, 0, 0, "03", "31", $y));
            } else {
                if (6 < $m && $m <= 9) {
                    $cFq = date("Y-m-d", mktime(0, 0, 0, "07", "01", $y));
                    $cFq1 = date("Y-m-d", mktime(0, 0, 0, "09", "30", $y));
                    $nFq = date("Y-m-d", mktime(0, 0, 0, "10", "01", $y));
                    $nFq1 = date("Y-m-d", mktime(0, 0, 0, "12", "31", $y));
                    $pFq = date("Y-m-d", mktime(0, 0, 0, "04", "01", $y));
                    $pFq1 = date("Y-m-d", mktime(0, 0, 0, "06", "30", $y));
                } else {
                    $cFq = date("Y-m-d", mktime(0, 0, 0, "10", "01", $y));
                    $cFq1 = date("Y-m-d", mktime(0, 0, 0, "12", "31", $y));
                    $nFq = date("Y-m-d", mktime(0, 0, 0, "01", "01", $y + 1));
                    $nFq1 = date("Y-m-d", mktime(0, 0, 0, "03", "31", $y + 1));
                    $pFq = date("Y-m-d", mktime(0, 0, 0, "07", "01", $y));
                    $pFq1 = date("Y-m-d", mktime(0, 0, 0, "09", "30", $y));
                }
            }
        }
        $dateValues = array();
        if ($type == "today") {
            $dateValues[0] = $today;
            $dateValues[1] = $today;
        } else {
            if ($type == "yesterday") {
                $dateValues[0] = $yesterday;
                $dateValues[1] = $yesterday;
            } else {
                if ($type == "tomorrow") {
                    $dateValues[0] = $tomorrow;
                    $dateValues[1] = $tomorrow;
                } else {
                    if ($type == "thisweek") {
                        $dateValues[0] = $thisweek0;
                        $dateValues[1] = $thisweek1;
                    } else {
                        if ($type == "lastweek") {
                            $dateValues[0] = $lastweek0;
                            $dateValues[1] = $lastweek1;
                        } else {
                            if ($type == "nextweek") {
                                $dateValues[0] = $nextweek0;
                                $dateValues[1] = $nextweek1;
                            } else {
                                if ($type == "thismonth") {
                                    $dateValues[0] = $currentmonth0;
                                    $dateValues[1] = $currentmonth1;
                                } else {
                                    if ($type == "lastmonth") {
                                        $dateValues[0] = $lastmonth0;
                                        $dateValues[1] = $lastmonth1;
                                    } else {
                                        if ($type == "nextmonth") {
                                            $dateValues[0] = $nextmonth0;
                                            $dateValues[1] = $nextmonth1;
                                        } else {
                                            if ($type == "next7days") {
                                                $dateValues[0] = $today;
                                                $dateValues[1] = $next7days;
                                            } else {
                                                if ($type == "next30days") {
                                                    $dateValues[0] = $today;
                                                    $dateValues[1] = $next30days;
                                                } else {
                                                    if ($type == "next60days") {
                                                        $dateValues[0] = $today;
                                                        $dateValues[1] = $next60days;
                                                    } else {
                                                        if ($type == "next90days") {
                                                            $dateValues[0] = $today;
                                                            $dateValues[1] = $next90days;
                                                        } else {
                                                            if ($type == "next120days") {
                                                                $dateValues[0] = $today;
                                                                $dateValues[1] = $next120days;
                                                            } else {
                                                                if ($type == "last7days") {
                                                                    $dateValues[0] = $last7days;
                                                                    $dateValues[1] = $today;
                                                                } else {
                                                                    if ($type == "last14days") {
                                                                        $dateValues[0] = $last14days;
                                                                        $dateValues[1] = $today;
                                                                    } else {
                                                                        if ($type == "last30days") {
                                                                            $dateValues[0] = $last30days;
                                                                            $dateValues[1] = $today;
                                                                        } else {
                                                                            if ($type == "last60days") {
                                                                                $dateValues[0] = $last60days;
                                                                                $dateValues[1] = $today;
                                                                            } else {
                                                                                if ($type == "last90days") {
                                                                                    $dateValues[0] = $last90days;
                                                                                    $dateValues[1] = $today;
                                                                                } else {
                                                                                    if ($type == "last120days") {
                                                                                        $dateValues[0] = $last120days;
                                                                                        $dateValues[1] = $today;
                                                                                    } else {
                                                                                        if ($type == "thisfy") {
                                                                                            $dateValues[0] = $currentFY0;
                                                                                            $dateValues[1] = $currentFY1;
                                                                                        } else {
                                                                                            if ($type == "prevfy") {
                                                                                                $dateValues[0] = $lastFY0;
                                                                                                $dateValues[1] = $lastFY1;
                                                                                            } else {
                                                                                                if ($type == "nextfy") {
                                                                                                    $dateValues[0] = $nextFY0;
                                                                                                    $dateValues[1] = $nextFY1;
                                                                                                } else {
                                                                                                    if ($type == "nextfq") {
                                                                                                        $dateValues[0] = $nFq;
                                                                                                        $dateValues[1] = $nFq1;
                                                                                                    } else {
                                                                                                        if ($type == "prevfq") {
                                                                                                            $dateValues[0] = $pFq;
                                                                                                            $dateValues[1] = $pFq1;
                                                                                                        } else {
                                                                                                            if ($type == "thisfq") {
                                                                                                                $dateValues[0] = $cFq;
                                                                                                                $dateValues[1] = $cFq1;
                                                                                                            } else {
                                                                                                                $dateValues[0] = "";
                                                                                                                $dateValues[1] = "";
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $dateValues;
    }
    protected function isDeletedRecord($id)
    {
        global $adb;
        $result = $adb->pquery("SELECT 1 FROM vtiger_crmentity WHERE crmid = ? AND deleted = 1", array($id));
        if (0 < $adb->num_rows($result)) {
            return true;
        }
        return false;
    }
}

?>