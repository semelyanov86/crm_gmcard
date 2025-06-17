<?php
/* ********************************************************************************
 * The content of this file is subject to the ITS4YouDescriptions license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 */

class Settings_ITS4YouDescriptions_Module_Model extends Settings_Vtiger_Module_Model
{
    public function getSettingLinks()
    {
        $moduleModel = Vtiger_Module_Model::getInstance('ITS4YouDescriptions');

        return $moduleModel->getSettingLinks();
    }
}
