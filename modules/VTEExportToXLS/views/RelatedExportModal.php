<?php

class VTEExportToXLS_RelatedExportModal_View extends Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->get("sourceModule");
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModuleActionPermission($moduleModel->getId(), "Export")) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    
    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $module = $request->getModule();
        $source_module = $request->get("sourceModule");
        $related_module = $request->get("related_module");
        $record = $request->get("record");
        $viewId = $request->get("viewname");
        $selectedIds = $request->get("selected_ids");
        $ids_in_page = $request->get("ids_in_page");
        $excludedIds = $request->get("excluded_ids");
        $orderBy = $request->get("orderby");
        $sortOrder = $request->get("sortorder");
        $tagParams = $request->get("tag_params");
        $page = $request->get("page");
        $viewer->assign("SELECTED_IDS", $selectedIds);
        $viewer->assign("IDS_IN_PAGE", $ids_in_page);
        $viewer->assign("EXCLUDED_IDS", $excludedIds);
        $viewer->assign("VIEWID", $viewId);
        $viewer->assign("PAGE", $page);
        $viewer->assign("SOURCE_MODULE", $source_module);
        $viewer->assign("RECORD", $record);
        $viewer->assign("RELATED_MODULE", $related_module);
        $viewer->assign("MODULE", $module);
        $viewer->assign("ORDER_BY", $orderBy);
        $viewer->assign("SORT_ORDER", $sortOrder);
        $viewer->assign("TAG_PARAMS", $tagParams);
        $searchKey = $request->get("search_key");
        $searchValue = $request->get("search_value");
        $operator = $request->get("operator");
        if (!empty($operator)) {
            $viewer->assign("OPERATOR", $operator);
            $viewer->assign("ALPHABET_VALUE", $searchValue);
            $viewer->assign("SEARCH_KEY", $searchKey);
        }
        $viewer->assign("SUPPORTED_FILE_TYPES", array("csv", "ics"));
        $viewer->assign("SEARCH_PARAMS", $request->get("search_params"));
        $viewer->view("RelatedExportModal.tpl", $module);
    }
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->get("sourceModule");
        if (in_array($moduleName, getInventoryModules())) {
            $moduleEditFile = "modules." . $moduleName . ".resources.Edit";
            unset($headerScriptInstances[$moduleEditFile]);
            $jsFileNames = array("modules.Inventory.resources.Edit", "modules." . $moduleName . ".resources.Edit");
        }
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}

?>