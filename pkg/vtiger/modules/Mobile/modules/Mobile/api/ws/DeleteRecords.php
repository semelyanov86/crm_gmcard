<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'include/Webservices/Delete.php';

class Mobile_WS_DeleteRecords extends Mobile_WS_Controller
{
    public function process(Mobile_API_Request $request)
    {
        global $current_user;

        $current_user = $this->getActiveUser();
        $records = $request->get('records');
        if (empty($records)) {
            $records = [$request->get('record')];
        } else {
            $records = Zend_Json::decode($records);
        }
        $deleted = [];
        foreach ($records as $record) {
            try {
                $recordModel = Vtiger_Record_Model::getInstanceById($record);
                $recordModel->delete();
                $result = true;
            } catch (Exception $e) {
                $result = false;
            }
            $deleted[$record] = $result;
        }

        $response = new Mobile_API_Response();
        $response->setResult(['deleted' => $deleted]);

        return $response;
    }
}
