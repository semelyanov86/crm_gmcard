<?php

/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class ITS4YouDescriptions_Field_Model extends Vtiger_Field_Model
{
    public function isAjaxEditable()
    {
        if ($this->get('name') === 'description') {
            return false;
        }

        return parent::isAjaxEditable();
    }

    public function getPicklistValues()
    {
        $values = parent::getPicklistValues();
        $name = $this->getName();

        if ($name === 'desc4youmodule') {
            $values = $this->updatePicklistValuesLabels($values);
        }

        return $values;
    }

    public function updatePicklistValuesLabels($values)
    {
        foreach ($values as $key => $value) {
            $values[$key] = vtranslate($value, $value);
        }

        return $values;
    }

    public function getEditablePicklistValues()
    {
        $values = parent::getEditablePicklistValues();
        $name = $this->getName();

        if ($name === 'desc4youmodule') {
            $values = $this->updatePicklistValuesLabels($values);
        }

        return $values;
    }
}
