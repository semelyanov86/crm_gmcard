<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once dirname(__FILE__) . '/SMSNotifierBase.php';
include_once dirname(__FILE__) . '/ext/SMSProvider.php';
include_once dirname(__FILE__) . '/ext/ISMSProvider.php';
include_once 'include/Zend/Json.php';

class SMSNotifier extends SMSNotifierBase
{
    /**
     * Check if there is active server configured.
     *
     * @return true if activer server is found, false otherwise
     */
    public static function checkServer()
    {
        $provider = SMSNotifierManager::getActiveProviderInstance();

        return $provider !== false;
    }

    /**
     * Send SMS (Creates SMS Entity record, links it with related CRM record and triggers provider to send sms).
     *
     * @param string $message
     * @param array $tonumbers
     * @param int $ownerid User id to assign the SMS record
     * @param mixed $linktoids List of CRM record id to link SMS record
     * @param string $linktoModule Modulename of CRM record to link with (if not provided lookup it will be calculated)
     */
    public static function sendsms($message, $tonumbers, $ownerid = false, $linktoids = false, $linktoModule = false)
    {
        global $current_user, $adb;

        if ($ownerid === false) {
            if (isset($current_user) && !empty($current_user)) {
                $ownerid = $current_user->id;
            } else {
                $ownerid = 1;
            }
        }

        $moduleName = 'SMSNotifier';
        $focus = CRMEntity::getInstance($moduleName);

        $focus->column_fields['message'] = $message;
        $focus->column_fields['assigned_user_id'] = $ownerid;
        $focus->save($moduleName);

        if ($linktoids !== false) {

            if ($linktoModule !== false) {
                relateEntities($focus, $moduleName, $focus->id, $linktoModule, $linktoids);
            } else {
                // Link modulename not provided (linktoids can belong to mix of module so determine proper modulename)
                $linkidsetypes = $adb->pquery('SELECT setype,crmid FROM vtiger_crmentity WHERE crmid IN (' . generateQuestionMarks($linktoids) . ')', [$linktoids]);
                if ($linkidsetypes && $adb->num_rows($linkidsetypes)) {
                    while ($linkidsetypesrow = $adb->fetch_array($linkidsetypes)) {
                        relateEntities($focus, $moduleName, $focus->id, $linkidsetypesrow['setype'], $linkidsetypesrow['crmid']);
                    }
                }
            }
        }
        $responses = self::fireSendSMS($message, $tonumbers);

        $status = ISMSProvider::MSG_STATUS_DISPATCHED;
        foreach ($responses as $response) {
            if ($response['error']) {
                $status = ISMSProvider::MSG_STATUS_FAILED;
            }
        }

        $focus->processFireSendSMSResponse($responses);

        return $focus->id;
    }

    /**
     * Detect the related modules based on the entity relation information for this instance.
     */
    public function detectRelatedModules()
    {

        global $adb, $current_user;

        // Pick the distinct modulenames based on related records.
        $result = $adb->pquery('SELECT distinct setype FROM vtiger_crmentity WHERE crmid in (
			SELECT relcrmid FROM vtiger_crmentityrel INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_crmentityrel.crmid
			WHERE vtiger_crmentity.crmid = ? AND vtiger_crmentity.deleted=0)', [$this->id]);

        $relatedModules = [];

        // Calculate the related module access (similar to getRelatedList API in DetailViewUtils.php)
        if ($result && $adb->num_rows($result)) {
            require 'user_privileges/user_privileges_' . $current_user->id . '.php';

            while ($resultrow = $adb->fetch_array($result)) {
                $accessCheck = false;
                $relatedTabId = getTabid($resultrow['setype']);
                if ($relatedTabId == 0) {
                    $accessCheck = true;
                } else {
                    if ($profileTabsPermission[$relatedTabId] == 0) {
                        if ($profileActionPermission[$relatedTabId][3] == 0) {
                            $accessCheck = true;
                        }
                    }
                }

                if ($accessCheck) {
                    $relatedModules[$relatedTabId] = $resultrow['setype'];
                }
            }
        }

        return $relatedModules;

    }

    protected function isUserOrGroup($id)
    {
        global $adb;
        $result = $adb->pquery('SELECT 1 FROM vtiger_users WHERE id=?', [$id]);
        if ($result && $adb->num_rows($result)) {
            return 'U';
        }

        return 'T';

    }

    protected function smsAssignedTo()
    {
        global $adb;

        // Determine the number based on Assign To
        $assignedtoid = $this->column_fields['assigned_user_id'];
        $type = $this->isUserOrGroup($assignedtoid);

        if ($type == 'U') {
            $userIds = [$assignedtoid];
        } else {
            require_once 'include/utils/GetGroupUsers.php';
            $getGroupObj = new GetGroupUsers();
            $getGroupObj->getAllUsersInGroup($assignedtoid);
            $userIds = $getGroupObj->group_users;

            // Clearing static cache for sub groups
            GetGroupUsers::$groupIdsList = [];
        }

        $tonumbers = [];

        if (php7_count($userIds) > 0) {
            $phoneSqlQuery = "select phone_mobile, id from vtiger_users WHERE status='Active' AND id in(" . generateQuestionMarks($userIds) . ')';
            $phoneSqlResult = $adb->pquery($phoneSqlQuery, [$userIds]);

            while ($phoneSqlResultRow = $adb->fetch_array($phoneSqlResult)) {
                $number = $phoneSqlResultRow['phone_mobile'];
                if (!empty($number)) {
                    $tonumbers[] = $number;
                }
            }
        }

        if (!empty($tonumbers)) {
            $responses = self::fireSendSMS($this->column_fields['message'], $tonumbers);
            $this->processFireSendSMSResponse($responses);
        }
    }

    private function processFireSendSMSResponse($responses)
    {

        if (empty($responses)) {
            return;
        }

        global $adb;

        foreach ($responses as $response) {
            $responseID = '';
            $responseStatus = '';
            $responseStatusMessage = '';

            $needlookup = 1;
            if ($response['error']) {
                $responseStatus = ISMSProvider::MSG_STATUS_FAILED;
                $needlookup = 0;
            } else {
                $responseID = $response['id'];
                $responseStatus = $response['status'];
            }

            if (isset($response['statusmessage'])) {
                $responseStatusMessage = $response['statusmessage'];
            }
            $adb->pquery(
                'INSERT INTO vtiger_smsnotifier_status(smsnotifierid,tonumber,status,statusmessage,smsmessageid,needlookup) VALUES(?,?,?,?,?,?)',
                [$this->id, $response['to'], $responseStatus, $responseStatusMessage, $responseID, $needlookup],
            );
        }
    }

    public static function smsquery($record)
    {
        global $adb;
        $results = [];
        $result = $adb->pquery('SELECT * FROM vtiger_smsnotifier_status WHERE smsnotifierid = ?', [$record]);
        if ($result && $adb->num_rows($result)) {
            $provider = SMSNotifierManager::getActiveProviderInstance();

            while ($resultrow = $adb->fetch_array($result)) {
                if ($resultrow['needlookup'] == 1) {
                    $messageid = $resultrow['smsmessageid'];

                    $response = $provider->query($messageid);

                    if ($response['error']) {
                        $responseStatus = ISMSProvider::MSG_STATUS_FAILED;
                        $needlookup = $response['needlookup'];
                    } else {
                        $responseStatus = $response['status'];
                        $needlookup = $response['needlookup'];
                    }

                    $responseStatusMessage = '';
                    if (isset($response['statusmessage'])) {
                        $responseStatusMessage = $response['statusmessage'];
                    }

                    $adb->pquery('UPDATE vtiger_smsnotifier_status SET status=?, statusmessage=?, needlookup=? WHERE smsmessageid = ?', [$responseStatus, $responseStatusMessage, $needlookup, $messageid]);
                    $resultrow['status'] = $responseStatus;
                    $resultrow['statusmessage'] = $responseStatusMessage;
                    $resultrow['needlookup'] = $needlookup;
                }
                $results[] = $resultrow;
            }
        }

        return $results;
    }

    public static function fireSendSMS($message, $tonumbers)
    {
        global $log;
        $provider = SMSNotifierManager::getActiveProviderInstance();
        if ($provider) {
            return $provider->send($message, $tonumbers);
        }
    }

    public static function getSMSStatusInfo($record)
    {
        global $adb;
        $results = [];
        $qresult = $adb->pquery('SELECT * FROM vtiger_smsnotifier_status WHERE smsnotifierid=?', [$record]);
        if ($qresult && $adb->num_rows($qresult)) {
            while ($resultrow = $adb->fetch_array($qresult)) {
                $results[] = $resultrow;
            }
        }

        return $results;
    }
}

class SMSNotifierManager
{
    /** Server configuration management */
    public static function listAvailableProviders()
    {
        return SMSProvider::listAll();
    }

    public static function getActiveProviderInstance()
    {
        global $adb;
        $result = $adb->pquery('SELECT * FROM vtiger_smsnotifier_servers WHERE isactive = 1 LIMIT 1', []);
        if ($result && $adb->num_rows($result)) {
            $resultrow = $adb->fetch_array($result);
            $provider = SMSProvider::getInstance($resultrow['providertype']);
            $parameters = [];
            if (!empty($resultrow['parameters'])) {
                $parameters = Zend_Json::decode(decode_html($resultrow['parameters']));
            }
            foreach ($parameters as $k => $v) {
                $provider->setParameter($k, $v);
            }
            $password = Vtiger_Functions::fromProtectedText($resultrow['password']);
            $provider->setAuthParameters($resultrow['username'], $password);

            return $provider;
        }

        return false;
    }

    public static function listConfiguredServer($id)
    {
        global $adb;
        $result = $adb->pquery('SELECT * FROM vtiger_smsnotifier_servers WHERE id=?', [$id]);
        if ($result) {
            return $adb->fetch_row($result);
        }

        return false;
    }

    public static function listConfiguredServers()
    {
        global $adb;
        $result = $adb->pquery('SELECT * FROM vtiger_smsnotifier_servers', []);
        $servers = [];
        if ($result) {
            while ($resultrow = $adb->fetch_row($result)) {
                $servers[] = $resultrow;
            }
        }

        return $servers;
    }

    public static function updateConfiguredServer($id, $frmvalues)
    {
        global $adb;
        $providertype = vtlib_purify($frmvalues['smsserver_provider']);
        $username     = vtlib_purify($frmvalues['smsserver_username']);
        $password     = vtlib_purify($frmvalues['smsserver_password']);
        $isactive     = vtlib_purify($frmvalues['smsserver_isactive']);

        $provider = SMSProvider::getInstance($providertype);

        $parameters = '';
        if ($provider) {
            $providerParameters = $provider->getRequiredParams();
            $inputServerParams = [];
            foreach ($providerParameters as $k => $v) {
                $lookupkey = "smsserverparam_{$providertype}_{$v}";
                if (isset($frmvalues[$lookupkey])) {
                    $inputServerParams[$v] = vtlib_purify($frmvalues[$lookupkey]);
                }
            }
            $parameters = Zend_Json::encode($inputServerParams);
        }

        if (empty($id)) {
            $adb->pquery(
                'INSERT INTO vtiger_smsnotifier_servers (providertype,username,password,isactive,parameters) VALUES(?,?,?,?,?)',
                [$providertype, $username, $password, $isactive, $parameters],
            );
        } else {
            $adb->pquery(
                'UPDATE vtiger_smsnotifier_servers SET username=?, password=?, isactive=?, providertype=?, parameters=? WHERE id=?',
                [$username, $password, $isactive, $providertype, $parameters, $id],
            );
        }
    }

    public static function deleteConfiguredServer($id)
    {
        global $adb;
        $adb->pquery('DELETE FROM vtiger_smsnotifier_servers WHERE id=?', [$id]);
    }
}
