<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Settings_Webforms_Delete_Action extends Settings_Vtiger_Index_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
        parent::checkPermission($request);

        $recordId = $request->get('record');
        $moduleModel = Vtiger_Module_Model::getInstance($request->getModule());

        $currentUserPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$recordId || !$currentUserPrivilegesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }

        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $recordId = $request->get('record');
        $qualifiedModuleName = $request->getModule(false);

        $recordModel = Settings_Webforms_Record_Model::getInstanceById($recordId, $qualifiedModuleName);
        $moduleModel = $recordModel->getModule();

        $recordModel->delete();

        $returnUrl = $moduleModel->getListViewUrl();
        $response = new Vtiger_Response();
        $response->setResult($returnUrl);

        return $response;
    }

    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}
