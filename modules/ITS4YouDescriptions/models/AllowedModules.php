<?php

/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class ITS4YouDescriptions_AllowedModules_Model extends Vtiger_Module_Model
{
    /**
     * @param false $fieldsData
     * @return array
     * @throws Exception
     */
    public static function getSupportedModules($fieldsData = false)
    {
        $adb = PearDatabase::getInstance();
        $sql = ITS4YouDescriptions_Record_Model::getFieldLabelQuery();
        $result = $adb->pquery($sql, []);
        $modulesData = [];
        $allowedModules = self::getAllowedModuleNames();

        while ($row = $adb->fetchByAssoc($result)) {
            $tabid = $row['tabid'];
            $moduleName = vtlib_getModuleNameById($tabid);
            $isEntity = $adb->pquery('SELECT isentitytype, presence FROM vtiger_tab WHERE name = ? AND presence = ? AND isentitytype = ?', [$moduleName, 0, 1]);
            $checked = false;
            $deniedModules = ['ITS4YouDescriptions', 'ModComments'];

            if (!in_array($moduleName, $deniedModules) && $adb->num_rows($isEntity) > 0) {
                if (isset($allowedModules[$tabid]) && !empty($allowedModules[$tabid])) {
                    $checked = true;
                }

                $modulesData[$tabid] = [
                    'tabid' => $tabid,
                    'name' => $moduleName,
                    'checked' => $checked,
                ];

                if ($fieldsData) {
                    $modulesData[$tabid]['fields'] = ITS4YouDescriptions_AllowedModules_Model::getTextareaFields($moduleName);
                    $modulesData[$tabid]['allowed_fields'] = ITS4YouDescriptions_AllowedFields_Model::getInstance($moduleName);
                }
            }
        }

        return $modulesData;
    }

    /**
     * @return array
     */
    public static function getAllowedModuleNames()
    {
        $adb = PearDatabase::getInstance();
        $sql = 'SELECT desc4youmodule FROM vtiger_desc4youmodule';
        $result = $adb->pquery($sql, []);
        $moduleModels = [];

        while ($row = $adb->fetchByAssoc($result)) {
            $moduleName = $row['desc4youmodule'];

            if (vtlib_isModuleActive($moduleName)) {
                $moduleModel = Vtiger_Module_Model::getInstance($moduleName);

                if ($moduleModel && $moduleModel->isEntityModule()) {
                    $moduleModels[getTabid($moduleName)] = $moduleName;
                }
            }
        }

        return $moduleModels;
    }

    /**
     * @return array
     */
    public static function getAllowedModules()
    {
        $moduleNames = self::getAllowedModuleNames();
        $moduleModels = [];

        foreach ($moduleNames as $tabId => $tabName) {
            $moduleModels[$tabId] = ITS4YouDescriptions_AllowedModules_Model::getInstance($tabName);
        }

        return $moduleModels;
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public static function isAllowed($moduleName)
    {
        return in_array($moduleName, self::getAllowedModuleNames());
    }

    /**
     * @param string $moduleName
     * @return array
     */
    public static function getTextareaFields($moduleName)
    {
        $fields = [];
        $adb = PearDatabase::getInstance();
        $queryData = ITS4YouDescriptions_Record_Model::getQueryDataForModule($moduleName);
        $res = $adb->pquery($queryData['query'], $queryData['params']);

        while ($row = $adb->fetchByAssoc($res)) {
            $row['fieldlabel'] = html_entity_decode($row['fieldlabel'], ENT_COMPAT, 'utf-8');
            $fields[] = $row;
        }

        return $fields;
    }
}
