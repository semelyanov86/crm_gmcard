<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

require_once 'libraries/HTTP_Session2/HTTP/Session2.php';
// Later may we can move this to config file.

global $maxWebServiceSessionLifeSpan, $maxWebServiceSessionIdleTime;

$maxWebServiceSessionLifeSpan = 86_400; // Max life span of a session is a day.
$maxWebServiceSessionIdleTime = 1_800; // Max life span session should be kept alive after the last transaction.

// Till Here.

class SessionManager
{
    private $maxLife;

    private $idleLife;

    // Note: the url lookup part of http_session will have String null or this be used as id instead of ignoring it.
    // private $sessionName = "sessionName";
    private $sessionVar = '__SessionExists';

    private $error;

    public function __construct()
    {
        global $maxWebServiceSessionLifeSpan, $maxWebServiceSessionIdleTime;

        $now = time();
        $this->maxLife = $now + $maxWebServiceSessionLifeSpan;
        $this->idleLife = $now + $maxWebServiceSessionIdleTime;

        HTTP_Session2::useCookies(false); // disable cookie usage. may this could be moved out constructor?
        // only first invocation of following method, which is setExpire
        // have an effect and any further invocation will be have no effect.
        HTTP_Session2::setExpire($this->maxLife);
        // this method replaces the new with old time if second params is true
        // otherwise it subtracts the time from previous time
        HTTP_Session2::setIdle($this->idleLife, true);
    }

    public function SessionManager()
    {
        // PHP4-style constructor.
        // This will NOT be invoked, unless a sub-class that extends `foo` calls it.
        // In that case, call the new-style constructor to keep compatibility.
        self::__construct();

    }

    public function isValid()
    {

        $valid = true;
        // expired
        if (HTTP_Session2::isExpired()) {
            $valid = false;
            HTTP_Session2::destroy();

            throw new WebServiceException(WebServiceErrorCode::$SESSLIFEOVER, 'Session has life span over please login again');
        }

        // idled
        if (HTTP_Session2::isIdle()) {
            $valid = false;
            HTTP_Session2::destroy();

            throw new WebServiceException(WebServiceErrorCode::$SESSIONIDLE, 'Session has been invalidated to due lack activity');
        }
        // echo "<br>is new: ", HTTP_Session2::isNew();
        // invalid sessionId provided.
        // echo "<br>get: ",$this->get($this->sessionVar);
        if (!$this->get($this->sessionVar) && !HTTP_Session2::isNew()) {
            $valid = false;
            HTTP_Session2::destroy();

            throw new WebServiceException(WebServiceErrorCode::$SESSIONIDINVALID, 'Session Identifier provided is Invalid');
        }

        return $valid;
    }

    public function startSession($sid = null, $adoptSession = false)
    {

        //			if($sid){
        //				HTTP_Session2::id($sid);
        //			}

        if (!$sid || strlen($sid) === 0) {
            $sid = null;
        }

        // session name is used for guessing the session id by http_session so pass null.
        HTTP_Session2::start(null, $sid);

        $newSID = HTTP_Session2::id();

        if (!$sid || $adoptSession == true) {
            $this->set($this->sessionVar, 'true');
        } else {
            if (!$this->get($this->sessionVar)) {
                HTTP_Session2::destroy();

                throw new WebServiceException(WebServiceErrorCode::$SESSIONIDINVALID, 'Session Identifier provided is Invalid');
                $newSID = null;
            }
        }

        if (!$this->isValid()) {
            $newSID = null;
        }
        $sid = $newSID;

        return $sid;

    }

    public function getSessionId()
    {
        return HTTP_Session2::id();
    }

    public function set($var_name, $var_value)
    {
        // TODO test setRef and getRef combination
        // echo "<br>setting name: ",$var_name," :value: ",$var_value;
        HTTP_Session2::set($var_name, $var_value);
    }

    public function get($name)
    {
        // echo "<br> getting for: ",$name," :value: ",HTTP_Session2::get($name);
        return HTTP_Session2::get($name);
    }

    public function getError()
    {
        return $this->error;
    }

    public function destroy()
    {
        HTTP_Session2::destroy();
    }
}
