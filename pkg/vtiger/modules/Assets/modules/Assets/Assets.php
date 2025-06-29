<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
class Assets extends CRMEntity
{
    public $db;

    public $log; // Used in class functions of CRMEntity

    public $table_name = 'vtiger_assets';

    public $table_index = 'assetsid';

    public $column_fields = [];

    /** Indicator if this is a custom module or standard module */
    public $IsCustomModule = true;

    /**
     * Mandatory table for supporting custom fields.
     */
    public $customFieldTable = ['vtiger_assetscf', 'assetsid'];

    /**
     * Mandatory for Saving, Include tables related to this module.
     */
    public $tab_name = ['vtiger_crmentity', 'vtiger_assets', 'vtiger_assetscf'];

    /**
     * Mandatory for Saving, Include tablename and tablekey columnname here.
     */
    public $tab_name_index = [
        'vtiger_crmentity' => 'crmid',
        'vtiger_assets' => 'assetsid',
        'vtiger_assetscf' => 'assetsid'];

    /**
     * Mandatory for Listing (Related listview).
     */
    public $list_fields = [
        /* Format: Field Label => Array(tablename, columnname) */
        // tablename should not have prefix 'vtiger_'
        'Asset No' => ['assets' => 'asset_no'],
        'Asset Name' => ['assets' => 'assetname'],
        'Customer Name' => ['account' => 'account'],
        'Product Name' => ['products' => 'product'],
    ];

    public $list_fields_name = [
        /* Format: Field Label => fieldname */
        'Asset No' => 'asset_no',
        'Asset Name' => 'assetname',
        'Customer Name' => 'account',
        'Product Name' => 'product',
    ];

    // Make the field link to detail view
    public $list_link_field = 'assetname';

    // For Popup listview and UI type support
    public $search_fields = [
        /* Format: Field Label => Array(tablename, columnname) */
        // tablename should not have prefix 'vtiger_'
        'Asset No' => ['assets' => 'asset_no'],
        'Asset Name' => ['assets' => 'assetname'],
        'Customer Name' => ['account' => 'account'],
        'Product Name' => ['products' => 'product'],
    ];

    public $search_fields_name = [
        /* Format: Field Label => fieldname */
        'Asset No' => 'asset_no',
        'Asset Name' => 'assetname',
        'Customer Name' => 'account',
        'Product Name' => 'product',
    ];

    // For Popup window record selection
    public $popup_fields =  ['assetname', 'account', 'product'];

    // Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
    public $sortby_fields = [];

    // For Alphabetical search
    public $def_basicsearch_col = 'assetname';

    // Required Information for enabling Import feature
    public $required_fields = ['assetname' => 1];

    // Used when enabling/disabling the mandatory fields for the module.
    // Refers to vtiger_field.fieldname values.
    public $mandatory_fields = ['assetname', 'product', 'assigned_user_id'];

    // Callback function list during Importing
    public $special_functions = ['set_import_assigned_user'];

    public $default_order_by = 'assetname';

    public $default_sort_order = 'ASC';

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

    public function save_module($module)
    {
        // module specific save
    }

    /**
     * Return query to use based on given modulename, fieldname
     * Useful to handle specific case handling for Popup.
     */
    public function getQueryByModuleField($module, $fieldname, $srcrecord)
    {
        // $srcrecord could be empty
    }

    /**
     * Get list view query.
     */
    public function getListQuery($module, $where = '')
    {
        $query = "SELECT vtiger_crmentity.*, {$this->table_name}.*";

        // Select Custom Field Table Columns if present
        if (!empty($this->customFieldTable)) {
            $query .= ', ' . $this->customFieldTable[0] . '.* ';
        }

        $query .= " FROM {$this->table_name}";

        $query .= "	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = {$this->table_name}.{$this->table_index}";

        // Consider custom table join as well.
        if (!empty($this->customFieldTable)) {
            $query .= ' INNER JOIN ' . $this->customFieldTable[0] . ' ON ' . $this->customFieldTable[0] . '.' . $this->customFieldTable[1]
                      . " = {$this->table_name}.{$this->table_index}";
        }
        $query .= ' LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid';
        $query .= ' LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid';


        $linkedModulesQuery = $this->db->pquery('SELECT distinct fieldname, columnname, relmodule FROM vtiger_field'
                . ' INNER JOIN vtiger_fieldmodulerel ON vtiger_fieldmodulerel.fieldid = vtiger_field.fieldid'
                . " WHERE uitype='10' AND vtiger_fieldmodulerel.module=?", [$module]);
        $linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

        for ($i = 0; $i < $linkedFieldsCount; ++$i) {
            $related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
            $fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
            $columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');

            $other = CRMEntity::getInstance($related_module);
            vtlib_setup_modulevars($related_module, $other);

            $query .= " LEFT JOIN {$other->table_name} ON {$other->table_name}.{$other->table_index} = {$this->table_name}.{$columnname}";
        }

        $query .= '	WHERE vtiger_crmentity.deleted = 0 ' . $where;
        $query .= $this->getListViewSecurityParameter($module);

        return $query;
    }

    /**
     * Apply security restriction (sharing privilege) query part for List view.
     */
    public function getListViewSecurityParameter($module)
    {
        global $current_user;
        require 'user_privileges/user_privileges_' . $current_user->id . '.php';
        require 'user_privileges/sharing_privileges_' . $current_user->id . '.php';

        $sec_query = '';
        $tabid = getTabid($module);

        if ($is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
            && $defaultOrgSharingPermission[$tabid] == 3) {

            $sec_query .= " AND (vtiger_crmentity.smownerid in({$current_user->id}) OR vtiger_crmentity.smownerid IN
					(
						SELECT vtiger_user2role.userid FROM vtiger_user2role
						INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid
						INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid
						WHERE vtiger_role.parentrole LIKE '" . $current_user_parent_role_seq . "::%'
					)
					OR vtiger_crmentity.smownerid IN
					(
						SELECT shareduserid FROM vtiger_tmp_read_user_sharing_per
						WHERE userid=" . $current_user->id . ' AND tabid=' . $tabid . '
					)
					OR
						(';

            // Build the query based on the group association of current user.
            if (php7_sizeof($current_user_groups) > 0) {
                $sec_query .= ' vtiger_groups.groupid IN (' . implode(',', $current_user_groups) . ') OR ';
            }
            $sec_query .= ' vtiger_groups.groupid IN
						(
							SELECT vtiger_tmp_read_group_sharing_per.sharedgroupid
							FROM vtiger_tmp_read_group_sharing_per
							WHERE userid=' . $current_user->id . ' and tabid=' . $tabid . '
						)';
            $sec_query .= ')
				)';
        }

        return $sec_query;
    }

    /**
     * Create query to export the records.
     */
    public function create_export_query($where)
    {
        global $current_user;

        include 'include/utils/ExportUtils.php';

        // To get the Permitted fields query and the permitted fields list
        $sql = getPermittedFieldsQuery('Assets', 'detail_view');

        $fields_list = getFieldsListFromQuery($sql);

        $query = "SELECT {$fields_list}, vtiger_users.user_name AS user_name
					FROM vtiger_crmentity INNER JOIN {$this->table_name} ON vtiger_crmentity.crmid={$this->table_name}.{$this->table_index}";

        if (!empty($this->customFieldTable)) {
            $query .= ' INNER JOIN ' . $this->customFieldTable[0] . ' ON ' . $this->customFieldTable[0] . '.' . $this->customFieldTable[1]
                      . " = {$this->table_name}.{$this->table_index}";
        }

        $query .= ' LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid';
        $query .= " LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id and vtiger_users.status='Active'";

        $where_auto = ' vtiger_crmentity.deleted=0';

        if ($where != '') {
            $query .= " WHERE ({$where}) AND {$where_auto}";
        } else {
            $query .= " WHERE {$where_auto}";
        }

        require 'user_privileges/user_privileges_' . $current_user->id . '.php';
        require 'user_privileges/sharing_privileges_' . $current_user->id . '.php';

        // Security Check for Field Access
        if ($is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1 && $defaultOrgSharingPermission[getTabid('Assets')] == 3) {
            // Added security check to get the permitted records only
            $query = $query . ' ' . getListViewSecurityParameter($thismodule);
        }

        return $query;
    }

    /**
     * Transform the value while exporting.
     */
    public function transform_export_value($key, $value)
    {
        if ($key == 'owner') {
            return getOwnerName($value);
        }

        return parent::transform_export_value($key, $value);
    }

    /**
     * Function which will give the basic query to find duplicates.
     */
    public function getDuplicatesQuery($module, $table_cols, $field_values, $ui_type_arr, $select_cols = '')
    {
        $select_clause = 'SELECT ' . $this->table_name . '.' . $this->table_index . ' AS recordid, vtiger_users_last_import.deleted,' . $table_cols;

        // Select Custom Field Table Columns if present
        if (isset($this->customFieldTable)) {
            $query .= ', ' . $this->customFieldTable[0] . '.* ';
        }

        $from_clause = " FROM {$this->table_name}";

        $from_clause .= "	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = {$this->table_name}.{$this->table_index}";

        // Consider custom table join as well.
        if (isset($this->customFieldTable)) {
            $from_clause .= ' INNER JOIN ' . $this->customFieldTable[0] . ' ON ' . $this->customFieldTable[0] . '.' . $this->customFieldTable[1]
                      . " = {$this->table_name}.{$this->table_index}";
        }
        $from_clause .= ' LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
						LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid';

        $where_clause = '	WHERE vtiger_crmentity.deleted = 0';
        $where_clause .= $this->getListViewSecurityParameter($module);

        if (isset($select_cols) && trim($select_cols) != '') {
            $sub_query = "SELECT {$select_cols} FROM  {$this->table_name} AS t "
                . ' INNER JOIN vtiger_crmentity AS crm ON crm.crmid = t.' . $this->table_index;
            // Consider custom table join as well.
            if (isset($this->customFieldTable)) {
                $sub_query .= ' INNER JOIN ' . $this->customFieldTable[0] . ' tcf ON tcf.' . $this->customFieldTable[1] . " = t.{$this->table_index}";
            }
            $sub_query .= " WHERE crm.deleted=0 GROUP BY {$select_cols} HAVING COUNT(*)>1";
        } else {
            $sub_query = "SELECT {$table_cols} {$from_clause} {$where_clause} GROUP BY {$table_cols} HAVING COUNT(*)>1";
        }


        $query = $select_clause . $from_clause
                    . ' LEFT JOIN vtiger_users_last_import ON vtiger_users_last_import.bean_id=' . $this->table_name . '.' . $this->table_index
                    . ' INNER JOIN (' . $sub_query . ') AS temp ON ' . get_on_clause($field_values, $ui_type_arr, $module)
                    . $where_clause
                    . " ORDER BY {$table_cols}," . $this->table_name . '.' . $this->table_index . ' ASC';

        return $query;
    }
    /**
     * Handle saving related module information.
     * NOTE: This function has been added to CRMEntity (base class).
     * You can override the behavior by re-defining it here.
     */
    // function save_related_module($module, $crmid, $with_module, $with_crmid) { }

    /**
     * Handle deleting related module information.
     * NOTE: This function has been added to CRMEntity (base class).
     * You can override the behavior by re-defining it here.
     */
    // function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

    /**
     * Handle getting related list information.
     * NOTE: This function has been added to CRMEntity (base class).
     * You can override the behavior by re-defining it here.
     */
    // function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }


    /*
     * Function to get the primary query part of a report
     * @param - $module primary module name
     * returns the query string formed on fetching the related data for report for secondary module
     */
    // function generateReportsQuery($module){ }

    /*
     * Function to get the secondary query part of a report
     * @param - $module primary module name
     * @param - $secmodule secondary module name
     * returns the query string formed on fetching the related data for report for secondary module
     */
    // function generateReportsSecQuery($module,$secmodule){ }

    // Function to unlink all the dependent entities of the given Entity by Id
    public function unlinkDependencies($module, $id)
    {
        global $log;
        parent::unlinkDependencies($module, $id);
    }

    /**
     * Invoked when special actions are performed on the module.
     * @param string Module name
     * @param string Event Type
     */
    public function vtlib_handler($moduleName, $eventType)
    {
        require_once 'include/utils/utils.php';
        global $adb;

        if ($eventType == 'module.postinstall') {
            // Add Assets Module to Customer Portal
            global $adb;

            $this->addModuleToCustomerPortal();

            include_once 'vtlib/Vtiger/Module.php';

            // Mark the module as Standard module
            $adb->pquery('UPDATE vtiger_tab SET customized=0 WHERE name=?', [$moduleName]);

            // adds sharing accsess
            $AssetsModule  = Vtiger_Module::getInstance('Assets');
            Vtiger_Access::setDefaultSharing($AssetsModule);

            // Showing Assets module in the related modules in the More Information Tab
            $assetInstance = Vtiger_Module::getInstance('Assets');
            $assetLabel = 'Assets';

            $accountInstance = Vtiger_Module::getInstance('Accounts');
            $accountInstance->setRelatedlist($assetInstance, $assetLabel, ['ADD'], 'get_dependents_list');

            $productInstance = Vtiger_Module::getInstance('Products');
            $productInstance->setRelatedlist($assetInstance, $assetLabel, ['ADD'], 'get_dependents_list');

            $InvoiceInstance = Vtiger_Module::getInstance('Invoice');
            $InvoiceInstance->setRelatedlist($assetInstance, $assetLabel, ['ADD'], 'get_dependents_list');

            $result = $adb->pquery('SELECT 1 FROM vtiger_modentity_num WHERE semodule = ? AND active = 1', [$moduleName]);
            if (!$adb->num_rows($result)) {
                // Initialize module sequence for the module
                $adb->pquery('INSERT INTO vtiger_modentity_num values(?,?,?,?,?,?)', [$adb->getUniqueId('vtiger_modentity_num'), $moduleName, 'ASSET', 1, 1, 1]);
            }

        } elseif ($eventType == 'module.disabled') {
            // TODO Handle actions when this module is disabled.
        } elseif ($eventType == 'module.enabled') {
            // TODO Handle actions when this module is enabled.
        } elseif ($eventType == 'module.preuninstall') {
            // TODO Handle actions when this module is about to be deleted.
        } elseif ($eventType == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } elseif ($eventType == 'module.postupdate') {
            $this->addModuleToCustomerPortal();

            $result = $adb->pquery('SELECT 1 FROM vtiger_modentity_num WHERE semodule = ? AND active =1 ', [$moduleName]);
            if (!$adb->num_rows($result)) {
                // Initialize module sequence for the module
                $adb->pquery('INSERT INTO vtiger_modentity_num values(?,?,?,?,?,?)', [$adb->getUniqueId('vtiger_modentity_num'), $moduleName, 'ASSET', 1, 1, 1]);
            }
        }
    }

    public function addModuleToCustomerPortal()
    {
        $adb = PearDatabase::getInstance();

        $assetsResult = $adb->pquery('SELECT tabid FROM vtiger_tab WHERE name=?', ['Assets']);
        $assetsTabId = $adb->query_result($assetsResult, 0, 'tabid');
        if (getTabid('CustomerPortal') && $assetsTabId) {
            $checkAlreadyExists = $adb->pquery('SELECT 1 FROM vtiger_customerportal_tabs WHERE tabid=?', [$assetsTabId]);
            if ($checkAlreadyExists && $adb->num_rows($checkAlreadyExists) < 1) {
                $maxSequenceQuery = $adb->pquery('SELECT max(sequence) as maxsequence FROM vtiger_customerportal_tabs', []);
                $maxSequence = $adb->query_result($maxSequenceQuery, 0, 'maxsequence');
                $nextSequence = $maxSequence + 1;
                $adb->pquery('INSERT INTO vtiger_customerportal_tabs(tabid,visible,sequence) VALUES (?,?,?)', [$assetsTabId, 1, $nextSequence]);
            }
            $checkAlreadyExists = $adb->pquery('SELECT 1 FROM vtiger_customerportal_prefs WHERE tabid=?', [$assetsTabId]);
            if ($checkAlreadyExists && $adb->num_rows($checkAlreadyExists) < 1) {
                $adb->pquery('INSERT INTO vtiger_customerportal_prefs(tabid,prefkey,prefvalue) VALUES (?,?,?)', [$assetsTabId, 'showrelatedinfo', 1]);
            }
        }
    }

    /**
     * Move the related records of the specified list of id's to the given record.
     * @param string This module name
     * @param array List of Entity Id's from which related records need to be transfered
     * @param int Id of the the Record to which the related records are to be moved
     */
    public function transferRelatedRecords($module, $transferEntityIds, $entityId)
    {
        global $adb,$log;
        $log->debug("Entering function transferRelatedRecords ({$module}, {$transferEntityIds}, {$entityId})");

        $rel_table_arr = ['Documents' => 'vtiger_senotesrel', 'Attachments' => 'vtiger_seattachmentsrel'];

        $tbl_field_arr = ['vtiger_senotesrel' => 'notesid', 'vtiger_seattachmentsrel' => 'attachmentsid'];

        $entity_tbl_field_arr = ['vtiger_senotesrel' => 'crmid', 'vtiger_seattachmentsrel' => 'crmid'];

        foreach ($transferEntityIds as $transferId) {
            foreach ($rel_table_arr as $rel_module => $rel_table) {
                $id_field = $tbl_field_arr[$rel_table];
                $entity_id_field = $entity_tbl_field_arr[$rel_table];
                // IN clause to avoid duplicate entries
                $sel_result =  $adb->pquery(
                    "select {$id_field} from {$rel_table} where {$entity_id_field}=? "
                        . " and {$id_field} not in (select {$id_field} from {$rel_table} where {$entity_id_field}=?)",
                    [$transferId, $entityId],
                );
                $res_cnt = $adb->num_rows($sel_result);
                if ($res_cnt > 0) {
                    for ($i = 0; $i < $res_cnt; ++$i) {
                        $id_field_value = $adb->query_result($sel_result, $i, $id_field);
                        $adb->pquery(
                            "update {$rel_table} set {$entity_id_field}=? where {$entity_id_field}=? and {$id_field}=?",
                            [$entityId, $transferId, $id_field_value],
                        );
                    }
                }
            }
        }
        parent::transferRelatedRecords($module, $transferEntityIds, $entityId);
        $log->debug('Exiting transferRelatedRecords...');
    }
}
