<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Settings_MailConverter_BodyRule_Model extends Settings_MailConverter_RuleRecord_Model
{
    public $_bodyruleTable = 'vtiger_mailscanner_bodyrule';

    public $_bodyruleMappingTable = 'vtiger_mailscanner_mapping';

    public static function getCleanInstance($qualifiedModuleName = 'Settings:MailConverter')
    {
        $modelClassName = Vtiger_Loader::getComponentClassName('Model', 'BodyRule', $qualifiedModuleName);

        return new $modelClassName();

    }

    public function getBodyRuleDelimiter()
    {
        $delimiter = [
            'colon' => ['symbol' => ':', 'label' => 'LBL_COLON'],
            'semicolon' => ['symbol' => ';', 'label' => 'LBL_SEMICOLON'],
            'dash' => ['symbol' => '-', 'label' => 'LBL_DASH'],
            'equal' => ['symbol' => '=', 'label' => 'LBL_EQUAL'],
            'lessthan' => ['symbol' => '>', 'label' => 'LBL_GREATER_THAN'],
            'colondash' => ['symbol' => ':-', 'label' => 'LBL_COLON_DASH'],
            'colonequal' => ['symbol' => ':=', 'label' => 'LBL_COLON_EQUAL'],
            'semicolondash' => ['symbol' => ';-', 'label' => 'LBL_SEMICOLON_DASH'],
            'semicolonequal' => ['symbol' => ';=', 'label' => 'LBL_SEMICOLON_EQUAL'],
            'equallessthan' => ['symbol' => '=>', 'label' => 'LBL_EQUAL_GREATER_THAN'],
        ];

        return $delimiter;
    }

    public static function getModuleFields($action)
    {
        $module = Settings_MailConverter_BodyRule_Model::getModuleNameByAction($action);
        if (!$module) {
            return [];
        }
        $fields = [];
        $moduleInstance = Vtiger_Module_Model::getInstance($module);
        $fieldInstances = Vtiger_Field_Model::getAllForModule($moduleInstance);
        foreach ($fieldInstances as $blockInstance) {
            foreach ($blockInstance as $fieldInstance) {
                if ($fieldInstance->isEditable()) {
                    $fieldName = $fieldInstance->getName();
                    $uiType = $fieldInstance->get('uitype');
                    $displayType = $fieldInstance->getDisplayType();
                    $dataType = $fieldInstance->getFieldDataType();
                    $blockedUiType = [4, 52, 53, 69, 105];
                    $blockedDisplayType = [2, 3];
                    if (!in_array($uiType, $blockedUiType) && !in_array($displayType, $blockedDisplayType)
                            && $dataType != 'boolean' && $dataType != 'reference' && $fieldName != 'comments') {
                        $fields[$fieldName] = vtranslate($fieldInstance->get('label'), $module);
                    }
                }
            }
        }

        return $fields;
    }

    public static function parseBody($bodyText, $delimeter)
    {
        $bodyFields = ['Subject', 'From Email', 'From Name', 'Email Content'];
        $rows = explode("\n", $bodyText);
        foreach ($rows as $row) {
            if (strrpos($row, $delimeter)) {
                $columns = explode($delimeter, $row);
                $bodyFields[] = decode_html(trim($columns[0]));
            }
        }

        return array_unique($bodyFields);
    }

    public static function getModuleNameByAction($action)
    {
        if ($action == 'CREATE_HelpDesk_FROM' || $action == 'CREATE_HelpDeskNoContact_FROM') {
            $module = 'HelpDesk';
        } elseif ($action == 'CREATE_Leads_SUBJECT') {
            $module = 'Leads';
        } elseif ($action == 'CREATE_Contacts_SUBJECT') {
            $module = 'Contacts';
        } elseif ($action == 'CREATE_Accounts_SUBJECT') {
            $module = 'Accounts';
        } elseif ($action == 'CREATE_Potentials_SUBJECT' || $action == 'CREATE_PotentialsNoContact_SUBJECT') {
            $module = 'Potentials';
        } else {
            $module = false;
        }

        return $module;
    }

    public function saveBodyRule()
    {
        $scannerId = $this->get('scannerId');
        $ruleId = $this->get('ruleId');
        $delimiter = $this->get('delimiter');
        $mappingData = $this->get('filedsMapping');
        $action = $this->get('action');
        $body = $this->get('body');
        $moduleName = $this->getModuleNameByAction($action);
        $db = PearDatabase::getInstance();

        $db->pquery("DELETE FROM {$this->_bodyruleTable} WHERE ruleid = ? AND scannerid = ?", [$ruleId, $scannerId]);
        $db->pquery("DELETE FROM {$this->_bodyruleMappingTable} WHERE ruleid = ? AND scannerid = ?", [$ruleId, $scannerId]);
        $db->pquery("INSERT INTO {$this->_bodyruleTable} VALUES (?, ?, ?, ?, ?)", [$ruleId, $scannerId, $delimiter, $moduleName, $body]);
        foreach ($mappingData as $bodyField => $crmField) {
            $db->pquery("INSERT INTO {$this->_bodyruleMappingTable} VALUES (?, ?, ?, ?)", [$ruleId, $scannerId, $crmField, $bodyField]);
        }
    }

    public function getDelimiter()
    {
        $db = PearDatabase::getInstance();
        $params = [$this->get('ruleid'), $this->get('scannerid')];
        $result = $db->pquery("SELECT delimiter FROM {$this->_bodyruleTable} WHERE ruleid = ? AND scannerid = ?", $params);
        if ($db->num_rows($result) > 0) {
            return decode_html(decode_html($db->query_result($result, 0, 'delimiter')));
        }
    }

    public function getBody()
    {
        $db = PearDatabase::getInstance();
        $params = [$this->get('ruleid'), $this->get('scannerid')];
        $result = $db->pquery("SELECT body FROM {$this->_bodyruleTable} WHERE ruleid = ? AND scannerid = ?", $params);
        if ($db->num_rows($result) > 0) {
            return decode_html($db->query_result($result, 0, 'body'));
        }
    }

    public function getMapping()
    {
        $db = PearDatabase::getInstance();
        $params = [$this->get('ruleid'), $this->get('scannerid')];
        $result = $db->pquery("SELECT crm_field, body_field FROM {$this->_bodyruleMappingTable} WHERE ruleid = ? AND scannerid = ?", $params);
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; ++$i) {
            $crmField = decode_html($db->query_result($result, $i, 'crm_field'));
            $bodyField = decode_html($db->query_result($result, $i, 'body_field'));
            $data[$bodyField] = $crmField;
        }

        return $data;
    }

    public function deleteBodyRule($scannerId, $ruleId)
    {
        $db = PearDatabase::getInstance();
        $db->pquery("DELETE FROM {$this->_bodyruleTable} WHERE ruleid = ? AND scannerid = ?", [$ruleId, $scannerId]);
        $db->pquery("DELETE FROM {$this->_bodyruleMappingTable} WHERE ruleid = ? AND scannerid = ?", [$ruleId, $scannerId]);
    }
}
