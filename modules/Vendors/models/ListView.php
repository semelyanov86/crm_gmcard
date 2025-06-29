<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Vendors_ListView_Model extends Vtiger_ListView_Model
{
    /**
     * Function to get the list of Mass actions for the module.
     * @param <Array> $linkParams
     * @return <Array> - Associative array of Link type to List of  Vtiger_Link_Model instances for Mass Actions
     */
    public function getListViewMassActions($linkParams)
    {
        $massActionLinks = parent::getListViewMassActions($linkParams);

        $currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $emailModuleModel = Vtiger_Module_Model::getInstance('Emails');

        if ($currentUserModel->hasModulePermission($emailModuleModel->getId())) {
            $massActionLink = [
                'linktype' => 'LISTVIEWMASSACTION',
                'linklabel' => 'LBL_SEND_EMAIL',
                'linkurl' => 'javascript:Vtiger_List_Js.triggerSendEmail("index.php?module=' . $this->getModule()->getName() . '&view=MassActionAjax&mode=showComposeEmailForm&step=step1","Emails");',
                'linkicon' => '',
            ];
            $massActionLinks['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
        }

        return $massActionLinks;
    }
}
