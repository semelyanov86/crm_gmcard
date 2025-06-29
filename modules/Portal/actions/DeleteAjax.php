<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Portal_DeleteAjax_Action extends Vtiger_DeleteAjax_Action
{
    public function requiresPermission(Vtiger_Request $request)
    {
        $permissions[] = ['module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record'];

        return $permissions;
    }

    public function process(Vtiger_Request $request)
    {
        $recordId = $request->get('record');
        $module = $request->getModule();
        $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $module);
        if ($recordModel) {
            $moduleModel = new Portal_Module_Model();
            $moduleModel->deleteRecord($recordModel);
        }

        $response = new Vtiger_Response();
        $response->setResult(['message' =>  vtranslate('LBL_RECORD_DELETED_SUCCESSFULLY', $module)]);
        $response->emit();
    }
}
