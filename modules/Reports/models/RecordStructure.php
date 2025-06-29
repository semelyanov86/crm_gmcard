<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

/**
 * Vtiger Edit View Record Structure Model.
 */
class Reports_RecordStructure_Model extends Vtiger_RecordStructure_Model
{
    /**
     * Function to get the values in stuctured format.
     * @return <array> - values in structure array('block'=>array(fieldinfo));
     */
    public function getStructure()
    {
        $eventCustomFields = [];
        [$moduleName] = func_get_args();
        if (!empty($this->structuredValues[$moduleName])) {
            return $this->structuredValues[$moduleName];
        }
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if ($moduleName === 'Emails') {
            $restrictedTablesList = ['vtiger_emaildetails', 'vtiger_attachments'];
            $moduleRecordStructure = [];
            $blockModelList = $moduleModel->getBlocks();
            foreach ($blockModelList as $blockLabel => $blockModel) {
                $fieldModelList = $blockModel->getFields();
                if (!empty($fieldModelList)) {
                    $moduleRecordStructure[$blockLabel] = [];
                    foreach ($fieldModelList as $fieldName => $fieldModel) {
                        if ($fieldModel->get('table') == 'vtiger_activity' && $this->getRecord()->getPrimaryModule() != 'Emails') {
                            $fieldModel->set('table', 'vtiger_activityEmails');
                        }
                        if (!in_array($fieldModel->get('table'), $restrictedTablesList) && $fieldModel->isViewable()) {
                            $moduleRecordStructure[$blockLabel][$fieldName] = $fieldModel;
                        }
                    }
                }
            }
        } elseif ($moduleName === 'Calendar') {
            $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceForModule($moduleModel);
            $moduleRecordStructure = [];
            $calendarRecordStructure = $recordStructureInstance->getStructure();

            $eventsModel = Vtiger_Module_Model::getInstance('Events');
            $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceForModule($eventsModel);
            $eventRecordStructure = $recordStructureInstance->getStructure();

            foreach ($eventRecordStructure as $blockLabel => $blockFields) {
                foreach ($blockFields as $fieldName => $fieldModel) {
                    if ($fieldModel->isCustomField()) {
                        $eventCustomFields[$fieldName] = $fieldModel;
                    }
                }
            }

            $blockLabel = 'LBL_CUSTOM_INFORMATION';
            if ($eventCustomFields) {
                if ($calendarRecordStructure[$blockLabel]) {
                    $calendarRecordStructure[$blockLabel] = array_merge($calendarRecordStructure[$blockLabel], $eventCustomFields);
                } else {
                    $calendarRecordStructure[$blockLabel] = $eventCustomFields;
                }
            }
            $moduleRecordStructure = $calendarRecordStructure;
        } else {
            $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceForModule($moduleModel);
            $moduleRecordStructure = $recordStructureInstance->getStructure();
        }
        // To remove starred and tag fields
        foreach ($moduleRecordStructure as $blockLabel => $blockFields) {
            foreach ($blockFields as $fieldName => $fieldModel) {
                if ($fieldModel->getDisplayType() == '6') {
                    unset($moduleRecordStructure[$blockLabel][$fieldName]);
                }
            }
        }
        if ($this->structuredValues === false) {
            $this->structuredValues = [];
        }
        $this->structuredValues[$moduleName] = $moduleRecordStructure;

        return $moduleRecordStructure;
    }

    /**
     * Function returns the Primary Module Record Structure.
     * @return <Vtiger_RecordStructure_Model>
     */
    public function getPrimaryModuleRecordStructure()
    {
        $primaryModule = $this->getRecord()->getPrimaryModule();
        $primaryModuleRecordStructure = $this->getStructure($primaryModule);

        return $primaryModuleRecordStructure;
    }

    /**
     * Function returns the Secondary Modules Record Structure.
     * @return <Array of Vtiger_RecordSructure_Models>
     */
    public function getSecondaryModuleRecordStructure()
    {
        $recordStructureInstances = [];

        $secondaryModule = $this->getRecord()->getSecondaryModules();
        if (!empty($secondaryModule)) {
            $moduleList = explode(':', $secondaryModule);

            foreach ($moduleList as $moduleName) {
                if (!empty($moduleName)) {
                    $recordStructureInstances[$moduleName] = $this->getStructure($moduleName);
                }
            }
        }

        return $recordStructureInstances;
    }
}
