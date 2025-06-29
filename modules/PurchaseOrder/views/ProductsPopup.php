<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class PurchaseOrder_ProductsPopup_View extends Inventory_ProductsPopup_View
{
    public function initializeListViewContents(Vtiger_Request $request, Vtiger_Viewer $viewer)
    {
        parent::initializeListViewContents($request, $viewer);
        $viewer->assign('GETURL', 'getPurchaseOrderTaxesURL');
    }
}
