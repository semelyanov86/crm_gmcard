<?php

/**
 * Date: 12/18/18
 * Time: 11:22 AM.
 */
class VReports_DashboardActions_Action extends Vtiger_Action_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('updatePositionWidgets');
        $this->exposeMethod('addTab');
        $this->exposeMethod('duplicateTab');
        $this->exposeMethod('EditBoard');
        $this->exposeMethod('deleteTab');
        $this->exposeMethod('deleteBoard');
        $this->exposeMethod('renameTab');
        $this->exposeMethod('updateTabSequence');
        $this->exposeMethod('getBoardInfo');
        $this->exposeMethod('getTabsByBoardId');
        $this->exposeMethod('saveDynamicFilter');
        $this->exposeMethod('deleteOrgInUrl');
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    public function updatePositionWidgets(Vtiger_Request $request)
    {
        global $adb;
        $widgets = $request->get('data');
        foreach ($widgets as $widgetData) {
            $widgetId = $widgetData['widgetId'];
            $position = json_encode(['x' => $widgetData['x'], 'y' => $widgetData['y']]);
            $width = $widgetData['width'];
            $height = $widgetData['height'];
            $widthPx = $widgetData['widthPx'];
            $adb->pquery('UPDATE vtiger_module_vreportdashboard_widgets SET `position` = ?, `sizeWidth` = ?, `sizeHeight` = ? ,widthPx = ? WHERE id = ?', [$position, $width, $height, $widthPx, $widgetId]);
        }
        $response = new Vtiger_Response();
        $response->setResult(true);
        $response->emit();
    }

    public function addTab(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $tabName = $request->getRaw('tabName');
        $currentBoardId = $request->getRaw('boardid');
        $boardId = $request->getRaw('slDashBoardBoard');
        if (!$boardId) {
            $boardId = $currentBoardId;
        }
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        $tabExist = $dashBoardModel->checkTabExist($tabName, $boardId);
        $tabLimitExceeded = $dashBoardModel->checkTabsLimitExceeded();
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($tabLimitExceeded) {
            $response->setError(100, vtranslate('LBL_TABS_LIMIT_EXCEEDED', $moduleName));
        } else {
            if ($tabExist) {
                $response->setError(100, vtranslate('LBL_DASHBOARD_TAB_ALREADY_EXIST', $moduleName));
            } else {
                $tabData = $dashBoardModel->addTab($tabName, $boardId);
                $response->setResult($tabData);
            }
        }
        $response->emit();
    }

    public function duplicateTab(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $tabName = $request->getRaw('tabName');
        if ($request->getRaw('slDashBoardBoard')) {
            $boardId = $request->getRaw('slDashBoardBoard');
        } else {
            $boardId = $request->getRaw('boardid');
        }
        $duplicateTabId = $request->getRaw('duplicateTabId');
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        $tabExist = $dashBoardModel->checkTabExist($tabName, $boardId);
        $tabLimitExceeded = $dashBoardModel->checkTabsLimitExceeded();
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($tabLimitExceeded) {
            $response->setError(100, vtranslate('LBL_TABS_LIMIT_EXCEEDED', $moduleName));
        } else {
            if ($tabExist) {
                $response->setError(100, vtranslate('LBL_DASHBOARD_TAB_ALREADY_EXIST', $moduleName));
            } else {
                $tabData = $dashBoardModel->duplicateTab($tabName, $boardId, $duplicateTabId);
                $response->setResult($tabData);
            }
        }
        $response->emit();
    }

    public function EditBoard(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $boardId = $request->getRaw('select-board');
        $boardName = $request->getRaw('boardName');
        $boardSharedTo = $request->getRaw('members');
        $defaultBoard = $request->getRaw('defaultToEveryone');
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        $boardExist = $dashBoardModel->checkBoardExist($boardName, $boardId);
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($boardExist) {
            $response->setError(100, vtranslate('LBL_DASHBOARD_BOARD_ALREADY_EXIST', $moduleName));
        } else {
            $tabData = $dashBoardModel->addBoard($boardId, $boardName, $boardSharedTo, $defaultBoard);
            $response->setResult($tabData);
        }
        $response->emit();
    }

    public function deleteTab(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $tabId = $request->get('tabid');
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        $result = $dashBoardModel->deleteTab($tabId);
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($result) {
            $response->setResult($result);
        } else {
            $response->setError(100, 'Failed To Delete Tab');
        }
        $response->emit();
    }

    public function deleteBoard(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $boardId = $request->get('select-board');
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        if ($boardId != 1) {
            $result = $dashBoardModel->deleteBoard($boardId);
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($result && $result !== 1) {
            $response->setResult($result);
        } else {
            $response->setError(100, 'Failed To Delete Board');
        }
        $response->emit();
    }

    public function renameTab(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $tabName = $request->get('tabname');
        $tabId = $request->get('tabid');
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        $result = $dashBoardModel->renameTab($tabId, $tabName);
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($result) {
            $response->setResult($result);
        } else {
            $response->setError(100, 'Failed To rename Tab');
        }
        $response->emit();
    }

    public function updateTabSequence(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $sequence = $request->get('sequence');
        $dashBoardModel = VReports_DashBoard_Model::getInstance($moduleName);
        $result = $dashBoardModel->updateTabSequence($sequence);
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($result) {
            $response->setResult($result);
        } else {
            $response->setError(100, 'Failed To rearrange Tabs');
        }
        $response->emit();
    }

    public function getBoardInfo(Vtiger_Request $request)
    {
        global $adb;
        global $default_charset;
        $boardId = $request->get('id');
        $rs = $adb->pquery('SELECT * FROM vtiger_vreportdashboard_boards WHERE id = ' . $boardId);

        while ($rs && ($row = $adb->fetchByAssoc($rs))) {
            $row['shared_to'] = str_replace('|##|', ',', $row['shared_to']);
            $row['boardname'] = html_entity_decode(trim($row['boardname']), ENT_QUOTES, $default_charset);
            $result = $row;
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($result) {
            $response->setResult($result);
        } else {
            $response->setError(100, 'Failed To get Board Info');
        }
        $response->emit();
    }

    public function getTabsByBoardId(Vtiger_Request $request)
    {
        global $adb;
        global $current_user;
        global $default_charset;
        $boardId = $request->get('boardid');
        $rsCheck = $adb->pquery('select boardname from vtiger_vreportdashboard_boards where id = ' . $boardId);
        $sql = '';
        if ($rsCheck && ($boardName = $adb->query_result($rsCheck, 0, 'boardname'))) {
            if ($boardName == 'Default') {
                $sql = "SELECT vtiger_vreportdashboard_tabs.* FROM vtiger_vreportdashboard_boards\n INNER JOIN vtiger_vreportdashboard_tabs on vtiger_vreportdashboard_tabs.boardid = vtiger_vreportdashboard_boards.id\n WHERE vtiger_vreportdashboard_boards.id = " . $boardId . ' and (vtiger_vreportdashboard_tabs.userid = ' . $current_user->id . ' or vtiger_vreportdashboard_tabs.userid = 0) ORDER BY sequence ASC';
            } else {
                $sql = "SELECT vtiger_vreportdashboard_tabs.* FROM vtiger_vreportdashboard_boards\n INNER JOIN vtiger_vreportdashboard_tabs on vtiger_vreportdashboard_tabs.boardid = vtiger_vreportdashboard_boards.id\n WHERE vtiger_vreportdashboard_boards.id = " . $boardId . ' ORDER BY sequence ASC';
            }
        }
        $rs = $adb->pquery($sql, []);

        while ($rs && ($row = $adb->fetchByAssoc($rs))) {
            if ($row['userid'] != $current_user->id && $row['userid'] != 0) {
                $row['sharedboard'] = true;
            }
            $row['tabname'] = html_entity_decode(html_entity_decode($row['tabname'], ENT_QUOTES, $default_charset), ENT_QUOTES, $default_charset);
            if ($current_user->is_admin == 'on') {
                $row['is_admin'] = true;
            }
            $result[] = $row;
        }
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($result) {
            $response->setResult($result);
        } else {
            $response->setError(100, 'Board not have Tabs');
        }
        $response->emit();
    }

    public function saveDynamicFilter(Vtiger_Request $request)
    {
        global $adb;
        $dynamicFilterAccount = $request->get('dynamic_filter_accountid') ? $request->get('dynamic_filter_accountid') : null;
        $dynamicFilterAssignedTo = is_numeric($request->get('dynamic_filter_assignedto')) ? $request->get('dynamic_filter_assignedto') : null;
        $dynamicFilterCreatedBy = is_numeric($request->get('dynamic_filter_createdby')) ? $request->get('dynamic_filter_createdby') : null;
        $dashboardId = $request->get('dashboardId');
        $dynamicFilterDate = $request->get('dynamic_filter_date');
        $dynamicFilterValueTypeDate = $request->get('value_type_date');
        $result = $adb->pquery('UPDATE vtiger_vreportdashboard_tabs SET dynamic_filter_account = ?, dynamic_filter_assignedto = ?, dynamic_filter_date=?,dynamic_filter_type_date=? , dynamic_filter_createdby = ? WHERE id = ?', [$dynamicFilterAccount, $dynamicFilterAssignedTo, $dynamicFilterDate, $dynamicFilterValueTypeDate, $dynamicFilterCreatedBy, $dashboardId]);
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        if ($result) {
            $response->setResult($result);
        } else {
            $response->setError(100, 'Failed to save Dynamic Filter');
        }
        $response->emit();
    }

    public function deleteOrgInUrl(Vtiger_Request $request)
    {
        global $site_URL;
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $dashBoardModel = VReports_DashBoard_Model::getInstance('VReports');
        $tabid = $request->get('tabId');
        $orgDynamicFilter = $dashBoardModel->getAccountDynamicFilter($tabid);
        $check = array_pop(explode('&', $_SERVER['HTTP_REFERER']));
        $checkOrg = explode('=', $check);
        $checkOrg = $checkOrg[0];
        $oldOrg = explode('=', $check);
        $oldOrg = $oldOrg[1];
        if ((!$orgDynamicFilter || $orgDynamicFilter != $oldOrg) && $checkOrg == 'organization') {
            $explodeUri = explode('&', $_SERVER['HTTP_REFERER']);
            array_pop($explodeUri);
            $newUrl = implode('&', $explodeUri);
            $response->setResult($newUrl);
        } else {
            $response->setResult('reload');
        }
        $response->emit();
    }
}
