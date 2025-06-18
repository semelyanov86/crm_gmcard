<?php

/**
 * Vtiger Record Structure Model.
 */
class VTEButtons_RecordStructure_Model extends Vtiger_RecordStructure_Model
{
    protected $record = false;

    protected $module = false;

    protected $structuredValues = false;
    public const RECORD_STRUCTURE_MODE_DEFAULT = '';
    public const RECORD_STRUCTURE_MODE_DETAIL = 'Detail';
    public const RECORD_STRUCTURE_MODE_EDIT = 'Edit';
    public const RECORD_STRUCTURE_MODE_QUICKCREATE = 'QuickCreate';
    public const RECORD_STRUCTURE_MODE_MASSEDIT = 'MassEdit';
    public const RECORD_STRUCTURE_MODE_SUMMARY = 'Summary';
    public const RECORD_STRUCTURE_MODE_FILTER = 'Filter';
    public const RECORD_STRUCTURE_MODE_CUSTOMEDIT = 'Custom';

    /**
     * Function to set the record Model.
     * @param <type> $record - record instance
     * @return Vtiger_RecordStructure_Model
     */
    public function setRecord($record)
    {
        $this->record = $record;

        return $this;
    }

    /**
     * Function to get the record.
     * @return <Vtiger_Record_Model>
     */
    public function getRecord()
    {
        return $this->record;
    }

    public function getRecordName()
    {
        return $this->record->getName();
    }

    /**
     * Function to get the module.
     * @return <Vtiger_Module_Model>
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * Function to set the module.
     * @param <type> $module - module model
     * @return Vtiger_RecordStructure_Model
     */
    public function setModule($module)
    {
        $this->module = $module;

        return $this;
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
        $values = [];
        $recordModel = $this->getRecord();
        $recordExists = !empty($recordModel);
        $baseModuleModel = $moduleModel = $this->getModule();
        $baseModuleName = $baseModuleModel->getName();
        $blockModelList = $moduleModel->getBlocks();
        foreach ($blockModelList as $blockLabel => $blockModel) {
            $fieldModelList = $blockModel->getFields();
            if (!empty($fieldModelList)) {
                $values[$blockLabel] = [];
                foreach ($fieldModelList as $fieldName => $fieldModel) {
                    if ($fieldModel->isViewable()) {
                        if ($recordExists) {
                            $fieldModel->set('fieldvalue', $recordModel->get($fieldName));
                        }
                        $values[$blockLabel][$fieldName] = $fieldModel;
                    }
                }
            }
        }
        $fields = $moduleModel->getFieldsByType(['reference']);
        foreach ($fields as $parentFieldName => $field) {
            if ($field->isViewableInFilterView()) {
                $referenceModules = $field->getReferenceList();
                foreach ($referenceModules as $refModule) {
                    if ($refModule == 'Users') {
                        continue;
                    }
                    $refModuleModel = Vtiger_Module_Model::getInstance($refModule);
                    $blockModelList = $refModuleModel->getBlocks();
                    $fieldModelList = null;
                    foreach ($blockModelList as $blockLabel => $blockModel) {
                        $fieldModelList = $blockModel->getFields();
                        if (!empty($fieldModelList)) {
                            if (count($referenceModules) > 1) {
                                $newblockLabel = vtranslate($field->get('label'), $baseModuleName) . ' (' . vtranslate($refModule, $refModule) . ') - ' . vtranslate($blockLabel, $refModule);
                            } else {
                                $newblockLabel = vtranslate($field->get('label'), $baseModuleName) . '-' . vtranslate($blockLabel, $refModule);
                            }
                            $values[$newblockLabel] = [];
                            $fieldModel = $fieldName = null;
                            foreach ($fieldModelList as $fieldName => $fieldModel) {
                                if ($fieldModel->isViewableInFilterView() && $fieldModel->getDisplayType() != '5' && $fieldName == 'assigned_user_id') {
                                    $newFieldModel = clone $fieldModel;
                                    $name = '(' . $parentFieldName . ' ; (' . $refModule . ') ' . $fieldName . ')';
                                    $label = vtranslate($field->get('label'), $baseModuleName) . '-' . vtranslate($fieldModel->get('label'), $refModule);
                                    $newFieldModel->set('reference_fieldname', $name)->set('label', $label);
                                    $values[$newblockLabel][$name] = $newFieldModel;
                                }
                            }
                        }
                    }
                }
            }
        }
        $this->structuredValues = $values;

        return $values;
    }

    /**
     * Function to retieve the instance from module model.
     * @param <Vtiger_Module_Model> $moduleModel - module instance
     * @return Vtiger_RecordStructure_Model
     */
    public static function getInstanceForModule($moduleModel, $mode = self::RECORD_STRUCTURE_MODE_DEFAULT)
    {
        $className = 'VTEButtons_RecordStructure_Model';
        $instance = new $className();
        $instance->setModule($moduleModel);

        return $instance;
    }
}
