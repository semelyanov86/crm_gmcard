<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Migration_Index_View extends Vtiger_View_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('step1');
        $this->exposeMethod('step2');
        $this->exposeMethod('applyDBChanges');
    }

    public function checkPermission(Vtiger_Request $request)
    {
        parent::checkPermission($request);
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }

        return true;
    }

    public function process(Vtiger_Request $request)
    {
        // Override error reporting to production mode
        // if not set to strict development.
        if (error_reporting() != E_ALL) {
            version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        }
        // Migration could be heavy at-times.
        set_time_limit(0);

        $mode = $request->getMode();
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    protected function step1(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);

        $viewer->assign('MODULENAME', $moduleName);
        $viewer->view('MigrationStep1.tpl', $moduleName);
    }

    protected function step2(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);

        $viewer->assign('MODULE', $moduleName);
        $viewer->view('MigrationStep2.tpl', $moduleName);
    }

    public function preProcess(Vtiger_Request $request, $display = true)
    {
        $viewer = $this->getViewer($request);
        $selectedModule = $request->getModule();
        $viewer->assign('MODULE', $selectedModule);
        parent::preProcess($request, false);
    }

    public function postProcess(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $viewer->view('MigrationPostProcess.tpl', $moduleName);
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = [];
        $cssFileNames = [
            '~/layouts/vlayout/modules/Migration/css/style.css',
            '~/layouts/vlayout/modules/Migration/css/mkCheckbox.css',
            '~/libraries/bootstrap-legacy/css/bootstrap.min.css',
            '~/libraries/bootstrap/css/bootstrap.min.css',
        ];
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);

        return $headerCssInstances;
    }

    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = [];
        $moduleName = $request->getModule();

        $jsFileNames = [
            'modules.Vtiger.resources.Popup',
            'modules.Vtiger.resources.List',
            "modules.{$moduleName}.resources.Index",
        ];

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }

    public function applyDBChanges()
    {
        $migrationModuleModel = Migration_Module_Model::getInstance();

        $getAllowedMigrationVersions = $migrationModuleModel->getAllowedMigrationVersions();
        $getDBVersion = str_replace(['.', ' '], '', $migrationModuleModel->getDBVersion());
        $getLatestSourceVersion = str_replace(['.', ' '], '', $migrationModuleModel->getLatestSourceVersion());
        $migrateVersions = [];
        foreach ($getAllowedMigrationVersions as $getAllowedMigrationVersion) {
            foreach ($getAllowedMigrationVersion as $version => $label) {
                if (strcasecmp($version, $getDBVersion) == 0 || $reach == 1) {
                    $reach = 1;
                    $migrateVersions[] = $version;
                }
            }
        }
        $migrateVersions[] = $getLatestSourceVersion;

        $patchCount  = php7_count($migrateVersions);

        define('VTIGER_UPGRADE', $getDBVersion);

        for ($i = 0; $i < $patchCount; ++$i) {
            $filename =  'modules/Migration/schema/' . $migrateVersions[$i] . '_to_' . $migrateVersions[$i + 1] . '.php';
            if (is_file($filename)) {
                if (!defined('INSTALLATION_MODE')) {
                    echo "<table class='config-table'><tr><th><span><b><font color='red'>" . $migrateVersions[$i] . ' ==> ' . $migrateVersions[$i + 1] . ' Database changes -- Starts. </font></b></span></th></tr></table>';
                    echo "<table class='config-table'>";
                }
                $_i_statesaved = $i;
                include $filename;
                $i = $_i_statesaved;
                if (!defined('INSTALLATION_MODE')) {
                    echo "<table class='config-table'><tr><th><span><b><font color='red'>" . $migrateVersions[$i] . ' ==> ' . $migrateVersions[$i + 1] . ' Database changes -- Ends.</font></b></span></th></tr></table>';
                }
            } elseif (isset($migrateVersions[$patchCount + 1])) {
                echo "<table class='config-table'><tr><th><span><b><font color='red'> There is no Database Changes from " . $migrateVersions[$i] . ' ==> ' . $migrateVersions[$i + 1] . '</font></b></span></th></tr></table>';
            }
        }

        // During migration process we need to upgrade the package changes
        if (defined('VTIGER_UPGRADE')) {

            echo "<table class='config-table'><tr><th><span><b><font color='red'> Upgrading Modules -- Starts. </font></b></span></th></tr></table>";
            echo "<table class='config-table'>";

            // Update existing package modules
            Install_Utils_Model::installModules();

            echo "<table class='config-table'><tr><th><span><b><font color='red'>Upgrading Modules -- Ends.</font></b></span></th></tr></table>";

        }

        // update vtiger version in db
        $migrationModuleModel->updateVtigerVersion();
        // To carry out all the necessary actions after migration
        $migrationModuleModel->postMigrateActivities();
    }

    public static function ExecuteQuery($query, $params)
    {
        $adb = PearDatabase::getInstance();
        $status = $adb->pquery($query, $params);
        if (!defined('INSTALLATION_MODE')) {
            $query = $adb->convert2sql($query, $params);
            if (is_object($status)) {
                echo '<tr><td width="80%"><span>' . $query . '</span></td><td style="color:green">Success</td></tr>';
            } else {
                echo '<tr><td width="80%"><span>' . $query . '</span></td><td style="color:red">Failure</td></tr>';
            }
        }

        return $status;
    }

    public static function insertSelectQuery()
    {
        global $adb;
        $genQueryId = $adb->getUniqueID('vtiger_selectquery');
        if ($genQueryId != '') {
            $iquerysql = 'insert into vtiger_selectquery (QUERYID,STARTINDEX,NUMOFOBJECTS) values (?,?,?)';
            self::ExecuteQuery($iquerysql, [$genQueryId, 0, 0]);
        }

        return $genQueryId;
    }

    public static function insertSelectColumns($queryid, $columnname)
    {
        if ($queryid != '') {
            for ($i = 0; $i < php7_count($columnname); ++$i) {
                $icolumnsql = 'insert into vtiger_selectcolumn (QUERYID,COLUMNINDEX,COLUMNNAME) values (?,?,?)';
                self::ExecuteQuery($icolumnsql, [$queryid, $i, $columnname[$i]]);
            }
        }
    }

    public static function insertReports($queryid, $folderid, $reportname, $description, $reporttype)
    {
        if ($queryid != '') {
            $ireportsql = 'insert into vtiger_report (REPORTID,FOLDERID,REPORTNAME,DESCRIPTION,REPORTTYPE,QUERYID,STATE) values (?,?,?,?,?,?,?)';
            $ireportparams = [$queryid, $folderid, $reportname, $description, $reporttype, $queryid, 'SAVED'];
            self::ExecuteQuery($ireportsql, $ireportparams);
        }
    }

    public static function insertReportModules($queryid, $primarymodule, $secondarymodule)
    {
        if ($queryid != '') {
            $ireportmodulesql = 'insert into vtiger_reportmodules (REPORTMODULESID,PRIMARYMODULE,SECONDARYMODULES) values (?,?,?)';
            self::ExecuteQuery($ireportmodulesql, [$queryid, $primarymodule, $secondarymodule]);
        }
    }

    public static function insertAdvFilter($queryid, $filters)
    {
        if ($queryid != '') {
            $columnIndexArray = [];
            foreach ($filters as $i => $filter) {
                $irelcriteriasql = 'insert into vtiger_relcriteria(QUERYID,COLUMNINDEX,COLUMNNAME,COMPARATOR,VALUE) values (?,?,?,?,?)';
                self::ExecuteQuery($irelcriteriasql, [$queryid, $i, $filter['columnname'], $filter['comparator'], $filter['value']]);
                $columnIndexArray[] = $i;
            }
            $conditionExpression = implode(' and ', $columnIndexArray);
            self::ExecuteQuery('INSERT INTO vtiger_relcriteria_grouping VALUES(?,?,?,?)', [1, $queryid, '', $conditionExpression]);
        }
    }

    /**
     * Function to transform workflow filter of old look in to new look.
     * @param <type> $conditions
     * @return <type>
     */
    public static function transformAdvanceFilterToWorkFlowFilter($conditions)
    {
        $wfCondition = [];

        if (!empty($conditions)) {
            $previousConditionGroupId = 0;
            foreach ($conditions as $condition) {

                $fieldName = $condition['fieldname'];
                $fieldNameContents = explode(' ', $fieldName);
                if (php7_count($fieldNameContents) > 1) {
                    $fieldName = '(' . $fieldName . ')';
                }

                $groupId = $condition['groupid'] ?? null;
                if (!$groupId) {
                    $groupId = 0;
                }

                $groupCondition = 'or';
                if ($groupId === $previousConditionGroupId || php7_count($conditions) === 1) {
                    $groupCondition = 'and';
                }

                $joinCondition = 'or';
                if (isset($condition['joincondition'])) {
                    $joinCondition = $condition['joincondition'];
                } elseif ($groupId === 0) {
                    $joinCondition = 'and';
                }

                $value = $condition['value'];
                switch ($value) {
                    case 'false:boolean': $value = 0;
                        break;
                    case 'true:boolean': $value = 1;
                        break;

                    default: $value;
                        break;
                }

                $wfCondition[] = [
                    'fieldname' => $fieldName,
                    'operation' => $condition['operation'],
                    'value' => $value,
                    'valuetype' => 'rawtext',
                    'joincondition' => $joinCondition,
                    'groupjoin' => $groupCondition,
                    'groupid' => $groupId,
                ];
                $previousConditionGroupId = $groupId;
            }
        }

        return $wfCondition;
    }
}
