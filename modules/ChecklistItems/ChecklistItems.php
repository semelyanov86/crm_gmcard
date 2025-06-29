<?php

class ChecklistItems extends CRMEntity
{
    public $db;

    public $log;

    public $table_name = 'vtiger_checklistitems';

    public $table_index = 'checklistitemsid';

    public $column_fields = [];

    /** Indicator if this is a custom module or standard module */
    public $IsCustomModule = true;

    /**
     * Mandatory table for supporting custom fields.
     */
    public $customFieldTable = ['vtiger_checklistitemscf', 'checklistitemsid'];

    /**
     * Mandatory for Saving, Include tables related to this module.
     */
    public $tab_name = ['vtiger_crmentity', 'vtiger_checklistitems', 'vtiger_checklistitemscf'];

    /**
     * Mandatory for Saving, Include tablename and tablekey columnname here.
     */
    public $tab_name_index = ['vtiger_crmentity' => 'crmid', 'vtiger_checklistitems' => 'checklistitemsid', 'vtiger_checklistitemscf' => 'checklistitemsid'];

    /**
     * Mandatory for Listing (Related listview).
     */
    public $list_fields = ['Title' => ['checklistitems' => 'title'], 'Checklist Name' => ['checklistitems' => 'checklistname']];

    public $list_fields_name = ['Title' => 'title', 'Checklist Name' => 'checklistname'];

    public $list_link_field = 'title';

    public $search_fields = ['Title' => ['checklistitems' => 'title'], 'Checklist Name' => ['checklistitems' => 'checklistname']];

    public $search_fields_name = ['Title' => 'title', 'Checklist Name' => 'checklistname'];

    public $popup_fields = ['title', 'checklistname'];

    public $sortby_fields = [];

    public $def_basicsearch_col = 'title';

    public $required_fields = ['title' => 1];

    public $mandatory_fields = ['title', 'assigned_user_id'];

    public $special_functions = ['set_import_assigned_user'];

    public $default_order_by = 'createdtime';

    public $default_sort_order = 'DESC';

    public $unit_price;

    /**	Constructor which will set the column_fields in this object.
     */
    public function __construct()
    {
        global $log;
        $this->column_fields = getColumnFields(get_class($this));
        $this->db = PearDatabase::getInstance();
        $this->log = $log;
    }

    public function save_module($module) {}

    /**
     * Invoked when special actions are performed on the module.
     * @param string Module name
     * @param string Event Type
     */
    public function vtlib_handler($moduleName, $eventType)
    {
        require_once 'include/utils/utils.php';
        if ($eventType == 'module.postinstall') {
            self::enableModTracker($moduleName);
            self::addComment($moduleName);
            self::_addLink();
            self::checkWebServiceEntry();
            self::resetValid();
        } else {
            if ($eventType == 'module.disabled') {
                self::_removeLink();
            } else {
                if ($eventType == 'module.enabled') {
                    self::_addLink();
                    self::checkWebServiceEntry();
                    self::updateFeature();
                } else {
                    if ($eventType == 'module.preuninstall') {
                        self::_removeLink();
                        self::removeValid();
                        self::removeData($moduleName);
                    } else {
                        if ($eventType != 'module.preupdate') {
                            if ($eventType == 'module.postupdate') {
                                self::_removeLink();
                                self::_addLink();
                                self::updateFeature();
                                self::checkWebServiceEntry();
                                self::resetValid();
                            }
                        }
                    }
                }
            }
        }
    }

    public static function _addLink()
    {
        global $adb;
        $max_id = $adb->getUniqueID('vtiger_settings_field');
        $blockid = 4;
        $res = $adb->pquery("SELECT blockid FROM `vtiger_settings_blocks` WHERE label='LBL_OTHER_SETTINGS'", []);
        if ($adb->num_rows($res) > 0) {
            while ($row = $adb->fetch_row($res)) {
                $blockid = $row['blockid'];
            }
        }
        $adb->pquery('INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES(?, ?, ?, ?, ?, ?)', [$max_id, $blockid, 'Checklists', 'Settings area for VTE Check List Settings', 'index.php?module=ChecklistItems&view=Settings&parent=Settings', '999']);
        $rs = $adb->pquery('SELECT * FROM `vtiger_checklistitems_settings` GROUP BY `modulename`', []);
        if ($adb->num_rows($rs) > 0) {
            while ($row = $adb->fetchByAssoc($rs)) {
                $moduleName = $row['modulename'];
                $module = Vtiger_Module::getInstance($moduleName);
                if ($module) {
                    $module->addLink('DETAILVIEWSIDEBARWIDGET', 'Checklists', 'module=ChecklistItems&view=Widget');
                }
            }
        }
    }

    public static function resetValid()
    {
        global $adb;
        $adb->pquery("CREATE TABLE IF NOT EXISTS `vte_modules` (\n                `module`  varchar(50) NOT NULL ,\n                `valid`  int(1) NULL ,\n                PRIMARY KEY (`module`));", []);
        $adb->pquery('DELETE FROM `vte_modules` WHERE module=?;', ['ChecklistItems']);
        $adb->pquery('INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);', ['ChecklistItems', '0']);
    }

    public static function removeValid()
    {
        global $adb;
        $adb->pquery('DELETE FROM `vte_modules` WHERE module=?;', ['ChecklistItems']);
    }

    public static function _removeLink()
    {
        global $adb;
        $adb->pquery('DELETE FROM vtiger_settings_field WHERE `linkto` LIKE ?', ['index.php?module=ChecklistItems&view=Settings&parent=Settings']);
        $adb->pquery('DELETE FROM `vtiger_links` WHERE `linktype` = ? AND  `linklabel` =?', ['DETAILVIEWSIDEBARWIDGET', 'Checklists']);
    }

    /**
     * Enable ModTracker for the module.
     */
    public static function enableModTracker($moduleName)
    {
        include_once 'vtlib/Vtiger/Module.php';
        include_once 'modules/ModTracker/ModTracker.php';
        $moduleInstance = Vtiger_Module::getInstance($moduleName);
        ModTracker::enableTrackingForModule($moduleInstance->getId());
    }

    /**
     * Disable ModTracker for the module.
     */
    public function removeData($moduleName)
    {
        include_once 'vtlib/Vtiger/Module.php';
        global $adb;
        $moduleInstance = Vtiger_Module::getInstance($moduleName);
        $adb->pquery('DELETE FROM `vtiger_modtracker_tabs` WHERE `tabid` = ?', [$moduleInstance->getId()]);
        $adb->pquery('DELETE FROM `vtiger_links` WHERE `linktype` = ? AND  `linklabel` = ?', ['HEADERSCRIPT', 'ModCommentsCommonHeaderScript']);
        $adb->pquery('DELETE FROM `vtiger_fieldmodulerel` WHERE `module` = ? OR  `relmodule` = ?', ['ChecklistItems', 'ChecklistItems']);
        $adb->pquery('DELETE FROM `vtiger_picklist` WHERE `name` = ? ', ['checklistitem_status']);
    }

    /**
     * Enable Disable for the module.
     */
    public static function addComment($moduleName)
    {
        include_once 'vtlib/Vtiger/Module.php';
        include_once 'modules/ModComments/ModComments.php';
        ModComments::addWidgetTo([$moduleName]);
    }

    public static function updateFeature()
    {
        global $adb;
        $query = "CREATE TABLE IF NOT EXISTS `vtiger_checklistitems_permissions` (\n                  `permissions` tinyint(1) DEFAULT '0'\n                ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $adb->pquery($query, []);
        $query7 = "CREATE TABLE IF NOT EXISTS `vtiger_checklistitems_user_field` (\n                      `recordid` int(25) NOT NULL,\n                      `userid` int(25) NOT NULL,\n                      `starred` varchar(100) DEFAULT NULL\n                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";
        $adb->pquery($query7, []);
    }

    /**
     * Function to check if entry exsist in webservices if not then enter the entry.
     */
    public static function checkWebServiceEntry()
    {
        global $log;
        $log->debug('Entering checkWebServiceEntry() method....');
        global $adb;
        $sql = "SELECT count(id) AS cnt FROM vtiger_ws_entity WHERE name = 'ChecklistItems'";
        $result = $adb->pquery($sql, []);
        if ($adb->num_rows($result) > 0) {
            $no = $adb->query_result($result, 0, 'cnt');
            if ($no == 0) {
                $tabid = $adb->getUniqueID('vtiger_ws_entity');
                $ws_entitySql = 'INSERT INTO vtiger_ws_entity ( id, name, handler_path, handler_class, ismodule ) VALUES' . " (?, 'ChecklistItems','include/Webservices/VtigerModuleOperation.php', 'VtigerModuleOperation' , 1)";
                $res = $adb->pquery($ws_entitySql, [$tabid]);
                $adb->pquery('UPDATE vtiger_ws_entity_seq SET id=(SELECT MAX(id) FROM vtiger_ws_entity)', []);
                $log->debug('Entered Record in vtiger WS entity ');
            }
        }
        $log->debug('Exiting checkWebServiceEntry() method....');
    }
}
