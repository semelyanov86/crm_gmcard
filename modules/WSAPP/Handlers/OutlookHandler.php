<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
require_once 'include/fields/DateTimeField.php';
require_once 'modules/WSAPP/SyncServer.php';
require_once 'modules/WSAPP/Handlers/SyncHandler.php';
require_once 'modules/WSAPP/OutlookSyncServer.php';

class OutlookHandler extends SyncHandler
{
    public function __construct($appkey)
    {
        $this->syncServer = new OutlookSyncServer();
        $this->key = $appkey;
    }

    public function get($module, $token, $user)
    {
        $this->syncModule = $module;
        $this->user = $user;
        $result = $this->syncServer->get($this->key, $module, $token, $user);
        $nativeForamtElementList = $result;
        $nativeForamtElementList['created'] = $this->syncToNativeFormat($result['created']);
        $nativeForamtElementList['updated'] = $this->syncToNativeFormat($result['updated']);

        return $nativeForamtElementList;
    }

    public function put($element, $user)
    {
        $this->user = $user;
        $element = $this->nativeToSyncFormat($element);
        if ($element == 'Events') {
            // To convert minutes to seconds. Since the webservices require the reminder to be in seconds
            $this->convertReminderTimeToSecond($element);
        }

        return $this->syncServer->put($this->key, $element, $user);
    }

    public function map($olMapElement, $user)
    {
        $this->user = $user;
        $element = $this->convertMapRecordsToSyncFormat($olMapElement);

        return $this->syncServer->map($this->key, $element, $user);
    }

    public function nativeToSyncFormat($element)
    {
        $syncFormatElementList = [];
        foreach ($element as $recordDetails) {
            if (!empty($recordDetails['values'])) {
                $recordDetails['values'] = $this->convertRecordToSyncFormat($recordDetails['module'], $recordDetails['values']);
            }
            $syncFormatElementList[] = $recordDetails;
        }

        return $syncFormatElementList;
    }

    public function syncToNativeFormat($recordList)
    {
        $nativeFormatRecordList = [];
        foreach ($recordList as $record) {
            $nativeFormatRecordList[] = $this->convertRecordToNativeFormat($this->syncModule, $record);
        }

        return $nativeFormatRecordList;
    }

    private function convertRecordToSyncFormat($module, $record)
    {
        if ($module == 'Events' || $module == 'Calendar') {
            $startTime = $record['start_time'];
            $endTime = $record['end_time'];
            $dateFormat = 'Y-m-d';
            $timeFormat = 'H:i:s';

            $record['date_start'] = date($dateFormat, strtotime($startTime));
            $record['time_start'] = date($timeFormat, strtotime($startTime));

            $record['due_date'] = date($dateFormat, strtotime($endTime));
            // Because there is no end time for Task module
            if ($module == 'Events') {
                $record['time_end'] = date($timeFormat, strtotime($endTime));
            }

            $record['duration_hours'] = date('H', strtotime($endTime) - strtotime($startTime));
            $record['duration_minutes'] = date('i', strtotime($endTime) - strtotime($startTime));

        }
        $record['modifiedtime'] = $record['utclastmodifiedtime'];

        return $record;
    }

    private function convertRecordToNativeFormat($module, $record)
    {
        if ($module == 'Events') {
            $record['start_time'] = $record['date_start'] . ' ' . $record['time_start'];
            $record['end_time'] = $record['due_date'] . ' ' . $record['time_end'];
        } elseif ($module == 'Calendar') {
            $dformat = 'Y-m-d H:i:s';
            $record['start_time'] = date($dformat, strtotime($record['date_start'] . ' ' . $record['time_start']));
            $record['end_time'] = date($dformat, strtotime($record['due_date']));

            /**
             * convert the start time to user time zone as outlook does not take the datetime in utc
             * Because, in Outlook , there is no time field for Tasks.
             */
            $oldDateFormat = $this->user->date_format;
            $this->user->date_format = 'yyyy-mm-dd';
            $dateTimeField = new DateTimeField($record['start_time']);
            $record['start_time'] = $dateTimeField->getDisplayDateTimeValue($this->user);
        }

        return $record;
    }

    private function convertMapRecordsToSyncFormat($elements)
    {
        $syncMapFormatElements = [];
        $syncMapFormatElements['create'] = [];
        $syncMapFormatElements['delete'] = [];
        $syncMapFormatElements['update'] = [];

        foreach ($elements as $olElement) {
            if ($olElement['mode'] == 'create') {
                $syncMapFormatElements['create'][$olElement['clientid']] = $olElement['values'];
            } elseif ($olElement['mode'] == 'update') {
                $syncMapFormatElements['update'][$olElement['clientid']] = $olElement['values'];
            } elseif ($olElement['mode'] == 'delete') {
                $syncMapFormatElements['delete'][] = $olElement['clientid'];
            }
        }

        return $syncMapFormatElements;
    }

    private function convertReminderTimeToSecond($records)
    {
        foreach ($records as $record) {
            if (!empty($record['values'])) {
                // converting mins to seconds
                $record['reminder_time']  = $record['reminder_time'] * 60;
            }
        }
    }
}
