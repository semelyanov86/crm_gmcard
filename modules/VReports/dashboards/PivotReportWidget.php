<?php

class VReports_PivotReportWidget_Dashboard extends Vtiger_IndexAjax_View
{
    public function process(Vtiger_Request $request)
    {
        global $current_user;
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $record = $request->get('reportid');
        $widgetId = $request->get('widgetid');
        $tabId = $request->get('tabid');
        $reportModel = VReports_Record_Model::getInstanceById($record);
        $reportPivotModel = VReports_Pivot_Model::getInstanceById($reportModel);
        $reportChartModel = VReports_Chart_Model::getInstanceById($reportModel);
        $primaryModule = $reportModel->getPrimaryModule();
        $moduleModel = Vtiger_Module_Model::getInstance($primaryModule);
        if (!$moduleModel->isPermitted('DetailView')) {
            $viewer->assign('MESSAGE', $primaryModule . ' ' . vtranslate('LBL_NOT_ACCESSIBLE'));
            $viewer->view('OperationNotPermitted.tpl', $primaryModule);
        }
        $secondaryModules = $reportModel->getSecondaryModules();
        if (empty($secondaryModules)) {
            $viewer->assign('CLICK_THROUGH', true);
        }
        $reportPivotModel->set('widgetId', $widgetId);
        $data = $reportPivotModel->getData();
        if ($reportChartModel->get('type')) {
            $reportChartModel->set('datafields', $reportChartModel->get('datafields-chart'));
            $dataChart = $reportChartModel->getData();
            $viewer->assign('DATA_CHART', $dataChart);
        }
        $viewer->assign('DATA', $data['pivot']);
        $viewer->assign('REPORT_TYPE', 'pivot');
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $widget = VReports_Widget_Model::getInstanceWithReportId($record, $tabId);
        $widget->set('title', $reportModel->getName() . ' (' . vtranslate($primaryModule, $primaryModule) . ')');
        $viewer->assign('WIDGET', $widget);
        $viewer->assign('RECORD_ID', $record);
        $viewer->assign('WIDGET_ID', $widgetId);
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('SECONDARY_MODULES', $secondaryModules);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('PRIMARY_MODULE', $primaryModule);
        $viewer->assign('CURRENT_USER', $current_user);
        $content = $request->get('content');
        if (!empty($content)) {
            $viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
        } else {
            $viewer->view('dashboards/DashBoardWidget.tpl', $moduleName);
        }
    }
}
