<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Inventory_SubProductsPopupAjax_View extends Inventory_SubProductsPopup_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('getListViewCount');
        $this->exposeMethod('getRecordsCount');
        $this->exposeMethod('getPageCount');
    }

    /**
     * Function returns module name for which Popup will be initialized.
     * @param type $request
     */
    public function getModule($request)
    {
        return 'Products';
    }

    public function preProcess(Vtiger_Request $request, $display = true)
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
        $viewer = $this->getViewer($request);

        $this->initializeListViewContents($request, $viewer);
        $moduleName = 'Inventory';
        $viewer->assign('MODULE_NAME', $moduleName);
        echo $viewer->view('PopupContents.tpl', $moduleName, true);
    }
}
