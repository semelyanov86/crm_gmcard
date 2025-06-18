<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'vtlib/Vtiger/Utils.php';

/**
 * Provides API to work with vtiger CRM Menu.
 */
class Vtiger_Menu
{
    /** ID of this menu instance */
    public $id = false;

    public $label = false;

    public $sequence = false;

    public $visible = 0;

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
        $this->id       = $valuemap['parenttabid'];
        $this->label    = $valuemap['parenttab_label'];
        $this->sequence = $valuemap['sequence'];
        $this->visible  = $valuemap['visible'];
    }

    /**
     * Get relation sequence to use.
     */
    public function __getNextRelSequence()
    {
        global $adb;
        $result = $adb->pquery(
            'SELECT MAX(sequence) AS max_seq FROM vtiger_parenttabrel WHERE parenttabid=?',
            [$this->id],
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
        if ($this->id) {
            global $adb;
            $relsequence = $this->__getNextRelSequence();
            $adb->pquery(
                'INSERT INTO vtiger_parenttabrel (parenttabid,tabid,sequence) VALUES(?,?,?)',
                [$this->id, $moduleInstance->id, $relsequence],
            );
            self::log("Added to menu {$this->label} ... DONE");
        } else {
            self::log('Menu could not be found!');
        }
        self::syncfile();
    }
    // No requirement of removeModule api()
    // Confirmed by (http://trac.vtiger.com/cgi-bin/trac.cgi/ticket/7477)

    /**
     * Detach module from menu.
     * @param Vtiger_Module Instance of the module
     */
    public static function detachModule($moduleInstance)
    {
        global $adb;
        $adb->pquery('DELETE FROM vtiger_parenttabrel WHERE tabid=?', [$moduleInstance->id]);
        self::log('Detaching from menu ... DONE');
        self::syncfile();
    }

    /**
     * Get instance of menu by label.
     * @param string Menu label
     */
    public static function getInstance($value)
    {
        global $adb;
        $query = false;
        $instance = false;
        if (Vtiger_Utils::isNumber($value)) {
            $query = 'SELECT * FROM vtiger_parenttab WHERE parenttabid=?';
        } else {
            $query = 'SELECT * FROM vtiger_parenttab WHERE parenttab_label=?';
        }
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

    /**
     * Synchronize the menu information to flat file.
     */
    public static function syncfile()
    {
        self::log('Updating parent_tabdata file ... STARTED');
        create_parenttab_data_file();
        self::log('Updating parent_tabdata file ... DONE');
    }
}
