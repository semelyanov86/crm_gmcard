<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Potentials_GroupedBySalesStage_Dashboard extends Vtiger_IndexAjax_View
{
    /**
     * Retrieves css styles that need to loaded in the page.
     * @param Vtiger_Request $request - request model
     * @return <array> - array of Vtiger_CssScript_Model
     */
    public function getHeaderCss(Vtiger_Request $request)
    {
        $cssFileNames = [
            // Place your widget specific css files here
        ];
        $headerCssScriptInstances = $this->checkAndConvertCssStyles($cssFileNames);

        return $headerCssScriptInstances;
    }

    public function getSearchParams($stage, $assignedto, $dates)
    {
        $listSearchParams = [];
        $conditions = [];
        array_push($conditions, ['sales_stage', 'e', decode_html(urlencode(escapeSlashes($stage)))]);
        if ($assignedto == '') {
            $currenUserModel = Users_Record_Model::getCurrentUserModel();
            $assignedto = $currenUserModel->getId();
        }
        if ($assignedto != 'all') {
            $ownerType = vtws_getOwnerType($assignedto);
            if ($ownerType == 'Users') {
                array_push($conditions, ['assigned_user_id', 'e', decode_html(urlencode(escapeSlashes(getUserFullName($assignedto))))]);
            } else {
                $groupName = getGroupName($assignedto);
                $groupName = $groupName[0];
                array_push($conditions, ['assigned_user_id', 'e', decode_html(urlencode(escapeSlashes($groupName)))]);
            }
        }
        if (!empty($dates)) {
            array_push($conditions, ['closingdate', 'bw', $dates['start'] . ',' . $dates['end']]);
        }
        $listSearchParams[] = $conditions;

        return '&search_params=' . json_encode($listSearchParams);
    }

    public function process(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();

        $linkId = $request->get('linkid');
        $owner = $request->get('owner');
        $dates = $request->get('expectedclosedate');

        // Date conversion from user to database format
        if (!empty($dates)) {
            $dates['start'] = Vtiger_Date_UIType::getDBInsertedValue($dates['start']);
            $dates['end'] = Vtiger_Date_UIType::getDBInsertedValue($dates['end']);
        }

        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $data = $moduleModel->getPotentialsCountBySalesStage($owner, $dates);
        $listViewUrl = $moduleModel->getListViewUrlWithAllFilter();
        for ($i = 0; $i < php7_count($data); ++$i) {
            $data[$i][] = $listViewUrl . $this->getSearchParams($data[$i]['link'], $owner, $dates) . '&nolistcache=1';
        }

        $widget = Vtiger_Widget_Model::getInstance($linkId, $currentUser->getId());

        $viewer->assign('WIDGET', $widget);
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('DATA', $data);

        // Include special script and css needed for this widget
        $viewer->assign('STYLES', $this->getHeaderCss($request));
        $viewer->assign('CURRENTUSER', $currentUser);

        $content = $request->get('content');
        if (!empty($content)) {
            $viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
        } else {
            $viewer->view('dashboards/GroupBySalesStage.tpl', $moduleName);
        }
    }
}
