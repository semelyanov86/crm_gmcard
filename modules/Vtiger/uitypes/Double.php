<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Vtiger_Double_UIType extends Vtiger_Base_UIType
{
    /**
     * Function to get the Template name for the current UI Type object.
     * @return <String> - Template Name
     */
    public function getTemplateName()
    {
        return 'uitypes/Number.tpl';
    }

    /**
     * Function to get the Display Value, for the current field type with given DB Insert Value.
     * @param <Object> $value
     * @return <Object>
     */
    public function getDisplayValue($value, $record = false, $recordInstance = false)
    {
        // The value is formatting to the user preffered format
        // The third parameter for the converTouserFormat() function is skipConversion.
        // We set skipConversion to true because there's no need to convert the values for different currency formats.
        $value = CurrencyField::convertToUserFormat(decimalFormat($value), null, true);

        return $value;
    }

    /**
     * Function to get the Value of the field in the format, the user provides it on Save.
     * @param <Object> $value
     * @return <Object>
     */
    public function getUserRequestValue($value)
    {
        return $this->getDisplayValue($value);
    }
}
