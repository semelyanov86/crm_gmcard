<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Project_MassActionAjax_View extends Vtiger_MassActionAjax_View
{
    protected function getEmailFieldsInfo(Vtiger_Request $request)
    {
        $sourceModule = $request->getModule();
        $emailFieldsInfo = [];
        $moduleModel = Vtiger_Module_Model::getInstance($sourceModule);
        $recipientPrefModel = Vtiger_RecipientPreference_Model::getInstance($sourceModule);
        $recipientPrefs = [];

        if ($recipientPrefModel) {
            $recipientPrefs = $recipientPrefModel->getPreferences();
        }
        $moduleEmailPrefs = isset($recipientPrefs[$moduleModel->getId()]) ? $recipientPrefs[$moduleModel->getId()] : '';
        $emailAndRefFields = $moduleModel->getFieldsByType(['email', 'reference']);
        $accesibleFields = [];
        $referenceFieldValues = [];

        foreach ($emailAndRefFields as $field) {
            $fieldName = $field->getName();
            if ($field->isViewable()) {
                if ($moduleEmailPrefs && in_array($field->getId(), $moduleEmailPrefs)) {
                    $field->set('isPreferred', true);
                }
                $accesibleFields[$fieldName] = $field;
            }
        }

        $allEmailFields = [];
        $moduleEmailFields = $moduleModel->getFieldsByType(['email']);
        foreach ($moduleEmailFields as $moduleEmailField) {
            if ($moduleEmailField->isViewable()) {
                if ($moduleEmailPrefs && in_array($moduleEmailField->getId(), $moduleEmailPrefs)) {
                    $moduleEmailField->set('isPreferred', true);
                }
                $allEmailFields[$sourceModule][$moduleEmailField->getFieldName()] = $moduleEmailField;
            }
        }

        $referenceFields = $moduleModel->getFieldsByType(['reference']);
        foreach ($referenceFields as $referenceField) {
            $referenceModules = $referenceField->getReferenceList();
            $refModuleName = $referenceModules[0];
            if (empty($refModuleName) || $refModuleName == 'Users') {
                continue;
            }
            $refModule = Vtiger_Module_Model::getInstance($refModuleName);
            if ($refModule) {
                $refModuleEmailFields = $refModule->getFieldsByType(['email']);
                if (empty($refModuleEmailFields)) {
                    continue;
                }
                $refModuleEmailPrefs = isset($recipientPrefs[$refModule->getId()]) ? $recipientPrefs[$refModule->getId()] : '';
                foreach ($refModuleEmailFields as $refModuleEmailField) {
                    if ($refModuleEmailField->isViewable()) {
                        $refModuleEmailField->set('baseRefField', $referenceField->getFieldName());
                        if ($refModuleEmailPrefs && in_array($refModuleEmailField->getId(), $refModuleEmailPrefs)) {
                            $refModuleEmailField->set('isPreferred', true);
                        }
                        $allEmailFields[$refModuleName][$refModuleEmailField->getFieldName()] = $refModuleEmailField;
                    }
                }
            }
        }

        if (php7_count($accesibleFields) > 0) {
            $recordIds = $this->getRecordsListFromRequest($request);
            global $current_user;
            $baseTableId = $moduleModel->get('basetableid');
            $queryGen = new QueryGenerator($moduleModel->getName(), $current_user);
            $selectFields = array_keys($accesibleFields);
            array_push($selectFields, 'id');
            $queryGen->setFields($selectFields);
            $query = $queryGen->getQuery();
            $query = $query . ' AND crmid IN (' . generateQuestionMarks($recordIds) . ')';
            $emailOptout = $moduleModel->getField('emailoptout');
            if ($emailOptout) {
                $query .= ' AND ' . $emailOptout->get('column') . ' = 0';
            }

            $db = PearDatabase::getInstance();
            $result = $db->pquery($query, $recordIds);
            $num_rows = $db->num_rows($result);

            if ($num_rows > 0) {
                for ($i = 0; $i < $num_rows; ++$i) {
                    $emailFieldsList = [];
                    foreach ($accesibleFields as $field) {
                        $fieldValue = $db->query_result($result, $i, $field->get('column'));
                        $recordIdValue = $db->query_result($result, $i, $baseTableId);
                        if (!empty($fieldValue)) {
                            if (in_array($field->getFieldDataType(), ['reference'])) {
                                $referenceFieldValues[$recordIdValue][] = $fieldValue;
                            } else {
                                $emailFieldsList[$fieldValue] = $field;
                            }
                        }
                    }
                    if (!empty($emailFieldsList)) {
                        $emailFieldsInfo[$recordIdValue][$moduleModel->getName()] = $emailFieldsList;
                    }
                }
            }
        }

        if (!empty($referenceFieldValues)) {
            foreach ($referenceFieldValues as $recordId => $refRecordIds) {
                foreach ($refRecordIds as $refRecordId) {
                    $refModuleName = Vtiger_Functions::getCRMRecordType($refRecordId);
                    if (empty($refModuleName) || $refModuleName == 'Users') {
                        continue;
                    }
                    $refModuleModel = Vtiger_Module_Model::getInstance($refModuleName);
                    if (!$refModuleModel || !$refModuleModel->isActive() || !Users_Privileges_Model::isPermitted($refModuleModel->getName(), 'DetailView')) {
                        continue;
                    }
                    $refModuleEmailPrefs = isset($recipientPrefs[$refModuleModel->getId()]) ? $recipientPrefs[$refModuleModel->getId()] : '';
                    $refModuleEmailFields = $refModuleModel->getFieldsByType('email');
                    if (empty($refModuleEmailFields)) {
                        continue;
                    }

                    $accesibleFields = [];
                    foreach ($refModuleEmailFields as $fieldModel) {
                        if ($fieldModel->isViewable()) {
                            if ($refModuleEmailPrefs && in_array($fieldModel->getId(), $refModuleEmailPrefs)) {
                                $fieldModel->set('isPreferred', true);
                            }
                            $accesibleFields[$fieldModel->getName()] = $fieldModel;
                        }
                    }
                    $refModuleEmailFields = $accesibleFields;
                    $qGen = new QueryGenerator($refModuleName, $current_user);
                    $qGen->setFields(array_keys($refModuleEmailFields));
                    $query = $qGen->getQuery();
                    $query .= " AND crmid = {$refRecordId}";

                    $params = [];
                    if ($refModuleModel->getField('emailoptout')) {
                        $query .= ' AND ' . $refModuleModel->basetable . '.emailoptout = ?';
                        $params[] = 0;
                    }
                    $result = $db->pquery($query, $params);
                    $numRows = $db->num_rows($result);
                    $emailFieldList = [];
                    if ($numRows > 0) {
                        foreach ($refModuleEmailFields as $emailFieldName => $emailField) {
                            $emailValue = $db->query_result($result, 0, $emailFieldName);
                            if (!empty($emailValue)) {
                                $emailFieldList[$emailValue] = $emailField;
                            }
                        }
                    }
                    if (!empty($emailFieldList)) {
                        $emailFieldsInfo[$recordId][$refModuleName] = $emailFieldList;
                    }
                }
            }
        }
        $viewer = $this->getViewer($request);
        $viewer->assign('RECORDS_COUNT', count($recordIds));
        if ($recipientPrefModel && !empty($recipientPrefs)) {
            $viewer->assign('RECIPIENT_PREF_ENABLED', true);
        }
        $viewer->assign('EMAIL_FIELDS', $allEmailFields);
        $viewer->assign('PREF_NEED_TO_UPDATE', $this->isPreferencesNeedToBeUpdated($request));

        return $emailFieldsInfo;
    }

    protected function isPreferencesNeedToBeUpdated(Vtiger_Request $request)
    {
        $parentStatus = parent::isPreferencesNeedToBeUpdated($request);
        if (!$parentStatus) {
            $recipientPrefModel = Vtiger_RecipientPreference_Model::getInstance($request->getModule());
            if (!$recipientPrefModel) {
                return $parentStatus;
            }
            $prefs = $recipientPrefModel->getPreferences();
            if (empty($prefs)) {
                return true;
            }
            $moduleModel = Vtiger_Module_Model::getInstance($request->getModule());
            $refFields = $moduleModel->getFieldsByType(['reference']);
            foreach ($refFields as $refField) {
                if ($refField && $refField->isViewable()) {
                    $referenceList = $refField->getReferenceList();
                    foreach ($referenceList as $moduleName) {
                        if ($moduleName !== 'Users') {
                            $refModuleModel = Vtiger_Module_Model::getInstance($moduleName);
                            if (!$prefs[$refModuleModel->getId()]) {
                                continue;
                            }
                            $moduleEmailPrefs = $prefs[$refModuleModel->getId()];
                            foreach ($moduleEmailPrefs as $fieldId) {
                                $field = Vtiger_Field_Model::getInstance($fieldId, $refModuleModel);
                                if ($field) {
                                    if (!$field->isActiveField()) {
                                        $parentStatus = true;
                                    }
                                } else {
                                    $parentStatus = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $parentStatus;
    }
}
