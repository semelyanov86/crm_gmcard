<?php

class VReports_MoveReports_View extends Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $folderList = $moduleModel->getFolders();
        $viewer = $this->getViewer($request);
        $viewer->assign("FOLDERS", $folderList);
        $viewer->assign("SELECTED_IDS", $request->get("selected_ids"));
        $viewer->assign("EXCLUDED_IDS", $request->get("excluded_ids"));
        $viewer->assign("VIEWNAME", $request->get("viewname"));
        $viewer->assign("MODULE", $moduleName);
        $searchParams = $request->get("search_params");
        $viewer->assign("SEARCH_PARAMS", $searchParams);
        $viewer->view("MoveReports.tpl", $moduleName);
    }
}

?>