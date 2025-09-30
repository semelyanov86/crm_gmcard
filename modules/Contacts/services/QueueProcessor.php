<?php

use CRMEntity;
include_once 'include/Webservices/Utils.php';
include_once 'include/Webservices/ModuleTypes.php';
include_once 'include/Webservices/Create.php';
include_once 'include/Webservices/Revise.php';
include_once 'include/Webservices/Retrieve.php';

class Contacts_QueueProcessor_Service extends Vtiger_QueueProcessor_Service
{
    public function handle(): bool
    {
        global $log;
        $current_user = CRMEntity::getInstance('Users');
        $current_user->retrieveCurrentUserInfoFromFile(1);
        $contact = null;

        if ($this->data['crmid'] != '0') {
            $wsid = vtws_getWebserviceEntityId('Contacts', $this->data['crmid']);
            /** @var array $current_user */
            $contact = vtws_retrieve($wsid, $current_user);
        }
        $data = $this->data;
        if (!$contact) {
            $data['assigned_user_id'] = '19x1';
            $data['firstname'] = $data['name'] ?? '';
            $data['lastname'] = $data['last_name'] ?? ($data['name'] ?? '');
            try {
                $res = vtws_create('Contacts', $data, $current_user);
            } catch (\WebServiceException $e) {
                $log->error($e->getMessage());
            }
        } else {
            $data['id'] = $wsid;
            try {
                $res = vtws_revise($data, $current_user);
            } catch (\WebServiceException $e) {
                $log->error($e->getMessage());
            }
        }
        return true;
    }
}