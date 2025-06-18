<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Reports_Field_Model extends Vtiger_Field_Model
{
    public static function getPicklistValueByField($fieldName)
    {
        $picklistValues = [];
        if ($fieldName == 'reporttype') {
            $picklistValues = [
                'tabular'	=> vtranslate('tabular', 'Reports'),
                'chart'		=> vtranslate('chart', 'Reports'),
            ];
        } elseif ($fieldName == 'foldername') {
            $allFolders = Reports_Folder_Model::getAll();
            foreach ($allFolders as $folder) {
                $picklistValues[$folder->get('folderid')] = vtranslate($folder->get('foldername'), 'Reports');
            }
        } elseif ($fieldName == 'owner') {
            $currentUserModel = Users_Record_Model::getCurrentUserModel();
            $allUsers = $currentUserModel->getAccessibleUsers();
            foreach ($allUsers as $userId => $userName) {
                $picklistValues[$userId] = $userName;
            }
        } elseif ($fieldName == 'primarymodule') {
            $reportModel = Reports_Record_Model::getCleanInstance();
            $picklistValues = $reportModel->getModulesList();
        }

        return $picklistValues;
    }

    public static function getFieldInfoByField($fieldName)
    {
        $fieldInfo = [
            'mandatory' => false,
            'presence' => true,
            'quickcreate' => false,
            'masseditable' => false,
            'defaultvalue' => false,
        ];
        if ($fieldName == 'reportname') {
            $fieldInfo['type'] = 'string';
            $fieldInfo['name'] = $fieldName;
            $fieldInfo['label'] = 'Report Name';
        } elseif ($fieldName == 'description') {
            $fieldInfo['type'] = 'string';
            $fieldInfo['name'] = $fieldName;
            $fieldInfo['label'] = 'Description';
        } elseif ($fieldName == 'reporttype') {
            $fieldInfo['type'] = 'picklist';
            $fieldInfo['name'] = $fieldName;
            $fieldInfo['label'] = 'Report Type';
            $fieldInfo['picklistvalues'] = self::getPicklistValueByField($fieldName);
        } elseif ($fieldName == 'foldername') {
            $fieldInfo['type'] = 'picklist';
            $fieldInfo['name'] = $fieldName;
            $fieldInfo['label'] = 'LBL_FOLDER_NAME';
            $fieldInfo['picklistvalues'] = self::getPicklistValueByField($fieldName);
        } else {
            $fieldInfo = false;
        }

        return $fieldInfo;
    }

    public static function getListViewFieldsInfo()
    {
        $fields = ['reporttype', 'reportname', 'foldername', 'description'];
        $fieldsInfo = [];
        foreach ($fields as $field) {
            $fieldsInfo[$field] = Reports_Field_Model::getFieldInfoByField($field);
        }

        return Zend_Json::encode($fieldsInfo);
    }
}
