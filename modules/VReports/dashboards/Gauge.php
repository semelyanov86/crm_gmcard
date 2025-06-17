<?php

class VReports_Gauge_Dashboard extends Vtiger_MiniList_Dashboard
{
    public function process(Vtiger_Request $request, $oldWidget = NULL)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $viewer = $this->getViewer($request);
        $componentName = $request->get("name");
        $moduleName = $request->getModule();
        $linkId = $request->get("linkid");
        if (!$linkId) {
            $linkId = VReports_Widget_Model::getLinkId($moduleName, $componentName);
        }
        $data = $request->get("data");
        if (!$request->get("widgetid")) {
            $widgetId = $oldWidget->get("id");
        } else {
            $widgetId = $request->get("widgetid");
        }
        $widget = VReports_Widget_Model::getHistoryWidget($linkId, $currentUser->getId(), $widgetId);
        if (!$data) {
            $data = html_entity_decode($widget->get("data"));
        } else {
            $data = Zend_Json::encode($data);
        }
        if (!$widget->get("id")) {
            $widget = $oldWidget;
        }
        $symbolPlacement = "last";
        $tempVal = explode("\$", $currentUser->currency_symbol_placement);
        if ($tempVal[0] == "") {
            $symbolPlacement = "first";
        }
        $gaugeModel = new VReports_Gauge_Model();
        if ($widget->get("sizewidth") == NULL || $widget->get("sizeheight") == NULL) {
            $gaugeModel->autoUpdateSize($widget->get("id"));
        }
        if (!empty($data) && $data != NULL && $data != "null") {
            $calculateGauge = $gaugeModel->calculateGaugeData($data);
        }
        $viewer->assign("CURRENT_USER", $currentUser);
        $viewer->assign("TITLE", "Gauge");
        $viewer->assign("DATA", $calculateGauge);
        $viewer->assign("WIDGET", $widget);
        $viewer->assign("MODULE_NAME", $moduleName);
        $userCurrencyInfo = getCurrencySymbolandCRate($currentUser->get("currency_id"));
        $viewer->assign("USER_CURRENCY_SYMBOL", $userCurrencyInfo["symbol"]);
        $viewer->assign("SYMBOL_PLACEMENT", $symbolPlacement);
        $accessibleUsers = $currentUser->getAccessibleUsers();
        $viewer->assign("ACCESSIBLE_USERS", $accessibleUsers);
        $viewer->view("dashboards/Gauge.tpl", $moduleName);
    }
}

?>