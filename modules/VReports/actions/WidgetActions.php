<?php

/**
 * Date: 12/18/18
 * Time: 4:51 PM.
 */
class VReports_WidgetActions_Action extends Vtiger_Action_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('removeWidget');
        $this->exposeMethod('saveSettingWidget');
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    public function removeWidget(Vtiger_Request $request)
    {
        $reportid = $request->get('reportid');
        $widgetInstance = VReports_Widget_Model::getInstanceWithReportId($reportid);
        $widgetInstance->remove();
        $response = new Vtiger_Response();
        $response->setResult(true);
        $response->emit();
    }

    public function saveSettingWidget(Vtiger_Request $request)
    {
        $widgetRecord = $request->get('widgetRecord');
        $widgetData = json_encode($request->get('data'));
        $selectedColor = $request->get('selectedColor');
        $timeRefresh = $request->get('timeRefresh');
        $titleWidget = $request->get('titleWidget');
        $showEmptyVal = $request->get('showEmptyVal');
        $minHeight = $request->get('minHeight');
        $maxHeight = $request->get('maxHeight');
        VReports_Widget_Model::updateSettingWidget($widgetRecord, $selectedColor, $timeRefresh, $widgetData, $showEmptyVal, $titleWidget, $minHeight, $maxHeight);
        $response = new Vtiger_Response();
        $response->setResult(true);
        $response->emit();
    }
}
