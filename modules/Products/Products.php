<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Products extends CRMEntity
{
    public $db;

    public $log; // Used in class functions of CRMEntity

    public $table_name = 'vtiger_products';

    public $table_index = 'productid';

    public $column_fields = [];

    public $isWorkFlowFieldUpdate;

    /**
     * Mandatory table for supporting custom fields.
     */
    public $customFieldTable = ['vtiger_productcf', 'productid'];

    public $tab_name = ['vtiger_crmentity', 'vtiger_products', 'vtiger_productcf'];

    public $tab_name_index = ['vtiger_crmentity' => 'crmid', 'vtiger_products' => 'productid', 'vtiger_productcf' => 'productid', 'vtiger_seproductsrel' => 'productid', 'vtiger_producttaxrel' => 'productid'];

    // This is the list of vtiger_fields that are in the lists.
    public $list_fields = [
        'Product Name' => ['products' => 'productname'],
        'Part Number' => ['products' => 'productcode'],
        'Commission Rate' => ['products' => 'commissionrate'],
        'Qty/Unit' => ['products' => 'qty_per_unit'],
        'Unit Price' => ['products' => 'unit_price'],
    ];

    public $list_fields_name = [
        'Product Name' => 'productname',
        'Part Number' => 'productcode',
        'Commission Rate' => 'commissionrate',
        'Qty/Unit' => 'qty_per_unit',
        'Unit Price' => 'unit_price',
    ];

    public $list_link_field = 'productname';

    public $search_fields = [
        'Product Name' => ['products' => 'productname'],
        'Part Number' => ['products' => 'productcode'],
        'Unit Price' => ['products' => 'unit_price'],
    ];

    public $search_fields_name = [
        'Product Name' => 'productname',
        'Part Number' => 'productcode',
        'Unit Price' => 'unit_price',
    ];

    public $required_fields = ['productname' => 1];

    // Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
    public $sortby_fields = [];

    public $def_basicsearch_col = 'productname';

    // Added these variables which are used as default order by and sortorder in ListView
    public $default_order_by = 'productname';

    public $default_sort_order = 'ASC';

    // Used when enabling/disabling the mandatory fields for the module.
    // Refers to vtiger_field.fieldname values.
    public $mandatory_fields = ['createdtime', 'modifiedtime', 'productname', 'assigned_user_id'];

    // Josh added for importing and exporting -added in patch2
    public $unit_price;

    /**	Constructor which will set the column_fields in this object.
     */
    public function __construct()
    {
        $this->log = Logger::getLogger('product');
        $this->log->debug('Entering Products() method ...');
        $this->db = PearDatabase::getInstance();
        $this->column_fields = getColumnFields('Products');
        $this->log->debug('Exiting Product method ...');
    }

    public function Products()
    {
        self::__construct();
    }

    public function save_module($module)
    {
        // Inserting into product_taxrel table
        if (((isset($_REQUEST['ajxaction']) && $_REQUEST['ajxaction'] != 'DETAILVIEW') || !isset($_REQUEST['ajxaction']))
           && ((isset($_REQUEST['action']) && $_REQUEST['action'] != 'ProcessDuplicates') || !isset($_REQUEST['action'])) && !$this->isWorkFlowFieldUpdate) {
            if ((isset($_REQUEST['ajxaction']) && $_REQUEST['ajxaction'] != 'CurrencyUpdate') || !isset($_REQUEST['ajxaction'])) {
                $this->insertTaxInformation('vtiger_producttaxrel', 'Products');
            }

            if ((isset($_REQUEST['action']) && $_REQUEST['action'] != 'MassEditSave') || !isset($_REQUEST['action'])) {
                $this->insertPriceInformation('vtiger_productcurrencyrel', 'Products');
            }
        }

        if ((isset($_REQUEST['action']) && $_REQUEST['action'] == 'SaveAjax') && isset($_REQUEST['base_currency'], $_REQUEST['unit_price'])) {
            $this->insertPriceInformation('vtiger_productcurrencyrel', 'Products');
        }
        // Update unit price value in vtiger_productcurrencyrel
        $this->updateUnitPrice();
        // Inserting into attachments, handle image save in crmentity uitype 69
        // $this->insertIntoAttachment($this->id,'Products');

    }

    /**	function to save the product tax information in vtiger_producttaxrel table.
     *	@param string $tablename - vtiger_tablename to save the product tax relationship (producttaxrel)
     *	@param string $module	 - current module name
     *	$return void
     */
    public function insertTaxInformation($tablename, $module)
    {
        global $adb, $log;
        $log->debug("Entering into insertTaxInformation({$tablename}, {$module}) method ...");
        $tax_details = getAllTaxes();

        $tax_per = '';
        // Save the Product - tax relationship if corresponding tax check box is enabled
        // Delete the existing tax if any
        if ($this->mode == 'edit' && (isset($_REQUEST['action']) && $_REQUEST['action'] != 'MassEditSave')) {
            for ($i = 0; $i < php7_count($tax_details); ++$i) {
                $taxid = getTaxId($tax_details[$i]['taxname']);
                $sql = 'DELETE FROM vtiger_producttaxrel WHERE productid=? AND taxid=?';
                $adb->pquery($sql, [$this->id, $taxid]);
            }
        }
        for ($i = 0; $i < php7_count($tax_details); ++$i) {
            $tax_name = $tax_details[$i]['taxname'];
            $tax_checkname = $tax_details[$i]['taxname'] . '_check';
            if (isset($_REQUEST[$tax_checkname]) && ($_REQUEST[$tax_checkname] == 'on' || $_REQUEST[$tax_checkname] == 1)) {
                $taxid = getTaxId($tax_name);
                $tax_per = $_REQUEST[$tax_name];

                $taxRegions = $_REQUEST[$tax_name . '_regions'] ?? '';
                if ($taxRegions || (isset($_REQUEST[$tax_name . '_defaultPercentage']) && $_REQUEST[$tax_name . '_defaultPercentage'] != '')) {
                    $tax_per = $_REQUEST[$tax_name . '_defaultPercentage'];
                } else {
                    $taxRegions = [];
                }

                if ($tax_per == '') {
                    $log->debug('Tax selected but value not given so default value will be saved.');
                    $tax_per = getTaxPercentage($tax_name);
                }

                $log->debug("Going to save the Product - {$tax_name} tax relationship");

                if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'MassEditSave') {
                    $adb->pquery('DELETE FROM vtiger_producttaxrel WHERE productid=? AND taxid=?', [$this->id, $taxid]);
                }

                $query = 'INSERT INTO vtiger_producttaxrel VALUES(?,?,?,?)';
                $adb->pquery($query, [$this->id, $taxid, $tax_per, Zend_Json::encode($taxRegions)]);
            }
        }

        $log->debug("Exiting from insertTaxInformation({$tablename}, {$module}) method ...");
    }

    /**	function to save the product price information in vtiger_productcurrencyrel table.
     *	@param string $tablename - vtiger_tablename to save the product currency relationship (productcurrencyrel)
     *	@param string $module	 - current module name
     *	$return void
     */
    public function insertPriceInformation($tablename, $module)
    {
        global $adb, $log, $current_user;
        $log->debug("Entering into insertPriceInformation({$tablename}, {$module}) method ...");
        // removed the update of currency_id based on the logged in user's preference : fix 6490

        $currency_details = getAllCurrencies('all');

        // Delete the existing currency relationship if any
        if ($this->mode == 'edit' && (isset($_REQUEST['action']) && $_REQUEST['action'] !== 'CurrencyUpdate')) {
            for ($i = 0; $i < php7_count($currency_details); ++$i) {
                $curid = $currency_details[$i]['curid'];
                $sql = 'delete from vtiger_productcurrencyrel where productid=? and currencyid=?';
                $adb->pquery($sql, [$this->id, $curid]);
            }
        }

        $product_base_conv_rate = getBaseConversionRateForProduct($this->id, $this->mode);
        $currencySet = 0;
        // Save the Product - Currency relationship if corresponding currency check box is enabled
        for ($i = 0; $i < php7_count($currency_details); ++$i) {
            $curid = $currency_details[$i]['curid'];
            $curname = $currency_details[$i]['currencylabel'];
            $cur_checkname = 'cur_' . $curid . '_check';
            $cur_valuename = 'curname' . $curid;

            $requestPrice = CurrencyField::convertToDBFormat($_REQUEST['unit_price'], null, true);
            $actualPrice = CurrencyField::convertToDBFormat($_REQUEST[$cur_valuename], null, true);
            $isQuickCreate = false;
            if ((isset($_REQUEST['action']) && $_REQUEST['action'] == 'SaveAjax') && isset($_REQUEST['base_currency']) && $_REQUEST['base_currency'] == $cur_valuename) {
                $actualPrice = $requestPrice;
                $isQuickCreate = true;
            }
            $_REQUEST[$cur_checkname] ??= '';
            if ($_REQUEST[$cur_checkname] == 'on' || $_REQUEST[$cur_checkname] == 1 || $isQuickCreate) {
                $conversion_rate = $currency_details[$i]['conversionrate'];
                $actual_conversion_rate = $product_base_conv_rate * $conversion_rate;
                $converted_price = $actual_conversion_rate * $requestPrice;

                $log->debug("Going to save the Product - {$curname} currency relationship");

                if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'CurrencyUpdate') {
                    $adb->pquery('DELETE FROM vtiger_productcurrencyrel WHERE productid=? AND currencyid=?', [$this->id, $curid]);
                }

                $query = 'insert into vtiger_productcurrencyrel values(?,?,?,?)';
                $adb->pquery($query, [$this->id, $curid, $converted_price, $actualPrice]);

                // Update the Product information with Base Currency choosen by the User.
                if ($_REQUEST['base_currency'] == $cur_valuename) {
                    $currencySet = 1;
                    $adb->pquery('update vtiger_products set currency_id=?, unit_price=? where productid=?', [$curid, $actualPrice, $this->id]);
                }
            }
            if (!$currencySet) {
                $curid = fetchCurrency($current_user->id);
                $adb->pquery('update vtiger_products set currency_id=? where productid=?', [$curid, $this->id]);
            }
        }

        $log->debug("Exiting from insertPriceInformation({$tablename}, {$module}) method ...");
    }

    public function updateUnitPrice()
    {
        $prod_res = $this->db->pquery('select unit_price, currency_id from vtiger_products where productid=?', [$this->id]);
        $prod_unit_price = $this->db->query_result($prod_res, 0, 'unit_price');
        $prod_base_currency = $this->db->query_result($prod_res, 0, 'currency_id');

        $query = 'update vtiger_productcurrencyrel set actual_price=? where productid=? and currencyid=?';
        $params = [$prod_unit_price, $this->id, $prod_base_currency];
        $this->db->pquery($query, $params);
    }

    public function insertIntoAttachment($id, $module)
    {
        global  $log,$adb;
        $log->debug("Entering into insertIntoAttachment({$id},{$module}) method.");

        $file_saved = false;
        foreach ($_FILES as $fileindex => $files) {
            if ($files['name'] != '' && $files['size'] > 0) {
                if ($_REQUEST[$fileindex . '_hidden'] != '') {
                    $files['original_name'] = vtlib_purify($_REQUEST[$fileindex . '_hidden']);
                } else {
                    $files['original_name'] = stripslashes($files['name']);
                }
                $files['original_name'] = str_replace('"', '', $files['original_name']);
                $file_saved = $this->uploadAndSaveFile($id, $module, $files);
            }
        }

        // Updating image information in main table of products
        $existingImageSql = 'SELECT name FROM vtiger_seattachmentsrel INNER JOIN vtiger_attachments ON
								vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid LEFT JOIN vtiger_products ON
								vtiger_products.productid = vtiger_seattachmentsrel.crmid WHERE vtiger_seattachmentsrel.crmid = ?';
        $existingImages = $adb->pquery($existingImageSql, [$id]);
        $numOfRows = $adb->num_rows($existingImages);
        $productImageMap = [];

        for ($i = 0; $i < $numOfRows; ++$i) {
            $imageName = $adb->query_result($existingImages, $i, 'name');
            array_push($productImageMap, decode_html($imageName));
        }
        $commaSeperatedFileNames = implode(',', $productImageMap);

        $adb->pquery('UPDATE vtiger_products SET imagename = ? WHERE productid = ?', [$commaSeperatedFileNames, $id]);

        // Remove the deleted vtiger_attachments from db - Products
        if ($module == 'Products' && $_REQUEST['del_file_list'] != '') {
            $del_file_list = explode('###', trim($_REQUEST['del_file_list'], '###'));
            foreach ($del_file_list as $del_file_name) {
                $attach_res = $adb->pquery('select vtiger_attachments.attachmentsid from vtiger_attachments inner join vtiger_seattachmentsrel on vtiger_attachments.attachmentsid=vtiger_seattachmentsrel.attachmentsid where crmid=? and name=?', [$id, $del_file_name]);
                $attachments_id = $adb->query_result($attach_res, 0, 'attachmentsid');

                $del_res1 = $adb->pquery('delete from vtiger_attachments where attachmentsid=?', [$attachments_id]);
                $del_res2 = $adb->pquery('delete from vtiger_seattachmentsrel where attachmentsid=?', [$attachments_id]);
            }
        }

        $log->debug("Exiting from insertIntoAttachment({$id},{$module}) method.");
    }

    /**	function used to get the list of leads which are related to the product.
     *	@param int $id - product id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_leads($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_leads(' . $id . ') method ...');
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

        $query = 'SELECT vtiger_leaddetails.leadid, vtiger_crmentity.crmid, vtiger_leaddetails.firstname, vtiger_leaddetails.lastname, vtiger_leaddetails.company, vtiger_leadaddress.phone, vtiger_leadsubdetails.website, vtiger_leaddetails.email, case when (vtiger_users.user_name not like "") then vtiger_users.user_name else vtiger_groups.groupname end as user_name, vtiger_crmentity.smownerid, vtiger_products.productname, vtiger_products.qty_per_unit, vtiger_products.unit_price, vtiger_products.expiry_date
			FROM vtiger_leaddetails
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_leaddetails.leadid
			INNER JOIN vtiger_leadaddress ON vtiger_leadaddress.leadaddressid = vtiger_leaddetails.leadid
			INNER JOIN vtiger_leadsubdetails ON vtiger_leadsubdetails.leadsubscriptionid = vtiger_leaddetails.leadid
			INNER JOIN vtiger_seproductsrel ON vtiger_seproductsrel.crmid=vtiger_leaddetails.leadid
			INNER JOIN vtiger_products ON vtiger_seproductsrel.productid = vtiger_products.productid
			INNER JOIN vtiger_leadscf ON vtiger_leaddetails.leadid = vtiger_leadscf.leadid
			LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			WHERE vtiger_crmentity.deleted = 0 AND vtiger_leaddetails.converted=0 AND vtiger_products.productid = ' . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_leads method ...');

        return $return_value;
    }

    /**	function used to get the list of accounts which are related to the product.
     *	@param int $id - product id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_accounts($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_accounts(' . $id . ') method ...');
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

        $query = 'SELECT vtiger_account.accountid, vtiger_crmentity.crmid, vtiger_account.accountname, vtiger_accountbillads.bill_city, vtiger_account.website, vtiger_account.phone, case when (vtiger_users.user_name not like "") then vtiger_users.user_name else vtiger_groups.groupname end as user_name, vtiger_crmentity.smownerid, vtiger_products.productname, vtiger_products.qty_per_unit, vtiger_products.unit_price, vtiger_products.expiry_date
			FROM vtiger_account
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_account.accountid
			INNER JOIN vtiger_accountbillads ON vtiger_accountbillads.accountaddressid = vtiger_account.accountid
			LEFT JOIN vtiger_accountshipads ON vtiger_accountshipads.accountaddressid = vtiger_account.accountid
			INNER JOIN vtiger_seproductsrel ON vtiger_seproductsrel.crmid=vtiger_account.accountid
			INNER JOIN vtiger_products ON vtiger_seproductsrel.productid = vtiger_products.productid
			INNER JOIN vtiger_accountscf ON vtiger_account.accountid = vtiger_accountscf.accountid
			LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			WHERE vtiger_crmentity.deleted = 0 AND vtiger_products.productid = ' . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_accounts method ...');

        return $return_value;
    }

    /**	function used to get the list of contacts which are related to the product.
     *	@param int $id - product id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_contacts($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_contacts(' . $id . ') method ...');
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

        $query = 'SELECT vtiger_contactdetails.firstname, vtiger_contactdetails.lastname, vtiger_contactdetails.title, vtiger_contactdetails.accountid, vtiger_contactdetails.email, vtiger_contactdetails.phone, vtiger_crmentity.crmid, case when (vtiger_users.user_name not like "") then vtiger_users.user_name else vtiger_groups.groupname end as user_name, vtiger_crmentity.smownerid, vtiger_products.productname, vtiger_products.qty_per_unit, vtiger_products.unit_price, vtiger_products.expiry_date,vtiger_account.accountname
			FROM vtiger_contactdetails
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_contactdetails.contactid
			INNER JOIN vtiger_seproductsrel ON vtiger_seproductsrel.crmid=vtiger_contactdetails.contactid
			INNER JOIN vtiger_contactaddress ON vtiger_contactdetails.contactid = vtiger_contactaddress.contactaddressid
			INNER JOIN vtiger_contactsubdetails ON vtiger_contactdetails.contactid = vtiger_contactsubdetails.contactsubscriptionid
			INNER JOIN vtiger_customerdetails ON vtiger_contactdetails.contactid = vtiger_customerdetails.customerid
			INNER JOIN vtiger_contactscf ON vtiger_contactdetails.contactid = vtiger_contactscf.contactid
			INNER JOIN vtiger_products ON vtiger_seproductsrel.productid = vtiger_products.productid
			LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_account ON vtiger_account.accountid = vtiger_contactdetails.accountid
			WHERE vtiger_crmentity.deleted = 0 AND vtiger_products.productid = ' . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_contacts method ...');

        return $return_value;
    }

    /**	function used to get the list of potentials which are related to the product.
     *	@param int $id - product id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_opportunities($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_opportunities(' . $id . ') method ...');
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
                $button .= "<input title='" . getTranslatedString('LBL_NEW') . ' ' . getTranslatedString($singular_modname) . "' class='crmbutton small create'"
                    . " onclick='this.form.action.value=\"EditView\";this.form.module.value=\"{$related_module}\"' type='submit' name='button'"
                    . " value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "'>&nbsp;";
            }
        }

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT vtiger_potential.potentialid, vtiger_crmentity.crmid,
			vtiger_potential.potentialname, vtiger_account.accountname, vtiger_potential.related_to, vtiger_potential.contact_id,
			vtiger_potential.sales_stage, vtiger_potential.amount, vtiger_potential.closingdate,
			case when (vtiger_users.user_name not like '') then {$userNameSql} else
			vtiger_groups.groupname end as user_name, vtiger_crmentity.smownerid,
			vtiger_products.productname, vtiger_products.qty_per_unit, vtiger_products.unit_price,
			vtiger_products.expiry_date FROM vtiger_potential
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_potential.potentialid
			INNER JOIN vtiger_seproductsrel ON vtiger_seproductsrel.crmid = vtiger_potential.potentialid
			INNER JOIN vtiger_products ON vtiger_seproductsrel.productid = vtiger_products.productid
			INNER JOIN vtiger_potentialscf ON vtiger_potential.potentialid = vtiger_potentialscf.potentialid
			LEFT JOIN vtiger_account ON vtiger_potential.related_to = vtiger_account.accountid
			LEFT JOIN vtiger_contactdetails ON vtiger_potential.contact_id = vtiger_contactdetails.contactid
			LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			WHERE vtiger_crmentity.deleted = 0 AND vtiger_products.productid = " . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_opportunities method ...');

        return $return_value;
    }

    /**	function used to get the list of tickets which are related to the product.
     *	@param int $id - product id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_tickets($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_tickets(' . $id . ') method ...');
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

        if ($actions && getFieldVisibilityPermission($related_module, $current_user->id, 'product_id', 'readwrite') == '0') {
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
        $query = "SELECT  case when (vtiger_users.user_name not like \"\") then {$userNameSql} else vtiger_groups.groupname end as user_name, vtiger_users.id,
			vtiger_products.productid, vtiger_products.productname,
			vtiger_troubletickets.ticketid,
			vtiger_troubletickets.parent_id, vtiger_troubletickets.title,
			vtiger_troubletickets.status, vtiger_troubletickets.priority,
			vtiger_crmentity.crmid, vtiger_crmentity.smownerid,
			vtiger_crmentity.modifiedtime, vtiger_troubletickets.ticket_no
			FROM vtiger_troubletickets
			INNER JOIN vtiger_crmentity
				ON vtiger_crmentity.crmid = vtiger_troubletickets.ticketid
			LEFT JOIN vtiger_products
				ON vtiger_products.productid = vtiger_troubletickets.product_id
			LEFT JOIN vtiger_ticketcf ON vtiger_troubletickets.ticketid = vtiger_ticketcf.ticketid
			LEFT JOIN vtiger_users
				ON vtiger_users.id = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups
				ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			WHERE vtiger_crmentity.deleted = 0
			AND vtiger_products.productid = " . $id;

        $log->debug('Exiting get_tickets method ...');

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_tickets method ...');

        return $return_value;
    }

    /**	function used to get the list of quotes which are related to the product.
     *	@param int $id - product id
     *	@return array - array which will be returned from the function GetRelatedList
     */
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
        $query = "SELECT vtiger_crmentity.*,
			vtiger_quotes.*,
			vtiger_potential.potentialname,
			vtiger_account.accountname,
			vtiger_inventoryproductrel.productid,
			case when (vtiger_users.user_name not like '') then {$userNameSql}
				else vtiger_groups.groupname end as user_name
			FROM vtiger_quotes
			INNER JOIN vtiger_crmentity
				ON vtiger_crmentity.crmid = vtiger_quotes.quoteid
			INNER JOIN vtiger_inventoryproductrel
				ON vtiger_inventoryproductrel.id = vtiger_quotes.quoteid
			LEFT OUTER JOIN vtiger_account
				ON vtiger_account.accountid = vtiger_quotes.accountid
			LEFT OUTER JOIN vtiger_potential
				ON vtiger_potential.potentialid = vtiger_quotes.potentialid
			LEFT JOIN vtiger_groups
				ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_quotescf
				ON vtiger_quotescf.quoteid = vtiger_quotes.quoteid
			LEFT JOIN vtiger_quotesbillads
				ON vtiger_quotesbillads.quotebilladdressid = vtiger_quotes.quoteid
			LEFT JOIN vtiger_quotesshipads
				ON vtiger_quotesshipads.quoteshipaddressid = vtiger_quotes.quoteid
			LEFT JOIN vtiger_users
				ON vtiger_users.id = vtiger_crmentity.smownerid
			WHERE vtiger_crmentity.deleted = 0
			AND vtiger_inventoryproductrel.productid = " . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_quotes method ...');

        return $return_value;
    }

    /**	function used to get the list of purchase orders which are related to the product.
     *	@param int $id - product id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_purchase_orders($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_purchase_orders(' . $id . ') method ...');
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

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT vtiger_crmentity.*,
			vtiger_purchaseorder.*,
			vtiger_products.productname,
			vtiger_inventoryproductrel.productid,
			case when (vtiger_users.user_name not like '') then {$userNameSql}
				else vtiger_groups.groupname end as user_name
			FROM vtiger_purchaseorder
			INNER JOIN vtiger_crmentity
				ON vtiger_crmentity.crmid = vtiger_purchaseorder.purchaseorderid
			INNER JOIN vtiger_inventoryproductrel
				ON vtiger_inventoryproductrel.id = vtiger_purchaseorder.purchaseorderid
			INNER JOIN vtiger_products
				ON vtiger_products.productid = vtiger_inventoryproductrel.productid
			LEFT JOIN vtiger_groups
				ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_purchaseordercf
				ON vtiger_purchaseordercf.purchaseorderid = vtiger_purchaseorder.purchaseorderid
			LEFT JOIN vtiger_pobillads
				ON vtiger_pobillads.pobilladdressid = vtiger_purchaseorder.purchaseorderid
			LEFT JOIN vtiger_poshipads
				ON vtiger_poshipads.poshipaddressid = vtiger_purchaseorder.purchaseorderid
			LEFT JOIN vtiger_users
				ON vtiger_users.id = vtiger_crmentity.smownerid
			WHERE vtiger_crmentity.deleted = 0
			AND vtiger_products.productid = " . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_purchase_orders method ...');

        return $return_value;
    }

    /**	function used to get the list of sales orders which are related to the product.
     *	@param int $id - product id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_salesorder($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_salesorder(' . $id . ') method ...');
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

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT vtiger_crmentity.*,
			vtiger_salesorder.*,
			vtiger_products.productname AS productname,
			vtiger_account.accountname,
			case when (vtiger_users.user_name not like '') then {$userNameSql}
				else vtiger_groups.groupname end as user_name
			FROM vtiger_salesorder
			INNER JOIN vtiger_crmentity
				ON vtiger_crmentity.crmid = vtiger_salesorder.salesorderid
			INNER JOIN vtiger_inventoryproductrel
				ON vtiger_inventoryproductrel.id = vtiger_salesorder.salesorderid
			INNER JOIN vtiger_products
				ON vtiger_products.productid = vtiger_inventoryproductrel.productid
			LEFT OUTER JOIN vtiger_account
				ON vtiger_account.accountid = vtiger_salesorder.accountid
			LEFT JOIN vtiger_groups
				ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_salesordercf
				ON vtiger_salesordercf.salesorderid = vtiger_salesorder.salesorderid
			LEFT JOIN vtiger_invoice_recurring_info
				ON vtiger_invoice_recurring_info.salesorderid = vtiger_salesorder.salesorderid
			LEFT JOIN vtiger_sobillads
				ON vtiger_sobillads.sobilladdressid = vtiger_salesorder.salesorderid
			LEFT JOIN vtiger_soshipads
				ON vtiger_soshipads.soshipaddressid = vtiger_salesorder.salesorderid
			LEFT JOIN vtiger_users
				ON vtiger_users.id = vtiger_crmentity.smownerid
			WHERE vtiger_crmentity.deleted = 0
			AND vtiger_products.productid = " . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_salesorder method ...');

        return $return_value;
    }

    /**	function used to get the list of invoices which are related to the product.
     *	@param int $id - product id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_invoices($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug('Entering get_invoices(' . $id . ') method ...');
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

        $userNameSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users');
        $query = "SELECT vtiger_crmentity.*,
			vtiger_invoice.*,
			vtiger_inventoryproductrel.quantity,
			vtiger_account.accountname,
			case when (vtiger_users.user_name not like '') then {$userNameSql}
				else vtiger_groups.groupname end as user_name
			FROM vtiger_invoice
			INNER JOIN vtiger_crmentity
				ON vtiger_crmentity.crmid = vtiger_invoice.invoiceid
			LEFT OUTER JOIN vtiger_account
				ON vtiger_account.accountid = vtiger_invoice.accountid
			INNER JOIN vtiger_inventoryproductrel
				ON vtiger_inventoryproductrel.id = vtiger_invoice.invoiceid
			LEFT JOIN vtiger_groups
				ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_invoicecf
				ON vtiger_invoicecf.invoiceid = vtiger_invoice.invoiceid
			LEFT JOIN vtiger_invoicebillads
				ON vtiger_invoicebillads.invoicebilladdressid = vtiger_invoice.invoiceid
			LEFT JOIN vtiger_invoiceshipads
				ON vtiger_invoiceshipads.invoiceshipaddressid = vtiger_invoice.invoiceid
			LEFT JOIN vtiger_users
				ON  vtiger_users.id=vtiger_crmentity.smownerid
			WHERE vtiger_crmentity.deleted = 0
			AND vtiger_inventoryproductrel.productid = " . $id;

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_invoices method ...');

        return $return_value;
    }

    /**	function used to get the list of pricebooks which are related to the product.
     *	@param int $id - product id
     *	@return array - array which will be returned from the function GetRelatedList
     */
    public function get_product_pricebooks($id, $cur_tab_id, $rel_tab_id, $actions = false)
    {
        global $log,$singlepane_view,$currentModule;
        $log->debug('Entering get_product_pricebooks(' . $id . ') method ...');

        $related_module = vtlib_getModuleNameById($rel_tab_id);
        checkFileAccessForInclusion("modules/{$related_module}/{$related_module}.php");
        require_once "modules/{$related_module}/{$related_module}.php";
        $focus = new $related_module();
        $singular_modname = vtlib_toSingular($related_module);

        $button = '';
        if ($actions) {
            if (is_string($actions)) {
                $actions = explode(',', strtoupper($actions));
            }
            if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes' && isPermitted($currentModule, 'EditView', $id) == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_ADD_TO') . ' ' . getTranslatedString($related_module) . "' class='crmbutton small create'"
                    . " onclick='this.form.action.value=\"AddProductToPriceBooks\";this.form.module.value=\"{$currentModule}\"' type='submit' name='button'"
                    . " value='" . getTranslatedString('LBL_ADD_TO') . ' ' . getTranslatedString($related_module) . "'>&nbsp;";
            }
        }

        if ($singlepane_view == 'true') {
            $returnset = '&return_module=Products&return_action=DetailView&return_id=' . $id;
        } else {
            $returnset = '&return_module=Products&return_action=CallRelatedList&return_id=' . $id;
        }


        $query = 'SELECT vtiger_crmentity.crmid,
			vtiger_pricebook.*,
			vtiger_pricebookproductrel.productid as prodid
			FROM vtiger_pricebook
			INNER JOIN vtiger_crmentity
				ON vtiger_crmentity.crmid = vtiger_pricebook.pricebookid
			INNER JOIN vtiger_pricebookproductrel
				ON vtiger_pricebookproductrel.pricebookid = vtiger_pricebook.pricebookid
			INNER JOIN vtiger_pricebookcf
				ON vtiger_pricebookcf.pricebookid = vtiger_pricebook.pricebookid
			WHERE vtiger_crmentity.deleted = 0
			AND vtiger_pricebookproductrel.productid = ' . $id;
        $log->debug('Exiting get_product_pricebooks method ...');

        $return_value = GetRelatedList($currentModule, $related_module, $focus, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        return $return_value;
    }

    /**	function used to get the number of vendors which are related to the product.
     *	@return int number of rows - return the number of products which do not have relationship with vendor
     */
    public function product_novendor()
    {
        global $log;
        $log->debug('Entering product_novendor() method ...');
        $query = 'SELECT vtiger_products.productname, vtiger_crmentity.deleted
			FROM vtiger_products
			INNER JOIN vtiger_crmentity
				ON vtiger_crmentity.crmid = vtiger_products.productid
			WHERE vtiger_crmentity.deleted = 0
			AND vtiger_products.vendor_id is NULL';
        $result = $this->db->pquery($query, []);
        $log->debug('Exiting product_novendor method ...');

        return $this->db->num_rows($result);
    }

    /**
     * Function to get Product's related Products.
     * @param  int   $id      - productid
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

        if ($actions && $this->ismember_check() === 0) {
            if (is_string($actions)) {
                $actions = explode(',', strtoupper($actions));
            }
            if (in_array('SELECT', $actions) && isPermitted($related_module, 4, '') == 'yes') {
                $button .= "<input title='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module={$related_module}&return_module={$currentModule}&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid={$id}&parenttab={$parenttab}','test','width=640,height=602,resizable=0,scrollbars=0');\" value='" . getTranslatedString('LBL_SELECT') . ' ' . getTranslatedString($related_module) . "'>&nbsp;";
            }
            if (in_array('ADD', $actions) && isPermitted($related_module, 1, '') == 'yes') {
                $button .= "<input type='hidden' name='createmode' id='createmode' value='link' />"
                    . "<input title='" . getTranslatedString('LBL_NEW') . ' ' . getTranslatedString($singular_modname) . "' class='crmbutton small create'"
                    . " onclick='this.form.action.value=\"EditView\";this.form.module.value=\"{$related_module}\";' type='submit' name='button'"
                    . " value='" . getTranslatedString('LBL_ADD_NEW') . ' ' . getTranslatedString($singular_modname) . "'>&nbsp;";
            }
        }

        $query = "SELECT vtiger_products.productid, vtiger_products.productname,
			vtiger_products.productcode, vtiger_products.commissionrate,
			vtiger_seproductsrel.quantity AS qty_per_unit, vtiger_products.unit_price, 
			vtiger_crmentity.crmid, vtiger_crmentity.smownerid
			FROM vtiger_products
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_products.productid
			INNER JOIN vtiger_productcf
				ON vtiger_products.productid = vtiger_productcf.productid
			LEFT JOIN vtiger_seproductsrel ON vtiger_seproductsrel.crmid = vtiger_products.productid AND vtiger_seproductsrel.setype='Products'
			LEFT JOIN vtiger_users
				ON vtiger_users.id=vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups
				ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			WHERE vtiger_crmentity.deleted = 0 AND vtiger_seproductsrel.productid = {$id} ";

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if ($return_value == null) {
            $return_value = [];
        }
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug('Exiting get_products method ...');

        return $return_value;
    }

    /**
     * Function to get Product's related Products.
     * @param  int   $id      - productid
     * returns related Products record in array format
     */
    public function get_parent_products($id)
    {
        global $log, $singlepane_view;
        $log->debug('Entering get_products(' . $id . ') method ...');

        global $app_strings;

        $focus = new Products();

        $button = '';

        if ((isPermitted('Products', 1, '') == 'yes') && vtranslate('LBL_NEW_PRODUCT', 'Products') != '') {
            $newProductLabel = vtranslate('LBL_NEW_PRODUCT', 'Products');
            $button .= '<input title="' . $newProductLabel . '" accessyKey="F" class="button" onclick="this.form.action.value=\'EditView\';this.form.module.value=\'Products\';this.form.return_module.value=\'Products\';this.form.return_action.value=\'DetailView\'" type="submit" name="button" value="' . $newProductLabel . '">&nbsp;';
        }
        if ($singlepane_view == 'true') {
            $returnset = '&return_module=Products&return_action=DetailView&is_parent=1&return_id=' . $id;
        } else {
            $returnset = '&return_module=Products&return_action=CallRelatedList&is_parent=1&return_id=' . $id;
        }

        $query = "SELECT vtiger_products.productid, vtiger_products.productname,
			vtiger_products.productcode, vtiger_products.commissionrate,
			vtiger_products.qty_per_unit, vtiger_products.unit_price,
			vtiger_crmentity.crmid, vtiger_crmentity.smownerid
			FROM vtiger_products
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_products.productid
			INNER JOIN vtiger_seproductsrel ON vtiger_seproductsrel.productid = vtiger_products.productid AND vtiger_seproductsrel.setype='Products'
			INNER JOIN vtiger_productcf ON vtiger_products.productid = vtiger_productcf.productid

			WHERE vtiger_crmentity.deleted = 0 AND vtiger_seproductsrel.crmid = {$id} ";

        $log->debug('Exiting get_products method ...');

        return GetRelatedList('Products', 'Products', $focus, $query, $button, $returnset);
    }

    /**	function used to get the export query for product.
     *	@param reference $where - reference of the where variable which will be added with the query
     *	@return string $query - return the query which will give the list of products to export
     */
    public function create_export_query($where)
    {
        global $log, $current_user;
        $log->debug('Entering create_export_query(' . $where . ') method ...');

        include 'include/utils/ExportUtils.php';

        // To get the Permitted fields query and the permitted fields list
        $sql = getPermittedFieldsQuery('Products', 'detail_view');
        $fields_list = getFieldsListFromQuery($sql);

        $query = "SELECT {$fields_list} FROM " . $this->table_name . '
			INNER JOIN vtiger_crmentity
				ON vtiger_crmentity.crmid = vtiger_products.productid
			LEFT JOIN vtiger_productcf
				ON vtiger_products.productid = vtiger_productcf.productid
			LEFT JOIN vtiger_vendor
				ON vtiger_vendor.vendorid = vtiger_products.vendor_id';

        $query .= ' LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid';
        $query .= " LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id AND vtiger_users.status='Active'";
        $query .= $this->getNonAdminAccessControlQuery('Products', $current_user);
        $where_auto = ' vtiger_crmentity.deleted=0';

        if ($where != '') {
            $query .= " WHERE ({$where}) AND {$where_auto}";
        } else {
            $query .= " WHERE {$where_auto}";
        }

        $log->debug('Exiting create_export_query method ...');

        return $query;
    }

    /** Function to check if the product is parent of any other product.
     */
    public function isparent_check()
    {
        global $adb;
        $isparent_query = $adb->pquery(getListQuery('Products') . " AND (vtiger_products.productid IN (SELECT productid from vtiger_seproductsrel WHERE vtiger_seproductsrel.productid = ? AND vtiger_seproductsrel.setype='Products'))", [$this->id]);
        $isparent = $adb->num_rows($isparent_query);

        return $isparent;
    }

    /** Function to check if the product is member of other product.
     */
    public function ismember_check()
    {
        global $adb;
        $ismember_query = $adb->pquery(getListQuery('Products') . " AND (vtiger_products.productid IN (SELECT crmid from vtiger_seproductsrel WHERE vtiger_seproductsrel.crmid = ? AND vtiger_seproductsrel.setype='Products'))", [$this->id]);
        $ismember = $adb->num_rows($ismember_query);

        return $ismember;
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

        $rel_table_arr = ['HelpDesk' => 'vtiger_troubletickets', 'Products' => 'vtiger_seproductsrel', 'Attachments' => 'vtiger_seattachmentsrel',
            'Quotes' => 'vtiger_inventoryproductrel', 'PurchaseOrder' => 'vtiger_inventoryproductrel', 'SalesOrder' => 'vtiger_inventoryproductrel',
            'Invoice' => 'vtiger_inventoryproductrel', 'PriceBooks' => 'vtiger_pricebookproductrel', 'Leads' => 'vtiger_seproductsrel',
            'Accounts' => 'vtiger_seproductsrel', 'Potentials' => 'vtiger_seproductsrel', 'Contacts' => 'vtiger_seproductsrel',
            'Documents' => 'vtiger_senotesrel', 'Assets' => 'vtiger_assets', ];

        $tbl_field_arr = ['vtiger_troubletickets' => 'ticketid', 'vtiger_seproductsrel' => 'crmid', 'vtiger_seattachmentsrel' => 'attachmentsid',
            'vtiger_inventoryproductrel' => 'id', 'vtiger_pricebookproductrel' => 'pricebookid', 'vtiger_seproductsrel' => 'crmid',
            'vtiger_senotesrel' => 'notesid', 'vtiger_assets' => 'assetsid'];

        $entity_tbl_field_arr = ['vtiger_troubletickets' => 'product_id', 'vtiger_seproductsrel' => 'crmid', 'vtiger_seattachmentsrel' => 'crmid',
            'vtiger_inventoryproductrel' => 'productid', 'vtiger_pricebookproductrel' => 'productid', 'vtiger_seproductsrel' => 'productid',
            'vtiger_senotesrel' => 'crmid', 'vtiger_assets' => 'product'];

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

    /*
     * Function to get the secondary query part of a report
     * @param - $module primary module name
     * @param - $secmodule secondary module name
     * returns the query string formed on fetching the related data for report for secondary module
     */
    public function generateReportsSecQuery($module, $secmodule, $queryPlanner)
    {
        global $current_user;
        $matrix = $queryPlanner->newDependencyMatrix();

        $matrix->setDependency('vtiger_crmentityProducts', ['vtiger_groupsProducts', 'vtiger_usersProducts', 'vtiger_lastModifiedByProducts']);
        // query planner Support  added
        if (!$queryPlanner->requireTable('vtiger_products', $matrix)) {
            return '';
        }
        $matrix->setDependency('vtiger_products', ['innerProduct', 'vtiger_crmentityProducts', 'vtiger_productcf', 'vtiger_vendorRelProducts']);

        $query = $this->getRelationQuery($module, $secmodule, 'vtiger_products', 'productid', $queryPlanner);
        if ($queryPlanner->requireTable('innerProduct')) {
            $query .= ' LEFT JOIN (
					SELECT vtiger_products.productid,
							(CASE WHEN (vtiger_products.currency_id = 1 ) THEN vtiger_products.unit_price
								ELSE (vtiger_products.unit_price / vtiger_currency_info.conversion_rate) END
							) AS actual_unit_price
					FROM vtiger_products
					LEFT JOIN vtiger_currency_info ON vtiger_products.currency_id = vtiger_currency_info.id
					LEFT JOIN vtiger_productcurrencyrel ON vtiger_products.productid = vtiger_productcurrencyrel.productid
					AND vtiger_productcurrencyrel.currencyid = ' . $current_user->currency_id . '
				) AS innerProduct ON innerProduct.productid = vtiger_products.productid';
        }
        if ($queryPlanner->requireTable('vtiger_crmentityProducts')) {
            $query .= ' left join vtiger_crmentity as vtiger_crmentityProducts on vtiger_crmentityProducts.crmid=vtiger_products.productid and vtiger_crmentityProducts.deleted=0';
        }
        if ($queryPlanner->requireTable('vtiger_productcf')) {
            $query .= ' left join vtiger_productcf on vtiger_products.productid = vtiger_productcf.productid';
        }
        if ($queryPlanner->requireTable('vtiger_groupsProducts')) {
            $query .= ' left join vtiger_groups as vtiger_groupsProducts on vtiger_groupsProducts.groupid = vtiger_crmentityProducts.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_usersProducts')) {
            $query .= ' left join vtiger_users as vtiger_usersProducts on vtiger_usersProducts.id = vtiger_crmentityProducts.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_vendorRelProducts')) {
            $query .= ' left join vtiger_vendor as vtiger_vendorRelProducts on vtiger_vendorRelProducts.vendorid = vtiger_products.vendor_id';
        }
        if ($queryPlanner->requireTable('vtiger_lastModifiedByProducts')) {
            $query .= ' left join vtiger_users as vtiger_lastModifiedByProducts on vtiger_lastModifiedByProducts.id = vtiger_crmentityProducts.modifiedby ';
        }
        if ($queryPlanner->requireTable('vtiger_createdbyProducts')) {
            $query .= ' left join vtiger_users as vtiger_createdbyProducts on vtiger_createdbyProducts.id = vtiger_crmentityProducts.smcreatorid ';
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
            'HelpDesk' => ['vtiger_troubletickets' => ['product_id', 'ticketid'], 'vtiger_products' => 'productid'],
            'Quotes' => ['vtiger_inventoryproductrel' => ['productid', 'id'], 'vtiger_products' => 'productid'],
            'PurchaseOrder' => ['vtiger_inventoryproductrel' => ['productid', 'id'], 'vtiger_products' => 'productid'],
            'SalesOrder' => ['vtiger_inventoryproductrel' => ['productid', 'id'], 'vtiger_products' => 'productid'],
            'Invoice' => ['vtiger_inventoryproductrel' => ['productid', 'id'], 'vtiger_products' => 'productid'],
            'Leads' => ['vtiger_seproductsrel' => ['productid', 'crmid'], 'vtiger_products' => 'productid'],
            'Accounts' => ['vtiger_seproductsrel' => ['productid', 'crmid'], 'vtiger_products' => 'productid'],
            'Contacts' => ['vtiger_seproductsrel' => ['productid', 'crmid'], 'vtiger_products' => 'productid'],
            'Potentials' => ['vtiger_seproductsrel' => ['productid', 'crmid'], 'vtiger_products' => 'productid'],
            'Products' => ['vtiger_products' => ['productid', 'product_id'], 'vtiger_products' => 'productid'],
            'PriceBooks' => ['vtiger_pricebookproductrel' => ['productid', 'pricebookid'], 'vtiger_products' => 'productid'],
            'Documents' => ['vtiger_senotesrel' => ['crmid', 'notesid'], 'vtiger_products' => 'productid'],
        ];

        return $rel_tables[$secmodule];
    }

    public function deleteProduct2ProductRelation($record, $return_id, $is_parent)
    {
        global $adb;
        if ($is_parent == 0) {
            $sql = 'delete from vtiger_seproductsrel WHERE crmid = ? AND productid = ?';
            $adb->pquery($sql, [$record, $return_id]);
        } else {
            $sql = 'delete from vtiger_seproductsrel WHERE crmid = ? AND productid = ?';
            $adb->pquery($sql, [$return_id, $record]);
        }
    }

    // Function to unlink all the dependent entities of the given Entity by Id
    public function unlinkDependencies($module, $id)
    {
        global $log;
        // Backup Campaigns-Product Relation
        $cmp_q = 'SELECT campaignid FROM vtiger_campaign WHERE product_id = ?';
        $cmp_res = $this->db->pquery($cmp_q, [$id]);
        if ($this->db->num_rows($cmp_res) > 0) {
            $cmp_ids_list = [];
            for ($k = 0; $k < $this->db->num_rows($cmp_res); ++$k) {
                $cmp_ids_list[] = $this->db->query_result($cmp_res, $k, 'campaignid');
            }
            $params = [$id, RB_RECORD_UPDATED, 'vtiger_campaign', 'product_id', 'campaignid', implode(',', $cmp_ids_list)];
            $this->db->pquery('INSERT INTO vtiger_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
        }
        // we have to update the product_id as null for the campaigns which are related to this product
        $this->db->pquery('UPDATE vtiger_campaign SET product_id=0 WHERE product_id = ?', [$id]);

        // restoring products relations
        $productRelRB = $this->db->pquery('SELECT * FROM vtiger_seproductsrel WHERE productid = ?', [$id]);
        $rows = $this->db->num_rows($productRelRB);
        if ($this->db->num_rows($productRelRB)) {
            for ($i = 0; $i < $rows; ++$i) {
                $crmid = $this->db->query_result($productRelRB, $i, 'crmid');
                $params = [$id, RB_RECORD_DELETED, 'vtiger_seproductsrel', 'productid', 'crmid', $crmid];
                $this->db->pquery('INSERT INTO vtiger_relatedlists_rb(entityid, action, rel_table, rel_column, ref_column, related_crm_ids)
						VALUES (?,?,?,?,?,?)', $params);
            }
        }
        $this->db->pquery('DELETE from vtiger_seproductsrel WHERE productid=? or crmid=?', [$id, $id]);

        parent::unlinkDependencies($module, $id);
    }

    // Function to unlink an entity with given Id from another entity
    public function unlinkRelationship($id, $return_module, $return_id)
    {
        global $log;
        if (empty($return_module) || empty($return_id)) {
            return;
        }

        if ($return_module == 'Calendar') {
            $sql = 'DELETE FROM vtiger_seactivityrel WHERE crmid = ? AND activityid = ?';
            $this->db->pquery($sql, [$id, $return_id]);
        } elseif ($return_module == 'Leads' || $return_module == 'Contacts' || $return_module == 'Potentials') {
            $sql = 'DELETE FROM vtiger_seproductsrel WHERE productid = ? AND crmid = ?';
            $this->db->pquery($sql, [$id, $return_id]);
        } elseif ($return_module == 'Vendors') {
            $sql = 'UPDATE vtiger_products SET vendor_id = ? WHERE productid = ?';
            $this->db->pquery($sql, [null, $id]);
        } elseif ($return_module == 'Accounts') {
            $sql = 'DELETE FROM vtiger_seproductsrel WHERE productid = ? AND (crmid = ? OR crmid IN (SELECT contactid FROM vtiger_contactdetails WHERE accountid=?))';
            $param = [$id, $return_id, $return_id];
            $this->db->pquery($sql, $param);
        } elseif ($return_module == 'Documents') {
            $sql = 'DELETE FROM vtiger_senotesrel WHERE crmid=? AND notesid=?';
            $this->db->pquery($sql, [$id, $return_id]);
        } else {
            parent::unlinkRelationship($id, $return_module, $return_id);
        }
    }

    public function save_related_module($module, $crmid, $with_module, $with_crmids, $otherParams = [])
    {
        $adb = PearDatabase::getInstance();

        $qtysList = [];
        if ($otherParams && is_array($otherParams['quantities'])) {
            $qtysList = $otherParams['quantities'];
        }

        if (!is_array($with_crmids)) {
            $with_crmids = [$with_crmids];
        }
        foreach ($with_crmids as $with_crmid) {
            $qty = 0;
            if (array_key_exists($with_crmid, $qtysList)) {
                $qty = (float) $qtysList[$with_crmid];
            }
            if (!$qty) {
                $qty = 1;
            }

            if (in_array($with_module, ['Leads', 'Accounts', 'Contacts', 'Potentials', 'Products'])) {
                $query = $adb->pquery('SELECT * FROM vtiger_seproductsrel WHERE crmid=? AND productid=?', [$crmid, $with_crmid]);
                if ($adb->num_rows($query) == 0) {
                    $adb->pquery('INSERT INTO vtiger_seproductsrel VALUES (?,?,?,?)', [$with_crmid, $crmid, $with_module, $qty]);
                }
            } else {
                parent::save_related_module($module, $crmid, $with_module, $with_crmid);
            }
        }
    }
}
