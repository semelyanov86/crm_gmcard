<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Reports_DetailView_Model extends Vtiger_DetailView_Model
{
    /**
     * Function to get the instance.
     * @param <String> $moduleName - module name
     * @param <String> $recordId - record id
     * @return <Vtiger_DetailView_Model>
     */
    public static function getInstance($moduleName, $recordId)
    {
        $modelClassName = Vtiger_Loader::getComponentClassName('Model', 'DetailView', $moduleName);
        $instance = new $modelClassName();

        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $recordModel = Reports_Record_Model::getCleanInstance($recordId, $moduleName);

        return $instance->setModule($moduleModel)->setRecord($recordModel);
    }

    /**
     * Function to get the detail view links (links and widgets).
     * @param <array> $linkParams - parameters which will be used to calicaulate the params
     * @return <array> - array of link models in the format as below
     *                   array('linktype'=>list of link models);
     */
    public function getDetailViewLinks($linkParams = '')
    {
        $currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

        $moduleModel = $this->getModule();
        $recordModel = $this->getRecord();
        $moduleName = $moduleModel->getName();

        $detailViewLinks = [];
        $printPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'Print');
        if ($printPermission) {
            $detailViewLinks[] = [
                'linklabel' => vtranslate('LBL_REPORT_PRINT', $moduleName),
                'linkurl' => $recordModel->getReportPrintURL(),
                'linkicon' => 'print.png',
            ];
        }

        $exportPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'Export');
        $primaryModuleExportPermission = Users_Privileges_Model::isPermitted($recordModel->getPrimaryModule(), 'Export');
        if ($exportPermission && $primaryModuleExportPermission) {
            $detailViewLinks[] = [
                'linklabel' => vtranslate('LBL_REPORT_CSV', $moduleName),
                'linkurl' => $recordModel->getReportCSVURL(),
                'linkicon' => 'csv.png',
            ];


            $detailViewLinks[] = [
                'linklabel' => vtranslate('LBL_REPORT_EXPORT_EXCEL', $moduleName),
                'linkurl' => $recordModel->getReportExcelURL(),
                'linkicon' => 'xlsx.png',
            ];
        }

        $linkModelList = [];
        foreach ($detailViewLinks as $detailViewLinkEntry) {
            $linkModelList[] = Vtiger_Link_Model::getInstanceFromValues($detailViewLinkEntry);
        }

        return $linkModelList;
    }

    /**
     * Function to get the detail view Actions (links and widgets) for Report.
     * @return <array> - array of link models in the format as below
     *                   array('linktype'=>list of link models);
     */
    public function getDetailViewActions()
    {
        $moduleModel = $this->getModule();
        $recordModel = $this->getRecord();
        $moduleName = $moduleModel->getName();

        $detailViewActions = [];
        if ($recordModel->isEditableBySharing()) {
            $detailViewActions[] = [
                'linklabel' => vtranslate('LBL_CUSTOMIZE', $moduleName),
                'linktitle' => vtranslate('LBL_CUSTOMIZE', $moduleName),
                'linkurl' => $recordModel->getEditViewUrl(),
                'linkiconclass' => 'icon-pencil',
            ];
        }
        if ($recordModel->getReportType() == 'chart') {
            $detailViewActions[] = [
                'linktitle' => vtranslate('LBL_PIN_CHART_TO_DASHBOARD', $moduleName),
                'customclass' => 'pinToDashboard',
                'linkiconclass' => 'vtGlyph vticon-attach',
            ];
        }
        if ($recordModel->isEditableBySharing()) {
            $detailViewActions[] = [
                'linklabel' => vtranslate('LBL_DUPLICATE', $moduleName),
                'linkurl' => $recordModel->getDuplicateRecordUrl(),
            ];
        }

        $linkModelList = [];
        foreach ($detailViewActions as $detailViewLinkEntry) {
            $linkModelList[] = Vtiger_Link_Model::getInstanceFromValues($detailViewLinkEntry);
        }

        return $linkModelList;
    }

    /**
     * Function to get the detail view widgets.
     * @return <Array> - List of widgets , where each widget is an Vtiger_Link_Model
     */
    public function getWidgets()
    {
        $moduleModel = $this->getModule();
        $widgets = [];

        if ($moduleModel->isTrackingEnabled()) {
            $widgets[] = [
                'linktype' => 'DETAILVIEWWIDGET',
                'linklabel' => 'LBL_RECENT_ACTIVITIES',
                'linkurl' => 'module=' . $this->getModuleName() . '&view=Detail&record=' . $this->getRecord()->getId()
                    . '&mode=showRecentActivities&page=1&limit=5',
            ];
        }

        $widgetLinks = [];
        foreach ($widgets as $widgetDetails) {
            $widgetLinks[] = Vtiger_Link_Model::getInstanceFromValues($widgetDetails);
        }

        return $widgetLinks;
    }
}
