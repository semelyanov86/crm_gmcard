<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Settings_Currency_Module_Model extends Settings_Vtiger_Module_Model
{
    public const tableName = 'vtiger_currency_info';

    public $listFields = ['currency_name' => 'Currency Name', 'currency_code' => 'Currency Code', 'currency_symbol' => 'Symbol',
        'conversion_rate' => 'Conversion Rate', 'currency_status' => 'Status'];

    public $name = 'Currency';

    public function isPagingSupported()
    {
        return false;
    }

    public function getCreateRecordUrl()
    {
        return 'javascript:Settings_Currency_Js.triggerAdd(event)';
    }

    public function getBaseTable()
    {
        return self::tableName;
    }

    public static function tranformCurrency($oldCurrencyId, $newCurrencyId)
    {
        return transferCurrency($oldCurrencyId, $newCurrencyId);
    }

    public static function delete($recordId)
    {
        $db = PearDatabase::getInstance();
        $query = 'UPDATE ' . self::tableName . ' SET deleted=1 WHERE id=?';
        $params = [$recordId];
        $db->pquery($query, $params);
    }
}
