<?php

chdir('../../');
require_once 'include/utils/utils.php';
require_once 'include/utils/CommonUtils.php';
require_once 'includes/Loader.php';
vimport('includes.runtime.EntryPoint');
$result = $adb->pquery('SELECT * FROM vtiger_vreport');

while ($row = $adb->fetchByAssoc($result)) {
    $tabId = getTabid('VReports');
    $type = 'DASHBOARDWIDGET';
    $label = $row['reportname'];
    $reporttype = $row['reporttype'];
    $reportId = $row['reportid'];
    $linkid = $adb->getUniqueID('vtiger_links') + 1;
    if ($reporttype == 'chart') {
        $url = 'index.php?module=VReports&action=ChartActions&mode=pinToDashboard&reportid=' . $reportId . '&linkid=' . $linkid;
    } else {
        if ($reporttype == 'tabular') {
            $url = 'index.php?module=VReports&action=TabularActions&mode=pinToDashboard&reportid=' . $reportId . '&linkid=' . $linkid;
        } else {
            $url = 'index.php?module=VReports&action=PivotActions&mode=pinToDashboard&reportid=' . $reportId . '&linkid=' . $linkid;
        }
    }
    Vtiger_Link::addLink($tabId, $type, $label, $url);
}
