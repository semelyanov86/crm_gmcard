<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
require_once 'include/Webservices/Create.php';
require_once 'include/Webservices/Update.php';
require_once 'include/Webservices/Delete.php';
require_once 'modules/WSAPP/Utils.php';

function wsapp_put($key, $element, $user)
{
    $name = wsapp_getApplicationName($key);
    if ($name) {
        $handlerDetails  = wsapp_getHandler($name);
        require_once $handlerDetails['handlerpath'];
        $handler = new $handlerDetails['handlerclass']($key);

        // for Record Source
        $appNameParts = explode('_', $name);
        Vtiger_Cache::set('WSAPP', 'appName', strtoupper($appNameParts[0]));

        return $handler->put($element, $user);
    }

    return [];
}
