<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Settings_Workflows_EditTaskRecordStructure_Model extends Settings_Workflows_RecordStructure_Model
{
    private $taskRecordModel = false;

    public function setTaskRecordModel($taskModel)
    {
        $this->taskRecordModel = $taskModel;

        return $this;
    }

    public function getTaskRecordModel()
    {
        return $this->taskRecordModel;
    }

    /**
     * Function to get the values in stuctured format.
     * @return <array> - values in structure array('block'=>array(fieldinfo));
     */
    public function getStructure()
    {
        if (!empty($this->structuredValues)) {
            return $this->structuredValues;
        }

        $taskTypeModel = $this->getTaskRecordModel()->getTaskType();
        $taskTypeName = $taskTypeModel->getName();

        if ($taskTypeName == 'VTUpdateFieldsTask' || $taskTypeName == 'VTCreateEntityTask') {
            return parent::getStructure();
        }
        $recordModel = $this->getWorkFlowModel();
        $recordId = $recordModel->getId();

        $values = [];

        $baseModuleModel = $moduleModel = $this->getModule();
        $blockModelList = $moduleModel->getBlocks();
        $moduleName = $moduleModel->getName();
        foreach ($blockModelList as $blockLabel => $blockModel) {
            $fieldModelList = $blockModel->getFields();
            if (!empty($fieldModelList)) {
                $values[$blockLabel] = [];
                foreach ($fieldModelList as $fieldName => $fieldModel) {
                    if ($fieldModel->isViewable()) {
                        if ($moduleModel->getName() == 'Documents' && $fieldName == 'filename') {
                            continue;
                        }
                        // Should not show starred and tags fields in edit task view
                        if ($fieldModel->getDisplayType() == '6') {
                            continue;
                        }
                        if (in_array($moduleModel->getName(), ['Calendar', 'Events']) && $fieldModel->getDisplayType() == 3) {
                            /* Restricting the following fields(Event module fields) for "Calendar" module
                             * time_start, time_end, eventstatus, activitytype,	visibility, duration_hours,
                             * duration_minutes, reminder_time, recurringtype, notime
                             */
                            continue;
                        }
                        if (!empty($recordId)) {
                            // Set the fieldModel with the valuetype for the client side.
                            $fieldValueType = $recordModel->getFieldFilterValueType($fieldName);
                            $fieldInfo = $fieldModel->getFieldInfo();
                            $fieldInfo['workflow_valuetype'] = $fieldValueType;
                            $fieldModel->setFieldInfo($fieldInfo);
                        }

                        switch ($fieldModel->getFieldDataType()) {
                            case 'date':	if (($moduleName === 'Events' && in_array($fieldName, ['date_start', 'due_date']))
                                                    || ($moduleName === 'Calendar' && $fieldName === 'date_start')) {
                                $fieldName = $fieldName . ' ($(general : (__VtigerMeta__) usertimezone))';
                            } else {
                                $fieldName = $fieldName . ' ($_DATE_FORMAT_)';
                            }
                                break;
                            case 'datetime':	$fieldName = $fieldName . ' ($(general : (__VtigerMeta__) usertimezone))';
                                break;

                            default:	$fieldName;
                        }

                        // This will be used during editing task like email, sms etc
                        $fieldModel->set('workflow_columnname', $fieldName)->set('workflow_columnlabel', vtranslate($fieldModel->get('label'), $moduleModel->getName()));
                        // This is used to identify the field belongs to source module of workflow
                        $fieldModel->set('workflow_sourcemodule_field', true);
                        $values[$blockLabel][$fieldName] = clone $fieldModel;
                    }
                }
            }
        }

        if ($moduleModel->isCommentEnabled()) {
            $commentFieldModelsList = Settings_Workflows_Field_Model::getCommentFieldsListForTasks($moduleModel);

            $labelName = vtranslate($moduleModel->getSingularLabelKey(), $moduleModel->getName()) . ' ' . vtranslate('LBL_COMMENTS', $moduleModel->getName());
            foreach ($commentFieldModelsList as $commentFieldName => $commentFieldModel) {
                switch ($commentFieldModel->getFieldDataType()) {
                    case 'date':	$commentFieldName = $commentFieldName . ' ($_DATE_FORMAT_)';
                        break;
                    case 'datetime':	$commentFieldName = $commentFieldName . ' ($(general : (__VtigerMeta__) usertimezone)_)';
                        break;

                    default:	$commentFieldName;
                }
                $commentFieldModel->set('workflow_columnname', $commentFieldName)
                                  ->set('workflow_columnlabel', vtranslate($commentFieldModel->get('label'), $moduleModel->getName()))
                                  ->set('workflow_sourcemodule_field', true);

                $values[$labelName][$commentFieldName] = $commentFieldModel;
            }
        }

        // All the reference fields should also be sent
        $fields = $moduleModel->getFieldsByType(['reference', 'owner', 'multireference']);
        foreach ($fields as $parentFieldName => $field) {
            $type = $field->getFieldDataType();
            $referenceModules = $field->getReferenceList();
            if ($type == 'owner') {
                $referenceModules = ['Users'];
            }
            foreach ($referenceModules as $refModule) {
                $moduleModel = Vtiger_Module_Model::getInstance($refModule);
                $blockModelList = $moduleModel->getBlocks();
                foreach ($blockModelList as $blockLabel => $blockModel) {
                    $fieldModelList = $blockModel->getFields();
                    if (!empty($fieldModelList)) {
                        foreach ($fieldModelList as $fieldName => $fieldModel) {
                            if ($fieldModel->isViewable()) {
                                // Should not show starred and tags fields in edit task view
                                if ($fieldModel->getDisplayType() == '6') {
                                    continue;
                                }
                                $label = vtranslate($field->get('label'), $baseModuleModel->getName()) . ' : (' . vtranslate($refModule, $refModule) . ') ' . vtranslate($fieldModel->get('label'), $refModule);
                                $name = "({$parentFieldName} : ({$refModule}) {$fieldName})";
                                switch ($fieldModel->getFieldDataType()) {
                                    case 'date':	if (($moduleName === 'Events' && in_array($fieldName, ['date_start', 'due_date']))
                                                                || ($moduleName === 'Calendar' && $fieldName === 'date_start')) {
                                        $workflowColumnName = $name . ' ($(general : (__VtigerMeta__) usertimezone))';
                                    } else {
                                        $workflowColumnName = $name . ' ($_DATE_FORMAT_)';
                                    }
                                        break;
                                    case 'datetime':	$workflowColumnName = $name . ' ($(general : (__VtigerMeta__) usertimezone))';
                                        break;

                                    default: $workflowColumnName = $name;
                                }
                                $fieldModel->set('workflow_columnname', $workflowColumnName)->set('workflow_columnlabel', $label);

                                if (!empty($recordId)) {
                                    $fieldValueType = $recordModel->getFieldFilterValueType($name);
                                    $fieldInfo = $fieldModel->getFieldInfo();
                                    $fieldInfo['workflow_valuetype'] = $fieldValueType;
                                    $fieldModel->setFieldInfo($fieldInfo);
                                }
                                $values[$field->get('label')][$name] = clone $fieldModel;
                            }
                        }
                    }
                }

                $commentFieldModelsList = [];
                if ($moduleModel->isCommentEnabled()) {
                    $labelName = vtranslate($moduleModel->getSingularLabelKey(), $moduleModel->getName()) . ' ' . vtranslate('LBL_COMMENTS', $moduleModel->getName());

                    $commentFieldModelsList = Settings_Workflows_Field_Model::getCommentFieldsListForTasks($moduleModel);
                    foreach ($commentFieldModelsList as $commentFieldName => $commentFieldModel) {
                        $name = "({$parentFieldName} : ({$refModule}) {$commentFieldName})";
                        $label = vtranslate($field->get('label'), $baseModuleModel->getName()) . ' : ('
                                 . vtranslate($refModule, $refModule) . ') '
                                 . vtranslate($commentFieldModel->get('label'), $refModule);

                        $commentFieldModel->set('workflow_columnname', $name)->set('workflow_columnlabel', $label);
                        $values[$labelName][$name] = $commentFieldModel;
                    }
                }
            }
        }
        $this->structuredValues = $values;

        return $values;
    }
}
