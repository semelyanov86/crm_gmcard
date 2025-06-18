<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class MailManager_Search_View extends MailManager_Relation_View
{
    /**
     * Processes the request for search Operation.
     * @global <type> $currentUserModel
     * @return bool
     */
    public function process(Vtiger_Request $request)
    {

        $response = new MailManager_Response(true);
        $viewer = $this->getViewer($request);
        if ($this->getOperationArg($request) == 'popupui') {
            $viewer->view('Search.Popupui.tpl', 'MailManager');
            $response = false;

        } elseif ($this->getOperationArg($request) == 'email') {
            $searchTerm = $request->get('q');
            if (empty($searchTerm)) {
                $searchTerm = '%@';
            } // To avoid empty value of email to be filtered.
            else {
                $searchTerm = "%{$searchTerm}%";
            }

            $filteredResult = MailManager::lookupMailInVtiger($searchTerm, Users_Record_Model::getCurrentUserModel());

            MailManager_Utils_Helper::emitJSON($filteredResult);
            $response = false;
        }

        return $response;
    }
}
