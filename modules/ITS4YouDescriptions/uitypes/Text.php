<?php

/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class ITS4YouDescriptions_Text_UIType extends Vtiger_Text_UIType
{
    /**
     * @param string $value
     * @param int $record
     * @param object $recordInstance
     * @param bool $removeTags
     * @return string
     */
    public function getDisplayValue($value, $record = false, $recordInstance = false, $removeTags = false)
    {
        return purifyHtmlEventAttributes($value, true);
    }
}
