<?php

class Settings_ChecklistItems_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        require_once dirname(dirname(__FILE__)) . '/models/UnInstall.php';
        $moduleName = $request->getModule();
        $current_user = Users_Record_Model::getCurrentUserModel();
        $unIntallInstance = new UnInstall($moduleName);
        $customQueries = [];
        $unIntallInstance->setCustomQuery($customQueries);
        $links = [['linktype' => 'DETAILVIEWBASIC', 'linklabel' => 'Checklist Items'], ['linktype' => 'HEADERSCRIPT', 'linklabel' => 'ChecklistItemsJS']];
        $unIntallInstance->setLinks($links);
        $tree_html = $unIntallInstance->getModuleStructureHTML();
        $query_html = $unIntallInstance->getQueriesHTML();
        $qualifiedModuleName = $request->getModule(false);
        $parentModuleName = $request->get('parent');
        $viewer = $this->getViewer($request);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->assign('PARENT_MODULE', $parentModuleName);
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('CURRENT_USER', $current_user);
        $viewer->assign('TREE_HTML', $tree_html);
        $viewer->assign('QUERY_HTML', $query_html);
        $viewer->view('Uninstall.tpl', $qualifiedModuleName);
    }
}
