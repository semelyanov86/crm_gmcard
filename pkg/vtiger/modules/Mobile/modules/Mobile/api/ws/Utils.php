<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
class Mobile_WS_Utils
{
    /*
    static function initAppGlobals() {
        global $current_language, $app_strings, $app_list_strings, $app_currency_strings;
        $current_language = 'en_us';

        $app_currency_strings = return_app_currency_strings_language($current_language);
        $app_strings = return_application_language($current_language);
        $app_list_strings = return_app_list_strings_language($current_language);
    }

    static function initModuleGlobals($module) {
        global $mod_strings, $current_language;
        if(isset($current_language)) {
            $mod_strings = return_module_language($current_language, $module);
        }
    }*/

    public static function getVtigerVersion()
    {
        global $vtiger_current_version;

        return $vtiger_current_version;
    }

    public static function getVersion()
    {
        global $adb;
        $versionResult = $adb->pquery("SELECT version FROM vtiger_tab WHERE name='Mobile'", []);

        return $adb->query_result($versionResult, 0, 'version');
    }

    public static function array_replace($search, $replace, $array)
    {
        $index = array_search($search, $array);
        if ($index !== false) {
            $array[$index] = $replace;
        }

        return $array;
    }

    public static function getModuleListQuery($moduleName, $where = '1=1')
    {
        $module = CRMEntity::getInstance($moduleName);

        return $module->create_list_query('', $where);
    }

    public static $moduleWSIdCache = [];

    public static function getEntityModuleWSId($moduleName)
    {

        if (!isset(self::$moduleWSIdCache[$moduleName])) {
            global $adb;
            $result = $adb->pquery('SELECT id FROM vtiger_ws_entity WHERE name=?', [$moduleName]);
            if ($result && $adb->num_rows($result)) {
                self::$moduleWSIdCache[$moduleName] = $adb->query_result($result, 0, 'id');
            }
        }

        return self::$moduleWSIdCache[$moduleName];
    }

    public static function getEntityModuleWSIds($ignoreNonModule = true)
    {
        global $adb;

        $modulewsids = [];
        $result = false;
        if ($ignoreNonModule) {
            $result = $adb->pquery('SELECT id, name FROM vtiger_ws_entity WHERE ismodule=1', []);
        } else {
            $result = $adb->pquery('SELECT id, name FROM vtiger_ws_entity', []);
        }

        while ($resultrow = $adb->fetch_array($result)) {
            $modulewsids[$resultrow['name']] = $resultrow['id'];
        }

        return $modulewsids;
    }

    public static function getEntityFieldnames($module)
    {
        global $adb;
        $result = $adb->pquery('SELECT fieldname FROM vtiger_entityname WHERE modulename=?', [$module]);
        $fieldnames = [];
        if ($result && $adb->num_rows($result)) {
            $fieldnames = explode(',', $adb->query_result($result, 0, 'fieldname'));
        }
        switch ($module) {
            case 'HelpDesk': $fieldnames = self::array_replace('title', 'ticket_title', $fieldnames);
                break;
            case 'Document': $fieldnames = self::array_replace('title', 'notes_title', $fieldnames);
                break;
        }

        return $fieldnames;
    }

    public static function getModuleColumnTableByFieldNames($module, $fieldnames)
    {
        global $adb;
        $result = $adb->pquery(
            'SELECT fieldname,columnname,tablename FROM vtiger_field WHERE tabid=? AND fieldname IN ('
            . generateQuestionMarks($fieldnames) . ')',
            [getTabid($module), $fieldnames],
        );
        $columnnames = [];
        if ($result && $adb->num_rows($result)) {
            while ($resultrow = $adb->fetch_array($result)) {
                $columnnames[$resultrow['fieldname']] = ['column' => $resultrow['columnname'], 'table' => $resultrow['tablename']];
            }
        }

        return $columnnames;
    }

    public static function detectModulenameFromRecordId($wsrecordid)
    {
        global $adb;
        $idComponents = vtws_getIdComponents($wsrecordid);
        $result = $adb->pquery('SELECT name FROM vtiger_ws_entity WHERE id=?', [$idComponents[0]]);
        if ($result && $adb->num_rows($result)) {
            return $adb->query_result($result, 0, 'name');
        }

        return false;
    }

    public static $detectFieldnamesToResolveCache = [];

    public static function detectFieldnamesToResolve($module)
    {
        global $adb;

        // Cache hit?
        if (isset(self::$detectFieldnamesToResolveCache[$module])) {
            return self::$detectFieldnamesToResolveCache[$module];
        }

        $resolveUITypes = [10, 101, 116, 117, 26, 357, 50, 51, 52, 53, 57, 58, 59, 66, 68, 73, 75, 76, 77, 78, 80, 81];

        $result = $adb->pquery(
            'SELECT DISTINCT fieldname FROM vtiger_field WHERE uitype IN('
            . generateQuestionMarks($resolveUITypes) . ') AND tabid=?',
            [$resolveUITypes, getTabid($module)],
        );
        $fieldnames = [];

        while ($resultrow = $adb->fetch_array($result)) {
            $fieldnames[] = $resultrow['fieldname'];
        }

        // Cache information
        self::$detectFieldnamesToResolveCache[$module] = $fieldnames;

        return $fieldnames;
    }

    public static $gatherModuleFieldGroupInfoCache = [];

    public static function gatherModuleFieldGroupInfo($module)
    {
        global $adb;

        if ($module == 'Events') {
            $module = 'Calendar';
        }

        // Cache hit?
        if (isset(self::$gatherModuleFieldGroupInfoCache[$module])) {
            return self::$gatherModuleFieldGroupInfoCache[$module];
        }

        $result = $adb->pquery(
            'SELECT fieldname, fieldlabel, blocklabel, uitype FROM vtiger_field INNER JOIN
			vtiger_blocks ON vtiger_blocks.tabid=vtiger_field.tabid AND vtiger_blocks.blockid=vtiger_field.block 
			WHERE vtiger_field.tabid=? AND vtiger_field.presence != 1 ORDER BY vtiger_blocks.sequence, vtiger_field.sequence',
            [getTabid($module)],
        );

        $fieldgroups = [];

        while ($resultrow = $adb->fetch_array($result)) {
            $blocklabel = getTranslatedString($resultrow['blocklabel'], $module);
            if (!isset($fieldgroups[$blocklabel])) {
                $fieldgroups[$blocklabel] = [];
            }
            $fieldgroups[$blocklabel][$resultrow['fieldname']]
                = [
                    'label' => getTranslatedString($resultrow['fieldlabel'], $module),
                    'uitype' => self::fixUIType($module, $resultrow['fieldname'], $resultrow['uitype']),
                ];
        }

        // Cache information
        self::$gatherModuleFieldGroupInfoCache[$module] = $fieldgroups;

        return $fieldgroups;
    }

    public static function documentFoldersInfo()
    {
        global $adb;
        $folders = $adb->pquery('SELECT folderid, foldername FROM vtiger_attachmentsfolder', []);
        $folderOptions = [];

        while ($folderrow = $adb->fetch_array($folders)) {
            $folderwsid = sprintf('%sx%s', self::getEntityModuleWSId('DocumentFolders'), $folderrow['folderid']);
            $folderOptions[] = ['value' => $folderwsid, 'label' => $folderrow['foldername']];
        }

        return $folderOptions;
    }

    public static function salutationValues()
    {
        $values = vtlib_getPicklistValues('salutationtype');
        $options = [];
        foreach ($values as $value) {
            $options[] = ['value' => $value, 'label' => $value];
        }

        return $options;
    }

    public static function visibilityValues()
    {
        $options = [];
        // Avoid translation for these picklist values.
        $options[] =  ['value' => 'Private', 'label' => 'Private'];
        $options[] =  ['value' => 'Public', 'label' => 'Public'];

        return $options;
    }

    public static function fixUIType($module, $fieldname, $uitype)
    {
        if ($module == 'Contacts' || $module == 'Leads') {
            if ($fieldname == 'salutationtype') {
                return 16;
            }
        } elseif ($module == 'Calendar' || $module == 'Events') {
            if ($fieldname == 'time_start' || $fieldname == 'time_end') {
                // Special type for mandatory time type (not defined in product)
                return 252;
            }
        }

        return $uitype;
    }

    public static function fixDescribeFieldInfo($module, &$describeInfo)
    {

        if ($module == 'Leads' || $module == 'Contacts') {
            foreach ($describeInfo['fields'] as $index => $fieldInfo) {
                if ($fieldInfo['name'] == 'salutationtype') {
                    $picklistValues = self::salutationValues();
                    $fieldInfo['uitype'] = self::fixUIType($module, $fieldInfo['name'], $fieldInfo['uitype']);
                    $fieldInfo['type']['name'] = 'picklist';
                    $fieldInfo['type']['picklistValues'] = $picklistValues;
                    // $fieldInfo['type']['defaultValue'] = $picklistValues[0];

                    $describeInfo['fields'][$index] = $fieldInfo;
                }
            }
        } elseif ($module == 'Documents') {
            foreach ($describeInfo['fields'] as $index => $fieldInfo) {
                if ($fieldInfo['name'] == 'folderid') {
                    $picklistValues = self::documentFoldersInfo();
                    $fieldInfo['type']['picklistValues'] = $picklistValues;
                    // $fieldInfo['type']['defaultValue'] = $picklistValues[0];

                    $describeInfo['fields'][$index] = $fieldInfo;
                }
            }
        } elseif ($module == 'Calendar' || $module == 'Events') {
            foreach ($describeInfo['fields'] as $index => $fieldInfo) {
                $fieldInfo['uitype'] = self::fixUIType($module, $fieldInfo['name'], $fieldInfo['uitype']);
                if ($fieldInfo['name'] == 'activitytype') {
                    // Provide the option to create Todo like anyother Event.
                    $taskTypeFound = false;
                    foreach ($fieldInfo['type']['picklistValues'] as $option) {
                        if ($option['value'] == 'Task') {
                            $taskTypeFound = true;
                            break;
                        }
                    }
                    if (!$taskTypeFound) {
                        array_unshift($fieldInfo['type']['picklistValues'], ['label' => 'Task', 'value' => 'Task']);
                    }
                } elseif ($fieldInfo['name'] == 'visibility') {
                    if (empty($fieldInfo['type']['picklistValues'])) {
                        $fieldInfo['type']['picklistValues'] = self::visibilityValues();
                        $fieldInfo['type']['defaultValue'] = $fieldInfo['type']['picklistValues'][0]['value'];
                    }
                    $fieldInfo['default'] = $fieldInfo['type']['picklistValues'][0]['value'];
                }
                $describeInfo['fields'][$index] = $fieldInfo;
            }
        }
    }

    public static function getRelatedFunctionHandler($sourceModule, $targetModule)
    {
        global $adb;
        $relationResult = $adb->pquery('SELECT name FROM vtiger_relatedlists WHERE tabid=? and related_tabid=? and presence=0', [getTabid($sourceModule), getTabid($targetModule)]);
        $functionName = false;
        if ($adb->num_rows($relationResult)) {
            $functionName = $adb->query_result($relationResult, 0, 'name');
        }

        return $functionName;
    }

    /**
     * Security restriction (sharing privilege) query part.
     */
    public static function querySecurityFromSuffix($module, $current_user)
    {
        require 'user_privileges/user_privileges_' . $current_user->id . '.php';
        require 'user_privileges/sharing_privileges_' . $current_user->id . '.php';

        $querySuffix = '';
        $tabid = getTabid($module);

        if ($is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
            && $defaultOrgSharingPermission[$tabid] == 3) {

            $querySuffix .= " AND (vtiger_crmentity.smownerid in({$current_user->id}) OR vtiger_crmentity.smownerid IN 
					(
						SELECT vtiger_user2role.userid FROM vtiger_user2role 
						INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid 
						INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid 
						WHERE vtiger_role.parentrole LIKE '" . $current_user_parent_role_seq . "::%'
					) 
					OR vtiger_crmentity.smownerid IN 
					(
						SELECT shareduserid FROM vtiger_tmp_read_user_sharing_per 
						WHERE userid=" . $current_user->id . ' AND tabid=' . $tabid . '
					) 
					OR 
						(';

            // Build the query based on the group association of current user.
            if (php7_sizeof($current_user_groups) > 0) {
                $querySuffix .= ' vtiger_groups.groupid IN (' . implode(',', $current_user_groups) . ') OR ';
            }
            $querySuffix .= ' vtiger_groups.groupid IN 
						(
							SELECT vtiger_tmp_read_group_sharing_per.sharedgroupid 
							FROM vtiger_tmp_read_group_sharing_per
							WHERE userid=' . $current_user->id . ' and tabid=' . $tabid . '
						)';
            $querySuffix .= ')
				)';
        }

        return $querySuffix;
    }
}
