<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Reports_List_View extends Vtiger_Index_View
{
    protected $listViewHeaders = false;

    protected $listViewEntries = false;

    protected $listViewCount   = false;

    protected $listviewinitcalled = false;

    public function preProcess(Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request, false);

        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);

        $folders = $moduleModel->getFolders();
        $listViewModel = new Reports_ListView_Model();
        $listViewModel->set('module', $moduleModel);
        $linkModels = $listViewModel->getListViewLinks();
        $listViewMassActionModels = $listViewModel->getListViewMassActions();
        $viewer->assign('LISTVIEW_LINKS', $linkModels);
        $viewer->assign('LISTVIEW_MASSACTIONS', $listViewMassActionModels);
        $viewer->assign('FOLDERS', $folders);
        $reportModel = Reports_Record_Model::getCleanInstance();
        $this->initializeListViewContents($request);

        if ($display) {
            $this->preProcessDisplay($request);
        }
    }

    public function preProcessTplName(Vtiger_Request $request)
    {
        return 'ListViewPreProcess.tpl';
    }

    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();

        $this->initializeListViewContents($request);
        $viewer->view('ListViewContents.tpl', $moduleName);
    }

    public function postProcess(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();

        $viewer->view('ListViewPostProcess.tpl', $moduleName);
        parent::postProcess($request);
    }

    /**
     * Function to get the list of Script models to be included.
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();

        $jsFileNames = [
            'modules.Vtiger.resources.Detail',
            "modules.{$moduleName}.resources.Detail",
            'modules.Vtiger.resources.dashboards.Widget',
            'modules.Vtiger.resources.List',
            "modules.{$moduleName}.resources.List",
            "modules.{$moduleName}.resources.ChartDetail",
            'modules.Vtiger.resources.ListSidebar',
            '~layouts/v7/lib/jquery/sadropdown.js',
            '~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/lib/jquery/floatThead/jquery.floatThead.js',
            '~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/lib/jquery/perfect-scrollbar/js/perfect-scrollbar.jquery.js',
            '~/libraries/jquery/gridster/jquery.gridster.min.js',
            '~/libraries/jquery/jqplot/jquery.jqplot.min.js',
            '~/libraries/jquery/jqplot/plugins/jqplot.canvasTextRenderer.min.js',
            '~/libraries/jquery/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js',
            '~/libraries/jquery/jqplot/plugins/jqplot.pieRenderer.min.js',
            '~/libraries/jquery/jqplot/plugins/jqplot.barRenderer.min.js',
            '~/libraries/jquery/jqplot/plugins/jqplot.categoryAxisRenderer.min.js',
            '~/libraries/jquery/jqplot/plugins/jqplot.pointLabels.min.js',
            '~/libraries/jquery/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js',
            '~/libraries/jquery/jqplot/plugins/jqplot.funnelRenderer.min.js',
            '~/libraries/jquery/jqplot/plugins/jqplot.barRenderer.min.js',
            '~/libraries/jquery/jqplot/plugins/jqplot.logAxisRenderer.min.js',
            '~/libraries/jquery/VtJqplotInterface.js',
            '~/libraries/jquery/vtchart.js',
        ];

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    public function initializeListViewContents(Vtiger_Request $request)
    {

        if ($this->listviewinitcalled) {
            return;
        }
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);

        $folderId = $request->get('viewname');
        if (empty($folderId) || $folderId == 'undefined') {
            $folderId = Vtiger_ListView_Model::getSortParamsSession($moduleName . '_folderId');
            if (empty($folderId)) {
                $folderId = 'All';
            }
        } else {
            Vtiger_ListView_Model::setSortParamsSession($moduleName . '_folderId', $folderId);
        }
        $pageNumber = $request->get('page');
        $orderBy = $request->get('orderby');
        $sortOrder = $request->get('sortorder');
        $searchParams = $request->get('search_params');
        $searchParams = $searchParams[0] ?? '';

        $orderParams = Vtiger_ListView_Model::getSortParamsSession($moduleName . '_' . $folderId);
        if ($request->get('mode') == 'removeSorting') {
            Vtiger_ListView_Model::deleteParamsSession($moduleName . '_' . $folderId, ['orderby', 'sortorder']);
            $orderBy = '';
            $sortOrder = '';
        }
        if (empty($orderBy) && empty($pageNumber)) {
            $orderParams = Vtiger_ListView_Model::getSortParamsSession($moduleName . '_' . $folderId);
            if ($orderParams) {
                $pageNumber = $orderParams['page'];
                $orderBy = $orderParams['orderby'];
                $sortOrder = $orderParams['sortorder'];
            }
        } elseif ($request->get('nolistcache') != 1) {
            $params = ['page' => $pageNumber, 'orderby' => $orderBy, 'sortorder' => $sortOrder, 'search_params' => $searchParams];
            Vtiger_ListView_Model::setSortParamsSession($moduleName . '_' . $folderId, $params);
        }

        if ($sortOrder == 'ASC') {
            $nextSortOrder = 'DESC';
            $sortImage = 'icon-chevron-down';
            $faSortImage = 'fa-sort-desc';
        } else {
            $nextSortOrder = 'ASC';
            $sortImage = 'icon-chevron-up';
            $faSortImage = 'fa-sort-asc';
        }

        $listViewModel = new Reports_ListView_Model();
        $listViewModel->set('module', $moduleModel);
        $listViewModel->set('folderid', $folderId);

        if (!empty($orderBy)) {
            $listViewModel->set('orderby', $orderBy);
            $listViewModel->set('sortorder', $sortOrder);
        }
        $listViewMassActionModels = $listViewModel->getListViewMassActions();
        if (empty($pageNumber)) {
            $pageNumber = '1';
        }

        if (empty($searchParams)) {
            $searchParams = [];
        }
        $listViewModel->set('search_params', $searchParams);

        $viewer->assign('MODULE', $moduleName);
        // preProcess is already loading this, we can reuse
        if (!property_exists($this, 'pagingModel') || !$this->pagingModel) {
            $pagingModel = new Vtiger_Paging_Model();
            $pagingModel->set('page', $pageNumber);
        } else {
            $pagingModel = $this->pagingModel;
        }

        $viewer->assign('LISTVIEW_MASSACTIONS', $listViewMassActionModels);

        if (!$this->listViewHeaders) {
            $this->listViewHeaders = $listViewModel->getListViewHeaders($folderId);
        }
        if (!$this->listViewEntries) {
            $this->listViewEntries = $listViewModel->getListViewEntries($pagingModel);
        }
        $noOfEntries = php7_count($this->listViewEntries);
        $viewer->assign('PAGE_NUMBER', $pageNumber);
        $viewer->assign('LISTVIEW_ENTRIES_COUNT', $noOfEntries);
        $viewer->assign('LISTVIEW_HEADERS', $this->listViewHeaders);
        $viewer->assign('LISTVIEW_ENTRIES', $this->listViewEntries);
        $viewer->assign('MODULE_MODEL', $moduleModel);
        $viewer->assign('VIEWNAME', $folderId);

        $viewer->assign('ORDER_BY', $orderBy);
        $viewer->assign('SORT_ORDER', $sortOrder);
        $viewer->assign('NEXT_SORT_ORDER', $nextSortOrder);
        $viewer->assign('SORT_IMAGE', $sortImage);
        $viewer->assign('FASORT_IMAGE', $faSortImage);
        $viewer->assign('COLUMN_NAME', $orderBy);
        $viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('SEARCH_DETAILS', []);
        $viewer->assign('LISTVIEW_MODEL', $listViewModel);
        $viewer->assign('PAGING_MODEL', $pagingModel);
        if (!property_exists($this, 'pagingModel') || !$this->pagingModel) {
            $this->pagingModel = $pagingModel;
        }

        if (!empty($searchParams)) {
            $listSearchParams = [];
            foreach ($searchParams as $conditions) {
                $fieldname = $conditions[0];
                $searchValue = $conditions[2];
                $comparator = $conditions[1];
                $listSearchParams[$fieldname] = ['searchValue' => $searchValue, 'comparator' => $comparator];
            }
            $viewer->assign('SEARCH_DETAILS', $listSearchParams);
        }
        if (PerformancePrefs::getBoolean('LISTVIEW_COMPUTE_PAGE_COUNT', false)) {
            if (!$this->listViewCount) {
                $this->listViewCount = $listViewModel->getListViewCount();
            }
            $totalCount = $this->listViewCount;
            $pageLimit = $pagingModel->getPageLimit();
            $pageCount = ceil((int) $totalCount / (int) $pageLimit);

            if ($pageCount == 0) {
                $pageCount = 1;
            }
            $viewer->assign('PAGE_COUNT', $pageCount);
            $viewer->assign('LISTVIEW_COUNT', $totalCount);
        }
        $dashBoardModel = new Vtiger_DashBoard_Model();
        $activeTabs = $dashBoardModel->getActiveTabs();
        foreach ($activeTabs as $index => $tabInfo) {
            if (!empty($tabInfo['appname'])) {
                unset($activeTabs[$index]);
            }
        }
        $viewer->assign('DASHBOARD_TABS', $activeTabs);

        $this->listviewinitcalled = true;  // to make a early exit if it is called more than once
    }

    /**
     * Function returns the number of records for the current filter.
     */
    public function getRecordsCount(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $cvId = $request->get('viewname');
        $count = $this->getListViewCount($request);

        $result = [];
        $result['module'] = $moduleName;
        $result['viewname'] = $cvId;
        $result['count'] = $count;

        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult($result);
        $response->emit();
    }

    /**
     * Function to get listView count.
     */
    public function getListViewCount(Vtiger_Request $request)
    {
        $folderId = $request->get('viewname');
        if (empty($folderId)) {
            $folderId = 'All';
        }
        $listViewModel = new Reports_ListView_Model();
        $listViewModel->set('folderid', $folderId);
        $searchParams = $request->get('search_params');
        if (!empty($searchParams[0])) {
            $listViewModel->set('search_params', $searchParams[0]);
        }
        $count = $listViewModel->getListViewCount();

        return $count;
    }

    /**
     * Function to get the page count for list.
     * @return total number of pages
     */
    public function getPageCount(Vtiger_Request $request)
    {
        $listViewCount = $this->getListViewCount($request);
        $pagingModel = new Vtiger_Paging_Model();
        $pageLimit = $pagingModel->getPageLimit();
        $pageCount = ceil((int) $listViewCount / (int) $pageLimit);

        if ($pageCount == 0) {
            $pageCount = 1;
        }
        $result = [];
        $result['page'] = $pageCount;
        $result['numberOfRecords'] = $listViewCount;
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = [
            '~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/lib/jquery/perfect-scrollbar/css/perfect-scrollbar.css',
        ];
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);

        return $headerCssInstances;
    }
}
