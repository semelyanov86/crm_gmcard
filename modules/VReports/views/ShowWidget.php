<?php

class VReports_ShowWidget_View extends Vtiger_ShowWidget_View
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function process(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $moduleName = $request->getModule();
        $componentName = $request->get("name");
        $historyType = $request->get("historyType");
        $type = $request->get("type");
        $widgetid = $request->get("widgetid");
        $tabid = $request->get("tabid");
        $autorefresh = $request->get("autorefresh");
        $avoidInsertDupicateWidget = array("KeyMetrics", "Gauge");
        if ($componentName != "MiniList") {
            $linkId = VReports_Widget_Model::getLinkId($moduleName, $componentName);
            $sortandgroup = $request->get("sortandgroup");
        } else {
            $linkId = $request->get("linkid");
        }
        if (!empty($componentName)) {
            $className = Vtiger_Loader::getComponentClassName("Dashboard", $componentName, $moduleName);
            if (!empty($className)) {
                $widget = NULL;
                if (!empty($linkId)) {
                    $widget = new VReports_Widget_Model();
                    $widget->set("linkid", $linkId);
                    $widget->set("color", $request->get("color"));
                    $widget->set("widget_name", $request->get("name"));
                    $widget->set("refresh_time", $request->get("refresh_time"));
                    $widget->set("min_height", $request->get("min_height"));
                    $widget->set("max_height", $request->get("max_height"));
                    $widget->set("userid", $currentUser->getId());
                    $widget->set("filterid", $request->get("filterid", NULL));
                    $widget->set("tabid", $tabid);
                    if ($componentName == "KeyMetrics") {
                        if ($request->get("fields")) {
                            $fields = "{\"fields\":[\"" . implode("\",\"", explode(",", urldecode($request->get("fields")))) . "\"]}";
                            $widget->set("data", json_decode($fields));
                        }
                        $widget->set("refresh_time", $request->get("time"));
                        $widget->set("showemptyval", $request->get("showemptyval"));
                    }
                    if ($sortandgroup) {
                        $widget->set("sortandgroup", $sortandgroup);
                    }
                    if ($request->has("data")) {
                        $widget->set("data", $request->get("data"));
                    }
                    if ($type && $historyType || $sortandgroup) {
                        $widget->setHistoryType($type, $historyType, $widgetid, $tabid, $sortandgroup);
                    } else {
                        if ($request->get("modeWidget") && in_array($request->get("name"), $avoidInsertDupicateWidget)) {
                            $widget->set("title", $request->get("title"));
                            $widget->add($request->get("modeWidget"));
                        } else {
                            $widget->add();
                        }
                    }
                }
                $createdTime = $request->get("createdtime");
                $request->set("dateFilter", $createdTime);
                if (!empty($createdTime)) {
                    $startDate = Vtiger_Date_UIType::getDBInsertedValue($createdTime["start"]);
                    $dates["start"] = getValidDBInsertDateTimeValue($startDate . " 00:00:00");
                    $endDate = Vtiger_Date_UIType::getDBInsertedValue($createdTime["end"]);
                    $dates["end"] = getValidDBInsertDateTimeValue($endDate . " 23:59:59");
                }
                $request->set("createdtime", $dates);
                $_REQUEST["call_from"] = "DashBoard";
                if ($autorefresh) {
                    $autorefresh_id = VReports_Widget_Model::saveAutoRefreshLog($widgetid);
                }
                $classInstance = new $className();
                $classInstance->process($request, $widget);
                if ($autorefresh) {
                    VReports_Widget_Model::updateAutoRefreshLog($widgetid, $autorefresh_id);
                }
                return NULL;
            }
        }
        $response = new Vtiger_Response();
        $response->setResult(array("success" => false, "message" => vtranslate("NO_DATA")));
        $response->emit();
    }
    public function validateRequest(Vtiger_Request $request)
    {
        $request->validateWriteAccess();
    }
}

?>