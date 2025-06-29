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
include_once 'vtlib/Vtiger/Version.php';

/**
 * Provides API to work with vtiger CRM Custom View (Filter).
 */
class Vtiger_Filter
{
    /** ID of this filter instance */
    public $id;

    public $name;

    public $isdefault;

    public $status    = false; // 5.1.0 onwards

    public $inmetrics = false;

    public $entitytype = false;

    public $module;

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Get unique id for this instance.
     */
    public function __getUniqueId()
    {
        global $adb;

        return $adb->getUniqueID('vtiger_customview');
    }

    /**
     * Initialize this filter instance.
     * @param Vtiger_Module instance of the module to which this filter is associated
     */
    public function initialize($valuemap, $moduleInstance = false)
    {
        $this->id = $valuemap['cvid'];
        $this->name = $valuemap['viewname'];
        $this->module = $moduleInstance ? $moduleInstance : Vtiger_Module::getInstance($valuemap['tabid']);
    }

    /**
     * Create this instance.
     * @param Vtiger_Module Instance of the module to which this filter should be associated with
     */
    public function __create($moduleInstance)
    {
        global $adb;
        $this->module = $moduleInstance;

        $this->id = $this->__getUniqueId();
        $this->isdefault = ($this->isdefault === true || $this->isdefault == 'true') ? 1 : 0;
        $this->inmetrics = ($this->inmetrics === true || $this->inmetrics == 'true') ? 1 : 0;

        $adb->pquery(
            'INSERT INTO vtiger_customview(cvid,viewname,setdefault,setmetrics,entitytype) VALUES(?,?,?,?,?)',
            [$this->id, $this->name, $this->isdefault, $this->inmetrics, $this->module->name],
        );

        self::log("Creating Filter {$this->name} ... DONE");

        // Filters are role based from 5.1.0 onwards
        if (!$this->status) {
            if (strtoupper(trim($this->name)) == 'ALL') {
                $this->status = '0';
            } // Default
            else {
                $this->status = '3';
            } // Public
            $adb->pquery('UPDATE vtiger_customview SET status=? WHERE cvid=?', [$this->status, $this->id]);

            self::log("Setting Filter {$this->name} to status [{$this->status}] ... DONE");
        }
        // END

    }

    /**
     * Update this instance.
     * @internal TODO
     */
    public function __update()
    {
        self::log("Updating Filter {$this->name} ... DONE");
    }

    /**
     * Delete this instance.
     */
    public function __delete()
    {
        global $adb;
        $adb->pquery('DELETE FROM vtiger_cvadvfilter WHERE cvid=?', [$this->id]);
        $adb->pquery('DELETE FROM vtiger_cvcolumnlist WHERE cvid=?', [$this->id]);
        $adb->pquery('DELETE FROM vtiger_customview WHERE cvid=?', [$this->id]);
    }

    /**
     * Save this instance.
     * @param Vtiger_Module Instance of the module to use
     */
    public function save($moduleInstance = false)
    {
        if ($this->id) {
            $this->__update();
        } else {
            $this->__create($moduleInstance);
        }

        return $this->id;
    }

    /**
     * Delete this instance.
     */
    public function delete()
    {
        $this->__delete();
    }

    /**
     * Get the column value to use in custom view tables.
     * @param Vtiger_Field Instance of the field
     */
    public function __getColumnValue($fieldInstance)
    {
        $tod = preg_split('/~/', $fieldInstance->typeofdata);
        $displayinfo = $fieldInstance->getModuleName() . '_' . str_replace(' ', '_', $fieldInstance->label) . ':' . $tod[0];
        $cvcolvalue = "{$fieldInstance->table}:{$fieldInstance->column}:{$fieldInstance->name}:{$displayinfo}";

        return $cvcolvalue;
    }

    /**
     * Add the field to this filer instance.
     * @param Vtiger_Field Instance of the field
     * @param int Index count to use
     */
    public function addField($fieldInstance, $index = 0)
    {
        global $adb;

        $cvcolvalue = $this->__getColumnValue($fieldInstance);

        $adb->pquery(
            'UPDATE vtiger_cvcolumnlist SET columnindex=columnindex+1 WHERE cvid=? AND columnindex>=? ORDER BY columnindex DESC',
            [$this->id, $index],
        );
        $adb->pquery('INSERT INTO vtiger_cvcolumnlist(cvid,columnindex,columnname) VALUES(?,?,?)', [$this->id, $index, $cvcolvalue]);

        $this->log("Adding {$fieldInstance->name} to {$this->name} filter ... DONE");

        return $this;
    }

    /**
     * Add rule to this filter instance.
     * @param Vtiger_Field Instance of the field
     * @param string One of [EQUALS, NOT_EQUALS, STARTS_WITH, ENDS_WITH, CONTAINS, DOES_NOT_CONTAINS, LESS_THAN,
     *                       GREATER_THAN, LESS_OR_EQUAL, GREATER_OR_EQUAL]
     * @param string Value to use for comparision
     * @param int Index count to use
     */
    public function addRule($fieldInstance, $comparator, $comparevalue, $index = 0, $group = 1, $condition = 'and')
    {
        global $adb;

        if (empty($comparator)) {
            return $this;
        }

        $comparator = self::translateComparator($comparator);
        $cvcolvalue = $this->__getColumnValue($fieldInstance);

        $adb->pquery(
            'UPDATE vtiger_cvadvfilter set columnindex=columnindex+1 WHERE cvid=? AND columnindex>=? ORDER BY columnindex DESC',
            [$this->id, $index],
        );
        $adb->pquery(
            'INSERT INTO vtiger_cvadvfilter(cvid, columnindex, columnname, comparator, value, groupid, column_condition) VALUES(?,?,?,?,?,?,?)',
            [$this->id, $index, $cvcolvalue, $comparator, $comparevalue, $group, $condition],
        );

        Vtiger_Utils::Log('Adding Condition ' . self::translateComparator($comparator, true) . " on {$fieldInstance->name} of {$this->name} filter ... DONE");

        return $this;
    }

    /**
     * Translate comparator (condition) to long or short form.
     * @internal Used from Vtiger_PackageExport also
     */
    public static function translateComparator($value, $tolongform = false)
    {
        $comparator = false;
        if ($tolongform) {
            $comparator = strtolower($value);
            if ($comparator == 'e') {
                $comparator = 'EQUALS';
            } elseif ($comparator == 'n') {
                $comparator = 'NOT_EQUALS';
            } elseif ($comparator == 's') {
                $comparator = 'STARTS_WITH';
            } elseif ($comparator == 'ew') {
                $comparator = 'ENDS_WITH';
            } elseif ($comparator == 'c') {
                $comparator = 'CONTAINS';
            } elseif ($comparator == 'k') {
                $comparator = 'DOES_NOT_CONTAINS';
            } elseif ($comparator == 'l') {
                $comparator = 'LESS_THAN';
            } elseif ($comparator == 'g') {
                $comparator = 'GREATER_THAN';
            } elseif ($comparator == 'm') {
                $comparator = 'LESS_OR_EQUAL';
            } elseif ($comparator == 'h') {
                $comparator = 'GREATER_OR_EQUAL';
            }
        } else {
            $comparator = strtoupper($value);
            if ($comparator == 'EQUALS') {
                $comparator = 'e';
            } elseif ($comparator == 'NOT_EQUALS') {
                $comparator = 'n';
            } elseif ($comparator == 'STARTS_WITH') {
                $comparator = 's';
            } elseif ($comparator == 'ENDS_WITH') {
                $comparator = 'ew';
            } elseif ($comparator == 'CONTAINS') {
                $comparator = 'c';
            } elseif ($comparator == 'DOES_NOT_CONTAINS') {
                $comparator = 'k';
            } elseif ($comparator == 'LESS_THAN') {
                $comparator = 'l';
            } elseif ($comparator == 'GREATER_THAN') {
                $comparator = 'g';
            } elseif ($comparator == 'LESS_OR_EQUAL') {
                $comparator = 'm';
            } elseif ($comparator == 'GREATER_OR_EQUAL') {
                $comparator = 'h';
            }
        }

        return $comparator;
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
     * Get instance by filterid or filtername.
     * @param mixed filterid or filtername
     * @param Vtiger_Module Instance of the module to use when filtername is used
     */
    public static function getInstance($value, $moduleInstance = false)
    {
        global $adb;
        $instance = false;

        $query = false;
        $queryParams = false;
        if (Vtiger_Utils::isNumber($value)) {
            $query = 'SELECT * FROM vtiger_customview WHERE cvid=?';
            $queryParams = [$value];
        } else {
            $query = 'SELECT * FROM vtiger_customview WHERE viewname=? AND entitytype=?';
            $queryParams = [$value, $moduleInstance->name];
        }
        $result = $adb->pquery($query, $queryParams);
        if ($adb->num_rows($result)) {
            $instance = new self();
            $instance->initialize($adb->fetch_array($result), $moduleInstance);
        }

        return $instance;
    }

    /**
     * Get all instances of filter for the module.
     * @param Vtiger_Module Instance of module
     */
    public static function getAllForModule($moduleInstance)
    {
        global $adb;
        $instances = false;

        $query = 'SELECT * FROM vtiger_customview WHERE entitytype=?';
        $queryParams = [$moduleInstance->name];

        $result = $adb->pquery($query, $queryParams);
        for ($index = 0; $index < $adb->num_rows($result); ++$index) {
            $instance = new self();
            $instance->initialize($adb->fetch_array($result), $moduleInstance);
            $instances[] = $instance;
        }

        return $instances;
    }

    /**
     * Delete filter associated for module.
     * @param Vtiger_Module Instance of module
     */
    public static function deleteForModule($moduleInstance)
    {
        global $adb;

        $cvidres = $adb->pquery('SELECT cvid FROM vtiger_customview WHERE entitytype=?', [$moduleInstance->name]);
        if ($adb->num_rows($cvidres)) {
            $cvids = [];
            for ($index = 0; $index < $adb->num_rows($cvidres); ++$index) {
                $cvids[] = $adb->query_result($cvidres, $index, 'cvid');
            }
            if (!empty($cvids)) {
                $adb->pquery('DELETE FROM vtiger_cvadvfilter WHERE cvid  IN (' . generateQuestionMarks($cvids) . ')', [$cvids]);
                $adb->pquery('DELETE FROM vtiger_cvcolumnlist WHERE cvid IN (' . generateQuestionMarks($cvids) . ')', [$cvids]);
                $adb->pquery('DELETE FROM vtiger_customview WHERE cvid   IN (' . generateQuestionMarks($cvids) . ')', [$cvids]);
            }
        }
    }
}
