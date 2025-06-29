<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Portal_MassDelete_Action extends Vtiger_MassDelete_Action
{
    public function requiresPermission(Vtiger_Request $request)
    {
        $permissions[] = ['module_parameter' => 'module', 'action' => 'DetailView'];

        return $permissions;
    }

    public function process(Vtiger_Request $request)
    {
        $module = $request->getModule();

        Portal_Module_Model::deleteRecords($request);

        $response = new Vtiger_Response();
        $result = ['message' => vtranslate('LBL_BOOKMARKS_DELETED_SUCCESSFULLY', $module)];
        $response->setResult($result);
        $response->emit();
    }
}
