<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

/**
 * Vtiger Detail View Record Structure Model.
 */
class Calendar_DetailRecordStructure_Model extends Vtiger_DetailRecordStructure_Model
{
    private $picklistValueMap = [];

    private $picklistRoleMap = [];

    /**
     * Function to get the values in stuctured format.
     * @return <array> - values in structure array('block'=>array(fieldinfo));
     */
    public function getStructure()
    {
        $currentUsersModel = Users_Record_Model::getCurrentUserModel();
        if (!empty($this->structuredValues)) {
            return $this->structuredValues;
        }

        $values = [];
        $recordModel = $this->getRecord();
        $recordExists = !empty($recordModel);
        $moduleModel = $this->getModule();
        $blockModelList = $moduleModel->getBlocks();
        foreach ($blockModelList as $blockLabel => $blockModel) {
            $fieldModelList = $blockModel->getFields();
            if (!empty($fieldModelList)) {
                $values[$blockLabel] = [];
                foreach ($fieldModelList as $fieldName => $fieldModel) {
                    if ($fieldModel->isViewableInDetailView()) {
                        if ($recordExists) {
                            if ($fieldName == 'due_date' && $moduleModel->get('name') != 'Calendar') {
                                $fieldModel->set('label', 'Due Date & Time');
                            }
                            $value = $recordModel->get($fieldName);
                            if (!$currentUsersModel->isAdminUser() && ($fieldModel->getFieldDataType() == 'picklist' || $fieldModel->getFieldDataType() == 'multipicklist')) {
                                $value = decode_html($value);
                                $this->setupAccessiblePicklistValueList($fieldModel);
                            }
                            $fieldModel->set('fieldvalue', $value);
                        }
                        $values[$blockLabel][$fieldName] = $fieldModel;
                    }
                }
            }
        }
        $this->structuredValues = $values;

        return $values;
    }
}
