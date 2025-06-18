<?php

class VReports_Gauge_Model extends Vtiger_MiniList_Model
{
    public function getListDetailReport()
    {
        global $adb;
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $result = $adb->pquery('SELECT * FROM vtiger_vreport WHERE reporttype = ?', ['tabular']);
        if ($adb->num_rows($result) > 0) {
            while ($row = $adb->fetch_array($result)) {
                $isRecordShared = true;
                $reportModel = VReports_Record_Model::getCleanInstance($row['reportid']);
                $owner = $reportModel->get('owner');
                $sharingType = $reportModel->get('sharingtype');
                if ($currentUserPriviligesModel->id != $owner && $sharingType == 'Private') {
                    $isRecordShared = $reportModel->isRecordHasViewAccess($sharingType);
                }
                if ($isRecordShared) {
                    $report[$row['reportid']] = $row['reportname'];
                }
            }
        }

        return $report;
    }

    public function getColumnsDetailReport($reportId)
    {
        global $adb;
        $listColumn['count_record'] = vtranslate('LBL_RECORD_COUNT', 'VReports');
        $result = $adb->pquery("select vtiger_vreportsummary.* from vtiger_vreport inner join \r\n                                    vtiger_vreportsummary on vtiger_vreport.reportid = vtiger_vreportsummary.reportsummaryid \r\n                                    where vtiger_vreport.reportid = ?", [$reportId]);
        if ($adb->num_rows($result) > 0) {
            while ($row = $adb->fetch_array($result)) {
                $listColumn[$row['columnname']] = $this->getFieldName($row['columnname']);
            }
        }

        return $listColumn;
    }

    public function getFieldName($fieldInfo)
    {
        global $adb;
        $fieldTable = explode(':', $fieldInfo);
        $fieldTable = $fieldTable[1];
        $fieldName = explode(':', $fieldInfo);
        $fieldName = $fieldName[3];
        $queryGetModule = $adb->pquery('SELECT modulename FROM vtiger_entityname WHERE tablename = ?', [$fieldTable]);
        $moduleReportName = $adb->query_result($queryGetModule, 0, 'modulename');

        return $fieldName;
    }

    public function calculateGaugeData($data)
    {
        global $adb;
        if (json_decode($data) != null) {
            $data = json_decode($data);
            $reportId = $data->targetReport;
            $fieldInfo = $data->dataGauge;
        } else {
            $reportId = $data['targetReport'];
            $fieldInfo = $data['dataGauge'];
        }
        $reportModel = VReports_Record_Model::getInstanceById($reportId);
        $calculation = $reportModel->getReportCalulationData();
        if ($fieldInfo != 'count_record') {
            $fieldTable = explode(':', $fieldInfo);
            $fieldTable = $fieldTable[1];
            $fieldGetData = explode(':', $fieldInfo);
            $fieldGetData = $fieldGetData[3];
            $queryGetModule = $adb->pquery('SELECT vtiger_tab.`name` FROM vtiger_tab INNER JOIN vtiger_field USING(tabid) WHERE vtiger_field.tablename = ? LIMIT 1', [$fieldTable]);
            $moduleReportName = $adb->query_result($queryGetModule, 0, 0);
            $gaugeFieldTemp = $moduleReportName . '_' . $fieldGetData;
        } else {
            $gaugeFieldTemp = $fieldInfo;
        }
        $gaugeField = '';
        $gaugeValue = 0;
        foreach ($calculation as $index => $value) {
            foreach ($value as $fieldName => $fieldValue) {
                if ($fieldName == $gaugeFieldTemp) {
                    $gaugeValue = $fieldValue;
                    $gaugeField = $gaugeFieldTemp;
                }
            }
        }
        if ($gaugeValue == '') {
            $gaugeValue = 0;
        }

        return [$gaugeField => $gaugeValue];
    }

    public static function getValueByName($widget, $name)
    {
        $value = json_decode(html_entity_decode($widget->get('data')));

        return $value->{$name};
    }

    public static function formatFinalValue($value, $decimal, $formatLargeNumber)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $decimal_separator = $currentUser->get('currency_decimal_separator');
        $grouping_separator = $currentUser->get('currency_grouping_separator');
        $userCurrencyInfo = getCurrencySymbolandCRate($currentUser->get('currency_id'));
        $userCurrencySymbol = $userCurrencyInfo['symbol'];
        if ($formatLargeNumber == 1 || $decimal != '') {
            $value = trim($value, $userCurrencySymbol);
            $value = str_replace($grouping_separator, '', $value);
            $value = str_replace($decimal_separator, '.', $value);
            $value = floatval($value);
        }
        $symbol = '';
        if ($formatLargeNumber == 1) {
            if ($value > 1_000_000_000_000.0) {
                $value = round($value / 1_000_000_000_000.0, 2);
                $symbol = ' T';
            } else {
                if ($value > 1_000_000_000) {
                    $value = round($value / 1_000_000_000, 2);
                    $symbol = ' B';
                } else {
                    if ($value > 1_000_000) {
                        $value = round($value / 1_000_000, 2);
                        $symbol = ' M';
                    } else {
                        if ($value > 1_000) {
                            $value = round($value / 1_000, 2);
                            $symbol = ' K';
                        }
                    }
                }
            }
        }
        if ($decimal != '') {
            if ($decimal == 0) {
                $value = number_format($value, 0);
            } else {
                if ($decimal == 1) {
                    $value = number_format($value, 1);
                } else {
                    if ($decimal == 2) {
                        $value = number_format($value, 2);
                    } else {
                        if ($decimal == 3) {
                            $value = number_format($value, 3);
                        }
                    }
                }
            }
        }

        return $value . $symbol;
    }

    public function autoUpdateSize($widgetId)
    {
        global $adb;
        $adb->pquery('UPDATE vtiger_module_vreportdashboard_widgets SET sizeHeight = ? , sizeWidth = ? WHERE id = ?', [2, 2, $widgetId]);
    }
}
