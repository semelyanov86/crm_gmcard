<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

/**
 * CustomView Record Model Class.
 */
class CustomView_Record_Model extends Vtiger_Base_Model
{
    // Constants to identify different status of the custom view
    public const CV_STATUS_DEFAULT = 0;
    public const CV_STATUS_PRIVATE = 1;
    public const CV_STATUS_PENDING = 2;
    public const CV_STATUS_PUBLIC = 3;

    private $members = false;

    /**
     * Function to get the Id.
     * @return <Number> Custom View Id
     */
    public function getId()
    {
        return $this->get('cvid');
    }

    /**
     * Function to get the Owner Id.
     * @return <Number> Id of the User who created the Custom View
     */
    public function getOwnerId()
    {
        return $this->get('userid');
    }

    /**
     * Function to get the Owner Name.
     * @return <String> Custom View creator User Name
     */
    public function getOwnerName()
    {
        $ownerId = $this->getOwnerId();
        $entityNames = getEntityName('Users', [$ownerId]);

        return $entityNames[$ownerId];
    }

    /**
     * Function to get the Module to which the record belongs.
     * @return Vtiger_Module_Model
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Function to set the Module to which the record belongs.
     * @param <String> $moduleName
     * @return Vtiger_Record_Model or Module Specific Record Model instance
     */
    public function setModule($moduleName)
    {
        $this->module = Vtiger_Module_Model::getInstance($moduleName);

        return $this;
    }

    /**
     * Function to set the Module to which the record belongs from the Module model instance.
     * @param <Vtiger_Module_Model> $module
     * @return Vtiger_Record_Model or Module Specific Record Model instance
     */
    public function setModuleFromInstance($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Function to check if the view is marked as default.
     * @return <Boolean> true/false
     */
    public function isDefault()
    {
        $db = PearDatabase::getInstance();
        $userPrivilegeModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $userId = $userPrivilegeModel->getId();
        $moduleId = $this->getModule()->getId();
        $cvId = Vtiger_Cache::get('UserDefaultCustomView', $userId . '-' . $moduleId);
        if (!$cvId && !is_null($cvId)) {
            $cvId = null;
            $result = $db->pquery('SELECT default_cvid FROM vtiger_user_module_preferences WHERE userid = ? AND tabid = ?', [$userId, $moduleId]);
            if ($db->num_rows($result)) {
                $cvId = $db->query_result($result, 0, 'default_cvid');
            }
            Vtiger_Cache::set('UserDefaultCustomView', $userId . '-' . $moduleId, $cvId);
            if ($cvId === $this->getId()) {
                return true;
            }

            return false;

        }

        return $this->get('setdefault') == 1;
    }

    /**
     * Function to check if the view is created by the current user or is default view.
     * @return <Boolean> true/false
     */
    public function isMine()
    {
        $userPrivilegeModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

        return $this->get('status') == self::CV_STATUS_DEFAULT || $this->get('userid') == $userPrivilegeModel->getId();
    }

    /**
     * Function to check if the view is approved to be Public.
     * @return <Boolean> true/false
     */
    public function isPublic()
    {
        return !$this->isMine() && $this->get('status') == self::CV_STATUS_PUBLIC;
    }

    /**
     * Function to check if the view is marked as Private.
     * @return <Boolean> true/false
     */
    public function isPrivate()
    {
        return $this->get('status') == self::CV_STATUS_PRIVATE;
    }

    /**
     * Function to check if the view is requested to be Public and is awaiting for Approval.
     * @return <Boolean> true/false
     */
    public function isPending()
    {
        return !$this->isMine() && $this->get('status') == self::CV_STATUS_PENDING;
    }

    /**
     * Function to check if the view is created by one of the users, who is below the current user in the role hierarchy.
     * @return <Boolean> true/false
     */
    public function isOthers()
    {
        return !$this->isMine() && $this->get('status') != self::CV_STATUS_PUBLIC;
    }

    /**
     * Function which checks if a view is set to Public by the user which may/may not be approved.
     * @return <Boolean> true/false
     */
    public function isSetPublic()
    {
        return $this->get('status') == self::CV_STATUS_PUBLIC || $this->get('status') == self::CV_STATUS_PENDING;
    }

    public function isCvEditable()
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if ($this->get('viewname') == 'All' && !$currentUser->isAdminUser()) {
            return false;
        }
        if ($currentUser->isAdminUser() || $this->isMine()) {
            return true;
        }

        return false;
    }

    public function isEditable()
    {
        if ($this->get('viewname') == 'All') {
            return false;
        }
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if ($currentUser->isAdminUser()) {
            return true;
        }

        if ($this->isMine() || $this->isOthers()) {
            return true;
        }

        return false;
    }

    public function isDeletable()
    {
        return $this->isEditable();
    }

    /**
     * Function which provides the records for the current view.
     * @param <Boolean> $skipRecords - List of the RecordIds to be skipped
     * @return <Array> List of RecordsIds
     */
    public function getRecordIds($skipRecords = false, $module = false)
    {
        $db = PearDatabase::getInstance();
        $cvId = $this->getId();
        $moduleModel = $this->getModule();
        $moduleName = $moduleModel->get('name');
        $baseTableName = $moduleModel->get('basetable');
        $baseTableId = $moduleModel->get('basetableid');

        $listViewModel = Vtiger_ListView_Model::getInstance($moduleName, $cvId);
        $queryGenerator = $listViewModel->get('query_generator');

        $searchKey = $this->get('search_key');
        $searchValue = $this->get('search_value');
        $operator = $this->get('operator');
        if (!empty($searchValue)) {
            $queryGenerator->addUserSearchConditions(['search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator]);
        }

        /**
         *  For Documents if we select any document folder and mass deleted it should delete documents related to that
         *  particular folder only.
         */
        if ($moduleName == 'Documents') {
            $folderValue = $this->get('folder_value');
            if (!empty($folderValue)) {
                $queryGenerator->addCondition($this->get('folder_id'), $folderValue, 'e');
            }
        }

        $searchParams = $this->get('search_params');
        if (empty($searchParams)) {
            $searchParams = [];
        }
        $transformedSearchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParams, $moduleModel);
        $glue = '';
        if (php7_count($queryGenerator->getWhereFields()) > 0 && php7_count($transformedSearchParams) > 0) {
            $glue = QueryGenerator::$AND;
        }
        $queryGenerator->parseAdvFilterList($transformedSearchParams, $glue);

        $listQuery = $queryGenerator->getQuery();
        $params = [];

        if ($module == 'RecycleBin') {
            $listQuery = preg_replace('/vtiger_crmentity.deleted\\s*=\\s*0/i', 'vtiger_crmentity.deleted = 1', $listQuery);
        }

        if ($skipRecords && !empty($skipRecords) && is_array($skipRecords) && php7_count($skipRecords) > 0) {
            $listQuery .= ' AND ' . $baseTableName . '.' . $baseTableId . ' NOT IN (' . generateQuestionMarks($skipRecords) . ')';
            $params = [$skipRecords];
        }
        $result = $db->pquery($listQuery, $params);
        $noOfRecords = $db->num_rows($result);
        $recordIds = [];
        for ($i = 0; $i < $noOfRecords; ++$i) {
            $recordIds[] = $db->query_result($result, $i, $baseTableId);
        }

        return $recordIds;
    }

    /**
     * Function to save the custom view record.
     */
    public function save($partial = false)
    {
        $db = PearDatabase::getInstance();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();

        $cvId = $this->getId();
        $moduleModel = $this->getModule();
        $moduleName = $moduleModel->get('name');
        $viewName = decode_html($this->get('viewname'));
        $setDefault = $this->get('setdefault');
        $setMetrics = $this->get('setmetrics');
        $status = $this->get('status');

        if ($status == self::CV_STATUS_PENDING) {
            if ($currentUserModel->isAdminUser()) {
                $status = self::CV_STATUS_PUBLIC;
            }
        }

        if (!$cvId) {
            $cvId = $db->getUniqueID('vtiger_customview');
            $this->set('cvid', $cvId);

            $sql = 'INSERT INTO vtiger_customview(cvid, viewname, setdefault, setmetrics, entitytype, status, userid) VALUES (?,?,?,?,?,?,?)';
            $params = [$cvId, $viewName, $setDefault, $setMetrics, $moduleName, $status, $currentUserModel->getId()];
            $db->pquery($sql, $params);

        } else {

            $sql = 'UPDATE vtiger_customview SET viewname=?, setdefault=?, setmetrics=?, status=? WHERE cvid=?';
            $params = [$viewName, $setDefault, $setMetrics, $status, $cvId];
            $db->pquery($sql, $params);

            if (!$partial) {
                $db->pquery('DELETE FROM vtiger_cvcolumnlist WHERE cvid = ?', [$cvId]);
                $db->pquery('DELETE FROM vtiger_cvstdfilter WHERE cvid = ?', [$cvId]);
                $db->pquery('DELETE FROM vtiger_cvadvfilter WHERE cvid = ?', [$cvId]);
                $db->pquery('DELETE FROM vtiger_cvadvfilter_grouping WHERE cvid = ?', [$cvId]);
            }
        }

        if ($setDefault == 1) {
            $query = 'SELECT 1 FROM vtiger_user_module_preferences WHERE userid = ? AND tabid = ?';
            $queryParams = [$currentUserModel->getId(), $moduleModel->getId()];
            $queryResult = $db->pquery($query, $queryParams);
            if ($db->num_rows($queryResult) > 0) {
                $updateSql = 'UPDATE vtiger_user_module_preferences SET default_cvid = ? WHERE userid = ? AND tabid = ?';
                $updateParams = [$cvId, $currentUserModel->getId(), $moduleModel->getId()];
                $db->pquery($updateSql, $updateParams);
            } else {
                $insertSql = 'INSERT INTO vtiger_user_module_preferences(userid, tabid, default_cvid) VALUES (?,?,?)';
                $insertParams = [$currentUserModel->getId(), $moduleModel->getId(), $cvId];
                $db->pquery($insertSql, $insertParams);
            }
        } else {
            $deleteSql = 'DELETE FROM vtiger_user_module_preferences WHERE userid = ? AND tabid = ? AND default_cvid = ?';
            $deleteParams = [$currentUserModel->getId(), $moduleModel->getId(), $cvId];
            $db->pquery($deleteSql, $deleteParams);
        }

        $selectedColumnsList = $this->get('columnslist');
        if (!empty($selectedColumnsList)) {
            $noOfColumns = php7_count($selectedColumnsList);
            for ($i = 0; $i < $noOfColumns; ++$i) {
                $columnSql = 'INSERT INTO vtiger_cvcolumnlist (cvid, columnindex, columnname) VALUES (?,?,?)';
                $columnParams = [$cvId, $i, $selectedColumnsList[$i]];
                $db->pquery($columnSql, $columnParams);
            }
        } elseif (!$partial) {
            // no fields were sent so add default All filter columns
            $defaultModuleFilter = $db->pquery(
                'SELECT cvid FROM vtiger_customview WHERE setdefault = 1 AND entitytype = ?',
                [$moduleName],
            );
            $defaultViewId = $db->query_result($defaultModuleFilter, 0, 'cvid');

            // User Specific filterId
            if (empty($defaultViewId)) {
                $userDefaultModuleFilter = $db->pquery('SELECT default_cvid FROM vtiger_user_module_preferences WHERE
											userid = ? AND tabid = ?', [$currentUserModel->id, $moduleModel->getId()]);
                $defaultViewId = $db->query_result($userDefaultModuleFilter, 0, 'default_cvid');
            }

            // First filterid of module
            if (empty($defaultViewId)) {
                $firstDefaultFilter = $db->pquery('SELECT cvid FROM vtiger_customview WHERE entitytype = ?', [$moduleName]);
                $defaultViewId = $db->query_result($firstDefaultFilter, 0, 'cvid');
            }

            // Get the defaults filters columnlist
            $columnSql = 'INSERT INTO vtiger_cvcolumnlist (cvid, columnindex, columnname)
							SELECT ?, columnindex, columnname FROM vtiger_cvcolumnlist WHERE cvid = ?';
            $db->pquery($columnSql, [$cvId, $defaultViewId]);
        }

        $stdFilterList = $this->get('stdfilterlist');
        if (!empty($stdFilterList) && !empty($stdFilterList['columnname'])) {
            $stdFilterSql = 'INSERT INTO vtiger_cvstdfilter(cvid,columnname,stdfilter,startdate,enddate) VALUES (?,?,?,?,?)';
            $stdFilterParams = [$cvId, $stdFilterList['columnname'], $stdFilterList['stdfilter'],
                $db->formatDate($stdFilterList['startdate'], true),
                $db->formatDate($stdFilterList['enddate'], true)];
            $db->pquery($stdFilterSql, $stdFilterParams);
        }

        $advFilterList = $this->get('advfilterlist');
        if (!empty($advFilterList)) {
            foreach ($advFilterList as $groupIndex => $groupInfo) {
                if (empty($groupInfo)) {
                    continue;
                }

                $groupColumns = $groupInfo['columns'];
                $groupCondition = $groupInfo['condition'] ?? null;

                foreach ($groupColumns as $columnIndex => $columnCondition) {
                    if (empty($columnCondition)) {
                        continue;
                    }
                    $referenceParentField = $referenceModule = $referenceFieldName = '';

                    $advFilterColumn = $columnCondition['columnname'];
                    $advFilterComparator = $columnCondition['comparator'];
                    $advFitlerValue = $columnCondition['value'];
                    $advFilterColumnCondition = $columnCondition['column_condition'];

                    $columnInfo = explode(':', $advFilterColumn);
                    $fieldName = $columnInfo[2];
                    preg_match('/(\w+) ; \((\w+)\) (\w+)/', $fieldName, $matches);
                    if (php7_count($matches) != 0) {
                        [$full, $referenceParentField, $referenceModule, $referenceFieldName] = $matches;
                    }
                    if ($referenceParentField) {
                        $referenceModuleModel = Vtiger_Module_Model::getInstance($referenceModule);
                        $fieldModel = $referenceModuleModel->getField($referenceFieldName);
                    } else {
                        $fieldModel = $moduleModel->getField($fieldName);
                    }
                    // Required if Events module fields are selected for the condition
                    if (!$fieldModel) {
                        $modulename = $moduleModel->get('name');
                        if ($modulename == 'Calendar') {
                            $eventModuleModel = Vtiger_Module_model::getInstance('Events');
                            $fieldModel = $eventModuleModel->getField($fieldName);
                        }
                    }
                    $fieldType = $fieldModel->getFieldDataType();

                    if ($fieldType == 'currency') {
                        if ($fieldModel->get('uitype') == '72') {
                            // Some of the currency fields like Unit Price, Totoal , Sub-total - doesn't need currency conversion during save
                            $advFitlerValue = CurrencyField::convertToDBFormat($advFitlerValue, null, true);
                        } else {
                            $advFitlerValue = CurrencyField::convertToDBFormat($advFitlerValue);
                        }
                    }

                    $temp_val = explode(',', $advFitlerValue);
                    $specialDateConditions = Vtiger_Functions::getSpecialDateTimeCondtions();
                    if (($fieldType == 'date' || ($fieldType == 'time' && $fieldName != 'time_start' && $fieldName != 'time_end') || ($fieldType == 'datetime')) && ($fieldType != '' && $advFitlerValue != '') && !in_array($advFilterComparator, $specialDateConditions)) {
                        $val = [];
                        for ($x = 0; $x < php7_count($temp_val); ++$x) {
                            // if date and time given then we have to convert the date and
                            // leave the time as it is, if date only given then temp_time
                            // value will be empty
                            if (trim($temp_val[$x]) != '') {
                                $date = new DateTimeField(trim($temp_val[$x]));
                                if ($fieldType == 'date') {
                                    $val[$x] = DateTimeField::convertToDBFormat(
                                        trim($temp_val[$x]),
                                    );
                                } elseif ($fieldType == 'datetime') {
                                    if ($advFilterComparator == 'bw'  || $advFilterComparator == 'custom') {
                                        $dates = explode(' ', $temp_val[$x]);
                                        if (empty($dates[1])) {
                                            if ($x == '0') {
                                                $filterValue = trim($temp_val[$x]) . ' 00:00:00';
                                            } elseif ($x == '1') {
                                                $filterValue = trim($temp_val[$x]) . ' 23:59:59';
                                            }
                                        }
                                        $date = new DateTimeField($filterValue);
                                    }
                                    $val[$x] = $date->getDBInsertDateTimeValue();
                                } else {
                                    $val[$x] = $date->getDBInsertTimeValue();
                                }
                            }
                        }
                        $advFitlerValue = implode(',', $val);
                    }

                    $advCriteriaSql = 'INSERT INTO vtiger_cvadvfilter(cvid,columnindex,columnname,comparator,value,groupid,column_condition)
											values (?,?,?,?,?,?,?)';
                    $advCriteriaParams = [$cvId, $columnIndex, $advFilterColumn, $advFilterComparator, $advFitlerValue, $groupIndex, $advFilterColumnCondition];
                    $db->pquery($advCriteriaSql, $advCriteriaParams);

                    // Update the condition expression for the group to which the condition column belongs
                    $groupConditionExpression = '';
                    if (!empty($advFilterList[$groupIndex]['conditionexpression'])) {
                        $groupConditionExpression = $advFilterList[$groupIndex]['conditionexpression'];
                    }
                    $groupConditionExpression = $groupConditionExpression . ' ' . $columnIndex . ' ' . $advFilterColumnCondition;
                    $advFilterList[$groupIndex]['conditionexpression'] = $groupConditionExpression;
                }

                $groupConditionExpression = isset($advFilterList[$groupIndex]['conditionexpression']);
                if (empty($groupConditionExpression)) {
                    continue;
                } // Case when the group doesn't have any column criteria

                $advGroupSql = 'INSERT INTO vtiger_cvadvfilter_grouping(groupid,cvid,group_condition,condition_expression) VALUES (?,?,?,?)';
                $advGroupParams = [$groupIndex, $cvId, $groupCondition, $groupConditionExpression];
                $db->pquery($advGroupSql, $advGroupParams);
            }
        }
        if ($this->has('sharelist') && !$partial) {
            $db->pquery('DELETE FROM vtiger_cv2users WHERE cvid=?', [$cvId]);
            $db->pquery('DELETE FROM vtiger_cv2group WHERE cvid=?', [$cvId]);
            $db->pquery('DELETE FROM vtiger_cv2role WHERE cvid=?', [$cvId]);
            $db->pquery('DELETE FROM vtiger_cv2rs WHERE cvid=?', [$cvId]);
            $members = $this->get('members');
            if (!$members) {
                $members = [];
            }

            $noOfMembers = php7_count($members);
            for ($i = 0; $i < $noOfMembers; ++$i) {
                $id = $members[$i];
                $idComponents = Settings_Groups_Member_Model::getIdComponentsFromQualifiedId($id);
                if ($idComponents && php7_count($idComponents) == 2) {
                    $memberType = $idComponents[0];
                    $memberId = $idComponents[1];

                    if ($memberType == Settings_Groups_Member_Model::MEMBER_TYPE_USERS) {
                        $db->pquery('INSERT INTO vtiger_cv2users(userid, cvid) VALUES (?,?)', [$memberId, $cvId]);
                    }
                    if ($memberType == Settings_Groups_Member_Model::MEMBER_TYPE_GROUPS) {
                        $db->pquery('INSERT INTO vtiger_cv2group(groupid, cvid) VALUES (?,?)', [$memberId, $cvId]);
                    }
                    if ($memberType == Settings_Groups_Member_Model::MEMBER_TYPE_ROLES) {
                        $db->pquery('INSERT INTO vtiger_cv2role(roleid, cvid) VALUES (?,?)', [$memberId, $cvId]);
                    }
                    if ($memberType == Settings_Groups_Member_Model::MEMBER_TYPE_ROLE_AND_SUBORDINATES) {
                        $db->pquery('INSERT INTO vtiger_cv2rs(rsid, cvid) VALUES (?,?)', [$memberId, $cvId]);
                    }
                }
            }
        }
    }

    public function saveSelectedFields($selectedColumnsList)
    {
        if (!empty($selectedColumnsList)) {
            $db = PearDatabase::getInstance();
            $cvId = $this->getId();
            $noOfColumns = php7_count($selectedColumnsList);
            for ($i = 0; $i < $noOfColumns; ++$i) {
                $columnSql = 'INSERT INTO vtiger_cvcolumnlist (cvid, columnindex, columnname) VALUES (?,?,?)';
                $columnParams = [$cvId, $i, $selectedColumnsList[$i]];
                $db->pquery($columnSql, $columnParams);
            }
        }
    }

    public function deleteSelectedFields()
    {
        $db = PearDatabase::getInstance();
        $cvId = $this->getId();
        $db->pquery('DELETE FROM vtiger_cvcolumnlist WHERE cvid = ?', [$cvId]);
    }

    /**
     * Function to delete the custom view record.
     */
    public function delete()
    {
        $db = PearDatabase::getInstance();
        $cvId = $this->getId();

        $db->pquery('DELETE FROM vtiger_customview WHERE cvid = ?', [$cvId]);
        $db->pquery('DELETE FROM vtiger_cvcolumnlist WHERE cvid = ?', [$cvId]);
        $db->pquery('DELETE FROM vtiger_cvstdfilter WHERE cvid = ?', [$cvId]);
        $db->pquery('DELETE FROM vtiger_cvadvfilter WHERE cvid = ?', [$cvId]);
        $db->pquery('DELETE FROM vtiger_cvadvfilter_grouping WHERE cvid = ?', [$cvId]);

        // To Delete the mini list widget associated with the filter
        $db->pquery('DELETE FROM vtiger_module_dashboard_widgets WHERE filterid = ?', [$cvId]);
    }

    /**
     * Function to get the list of selected fields for the current custom view.
     * @return <Array> List of Field Column Names
     */
    public function getSelectedFields()
    {
        $db = PearDatabase::getInstance();

        $query = 'SELECT vtiger_cvcolumnlist.* FROM vtiger_cvcolumnlist
					INNER JOIN vtiger_customview ON vtiger_customview.cvid = vtiger_cvcolumnlist.cvid
				WHERE vtiger_customview.cvid  = ? ORDER BY vtiger_cvcolumnlist.columnindex';
        $params = [$this->getId()];

        $result = $db->pquery($query, $params);
        $noOfFields = $db->num_rows($result);
        $selectedFields = [];
        for ($i = 0; $i < $noOfFields; ++$i) {
            $columnIndex = $db->query_result($result, $i, 'columnindex');
            $columnName = $db->query_result($result, $i, 'columnname');
            $selectedFields[$columnIndex] = decode_html($columnName);
        }

        return $selectedFields;
    }

    /**
     * Function to get the Standard filter condition for the current custom view.
     * @return <Array> Standard filter condition
     */
    public function getStandardCriteria()
    {
        $db = PearDatabase::getInstance();
        $stdfilterlist = [];
        $cvId = $this->getId();
        if (empty($cvId)) {
            return [];
        }

        $query = 'SELECT vtiger_cvstdfilter.* FROM vtiger_cvstdfilter
					INNER JOIN vtiger_customview ON vtiger_customview.cvid = vtiger_cvstdfilter.cvid
				WHERE vtiger_cvstdfilter.cvid = ?';
        $params = [$this->getId()];
        $result = $db->pquery($query, $params);
        $stdfilterrow = $db->fetch_array($result);
        if (!empty($stdfilterrow)) {
            $stdfilterlist = [];
            $stdfilterlist['columnname'] = $stdfilterrow['columnname'];
            $stdfilterlist['stdfilter'] = $stdfilterrow['stdfilter'];

            if ($stdfilterrow['stdfilter'] == 'custom' || $stdfilterrow['stdfilter'] == '') {
                if ($stdfilterrow['startdate'] != '0000-00-00' && $stdfilterrow['startdate'] != '') {
                    $startDateTime = new DateTimeField($stdfilterrow['startdate'] . ' ' . date('H:i:s'));
                    $stdfilterlist['startdate'] = $startDateTime->getDisplayDate();
                }
                if ($stdfilterrow['enddate'] != '0000-00-00' && $stdfilterrow['enddate'] != '') {
                    $endDateTime = new DateTimeField($stdfilterrow['enddate'] . ' ' . date('H:i:s'));
                    $stdfilterlist['enddate'] = $endDateTime->getDisplayDate();
                }
            } else { // if it is not custom get the date according to the selected duration
                $datefilter = self::getDateForStdFilterBytype($stdfilterrow['stdfilter']);
                $startDateTime = new DateTimeField($datefilter[0] . ' ' . date('H:i:s'));
                $stdfilterlist['startdate'] = $startDateTime->getDisplayDate();
                $endDateTime = new DateTimeField($datefilter[1] . ' ' . date('H:i:s'));
                $stdfilterlist['enddate'] = $endDateTime->getDisplayDate();
            }
        }

        return $stdfilterlist;
    }

    /**
     * Function to get the list of advanced filter conditions for the current custom view.
     * @return <Array> - All the advanced filter conditions for the custom view, grouped by the condition grouping
     */
    public function getAdvancedCriteria()
    {
        $db = PearDatabase::getInstance();
        $default_charset = vglobal('default_charset');

        $cvId = $this->getId();
        $advft_criteria = [];
        if (empty($cvId)) {
            return $advft_criteria;
        }

        $sql = 'SELECT * FROM vtiger_cvadvfilter_grouping WHERE cvid = ? ORDER BY groupid';
        $groupsresult = $db->pquery($sql, [$this->getId()]);

        $i = 1;
        $j = 0;

        while ($relcriteriagroup = $db->fetch_array($groupsresult)) {
            $groupId = $relcriteriagroup['groupid'];
            $groupCondition = $relcriteriagroup['group_condition'];

            $ssql = 'select vtiger_cvadvfilter.* from vtiger_customview
						inner join vtiger_cvadvfilter on vtiger_cvadvfilter.cvid = vtiger_customview.cvid
						left join vtiger_cvadvfilter_grouping on vtiger_cvadvfilter.cvid = vtiger_cvadvfilter_grouping.cvid
								and vtiger_cvadvfilter.groupid = vtiger_cvadvfilter_grouping.groupid';
            $ssql .= ' where vtiger_customview.cvid = ? AND vtiger_cvadvfilter.groupid = ? order by vtiger_cvadvfilter.columnindex';

            $result = $db->pquery($ssql, [$this->getId(), $groupId]);
            $noOfColumns = $db->num_rows($result);
            if ($noOfColumns <= 0) {
                continue;
            }

            while ($relcriteriarow = $db->fetch_array($result)) {
                $criteria = [];
                $criteria['columnname'] = html_entity_decode($relcriteriarow['columnname'], ENT_QUOTES, $default_charset);
                $criteria['comparator'] = $relcriteriarow['comparator'];
                $advfilterval = html_entity_decode($relcriteriarow['value'], ENT_QUOTES, $default_charset);
                $col = explode(':', $relcriteriarow['columnname']);
                $temp_val = explode(',', $relcriteriarow['value']);

                $advFilterColumn = $criteria['columnname'];
                $advFilterComparator = $criteria['comparator'];
                $advFilterColumnCondition = $criteria['column_condition'];

                $columnInfo = explode(':', $advFilterColumn);
                $fieldName = $columnInfo[2];
                $moduleModel = $this->getModule();
                $moduleName = $moduleModel->get('name');
                preg_match('/(\w+) ; \((\w+)\) (\w+)/', $fieldName, $matches);
                if (php7_count($matches) != 0) {
                    [$full, $referenceParentField, $referenceModule, $referenceFieldName] = $matches;
                }
                if ($referenceParentField) {
                    $referenceModuleModel = Vtiger_Module_Model::getInstance($referenceModule);
                    $fieldModel = $referenceModuleModel->getField($referenceFieldName);
                } else {
                    $fieldModel = $moduleModel->getField($fieldName);
                }
                // Required if Events module fields are selected for the condition
                if (!$fieldModel) {
                    $modulename = $moduleModel->get('name');
                    if ($modulename == 'Calendar') {
                        $eventModuleModel = Vtiger_Module_model::getInstance('Events');
                        $fieldModel = $eventModuleModel->getField($fieldName);
                    }
                }
                $fieldType = $fieldModel->getFieldDataType();

                if ($fieldType == 'currency') {
                    if ($fieldModel->get('uitype') == '72') {
                        // Some of the currency fields like Unit Price, Totoal , Sub-total - doesn't need currency conversion during save
                        $advfilterval = CurrencyField::convertToUserFormat($advfilterval, null, true);
                    } else {
                        $advfilterval = CurrencyField::convertToUserFormat($advfilterval);
                    }
                }

                $specialDateConditions = Vtiger_Functions::getSpecialDateTimeCondtions();
                if (($col[4] == 'D' || ($col[4] == 'T' && $col[1] != 'time_start' && $col[1] != 'time_end') || ($col[4] == 'DT')) && !in_array($criteria['comparator'], $specialDateConditions)) {
                    $val = [];
                    for ($x = 0; $x < php7_count($temp_val); ++$x) {
                        if (empty($temp_val[$x])) {
                            $val[$x] = '';
                        } elseif ($col[4] == 'D') {
                            /** while inserting in db for due_date it was taking date and time values also as it is
                             * date time field. We only need to take date from that value.
                             */
                            if ($col[0] == 'vtiger_activity' && $col[1] == 'due_date') {
                                $originalValue = $temp_val[$x];
                                $dateTime = explode(' ', $originalValue);
                                $temp_val[$x] = $dateTime[0];
                            }
                            $date = new DateTimeField(trim($temp_val[$x]));
                            $val[$x] = $date->getDisplayDate();
                        } elseif ($col[4] == 'DT') {
                            $comparator = ['e', 'n', 'b', 'a'];
                            if (in_array($criteria['comparator'], $comparator)) {
                                $originalValue = $temp_val[$x];
                                $dateTime = explode(' ', $originalValue);
                                $temp_val[$x] = $dateTime[0];
                            }
                            $date = new DateTimeField(trim($temp_val[$x]));
                            $val[$x] = $date->getDisplayDateTimeValue();
                        } else {
                            $date = new DateTimeField(trim($temp_val[$x]));
                            $val[$x] = $date->getDisplayTime();
                        }
                    }
                    $advfilterval = implode(',', $val);
                }
                $criteria['value'] = Vtiger_Util_Helper::toSafeHTML(decode_html($advfilterval));
                $criteria['column_condition'] = $relcriteriarow['column_condition'];

                $groupId = $relcriteriarow['groupid'];
                $advft_criteria[$groupId]['columns'][$j] = $criteria;
                $advft_criteria[$groupId]['condition'] = $groupCondition;
                ++$j;
            }
            if (!empty($advft_criteria[$groupId]['columns'][$j - 1]['column_condition'])) {
                $advft_criteria[$groupId]['columns'][$j - 1]['column_condition'] = '';
            }
            ++$i;
        }
        // Clear the condition (and/or) for last group, if any.
        if (!empty($advft_criteria[$i - 1]['condition'])) {
            $advft_criteria[$i - 1]['condition'] = '';
        }

        return $advft_criteria;
    }

    /**
     * Function returns standard filter sql.
     * @return <String>
     */
    public function getCVStdFilterSQL()
    {
        $customView = new CustomView();

        return $customView->getCVStdFilterSQL($this->getId());
    }

    /**
     * Function returns Advanced filter sql.
     * @return <String>
     */
    public function getCVAdvFilterSQL()
    {
        $customView = new CustomView();

        return $customView->getCVAdvFilterSQL($this->getId());
    }

    /**
     * Function returns approve url.
     * @return string - approve url
     */
    public function getCreateUrl()
    {
        return 'index.php?module=CustomView&view=EditAjax&source_module=' . $this->getModule()->get('name');
    }

    public static function getCreateViewUrl($moduleName)
    {
        return 'index.php?module=CustomView&view=EditAjax&source_module=' . $moduleName;
    }

    /**
     * Function returns approve url.
     * @return string - approve url
     */
    public function getEditUrl()
    {
        return 'index.php?module=CustomView&view=EditAjax&source_module=' . $this->getModule()->get('name') . '&record=' . $this->getId();
    }

    public function getDuplicateUrl()
    {
        return 'index.php?module=CustomView&view=EditAjax&source_module=' . $this->getModule()->get('name') . '&source_viewname=' . $this->getId();
    }

    public function getToggleDefaultUrl()
    {
        return 'index.php?module=CustomView&source_module=' . $this->getModule()->get('name') . '&action=SaveAjax&record=' . $this->getId();
    }

    /**
     * Function returns approve url.
     * @return string - approve url
     */
    public function getApproveUrl()
    {
        return 'index.php?module=CustomView&action=Approve&sourceModule=' . $this->getModule()->get('name') . '&record=' . $this->getId();
    }

    /**
     * Function returns deny url.
     * @return string - deny url
     */
    public function getDenyUrl()
    {
        return 'index.php?module=CustomView&action=Deny&sourceModule=' . $this->getModule()->get('name') . '&record=' . $this->getId();
    }

    /**
     *  Functions returns delete url.
     * @return string - delete url
     */
    public function getDeleteUrl()
    {
        return 'index.php?module=CustomView&action=Delete&sourceModule=' . $this->getModule()->get('name') . '&record=' . $this->getId();
    }

    public function approve()
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'UPDATE vtiger_customview SET status = ? WHERE cvid = ?',
            [self::CV_STATUS_PUBLIC, $this->getId()],
        );
    }

    public function deny()
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'UPDATE vtiger_customview SET status = ? WHERE cvid = ?',
            [self::CV_STATUS_PRIVATE, $this->getId()],
        );
    }

    /**
     * Function to get the date values for the given type of Standard filter.
     * @param <String> $type
     * @return <Array> - 2 date values representing the range for the given type of Standard filter
     */
    protected static function getDateForStdFilterBytype($type)
    {
        $today = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
        $tomorrow = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')));
        $yesterday = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')));

        $currentmonth0 = date('Y-m-d', mktime(0, 0, 0, date('m'), '01', date('Y')));
        $currentmonth1 = date('Y-m-t');
        $lastmonth0 = date('Y-m-d', mktime(0, 0, 0, date('m') - 1, '01', date('Y')));
        $lastmonth1 = date('Y-m-t', strtotime('-1 Month'));
        $nextmonth0 = date('Y-m-d', mktime(0, 0, 0, date('m') + 1, '01', date('Y')));
        $nextmonth1 = date('Y-m-t', strtotime('+1 Month'));

        $lastweek0 = date('Y-m-d', strtotime('-2 week Sunday'));
        $lastweek1 = date('Y-m-d', strtotime('-1 week Saturday'));

        $thisweek0 = date('Y-m-d', strtotime('-1 week Sunday'));
        $thisweek1 = date('Y-m-d', strtotime('this Saturday'));

        $nextweek0 = date('Y-m-d', strtotime('this Sunday'));
        $nextweek1 = date('Y-m-d', strtotime('+1 week Saturday'));

        $next7days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 6, date('Y')));
        $next14days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 13, date('Y')));
        $next30days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 29, date('Y')));
        $next60days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 59, date('Y')));
        $next90days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 89, date('Y')));
        $next120days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 119, date('Y')));

        $last7days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 6, date('Y')));
        $last14days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 13, date('Y')));
        $last30days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 29, date('Y')));
        $last60days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 59, date('Y')));
        $last90days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 89, date('Y')));
        $last120days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 119, date('Y')));

        $currentFY0 = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y')));
        $currentFY1 = date('Y-m-t', mktime(0, 0, 0, '12', date('d'), date('Y')));
        $lastFY0 = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y') - 1));
        $lastFY1 = date('Y-m-t', mktime(0, 0, 0, '12', date('d'), date('Y') - 1));
        $nextFY0 = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y') + 1));
        $nextFY1 = date('Y-m-t', mktime(0, 0, 0, '12', date('d'), date('Y') + 1));

        if (date('m') <= 4) {
            $cFq = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y')));
            $cFq1 = date('Y-m-d', mktime(0, 0, 0, '04', '30', date('Y')));
            $nFq = date('Y-m-d', mktime(0, 0, 0, '05', '01', date('Y')));
            $nFq1 = date('Y-m-d', mktime(0, 0, 0, '08', '31', date('Y')));
            $pFq = date('Y-m-d', mktime(0, 0, 0, '09', '01', date('Y') - 1));
            $pFq1 = date('Y-m-d', mktime(0, 0, 0, '12', '31', date('Y') - 1));
        } elseif (date('m') > 4 and date('m') <= 8) {
            $pFq = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y')));
            $pFq1 = date('Y-m-d', mktime(0, 0, 0, '04', '30', date('Y')));
            $cFq = date('Y-m-d', mktime(0, 0, 0, '05', '01', date('Y')));
            $cFq1 = date('Y-m-d', mktime(0, 0, 0, '08', '31', date('Y')));
            $nFq = date('Y-m-d', mktime(0, 0, 0, '09', '01', date('Y')));
            $nFq1 = date('Y-m-d', mktime(0, 0, 0, '12', '31', date('Y')));
        } else {
            $nFq = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y') + 1));
            $nFq1 = date('Y-m-d', mktime(0, 0, 0, '04', '30', date('Y') + 1));
            $pFq = date('Y-m-d', mktime(0, 0, 0, '05', '01', date('Y')));
            $pFq1 = date('Y-m-d', mktime(0, 0, 0, '08', '31', date('Y')));
            $cFq = date('Y-m-d', mktime(0, 0, 0, '09', '01', date('Y')));
            $cFq1 = date('Y-m-d', mktime(0, 0, 0, '12', '31', date('Y')));
        }

        $dateValues = [];
        if ($type == 'today') {
            $dateValues[0] = $today;
            $dateValues[1] = $today;
        } elseif ($type == 'yesterday') {
            $dateValues[0] = $yesterday;
            $dateValues[1] = $yesterday;
        } elseif ($type == 'tomorrow') {
            $dateValues[0] = $tomorrow;
            $dateValues[1] = $tomorrow;
        } elseif ($type == 'thisweek') {
            $dateValues[0] = $thisweek0;
            $dateValues[1] = $thisweek1;
        } elseif ($type == 'lastweek') {
            $dateValues[0] = $lastweek0;
            $dateValues[1] = $lastweek1;
        } elseif ($type == 'nextweek') {
            $dateValues[0] = $nextweek0;
            $dateValues[1] = $nextweek1;
        } elseif ($type == 'thismonth') {
            $dateValues[0] = $currentmonth0;
            $dateValues[1] = $currentmonth1;
        } elseif ($type == 'lastmonth') {
            $dateValues[0] = $lastmonth0;
            $dateValues[1] = $lastmonth1;
        } elseif ($type == 'nextmonth') {
            $dateValues[0] = $nextmonth0;
            $dateValues[1] = $nextmonth1;
        } elseif ($type == 'next7days') {
            $dateValues[0] = $today;
            $dateValues[1] = $next7days;
        } elseif ($type == 'next14days') {
            $dateValues[0] = $today;
            $dateValues[1] = $next14days;
        } elseif ($type == 'next30days') {
            $dateValues[0] = $today;
            $dateValues[1] = $next30days;
        } elseif ($type == 'next60days') {
            $dateValues[0] = $today;
            $dateValues[1] = $next60days;
        } elseif ($type == 'next90days') {
            $dateValues[0] = $today;
            $dateValues[1] = $next90days;
        } elseif ($type == 'next120days') {
            $dateValues[0] = $today;
            $dateValues[1] = $next120days;
        } elseif ($type == 'last7days') {
            $dateValues[0] = $last7days;
            $dateValues[1] = $today;
        } elseif ($type == 'last14days') {
            $dateValues[0] = $last14days;
            $dateValues[1] = $today;
        } elseif ($type == 'last30days') {
            $dateValues[0] = $last30days;
            $dateValues[1] = $today;
        } elseif ($type == 'last60days') {
            $dateValues[0] = $last60days;
            $dateValues[1] = $today;
        } elseif ($type == 'last90days') {
            $dateValues[0] = $last90days;
            $dateValues[1] = $today;
        } elseif ($type == 'last120days') {
            $dateValues[0] = $last120days;
            $dateValues[1] = $today;
        } elseif ($type == 'thisfy') {
            $dateValues[0] = $currentFY0;
            $dateValues[1] = $currentFY1;
        } elseif ($type == 'prevfy') {
            $dateValues[0] = $lastFY0;
            $dateValues[1] = $lastFY1;
        } elseif ($type == 'nextfy') {
            $dateValues[0] = $nextFY0;
            $dateValues[1] = $nextFY1;
        } elseif ($type == 'nextfq') {
            $dateValues[0] = $nFq;
            $dateValues[1] = $nFq1;
        } elseif ($type == 'prevfq') {
            $dateValues[0] = $pFq;
            $dateValues[1] = $pFq1;
        } elseif ($type == 'thisfq') {
            $dateValues[0] = $cFq;
            $dateValues[1] = $cFq1;
        } else {
            $dateValues[0] = '';
            $dateValues[1] = '';
        }

        return $dateValues;
    }

    /**
     * Function to get all the date filter type informations.
     * @return <Array>
     */
    public static function getDateFilterTypes()
    {
        $dateFilters = ['custom' => ['label' => 'LBL_CUSTOM'],
            'prevfy' => ['label' => 'LBL_PREVIOUS_FY'],
            'thisfy' => ['label' => 'LBL_CURRENT_FY'],
            'nextfy' => ['label' => 'LBL_NEXT_FY'],
            'prevfq' => ['label' => 'LBL_PREVIOUS_FQ'],
            'thisfq' => ['label' => 'LBL_CURRENT_FQ'],
            'nextfq' => ['label' => 'LBL_NEXT_FQ'],
            'yesterday' => ['label' => 'LBL_YESTERDAY'],
            'today' => ['label' => 'LBL_TODAY'],
            'tomorrow' => ['label' => 'LBL_TOMORROW'],
            'lastweek' => ['label' => 'LBL_LAST_WEEK'],
            'thisweek' => ['label' => 'LBL_CURRENT_WEEK'],
            'nextweek' => ['label' => 'LBL_NEXT_WEEK'],
            'lastmonth' => ['label' => 'LBL_LAST_MONTH'],
            'thismonth' => ['label' => 'LBL_CURRENT_MONTH'],
            'nextmonth' => ['label' => 'LBL_NEXT_MONTH'],
            'last7days' => ['label' => 'LBL_LAST_7_DAYS'],
            'last14days' => ['label' => 'LBL_LAST_14_DAYS'],
            'last30days' => ['label' => 'LBL_LAST_30_DAYS'],
            'last60days' => ['label' => 'LBL_LAST_60_DAYS'],
            'last90days' => ['label' => 'LBL_LAST_90_DAYS'],
            'last120days' => ['label' => 'LBL_LAST_120_DAYS'],
            'next7days' => ['label' => 'LBL_NEXT_7_DAYS'],
            'next14days' => ['label' => 'LBL_NEXT_14_DAYS'],
            'next30days' => ['label' => 'LBL_NEXT_30_DAYS'],
            'next60days' => ['label' => 'LBL_NEXT_60_DAYS'],
            'next90days' => ['label' => 'LBL_NEXT_90_DAYS'],
            'next120days' => ['label' => 'LBL_NEXT_120_DAYS'],
        ];

        foreach ($dateFilters as $filterType => $filterDetails) {
            $dateValues = self::getDateForStdFilterBytype($filterType);
            $dateFilters[$filterType]['startdate'] = $dateValues[0];
            $dateFilters[$filterType]['enddate'] = $dateValues[1];
        }

        return $dateFilters;
    }

    /**
     * Function to get all the supported advanced filter operations.
     * @return <Array>
     */
    public static function getAdvancedFilterOptions()
    {
        return [
            'e' => 'LBL_EQUALS',
            'n' => 'LBL_NOT_EQUAL_TO',
            's' => 'LBL_STARTS_WITH',
            'ew' => 'LBL_ENDS_WITH',
            'c' => 'LBL_CONTAINS',
            'k' => 'LBL_DOES_NOT_CONTAIN',
            'l' => 'LBL_LESS_THAN',
            'g' => 'LBL_GREATER_THAN',
            'm' => 'LBL_LESS_THAN_OR_EQUAL',
            'h' => 'LBL_GREATER_OR_EQUAL',
            'b' => 'LBL_BEFORE',
            'a' => 'LBL_AFTER',
            'bw' => 'LBL_BETWEEN',
        ];
    }

    /**
     * Function to get the advanced filter option names by Field type.
     * @return <Array>
     */
    public static function getAdvancedFilterOpsByFieldType()
    {
        return [
            'V' => ['e', 'n', 's', 'ew', 'c', 'k'],
            'N' => ['e', 'n', 'l', 'g', 'm', 'h'],
            'T' => ['e', 'n', 'l', 'g', 'm', 'h', 'bw', 'b', 'a'],
            'I' => ['e', 'n', 'l', 'g', 'm', 'h'],
            'C' => ['e', 'n'],
            'D' => ['e', 'n', 'bw', 'b', 'a'],
            'DT' => ['e', 'n', 'bw', 'b', 'a'],
            'NN' => ['e', 'n', 'l', 'g', 'm', 'h'],
            'E' => ['e', 'n', 's', 'ew', 'c', 'k'],
        ];
    }

    /**
     * Function to get all the accessible Custom Views, for a given module if specified.
     * @param <String> $moduleName
     * @return <Array> - Array of Vtiger_CustomView_Record models
     */
    public static function getAll($moduleName = '')
    {
        $db = PearDatabase::getInstance();
        $userPrivilegeModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $currentUser = Users_Record_Model::getCurrentUserModel();

        $sql = 'SELECT * FROM vtiger_customview';
        $params = [];

        if (!empty($moduleName)) {
            $sql .= ' WHERE entitytype=?';
            $params[] = $moduleName;
        }
        if (!$userPrivilegeModel->isAdminUser()) {
            $userGroups = new GetUserGroups();
            $userGroups->getAllUserGroups($currentUser->getId());
            $groups = $userGroups->user_groups;
            $userRole = fetchUserRole($currentUser->getId());
            $parentRoles = getParentRole($userRole);
            $parentRolelist = [];
            foreach ($parentRoles as $par_rol_id) {
                array_push($parentRolelist, $par_rol_id);
            }
            array_push($parentRolelist, $userRole);

            $userParentRoleSeq = $userPrivilegeModel->get('parent_role_seq');
            $sql .= " AND ( vtiger_customview.userid = ? OR vtiger_customview.status = 0 OR vtiger_customview.status = 3
							OR vtiger_customview.userid IN (
								SELECT vtiger_user2role.userid FROM vtiger_user2role
									INNER JOIN vtiger_users ON vtiger_users.id = vtiger_user2role.userid
									INNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid
								WHERE vtiger_role.parentrole LIKE '" . $userParentRoleSeq . "::%') 
							OR vtiger_customview.cvid IN (SELECT vtiger_cv2users.cvid FROM vtiger_cv2users WHERE vtiger_cv2users.userid=?)";
            $params[] = $currentUser->getId();
            $params[] = $currentUser->getId();
            if (!empty($groups)) {
                $sql .= 'OR vtiger_customview.cvid IN (SELECT vtiger_cv2group.cvid FROM vtiger_cv2group WHERE vtiger_cv2group.groupid IN (' . generateQuestionMarks($groups) . '))';
                $params = array_merge($params, $groups);
            }

            $sql .= 'OR vtiger_customview.cvid IN (SELECT vtiger_cv2role.cvid FROM vtiger_cv2role WHERE vtiger_cv2role.roleid =?)';
            $params[] = $userRole;
            if (!empty($parentRolelist)) {
                $sql .= 'OR vtiger_customview.cvid IN (SELECT vtiger_cv2rs.cvid FROM vtiger_cv2rs WHERE vtiger_cv2rs.rsid IN (' . generateQuestionMarks($parentRolelist) . '))';
                $params = array_merge($params, $parentRolelist);
            }

            $sql .= ')';
        }

        $result = $db->pquery($sql, $params);
        $noOfCVs = $db->num_rows($result);
        $customViews = [];
        for ($i = 0; $i < $noOfCVs; ++$i) {
            $row = $db->query_result_rowdata($result, $i);
            $customView = new self();
            $customViews[] = $customView->setData($row)->setModule($row['entitytype']);
        }

        return $customViews;
    }

    /**
     * Function to get the instance of Custom View module, given custom view id.
     * @param <Integer> $cvId
     * @return CustomView_Record_Model instance, if exists. Null otherwise
     */
    public static function getInstanceById($cvId)
    {
        $db = PearDatabase::getInstance();

        $sql = 'SELECT * FROM vtiger_customview WHERE cvid = ?';
        $params = [$cvId];
        $result = $db->pquery($sql, $params);
        if ($db->num_rows($result) > 0) {
            $row = $db->query_result_rowdata($result, 0);
            $customView = new self();

            return $customView->setData($row)->setModule($row['entitytype']);
        }

        return null;
    }

    /**
     * Function to get all the custom views, of a given module if specified, grouped by their status.
     * @param <String> $moduleName
     * @return <Array> - Associative array of Status label to an array of Vtiger_CustomView_Record models
     */
    public static function getAllByGroup($moduleName = '', $listMode = true)
    {
        $customViews = self::getAll($moduleName);
        $groupedCustomViews = [];
        $groupedCustomViews['Mine'] = [];
        $groupedCustomViews['Shared'] = [];
        foreach ($customViews as $index => $customView) {
            if ($customView->isMine() && ($customView->get('viewname') != 'All' || !$listMode)) {
                $groupedCustomViews['Mine'][] = $customView;
            } elseif ($customView->isPublic()) {
                $groupedCustomViews['Public'][] = $customView;
                $groupedCustomViews['Shared'][] = $customView;
            } elseif ($customView->isPending()) {
                $groupedCustomViews['Pending'][] = $customView;
                $groupedCustomViews['Shared'][] = $customView;
            } else {
                $groupedCustomViews['Others'][] = $customView;
                $groupedCustomViews['Shared'][] = $customView;
            }
        }
        if (empty($groupedCustomViews['Shared'])) {
            unset($groupedCustomViews['Shared']);
        }

        return $groupedCustomViews;
    }

    /**
     * Function to get Clean instance of this record.
     * @return self
     */
    public static function getCleanInstance()
    {
        return new self();
    }

    /**
     * function to check duplicates from database.
     * @param <type> $viewName
     * @param <type> module name entity type in database
     * @return <boolean> true/false
     */
    public function checkDuplicate()
    {
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();

        $query = 'SELECT 1 FROM vtiger_customview WHERE viewname = ? AND entitytype = ? AND userid = ?';
        $params = [$this->get('viewname'), $this->getModule()->getName(), $currentUser->getId()];

        $cvid = $this->getId();
        if ($cvid) {
            $query .= ' AND cvid != ?';
            array_push($params, $cvid);
        }

        $result = $db->pquery($query, $params);
        if ($db->num_rows($result)) {
            return true;
        }

        return false;
    }

    /**
     * Function used to transform the older filter condition to suit newer filters.
     * The newer filters have only two groups one with ALL(AND) condition between each
     * filter and other with ANY(OR) condition, this functions tranforms the older
     * filter with 'AND' condition between filters of a group and will be placed under
     * match ALL conditions group and the rest of it will be placed under match Any group.
     * @return <Array>
     */
    public function transformToNewAdvancedFilter()
    {
        $standardFilter = $this->transformStandardFilter();
        $advancedFilter = $this->getAdvancedCriteria();
        $allGroupColumns = $anyGroupColumns = [];
        foreach ($advancedFilter as $index => $group) {
            $columns = $group['columns'];
            $and = $or = 0;
            $block = $group['condition'];
            if (php7_count($columns) != 1) {
                foreach ($columns as $column) {
                    if ($column['column_condition'] == 'and') {
                        ++$and;
                    } else {
                        ++$or;
                    }
                }
                if ($and == php7_count($columns) - 1 && php7_count($columns) != 1) {
                    $allGroupColumns = array_merge($allGroupColumns, $group['columns']);
                } else {
                    $anyGroupColumns = array_merge($anyGroupColumns, $group['columns']);
                }
            } elseif ($block == 'and'  || $index == 1) {
                $allGroupColumns = array_merge($allGroupColumns, $group['columns']);
            } else {
                $anyGroupColumns = array_merge($anyGroupColumns, $group['columns']);
            }
        }
        if ($standardFilter) {
            $allGroupColumns = array_merge($allGroupColumns, $standardFilter);
        }
        $transformedAdvancedCondition = [];
        $transformedAdvancedCondition[1] = ['columns' => $allGroupColumns, 'condition' => 'and'];
        $transformedAdvancedCondition[2] = ['columns' => $anyGroupColumns, 'condition' => ''];

        return $transformedAdvancedCondition;
    }

    /*
     *  Function used to tranform the standard filter as like as advanced filter format
     *	@returns array of tranformed standard filter
     */
    public function transformStandardFilter()
    {
        $standardFilter = $this->getStandardCriteria();
        if (!empty($standardFilter)) {
            $tranformedStandardFilter = [];
            $tranformedStandardFilter['comparator'] = 'bw';

            $fields = explode(':', $standardFilter['columnname']);

            if ($fields[1] == 'createdtime' || $fields[1] == 'modifiedtime' || ($fields[0] == 'vtiger_activity' && $fields[1] == 'date_start')) {
                $tranformedStandardFilter['columnname'] = $standardFilter['columnname'] . ':DT';
                $date[] = $standardFilter['startdate'] . ' 00:00:00';
                $date[] = $standardFilter['enddate'] . ' 00:00:00';
                $tranformedStandardFilter['value'] =  implode(',', $date);
            } else {
                $tranformedStandardFilter['columnname'] = $standardFilter['columnname'] . ':D';
                $tranformedStandardFilter['value'] = $standardFilter['startdate'] . ',' . $standardFilter['enddate'];
            }

            return [$tranformedStandardFilter];
        }

        return false;

    }

    public function getMembers()
    {
        if ($this->members == false) {
            $this->members = Settings_Groups_Member_Model::getAllByGroup($this, Settings_Groups_Member_Model::CUSTOM_VIEW_MODE);
        }

        return $this->members;
    }

    /**
     * Function gives default custom view for a module.
     * @param <String> $module
     * @return <CustomView_Record_Model>
     */
    public static function getAllFilterByModule($module)
    {
        $instance = Vtiger_Cache::get('AllCustomViewInstance', $module);
        if (!$instance) {
            $db = PearDatabase::getInstance();
            $query = "SELECT cvid FROM vtiger_customview WHERE viewname='All' AND entitytype = ?";
            $result = $db->pquery($query, [$module]);
            $viewId = $db->query_result($result, 0, 'cvid');
            if (!$viewId) {
                $customView = new CustomView($module);
                $viewId = $customView->getViewId($module);
            }
            if ($viewId) {
                $instance = self::getInstanceById($viewId);
            } else {
                $instance = self::getCleanInstance();
            }
            Vtiger_Cache::set('AllCustomViewInstance',$module,$instance);
        }

        return $instance;
    }
}
