<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

vimport('~~/modules/WSAPP/synclib/connectors/TargetConnector.php');
require_once 'vtlib/Vtiger/Net/Client.php';

class Google_Contacts_Connector extends WSAPP_TargetConnector
{
    protected $apiConnection;

    protected $totalRecords;

    protected $createdRecords;

    protected $maxResults = 100;
    public const CONTACTS_URI = 'https://people.googleapis.com/v1/people/me/connections';
    public const PEOPLE_URI = 'https://people.googleapis.com/v1/';
    public const CONTACTS_GROUP_URI = 'https://people.googleapis.com/v1/contactGroups';
    public const CONTACTS_BATCH_CREATE_URI = 'https://people.googleapis.com/v1/people:batchCreateContacts';
    public const CONTACTS_BATCH_UPDATE_URI = 'https://people.googleapis.com/v1/people:batchUpdateContacts';
    public const CONTACTS_BATCH_DELETE_URI = 'https://people.googleapis.com/v1/people:batchDeleteContacts';
    public const USER_PROFILE_INFO = 'https://www.googleapis.com/oauth2/v1/userinfo';

    protected $apiVersion = '3.0';

    private $groups;

    private $selectedGroup;

    private $fieldMapping;

    private $maxBatchSize = 100;

    protected $fields = [
        'salutationtype' => [
            'name' => 'gd:namePrefix',
        ],
        'firstname' => [
            'name' => 'gd:givenName',
        ],
        'lastname' => [
            'name' => 'gd:familyName',
        ],
        'title' => [
            'name' => 'gd:orgTitle',
        ],
        'organizationname' => [
            'name' => 'gd:orgName',
        ],
        'birthday' => [
            'name' => 'gContact:birthday',
        ],
        'email' => [
            'name' => 'gd:email',
            'types' => ['home', 'work', 'custom'],
        ],
        'phone' => [
            'name' => 'gd:phoneNumber',
            'types' => ['mobile', 'home', 'work', 'main', 'work_fax', 'home_fax', 'pager', 'custom'],
        ],
        'address' => [
            'name' => 'gd:structuredPostalAddress',
            'types' => ['home', 'work', 'custom'],
        ],
        'date' => [
            'name' => 'gContact:event',
            'types' => ['anniversary', 'custom'],
        ],

        'custom' => [
            'name' => 'gContact:userDefinedField',
        ],

    ];

    public function __construct($oauth2Connection)
    {
        $this->apiConnection = $oauth2Connection;
    }

    /**
     * Get the name of the Google Connector.
     * @return string
     */
    public function getName()
    {
        return 'GoogleContacts';
    }

    /**
     * Function to get Fields.
     * @return <Array>
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Function to get the mapped value.
     * @param <Array> $valueSet
     * @param <Array> $mapping
     * @return <Mixed>
     */
    public function getMappedValue($valueSet, $mapping)
    {
        $key = $mapping['google_field_type'];
        if ($key == 'custom') {
            $key = $mapping['google_custom_label'];
        }

        return $valueSet[decode_html($key)];
    }

    /**
     * Function to get field value of google field.
     * @param <Array> $googleFieldDetails
     * @param <Google_Contacts_Model> $user
     * @return <Mixed>
     */
    public function getGoogleFieldValue($googleFieldDetails, $googleRecord, $user)
    {
        $googleFieldValue = '';
        switch ($googleFieldDetails['google_field_name']) {
            case 'gd:namePrefix':
                $googleFieldValue = $googleRecord->getNamePrefix();
                break;
            case 'gd:givenName':
                $googleFieldValue = $googleRecord->getFirstName();
                break;
            case 'gd:familyName':
                $googleFieldValue = $googleRecord->getLastName();
                break;
            case 'gd:orgTitle':
                $googleFieldValue = $googleRecord->getTitle();
                break;
            case 'gd:orgName':
                $googleFieldValue = $googleRecord->getAccountName($user->id);
                break;
            case 'gContact:birthday':
                $googleFieldValue = $googleRecord->getBirthday();
                break;
            case 'gd:email':
                $emails = $googleRecord->getEmails();
                $googleFieldValue = $this->getMappedValue($emails, $googleFieldDetails);
                break;
            case 'gd:phoneNumber':
                $phones = $googleRecord->getPhones();
                $googleFieldValue = $this->getMappedValue($phones, $googleFieldDetails);
                break;
            case 'gd:structuredPostalAddress':
                $addresses = $googleRecord->getAddresses();
                $googleFieldValue = $this->getMappedValue($addresses, $googleFieldDetails);
                break;

            case 'gContact:userDefinedField':
                $userDefinedFields = $googleRecord->getUserDefineFieldsValues();
                $googleFieldValue = $this->getMappedValue($userDefinedFields, $googleFieldDetails);
                break;

        }

        return $googleFieldValue;
    }

    /**
     * Tarsform Google Records to Vtiger Records.
     * @param <array> $targetRecords
     * @return <array> tranformed Google Records
     */
    public function transformToSourceRecord($targetRecords, $user = false)
    {
        $entity = [];
        $contacts = [];

        if (!isset($this->fieldMapping)) {
            $this->fieldMapping = Google_Utils_Helper::getFieldMappingForUser($user);
        }

        foreach ($targetRecords as $googleRecord) {
            if ($googleRecord->getMode() != WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) {
                if (!$user) {
                    $user = Users_Record_Model::getCurrentUserModel();
                }

                $entity = Vtiger_Functions::getMandatoryReferenceFields('Contacts');
                $entity['assigned_user_id'] = vtws_getWebserviceEntityId('Users', $user->id);

                foreach ($this->fieldMapping as $vtFieldName => $googleFieldDetails) {
                    $googleFieldValue = $this->getGoogleFieldValue($googleFieldDetails, $googleRecord, $user);
                    if ($vtFieldName == 'mailingaddress') {
                        $address = $googleFieldValue;
                        $entity['mailingstreet'] = $address['street'];
                        $entity['mailingpobox'] = $address['pobox'];
                        $entity['mailingcity'] = $address['city'];
                        $entity['mailingstate'] = $address['region'];
                        $entity['mailingzip'] = $address['postcode'];
                        $entity['mailingcountry'] = $address['country'];
                        if (empty($entity['mailingstreet'])) {
                            $entity['mailingstreet'] = $address['formattedAddress'];
                        }
                    } elseif ($vtFieldName == 'otheraddress') {
                        $address = $googleFieldValue;
                        $entity['otherstreet'] = $address['street'];
                        $entity['otherpobox'] = $address['pobox'];
                        $entity['othercity'] = $address['city'];
                        $entity['otherstate'] = $address['region'];
                        $entity['otherzip'] = $address['postcode'];
                        $entity['othercountry'] = $address['country'];
                        if (empty($entity['otherstreet'])) {
                            $entity['otherstreet'] = $address['formattedAddress'];
                        }
                    } else {
                        $entity[$vtFieldName] = $googleFieldValue;
                    }
                }

                if (empty($entity['lastname'])) {
                    if (!empty($entity['firstname'])) {
                        $entity['lastname'] = $entity['firstname'];
                    } elseif (empty($entity['firstname']) && !empty($entity['email'])) {
                        $entity['lastname'] = $entity['email'];
                    } elseif (!empty($entity['mobile']) || !empty($entity['mailingstreet'])) {
                        $entity['lastname'] = 'Google Contact';
                    } else {
                        continue;
                    }
                }
            }
            $contact = $this->getSynchronizeController()->getSourceRecordModel($entity);

            $contact = $this->performBasicTransformations($googleRecord, $contact);
            $contact = $this->performBasicTransformationsToSourceRecords($contact, $googleRecord);
            $contacts[] = $contact;
        }

        return $contacts;
    }

    /**
     * Pull the contacts from google.
     * @param <object> $SyncState
     * @return <array> google Records
     */
    public function pull($SyncState, $user = false)
    {
        return $this->getContacts($SyncState, $user);
    }

    /**
     * Helper to send http request using NetClient.
     * @param <String> $url
     * @param <Array> $headers
     * @param <Array> $params
     * @param <String> $method
     * @return <Mixed>
     */
    protected function fireRequest($url, $headers, $params = [], $method = 'POST')
    {
        $httpClient = new Vtiger_Net_Client($url);
        if (php7_count($headers)) {
            $httpClient->setHeaders($headers);
        }
        switch ($method) {
            case 'POST':
                $response = $httpClient->doPost($params);
                break;
            case 'GET':
                $response = $httpClient->doGet($params);
                break;
        }

        return $response;
    }

    public function fetchContactsFeed($query)
    {
        if ($this->apiConnection->isTokenExpired()) {
            $this->apiConnection->refreshToken();
        }
        $headers = [
            'Authorization' => $this->apiConnection->token['access_token']['token_type'] . ' '
                               . $this->apiConnection->token['access_token']['access_token'],
        ];
        $response = $this->fireRequest(self::CONTACTS_URI, $headers, $query, 'GET');

        return $response;
    }

    public function getContactListFeed($query)
    {
        $feed = $this->fetchContactsFeed($query);
        $decoded_feed = json_decode($feed, true);

        return $decoded_feed;
    }

    public function googleFormat($date)
    {
        return str_replace(' ', 'T', $date);
    }

    /**
     * Pull the contacts from google.
     * @param <object> $SyncState
     * @return <array> google Records
     */
    public function getContacts($SyncState, $user = false)
    {
        if (!$user) {
            $user = Users_Record_Model::getCurrentUserModel();
        }
        $query = [
            'pageSize' => $this->maxResults,
            'requestSyncToken' => true,
            'sortOrder' => 'LAST_MODIFIED_ASCENDING',
            'personFields' => 'addresses,birthdays,emailAddresses,memberships,names,organizations,phoneNumbers,userDefined,metadata',
        ];
        $contactRecords = $feed = [];

        do {
            if ($feed['nextPageToken']) {
                $query['pageToken'] = $feed['nextPageToken'];
            }

            if ($SyncState->getSyncToken()) {
                $query['syncToken'] = $SyncState->getSyncToken();
            }

            $feed = $this->getContactListFeed($query);

            if ($feed['error']) {
                $SyncState->setSyncToken('');
                $this->updateSyncState($SyncState);
                unset($query['syncToken']);
                $feed = $this->getContactListFeed($query);
            }

            if ($feed['connections']) {
                $contactRecords = array_merge($contactRecords, $feed['connections']);
            }

            if ($feed['nextSyncToken']) {
                $SyncState->setSyncToken($feed['nextSyncToken']);
                $this->updateSyncState($SyncState);
            }

        } while ($feed['nextPageToken']);

        if (count($contactRecords)) {
            if (Google_Utils_Helper::getSyncTime('Contacts', $user)) {
                $preModifiedTime = Google_Utils_Helper::getSyncTime('Contacts', $user);
            }

            if (!isset($this->selectedGroup)) {
                $this->selectedGroup = Google_Utils_Helper::getSelectedContactGroupForUser($user);
            }

            if ($this->selectedGroup != '' && $this->selectedGroup != 'all') {
                if ($this->selectedGroup == 'none') {
                    return [];
                }
                if (!isset($this->groups)) {
                    $this->groups = $this->pullGroups(true);
                }
                if (in_array($this->selectedGroup, $this->groups['entry'])) {
                    $group = 'contactGroups/' . $this->selectedGroup;
                }
            }
            $lastEntry = end($contactRecords);
            $maxModifiedTime = date('Y-m-d H:i:s', strtotime(Google_Contacts_Model::vtigerFormat($lastEntry['metadata']['sources'][0]['updateTime'])) + 1);

            $googleRecords = [];
            foreach ($contactRecords as $i => $contact) {
                $updateTime = date('Y-m-d H:i:s', strtotime(Google_Contacts_Model::vtigerFormat($contact['metadata']['sources'][0]['updateTime'])) + 1);
                if (strtotime($updateTime) >= strtotime($preModifiedTime) || $contact['metadata']['deleted']) {
                    if ($group && $contact['memberships'] && $group != $contact['memberships'][0]['contactGroupMembership']['contactGroupResourceName']) {
                        continue;
                    }

                    $recordModel = Google_Contacts_Model::getInstanceFromValues(['entity' => $contact]);
                    $deleted = false;
                    if ($contact['metadata']['deleted']) {
                        $deleted = true;
                    }
                    if (!$deleted) {
                        $recordModel->setType($this->getSynchronizeController()->getSourceType())->setMode(WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE);
                    } else {
                        $recordModel->setType($this->getSynchronizeController()->getSourceType())->setMode(WSAPP_SyncRecordModel::WSAPP_DELETE_MODE);
                    }
                    $googleRecords[$contact['resourceName']] = $recordModel;
                }
            }
            $this->createdRecords = count($googleRecords);
            if (isset($maxModifiedTime)) {
                Google_Utils_Helper::updateSyncTime('Contacts', $maxModifiedTime, $user);
            } else {
                Google_Utils_Helper::updateSyncTime('Contacts', false, $user);
            }

        }

        return $googleRecords;
    }

    /**
     * Function to send a batch request.
     * @param <String> <Xml> $batchFeed
     * @return <Mixed>
     */
    protected function sendBatchRequest($batchFeed, $url)
    {
        if ($this->apiConnection->isTokenExpired()) {
            $this->apiConnection->refreshToken();
        }
        $headers = [
            'Authorization' => $this->apiConnection->token['access_token']['token_type'] . ' '
                               . $this->apiConnection->token['access_token']['access_token'],
            'Content-Type' => 'application/json',
        ];
        $response = $this->fireRequest($url, $headers, json_encode($batchFeed));

        return $response;
    }

    public function mbEncode($str)
    {
        global $default_charset;
        $convmap = [0x0_80, 0xFF_FF, 0, 0xFF_FF];

        return mb_encode_numericentity(htmlspecialchars($str), $convmap, $default_charset);
    }

    /**
     * Function to add detail to entry element.
     * @param <SimpleXMLElement> $entry
     * @param <Google_Contacts_Model> $entity
     * @param <Users_Record_Model> $user
     */
    protected function getPersonDetails($resourceName)
    {
        if ($this->apiConnection->isTokenExpired()) {
            $this->apiConnection->refreshToken();
        }
        $headers = [
            'Authorization' => $this->apiConnection->token['access_token']['token_type'] . ' '
                               . $this->apiConnection->token['access_token']['access_token'],
        ];

        $query = ['personFields' => 'metadata,memberships'];
        $response = $this->fireRequest(self::PEOPLE_URI . $resourceName, $headers, $query, 'GET');
        if ($response) {
            $response = json_decode($response, true);
        }

        return $response;

    }

    /**
     * Function to add update entry to the atomfeed.
     * @param <SimpleXMLElement> $feed
     * @param <Google_Contacts_Model> $entity
     * @param <Users_Record_Model> $user
     */
    protected function addUpdateContactEntry($entity, $user)
    {
        $baseEntryId = $entryId = $entity->get('_id');
        if (strpos($entryId, '/base/') !== false) {
            $entryId = explode('/base/', $entryId);
            $entryId = 'people/' . $entryId[1];
        } else {
            $entryId = $baseEntryId;
        }

        $personData = $this->getPersonDetails($entryId);

        if (!$user) {
            $user = Users_Record_Model::getCurrentUserModel();
        }

        if (!isset($this->selectedGroup)) {
            $this->selectedGroup = Google_Utils_Helper::getSelectedContactGroupForUser($user);
        }
        if ($personData['memberships'][0]['contactGroupMembership']['contactGroupResourceName']) {
            $groupId = $personData['memberships'][0]['contactGroupMembership']['contactGroupResourceName'];
        } elseif ($this->selectedGroup != '' && $this->selectedGroup != 'all') {
            $groupId = 'contactGroups/' . $this->selectedGroup;
        }

        $data['contactPerson'] = $this->getContactEntry($entity, $user);

        if ($groupId) {
            $data['contactPerson']['memberships'][0]['contactGroupMembership'] = ['contactGroupResourceName' => $groupId];
        } else {
            $data['contactPerson']['memberships'][0]['contactGroupMembership'] = $personData['memberships'][0]['contactGroupMembership'];
        }
        $data['contactPerson']['resourceName'] = $entryId;

        $data['contactPerson']['etag'] = $personData['etag'];


        return $data;
    }

    /**
     * Function to add delete contact entry to atom feed.
     * @param <SimpleXMLElement> $feed
     * @param <Google_Contacts_Model> $entity
     */
    protected function addDeleteContactEntry($entity)
    {
        $baseEntryId = $entryId = $entity->get('_id');

        if (strpos($entryId, '/base/') !== false) {
            $entryId = explode('/base/', $entryId);
            $entryId = 'people/' . $entryId[1];
        } else {
            $entryId = $baseEntryId;
        }

        return $entryId;
    }

    /**
     * Function to add create entry to the atomfeed.
     * @param <SimpleXMLElement> $feed
     * @param <Google_Contacts_Model> $entity
     * @param <Users_Record_Model> $user
     */
    protected function addCreateContactEntry($entity, $user)
    {

        if (!$user) {
            $user = Users_Record_Model::getCurrentUserModel();
        }

        if (!isset($this->selectedGroup)) {
            $this->selectedGroup = Google_Utils_Helper::getSelectedContactGroupForUser($user);
        }

        if ($this->selectedGroup != '' && $this->selectedGroup != 'all') {
            $groupId = 'contactGroups/' . $this->selectedGroup;
        }
        $data['contactPerson'] = $this->getContactEntry($entity, $user);

        /**
         * Function to add Retreive entry to atomfeed.
         * @param <SimpleXMLElement> $feed
         * @param <Google_Contacts_Model> $entity
         * @param <Users_Record_Model> $user
         */
        if ($groupId) {
            $data['contactPerson']['memberships'][0]['contactGroupMembership'] = ['contactGroupResourceName' => $groupId];
        }

        return $data;
    }

    /**
     * Function to get GoogleContacts-ContactsGroup map for the supplied records.
     * @global  $default_charset
     * @param <Array> $records
     * @param <Users_Record_Model> $user
     * @return <Array>
     */
    protected function getContactEntry($entity, $user)
    {
        if ($entity->get('salutationtype')) {
            $data['names'][0]['honorificPrefix'] = $entity->get('salutationtype');
        }
        if ($entity->get('firstname')) {
            $data['names'][0]['givenName'] = $entity->get('firstname');
        }
        if ($entity->get('lastname')) {
            $data['names'][0]['familyName'] = $entity->get('lastname');
        }

        if ($entity->get('account_id') || $entity->get('title')) {
            if ($entity->get('account_id')) {
                $data['organizations'][0]['name'] = $entity->get('account_id');
            }
            if ($entity->get('title')) {
                $data['organizations'][0]['title'] = $entity->get('title');
            }
        }

        if (!isset($this->fieldMapping)) {
            $this->fieldMapping = Google_Utils_Helper::getFieldMappingForUser($user);
        }

        $contacts_module = Vtiger_Module_Model::getInstance('Contacts');
        foreach ($this->fieldMapping as $vtFieldName => $googleFieldDetails) {
            if (in_array($googleFieldDetails['google_field_name'], ['gd:givenName', 'gd:familyName', 'gd:orgTitle', 'gd:orgName', 'gd:namePrefix'])) {
                continue;
            }

            switch ($googleFieldDetails['google_field_name']) {
                case 'gd:email':
                    if ($entity->get($vtFieldName)) {
                        if ($googleFieldDetails['google_field_type'] == 'custom') {
                            $type = $this->mbEncode(decode_html($googleFieldDetails['google_custom_label']));
                        } else {
                            $type = $googleFieldDetails['google_field_type'];
                        }
                        $data['emailAddresses'][] = ['value' => $entity->get($vtFieldName), 'type' => $type];
                    }
                    break;
                case 'gContact:birthday':
                    if ($entity->get('birthday')) {
                        $date = $entity->get('birthday');
                        $date = explode('-', $date);
                        $data['birthdays'][] = ['date' => ['year' => $date[0], 'month' => $date[1], 'day' => $date[2]]];
                    }
                    break;
                case 'gd:phoneNumber':
                    if ($entity->get($vtFieldName)) {
                        if ($googleFieldDetails['google_field_type'] == 'custom') {
                            $type = $this->mbEncode(decode_html($googleFieldDetails['google_custom_label']));
                        } else {
                            $type = $googleFieldDetails['google_field_type'];
                        }

                        $data['phoneNumbers'][] = ['type' => $type, 'value' => $entity->get($vtFieldName)];
                    }
                    break;
                case 'gd:structuredPostalAddress':
                    if ($vtFieldName == 'mailingaddress') {
                        if ($entity->get('mailingstreet') || $entity->get('mailingpobox') || $entity->get('mailingzip')
                                || $entity->get('mailingcity') || $entity->get('mailingstate') || $entity->get('mailingcountry')) {
                            if ($googleFieldDetails['google_field_type'] == 'custom') {
                                $type = $this->mbEncode(decode_html($googleFieldDetails['google_custom_label']));
                            } else {
                                $type = $googleFieldDetails['google_field_type'];
                            }

                            $address = [];
                            $address['type'] = $type;

                            if ($entity->get('mailingstreet')) {
                                $address['streetAddress'] = $entity->get('mailingstreet');
                            }
                            if ($entity->get('mailingpobox')) {
                                $address['poBox'] = $entity->get('mailingpobox');
                            }
                            if ($entity->get('mailingzip')) {
                                $address['postalCode'] = $entity->get('mailingzip');
                            }
                            if ($entity->get('mailingcity')) {
                                $address['city'] = $entity->get('mailingcity');
                            }
                            if ($entity->get('mailingstate')) {
                                $address['region'] = $entity->get('mailingstate');
                            }
                            if ($entity->get('mailingcountry')) {
                                $address['country'] = $entity->get('mailingcountry');
                            }

                            $data['addresses'][] = $address;
                        }
                    } else {
                        if ($entity->get('otherstreet') || $entity->get('otherpobox') || $entity->get('otherzip')
                                || $entity->get('othercity') || $entity->get('otherstate') || $entity->get('othercountry')) {

                            if ($googleFieldDetails['google_field_type'] == 'custom') {
                                $type = $this->mbEncode(decode_html($googleFieldDetails['google_custom_label']));
                            } else {
                                $type = $googleFieldDetails['google_field_type'];
                            }

                            $address = [];
                            $address['type'] = $type;

                            if ($entity->get('otherstreet')) {
                                $address['streetAddress'] = $entity->get('otherstreet');
                            }
                            if ($entity->get('otherpobox')) {
                                $address['poBox'] = $entity->get('otherpobox');
                            }
                            if ($entity->get('otherzip')) {
                                $address['postalCode'] = $entity->get('otherzip');
                            }
                            if ($entity->get('othercity')) {
                                $address['city'] = $entity->get('othercity');
                            }
                            if ($entity->get('otherstate')) {
                                $address['region'] = $entity->get('otherstate');
                            }
                            if ($entity->get('othercountry')) {
                                $address['country'] = $entity->get('othercountry');
                            }

                            $data['addresses'][] = $address;

                        }

                    }
                    break;
                case 'gContact:userDefinedField':
                    if ($entity->get($vtFieldName) && $googleFieldDetails['google_custom_label']) {
                        $fieldModel = Vtiger_Field_Model::getInstance($vtFieldName, $contacts_module);
                        $data['userDefined'][] = ['key' => $this->mbEncode(decode_html($googleFieldDetails['google_custom_label'])),
                            'value' => $this->mbEncode($entity->get($vtFieldName))];
                    }
                    break;

            }
        }

        return $data;
    }

    /**
     * Function to push records in a batch
     * https://developers.google.com/google-apps/contacts/v3/index#batch_operations.
     * @global <String> $default_charset
     * @param <Array> $records
     * @param <Users_Record_Model> $user
     * @return <Array> - pushedRecords
     */
    protected function pushChunk($records, $user)
    {
        global $default_charset;
        $createdContacts = $updatedContacts = $deletedContacts = [];
        foreach ($records as $record) {
            $entity = $record->get('entity');

            try {
                if ($record->getMode() == WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE) {
                    $personData = $this->addUpdateContactEntry($entity, $user);
                    $resourceName = $personData['contactPerson']['resourceName'];
                    $updatedContacts[$resourceName] = $personData['contactPerson'];
                } elseif ($record->getMode() == WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) {
                    $deletedContacts[] = $this->addDeleteContactEntry($entity);
                } else {
                    $createdContacts[] = $this->addCreateContactEntry($entity, $user);
                }
            } catch (Exception $e) {
                continue;
            }
        }

        if (count($createdContacts)) {
            $url = self::CONTACTS_BATCH_CREATE_URI;
            $payload = ['contacts' => $createdContacts, 'readMask' => 'metadata'];
            $response = $this->sendBatchRequest($payload, $url);
            $response = json_decode($response, true);

            if ($response['createdPeople']) {
                foreach ($records as $index => $record) {
                    $newEntity = [];
                    $entry = $response['createdPeople'][$index];
                    $newEntityId = $entry['person']['resourceName'];
                    $newEntity['id']['$t'] = $newEntityId;
                    $newEntity['updated']['$t'] = (string) $entry['person']['metadata']['sources'][0]['updateTime'];
                    $record->set('entity', $newEntity);
                }
            } else {
                foreach ($records as $index => $record) {
                    $record->set('entity', []);
                }
            }
        }

        if (count($updatedContacts)) {
            $url = self::CONTACTS_BATCH_UPDATE_URI;
            $payload = ['contacts' => $updatedContacts, 'updateMask' => 'addresses,birthdays,emailAddresses,memberships,names,organizations,phoneNumbers,userDefined', 'readMask' => 'metadata'];

            $response = $this->sendBatchRequest($payload, $url);
            $response = json_decode($response, true);

            if ($response['updateResult']) {
                $response['updateResult'] = array_values($response['updateResult']);
                foreach ($records as $index => $record) {
                    $newEntity = [];
                    $entry = $response['updateResult'][$index];
                    $newEntityId = $entry['person']['resourceName'];
                    $newEntity['id']['$t'] = $newEntityId;
                    $newEntity['updated']['$t'] = (string) $entry['person']['metadata']['sources'][0]['updateTime'];
                    $record->set('entity', $newEntity);
                }
            } else {
                foreach ($records as $index => $record) {
                    $record->set('entity', []);
                }
            }
        }

        if (count($deletedContacts)) {
            $url = self::CONTACTS_BATCH_DELETE_URI;
            $payload = ['resourceNames' => $deletedContacts];
            $response = $this->sendBatchRequest($payload, $url);
            foreach ($records as $index => $record) {
                $record->set('entity', []);
                $newEntity = [];
                $newEntityId = $deletedContacts[$index];
                $newEntity['id']['$t'] = $newEntityId;
                $newEntity['updated']['$t'] = $this->googleFormat(date('Y-m-d H:i:s'));
                $record->set('entity', $newEntity);
            }
        }

        return $records;
    }

    /**
     * Function to push records in batch of maxBatchSize.
     * @param <Array Google_Contacts_Model> $records
     * @param <Users_Record_Model> $user
     * @return <Array> - pushed records
     */
    protected function batchPush($records, $user)
    {
        $chunks = array_chunk($records, $this->maxBatchSize);
        $mergedRecords = [];
        foreach ($chunks as $chunk) {
            $pushedRecords = $this->pushChunk($chunk, $user);
            $mergedRecords = array_merge($mergedRecords, $pushedRecords);
        }

        return $mergedRecords;
    }

    /**
     * Push the vtiger records to google.
     * @param <array> $records vtiger records to be pushed to google
     * @return <array> pushed records
     */
    public function push($records, $user = false)
    {
        if (!$user) {
            $user = Users_Record_Model::getCurrentUserModel();
        }

        if (!isset($this->selectedGroup)) {
            $this->selectedGroup = Google_Utils_Helper::getSelectedContactGroupForUser($user);
        }

        if ($this->selectedGroup != '' && $this->selectedGroup != 'all') {
            if ($this->selectedGroup == 'none') {
                return [];
            }
            if (!isset($this->groups)) {
                $this->groups = $this->pullGroups(true);
            }
            if (!in_array($this->selectedGroup, $this->groups['entry'])) {
                return [];
            }
        }

        $updateRecords = $deleteRecords = $addRecords = [];
        foreach ($records as $record) {
            if ($record->getMode() == WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE) {
                $updateRecords[] = $record;
            } elseif ($record->getMode() == WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) {
                $deleteRecords[] = $record;
            } else {
                $addRecords[] = $record;
            }
        }

        if (php7_count($deleteRecords)) {
            $deletedRecords = $this->batchPush($deleteRecords, $user);
        }

        if (php7_count($updateRecords)) {
            $updatedRecords = $this->batchPush($updateRecords, $user);
        }

        if (php7_count($addRecords)) {
            $addedRecords = $this->batchPush($addRecords, $user);
        }

        $i = $j = $k = 0;
        foreach ($records as $record) {
            if ($record->getMode() == WSAPP_SyncRecordModel::WSAPP_UPDATE_MODE) {
                $uprecord = $updatedRecords[$i++];
                $newEntity = $uprecord->get('entity');
                $record->set('entity', $newEntity);
            } elseif ($record->getMode() == WSAPP_SyncRecordModel::WSAPP_DELETE_MODE) {
                $delrecord = $deletedRecords[$j++];
                $newEntity = $delrecord->get('entity');
                $record->set('entity', $newEntity);
            } else {
                $adrecord = $addedRecords[$k++];
                $newEntity = $adrecord->get('entity');
                $record->set('entity', $newEntity);
            }
        }

        return $records;
    }

    /**
     * Tarsform  Vtiger Records to Google Records.
     * @param <array> $vtContacts
     * @return <array> tranformed vtiger Records
     */
    public function transformToTargetRecord($vtContacts, $user = false)
    {
        $records = [];
        foreach ($vtContacts as $vtContact) {
            $recordModel = Google_Contacts_Model::getInstanceFromValues(['entity' => $vtContact]);
            $recordModel->setType($this->getSynchronizeController()->getSourceType())->setMode($vtContact->getMode())->setSyncIdentificationKey($vtContact->get('_syncidentificationkey'));
            $recordModel = $this->performBasicTransformations($vtContact, $recordModel);
            $recordModel = $this->performBasicTransformationsToTargetRecords($recordModel, $vtContact);
            $records[] = $recordModel;
        }

        return $records;
    }

    /**
     * returns if more records exits or not.
     * @return <boolean> true or false
     */
    public function moreRecordsExits()
    {
        return ($this->totalRecords - $this->createdRecords > 0) ? true : false;
    }

    /**
     * Function to pull contact groups for user.
     * @param <Boolean> $onlyIds
     * @return <Array>
     */
    public function pullGroups($onlyIds = false)
    {
        // max-results: If you want to receive all of the groups, rather than only the default maximum.
        $query = ['pageSize' => 1_000];
        $headers = [
            'Authorization' => $this->apiConnection->token['access_token']['token_type'] . ' '
                               . $this->apiConnection->token['access_token']['access_token'],
        ];
        $response = $this->fireRequest(self::CONTACTS_GROUP_URI, $headers, $query, 'GET');
        $decoded_resp = json_decode($response, true);
        $entries = $decoded_resp['contactGroups'];
        if (is_array($entries)) {
            foreach ($entries as $entry) {
                $resourceName = explode('/', $entry['resourceName']);
                $group = [
                    'id' => $resourceName[1],
                    'title' => $entry['formattedName'],
                ];
                if ($onlyIds) {
                    $group = $group['id'];
                }
                $groups['entry'][] = $group;
            }
        }

        return $groups;
    }

    /**
     * Function to get user profile info.
     * @return <Mixed>
     */
    public function getUserProfileInfo()
    {
        if ($this->apiConnection->isTokenExpired()) {
            $this->apiConnection->refreshToken();
        }
        $headers = [
            'GData-Version' => $this->apiVersion,
            'Authorization' => $this->apiConnection->token['access_token']['token_type'] . ' '
                               . $this->apiConnection->token['access_token']['access_token'],
            'If-Match' => '*',
            'Content-Type' => 'application/json',
        ];
        $response = $this->fireRequest(self::USER_PROFILE_INFO, $headers, [], 'GET');

        return $response;
    }

    public function getSyncState()
    {
        $result = null;
        $db = PearDatabase::getInstance();
        if ($this->getSynchronizeController()->getSyncType() == 'app') {
            $result = $db->pquery('SELECT * FROM vtiger_wsapp_sync_state WHERE name=?', [$this->getName()]);
        } else {
            $result = $db->pquery('SELECT * FROM vtiger_wsapp_sync_state WHERE name=? and userid=?', [$this->getName(), $this->getSynchronizeController()->user->id]); // $this->getSYnchronizeController()->getSyncType();
        }
        if ($db->num_rows($result) <= 0) {
            return parent::getSyncState();
        }
        $rowData = $db->raw_query_result_rowdata($result);
        $stateValues = Zend_Json::decode($rowData['stateencodedvalues']);
        $model = WSAPP_SyncStateModel::getInstanceFromQueryResult($stateValues);

        return $model;
    }

    public function isSyncStateExists()
    {
        $db = PearDatabase::getInstance();
        $result = null;
        if ($this->getSynchronizeController()->getSyncType() == 'app') {
            $result = $db->pquery('SELECT 1 FROM vtiger_wsapp_sync_state where name=?', [$this->getName()]);
        } else {
            $result = $db->pquery('SELECT 1 FROM vtiger_wsapp_sync_state where name=? and userid=?', [$this->getName(), $this->getSynchronizeController()->user->id]);
        }

        return ($db->num_rows($result) > 0) ? true : false;
    }

    public function updateSyncState(WSAPP_SyncStateModel $syncStateModel)
    {
        $db = PearDatabase::getInstance();
        $encodedValues = Zend_Json::encode(['synctrackerid' => $syncStateModel->getSyncTrackerId(), 'synctoken' => $syncStateModel->getSyncToken(), 'more' => $syncStateModel->get('more')]);
        $query = 'INSERT INTO vtiger_wsapp_sync_state(stateencodedvalues,name,userid) VALUES (?,?,?)';
        $parameters = [$encodedValues, $this->getName(), $this->getSynchronizeController()->user->id];
        if ($this->isSyncStateExists()) {
            $query		= '';
            $parameters = [];
            if ($this->getSynchronizeController()->getSyncType() == 'app') {
                $query = 'UPDATE vtiger_wsapp_sync_state SET stateencodedvalues=? where name=?';
                $parameters = [$encodedValues, $this->getName()];
            } else {
                $query = 'UPDATE vtiger_wsapp_sync_state SET stateencodedvalues=? where name=? and userid=?';
                $parameters = [$encodedValues, $this->getName(), $this->getSynchronizeController()->user->id];
            }
        }
        $result = $db->pquery($query, $parameters);
        if ($result) {
            return true;
        }

        return false;
    }
}
