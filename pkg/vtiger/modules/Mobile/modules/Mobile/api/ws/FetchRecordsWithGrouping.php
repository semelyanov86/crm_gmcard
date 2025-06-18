<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'include/Webservices/Query.php';

include_once dirname(__FILE__) . '/FetchRecordWithGrouping.php';
include_once dirname(__FILE__) . '/models/Alert.php';

class Mobile_WS_FetchRecordsWithGrouping extends Mobile_WS_FetchRecordWithGrouping
{
    public function process(Mobile_API_Request $request)
    {
        $response = new Mobile_API_Response();
        $current_user = $this->getActiveUser();

        $module = $request->get('module');
        $moduleWSID = Mobile_WS_Utils::getEntityModuleWSId($module);

        if (empty($module)) {
            $response->setError(1_501, 'Module not specified.');

            return $response;
        }

        $records = [];

        // Fetch the request parameters
        $idlist = $request->get('ids');
        $alertid = $request->get('alertid');

        // List of ids specified?
        if (!empty($idlist)) {
            $idlist = Zend_Json::decode($idlist);
            $records = $this->fetchRecordsWithId($module, $idlist, $current_user);
        }
        // Alert id specified?
        elseif (!empty($alertid)) {
            $alert = Mobile_WS_AlertModel::modelWithId($alertid);
            if ($alert === false) {
                $response->setError(1_404, 'Alert not found.');
                $records = false;
            }

            $alert->setUser($current_user);
            $records = $this->fetchAlertRecords($module, $alert);
        }

        if ($records !== false) {
            $response->setResult(['records' => $records]);
        }

        return $response;
    }

    public function fetchRecordsWithId($module, $idlist, $user)
    {
        if (empty($idlist)) {
            return [];
        }

        $wsresult = vtws_query(sprintf("SELECT * FROM {$module} WHERE id IN ('%s');", implode("','", $idlist)), $user);
        if (!empty($wsresult)) {
            $resolvedRecords = [];
            foreach ($wsresult as $record) {
                $this->resolveRecordValues($record, $user);
                $resolvedRecords[] = $this->transformRecordWithGrouping($record, $module, false);
            }
        }

        return $resolvedRecords;
    }

    public function fetchAlertRecords($module, $alert)
    {
        global $adb;

        // Initialize global variable: ($alert->query() could indirectly depend if its using Module API as its base)
        global $current_user;
        if (!isset($current_user)) {
            $current_user = $alert->getUser();
        }

        $moduleWSID = Mobile_WS_Utils::getEntityModuleWSId($module);

        $alertResult = $adb->pquery($alert->query(), $alert->queryParameters());
        $alertRecords = [];

        // For Calendar module there is a need for merging Todo's
        if ($module == 'Calendar') {
            $eventsWSID = Mobile_WS_Utils::getEntityModuleWSId('Events');
            $eventIds = [];
            $taskIds = [];

            while ($resultrow = $adb->fetch_array($alertResult)) {
                if (isset($resultrow['activitytype']) && $resultrow['activitytype'] == 'Task') {
                    $taskIds[] = "{$moduleWSID}x" . $resultrow['crmid'];
                } else {
                    $eventIds[] = "{$eventsWSID}x" . $resultrow['crmid'];
                }
            }
            $alertRecords = $this->fetchRecordsWithId($module, $taskIds, $alert->getUser());
            if (!empty($eventIds)) {
                $alertRecords = array_merge($alertRecords, $this->fetchRecordsWithId('Events', $eventIds, $alert->getUser()));
            }
        } else {
            $fetchIds = [];

            while ($resultrow = $adb->fetch_array($alertResult)) {
                $fetchIds[] = "{$moduleWSID}x" . $resultrow['crmid'];
            }
            $alertRecords = $this->fetchRecordsWithId($module, $fetchIds, $alert->getUser());
        }

        return $alertRecords;
    }
}
