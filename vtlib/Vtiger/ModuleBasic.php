<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'vtlib/Vtiger/Access.php';
include_once 'vtlib/Vtiger/Block.php';
include_once 'vtlib/Vtiger/Field.php';
include_once 'vtlib/Vtiger/Filter.php';
include_once 'vtlib/Vtiger/Profile.php';
include_once 'vtlib/Vtiger/Menu.php';
include_once 'vtlib/Vtiger/AppMenu.php';
include_once 'vtlib/Vtiger/Link.php';
include_once 'vtlib/Vtiger/Event.php';
include_once 'vtlib/Vtiger/Webservice.php';
include_once 'vtlib/Vtiger/Version.php';
require_once 'includes/runtime/Cache.php';

/**
 * Provides API to work with vtiger CRM Module.
 */
#[AllowDynamicProperties]
class Vtiger_ModuleBasic
{
    /** ID of this instance */
    public $id = false;

    public $name = false;

    public $label = false;

    public $version = 0;

    public $minversion = false;

    public $maxversion = false;

    public $presence = 0;

    public $ownedby = 0; // 0 - Sharing Access Enabled, 1 - Sharing Access Disabled

    public $tabsequence = false;

    public $parent = false;

    public $customized = 0;

    public $trial = 0;

    public $isentitytype = true; // Real module or an extension?

    public $entityidcolumn = false;

    public $entityidfield = false;

    public $basetable = false;

    public $basetableid = false;

    public $customtable = false;

    public $grouptable = false;

    public $fieldtable = false;

    public $allowDuplicates = false;

    public $isSyncable = 0;

    public $syncActionForDuplicate = 1;
    public const EVENT_MODULE_ENABLED     = 'module.enabled';
    public const EVENT_MODULE_DISABLED    = 'module.disabled';
    public const EVENT_MODULE_POSTINSTALL = 'module.postinstall';
    public const EVENT_MODULE_PREUNINSTALL = 'module.preuninstall';
    public const EVENT_MODULE_PREUPDATE   = 'module.preupdate';
    public const EVENT_MODULE_POSTUPDATE  = 'module.postupdate';

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Initialize this instance.
     */
    public function initialize($valuemap)
    {
        $this->id = $valuemap['tabid'];
        $this->name = $valuemap['name'];
        $this->label = $valuemap['tablabel'];
        $this->version = $valuemap['version'];

        $this->presence = $valuemap['presence'];
        $this->ownedby = $valuemap['ownedby'];
        $this->tabsequence = $valuemap['tabsequence'];
        $this->parent = $valuemap['parent'];
        $this->customized = $valuemap['customized'];
        $this->trial = $valuemap['trial'];

        $this->isentitytype = $valuemap['isentitytype'];
        if ($this->isentitytype || $this->name == 'Users') {
            // Initialize other details too
            $this->initialize2();
        }
        $this->source = $valuemap['source'];

        if (isset($valuemap['issyncable'])) {
            $this->isSyncable = $valuemap['issyncable'];
        }
        if (isset($valuemap['allowduplicates'])) {
            $this->allowDuplicates = $valuemap['allowduplicates'];
        }
        if (isset($valuemap['sync_action_for_duplicates'])) {
            $this->syncActionForDuplicate = $valuemap['sync_action_for_duplicates'];
        }
    }

    /**
     * Initialize more information of this instance.
     */
    public function initialize2()
    {
        $entitydata = Vtiger_Functions::getEntityModuleInfo($this->name);
        if ($entitydata) {
            $this->basetable = $entitydata['tablename'];
            $this->basetableid = $entitydata['entityidfield'];
        }
    }

    /**
     * Get unique id for this instance.
     */
    public function __getUniqueId()
    {
        global $adb;
        $result = $adb->pquery('SELECT MAX(tabid) AS max_seq FROM vtiger_tab', []);
        $maxseq = $adb->query_result($result, 0, 'max_seq');

        return ++$maxseq;
    }

    /**
     * Get next sequence to use for this instance.
     */
    public function __getNextSequence()
    {
        global $adb;
        $result = $adb->pquery('SELECT MAX(tabsequence) AS max_tabseq FROM vtiger_tab', []);
        $maxtabseq = $adb->query_result($result, 0, 'max_tabseq');

        return ++$maxtabseq;
    }

    /**
     * Initialize vtiger schema changes.
     */
    public function __handleVtigerCoreSchemaChanges()
    {
        // Add version column to the table first
        Vtiger_Utils::AddColumn('vtiger_tab', 'version', ' VARCHAR(10)');
        Vtiger_Utils::AddColumn('vtiger_tab', 'parent', ' VARCHAR(30)');
    }

    /**
     * Create this module instance.
     */
    public function __create()
    {
        global $adb;

        self::log("Creating Module {$this->name} ... STARTED");

        $this->id = $this->__getUniqueId();
        if (!$this->tabsequence) {
            $this->tabsequence = $this->__getNextSequence();
        }
        if (!$this->label) {
            $this->label = $this->name;
        }

        $customized = 1; // To indicate this is a Custom Module

        $this->__handleVtigerCoreSchemaChanges();

        $adb->pquery(
            'INSERT INTO vtiger_tab (tabid,name,presence,tabsequence,tablabel,modifiedby,
			modifiedtime,customized,ownedby,version,parent) VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$this->id, $this->name, $this->presence, -1, $this->label, null, null, $customized, $this->ownedby, $this->version, $this->parent],
        );

        $useisentitytype = $this->isentitytype ? 1 : 0;
        $adb->pquery('UPDATE vtiger_tab set isentitytype=? WHERE tabid=?', [$useisentitytype, $this->id]);

        if (!Vtiger_Utils::CheckTable('vtiger_tab_info')) {
            Vtiger_Utils::CreateTable(
                'vtiger_tab_info',
                '(tabid INT, prefname VARCHAR(256), prefvalue VARCHAR(256), FOREIGN KEY fk_1_vtiger_tab_info(tabid) REFERENCES vtiger_tab(tabid) ON DELETE CASCADE ON UPDATE CASCADE)',
                true,
            );
        }
        if ($this->minversion) {
            $tabResult = $adb->pquery("SELECT 1 FROM vtiger_tab_info WHERE tabid=? AND prefname='vtiger_min_version'", [$this->id]);
            if ($adb->num_rows($tabResult) > 0) {
                $adb->pquery("UPDATE vtiger_tab_info SET prefvalue=? WHERE tabid=? AND prefname='vtiger_min_version'", [$this->minversion, $this->id]);
            } else {
                $adb->pquery('INSERT INTO vtiger_tab_info(tabid, prefname, prefvalue) VALUES (?,?,?)', [$this->id, 'vtiger_min_version', $this->minversion]);
            }
        }
        if ($this->maxversion) {
            $tabResult = $adb->pquery("SELECT 1 FROM vtiger_tab_info WHERE tabid=? AND prefname='vtiger_max_version'", [$this->id]);
            if ($adb->num_rows($tabResult) > 0) {
                $adb->pquery("UPDATE vtiger_tab_info SET prefvalue=? WHERE tabid=? AND prefname='vtiger_max_version'", [$this->maxversion, $this->id]);
            } else {
                $adb->pquery('INSERT INTO vtiger_tab_info(tabid, prefname, prefvalue) VALUES (?,?,?)', [$this->id, 'vtiger_max_version', $this->maxversion]);
            }
        }

        Vtiger_Profile::initForModule($this);

        self::syncfile();

        if ($this->isentitytype) {
            Vtiger_Access::initSharing($this);
        }

        $moduleInstance = Vtiger_Module::getInstance($this->name);
        $parentTab = $this->parent;

        if (!empty($parentTab)) {
            $menuInstance = Vtiger_Menu::getInstance($parentTab);
            if ($menuInstance) {
                $menuInstance->addModule($moduleInstance);
            }

            $appmenuInstance = Vtiger_AppMenu::getInstance($parentTab);
            if ($appmenuInstance) {
                $appmenuInstance->addModule($moduleInstance);
            }
        }

        self::log("Creating Module {$this->name} ... DONE");
    }

    /**
     * Update this instance.
     */
    public function __update()
    {
        self::log("Updating Module {$this->name} ... DONE");
    }

    /**
     * Delete this instance.
     */
    public function __delete()
    {
        Vtiger_Module::fireEvent(
            $this->name,
            Vtiger_Module::EVENT_MODULE_PREUNINSTALL,
        );

        global $adb;
        if ($this->isentitytype) {
            $this->unsetEntityIdentifier();
            $this->deleteRelatedLists();
        }

        $adb->pquery('DELETE FROM vtiger_tab WHERE tabid=?', [$this->id]);
        self::log("Deleting Module {$this->name} ... DONE");
    }

    /**
     * Update module version information.
     */
    public function __updateVersion($newversion)
    {
        $this->__handleVtigerCoreSchemaChanges();
        global $adb;
        $adb->pquery('UPDATE vtiger_tab SET version=? WHERE tabid=?', [$newversion, $this->id]);
        $this->version = $newversion;
        self::log("Updating version to {$newversion} ... DONE");
    }

    /**
     * Save this instance.
     */
    public function save()
    {
        if ($this->id) {
            $this->__update();
        } else {
            $this->__create();
        }
        Vtiger_Cache::delete('module', $this->name);

        return $this->id;
    }

    /**
     * Delete this instance.
     */
    public function delete()
    {
        if ($this->isentitytype) {
            Vtiger_Access::deleteSharing($this);
            Vtiger_Access::deleteTools($this);
            Vtiger_Filter::deleteForModule($this);
            Vtiger_Block::deleteForModule($this);
            if (method_exists($this, 'deinitWebservice')) {
                $this->deinitWebservice();
            }
        }
        $this->__delete();
        Vtiger_Profile::deleteForModule($this);
        Vtiger_Link::deleteAll($this->id);
        Vtiger_Menu::detachModule($this);
        Vtiger_AppMenu::detachModule($this);
        self::syncfile();
        Vtiger_Cache::flushModuleCache($this->name);
    }

    /**
     * Initialize table required for the module.
     * @param string Base table name (default modulename in lowercase)
     * @param string Base table column (default modulenameid in lowercase)
     *
     * Creates basetable, customtable, grouptable <br>
     * customtable name is basetable + 'cf'<br>
     * grouptable name is basetable + 'grouprel'<br>
     */
    public function initTables($basetable = false, $basetableid = false)
    {
        $this->basetable = $basetable;
        $this->basetableid = $basetableid;

        // Initialize tablename and index column names
        $lcasemodname = strtolower($this->name);
        if (!$this->basetable) {
            $this->basetable = "vtiger_{$lcasemodname}";
        }
        if (!$this->basetableid) {
            $this->basetableid = $lcasemodname . 'id';
        }

        if (!$this->customtable) {
            $this->customtable = $this->basetable . 'cf';
        }
        if (!$this->grouptable) {
            $this->grouptable = $this->basetable . 'grouprel';
        }

        Vtiger_Utils::CreateTable($this->basetable, "({$this->basetableid} INT(19) PRIMARY KEY)", true);
        Vtiger_Utils::CreateTable($this->customtable, "({$this->basetableid} INT(19) PRIMARY KEY)", true);

        if (Vtiger_Version::check('5.0.4', '<=')) {
            Vtiger_Utils::CreateTable($this->grouptable, "({$this->basetableid} INT PRIMARY KEY, groupname varchar(100))", true);
        }
    }

    /**
     * Set entity identifier field for this module.
     * @param Vtiger_Field Instance of field to use
     */
    public function setEntityIdentifier($fieldInstance)
    {
        global $adb;

        if ($this->basetableid) {
            if (!$this->entityidfield) {
                $this->entityidfield = $this->basetableid;
            }
            if (!$this->entityidcolumn) {
                $this->entityidcolumn = $this->basetableid;
            }
        }
        if ($this->entityidfield && $this->entityidcolumn) {
            $result = $adb->pquery('SELECT tabid FROM vtiger_entityname WHERE tablename=? AND tabid=?', [$fieldInstance->table, $this->id]);
            if ($adb->num_rows($result) == 0) {
                $adb->pquery('INSERT INTO vtiger_entityname(tabid, modulename, tablename, fieldname, entityidfield, entityidcolumn) VALUES(?,?,?,?,?,?)', [$this->id, $this->name, $fieldInstance->table, $fieldInstance->name, $this->entityidfield, $this->entityidcolumn]);
                self::log('Setting entity identifier ... DONE');
            } else {
                $adb->pquery('UPDATE vtiger_entityname SET fieldname=?,entityidfield=?,entityidcolumn=? WHERE tablename=? AND tabid=?', [$fieldInstance->name, $this->entityidfield, $this->entityidcolumn, $fieldInstance->table, $this->id]);
                self::log('Updating entity identifier ... DONE');
            }
        }
    }

    /**
     * Unset entity identifier information.
     */
    public function unsetEntityIdentifier()
    {
        global $adb;
        $adb->pquery('DELETE FROM vtiger_entityname WHERE tabid=?', [$this->id]);
        self::log('Unsetting entity identifier ... DONE');
    }

    /**
     * Delete related lists information.
     */
    public function deleteRelatedLists()
    {
        global $adb;
        $adb->pquery('DELETE FROM vtiger_relatedlists WHERE tabid=?', [$this->id]);
        self::log('Deleting related lists ... DONE');
    }

    /**
     * Delete links information.
     */
    public function deleteLinks()
    {
        global $adb;
        $adb->pquery('DELETE FROM vtiger_links WHERE tabid=?', [$this->id]);
        self::log('Deleting links ... DONE');
    }

    /**
     * Configure default sharing access for the module.
     * @param string Permission text should be one of ['Public_ReadWriteDelete', 'Public_ReadOnly', 'Public_ReadWrite', 'Private']
     */
    public function setDefaultSharing($permission_text = 'Public_ReadWriteDelete')
    {
        Vtiger_Access::setDefaultSharing($this, $permission_text);
    }

    /**
     * Allow module sharing control.
     */
    public function allowSharing()
    {
        Vtiger_Access::allowSharing($this, true);
    }

    /**
     * Disallow module sharing control.
     */
    public function disallowSharing()
    {
        Vtiger_Access::allowSharing($this, false);
    }

    /**
     * Enable tools for this module.
     * @param mixed String or Array with value ['Import', 'Export', 'Merge']
     */
    public function enableTools($tools)
    {
        if (is_string($tools)) {
            $tools = [0 => $tools];
        }

        foreach ($tools as $tool) {
            Vtiger_Access::updateTool($this, $tool, true);
        }
    }

    /**
     * Disable tools for this module.
     * @param mixed String or Array with value ['Import', 'Export', 'Merge']
     */
    public function disableTools($tools)
    {
        if (is_string($tools)) {
            $tools = [0 => $tools];
        }
        foreach ($tools as $tool) {
            Vtiger_Access::updateTool($this, $tool, false);
        }
    }

    /**
     * Add block to this module.
     * @param Vtiger_Block Instance of block to add
     */
    public function addBlock($blockInstance)
    {
        $blockInstance->save($this);

        return $this;
    }

    /**
     * Add filter to this module.
     * @param Vtiger_Filter Instance of filter to add
     */
    public function addFilter($filterInstance)
    {
        $filterInstance->save($this);

        return $this;
    }

    /**
     * Get all the fields of the module or block.
     * @param Vtiger_Block Instance of block to use to get fields, false to get all the block fields
     */
    public function getFields($blockInstance = false)
    {
        $fields = false;
        if ($blockInstance) {
            $fields = Vtiger_Field::getAllForBlock($blockInstance, $this);
        } else {
            $fields = Vtiger_Field::getAllForModule($this);
        }

        return $fields;
    }

    /**
     * Helper function to log messages.
     * @param string Message to log
     * @param bool true appends linebreak, false to avoid it
     */
    public static function log($message, $delimit = true)
    {
        Vtiger_Utils::Log($message, $delimit);
    }

    /**
     * Synchronize the menu information to flat file.
     */
    public static function syncfile()
    {
        self::log('Updating tabdata file ... ', false);
        create_tab_data_file();
        self::log('DONE');
    }
}
