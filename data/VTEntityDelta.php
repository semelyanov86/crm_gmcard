<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
require_once 'include/events/VTEntityData.inc';

class VTEntityDelta extends VTEventHandler
{
    private static $oldEntity;

    private static $newEntity;

    private static $entityDelta;

    public function __construct() {}

    public function handleEvent($eventName, $entityData)
    {

        $adb = PearDatabase::getInstance();
        $moduleName = $entityData->getModuleName();
        $recordId = $entityData->getId();

        if ($eventName == 'vtiger.entity.beforesave') {
            if (!empty($recordId)) {
                $entityData = VTEntityData::fromEntityId($adb, $recordId, $moduleName);
                if ($moduleName == 'HelpDesk') {
                    $entityData->set('comments', getTicketComments($recordId));
                } elseif ($moduleName == 'Invoice') {
                    $entityData->set('invoicestatus', getInvoiceStatus($recordId));
                }
                self::$oldEntity[$moduleName][$recordId] = $entityData;
            }
        }

        if ($eventName == 'vtiger.entity.aftersave') {
            $this->fetchEntity($moduleName, $recordId);
            $this->computeDelta($moduleName, $recordId);
        }
    }

    public function fetchEntity($moduleName, $recordId)
    {
        $adb = PearDatabase::getInstance();
        $entityData = VTEntityData::fromEntityId($adb, $recordId, $moduleName);
        if ($moduleName == 'HelpDesk') {
            $entityData->set('comments', getTicketComments($recordId));
        } elseif ($moduleName == 'Invoice') {
            $entityData->set('invoicestatus', getInvoiceStatus($recordId));
        }
        self::$newEntity[$moduleName][$recordId] = $entityData;
    }

    public function computeDelta($moduleName, $recordId)
    {

        $delta = [];

        $oldData = [];
        if (!empty(self::$oldEntity[$moduleName][$recordId])) {
            $oldEntity = self::$oldEntity[$moduleName][$recordId];
            $oldData = $oldEntity->getData();
        }
        $newEntity = self::$newEntity[$moduleName][$recordId];
        $newData = $newEntity->getData();
        /** Detect field value changes */
        foreach ($newData as $fieldName => $fieldValue) {
            $isModified = false;
            if (empty($oldData[$fieldName])) {
                if (!empty($newData[$fieldName])) {
                    $isModified = true;
                }
            } elseif ($oldData[$fieldName] != $newData[$fieldName]) {
                $isModified = true;
            }
            if ($isModified) {
                $delta[$fieldName] = ['oldValue' => $oldData[$fieldName] ?? null,
                    'currentValue' => $newData[$fieldName]];
            }
        }
        self::$entityDelta[$moduleName][$recordId] = $delta;
    }

    public function getEntityDelta($moduleName, $recordId, $forceFetch = false)
    {
        if ($forceFetch) {
            $this->fetchEntity($moduleName, $recordId);
            $this->computeDelta($moduleName, $recordId);
        }

        return self::$entityDelta[$moduleName][$recordId];
    }

    public function getOldValue($moduleName, $recordId, $fieldName)
    {
        $entityDelta = self::$entityDelta[$moduleName][$recordId];

        return $entityDelta[$fieldName]['oldValue'] ?? '';
    }

    public function getCurrentValue($moduleName, $recordId, $fieldName)
    {
        $entityDelta = self::$entityDelta[$moduleName][$recordId];

        return $entityDelta[$fieldName]['currentValue'] ?? '';
    }

    public function getOldEntity($moduleName, $recordId)
    {
        return self::$oldEntity[$moduleName][$recordId];
    }

    public function getNewEntity($moduleName, $recordId)
    {
        return self::$newEntity[$moduleName][$recordId];
    }

    public function hasChanged($moduleName, $recordId, $fieldName, $fieldValue = null)
    {
        $result = false;
        if (empty(self::$oldEntity[$moduleName][$recordId])) {
            return false;
        }
        if (!array_key_exists($fieldName, self::$entityDelta[$moduleName][$recordId])) {
            return false;
        }
        $fieldDelta = self::$entityDelta[$moduleName][$recordId][$fieldName];
        if (is_array($fieldDelta)) {
            $fieldDelta = array_map('decode_html', $fieldDelta);
        }
        if (isset($fieldDelta['oldValue'], $fieldDelta['currentValue'])) {
            $result = $fieldDelta['oldValue'] != $fieldDelta['currentValue'];
        }
        if ($fieldValue !== null) {
            $result = $result && ($fieldDelta['currentValue'] === $fieldValue);
        }

        return $result;
    }
}
