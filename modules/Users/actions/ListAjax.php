<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Users_ListAjax_Action extends Vtiger_BasicAjax_Action
{
    public function __construct()
    {
        parent::__construct();
    }

    public function requiresPermission(Vtiger_Request $request)
    {
        return [];
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (!$currentUser->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    public function preProcess(Vtiger_Request $request)
    {
        return true;
    }

    public function postProcess(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);

            return;
        }
    }
}
