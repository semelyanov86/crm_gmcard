<?php

class VReports_History_Model extends Vtiger_Module_Model
{
    public function getHistory($pagingModel, $type = "", $userId = "", $dateFilter = "", $group_and_sort = "")
    {
        if (!$userId || $userId == "undefined") {
            $userId = "all";
        }
        if (!$type || $type == "undefined") {
            $type = "all";
        }
        $comments = array();
        if ($type == "all" || $type == "comments") {
            $modCommentsModel = Vtiger_Module_Model::getInstance("ModComments");
            if ($modCommentsModel->isPermitted("DetailView") || $group_and_sort != "") {
                $comments = $this->getComments($pagingModel, $userId, $dateFilter, $group_and_sort);
            }
            if ($type == "comments") {
                return $comments;
            }
        }
        $db = PearDatabase::getInstance();
        $params = array();
        $sql = "SELECT vtiger_modtracker_basic.*\r\n\t\t\t\tFROM vtiger_modtracker_basic\r\n\t\t\t\tINNER JOIN vtiger_crmentity ON vtiger_modtracker_basic.crmid = vtiger_crmentity.crmid\r\n\t\t\t\tAND module NOT IN (\"ModComments\",\"Users\")  ";
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if ($userId === "all") {
            if (!$currentUser->isAdminUser()) {
                $accessibleUsers = array_keys($currentUser->getAccessibleUsers());
                $sql .= " AND whodid IN (" . generateQuestionMarks($accessibleUsers) . ")";
                $params = array_merge($params, $accessibleUsers);
            }
        } else {
            $sql .= " AND whodid = ?";
            $params[] = $userId;
        }
        if (!empty($dateFilter)) {
            $sql .= " AND vtiger_modtracker_basic.changedon BETWEEN ? AND ? ";
            $params[] = $dateFilter["start"];
            $params[] = $dateFilter["end"];
        }
        if ($group_and_sort == "1") {
            $sql .= " ORDER BY DATE_FORMAT(vtiger_modtracker_basic.changedon,\"%y-%m-%d\") DESC,\r\n\t                  vtiger_modtracker_basic.crmid,vtiger_modtracker_basic.changedon DESC ";
        } else {
            $sql .= " ORDER BY vtiger_modtracker_basic.id DESC";
        }
        $sql .= " LIMIT ?, ?";
        $params[] = $pagingModel->getStartIndex();
        $params[] = $pagingModel->getPageLimit();
        $result = $db->pquery($sql, $params);
        $activites = array();
        $noOfRows = $db->num_rows($result);
        if ($pagingModel->get("historycount") < $noOfRows) {
            $pagingModel->set("historycount", $noOfRows);
        }
        for ($i = 0; $i < $noOfRows; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            $moduleName = $row["module"];
            $recordId = $row["crmid"];
            if (Users_Privileges_Model::isPermitted($moduleName, "DetailView", $recordId)) {
                $modTrackerRecorModel = new ModTracker_Record_Model();
                $modTrackerRecorModel->setData($row)->setParent($recordId, $moduleName);
                $activites[] = $modTrackerRecorModel;
            }
        }
        $history = array_merge($activites, $comments);
        $dateTime = array();
        $dateArray = array();
        $crmidArray = array();
        foreach ($history as $model) {
            if (get_class($model) == "ModComments_Record_Model") {
                $time = $model->get("createdtime");
                $date = date("Y-m-d", strtotime($time));
                $crmid = $model->get("parentid");
            } else {
                $time = $model->get("changedon");
                $date = date("Y-m-d", strtotime($time));
                $crmid = $model->get("crmid");
            }
            $dateTime[] = $time;
            $dateArray[] = $date;
            $crmidArray[] = $crmid;
        }
        if (!empty($history)) {
            if ($group_and_sort == "1") {
                array_multisort($dateArray, SORT_DESC, SORT_STRING, $crmidArray, SORT_NUMERIC, $dateTime, SORT_DESC, SORT_STRING, $history);
            } else {
                array_multisort($dateTime, SORT_DESC, SORT_STRING, $history);
            }
            return $history;
        }
        return false;
    }
    public function getComments($pagingModel, $user, $dateFilter = "", $group_and_sort)
    {
        $db = PearDatabase::getInstance();
        $sql = "SELECT vtiger_modcomments.*,vtiger_crmentity.setype AS setype,vtiger_crmentity.createdtime AS createdtime, vtiger_crmentity.smownerid AS smownerid,\r\n\t\t\t\tcrmentity2.crmid AS parentId, crmentity2.setype AS parentModule FROM vtiger_modcomments\r\n\t\t\t\tINNER JOIN vtiger_crmentity ON vtiger_modcomments.modcommentsid = vtiger_crmentity.crmid\r\n\t\t\t\tAND vtiger_crmentity.deleted = 0\r\n\t\t\t\tINNER JOIN vtiger_crmentity crmentity2 ON vtiger_modcomments.related_to = crmentity2.crmid\r\n\t\t\t\tAND crmentity2.deleted = 0 \r\n\t\t\t\tINNER JOIN vtiger_modtracker_basic ON vtiger_modtracker_basic.crmid = vtiger_crmentity.crmid";
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $params = array();
        if ($user === "all") {
            if (!$currentUser->isAdminUser()) {
                $accessibleUsers = array_keys($currentUser->getAccessibleUsers());
                $nonAdminAccessQuery = Users_Privileges_Model::getNonAdminAccessControlQuery("ModComments");
                $sql .= $nonAdminAccessQuery;
                $sql .= " AND userid IN(" . generateQuestionMarks($accessibleUsers) . ")";
                $params = array_merge($params, $accessibleUsers);
            }
        } else {
            $sql .= " AND userid = ?";
            $params[] = $user;
        }
        $sql .= " GROUP BY vtiger_modtracker_basic.crmid";
        if (!empty($dateFilter)) {
            $sql .= " AND vtiger_modtracker_basic.changedon BETWEEN ? AND ? ";
            $params[] = $dateFilter["start"];
            $params[] = $dateFilter["end"];
        }
        if ($group_and_sort == "1") {
            $sql .= " ORDER BY DATE_FORMAT(vtiger_modtracker_basic.changedon,\"%y-%m-%d\") DESC,\r\n\t                  vtiger_modtracker_basic.crmid,vtiger_modtracker_basic.changedon DESC";
        } else {
            $sql .= " ORDER BY vtiger_crmentity.crmid DESC";
        }
        $sql .= " LIMIT ?, ?";
        $params[] = $pagingModel->getStartIndex();
        $params[] = $pagingModel->getPageLimit();
        $result = $db->pquery($sql, $params);
        $noOfRows = $db->num_rows($result);
        $pagingModel->set("historycount", $noOfRows);
        $comments = array();
        for ($i = 0; $i < $noOfRows; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            if (Users_Privileges_Model::isPermitted($row["setype"], "DetailView", $row["related_to"])) {
                $commentModel = Vtiger_Record_Model::getCleanInstance("ModComments");
                $commentModel->setData($row);
                $commentcontent_parsed = self::checkColumnExist("vtiger_modcomments", "commentcontent_parsed");
                if ($commentcontent_parsed && $row["parentmodule"] == "HelpDesk") {
                    $commentModel->set("commentcontent", $commentModel->get("commentcontent_parsed"));
                } else {
                    $commentModel->set("commentcontent", $commentModel->getParsedContent());
                }
                $comments[] = $commentModel;
            }
        }
        return $comments;
    }
    public static function getIdForComment($data)
    {
        $db = PearDatabase::getInstance();
        $query = $db->pquery("SELECT vtiger_modtracker_basic.id FROM vtiger_modtracker_basic INNER JOIN \r\n              vtiger_modtracker_relations ON vtiger_modtracker_basic.crmid =  vtiger_modtracker_relations.targetid\r\n              WHERE vtiger_modtracker_basic.crmid = ?", array($data->get("modcommentsid")));
        return $db->query_result($query, 0, 0);
    }
    public static function checkColumnExist($tableName, $columnName)
    {
        global $adb;
        $sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = ? AND table_name = ? AND column_name = ?";
        $res = $adb->pquery($sql, array($adb->dbName, $tableName, $columnName));
        if (0 < $adb->num_rows($res)) {
            return true;
        }
        return false;
    }
    public static function runQueryForHistory($column)
    {
        $sql = "SELECT " . $column . " FROM vtiger_modtracker_basic INNER JOIN vtiger_emaildetails ON\r\n                                    vtiger_modtracker_basic.crmid = vtiger_emaildetails.emailid INNER JOIN vtiger_crmentity ON\r\n                                    vtiger_modtracker_basic.crmid = vtiger_crmentity.crmid WHERE vtiger_modtracker_basic.id = ?";
        return $sql;
    }
    public static function getModuleNameForHistory($relId)
    {
        $db = PearDatabase::getInstance();
        $result = trim($relId, "|");
        $modId = explode("@", $result);
        $modId = $modId[0];
        $query2 = $db->pquery("SELECT * FROM vtiger_crmentity WHERE crmid = ?", array($modId));
        return $db->query_result($query2, 0, "setype");
    }
    public static function getRelatedRecordForHistory($data)
    {
        $db = PearDatabase::getInstance();
        $countEmailBodyPlain = self::checkColumnExist("vtiger_emaildetails", "email_body_plain");
        $sql = self::runQueryForHistory("vtiger_emaildetails.idlists");
        $query = $db->pquery($sql, array($data->get("id")));
        $result = $db->query_result($query, 0, 0);
        if ($result) {
            $result = trim($result, "|");
            $modId = explode("@", $result);
            $modId = $modId[0];
            $moduleName = self::getModuleNameForHistory($result);
            $recordModel = Vtiger_Record_Model::getInstanceById($modId, $moduleName);
            $relation["data"] = "relation";
            $relation["label"] = $recordModel->get("label");
            $relation["id"] = $recordModel->get("id");
            $relation["url"] = $recordModel->getDetailViewUrl();
            $relation["module"] = $moduleName;
            if ($countEmailBodyPlain) {
                $sql2 = self::runQueryForHistory("vtiger_emaildetails.email_body_plain");
                $query3 = $db->pquery($sql2, array($data->get("id")));
                $relation["email_body_plain"] = $db->query_result($query3, 0, "email_body_plain");
            }
            return $relation;
        }
        $result = $db->pquery("SELECT * FROM vtiger_modtracker_relations WHERE id = ?", array($data->get("id")));
        $row = $db->query_result_rowdata($result, 0);
        $relationInstance = new ModTracker_Relation_Model();
        $relationInstance->setData($row)->setParent($data);
        $sql3 = self::runQueryForHistory("vtiger_emaildetails.to_email");
        $query3 = $db->pquery($sql3, array($data->get("id")));
        $emailList = "";
        $relation["from_email"] = "";
        while ($row_email = $db->fetch_array($query3)) {
            foreach (json_decode(html_entity_decode($row_email["to_email"])) as $item => $value) {
                $emailList .= $value . " , ";
            }
        }
        $relation["from_email"] .= trim($emailList, ",");
        if ($countEmailBodyPlain) {
            $sql4 = self::runQueryForHistory("vtiger_emaildetails.email_body_plain");
            $query4 = $db->pquery($sql4, array($data->get("id")));
            $relation["email_body_plain"] = $db->query_result($query4, 0, "email_body_plain");
        }
        return $relation;
    }
}

?>