<?php

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * */

include_once dirname(__FILE__) . '/../libraries/NetClient.php';

class Settings_ExtensionStore_ExtnStore_Connector
{
    protected $url;

    protected $auth;

    protected $user_table = 'vtiger_extnstore_users';

    protected $identifier_name = 'extnstore';

    protected function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Function to get connector instance either pro or free version.
     * @staticvar null $singletons
     * @param type $url
     * @return self
     */
    public static function getInstance($url)
    {
        static $singletons = null;
        if ($singletons === null) {
            $singletons = [];
        }
        if (!isset($singletons[$url])) {
            $singletons[$url] = new self($url);
        }

        return $singletons[$url];
    }

    /**
     * Function to intialize basic auth based on data in database or session.
     * @global type $currentModule
     * @return auth
     * @throws Exception
     */
    protected function initializeAuth()
    {
        $db = PearDatabase::getInstance();
        if (!$this->auth) {
            // Quick way to check if entry exists and pull 1st undeleted is
            // to pull only one record by the order on deleted column and
            // evaluating at-least one row exists.
            $authResult = $db->pquery('SELECT * FROM ' . $this->user_table . ' ORDER BY deleted LIMIT 1', []);
            if ($db->num_rows($authResult)) {
                $this->auth = $db->fetch_array($authResult);
                if ($this->auth['deleted'] == 1) {
                    $this->auth = null;
                }
            }
            if (empty($this->auth['password'])) {
                $this->auth['password'] = $_SESSION[$this->identifier_name . '_password'] ?? null;
            }
            if (empty($this->auth['username'])) {
                $this->auth['username'] = $_SESSION[$this->identifier_name . '_username'] ?? null;
            }
            if (empty($this->auth['password']) && (empty($this->auth['username']))) {
                throw new Exception(vtranslate('LBL_USERNAME_AND_PASSWORD_REQUIRED_FOR_AUTHENTICATION'));
            }
        }

        return $this->auth;
    }

    /**
     * Function to perform client request to get response.
     * @param type $uri
     * @param type $method
     * @param type $params
     * @param type $auth
     * @return json response
     * @throws Exception
     */
    protected function api($uri, $method, $params, $auth)
    {
        if ($auth) {
            try {
                $this->initializeAuth();
            } catch (Exception $ex) {
                return ['success' => 'false', 'error' => $ex->getMessage()];
            }
        }

        $fn = ($method == 'GET' || $method == 'DLD') ? 'doGet' : 'doPost';
        if ($method == 'PUT') {
            $fn = 'doPut';
        }
        $client = $this->getNetClientInstance($method, $uri);

        if ($auth && $this->auth) {
            $authParams = $this->prepareAuthParams($this->auth['username'], $this->auth['password']);
            $client->setAuthorization($authParams['username'], $authParams['password']);
        }

        global $application_unique_key;
        if (!$params) {
            $params = [];
        }
        if (!isset($params['uid'])) {
            $params['uid'] = $application_unique_key;
        }

        $content = $client->{$fn}($params);
        $response = $content['response'];
        $status = $content['status'];

        if ($status != 200) {
            throw new Exception($content['errorMessage'] ?? $response);
        }

        if ($method == 'DLD') {
            return $response;
        }
        $json = Zend_Json::decode($response);
        if ($json) {
            if ($json['success']) {
                return $json['result'];
            }

            throw new Exception($json['error']['message']);

        }

        return null;
    }

    /**
     * Function to get net client instance for free version.
     * @param type $method
     * @param type $uri
     * @return Settings_ExtensionStore_NetClient
     */
    protected function getNetClientInstance($method, $uri)
    {
        $clientInstance = new Settings_ExtensionStore_NetClient($method == 'DLD' ? $uri : ($this->url . $uri));

        return $clientInstance;
    }

    /**
     * Function to generate suth params for free version.
     * @param type $username
     * @param type $password
     * @return type array
     */
    protected function prepareAuthParams($username, $password)
    {
        return ['username' => $username,
            'password' => urlencode(Zend_Json::encode(['password' => $password]))];
    }

    /**
     * Function to get session identifier name.
     * @return type string
     */
    public function getSessionIdentifier()
    {
        return $this->identifier_name;
    }

    /**
     * Function to get extension table name.
     * @return type string
     */
    public function getExtensionTable()
    {
        return $this->user_table;
    }

    /**
     * Function to get max created on for promotions.
     */
    public function getMaxCreatedOn($type = 'Extension', $function = null, $field = null)
    {
        $q = ['type' => $type];

        try {
            $response = $this->api('/app/listings', 'GET', $q ? ['q' => Zend_Json::encode($q), 'fn' => $function, 'max' => $field] : null, false);

            return ['success' => true, 'response' => $response];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to get basic listings based on type of listing.
     * @param type $id
     * @param type $type
     * @return type json reponse
     */
    public function getListings($id = null, $type = 'Extension')
    {
        global $vtiger_current_version;
        $q = ['type' => $type, 'vv' => $vtiger_current_version];
        if ($id) {
            $q['id'] = $id;
        }

        try {
            $response = $this->api('/app/listings', 'GET', $q ? ['q' => Zend_Json::encode($q)] : null, false);

            return ['success' => true, 'response' => $response];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to get specified listing based on type of listing.
     * @param type $term
     * @param type $type
     * @return type json response
     */
    public function findListings($term = null, $type = 'Extension')
    {
        $q = ['term' => $term, 'type' => $type];

        try {
            $response = $this->api('/app/searchlistings', 'GET', ['q' => Zend_Json::encode($q)], false);

            return ['success' => true, 'response' => $response];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to download listing.
     * @param type $downloadurl
     * @return type
     */
    public function download($downloadurl)
    {
        try {
            $response = $this->api($downloadurl, 'DLD', null, true);

            return ['success' => true, 'response' => $response];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to get customer reviews of listing.
     * @param type $extensionId
     * @return type json response
     */
    public function getCustomerReviews($extensionId)
    {
        $q = $extensionId ? ['listing' => $extensionId] : null;

        try {
            return $this->api('/app/reviews', 'GET', $q ? ['q' => Zend_Json::encode($q)] : null, false);
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to get author information of listing.
     * @param type $extensionId
     * @return type json response
     */
    public function getListingAuthor($extensionId)
    {
        $q = $extensionId ? ['listing' => $extensionId] : null;

        try {
            return $this->api('/app/listingauthor', 'GET', $q ? ['q' => Zend_Json::encode($q)] : null, false);
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to post review for listing.
     * @param type $listing
     * @param type $comment
     * @param type $rating
     * @return type json response
     */
    public function postReview($listing, $comment, $rating)
    {
        $listing = $listing ? ['listing' => $listing] : null;
        $comment = $comment ? ['comment' => $comment, 'rating' => $rating] : null;

        try {
            $response = $this->api('/customer/reviews', 'POST', $listing ? ['q' => Zend_Json::encode($listing), 'review' => Zend_Json::encode($comment)] : null, true);

            return ['success' => true, 'result' => $response];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to get screen shots of listing.
     * @param type $extensionId
     * @return type json response
     */
    public function getScreenShots($extensionId)
    {
        $q = $extensionId ? ['listing' => $extensionId] : null;

        try {
            return $this->api('/app/listingscreenshots', 'GET', $q ? ['q' => Zend_Json::encode($q)] : null, false);
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to verify purchase of extension.
     * @param $listingName => extension name to verify purchase
     */
    public function verifyPurchase($listingName)
    {
        $q = $listingName ? ['identifier' => $listingName] : null;

        try {
            return $this->api('/customer/mysubscriptions', 'GET', $q ? ['type' => 'verifypurchase', 'q' => Zend_Json::encode($q)] : null, true);
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to retrieve profile of loged in user.
     * @return type
     */
    public function getProfile()
    {
        try {
            return $this->api('/customer/profile', 'GET', '', true);
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to create card details for logged in user in pro version.
     * @param type $number
     * @param type $expmonth
     * @param type $expyear
     * @param type $cvc
     * @return type json response
     */
    public function createCard($number, $expmonth, $expyear, $cvc)
    {
        $cardDetails = ['number' => $number, 'expmonth' => $expmonth, 'expyear' => $expyear, 'cvc' => $cvc];

        try {
            $response = $this->api('/customer/card', 'POST', $cardDetails, true);

            return ['success' => true, 'result' => $response];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to update card details for logged in user.
     * @param type $number
     * @param type $expmonth
     * @param type $expyear
     * @param type $cvc
     * @param type $customerId
     * @return type json response
     */
    public function updateCard($number, $expmonth, $expyear, $cvc, $customerId)
    {
        $cardDetails = ['number' => $number, 'expmonth' => $expmonth, 'expyear' => $expyear, 'cvc' => $cvc, 'id' => $customerId];

        try {
            $response = $this->api('/customer/card', 'PUT', $cardDetails, true);

            return ['success' => true, 'result' => $response];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to get Customer Card Details.
     * @param array $cardId
     * @return type array
     */
    public function getCardDetails($cardId)
    {
        $cardId = ['id' => $cardId];

        try {
            return $this->api('/customer/card', 'GET', $cardId, true);
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to signup for marketplace.
     * @param type $username
     * @param type $password
     * @param type $confirmPassword
     * @param type $firstName
     * @param type $lastName
     * @param type $companyName
     * @return type json result
     */
    public function signUp($username, $password, $confirmPassword, $firstName, $lastName, $companyName)
    {
        $signupParams = $this->prepareSignUpParams($username, $password, $confirmPassword, $firstName, $lastName, $companyName);

        try {
            $this->auth = $this->api('/app/customer', 'POST', $signupParams, false);
            if ($this->auth) {
                $this->persistLogin($this->auth['email'], md5($this->auth['password']), false);
            }

            return ['success' => true, 'result' => $this->auth];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    /**
     * Function to prepare signup params for signup operation.
     * @param type $username
     * @param type $password
     * @param type $confirmPassword
     * @param type $firstName
     * @param type $lastName
     * @param type $companyName
     * @return type array
     */
    protected function prepareSignUpParams($username, $password, $confirmPassword, $firstName, $lastName, $companyName)
    {
        return ['email' => $username,
            'password' => $password,
            'confirmPassword' => $confirmPassword,
            'firstname' => $firstName,
            'lastname' => $lastName,
            'company' => $companyName];
    }

    /**
     * Function to retrieve persistence status of login.
     * @return bool
     */
    protected function getPersistenceStatus()
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT 1 FROM ' . $this->user_table, []);
        if ($db->num_rows($result)) {
            return true;
        }

        return false;
    }

    /**
     * Function to persist login based on status of $persistLogin.
     * @param type $userName
     * @param type $password
     */
    protected function persistLogin($userName, $password, $rememberPassword)
    {
        $db = PearDatabase::getInstance();
        if ($rememberPassword) {
            $db->pquery('DELETE FROM ' . $this->user_table, []);
            $db->pquery('INSERT INTO ' . $this->user_table . '(username,password, createdon) VALUES(?,?,?)', [$userName, $password, date('Y-m-d H:i:s')]);
        } else {
            $persistanceStatus = $this->getPersistenceStatus();
            if (!$persistanceStatus) {
                $db->pquery('INSERT INTO ' . $this->user_table . ' (username, createdon) VALUES(?,?)', [$userName, date('Y-m-d H:i:s')]);
            }
            $_SESSION[$this->identifier_name . '_username'] = $userName;
            $_SESSION[$this->identifier_name . '_password'] = $password;
        }
    }

    /**
     * Function to login to market place and persist data based on $persistLogin.
     * @param type $userName
     * @param type $password
     * @param type $persistLogin
     * @return type
     */
    public function login($userName, $password, $persistLogin)
    {
        try {
            /** set user entered password to session as we are using to set auth
             * header initializeAuth() function which we are depending on session
             * password if password not exists in db.
             */
            $_SESSION[$this->identifier_name . '_username'] = $userName;
            $_SESSION[$this->identifier_name . '_password'] = $password;
            $this->auth = $this->api('/customer/profile', 'GET', '', true);
            if ($this->auth) {
                $this->persistLogin($this->auth['email'], $this->auth['password'], $persistLogin);
            }

            return ['success' => true, 'result' => $this->auth];
        } catch (Exception $ex) {
            // Should flush credentials from session if login fails
            $_SESSION[$this->identifier_name . '_username'] = null;
            $_SESSION[$this->identifier_name . '_password'] = null;
            $exceptionMessage = $ex->getMessage();
            if (empty($exceptionMessage)) {
                $error = vtranslate('LBL_UNAUTHORIZED', 'Settings:ExtensionStore');
            } else {
                $error = $exceptionMessage;
            }

            return ['success' => false, 'error' => $error];
        }
    }

    public function getCustomerDetails($customerId)
    {
        try {
            $response = $this->api("/app/customer?id={$customerId}", 'GET', '', true);

            return ['success' => true, 'result' => $response];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    public function getNews()
    {
        try {
            $response = $this->api('/app/news', 'GET', '', false);

            return ['success' => true, 'result' => $response];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }

    public function forgotPassword($emailAddress)
    {
        $params = ['email' => $emailAddress];

        try {
            $response = $this->api('/app/forgotpassword', 'POST', $params, false);

            return ['success' => true, 'result' => $response];
        } catch (Exception $ex) {
            return ['success' => false, 'error' => $ex->getMessage()];
        }
    }
}
