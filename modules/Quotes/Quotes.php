<?php

/*
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 */
/*
 * $Header$
 * Description:  Defines the Account SugarBean Account entity with the necessary
 * methods and variables.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 */
class Quotes extends CRMEntity
{
    public $log;

    public $db;

    public $table_name = 'vtiger_quotes';

    public $table_index = 'quoteid';

    public $tab_name = ['vtiger_crmentity', 'vtiger_quotes', 'vtiger_quotesbillads', 'vtiger_quotesshipads', 'vtiger_quotescf', 'vtiger_inventoryproductrel'];

    public $tab_name_index = ['vtiger_crmentity' => 'crmid', 'vtiger_quotes' => 'quoteid', 'vtiger_quotesbillads' => 'quotebilladdressid', 'vtiger_quotesshipads' => 'quoteshipaddressid', 'vtiger_quotescf' => 'quoteid', 'vtiger_inventoryproductrel' => 'id'];

    /**
     * Mandatory table for supporting custom fields.
     */
    public $customFieldTable = ['vtiger_quotescf', 'quoteid'];

    public $entity_table = 'vtiger_crmentity';

    public $billadr_table = 'vtiger_quotesbillads';

    public $object_name = 'Quote';

    public $new_schema = true;

    public $column_fields = [];

    public $sortby_fields = ['subject', 'crmid', 'smownerid', 'accountname', 'lastname'];

    // This is used to retrieve related vtiger_fields from form posts.
    public $additional_column_fields = ['assigned_user_name', 'smownerid', 'opportunity_id', 'case_id', 'contact_id', 'task_id', 'note_id', 'meeting_id', 'call_id', 'email_id', 'parent_name', 'member_id'];

    // This is the list of vtiger_fields that are in the lists.
    public $list_fields = [
        // 'Quote No'=>Array('crmentity'=>'crmid'),
        // Module Sequence Numbering
        'Quote No' => ['quotes' => 'quote_no'],
        // END
        'Subject' => ['quotes' => 'subject'],
        'Quote Stage' => ['quotes' => 'quotestage'],
        'Potential Name' => ['quotes' => 'potentialid'],
        'Account Name' => ['account' => 'accountid'],
        'Total' => ['quotes' => 'total'],
        'Assigned To' => ['crmentity' => 'smownerid'],
    ];

    public $list_fields_name = [
        'Quote No' => 'quote_no',
        'Subject' => 'subject',
        'Quote Stage' => 'quotestage',
        'Potential Name' => 'potential_id',
        'Account Name' => 'account_id',
        'Total' => 'hdnGrandTotal',
        'Assigned To' => 'assigned_user_id',
    ];

    public $list_link_field = 'subject';

    public $search_fields = [
        'Quote No' => ['quotes' => 'quote_no'],
        'Subject' => ['quotes' => 'subject'],
        'Account Name' => ['quotes' => 'accountid'],
        'Quote Stage' => ['quotes' => 'quotestage'],
    ];

    public $search_fields_name = [
        'Quote No' => 'quote_no',
        'Subject' => 'subject',
        'Account Name' => 'account_id',
        'Quote Stage' => 'quotestage',
    ];

    // This is the list of vtiger_fields that are required.
    public $required_fields =  ['accountname' => 1];

    // Added these variables which are used as default order by and sortorder in ListView
    public $default_order_by = 'crmid';

    public $default_sort_order = 'ASC';
    // var $groupTable = Array('vtiger_quotegrouprelation','quoteid');

    public $mandatory_fields = ['subject', 'createdtime', 'modifiedtime', 'assigned_user_id', 'quantity', 'listprice', 'productid'];

    // For Alphabetical search
    public $def_basicsearch_col = 'subject';

    // For workflows update field tasks is deleted all the lineitems.
    public $isLineItemUpdate = true;

    /**	Constructor which will set the column_fields in this object.
     */
    public function __construct()
    {
        $this->log = Logger::getLogger('quote');
        $this->db = PearDatabase::getInstance();
        $this->column_fields = getColumnFields('Quotes');
    }

    public function Quotes()
    {
        self::__construct();
    }

    public function save_module()
    {
        global $adb;

        /* $_REQUEST['REQUEST_FROM_WS'] is set from webservices script.
         * Depending on $_REQUEST['totalProductCount'] value inserting line items into DB.
         * This should be done by webservices, not be normal save of Inventory record.
         * So unsetting the value $_REQUEST['totalProductCount'] through check point
         */
        if (isset($_REQUEST['REQUEST_FROM_WS']) && $_REQUEST['REQUEST_FROM_WS']) {
            unset($_REQUEST['totalProductCount']);
        }
        $_REQUEST['ajxaction'] ??= '';
        // in ajax save we should not call this function, because this will delete all the existing product values
        if ($_REQUEST['action'] != 'QuotesAjax' && isset($_REQUEST['ajxaction']) && $_REQUEST['ajxaction'] != 'DETAILVIEW'
                && $_REQUEST['action'] != 'MassEditSave' && $_REQUEST['action'] != 'ProcessDuplicates'
                && $_REQUEST['action'] != 'SaveAjax' && $this->isLineItemUpdate != false) {
            // Based on the total Number of rows we will save the product relationship with this entity
            saveInventoryProductDetails($this, 'Quotes');
        }

        // Update the currency id and the conversion rate for the quotes
        $update_query = 'update vtiger_quotes set currency_id=?, conversion_rate=? where quoteid=?';
        $update_params = [$this->column_fields['currency_id'], $this->column_fields['conversion_rate'], $this->id];
        $adb->pquery($update_query, $update_params);
    }

    /**	function used to get the list of sales orders which are related to the Quotes.
     *	@param int $id - quote id
     *	@return array - return an array which will be returned from the function GetRelatedList
     */
    public function get_salesorder($id)
    {
        global $log,$singlepane_view;
        $log->debug('Entering get_salesorder(' . $id . ') method ...');
        require_once 'modules/SalesOrder/SalesOrder.php';
        $focus = new SalesOrder();

        $button = '';

        if ($singlepane_view == 'true') {
            $returnset = '&return_module=Quotes&return_action=DetailView&return_id=' . $id;
        } else {
            $returnset = '&return_module=Quotes&return_action=CallRelatedList&return_id=' . $id;
        }

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "select vtiger_crmentity.*, vtiger_salesorder.*, vtiger_quotes.subject as quotename
			, vtiger_account.accountname,case when (vtiger_users.user_name not like '') then
			{$userNameSql} else vtiger_groups.groupname end as user_name
		from vtiger_salesorder
		inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_salesorder.salesorderid
		left outer join vtiger_quotes on vtiger_quotes.quoteid=vtiger_salesorder.quoteid
		left outer join vtiger_account on vtiger_account.accountid=vtiger_salesorder.accountid
		left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
        LEFT JOIN vtiger_salesordercf ON vtiger_salesordercf.salesorderid = vtiger_salesorder.salesorderid
        LEFT JOIN vtiger_invoice_recurring_info ON vtiger_invoice_recurring_info.salesorderid = vtiger_salesorder.salesorderid
		LEFT JOIN vtiger_sobillads ON vtiger_sobillads.sobilladdressid = vtiger_salesorder.salesorderid
		LEFT JOIN vtiger_soshipads ON vtiger_soshipads.soshipaddressid = vtiger_salesorder.salesorderid
		left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
		where vtiger_crmentity.deleted=0 and vtiger_salesorder.quoteid = " . $id;
        $log->debug('Exiting get_salesorder method ...');

        return GetRelatedList('Quotes', 'SalesOrder', $focus, $query, $button, $returnset);
    }

    /**	function used to get the list of activities which are related to the Quotes.
     *	@param int $id - quote id
     *	@return array - return an array which will be returned from the function GetRelatedList
     */
    public function get_activities($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_activities(' . $id . ') method ...');
        $this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
        require_once "modules/{$related_module}/Activity.php";
        $other = new Activity();
        vtlib_setup_modulevars($related_module, $other);
        $singular_modname = vtlib_toSingular($related_module);

        $parenttab = getParentTab();

        if ($singlepane_view == 'true') {
            $returnset = '&return_module=' . $this_module . '&return_action=DetailView&return_id=' . $id;
        } else {
            $returnset = '&return_module=' . $this_module . '&return_action=CallRelatedList&return_id=' . $id;
        }

        $button = '';

        $button .= '<input type="hidden" name="activity_mode">';

        if ($actions) {
            if (is_string($actions)) {
                $actions = explode(',', strtoupper($actions));
            }
            if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
                if (getFieldVisibilityPermission('Calendar', $current_user->id, 'parent_id', 'readwrite') == '0') {
                    $button .= "<input title='" . getTranslatedString('LBL_NEW') . ' ' . getTranslatedString('LBL_TODO', $related_module) . "' class='crmbutton small create'"
                        . " onclick='this.form.action.value=\"EditView\";this.form.module.value=\"{$related_module}\";this.form.return_module.value=\"{$this_module}\";this.form.activity_mode.value=\"Task\";' type='submit' name='button'"
                        . " value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString('LBL_TODO', $related_module) . "'>&nbsp;";
                }
            }
        }

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT case when (vtiger_users.user_name not like '') then {$userNameSql} else
		vtiger_groups.groupname end as user_name, vtiger_contactdetails.contactid,
		vtiger_contactdetails.lastname, vtiger_contactdetails.firstname, vtiger_activity.*,
		vtiger_seactivityrel.crmid as parent_id,vtiger_crmentity.crmid, vtiger_crmentity.smownerid,
		vtiger_crmentity.modifiedtime,vtiger_recurringevents.recurringtype
		from vtiger_activity
		inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=
		vtiger_activity.activityid
		inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid
		left join vtiger_cntactivityrel on vtiger_cntactivityrel.activityid=
		vtiger_activity.activityid
		left join vtiger_contactdetails on vtiger_contactdetails.contactid =
		vtiger_cntactivityrel.contactid
		left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
		left outer join vtiger_recurringevents on vtiger_recurringevents.activityid=
		vtiger_activity.activityid
		left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
		where vtiger_seactivityrel.crmid=" . $id . " and vtiger_crmentity.deleted=0 and
			activitytype='Task' and (vtiger_activity.status is not NULL and
			vtiger_activity.status != 'Completed') and (vtiger_activity.status is not NULL and
			vtiger_activity.status != 'Deferred')";

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_activities method ...');

        return $return_value;
    }

    /**	function used to get the the activity history related to the quote.
     *	@param int $id - quote id
     *	@return array - return an array which will be returned from the function GetHistory
     */
    public function get_history($id)
    {
        global $log;
        $log->debug('Entering get_history(' . $id . ') method ...');
        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.status,
			vtiger_activity.eventstatus, vtiger_activity.activitytype,vtiger_activity.date_start,
			vtiger_activity.due_date,vtiger_activity.time_start, vtiger_activity.time_end,
			vtiger_contactdetails.contactid,
			vtiger_contactdetails.firstname,vtiger_contactdetails.lastname, vtiger_crmentity.modifiedtime,
			vtiger_crmentity.createdtime, vtiger_crmentity.description, case when (vtiger_users.user_name not like '') then {$userNameSql} else vtiger_groups.groupname end as user_name
			from vtiger_activity
				inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid
				inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid
				left join vtiger_cntactivityrel on vtiger_cntactivityrel.activityid= vtiger_activity.activityid
				left join vtiger_contactdetails on vtiger_contactdetails.contactid= vtiger_cntactivityrel.contactid
                                left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
				left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
				where vtiger_activity.activitytype='Task'
  				and (vtiger_activity.status = 'Completed' or vtiger_activity.status = 'Deferred')
	 	        	and vtiger_seactivityrel.crmid=" . $id . '
                                and vtiger_crmentity.deleted = 0';
        // Don't add order by, because, for security, one more condition will be added with this query in include/RelatedListView.php

        $log->debug('Exiting get_history method ...');

        return getHistory('Quotes', $query, $id);
    }

    /**	Function used to get the Quote Stage history of the Quotes.
     *	@param $id - quote id
     *	@return $return_data - array with header and the entries in format Array('header'=>$header,'entries'=>$entries_list) where as $header and $entries_list are arrays which contains header values and all column values of all entries
     */
    public function get_quotestagehistory($id)
    {
        global $log;
        $log->debug('Entering get_quotestagehistory(' . $id . ') method ...');

        global $adb;
        global $mod_strings;
        global $app_strings;

        $query = 'select vtiger_quotestagehistory.*, vtiger_quotes.quote_no from vtiger_quotestagehistory inner join vtiger_quotes on vtiger_quotes.quoteid = vtiger_quotestagehistory.quoteid inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_quotes.quoteid where vtiger_crmentity.deleted = 0 and vtiger_quotes.quoteid = ?';
        $result = $adb->pquery($query, [$id]);
        $noofrows = $adb->num_rows($result);

        $header[] = $app_strings['Quote No'];
        $header[] = $app_strings['LBL_ACCOUNT_NAME'];
        $header[] = $app_strings['LBL_AMOUNT'];
        $header[] = $app_strings['Quote Stage'];
        $header[] = $app_strings['LBL_LAST_MODIFIED'];

        // Getting the field permission for the current user. 1 - Not Accessible, 0 - Accessible
        // Account Name , Total are mandatory fields. So no need to do security check to these fields.
        global $current_user;

        // If field is accessible then getFieldVisibilityPermission function will return 0 else return 1
        $quotestage_access = (getFieldVisibilityPermission('Quotes', $current_user->id, 'quotestage') != '0') ? 1 : 0;
        $picklistarray = getAccessPickListValues('Quotes');

        $quotestage_array = ($quotestage_access != 1) ? $picklistarray['quotestage'] : [];
        // - ==> picklist field is not permitted in profile
        // Not Accessible - picklist is permitted in profile but picklist value is not permitted
        $error_msg = ($quotestage_access != 1) ? 'Not Accessible' : '-';

        while ($row = $adb->fetch_array($result)) {
            $entries = [];

            // Module Sequence Numbering
            // $entries[] = $row['quoteid'];
            $entries[] = $row['quote_no'];
            // END
            $entries[] = $row['accountname'];
            $entries[] = $row['total'];
            $entries[] = (in_array($row['quotestage'], $quotestage_array)) ? $row['quotestage'] : $error_msg;
            $date = new DateTimeField($row['lastmodified']);
            $entries[] = $date->getDisplayDateTimeValue();

            $entries_list[] = $entries;
        }

        $return_data = ['header' => $header, 'entries' => $entries_list];

        $log->debug('Exiting get_quotestagehistory method ...');

        return $return_data;
    }

    // Function to get column name - Overriding function of base class
    public function get_column_value($columname, $fldvalue, $fieldname, $uitype, $datatype = '')
    {
        if ($columname == 'potentialid' || $columname == 'contactid') {
            if ($fldvalue == '') {
                return null;
            }
        }

        return parent::get_column_value($columname, $fldvalue, $fieldname, $uitype, $datatype);
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
        $matrix->setDependency('vtiger_crmentityQuotes', ['vtiger_usersQuotes', 'vtiger_groupsQuotes', 'vtiger_lastModifiedByQuotes']);
        $matrix->setDependency('vtiger_inventoryproductrelQuotes', ['vtiger_productsQuotes', 'vtiger_serviceQuotes']);

        if (!$queryPlanner->requireTable('vtiger_quotes', $matrix)) {
            return '';
        }
        $matrix->setDependency('vtiger_quotes', ['vtiger_crmentityQuotes', "vtiger_currency_info{$secmodule}",
            'vtiger_quotescf', 'vtiger_potentialRelQuotes', 'vtiger_quotesbillads', 'vtiger_quotesshipads',
            'vtiger_inventoryproductrelQuotes', 'vtiger_contactdetailsQuotes', 'vtiger_accountQuotes',
            'vtiger_invoice_recurring_info', 'vtiger_quotesQuotes', 'vtiger_usersRel1']);

        $query = $this->getRelationQuery($module, $secmodule, 'vtiger_quotes', 'quoteid', $queryPlanner);
        if ($queryPlanner->requireTable('vtiger_crmentityQuotes', $matrix)) {
            $query .= ' left join vtiger_crmentity as vtiger_crmentityQuotes on vtiger_crmentityQuotes.crmid=vtiger_quotes.quoteid and vtiger_crmentityQuotes.deleted=0';
        }
        if ($queryPlanner->requireTable('vtiger_quotescf')) {
            $query .= ' left join vtiger_quotescf on vtiger_quotes.quoteid = vtiger_quotescf.quoteid';
        }
        if ($queryPlanner->requireTable('vtiger_quotesbillads')) {
            $query .= ' left join vtiger_quotesbillads on vtiger_quotes.quoteid=vtiger_quotesbillads.quotebilladdressid';
        }
        if ($queryPlanner->requireTable('vtiger_quotesshipads')) {
            $query .= ' left join vtiger_quotesshipads on vtiger_quotes.quoteid=vtiger_quotesshipads.quoteshipaddressid';
        }
        if ($queryPlanner->requireTable("vtiger_currency_info{$secmodule}")) {
            $query .= " left join vtiger_currency_info as vtiger_currency_info{$secmodule} on vtiger_currency_info{$secmodule}.id = vtiger_quotes.currency_id";
        }
        if ($queryPlanner->requireTable('vtiger_inventoryproductrelQuotes', $matrix)) {
        }
        if ($queryPlanner->requireTable('vtiger_productsQuotes')) {
            $query .= ' left join vtiger_products as vtiger_productsQuotes on vtiger_productsQuotes.productid = vtiger_inventoryproductreltmpQuotes.productid';
        }
        if ($queryPlanner->requireTable('vtiger_serviceQuotes')) {
            $query .= ' left join vtiger_service as vtiger_serviceQuotes on vtiger_serviceQuotes.serviceid = vtiger_inventoryproductreltmpQuotes.productid';
        }
        if ($queryPlanner->requireTable('vtiger_groupsQuotes')) {
            $query .= ' left join vtiger_groups as vtiger_groupsQuotes on vtiger_groupsQuotes.groupid = vtiger_crmentityQuotes.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_usersQuotes')) {
            $query .= ' left join vtiger_users as vtiger_usersQuotes on vtiger_usersQuotes.id = vtiger_crmentityQuotes.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_usersRel1')) {
            $query .= ' left join vtiger_users as vtiger_usersRel1 on vtiger_usersRel1.id = vtiger_quotes.inventorymanager';
        }
        if ($queryPlanner->requireTable('vtiger_potentialRelQuotes')) {
            $query .= ' left join vtiger_potential as vtiger_potentialRelQuotes on vtiger_potentialRelQuotes.potentialid = vtiger_quotes.potentialid';
        }
        if ($queryPlanner->requireTable('vtiger_contactdetailsQuotes')) {
            $query .= ' left join vtiger_contactdetails as vtiger_contactdetailsQuotes on vtiger_contactdetailsQuotes.contactid = vtiger_quotes.contactid';
        }
        if ($queryPlanner->requireTable('vtiger_accountQuotes')) {
            $query .= ' left join vtiger_account as vtiger_accountQuotes on vtiger_accountQuotes.accountid = vtiger_quotes.accountid';
        }
        if ($queryPlanner->requireTable('vtiger_lastModifiedByQuotes')) {
            $query .= ' left join vtiger_users as vtiger_lastModifiedByQuotes on vtiger_lastModifiedByQuotes.id = vtiger_crmentityQuotes.modifiedby ';
        }
        if ($queryPlanner->requireTable('vtiger_createdbyQuotes')) {
            $query .= ' left join vtiger_users as vtiger_createdbyQuotes on vtiger_createdbyQuotes.id = vtiger_crmentityQuotes.smcreatorid ';
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
            'SalesOrder' => ['vtiger_salesorder' => ['quoteid', 'salesorderid'], 'vtiger_quotes' => 'quoteid'],
            'Calendar' => ['vtiger_seactivityrel' => ['crmid', 'activityid'], 'vtiger_quotes' => 'quoteid'],
            'Documents' => ['vtiger_senotesrel' => ['crmid', 'notesid'], 'vtiger_quotes' => 'quoteid'],
            'Accounts' => ['vtiger_quotes' => ['quoteid', 'accountid']],
            'Contacts' => ['vtiger_quotes' => ['quoteid', 'contactid']],
            'Potentials' => ['vtiger_quotes' => ['quoteid', 'potentialid']],
        ];

        return $rel_tables[$secmodule];
    }

    // Function to unlink an entity with given Id from another entity
    public function unlinkRelationship($id, $return_module, $return_id)
    {
        global $log;
        if (empty($return_module) || empty($return_id)) {
            return;
        }

        if ($return_module == 'Accounts') {
            $this->trash('Quotes', $id);
        } elseif ($return_module == 'Potentials') {
            $relation_query = 'UPDATE vtiger_quotes SET potentialid=? WHERE quoteid=?';
            $this->db->pquery($relation_query, [null, $id]);
        } elseif ($return_module == 'Contacts') {
            $relation_query = 'UPDATE vtiger_quotes SET contactid=? WHERE quoteid=?';
            $this->db->pquery($relation_query, [null, $id]);
        } elseif ($return_module == 'Documents') {
            $sql = 'DELETE FROM vtiger_senotesrel WHERE crmid=? AND notesid=?';
            $this->db->pquery($sql, [$id, $return_id]);
        } elseif ($return_module == 'Leads') {
            $relation_query = 'UPDATE vtiger_quotes SET contactid=? WHERE quoteid=?';
            $this->db->pquery($relation_query, [null, $id]);
        } else {
            parent::unlinkRelationship($id, $return_module, $return_id);
        }
    }

    public function insertIntoEntityTable($table_name, $module, $fileid = '')
    {
        // Ignore relation table insertions while saving of the record
        if ($table_name == 'vtiger_inventoryproductrel') {
            return;
        }
        parent::insertIntoEntityTable($table_name, $module, $fileid);
    }

    /*Function to create records in current module.
    **This function called while importing records to this module*/
    public function createRecords($obj)
    {
        $createRecords = createRecords($obj);

        return $createRecords;
    }

    /*Function returns the record information which means whether the record is imported or not
    **This function called while importing records to this module*/
    public function importRecord($obj, $inventoryFieldData, $lineItemDetails)
    {
        $entityInfo = importRecord($obj, $inventoryFieldData, $lineItemDetails);

        return $entityInfo;
    }

    /*Function to return the status count of imported records in current module.
    **This function called while importing records to this module*/
    public function getImportStatusCount($obj)
    {
        $statusCount = getImportStatusCount($obj);

        return $statusCount;
    }

    public function undoLastImport($obj, $user)
    {
        $undoLastImport = undoLastImport($obj, $user);
    }

    /** Function to export the lead records in CSV Format.
     * @param reference variable - where condition is passed when the query is executed
     * Returns Export Quotes Query
     */
    public function create_export_query($where)
    {
        global $log;
        global $current_user;
        $log->debug('Entering create_export_query(' . $where . ') method ...');

        include 'include/utils/ExportUtils.php';

        // To get the Permitted fields query and the permitted fields list
        $sql = getPermittedFieldsQuery('Quotes', 'detail_view');
        $fields_list = getFieldsListFromQuery($sql);
        $fields_list .= getInventoryFieldsForExport($this->table_name);
        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');

        $query = "SELECT {$fields_list} FROM " . $this->entity_table . '
				INNER JOIN vtiger_quotes ON vtiger_quotes.quoteid = vtiger_crmentity.crmid
				LEFT JOIN vtiger_quotescf ON vtiger_quotescf.quoteid = vtiger_quotes.quoteid
				LEFT JOIN vtiger_quotesbillads ON vtiger_quotesbillads.quotebilladdressid = vtiger_quotes.quoteid
				LEFT JOIN vtiger_quotesshipads ON vtiger_quotesshipads.quoteshipaddressid = vtiger_quotes.quoteid
				LEFT JOIN vtiger_inventoryproductrel ON vtiger_inventoryproductrel.id = vtiger_quotes.quoteid
				LEFT JOIN vtiger_products ON vtiger_products.productid = vtiger_inventoryproductrel.productid
				LEFT JOIN vtiger_service ON vtiger_service.serviceid = vtiger_inventoryproductrel.productid
				LEFT JOIN vtiger_contactdetails ON vtiger_contactdetails.contactid = vtiger_quotes.contactid
				LEFT JOIN vtiger_potential ON vtiger_potential.potentialid = vtiger_quotes.potentialid
				LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_quotes.accountid
				LEFT JOIN vtiger_currency_info ON vtiger_currency_info.id = vtiger_quotes.currency_id
				LEFT JOIN vtiger_users AS vtiger_inventoryManager ON vtiger_inventoryManager.id = vtiger_quotes.inventorymanager
				LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid';

        $query .= $this->getNonAdminAccessControlQuery('Quotes', $current_user);
        $where_auto = ' vtiger_crmentity.deleted=0';

        if ($where != '') {
            $query .= " where ({$where}) AND " . $where_auto;
        } else {
            $query .= ' where ' . $where_auto;
        }

        $log->debug('Exiting create_export_query method ...');

        return $query;
    }

    /**
     * Function to get importable mandatory fields
     * By default some fields like Quantity, List Price is not mandaroty for Invertory modules but
     * import fails if those fields are not mapped during import.
     */
    public function getMandatoryImportableFields()
    {
        return getInventoryImportableMandatoryFeilds($this->moduleName);
    }
}
