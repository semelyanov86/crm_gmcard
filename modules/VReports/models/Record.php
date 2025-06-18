<?php

vimport('~~/modules/VReports/VReports.php');
vimport('~~/modules/VReports/ReportRun.php');
require_once 'modules/VReports/ReportUtils.php';
require_once 'VReport.php';

class VReports_Record_Model extends Vtiger_Record_Model
{
    /**
     * Function to get the id of the Report.
     * @return <Number> - Report Id
     */
    public function getId()
    {
        return $this->get('reportid');
    }

    /**
     * Function to set the id of the Report.
     * @param <type> $value - id value
     * @return <Object> - current instance
     */
    public function setId($value)
    {
        return $this->set('reportid', $value);
    }

    /**
     * Fuction to get the Name of the Report.
     * @return <String>
     */
    public function getName()
    {
        return $this->get('reportname');
    }

    /**
     * Function deletes the Report.
     * @return bool
     */
    public function delete()
    {
        global $adb;
        $adb->pquery('DELETE FROM vtiger_links WHERE linkurl LIKE ?', ['%reportid=' . $this->getId() . '%']);

        return $this->getModule()->deleteRecord($this);
    }

    /**
     * Function to existing shared members of a report.
     * @return type
     */
    public function getMembers()
    {
        if ($this->members == false) {
            $this->members = VReports_Member_Model::getAllByGroup($this, VReports_Member_Model::REPORTS_VIEW_MODE);
        }

        return $this->members;
    }

    /**
     * Function to get the detail view url.
     * @return <String>
     */
    public function getDetailViewUrl()
    {
        $module = $this->getModule();
        $reporttype = $this->get('reporttype');
        if ($reporttype == 'chart') {
            $view = 'ChartDetail';
        } else {
            if ($reporttype == 'pivot') {
                $view = 'PivotDetail';
            } else {
                if ($reporttype == 'sql') {
                    if (isset($_REQUEST['excuteLimit'])) {
                        $view = 'SqlReportDetail&excuteLimit=' . $_REQUEST['excuteLimit'];
                    } else {
                        $view = 'SqlReportDetail';
                    }
                } else {
                    $view = $module->getDetailViewName();
                }
            }
        }

        return 'index.php?module=' . $this->getModuleName() . '&view=' . $view . '&record=' . $this->getId();
    }

    /**
     * Function to get the edit view url.
     * @return <String>
     */
    public function getEditViewUrl()
    {
        $module = $this->getModule();
        $reporttype = $this->get('reporttype');
        if ($reporttype == 'chart') {
            $view = 'ChartEdit';
        } else {
            if ($reporttype == 'pivot') {
                $view = 'PivotEdit';
            } else {
                if ($reporttype == 'sql') {
                    $view = 'SqlReportEdit';
                } else {
                    $view = $module->getEditViewName();
                }
            }
        }

        return 'index.php?module=' . $this->getModuleName() . '&view=' . $view . '&record=' . $this->getId();
    }

    /**
     * Funtion to get Duplicate Record Url.
     * @return <String>
     */
    public function getDuplicateRecordUrl()
    {
        $module = $this->getModule();
        $reporttype = $this->get('reporttype');
        if ($reporttype == 'chart') {
            $view = 'ChartEdit';
        } else {
            if ($reporttype == 'pivot') {
                $view = 'PivotEdit';
            } else {
                if ($reporttype == 'sql') {
                    $view = 'SqlReportEdit';
                } else {
                    $view = $module->getEditViewName();
                }
            }
        }

        return 'index.php?module=' . $this->getModuleName() . '&view=' . $view . '&record=' . $this->getId() . '&isDuplicate=true';
    }

    /**
     * Function returns the url that generates Report in Excel format.
     * @return <String>
     */
    public function getReportExcelURL()
    {
        return 'index.php?module=' . $this->getModuleName() . '&view=ExportReport&mode=GetXLS&record=' . $this->getId();
    }

    /**
     * Function returns the url that generates Report in CSV format.
     * @return <String>
     */
    public function getReportCSVURL()
    {
        return 'index.php?module=' . $this->getModuleName() . '&view=ExportReport&mode=GetCSV&record=' . $this->getId();
    }

    /**
     * Function returns the url that generates Report in printable format.
     * @return <String>
     */
    public function getReportPrintURL()
    {
        return 'index.php?module=' . $this->getModuleName() . '&view=ExportReport&mode=GetPrintReport&record=' . $this->getId();
    }

    public function getReportPrintURLV2()
    {
        return 'index.php?module=' . $this->getModuleName() . '&view=ExportReport&mode=GetPrintReportV2&record=' . $this->getId();
    }

    /**
     * Function returns the Reports Model instance.
     * @param <Number> $recordId
     * @param <String> $module
     * @return <Reports_Record_Model>
     */
    public static function getInstanceById($recordId, $module = null)
    {
        $db = PearDatabase::getInstance();
        $self = new self();
        $reportResult = $db->pquery("SELECT vtiger_vreport.*,vtiger_vreport_shareall.is_shareall,vtiger_vreporttype.rename_field,vtiger_vreporttype.rename_field_chart,vtiger_vreporttype.data FROM vtiger_vreport \n\t\t\t\t\t\t\tLEFT JOIN vtiger_vreport_shareall ON vtiger_vreport.reportid = vtiger_vreport_shareall.reportid\n\t\t\t\t\t\t    LEFT JOIN vtiger_vreporttype ON vtiger_vreport.reportid = vtiger_vreporttype.reportid\n                            WHERE vtiger_vreport.reportid = ?", [$recordId]);
        if ($db->num_rows($reportResult)) {
            $values = $db->query_result_rowdata($reportResult, 0);
            $module = Vtiger_Module_Model::getInstance('VReports');
            $self->setData($values)->setId($values['reportid'])->setModuleFromInstance($module);
            $self->initialize();
        }

        return $self;
    }

    /**
     * Function creates Reports_Record_Model.
     * @param <Number> $recordId
     * @return <Reports_Record_Model>
     */
    public static function getCleanInstance($recordId = null)
    {
        if (empty($recordId)) {
            $self = new VReports_Record_Model();
        } else {
            $self = self::getInstanceById($recordId);
        }
        $self->initialize();
        $module = Vtiger_Module_Model::getInstance('VReports');
        $self->setModuleFromInstance($module);

        return $self;
    }

    /**
     * Function initializes Report.
     */
    public function initialize()
    {
        $reportId = $this->getId();
        $this->report = Vtiger_VReport_Model::getInstance($reportId);
    }

    /**
     * Function returns Primary Module of the Report.
     * @return <String>
     */
    public function getPrimaryModule()
    {
        if (!isset($this->report)) {
            $reportId = $this->getId();
            $this->report = Vtiger_VReport_Model::getInstance($reportId);
        }

        return $this->report->primodule;
    }

    /**
     * Function returns Secondary Module of the Report.
     * @return <String>
     */
    public function getSecondaryModules()
    {
        if (!isset($this->report)) {
            $reportId = $this->getId();
            $this->report = Vtiger_VReport_Model::getInstance($reportId);
        }

        return $this->report->secmodule;
    }

    /**
     * Function sets the Primary Module of the Report.
     * @param <String> $module
     */
    public function setPrimaryModule($module)
    {
        if (!isset($this->report)) {
            $reportId = $this->getId();
            $this->report = Vtiger_VReport_Model::getInstance($reportId);
        }
        $this->report->primodule = $module;
    }

    /**
     * Function sets the Secondary Modules for the Report.
     * @param <String> $modules, modules separated with colon(:)
     */
    public function setSecondaryModule($modules)
    {
        if (!isset($this->report)) {
            $reportId = $this->getId();
            $this->report = Vtiger_VReport_Model::getInstance($reportId);
        }
        $this->report->secmodule = $modules;
    }

    /**
     * Function returns Report Type(Summary/Tabular).
     * @return <String>
     */
    public function getReportType()
    {
        $reportType = $this->get('reporttype');
        if (!empty($reportType)) {
            return $reportType;
        }

        return $this->report->reporttype;
    }

    /**
     * Returns the Reports Owner.
     * @return <Number>
     */
    public function getOwner()
    {
        return $this->get('owner');
    }

    /**
     * Function checks if the Report is editable.
     * @return bool
     */
    public function isEditable()
    {
        return $this->report->isEditable();
    }

    /**
     * Function returns Report enabled Modules.
     * @return type
     */
    public function getVReportRelatedModules()
    {
        if (!isset($this->report)) {
            $reportId = $this->getId();
            $this->report = Vtiger_VReport_Model::getInstance($reportId);
        }
        $report = $this->report;

        return $report->related_modules;
    }

    public function getModulesList()
    {
        return $this->report->getModulesList();
    }

    /**
     * Function returns Primary Module Fields.
     * @return <Array>
     */
    public function getPrimaryModuleFields()
    {
        if (!isset($this->report)) {
            $reportId = $this->getId();
            $this->report = Vtiger_VReport_Model::getInstance($reportId);
        }
        $report = $this->report;
        $primaryModule = $this->getPrimaryModule();
        $report->getPriModuleColumnsList($primaryModule);

        return $report->pri_module_columnslist;
    }

    /**
     * Function returns Secondary Module fields.
     * @return <Array>
     */
    public function getSecondaryModuleFields()
    {
        if (!isset($this->report)) {
            $reportId = $this->getId();
            $this->report = Vtiger_VReport_Model::getInstance($reportId);
        }
        $report = $this->report;
        $secondaryModule = $this->getSecondaryModules();
        $primaryModule = $this->getPrimaryModule();
        $arraySecondaryModule = explode(':', $secondaryModule);
        if ($primaryModule == 'VTEItems') {
            array_push($arraySecondaryModule, 'Services');
            array_push($arraySecondaryModule, 'Products');
            $secondaryModule = implode(':', $arraySecondaryModule);
        }
        if (in_array('VTEItems', $arraySecondaryModule)) {
            array_push($arraySecondaryModule, 'Services');
            array_push($arraySecondaryModule, 'Products');
            $secondaryModule = implode(':', $arraySecondaryModule);
        }
        $report->getSecModuleColumnsList($secondaryModule);

        return $report->sec_module_columnslist;
    }

    /**
     * Function checks whether a non admin user is having permission to access record
     * and also function returns the list of shared records for a user, it parameter is true.
     * @return type
     */
    public function isRecordHasViewAccess($reportType)
    {
        $db = PearDatabase::getInstance();
        $current_user = vglobal('current_user');
        $params = [];
        $sql = ' SELECT vtiger_vreport.reportid,vtiger_vreport.reportname FROM vtiger_vreport ';
        require 'user_privileges/user_privileges_' . $current_user->id . '.php';
        require_once 'include/utils/GetUserGroups.php';
        $userGroups = new GetUserGroups();
        $userGroups->getAllUserGroups($current_user->id);
        $user_groups = $userGroups->user_groups;
        if (!empty($user_groups) && $reportType == 'Private') {
            $user_group_query = ' (shareid IN (' . generateQuestionMarks($user_groups) . ") AND setype='groups') OR";
            array_push($params, $user_groups);
        }
        $non_admin_query = ' vtiger_vreport.reportid IN (SELECT reportid FROM vtiger_vreportsharing WHERE ' . $user_group_query . " (shareid=? AND setype='users'))";
        if ($reportType == 'Private') {
            $sql .= ' WHERE ( ( (' . $non_admin_query . ") OR vtiger_vreport.sharingtype='Public' OR " . 'vtiger_vreport.owner = ? OR vtiger_vreport.owner IN (SELECT vtiger_user2role.userid FROM vtiger_user2role INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid ' . "WHERE vtiger_role.parentrole LIKE '" . $current_user_parent_role_seq . "::%'))";
            array_push($params, $current_user->id);
            array_push($params, $current_user->id);
        }
        $queryObj = new stdClass();
        $queryObj->query = $sql;
        $queryObj->queryParams = $params;
        $queryObj = VReports::getVReportSharingQuery($queryObj, $reportType);
        $sql = $queryObj->query . ' AND vtiger_vreport.reportid = ' . $this->getId();
        $params = $queryObj->queryParams;
        $result = $db->pquery($sql, $params);

        return $db->num_rows($result) > 0 ? true : false;
    }

    /**
     * Function returns Report Selected Fields.
     * @return <Array>
     */
    public function getSelectedFields()
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT vtiger_selectcolumn.columnname FROM vtiger_vreport\n\t\t\t\t\tINNER JOIN vtiger_selectquery ON vtiger_selectquery.queryid = vtiger_vreport.queryid\n\t\t\t\t\tINNER JOIN vtiger_selectcolumn ON vtiger_selectcolumn.queryid = vtiger_selectquery.queryid\n\t\t\t\t\tWHERE vtiger_vreport.reportid = ? ORDER BY vtiger_selectcolumn.columnindex", [$this->getId()]);
        $selectedColumns = [];
        $primaryModule = $this->report->primodule;
        for ($i = 0; $i < $db->num_rows($result); ++$i) {
            $column = $db->query_result($result, $i, 'columnname');
            [$tableName, $columnName, $moduleFieldLabel, $fieldName, $type] = explode(':', $column);
            $fieldLabel = explode('_', $moduleFieldLabel);
            $module = $fieldLabel[0];
            $dbFieldLabel = trim(str_replace([$module, '_'], ' ', $moduleFieldLabel));
            $translatedFieldLabel = vtranslate($dbFieldLabel, $module);
            if ($module == 'Calendar') {
                if (CheckFieldPermission($fieldName, $module) == 'true' || CheckFieldPermission($fieldName, 'Events') == 'true') {
                    $selectedColumns[$module . '_' . $translatedFieldLabel] = $column;
                }
            } else {
                if ($primaryModule == 'PriceBooks' && $fieldName == 'listprice' && in_array($module, ['Products', 'Services'])) {
                    $selectedColumns[$module . '_' . $translatedFieldLabel] = $column;
                } else {
                    if (CheckFieldPermission($fieldName, $module) == 'true') {
                        $selectedColumns[$module . '_' . $translatedFieldLabel] = $column;
                    }
                }
            }
        }

        return $selectedColumns;
    }

    /**
     * Function returns Report Calculation Fields.
     * @return type
     */
    public function getSelectedCalculationFields()
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT vtiger_vreportsummary.columnname,vtiger_vreportsummary.column_rename,vtiger_vreportsummary.column_rename_status FROM vtiger_vreportsummary\n\t\t\t\t\tINNER JOIN vtiger_vreport ON vtiger_vreport.reportid = vtiger_vreportsummary.reportsummaryid\n\t\t\t\t\tWHERE vtiger_vreport.reportid=?", [$this->getId()]);
        $columns = [];
        $renameColumns = [];
        $resultData = [];
        for ($i = 0; $i < $db->num_rows($result); ++$i) {
            $columns[$i] = $db->query_result($result, $i, 'columnname');
            $columnsName = explode(':', $columns[$i]);
            $columnsName = $columnsName[2];
            $renameColumns[$columnsName]['rename'] = $db->query_result($result, $i, 'column_rename');
            $renameColumns[$columnsName]['rename_status'] = $db->query_result($result, $i, 'column_rename_status');
        }
        $resultData['columns'] = $columns;
        $resultData['rename_columns'] = $renameColumns;

        return $resultData;
    }

    /**
     * Function returns Report Sort Fields.
     * @return type
     */
    public function getSelectedSortFields()
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT vtiger_vreportsortcol.* FROM vtiger_vreport\n\t\t\t\t\tINNER JOIN vtiger_vreportsortcol ON vtiger_vreport.reportid = vtiger_vreportsortcol.reportid\n\t\t\t\t\tWHERE vtiger_vreport.reportid = ? ORDER BY vtiger_vreportsortcol.sortcolid", [$this->getId()]);
        $sortColumns = [];
        for ($i = 0; $i < $db->num_rows($result); ++$i) {
            $column = $db->query_result($result, $i, 'columnname');
            $order = $db->query_result($result, $i, 'sortorder');
            $sortColumns[decode_html($column)] = $order;
        }

        return $sortColumns;
    }

    /**
     * Function returns Reports Standard Filters.
     * @return type
     */
    public function getSelectedStandardFilter()
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT * FROM vtiger_vreportdatefilter WHERE datefilterid = ? AND startdate != ? AND enddate != ?', [$this->getId(), '0000-00-00', '0000-00-00']);
        $standardFieldInfo = [];
        if ($db->num_rows($result)) {
            $standardFieldInfo['columnname'] = $db->query_result($result, 0, 'datecolumnname');
            $standardFieldInfo['type'] = $db->query_result($result, 0, 'datefilter');
            $standardFieldInfo['startdate'] = $db->query_result($result, 0, 'startdate');
            $standardFieldInfo['enddate'] = $db->query_result($result, 0, 'enddate');
            if ($standardFieldInfo['type'] == 'custom' || $standardFieldInfo['type'] == '') {
                if ($standardFieldInfo['startdate'] != '0000-00-00' && $standardFieldInfo['startdate'] != '') {
                    $startDateTime = new DateTimeField($standardFieldInfo['startdate'] . ' ' . date('H:i:s'));
                    $standardFieldInfo['startdate'] = $startDateTime->getDisplayDate();
                }
                if ($standardFieldInfo['enddate'] != '0000-00-00' && $standardFieldInfo['enddate'] != '') {
                    $endDateTime = new DateTimeField($standardFieldInfo['enddate'] . ' ' . date('H:i:s'));
                    $standardFieldInfo['enddate'] = $endDateTime->getDisplayDate();
                }
            } else {
                $startDateTime = new DateTimeField($standardFieldInfo['startdate'] . ' ' . date('H:i:s'));
                $standardFieldInfo['startdate'] = $startDateTime->getDisplayDate();
                $endDateTime = new DateTimeField($standardFieldInfo['enddate'] . ' ' . date('H:i:s'));
                $standardFieldInfo['enddate'] = $endDateTime->getDisplayDate();
            }
        }

        return $standardFieldInfo;
    }

    /**
     * Function returns Reports Advanced Filters.
     * @return type
     */
    public function getSelectedAdvancedFilter()
    {
        if (!isset($this->report)) {
            $reportId = $this->getId();
            $this->report = Vtiger_VReport_Model::getInstance($reportId);
        }
        $report = $this->report;
        $report->getAdvancedFilterList($this->getId());

        return $report->advft_criteria;
    }

    /**
     * Function saves a Report.
     */
    public function save()
    {
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $reportId = $this->getId();
        $sharingType = 'Public';
        $members = $this->get('members', []);
        if (!empty($members) && !in_array('ShareAll:1', $members) || empty($members)) {
            $sharingType = 'Private';
        }
        $date_var = date('Y-m-d H:i:s');
        if (empty($reportId)) {
            $reportId = $db->getUniqueID('vtiger_selectquery');
            $this->setId($reportId);
            if ($this->get('reporttype') == 'sql') {
                $this->set('description', $_REQUEST['description']);
            }
            $db->pquery('INSERT INTO vtiger_selectquery(queryid, startindex, numofobjects) VALUES(?,?,?)', [$reportId, 0, 0]);
            $reportParams = [$reportId, $this->get('folderid'), $this->get('reportname'), $this->get('description'), $this->get('reporttype', 'tabular'), $reportId, 'CUSTOM', $currentUser->id, $sharingType, $db->formatDate($date_var, true)];
            $db->pquery("INSERT INTO vtiger_vreport(reportid, folderid, reportname, description,\n\t\t\t\t\t\t\t\treporttype, queryid, state, owner, sharingtype, modifiedtime) VALUES(?,?,?,?,?,?,?,?,?,?)", $reportParams);
            $secondaryModule = $this->getSecondaryModules();
            $db->pquery('INSERT INTO vtiger_vreportmodules(reportmodulesid, primarymodule, secondarymodules) VALUES(?,?,?)', [$reportId, $this->getPrimaryModule(), $secondaryModule]);
            $this->saveSelectedFields();
            $this->saveSortFields();
            $this->saveCalculationFields();
            $this->saveStandardFilter();
            $this->saveAdvancedFilters();
            $this->saveReportType();
            $this->saveSharingInformation();
            $this->saveReportToLinks();
        } else {
            $reportId = $this->getId();
            $db->pquery('DELETE FROM vtiger_selectcolumn WHERE queryid = ?', [$reportId]);
            $this->saveSelectedFields();
            $db->pquery('DELETE FROM vtiger_vreportsharing WHERE reportid = ?', [$reportId]);
            $this->saveSharingInformation();
            if ($this->get('reporttype') == 'sql') {
                $this->set('description', $_REQUEST['description']);
            }
            $db->pquery('UPDATE vtiger_vreportmodules SET primarymodule = ?,secondarymodules = ? WHERE reportmodulesid = ?', [$this->getPrimaryModule(), $this->getSecondaryModules(), $reportId]);
            if (empty($_REQUEST['view'])) {
                $db->pquery("UPDATE vtiger_vreport SET reportname = ?, description = ?, reporttype = ?, folderid = ?,sharingtype = ?,modifiedtime = ? WHERE\n\t\t\t\treportid = ?", [decode_html($this->get('reportname')), decode_html($this->get('description')), $this->get('reporttype'), $this->get('folderid'), $sharingType, $db->formatDate($date_var, true), $reportId]);
            }
            $db->pquery('DELETE FROM vtiger_vreportsortcol WHERE reportid = ?', [$reportId]);
            $db->pquery('DELETE FROM vtiger_vreportgroupbycolumn WHERE reportid = ?', [$reportId]);
            $this->saveSortFields();
            $db->pquery('DELETE FROM vtiger_vreportsummary WHERE reportsummaryid = ?', [$reportId]);
            $this->saveCalculationFields();
            $db->pquery('DELETE FROM vtiger_vreportdatefilter WHERE datefilterid = ?', [$reportId]);
            $this->saveStandardFilter();
            $this->saveReportType();
            $this->saveAdvancedFilters();
            $this->saveReportToLinks($reportId);
        }
    }

    /**
     * Function saves Reports Sorting Fields.
     */
    public function saveSortFields()
    {
        $db = PearDatabase::getInstance();
        $sortFields = $this->get('sortFields');
        if (!empty($sortFields)) {
            $i = 0;
            foreach ($sortFields as $fieldInfo) {
                $db->pquery('INSERT INTO vtiger_vreportsortcol(sortcolid, reportid, columnname, sortorder) VALUES (?,?,?,?)', [$i, $this->getId(), $fieldInfo[0], $fieldInfo[1]]);
                if (IsDateFieldVReport($fieldInfo[0])) {
                    if (empty($fieldInfo[2])) {
                        $fieldInfo[2] = 'None';
                    }
                    $db->pquery("INSERT INTO vtiger_vreportgroupbycolumn(reportid, sortid, sortcolname, dategroupbycriteria)\n\t\t\t\t\t\tVALUES(?,?,?,?)", [$this->getId(), $i, $fieldInfo[0], $fieldInfo[2]]);
                }
                ++$i;
            }
        }
    }

    /**
     * Function saves Reports Calculation Fields information.
     */
    public function saveCalculationFields()
    {
        $db = PearDatabase::getInstance();
        $renameFields = $this->get('rename_columns');
        $calculationFields = $this->get('calculationFields');
        $count_calculationFields = 0;
        if (is_array($calculationFields)) {
            $count_calculationFields = count($calculationFields);
        }
        for ($i = 0; $i < $count_calculationFields; ++$i) {
            $renameField = '';
            $renameFieldStatus = false;
            $calculationFieldsExplode = explode(':', $calculationFields[$i]);
            $columnName = $calculationFieldsExplode[2];
            if (is_array($renameFields) && $renameFields[$columnName]) {
                $renameFieldsExplode = explode(':', $renameFields[$columnName]);
                [$renameFieldStatus, $renameField] = $renameFieldsExplode;
            }
            $db->pquery('INSERT INTO vtiger_vreportsummary (reportsummaryid, summarytype, columnname,column_rename,column_rename_status) VALUES (?,?,?,?,?)', [$this->getId(), $i, $calculationFields[$i], $renameField, $renameFieldStatus]);
        }
    }

    /**
     * Function saves Reports Standard Filter information.
     */
    public function saveStandardFilter()
    {
        $db = PearDatabase::getInstance();
        $standardFilter = $this->get('standardFilter');
        if (!empty($standardFilter)) {
            $db->pquery("INSERT INTO vtiger_vreportdatefilter (datefilterid, datecolumnname, datefilter, startdate, enddate)\n\t\t\t\t\t\t\tVALUES (?,?,?,?,?)", [$this->getId(), $standardFilter['field'], $standardFilter['type'], $standardFilter['start'], $standardFilter['end']]);
        }
    }

    /**
     * Function saves Reports Sharing information.
     */
    public function saveSharingInformation()
    {
        if (empty($_REQUEST['view'])) {
            $db = PearDatabase::getInstance();
            $currentUser = Users_Record_Model::getCurrentUserModel();
            $reportId = $this->getId();
            $sharingInfo = $this->get('sharingInfo');
            if (is_array($sharingInfo)) {
                $count_sharingInfo = count($sharingInfo);
            } else {
                $count_sharingInfo = 0;
            }
            for ($i = 0; $i < $count_sharingInfo; ++$i) {
                $db->pquery('INSERT INTO vtiger_vreportsharing(reportid, shareid, setype) VALUES (?,?,?)', [$reportId, $sharingInfo[$i]['id'], $sharingInfo[$i]['type']]);
            }
            $db->pquery('DELETE FROM vtiger_vreport_shareusers WHERE reportid=?', [$reportId]);
            $db->pquery('DELETE FROM vtiger_vreport_sharegroups WHERE reportid=?', [$reportId]);
            $db->pquery('DELETE FROM vtiger_vreport_sharerole WHERE reportid=?', [$reportId]);
            $db->pquery('DELETE FROM vtiger_vreport_sharers WHERE reportid=?', [$reportId]);
            $db->pquery('DELETE FROM vtiger_vreport_shareall WHERE reportid=?', [$reportId]);
            $members = $this->get('members', []);
            if (!empty($members)) {
                $noOfMembers = count($members);
                for ($i = 0; $i < $noOfMembers; ++$i) {
                    $id = $members[$i];
                    $idComponents = Settings_Groups_Member_Model::getIdComponentsFromQualifiedId($id);
                    if ($idComponents && count($idComponents) == 2) {
                        [$memberType, $memberId] = $idComponents;
                        if ($memberType == Settings_Groups_Member_Model::MEMBER_TYPE_USERS) {
                            $db->pquery('INSERT INTO vtiger_vreport_shareusers(userid, reportid) VALUES (?,?)', [$memberId, $reportId]);
                        }
                        if ($memberType == Settings_Groups_Member_Model::MEMBER_TYPE_GROUPS) {
                            $db->pquery('INSERT INTO vtiger_vreport_sharegroups(groupid, reportid) VALUES (?,?)', [$memberId, $reportId]);
                        }
                        if ($memberType == Settings_Groups_Member_Model::MEMBER_TYPE_ROLES) {
                            $db->pquery('INSERT INTO vtiger_vreport_sharerole(roleid, reportid) VALUES (?,?)', [$memberId, $reportId]);
                        }
                        if ($memberType == Settings_Groups_Member_Model::MEMBER_TYPE_ROLE_AND_SUBORDINATES) {
                            $db->pquery('INSERT INTO vtiger_vreport_sharers(rsid, reportid) VALUES (?,?)', [$memberId, $reportId]);
                        }
                        if ($memberType == 'ShareAll') {
                            $db->pquery('INSERT INTO vtiger_vreport_shareall(is_shareall, reportid) VALUES (?,?)', [$memberId, $reportId]);
                        }
                    }
                }
            } else {
                $db->pquery('INSERT INTO vtiger_vreport_shareusers(userid, reportid) VALUES (?,?)', [$currentUser->getId(), $reportId]);
            }
        }
    }

    /**
     * Functions saves Reports selected fields.
     */
    public function saveSelectedFields()
    {
        $db = PearDatabase::getInstance();
        $selectedFields = $this->get('selectedFields');
        if (!empty($selectedFields)) {
            for ($i = 0; $i < count($selectedFields); ++$i) {
                if (!empty($selectedFields[$i])) {
                    $db->pquery('INSERT INTO vtiger_selectcolumn(queryid, columnindex, columnname) VALUES (?,?,?)', [$this->getId(), $i, decode_html($selectedFields[$i])]);
                }
            }
        }
    }

    /**
     * Function saves Reports Filter information.
     */
    public function saveAdvancedFilters()
    {
        $db = PearDatabase::getInstance();
        $reportId = $this->getId();
        $advancedFilters = $this->get('advancedFilter');
        if (!empty($advancedFilters)) {
            $db->pquery('DELETE FROM vtiger_vreport_relcriteria WHERE queryid = ?', [$reportId]);
            $db->pquery('DELETE FROM vtiger_vreport_relcriteria_grouping WHERE queryid = ?', [$reportId]);
            $db->pquery('DELETE FROM vtiger_vreport_relcriteria_grouping_parent WHERE queryid = ?', [$reportId]);
            foreach ($advancedFilters as $groupParentIndex => $advancedFilter) {
                foreach ($advancedFilter as $groupIndex => $groupInfo) {
                    if (empty($groupInfo) || $groupIndex == 'groupParentCondition') {
                        continue;
                    }
                    $groupColumns = $groupInfo['columns'];
                    $groupCondition = $groupInfo['condition'];
                    foreach ($groupColumns as $columnIndex => $columnCondition) {
                        if (empty($columnCondition)) {
                            continue;
                        }
                        $advFilterColumn = $columnCondition['columnname'];
                        $advFilterComparator = $columnCondition['comparator'];
                        $advFilterValue = $columnCondition['value'];
                        $advFilterColumnCondition = $columnCondition['column_condition'];
                        $columnInfo = explode(':', $advFilterColumn);
                        $moduleFieldLabel = $columnInfo[2];
                        [$module, $fieldLabel] = explode('_', $moduleFieldLabel, 2);
                        $fieldInfo = getFieldByVReportLabel($module, $fieldLabel);
                        $fieldType = null;
                        if (!empty($fieldInfo)) {
                            $field = WebserviceField::fromArray($db, $fieldInfo);
                            $fieldType = $field->getFieldDataType();
                        }
                        if ($fieldType == 'currency') {
                            if ($field->getUIType() == '72') {
                                $advFilterValue = Vtiger_Currency_UIType::convertToDBFormat($advFilterValue, null, true);
                            } else {
                                $advFilterValue = Vtiger_Currency_UIType::convertToDBFormat($advFilterValue);
                            }
                        }
                        $specialDateConditions = Vtiger_Functions::getSpecialDateTimeCondtions();
                        $tempVal = explode(',', $advFilterValue);
                        if (($columnInfo[4] == 'D' || $columnInfo[4] == 'T' && $columnInfo[1] != 'time_start' && $columnInfo[1] != 'time_end' || $columnInfo[4] == 'DT') && $columnInfo[4] != '' && $advFilterValue != '' && !in_array($advFilterComparator, $specialDateConditions)) {
                            $val = [];
                            for ($i = 0; $i < count($tempVal); ++$i) {
                                if (trim($tempVal[$i]) != '') {
                                    $date = new DateTimeField(trim($tempVal[$i]));
                                    if ($columnInfo[4] == 'D') {
                                        $val[$i] = DateTimeField::convertToDBFormat(trim($tempVal[$i]));
                                    } else {
                                        if ($columnInfo[4] == 'DT') {
                                            $values = explode(' ', $tempVal[$i]);
                                            $date = new DateTimeField($values[0]);
                                            $val[$i] = $date->getDBInsertDateValue();
                                        } else {
                                            if ($fieldType == 'time') {
                                                $val[$i] = Vtiger_Time_UIType::getTimeValueWithSeconds($tempVal[$i]);
                                            } else {
                                                $val[$i] = $date->getDBInsertTimeValue();
                                            }
                                        }
                                    }
                                }
                            }
                            $advFilterValue = implode(',', $val);
                        }
                        $db->pquery("INSERT INTO vtiger_vreport_relcriteria (queryid, columnindex, columnname, comparator, value,\n\t\t\t\t\t\tgroupid, column_condition,groupparentid) VALUES (?,?,?,?,?,?,?,?)", [$reportId, $columnIndex, $advFilterColumn, $advFilterComparator, $advFilterValue, $groupIndex, $advFilterColumnCondition, $groupParentIndex]);
                        $groupConditionExpression = '';
                        if (!empty($advancedFilter[$groupIndex]['conditionexpression'])) {
                            $groupConditionExpression = $advancedFilter[$groupIndex]['conditionexpression'];
                        }
                        $groupConditionExpression = $groupConditionExpression . ' ' . $columnIndex . ' ' . $advFilterColumnCondition;
                        $advancedFilter[$groupIndex]['conditionexpression'] = $groupConditionExpression;
                    }
                    $groupConditionExpression = $advancedFilter[$groupIndex]['conditionexpression'];
                    if (empty($groupConditionExpression)) {
                        continue;
                    }
                    $db->pquery('INSERT INTO vtiger_vreport_relcriteria_grouping(groupid, queryid, groupparentid, group_condition, condition_expression) VALUES (?,?,?,?,?)', [$groupIndex, $reportId, $groupParentIndex, $groupCondition, $groupConditionExpression]);
                }
                $db->pquery('INSERT INTO vtiger_vreport_relcriteria_grouping_parent(groupparentid,queryid,group_parent_condition) VALUES(?,?,?)', [$groupParentIndex, $reportId, $advancedFilter['groupParentCondition']]);
            }
        }
    }

    /**
     * Function saves Reports Scheduling information.
     */
    public function saveScheduleInformation()
    {
        $db = PearDatabase::getInstance();
        $selectedRecipients = $this->get('selectedRecipients');
        $scheduledInterval = $this->get('scheduledInterval');
        $scheduledFormat = $this->get('scheduledFormat');
        $db->pquery("INSERT INTO vtiger_scheduled_reports(reportid, recipients, schedule, format, next_trigger_time) VALUES\n\t\t\t(?,?,?,?,?)", [$this->getId(), $selectedRecipients, $scheduledInterval, $scheduledFormat, date('Y-m-d H:i:s')]);
    }

    /**
     * Function deletes report scheduling information.
     */
    public function deleteScheduling()
    {
        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_scheduled_reports WHERE reportid = ?', [$this->getId()]);
    }

    /**
     * Function returns sql for the report.
     * @param <String> $advancedFilterSQL
     * @param <String> $format
     * @return <String>
     */
    public function getVReportSQL($advancedFilterSQL = false, $format = false)
    {
        $reportRun = VReportRun::getInstance($this->getId());
        $sql = $reportRun->sGetSQLforReport($this->getId(), $advancedFilterSQL, $format);

        return $sql;
    }

    /**
     * Function returns sql for count query which don't need any fields.
     * @param <String> $query (with all columns)
     * @return <String> $query (by removing all columns)
     */
    public function generateCountQuery($query)
    {
        $from = explode(' from ', $query, 2);
        $fromAndWhereQuery = explode(' order by ', $from[1]);
        $sql = 'SELECT count(*) AS count FROM ' . $fromAndWhereQuery[0];

        return $sql;
    }

    /**
     * Function returns report's data.
     * @param <Vtiger_Paging_Model> $pagingModel
     * @param <String> $filterQuery
     * @return <Array>
     */
    public function getReportData($pagingModel = false, $filterQuery = false)
    {
        $reportRun = VReportRun::getInstance($this->getId());
        $data = $reportRun->GenerateReport('PDF', $filterQuery, true, $pagingModel->getStartIndex(), $pagingModel->getPageLimit());

        return $data;
    }

    public function getVReportsCount($query = null)
    {
        if ($query == null) {
            $query = $this->get('recordCountQuery');
        }
        global $adb;
        $count = 0;
        $result = $adb->query($query, []);
        if ($adb->num_rows($result) > 0) {
            $count = $adb->query_result($result, 0, 'count');
        }

        return $count;
    }

    public function getReportCalulationData($filterQuery = false)
    {
        $reportRun = VReportRun::getInstance($this->getId());
        $dataDetail = $reportRun->GenerateReport('PDF', $filterQuery, true);
        $data = $reportRun->GenerateReport('TOTALXLS', $filterQuery, true);
        $data[] = ['count_record' => $dataDetail['count']];

        return $data;
    }

    /**
     * Function exports reports data into a Excel file.
     */
    public function getReportXLS($type = false)
    {
        $reportRun = VReportRun::getInstance($this->getId());
        $advanceFilterSql = $this->getAdvancedFilterSQL();
        $rootDirectory = vglobal('root_directory');
        $tmpDir = vglobal('tmp_dir');
        $tempFileName = tempnam($rootDirectory . $tmpDir, 'xls');
        $fileName = decode_html($this->getName()) . '.xls';
        if ($this->get('reporttype') == 'pivot') {
            $reportRun->writeReportPivotToExcelFile($tempFileName);
        } else {
            $reportRun->writeReportToExcelFile($tempFileName, $advanceFilterSql);
        }
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        }
        header('Content-Type: application/x-msexcel');
        header('Content-Length: ' . @filesize($tempFileName));
        header('Content-disposition: attachment; filename="' . $fileName . '"');
        $fp = fopen($tempFileName, 'rb');
        fpassthru($fp);
        fclose($fp);
        @unlink($tempFileName);
    }

    /**
     * Function exports reports data into a csv file.
     */
    public function getReportCSV($type = false)
    {
        $reportRun = VReportRun::getInstance($this->getId());
        $advanceFilterSql = $this->getAdvancedFilterSQL();
        $rootDirectory = vglobal('root_directory');
        $tmpDir = vglobal('tmp_dir');
        $tempFileName = tempnam($rootDirectory . $tmpDir, 'csv');
        $reportRun->writeReportToCSVFile($tempFileName, $advanceFilterSql);
        $fileName = decode_html($this->getName()) . '.csv';
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            header('Pragma: public');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        }
        $fileSize = @filesize($tempFileName);
        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header('Content-Length: ' . $fileSize);
        header('Content-disposition: attachment; filename="' . $fileName . '"');
        echo '﻿';
        $fp = fopen($tempFileName, 'rb');
        fpassthru($fp);
        fclose($fp);
        @unlink($tempFileName);
    }

    /**
     * Function returns data in printable format.
     * @return <Array>
     */
    public function getReportPrint()
    {
        $reportRun = VReportRun::getInstance($this->getId());
        $advanceFilterSql = $this->getAdvancedFilterSQL();
        $data = [];
        $data['data'] = $reportRun->GenerateReport('PRINT', $advanceFilterSql);
        $data['total'] = $reportRun->GenerateReport('PRINT_TOTAL', $advanceFilterSql);

        return $data;
    }

    /**
     * Function returns reports is default or not.
     * @return <boolean>
     */
    public function isDefault()
    {
        if ($this->get('state') == 'SAVED') {
            return true;
        }

        return false;
    }

    /**
     * Function move report to another specified folder.
     * @param folderid
     */
    public function move($folderId)
    {
        $db = PearDatabase::getInstance();
        $db->pquery('UPDATE vtiger_vreport SET folderid = ? WHERE reportid = ?', [$folderId, $this->getId()]);
    }

    /**
     * Function to get Calculation fields for Primary module.
     * @return <Array> Primary module calculation fields
     */
    public function getPrimaryModuleCalculationFields()
    {
        $primaryModule = $this->getPrimaryModule();
        $primaryModuleFields = $this->getPrimaryModuleFields();
        $calculationFields = [];
        foreach ($primaryModuleFields[$primaryModule] as $blocks) {
            if (!empty($blocks)) {
                foreach ($blocks as $fieldType => $fieldName) {
                    $fieldDetails = explode(':', $fieldType);
                    if ($fieldName == 'Send Reminder' && $primaryModule == 'Calendar') {
                        continue;
                    }
                    if ($primaryModule == 'ModComments' && ($fieldName == 'Integer' || $fieldName == 'Is Private')) {
                        continue;
                    }
                    if ($fieldDetails[4] === 'I' || $fieldDetails[4] === 'N' || $fieldDetails[4] === 'NN') {
                        $calculationFields[$fieldType] = $fieldName;
                    }
                }
            }
        }
        $primaryModuleCalculationFields[$primaryModule] = $calculationFields;

        return $primaryModuleCalculationFields;
    }

    /**
     * Function to get Calculation fields for Secondary modules.
     * @return <Array> Secondary modules calculation fields
     */
    public function getSecondaryModuleCalculationFields()
    {
        $secondaryModuleCalculationFields = [];
        $secondaryModules = $this->getSecondaryModules();
        if (!empty($secondaryModules)) {
            $secondaryModulesList = explode(':', $secondaryModules);
            $count = count($secondaryModulesList);
            $secondaryModuleFields = $this->getSecondaryModuleFields();
            for ($i = 0; $i < $count; ++$i) {
                $calculationFields = [];
                $secondaryModule = $secondaryModulesList[$i];
                if ($secondaryModuleFields[$secondaryModule]) {
                    foreach ($secondaryModuleFields[$secondaryModule] as $blocks) {
                        if (!empty($blocks)) {
                            foreach ($blocks as $fieldType => $fieldName) {
                                $fieldDetails = explode(':', $fieldType);
                                if ($fieldName == 'Send Reminder' && $secondaryModule == 'Calendar') {
                                    continue;
                                }
                                if ($secondaryModule == 'ModComments' && ($fieldName == 'Integer' || $fieldName == 'Is Private')) {
                                    continue;
                                }
                                if ($fieldDetails[4] === 'I' || $fieldDetails[4] === 'N' || $fieldDetails[4] === 'NN') {
                                    $calculationFields[$fieldType] = $fieldName;
                                }
                            }
                        }
                    }
                }
                $secondaryModuleCalculationFields[$secondaryModule] = $calculationFields;
            }
        }

        return $secondaryModuleCalculationFields;
    }

    /**
     * Function to get Calculation fields for entire Report.
     * @return <Array> report calculation fields
     */
    public function getCalculationFields()
    {
        $primaryModuleCalculationFields = $this->getPrimaryModuleCalculationFields();
        $secondaryModuleCalculationFields = $this->getSecondaryModuleCalculationFields();

        return array_merge($primaryModuleCalculationFields, $secondaryModuleCalculationFields);
    }

    /**
     * Function used to transform the older filter condition to suit newer filters.
     * The newer filters have only two groups one with ALL(AND) condition between each
     * filter and other with ANY(OR) condition, this functions tranforms the older
     * filter with 'AND' condition between filters of a group and will be placed under
     * match ALL conditions group and the rest of it will be placed under match Any group.
     * @return <Array>
     */
    public function transformToNewAdvancedFilter()
    {
        $standardFilter = $this->transformStandardFilter();
        $advancedFilters = $this->getSelectedAdvancedFilter();
        $transformedAdvancedCondition = [];
        foreach ($advancedFilters as $groupParentIndex => $advancedFilter) {
            $allGroupColumns = $anyGroupColumns = [];
            $groupParentCondition = '';
            foreach ($advancedFilter as $index => $group) {
                if ($index == 'groupParentCondition') {
                    $groupParentCondition = $group;

                    continue;
                }
                $columns = $group['columns'];
                $and = $or = 0;
                $block = $group['condition'];
                if (count($columns) != 1) {
                    foreach ($columns as $column) {
                        if ($column['column_condition'] == 'and') {
                            ++$and;
                        } else {
                            ++$or;
                        }
                    }
                    if ($and == count($columns) - 1 && count($columns) != 1) {
                        $allGroupColumns = array_merge($allGroupColumns, $group['columns']);
                    } else {
                        $anyGroupColumns = array_merge($anyGroupColumns, $group['columns']);
                    }
                } else {
                    if ($block == 'and' || $index == 1) {
                        $allGroupColumns = array_merge($allGroupColumns, $group['columns']);
                    } else {
                        $anyGroupColumns = array_merge($anyGroupColumns, $group['columns']);
                    }
                }
            }
            $transformedAdvancedCondition[$groupParentIndex][1] = ['columns' => $allGroupColumns, 'condition' => 'and'];
            $transformedAdvancedCondition[$groupParentIndex][2] = ['columns' => $anyGroupColumns, 'condition' => ''];
            $transformedAdvancedCondition[$groupParentIndex]['groupParentCondition'] = $groupParentCondition;
        }
        if ($standardFilter) {
            $allGroupColumns = array_merge($allGroupColumns, $standardFilter);
        }

        return $transformedAdvancedCondition;
    }

    public function transformStandardFilter()
    {
        $standardFilter = $this->getSelectedStandardFilter();
        if (!empty($standardFilter)) {
            $tranformedStandardFilter = [];
            $tranformedStandardFilter['comparator'] = 'bw';
            $fields = explode(':', $standardFilter['columnname']);
            if ($fields[1] == 'createdtime' || $fields[1] == 'modifiedtime' || $fields[0] == 'vtiger_activity' && $fields[1] == 'date_start') {
                $tranformedStandardFilter['columnname'] = (string) $fields[0] . ':' . $fields[1] . ':' . $fields[3] . ':' . $fields[2] . ':DT';
                $date[] = $standardFilter['startdate'] . ' 00:00:00';
                $date[] = $standardFilter['enddate'] . ' 00:00:00';
                $tranformedStandardFilter['value'] = implode(',', $date);
            } else {
                $tranformedStandardFilter['columnname'] = (string) $fields[0] . ':' . $fields[1] . ':' . $fields[3] . ':' . $fields[2] . ':D';
                $tranformedStandardFilter['value'] = $standardFilter['startdate'] . ',' . $standardFilter['enddate'];
            }

            return [$tranformedStandardFilter];
        }

        return false;
    }

    /**
     * Function returns the Advanced filter SQL.
     * @return <String>
     */
    public function getAdvancedFilterSQL()
    {
        $advancedFilters = $this->get('advancedFilter');
        $advancedFilterCriteria = [];
        $advancedFilterCriteriaGroup = [];
        if (is_array($advancedFilters)) {
            foreach ($advancedFilters as $groupParentIndex => $advancedFilter) {
                foreach ($advancedFilter as $groupIndex => $groupInfo) {
                    if ($groupIndex == 'groupParentCondition') {
                        $advancedFilterCriteria[$groupParentIndex]['groupParentCondition'] = $groupInfo;

                        continue;
                    }
                    $groupColumns = $groupInfo['columns'];
                    $groupCondition = $groupInfo['condition'];
                    if (empty($groupColumns)) {
                        unset($advancedFilterCriteriaGroup[$groupParentIndex][1]['groupcondition']);
                    } else {
                        if (!empty($groupCondition)) {
                            $advancedFilterCriteriaGroup[$groupParentIndex][$groupIndex] = ['groupcondition' => $groupCondition];
                        }
                    }
                    foreach ($groupColumns as $groupColumn) {
                        $groupColumn['groupid'] = $groupIndex;
                        $groupColumn['columncondition'] = $groupColumn['column_condition'];
                        unset($groupColumn['column_condition']);
                        $advancedFilterCriteria[$groupParentIndex][] = $groupColumn;
                    }
                }
            }
        }
        $this->reportRun = VReportRun::getInstance($this->getId());
        $filterQuery = $this->reportRun->RunTimeAdvFilter($advancedFilterCriteria, $advancedFilterCriteriaGroup);

        return $filterQuery;
    }

    /**
     * Function to generate data for advanced filter conditions.
     * @param Vtiger_Paging_Model $pagingModel
     * @return <Array>
     */
    public function generateData($pagingModel = false)
    {
        $filterQuery = $this->getAdvancedFilterSQL();
        if (!$filterQuery) {
            $filterQuery = true;
        }

        return $this->getReportData($pagingModel, $filterQuery);
    }

    /**
     * Function to generate data for advanced filter conditions.
     * @return <Array>
     */
    public function generateCalculationData()
    {
        $filterQuery = $this->getAdvancedFilterSQL();

        return $this->getReportCalulationData($filterQuery);
    }

    /**
     * Function to check duplicate exists or not.
     * @return <boolean>
     */
    public function checkDuplicate()
    {
        $db = PearDatabase::getInstance();
        $query = 'SELECT 1 FROM vtiger_vreport WHERE reportname = ?';
        $params = [$this->getName()];
        $record = $this->getId();
        if ($record && !$this->get('isDuplicate')) {
            $query .= ' AND reportid != ?';
            array_push($params, $record);
        }
        $result = $db->pquery($query, $params);
        if ($db->num_rows($result)) {
            return true;
        }

        return false;
    }

    /**
     * Function is used for Inventory reports, filters should show line items fields only if they are selected in
     * calculation otherwise it should not be shown.
     * @return bool
     */
    public function showLineItemFieldsInFilter($calculationFields = false)
    {
        if ($calculationFields == false) {
            $calculationFields = $this->getSelectedCalculationFields();
        }
        $primaryModule = $this->getPrimaryModule();
        $inventoryModules = ['Invoice', 'Quotes', 'SalesOrder', 'PurchaseOrder'];
        if (!in_array($primaryModule, $inventoryModules)) {
            return false;
        }
        if (!empty($calculationFields)) {
            foreach ($calculationFields as $field) {
                if (stripos($field, 'cb:vtiger_inventoryproductrel') !== false) {
                    return true;
                }
            }

            return false;
        }

        return true;

    }

    public function getScheduledVReport()
    {
        return VReports_ScheduleReports_Model::getInstanceById($this->getId());
    }

    public static function getRecordsListFromRequest(Vtiger_Request $request)
    {
        $folderId = $request->get('viewname');
        $module = $request->get('module');
        $selectedIds = $request->get('selected_ids');
        $excludedIds = $request->get('excluded_ids');
        $searchParams = $request->get('search_params');
        $searchParams = $searchParams[0];
        if (!empty($selectedIds) && $selectedIds != 'all' && !empty($selectedIds) && count($selectedIds) > 0) {
            return $selectedIds;
        }
        $reportFolderModel = VReports_Folder_Model::getInstance();
        $reportFolderModel->set('folderid', $folderId);
        if ($reportFolderModel) {
            return $reportFolderModel->getRecordIds($excludedIds, $module, $searchParams);
        }
    }

    public function getModuleCalculationFieldsForReport()
    {
        $aggregateFunctions = $this->getAggregateFunctions();
        $moduleFields = [];
        $primaryModuleFields = $this->getPrimaryModuleCalculationFields();
        $secondaryModuleFields = $this->getSecondaryModuleCalculationFields();
        $moduleFields = array_merge($primaryModuleFields, $secondaryModuleFields);
        foreach ($moduleFields as $moduleName => $fieldList) {
            $fields = [];
            if (!empty($fieldList)) {
                foreach ($fieldList as $column => $label) {
                    foreach ($aggregateFunctions as $function) {
                        $fLabel = vtranslate($label, $moduleName) . ' (' . vtranslate('LBL_' . $function, 'VReports') . ')';
                        $fColumn = $column . ':' . $function;
                        $fields[$fColumn] = $fLabel;
                    }
                }
            }
            $moduleFields[$moduleName] = $fields;
        }

        return $moduleFields;
    }

    public function getAggregateFunctions()
    {
        $functions = ['SUM', 'AVG', 'MIN', 'MAX'];

        return $functions;
    }

    /**
     * Function to save reprot tyep data.
     */
    public function saveReportType()
    {
        $db = PearDatabase::getInstance();
        if ($this->get('reporttype') == 'sql') {
            $data = $this->get('query');
        } else {
            $data = $this->get('reporttypedata');
        }
        $renameData = $_REQUEST['renamedatavalue'];
        $renameChart = $_REQUEST['renamedatavalue_chart'];
        if ($_REQUEST['record'] != '' && $_REQUEST['isDuplicate'] != '1') {
            if (isset($_REQUEST['step'])) {
                $db->pquery('UPDATE vtiger_vreporttype SET `data` = ? ,`rename_field` = ?,rename_field_chart=? WHERE reportid =?', [$data, $renameData, $renameChart, $this->get('reportid')]);
            } else {
                $db->pquery('UPDATE vtiger_vreporttype SET `data` = ? ,`rename_field` = ?,rename_field_chart=?,sort_by=?,`limit`=?,`order_by`=? WHERE reportid =?', [$data, $renameData, $renameChart, json_encode($this->get('sort_by')), $this->get('limit'), $this->get('order_by'), $this->get('reportid')]);
            }
        } else {
            $db->pquery('DELETE FROM vtiger_vreporttype WHERE reportid = ?', [$this->getId()]);
            $db->pquery('INSERT INTO vtiger_vreporttype(reportid, data,rename_field,rename_field_chart,sort_by,`limit`,order_by) VALUES (?,?,?,?,?,?,?)', [$this->getId(), $data, $renameData, $renameChart, json_encode($this->get('sort_by')), $this->get('limit'), $this->get('order_by')]);
        }
    }

    public function getReportTypeInfo()
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT data FROM vtiger_vreporttype WHERE reportid = ?', [$this->getId()]);
        $dataFields = '';
        if ($db->num_rows($result) > 0) {
            $dataFields = $db->query_result($result, 0, 'data');
        }

        return $dataFields;
    }

    /**
     * Function is used in Charts and Pivots to remove fields like email, phone, descriptions etc
     * as these fields are not generally used for grouping records.
     * @return $fields - array of report field columns
     */
    public function getPrimaryModuleFieldsForAdvancedReporting()
    {
        $fields = $this->getPrimaryModuleFields();
        $primaryModule = $this->getPrimaryModule();
        if ($primaryModule == 'Calendar') {
            $eventModuleModel = Vtiger_Module_Model::getInstance('Events');
            $eventModuleFieldInstances = $eventModuleModel->getFields();
        }
        $primaryModuleModel = Vtiger_Module_Model::getInstance($primaryModule);
        $primaryModuleFieldInstances = $primaryModuleModel->getFields();
        if (is_array($fields)) {
            foreach ($fields as $module => $blocks) {
                if (is_array($blocks)) {
                    foreach ($blocks as $blockLabel => $blockFields) {
                        if (is_array($blockFields)) {
                            foreach ($blockFields as $reportFieldInfo => $fieldLabel) {
                                $fieldInfo = explode(':', $reportFieldInfo);
                                $fieldInstance = $primaryModuleFieldInstances[$fieldInfo[3]];
                                if (!$fieldInstance && $eventModuleFieldInstances) {
                                    $fieldInstance = $eventModuleFieldInstances[$fieldInfo[3]];
                                }
                                if (empty($fieldInstance) || $fieldInfo[0] == 'vtiger_inventoryproductrel' || $fieldInstance->getFieldDataType() == 'email' || $fieldInstance->getFieldDataType() == 'phone' || $fieldInstance->getFieldDataType() == 'image' || $fieldInstance->get('uitype') == '4') {
                                    unset($fields[$module][$blockLabel][$reportFieldInfo]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Function is used in Charts and Pivots to remove fields like email, phone, descriptions etc
     * as these fields are not generally used for grouping records.
     * @return $fields - array of report field columns
     */
    public function getSecondaryModuleFieldsForAdvancedReporting()
    {
        $fields = $this->getSecondaryModuleFields();
        $secondaryModules = $this->getSecondaryModules();
        $secondaryModules = @explode(':', $secondaryModules);
        $primaryModule = $this->getPrimaryModule();
        if ($primaryModule == 'VTEItems' && is_array($secondaryModules)) {
            array_push($secondaryModules, 'Services');
            array_push($secondaryModules, 'Products');
        }
        if (is_array($secondaryModules)) {
            if (in_array('VTEItems', $secondaryModules)) {
                array_push($secondaryModules, 'Services');
                array_push($secondaryModules, 'Products');
            }
        } else {
            if ($secondaryModules == 'VTEItems') {
                array_push($secondaryModules, 'Services');
                array_push($secondaryModules, 'Products');
            }
        }
        if (is_array($secondaryModules)) {
            $secondaryModuleFieldInstances = [];
            foreach ($secondaryModules as $secondaryModule) {
                if (!empty($secondaryModule)) {
                    if ($secondaryModule == 'Calendar') {
                        $eventModuleModel = Vtiger_Module_Model::getInstance('Events');
                        $eventModuleFieldInstances['Events'] = $eventModuleModel->getFields();
                    }
                    $secondaryModuleModel = Vtiger_Module_Model::getInstance($secondaryModule);
                    $secondaryModuleFieldInstances[$secondaryModule] = $secondaryModuleModel->getFields();
                }
            }
        }
        if (is_array($fields)) {
            foreach ($fields as $module => $blocks) {
                if (is_array($blocks)) {
                    foreach ($blocks as $blockLabel => $blockFields) {
                        if (is_array($blockFields)) {
                            foreach ($blockFields as $reportFieldInfo => $fieldLabel) {
                                $fieldInfo = explode(':', $reportFieldInfo);
                                $fieldInstance = $secondaryModuleFieldInstances[$module][$fieldInfo[3]];
                                if (!$fieldInstance && $eventModuleFieldInstances['Events']) {
                                    $fieldInstance = $eventModuleFieldInstances['Events'][$fieldInfo[3]];
                                }
                                if (empty($fieldInstance) || $fieldInfo[0] == 'vtiger_inventoryproductrel' || $fieldInstance->getFieldDataType() == 'email' || $fieldInstance->getFieldDataType() == 'phone' || $fieldInstance->getFieldDataType() == 'image' || $fieldInstance->get('uitype') == '4') {
                                    unset($fields[$module][$blockLabel][$reportFieldInfo]);
                                }
                            }
                        }
                    }
                }
            }
        }

        return $fields;
    }

    public function isInventoryModuleSelected()
    {
        $inventoryModules = getInventoryModules();
        $primaryModule = $this->getPrimaryModule();
        $secondaryModules = explode(':', $this->getSecondaryModules());
        $selectedModules = array_merge([$primaryModule], $secondaryModules);
        foreach ($selectedModules as $module) {
            if (in_array($module, $inventoryModules)) {
                return true;
            }
        }

        return false;
    }

    public function isPinnedToDashboard($tabid)
    {
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $result = $db->pquery('SELECT 1 FROM vtiger_module_vreportdashboard_widgets WHERE reportid = ? AND userid = ? AND dashboardtabid = ?', [$this->getId(), $currentUser->getId(), $tabid]);
        if ($db->num_rows($result)) {
            return true;
        }

        return false;
    }

    public function isEditableBySharing()
    {
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $ownerResult = $db->pquery('SELECT `owner` FROM vtiger_vreport WHERE reportid = ?', [$this->getId()]);
        $reportOnwer = $db->query_result($ownerResult, 0, 'owner');
        $reportId = $this->getId();
        if (!$reportId) {
            return true;
        }
        if ($currentUser->getId() == $reportOnwer || strpos($this->get('sharingtype'), 'Public') !== false) {
            if ($currentUser->getId() != $reportOnwer) {
                return false;
            }

            return true;
        }
        if ($currentUser->getId() != $reportOnwer) {
            return false;
        }
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

    public function saveReportToLinks($reportId = null)
    {
        global $adb;
        $data = $this->getData();
        $tabId = $this->getModule()->id;
        $label = textlength_check($data['reportname']);
        if ($reportId) {
            $adb->pquery('UPDATE vtiger_links SET linklabel = ? WHERE tabid = ? AND linkurl LIKE ?', [$label, $tabId, '%reportid=' . $reportId . '%']);
        } else {
            $type = 'DASHBOARDWIDGET';
            $reporttype = $data['reporttype'];
            $reportId = $data['reportid'];
            $linkid = $adb->getUniqueID('vtiger_links') + 1;
            if ($reporttype == 'chart') {
                $url = 'index.php?module=VReports&action=ChartActions&mode=pinToDashboard&reportid=' . $reportId . '&linkid=' . $linkid;
            } else {
                if ($reporttype == 'tabular') {
                    $url = 'index.php?module=VReports&action=TabularActions&mode=pinToDashboard&reportid=' . $reportId . '&linkid=' . $linkid;
                } else {
                    if ($reporttype == 'sql') {
                        $url = 'index.php?module=VReports&action=SqlReportActions&mode=pinToDashboard&reportid=' . $reportId . '&linkid=' . $linkid;
                    } else {
                        $url = 'index.php?module=VReports&action=PivotActions&mode=pinToDashboard&reportid=' . $reportId . '&linkid=' . $linkid;
                    }
                }
            }
            Vtiger_Link::addLink($tabId, $type, $label, $url);
        }
    }

    public static function getLinkId($recordId)
    {
        global $adb;
        $linkid = 0;
        $result = $adb->pquery("SELECT linkid FROM vtiger_links WHERE tabid =? AND linkurl LIKE '%reportid=" . $recordId . "%'", [getTabid('VReports')]);
        if ($adb->num_rows($result)) {
            $linkid = $adb->query_result($result, 0, 'linkid');
        }

        return $linkid;
    }
}
