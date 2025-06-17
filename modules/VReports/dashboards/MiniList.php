<?php

class VReports_MiniList_Dashboard extends Vtiger_MiniList_Dashboard
{
    public function process(Vtiger_Request $request, $widget = NULL)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $currentPage = $request->get("currentPage");
        if (empty($currentPage)) {
            $currentPage = 1;
            $nextPage = 1;
        } else {
            $nextPage = $currentPage + 1;
        }
        if ($widget && !$request->has("widgetid")) {
            $widgetId = $widget->get("id");
        } else {
            $widgetId = $request->get("widgetid");
        }
        $widget = VReports_Widget_Model::getInstanceWithWidgetId($widgetId, $currentUser->getId());
        $minilistWidgetModel = new VReports_MiniList_Model();
        $minilistWidgetModel->setWidgetModel($widget);
        $minilistWidgetModel->set("nextPage", $nextPage);
        $minilistWidgetModel->set("currentPage", $currentPage);
        $moreExists = $minilistWidgetModel->moreRecordExists();
        $minilistWidgetRecords = $minilistWidgetModel->getRecords();
        $viewer->assign("WIDGET", $widget);
        $viewer->assign("WIDGET_NAME", $request->get("name"));
        $viewer->assign("MODULE_NAME", $moduleName);
        $viewer->assign("SELECTED_MODULE_NAME", $minilistWidgetModel->getTargetModule());
        $viewer->assign("MINILIST_WIDGET_MODEL", $minilistWidgetModel);
        $viewer->assign("BASE_MODULE", $minilistWidgetModel->getTargetModule());
        $viewer->assign("CURRENT_PAGE", $currentPage);
        $viewer->assign("MORE_EXISTS", $moreExists);
        $script = $this->getHeaderScripts($request);
        $viewer->assign("SCRIPTS", $script);
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        $viewer->assign("MINILIST_WIDGET_RECORDS", $minilistWidgetRecords);
        $viewer->assign("RECORD_COUNTS", count($minilistWidgetRecords));
        $viewer->assign("ALL_RECORD_COUNTS", $minilistWidgetModel->getRecords("count"));
        $recordColors = array();
        $listviewColorModule = Vtiger_Module_Model::getInstance("ListviewColors");
        if ($listviewColorModule && $listviewColorModule->isActive()) {
            $moduleModel = new ListviewColors_Module_Model();
            $conditions = $moduleModel->getConditionalColors($minilistWidgetModel->getTargetModule(), true);
            if (!empty($conditions)) {
                foreach ($conditions as $condition) {
                    $recordsMatched = $moduleModel->getRecordsByCondition($condition, array_keys($minilistWidgetRecords));
                    if (!empty($recordsMatched)) {
                        foreach ($recordsMatched as $recordMatched) {
                            $recordColors[$recordMatched] = array("text_color" => $condition["text_color"], "bg_color" => $condition["bg_color"], "related_record_color" => $condition["related_record_color"]);
                        }
                    }
                }
            }
        }
        $viewer->assign("MINILIST_RECORDS_COLOR", $recordColors);
        $pagingModel = new Vtiger_Paging_Model();
        $pageLimit = $pagingModel->getPageLimit();
        $viewer->assign("PAGE_LIMIT", $pageLimit);
        $content = $request->get("content");
        if (!empty($content)) {
            $viewer->view("dashboards/MiniListContents.tpl", $moduleName);
        } else {
            $widget->set("title", $minilistWidgetModel->getTitle());
            $viewer->view("dashboards/MiniList.tpl", $moduleName);
        }
    }
    public function getListViewCount(Vtiger_Request $request)
    {
        $minilistWidgetModel = new VReports_MiniList_Model();
        $count = count($minilistWidgetModel->getRecords());
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($count);
        $response->emit();
    }
}

?>