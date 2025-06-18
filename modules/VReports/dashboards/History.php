<?php

class VReports_History_Dashboard extends Vtiger_History_Dashboard
{
    public function process(Vtiger_Request $request, $oldWidget = null)
    {
        $LIMIT = 50;
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $viewer = $this->getViewer($request);
        $componentName = $request->get('name');
        $moduleName = $request->getModule();
        $historyType = $request->get('historyType');
        $type = $request->get('type');
        $page = $request->get('page');
        if (empty($page)) {
            $page = 1;
        }
        $linkId = $request->get('linkid');
        if (!$linkId) {
            $linkId = VReports_Widget_Model::getLinkId($moduleName, $componentName);
        }
        $modifiedTime = $request->get('modifiedtime');
        if ($request->get('start') && $request->get('end')) {
            $modifiedTime = [];
            $modifiedTime['start'] = $request->get('start');
            $modifiedTime['end'] = $request->get('end');
        }
        if (!empty($modifiedTime)) {
            $startDate = Vtiger_Date_UIType::getDBInsertedValue($modifiedTime['start']);
            $dates['start'] = getValidDBInsertDateTimeValue($startDate . ' 00:00:00');
            $endDate = Vtiger_Date_UIType::getDBInsertedValue($modifiedTime['end']);
            $dates['end'] = getValidDBInsertDateTimeValue($endDate . ' 23:59:59');
        }
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set('page', $page);
        $pagingModel->set('limit', $LIMIT);
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $widget = VReports_Widget_Model::getHistoryWidget($linkId, $currentUser->getId(), $request->get('widgetid'));
        if (!$widget->get('id')) {
            $widget = $oldWidget;
        }
        if (!$historyType && !$type) {
            $historyType = $widget->get('history_type');
            $type = $widget->get('history_type_radio');
        }
        $group_and_sort = $widget->get('group_and_sort');
        $historyModel = new VReports_History_Model();
        $history = $historyModel->getHistory($pagingModel, $type, $historyType, $dates, $group_and_sort);
        $modCommentsModel = Vtiger_Module_Model::getInstance('ModComments');
        $viewer->assign('CURRENT_USER', $currentUser);
        $viewer->assign('TITLE', 'History');
        $viewer->assign('WIDGET', $widget);
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('HISTORIES', $history);
        $viewer->assign('PAGE', $page);
        $viewer->assign('HISTORY_TYPE', $historyType);
        $viewer->assign('NEXTPAGE', $pagingModel->get('historycount') < $LIMIT ? 0 : $page + 1);
        $viewer->assign('COMMENTS_MODULE_MODEL', $modCommentsModel);
        $userCurrencyInfo = getCurrencySymbolandCRate($currentUser->get('currency_id'));
        $viewer->assign('USER_CURRENCY_SYMBOL', $userCurrencyInfo['symbol']);
        $content = $request->get('content');
        if (!empty($content)) {
            $viewer->view('dashboards/HistoryContents.tpl', $moduleName);
        } else {
            $accessibleUsers = $currentUser->getAccessibleUsers();
            $viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);
            $viewer->assign('HISTORY_TYPE', $widget->get('history_type'));
            $viewer->assign('HISTORY_TYPE_CHECKBOX', $widget->get('history_type_radio'));
            $viewer->assign('GROUP_AND_SORT', $widget->get('group_and_sort'));
            $viewer->view('dashboards/History.tpl', $moduleName);
        }
    }
}
