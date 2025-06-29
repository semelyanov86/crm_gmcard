<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Events_FilterRecordStructure_Model extends Vtiger_FilterRecordStructure_Model
{
    /**
     * Function to get the fields & reference fields in stuctured format.
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
                $values[vtranslate($blockLabel, $baseModuleName)] = [];
                foreach ($fieldModelList as $fieldName => $fieldModel) {
                    if ($fieldModel->isViewableInFilterView()) {
                        $newFieldModel = clone $fieldModel;
                        if ($recordExists) {
                            $newFieldModel->set('fieldvalue', $recordModel->get($fieldName));
                        }
                        $values[vtranslate($blockLabel, $baseModuleName)][$fieldName] = $newFieldModel;
                    }
                }
            }
        }
        $this->structuredValues = $values;

        return $values;
    }
}
