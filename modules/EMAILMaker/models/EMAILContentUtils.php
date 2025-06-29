<?php

/* * *******************************************************************************
 * The content of this file is subject to the EMAIL Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class EMAILMaker_EMAILContentUtils_Model extends Vtiger_Base_Model
{
    private static $is_inventory_module = [];

    public function getOwnerNameCustom($id)
    {
        $db = PearDatabase::getInstance();
        if ($id != '') {
            $result = $db->pquery('SELECT user_name FROM vtiger_users WHERE id=?', [$id]);
            $ownername = $db->query_result($result, 0, 'user_name');
        }
        if ($ownername == '') {
            $result = $db->pquery('SELECT groupname FROM vtiger_groups WHERE groupid=?', [$id]);
            $ownername = $db->query_result($result, 0, 'groupname');
        } else {
            $ownername = getUserFullName($id);
        }

        return $ownername;
    }

    public function getSettings()
    {
        $adb = PearDatabase::getInstance();
        $result = $adb->pquery('SELECT * FROM vtiger_emakertemplates_settings', []);
        $Settings = $adb->fetchByAssoc($result, 1);

        return $Settings;
    }

    public function getAccountNo($account_id)
    {
        $accountno = '';
        if ($account_id != '') {
            $adb = PearDatabase::getInstance();
            $result = $adb->pquery('SELECT account_no FROM vtiger_account WHERE accountid=?', [$account_id]);
            $accountno = $adb->query_result($result, 0, 'account_no');
        }

        return $accountno;
    }

    public function convertListViewBlock($content)
    {
        EMAILMaker_EMAILMaker_Model::getSimpleHtmlDomFile();
        $html = str_get_html($content);
        if (is_array($html->find('td'))) {
            foreach ($html->find('td') as $td) {
                if (trim($td->plaintext) == '#LISTVIEWBLOCK_START#') {
                    $td->parent->outertext = '#LISTVIEWBLOCK_START#';
                }

                if (trim($td->plaintext) == '#LISTVIEWBLOCK_END#') {
                    $td->parent->outertext = '#LISTVIEWBLOCK_END#';
                }
            }
            $content = $html->save();
        }

        return $content;
    }

    public function convertVatBlock($content)
    {

        return $this->convertBlock('VAT', $content);
    }

    public function convertBlock($type, $content)
    {
        EMAILMaker_EMAILMaker_Model::getSimpleHtmlDomFile();
        $html = str_get_html($content);
        if (is_array($html->find('td'))) {
            foreach ($html->find('td') as $td) {
                if (trim($td->plaintext) == '#' . $type . 'BLOCK_START#') {
                    $td->parent->outertext = '#' . $type . 'BLOCK_START#';
                }
                if (trim($td->plaintext) == '#' . $type . 'BLOCK_END#') {
                    $td->parent->outertext = '#' . $type . 'BLOCK_END#';
                }
            }
            $content = $html->save();
        }

        return $content;
    }

    public function getUserValue($name, $data)
    {
        if (is_object($data)) {
            return $data->column_fields[$name];
        }
        if (isset($data[$name])) {
            return $data[$name];
        }

        return '';

    }

    public function getUITypeName($uitype, $typeofdata)
    {
        $type = '';
        switch ($uitype) {
            case '19':
            case '20':
            case '21':
            case '24':
                $type = 'textareas';
                break;
            case '5':
            case '6':
            case '23':
            case '70':
                $type = 'datefields';
                break;
            case '15':
                $type = 'picklists';
                break;
            case '56':
                $type = 'checkboxes';
                break;
            case '33':
                $type = 'multipicklists';
                break;
            case '71':
                $type = 'currencyfields';
                break;
            case '9':
            case '72':
            case '83':
                $type = 'numberfields';
                break;
            case '53':
            case '101':
                $type = 'userfields';
                break;
            case '52':
                $type = 'userorotherfields';
                break;
            case '10':
                $type = 'related';
                break;
            case '7':
                if (substr($typeofdata, 0, 1) == 'N') {
                    $type = 'numberfields';
                }
                break;
        }

        return $type;
    }

    public function getDOMElementAtts($elm)
    {
        $atts_string = '';
        if ($elm != null) {
            foreach ($elm->attr as $attName => $attVal) {
                $atts_string .= $attName . '="' . $attVal . '" ';
            }
        }

        return $atts_string;
    }

    public function GetFieldModuleRel()
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT fieldid, relmodule FROM vtiger_fieldmodulerel', []);
        $fieldModRel = [];

        while ($row = $db->fetchByAssoc($result)) {
            $fieldModRel[$row['fieldid']][] = $row['relmodule'];
        }

        return $fieldModRel;
    }

    public function getAttachmentsForId($templateid)
    {

        $adb = PearDatabase::getInstance();
        $Att_Documents = [];

        $sql = "SELECT vtiger_notes.notesid, vtiger_notes.title FROM vtiger_notes 
                      INNER JOIN vtiger_crmentity 
                         ON vtiger_crmentity.crmid = vtiger_notes.notesid
                      INNER JOIN vtiger_emakertemplates_documents 
                         ON vtiger_emakertemplates_documents.documentid = vtiger_notes.notesid
                      WHERE vtiger_crmentity.deleted = '0' AND vtiger_emakertemplates_documents.templateid = ?";
        $result = $adb->pquery($sql, [$templateid]);
        $num_rows = $adb->num_rows($result);

        if ($num_rows > 0) {
            while ($row = $adb->fetchByAssoc($result)) {
                $Att_Documents[] = $row['notesid'];
            }
        }

        return $Att_Documents;
    }

    public function getCustomfunctionParams($val)
    {
        $Params = [];
        $end = false;

        do {
            if (strstr($val, '|')) {
                if ($val[0] == '"') {
                    $delimiter = '"|';
                    $val = substr($val, 1);
                } elseif (substr($val, 0, 6) == '&quot;') {
                    $delimiter = '&quot;|';
                    $val = substr($val, 6);
                } else {
                    $delimiter = '|';
                }
                [$Params[], $val] = explode($delimiter, $val, 2);
            } else {
                $Params[] = $val;
                $end = true;
            }
        } while (!$end);

        return $Params;
    }

    public function getInventoryTableArray()
    {
        $Inventory_Table_Array = [
            'PurchaseOrder' => 'vtiger_purchaseorder',
            'SalesOrder' => 'vtiger_salesorder',
            'Quotes' => 'vtiger_quotes',
            'Invoice' => 'vtiger_invoice',
            'Issuecards' => 'vtiger_issuecards',
            'Receiptcards' => 'vtiger_receiptcards',
            'Creditnote' => 'vtiger_creditnote',
            'StornoInvoice' => 'vtiger_stornoinvoice',
        ];

        return $Inventory_Table_Array;
    }

    public function getInventoryIdArray()
    {
        $Inventory_Id_Array = [
            'PurchaseOrder' => 'purchaseorderid',
            'SalesOrder' => 'salesorderid',
            'Quotes' => 'quoteid',
            'Invoice' => 'invoiceid',
            'Issuecards' => 'issuecardid',
            'Receiptcards' => 'receiptcardid',
            'Creditnote' => 'creditnote_id',
            'StornoInvoice' => 'stornoinvoice_id',
        ];

        return $Inventory_Id_Array;
    }

    public function getUserFieldsForPDF()
    {
        return [
            'username' => 'username',
            'firstname' => 'first_name',
            'lastname' => 'last_name',
            'email' => 'email1',
            'title' => 'title',
            'fax' => 'phone_fax',
            'department' => 'department',
            'other_email' => 'email2',
            'phone' => 'phone_work',
            'yahooid' => 'yahoo_id',
            'mobile' => 'phone_mobile',
            'home_phone' => 'phone_home',
            'other_phone' => 'phone_other',
            'signature' => 'signature',
            'notes' => 'description',
            'address' => 'address_street',
            'country' => 'address_country',
            'city' => 'address_city',
            'zip' => 'address_postalcode',
            'state' => 'address_state',
        ];
    }

    public function getUserImage($id, $site_url = '')
    {

        if (isset($id) and $id != '') {
            $image = '';
            $adb = PearDatabase::getInstance();
            $image_res = $adb->pquery('select vtiger_attachments.* from vtiger_attachments left join vtiger_salesmanattachmentsrel on vtiger_salesmanattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid where vtiger_salesmanattachmentsrel.smid=?', [$id]);
            $row = $adb->query_result_rowdata($image_res);
            $row = self::fixStoredName($row);
            $site_url = self::fixSiteUrl($site_url);

            $image_id = $row['attachmentsid'];
            $image_path = $row['path'];
            $image_name = $row['storedname'];
            $imgpath = $site_url . $image_path . $image_id . '_' . $image_name;

            if ($image_name != '') {
                $image = '<img src="' . $imgpath . '" width="250px" border="0">';
            }

            return $image;
        }

        return '';

    }

    public function getContactImage($id, $site_url = '')
    {
        if (isset($id) and $id != '') {
            $db = PearDatabase::getInstance();
            $query = $this->getContactImageQuery();
            $result = $db->pquery($query, [$id]);
            $num_rows = $db->num_rows($result);
            if ($num_rows > 0) {
                $site_url = self::fixSiteUrl($site_url);
                $row = $db->query_result_rowdata($result);
                $row = self::fixStoredName($row);

                $image_src = $row['path'] . $row['attachmentsid'] . '_' . $row['storedname'];

                return "<img src='" . $site_url . $image_src . "'/>";
            }
        } else {
            return '';
        }
    }

    /**
     * @param array $data
     * @return array
     */
    public static function fixStoredName($data)
    {
        if (!isset($data['storedname']) || empty($data['storedname'])) {
            $data['storedname'] = $data['name'];
        }

        return $data;
    }

    /**
     * @param string $site_url
     * @return string
     */
    public static function fixSiteUrl($site_url)
    {
        if (!empty($site_url) && substr($site_url, -1) !== '/') {
            $site_url .= '/';
        }

        return $site_url;
    }

    public function getContactImageQuery()
    {
        return 'SELECT vtiger_attachments.*
	            FROM vtiger_contactdetails
	            INNER JOIN vtiger_seattachmentsrel
	            ON vtiger_contactdetails.contactid=vtiger_seattachmentsrel.crmid
	            INNER JOIN vtiger_attachments
	            ON vtiger_attachments.attachmentsid=vtiger_seattachmentsrel.attachmentsid
	            INNER JOIN vtiger_crmentity
	            ON vtiger_attachments.attachmentsid=vtiger_crmentity.crmid
	            WHERE deleted=0 AND vtiger_contactdetails.contactid=?';
    }

    public function getProductImage($id, $site_url = '')
    {
        $productid = $id;
        [$images, $bacImgs] = $this->getInventoryImages($productid, true);
        $sequence = '1';
        $retImage = '';
        $site_url = self::fixSiteUrl($site_url);

        if (isset($images[$productid . '_' . $sequence])) {
            $width = $height = '';
            if ($images[$productid . '_' . $sequence]['width'] > 0) {
                $width = " width='" . $images[$productid . '_' . $sequence]['width'] . "' ";
            }
            if ($images[$productid . '_' . $sequence]['height'] > 0) {
                $height = " height='" . $images[$productid . '_' . $sequence]['height'] . "' ";
            }
            $retImage = "<img src='" . $site_url . $images[$productid . '_' . $sequence]['src'] . "' " . $width . $height . '/>';
        } elseif (isset($bacImgs[$productid . '_' . $sequence])) {
            $retImage = "<img src='" . $site_url . $bacImgs[$productid . '_' . $sequence]['src'] . "' width='83' />";
        }

        return $retImage;
    }

    public function getInventoryImages($id, $isProductModule = false)
    {
        $db = PearDatabase::getInstance();
        $sql = $this->getInventoryImagesQuery($isProductModule);
        $mainImgs = $bacImgs = [];

        $res = $db->pquery($sql, [$id]);
        $products = [];

        while ($row = $db->fetchByAssoc($res)) {

            if (!isset($row['storedname']) || empty($row['storedname'])) {
                $row['storedname'] = $row['name'];
            }

            $products[$row['productid'] . '#_#' . $row['sequence_no']][$row['attachmentsid']]['path'] = $row['path'];
            $products[$row['productid'] . '#_#' . $row['sequence_no']][$row['attachmentsid']]['name'] = $row['storedname'];
        }

        $saved_res = $db->pquery('SELECT productid, sequence, attachmentid, width, height FROM vtiger_emakertemplates_images WHERE crmid=?', [$id]);
        $saved_products = [];
        $saved_wh = [];

        while ($saved_row = $db->fetchByAssoc($saved_res)) {
            $saved_products[$saved_row['productid'] . '_' . $saved_row['sequence']] = $saved_row['attachmentid'];
            $saved_wh[$saved_row['productid'] . '_' . $saved_row['sequence']]['width'] = ($saved_row['width'] > 0 ? $saved_row['width'] : '');
            $saved_wh[$saved_row['productid'] . '_' . $saved_row['sequence']]['height'] = ($saved_row['height'] > 0 ? $saved_row['height'] : '');
        }

        foreach ($products as $productnameid => $data) {
            [$productid, $seq] = explode('#_#', $productnameid, 2);
            foreach ($data as $attid => $images) {
                if ($attid != '') {
                    if (isset($saved_products[$productid . '_' . $seq])) {
                        if ($saved_products[$productid . '_' . $seq] == $attid) {
                            $width = $saved_wh[$productid . '_' . $seq]['width'];
                            $height = $saved_wh[$productid . '_' . $seq]['height'];

                            $mainImgs[$productid . '_' . $seq]['src'] = $images['path'] . $attid . '_' . $images['name'];
                            $mainImgs[$productid . '_' . $seq]['width'] = $width;
                            $mainImgs[$productid . '_' . $seq]['height'] = $height;
                        }
                    } elseif (!isset($bacImgs[$productid . '_' . $seq])) {   // add only the first backup image
                        $bacImgs[$productid . '_' . $seq]['src'] = $images['path'] . $attid . '_' . $images['name'];
                    }
                }
            }
        }

        return [$mainImgs, $bacImgs];
    }

    public function getInventoryImagesQuery($isProductModule)
    {
        if ($isProductModule === false) {
            $query = 'SELECT vtiger_inventoryproductrel.productid, vtiger_inventoryproductrel.sequence_no, vtiger_attachments.*
				FROM vtiger_inventoryproductrel
				LEFT JOIN vtiger_seattachmentsrel
				ON vtiger_seattachmentsrel.crmid=vtiger_inventoryproductrel.productid
				LEFT JOIN vtiger_attachments
				ON vtiger_attachments.attachmentsid=vtiger_seattachmentsrel.attachmentsid
				INNER JOIN vtiger_crmentity
				ON vtiger_attachments.attachmentsid=vtiger_crmentity.crmid
				WHERE vtiger_crmentity.deleted=0 AND vtiger_inventoryproductrel.id=?
				ORDER BY vtiger_inventoryproductrel.sequence_no';
        } else {
            $query = "SELECT vtiger_products.productid, '1' AS sequence_no, vtiger_attachments.*
                    FROM vtiger_products
                    LEFT JOIN vtiger_seattachmentsrel
                    ON vtiger_seattachmentsrel.crmid=vtiger_products.productid
                    LEFT JOIN vtiger_attachments
                    ON vtiger_attachments.attachmentsid=vtiger_seattachmentsrel.attachmentsid
                    INNER JOIN vtiger_crmentity
                    ON vtiger_attachments.attachmentsid=vtiger_crmentity.crmid
                    WHERE vtiger_crmentity.deleted=0 AND vtiger_products.productid=? ORDER BY vtiger_attachments.attachmentsid";
        }

        return $query;
    }

    public function getFieldValueUtils($efocus, $emodule, $fieldname, $value, $UITypes, $inventory_currency, $ignored_picklist_values, $def_charset, $decimals, $decimal_point, $thousands_separator, $language)
    {

        $db = PearDatabase::getInstance();

        $res2 = $db->pquery('SELECT * FROM vtiger_crmentity WHERE crmid = ?', [$efocus->id]);
        $CData = $db->fetchByAssoc($res2, 0);

        if (isset($CData['historized']) && $CData['historized'] == '1') {
            $type = 'e';
            if (in_array($fieldname, $UITypes['userorotherfields']) || in_array($fieldname, $UITypes['userfields'])) {
                $type = 'u';
            }
            $label_res = $db->pquery('SELECT label FROM its4you_historized WHERE crmid =? AND relid=? AND type=?', [$efocus->id, $value, $type]);
            if ($label_res != false && $db->num_rows($label_res) != 0) {
                return $db->query_result($label_res, 0, 'label');
            }
        }

        $current_user = Users_Record_Model::getCurrentUserModel();
        $related_fieldnames = ['related_to', 'relatedto', 'parent_id', 'parentid', 'product_id', 'productid', 'service_id', 'serviceid', 'vendor_id', 'product', 'account', 'invoiceid', 'linktoaccountscontacts', 'projectid', 'sc_related_to'];

        if (count($UITypes['related']) > 0) {
            foreach ($UITypes['related'] as $related_field) {
                if (!in_array($related_field, $related_fieldnames)) {
                    $related_fieldnames[] = $related_field;
                }
            }
        }

        if ($fieldname == 'account_id') {
            $value = getAccountName($value);
        } elseif ($fieldname == 'potential_id') {
            $value = getPotentialName($value);
        } elseif ($fieldname == 'contact_id') {
            $value = getContactName($value);
        } elseif ($fieldname == 'quote_id') {
            $value = getQuoteName($value);
        } elseif ($fieldname == 'salesorder_id') {
            $value = getSoName($value);
        } elseif ($fieldname == 'campaignid') {
            $value = getCampaignName($value);
        } elseif ($fieldname == 'terms_conditions') {
            $value = $this->getTermsAndConditionsCustom($value);
        } elseif ($fieldname == 'folderid') {
            $value = $this->getFolderName($value);
        } elseif ($fieldname == 'time_start' || $fieldname == 'time_end') {
            $curr_time = DateTimeField::convertToUserTimeZone($value);
            $value = $curr_time->format('H:i');
        } elseif (in_array($fieldname, $related_fieldnames)) {
            if ($value != '') {
                $parent_module = getSalesEntityType($value);
                $displayValueArray = getEntityName($parent_module, $value);

                if (!empty($displayValueArray)) {
                    foreach ($displayValueArray as $p_value) {
                        $value = $p_value;
                    }
                }
                if ($fieldname == 'invoiceid' && $value == '0') {
                    $value = '';
                }
            }
        }
        if (in_array($fieldname, $UITypes['datefields'])) {
            if ($emodule == 'Events' || $emodule == 'Calendar') {
                if ($fieldname == 'date_start' && $efocus->column_fields['time_start'] != '') {
                    $curr_time = $efocus->column_fields['time_start'];
                    $value = $value . ' ' . $curr_time;
                } elseif ($fieldname == 'due_date' && $efocus->column_fields['time_end'] != '') {
                    $curr_time = $efocus->column_fields['time_end'];
                    $value = $value . ' ' . $curr_time;
                }
            }
            if ($value != '') {
                $value = getValidDisplayDate($value);
            }
        } elseif (in_array($fieldname, $UITypes['picklists'])) {
            if (!in_array(trim($value), $ignored_picklist_values)) {
                $value = $this->getTranslatedStringCustom($value, $emodule, $language);
            } else {
                $value = '';
            }
        } elseif (in_array($fieldname, $UITypes['checkboxes'])) {
            if ($value == 1) {
                $value = vtranslate('LBL_YES');
            } else {
                $value = vtranslate('LBL_NO');
            }
        } elseif (in_array($fieldname, $UITypes['textareas'])) {
            if (strpos($value, '&lt;br /&gt;') === false && strpos($value, '&lt;br/&gt;') === false && strpos($value, '&lt;br&gt;') === false) {
                $value = nl2br($value);
            }
            $value = html_entity_decode($value, ENT_QUOTES, $def_charset);
        } elseif (in_array($fieldname, $UITypes['multipicklists'])) {
            $MultipicklistValues = explode(' |##| ', $value);
            foreach ($MultipicklistValues as &$value) {
                $value = $this->getTranslatedStringCustom($value, $emodule, $language);
            }
            $value = implode(', ', $MultipicklistValues);
        } elseif (in_array($fieldname, $UITypes['currencyfields'])) {
            if (is_numeric($value)) {
                if ($inventory_currency === false) {
                    $user_currency_data = getCurrencySymbolandCRate($current_user->currency_id);
                    $crate = $user_currency_data['rate'];
                } else {
                    $crate = $inventory_currency['conversion_rate'];
                }
                $value = $value * $crate;
            }
            $value = $this->formatNumberToEMAILwithAtr($value, $decimals, $decimal_point, $thousands_separator);
        } elseif (in_array($fieldname, $UITypes['numberfields'])) {
            $value = $this->formatNumberToEMAILwithAtr($value, $decimals, $decimal_point, $thousands_separator);
        } elseif (in_array($fieldname, $UITypes['userfields'])) {
            if ($value != '0' && $value != '') {
                $value = getOwnerName($value);
            } else {
                $value = '';
            }
        } elseif (in_array($fieldname, $UITypes['userorotherfields'])) {
            if ($value != '0' && $value != '') {
                $selid = $value;
                $value = getUserFullName($selid);

                if ($value == '') {
                    $value = $selid;
                    $parent_module = getSalesEntityType($selid);
                    $displayValueArray = getEntityName($parent_module, $selid);

                    if (!empty($displayValueArray)) {
                        foreach ($displayValueArray as $p_value) {
                            $value = $p_value;
                        }
                    }
                }
            } else {
                $value = '';
            }
        }

        return $value;
    }

    public function getTermsAndConditionsCustom($value)
    {
        if (file_exists('modules/Settings/EditTermDetails.php')) {
            $adb = PearDatabase::getInstance();
            $res = $adb->pquery('SELECT tandc FROM vtiger_inventory_tandc WHERE id=?', [$value]);
            $num = $adb->num_rows($res);
            if ($num > 0) {
                $tandc = $adb->query_result($res, 0, 'tandc');
            } else {
                $tandc = $value;
            }
        } else {
            $tandc = $value;
        }

        return $tandc;
    }

    public function getFolderName($folderid)
    {
        $foldername = '';
        if ($folderid != '') {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT foldername FROM vtiger_attachmentsfolder WHERE folderid = ?', [$folderid]);
            if ($db->num_rows($result) > 0) {
                return $foldername = $db->query_result($result, 0, 'foldername');
            }
        }

        return $foldername;
    }

    public function getTranslatedStringCustom($str, $emodule, $language)
    {

        if ($emodule != 'Products/Services') {
            $app_lang = return_application_language($language);
            $mod_lang = return_specified_module_language($language, $emodule);
        } else {
            $app_lang = return_specified_module_language($language, 'Services');
            $mod_lang = return_specified_module_language($language, 'Products');
        }
        $trans_str = ($mod_lang[$str] != '') ? $mod_lang[$str] : (($app_lang[$str] != '') ? $app_lang[$str] : $str);

        return $trans_str;
    }

    public function formatNumberToEMAILwithAtr($value, $decimals, $decimal_point, $thousands_separator)
    {
        $number = '';
        if (is_numeric($value)) {
            $number = number_format($value, $decimals, $decimal_point, $thousands_separator);
        }

        return $number;
    }

    public function getInventoryCurrencyInfoCustomArray($inventory_table, $inventory_id, $id)
    {
        $db = PearDatabase::getInstance();

        if ($inventory_table != '') {
            $sql = 'SELECT currency_id, ' . $inventory_table . '.conversion_rate AS conv_rate, vtiger_currency_info.* FROM ' . $inventory_table . '
                           INNER JOIN vtiger_currency_info ON ' . $inventory_table . '.currency_id = vtiger_currency_info.id
                           WHERE ' . $inventory_id . '=?';
        } else {
            $sql = "SELECT vtiger_currency_info.*, id AS currency_id, '' AS conv_rate FROM vtiger_currency_info WHERE  vtiger_currency_info.id=?";
        }
        $res = $db->pquery($sql, [$id]);

        $currency_info = [];
        $currency_info['currency_id'] = $db->query_result($res, 0, 'currency_id');
        $currency_info['conversion_rate'] = $db->query_result($res, 0, 'conv_rate');
        $currency_info['currency_name'] = $db->query_result($res, 0, 'currency_name');
        $currency_info['currency_code'] = $db->query_result($res, 0, 'currency_code');
        $currency_info['currency_symbol'] = $db->query_result($res, 0, 'currency_symbol');

        return $currency_info;
    }

    public function getInventoryProductsQuery()
    {
        $query = "select case when vtiger_products.productid != '' then vtiger_products.productname else vtiger_service.servicename end as productname,"
            . " case when vtiger_products.productid != '' then vtiger_products.productid else vtiger_service.serviceid end as psid,"
            . " case when vtiger_products.productid != '' then vtiger_products.product_no else vtiger_service.service_no end as psno,"
            . " case when vtiger_products.productid != '' then 'Products' else 'Services' end as entitytype,"
            . " case when vtiger_products.productid != '' then vtiger_products.unit_price else vtiger_service.unit_price end as unit_price,"
            . " case when vtiger_products.productid != '' then vtiger_products.usageunit else vtiger_service.service_usageunit end as usageunit,"
            . " case when vtiger_products.productid != '' then vtiger_products.qty_per_unit else vtiger_service.qty_per_unit end as qty_per_unit,"
            . " case when vtiger_products.productid != '' then vtiger_products.qtyinstock else 'NA' end as qtyinstock,"
            . " case when vtiger_products.productid != '' then c1.description else c2.description end as psdescription, vtiger_inventoryproductrel.* "
            . ' from vtiger_inventoryproductrel'
            . ' left join vtiger_products on vtiger_products.productid=vtiger_inventoryproductrel.productid '
            . ' left join vtiger_crmentity as c1 on c1.crmid = vtiger_products.productid '
            . ' left join vtiger_service on vtiger_service.serviceid=vtiger_inventoryproductrel.productid '
            . ' left join vtiger_crmentity as c2 on c2.crmid = vtiger_service.serviceid '
            . ' where id = ? ORDER BY sequence_no';

        return $query;
    }

    public function getOrgOldCols()
    {
        $org_cols = [
            'organizationname' => 'COMPANY_NAME',
            'address' => 'COMPANY_ADDRESS',
            'city' => 'COMPANY_CITY',
            'state' => 'COMPANY_STATE',
            'code' => 'COMPANY_ZIP',
            'country' => 'COMPANY_COUNTRY',
            'phone' => 'COMPANY_PHONE',
            'fax' => 'COMPANY_FAX',
            'website' => 'COMPANY_WEBSITE',
            'logo' => 'COMPANY_LOGO',
        ];

        return $org_cols;
    }

    public function isInventoryModule($module)
    {

        $class_name = $module . '_Module_Model';

        if (class_exists($class_name)) {
            if (is_subclass_of($class_name, 'Inventory_Module_Model')) {
                self::$is_inventory_module[$module] = true;
            } else {
                self::$is_inventory_module[$module] = false;
            }
        }

        return self::$is_inventory_module[$module];
    }

    public function getUITypeRelatedModule($uitype, $fk_record)
    {

        $related_module = '';
        switch ($uitype) {
            case '51':
            case '73':
                $related_module = 'Accounts';
                break;
            case '57':
                $related_module = 'Contacts';
                break;
            case '58':
                $related_module = 'Campaigns';
                break;
            case '59':
                $related_module = 'Products';
                break;
            case '81':
            case '75':
                $related_module = 'Vendors';
                break;
            case '76':
                $related_module = 'Potentials';
                break;
            case '78':
                $related_module = 'Quotes';
                break;
            case '80':
                $related_module = 'SalesOrder';
                break;
            case '53':
            case '101':
                $related_module = 'Users';
                break;
            case '68':
            case '10':
                $related_module = getSalesEntityType($fk_record);
                break;
        }

        return $related_module;
    }

    public function getRelBlockLabels()
    {
        return [
            'Last Modified By' => 'Last Modified',
            'Conversion Rate' => 'LBL_CONVERSION_RATE',
            'List Price' => 'LBL_LIST_PRICE',
            'Discount' => 'LBL_DISCOUNT',
            'Quantity' => 'LBL_QUANTITY',
            'Comments' => 'LBL_COMMENTS',
            'Currency' => 'LBL_CURRENCY',
            'Due Date' => 'LBL_DUE_DATE',
            'End Time' => 'End Time',
            'Related to' => 'LBL_RELATED_TO',
            'Assigned To' => 'Assigned To',
            'Created Time' => 'Created Time',
            'Modified Time' => 'Modified Time',
        ];
    }

    public function getAttachmentImage($id, $site_url)
    {
        if (isset($id) and $id != '') {
            $db = PearDatabase::getInstance();
            $query = $this->getAttachmentImageQuery();
            $result = $db->pquery($query, [$id]);
            $num_rows = $db->num_rows($result);
            if ($num_rows > 0) {
                $row = $db->query_result_rowdata($result);

                if (!isset($row['storedname']) || empty($row['storedname'])) {
                    $row['storedname'] = $row['name'];
                }

                $image_src = $row['path'] . $row['attachmentsid'] . '_' . $row['storedname'];

                return "<img src='" . $site_url . '/' . $image_src . "'/>";
            }
        } else {
            return '';
        }
    }

    public function getAttachmentImageQuery()
    {
        return 'SELECT vtiger_attachments.*
	            FROM vtiger_seattachmentsrel
	            INNER JOIN vtiger_attachments
	            ON vtiger_attachments.attachmentsid=vtiger_seattachmentsrel.attachmentsid
	            INNER JOIN vtiger_crmentity
	            ON vtiger_attachments.attachmentsid=vtiger_crmentity.crmid
	            WHERE deleted=0 AND vtiger_attachments.attachmentsid=?';
    }
}
