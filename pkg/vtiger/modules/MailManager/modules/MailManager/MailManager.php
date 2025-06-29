<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
require_once 'include/Webservices/Query.php';

#[AllowDynamicProperties]
class MailManager
{
    public static function updateMailAssociation($mailuid, $emailid, $crmid)
    {
        global $adb;
        $adb->pquery('INSERT INTO vtiger_mailmanager_mailrel (mailuid, emailid, crmid) VALUES (?,?,?)', [$mailuid, $emailid, $crmid]);
    }

    public static function lookupMailInVtiger($searchTerm, $user)
    {
        $handler = vtws_getModuleHandlerFromName('Emails', $user);
        $meta = $handler->getMeta();
        $moduleFields = $meta->getModuleFields();
        $parentIdFieldInstance = $moduleFields['parent_id'];
        $referenceModules = $parentIdFieldInstance->getReferenceList();

        $filteredResult = [];
        foreach ($referenceModules as $referenceModule) {
            $referenceModuleHandler = vtws_getModuleHandlerFromName($referenceModule, $user);
            $referenceModuleMeta = $referenceModuleHandler->getMeta();
            $referenceModuleEmailFields = $referenceModuleMeta->getEmailFields();
            $referenceModuleModel = Vtiger_Module_Model::getInstance($referenceModule);
            if ($referenceModuleModel) {
                $referenceModuleEntityFieldsArray = $referenceModuleModel->getNameFields();
            }
            $searchFieldList = array_merge($referenceModuleEmailFields, $referenceModuleEntityFieldsArray);
            if (!empty($searchFieldList) && !empty($referenceModuleEmailFields)) {
                $searchFieldListString = implode(',', $referenceModuleEmailFields);
                $where = '';
                $params = [];
                for ($i = 0; $i < php7_count($searchFieldList); ++$i) {
                    if ($i == php7_count($searchFieldList) - 1) {
                        $where .= ($searchFieldList[$i] . " like '%s'");
                        $params[] = $searchTerm;
                    } else {
                        $where .= ($searchFieldList[$i] . " like '%s' or ");
                        $params[] = $searchTerm;
                    }
                }
                if ($referenceModule == 'Users' && !is_admin($user)) {
                    // Have to do seperate query since webservices will throw permission denied for users module for non admin users
                    global $adb;
                    if (!empty($where)) {
                        $where = 'WHERE ' . str_replace("'%s'", '?', $where);
                    } // query placeholders
                    $where .= " AND vtiger_users.status='Active'";
                    $query = "select {$searchFieldListString},id from vtiger_users {$where}";
                    $dbResult = $adb->pquery($query, $params);
                    $num_rows = $adb->num_rows($dbResult);
                    $result = [];
                    for ($i = 0; $i < $num_rows; ++$i) {
                        $row = $adb->query_result_rowdata($dbResult, $i);
                        $id = $row['id'];
                        $webserviceId = vtws_getWebserviceEntityId($referenceModule, $id);
                        $row['id'] = $webserviceId;
                        $result[] = $row;
                    }
                } else {
                    if (!empty($where)) {
                        array_unshift($params, $where);
                        $where = 'WHERE ' . call_user_func_array('sprintf', $params); // webservice query strings
                    }
                    $result = vtws_query("select {$searchFieldListString} from {$referenceModule} {$where};", $user);
                }


                foreach ($result as $record) {
                    foreach ($searchFieldList as $searchField) {
                        if (!empty($record[$searchField])) {
                            $filteredResult[] = ['id' => $record[$searchField], 'name' => $record[$searchField] . ' - ' . getTranslatedString($referenceModule),
                                'record' => $record['id'], 'module' => $referenceModule];
                        }
                    }
                }
            }
        }

        return $filteredResult;
    }

    public static function lookupMailAssociation($mailuid)
    {
        global $adb;

        // Mail could get associated with two-or-more records if they get deleted after linking.
        $result = $adb->pquery(
            'SELECT vtiger_mailmanager_mailrel.* FROM vtiger_mailmanager_mailrel INNER JOIN
			vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_mailmanager_mailrel.crmid AND vtiger_crmentity.deleted=0
			AND vtiger_mailmanager_mailrel.mailuid=? LIMIT 1',
            [decode_html($mailuid)],
        );
        if ($adb->num_rows($result)) {
            $resultrow = $adb->fetch_array($result);

            return $resultrow;
        }

        return false;
    }

    public static function lookupVTEMailAssociation($emailId)
    {
        global $adb;
        $result = $adb->pquery(
            'SELECT vtiger_mailmanager_mailrel.* FROM vtiger_mailmanager_mailrel INNER JOIN
			vtiger_crmentity ON vtiger_crmentity.crmid=vtiger_mailmanager_mailrel.crmid AND vtiger_crmentity.deleted=0
			AND vtiger_mailmanager_mailrel.mailuid=? LIMIT 1',
            [decode_html($mailuid)],
        );
        if ($adb->num_rows($result)) {
            $resultrow = $adb->fetch_array($result);

            return $resultrow;
        }

        return false;
    }

    public static function checkModuleWriteAccessForCurrentUser($module)
    {
        global $current_user;
        if (isPermitted($module, 'CreateView') == 'yes' && vtlib_isModuleActive($module)) {
            return true;
        }

        return false;
    }

    /**
     * function to check the read access for the current user.
     * @global Users Instance $current_user
     * @param string $module - Name of the module
     * @return bool
     */
    public static function checkModuleReadAccessForCurrentUser($module)
    {
        global $current_user;
        if (isPermitted($module, 'DetailView') == 'yes' && vtlib_isModuleActive($module)) {
            return true;
        }

        return false;
    }

    /**
     * Invoked when special actions are performed on the module.
     * @param string $modulename - Module name
     * @param string $event_type - Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    public function vtlib_handler($modulename, $event_type)
    {
        if ($event_type == 'module.postinstall') {
            // TODO Handle actions when this module is installed.
        } elseif ($event_type == 'module.disabled') {
            // TODO Handle actions when this module is disabled.
        } elseif ($event_type == 'module.enabled') {
            // TODO Handle actions when this module is enabled.
        } elseif ($event_type == 'module.preuninstall') {
            // TODO Handle actions when this module is about to be deleted.
        } elseif ($event_type == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } elseif ($event_type == 'module.postupdate') {
            // TODO Handle actions when this module is updated.
        }
    }
}
