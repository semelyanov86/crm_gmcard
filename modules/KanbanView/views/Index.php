<?php

class KanbanView_Index_View extends Vtiger_Index_View
{
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request, false);
        $viewer = $this->getViewer($request);
        $moduleName = $request->get("source_module");
        $this->viewName = $request->get("viewname");
        $viewer->assign("CUSTOM_VIEWS", CustomView_Record_Model::getAllByGroup($moduleName));
        $viewer->assign("VIEWID", $this->viewName);
        $viewer->assign("KANBAN_PARENT_MODULE", $request->get("source_module"));
        if ($display) {
            $this->preProcessDisplay($request);
        }
    }
    public function setModuleInfo($request, $moduleModel = false)
    {
        $fieldsInfo = array();
        $moduleName = $request->get("source_module");
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $moduleFields = $moduleModel->getFields();
        foreach ($moduleFields as $fieldName => $fieldModel) {
            $fieldsInfo[$fieldName] = $fieldModel->getFieldInfo();
        }
        $viewer = $this->getViewer($request);
        $viewer->assign("FIELDS_INFO", json_encode($fieldsInfo));
    }
    protected function preProcessTplName(Vtiger_Request $request)
    {
        return "IndexViewPreProcess.tpl";
    }
    public static function cmp($a, $b)
    {
        return strcmp($a["fruit"], $b["fruit"]);
    }
    public function process(Vtiger_Request $request)
    {
        global $vtiger_current_version;
        $db = PearDatabase::getInstance();
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $cvId = $request->get("viewname");
        $sourceModule = $request->get("source_module");
        $sourceModuleModel = Vtiger_Module_Model::getInstance($sourceModule);
        $kanbanModel = new KanbanView_Module_Model();
        $kanbanFieldSetting = $kanbanModel->getKanbanviewSetting($sourceModule, $cvId);
        if ($kanbanFieldSetting) {
            $currentUser = vglobal("current_user");
            $queryGenerator = new QueryGenerator($sourceModule, $currentUser);
            $queryGenerator->initForCustomViewById($cvId);
            $moduleFocus = CRMEntity::getInstance($sourceModule);
            $primaryFieldModel = Vtiger_Field_Model::getInstanceFromFieldId($kanbanFieldSetting["primary_field"], getTabid($sourceModule));
            $primaryFieldModel = $primaryFieldModel[0];
            $primaryFieldName = $primaryFieldModel->getName();
            $searchParmams = array(array(array($primaryFieldName, "e", implode(",", $kanbanFieldSetting["primary_value_setting"]))));
            $transformedSearchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParmams, $sourceModuleModel);
            $glue = "";
            if (0 < count($queryGenerator->getWhereFields()) && 0 < count($transformedSearchParams)) {
                $glue = QueryGenerator::$AND;
            }
            $queryGenerator->parseAdvFilterList($transformedSearchParams, $glue);
            $listQuery = $queryGenerator->getQuery();
            $listQueryResult = $db->pquery($listQuery, array());
            while ($rowRecord = $db->fetch_array($listQueryResult)) {
                $table_index = $moduleFocus->table_index;
                $recordId = $rowRecord[$table_index];
                $recordModel = Vtiger_Record_Model::getInstanceById($recordId);
                $recordModel = $kanbanModel->setFontColor($recordModel);
                $key = decode_html($recordModel->get($primaryFieldName));
                $listRecord[$key][$recordId]["RECORD"] = $recordModel;
                $listRecord[$key][$recordId]["sequence"] = $kanbanModel->getSequence($recordId, $sourceModule, $kanbanFieldSetting["primary_field"], $recordModel->get($primaryFieldName), $cvId);
            }
            $arrFieldModels = array();
            foreach ($kanbanFieldSetting["other_field"] as $selectField) {
                list($tableName, $columnName, $fieldName, $moduleFieldLabel, $fieldType) = explode(":", $selectField);
                $arrFieldModels[$fieldName] = $sourceModuleModel->getField($fieldName);
            }
            if (!empty($kanbanFieldSetting["primary_value_setting"])) {
                foreach ($kanbanFieldSetting["primary_value_setting"] as $val) {
                    if ($listRecord[$val]) {
                        usort($listRecord[$val], function ($a, $b) {
                            if ($a["sequence"] == $b["sequence"]) {
                                return 0;
                            }
                            return $a["sequence"] < $b["sequence"] ? -1 : 1;
                        });
                    }
                }
            }
        }
        if (version_compare($vtiger_current_version, "7.0.0", ">=")) {
            $picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
            $viewer->assign("PICKIST_DEPENDENCY_DATASOURCE", Vtiger_Functions::jsonEncode($picklistDependencyDatasource));
        }
        $viewer->assign("LIST_RECORDS", $listRecord);
        $viewer->assign("LIST_COLOR_CARD", array("Red", "Orange", "Green", "Yellow", "Teal", "Blue", "Purple", "Peru", "Silver", "Olive"));
        $viewer->assign("CV_ID", $cvId);
        $viewer->assign("PRIMARY_FIELD_SELECT", $primaryFieldName);
        $viewer->assign("FIELD_SETTING", $kanbanFieldSetting);
        $viewer->assign("ARR_SELECTED_FIELD_MODELS", $arrFieldModels);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $viewer->assign("KANBAN_SOURCE_MODULE", $sourceModule);
        $viewer->view("Index.tpl", $moduleName);
    }
    /**
     * Function to get the list of Script models to be included
     * @param Vtiger_Request $request
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        global $vtiger_current_version;
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = array("modules.Vtiger.resources.List", "modules." . $moduleName . ".resources.List", "modules.Vtiger.resources.Detail", "modules.Vtiger.resources.ListSidebar", "modules.CustomView.resources.CustomView", "libraries.jquery.ckeditor.ckeditor", "libraries.jquery.ckeditor.adapters.jquery", "modules.Vtiger.resources.CkEditor", "~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.min.js");
        if (version_compare($vtiger_current_version, "7.0.0", ">=")) {
            $jsFileNames[] = "~layouts/v7/lib/jquery/Lightweight-jQuery-In-page-Filtering-Plugin-instaFilta/instafilta.min.js";
            $jsFileNames[] = "modules.Vtiger.resources.Tag";
            $jsFileNames[] = "~layouts/" . Vtiger_Viewer::getDefaultLayoutName() . "/lib/jquery/floatThead/jquery.floatThead.js";
            $jsFileNames[] = "~layouts/" . Vtiger_Viewer::getDefaultLayoutName() . "/lib/jquery/perfect-scrollbar/js/perfect-scrollbar.jquery.js";
            $jsFileNames[] = "~layouts/v7/modules/KanbanView/resources/ListSideBar.js";
        }
        $jsFileNames[] = "modules." . $moduleName . ".resources.Index";
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
    public function getHeaderCss(Vtiger_Request $request)
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
        } else {
            $template_folder = "layouts/v7";
        }
        $headerCssInstances = parent::getHeaderCss($request);
        $currentTheme = Vtiger_Theme::getDefaultThemeName();
        $cssFileNames = array("~/" . $template_folder . "/modules/KanbanView/css/kanbanStyle.css", "~/" . $template_folder . "/modules/KanbanView/css/themes/" . $currentTheme . ".css");
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);
        return $headerCssInstances;
    }
    public function transferListSearchParamsToFilterCondition($listSearchParams, $moduleModel)
    {
        return Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($listSearchParams, $moduleModel);
    }
}

?>