<?php

/*
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
*/

require_once 'include/logging.php';
require_once 'modules/CustomView/CustomView.php';

class ListViewSession
{
    public $module;

    public $viewname;

    public $start;

    public $sorder;

    public $sortby;

    public $page_view;

    /*initializes ListViewSession
     * Portions created by vtigerCRM are Copyright (C) vtigerCRM.
     * All Rights Reserved.
    */
    public function __construct()
    {
        global $log,$currentModule;
        $log->debug('Entering ListViewSession() method ...');

        $this->module = $currentModule;
        $this->sortby = 'ASC';
        $this->start = 1;
    }

    public function ListViewSession()
    {
        // PHP4-style constructor.
        // This will NOT be invoked, unless a sub-class that extends `foo` calls it.
        // In that case, call the new-style constructor to keep compatibility.
        self::__construct();
    }

    public static function getCurrentPage($currentModule, $viewId)
    {
        if (!empty($_SESSION['lvs'][$currentModule][$viewId]['start'])) {
            return $_SESSION['lvs'][$currentModule][$viewId]['start'];
        }

        return 1;
    }

    public static function getRequestStartPage()
    {
        $start = $_REQUEST['start'];
        if (!is_numeric($start)) {
            $start = 1;
        }
        if ($start < 1) {
            $start = 1;
        }
        $start = ceil($start);

        return $start;
    }

    public static function getListViewNavigation($currentRecordId)
    {
        global $currentModule,$current_user,$adb,$log,$list_max_entries_per_page;
        Zend_Json::$useBuiltinEncoderDecoder = true;
        $reUseData = false;
        $displayBufferRecordCount = 10;
        $bufferRecordCount = 15;
        if ($currentModule == 'Documents') {
            $sql = 'select folderid from vtiger_notes where notesid=?';
            $params = [$currentRecordId];
            $result = $adb->pquery($sql, $params);
            $folderId = $adb->query_result($result, 0, 'folderid');
        }
        $cv = new CustomView();
        $viewId = $cv->getViewId($currentModule);
        $recordNavigationInfo = [];
        $searchKey = [];
        if (!empty($_SESSION[$currentModule . '_DetailView_Navigation' . $viewId])) {
            $recordNavigationInfo = Zend_Json::decode($_SESSION[$currentModule . '_DetailView_Navigation' . $viewId]);
            $pageNumber = 0;
            if (php7_count($recordNavigationInfo) == 1) {
                foreach ($recordNavigationInfo as $recordIdList) {
                    if (in_array($currentRecordId, $recordIdList)) {
                        $reUseData = true;
                    }
                }
            } else {
                $recordList = [];
                $recordPageMapping = [];
                foreach ($recordNavigationInfo as $start => $recordIdList) {
                    foreach ($recordIdList as $index => $recordId) {
                        $recordList[] = $recordId;
                        $recordPageMapping[$recordId] = $start;
                        if ($recordId == $currentRecordId) {
                            $searchKey = php7_count($recordList) - 1;
                            $_REQUEST['start'] = $start;
                        }
                    }
                }
                if ($searchKey > $displayBufferRecordCount - 1 && $searchKey < php7_count($recordList) - $displayBufferRecordCount) {
                    $reUseData = true;
                }
            }
        }

        $list_query = $_SESSION[$currentModule . '_listquery'] ?? '';

        if ($reUseData === false && !empty($list_query)) {
            $recordNavigationInfo = [];
            if (!empty($_REQUEST['start'])) {
                $start = ListViewSession::getRequestStartPage();
            } else {
                $start = ListViewSession::getCurrentPage($currentModule, $viewId);
            }
            $startRecord = (($start - 1) * $list_max_entries_per_page) - $bufferRecordCount;
            if ($startRecord < 0) {
                $startRecord = 0;
            }

            $instance = CRMEntity::getInstance($currentModule);
            $instance->getNonAdminAccessControlQuery($currentModule, $current_user);
            vtlib_setup_modulevars($currentModule, $instance);
            if ($currentModule == 'Documents' && !empty($folderId)) {
                $list_query = preg_replace("/[\n\r\\s]+/", ' ', $list_query);
                $list_query = explode('ORDER BY', $list_query);
                $default_orderby = $list_query[1];
                $list_query = $list_query[0];
                $list_query .= " AND vtiger_notes.folderid={$folderId}";
                $order_by = $instance->getOrderByForFolder($folderId);
                $sorder = $instance->getSortOrderForFolder($folderId);
                $tablename = getTableNameForField($currentModule, $order_by);
                $tablename = (($tablename != '') ? ($tablename . '.') : '');
                if (!empty($order_by)) {
                    $list_query .= ' ORDER BY ' . $tablename . $order_by . ' ' . $sorder;
                } else {
                    $list_query .= ' ORDER BY ' . $default_orderby . '';
                }
            }
            if ($start != 1) {
                $recordCount = ($list_max_entries_per_page * $start + $bufferRecordCount);
            } else {
                $recordCount = ($list_max_entries_per_page + $bufferRecordCount);
            }
            if ($adb->dbType == 'pgsql') {
                $list_query .= " OFFSET {$startRecord} LIMIT {$recordCount}";
            } else {
                $list_query .= " LIMIT {$startRecord}, {$recordCount}";
            }

            $resultAllCRMIDlist_query = $adb->pquery($list_query, []);
            $navigationRecordList = [];

            while ($forAllCRMID = $adb->fetch_array($resultAllCRMIDlist_query)) {
                $navigationRecordList[] = $forAllCRMID[$instance->table_index];
            }

            $pageCount = 0;
            $current = $start;
            if ($start == 1) {
                $firstPageRecordCount = $list_max_entries_per_page;
            } else {
                $firstPageRecordCount = $bufferRecordCount;
                --$current;
            }

            $searchKey = array_search($currentRecordId, $navigationRecordList);
            $recordNavigationInfo = [];
            if ($searchKey !== false) {
                foreach ($navigationRecordList as $index => $recordId) {
                    if (!isset($recordNavigationInfo[$current])) {
                        $recordNavigationInfo[$current] = [];
                    }
                    if ($index == $firstPageRecordCount  || $index == ($firstPageRecordCount + $pageCount * $list_max_entries_per_page)) {
                        ++$current;
                        ++$pageCount;
                    }
                    $recordNavigationInfo[$current][] = $recordId;
                }
            }
            $_SESSION[$currentModule . '_DetailView_Navigation' . $viewId]
                = Zend_Json::encode($recordNavigationInfo);
        }

        return $recordNavigationInfo;
    }

    public static function getRequestCurrentPage($currentModule, $query, $viewid, $queryMode = false)
    {
        global $list_max_entries_per_page, $adb;
        $start = 1;
        if (isset($_REQUEST['query']) && $_REQUEST['query'] == 'true' && $_REQUEST['start'] != 'last') {
            return ListViewSession::getRequestStartPage();
        }
        if (!empty($_REQUEST['start'])) {
            $start = $_REQUEST['start'];
            if ($start == 'last') {
                $count_result = $adb->query(Vtiger_Functions::mkCountQuery($query));
                $noofrows = $adb->query_result($count_result, 0, 'count');
                if ($noofrows > 0) {
                    $start = ceil($noofrows / $list_max_entries_per_page);
                }
            }
            if (!is_numeric($start)) {
                $start = 1;
            } elseif ($start < 1) {
                $start = 1;
            }
            $start = ceil($start);
        } elseif (!empty($_SESSION['lvs'][$currentModule][$viewid]['start'])) {
            $start = $_SESSION['lvs'][$currentModule][$viewid]['start'];
        }
        if (!$queryMode) {
            $_SESSION['lvs'][$currentModule][$viewid]['start'] = intval($start);
        }

        return $start;
    }

    public static function setSessionQuery($currentModule, $query, $viewid)
    {
        if (isset($_SESSION[$currentModule . '_listquery'])) {
            if ($_SESSION[$currentModule . '_listquery'] != $query) {
                unset($_SESSION[$currentModule . '_DetailView_Navigation' . $viewid]);
            }
        }
        $_SESSION[$currentModule . '_listquery'] = $query;
    }

    public static function hasViewChanged($currentModule)
    {
        if (empty($_SESSION['lvs'][$currentModule]['viewname'])) {
            return true;
        }
        if (empty($_REQUEST['viewname'])) {
            return false;
        }
        if ($_REQUEST['viewname'] != $_SESSION['lvs'][$currentModule]['viewname']) {
            return true;
        }

        return false;
    }

    /**
     * Function that sets the module filter in session.
     * @param <String> $module - module name
     * @param <Integer> $viewId - filter id
     */
    public static function setCurrentView($module, $viewId)
    {
        $_SESSION['lvs'][$module]['viewname'] = $viewId;
    }

    /**
     * Function that reads current module filter.
     * @param <String> $module - module name
     * @return <Integer>
     */
    public static function getCurrentView($module)
    {
        if (!empty($_SESSION['lvs'][$module]['viewname'])) {
            return $_SESSION['lvs'][$module]['viewname'];
        }
    }
}
