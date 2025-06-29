<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

require_once 'modules/WSAPP/WSAPP.php';
require_once 'include/Webservices/Utils.php';
require_once 'include/database/PearDatabase.php';
require_once 'include/Webservices/GetUpdates.php';
require_once 'include/utils/CommonUtils.php';
require_once 'modules/WSAPP/Utils.php';
require_once 'include/Webservices/Update.php';
require_once 'include/Webservices/Revise.php';
require_once 'modules/WSAPP/Handlers/SyncHandler.php';

class vtigerCRMHandler extends SyncHandler
{
    private $assignToChangedRecords;

    protected $clientSyncType = 'user';

    public function __construct($appkey)
    {
        $this->key = $appkey;
        $this->assignToChangedRecords = [];
    }

    public function get($module, $token, $user)
    {
        $syncModule = $module;
        $this->user = $user;
        $syncModule = $module;
        $syncType = 'user';
        if (!$this->isClientUserSyncType()) {
            if ($this->isClientUserAndGroupSyncType()) {
                $syncType = 'userandgroup';
            } else {
                $syncType = 'application';
            }
        }
        $result = vtws_sync($token, $syncModule, $syncType, $this->user);
        $result['updated'] = $this->translateTheReferenceFieldIdsToName($result['updated'], $syncModule, $user);

        return $this->nativeToSyncFormat($result);
    }

    public function put($recordDetails, $user)
    {
        global $current_user;
        $current_user = $user;
        $this->user = $user;
        $recordDetails = $this->syncToNativeFormat($recordDetails);
        $createdRecords = $recordDetails['created'];
        $updatedRecords = $recordDetails['updated'];
        $deletedRecords = $recordDetails['deleted'];
        $updateDuplicateRecords = [];

        if (php7_count($createdRecords) > 0) {
            $createdRecords = $this->translateReferenceFieldNamesToIds($createdRecords, $user);
            $createdRecords = $this->fillNonExistingMandatoryPicklistValues($createdRecords);
            $createdRecords = $this->fillMandatoryFields($createdRecords, $user);
        }
        foreach ($createdRecords as $index => $record) {
            try {
                $createdRecords[$index] = vtws_create($record['module'], $record, $this->user);
            } catch (DuplicateException $e) {
                $skipped = true;
                $duplicateRecordIds = $e->getDuplicateRecordIds();
                $duplicatesResult = $this->triggerSyncActionForDuplicate($record, $duplicateRecordIds);

                if ($duplicatesResult) {
                    $updateDuplicateRecords[$index] = $duplicatesResult;
                    $skipped = false;
                }
                if ($skipped) {
                    $recordDetails['skipped'][] = ['record' => $createdRecords[$index],
                        'messageidentifier' => '',
                        'message' => $e->getMessage()];
                }
                unset($createdRecords[$index]);

                continue;
            } catch (Exception $e) {
                $recordDetails['skipped'][] = ['record' => $createdRecords[$index],
                    'messageidentifier' => '',
                    'message' => $e->getMessage()];
                unset($createdRecords[$index]);

                continue;
            }
        }

        if (php7_count($updatedRecords) > 0) {
            $updatedRecords = $this->translateReferenceFieldNamesToIds($updatedRecords, $user);
        }

        $crmIds = [];

        foreach ($updatedRecords as $index => $record) {
            $webserviceRecordId = $record['id'];
            $recordIdComp = vtws_getIdComponents($webserviceRecordId);
            $crmIds[] = $recordIdComp[1];
        }
        $assignedRecordIds = [];
        if ($this->isClientUserSyncType() || $this->isClientUserAndGroupSyncType()) {
            $assignedRecordIds = wsapp_checkIfRecordsAssignToUser($crmIds, $this->user->id);
            // To check if the record assigned to group
            if ($this->isClientUserAndGroupSyncType()) {
                $groupIds = $this->getGroupIds($this->user->id);
                foreach ($groupIds as $group) {
                    $groupRecordId = wsapp_checkIfRecordsAssignToUser($crmIds, $group);
                    $assignedRecordIds = array_merge($assignedRecordIds, $groupRecordId);
                }
            }
            //  End
        }
        foreach ($updatedRecords as $index => $record) {
            $webserviceRecordId = $record['id'];
            $recordIdComp = vtws_getIdComponents($webserviceRecordId);

            try {
                if (in_array($recordIdComp[1], $assignedRecordIds)) {
                    $updatedRecords[$index] = vtws_revise($record, $this->user);
                } elseif (!$this->isClientUserSyncType()) {
                    $updatedRecords[$index] = vtws_revise($record, $this->user);
                } else {
                    $this->assignToChangedRecords[$index] = $record;
                }
            } catch (DuplicateException $e) {
                $skipped = true;
                $duplicateRecordIds = $e->getDuplicateRecordIds();
                $duplicatesResult = $this->triggerSyncActionForDuplicate($record, $duplicateRecordIds);

                if ($duplicatesResult) {
                    $updateDuplicateRecords[$index] = $duplicatesResult;
                    $skipped = false;
                }
                if ($skipped) {
                    $recordDetails['skipped'][] = ['record' => $updatedRecords[$index],
                        'messageidentifier' => '',
                        'message' => $e->getMessage()];
                }
                unset($updatedRecords[$index]);

                continue;
            } catch (Exception $e) {
                $recordDetails['skipped'][] = ['record' => $updatedRecords[$index],
                    'messageidentifier' => '',
                    'message' => $e->getMessage()];
                unset($updatedRecords[$index]);

                continue;
            }
        }

        foreach ($updateDuplicateRecords as $index => $record) {
            $updatedRecords[$index] = $record;
        }

        $hasDeleteAccess = null;
        $deletedCrmIds = [];
        foreach ($deletedRecords as $index => $record) {
            $webserviceRecordId = $record;
            $recordIdComp = vtws_getIdComponents($webserviceRecordId);
            $deletedCrmIds[] = $recordIdComp[1];
        }
        $assignedDeletedRecordIds = wsapp_checkIfRecordsAssignToUser($deletedCrmIds, $this->user->id);

        // To get record id's assigned to group of the current user
        if ($this->isClientUserAndGroupSyncType()) {
            foreach ($groupIds as $group) {
                $groupRecordId = wsapp_checkIfRecordsAssignToUser($deletedCrmIds, $group);
                $assignedDeletedRecordIds = array_merge($assignedDeletedRecordIds, $groupRecordId);
            }
        }
        // End

        foreach ($deletedRecords as $index => $record) {
            $idComp = vtws_getIdComponents($record);
            if (empty($hasDeleteAccess)) {
                $handler = vtws_getModuleHandlerFromId($idComp[0], $this->user);
                $meta = $handler->getMeta();
                $hasDeleteAccess = $meta->hasDeleteAccess();
            }
            if ($hasDeleteAccess) {
                if (in_array($idComp[1], $assignedDeletedRecordIds)) {
                    try {
                        vtws_delete($record, $this->user);
                    } catch (Exception $e) {
                        $recordDetails['skipped'][] = ['record' => $deletedRecords[$index],
                            'messageidentifier' => '',
                            'message' => $e->getMessage()];
                        unset($deletedRecords[$index]);

                        continue;
                    }
                }
            }
        }

        $recordDetails['created'] = $createdRecords;
        $recordDetails['updated'] = $updatedRecords;
        $recordDetails['deleted'] = $deletedRecords;

        return $this->nativeToSyncFormat($recordDetails);
    }

    public function nativeToSyncFormat($element)
    {
        return $element;
    }

    public function syncToNativeFormat($element)
    {
        $syncCreatedRecords = $element['created'];
        $nativeCreatedRecords = [];
        foreach ($syncCreatedRecords as $index => $createRecord) {
            if (empty($createRecord['assigned_user_id'])) {
                $createRecord['assigned_user_id'] = vtws_getWebserviceEntityId('Users', $this->user->id);
            }
            $nativeCreatedRecords[$index] = $createRecord;
        }
        $element['created'] = $nativeCreatedRecords;

        return $element;
    }

    public function map($element, $user)
    {
        return $element;
    }

    public function translateReferenceFieldNamesToIds($entityRecords, $user)
    {
        $entityRecordList = [];
        foreach ($entityRecords as $index => $record) {
            $entityRecordList[$record['module']][$index] = $record;
        }
        foreach ($entityRecordList as $module => $records) {
            $handler = vtws_getModuleHandlerFromName($module, $user);
            $meta = $handler->getMeta();
            $referenceFieldDetails = $meta->getReferenceFieldDetails();

            foreach ($referenceFieldDetails as $referenceFieldName => $referenceModuleDetails) {
                $recordReferenceFieldNames = [];
                foreach ($records as $index => $recordDetails) {
                    if (!empty($recordDetails[$referenceFieldName])) {
                        $recordReferenceFieldNames[] = $recordDetails[$referenceFieldName];
                    }
                }
                $entityNameIds = wsapp_getRecordEntityNameIds(array_values($recordReferenceFieldNames), $referenceModuleDetails, $user);
                foreach ($records as $index => $recordInfo) {
                    if (array_key_exists($referenceFieldName, $recordInfo)) {
                        $array = explode('x', $record[$referenceFieldName]);
                        if (is_numeric($array[0]) && is_numeric($array[1])) {
                            $recordInfo[$referenceFieldName] = $recordInfo[$referenceFieldName];
                        } elseif (!empty($entityNameIds[strtolower($recordInfo[$referenceFieldName])])) {
                            $recordInfo[$referenceFieldName] = $entityNameIds[strtolower($recordInfo[$referenceFieldName])];
                        } else {
                            $recordInfo[$referenceFieldName] = '';
                        }
                    }
                    $records[$index] = $recordInfo;
                }
            }
            $entityRecordList[$module] = $records;
        }

        $crmRecords = [];
        foreach ($entityRecordList as $module => $entityRecords) {
            foreach ($entityRecords as $index => $record) {
                $crmRecords[$index] = $record;
            }
        }

        return $crmRecords;
    }

    public function translateTheReferenceFieldIdsToName($records, $module, $user)
    {
        $db = PearDatabase::getInstance();
        global $current_user;
        $current_user = $user;
        $handler = vtws_getModuleHandlerFromName($module, $user);
        $meta = $handler->getMeta();
        $referenceFieldDetails = $meta->getReferenceFieldDetails();
        foreach ($referenceFieldDetails as $referenceFieldName => $referenceModuleDetails) {
            $referenceFieldIds = [];
            $referenceModuleIds = [];
            $referenceIdsName = [];
            foreach ($records as $recordDetails) {
                $referenceWsId = $recordDetails[$referenceFieldName];
                if (!empty($referenceWsId)) {
                    $referenceIdComp = vtws_getIdComponents($referenceWsId);
                    $webserviceObject = VtigerWebserviceObject::fromId($db, $referenceIdComp[0]);
                    if ($webserviceObject->getEntityName() == 'Currency') {
                        continue;
                    }
                    $referenceModuleIds[$webserviceObject->getEntityName()][] = $referenceIdComp[1];
                    $referenceFieldIds[] = $referenceIdComp[1];
                }
            }

            foreach ($referenceModuleIds as $referenceModule => $idLists) {
                $nameList = getEntityName($referenceModule, $idLists);
                foreach ($nameList as $key => $value) {
                    $referenceIdsName[$key] = $value;
                }
            }
            $recordCount = php7_count($records);
            for ($i = 0; $i < $recordCount; ++$i) {
                $record = $records[$i];
                if (!empty($record[$referenceFieldName])) {
                    $wsId = vtws_getIdComponents($record[$referenceFieldName]);
                    $record[$referenceFieldName] = decode_html($referenceIdsName[$wsId[1]]);
                }
                $records[$i] = $record;
            }
        }

        return $records;
    }

    public function getAssignToChangedRecords()
    {
        return $this->assignToChangedRecords;
    }

    public function fillNonExistingMandatoryPicklistValues($recordList)
    {
        // Meta is cached to eliminate overhead of doing the query every time to get the meta details(retrieveMeta)
        $modulesMetaCache = [];
        foreach ($recordList as $index => $recordDetails) {
            if (!array_key_exists($recordDetails['module'], $modulesMetaCache)) {
                $handler = vtws_getModuleHandlerFromName($recordDetails['module'], $this->user);
                $meta = $handler->getMeta();
                $modulesMetaCache[$recordDetails['module']] = $meta;
            }
            $moduleMeta = $modulesMetaCache[$recordDetails['module']];
            $mandatoryFieldsList = $meta->getMandatoryFields();
            $moduleFields = $meta->getModuleFields();
            foreach ($mandatoryFieldsList as $fieldName) {
                $fieldInstance = $moduleFields[$fieldName];
                if (empty($recordDetails[$fieldName])
                        && ($fieldInstance->getFieldDataType() == 'multipicklist' || $fieldInstance->getFieldDataType() == 'picklist')) {
                    if ($fieldInstance->hasDefault() && trim($fieldInstance->getDefault())) {
                        $defaultValue = decode_html($fieldInstance->getDefault());
                    } else {
                        $pickListDetails = $fieldInstance->getPicklistDetails($webserviceField);
                        $defaultValue = $pickListDetails[0]['value'];
                    }
                    $recordDetails[$fieldName] = $defaultValue;
                }
            }
            $recordList[$index] = $recordDetails;
        }

        return $recordList;
    }

    /**
     * Function to fillMandatory fields in vtiger with given values.
     * @param type $recordLists
     * @param type $user
     * @return type
     */
    public function fillMandatoryFields($recordLists, $user)
    {
        $transformedRecords = [];
        foreach ($recordLists as $index => $record) {
            $handler = vtws_getModuleHandlerFromName($record['module'], $user);
            $meta = $handler->getMeta();
            $fields = $meta->getModuleFields();
            $mandatoryFields = $meta->getMandatoryFields();
            $ownerFields = $meta->getOwnerFields();
            foreach ($mandatoryFields as $fieldName) {
                // ignore owner fields
                if (in_array($fieldName, $ownerFields)) {
                    continue;
                }

                $fieldInstance = $fields[$fieldName];
                $currentFieldValue = $record[$fieldName];
                if (!empty($currentFieldValue)) {
                    continue;
                }

                $fieldDataType = $fieldInstance->getFieldDataType();
                $defaultValue = $fieldInstance->getDefault();
                $value = '';
                switch ($fieldDataType) {
                    case 'date':
                        $value = $defaultValue;
                        if (empty($defaultValue)) {
                            $dateObject = new DateTimeImmutable();
                            $value = $dateObject->format('Y-m-d');
                        }
                        break;
                    case 'time':
                        $value = '00:00:00';
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                    case 'text':
                        $value = '?????';
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                    case 'phone':
                        $value = '?????';
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                    case 'boolean':
                        $value = false;
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                    case 'email':
                        $value = '?????';
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                    case 'string':
                        $value = '?????';
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                    case 'url':
                        $value = '?????';
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                    case 'integer':
                        $value = 0;
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                    case 'double':
                        $value = 00.00;
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                    case 'currency':
                        $value = 0.00;
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                    case 'skype':
                        $value = '?????';
                        if (!empty($defaultValue)) {
                            $value = $defaultValue;
                        }
                        break;
                }
                $record[$fieldName] = $value;
            }

            // New field added to show Record Source
            if (!isset($record['source'])) {
                $record['source'] = Vtiger_Cache::get('WSAPP', 'appName');
            }

            $transformedRecords[$index] = $record;
        }

        return $transformedRecords;
    }

    public function setClientSyncType($syncType = 'user')
    {
        $this->clientSyncType = $syncType;

        return $this;
    }

    public function isClientUserSyncType()
    {
        return ($this->clientSyncType == 'user') ? true : false;
    }

    public function isClientUserAndGroupSyncType()
    {
        return ($this->clientSyncType == 'userandgroup') ? true : false;
    }

    public function triggerSyncActionForDuplicate($recordData, $duplicateRecordIds)
    {
        $db = PearDatabase::getInstance();
        $result = [];
        $user = $this->user;
        $moduleName = $recordData['module'];
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);

        if ($moduleModel && $moduleModel->isSyncable) {
            $webSeviceModuleModel = VtigerWebserviceObject::fromName($db, $moduleName);
            $moduleId = $webSeviceModuleModel->getEntityId();

            $recordId = $recordData['id'];
            $recordIdComponents = vtws_getIdComponents($recordId);
            if (php7_count($recordIdComponents) == 2 && in_array($moduleId, $recordIdComponents)) {
                return [];
            }

            $elemId = reset($duplicateRecordIds);
            $recordId = vtws_getId($moduleId, $elemId);

            try {
                $vtigerRecordData = vtws_retrieve($recordId, $user);
            } catch (Exception $e) {
                return $result;
            }
            global $skipDuplicateCheck;
            $skipDuplicateCheck = true;
            switch ($moduleModel->syncActionForDuplicate) {
                case 1:	// Prefer latest record
                    $finalRecordData = $vtigerRecordData;
                    if ($recordData['modifiedtime'] > $vtigerRecordData['modifiedtime']) {
                        $finalRecordData = $recordData;
                        $finalRecordData['id'] = $recordId;
                        $finalRecordData = vtws_revise($finalRecordData, $user);
                    }
                    $result = $finalRecordData;
                    break;
                    //				case 3	:	//Prefer Vtiger Record
                    //							$result = $vtigerRecordData;
                    //							break;
                case 4:	// Prefer external record
                    $recordData['id'] = $recordId;
                    foreach ($recordData as $fieldName => $fieldValue) {
                        if (!$fieldValue) {
                            unset($recordData[$fieldName]);
                        }
                    }
                    $result = vtws_revise($recordData, $user);
                    break;
                case 2:	// Prefer internal record
                default:	$result = [];
                    break;
            }
            $skipDuplicateCheck = false;
        }

        return $result;
    }
}
