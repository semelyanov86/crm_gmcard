<?php

/*+*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'vtlib/Vtiger/Utils.php';
require_once 'includes/runtime/Cache.php';

/**
 * Provides API to work with vtiger CRM Module Blocks.
 */
class Vtiger_Block
{
    /** ID of this block instance */
    public $id;

    /** Label for this block instance */
    public $label;

    public $sequence;

    public $showtitle = 0;

    public $visible = 0;

    public $increateview = 0;

    public $ineditview = 0;

    public $indetailview = 0;

    public $display_status = 1;

    public $iscustom = 0;

    public $module;

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Get unquie id for this instance.
     */
    public function __getUniqueId()
    {
        global $adb;

        /** Sequence table was added from 5.1.0 */
        $maxblockid = $adb->getUniqueID('vtiger_blocks');

        return $maxblockid;
    }

    /**
     * Get next sequence value to use for this block instance.
     */
    public function __getNextSequence()
    {
        global $adb;
        $result = $adb->pquery('SELECT MAX(sequence) as max_sequence from vtiger_blocks where tabid = ?', [$this->module->id]);
        $maxseq = 0;
        if ($adb->num_rows($result)) {
            $maxseq = $adb->query_result($result, 0, 'max_sequence');
        }

        return ++$maxseq;
    }

    /**
     * Initialize this block instance.
     * @param array Map of column name and value
     * @param Vtiger_Module Instance of module to which this block is associated
     */
    public function initialize($valuemap, $moduleInstance = false)
    {
        $this->id = $valuemap['blockid'] ?? null;
        $this->label = $valuemap['blocklabel'] ?? null;
        $this->display_status = $valuemap['display_status'] ?? null;
        $this->sequence = $valuemap['sequence'] ?? null;
        $this->iscustom = $valuemap['iscustom'] ?? null;
        $tabid = $valuemap['tabid'] ?? null;
        $this->module = $moduleInstance ? $moduleInstance : Vtiger_Module::getInstance($tabid);
    }

    /**
     * Create vtiger CRM block.
     */
    public function __create($moduleInstance)
    {
        global $adb;

        $this->module = $moduleInstance;

        $this->id = $this->__getUniqueId();
        if (!$this->sequence) {
            $this->sequence = $this->__getNextSequence();
        }

        $adb->pquery('INSERT INTO vtiger_blocks(blockid,tabid,blocklabel,sequence,show_title,visible,create_view,edit_view,detail_view,iscustom)
			VALUES(?,?,?,?,?,?,?,?,?,?)', [$this->id, $this->module->id, $this->label, $this->sequence,
            $this->showtitle, $this->visible, $this->increateview, $this->ineditview, $this->indetailview, $this->iscustom]);
        self::log("Creating Block {$this->label} ... DONE");
        self::log("Module language entry for {$this->label} ... CHECK");
    }

    /**
     * Update vtiger CRM block.
     * @internal TODO
     */
    public function __update()
    {
        self::log("Updating Block {$this->label} ... DONE");
    }

    /**
     * Delete this instance.
     */
    public function __delete()
    {
        global $adb;
        self::log("Deleting Block {$this->label} ... ", false);
        $adb->pquery('DELETE FROM vtiger_blocks WHERE blockid=?', [$this->id]);
        self::log('DONE');
    }

    /**
     * Save this block instance.
     * @param Vtiger_Module Instance of the module to which this block is associated
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
     * Delete block instance.
     * @param bool True to delete associated fields, False to avoid it
     */
    public function delete($recursive = true)
    {
        if ($recursive) {
            $fields = Vtiger_Field::getAllForBlock($this);
            foreach ($fields as $fieldInstance) {
                $fieldInstance->delete($recursive);
            }
        }
        $this->__delete();
    }

    /**
     * Add field to this block.
     * @param Vtiger_Field instance of field to add to this block
     * @return Reference to this block instance
     */
    public function addField($fieldInstance)
    {
        $fieldInstance->save($this);

        return $this;
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
     * Get instance of block.
     * @param mixed block id or block label
     * @param Vtiger_Module Instance of the module if block label is passed
     */
    public static function getInstance($value, $moduleInstance = false)
    {
        global $adb;
        $instance = false;

        if (Vtiger_Utils::isNumber($value)) {
            $query = 'SELECT * FROM vtiger_blocks WHERE blockid=?';
            $queryParams = [$value];
        } else {
            $query = 'SELECT * FROM vtiger_blocks WHERE blocklabel=? AND tabid=?';
            $queryParams = [$value, $moduleInstance->id];
        }

        $result = $adb->pquery($query, $queryParams);
        if ($adb->num_rows($result)) {
            $instance = new self();
            $instance->initialize($adb->fetch_array($result), $moduleInstance);
        }

        return $instance;
    }

    /**
     * Get all block instances associated with the module.
     * @param Vtiger_Module Instance of the module
     */
    public static function getAllForModule($moduleInstance)
    {
        global $adb;
        $instances = [];

        $query = 'SELECT * FROM vtiger_blocks WHERE tabid=? ORDER BY sequence';
        $queryParams = [$moduleInstance->id];

        $result = $adb->pquery($query, $queryParams);
        for ($index = 0; $index < $adb->num_rows($result); ++$index) {
            $instance = new self();
            $instance->initialize($adb->fetch_array($result), $moduleInstance);
            $instances[] = $instance;
        }

        return $instances;
    }

    /**
     * Delete all blocks associated with module.
     * @param Vtiger_Module Instnace of module to use
     * @param bool true to delete associated fields, false otherwise
     */
    public static function deleteForModule($moduleInstance, $recursive = true)
    {
        global $adb;
        if ($recursive) {
            Vtiger_Field::deleteForModule($moduleInstance);
        }
        $adb->pquery('DELETE FROM vtiger_blocks WHERE tabid=?', [$moduleInstance->id]);
        self::log('Deleting blocks for module ... DONE');
    }
}
