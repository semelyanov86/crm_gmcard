<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
abstract class Mobile_WS_AlertModel
{
    public $alertid;    // Unique id refering the instance

    public $name;       // Name of the alert - should be unique to make it easy on client side

    public $moduleName; // If alert is targeting module record count, this should be set along with $recordsLinked

    public $refreshRate; // Recommended lookup rate in SECONDS

    public $description; // Describe the purpose of alert to client

    public $recordsLinked; // TRUE if message is based on records of module, FALSE otherwise

    protected $user;

    public function __construct()
    {
        $this->recordsLinked = true;
    }

    public function setUser($userInstance)
    {
        $this->user = $userInstance;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function serializeToSend()
    {
        $category = $this->moduleName;
        if (empty($category)) {
            $category = 'General';
        }

        return [
            'alertid' => (string) $this->alertid,
            'name' => $this->name,
            'category' => $category,
            'refreshRate' => $this->refreshRate,
            'description' => $this->description,
            'recordsLinked' => $this->recordsLinked,
        ];
    }

    abstract public function query();

    abstract public function queryParameters();

    public function message()
    {
        return (string) $this->executeCount();
    }

    /*function execute() {
        global $adb;
        $result = $adb->pquery($this->query(), $this->queryParameters());
        return $result;
    }*/

    public function executeCount()
    {
        global $adb;
        $result = $adb->pquery($this->countQuery(), $this->queryParameters());

        return $adb->query_result($result, 0, 'count');
    }

    // Function provided to enable sub-classes to over-ride in case required
    protected function countQuery()
    {
        return Vtiger_Functions::mkCountQuery($this->query());
    }

    public static function models()
    {
        global $adb;

        $models = [];
        $handlerResult = $adb->pquery('SELECT * FROM vtiger_mobile_alerts WHERE deleted = 0', []);
        if ($adb->num_rows($handlerResult)) {
            while ($handlerRow = $adb->fetch_array($handlerResult)) {
                $handlerPath = $handlerRow['handler_path'];
                if (file_exists($handlerPath)) {
                    checkFileAccessForInclusion($handlerPath);
                    include_once $handlerPath;
                    $alertModel = new $handlerRow['handler_class']();
                    $alertModel->alertid = $handlerRow['id'];
                    $models[] = $alertModel;
                }
            }
        }

        return $models;
    }

    public static function modelWithId($alertid)
    {
        $models = self::models();
        foreach ($models as $model) {
            if ($model->alertid == $alertid) {
                return $model;
            }
        }

        return false;
    }
}
