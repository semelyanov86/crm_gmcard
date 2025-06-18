<?php

class VReports_Save_Action extends Vtiger_Save_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        $record = $request->get('record');
        if ($record) {
            $reportModel = VReports_Record_Model::getCleanInstance($record);
            if (!$reportModel->isEditable()) {
                throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
            }
        }
    }

    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $reportModel = new VReports_Record_Model();
        $reportModel->setModule('VReports');
        if (!empty($record) && !$request->get('isDuplicate')) {
            $reportModel->setId($record);
        }
        $reporttype = $request->get('reporttype');
        if (empty($reporttype)) {
            $reporttype = 'tabular';
        }
        $reportModel->set('reportname', $request->get('reportname'));
        $reportModel->set('folderid', $request->get('folderid'));
        $reportModel->set('description', $request->get('reports_description'));
        $reportModel->set('query', $request->get('report_query'));
        $reportModel->set('reporttype', $reporttype);
        $reportModel->setPrimaryModule($request->get('primary_module'));
        $secondaryModules = $request->get('secondary_modules');
        $secondaryModules = implode(':', $secondaryModules);
        $reportModel->setSecondaryModule($secondaryModules);
        $reportModel->set('selectedFields', $request->get('selected_fields'));
        $reportModel->set('sortFields', $request->get('selected_sort_fields'));
        $reportModel->set('calculationFields', $request->get('selected_calculation_fields'));
        $reportModel->set('rename_columns', $request->get('rename_columns'));
        $reportModel->set('standardFilter', $request->get('standard_fiter'));
        $reportModel->set('advancedFilter', $request->get('advanced_filter'));
        $reportModel->set('advancedGroupFilterConditions', $request->get('advanced_group_condition'));
        $reportModel->set('members', $request->get('members'));
        $reportModel->save();
        $scheduleReportModel = new VReports_ScheduleReports_Model();
        $scheduleReportModel->set('scheduleid', $request->get('schtypeid'));
        $scheduleReportModel->set('schtime', date('H:i', strtotime($request->get('schtime'))));
        $scheduleReportModel->set('schdate', $request->get('schdate'));
        $scheduleReportModel->set('schdayoftheweek', $request->get('schdayoftheweek'));
        $scheduleReportModel->set('schdayofthemonth', $request->get('schdayofthemonth'));
        $scheduleReportModel->set('schannualdates', $request->get('schannualdates'));
        $scheduleReportModel->set('reportid', $reportModel->getId());
        $scheduleReportModel->set('recipients', $request->get('recipients'));
        $scheduleReportModel->set('isReportScheduled', $request->get('enable_schedule'));
        $scheduleReportModel->set('specificemails', $request->get('specificemails'));
        $scheduleReportModel->set('from_address', $request->get('from_address'));
        $scheduleReportModel->set('subject_mail', $request->get('subject_mail'));
        $scheduleReportModel->set('content_mail', $request->getRaw('content_mail'));
        $scheduleReportModel->set('signature', $request->get('signature'));
        $scheduleReportModel->set('signature_user', $request->get('signature_user'));
        $scheduleReportModel->set('fileformat', $request->get('fileformat'));
        $scheduleReportModel->saveScheduleReport();
        $loadUrl = $reportModel->getDetailViewUrl();
        header('Location: ' . $loadUrl);
    }
}
