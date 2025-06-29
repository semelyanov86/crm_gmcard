<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
vimport('~~/modules/Reports/Reports.php');

class Vtiger_Report_Model extends Reports
{
    public static function getInstance($reportId = '')
    {
        $self = new self();

        return $self->Reports($reportId);
    }

    public function Reports($reportId = '')
    {
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userId = $currentUser->getId();
        $currentUserRoleId = $currentUser->get('roleid');
        $userGroupsQuery = '';
        $current_user_parent_role_seq = '';
        $subordinateRoles = getRoleSubordinates($currentUserRoleId);
        array_push($subordinateRoles, $currentUserRoleId);

        $this->initListOfModules();

        if ($reportId != '') {
            // Lookup information in cache first
            $cachedInfo = VTCacheUtils::lookupReport_Info($userId, $reportId);
            $subOrdinateUsers = VTCacheUtils::lookupReport_SubordinateUsers($reportId);

            if ($cachedInfo === false) {
                $ssql = 'SELECT vtiger_reportmodules.*, vtiger_report.* FROM vtiger_report
							INNER JOIN vtiger_reportmodules ON vtiger_report.reportid = vtiger_reportmodules.reportmodulesid
							WHERE vtiger_report.reportid = ?';
                $params = [$reportId];

                require_once 'include/utils/GetUserGroups.php';
                require 'user_privileges/user_privileges_' . $userId . '.php';

                $userGroups = new GetUserGroups();
                $userGroups->getAllUserGroups($userId);
                $userGroupsList = $userGroups->user_groups;

                if (!empty($userGroupsList) && $currentUser->isAdminUser() == false) {
                    $userGroupsQuery = ' (shareid IN (' . generateQuestionMarks($userGroupsList) . ") AND setype='groups') OR";
                    foreach ($userGroupsList as $group) {
                        array_push($params, $group);
                    }
                }

                $nonAdminQuery = " vtiger_report.reportid IN (SELECT reportid from vtiger_reportsharing
									WHERE {$userGroupsQuery} (shareid=? AND setype='users'))";
                if ($currentUser->isAdminUser() == false) {
                    $ssql .= " AND (({$nonAdminQuery})
								OR vtiger_report.sharingtype = 'Public'
								OR vtiger_report.owner = ? OR vtiger_report.owner IN
									(SELECT vtiger_user2role.userid FROM vtiger_user2role
									INNER JOIN vtiger_users ON vtiger_users.id = vtiger_user2role.userid
									INNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid
									WHERE vtiger_role.parentrole LIKE '{$current_user_parent_role_seq}::%') 
								OR (vtiger_report.reportid IN (SELECT reportid FROM vtiger_report_shareusers WHERE userid = ?))";
                    if (!empty($userGroupsList)) {
                        $ssql .= ' OR (vtiger_report.reportid IN (SELECT reportid FROM vtiger_report_sharegroups 
									WHERE groupid IN (' . generateQuestionMarks($userGroupsList) . ')))';
                    }
                    $ssql .= ' OR (vtiger_report.reportid IN (SELECT reportid FROM vtiger_report_sharerole WHERE roleid = ?))
							   OR (vtiger_report.reportid IN (SELECT reportid FROM vtiger_report_sharers 
								WHERE rsid IN (' . generateQuestionMarks($subordinateRoles) . ')))
							  )';
                    array_push($params, $userId, $userId, $userId);
                    foreach ($userGroupsList as $groups) {
                        array_push($params, $groups);
                    }
                    array_push($params, $currentUserRoleId);
                    foreach ($subordinateRoles as $role) {
                        array_push($params, $role);
                    }
                }
                $result = $db->pquery($ssql, $params);

                if ($result && $db->num_rows($result)) {
                    $reportModulesRow = $db->fetch_array($result);

                    // Update information in cache now
                    VTCacheUtils::updateReport_Info(
                        $userId,
                        $reportId,
                        $reportModulesRow['primarymodule'],
                        $reportModulesRow['secondarymodules'],
                        $reportModulesRow['reporttype'],
                        $reportModulesRow['reportname'],
                        $reportModulesRow['description'],
                        $reportModulesRow['folderid'],
                        $reportModulesRow['owner'],
                    );
                }

                $subOrdinateUsers = [];

                $subResult = $db->pquery("SELECT userid FROM vtiger_user2role
									INNER JOIN vtiger_users ON vtiger_users.id = vtiger_user2role.userid
									INNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid
									WHERE vtiger_role.parentrole LIKE '{$current_user_parent_role_seq}::%'", []);

                $numOfSubRows = $db->num_rows($subResult);

                for ($i = 0; $i < $numOfSubRows; ++$i) {
                    $subOrdinateUsers[] = $db->query_result($subResult, $i, 'userid');
                }

                // Update subordinate user information for re-use
                VTCacheUtils::updateReport_SubordinateUsers($reportId, $subOrdinateUsers);

                // Re-look at cache to maintain code-consistency below
                $cachedInfo = VTCacheUtils::lookupReport_Info($userId, $reportId);
            }

            if ($cachedInfo) {
                $this->primodule = $cachedInfo['primarymodule'];
                $this->secmodule = $cachedInfo['secondarymodules'];
                $this->reporttype = $cachedInfo['reporttype'];
                $this->reportname = decode_html($cachedInfo['reportname']);
                $this->reportdescription = decode_html($cachedInfo['description']);
                $this->folderid = $cachedInfo['folderid'];
                if ($currentUser->isAdminUser() == true || in_array($cachedInfo['owner'], $subOrdinateUsers) || $cachedInfo['owner'] == $userId) {
                    $this->is_editable = true;
                } else {
                    $this->is_editable = false;
                }
            }
        }

        return $this;
    }

    public function isEditable()
    {
        return $this->is_editable;
    }

    public function getModulesList()
    {
        foreach ($this->module_list as $key => $value) {
            if (isPermitted($key, 'index') == 'yes') {
                $modules[$key] = vtranslate($key, $key);
            }
        }
        asort($modules);

        return $modules;
    }
}
