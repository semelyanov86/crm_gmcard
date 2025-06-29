<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Calendar_Datetime_UIType extends Vtiger_Datetime_UIType
{
    public function getDisplayValue($value, $record = false, $recordInstance = false)
    {
        // Since date_start and due_date fields of calendar can have time appended or removed
        if ($this->hasTimeComponent($value)) {
            $fieldInstance = $this->get('field')->getWebserviceFieldObject();
            $moduleName = $this->get('field')->getModule()->getName();
            $fieldName = $fieldInstance->getFieldName();
            if ($fieldName == 'date_start' || $fieldName == 'due_date') {
                return self::getDisplayDateTimeValue($value);
            }

            return parent::getDisplayValue($value);

        }

        return $this->getDisplayDateValue($value);

    }

    public function hasTimeComponent($value)
    {
        $component = explode(' ', $value);
        if (!empty($component[1])) {
            return true;
        }

        return false;
    }
}
