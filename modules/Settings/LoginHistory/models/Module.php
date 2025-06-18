<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

/*
 * Settings Module Model Class
 */
class Settings_LoginHistory_Module_Model extends Settings_Vtiger_Module_Model
{
    public $baseTable = 'vtiger_loginhistory';

    public $baseIndex = 'login_id';

    public $listFields = [
        'user_name' => 'LBL_USER_NAME',
        'user_ip' => 'LBL_USER_IP_ADDRESS',
        'login_time' => 'LBL_LOGIN_TIME',
        'logout_time' => 'LBL_LOGGED_OUT_TIME',
        'status' => 'LBL_STATUS',
    ];

    public $name = 'LoginHistory';

    /**
     * Function to get the url for default view of the module.
     * @return <string> - url
     */
    public function getDefaultUrl()
    {
        return 'index.php?module=LoginHistory&parent=Settings&view=List';
    }
}
