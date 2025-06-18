<?php

class VReports_GaugeWizard_View extends Vtiger_MiniListWizard_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function process(Vtiger_Request $request)
    {
        $widgetName = $request->get('widgetName');
        $viewer = $this->getViewer($request);
        $moduleName = $request->get('module');
        $gaugeModel = new VReports_Gauge_Model();
        $viewer->assign('GAUGE_WIZARD_STEP', $request->get('step'));
        $viewer->assign('WIDGET_NAME', $widgetName);
        $viewer->assign('WIDGET_MODE', 'Settings');
        $viewer->assign('WIDGET_FORM', 'Create');
        $viewer->assign('MODULE', $moduleName);
        $input_lines = file_get_contents('layouts/v7/lib/vt-icons/style.css');
        preg_match_all('/(.vicon-[a-z0-9]+-[a-z0-9]+)|(.vicon-[a-z0-9_]+)|(\\\\[a-z0-9]+)/', $input_lines, $output_array);
        $output_array = $output_array[0];
        unset($output_array[0]);
        $arrResults = array_chunk($output_array, 2);
        $arrIconClasses = [];
        foreach ($arrResults as $cssDetail) {
            $arrIconClasses[str_replace('.', '', $cssDetail[0])] = $cssDetail[1];
        }
        $viewer->assign('LISTICONS', $arrIconClasses);
        switch ($request->get('step')) {
            case 'step1':
                $detailReports = $gaugeModel->getListDetailReport();
                $viewer->assign('ALL_DETAIL_REPORTS', $detailReports);
                break;
            case 'step2':
                $reportId = $request->get('selectedReport');
                $viewer->assign('CALCULATION_FIELDS', $gaugeModel->getColumnsDetailReport($reportId));
                $viewer->assign('WIDGET_MODE', 'Create');
                break;
        }
        $viewer->view('dashboards/MiniListWizard.tpl', $moduleName);
    }
}
