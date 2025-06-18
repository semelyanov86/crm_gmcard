<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Contacts_Record_Model extends Vtiger_Record_Model
{
    /**
     * Function returns the url for create event.
     * @return <String>
     */
    public function getCreateEventUrl()
    {
        $calendarModuleModel = Vtiger_Module_Model::getInstance('Calendar');

        return $calendarModuleModel->getCreateEventRecordUrl() . '&contact_id=' . $this->getId();
    }

    /**
     * Function returns the url for create todo.
     * @return <String>
     */
    public function getCreateTaskUrl()
    {
        $calendarModuleModel = Vtiger_Module_Model::getInstance('Calendar');

        return $calendarModuleModel->getCreateTaskRecordUrl() . '&contact_id=' . $this->getId();
    }

    /**
     * Function to get List of Fields which are related from Contacts to Inventory Record.
     * @return <array>
     */
    public function getInventoryMappingFields()
    {
        return [
            ['parentField' => 'account_id', 'inventoryField' => 'account_id', 'defaultValue' => ''],

            // Billing Address Fields
            ['parentField' => 'mailingcity', 'inventoryField' => 'bill_city', 'defaultValue' => ''],
            ['parentField' => 'mailingstreet', 'inventoryField' => 'bill_street', 'defaultValue' => ''],
            ['parentField' => 'mailingstate', 'inventoryField' => 'bill_state', 'defaultValue' => ''],
            ['parentField' => 'mailingzip', 'inventoryField' => 'bill_code', 'defaultValue' => ''],
            ['parentField' => 'mailingcountry', 'inventoryField' => 'bill_country', 'defaultValue' => ''],
            ['parentField' => 'mailingpobox', 'inventoryField' => 'bill_pobox', 'defaultValue' => ''],

            // Shipping Address Fields
            ['parentField' => 'otherstreet', 'inventoryField' => 'ship_street', 'defaultValue' => ''],
            ['parentField' => 'othercity', 'inventoryField' => 'ship_city', 'defaultValue' => ''],
            ['parentField' => 'otherstate', 'inventoryField' => 'ship_state', 'defaultValue' => ''],
            ['parentField' => 'otherzip', 'inventoryField' => 'ship_code', 'defaultValue' => ''],
            ['parentField' => 'othercountry', 'inventoryField' => 'ship_country', 'defaultValue' => ''],
            ['parentField' => 'otherpobox', 'inventoryField' => 'ship_pobox', 'defaultValue' => ''],
        ];
    }
}
