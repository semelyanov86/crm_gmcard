<?php

/*
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 */
class Vendors extends CRMEntity
{
    public $log;

    public $db;

    public $table_name = 'vtiger_vendor';

    public $table_index = 'vendorid';

    public $tab_name = ['vtiger_crmentity', 'vtiger_vendor', 'vtiger_vendorcf'];

    public $tab_name_index = ['vtiger_crmentity' => 'crmid', 'vtiger_vendor' => 'vendorid', 'vtiger_vendorcf' => 'vendorid'];

    /**
     * Mandatory table for supporting custom fields.
     */
    public $customFieldTable = ['vtiger_vendorcf', 'vendorid'];

    public $column_fields = [];

    // Pavani: Assign value to entity_table
    public $entity_table = 'vtiger_crmentity';

    public $sortby_fields = ['vendorname', 'category'];

    // This is the list of vtiger_fields that are in the lists.
    public $list_fields = [
        'Vendor Name' => ['vendor' => 'vendorname'],
        'Phone' => ['vendor' => 'phone'],
        'Email' => ['vendor' => 'email'],
        'Category' => ['vendor' => 'category'],
    ];

    public $list_fields_name = [
        'Vendor Name' => 'vendorname',
        'Phone' => 'phone',
        'Email' => 'email',
        'Category' => 'category',
    ];

    public $list_link_field = 'vendorname';

    public $search_fields = [
        'Vendor Name' => ['vendor' => 'vendorname'],
        'Phone' => ['vendor' => 'phone'],
    ];

    public $search_fields_name = [
        'Vendor Name' => 'vendorname',
        'Phone' => 'phone',
    ];

    // Specifying required fields for vendors
    public $required_fields =  [];

    // Used when enabling/disabling the mandatory fields for the module.
    // Refers to vtiger_field.fieldname values.
    public $mandatory_fields = ['createdtime', 'modifiedtime', 'vendorname', 'assigned_user_id'];

    // Added these variables which are used as default order by and sortorder in ListView
    public $default_order_by = 'vendorname';

    public $default_sort_order = 'ASC';

    // For Alphabetical search
    public $def_basicsearch_col = 'vendorname';

    /**	Constructor which will set the column_fields in this object.
     */
    public function __construct()
    {
        $this->log = Logger::getLogger('vendor');
        $this->log->debug('Entering Vendors() method ...');
        $this->db = PearDatabase::getInstance();
        $this->column_fields = getColumnFields('Vendors');
        $this->log->debug('Exiting Vendor method ...');
    }

    public function Vendors()
    {
        self::__construct();
    }

    public function save_module($module) {}

    /**	function used to get the list of products which are related to the vendor.
     *	@param int $id - vendor id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_products($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_products(' . $id . ') method ...');
        $this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
        checkFileAccessForInclusion("modules/{$related_module}/{$related_module}.php");
        require_once "modules/{$related_module}/{$related_module}.php";
        $other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);
        $singular_modname = vtlib_toSingular($related_module);

        $parenttab = getParentTab();

        if ($singlepane_view == 'true') {
            $returnset = '&return_module=' . $this_module . '&return_action=DetailView&return_id=' . $id;
        } else {
            $returnset = '&return_module=' . $this_module . '&return_action=CallRelatedList&return_id=' . $id;
        }

        $button = '';

        if ($actions) {
            if (is_string($actions)) {
                $actions = explode(',', strtoupper($actions));
            }
            if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module={$related_module}&return_module={$currentModule}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid={$id}&parenttab={$parenttab}','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "'>&nbsp;";
            }
            if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "' class='crmbutton small create'"
                    . " onclick='this.form.action.value=\"EditView\";this.form.module.value=\"{$related_module}\";this.form.parent_id.value=\"\";' type='submit' name='button'"
                    . " value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "'>";
            }
        }

        $query = "SELECT vtiger_products.productid, vtiger_products.productname, vtiger_products.productcode,
					vtiger_products.commissionrate, vtiger_products.qty_per_unit, vtiger_products.unit_price,
					vtiger_crmentity.crmid, vtiger_crmentity.smownerid,vtiger_vendor.vendorname
			  		FROM vtiger_products
			  		INNER JOIN vtiger_vendor ON vtiger_vendor.vendorid = vtiger_products.vendor_id
			  		INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_products.productid INNER JOIN vtiger_productcf
				    ON vtiger_products.productid = vtiger_productcf.productid
					LEFT JOIN vtiger_users
						ON vtiger_users.id=vtiger_crmentity.smownerid
					LEFT JOIN vtiger_groups
						ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			  		WHERE vtiger_crmentity.deleted = 0 AND vtiger_vendor.vendorid = {$id}";

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_products method ...');

        return $return_value;
    }

    /**	function used to get the list of purchase orders which are related to the vendor.
     *	@param int $id - vendor id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_purchase_orders($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_purchase_orders(' . $id . ') method ...');
        $this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
        checkFileAccessForInclusion("modules/{$related_module}/{$related_module}.php");
        require_once "modules/{$related_module}/{$related_module}.php";
        $other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);
        $singular_modname = vtlib_toSingular($related_module);

        $parenttab = getParentTab();

        if ($singlepane_view == 'true') {
            $returnset = '&return_module=' . $this_module . '&return_action=DetailView&return_id=' . $id;
        } else {
            $returnset = '&return_module=' . $this_module . '&return_action=CallRelatedList&return_id=' . $id;
        }

        $button = '';

        if ($actions) {
            if (is_string($actions)) {
                $actions = explode(',', strtoupper($actions));
            }
            if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module={$related_module}&return_module={$currentModule}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid={$id}&parenttab={$parenttab}','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "'>&nbsp;";
            }
            if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "' class='crmbutton small create'"
                    . " onclick='this.form.action.value=\"EditView\";this.form.module.value=\"{$related_module}\"' type='submit' name='button'"
                    . " value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "'>";
            }
        }

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "select case when (vtiger_users.user_name not like '') then {$userNameSql} else vtiger_groups.groupname end as user_name,vtiger_crmentity.*, vtiger_purchaseorder.*,vtiger_vendor.vendorname from vtiger_purchaseorder inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_purchaseorder.purchaseorderid left outer join vtiger_vendor on vtiger_purchaseorder.vendorid=vtiger_vendor.vendorid LEFT JOIN vtiger_purchaseordercf ON vtiger_purchaseordercf.purchaseorderid = vtiger_purchaseorder.purchaseorderid LEFT JOIN vtiger_pobillads ON vtiger_pobillads.pobilladdressid = vtiger_purchaseorder.purchaseorderid LEFT JOIN vtiger_poshipads ON vtiger_poshipads.poshipaddressid = vtiger_purchaseorder.purchaseorderid  left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid where vtiger_crmentity.deleted=0 and vtiger_purchaseorder.vendorid=" . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_purchase_orders method ...');

        return $return_value;
    }

    // Pavani: Function to create, export query for vendors module
    /** Function to export the vendors in CSV Format.
     * @param reference variable - where condition is passed when the query is executed
     * Returns Export Vendors Query
     */
    public function create_export_query($where)
    {
        global $log;
        global $current_user;
        $log->debug('Entering create_export_query(' . $where . ') method ...');

        include 'include/utils/ExportUtils.php';

        // To get the Permitted fields query and the permitted fields list
        $sql = getPermittedFieldsQuery('Vendors', 'detail_view');
        $fields_list = getFieldsListFromQuery($sql);

        $query = "SELECT {$fields_list} FROM " . $this->entity_table . "
                                INNER JOIN vtiger_vendor
                                        ON vtiger_crmentity.crmid = vtiger_vendor.vendorid
                                LEFT JOIN vtiger_vendorcf
                                        ON vtiger_vendorcf.vendorid=vtiger_vendor.vendorid
                                LEFT JOIN vtiger_seattachmentsrel
                                        ON vtiger_vendor.vendorid=vtiger_seattachmentsrel.crmid
                                LEFT JOIN vtiger_attachments
                                ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
                                LEFT JOIN vtiger_users
                                        ON vtiger_crmentity.smownerid = vtiger_users.id and vtiger_users.status='Active'
                                ";
        $where_auto = ' vtiger_crmentity.deleted = 0 ';

        if ($where != '') {
            $query .= "  WHERE ({$where}) AND " . $where_auto;
        } else {
            $query .= '  WHERE ' . $where_auto;
        }

        $log->debug('Exiting create_export_query method ...');

        return $query;
    }

    /**	function used to get the list of contacts which are related to the vendor.
     *	@param int $id - vendor id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_contacts($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_contacts(' . $id . ') method ...');
        $this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
        checkFileAccessForInclusion("modules/{$related_module}/{$related_module}.php");
        require_once "modules/{$related_module}/{$related_module}.php";
        $other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);
        $singular_modname = vtlib_toSingular($related_module);

        $parenttab = getParentTab();

        if ($singlepane_view == 'true') {
            $returnset = '&return_module=' . $this_module . '&return_action=DetailView&return_id=' . $id;
        } else {
            $returnset = '&return_module=' . $this_module . '&return_action=CallRelatedList&return_id=' . $id;
        }

        $button = '';

        if ($actions) {
            if (is_string($actions)) {
                $actions = explode(',', strtoupper($actions));
            }
            if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module={$related_module}&return_module={$currentModule}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid={$id}&parenttab={$parenttab}','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "'>&nbsp;";
            }
            if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "' class='crmbutton small create'"
                    . " onclick='this.form.action.value=\"EditView\";this.form.module.value=\"{$related_module}\"' type='submit' name='button'"
                    . " value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "'>&nbsp;";
            }
        }

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT case when (vtiger_users.user_name not like '') then {$userNameSql} else vtiger_groups.groupname end as user_name,vtiger_contactdetails.*, vtiger_crmentity.crmid, vtiger_crmentity.smownerid,vtiger_vendorcontactrel.vendorid,vtiger_account.accountname from vtiger_contactdetails
				inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid
				inner join vtiger_vendorcontactrel on vtiger_vendorcontactrel.contactid=vtiger_contactdetails.contactid
				INNER JOIN vtiger_contactaddress ON vtiger_contactdetails.contactid = vtiger_contactaddress.contactaddressid
				INNER JOIN vtiger_contactsubdetails ON vtiger_contactdetails.contactid = vtiger_contactsubdetails.contactsubscriptionid
				INNER JOIN vtiger_customerdetails ON vtiger_contactdetails.contactid = vtiger_customerdetails.customerid
				INNER JOIN vtiger_contactscf ON vtiger_contactdetails.contactid = vtiger_contactscf.contactid
				left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
				left join vtiger_account on vtiger_account.accountid = vtiger_contactdetails.accountid
				left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
				where vtiger_crmentity.deleted=0 and vtiger_vendorcontactrel.vendorid = " . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_contacts method ...');

        return $return_value;
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

        $rel_table_arr = ['Products' => 'vtiger_products', 'PurchaseOrder' => 'vtiger_purchaseorder', 'Contacts' => 'vtiger_vendorcontactrel', 'Emails' => 'vtiger_seactivityrel'];

        $tbl_field_arr = ['vtiger_products' => 'productid', 'vtiger_vendorcontactrel' => 'contactid', 'vtiger_purchaseorder' => 'purchaseorderid', 'vtiger_seactivityrel' => 'activityid'];

        $entity_tbl_field_arr = ['vtiger_products' => 'vendor_id', 'vtiger_vendorcontactrel' => 'vendorid', 'vtiger_purchaseorder' => 'vendorid', 'vtiger_seactivityrel' => 'crmid'];

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
        $log->debug('Exiting transferRelatedRecords...');
    }

    /** Returns a list of the associated emails
     * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
     * All Rights Reserved..
     * Contributor(s): ______________________________________..
     */
    public function get_emails($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_emails(' . $id . ') method ...');
        $this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
        checkFileAccessForInclusion("modules/{$related_module}/{$related_module}.php");
        require_once "modules/{$related_module}/{$related_module}.php";
        $other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);
        $singular_modname = vtlib_toSingular($related_module);

        $parenttab = getParentTab();

        if ($singlepane_view == 'true') {
            $returnset = '&return_module=' . $this_module . '&return_action=DetailView&return_id=' . $id;
        } else {
            $returnset = '&return_module=' . $this_module . '&return_action=CallRelatedList&return_id=' . $id;
        }

        $button = '';

        $button .= '<input type="hidden" name="email_directing_module"><input type="hidden" name="record">';

        if ($actions) {
            if (is_string($actions)) {
                $actions = explode(',', strtoupper($actions));
            }
            if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "' accessyKey='F' class='crmbutton small create' onclick='fnvshobj(this,\"sendmail_cont\");sendmail(\"{$this_module}\",{$id});' type='button' name='button' value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "'></td>";
            }
        }

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT case when (vtiger_users.user_name not like '') then {$userNameSql} else vtiger_groups.groupname end as user_name,
			vtiger_activity.activityid, vtiger_activity.subject,
			vtiger_activity.activitytype, vtiger_crmentity.modifiedtime,
			vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_activity.date_start,vtiger_activity.time_start, vtiger_seactivityrel.crmid as parent_id
			FROM vtiger_activity, vtiger_seactivityrel, vtiger_vendor, vtiger_users, vtiger_crmentity
			LEFT JOIN vtiger_groups
				ON vtiger_groups.groupid=vtiger_crmentity.smownerid
			WHERE vtiger_seactivityrel.activityid = vtiger_activity.activityid
				AND vtiger_vendor.vendorid = vtiger_seactivityrel.crmid
				AND vtiger_users.id=vtiger_crmentity.smownerid
				AND vtiger_crmentity.crmid = vtiger_activity.activityid
				AND vtiger_vendor.vendorid = " . $id . "
				AND vtiger_activity.activitytype='Emails'
				AND vtiger_crmentity.deleted = 0";

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_emails method ...');

        return $return_value;
    }

    /*
     * Function to get the primary query part of a report
     * @param - $module Primary module name
     * returns the query string formed on fetching the related data for report for primary module
     */
    public function generateReportsQuery($module, $queryPlanner)
    {
        $moduletable = $this->table_name;
        $moduleindex = $this->table_index;
        $modulecftable = $this->tab_name[2];
        $modulecfindex = $this->tab_name_index[$modulecftable];

        $query = "from {$moduletable}
			inner join {$modulecftable} as {$modulecftable} on {$modulecftable}.{$modulecfindex}={$moduletable}.{$moduleindex}
			inner join vtiger_crmentity on vtiger_crmentity.crmid={$moduletable}.{$moduleindex}
			left join vtiger_groups as vtiger_groups{$module} on vtiger_groups{$module}.groupid = vtiger_crmentity.smownerid
			left join vtiger_users as vtiger_users" . $module . ' on vtiger_users' . $module . '.id = vtiger_crmentity.smownerid
			left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid
			left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid 
            left join vtiger_users as vtiger_createdby' . $module . ' on vtiger_createdby' . $module . '.id = vtiger_crmentity.smcreatorid 
			left join vtiger_users as vtiger_lastModifiedByVendors on vtiger_lastModifiedByVendors.id = vtiger_crmentity.modifiedby ';

        return $query;
    }

    /*
     * Function to get the secondary query part of a report
     * @param - $module primary module name
     * @param - $secmodule secondary module name
     * returns the query string formed on fetching the related data for report for secondary module
     */
    public function generateReportsSecQuery($module, $secmodule, $queryPlanner)
    {

        $matrix = $queryPlanner->newDependencyMatrix();

        $matrix->setDependency('vtiger_crmentityVendors', ['vtiger_usersVendors', 'vtiger_lastModifiedByVendors']);
        if (!$queryPlanner->requireTable('vtiger_vendor', $matrix)) {
            return '';
        }
        $matrix->setDependency('vtiger_vendor', ['vtiger_crmentityVendors', 'vtiger_vendorcf', 'vtiger_email_trackVendors']);
        $query = $this->getRelationQuery($module, $secmodule, 'vtiger_vendor', 'vendorid', $queryPlanner);
        // TODO Support query planner
        if ($queryPlanner->requireTable('vtiger_crmentityVendors', $matrix)) {
            $query .= ' left join vtiger_crmentity as vtiger_crmentityVendors on vtiger_crmentityVendors.crmid=vtiger_vendor.vendorid and vtiger_crmentityVendors.deleted=0';
        }
        if ($queryPlanner->requireTable('vtiger_vendorcf')) {
            $query .= ' left join vtiger_vendorcf on vtiger_vendorcf.vendorid = vtiger_crmentityVendors.crmid';
        }
        if ($queryPlanner->requireTable('vtiger_email_trackVendors')) {
            $query .= ' LEFT JOIN vtiger_email_track AS vtiger_email_trackVendors ON vtiger_email_trackVendors.crmid = vtiger_vendor.vendorid';
        }
        if ($queryPlanner->requireTable('vtiger_usersVendors')) {
            $query .= ' left join vtiger_users as vtiger_usersVendors on vtiger_usersVendors.id = vtiger_crmentityVendors.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_lastModifiedByVendors')) {
            $query .= ' left join vtiger_users as vtiger_lastModifiedByVendors on vtiger_lastModifiedByVendors.id = vtiger_crmentityVendors.modifiedby ';
        }
        if ($queryPlanner->requireTable('vtiger_createdbyVendors')) {
            $query .= ' left join vtiger_users as vtiger_createdbyVendors on vtiger_createdbyVendors.id = vtiger_crmentityVendors.smcreatorid ';
        }
        // if secondary modules custom reference field is selected
        $query .= parent::getReportsUiType10Query($secmodule, $queryPlanner);

        return $query;
    }

    /*
     * Function to get the relation tables for related modules
     * @param - $secmodule secondary module name
     * returns the array with table names and fieldnames storing relations between module and this module
     */
    public function setRelationTables($secmodule)
    {
        $rel_tables =  [
            'Products' => ['vtiger_products' => ['vendor_id', 'productid'], 'vtiger_vendor' => 'vendorid'],
            'PurchaseOrder' => ['vtiger_purchaseorder' => ['vendorid', 'purchaseorderid'], 'vtiger_vendor' => 'vendorid'],
            'Contacts' => ['vtiger_vendorcontactrel' => ['vendorid', 'contactid'], 'vtiger_vendor' => 'vendorid'],
            'Emails' => ['vtiger_seactivityrel' => ['crmid', 'activityid'], 'vtiger_vendor' => 'vendorid'],
        ];

        return $rel_tables[$secmodule];
    }

    // Function to unlink all the dependent entities of the given Entity by Id
    public function unlinkDependencies($module, $id)
    {
        global $log;
        // Deleting Vendor related PO.
        $po_q = 'SELECT vtiger_crmentity.crmid FROM vtiger_crmentity
			INNER JOIN vtiger_purchaseorder ON vtiger_crmentity.crmid=vtiger_purchaseorder.purchaseorderid
			INNER JOIN vtiger_vendor ON vtiger_vendor.vendorid=vtiger_purchaseorder.vendorid
			WHERE vtiger_crmentity.deleted=0 AND vtiger_purchaseorder.vendorid=?';
        $po_res = $this->db->pquery($po_q, [$id]);
        $po_ids_list = [];
        for ($k = 0; $k < $this->db->num_rows($po_res); ++$k) {
            $po_id = $this->db->query_result($po_res, $k, 'crmid');
            $po_ids_list[] = $po_id;
            $sql = 'UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?';
            $this->db->pquery($sql, [$po_id]);
        }
        // Backup deleted Vendors related Potentials.
        $params = [$id, RB_RECORD_UPDATED, 'vtiger_crmentity', 'deleted', 'crmid', implode(',', $po_ids_list)];
        $this->db->pquery('INSERT INTO vtiger_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);

        // Backup Product-Vendor Relation
        $pro_q = 'SELECT productid FROM vtiger_products WHERE vendor_id=?';
        $pro_res = $this->db->pquery($pro_q, [$id]);
        if ($this->db->num_rows($pro_res) > 0) {
            $pro_ids_list = [];
            for ($k = 0; $k < $this->db->num_rows($pro_res); ++$k) {
                $pro_ids_list[] = $this->db->query_result($pro_res, $k, 'productid');
            }
            $params = [$id, RB_RECORD_UPDATED, 'vtiger_products', 'vendor_id', 'productid', implode(',', $pro_ids_list)];
            $this->db->pquery('INSERT INTO vtiger_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
        }
        // Deleting Product-Vendor Relation.
        $pro_q = 'UPDATE vtiger_products SET vendor_id = 0 WHERE vendor_id = ?';
        $this->db->pquery($pro_q, [$id]);

        /*//Backup Contact-Vendor Relaton
        $con_q = 'SELECT contactid FROM vtiger_vendorcontactrel WHERE vendorid = ?';
        $con_res = $this->db->pquery($con_q, array($id));
        if ($this->db->num_rows($con_res) > 0) {
            for($k=0;$k < $this->db->num_rows($con_res);$k++)
            {
                $con_id = $this->db->query_result($con_res,$k,"contactid");
                $params = array($id, RB_RECORD_DELETED, 'vtiger_vendorcontactrel', 'vendorid', 'contactid', $con_id);
                $this->db->pquery('INSERT INTO vtiger_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
            }
        }
        //Deleting Contact-Vendor Relaton
        $vc_sql = 'DELETE FROM vtiger_vendorcontactrel WHERE vendorid=?';
        $this->db->pquery($vc_sql, array($id));*/

        parent::unlinkDependencies($module, $id);
    }

    public function save_related_module($module, $crmid, $with_module, $with_crmids, $otherParams = [])
    {
        $adb = PearDatabase::getInstance();

        if (!is_array($with_crmids)) {
            $with_crmids = [$with_crmids];
        }
        foreach ($with_crmids as $with_crmid) {
            if ($with_module == 'Contacts') {
                $adb->pquery('insert into vtiger_vendorcontactrel values (?,?)', [$crmid, $with_crmid]);
            } elseif ($with_module == 'Products') {
                $adb->pquery('update vtiger_products set vendor_id=? where productid=?', [$crmid, $with_crmid]);
            } else {
                parent::save_related_module($module, $crmid, $with_module, $with_crmid);
            }
        }
    }

    // Function to unlink an entity with given Id from another entity
    public function unlinkRelationship($id, $return_module, $return_id)
    {
        global $log;
        if (empty($return_module) || empty($return_id)) {
            return;
        }
        if ($return_module == 'Contacts') {
            $sql = 'DELETE FROM vtiger_vendorcontactrel WHERE vendorid=? AND contactid=?';
            $this->db->pquery($sql, [$id, $return_id]);
        } else {
            parent::unlinkRelationship($id, $return_module, $return_id);
        }
    }
}
