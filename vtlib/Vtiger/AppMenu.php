<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

/**
 * Provides API to work with vtiger CRM App Menu.
 */
class Vtiger_AppMenu
{
    public $appname;

    public $tabid;

    public $sequence;

    public $visibile;

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Initialize this instance.
     * @param array Map
     */
    public function initialize($valuemap)
    {
        $this->appname  = $valuemap['appname'];
        $this->tabid    = $valuemap['tabid'];
        $this->sequence = $valuemap['sequence'];
        $this->visibile = $valuemap['visible'];
    }

    /**
     * Get relation sequence to use.
     */
    public function __getNextSequence()
    {
        global $adb;
        $result = $adb->pquery(
            'SELECT MAX(sequence) AS max_seq FROM vtiger_app2tab WHERE appname=?',
            [$this->appname],
        );
        $maxseq = $adb->query_result($result, 0, 'max_seq');

        return ++$maxseq;
    }

    /**
     * Add module to this menu instance.
     * @param Vtiger_Module Instance of the module
     */
    public function addModule($moduleInstance)
    {
        if ($this->appname) {
            global $adb;
            // check existing
            $checkrs = $adb->pquery('SELECT * FROM vtiger_app2tab WHERE appname = ? and tabid = ? LIMIT 1', [
                $this->appname, $moduleInstance->id]);
            if ($row = $adb->fetch_row($checkrs)) {
                $this->initialize($row);
                self::log("Found appmenu in {$this->appname} ... DONE");
            } else {
                // add new
                $sequence = $this->__getNextSequence();
                $adb->pquery(
                    'INSERT INTO vtiger_app2tab (appname,tabid,sequence,visible) VALUES(?,?,?,?)',
                    [$this->appname, $moduleInstance->id, $sequence, 1],
                );
                self::log("Added to appmenu {$this->appname} ... DONE");
            }
        } else {
            self::log('AppMenu could not be found!');
        }
    }

    /**
     * Detach module from menu.
     * @param Vtiger_Module Instance of the module
     */
    public static function detachModule($moduleInstance)
    {
        global $adb;
        $adb->pquery('DELETE FROM vtiger_app2tab WHERE tabid=?', [$moduleInstance->id]);
        self::log('Detaching from appmenu ... DONE');
    }

    /**
     * Get instance of menu by appname.
     * @param string AppMenu
     */
    public static function getInstance($value)
    {
        global $adb;
        $instance = false;
        $query = 'SELECT appname FROM vtiger_app2tab WHERE appname=? LIMIT 1'; /* existing appname */
        $result = $adb->pquery($query, [$value]);
        if ($adb->num_rows($result)) {
            $instance = new self();
            $instance->initialize($adb->fetch_array($result));
        }

        return $instance;
    }

    /**
     * Helper function to log messages.
     * @param string Message to log
     * @param bool true appends linebreak, false to avoid it
     */
    public static function log($message, $delim = true)
    {
        Vtiger_Utils::Log($message, $delim);
    }
}
