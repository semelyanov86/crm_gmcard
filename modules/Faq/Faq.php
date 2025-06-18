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

// Faq is used to store vtiger_faq information.
class Faq extends CRMEntity
{
    public $log;

    public $db;

    public $table_name = 'vtiger_faq';

    public $table_index = 'id';

    // fix for Custom Field for FAQ
    public $tab_name = ['vtiger_crmentity', 'vtiger_faq', 'vtiger_faqcf'];

    public $tab_name_index = ['vtiger_crmentity' => 'crmid', 'vtiger_faq' => 'id', 'vtiger_faqcomments' => 'faqid', 'vtiger_faqcf' => 'faqid'];

    public $customFieldTable = ['vtiger_faqcf', 'faqid'];

    public $entity_table = 'vtiger_crmentity';

    public $column_fields = [];

    public $sortby_fields = ['question', 'category', 'id'];

    // This is the list of vtiger_fields that are in the lists.
    public $list_fields = [
        'FAQ Id' => ['faq' => 'id'],
        'Question' => ['faq' => 'question'],
        'Category' => ['faq' => 'category'],
        'Product Name' => ['faq' => 'product_id'],
        'Created Time' => ['crmentity' => 'createdtime'],
        'Modified Time' => ['crmentity' => 'modifiedtime'],
    ];

    public $list_fields_name = [
        'FAQ Id' => '',
        'Question' => 'question',
        'Category' => 'faqcategories',
        'Product Name' => 'product_id',
        'Created Time' => 'createdtime',
        'Modified Time' => 'modifiedtime',
    ];

    public $list_link_field = 'question';

    public $search_fields = [
        'Account Name' => ['account' => 'accountname'],
        'City' => ['accountbillads' => 'bill_city'],
    ];

    public $search_fields_name = [
        'Account Name' => 'accountname',
        'City' => 'bill_city',
    ];

    // Added these variables which are used as default order by and sortorder in ListView
    public $default_order_by = 'id';

    public $default_sort_order = 'DESC';

    public $mandatory_fields = ['question', 'faq_answer', 'createdtime', 'modifiedtime'];

    // For Alphabetical search
    public $def_basicsearch_col = 'question';

    /**	Constructor which will set the column_fields in this object.
     */
    public function __construct()
    {
        $this->log = Logger::getLogger('faq');
        $this->log->debug('Entering Faq() method ...');
        $this->db = PearDatabase::getInstance();
        $this->column_fields = getColumnFields('Faq');
        $this->log->debug('Exiting Faq method ...');
    }

    public function Faq()
    {
        self::__construct();
    }

    public function save_module($module)
    {
        // Inserting into Faq comment table
        $this->insertIntoFAQCommentTable('vtiger_faqcomments', $module);

    }

    /** Function to insert values in vtiger_faqcomments table for the specified module,.
     * @param $table_name -- table name:: Type varchar
     * @param $module -- module:: Type varchar
     */
    public function insertIntoFAQCommentTable($table_name, $module)
    {
        global $log;
        $log->info('in insertIntoFAQCommentTable  ' . $table_name . '    module is  ' . $module);
        global $adb;

        $current_time = $adb->formatDate(date('Y-m-d H:i:s'), true);

        if ($this->column_fields['comments'] != '') {
            $comment = $this->column_fields['comments'];
        } else {
            $comment = $_REQUEST['comments'] ?? '';
        }

        if ($comment != '') {
            $params = ['', $this->id, from_html($comment), $current_time];
            $sql = 'insert into vtiger_faqcomments values(?, ?, ?, ?)';
            $adb->pquery($sql, $params);
        }
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

        $query = "from {$moduletable}
			inner join vtiger_crmentity on vtiger_crmentity.crmid={$moduletable}.{$moduleindex}
			left join vtiger_products as vtiger_products{$module} on vtiger_products{$module}.productid = vtiger_faq.product_id
			left join vtiger_groups as vtiger_groups{$module} on vtiger_groups{$module}.groupid = vtiger_crmentity.smownerid
			left join vtiger_users as vtiger_users{$module} on vtiger_users{$module}.id = vtiger_crmentity.smownerid
			left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid
			left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid 
			left join vtiger_faqcf on vtiger_faqcf.faqid = vtiger_faq.id
            left join vtiger_users as vtiger_createdby" . $module . ' on vtiger_createdby' . $module . '.id = vtiger_crmentity.smcreatorid
            left join vtiger_users as vtiger_lastModifiedBy' . $module . ' on vtiger_lastModifiedBy' . $module . '.id = vtiger_crmentity.modifiedby';

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
            'Documents' => ['vtiger_senotesrel' => ['crmid', 'notesid'], 'vtiger_faq' => 'id'],
        ];

        return $rel_tables[$secmodule];
    }

    public function clearSingletonSaveFields()
    {
        $this->column_fields['comments'] = '';
    }
}
