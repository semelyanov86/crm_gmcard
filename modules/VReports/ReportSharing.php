<?php

require_once 'data/Tracker.php';
require_once 'include/logging.php';
require_once 'include/utils/utils.php';
require_once 'modules/VReports/VReports.php';
global $app_strings;
global $app_list_strings;
global $mod_strings;
$current_module_strings = return_module_language($current_language, 'VReports');
global $list_max_entries_per_page;
global $urlPrefix;
$log = LoggerManager::getLogger('report_type');
global $currentModule;
global $image_path;
global $theme;
global $current_user;
$report_std_filter = new vtigerCRM_Smarty();
$report_std_filter->assign('MOD', $mod_strings);
$report_std_filter->assign('APP', $app_strings);
$report_std_filter->assign('IMAGE_PATH', $image_path);
$report_std_filter->assign('DATEFORMAT', $current_user->date_format);
$report_std_filter->assign('JS_DATEFORMAT', parse_calendardate($app_strings['NTC_DATE_FORMAT']));
$roleid = $current_user->column_fields['roleid'];
$user_array = getAllUserName();
$userIdStr = '';
$userNameStr = '';
$m = 0;
foreach ($user_array as $userid => $username) {
    if ($userid != $current_user->id) {
        if ($m != 0) {
            $userIdStr .= ',';
            $userNameStr .= ',';
        }
        $userIdStr .= "'" . $userid . "'";
        $userNameStr .= "'" . escape_single_quotes(decode_html($username)) . "'";
        ++$m;
    }
}
$user_groups = getAllGroupName();
$groupIdStr = '';
$groupNameStr = '';
$l = 0;
foreach ($user_groups as $grpid => $groupname) {
    if ($l != 0) {
        $groupIdStr .= ',';
        $groupNameStr .= ',';
    }
    $groupIdStr .= "'" . $grpid . "'";
    $groupNameStr .= "'" . escape_single_quotes(decode_html($groupname)) . "'";
    ++$l;
}
if (isset($_REQUEST['record']) && $_REQUEST['record'] != '') {
    $reportid = vtlib_purify($_REQUEST['record']);
    $visiblecriteria = getVisibleCriteria($recordid);
    $report_std_filter->assign('VISIBLECRITERIA', $visiblecriteria);
    $member = getShareInfo($recordid);
    $report_std_filter->assign('MEMBER', $member);
} else {
    $visiblecriteria = getVisibleCriteria();
    $report_std_filter->assign('VISIBLECRITERIA', $visiblecriteria);
}
$report_std_filter->assign('GROUPNAMESTR', $groupNameStr);
$report_std_filter->assign('USERNAMESTR', $userNameStr);
$report_std_filter->assign('GROUPIDSTR', $groupIdStr);
$report_std_filter->assign('USERIDSTR', $userIdStr);
$report_std_filter->display('ReportSharing.tpl');
/** Function to get visible criteria for a report
 *  This function accepts The reportid as an argument
 *  It returns a array of selected option of sharing along with other options.
 */
function getVisibleCriteria($recordid = '')
{
    global $mod_strings;
    global $app_strings;
    global $adb;
    global $current_user;
    $filter = [];
    $selcriteria = '';
    if ($recordid != '') {
        $result = $adb->pquery('select sharingtype from vtiger_vreport where reportid=?', [$recordid]);
        $selcriteria = $adb->query_result($result, 0, 'sharingtype');
    }
    if ($selcriteria == '') {
        $selcriteria = 'Public';
    }
    $filter_result = $adb->query('select * from vtiger_vreportfilters');
    $numrows = $adb->num_rows($filter_result);
    for ($j = 0; $j < $numrows; ++$j) {
        $filter_id = $adb->query_result($filter_result, $j, 'filterid');
        $filtername = $adb->query_result($filter_result, $j, 'name');
        $name = str_replace(' ', '_', $filtername);
        if ($filtername == 'Private') {
            $FilterKey = 'Private';
            $FilterValue = getTranslatedString('PRIVATE_FILTER');
        } else {
            if ($filtername == 'Shared') {
                $FilterKey = 'Shared';
                $FilterValue = getTranslatedString('SHARE_FILTER');
            } else {
                $FilterKey = 'Public';
                $FilterValue = getTranslatedString('PUBLIC_FILTER');
            }
        }
        if ($FilterKey == $selcriteria) {
            $shtml['value'] = $FilterKey;
            $shtml['text'] = $FilterValue;
            $shtml['selected'] = 'selected';
        } else {
            $shtml['value'] = $FilterKey;
            $shtml['text'] = $FilterValue;
            $shtml['selected'] = '';
        }
        $filter[] = $shtml;
    }

    return $filter;
}
function getShareInfo($recordid = '')
{
    global $adb;
    $member_query = $adb->pquery("SELECT vtiger_vreportsharing.setype,vtiger_users.id,vtiger_users.user_name FROM vtiger_vreportsharing INNER JOIN vtiger_users on vtiger_users.id = vtiger_vreportsharing.shareid WHERE vtiger_vreportsharing.setype='users' AND vtiger_vreportsharing.reportid = ?", [$recordid]);
    $noofrows = $adb->num_rows($member_query);
    if ($noofrows > 0) {
        for ($i = 0; $i < $noofrows; ++$i) {
            $userid = $adb->query_result($member_query, $i, 'id');
            $username = $adb->query_result($member_query, $i, 'user_name');
            $setype = $adb->query_result($member_query, $i, 'setype');
            $member_data[] = ['id' => $setype . '::' . $userid, 'name' => $setype . '::' . $username];
        }
    }
    $member_query = $adb->pquery("SELECT vtiger_vreportsharing.setype,vtiger_groups.groupid,vtiger_groups.groupname FROM vtiger_vreportsharing INNER JOIN vtiger_groups on vtiger_groups.groupid = vtiger_vreportsharing.shareid WHERE vtiger_vreportsharing.setype='groups' AND vtiger_vreportsharing.reportid = ?", [$recordid]);
    $noofrows = $adb->num_rows($member_query);
    if ($noofrows > 0) {
        for ($i = 0; $i < $noofrows; ++$i) {
            $grpid = $adb->query_result($member_query, $i, 'groupid');
            $grpname = $adb->query_result($member_query, $i, 'groupname');
            $setype = $adb->query_result($member_query, $i, 'setype');
            $member_data[] = ['id' => $setype . '::' . $grpid, 'name' => $setype . '::' . $grpname];
        }
    }

    return $member_data;
}
