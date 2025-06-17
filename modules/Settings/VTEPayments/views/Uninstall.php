<?php

define("DS", DIRECTORY_SEPARATOR);
class Settings_VTEPayments_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }
    public function process(Vtiger_Request $request)
    {
        require_once dirname(dirname(__FILE__)) . "/models/UnInstall.php";
        $moduleName = $request->getModule();
        $current_user = Users_Record_Model::getCurrentUserModel();
        $unIntallInstance = new UnInstall($moduleName);
        $customQueries = array();
        $customQueries[] = "DELETE FROM vtiger_eventhandlers WHERE `handler_class` LIKE 'VTEPaymentsHandler';";
        $unIntallInstance->setCustomQuery($customQueries);
        $links = array(array("linktype" => "HEADERSCRIPT", "linklabel" => "VTEPayments Header Script"));
        $unIntallInstance->setLinks($links);
        $tree_html = $unIntallInstance->getModuleStructureHTML();
        $query_html = $unIntallInstance->getQueriesHTML();
        $qualifiedModuleName = $request->getModule(false);
        $parentModuleName = $request->get("parent");
        $viewer = $this->getViewer($request);
        $viewer->assign("QUALIFIED_MODULE", $qualifiedModuleName);
        $viewer->assign("PARENT_MODULE", $parentModuleName);
        $viewer->assign("MODULE_NAME", $moduleName);
        $viewer->assign("CURRENT_USER", $current_user);
        $viewer->assign("TREE_HTML", $tree_html);
        $viewer->assign("QUERY_HTML", $query_html);
        $viewer->view("Uninstall.tpl", $qualifiedModuleName);
    }
}

?>