<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Products_ProductsPopupAjax_View extends Products_ProductsPopup_View
{
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);

            return;
        }
        $viewer = $this->getViewer($request);
        $this->initializeListViewContents($request, $viewer);

        $moduleName = $request->getModule();
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('SELECTED_RECORDS', $request->get('selectedRecords'));
        echo $viewer->view('ProductsPopupContents.tpl', $moduleName, true);
    }
}
