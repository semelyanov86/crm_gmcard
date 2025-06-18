<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

vimport('~~/vtlib/Vtiger/Module.php');

/**
 * Calendar Module Model Class.
 */
class Calendar_Module_Model extends Vtiger_Module_Model
{
    /**
     * Function returns the default view for the Calendar module.
     * @return <String>
     */
    public function getDefaultViewName()
    {
        return $this->getCalendarViewName();
    }

    /**
     * Function returns the calendar view name.
     * @return <String>
     */
    public function getCalendarViewName()
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $arrayofViews = ['ListView' => 'List', 'MyCalendar' => 'Calendar', 'SharedCalendar' => 'SharedCalendar'];

        $calendarViewName = $currentUserModel->get('defaultcalendarview');
        if (array_key_exists($calendarViewName, $arrayofViews)) {
            $calendarViewName = $arrayofViews[$calendarViewName];
        }
        if (empty($calendarViewName)) {
            $calendarViewName = 'Calendar';
        }

        return $calendarViewName;
    }

    /**
     *  Function returns the url for Calendar view.
     * @return <String>
     */
    public function getCalendarViewUrl()
    {
        return 'index.php?module=' . $this->get('name') . '&view=Calendar';
    }

    /**
     * Function to check whether the module is summary view supported.
     * @return <Boolean> - true/false
     */
    public function isSummaryViewSupported()
    {
        return false;
    }

    /**
     * Function to check whether to allow excel like grid edit.
     * Disabled as there are more dependent fields.
     */
    public function isExcelEditAllowed()
    {
        return false;
    }

    /**
     * Function returns the URL for creating Events.
     * @return <String>
     */
    public function getCreateEventRecordUrl()
    {
        return 'index.php?module=' . $this->get('name') . '&view=' . $this->getEditViewName() . '&mode=Events';
    }

    /**
     * Function returns the URL for creating Task.
     * @return <String>
     */
    public function getCreateTaskRecordUrl()
    {
        return 'index.php?module=' . $this->get('name') . '&view=' . $this->getEditViewName() . '&mode=Calendar';
    }

    /**
     * Function to get a Vtiger Record Model instance from an array of key-value mapping.
     * @param <Array> $valueArray
     * @return Vtiger_Record_Model or Module Specific Record Model instance
     */
    public function getRecordFromArray($valueArray, $rawData = false)
    {
        $recordInstance = parent::getRecordFromArray($valueArray, $rawData);
        $recordInstance->setData($valueArray)->setModuleFromInstance($this)->setRawData($rawData);
        // added to fix picklist colorizer issue, list page not showing color for records
        if (isset($rawData['status']) && $rawData['status'] && empty($rawData['taskstatus'])) {
            $recordInstance->rawData['taskstatus'] = $recordInstance->rawData['status'];
        }

        return $recordInstance;
    }

    /**
     * Function that returns related list header fields that will be showed in the Related List View.
     * @return <Array> returns related fields list
     */
    public function getRelatedListFields()
    {
        $entityInstance = CRMEntity::getInstance($this->getName());
        $list_fields = $entityInstance->list_fields;
        $list_fields_name = $entityInstance->list_fields_name;
        $relatedListFields = [];
        foreach ($list_fields as $key => $fieldInfo) {
            foreach ($fieldInfo as $columnName) {
                if (array_key_exists($key, $list_fields_name)) {
                    if ($columnName == 'lastname' || $columnName == 'activity') {
                        continue;
                    }
                    if ($columnName == 'status') {
                        $relatedListFields[$columnName] = 'taskstatus';
                    } else {
                        $relatedListFields[$columnName] = $list_fields_name[$key];
                    }
                }
            }
        }

        return $relatedListFields;
    }

    /**
     * Function to get list of field for related list.
     * @return <Array> empty array
     */
    public function getConfigureRelatedListFields()
    {
        return [];
    }

    /**
     * Function to get list of field for summary view.
     * @return <Array> empty array
     */
    public function getSummaryViewFieldsList()
    {
        return [];
    }

    /**
     * Function to get the Quick Links for the module.
     * @param <Array> $linkParams
     * @return <Array> List of Vtiger_Link_Model instances
     */
    public function getSideBarLinks($linkParams)
    {
        $linkTypes = ['SIDEBARLINK', 'SIDEBARWIDGET'];
        $links = Vtiger_Link_Model::getAllByType($this->getId(), $linkTypes, $linkParams);

        $quickLinks = [
            [
                'linktype' => 'SIDEBARLINK',
                'linklabel' => 'LBL_CALENDAR_VIEW',
                'linkurl' => $this->getCalendarViewUrl(),
                'linkicon' => '',
            ],
            [
                'linktype' => 'SIDEBARLINK',
                'linklabel' => 'LBL_SHARED_CALENDAR',
                'linkurl' => $this->getSharedCalendarViewUrl(),
                'linkicon' => '',
            ],
            [
                'linktype' => 'SIDEBARLINK',
                'linklabel' => 'LBL_RECORDS_LIST',
                'linkurl' => $this->getListViewUrl(),
                'linkicon' => '',
            ],
        ];
        foreach ($quickLinks as $quickLink) {
            $links['SIDEBARLINK'][] = Vtiger_Link_Model::getInstanceFromValues($quickLink);
        }

        $quickWidgets = [];

        if ($linkParams['ACTION'] == 'Calendar') {
            $quickWidgets[] = [
                'linktype' => 'SIDEBARWIDGET',
                'linklabel' => 'LBL_ACTIVITY_TYPES',
                'linkurl' => 'module=' . $this->get('name') . '&view=ViewTypes&mode=getViewTypes',
                'linkicon' => '',
            ];
        }

        if ($linkParams['ACTION'] == 'SharedCalendar') {
            $quickWidgets[] = [
                'linktype' => 'SIDEBARWIDGET',
                'linklabel' => 'LBL_ADDED_CALENDARS',
                'linkurl' => 'module=' . $this->get('name') . '&view=ViewTypes&mode=getSharedUsersList',
                'linkicon' => '',
            ];
        }

        $quickWidgets[] = [
            'linktype' => 'SIDEBARWIDGET',
            'linklabel' => 'LBL_RECENTLY_MODIFIED',
            'linkurl' => 'module=' . $this->get('name') . '&view=IndexAjax&mode=showActiveRecords',
            'linkicon' => '',
        ];

        foreach ($quickWidgets as $quickWidget) {
            $links['SIDEBARWIDGET'][] = Vtiger_Link_Model::getInstanceFromValues($quickWidget);
        }

        return $links;
    }

    /**
     * Function returns the url that shows Calendar Import result.
     * @return <String> url
     */
    public function getImportResultUrl()
    {
        return 'index.php?module=' . $this->getName() . '&view=ImportResult';
    }

    /**
     * Function to get export query.
     * @return <String> query;
     */
    public function getExportQuery($focus, $where)
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $userId = $currentUserModel->getId();
        $userGroup = new GetUserGroups();
        $userGroup->getAllUserGroups($userId);
        $userGroupIds = $userGroup->user_groups;
        array_push($userGroupIds, $userId);
        $value = implode(',', $userGroupIds);
        $query = "SELECT vtiger_activity.*, vtiger_crmentity.description, vtiger_activity_reminder.reminder_time FROM vtiger_activity
					INNER JOIN vtiger_crmentity ON vtiger_activity.activityid = vtiger_crmentity.crmid
					LEFT JOIN vtiger_activity_reminder ON vtiger_activity_reminder.activity_id = vtiger_activity.activityid AND vtiger_activity_reminder.recurringid = 0
					WHERE vtiger_crmentity.deleted = 0 AND vtiger_crmentity.smownerid IN ({$value}) AND vtiger_activity.activitytype NOT IN ('Emails')";

        return $query;
    }

    /**
     * Function to set event fields for export.
     */
    public function setEventFieldsForExport()
    {
        $moduleFields = array_flip($this->getColumnFieldMapping());
        $userModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

        $keysToReplace = ['taskpriority'];
        $keysValuesToReplace = ['taskpriority' => 'priority'];

        foreach ($moduleFields as $fieldName => $fieldValue) {
            $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $this);
            if ($fieldName != 'id' && $fieldModel->getPermissions()) {
                if (!in_array($fieldName, $keysToReplace)) {
                    $eventFields[$fieldName] = 'yes';
                } else {
                    $eventFields[$keysValuesToReplace[$fieldName]] = 'yes';
                }
            }
        }
        $this->set('eventFields', $eventFields);
    }

    /**
     * Function to set todo fields for export.
     */
    public function setTodoFieldsForExport()
    {
        $moduleFields = array_flip($this->getColumnFieldMapping());
        $userModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

        $keysToReplace = ['taskpriority', 'taskstatus'];
        $keysValuesToReplace = ['taskpriority' => 'priority', 'taskstatus' => 'status'];

        foreach ($moduleFields as $fieldName => $fieldValue) {
            $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $this);
            if ($fieldName != 'id' && $fieldModel->getPermissions()) {
                if (!in_array($fieldName, $keysToReplace)) {
                    $todoFields[$fieldName] = 'yes';
                } else {
                    $todoFields[$keysValuesToReplace[$fieldName]] = 'yes';
                }
            }
        }
        $this->set('todoFields', $todoFields);
    }

    /**
     * Function to get the url to view Details for the module.
     * @return <String> - url
     */
    public function getDetailViewUrl($id)
    {
        return 'index.php?module=Calendar&view=' . $this->getDetailViewName() . '&record=' . $id;
    }

    /**
     * To get the lists of sharedids.
     * @param $id --  user id
     * @returns <Array> $sharedids
     */
    public static function getCaledarSharedUsers($id)
    {
        $db = PearDatabase::getInstance();

        $query = 'SELECT vtiger_users.user_name, vtiger_sharedcalendar.* FROM vtiger_sharedcalendar
				LEFT JOIN vtiger_users ON vtiger_sharedcalendar.sharedid=vtiger_users.id WHERE userid=?';
        $result = $db->pquery($query, [$id]);
        $rows = $db->num_rows($result);

        $sharedids = [];
        $focus = new Users();
        for ($i = 0; $i < $rows; ++$i) {
            $sharedid = $db->query_result($result, $i, 'sharedid');
            $userId = $db->query_result($result, $i, 'userid');
            $sharedids[$sharedid] = $userId;
        }

        return $sharedids;
    }

    /**
     * To get the lists of sharedids.
     * @param $id --  user id
     * @returns <Array> $sharedids
     */
    public static function getSharedUsersOfCurrentUser($id)
    {
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if ($currentUser->isAdminUser()) {
            $query = "SELECT userlabel, id AS userid
					FROM vtiger_users WHERE status='Active' AND id <> ?";
            $result = $db->pquery($query, [$id]);
        } else {
            $query = "SELECT vtiger_users.userlabel, vtiger_users.id AS userid
				FROM vtiger_sharedcalendar RIGHT JOIN vtiger_users ON vtiger_sharedcalendar.userid=vtiger_users.id AND vtiger_users.status= 'Active'
				WHERE vtiger_sharedcalendar.sharedid=? OR (vtiger_users.status='Active' AND vtiger_users.calendarsharedtype='public' AND vtiger_users.id <> ?)";
            $result = $db->pquery($query, [$id, $id]);
        }
        $rows = $db->num_rows($result);

        $userIds = [];
        for ($i = 0; $i < $rows; ++$i) {
            $id = $db->query_result($result, $i, 'userid');
            $userName = $db->query_result($result, $i, 'userlabel');
            $userIds[$id] = $userName;
        }

        return $sharedids[$id] = $userIds;
    }

    /**
     * To get the lists of accessible groups.
     * @param $id --  user id
     * @returns <Array> accessible groups
     */
    public static function getSharedCalendarGroupsList($id)
    {
        $currentUsersRecordModel = Users_Record_Model::getCurrentUserModel();
        $sharedGroups = $currentUsersRecordModel->getAccessibleGroupForModule('Calendar');

        return $sharedGroups;
    }

    /**
     * To get the lists of sharedids and colors.
     * @param $id --  user id
     * @returns <Array> $sharedUsers
     */
    public static function getSharedUsersInfoOfCurrentUser($id)
    {
        $db = PearDatabase::getInstance();

        $query = 'SELECT shareduserid,color,visible FROM vtiger_shareduserinfo where userid = ?';
        $result = $db->pquery($query, [$id]);
        $rows = $db->num_rows($result);

        $sharedUsers = [];
        for ($i = 0; $i < $rows; ++$i) {
            $sharedUserId = $db->query_result($result, $i, 'shareduserid');
            $color = $db->query_result($result, $i, 'color');
            $visible = $db->query_result($result, $i, 'visible');
            $sharedUsers[$sharedUserId] = ['visible' => $visible, 'color' => $color];
        }

        return $sharedUsers;
    }

    /**
     * To get the lists of sharedids and colors.
     * @param $id --  user id
     * @returns <Array> $sharedUsers
     */
    public static function getCalendarViewTypes($id)
    {
        $db = PearDatabase::getInstance();

        $query = 'SELECT * FROM vtiger_calendar_user_activitytypes 
			INNER JOIN vtiger_calendar_default_activitytypes on vtiger_calendar_default_activitytypes.id=vtiger_calendar_user_activitytypes.defaultid 
			WHERE vtiger_calendar_user_activitytypes.userid=?';
        $result = $db->pquery($query, [$id]);
        $rows = $db->num_rows($result);

        $calendarViewTypes = [];
        for ($i = 0; $i < $rows; ++$i) {
            $activityTypes = $db->query_result_rowdata($result, $i);
            $moduleInstance = Vtiger_Module_Model::getInstance($activityTypes['module']);
            // If there is no module view permission, should not show in calendar view
            if ($moduleInstance === false || !$moduleInstance->isPermitted('Detail')) {
                continue;
            }
            $type = '';
            if (in_array($activityTypes['module'], ['Events', 'Calendar']) && $activityTypes['isdefault']) {
                $type = $activityTypes['module'] . '_' . $activityTypes['isdefault'];
            }
            $fieldNamesList = Zend_Json::decode(html_entity_decode($activityTypes['fieldname']));
            $fieldLabelsList = [];
            foreach ($fieldNamesList as $fieldName) {
                $fieldInstance = Vtiger_Field_Model::getInstance($fieldName, $moduleInstance);
                if ($fieldInstance) {
                    // If there is no field view permission, should not show in calendar view
                    if (!$type && !$fieldInstance->isViewableInDetailView()) {
                        $fieldLabelsList = [];
                        break;
                    }
                    $fieldLabelsList[$fieldName] = $fieldInstance->label;
                }
            }

            $conditionsName = '';
            if (!empty($activityTypes['conditions'])) {
                $conditions = Zend_Json::decode(decode_html($activityTypes['conditions']));
                $conditions = Zend_Json::decode($conditions);
                $conditionsName = $conditions['value'];
            }
            $fieldInfo = ['module'	=> $activityTypes['module'],
                'fieldname' => implode(',', array_keys($fieldLabelsList)),
                'fieldlabel' => implode(',', $fieldLabelsList),
                'visible'	=> $activityTypes['visible'],
                'color'		=> $activityTypes['color'],
                'type'		=> $type,
                'conditions' => [
                    'name' => $conditionsName,
                    'rules' => $activityTypes['conditions'],
                ],
            ];

            if ($activityTypes['visible'] == '1') {
                if ($fieldLabelsList) {
                    $calendarViewTypes['visible'][] = $fieldInfo;
                }
            } else {
                $calendarViewTypes['invisible'][] = $fieldInfo;
            }
        }

        return $calendarViewTypes;
    }

    public static function getDateFieldModulesList()
    {
        $db = PearDatabase::getInstance();

        $query = 'SELECT DISTINCT(name) AS modulename FROM vtiger_tab 
				  LEFT JOIN vtiger_field ON vtiger_field.tabid = vtiger_tab.tabid
				  WHERE vtiger_field.typeofdata LIKE ?';
        $result = $db->pquery($query, ['D~%']);
        $num_rows = $db->num_rows($result);

        $moduleList = [];
        for ($i = 0; $i < $num_rows; ++$i) {
            $moduleList[] = $db->query_result($result, $i, 'modulename');
        }
        // Remove the modules not having owner field, need to show activities based on owner field
        $moduleList = array_diff($moduleList, ['PriceBooks', 'Faq']);

        $moduleFieldsList = [];
        foreach ($moduleList as $module) {
            $moduleModel = Vtiger_Module_Model::getInstance($module);
            // If there is no module view permission, should not show in calendar view
            if (!$moduleModel->isPermitted('Detail')) {
                continue;
            }
            $dateFields = $moduleModel->getFieldsByType('date');
            $fieldsList = [];
            foreach ($dateFields as $fieldName => $fieldModel) {
                // If there is no field view permission, should not show in calendar view
                if ($fieldModel->isViewableInDetailView()) {
                    $fieldsList[$fieldName] = vtranslate($fieldModel->get('label'), $module);
                }
            }
            if (!empty($fieldsList)) {
                $moduleFieldsList[$module] = $fieldsList;
            }
        }

        $eventFieldsList = ['date_start', 'due_date'];
        $eventFieldLabelsList = [];
        $moduleInstance = Vtiger_Module_Model::getInstance('Events');
        foreach ($eventFieldsList as $fieldName) {
            $fieldInstance = Vtiger_Field_Model::getInstance($fieldName, $moduleInstance);
            if ($fieldInstance) {
                $eventFieldLabelsList[$fieldName] = $fieldInstance->label;
            }
        }

        $calendarFieldsList = ['date_start', 'due_date'];
        $calendarFieldLabelsList = [];
        $calendarInstance = Vtiger_Module_Model::getInstance('Calendar');
        foreach ($calendarFieldsList as $fieldName) {
            $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $calendarInstance);
            if ($fieldModel) {
                $calendarFieldLabelsList[$fieldName] = $fieldModel->label;
            }
        }

        // Default activity types Events and Calendar should append to the date fields list
        $moduleFieldsList['Events'][implode(',', array_keys($eventFieldLabelsList))] = implode(',', $eventFieldLabelsList);
        $moduleFieldsList['Calendar'][implode(',', array_keys($calendarFieldLabelsList))] = implode(',', $calendarFieldLabelsList);

        return $moduleFieldsList;
    }

    public static function getCalendarViewTypesToAdd($userId)
    {
        $calendarViewTypes = self::getCalendarViewTypes($userId);
        $moduleViewTypes = self::getDateFieldModulesList();

        $visibleList = $calendarViewTypes['visible'];
        if (is_array($visibleList)) {
            foreach ($visibleList as $list) {
                $fieldsListArray = $moduleViewTypes[$list['module']];
                if (php7_count($fieldsListArray) == 1) {
                    if ($list['module'] !== 'Events') {
                        unset($fieldsListArray[$list['fieldname']]);
                    }
                }
                if (!empty($fieldsListArray)) {
                    $moduleViewTypes[$list['module']] = $fieldsListArray;
                } else {
                    unset($moduleViewTypes[$list['module']]);
                }
            }
        }

        return $moduleViewTypes;
    }

    public static function getVisibleCalendarViewTypes($userId)
    {
        $db = PearDatabase::getInstance();

        $query = 'SELECT * FROM vtiger_calendar_user_activitytypes 
			INNER JOIN vtiger_calendar_default_activitytypes on vtiger_calendar_default_activitytypes.id=vtiger_calendar_user_activitytypes.defaultid 
			WHERE vtiger_calendar_user_activitytypes.userid=? AND vtiger_calendar_user_activitytypes.visible=?';
        $result = $db->pquery($query, [$userId, '1']);
        $rows = $db->num_rows($result);

        $calendarViewTypes = [];
        for ($i = 0; $i < $rows; ++$i) {
            $activityTypes = $db->query_result_rowdata($result, $i);
            $moduleInstance = Vtiger_Module_Model::getInstance($activityTypes['module']);
            // If there is no module view permission, should not show in calendar view
            if (!$moduleInstance->isPermitted('Detail')) {
                continue;
            }

            $fieldNamesList = Zend_Json::decode(html_entity_decode($activityTypes['fieldname']));
            $fieldLabelsList = [];
            foreach ($fieldNamesList as $fieldName) {
                $fieldInstance = Vtiger_Field_Model::getInstance($fieldName, $moduleInstance);
                if ($fieldInstance) {
                    // If there is no field view permission, should not show in calendar view
                    if (!$fieldInstance->isViewableInDetailView()) {
                        $fieldLabelsList = [];
                        break;
                    }
                    $fieldLabelsList[$fieldName] = $fieldInstance->label;
                }
            }
            if (!empty($fieldLabelsList)) {
                $calendarViewTypes[$activityTypes['module']][implode(',', array_keys($fieldLabelsList))] = implode(',', $fieldLabelsList);
            }
        }

        return $calendarViewTypes;
    }

    /**
     *  Function to check duplicate activity view while adding.
     * @return <boolean>
     */
    public static function checkDuplicateView(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userId = $currentUser->getId();
        $viewmodule = $request->get('viewmodule');
        $fieldName = $request->get('viewfieldname');
        $conditions = $request->get('viewConditions', '');
        $viewfieldname = [];
        $viewfieldname = Zend_Json::encode(explode(',', $fieldName));
        $db = PearDatabase::getInstance();

        $queryResult = $db->pquery('SELECT id FROM vtiger_calendar_default_activitytypes WHERE module=? AND fieldname=? AND conditions=?', [$viewmodule, $viewfieldname, $conditions]);
        if ($db->num_rows($queryResult) > 0) {
            $defaultId = $db->query_result($queryResult, 0, 'id');

            $query = $db->pquery('SELECT 1 FROM vtiger_calendar_user_activitytypes WHERE defaultid=? AND userid=? AND visible=?', [$defaultId, $userId, '1']);
            if ($db->num_rows($query) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Function to delete calendar view.
     */
    public static function deleteCalendarView(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userId = $currentUser->getId();
        $viewmodule = $request->get('viewmodule');
        $fieldName = $request->get('viewfieldname');
        $viewconditions = $request->get('viewConditions', '');
        $viewfieldname = [];
        $viewfieldname = Zend_Json::encode(explode(',', $fieldName));

        $db = PearDatabase::getInstance();
        $defaultIdQueryResult = $db->pquery('SELECT id FROM vtiger_calendar_default_activitytypes WHERE module=? AND fieldname=? AND isdefault=? AND conditions=?', [$viewmodule, $viewfieldname, 0, $viewconditions]);
        if ($db->num_rows($defaultIdQueryResult) > 0) {
            $defaultId = $db->query_result($defaultIdQueryResult, 0, 'id');
            $db->pquery('DELETE FROM vtiger_calendar_user_activitytypes WHERE defaultid=? AND vtiger_calendar_user_activitytypes.userid=?', [$defaultId, $userId]);

            $queryResult = $db->pquery('SELECT 1 FROM vtiger_calendar_user_activitytypes WHERE defaultid=?', [$defaultId]);
            if ($db->num_rows($queryResult) <= 0) {
                $db->pquery('DELETE FROM vtiger_calendar_default_activitytypes WHERE module=? AND fieldname=? AND isdefault=? AND id=? AND conditions=?', [$viewmodule, $viewfieldname, 0, $defaultId, $viewconditions]);
            }
        } else {
            $db->pquery(
                'UPDATE vtiger_calendar_user_activitytypes 
						INNER JOIN vtiger_calendar_default_activitytypes ON vtiger_calendar_default_activitytypes.id = vtiger_calendar_user_activitytypes.defaultid
						SET vtiger_calendar_user_activitytypes.visible=? WHERE vtiger_calendar_user_activitytypes.userid=? AND vtiger_calendar_default_activitytypes.module=? AND vtiger_calendar_default_activitytypes.fieldname=? AND 
						vtiger_calendar_default_activitytypes.conditions=?',
                ['0', $userId, $viewmodule, $viewfieldname, $viewconditions],
            );
        }
    }

    /**
     *  Function to add calendar view.
     * @return <string>
     */
    public static function addCalendarView(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userId = $currentUser->getId();
        $viewmodule = $request->get('viewmodule');
        $fieldName = $request->get('viewfieldname');
        $viewcolor = $request->get('viewColor');
        $viewconditions = $request->get('viewConditions', '');
        $viewfieldname = [];
        $viewfieldname = Zend_Json::encode(explode(',', $fieldName));

        $db = PearDatabase::getInstance();
        $queryResult = $db->pquery('SELECT id,isdefault FROM vtiger_calendar_default_activitytypes WHERE module=? AND fieldname=? AND conditions=?', [$viewmodule, $viewfieldname, $viewconditions]);
        $type = '';
        if ($db->num_rows($queryResult) > 0) {
            $defaultId = $db->query_result($queryResult, 0, 'id');
            $isDefault = $db->query_result($queryResult, 0, 'isdefault');

            if (in_array($viewmodule, ['Events', 'Calendar']) && $isDefault) {
                $type = $viewmodule . '_' . $isDefault;
            }

            $query = $db->pquery('SELECT 1 FROM vtiger_calendar_user_activitytypes WHERE defaultid=? AND userid=?', [$defaultId, $userId]);
            if ($db->num_rows($query) > 0) {
                $db->pquery(
                    'UPDATE vtiger_calendar_user_activitytypes 
							INNER JOIN vtiger_calendar_default_activitytypes ON vtiger_calendar_default_activitytypes.id = vtiger_calendar_user_activitytypes.defaultid
							SET vtiger_calendar_user_activitytypes.color=?, vtiger_calendar_user_activitytypes.visible=? 
							WHERE vtiger_calendar_user_activitytypes.userid=? AND vtiger_calendar_default_activitytypes.module=? AND vtiger_calendar_default_activitytypes.fieldname=? 
							AND vtiger_calendar_default_activitytypes.conditions=?',
                    [$viewcolor, '1', $userId, $viewmodule, $viewfieldname, $viewconditions],
                );
            } else {
                $db->pquery('INSERT INTO vtiger_calendar_user_activitytypes (id, defaultid, userid, color) VALUES (?,?,?,?)', [$db->getUniqueID('vtiger_calendar_user_activitytypes'), $defaultId, $userId, $viewcolor]);
            }
        } else {
            $defaultId = $db->getUniqueID('vtiger_calendar_default_activitytypes');
            $db->pquery('INSERT INTO vtiger_calendar_default_activitytypes (id, module, fieldname, defaultcolor, isdefault, conditions) VALUES (?,?,?,?,?,?)', [$defaultId, $viewmodule, $viewfieldname, $viewcolor, '0', $viewconditions]);

            $db->pquery('INSERT INTO vtiger_calendar_user_activitytypes (id, defaultid, userid, color) VALUES (?,?,?,?)', [$db->getUniqueID('vtiger_calendar_user_activitytypes'), $defaultId, $userId, $viewcolor]);
        }

        return $type;
    }

    /**
     *  Function to get all calendar view conditions.
     * @return <string>
     */
    public static function getCalendarViewConditions()
    {
        $eventsModuleModel = Vtiger_Module_Model::getInstance('Events');
        $eventTypePicklistValues = $eventsModuleModel->getField('activitytype')->getPicklistValues();
        $eventsModuleConditions = [];

        foreach ($eventTypePicklistValues as $picklistValue => $picklistLabel) {
            $eventsModuleConditions[$picklistLabel] = ['fieldname' => 'activitytype', 'operator' => 'e', 'value' => $picklistValue];
        }

        $conditions = [
            'Events' => $eventsModuleConditions,
        ];

        return $conditions;
    }

    /**
     *  Function returns the url for Shared Calendar view.
     * @return <String>
     */
    public function getSharedCalendarViewUrl()
    {
        return 'index.php?module=' . $this->get('name') . '&view=SharedCalendar';
    }

    /**
     * Function to delete shared users.
     * @param type $currentUserId
     */
    public function deleteSharedUsers($currentUserId)
    {
        $db = PearDatabase::getInstance();
        $delquery = 'DELETE FROM vtiger_sharedcalendar WHERE userid=?';
        $db->pquery($delquery, [$currentUserId]);
    }

    /**
     * Function to insert shared users.
     * @param type $currentUserId
     * @param type $sharedIds
     */
    public function insertSharedUsers($currentUserId, $sharedIds, $sharedType = false)
    {
        $db = PearDatabase::getInstance();
        foreach ($sharedIds as $sharedId) {
            if ($sharedId != $currentUserId) {
                $sql = 'INSERT INTO vtiger_sharedcalendar VALUES (?,?)';
                $db->pquery($sql, [$currentUserId, $sharedId]);
            }
        }
    }

    /**
     * Function to get shared type.
     * @param type $currentUserId
     */
    public static function getSharedType($currentUserId)
    {
        $db = PearDatabase::getInstance();

        $query = 'SELECT calendarsharedtype FROM vtiger_users WHERE id=?';
        $result = $db->pquery($query, [$currentUserId]);
        if ($db->num_rows($result) > 0) {
            $sharedType = $db->query_result($result, 0, 'calendarsharedtype');
        }

        return $sharedType;
    }

    /**
     * Function to get Alphabet Search Field.
     */
    public function getAlphabetSearchField()
    {
        return 'subject';
    }

    /**
     * Function to get the list of recently visisted records.
     * @param <Number> $limit
     * @return <Array> - List of Calendar_Record_Model
     */
    public function getRecentRecords($limit = 10)
    {
        $db = PearDatabase::getInstance();

        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $deletedCondition = parent::getDeletedRecordCondition();
        $nonAdminQuery .= Users_Privileges_Model::getNonAdminAccessControlQuery($this->getName());

        $query = 'SELECT * FROM vtiger_crmentity ';
        if ($nonAdminQuery) {
            $query .= ' INNER JOIN vtiger_activity ON vtiger_crmentity.crmid = vtiger_activity.activityid ' . $nonAdminQuery;
        }
        $query .= ' WHERE setype=? AND ' . $deletedCondition . ' AND modifiedby = ? ORDER BY modifiedtime DESC LIMIT ?';
        $params = ['Calendar', $currentUserModel->id, $limit];
        $result = $db->pquery($query, $params);
        $noOfRows = $db->num_rows($result);
        $recentRecords = [];
        for ($i = 0; $i < $noOfRows; ++$i) {
            $row = $db->query_result_rowdata($result, $i);
            $row['id'] = $row['crmid'];
            $recentRecords[$row['id']] = $this->getRecordFromArray($row);
        }

        return $recentRecords;
    }

    public function getAllTasksbyPriority($conditions = false, $pagingModel = false)
    {
        global $current_user;
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $db = PearDatabase::getInstance();

        $queryGenerator = new QueryGenerator('Calendar', $current_user);

        $moduleModel = Vtiger_Module_Model::getInstance('Calendar');
        $quickCreateFields = $moduleModel->getQuickCreateFields();
        $mandatoryFields = ['id', 'taskpriority', 'parent_id', 'contact_id'];
        $fields = array_unique(array_merge($mandatoryFields, array_keys($quickCreateFields)));
        $queryGenerator->setFields($fields);
        $queryGenerator->addCondition('activitytype', 'Task', 'e', 'AND');
        if ($conditions) {
            foreach ($conditions as $condition) {
                if ($condition['comparator'] === 'bw') {
                    $condition['fieldValue'] = implode(',', $condition['fieldValue']);
                }
                $queryGenerator->addCondition($condition['fieldName'], $condition['fieldValue'], $condition['comparator'], 'AND');
            }
        }
        $query = $queryGenerator->getQuery();

        $startIndex = $pagingModel->getStartIndex();
        $pageLimit = $pagingModel->getPageLimit();

        $query .= " LIMIT {$startIndex}," . ($pageLimit + 1);

        $result = $db->pquery($query, []);
        $noOfRows = $db->num_rows($result);

        $mandatoryReferenceFields = ['parent_id', 'contact_id'];
        $tasks = [];
        for ($i = 0; $i < $noOfRows; ++$i) {
            $newRow = $db->query_result_rowdata($result, $i);
            $model = Vtiger_Record_Model::getCleanInstance('Calendar');
            $model->setData($newRow);
            $model->setId($newRow['activityid']);
            $basicInfo = [];
            foreach ($quickCreateFields as $fieldName => $fieldModel) {
                if (in_array($fieldName, $mandatoryReferenceFields)) {
                    continue;
                }
                $columnName = $fieldModel->get('column');
                $fieldType = $fieldModel->getFieldDataType();
                $value = $model->get($columnName);
                switch ($fieldType) {
                    case 'reference':	if (!empty($value)) {
                        $value = ['id' => $value, 'display_value' => Vtiger_Functions::getCRMRecordLabel($value), 'module' => Vtiger_Functions::getCRMRecordType($value)];

                    }
                        break;
                    case 'datetime':	$value = Vtiger_Date_UIType::getDisplayDateValue($value);
                        break;

                        // Assigning the time value to the basic info, which we use for the quick edit of tasks ex: start time.
                    case 'time':		$value = Vtiger_Time_UIType::getDisplayTimeValue($value);
                        $value = $fieldModel->getDisplayValue($value);
                        $hourFormat = $currentUserModel->get('hour_format');
                        if ($hourFormat == '24') {
                            $value = date('H:i', strtotime($value));
                        }
                        break;
                }
                $basicInfo[$fieldName] = $value;
            }

            foreach ($mandatoryReferenceFields as $fieldName) {
                if ($fieldName == 'parent_id') {
                    $value = $model->get('crmid');
                } else {
                    $value = $model->get('contactid');
                }
                if (!empty($value)) {
                    $value = ['id' => $value, 'display_value' => Vtiger_Functions::getCRMRecordLabel($value), 'module' => Vtiger_Functions::getCRMRecordType($value)];

                }
                $basicInfo[$fieldName] = $value;
            }

            $model->set('basicInfo', $basicInfo);

            $priority = $model->get('priority');
            if ($priority) {
                $tasks[$priority][$model->getId()] = $model;
            }
        }

        if (isset($priority, $tasks[$priority])   && php7_count($tasks[$priority]) > $pageLimit) {
            array_pop($tasks[$priority]);
            $pagingModel->set('nextPageExists', true);
        } else {
            $pagingModel->set('nextPageExists', false);
        }

        return $tasks;
    }

    /**
     * Function returns Calendar Reminder record models.
     * @return <Array of Calendar_Record_Model>
     */
    public static function getCalendarReminder()
    {
        $db = PearDatabase::getInstance();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $activityReminder = $currentUserModel->getCurrentUserActivityReminderInSeconds();
        $recordModels = [];

        if ($activityReminder != '') {
            $currentTime = time();
            $date = date('Y-m-d', strtotime("+{$activityReminder} seconds", $currentTime));
            $time = date('H:i', strtotime("+{$activityReminder} seconds", $currentTime));
            $dateAndTime = $date . ' ' . $time;
            $reminderActivitiesResult = "SELECT reminderid, recordid FROM vtiger_activity_reminder_popup
								INNER JOIN vtiger_activity on vtiger_activity.activityid = vtiger_activity_reminder_popup.recordid
								INNER JOIN vtiger_crmentity ON vtiger_activity_reminder_popup.recordid = vtiger_crmentity.crmid
								WHERE vtiger_activity_reminder_popup.status = 0
								AND vtiger_crmentity.smownerid = ? AND vtiger_crmentity.deleted = 0
								and CONCAT(
									DATE_FORMAT(vtiger_activity_reminder_popup.date_start,'%Y-%m-%d'),
									' ',
									TIME_FORMAT(vtiger_activity_reminder_popup.time_start,'%H:%i')
								) <= ?
								AND vtiger_activity.eventstatus <> 'Held' AND (vtiger_activity.status <> 'Completed' OR vtiger_activity.status IS NULL) LIMIT 20";
            $result = $db->pquery($reminderActivitiesResult, [$currentUserModel->getId(), $dateAndTime]);
            $rows = $db->num_rows($result);
            for ($i = 0; $i < $rows; ++$i) {
                $recordId = $db->query_result($result, $i, 'recordid');
                $recordModels[] = Vtiger_Record_Model::getInstanceById($recordId, 'Calendar');
            }
        }

        return $recordModels;
    }

    /**
     * Function gives fields based on the type.
     * @param <String> $type - field type
     * @return <Array of Vtiger_Field_Model> - list of field models
     */
    public function getFieldsByType($type)
    {
        $restrictedField = ['picklist' => ['eventstatus', 'recurringtype', 'visibility', 'duration_minutes']];

        if (!is_array($type)) {
            $type = [$type];
        }
        $fields = $this->getFields();
        $fieldList = [];
        foreach ($fields as $field) {
            $fieldType = $field->getFieldDataType();
            if (in_array($fieldType, $type)) {
                $fieldName = $field->getName();
                if ($fieldType == 'picklist' && in_array($fieldName, $restrictedField[$fieldType])) {
                } else {
                    $fieldList[$fieldName] = $field;
                }
            }
        }

        return $fieldList;
    }

    /**
     * Function returns Settings Links.
     * @return array
     */
    public function getSettingLinks()
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $settingLinks = [];

        if ($currentUserModel->isAdminUser()) {
            $settingLinks[] = [
                'linktype' => 'LISTVIEWSETTING',
                'linklabel' => 'LBL_EDIT_FIELDS',
                'linkurl' => 'index.php?parent=Settings&module=LayoutEditor',
                'linkicon' => Vtiger_Theme::getImagePath('LayoutEditor.gif'),
            ];

            $settingLinks[] = [
                'linktype' => 'LISTVIEWSETTING',
                'linklabel' => 'LBL_EDIT_WORKFLOWS',
                'linkurl' => 'index.php?parent=Settings&module=Workflows&view=List',
                'linkicon' => '',
            ];
            $settingLinks[] = [
                'linktype' => 'LISTVIEWSETTING',
                'linklabel' => 'LBL_EDIT_PICKLIST_VALUES',
                'linkurl' => 'index.php?parent=Settings&module=Picklist&view=Index&source_module=' . $this->getName(),
                'linkicon' => '',
            ];
        }

        return $settingLinks;
    }

    /**
     * Function to get orderby sql from orderby field.
     */
    public function getOrderBySql($orderBy)
    {
        if ($orderBy == 'status') {
            return $orderBy;
        }

        return parent::getOrderBySql($orderBy);
    }

    /**
     * Function is used to give links in the All menu bar.
     */
    public function getQuickMenuModels()
    {
        if ($this->isEntityModule()) {
            $moduleName = $this->getName();
            $listViewModel = Vtiger_ListView_Model::getCleanInstance($moduleName);
            $basicListViewLinks = $listViewModel->getBasicLinks();
        }

        if ($basicListViewLinks) {
            foreach ($basicListViewLinks as $basicListViewLink) {
                if (is_array($basicListViewLink)) {
                    $links[] = Vtiger_Link_Model::getInstanceFromValues($basicListViewLink);
                } elseif (is_a($basicListViewLink, 'Vtiger_Link_Model')) {
                    $links[] = $basicListViewLink;
                }
            }
        }

        return $links;
    }

    /*
     * Function to get supported utility actions for a module
     */
    public function getUtilityActionsNames()
    {
        return ['Import', 'Export'];
    }

    /**
     * Function which will be give you the actions that are allowed when this module is added as a tab.
     */
    public function getRelationShipActions()
    {
        return ['ADD'];
    }

    public function getModuleIcon($activityType = null)
    {
        $moduleName = $this->getName();
        $title = vtranslate($moduleName, $moduleName);

        if (!$activityType) {
            if ($moduleName == 'Events') {
                $activityType = 'calendar';
            }
        }

        $activityType = strtolower($activityType ? $activityType : '');
        $moduleIcon = "<i class='vicon-{$activityType}' title='{$title}' ></i>";

        if (!in_array($activityType, ['task', 'calendar'])) {
            $moduleIcon = parent::getModuleIcon();
        }

        return $moduleIcon;
    }
}
