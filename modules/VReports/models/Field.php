<?php

class VReports_Field_Model extends Vtiger_Field_Model
{
    public static function getPicklistValueByField($fieldName)
    {
        $picklistValues = false;
        if ($fieldName == 'reporttype') {
            $picklistValues = ['tabular' => vtranslate('tabular', 'VReports'), 'chart' => vtranslate('chart', 'VReports'), 'pivot' => vtranslate('pivot', 'VReports'), 'sql' => vtranslate('sqlreport', 'VReports')];
        } else {
            if ($fieldName == 'foldername') {
                $allFolders = VReports_Folder_Model::getAll();
                foreach ($allFolders as $folder) {
                    $picklistValues[$folder->get('folderid')] = vtranslate($folder->get('foldername'), 'VReports');
                }
            } else {
                if ($fieldName == 'owner') {
                    $currentUserModel = Users_Record_Model::getCurrentUserModel();
                    $allUsers = $currentUserModel->getAccessibleUsers();
                    foreach ($allUsers as $userId => $userName) {
                        $picklistValues[$userId] = $userName;
                    }
                } else {
                    if ($fieldName == 'primarymodule') {
                        $reportModel = VReports_Record_Model::getCleanInstance();
                        $picklistValues = $reportModel->getModulesList();
                    }
                }
            }
        }

        return $picklistValues;
    }

    public static function getFieldInfoByField($fieldName)
    {
        $fieldInfo = ['mandatory' => false, 'presence' => true, 'quickcreate' => false, 'masseditable' => false, 'defaultvalue' => false];
        if ($fieldName == 'reportname') {
            $fieldInfo['type'] = 'string';
            $fieldInfo['name'] = $fieldName;
            $fieldInfo['label'] = 'Report Name';
        } else {
            if ($fieldName == 'description') {
                $fieldInfo['type'] = 'string';
                $fieldInfo['name'] = $fieldName;
                $fieldInfo['label'] = 'Description';
            } else {
                if ($fieldName == 'reporttype') {
                    $fieldInfo['type'] = 'picklist';
                    $fieldInfo['name'] = $fieldName;
                    $fieldInfo['label'] = 'Report Type';
                    $fieldInfo['picklistvalues'] = self::getPicklistValueByField($fieldName);
                } else {
                    if ($fieldName == 'foldername') {
                        $fieldInfo['type'] = 'picklist';
                        $fieldInfo['name'] = $fieldName;
                        $fieldInfo['label'] = 'LBL_FOLDER_NAME';
                        $fieldInfo['picklistvalues'] = self::getPicklistValueByField($fieldName);
                    } else {
                        $fieldInfo = false;
                    }
                }
            }
        }

        return $fieldInfo;
    }

    public static function getDateFilterTypes()
    {
        $dateFilters = ['prevfy' => ['label' => 'LBL_PREVIOUS_FY'], 'thisfy' => ['label' => 'LBL_CURRENT_FY'], 'nextfy' => ['label' => 'LBL_NEXT_FY'], 'prevfq' => ['label' => 'LBL_PREVIOUS_FQ'], 'thisfq' => ['label' => 'LBL_CURRENT_FQ'], 'nextfq' => ['label' => 'LBL_NEXT_FQ'], 'yesterday' => ['label' => 'LBL_YESTERDAY'], 'today' => ['label' => 'LBL_TODAY'], 'tomorrow' => ['label' => 'LBL_TOMORROW'], 'lastweek' => ['label' => 'LBL_LAST_WEEK'], 'thisweek' => ['label' => 'LBL_CURRENT_WEEK'], 'nextweek' => ['label' => 'LBL_NEXT_WEEK'], 'lastmonth' => ['label' => 'LBL_LAST_MONTH'], 'thismonth' => ['label' => 'LBL_CURRENT_MONTH'], 'nextmonth' => ['label' => 'LBL_NEXT_MONTH'], 'last7days' => ['label' => 'LBL_LAST_7_DAYS'], 'last14days' => ['label' => 'LBL_LAST_14_DAYS'], 'last30days' => ['label' => 'LBL_LAST_30_DAYS'], 'last60days' => ['label' => 'LBL_LAST_60_DAYS'], 'last90days' => ['label' => 'LBL_LAST_90_DAYS'], 'last120days' => ['label' => 'LBL_LAST_120_DAYS'], 'next30days' => ['label' => 'LBL_NEXT_30_DAYS'], 'next60days' => ['label' => 'LBL_NEXT_60_DAYS'], 'next90days' => ['label' => 'LBL_NEXT_90_DAYS'], 'next120days' => ['label' => 'LBL_NEXT_120_DAYS']];
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $userPeferredDayOfTheWeek = $currentUserModel->get('dayoftheweek');
        foreach ($dateFilters as $filterType => $filterDetails) {
            $dateValues = self::getDateForStdFilterBytype($filterType, $userPeferredDayOfTheWeek);
            [$dateFilters[$filterType]['startdate'], $dateFilters[$filterType]['enddate']] = $dateValues;
        }

        return $dateFilters;
    }

    public static function getListViewFieldsInfo()
    {
        $fields = ['reporttype', 'reportname', 'foldername', 'description'];
        $fieldsInfo = [];
        foreach ($fields as $field) {
            $fieldsInfo[$field] = VReports_Field_Model::getFieldInfoByField($field);
        }

        return Zend_Json::encode($fieldsInfo);
    }
}
