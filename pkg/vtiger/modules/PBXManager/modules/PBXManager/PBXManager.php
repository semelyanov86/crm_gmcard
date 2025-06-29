<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

include_once 'data/CRMEntity.php';
require_once 'vtlib/Vtiger/Link.php';
include_once 'vtlib/Vtiger/Module.php';
include_once 'vtlib/Vtiger/Menu.php';
require 'include/events/include.inc';
require_once 'include/utils/utils.php';

class PBXManager extends CRMEntity
{
    protected $incominglinkLabel = 'Incoming Calls';

    protected $tabId = 0;

    protected $headerScriptLinkType = 'HEADERSCRIPT';

    protected $dependentModules = ['Contacts', 'Leads', 'Accounts'];

    public $mandatory_fields;

    public $db;

    public $table_name = 'vtiger_pbxmanager';

    public $table_index = 'pbxmanagerid';

    public $customFieldTable = ['vtiger_pbxmanagercf', 'pbxmanagerid'];

    public $tab_name = ['vtiger_crmentity', 'vtiger_pbxmanager', 'vtiger_pbxmanagercf'];

    public $tab_name_index = [
        'vtiger_crmentity' => 'crmid',
        'vtiger_pbxmanager' => 'pbxmanagerid',
        'vtiger_pbxmanagercf' => 'pbxmanagerid'];

    public $list_fields = [
        /* Format: Field Label => Array(tablename, columnname) */
        // tablename should not have prefix 'vtiger_'
        'Call Status'    => ['vtiger_pbxmanager', 'callstatus'],
        'Customer' => ['vtiger_pbxmanager', 'customer'],
        'User' => ['vtiger_pbxmanager', 'user'],
        'Recording' => ['vtiger_pbxmanager', 'recordingurl'],
        'Start Time' => ['vtiger_pbxmanager', 'starttime'],
    ];

    public $list_fields_name = [
        /* Format: Field Label => fieldname */
        'Call Status' => 'callstatus',
        'Customer' => 'customer',
        'User'     => 'user',
        'Recording' => 'recordingurl',
        'Start Time' => 'starttime',
    ];

    // Make the field link to detail view
    public $list_link_field = 'customernumber';

    // For Popup listview and UI type support
    public $search_fields = [
        /* Format: Field Label => Array(tablename, columnname) */
        // tablename should not have prefix 'vtiger_'
        'Customer' => ['vtiger_pbxmanager', 'customer'],
    ];

    public $search_fields_name = [
        /* Format: Field Label => fieldname */
        'Customer' => 'customer',
    ];

    // For Popup window record selection
    public $popup_fields = ['customernumber'];

    // For Alphabetical search
    public $def_basicsearch_col = 'customer';

    // Column value to use on detail view record text display
    public $def_detailview_recname = 'customernumber';

    // Used when enabling/disabling the mandatory fields for the module.
    // Refers to vtiger_field.fieldname values.
    //    var $mandatory_fields = Array('assigned_user_id');
    public $column_fields = [];

    public $default_order_by = 'customernumber';

    public $default_sort_order = 'ASC';

    public function __construct()
    {
        $this->db = PearDatabase::getInstance();
        $this->column_fields = getColumnFields('PBXManager');
    }

    public function PBXManager()
    {
        self::__construct();
    }

    /**
     * Invoked when special actions are performed on the module.
     * @param string Module name
     * @param string Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    public function vtlib_handler($modulename, $event_type)
    {
        if ($event_type == 'module.postinstall') {
            $this->addLinksForPBXManager();
            $this->registerLookupEvents();
            $this->addSettingsLinks();
            $this->addActionMapping();
            $this->setModuleRelatedDependencies();
            $this->addUserExtensionField();
        } elseif ($event_type == 'module.disabled') {
            $this->removeLinksForPBXManager();
            $this->unregisterLookupEvents();
            $this->removeSettingsLinks();
            $this->removeActionMapping();
            $this->unsetModuleRelatedDependencies();
        } elseif ($event_type == 'module.enabled') {
            $this->addLinksForPBXManager();
            $this->registerLookupEvents();
            $this->addSettingsLinks();
            $this->addActionMapping();
            $this->setModuleRelatedDependencies();
        } elseif ($event_type == 'module.preuninstall') {
            $this->removeLinksForPBXManager();
            $this->unregisterLookupEvents();
            $this->removeSettingsLinks();
            $this->removeActionMapping();
            $this->unsetModuleRelatedDependencies();
        } elseif ($event_type == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } elseif ($event_type == 'module.postupdate') {
            $this->registerLookupEvents();
            // TODO Handle actions before this module is updated.
        }
    }

    /** Function to handle module specific operations when saving a entity.
     */
    public function save_module($module) {}

    /**
     * To add a phone extension field in user preferences page.
     */
    public function addUserExtensionField()
    {
        global $log;
        $module = Vtiger_Module::getInstance('Users');
        if ($module) {
            $blockInstance = Vtiger_Block::getInstance('LBL_MORE_INFORMATION', $module);
            if ($blockInstance) {
                $fieldInstance = new Vtiger_Field();
                $fieldInstance->name = 'phone_crm_extension';
                $fieldInstance->label = 'CRM Phone Extension';
                $fieldInstance->uitype = 11;
                $fieldInstance->typeofdata = 'V~O';
                $blockInstance->addField($fieldInstance);
            }
            $log->info('User Extension Field added');
        }
    }

    /**
     * To register phone lookup events.
     */
    public function registerLookupEvents()
    {
        global $log;
        $adb = PearDatabase::getInstance();
        $EventManager = new VTEventsManager($adb);
        $createEvent = 'vtiger.entity.aftersave';
        $deleteEVent = 'vtiger.entity.afterdelete';
        $restoreEvent = 'vtiger.entity.afterrestore';
        $batchSaveEvent = 'vtiger.batchevent.save';
        $batchDeleteEvent = 'vtiger.batchevent.delete';
        $convertLeadEvent = 'vtiger.lead.convertlead';
        $handler_path = 'modules/PBXManager/PBXManagerHandler.php';
        $className = 'PBXManagerHandler';
        $batchEventClassName = 'PBXManagerBatchHandler';
        $EventManager->registerHandler($createEvent, $handler_path, $className, '', '["VTEntityDelta"]');
        $EventManager->registerHandler($deleteEVent, $handler_path, $className);
        $EventManager->registerHandler($restoreEvent, $handler_path, $className);
        $EventManager->registerHandler($batchSaveEvent, $handler_path, $batchEventClassName);
        $EventManager->registerHandler($batchDeleteEvent, $handler_path, $batchEventClassName);
        $EventManager->registerHandler($convertLeadEvent, $handler_path, $className);
        $log->fatal('Lookup Events Registered');
    }

    /**
     * To add PBXManager module in module($this->dependentModules) related lists.
     */
    public function setModuleRelatedDependencies()
    {
        global $log;
        $pbxmanager = Vtiger_Module::getInstance('PBXManager');
        foreach ($this->dependentModules as $module) {
            $moduleInstance = Vtiger_Module::getInstance($module);
            $moduleInstance->setRelatedList($pbxmanager, 'PBXManager', [], 'get_dependents_list');
        }
        $log->fatal('Successfully added Module Related lists');
    }

    /**
     * To remove PBXManager module from module($this->dependentModules) related lists.
     */
    public function unsetModuleRelatedDependencies()
    {
        global $log;
        $pbxmanager = Vtiger_Module::getInstance('PBXManager');
        foreach ($this->dependentModules as $module) {
            $moduleInstance = Vtiger_Module::getInstance($module);
            $moduleInstance->unsetRelatedList($pbxmanager, 'PBXManager', 'get_dependents_list');
        }
        $log->fatal('Successfully removed Module Related lists');
    }

    /**
     * To unregister phone lookup events.
     */
    public function unregisterLookupEvents()
    {
        global $log;
        $adb = PearDatabase::getInstance();
        $EventManager = new VTEventsManager($adb);
        $className = 'PBXManagerHandler';
        $batchEventClassName = 'PBXManagerBatchHandler';
        $EventManager->unregisterHandler($className);
        $EventManager->unregisterHandler($batchEventClassName);
        $log->fatal('Lookup Events Unregistered');
    }

    /**
     * To add a link in vtiger_links which is to load our PBXManagerJS.js.
     */
    public function addLinksForPBXManager()
    {
        global $log;
        $handlerInfo = ['path' => 'modules/PBXManager/PBXManager.php',
            'class' => 'PBXManager',
            'method' => 'checkLinkPermission'];

        Vtiger_Link::addLink($this->tabId, $this->headerScriptLinkType, $this->incominglinkLabel, 'modules/PBXManager/resources/PBXManagerJS.js', '', '', $handlerInfo);
        $log->fatal('Links added');
    }

    /**
     * To remove link for PBXManagerJS.js from vtiger_links.
     */
    public function removeLinksForPBXManager()
    {
        global $log;
        // Deleting Headerscripts links
        Vtiger_Link::deleteLink($this->tabId, $this->headerScriptLinkType, $this->incominglinkLabel, 'modules/PBXManager/resources/PBXManagerJS.js');
        $log->fatal('Links Removed');
    }

    /**
     * To add Integration->PBXManager block in Settings page.
     */
    public function addSettingsLinks()
    {
        global $log;
        $adb = PearDatabase::getInstance();
        $integrationBlock = $adb->pquery('SELECT * FROM vtiger_settings_blocks WHERE label=?', ['LBL_INTEGRATION']);
        $integrationBlockCount = $adb->num_rows($integrationBlock);

        // To add Block
        if ($integrationBlockCount > 0) {
            $blockid = $adb->query_result($integrationBlock, 0, 'blockid');
        } else {
            $blockid = $adb->getUniqueID('vtiger_settings_blocks');
            $sequenceResult = $adb->pquery('SELECT max(sequence) as sequence FROM vtiger_settings_blocks', []);
            if ($adb->num_rows($sequenceResult)) {
                $sequence = $adb->query_result($sequenceResult, 0, 'sequence');
            }
            $adb->pquery('INSERT INTO vtiger_settings_blocks(blockid, label, sequence) VALUES(?,?,?)', [$blockid, 'LBL_INTEGRATION', ++$sequence]);
        }

        // To add a Field
        $fieldid = $adb->getUniqueID('vtiger_settings_field');
        $adb->pquery('INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence, active)
            VALUES(?,?,?,?,?,?,?,?)', [$fieldid, $blockid, 'LBL_PBXMANAGER', '', 'PBXManager module Configuration', 'index.php?module=PBXManager&parent=Settings&view=Index', 2, 0]);
        $log->fatal('Settings Block and Field added');
    }

    /**
     * To delete Integration->PBXManager block in Settings page.
     */
    public function removeSettingsLinks()
    {
        global $log;
        $adb = PearDatabase::getInstance();
        $adb->pquery('DELETE FROM vtiger_settings_field WHERE name=?', ['LBL_PBXMANAGER']);
        $log->fatal('Settings Field Removed');

    }

    /**
     * To enable(ReceiveIncomingCall & MakeOutgoingCall) tool in profile.
     */
    public function addActionMapping()
    {
        global $log;
        $adb = PearDatabase::getInstance();
        $module = new Vtiger_Module();
        $moduleInstance = $module->getInstance('PBXManager');

        // To add actionname as ReceiveIncomingcalls
        $maxActionIdresult = $adb->pquery('SELECT max(actionid+1) AS actionid FROM vtiger_actionmapping', []);
        if ($adb->num_rows($maxActionIdresult)) {
            $actionId = $adb->query_result($maxActionIdresult, 0, 'actionid');
        }
        $adb->pquery('INSERT INTO vtiger_actionmapping
                     (actionid, actionname, securitycheck) VALUES(?,?,?)', [$actionId, 'ReceiveIncomingCalls', 0]);
        $moduleInstance->enableTools('ReceiveIncomingcalls');
        $log->fatal('ReceiveIncomingcalls ActionName Added');

        // To add actionname as MakeOutgoingCalls
        $maxActionIdresult = $adb->pquery('SELECT max(actionid+1) AS actionid FROM vtiger_actionmapping', []);
        if ($adb->num_rows($maxActionIdresult)) {
            $actionId = $adb->query_result($maxActionIdresult, 0, 'actionid');
        }
        $adb->pquery('INSERT INTO vtiger_actionmapping
                     (actionid, actionname, securitycheck) VALUES(?,?,?)', [$actionId, 'MakeOutgoingCalls', 0]);
        $moduleInstance->enableTools('MakeOutgoingCalls');
        $log->fatal('MakeOutgoingCalls ActionName Added');
    }

    /**
     * To remove(ReceiveIncomingCall & MakeOutgoingCall) tool from profile.
     */
    public function removeActionMapping()
    {
        global $log;
        $adb = PearDatabase::getInstance();
        $module = new Vtiger_Module();
        $moduleInstance = $module->getInstance('PBXManager');

        $moduleInstance->disableTools('ReceiveIncomingcalls');
        $adb->pquery('DELETE FROM vtiger_actionmapping 
                     WHERE actionname=?', ['ReceiveIncomingCalls']);
        $log->fatal('ReceiveIncomingcalls ActionName Removed');

        $moduleInstance->disableTools('MakeOutgoingCalls');
        $adb->pquery('DELETE FROM vtiger_actionmapping 
                      WHERE actionname=?', ['MakeOutgoingCalls']);
        $log->fatal('MakeOutgoingCalls ActionName Removed');
    }

    public static function checkLinkPermission($linkData)
    {
        $module = new Vtiger_Module();
        $moduleInstance = $module->getInstance('PBXManager');

        if ($moduleInstance) {
            return true;
        }

        return false;

    }
}
