<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

include_once dirname(__FILE__) . '/Mobile.Config.php';

#[AllowDynamicProperties]
class Mobile
{
    /**
     * Detect if request is from IPhone.
     */
    public static function isSafari()
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $ua = $_SERVER['HTTP_USER_AGENT'];
            if (preg_match('/safari/i', $ua)) {
                return true;
            }
        }

        return false;
    }

    public static function config($key, $defvalue = false)
    {
        // Defined in the configuration file
        global $Module_Mobile_Configuration;
        if (isset($Module_Mobile_Configuration, $Module_Mobile_Configuration[$key])) {
            return $Module_Mobile_Configuration[$key];
        }

        return $defvalue;
    }

    /**
     * Alert management.
     */
    public static function alert_lookup($handlerPath, $handlerClass)
    {
        global $adb;
        $check = $adb->pquery('SELECT id FROM vtiger_mobile_alerts WHERE handler_path=? and handler_class=?', [$handlerPath, $handlerClass]);
        if ($adb->num_rows($check)) {
            return $adb->query_result($check, 0, 'id');
        }

        return false;
    }

    public static function alert_register($handlerPath, $handlerClass)
    {
        global $adb;
        if (self::alert_lookup($handlerPath, $handlerClass) === false) {
            Vtiger_Utils::Log("Registered alert {$handlerClass} [{$handlerPath}]");
            $adb->pquery('INSERT INTO vtiger_mobile_alerts (handler_path, handler_class, deleted) VALUES(?,?,?)', [$handlerPath, $handlerClass, 0]);
        }
    }

    public static function alert_deregister($handlerPath, $handlerClass)
    {
        global $adb;
        Vtiger_Utils::Log("De-registered alert {$handlerClass} [{$handlerPath}]");
        $adb->pquery('DELETE FROM vtiger_mobile_alerts WHERE handler_path=? AND handler_class=?', [$handlerPath, $handlerClass]);
    }

    public static function alert_markdeleted($handlerPath, $handlerClass, $flag)
    {
        global $adb;
        $adb->pquery('UPDATE vtiger_mobile_alerts SET deleted=? WHERE handler_path=? AND handler_class=?', [$flag, $handlerPath, $handlerClass]);
    }

    /**
     * Invoked when special actions are performed on the module.
     * @param string Module name
     * @param string Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    public function vtlib_handler($modulename, $event_type)
    {

        $registerWSAPI = false;
        $registerAlerts = false;

        if ($event_type == 'module.postinstall') {
            $registerWSAPI = true;
            $registerAlerts = true;
        } elseif ($event_type == 'module.disabled') {
            // TODO Handle actions when this module is disabled.
        } elseif ($event_type == 'module.enabled') {
            // TODO Handle actions when this module is enabled.
        } elseif ($event_type == 'module.preuninstall') {
            // TODO Handle actions when this module is about to be deleted.
        } elseif ($event_type == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } elseif ($event_type == 'module.postupdate') {
            $registerWSAPI = true;
            $registerAlerts = true;
        }

        // Register alerts
        if ($registerAlerts) {
            self::alert_register('modules/Mobile/api/ws/models/alerts/IdleTicketsOfMine.php', 'Mobile_WS_AlertModel_IdleTicketsOfMine');
            self::alert_register('modules/Mobile/api/ws/models/alerts/NewTicketOfMine.php', 'Mobile_WS_AlertModel_NewTicketOfMine');
            self::alert_register('modules/Mobile/api/ws/models/alerts/PendingTicketsOfMine.php', 'Mobile_WS_AlertModel_PendingTicketsOfMine');
            self::alert_register('modules/Mobile/api/ws/models/alerts/PotentialsDueIn5Days.php', 'Mobile_WS_AlertModel_PotentialsDueIn5Days');
            self::alert_register('modules/Mobile/api/ws/models/alerts/EventsOfMineToday.php', 'Mobile_WS_AlertModel_EventsOfMineToday');
            self::alert_register('modules/Mobile/api/ws/models/alerts/ProjectTasksOfMine.php', 'Mobile_WS_AlertModel_ProjectTasksOfMine');
            self::alert_register('modules/Mobile/api/ws/models/alerts/Projects.php', 'Mobile_WS_AlertModel_Projects');
        }

        // Register webservice API
        if ($registerWSAPI) {
            $operations = [];

            $operations[] =  [
                'name'       => 'mobile.fetchallalerts',
                'handler'    => 'mobile_ws_fetchAllAlerts',
            ];

            $operations[] =  [
                'name'       => 'mobile.alertdetailswithmessage',
                'handler'    => 'mobile_ws_alertDetailsWithMessage',
                'parameters' => [['name' => 'alertid', 'type' => 'string']],
            ];

            $operations[] =  [
                'name'       => 'mobile.fetchmodulefilters',
                'handler'    => 'mobile_ws_fetchModuleFilters',
                'parameters' => [['name' => 'module', 'type' => 'string']],
            ];

            $operations[] =  [
                'name'       => 'mobile.fetchrecord',
                'handler'    => 'mobile_ws_fetchRecord',
                'parameters' => [['name' => 'record', 'type' => 'string']],
            ];

            $operations[] =  [
                'name'       => 'mobile.fetchrecordwithgrouping',
                'handler'    => 'mobile_ws_fetchRecordWithGrouping',
                'parameters' => [['name' => 'record', 'type' => 'string']],
            ];

            $operations[] =  [
                'name'       => 'mobile.filterdetailswithcount',
                'handler'    => 'mobile_ws_filterDetailsWithCount',
                'parameters' => [['name' => 'filterid', 'type' => 'string']],
            ];

            $operations[] =  [
                'name'       => 'mobile.listmodulerecords',
                'handler'    => 'mobile_ws_listModuleRecords',
                'parameters' => [['name' => 'elements', 'type' => 'encoded']],
            ];

            $operations[] =  [
                'name'       => 'mobile.saverecord',
                'handler'    => 'mobile_ws_saveRecord',
                'parameters' => [['name' => 'module', 'type' => 'string'],
                    ['name' => 'record', 'type' => 'string'],
                    ['name' => 'values', 'type' => 'encoded'],
                ],
            ];

            $operations[] =  [
                'name'       => 'mobile.syncModuleRecords',
                'handler'    => 'mobile_ws_syncModuleRecords',
                'parameters' => [['name' => 'module', 'type' => 'string'],
                    ['name' => 'syncToken', 'type' => 'string'],
                    ['name' => 'page', 'type' => 'string'],
                ],
            ];

            $operations[] =  [
                'name'       => 'mobile.query',
                'handler'    => 'mobile_ws_query',
                'parameters' => [['name' => 'module', 'type' => 'string'],
                    ['name' => 'query', 'type' => 'string'],
                    ['name' => 'page', 'type' => 'string'],
                ],
            ];

            $operations[] =  [
                'name'       => 'mobile.querywithgrouping',
                'handler'    => 'mobile_ws_queryWithGrouping',
                'parameters' => [['name' => 'module', 'type' => 'string'],
                    ['name' => 'query', 'type' => 'string'],
                    ['name' => 'page', 'type' => 'string'],
                ],
            ];

            foreach ($operations as $o) {
                $operation = new Mobile_WS_Operation($o['name'], $o['handler'], 'modules/Mobile/api/wsapi.php', 'POST');
                if (!empty($o['parameters'])) {
                    foreach ($o['parameters'] as $p) {
                        $operation->addParameter($p['name'], $p['type']);
                    }
                }
                $operation->register();
            }
        }
    }
}

/* Helper functions */
class Mobile_WS_Operation
{
    public $opName;

    public $opClass;

    public $opFile;

    public $opType;

    public $parameters = [];

    public function __construct($apiName, $className, $handlerFile, $reqType)
    {
        $this->opName = $apiName;
        $this->opClass = $className;
        $this->opFile = $handlerFile;
        $this->opType = $reqType;
    }

    public function addParameter($name, $type)
    {
        $this->parameters[] = ['name' => $name, 'type' => $type];

        return $this;
    }

    public function register()
    {
        global $adb;
        $checkresult = $adb->pquery('SELECT 1 FROM vtiger_ws_operation WHERE name = ?', [$this->opName]);
        if ($adb->num_rows($checkresult)) {
            return;
        }

        Vtiger_Utils::Log("Enabling webservice operation {$this->opName}", true);

        $operationid = vtws_addWebserviceOperation($this->opName, $this->opFile, $this->opClass, $this->opType);
        for ($index = 0; $index < php7_count($this->parameters); ++$index) {
            vtws_addWebserviceOperationParam($operationid, $this->parameters[$index]['name'], $this->parameters[$index]['type'], $index + 1);
        }
    }
}
