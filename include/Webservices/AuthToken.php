<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

function vtws_getchallenge($username)
{

    global $adb;

    if (empty($username)) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'No username given');
    }

    $user = new Users();
    $userid = $user->retrieve_user_id($username);

    if (empty($userid)) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'username does not exists');
    }

    $authToken = uniqid();
    $servertime = time();
    $expireTime = time() + (60 * 5);

    $sql = 'delete from vtiger_ws_userauthtoken where userid=?';
    $adb->pquery($sql, [$userid]);

    $sql = 'insert into vtiger_ws_userauthtoken(userid,token,expireTime) values (?,?,?)';
    $adb->pquery($sql, [$userid, $authToken, $expireTime]);

    return ['token' => $authToken, 'serverTime' => $servertime, 'expireTime' => $expireTime];
}
