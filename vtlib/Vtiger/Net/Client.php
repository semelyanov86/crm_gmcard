<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'vtlib/thirdparty/network/Request.php';

/**
 * Provides API to work with HTTP Connection.
 */
class Vtiger_Net_Client
{
    public $client;

    public $url;

    public $response;

    /**
     * Constructor.
     * @param string URL of the site
     * Example:
     * $client = new Vtiger_New_Client('http://www.vtiger.com');
     */
    public function __construct($url)
    {
        $this->setURL($url);
    }

    /**
     * Set another url for this instance.
     * @param string URL to use go forward
     */
    public function setURL($url)
    {
        $this->url = $url;
        $this->client = new HTTP_Request();
        $this->response = false;
        $this->setDefaultHeaders();
    }

    public function setDefaultHeaders()
    {
        $headers = [];
        if (isset($_SERVER)) {
            global $site_URL;
            $headers['referer'] = $_SERVER['HTTP_REFERER'] ?? ($site_URL . '?noreferer');

            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $headers['user-agent'] = $_SERVER['HTTP_USER_AGENT'];
            }

        } else {
            global $site_URL;
            $headers['referer'] = ($site_URL . '?noreferer');
        }

        $this->setHeaders($headers);
    }

    /**
     * Set custom HTTP Headers.
     * @param Map HTTP Header and Value Pairs
     */
    public function setHeaders($values)
    {
        foreach ($values as $key => $value) {
            $this->client->addHeader($key, $value);
        }
    }

    /**
     * Perform a GET request.
     * @param Map key-value pair or false
     * @param int timeout value
     */
    public function doGet($params = false, $timeout = null)
    {
        if ($timeout) {
            $this->client->_timeout = $timeout;
        }
        $this->client->setURL($this->url);
        $this->client->setMethod(HTTP_REQUEST_METHOD_GET);

        if ($params) {
            foreach ($params as $key => $value) {
                $this->client->addQueryString($key, $value);
            }
        }
        $this->response = $this->client->sendRequest();

        $content = false;
        if (!$this->wasError()) {
            $content = $this->client->getResponseBody();
        }
        $this->disconnect();

        return $content;
    }

    /**
     * Perform a POST request.
     * @param Map key-value pair or false
     * @param int timeout value
     */
    public function doPost($params = false, $timeout = null)
    {
        if ($timeout) {
            $this->client->_timeout = $timeout;
        }
        $this->client->setURL($this->url);
        $this->client->setMethod(HTTP_REQUEST_METHOD_POST);

        if ($params) {
            if (is_string($params)) {
                $this->client->addRawPostData($params);
            } else {
                foreach ($params as $key => $value) {
                    $this->client->addPostData($key, $value);
                }
            }
        }
        $this->response = $this->client->sendRequest();

        $content = false;
        if (!$this->wasError()) {
            $content = $this->client->getResponseBody();
        }
        $this->disconnect();

        return $content;
    }

    /**
     * Add File to Send with a post.
     * @param string file upload fieldname
     * @param mixed path of file to add
     * @param mixed file content type of file being uploaded(default : application/octet-stream)
     */
    public function addFiles($inputName, $filePath, $fileContentType = 'application/octet-stream')
    {
        $this->client->addFile($inputName, $filePath, $fileContentType);
    }

    /**
     * Did last request resulted in error?
     */
    public function wasError()
    {
        // calling non-static method statically is throwing error while Cron run
        return $this->_isError();
    }

    /**
     * Tell whether a value is a PEAR error.
     * @return type
     */
    public function _isError()
    {
        $pear = new PEAR();

        return $pear->isError($this->response);
    }

    /**
     * Disconnect this instance.
     */
    public function disconnect()
    {
        $this->client->disconnect();
    }
}
