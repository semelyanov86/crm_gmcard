<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'include/Webservices/Query.php';
include_once dirname(__FILE__) . '/FetchAllAlerts.php';

class Mobile_WS_AlertDetailsWithMessage extends Mobile_WS_FetchAllAlerts
{
    public function process(Mobile_API_Request $request)
    {
        global $current_user;

        $response = new Mobile_API_Response();

        $alertid = $request->get('alertid');
        $current_user = $this->getActiveUser();

        $alert = $this->getAlertDetails($alertid);
        if (empty($alert)) {
            $response->setError(1_401, 'Alert not found');
        } else {
            $result = [];
            $result['alert'] = $this->getAlertDetails($alertid);
            $response->setResult($result);
        }

        return $response;
    }

    public function getAlertDetails($alertid = false)
    {

        $alertModel = Mobile_WS_AlertModel::modelWithId($alertid);

        $alert = false;
        if ($alertModel) {
            $alert = $alertModel->serializeToSend();

            $alertModel->setUser($this->getActiveUser());
            $alert['message'] = $alertModel->message();
        }

        return $alert;
    }
}
