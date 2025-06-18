<?php

/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class ITS4YouDescriptions_AllowedModules_Action extends Vtiger_Action_Controller
{
    public $fieldModule = 'desc4youmodule';

    public $fieldField = 'desc4youfield';

    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $record = $request->get('record');

        if (!Users_Privileges_Model::isPermitted($moduleName, 'Save', $record)) {
            throw new AppException('LBL_PERMISSION_DENIED');
        }
    }

    /**
     * @throws Exception
     */
    public function process(Vtiger_Request $request)
    {
        $this->addPicklists($request);
        $this->addDependencyModuleField($request);
        $this->addAllowedFields($request);

        $response = new Vtiger_Response();
        $response->setResult(['message' => vtranslate('LBL_SAVED_SUCCESSFULLY', $request->getModule())]);
        $response->emit();
    }

    public function addPicklists($request)
    {
        $adb = PearDatabase::getInstance();
        $picklistModulevalues = ['Global'];

        foreach ($request->getAll() as $key => $value) {
            if (substr($key, 0, 8) == 'allowed_') {
                $moduleName = vtlib_getmodulenamebyid($value);
                array_push($picklistModulevalues, $moduleName);
            }
        }
        $this->setCustomPicklist($request, $this->fieldModule, $picklistModulevalues);

        $sql = ITS4YouDescriptions_Record_Model::getFieldLabelQuery();
        $result = $adb->pquery($sql, []);
        $picklistFieldvalues = [];

        while ($row = $adb->fetchByAssoc($result)) {
            $fieldlabel = $row['fieldlabel'];

            array_push($picklistFieldvalues, html_entity_decode($fieldlabel, ENT_COMPAT, 'utf-8'));
        }
        $this->setCustomPicklist($request, $this->fieldField, $picklistFieldvalues);
    }

    public function setCustomPicklist($request, $fieldname, $values)
    {
        $adb = PearDatabase::getInstance();
        $sql = 'TRUNCATE TABLE vtiger_' . $fieldname;
        $adb->pquery($sql);
        $moduleName = $request->get('module');
        $moduleInstance = Settings_Picklist_Module_Model::getInstance($moduleName);
        $fieldFieldInstance = Settings_Picklist_Field_Model::getInstance($fieldname, $moduleInstance);
        $fieldFieldInstance->setPicklistValues($values);
    }

    public function addDependencyModuleField($request)
    {
        $adb = PearDatabase::getInstance();
        $sourceModule = $request->getModule();
        $sourceField = $this->fieldModule;
        $targetField = $this->fieldField;
        $recordModel = new Settings_PickListDependency_Record_Model();
        $recordModel->set('sourceModule', $sourceModule)->set('sourcefield', $sourceField)->set('targetfield', $targetField);

        $result = $adb->pquery('SELECT desc4youmodule FROM vtiger_desc4youmodule', []);
        $mapping = [['sourcevalue' => 'Global', 'targetvalues' => ['Description', 'Terms & Conditions']]];

        while ($row = $adb->fetchByAssoc($result)) {
            $moduleName = $row[$sourceField];
            $module = Vtiger_Module::getInstance($moduleName);

            if ($module) {
                $dependencyFields = [];
                $queryData = ITS4YouDescriptions_Record_Model::getQueryDataForModule($moduleName);
                $result1 = $adb->pquery($queryData['query'], $queryData['params']);

                while ($row1 = $adb->fetchByAssoc($result1)) {
                    $fieldlabel = $row1['fieldlabel'];

                    if ($row1['fieldname'] == 'description') {
                        $fieldlabel = 'Description';
                    }

                    if ($row1['fieldname'] == 'terms_conditions') {
                        $fieldlabel = 'Terms & Conditions';
                    }

                    $dependencyFields[] = html_entity_decode($fieldlabel, ENT_COMPAT, 'utf-8');
                }

                $mapping[] = [
                    'sourcevalue' => $row[$sourceField],
                    'targetvalues' => $dependencyFields,
                ];
            }
        }

        $recordModel->delete();
        $recordModel->save($mapping);
    }

    /**
     * @throws Exception
     */
    public function addAllowedFields(Vtiger_Request $request)
    {
        foreach (array_keys($request->getAll()) as $key) {
            if (strpos($key, 'module_') !== false) {
                $tabId = explode('_', $key)[1];

                if ($tabId) {
                    $allowedFields = ITS4YouDescriptions_AllowedFields_Model::getInstance(vtlib_getModuleNameById($tabId));
                    $allowedFields->set('fields', $request->get('fields_' . $tabId));
                    $allowedFields->save();
                }
            }
        }
    }
}
