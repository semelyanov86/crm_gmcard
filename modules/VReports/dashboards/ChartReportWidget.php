<?php

class VReports_ChartReportWidget_Dashboard extends Vtiger_IndexAjax_View
{
    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $record = $request->get('reportid');
        $widgetId = $request->get('widgetid');
        $tabId = $request->get('tabid');
        $reportModel = VReports_Record_Model::getInstanceById($record);
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
        $viewer->assign('CHART_TYPE', $reportChartModel->getChartType());
        $viewer->assign('REPORT_TYPE', 'chart');
        $data = $reportChartModel->getData();
        $viewer->assign('DATA', $data);
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
        $content = $request->get('content');
        if (!empty($content)) {
            $viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
        } else {
            $viewer->view('dashboards/DashBoardWidget.tpl', $moduleName);
        }
    }
}
