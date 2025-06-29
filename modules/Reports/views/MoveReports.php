<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Reports_MoveReports_View extends Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $folderList = $moduleModel->getFolders();
        $viewer = $this->getViewer($request);
        $viewer->assign('FOLDERS', $folderList);
        $viewer->assign('SELECTED_IDS', $request->get('selected_ids'));
        $viewer->assign('EXCLUDED_IDS', $request->get('excluded_ids'));
        $viewer->assign('VIEWNAME', $request->get('viewname'));
        $viewer->assign('MODULE', $moduleName);
        $searchParams = $request->get('search_params');
        $viewer->assign('SEARCH_PARAMS', $searchParams);
        $viewer->view('MoveReports.tpl', $moduleName);
    }
}
