<?php

class VReports_ChartSave_Action extends VReports_Save_Action
{
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $record = $request->get('record');
        $reportModel = new VReports_Record_Model();
        $reportModel->setModule('VReports');
        if (!empty($record) && !$request->get('isDuplicate')) {
            $reportModel->setId($record);
        }
        $reportModel->set('reportname', $request->get('reportname'));
        $reportModel->set('folderid', $request->get('folderid'));
        $reportModel->set('description', $request->get('reports_description'));
        $reportModel->set('members', $request->get('members'));
        $reportModel->setPrimaryModule($request->get('primary_module'));
        $secondaryModules = $request->get('secondary_modules');
        $secondaryModules = implode(':', $secondaryModules);
        $reportModel->setSecondaryModule($secondaryModules);
        $reportModel->set('advancedFilter', $request->get('advanced_filter'));
        $reportModel->set('reporttype', 'chart');
        $dataFields = $request->get('datafields', 'count(*)');
        if (is_string($dataFields)) {
            $dataFields = [$dataFields];
        }
        $reportModel->set('reporttypedata', Zend_Json::encode(['type' => $request->get('charttype', 'pieChart'), 'legendposition' => $request->get('legendposition'), 'displaygrid' => $request->get('displaygrid'), 'displaylabel' => $request->get('displaylabel'), 'legendvalue' => $request->get('legendvalue'), 'formatlargenumber' => $request->get('formatlargenumber'), 'drawline' => $request->get('drawline'), 'groupbyfield' => $request->get('groupbyfield'), 'datafields' => $dataFields]));
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
        $scheduleReportModel->saveScheduleReport();
        $loadUrl = $reportModel->getDetailViewUrl();
        header('Location: ' . $loadUrl);
    }
}
