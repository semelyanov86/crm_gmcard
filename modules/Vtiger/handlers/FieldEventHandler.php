<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

require_once 'include/events/VTEventHandler.inc';

class FieldEventHandler extends VTEventHandler
{
    public function handleEvent($eventName, $fieldEntity)
    {
        global $log, $adb;

        if ($eventName == 'vtiger.field.afterdelete') {
            $this->triggerPostDeleteEvents($fieldEntity);
        }
    }

    public function triggerPostDeleteEvents($fieldEntity)
    {
        $db = PearDatabase::getInstance();

        $fieldId		= $fieldEntity->id;
        $fieldName		= $fieldEntity->name;
        $columnName		= $fieldEntity->column;
        $fieldLabel		= $fieldEntity->label;
        $tableName		= $fieldEntity->table;
        $typeOfData		= $fieldEntity->typeofdata;
        $fieldModuleName = $fieldEntity->getModuleName();
        $fieldType		= explode('~', $typeOfData);

        $deleteColumnName	= "{$tableName}:{$columnName}:" . $fieldName . ':' . $fieldModuleName . '_' . str_replace(' ', '_', $fieldLabel) . ':' . $fieldType[0];
        $columnCvStdFilter	= "{$tableName}:{$columnName}:" . $fieldName . ':' . $fieldModuleName . '_' . str_replace(' ', '_', $fieldLabel);
        $selectColumnName	= "{$tableName}:{$columnName}:" . $fieldModuleName . '_' . str_replace(' ', '_', $fieldLabel) . ':' . $fieldName . ':' . $fieldType[0];
        $reportSummaryColumn = "{$tableName}:{$columnName}:" . str_replace(' ', '_', $fieldLabel);

        $query = 'ALTER TABLE ' . $db->sql_escape_string($tableName) . ' DROP COLUMN ' . $db->sql_escape_string($columnName);
        $db->pquery($query, []);

        // we have to remove the entries in customview and report related tables which have this field ($colName)
        $db->pquery('DELETE FROM vtiger_cvcolumnlist WHERE columnname = ?', [$deleteColumnName]);
        $db->pquery('DELETE FROM vtiger_cvstdfilter WHERE columnname = ?', [$columnCvStdFilter]);
        $db->pquery('DELETE FROM vtiger_cvadvfilter WHERE columnname = ?', [$deleteColumnName]);
        $db->pquery('DELETE FROM vtiger_selectcolumn WHERE columnname = ?', [$selectColumnName]);
        $db->pquery('DELETE FROM vtiger_relcriteria WHERE columnname = ?', [$selectColumnName]);
        $db->pquery('DELETE FROM vtiger_reportsortcol WHERE columnname = ?', [$selectColumnName]);
        $db->pquery('DELETE FROM vtiger_reportsummary WHERE columnname LIKE ?', ['%' . $reportSummaryColumn . '%']);
        $db->pquery('DELETE FROM vtiger_reportdatefilter WHERE datecolumnname = ?', [$columnCvStdFilter]);

        if ($fieldModuleName == 'Leads') {
            $db->pquery('DELETE FROM vtiger_convertleadmapping WHERE leadfid=?', [$fieldId]);
        } elseif ($fieldModuleName == 'Accounts' || $fieldModuleName == 'Contacts' || $fieldModuleName == 'Potentials') {
            $params = ['Accounts' => 'accountfid', 'Contacts' => 'contactfid', 'Potentials' => 'potentialfid'];
            $query = 'UPDATE vtiger_convertleadmapping SET ' . $params[$fieldModuleName] . '=0 WHERE ' . $params[$fieldModuleName] . '=?';
            $db->pquery($query, [$fieldId]);
        }

        if (in_array($fieldEntity->uitype, [15, 33])) {
            $db->pquery('DROP TABLE IF EXISTS vtiger_' . $db->sql_escape_string($columnName), []);
            $db->pquery('DROP TABLE IF EXISTS vtiger_' . $db->sql_escape_string($columnName) . '_seq', []); // To Delete Sequence Table
            $db->pquery('DELETE FROM vtiger_picklist_dependency WHERE sourcefield=? OR targetfield=?', [$columnName, $columnName]);

            // delete from picklist tables
            $picklistResult = $db->pquery('SELECT picklistid FROM vtiger_picklist WHERE name = ?', [$fieldName]);
            $picklistRow = $db->num_rows($picklistResult);
            if ($picklistRow) {
                $picklistId = $db->query_result($picklistResult, 0, 'picklistid');
                $db->pquery('DELETE FROM vtiger_picklist WHERE name = ?', [$fieldName]);
                $db->pquery('DELETE FROM vtiger_role2picklist WHERE picklistid = ?', [$picklistId]);
            }

            $rolesList = array_keys(getAllRoleDetails());
            Vtiger_Cache::flushPicklistCache($fieldName, $rolesList);
        }

        $this->triggerInventoryFieldPostDeleteEvents($fieldEntity);
    }

    public function triggerInventoryFieldPostDeleteEvents($fieldEntity)
    {
        $db = PearDatabase::getInstance();
        $fieldId = $fieldEntity->id;
        $fieldModuleName = $fieldEntity->getModuleName();

        if (in_array($fieldModuleName, getInventoryModules())) {

            $db->pquery('DELETE FROM vtiger_inventorycustomfield WHERE fieldid=?', [$fieldId]);

        } elseif (in_array($fieldModuleName, ['Products', 'Services'])) {

            $refFieldName			= ($fieldModuleName == 'Products') ? 'productfieldid' : 'servicefieldid';
            $refFieldDefaultValue	= ($fieldModuleName == 'Products') ? 'productFieldDefaultValue' : 'serviceFieldDefaultValue';

            $query = "SELECT vtiger_inventorycustomfield.* FROM vtiger_inventorycustomfield
							INNER JOIN vtiger_field ON vtiger_field.fieldid = vtiger_inventorycustomfield.fieldid
							WHERE {$refFieldName} = ? AND defaultvalue LIKE ?";
            $result = $db->pquery($query, [$fieldId, '%productFieldDefaultValue%serviceFieldDefaultValue%']);

            $removeCacheModules = [];

            while ($rowData = $db->fetch_row($result)) {
                $lineItemFieldModel = Vtiger_Field_Model::getInstance($rowData['fieldid']);
                if ($lineItemFieldModel) {
                    $defaultValue = $lineItemFieldModel->getDefaultFieldValue();
                    if (is_array($defaultValue)) {
                        $defaultValue[$refFieldDefaultValue] = '';

                        if ($defaultValue['productFieldDefaultValue'] === '' && $defaultValue['serviceFieldDefaultValue'] === '') {
                            $defaultValue = '';
                        } else {
                            $defaultValue = Zend_Json::encode($defaultValue);
                        }

                        $lineItemFieldModel->set('defaultvalue', $defaultValue);
                        $lineItemFieldModel->save();
                    }

                    $removeCacheModules[$rowData['tabid']][] = $lineItemFieldModel->get('block')->id;
                }
            }

            foreach ($removeCacheModules as $tabId => $blockIdsList) {
                $moduleModel = Vtiger_Module_Model::getInstance($tabId);
                foreach ($blockIdsList as $blockId) {
                    Vtiger_Cache::flushModuleandBlockFieldsCache($moduleModel, $blockId);
                }
            }

            $db->pquery("UPDATE vtiger_inventorycustomfield SET {$refFieldName}=? WHERE fieldid=?", ['0', $fieldId]);
        }

    }
}
