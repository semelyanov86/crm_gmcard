<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Events_Multireference_UIType extends Calendar_Multireference_UIType
{
    /**
     * Function to get the Detailview template name for the current UI Type Object.
     * @return <String> - Template Name
     */
    public function getDetailViewTemplateName()
    {
        return 'uitypes/MultireferenceDetailView.tpl';
    }
}
