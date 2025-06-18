<?php

class VReports_ChartSaveAjax_View extends Vtiger_IndexAjax_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $record = $request->get('record');
        if (!$record) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $reportModel = VReports_Record_Model::getCleanInstance($record);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $reportModel = VReports_Record_Model::getInstanceById($record);
        $reportModel->setModule('VReports');
        $reportModel->set('advancedFilter', $request->get('advanced_filter'));
        $secondaryModules = $reportModel->getSecondaryModules();
        if (empty($secondaryModules)) {
            $viewer->assign('CLICK_THROUGH', true);
        }
        $dataFields = $request->get('datafields', 'count(*)');
        if (is_string($dataFields)) {
            $dataFields = [$dataFields];
        }
        $reportModel->set('reporttypedata', Zend_Json::encode(['type' => $request->get('charttype', 'pieChart'), 'legendposition' => $request->get('legendposition'), 'displaygrid' => $request->get('displaygrid'), 'displaylabel' => $request->get('displaylabel'), 'legendvalue' => $request->get('legendvalue'), 'formatlargenumber' => $request->get('formatlargenumber'), 'drawline' => $request->get('drawline'), 'groupbyfield' => $request->get('groupbyfield'), 'datafields' => $dataFields]));
        $reportModel->set('sort_by', $request->get('sort_by'));
        $reportModel->set('limit', $request->get('limit'));
        $reportModel->set('order_by', $request->get('order_by'));
        $reportModel->set('reporttype', 'chart');
        $reportModel->save();
        $reportChartModel = VReports_Chart_Model::getInstanceById($reportModel);
        $reportChartModel->set('call_from', 'ChartSaveAjax');
        $data = $reportChartModel->getData();
        if ($data) {
            $dataChart = true;
        } else {
            $dataChart = false;
        }
        $viewer->assign('DATA_CHART', $dataChart);
        $viewer->assign('CHART_TYPE', $reportChartModel->getChartType());
        $viewer->assign('DATA', $data);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('REPORT_CHART_MODEL', $reportChartModel);
        if ($reportModel->get('position')) {
            $viewer->assign('POSITION', json_decode(html_entity_decode($reportModel->get('position'))));
        }
        $isPercentExist = false;
        $selectedDataFields = $reportChartModel->get('datafields');
        foreach ($selectedDataFields as $dataField) {
            [$tableName, $columnName, $moduleField, $fieldName, $single] = explode(':', $dataField);
            [$relModuleName, $fieldLabel] = explode('_', $moduleField);
            $relModuleModel = Vtiger_Module_Model::getInstance($relModuleName);
            $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $relModuleModel);
            if ($fieldModel && $fieldModel->getFieldDataType() != 'currency') {
                $isPercentExist = true;
                break;
            }
            if (!$fieldModel) {
                $isPercentExist = true;
            }
        }
        $yAxisFieldDataType = !$isPercentExist ? 'currency' : '';
        $viewer->assign('YAXIS_FIELD_TYPE', $yAxisFieldDataType);
        $viewer->view('ChartReportContents.tpl', $moduleName);
    }
}
