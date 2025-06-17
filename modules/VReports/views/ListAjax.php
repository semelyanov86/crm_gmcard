<?php

class VReports_ListAjax_View extends Reports_List_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("getListViewCount");
        $this->exposeMethod("getRecordsCount");
        $this->exposeMethod("getPageCount");
        $this->exposeMethod("settingForWidget");
        $this->exposeMethod("getAllRecordsCount");
        $this->exposeMethod("checkWarnigsAndErrors");
        $this->exposeMethod("fixError");
        $this->exposeMethod("openWidget");
    }
    
    public function preProcess(Vtiger_Request $request, $display = true)
    {
    }
    public function getAllRecordsCount(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $widgetId = $request->get("widgetId");
        $widget = VReports_Widget_Model::getInstanceWithWidgetId($widgetId, $currentUser->getId());
        $minilistWidgetModel = new VReports_MiniList_Model();
        $minilistWidgetModel->setWidgetModel($widget);
        $allRecordsCount = $minilistWidgetModel->getRecords("count");
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($allRecordsCount);
        $response->emit();
    }
    public function settingForWidget(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $widgetName = $request->get("widgetName");
        $widgetType = $request->get("widgetType");
        $viewer = $this->getViewer($request);
        $modules = Vtiger_Module_Model::getSearchableModules();
        $moduleName = $request->get("module");
        $selectedModuleName = $request->get("selectedModule");
        $widget = VReports_Widget_Model::getInstanceWithWidgetId($request->get("record"), $currentUser->getId());
        $moduleModel = Vtiger_Module_Model::getInstance($selectedModuleName);
        if ($moduleModel) {
            $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceForModule($moduleModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_FILTER);
            $recordStructure = $recordStructureInstance->getStructure();
            $viewer->assign("RECORD_STRUCTURE", $recordStructure);
            $viewer->assign("SELECTED_MODULE", $selectedModuleName);
            $data = json_decode(html_entity_decode($widget->get("data")));
            $viewer->assign("SELECTED_FIELD", html_entity_decode($widget->get("data")));
            $viewer->assign("SELECTED_ORDER_FIELD", $data->orderField);
            $viewer->assign("SELECTED_ORDER_KEYWORD", $data->orderKeyword);
            $viewer->assign("SELECTED_ORDER_FIELD_1", $data->orderField1);
            $viewer->assign("SELECTED_ORDER_KEYWORD_1", $data->orderKeyword1);
            $viewer->assign("SHOW_LINE_ON_ROW", $data->showLineOnRow);
            $viewer->assign("SHOW_LINE_ON_ROW_1", $data->showLineOnRow1);
        }
        if ($widgetName == "KeyMetrics") {
            $arrayKey = array();
            $allarrayKey = array();
            $keyMetricsModel = new VReports_KeyMetrics_Model();
            $keymetrics = $keyMetricsModel->getKeyMetricsWithCount($widget);
            foreach ($keymetrics as $key => $value) {
                $arrayKey[$value["id"]] = $value["name"];
            }
            $allkeymetrics = $keyMetricsModel->getKeyMetricsWithCount();
            foreach ($allkeymetrics as $allkey => $allvalue) {
                $allarrayKey[$allvalue["module"]][$allvalue["id"]] = $allvalue["name"];
            }
            $viewer->assign("KEY_METRICS_LIST", $arrayKey);
            $viewer->assign("SHOW_EMPTY_VAL", $widget->get("km_show_empty_val"));
            $viewer->assign("ALL_KEY_METRICS_LIST", $allarrayKey);
        } else {
            if ($widgetName == "Gauge") {
                $gaugeModel = new VReports_Gauge_Model();
                $dataGauge = json_decode(html_entity_decode($widget->get("data")));
                $viewer->assign("TARGET_REPORT", $dataGauge->targetReport);
                $viewer->assign("DATA_GAUGE", $dataGauge->dataGauge);
                $viewer->assign("SELECTED_FIELD", html_entity_decode($widget->get("data")));
                $input_lines = file_get_contents("layouts/v7/lib/vt-icons/style.css");
                preg_match_all("/(.vicon-[a-z0-9]+-[a-z0-9]+)|(.vicon-[a-z0-9_]+)|(\\\\[a-z0-9]+)/", $input_lines, $output_array);
                $output_array = $output_array[0];
                unset($output_array[0]);
                $arrResults = array_chunk($output_array, 2);
                $arrIconClasses = array();
                foreach ($arrResults as $cssDetail) {
                    $arrIconClasses[str_replace(".", "", $cssDetail[0])] = $cssDetail[1];
                }
                $viewer->assign("LISTICONS", $arrIconClasses);
                $detailReports = $gaugeModel->getListDetailReport();
                $viewer->assign("ALL_DETAIL_REPORTS", $detailReports);
                $viewer->assign("CALCULATION_FIELDS", $gaugeModel->getColumnsDetailReport($dataGauge->targetReport));
            }
        }
        $viewer->assign("MODULES", $modules);
        $viewer->assign("WIDGET_TYPE", $widgetType);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("RECORD_ID", $widget->get("id"));
        $viewer->assign("WIDGET", $widget);
        $viewer->assign("WIDGET_NAME", $widgetName);
        $viewer->assign("WIDGET_MODE", "Settings");
        $viewer->assign("WIDGET_FORM", "Edit");
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $viewer->view("dashboards/MiniListWizard.tpl", $moduleName);
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        } else {
            $viewer = $this->getViewer($request);
            $moduleName = $request->getModule();
            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
            $folders = $moduleModel->getFolders();
            $listViewModel = new VReports_ListView_Model();
            $listViewModel->set("module", $moduleModel);
            $linkModels = $listViewModel->getListViewLinks();
            $viewer->assign("LISTVIEW_LINKS", $linkModels);
            $viewer->assign("FOLDERS", $folders);
            $viewer->view("ListViewFolders.tpl", $moduleName);
        }
    }
    public function getPageCount(Vtiger_Request $request)
    {
        $listViewCount = $this->getListViewCount($request);
        $pagingModel = new Vtiger_Paging_Model();
        $pageLimit = $pagingModel->getPageLimit();
        $pageCount = ceil((int) $listViewCount / (int) $pageLimit);
        if ($pageCount == 0) {
            $pageCount = 1;
        }
        $result = array();
        $result["page"] = $pageCount;
        $result["numberOfRecords"] = $listViewCount;
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
    }
    public function getListViewCount(Vtiger_Request $request)
    {
        $folderId = $request->get("viewname");
        if (empty($folderId)) {
            $folderId = "All";
        }
        $listViewModel = new VReports_ListView_Model();
        $listViewModel->set("folderid", $folderId);
        $searchParams = $request->get("search_params");
        if (!empty($searchParams[0])) {
            $listViewModel->set("search_params", $searchParams[0]);
        }
        $count = $listViewModel->getListViewCount();
        return $count;
    }
    public function checkWarnigsAndErrors(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $dashBoardModel = new VReports_DashBoard_Model();
        $errorDashboardMissingLink = $dashBoardModel->findMissingLink("find");
        $errorDashboardWidget = $dashBoardModel->findMissingWidget("find");
        $errorDashboardEmptyLink = $dashBoardModel->findEmptyLink("find");
        $errorDashboardMissingDefaultTab = $dashBoardModel->findDefaultTab("find");
        $viewer->assign("DASHBOARD_MISSING_LINK", $errorDashboardMissingLink["diff"]);
        $viewer->assign("COUNT_DASHBOARD_MISSING_LINK", count($errorDashboardMissingLink["diff"]));
        $viewer->assign("RAW_VALUE_DASHBOARD_MISSING_LINK", $errorDashboardMissingLink["rawValue"]);
        $viewer->assign("DASHBOARD_ERROR_WIDGET", $errorDashboardWidget);
        $count_errorDashboardWidget = 0;
        if (is_array($errorDashboardWidget)) {
            $count_errorDashboardWidget = count($errorDashboardWidget);
        }
        $viewer->assign("COUNT_DASHBOARD_ERROR_WIDGET", $count_errorDashboardWidget);
        $viewer->assign("DASHBOARD_EMPTY_LINK", $errorDashboardEmptyLink);
        $count_errorDashboardEmptyLink = 0;
        if (is_array($errorDashboardEmptyLink["emptyLink"])) {
            $count_errorDashboardEmptyLink = count($errorDashboardEmptyLink["emptyLink"]);
        }
        $viewer->assign("COUNT_DASHBOARD_EMPTY_LINK", $count_errorDashboardEmptyLink);
        $viewer->assign("DASHBOARD_MISSING_DEFAULT_TAB", $errorDashboardMissingDefaultTab);
        $count_errorDashboardMissingDefaultTab = 0;
        if (is_array($errorDashboardMissingDefaultTab)) {
            $count_errorDashboardMissingDefaultTab = count($errorDashboardMissingDefaultTab);
        }
        $viewer->assign("COUNT_DASHBOARD_MISSING_DEFAULT_TAB", $count_errorDashboardMissingDefaultTab);
        $viewer->assign("MODULE_NAME", $moduleName);
        $viewer->view("WarningsAndErrors.tpl", $moduleName);
    }
    public function fixError(Vtiger_Request $request)
    {
        $function = $request->get("runIn");
        $dashBoardModel = new VReports_DashBoard_Model();
        $affectedRow = $dashBoardModel->{$function}("fix");
        $response = new Vtiger_Response();
        $response->setResult($affectedRow);
        $response->emit();
    }
    public function openWidget(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $tabId = $request->get("tabid");
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        $dashBoardModel->set("tabid", $tabId);
        $widgets = $dashBoardModel->getSelectableDashboard();
        $folders = VReports_Folder_Model::getAll();
        $viewer->assign("SELECTABLE_WIDGETS", $widgets);
        $viewer->assign("MODULE_NAME", $moduleName);
        $viewer->assign("FOLDERS", $folders);
        $viewer->view("dashboards/DashBoardHeader.tpl", $moduleName);
    }
}

?>