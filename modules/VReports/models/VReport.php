<?php

vimport('~~/modules/VReports/VReports.php');

class Vtiger_VReport_Model extends VReports
{
    public static function getInstance($reportId = '')
    {
        $self = new self();

        return $self->VReports($reportId);
    }

    public function VReports($reportId = '')
    {
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userId = $currentUser->getId();
        $currentUserRoleId = $currentUser->get('roleid');
        $subordinateRoles = getRoleSubordinates($currentUserRoleId);
        array_push($subordinateRoles, $currentUserRoleId);
        $this->initListOfModules();
        if ($reportId != '') {
            $cachedInfo = VTCacheUtils::lookupReport_Info($userId, $reportId);
            $subOrdinateUsers = VTCacheUtils::lookupReport_SubordinateUsers($reportId);
            if ($cachedInfo === false) {
                $ssql = "SELECT vtiger_vreportmodules.*, vtiger_vreport.*,vtiger_vreport_shareall.is_shareall FROM vtiger_vreport\n\t\t\t\t\t\t\tINNER JOIN vtiger_vreportmodules ON vtiger_vreport.reportid = vtiger_vreportmodules.reportmodulesid\n\t\t\t\t\t\t\tLEFT JOIN vtiger_vreport_shareall ON vtiger_vreport.reportid = vtiger_vreport_shareall.reportid\n\t\t\t\t\t\t\tWHERE vtiger_vreport.reportid = ?";
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
                $nonAdminQuery = " vtiger_vreport.reportid IN (SELECT reportid from vtiger_vreportsharing\n\t\t\t\t\t\t\t\t\tWHERE " . $userGroupsQuery . " (shareid=? AND setype='users'))";
                if ($currentUser->isAdminUser() == false) {
                    $ssql .= ' AND ((' . $nonAdminQuery . ")\n\t\t\t\t\t\t\t\tOR vtiger_vreport.sharingtype = 'Public'\n\t\t\t\t\t\t\t\tOR vtiger_vreport.owner = ? OR vtiger_vreport.owner IN\n\t\t\t\t\t\t\t\t\t(SELECT vtiger_user2role.userid FROM vtiger_user2role\n\t\t\t\t\t\t\t\t\tINNER JOIN vtiger_users ON vtiger_users.id = vtiger_user2role.userid\n\t\t\t\t\t\t\t\t\tINNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid\n\t\t\t\t\t\t\t\t\tWHERE vtiger_role.parentrole LIKE '" . $current_user_parent_role_seq . "::%') \n\t\t\t\t\t\t\t\tOR (vtiger_vreport.reportid IN (SELECT reportid FROM vtiger_vreport_shareusers WHERE userid = ?))";
                    if (!empty($userGroupsList)) {
                        $ssql .= " OR (vtiger_vreport.reportid IN (SELECT reportid FROM vtiger_vreport_sharegroups \n\t\t\t\t\t\t\t\t\tWHERE groupid IN (" . generateQuestionMarks($userGroupsList) . ')))';
                    }
                    $ssql .= " OR (vtiger_vreport.reportid IN (SELECT reportid FROM vtiger_vreport_sharerole WHERE roleid = ?))\n\t\t\t\t\t\t\t   OR (vtiger_vreport.reportid IN (SELECT reportid FROM vtiger_vreport_sharers \n\t\t\t\t\t\t\t\tWHERE rsid IN (" . generateQuestionMarks($subordinateRoles) . ")))\n\t\t\t\t\t\t\t  )";
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
                    VTCacheUtils::updateReport_Info($userId, $reportId, $reportModulesRow['primarymodule'], $reportModulesRow['secondarymodules'], $reportModulesRow['reporttype'], $reportModulesRow['reportname'], $reportModulesRow['description'], $reportModulesRow['folderid'], $reportModulesRow['owner']);
                    $sharingType = $reportModulesRow['sharingtype'];
                }
                $subOrdinateUsers = [];
                $subResult = $db->pquery("SELECT userid FROM vtiger_user2role\n\t\t\t\t\t\t\t\t\tINNER JOIN vtiger_users ON vtiger_users.id = vtiger_user2role.userid\n\t\t\t\t\t\t\t\t\tINNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid\n\t\t\t\t\t\t\t\t\tWHERE vtiger_role.parentrole LIKE '" . $current_user_parent_role_seq . "::%'", []);
                $numOfSubRows = $db->num_rows($subResult);
                for ($i = 0; $i < $numOfSubRows; ++$i) {
                    $subOrdinateUsers[] = $db->query_result($subResult, $i, 'userid');
                }
                VTCacheUtils::updateReport_SubordinateUsers($reportId, $subOrdinateUsers);
                $cachedInfo = VTCacheUtils::lookupReport_Info($userId, $reportId);
            }
            if ($cachedInfo) {
                $this->primodule = $cachedInfo['primarymodule'];
                $this->secmodule = $cachedInfo['secondarymodules'];
                $this->reporttype = $cachedInfo['reporttype'];
                $this->reportname = decode_html($cachedInfo['reportname']);
                $this->reportdescription = decode_html($cachedInfo['description']);
                $this->folderid = $cachedInfo['folderid'];
                $this->isEditableBySharing($reportId);
                if ($currentUser->isAdminUser() == true || in_array($cachedInfo['owner'], $subOrdinateUsers) || $cachedInfo['owner'] == $userId || $this->isEditableBySharing($reportId) == true || $sharingType == 'Public') {
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

    public function isEditableBySharing($reportId)
    {
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $rsUserSharing = $db->pquery('SELECT 1 FROM vtiger_vreport_shareusers WHERE reportid = ? AND  userid = ?', [$reportId, $currentUser->getId()]);
        if ($db->num_rows($rsUserSharing) > 0) {
            return true;
        }
        $rsRoleSharing = $db->pquery('SELECT 1 FROM vtiger_vreport_sharerole WHERE reportid = ? AND  roleid = ?', [$reportId, $currentUser->getRole()]);
        if ($db->num_rows($rsRoleSharing) > 0) {
            return true;
        }
        $rsGroupsSharing = $db->pquery('SELECT * FROM vtiger_vreport_sharegroups WHERE reportid = ?', [$reportId]);

        while ($rowGroupsSharing = $db->fetchByAssoc($rsGroupsSharing)) {
            $groupId = $rowGroupsSharing['groupid'];
            $groupModel = Settings_Groups_Record_Model::getInstance($groupId);
            $userList = $groupModel->getUsersList();
            if (array_key_exists($currentUser->getId(), $userList)) {
                return true;
            }
        }
        $rsRoleSharing = $db->pquery('SELECT * FROM vtiger_vreport_sharers WHERE reportid = ?', [$reportId]);

        while ($rowRoleSharing = $db->fetchByAssoc($rsRoleSharing)) {
            $roleId = $rowRoleSharing['rsid'];
            $roleModel = Settings_Roles_Record_Model::getInstanceById($roleId);
            $allChildrenRole = $roleModel->getAllChildren();
            if ($roleId == $currentUser->getRole() || array_key_exists($currentUser->getRole(), $allChildrenRole)) {
                return true;
            }
        }

        return false;
    }
}
