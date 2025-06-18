<?php

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

/**
 * Provides basic API to work with vtiger CRM Fields.
 */
#[AllowDynamicProperties]
class Vtiger_FieldBasic
{
    /** ID of this field instance */
    public $id;

    public $name;

    public $label = false;

    public $table = false;

    public $column = false;

    public $columntype = false;

    public $helpinfo = '';

    public $summaryfield = 0;

    public $masseditable = 1; // Default: Enable massedit for field

    public $uitype = 1;

    public $typeofdata = 'V~O';

    public $displaytype   = 1;

    public $generatedtype = 1;

    public $readonly      = 1;

    public $presence      = 2;

    public $defaultvalue  = '';

    public $maximumlength = 100;

    public $sequence      = false;

    public $quickcreate   = 1;

    public $quicksequence = false;

    public $info_type     = 'BAS';

    public $isunique = false;

    public $block;

    public $headerfield = 0;

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Initialize this instance.
     * @param array
     * @param Vtiger_Module Instance of module to which this field belongs
     * @param Vtiger_Block Instance of block to which this field belongs
     */
    public function initialize($valuemap, $moduleInstance = false, $blockInstance = false)
    {
        $valuemap = vtlib_array($valuemap);

        $this->id = $valuemap['fieldid'];
        $this->name = $valuemap['fieldname'];
        $this->label = $valuemap['fieldlabel'];
        $this->column = $valuemap['columnname'];
        $this->table  = $valuemap['tablename'];
        $this->uitype = $valuemap['uitype'];
        $this->typeofdata = $valuemap['typeofdata'];
        $this->helpinfo = $valuemap['helpinfo'];
        $this->masseditable = $valuemap['masseditable'];
        $this->displaytype   = $valuemap['displaytype'];
        $this->generatedtype = $valuemap['generatedtype'];
        $this->readonly      = $valuemap['readonly'];
        $this->presence      = $valuemap['presence'];
        $this->defaultvalue  = $valuemap['defaultvalue'];
        $this->quickcreate = $valuemap['quickcreate'];
        $this->sequence = $valuemap['sequence'];
        $this->summaryfield = $valuemap['summaryfield'];
        $this->isunique = $valuemap['isunique'];
        $this->block = $blockInstance ? $blockInstance : Vtiger_Block::getInstance($valuemap['block'], $moduleInstance);
        $this->headerfield = $valuemap['headerfield'];
    }

    /** Cache (Record) the schema changes to improve performance */
    public static $__cacheSchemaChanges = [];

    /**
     * Initialize vtiger schema changes.
     */
    public function __handleVtigerCoreSchemaChanges()
    {
        // Add helpinfo column to the vtiger_field table
        if (empty(self::$__cacheSchemaChanges['vtiger_field.helpinfo'])) {
            Vtiger_Utils::AddColumn('vtiger_field', 'helpinfo', ' TEXT');
            self::$__cacheSchemaChanges['vtiger_field.helpinfo'] = true;
        }
        if (empty(self::$__cacheSchemaChanges['vtiger_field.summaryfield'])) {
            Vtiger_Utils::AddColumn('vtiger_field', 'summaryfield', ' INT(10) NOT NULL DEFAULT 0');
            self::$__cacheSchemaChanges['vtiger_field.summaryfield'] = 0;
        }
        if (empty(self::$__cacheSchemaChanges['vtiger_field.headerfield'])) {
            Vtiger_Utils::AddColumn('vtiger_field', 'headerfield', ' INT(1) DEFAULT 0');
            self::$__cacheSchemaChanges['vtiger_field.headerfield'] = 0;
        }
    }

    /**
     * Get unique id for this instance.
     */
    public function __getUniqueId()
    {
        global $adb;

        return $adb->getUniqueID('vtiger_field');
    }

    /**
     * Get next sequence id to use within a block for this instance.
     */
    public function __getNextSequence()
    {
        global $adb;
        $result = $adb->pquery('SELECT MAX(sequence) AS max_seq FROM vtiger_field WHERE tabid=? AND block=?', [$this->getModuleId(), $this->getBlockId()]);
        $maxseq = 0;
        if ($result && $adb->num_rows($result)) {
            $maxseq = $adb->query_result($result, 0, 'max_seq');
            ++$maxseq;
        }

        return $maxseq;
    }

    /**
     * Get next quick create sequence id for this instance.
     */
    public function __getNextQuickCreateSequence()
    {
        global $adb;
        $result = $adb->pquery('SELECT MAX(quickcreatesequence) AS max_quickcreateseq FROM vtiger_field WHERE tabid=?', [$this->getModuleId()]);
        $max_quickcreateseq = 0;
        if ($result && $adb->num_rows($result)) {
            $max_quickcreateseq = $adb->query_result($result, 0, 'max_quickcreateseq');
            ++$max_quickcreateseq;
        }

        return $max_quickcreateseq;
    }

    /**
     * Create this field instance.
     * @param Vtiger_Block Instance of the block to use
     */
    public function __create($blockInstance)
    {
        $this->__handleVtigerCoreSchemaChanges();

        global $adb;

        $this->block = $blockInstance;

        $moduleInstance = $this->getModuleInstance();

        $this->id = $this->__getUniqueId();

        if (!$this->sequence) {
            $this->sequence = $this->__getNextSequence();
        }

        if ($this->quickcreate != 1) { // If enabled for display
            if (!$this->quicksequence) {
                $this->quicksequence = $this->__getNextQuickCreateSequence();
            }
        } else {
            $this->quicksequence = null;
        }

        // Initialize other variables which are not done
        if (!$this->table) {
            $this->table = $moduleInstance->basetable;
        }
        if (!$this->column) {
            $this->column = strtolower($this->name);
        }
        if (!$this->columntype) {
            $this->columntype = 'VARCHAR(100)';
        }

        if (!$this->label) {
            $this->label = $this->name;
        }

        if (!empty($this->columntype)) {
            Vtiger_Utils::AddColumn($this->table, $this->column, $this->columntype);
        }

        if (!$this->label) {
            $this->label = $this->name;
        }

        $adb->pquery('INSERT INTO vtiger_field (tabid, fieldid, columnname, tablename, generatedtype,
			uitype, fieldname, fieldlabel, readonly, presence, defaultvalue, maximumlength, sequence,
			block, displaytype, typeofdata, quickcreate, quickcreatesequence, info_type, helpinfo,summaryfield,headerfield)
			VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [$this->getModuleId(), $this->id, $this->column, $this->table, intval($this->generatedtype),
            $this->uitype, $this->name, $this->label, $this->readonly, $this->presence, $this->defaultvalue,
            $this->maximumlength, $this->sequence, $this->getBlockId(), $this->displaytype, $this->typeofdata,
            intval($this->quickcreate), intval($this->quicksequence), $this->info_type, $this->helpinfo, intval($this->summaryfield), $this->headerfield]);

        // Set the field status for mass-edit (if set)
        $adb->pquery('UPDATE vtiger_field SET masseditable=? WHERE fieldid=?', [$this->masseditable, $this->id]);

        Vtiger_Profile::initForField($this);

        self::log("Creating Field {$this->name} ... DONE");
        self::log("Module language mapping for {$this->label} ... CHECK");
    }

    /**
     * Update this field instance.
     * @internal TODO
     */
    public function __update()
    {
        self::log('Make use of Vtiger_Field_Model => __update() api.');
    }

    /**
     * Delete this field instance.
     */
    public function __delete()
    {
        global $adb;

        Vtiger_Profile::deleteForField($this);

        // TODO : should we check if the field is realtion field or not
        $this->getModuleInstance()->unsetRelatedListForField($this->id);

        $adb->pquery('DELETE FROM vtiger_field WHERE fieldid=?', [$this->id]);

        $em = new VTEventsManager($adb);
        $em->triggerEvent('vtiger.field.afterdelete', $this);

        self::log("Deleteing Field {$this->name} ... DONE");
    }

    /**
     * Get block id to which this field instance is associated.
     */
    public function getBlockId()
    {
        return $this->block->id;
    }

    /**
     * Get module id to which this field instance is associated.
     */
    public function getModuleId()
    {
        return $this->block && $this->block->module ? $this->block->module->id : '';
    }

    /**
     * Get module name to which this field instance is associated.
     */
    public function getModuleName()
    {
        return $this->block && $this->block->module ? $this->block->module->name : '';
    }

    /**
     * Get module instance to which this field instance is associated.
     */
    public function getModuleInstance()
    {
        return $this->block->module;
    }

    /**
     * Save this field instance.
     * @param Vtiger_Block instance of block to which this field should be added
     */
    public function save($blockInstance = false)
    {
        if ($this->id) {
            $this->__update();
        } else {
            $this->__create($blockInstance);
        }
        // Clearing cache
        Vtiger_Cache::flushModuleandBlockFieldsCache($this->getModuleInstance(), $this->getBlockId());

        return $this->id;
    }

    /**
     * Delete this field instance.
     */
    public function delete()
    {
        $this->__delete();

        // Clearing cache
        Vtiger_Cache::flushModuleandBlockFieldsCache($this->getModuleInstance(), $this->getBlockId());
    }

    /**
     * Set Help Information for this instance.
     * @param string Help text (content)
     */
    public function setHelpInfo($helptext)
    {
        // Make sure to initialize the core tables first
        $this->__handleVtigerCoreSchemaChanges();

        global $adb;
        $adb->pquery('UPDATE vtiger_field SET helpinfo=? WHERE fieldid=?', [$helptext, $this->id]);
        self::log("Updated help information of {$this->name} ... DONE");
    }

    /**
     * Set Masseditable information for this instance.
     * @param int Masseditable value
     */
    public function setMassEditable($value)
    {
        global $adb;
        $adb->pquery('UPDATE vtiger_field SET masseditable=? WHERE fieldid=?', [$value, $this->id]);
        self::log("Updated masseditable information of {$this->name} ... DONE");
    }

    /**
     * Set Summaryfield information for this instance.
     * @param int Summaryfield value
     */
    public function setSummaryField($value)
    {
        global $adb;
        $adb->pquery('UPDATE vtiger_field SET summaryfield=? WHERE fieldid=?', [$value, $this->id]);
        self::log("Updated summaryfield information of {$this->name} ... DONE");
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
