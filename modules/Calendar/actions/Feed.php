<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

vimport('~~/include/Webservices/Query.php');

class Calendar_Feed_Action extends Vtiger_BasicAjax_Action
{
    public function process(Vtiger_Request $request)
    {
        if ($request->get('mode') === 'batch') {
            $feedsRequest = $request->get('feedsRequest', []);
            $result = [];
            if (php7_count($feedsRequest)) {
                foreach ($feedsRequest as $key => $value) {
                    $value = vtlib_array($value); // isset guarded.

                    $requestParams = [];
                    $requestParams['start'] = $value['start'];
                    $requestParams['end'] = $value['end'];
                    $requestParams['type'] = $value['type'];
                    $requestParams['userid'] = $value['userid'];
                    $requestParams['color'] = $value['color'];
                    $requestParams['textColor'] = $value['textColor'];
                    $requestParams['targetModule'] = $value['targetModule'];
                    $requestParams['fieldname'] = $value['fieldname'];
                    $requestParams['group'] = $value['group'];
                    $requestParams['mapping'] = $value['mapping'];
                    $requestParams['conditions'] = $value['conditions'];
                    $result[$key] = $this->_process($requestParams);
                }
            }
            echo json_encode($result);
        } else {
            $requestParams = [];
            $requestParams['start'] = $request->get('start');
            $requestParams['end'] = $request->get('end');
            $requestParams['type'] = $request->get('type');
            $requestParams['userid'] = $request->get('userid');
            $requestParams['color'] = $request->get('color');
            $requestParams['textColor'] = $request->get('textColor');
            $requestParams['targetModule'] = $request->get('targetModule');
            $requestParams['fieldname'] = $request->get('fieldname');
            $requestParams['group'] = $request->get('group');
            $requestParams['mapping'] = $request->get('mapping');
            $requestParams['conditions'] = $request->get('conditions', '');
            echo $this->_process($requestParams);
        }
    }

    public function _process($request)
    {
        try {
            $start = $request['start'];
            $end = $request['end'];
            $type = $request['type'];
            $userid = $request['userid'];
            $color = $request['color'];
            $textColor = $request['textColor'];
            $targetModule = $request['targetModule'];
            $fieldName = $request['fieldname'];
            $isGroupId = $request['group'];
            $mapping = $request['mapping'];
            $conditions = $request['conditions'];
            $result = [];
            switch ($type) {
                case 'Events':	if ($fieldName == 'date_start,due_date' || $userid) {
                    $this->pullEvents($start, $end, $result, $userid, $color, $textColor, $isGroupId, $conditions);
                } else {
                    $this->pullDetails($start, $end, $result, $type, $fieldName, $color, $textColor, $conditions);
                }
                    break;
                case 'Calendar':	if ($fieldName == 'date_start,due_date') {
                    $this->pullTasks($start, $end, $result, $color, $textColor);
                } else {
                    $this->pullDetails($start, $end, $result, $type, $fieldName, $color, $textColor);
                }
                    break;
                case 'MultipleEvents':	$this->pullMultipleEvents($start, $end, $result, $mapping);
                    break;
                case $type:	$this->pullDetails($start, $end, $result, $type, $fieldName, $color, $textColor);
                    break;
            }

            return json_encode($result);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function valForSql($value)
    {
        return Vtiger_Util_Helper::validateStringForSql($value);
    }

    protected function pullDetails($start, $end, &$result, $type, $fieldName, $color = null, $textColor = 'white', $conditions = '')
    {
        // +angelo
        $start = DateTimeField::convertToDBFormat($start);
        $end = DateTimeField::convertToDBFormat($end);
        // -angelo
        $moduleModel = Vtiger_Module_Model::getInstance($type);
        $nameFields = $moduleModel->getNameFields();
        foreach ($nameFields as $i => $nameField) {
            $fieldInstance = $moduleModel->getField($nameField);
            if (!$fieldInstance->isViewable()) {
                unset($nameFields[$i]);
            }
        }
        $nameFields = array_values($nameFields);
        $selectFields = implode(',', $nameFields);
        $fieldsList = explode(',', $fieldName);
        if (php7_count($fieldsList) == 2) {
            $db = PearDatabase::getInstance();
            $user = Users_Record_Model::getCurrentUserModel();
            $userAndGroupIds = array_merge([$user->getId()], $this->getGroupsIdsForUsers($user->getId()));
            $queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);
            $meta = $queryGenerator->getMeta($moduleModel->get('name'));

            $queryGenerator->setFields(array_merge(array_merge($nameFields, ['id']), $fieldsList));
            $query = $queryGenerator->getQuery();
            $startDateColumn = Vtiger_Util_Helper::validateStringForSql($fieldsList[0]);
            $endDateColumn = Vtiger_Util_Helper::validateStringForSql($fieldsList[1]);
            $query .= " AND (({$startDateColumn} >= ? AND {$endDateColumn} < ?) OR ({$endDateColumn} >= ?)) ";
            $params = [$start, $end, $start];
            $query .= ' AND vtiger_crmentity.smownerid IN (' . generateQuestionMarks($userAndGroupIds) . ')';
            $params = array_merge($params, $userAndGroupIds);
            $queryResult = $db->pquery($query, $params);

            $records = [];

            while ($rowData = $db->fetch_array($queryResult)) {
                $records[] = DataTransform::sanitizeDataWithColumn($rowData, $meta);
            }
        } else {
            if ($fieldName == 'birthday') {
                $startDateComponents = explode('-', $start);
                $endDateComponents = explode('-', $end);

                $year = $startDateComponents[0];
                $db = PearDatabase::getInstance();
                $user = Users_Record_Model::getCurrentUserModel();
                $userAndGroupIds = array_merge([$user->getId()], $this->getGroupsIdsForUsers($user->getId()));
                $queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);
                $meta = $queryGenerator->getMeta($moduleModel->get('name'));

                $queryGenerator->setFields(array_merge(array_merge($nameFields, ['id']), $fieldsList));
                $query = $queryGenerator->getQuery();
                $query .= " AND ((CONCAT(?, date_format(birthday,'%m-%d')) >= ? AND CONCAT(?, date_format(birthday,'%m-%d')) <= ? )";
                $params = ["{$year}-", $start, "{$year}-", $end];
                $endDateYear = $endDateComponents[0];
                if ($year !== $endDateYear) {
                    $query .= " OR (CONCAT(?, date_format(birthday,'%m-%d')) >= ?  AND CONCAT(?, date_format(birthday,'%m-%d')) <= ? )";
                    $params = array_merge($params, ["{$endDateYear}-", $start, "{$endDateYear}-", $end]);
                }
                $query .= ')';
                $query .= ' AND vtiger_crmentity.smownerid IN (' . generateQuestionMarks($userAndGroupIds) . ')';
                $params = array_merge($params, $userAndGroupIds);
                $queryResult = $db->pquery($query, $params);
                $records = [];

                while ($rowData = $db->fetch_array($queryResult)) {
                    $records[] = DataTransform::sanitizeDataWithColumn($rowData, $meta);
                }
            } else {
                $query = "SELECT {$selectFields}, {$fieldsList[0]} FROM {$type}";
                $query .= " WHERE {$fieldsList[0]} >= '{$start}' AND {$fieldsList[0]} <= '{$end}' ";


                if (!empty($conditions)) {
                    $conditions = Zend_Json::decode(Zend_Json::decode($conditions));
                    $query .=  'AND ' . $this->generateCalendarViewConditionQuery($conditions);
                }

                if ($type == 'PriceBooks') {
                    $records = $this->queryForRecords($query, false);
                } else {
                    $records = $this->queryForRecords($query);
                }
            }
        }
        foreach ($records as $record) {
            $item = [];
            [$modid, $crmid] = vtws_getIdComponents($record['id']);
            $item['id'] = $crmid;
            $item['title'] = decode_html($record[$nameFields[0]]);
            if (php7_count($nameFields) > 1) {
                $item['title'] = decode_html(trim($record[$nameFields[0]] . ' ' . $record[$nameFields[1]]));
            }
            if (!empty($record[$fieldsList[0]])) {
                $item['start'] = $record[$fieldsList[0]];
            } else {
                $item['start'] = $record[$fieldsList[1]];
            }
            if (php7_count($fieldsList) == 2) {
                $item['end'] = $record[$fieldsList[1]];
            }
            if ($fieldName == 'birthday') {
                $recordDateTime = new DateTimeImmutable($record[$fieldName]);

                $calendarYear = $year;
                if ($recordDateTime->format('m') < $startDateComponents[1]) {
                    $calendarYear = $endDateYear;
                }
                $recordDateTime->setDate($calendarYear, $recordDateTime->format('m'), $recordDateTime->format('d'));
                $item['start'] = $recordDateTime->format('Y-m-d');
            }

            $urlModule = $type;
            if ($urlModule === 'Events') {
                $urlModule = 'Calendar';
            }
            $item['url']   = sprintf('index.php?module=' . $urlModule . '&view=Detail&record=%s', $crmid);
            $item['color'] = $color;
            $item['textColor'] = $textColor;
            $item['module'] = $moduleModel->getName();
            $item['sourceModule'] = $moduleModel->getName();
            $item['fieldName'] = $fieldName;
            $item['conditions'] = '';
            $item['end'] = date('Y-m-d', strtotime(($item['end'] ?? $item['start']) . ' +1day'));
            if (!empty($conditions)) {
                $item['conditions'] = Zend_Json::encode(Zend_Json::encode($conditions));
            }
            $result[] = $item;
        }
    }

    protected function generateCalendarViewConditionQuery($conditions)
    {
        $conditionQuery = $operator = '';
        switch ($conditions['operator']) {
            case 'e': $operator = '=';
        }

        if (!empty($operator) && !empty($conditions['fieldname']) && !empty($conditions['value'])) {
            $fieldname = vtlib_purifyForSql($conditions['fieldname']);
            if (empty($fieldname)) {
                throw new Exception('Invalid fieldname.');
            }
            $conditionQuery = ' ' . $fieldname . $operator . '\'' . Vtiger_Functions::realEscapeString($conditions['value']) . '\' ';
        }

        return $conditionQuery;
    }

    protected function getGroupsIdsForUsers($userId)
    {
        vimport('~~/include/utils/GetUserGroups.php');

        $userGroupInstance = new GetUserGroups();
        $userGroupInstance->getAllUserGroups($userId);

        return $userGroupInstance->user_groups;
    }

    protected function queryForRecords($query, $onlymine = true)
    {
        $user = Users_Record_Model::getCurrentUserModel();
        if ($onlymine) {
            $groupIds = $this->getGroupsIdsForUsers($user->getId());
            $groupWsIds = [];
            foreach ($groupIds as $groupId) {
                $groupWsIds[] = vtws_getWebserviceEntityId('Groups', $groupId);
            }
            $userwsid = vtws_getWebserviceEntityId('Users', $user->getId());
            $userAndGroupIds = array_merge([$userwsid], $groupWsIds);
            $query .= " AND assigned_user_id IN ('" . implode("','", $userAndGroupIds) . "')";
        }

        // TODO take care of pulling 100+ records
        return vtws_query($query . ';', $user);
    }

    protected function pullEvents($start, $end, &$result, $userid = false, $color = null, $textColor = 'white', $isGroupId = false, $conditions = '')
    {
        $dbStartDateOject = DateTimeField::convertToDBTimeZone($start);
        $dbStartDateTime = $dbStartDateOject->format('Y-m-d H:i:s');
        $dbStartDateTimeComponents = explode(' ', $dbStartDateTime);
        $dbStartDate = $dbStartDateTimeComponents[0];

        $dbEndDateObject = DateTimeField::convertToDBTimeZone($end);
        $dbEndDateTime = $dbEndDateObject->format('Y-m-d H:i:s');

        $currentUser = Users_Record_Model::getCurrentUserModel();
        $db = PearDatabase::getInstance();
        $groupsIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());
        require 'user_privileges/user_privileges_' . $currentUser->id . '.php';
        require 'user_privileges/sharing_privileges_' . $currentUser->id . '.php';

        $moduleModel = Vtiger_Module_Model::getInstance('Events');
        if ($userid && !$isGroupId) {
            $focus = new Users();
            $focus->id = $userid;
            $focus->retrieve_entity_info($userid, 'Users');
            $user = Users_Record_Model::getInstanceFromUserObject($focus);
            $userName = $user->getName();
            $queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);
        } else {
            $queryGenerator = new QueryGenerator($moduleModel->get('name'), $currentUser);
        }

        $queryGenerator->setFields(['subject', 'eventstatus', 'visibility', 'date_start', 'time_start', 'due_date', 'time_end', 'assigned_user_id', 'id', 'activitytype', 'recurringtype']);
        $query = $queryGenerator->getQuery();

        $query .= " AND vtiger_activity.activitytype NOT IN ('Emails','Task') AND ";
        $hideCompleted = $currentUser->get('hidecompletedevents');
        if ($hideCompleted) {
            $query .= "vtiger_activity.eventstatus != 'HELD' AND ";
        }

        if (!empty($conditions)) {
            $conditions = Zend_Json::decode(Zend_Json::decode($conditions));
            $query .=  $this->generateCalendarViewConditionQuery($conditions) . 'AND ';
        }
        $query .= " ((concat(date_start, '', time_start)  >= ? AND concat(due_date, '', time_end) < ? ) OR ( due_date >= ? ))";
        $params = [$dbStartDateTime, $dbEndDateTime, $dbStartDate];
        if (empty($userid)) {
            $eventUserId  = $currentUser->getId();
        } else {
            $eventUserId = $userid;
        }
        $userIds = array_merge([$eventUserId], $this->getGroupsIdsForUsers($eventUserId));

        $query .= ' AND vtiger_crmentity.smownerid IN (' . generateQuestionMarks($userIds) . ')';
        $params = array_merge($params, $userIds);
        $queryResult = $db->pquery($query, $params);

        while ($record = $db->fetchByAssoc($queryResult)) {
            $item = [];
            $crmid = $record['activityid'];
            $visibility = $record['visibility'];
            $activitytype = $record['activitytype'];
            $status = $record['eventstatus'];
            $ownerId = $record['smownerid'];
            $item['id'] = $crmid;
            $item['visibility'] = $visibility;
            $item['activitytype'] = $activitytype;
            $item['status'] = $status;
            $recordBusy = true;
            if (in_array($ownerId, $groupsIds)) {
                $recordBusy = false;
            } elseif ($ownerId == $currentUser->getId()) {
                $recordBusy = false;
            }
            // if the user is having view all permission then it should show the record
            // as we are showing in detail view
            if ($profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0) {
                $recordBusy = false;
            }

            if (!$currentUser->isAdminUser() && $visibility == 'Private' && $userid && $userid != $currentUser->getId() && $recordBusy) {
                $item['title'] = decode_html($userName) . ' - ' . decode_html(vtranslate('Busy', 'Events')) . '*';
                $item['url']   = '';
            } else {
                $item['title'] = decode_html($record['subject']) . ' - (' . decode_html(vtranslate($record['eventstatus'], 'Calendar')) . ')';
                $item['url']   = sprintf('index.php?module=Calendar&view=Detail&record=%s', $crmid);
            }

            $dateTimeFieldInstance = new DateTimeField($record['date_start'] . ' ' . $record['time_start']);
            $userDateTimeString = $dateTimeFieldInstance->getDisplayDateTimeValue($currentUser);
            $dateTimeComponents = explode(' ', $userDateTimeString);
            $dateComponent = $dateTimeComponents[0];
            // Conveting the date format in to Y-m-d.since full calendar expects in the same format
            $dataBaseDateFormatedString = DateTimeField::__convertToDBFormat($dateComponent, $currentUser->get('date_format'));
            $item['start'] = $dataBaseDateFormatedString . ' ' . $dateTimeComponents[1];

            $dateTimeFieldInstance = new DateTimeField($record['due_date'] . ' ' . $record['time_end']);
            $userDateTimeString = $dateTimeFieldInstance->getDisplayDateTimeValue($currentUser);
            $dateTimeComponents = explode(' ', $userDateTimeString);
            $dateComponent = $dateTimeComponents[0];
            // Conveting the date format in to Y-m-d.since full calendar expects in the same format
            $dataBaseDateFormatedString = DateTimeField::__convertToDBFormat($dateComponent, $currentUser->get('date_format'));
            $item['end']   =  $dataBaseDateFormatedString . ' ' . $dateTimeComponents[1];

            $item['className'] = $cssClass;
            $item['allDay'] = false;
            $item['color'] = $color;
            $item['textColor'] = $textColor;
            $item['module'] = $moduleModel->getName();
            $recurringCheck = false;
            if ($record['recurringtype'] != '' && $record['recurringtype'] != '--None--') {
                $recurringCheck = true;
            }
            $item['recurringcheck'] = $recurringCheck;
            $item['userid'] = $eventUserId;
            $item['fieldName'] = 'date_start,due_date';
            $item['conditions'] = '';
            if (!empty($conditions)) {
                $item['conditions'] = Zend_Json::encode(Zend_Json::encode($conditions));
            }
            $result[] = $item;
        }
    }

    protected function pullMultipleEvents($start, $end, &$result, $data)
    {

        foreach ($data as $id => $backgroundColorAndTextColor) {
            $userEvents = [];
            $colorComponents = explode(',', $backgroundColorAndTextColor);
            $this->pullEvents($start, $end, $userEvents, $id, $colorComponents[0], $colorComponents[1], $colorComponents[2]);
            $result[$id] = $userEvents;
        }
    }

    protected function pullTasks($start, $end, &$result, $color = null, $textColor = 'white')
    {
        $user = Users_Record_Model::getCurrentUserModel();
        $db = PearDatabase::getInstance();

        $moduleModel = Vtiger_Module_Model::getInstance('Calendar');
        $userAndGroupIds = array_merge([$user->getId()], $this->getGroupsIdsForUsers($user->getId()));
        $queryGenerator = new QueryGenerator($moduleModel->get('name'), $user);

        $queryGenerator->setFields(['activityid', 'subject', 'taskstatus', 'activitytype', 'date_start', 'time_start', 'due_date', 'time_end', 'id']);
        $query = $queryGenerator->getQuery();

        $query .= " AND vtiger_activity.activitytype = 'Task' AND ";
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $hideCompleted = $currentUser->get('hidecompletedevents');
        if ($hideCompleted) {
            $query .= "vtiger_activity.status != 'Completed' AND ";
        }
        $query .= ' ((date_start >= ? AND due_date < ? ) OR ( due_date >= ? ))';

        // +angelo
        $start = DateTimeField::__convertToDBFormat($start, $user->get('date_format'));
        $end = DateTimeField::__convertToDBFormat($end, $user->get('date_format'));
        // -angelo
        $params = [$start, $end, $start];
        $userIds = $userAndGroupIds;
        $query .= ' AND vtiger_crmentity.smownerid IN (' . generateQuestionMarks($userIds) . ')';
        $params = array_merge($params, $userIds);
        $queryResult = $db->pquery($query, $params);

        while ($record = $db->fetchByAssoc($queryResult)) {
            $item = [];
            $crmid = $record['activityid'];
            $item['title'] = decode_html($record['subject']) . ' - (' . decode_html(vtranslate($record['status'], 'Calendar')) . ')';
            $item['status'] = $record['status'];
            $item['activitytype'] = $record['activitytype'];
            $item['id'] = $crmid;
            $dateTimeFieldInstance = new DateTimeField($record['date_start'] . ' ' . $record['time_start']);
            $userDateTimeString = $dateTimeFieldInstance->getDisplayDateTimeValue();
            $dateTimeComponents = explode(' ', $userDateTimeString);
            $dateComponent = $dateTimeComponents[0];
            // Conveting the date format in to Y-m-d.since full calendar expects in the same format
            $dataBaseDateFormatedString = DateTimeField::__convertToDBFormat($dateComponent, $user->get('date_format'));
            $item['start'] = $dataBaseDateFormatedString . ' ' . $dateTimeComponents[1];

            $item['end']   = $record['due_date'];
            $item['url']   = sprintf('index.php?module=Calendar&view=Detail&record=%s', $crmid);
            $item['color'] = $color;
            $item['textColor'] = $textColor;
            $item['module'] = $moduleModel->getName();
            $item['allDay'] = true;
            $item['fieldName'] = 'date_start,due_date';
            $item['conditions'] = '';
            $result[] = $item;
        }
    }
}
