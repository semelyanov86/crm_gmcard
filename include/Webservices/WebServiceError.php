<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

require_once 'include/Webservices/WebServiceErrorCode.php';
class WebServiceException extends Exception
{
    public $code;

    public $message;

    public function __construct($errCode, $msg)
    {
        $this->code = $errCode;
        $this->message = $msg;
    }

    public function WebServiceException($errCode, $msg)
    {
        // PHP4-style constructor.
        // This will NOT be invoked, unless a sub-class that extends `foo` calls it.
        // In that case, call the new-style constructor to keep compatibility.
        self::__construct($errCode, $msg);
    }
}
