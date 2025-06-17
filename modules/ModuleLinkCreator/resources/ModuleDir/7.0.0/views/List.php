<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class __ModuleName___List_View extends Vtiger_List_View {
    public function checkPermission(Vtiger_Request $request) {
        require_once "modules/__ModuleName__/helpers/Util.php";
        $helpersUtil=new CustomModule_Util_Helper();
        if($helpersUtil->checkPermis()){
            return parent::checkPermission($request);
        }
    }
}