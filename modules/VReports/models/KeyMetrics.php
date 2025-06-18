<?php

class VReports_KeyMetrics_Model extends Vtiger_MiniList_Model
{
    public function getShareIdForUser($current_user)
    {
        $db = PearDatabase::getInstance();
        $id = $current_user->id;
        $role = explode('::', $current_user->getParentRoleSequence());
        $cvid = '';
        $query = $db->pquery('SELECT cvid FROM vtiger_cv2users WHERE userid = ?', [$id]);
        if ($db->num_rows($query) > 0) {
            while ($row = $db->fetch_array($query)) {
                $cvid .= $row['cvid'] . ',';
            }
        }
        $userRole = '';
        foreach ($role as $item => $value) {
            $userRole .= "'" . $value . "',";
        }
        $userRole = trim($userRole, ',');
        $userRole = trim($userRole, '"');
        $query2 = $db->pquery('SELECT cvid FROM vtiger_cv2role WHERE roleid in (' . $userRole . ')', []);
        if ($db->num_rows($query2) > 0) {
            while ($row = $db->fetch_array($query2)) {
                $cvid .= $row['cvid'] . ',';
            }
        }
        $cvid = trim($cvid, ',');

        return $cvid;
    }

    public function getMetricList($data = '')
    {
        $current_user = Users_Privileges_Model::getCurrentUserModel();
        $db = PearDatabase::getInstance();
        require 'user_privileges/user_privileges_' . $current_user->id . '.php';
        if ($data && $data != 'null') {
            $data = json_decode(html_entity_decode($data));
            $inArray = "'" . implode("','", $data->fields) . "'";
        }
        $keyMetricsModel = new self();
        $cvid = $keyMetricsModel->getShareIdForUser($current_user);
        if (!$cvid) {
            $cvid = '""';
        }
        $ssql = 'select vtiger_customview.* from vtiger_customview inner join vtiger_tab on vtiger_tab.name = vtiger_customview.entitytype';
        $sparams = [];
        if ($is_admin == false) {
            $ssql .= ' WHERE (vtiger_customview.status=0 or vtiger_customview.userid = ? OR vtiger_customview.cvid in (' . $cvid . ") or vtiger_customview.status =3 or vtiger_customview.userid in(select vtiger_user2role.userid from vtiger_user2role inner join vtiger_users on vtiger_users.id=vtiger_user2role.userid inner join vtiger_role on vtiger_role.roleid=vtiger_user2role.roleid where vtiger_role.parentrole like '" . $current_user_parent_role_seq . "::%'))";
            array_push($sparams, $current_user->id);
        }
        if ($inArray && $is_admin == false) {
            $ssql .= ' AND vtiger_customview.cvid IN ( ' . $inArray . ' ) ';
            $ssql .= ' ORDER BY FIELD(cvid, ' . $inArray . ' ) ';
        } else {
            if ($inArray && $is_admin == true) {
                $ssql .= ' WHERE vtiger_customview.cvid IN ( ' . $inArray . ' ) ';
                $ssql .= ' ORDER BY FIELD(cvid, ' . $inArray . ' ) ';
            } else {
                $ssql .= ' ORDER BY vtiger_customview.entitytype';
            }
        }
        $result = $db->pquery($ssql, $sparams);
        $metriclists = [];

        while ($cvrow = $db->fetch_array($result)) {
            $nonVisibleModules = Settings_Profiles_Module_Model::getNonVisibleModulesList();
            $metricslist = [];
            if (in_array($cvrow['entitytype'], $nonVisibleModules)) {
                continue;
            }
            if (vtlib_isModuleActive($cvrow['entitytype'])) {
                $metricslist['id'] = $cvrow['cvid'];
                $metricslist['name'] = $cvrow['viewname'];
                $metricslist['module'] = $cvrow['entitytype'];
                $metricslist['user'] = getUserFullName($cvrow['userid']);
                $metricslist['count'] = '';
                if (isPermitted($cvrow['entitytype'], 'index') == 'yes') {
                    $metriclists[] = $metricslist;
                }
            }
        }

        return $metriclists;
    }

    public function getKeyMetricsWithCount($widget = '', $mode = '')
    {
        $db = PearDatabase::getInstance();
        $keyMetricsModel = new self();
        $current_user = Users_Record_Model::getCurrentUserModel();
        if ($widget) {
            if (!is_string($widget)) {
                $metriclists = $keyMetricsModel->getMetricList($widget->get('data'));
            } else {
                $metriclists = $keyMetricsModel->getMetricList($widget);
            }
        } else {
            $metriclists = $keyMetricsModel->getMetricList();
        }
        foreach ($metriclists as $key => $metriclist) {
            $metricresult = null;
            if ($metriclists[$key]['name'] == 'All') {
                $metriclists[$key]['name'] = vtranslate($metriclists[$key]['module'], $metriclists[$key]['module']) . ' - ' . vtranslate($metriclists[$key]['name'], $metriclists[$key]['module']);
            }

            try {
                $queryGenerator = new EnhancedQueryGenerator($metriclist['module'], $current_user);
            } catch (Exception $e) {
                if ($e->getCode() == 'ACCESS_DENIED') {
                    continue;
                }
            }
            $queryGenerator->initForCustomViewById($metriclist['id']);
            if ($metriclist['module'] == 'Calendar') {
                $queryGenerator->addCondition('activitytype', 'Emails', 'n', QueryGenerator::$AND);
            }
            $metricsql = $queryGenerator->getQuery();
            if ($mode != 'add') {
                if ($widget) {
                    $metricresult = $db->query(Vtiger_Functions::mkCountQuery($metricsql));
                }
                if ($metricresult) {
                    $rowcount = $db->fetch_array($metricresult);
                    $metriclists[$key]['count'] = $rowcount['count'];
                    $metriclists[$key]['km_show_empty_val'] = $widget->get('km_show_empty_val');
                }
            }
        }

        return $metriclists;
    }
}
