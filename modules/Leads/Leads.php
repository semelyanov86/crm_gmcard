<?php

/*
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of txhe License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 */
class Leads extends CRMEntity
{
    public $log;

    public $db;

    public $table_name = 'vtiger_leaddetails';

    public $table_index = 'leadid';

    public $tab_name = ['vtiger_crmentity', 'vtiger_leaddetails', 'vtiger_leadsubdetails', 'vtiger_leadaddress', 'vtiger_leadscf'];

    public $tab_name_index = ['vtiger_crmentity' => 'crmid', 'vtiger_leaddetails' => 'leadid', 'vtiger_leadsubdetails' => 'leadsubscriptionid', 'vtiger_leadaddress' => 'leadaddressid', 'vtiger_leadscf' => 'leadid'];

    public $entity_table = 'vtiger_crmentity';

    /**
     * Mandatory table for supporting custom fields.
     */
    public $customFieldTable = ['vtiger_leadscf', 'leadid'];

    // construct this from database;
    public $column_fields = [];

    public $sortby_fields = ['lastname', 'firstname', 'email', 'phone', 'company', 'smownerid', 'website'];

    // This is used to retrieve related vtiger_fields from form posts.
    public $additional_column_fields = ['smcreatorid', 'smownerid', 'contactid', 'potentialid', 'crmid'];

    // This is the list of vtiger_fields that are in the lists.
    public $list_fields = [
        'First Name' => ['leaddetails' => 'firstname'],
        'Last Name' => ['leaddetails' => 'lastname'],
        'Company' => ['leaddetails' => 'company'],
        'Phone' => ['leadaddress' => 'phone'],
        'Website' => ['leadsubdetails' => 'website'],
        'Email' => ['leaddetails' => 'email'],
        'Assigned To' => ['crmentity' => 'smownerid'],
    ];

    public $list_fields_name = [
        'First Name' => 'firstname',
        'Last Name' => 'lastname',
        'Company' => 'company',
        'Phone' => 'phone',
        'Website' => 'website',
        'Email' => 'email',
        'Assigned To' => 'assigned_user_id',
    ];

    public $list_link_field = 'lastname';

    public $search_fields = [
        'Name' => ['leaddetails' => 'lastname'],
        'Company' => ['leaddetails' => 'company'],
    ];

    public $search_fields_name = [
        'Name' => 'lastname',
        'Company' => 'company',
    ];

    public $required_fields =  [];

    // Used when enabling/disabling the mandatory fields for the module.
    // Refers to vtiger_field.fieldname values.
    public $mandatory_fields = ['assigned_user_id', 'lastname', 'createdtime', 'modifiedtime'];

    // Default Fields for Email Templates -- Pavani
    public $emailTemplate_defaultFields = ['firstname', 'lastname', 'leadsource', 'leadstatus', 'rating', 'industry', 'secondaryemail', 'email', 'annualrevenue', 'designation', 'salutation'];

    // Added these variables which are used as default order by and sortorder in ListView
    public $default_order_by = 'lastname';

    public $default_sort_order = 'ASC';

    // For Alphabetical search
    public $def_basicsearch_col = 'lastname';

    public $LBL_LEAD_MAPPING = 'LBL_LEAD_MAPPING';
    // var $groupTable = Array('vtiger_leadgrouprelation','leadid');

    public function __construct()
    {
        $this->log = Logger::getLogger('lead');
        $this->log->debug('Entering Leads() method ...');
        $this->db = PearDatabase::getInstance();
        $this->column_fields = getColumnFields('Leads');
        $this->log->debug('Exiting Lead method ...');
    }

    public function Leads()
    {
        self::__construct();
    }

    /** Function to handle module specific operations when saving a entity.
     */
    public function save_module($module) {}

    // Mike Crowe Mod --------------------------------------------------------Default ordering for us

    /** Function to export the lead records in CSV Format.
     * @param reference variable - where condition is passed when the query is executed
     * Returns Export Leads Query
     */
    public function create_export_query($where)
    {
        global $log;
        global $current_user;
        $log->debug('Entering create_export_query(' . $where . ') method ...');

        include 'include/utils/ExportUtils.php';

        // To get the Permitted fields query and the permitted fields list
        $sql = getPermittedFieldsQuery('Leads', 'detail_view');
        $fields_list = getFieldsListFromQuery($sql);

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT {$fields_list},case when (vtiger_users.user_name not like '') then {$userNameSql} else vtiger_groups.groupname end as user_name
					FROM " . $this->entity_table . "
				INNER JOIN vtiger_leaddetails
					ON vtiger_crmentity.crmid=vtiger_leaddetails.leadid
				LEFT JOIN vtiger_leadsubdetails
					ON vtiger_leaddetails.leadid = vtiger_leadsubdetails.leadsubscriptionid
				LEFT JOIN vtiger_leadaddress
					ON vtiger_leaddetails.leadid=vtiger_leadaddress.leadaddressid
				LEFT JOIN vtiger_leadscf
					ON vtiger_leadscf.leadid=vtiger_leaddetails.leadid
							LEFT JOIN vtiger_groups
									ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				LEFT JOIN vtiger_users
					ON vtiger_crmentity.smownerid = vtiger_users.id and vtiger_users.status='Active'
				";

        $query .= $this->getNonAdminAccessControlQuery('Leads', $current_user);
        $where_auto = ' vtiger_crmentity.deleted=0 AND vtiger_leaddetails.converted =0';

        if ($where != '') {
            $query .= " where ({$where}) AND " . $where_auto;
        } else {
            $query .= ' where ' . $where_auto;
        }

        $log->debug('Exiting create_export_query method ...');

        return $query;
    }

    /** Returns a list of the associated tasks.
     * @param  int   $id      - leadid
     * returns related Task or Event record in array format
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
                if (getFieldVisibilityPermission('Events', $current_user->id, 'parent_id', 'readwrite') == '0') {
                    $button .= "<input title='" . getTranslatedString('LBL_NEW') . ' ' . getTranslatedString('LBL_TODO', $related_module) . "' class='crmbutton small create'"
                        . " onclick='this.form.action.value=\"EditView\";this.form.module.value=\"{$related_module}\";this.form.return_module.value=\"{$this_module}\";this.form.activity_mode.value=\"Events\";' type='submit' name='button'"
                        . " value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString('LBL_EVENT', $related_module) . "'>";
                }
            }
        }

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT vtiger_activity.*,vtiger_seactivityrel.crmid as parent_id, vtiger_contactdetails.lastname,
			vtiger_contactdetails.contactid, vtiger_crmentity.crmid, vtiger_crmentity.smownerid,
			vtiger_crmentity.modifiedtime,case when (vtiger_users.user_name not like '') then
		{$userNameSql} else vtiger_groups.groupname end as user_name,
		vtiger_recurringevents.recurringtype
		from vtiger_activity inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=
		vtiger_activity.activityid inner join vtiger_crmentity on vtiger_crmentity.crmid=
		vtiger_activity.activityid left join vtiger_cntactivityrel on
		vtiger_cntactivityrel.activityid = vtiger_activity.activityid left join
		vtiger_contactdetails on vtiger_contactdetails.contactid = vtiger_cntactivityrel.contactid
		left join vtiger_users on vtiger_users.id=vtiger_crmentity.smownerid
		left outer join vtiger_recurringevents on vtiger_recurringevents.activityid=
		vtiger_activity.activityid left join vtiger_groups on vtiger_groups.groupid=
		vtiger_crmentity.smownerid where vtiger_seactivityrel.crmid=" . $id . " and
			vtiger_crmentity.deleted = 0 and ((vtiger_activity.activitytype='Task' and
			vtiger_activity.status not in ('Completed','Deferred')) or
			(vtiger_activity.activitytype NOT in ('Emails','Task') and
			vtiger_activity.eventstatus not in ('','Held'))) ";

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_activities method ...');

        return $return_value;
    }

    public function get_quotes($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_quotes(' . $id . ') method ...');
        $this_module = $currentModule;
        $related_module = vtlib_getModuleNameById($rel_tab_id);

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
        if ($actions && getFieldVisibilityPermission($related_module, $current_user->id, 'account_id', 'readwrite') == '0') {
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

        $query = "SELECT vtiger_crmentity.*, vtiger_quotes.*, vtiger_leaddetails.leadid,
					case when (vtiger_users.user_name not like '') then {$userNameSql} else vtiger_groups.groupname end as user_name
					FROM vtiger_quotes
					INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_quotes.quoteid
					LEFT JOIN vtiger_leaddetails ON vtiger_leaddetails.leadid = vtiger_quotes.contactid
					LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
					LEFT JOIN vtiger_quotescf ON vtiger_quotescf.quoteid = vtiger_quotes.quoteid
					LEFT JOIN vtiger_quotesbillads ON vtiger_quotesbillads.quotebilladdressid = vtiger_quotes.quoteid
					LEFT JOIN vtiger_quotesshipads ON vtiger_quotesshipads.quoteshipaddressid = vtiger_quotes.quoteid
					LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
					WHERE vtiger_crmentity.deleted = 0 AND vtiger_leaddetails.leadid = {$id}";

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_quotes method ...');

        return $return_value;
    }

    /** Returns a list of the associated Campaigns.
     * @param $id -- campaign id :: Type Integer
     * @returns list of campaigns in array format
     */
    public function get_campaigns($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_campaigns(' . $id . ') method ...');
        $this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
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
            if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module={$related_module}&return_module={$currentModule}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid={$id}&parenttab={$parenttab}','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "'>&nbsp;";
            }
        }

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT case when (vtiger_users.user_name not like '') then {$userNameSql} else vtiger_groups.groupname end as user_name ,
				vtiger_campaign.campaignid, vtiger_campaign.campaignname, vtiger_campaign.campaigntype, vtiger_campaign.campaignstatus,
				vtiger_campaign.expectedrevenue, vtiger_campaign.closingdate, vtiger_crmentity.crmid, vtiger_crmentity.smownerid,
				vtiger_crmentity.modifiedtime from vtiger_campaign
				inner join vtiger_campaignleadrel on vtiger_campaignleadrel.campaignid=vtiger_campaign.campaignid
				inner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_campaign.campaignid
				inner join vtiger_campaignscf ON vtiger_campaignscf.campaignid = vtiger_campaign.campaignid
				left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
				left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid
				where vtiger_campaignleadrel.leadid=" . $id . ' and vtiger_crmentity.deleted=0';

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_campaigns method ...');

        return $return_value;
    }

    /** Returns a list of the associated emails.
     * @param  int   $id      - leadid
     * returns related emails record in array format
     */
    public function get_emails($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_emails(' . $id . ') method ...');
        $this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
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
            if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module={$related_module}&return_module={$currentModule}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid={$id}&parenttab={$parenttab}','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "'>&nbsp;";
            }
            if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "' accessyKey='F' class='crmbutton small create' onclick='fnvshobj(this,\"sendmail_cont\");sendmail(\"{$this_module}\",{$id});' type='button' name='button' value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "'></td>";
            }
        }

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "select case when (vtiger_users.user_name not like '') then {$userNameSql} else vtiger_groups.groupname end as user_name,"
                . ' vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.semodule, vtiger_activity.activitytype,'
                . ' vtiger_activity.date_start, vtiger_activity.time_start, vtiger_activity.status, vtiger_activity.priority, vtiger_crmentity.crmid,'
                . ' vtiger_crmentity.smownerid,vtiger_crmentity.modifiedtime, vtiger_users.user_name, vtiger_seactivityrel.crmid as parent_id '
                . ' from vtiger_activity'
                . ' inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid'
                . ' inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid'
                . ' left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid'
                . ' left join vtiger_users on  vtiger_users.id=vtiger_crmentity.smownerid'
                . " where vtiger_activity.activitytype='Emails' and vtiger_crmentity.deleted=0 and vtiger_seactivityrel.crmid=" . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_emails method ...');

        return $return_value;
    }

    /**
     * Function to get Lead related Task & Event which have activity type Held, Completed or Deferred.
     * @param  int   $id      - leadid
     * returns related Task or Event record in array format
     */
    public function get_history($id)
    {
        global $log;
        $log->debug('Entering get_history(' . $id . ') method ...');
        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.status,
			vtiger_activity.eventstatus, vtiger_activity.activitytype,vtiger_activity.date_start,
			vtiger_activity.due_date,vtiger_activity.time_start,vtiger_activity.time_end,
			vtiger_crmentity.modifiedtime,vtiger_crmentity.createdtime,
			vtiger_crmentity.description, {$userNameSql} as user_name,vtiger_groups.groupname
				from vtiger_activity
				inner join vtiger_seactivityrel on vtiger_seactivityrel.activityid=vtiger_activity.activityid
				inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid
				left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid
				left join vtiger_users on vtiger_crmentity.smownerid= vtiger_users.id
				where (vtiger_activity.activitytype != 'Emails')
				and (vtiger_activity.status = 'Completed' or vtiger_activity.status = 'Deferred' or (vtiger_activity.eventstatus = 'Held' and vtiger_activity.eventstatus != ''))
				and vtiger_seactivityrel.crmid=" . $id . '
							and vtiger_crmentity.deleted = 0';
        // Don't add order by, because, for security, one more condition will be added with this query in include/RelatedListView.php

        $log->debug('Exiting get_history method ...');

        return getHistory('Leads', $query, $id);
    }

    /**
     * Function to get lead related Products.
     * @param  int   $id      - leadid
     * returns related Products record in array format
     */
    public function get_products($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_products(' . $id . ') method ...');
        $this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
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

        $query = "SELECT vtiger_products.productid, vtiger_products.productname, vtiger_products.productcode,
				vtiger_products.commissionrate, vtiger_products.qty_per_unit, vtiger_products.unit_price,
				vtiger_crmentity.crmid, vtiger_crmentity.smownerid
				FROM vtiger_products
				INNER JOIN vtiger_seproductsrel ON vtiger_products.productid = vtiger_seproductsrel.productid  and vtiger_seproductsrel.setype = 'Leads'
				INNER JOIN vtiger_productcf
					ON vtiger_products.productid = vtiger_productcf.productid
				INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_products.productid
				INNER JOIN vtiger_leaddetails ON vtiger_leaddetails.leadid = vtiger_seproductsrel.crmid
				LEFT JOIN vtiger_users
					ON vtiger_users.id=vtiger_crmentity.smownerid
				LEFT JOIN vtiger_groups
					ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			   WHERE vtiger_crmentity.deleted = 0 AND vtiger_leaddetails.leadid = {$id}";

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_products method ...');

        return $return_value;
    }

    /** Function to get the Columnnames of the Leads Record
     * Used By vtigerCRM Word Plugin
     * Returns the Merge Fields for Word Plugin.
     */
    public function getColumnNames_Lead()
    {
        global $log,$current_user;
        $log->debug('Entering getColumnNames_Lead() method ...');
        require 'user_privileges/user_privileges_' . $current_user->id . '.php';
        if ($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0) {
            $sql1 = 'select fieldlabel from vtiger_field where tabid=7 and vtiger_field.presence in (0,2)';
            $params1 = [];
        } else {
            $profileList = getCurrentUserProfileList();
            $sql1 = 'select vtiger_field.fieldid,fieldlabel from vtiger_field inner join vtiger_profile2field on vtiger_profile2field.fieldid=vtiger_field.fieldid inner join vtiger_def_org_field on vtiger_def_org_field.fieldid=vtiger_field.fieldid where vtiger_field.tabid=7 and vtiger_field.displaytype in (1,2,3,4) and vtiger_profile2field.visible=0 and vtiger_def_org_field.visible=0 and vtiger_field.presence in (0,2)';
            $params1 = [];
            if (php7_count($profileList) > 0) {
                $sql1 .= ' and vtiger_profile2field.profileid in (' . generateQuestionMarks($profileList) . ')  group by fieldid';
                array_push($params1, $profileList);
            }
        }
        $result = $this->db->pquery($sql1, $params1);
        $numRows = $this->db->num_rows($result);
        for ($i = 0; $i < $numRows; ++$i) {
            $custom_fields[$i] = $this->db->query_result($result, $i, 'fieldlabel');
            $custom_fields[$i] = preg_replace('/\\s+/', '', $custom_fields[$i]);
            $custom_fields[$i] = strtoupper($custom_fields[$i]);
        }
        $mergeflds = $custom_fields;
        $log->debug('Exiting getColumnNames_Lead method ...');

        return $mergeflds;
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

        $rel_table_arr = ['Activities' => 'vtiger_seactivityrel', 'Documents' => 'vtiger_senotesrel', 'Attachments' => 'vtiger_seattachmentsrel',
            'Products' => 'vtiger_seproductsrel', 'Campaigns' => 'vtiger_campaignleadrel', 'Emails' => 'vtiger_seactivityrel'];

        $tbl_field_arr = ['vtiger_seactivityrel' => 'activityid', 'vtiger_senotesrel' => 'notesid', 'vtiger_seattachmentsrel' => 'attachmentsid',
            'vtiger_seproductsrel' => 'productid', 'vtiger_campaignleadrel' => 'campaignid', 'vtiger_seactivityrel' => 'activityid'];

        $entity_tbl_field_arr = ['vtiger_seactivityrel' => 'crmid', 'vtiger_senotesrel' => 'crmid', 'vtiger_seattachmentsrel' => 'crmid',
            'vtiger_seproductsrel' => 'crmid', 'vtiger_campaignleadrel' => 'leadid', 'vtiger_seactivityrel' => 'crmid'];

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

    /*
     * Function to get the secondary query part of a report
     * @param - $module primary module name
     * @param - $secmodule secondary module name
     * returns the query string formed on fetching the related data for report for secondary module
     */
    public function generateReportsSecQuery($module, $secmodule, $queryPlanner)
    {
        $matrix = $queryPlanner->newDependencyMatrix();
        $matrix->setDependency('vtiger_crmentityLeads', ['vtiger_groupsLeads', 'vtiger_usersLeads', 'vtiger_lastModifiedByLeads']);

        // TODO Support query planner
        if (!$queryPlanner->requireTable('vtiger_leaddetails', $matrix)) {
            return '';
        }

        $matrix->setDependency('vtiger_leaddetails', ['vtiger_crmentityLeads', 'vtiger_leadaddress', 'vtiger_leadsubdetails', 'vtiger_leadscf', 'vtiger_email_trackLeads']);

        $query = $this->getRelationQuery($module, $secmodule, 'vtiger_leaddetails', 'leadid', $queryPlanner);
        if ($queryPlanner->requireTable('vtiger_crmentityLeads', $matrix)) {
            $query .= ' left join vtiger_crmentity as vtiger_crmentityLeads on vtiger_crmentityLeads.crmid = vtiger_leaddetails.leadid and vtiger_crmentityLeads.deleted=0';
        }
        if ($queryPlanner->requireTable('vtiger_leadaddress')) {
            $query .= ' left join vtiger_leadaddress on vtiger_leaddetails.leadid = vtiger_leadaddress.leadaddressid';
        }
        if ($queryPlanner->requireTable('vtiger_leadsubdetails')) {
            $query .= ' left join vtiger_leadsubdetails on vtiger_leadsubdetails.leadsubscriptionid = vtiger_leaddetails.leadid';
        }
        if ($queryPlanner->requireTable('vtiger_leadscf')) {
            $query .= ' left join vtiger_leadscf on vtiger_leadscf.leadid = vtiger_leaddetails.leadid';
        }
        if ($queryPlanner->requireTable('vtiger_email_trackLeads')) {
            $query .= ' LEFT JOIN vtiger_email_track AS vtiger_email_trackLeads ON vtiger_email_trackLeads.crmid = vtiger_leaddetails.leadid';
        }
        if ($queryPlanner->requireTable('vtiger_groupsLeads')) {
            $query .= ' left join vtiger_groups as vtiger_groupsLeads on vtiger_groupsLeads.groupid = vtiger_crmentityLeads.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_usersLeads')) {
            $query .= ' left join vtiger_users as vtiger_usersLeads on vtiger_usersLeads.id = vtiger_crmentityLeads.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_lastModifiedByLeads')) {
            $query .= ' left join vtiger_users as vtiger_lastModifiedByLeads on vtiger_lastModifiedByLeads.id = vtiger_crmentityLeads.modifiedby ';
        }
        if ($queryPlanner->requireTable('vtiger_createdbyLeads')) {
            $query .= ' left join vtiger_users as vtiger_createdbyLeads on vtiger_createdbyLeads.id = vtiger_crmentityLeads.smcreatorid ';
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
            'Calendar' => ['vtiger_seactivityrel' => ['crmid', 'activityid'], 'vtiger_leaddetails' => 'leadid'],
            'Products' => ['vtiger_seproductsrel' => ['crmid', 'productid'], 'vtiger_leaddetails' => 'leadid'],
            'Campaigns' => ['vtiger_campaignleadrel' => ['leadid', 'campaignid'], 'vtiger_leaddetails' => 'leadid'],
            'Documents' => ['vtiger_senotesrel' => ['crmid', 'notesid'], 'vtiger_leaddetails' => 'leadid'],
            'Services' => ['vtiger_crmentityrel' => ['crmid', 'relcrmid'], 'vtiger_leaddetails' => 'leadid'],
            'Emails' => ['vtiger_seactivityrel' => ['crmid', 'activityid'], 'vtiger_leaddetails' => 'leadid'],
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

        if ($return_module == 'Campaigns') {
            $sql = 'DELETE FROM vtiger_campaignleadrel WHERE leadid=? AND campaignid=?';
            $this->db->pquery($sql, [$id, $return_id]);
        } elseif ($return_module == 'Products') {
            $sql = 'DELETE FROM vtiger_seproductsrel WHERE crmid=? AND productid=?';
            $this->db->pquery($sql, [$id, $return_id]);
        } elseif ($return_module == 'Documents') {
            $sql = 'DELETE FROM vtiger_senotesrel WHERE crmid=? AND notesid=?';
            $this->db->pquery($sql, [$id, $return_id]);
        } else {
            parent::unlinkRelationship($id, $return_module, $return_id);
        }
    }

    public function getListButtons($app_strings)
    {
        $list_buttons = [];

        if (isPermitted('Leads', 'Delete', '') == 'yes') {
            $list_buttons['del'] =	$app_strings['LBL_MASS_DELETE'];
        }
        if (isPermitted('Leads', 'EditView', '') == 'yes') {
            $list_buttons['mass_edit'] = $app_strings['LBL_MASS_EDIT'];
            $list_buttons['c_owner'] = $app_strings['LBL_CHANGE_OWNER'];
        }
        if (isPermitted('Emails', 'EditView', '') == 'yes') {
            $list_buttons['s_mail'] = $app_strings['LBL_SEND_MAIL_BUTTON'];
        }

        // end of mailer export
        return $list_buttons;
    }

    public function save_related_module($module, $crmid, $with_module, $with_crmids, $otherParams = [])
    {
        $adb = PearDatabase::getInstance();

        if (!is_array($with_crmids)) {
            $with_crmids = [$with_crmids];
        }
        foreach ($with_crmids as $with_crmid) {
            if ($with_module == 'Products') {
                $adb->pquery('INSERT INTO vtiger_seproductsrel VALUES(?,?,?,?)', [$crmid, $with_crmid, $module, 1]);
            } elseif ($with_module == 'Campaigns') {
                $adb->pquery('insert into  vtiger_campaignleadrel values(?,?,1)', [$with_crmid, $crmid]);
            } else {
                parent::save_related_module($module, $crmid, $with_module, $with_crmid);
            }
        }
    }

    public function getQueryForDuplicates($module, $tableColumns, $selectedColumns = '', $ignoreEmpty = false, $requiredTables = [], $columnTypes = null)
    {
        if (is_array($tableColumns)) {
            $tableColumnsString = implode(',', $tableColumns);
        }
        $selectClause = 'SELECT ' . $this->table_name . '.' . $this->table_index . ' AS recordid,' . $tableColumnsString;

        // Select Custom Field Table Columns if present
        if (isset($this->customFieldTable)) {
            $query .= ', ' . $this->customFieldTable[0] . '.* ';
        }

        $fromClause = " FROM {$this->table_name}";

        $fromClause .= " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = {$this->table_name}.{$this->table_index}";

        if ($this->tab_name) {
            foreach ($this->tab_name as $tableName) {
                if ($tableName != 'vtiger_crmentity' && $tableName != $this->table_name && in_array($tableName, $requiredTables)) {
                    if ($this->tab_name_index[$tableName]) {
                        $fromClause .= ' INNER JOIN ' . $tableName . ' ON ' . $tableName . '.' . $this->tab_name_index[$tableName]
                            . " = {$this->table_name}.{$this->table_index}";
                    }
                }
            }
        }

        $whereClause = ' WHERE vtiger_crmentity.deleted = 0 AND vtiger_leaddetails.converted=0 ';
        $whereClause .= $this->getListViewSecurityParameter($module);

        if ($ignoreEmpty) {
            foreach ($tableColumns as $tableColumn) {
                if ($columnTypes && ($columnTypes[$tableColumn] == 'date' || $columnTypes[$tableColumn] == 'datetime')) {
                    $whereClause .= " AND ({$tableColumn} IS NOT NULL) ";
                } else {
                    $whereClause .= " AND ({$tableColumn} IS NOT NULL AND {$tableColumn} != '') ";
                }
            }
        }

        if (isset($selectedColumns) && trim($selectedColumns) != '') {
            $sub_query = "SELECT {$selectedColumns} FROM {$this->table_name} AS t "
                    . ' INNER JOIN vtiger_crmentity AS crm ON crm.crmid = t.' . $this->table_index;
            // Consider custom table join as well.
            if (isset($this->customFieldTable)) {
                $sub_query .= ' LEFT JOIN ' . $this->customFieldTable[0] . ' tcf ON tcf.' . $this->customFieldTable[1] . " = t.{$this->table_index}";
            }
            $sub_query .= " WHERE crm.deleted=0 GROUP BY {$selectedColumns} HAVING COUNT(*)>1";
        } else {
            $sub_query = "SELECT {$tableColumnsString} {$fromClause} {$whereClause} GROUP BY {$tableColumnsString} HAVING COUNT(*)>1";
        }

        $i = 1;
        foreach ($tableColumns as $tableColumn) {
            $tableInfo = explode('.', $tableColumn);
            $duplicateCheckClause .= " ifnull({$tableColumn},'null') = ifnull(temp.{$tableInfo[1]},'null')";
            if (php7_count($tableColumns) != $i++) {
                $duplicateCheckClause .= ' AND ';
            }
        }

        $query = $selectClause . $fromClause
                . ' LEFT JOIN vtiger_users_last_import ON vtiger_users_last_import.bean_id=' . $this->table_name . '.' . $this->table_index
                . ' INNER JOIN (' . $sub_query . ') AS temp ON ' . $duplicateCheckClause
                . $whereClause
                . " ORDER BY {$tableColumnsString}," . $this->table_name . '.' . $this->table_index . ' ASC';

        return $query;
    }

    /**
     * Invoked when special actions are to be performed on the module.
     * @param string Module name
     * @param string Event Type
     */
    public function vtlib_handler($moduleName, $eventType)
    {
        if ($moduleName == 'Leads') {
            $db = PearDatabase::getInstance();
            if ($eventType == 'module.disabled') {
                $db->pquery('UPDATE vtiger_settings_field SET active=1 WHERE name=?', [$this->LBL_LEAD_MAPPING]);
            } elseif ($eventType == 'module.enabled') {
                $db->pquery('UPDATE vtiger_settings_field SET active=0 WHERE name=?', [$this->LBL_LEAD_MAPPING]);
            }
        }
    }
}
