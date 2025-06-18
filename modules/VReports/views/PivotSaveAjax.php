<?php

class VReports_PivotSaveAjax_View extends Vtiger_IndexAjax_View
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
        global $site_URL;
        global $current_user;
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
        $dataFieldsChart = $request->get('datafields-chart', 'count(*)');
        if (is_string($dataFieldsChart)) {
            $dataFieldsChart = [$dataFieldsChart];
        }
        $reportModel->set('reporttypedata', Zend_Json::encode(['groupbyfield_rows' => $request->get('groupbyfield_rows'), 'groupbyfield_columns' => $request->get('groupbyfield_columns'), 'datafields' => $dataFields, 'type' => $request->get('charttype'), 'legendposition' => $request->get('legendposition'), 'displaygrid' => $request->get('displaygrid'), 'displaylabel' => $request->get('displaylabel'), 'formatlargenumber' => $request->get('formatlargenumber'), 'legendvalue' => $request->get('legendvalue'), 'drawline' => $request->get('drawline'), 'groupbyfield' => $request->get('groupbyfield'), 'datafields-chart' => $dataFieldsChart]));
        $renamefield = $request->get('renameDataValue');
        $reportModel->set('sort_by', $request->get('sort_by'));
        $reportModel->set('limit', $request->get('limit'));
        $reportModel->set('order_by', $request->get('order_by'));
        $reportModel->set('rename_field', json_encode($renamefield));
        $reportModel->set('reporttype', 'pivot');
        $reportModel->save();
        $reportPivotModel = VReports_Pivot_Model::getInstanceById($reportModel);
        $reportChartModel = VReports_Chart_Model::getInstanceById($reportModel);
        $data = $reportPivotModel->getData();
        $reportChartModel->set('datafields', $reportChartModel->get('datafields-chart'));
        $tempVal = $reportChartModel->get('datafields');
        if ($tempVal[0] != 'null' && $reportChartModel->get('type')) {
            $dataChart = $reportChartModel->getData();
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
        }
        $viewer->assign('SITE_URL', $site_URL);
        $viewer->assign('DATA', $data);
        if ($dataChart) {
            $dataChartCheck = true;
        } else {
            $dataChart = '';
            $dataChartCheck = false;
        }
        $viewer->assign('CHART_TYPE', $reportChartModel->getChartType());
        $viewer->assign('DATA_CHART', $dataChart);
        $viewer->assign('DATA_CHART_CHECK', $dataChartCheck);
        $viewer->assign('RECORD_ID', $record);
        $viewer->assign('REPORT_MODEL', $reportModel);
        $viewer->assign('SECONDARY_MODULES', $secondaryModules);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('CURRENT_USER', $current_user);
        $viewer->view('PivotReportContents.tpl', $moduleName);
    }
}
