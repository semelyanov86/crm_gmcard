<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

require_once 'modules/WSAPP/Utils.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/Zend/Json.php';
require_once 'include/utils/utils.php';

class SyncServer
{
    private $appkey;

    private $syncModule;

    private $destHandler;

    private $create = 'create';

    private $update = 'update';

    private $delete = 'delete';

    private $save = 'save';

    private $syncTypes = ['user', 'app', 'userandgroup'];

    /**
     * Lookup application id using the key provided.
     */
    public function appid_with_key($key)
    {
        $db = PearDatabase::getInstance();
        $appidresult = $db->pquery('SELECT appid FROM vtiger_wsapp WHERE appkey=?', [$key]);
        if ($db->num_rows($appidresult)) {
            return $db->query_result($appidresult, 0, 'appid');
        }

        return false;
    }

    /**
     * Retrieve serverid-clientid record map information for the given
     * application and serverid.
     */
    public function idmap_get_clientmap($appid, $serverids)
    {
        if (!is_array($serverids)) {
            $serverids = [$serverids];
        }
        $db = PearDatabase::getInstance();
        $result = $db->pquery(sprintf(
            "SELECT serverid, clientid,clientmodifiedtime,servermodifiedtime,id FROM vtiger_wsapp_recordmapping WHERE appid=? AND serverid IN ('%s')",
            implode("','", $serverids),
        ), [$appid]);

        $mapping = [];
        if ($db->num_rows($result)) {
            while ($row = $db->fetch_array($result)) {
                $mapping[$row['serverid']] = ['clientid' => $row['clientid'], 'clientmodifiedtime' => $row['clientmodifiedtime'],
                    'servermodifiedtime' => $row['servermodifiedtime'], 'id' => $row['id']];
            }
        }

        return $mapping;
    }

    /**
     * Retrieve serverid-clientid record map information for the given
     * application and client.
     */
    public function idmap_get_clientservermap($appid, $clientids)
    {
        if (!is_array($clientids)) {
            $clientids = [$clientids];
        }

        $db = PearDatabase::getInstance();

        $result = $db->pquery(sprintf(
            "SELECT serverid, clientid FROM vtiger_wsapp_recordmapping WHERE appid=? AND clientid IN ('%s')",
            implode("','", $clientids),
        ), [$appid]);
        $mapping = [];
        if ($db->num_rows($result)) {
            while ($row = $db->fetch_array($result)) {
                $mapping[$row['clientid']] = $row['serverid'];
            }
        }

        return $mapping;
    }

    public function idmap_storeRecordsInQueue($syncServerId, $recordDetails, $flag, $appid)
    {
        if (!is_array($recordDetails)) {
            $recordDetails = [$recordDetails];
        }
        $db = PearDatabase::getInstance();
        $params = [];
        $params[] = $syncServerId;
        $params[] = Zend_Json::encode($recordDetails);
        $params[] = $flag;
        $params[] = $appid;
        $db->pquery('INSERT INTO vtiger_wsapp_queuerecords(syncserverid,details,flag,appid) VALUES(?,?,?,?)', [$params]);
    }

    public function checkIdExistInQueue($syncServerId)
    {
        $db = PearDatabase::getInstance();
        $checkQuery = 'SELECT syncserverid FROM vtiger_wsapp_queuerecords WHERE syncserverid=?';
        $result = $db->pquery($checkQuery, [$syncServerId]);
        if ($db->num_rows($result) > 0) {
            return true;
        }

        return false;
    }

    public function markRecordAsDeleteForAllCleints($recordValues)
    {
        $recordWsId = $recordValues['id'];
        $modifiedTime = $recordValues['modifiedtime'];
        $db = PearDatabase::getInstance();
        $query = 'SELECT * FROM vtiger_wsapp_recordmapping WHERE serverid=? and servermodifiedtime < ?';
        $params = [$recordWsId, $modifiedTime];
        $result = $db->pquery($query, $params);

        while ($arre = $db->fetchByAssoc($result)) {
            $syncServerId = $arre['id'];
            $clientId = $arre['clientid'];
            $clientMappedId = $arre['appid'];
            if (!$this->checkIdExistInQueue($syncServerId)) {
                $this->idmap_storeRecordsInQueue($syncServerId, $recordValues, $this->delete, $clientMappedId);
            }
        }
    }

    public function getSyncServerId($clientId, $serverId, $clientAppId)
    {
        $db = PearDatabase::getInstance();
        $syncServerId = null;
        $query = 'SELECT id FROM vtiger_wsapp_recordmapping WHERE clientid=? and serverid=? and appid=?';
        $result = $db->pquery($query, [$clientId, $serverId, $clientAppId]);
        if ($db->num_rows($result) > 0) {
            $syncServerId = $db->query_result($result, 0, 'id');
        }

        return $syncServerId;
    }

    public function deleteQueueRecords($syncServerIdList)
    {
        $db = PearDatabase::getInstance();
        $deleteQuery = 'DELETE FROM vtiger_wsapp_queuerecords WHERE syncserverid IN (' . generateQuestionMarks($syncServerIdList) . ')';
        $result = $db->pquery($deleteQuery, $syncServerIdList);
    }

    /**
     * Create serverid-clientid record map for the application.
     */
    public function idmap_put($appid, $serverid, $clientid, $clientModifiedTime, $serverModifiedTime, $serverAppId, $mode = 'save')
    {
        $db = PearDatabase::getInstance();
        if ($mode == $this->create) {
            $this->idmap_create($appid, $serverid, $clientid, $clientModifiedTime, $serverModifiedTime, $serverAppId);
        } elseif ($mode == $this->update) {
            $this->idmap_update($appid, $serverid, $clientid, $clientModifiedTime, $serverModifiedTime, $serverAppId);
        } elseif ($mode == $this->save) {
            $result = $db->pquery('SELECT 1 FROM vtiger_wsapp_recordmapping WHERE appid=? and serverid=? and clientid=?', [$appid, $serverid, $clientid]);
            if ($db->num_rows($result) <= 0) {
                $this->idmap_create($appid, $serverid, $clientid, $clientModifiedTime, $serverModifiedTime, $serverAppId);
            } else {
                $this->idmap_update($appid, $serverid, $clientid, $clientModifiedTime, $serverModifiedTime, $serverAppId);
            }
        } elseif ($mode == $this->delete) {
            $this->idmap_delete($appid, $serverid, $clientid, $serverAppId);
        }
    }

    public function idmap_create($appid, $serverid, $clientid, $clientModifiedTime, $serverModifiedTime, $serverAppId)
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'INSERT INTO vtiger_wsapp_recordmapping (appid, serverid, clientid,clientmodifiedtime,servermodifiedtime,serverappid) VALUES (?,?,?,?,?,?)',
            [$appid, $serverid, $clientid, $clientModifiedTime, $serverModifiedTime, $serverAppId],
        );
    }

    /**
     * @param <type> $appid
     * @param <type> $serverid
     * @param <type> $clientid
     * @param <type> $modifiedTime
     * update the mapping of server and client id
     */
    public function idmap_update($appid, $serverid, $clientid, $clientModifiedTime, $serverModifiedTime, $serverAppId)
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'UPDATE vtiger_wsapp_recordmapping SET clientmodifiedtime=?,servermodifiedtime=? WHERE appid=? and serverid=? and clientid=? and serverappid=?',
            [$clientModifiedTime, $serverModifiedTime, $appid, $serverid, $clientid, $serverAppId],
        );
    }

    /**
     * @param <type> $appid
     * @param <type> $serverid
     * @param <type> $clientid
     * delete the mapping for client and server id
     */
    public function idmap_delete($appid, $serverid, $clientid, $serverAppId)
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'DELETE FROM vtiger_wsapp_recordmapping WHERE appid=? and serverid=? and clientid=? and serverappid=?',
            [$appid, $serverid, $clientid, $serverAppId],
        );
    }

    public function idmap_updateMapDetails($appid, $clientid, $clientModifiedTime, $serverModifiedTime)
    {
        $db = PearDatabase::getInstance();
        $db->pquery(
            'UPDATE vtiger_wsapp_recordmapping SET clientmodifiedtime=?,servermodifiedtime=? WHERE appid=? and clientid=?',
            [$clientModifiedTime, $serverModifiedTime, $appid, $clientid],
        );
    }

    public function getDestinationHandleDetails()
    {
        return wsapp_getHandler('vtigerCRM');
    }


    /*
     * Web services
     */

    /**
     * Register the application.
     */
    public function register($name, $type)
    {
        if (empty($name)) {
            throw new WebServiceException('WSAPP01', 'No type specified');
        }
        if (empty($type)) {
            throw new WebServiceException('WSAPP06', 'No sync type specified');
        }
        if (is_array($name)) {
            throw new WebServiceException('WSAPP07', 'type is in the wrong format');
        }
        $type = strtolower($type);
        if (!in_array($type, $this->syncTypes)) {
            throw new WebServiceException('WSAPP05', 'Wrong sync type specified');
        }
        $db = PearDatabase::getInstance();
        $uid = uniqid();
        $db->pquery('INSERT INTO vtiger_wsapp (name, appkey,type) VALUES(?,?,?)', [$name, $uid, $type]);

        return ['key' => $uid];
    }

    /**
     * Deregister the application.
     */
    public function deregister($name, $key, $user)
    {
        if (!empty($name) && !empty($key)) {
            $db = PearDatabase::getInstance();
            $uid = uniqid();
            $db->pquery(
                'DELETE FROM vtiger_wsapp_recordmapping WHERE appid=(SELECT appid FROM vtiger_wsapp WHERE name=? AND appkey=?)',
                [$name, $key],
            );
            $db->pquery('DELETE FROM vtiger_wsapp WHERE name=? AND appkey=?', [$name, $key]);
        }

        return  [$name, $key];
    }

    /**
     * Handles Create/Update/Delete operations on record.
     */
    public function put($key, $element, $user)
    {
        $db = PearDatabase::getInstance();
        $appid = $this->appid_with_key($key);

        if (empty($appid)) {
            throw new WebServiceException('WSAPP04', 'Access restricted to app');
        }

        if (!is_array($element)) {
            $records = [$element];
        } else {
            $records = $element;
        }

        // hardcoded since the destination handler will be vtigerCRM
        $serverKey = wsapp_getAppKey('vtigerCRM');
        $serverAppId = $this->appid_with_key($serverKey);
        $handlerDetails  = $this->getDestinationHandleDetails();
        $clientApplicationSyncType = wsapp_getAppSyncType($key);
        require_once $handlerDetails['handlerpath'];
        $this->destHandler = new $handlerDetails['handlerclass']($serverKey);
        $this->destHandler->setClientSyncType($clientApplicationSyncType);

        $recordDetails = [];

        $createRecords = [];
        $updateRecords = [];
        $deleteRecords = [];

        $clientModifiedTimeList = [];
        foreach ($records as $record) {
            $recordDetails = [];
            $clientRecordId = $record['id'];

            // Missing client record id?
            if (empty($clientRecordId)) {
                continue;
            }

            $lookupRecordId = false;
            $lookupResult = $db->pquery('SELECT serverid,clientmodifiedtime FROM vtiger_wsapp_recordmapping WHERE appid=? AND clientid COLLATE utf8_bin =?', [$appid, $clientRecordId]);
            if ($db->num_rows($lookupResult)) {
                $lookupRecordId = $db->query_result($lookupResult, 0, 'serverid');
            }
            if (empty($lookupRecordId) && $record['mode'] != 'delete') {
                $createRecords[$clientRecordId] = $record['values'];
                $createRecords[$clientRecordId]['module'] = $record['module'];
                $clientModifiedTimeList[$clientRecordId] = $record['values']['modifiedtime'];
            } else {
                if (empty($record['values']) && !(empty($lookupRecordId))) {
                    $deleteRecords[$clientRecordId] = $lookupRecordId;
                } elseif (!(empty($lookupRecordId))) {
                    $clientLastModifiedTime = $db->query_result($lookupResult, 0, 'clientmodifiedtime');
                    if ($clientLastModifiedTime >= $record['values']['modifiedtime']) {
                        continue;
                    }
                    $record['values']['id'] = $lookupRecordId;
                    $updateRecords[$clientRecordId] = $record['values'];
                    $updateRecords[$clientRecordId]['module'] = $record['module'];
                    $clientModifiedTimeList[$clientRecordId] = $record['values']['modifiedtime'];

                }
            }
        }

        $recordDetails['created'] = $createRecords;
        $recordDetails['updated'] = $updateRecords;
        $recordDetails['deleted'] = $deleteRecords;
        $result = $this->destHandler->put($recordDetails, $user);

        $response = [];
        $response['created'] = [];
        $response['updated'] = [];
        $response['deleted'] = [];
        $clientID2ServerIDMap = [];

        $nextSyncDeleteRecords = $this->destHandler->getAssignToChangedRecords();
        foreach ($result['created'] as $clientRecordId => $record) {
            $this->idmap_put($appid, $record['id'], $clientRecordId, $clientModifiedTimeList[$clientRecordId], $record['modifiedtime'], $serverAppId);
            $responseRecord = $record;
            $responseRecord['_id'] = $record['id'];
            $responseRecord['id'] = $clientRecordId;
            $responseRecord['_modifiedtime'] = $record['modifiedtime'];
            $responseRecord['modifiedtime'] = $clientModifiedTimeList[$clientRecordId];
            $response['created'][] = $responseRecord;
        }
        foreach ($result['updated'] as $clientRecordId => $record) {
            $this->idmap_put($appid, $record['id'], $clientRecordId, $clientModifiedTimeList[$clientRecordId], $record['modifiedtime'], $serverAppId);
            $responseRecord = $record;
            $responseRecord['_id'] = $record['id'];
            $responseRecord['id'] = $clientRecordId;
            $responseRecord['_modifiedtime'] = $record['modifiedtime'];
            $responseRecord['modifiedtime'] = $clientModifiedTimeList[$clientRecordId];
            $response['updated'][] = $responseRecord;
        }
        foreach ($result['deleted'] as $clientRecordId => $record) {
            $this->idmap_put($appid, $record, $clientRecordId, '', '', $serverAppId, $this->delete);
            $response['deleted'][] = $clientRecordId;
            $clientID2ServerIDMap[$clientRecordId] = $record; // For WSAPP logs
        }
        $queueRecordIds = [];
        $queueRecordDetails = [];
        foreach ($nextSyncDeleteRecords as $clientRecordId => $record) {
            $queueRecordIds[] = $record['id'];
            $queueRecordDetails[$record['id']] = $this->convertToQueueRecordFormat($record, $this->delete);
        }
        if (php7_count($queueRecordIds > 0)) {
            $syncServerDetails = $this->idmap_get_clientmap($appid, $queueRecordIds);
            foreach ($queueRecordIds as $serverId) {
                $syncServerId = $syncServerDetails[$serverId]['id'];
                $recordValues = $queueRecordDetails[$serverId];
                if (!$this->checkIdExistInQueue($syncServerId)) {
                    $this->idmap_storeRecordsInQueue($syncServerId, $recordValues, $this->delete, $appid);
                }
            }
        }

        if (array_key_exists('skipped', $result)) {
            $response['skipped'] = $result['skipped'];
        }

        $response['client2serverIdMap'] = $clientID2ServerIDMap;

        return $response;
    }

    /**
     * Share the Create/Update/Delete state information.
     */
    public function get($key, $module, $token, $user)
    {
        $db = PearDatabase::getInstance();
        $appid = $this->appid_with_key($key);
        if (empty($appid)) {
            throw new WebServiceException('WSAPP04', 'Access restricted to app');
        }
        $clientApplicationSyncType = wsapp_getAppSyncType($key);
        // hardcoded since the destination handler will be vtigerCRM
        $serverKey = wsapp_getAppKey('vtigerCRM');
        $handlerDetails  = $this->getDestinationHandleDetails();
        require_once $handlerDetails['handlerpath'];
        $this->destHandler = new $handlerDetails['handlerclass']($serverKey);
        $this->destHandler->setClientSyncType($clientApplicationSyncType);
        $result = $this->destHandler->get($module, $token, $user);
        // Lookup Ids
        $updatedIds = [];
        $deletedIds = [];
        foreach ($result['updated'] as $u) {
            $updatedIds[] = $u['id'];
        }
        foreach ($result['deleted'] as $d) {
            $deletedIds[] = $d;
        }
        $syncServerDeleteIds = $this->getQueueDeleteRecord($appid);
        foreach ($syncServerDeleteIds as $deleteServerId) {
            $deletedIds[] = $deleteServerId;
        }

        $updateDeleteCommonIds = array_values(array_intersect($updatedIds, $deletedIds));
        // if the record exist in both the update and delete , then send record as update
        // and unset the id from deleted list
        $deletedIds = array_diff($deletedIds, $updateDeleteCommonIds);

        $updatedLookupIds = $this->idmap_get_clientmap($appid, $updatedIds);
        $deletedLookupIds = $this->idmap_get_clientmap($appid, $deletedIds);
        $filteredCreates = [];
        $filteredUpdates = [];
        foreach ($result['updated'] as $u) {
            if (in_array($u['id'], $updatedIds)) {
                if (isset($updatedLookupIds[$u['id']]) && ($u['modifiedtime'] > $updatedLookupIds[$u['id']]['servermodifiedtime'])) {
                    $u['_id'] = $u['id'];
                    $u['id'] = $updatedLookupIds[$u['id']]['clientid']; // Replace serverid with clientid
                    $u['_modifiedtime'] = $u['modifiedtime'];
                    $filteredUpdates[] = $u;
                } elseif (empty($updatedLookupIds[$u['id']])) {
                    $u['_id'] = $u['id']; // Rename the id key
                    $u['_modifiedtime'] = $u['modifiedtime'];
                    unset($u['id']);
                    $filteredCreates[] = $u;
                }
            }
        }

        $filteredDeletes = [];
        $clientID2ServerIDMap = [];
        foreach ($deletedIds as $d) {
            if (isset($deletedLookupIds[$d])) {
                $clientId = $deletedLookupIds[$d]['clientid'];
                $filteredDeletes[] = $clientId; // Replace serverid with clientid;
                $clientID2ServerIDMap[$clientId] = $d; // For WSAPP logs
            }
        }

        $result['created'] = $filteredCreates;
        $result['updated'] = $filteredUpdates;
        $result['deleted'] = $filteredDeletes;
        $result['client2serverIdMap'] = $clientID2ServerIDMap;

        return $result;
    }

    /**
     * Update the missing serverid-clientid map as requested from application.
     */
    public function map($key, $element, $user)
    {
        if (empty($element)) {
            return;
        }
        $db = PearDatabase::getInstance();
        $appid = $this->appid_with_key($key);
        $createDetails = $element['create'];
        $deleteDetails = $element['delete'];
        $updatedDetails = $element['update'];
        $deleteQueueSyncServerIds = [];
        $serverKey = wsapp_getAppKey('vtigerCRM');
        $serverAppId = $this->appid_with_key($serverKey);
        // $lookups = $this->idmap_get_clientmap($appid, array_values($createDetails));
        foreach ($createDetails as $clientid => $serverDetails) {
            $this->idmap_put($appid, $serverDetails['serverid'], $clientid, $serverDetails['modifiedtime'], $serverDetails['_modifiedtime'], $serverAppId);
        }
        foreach ($updatedDetails as $clientid => $serverDetails) {
            $this->idmap_updateMapDetails($appid, $clientid, $serverDetails['modifiedtime'], $serverDetails['_modifiedtime'], $this->update);
            $syncServerId = $this->getSyncServerId($clientid, $serverDetails['serverid'], $appid);
            if (isset($syncServerId) && $syncServerId != null) {
                $deleteQueueSyncServerIds[] = $syncServerId;
            }
        }
        if (php7_count($deleteDetails) > 0) {
            $deleteLookUps = $this->idmap_get_clientservermap($appid, array_values($deleteDetails));
            foreach ($deleteDetails as $clientid) {
                if (isset($deleteLookUps[$clientid])) {
                    $serverId = $deleteLookUps[$clientid];
                    $syncServerId = $this->getSyncServerId($clientid, $serverId, $appid);
                    if (isset($syncServerId) && $syncServerId != null) {
                        $deleteQueueSyncServerIds[] = $syncServerId;
                    }
                    $this->idmap_delete($appid, $serverId, $clientid, $serverAppId);
                }

            }
        }
        if (php7_count($deleteQueueSyncServerIds) > 0) {
            $this->deleteQueueRecords($deleteQueueSyncServerIds);
        }
    }

    public function getQueueDeleteRecord($appId)
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT * FROM vtiger_wsapp_queuerecords
								INNER JOIN vtiger_wsapp_recordmapping ON (vtiger_wsapp_recordmapping.id=vtiger_wsapp_queuerecords.syncserverid)
								WHERE vtiger_wsapp_recordmapping.appid=? ', [$appId]);
        $serverIds = [];
        $num_rows = $db->num_rows($result);
        for ($i = 0; $i < $num_rows; ++$i) {
            $serverId = $db->query_result($result, $i, 'serverid');
            $serverIds[] = $serverId;
        }

        return $serverIds;
    }

    public function convertToQueueRecordFormat($record, $flag)
    {
        if ($flag != $this->delete) {
            return $record;
        }

        $recordFormat = [];
        $recordFormat['id'] = $record['id'];

        return $recordFormat;

    }

    /**
     * Retrieve serverid  of record   for the given
     *  client.
     */
    public function idmap_get_serverId($clientid, $appId)
    {

        $db = PearDatabase::getInstance();

        $result = $db->pquery('SELECT serverid, clientid FROM vtiger_wsapp_recordmapping WHERE  clientid = ? and appid=?', [$clientid, $appId]);
        $mapping = [];
        if ($db->num_rows($result)) {
            while ($row = $db->fetch_array($result)) {
                return $row['serverid'];
            }
        }

        return false;
    }

    /**
     * Retrieve clientid  of record   for the given
     *  client.
     */
    public function idmap_get_clientId($serverid, $appId)
    {

        $db = PearDatabase::getInstance();

        $result = $db->pquery('SELECT serverid, clientid FROM vtiger_wsapp_recordmapping WHERE  serverid = ? and appid=?', [$serverid, $appId]);
        $mapping = [];
        if ($db->num_rows($result)) {
            while ($row = $db->fetch_array($result)) {
                return $row['clientid'];
            }
        }

        return false;
    }
}
