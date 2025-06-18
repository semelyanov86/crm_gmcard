<?php

global $calpath;
global $app_strings;
global $mod_strings;
global $theme;
global $log;
$theme_path = 'themes/' . $theme . '/';
$image_path = $theme_path . 'images/';
require_once 'include/database/PearDatabase.php';
require_once 'data/CRMEntity.php';
require_once 'modules/VReports/VReports.php';
require_once 'modules/VReports/ReportUtils.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'modules/Vtiger/helpers/Util.php';
require_once 'include/RelatedListView.php';

class VReportRun extends CRMEntity
{
    public static $HTMLVIEW_MAX_ROWS = 1_000;

    public $reportid;

    public $primarymodule;

    public $secondarymodule;

    public $orderbylistsql;

    public $orderbylistcolumns;

    public $selectcolumns;

    public $groupbylist;

    public $reporttype;

    public $reportname;

    public $totallist;

    public $_groupinglist = false;

    public $_groupbycondition = false;

    public $_reportquery = false;

    public $_tmptablesinitialized = false;

    public $_columnslist = false;

    public $_stdfilterlist = false;

    public $_columnstotallist = false;

    public $_advfiltersql = false;

    public $append_currency_symbol_to_value = ['Products_Unit_Price', 'Services_Price', 'Invoice_Total', 'Invoice_Sub_Total', 'Invoice_Pre_Tax_Total', 'Invoice_S&H_Amount', 'Invoice_Discount_Amount', 'Invoice_Adjustment', 'Quotes_Total', 'Quotes_Sub_Total', 'Quotes_Pre_Tax_Total', 'Quotes_S&H_Amount', 'Quotes_Discount_Amount', 'Quotes_Adjustment', 'SalesOrder_Total', 'SalesOrder_Sub_Total', 'SalesOrder_Pre_Tax_Total', 'SalesOrder_S&H_Amount', 'SalesOrder_Discount_Amount', 'SalesOrder_Adjustment', 'PurchaseOrder_Total', 'PurchaseOrder_Sub_Total', 'PurchaseOrder_Pre_Tax_Total', 'PurchaseOrder_S&H_Amount', 'PurchaseOrder_Discount_Amount', 'PurchaseOrder_Adjustment', 'Invoice_Received', 'PurchaseOrder_Paid', 'Invoice_Balance', 'PurchaseOrder_Balance'];

    public $ui10_fields = [];

    public $ui101_fields = [];

    public $ui72_fields = [];

    public $groupByTimeParent = ['Quarter' => ['Year'], 'Month' => ['Year']];

    public $queryPlanner;

    protected static $instances = false;

    public $lineItemFieldsInCalculation = false;

    public $orderByMonthWeekDate = [];

    /** Function to set reportid,primarymodule,secondarymodule,reporttype,reportname, for given reportid
     *  This function accepts the $reportid as argument
     *  It sets reportid,primarymodule,secondarymodule,reporttype,reportname for the given reportid
     *  To ensure single-instance is present for $reportid
     *  as we optimize using ReportRunPlanner and setup temporary tables.
     */
    public function __construct($reportid)
    {
        $oReport = new VReports($reportid);
        $this->reportid = $reportid;
        $this->primarymodule = $oReport->primodule;
        $this->secondarymodule = $oReport->secmodule;
        $this->reporttype = $oReport->reporttype;
        $this->reportname = $oReport->reportname;
        $this->queryPlanner = new VReportRunQueryPlanner();
        $this->queryPlanner->reportRun = $this;
    }

    public static function getInstance($reportid)
    {
        if (!isset(self::$instances[$reportid])) {
            self::$instances[$reportid] = new VReportRun($reportid);
        }

        return self::$instances[$reportid];
    }

    /** Function to get the columns for the reportid
     *  This function accepts the $reportid and $outputformat (optional)
     *  This function returns  $columnslist Array($tablename:$columnname:$fieldlabel:$fieldname:$typeofdata=>$tablename.$columnname As Header value,
     * 					      $tablename1:$columnname1:$fieldlabel1:$fieldname1:$typeofdata1=>$tablename1.$columnname1 As Header value,
     * 					      					|
     * 					      $tablenamen:$columnnamen:$fieldlabeln:$fieldnamen:$typeofdatan=>$tablenamen.$columnnamen As Header value
     * 				      	     ).
     */
    public function getQueryColumnsList($reportid, $outputformat = '')
    {
        if ($this->_columnslist !== false) {
            return $this->_columnslist;
        }
        global $adb;
        global $modules;
        global $log;
        global $current_user;
        global $current_language;
        $ssql = 'select vtiger_selectcolumn.* from vtiger_vreport inner join vtiger_selectquery on vtiger_selectquery.queryid = vtiger_vreport.queryid';
        $ssql .= ' left join vtiger_selectcolumn on vtiger_selectcolumn.queryid = vtiger_selectquery.queryid';
        $ssql .= ' where vtiger_vreport.reportid = ?';
        $ssql .= ' order by vtiger_selectcolumn.columnindex';
        $result = $adb->pquery($ssql, [$reportid]);
        $permitted_fields = [];
        $selectedModuleFields = [];
        require 'user_privileges/user_privileges_' . $current_user->id . '.php';

        while ($columnslistrow = $adb->fetch_array($result)) {
            $fieldname = '';
            $fieldcolname = $columnslistrow['columnname'];
            [$tablename, $colname, $module_field, $fieldname, $single] = explode(':', $fieldcolname);
            [$module, $field] = explode('_', $module_field, 2);
            $selectedModuleFields[$module][] = $fieldname;
            $inventory_fields = ['serviceid'];
            $inventory_modules = getInventoryModules();
            $sizeof_permitted_fields_module = 0;
            if (is_array($permitted_fields[$module])) {
                $sizeof_permitted_fields_module = sizeof($permitted_fields[$module]);
            }
            if ($sizeof_permitted_fields_module == 0 && $is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1) {
                $permitted_fields[$module] = $this->getaccesfield($module);
            }
            if (in_array($module, $inventory_modules) && !empty($permitted_fields)) {
                foreach ($inventory_fields as $value) {
                    array_push($permitted_fields[$module], $value);
                }
            }
            $selectedfields = explode(':', $fieldcolname);
            if ($is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1 && !in_array($selectedfields[3], $permitted_fields[$module])) {
                continue;
            }
            $querycolumns = $this->getEscapedColumns($selectedfields);
            if (isset($module) && $module != '') {
                $mod_strings = return_module_language($current_language, $module);
            }
            $targetTableName = $tablename;
            $fieldlabel = trim(preg_replace('/' . $module . '/', ' ', $selectedfields[2], 1));
            $mod_arr = explode('_', $fieldlabel);
            $fieldlabel = trim(str_replace('_', ' ', $fieldlabel));
            $fld_arr = explode(' ', $fieldlabel);
            if ($mod_arr[0] == '') {
                $mod = $module;
                $mod_lbl = getTranslatedString($module, $module);
            } else {
                $mod = $mod_arr[0];
                array_shift($fld_arr);
                $mod_lbl = getTranslatedString($fld_arr[0], $mod);
            }
            $fld_lbl_str = implode(' ', $fld_arr);
            $fld_lbl = getTranslatedString($fld_lbl_str, $module);
            $fieldlabel = $mod_lbl . ' ' . $fld_lbl;
            if ($selectedfields[0] == 'vtiger_usersRel1' && $selectedfields[1] == 'user_name' && $selectedfields[2] == 'Quotes_Inventory_Manager') {
                $concatSql = getSqlForNameInDisplayFormat(['first_name' => $selectedfields[0] . '.first_name', 'last_name' => $selectedfields[0] . '.last_name'], 'Users');
                $columnslist[$fieldcolname] = 'trim( ' . $concatSql . ' ) as ' . $module . '_Inventory_Manager';
                $this->queryPlanner->addTable($selectedfields[0]);

                continue;
            }
            if (CheckFieldPermission($fieldname, $mod) != 'true' && $colname != 'crmid' && !in_array($fieldname, $inventory_fields) && in_array($module, $inventory_modules) || empty($fieldname)) {
                continue;
            }
            $this->labelMapping[$selectedfields[2]] = str_replace(' ', '_', $fieldlabel);
            if ($querycolumns == '') {
                $columnslist[$fieldcolname] = $this->getColumnSQL($selectedfields);
            } else {
                $columnslist[$fieldcolname] = $querycolumns;
            }
            $this->queryPlanner->addTable($targetTableName);
        }
        if ($outputformat == 'HTML' || $outputformat == 'PDF' || $outputformat == 'PRINT') {
            if ($this->primarymodule == 'ModComments') {
                $columnslist['vtiger_modcomments:related_to:ModComments_Related_To_Id:related_to:V'] = "vtiger_modcomments.related_to AS '" . $this->primarymodule . "_LBL_ACTION'";
            } else {
                $columnslist['vtiger_crmentity:crmid:LBL_ACTION:crmid:I'] = 'vtiger_crmentity.crmid AS "' . $this->primarymodule . '_LBL_ACTION"';
            }
            if ($this->secondarymodule) {
                $secondaryModules = explode(':', $this->secondarymodule);
                foreach ($secondaryModules as $secondaryModule) {
                    $columnsSelected = (array) $selectedModuleFields[$secondaryModule];
                    $moduleModel = Vtiger_Module_Model::getInstance($secondaryModule);
                    $moduleFields = $moduleModel->getFields();
                    $moduleFieldNames = array_keys($moduleFields);
                    $commonFields = array_intersect($moduleFieldNames, $columnsSelected);
                    if (count($commonFields) > 0) {
                        $baseTable = $moduleModel->get('basetable');
                        $this->queryPlanner->addTable($baseTable);
                        if ($secondaryModule == 'Emails') {
                            $baseTable .= 'Emails';
                        }
                        $baseTableId = $moduleModel->get('basetableid');
                        $columnslist[$baseTable . ':' . $baseTableId . ':' . $secondaryModule . ':' . $baseTableId . ':I'] = $baseTable . '.' . $baseTableId . ' AS ' . $secondaryModule . '_LBL_ACTION';
                    }
                }
            }
        }
        $this->_columnslist = $columnslist;
        $log->info('ReportRun :: Successfully returned getQueryColumnsList' . $reportid);

        return $columnslist;
    }

    public function getColumnSQL($selectedfields)
    {
        global $adb;
        $selectedfields[2] = addslashes($selectedfields[2]);
        $header_label = $selectedfields[2];
        [$module, $field] = explode('_', $selectedfields[2]);
        $concatSql = getSqlForNameInDisplayFormat(['first_name' => $selectedfields[0] . '.first_name', 'last_name' => $selectedfields[0] . '.last_name'], 'Users');
        $emailTableName = 'vtiger_activity';
        if ($module != $this->primarymodule) {
            $emailTableName .= 'Emails';
        }
        if ($selectedfields[0] == 'vtiger_inventoryproductrel') {
            if ($selectedfields[1] == 'discount_amount') {
                $columnSQL = 'CASE WHEN (vtiger_inventoryproductreltmp' . $module . ".discount_amount != '') THEN vtiger_inventoryproductreltmp" . $module . '.discount_amount ELSE ROUND((vtiger_inventoryproductreltmp' . $module . '.listprice * vtiger_inventoryproductreltmp' . $module . '.quantity * (vtiger_inventoryproductreltmp' . $module . ".discount_percent/100)),3) END AS '" . decode_html($header_label) . "'";
                $this->queryPlanner->addTable($selectedfields[0] . 'tmp' . $module);
            } else {
                if ($selectedfields[1] == 'productid') {
                    $columnSQL = 'CASE WHEN (vtiger_products' . $module . ".productname NOT LIKE '') THEN vtiger_products" . $module . '.productname ELSE vtiger_service' . $module . ".servicename END AS '" . decode_html($header_label) . "'";
                    $this->queryPlanner->addTable('vtiger_products' . $module);
                    $this->queryPlanner->addTable('vtiger_service' . $module);
                } else {
                    if ($selectedfields[1] == 'listprice') {
                        $moduleInstance = CRMEntity::getInstance($module);
                        $fieldName = $selectedfields[0] . 'tmp' . $module . '.' . $selectedfields[1];
                        $columnSQL = 'CASE WHEN vtiger_currency_info' . $module . '.id = vtiger_users' . $module . '.currency_id THEN ' . $fieldName . '/vtiger_currency_info' . $module . '.conversion_rate ELSE ' . $fieldName . '/' . $moduleInstance->table_name . ".conversion_rate END AS '" . decode_html($header_label) . "'";
                        $this->queryPlanner->addTable($selectedfields[0] . 'tmp' . $module);
                        $this->queryPlanner->addTable('vtiger_currency_info' . $module);
                        $this->queryPlanner->addTable('vtiger_users' . $module);
                    } else {
                        if (in_array($this->primarymodule, ['Products', 'Services'])) {
                            $columnSQL = $selectedfields[0] . 'tmp' . $module . '.' . $selectedfields[1] . " AS '" . decode_html($header_label) . "'";
                            $this->queryPlanner->addTable($selectedfields[0] . $module);
                        } else {
                            if ($selectedfields[0] == 'vtiger_inventoryproductrel') {
                                $selectedfields[0] = $selectedfields[0] . 'tmp';
                            }
                            $columnSQL = $selectedfields[0] . $module . '.' . $selectedfields[1] . " AS '" . decode_html($header_label) . "'";
                            $this->queryPlanner->addTable($selectedfields[0] . $module);
                        }
                    }
                }
            }
        } else {
            if ($selectedfields[0] == 'vtiger_pricebookproductrel') {
                if ($selectedfields[1] == 'listprice') {
                    $listPriceFieldName = $selectedfields[0] . 'tmp' . $module . '.' . $selectedfields[1];
                    $currencyPriceFieldName = $selectedfields[0] . 'tmp' . $module . '.usedcurrency';
                    $columnSQL = 'CONCAT(' . $currencyPriceFieldName . ",'::'," . $listPriceFieldName . ')' . " AS '" . decode_html($header_label) . "'";
                    $this->queryPlanner->addTable($selectedfields[0] . 'tmp' . $module);
                }
            } else {
                if ($selectedfields[4] == 'C') {
                    $field_label_data = explode('_', $selectedfields[2]);
                    $module = $field_label_data[0];
                    if ($module != $this->primarymodule) {
                        $columnSQL = 'case when (' . $selectedfields[0] . '.' . $selectedfields[1] . "='1')then 'yes' else case when (vtiger_crmentity" . $module . ".crmid !='') then 'no' else '-' end end AS '" . decode_html($selectedfields[2]) . "'";
                        $this->queryPlanner->addTable('vtiger_crmentity' . $module);
                    } else {
                        $columnSQL = 'case when (' . $selectedfields[0] . '.' . $selectedfields[1] . "='1')then 'yes' else case when (vtiger_crmentity.crmid !='') then 'no' else '-' end end AS '" . decode_html($selectedfields[2]) . "'";
                        $this->queryPlanner->addTable($selectedfields[0]);
                    }
                } else {
                    if ($selectedfields[4] == 'D' || $selectedfields[4] == 'DT') {
                        if ($selectedfields[5] == 'Y') {
                            if ($selectedfields[0] == 'vtiger_activity' && $selectedfields[1] == 'date_start') {
                                if ($module == 'Emails') {
                                    $columnSQL = 'YEAR(cast(concat(' . $emailTableName . ".date_start,'  '," . $emailTableName . '.time_start) as DATE)) AS Emails_Date_Sent_Year';
                                } else {
                                    $columnSQL = "YEAR(cast(concat(vtiger_activity.date_start,'  ',vtiger_activity.time_start) as DATETIME)) AS Calendar_Start_Date_and_Time_Year";
                                }
                            } else {
                                if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                    $columnSQL = 'YEAR(vtiger_crmentity.' . $selectedfields[1] . ") AS '" . decode_html($header_label) . "_Year'";
                                } else {
                                    $columnSQL = 'YEAR(' . $selectedfields[0] . '.' . $selectedfields[1] . ") AS '" . decode_html($header_label) . "_Year'";
                                }
                            }
                            $this->queryPlanner->addTable($selectedfields[0]);
                        } else {
                            if ($selectedfields[5] == 'M') {
                                if ($selectedfields[0] == 'vtiger_activity' && $selectedfields[1] == 'date_start') {
                                    if ($module == 'Emails') {
                                        $columnSQL = 'MONTHNAME(cast(concat(' . $emailTableName . ".date_start,'  '," . $emailTableName . '.time_start) as DATE)) AS Emails_Date_Sent_Month';
                                    } else {
                                        $columnSQL = "MONTHNAME(cast(concat(vtiger_activity.date_start,'  ',vtiger_activity.time_start) as DATETIME)) AS Calendar_Start_Date_and_Time_Month";
                                    }
                                } else {
                                    if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                        $columnSQL = 'MONTHNAME(vtiger_crmentity.' . $selectedfields[1] . ") AS '" . decode_html($header_label) . "_Month'";
                                    } else {
                                        $columnSQL = 'MONTHNAME(' . $selectedfields[0] . '.' . $selectedfields[1] . ") AS '" . decode_html($header_label) . "_Month'";
                                    }
                                }
                                $this->queryPlanner->addTable($selectedfields[0]);
                            } else {
                                if ($selectedfields[5] == 'W') {
                                    if ($selectedfields[0] == 'vtiger_activity' && $selectedfields[1] == 'date_start') {
                                        if ($module == 'Emails') {
                                            $columnSQL = "CONCAT('Week ',WEEK(cast(concat(" . $emailTableName . ".date_start,'  '," . $emailTableName . '.time_start) as DATE), 1)) AS Emails_Date_Sent_Week';
                                        } else {
                                            $columnSQL = "CONCAT('Week ',WEEK(cast(concat(vtiger_activity.date_start,'  ',vtiger_activity.time_start) as DATETIME), 1)) AS Calendar_Start_Date_and_Time_Week";
                                        }
                                    } else {
                                        if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                            $columnSQL = "CONCAT('Week ',WEEK(vtiger_crmentity." . $selectedfields[1] . ", 1)) AS '" . decode_html($header_label) . "_Week'";
                                            $fieldOrder = 'vtiger_crmentity.' . $selectedfields[1];
                                        } else {
                                            $columnSQL = "CONCAT('Week ',WEEK(" . $selectedfields[0] . '.' . $selectedfields[1] . ", 1)) AS '" . decode_html($header_label) . "_Week'";
                                            $fieldOrder = $selectedfields[0] . '.' . $selectedfields[1];
                                        }
                                    }
                                    $this->orderByMonthWeekDate[] = $fieldOrder . ' ASC';
                                    $this->queryPlanner->addTable($selectedfields[0]);
                                } else {
                                    if ($selectedfields[5] == 'MY') {
                                        if ($selectedfields[0] == 'vtiger_activity' && $selectedfields[1] == 'date_start') {
                                            if ($module == 'Emails') {
                                                $columnSQL = 'date_format(cast(concat(' . $emailTableName . ".date_start,'  '," . $emailTableName . ".time_start) as DATE), '%M %Y') AS Emails_Date_Sent_Month";
                                            } else {
                                                $columnSQL = "date_format(cast(concat(vtiger_activity.date_start,'  ',vtiger_activity.time_start) as DATETIME), '%M %Y') AS Calendar_Start_Date_and_Time_Month";
                                            }
                                        } else {
                                            if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                                $columnSQL = 'date_format(vtiger_crmentity.' . $selectedfields[1] . ", '%M %Y') AS '" . decode_html($header_label) . "_Month'";
                                                $fieldOrder = 'vtiger_crmentity.' . $selectedfields[1];
                                            } else {
                                                $columnSQL = 'date_format(' . $selectedfields[0] . '.' . $selectedfields[1] . ", '%M %Y') AS '" . decode_html($header_label) . "_Month'";
                                                $fieldOrder = $selectedfields[0] . '.' . $selectedfields[1];
                                            }
                                        }
                                        $this->orderByMonthWeekDate[] = $fieldOrder . ' ASC';
                                        $this->queryPlanner->addTable($selectedfields[0]);
                                    } else {
                                        if ($selectedfields[5] == 'D') {
                                            if ($selectedfields[0] == 'vtiger_activity' && $selectedfields[1] == 'date_start') {
                                                if ($module == 'Emails') {
                                                    $columnSQL = 'date_format(cast(concat(' . $emailTableName . ".date_start,'  '," . $emailTableName . ".time_start) as DATE), '%d %M %Y') AS Emails_Date_Sent_Day";
                                                } else {
                                                    $columnSQL = "date_format(cast(concat(vtiger_activity.date_start,'  ',vtiger_activity.time_start) as DATETIME), '%d %M %Y') AS Calendar_Start_Date_and_Time_Day";
                                                }
                                            } else {
                                                if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                                    $columnSQL = 'date_format(vtiger_crmentity.' . $selectedfields[1] . ", '%d %M %Y') AS '" . decode_html($header_label) . "_Day'";
                                                    $fieldOrder = 'vtiger_crmentity.' . $selectedfields[1];
                                                } else {
                                                    $columnSQL = 'date_format(' . $selectedfields[0] . '.' . $selectedfields[1] . ", '%d %M %Y') AS '" . decode_html($header_label) . "_Day'";
                                                    $fieldOrder = $selectedfields[0] . '.' . $selectedfields[1];
                                                }
                                            }
                                            $this->orderByMonthWeekDate[] = $fieldOrder . ' ASC';
                                            $this->queryPlanner->addTable($selectedfields[0]);
                                        } else {
                                            if ($selectedfields[5] == 'H') {
                                                if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                                    $columnSQL = 'date_format(vtiger_crmentity.' . $selectedfields[1] . ", '%h %p %d %M %Y') AS '" . decode_html($header_label) . "_Hours'";
                                                    $fieldOrder = 'vtiger_crmentity.' . $selectedfields[1];
                                                } else {
                                                    $columnSQL = $selectedfields[0] . '.' . $selectedfields[1] . " AS '" . decode_html($header_label) . "'";
                                                    $fieldOrder = $selectedfields[0] . '.' . $selectedfields[1];
                                                }
                                                $this->orderByMonthWeekDate[] = $fieldOrder . ' ASC';
                                                $this->queryPlanner->addTable($selectedfields[0]);
                                            } else {
                                                if ($selectedfields[0] == 'vtiger_activity' && $selectedfields[1] == 'date_start') {
                                                    if ($module == 'Emails') {
                                                        $columnSQL = 'cast(concat(' . $emailTableName . ".date_start,'  '," . $emailTableName . '.time_start) as DATE) AS Emails_Date_Sent';
                                                    } else {
                                                        $columnSQL = "cast(concat(vtiger_activity.date_start,'  ',vtiger_activity.time_start) as DATETIME) AS Calendar_Start_Date_and_Time";
                                                    }
                                                } else {
                                                    if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                                        $columnSQL = 'vtiger_crmentity.' . $selectedfields[1] . " AS '" . decode_html($header_label) . "'";
                                                    } else {
                                                        $columnSQL = $selectedfields[0] . '.' . $selectedfields[1] . " AS '" . decode_html($header_label) . "'";
                                                    }
                                                }
                                                $this->queryPlanner->addTable($selectedfields[0]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        if ($selectedfields[0] == 'vtiger_activity' && $selectedfields[1] == 'status') {
                            $columnSQL = " case when (vtiger_activity.status not like '') then vtiger_activity.status else vtiger_activity.eventstatus end AS Calendar_Status";
                        } else {
                            if ($selectedfields[0] == 'vtiger_activity' && $selectedfields[1] == 'date_start') {
                                if ($module == 'Emails') {
                                    $columnSQL = 'cast(concat(' . $emailTableName . ".date_start,'  '," . $emailTableName . '.time_start) as DATE) AS Emails_Date_Sent';
                                } else {
                                    $columnSQL = "cast(concat(vtiger_activity.date_start,'  ',vtiger_activity.time_start) as DATETIME) AS Calendar_Start_Date_and_Time";
                                }
                            } else {
                                if (stristr($selectedfields[0], 'vtiger_users') && $selectedfields[1] == 'user_name') {
                                    $temp_module_from_tablename = str_replace('vtiger_users', '', $selectedfields[0]);
                                    if ($module != $this->primarymodule) {
                                        $condition = 'and vtiger_crmentity' . $module . ".crmid!=''";
                                        $this->queryPlanner->addTable('vtiger_crmentity' . $module);
                                    } else {
                                        $condition = "and vtiger_crmentity.crmid!=''";
                                    }
                                    if ($temp_module_from_tablename == $module) {
                                        $concatSql = getSqlForNameInDisplayFormat(['first_name' => $selectedfields[0] . '.first_name', 'last_name' => $selectedfields[0] . '.last_name'], 'Users');
                                        $columnSQL = ' case when(' . $selectedfields[0] . ".last_name NOT LIKE '' " . $condition . ' ) THEN ' . $concatSql . ' else vtiger_groups' . $module . ".groupname end AS '" . decode_html($header_label) . "'";
                                        $this->queryPlanner->addTable('vtiger_groups' . $module);
                                    } else {
                                        $columnSQL = $selectedfields[0] . ".user_name AS '" . decode_html($header_label) . "'";
                                    }
                                    $this->queryPlanner->addTable($selectedfields[0]);
                                } else {
                                    if (stristr($selectedfields[0], 'vtiger_crmentity') && $selectedfields[1] == 'modifiedby') {
                                        $targetTableName = 'vtiger_lastModifiedBy' . $module;
                                        $concatSql = getSqlForNameInDisplayFormat(['last_name' => $targetTableName . '.last_name', 'first_name' => $targetTableName . '.first_name'], 'Users');
                                        $columnSQL = 'trim(' . $concatSql . ') AS ' . $header_label;
                                        $this->queryPlanner->addTable('vtiger_crmentity' . $module);
                                        $this->queryPlanner->addTable($targetTableName);
                                        $moduleInstance = CRMEntity::getInstance($module);
                                        $this->queryPlanner->addTable($moduleInstance->table_name);
                                    } else {
                                        if (stristr($selectedfields[0], 'vtiger_crmentity') && $selectedfields[1] == 'smcreatorid') {
                                            $targetTableName = 'vtiger_createdby' . $module;
                                            $concatSql = getSqlForNameInDisplayFormat(['last_name' => $targetTableName . '.last_name', 'first_name' => $targetTableName . '.first_name'], 'Users');
                                            $columnSQL = 'trim(' . $concatSql . ') AS ' . decode_html($header_label) . '';
                                            $this->queryPlanner->addTable('vtiger_crmentity' . $module);
                                            $this->queryPlanner->addTable($targetTableName);
                                            $moduleInstance = CRMEntity::getInstance($module);
                                            $this->queryPlanner->addTable($moduleInstance->table_name);
                                        } else {
                                            if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                                $columnSQL = 'vtiger_crmentity.' . $selectedfields[1] . " AS '" . decode_html($header_label) . "'";
                                            } else {
                                                if ($selectedfields[0] == 'vtiger_products' && $selectedfields[1] == 'unit_price') {
                                                    $columnSQL = 'concat(' . $selectedfields[0] . ".currency_id,'::',innerProduct.actual_unit_price) AS '" . decode_html($header_label) . "'";
                                                    $this->queryPlanner->addTable('innerProduct');
                                                } else {
                                                    if ($selectedfields[0] == 'vtiger_service' && $selectedfields[1] == 'unit_price') {
                                                        $columnSQL = 'concat(' . $selectedfields[0] . ".currency_id,'::',innerService.actual_unit_price) AS '" . decode_html($header_label) . "'";
                                                        $this->queryPlanner->addTable('innerService');
                                                    } else {
                                                        if (in_array(decode_html($selectedfields[2]), $this->append_currency_symbol_to_value) || in_array(decode_html($selectedfields[2]), $this->ui72_fields)) {
                                                            if ($selectedfields[1] == 'discount_amount') {
                                                                $columnSQL = 'CONCAT(' . $selectedfields[0] . ".currency_id,'::', IF(" . $selectedfields[0] . ".discount_amount != ''," . $selectedfields[0] . '.discount_amount, (' . $selectedfields[0] . '.discount_percent/100) * ' . $selectedfields[0] . '.subtotal)) AS ' . decode_html($header_label);
                                                            } else {
                                                                if (in_array($selectedfields[0], ['vtiger_quotescf', 'vtiger_purchaseordercf', 'vtiger_salesordercf', 'vtiger_invoicecf'])) {
                                                                    $tableName = str_replace('cf', '', $selectedfields[0]);
                                                                    $columnSQL = 'concat(' . $tableName . ".currency_id,'::'," . $selectedfields[0] . '.' . $selectedfields[1] . ") AS '" . decode_html($header_label) . "'";
                                                                } else {
                                                                    $columnSQL = 'concat(' . $selectedfields[0] . ".currency_id,'::'," . $selectedfields[0] . '.' . $selectedfields[1] . ") AS '" . decode_html($header_label) . "'";
                                                                }
                                                            }
                                                        } else {
                                                            if ($selectedfields[0] == 'vtiger_notes' && ($selectedfields[1] == 'filelocationtype' || $selectedfields[1] == 'filesize' || $selectedfields[1] == 'folderid' || $selectedfields[1] == 'filestatus')) {
                                                                if ($selectedfields[1] == 'filelocationtype') {
                                                                    $columnSQL = 'case ' . $selectedfields[0] . '.' . $selectedfields[1] . " when 'I' then 'Internal' when 'E' then 'External' else '-' end AS '" . decode_html($selectedfields[2]) . "'";
                                                                } else {
                                                                    if ($selectedfields[1] == 'folderid') {
                                                                        $columnSQL = "vtiger_attachmentsfolder.foldername AS '" . $selectedfields[2] . "'";
                                                                        $this->queryPlanner->addTable('vtiger_attachmentsfolder');
                                                                    } else {
                                                                        if ($selectedfields[1] == 'filestatus') {
                                                                            $columnSQL = 'case ' . $selectedfields[0] . '.' . $selectedfields[1] . " when '1' then 'yes' when '0' then 'no' else '-' end AS '" . decode_html($selectedfields[2]) . "'";
                                                                        } else {
                                                                            if ($selectedfields[1] == 'filesize') {
                                                                                $columnSQL = 'case ' . $selectedfields[0] . '.' . $selectedfields[1] . " when '' then '-' else concat(" . $selectedfields[0] . '.' . $selectedfields[1] . "/1024,'  ','KB') end AS '" . decode_html($selectedfields[2]) . "'";
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            } else {
                                                                $tableName = $selectedfields[0];
                                                                if ($module != $this->primarymodule && $module == 'Emails' && $tableName == 'vtiger_activity') {
                                                                    $tableName = $emailTableName;
                                                                }
                                                                $columnSQL = $tableName . '.' . $selectedfields[1] . " AS '" . decode_html($header_label) . "'";
                                                                $this->queryPlanner->addTable($selectedfields[0]);
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $columnSQL;
    }

    /** Function to get field columns based on profile.
     *  @ param $module : Type string
     *  returns permitted fields in array format
     */
    public function getaccesfield($module)
    {
        global $current_user;
        global $adb;
        $access_fields = [];
        $profileList = getCurrentUserProfileList();
        $query = 'select vtiger_field.fieldname from vtiger_field inner join vtiger_profile2field on vtiger_profile2field.fieldid=vtiger_field.fieldid inner join vtiger_def_org_field on vtiger_def_org_field.fieldid=vtiger_field.fieldid where';
        $params = [];
        if ($module == 'Calendar') {
            if (count($profileList) > 0) {
                $query .= " vtiger_field.tabid in (9,16) and vtiger_field.displaytype in (1,2,3) and vtiger_profile2field.visible=0 and vtiger_def_org_field.visible=0\n\t\t\t\t\t\t\t\tand vtiger_field.presence IN (0,2) and vtiger_profile2field.profileid in (" . generateQuestionMarks($profileList) . ') group by vtiger_field.fieldid order by block,sequence';
                array_push($params, $profileList);
            } else {
                $query .= " vtiger_field.tabid in (9,16) and vtiger_field.displaytype in (1,2,3) and vtiger_profile2field.visible=0 and vtiger_def_org_field.visible=0\n\t\t\t\t\t\t\t\tand vtiger_field.presence IN (0,2) group by vtiger_field.fieldid order by block,sequence";
            }
        } else {
            array_push($params, $module);
            if (count($profileList) > 0) {
                $query .= " vtiger_field.tabid in (select tabid from vtiger_tab where vtiger_tab.name in (?)) and vtiger_field.displaytype in (1,2,3,5) and vtiger_profile2field.visible=0\n\t\t\t\t\t\t\t\tand vtiger_field.presence IN (0,2) and vtiger_def_org_field.visible=0 and vtiger_profile2field.profileid in (" . generateQuestionMarks($profileList) . ') group by vtiger_field.fieldid order by block,sequence';
                array_push($params, $profileList);
            } else {
                $query .= " vtiger_field.tabid in (select tabid from vtiger_tab where vtiger_tab.name in (?)) and vtiger_field.displaytype in (1,2,3,5) and vtiger_profile2field.visible=0\n\t\t\t\t\t\t\t\tand vtiger_field.presence IN (0,2) and vtiger_def_org_field.visible=0 group by vtiger_field.fieldid order by block,sequence";
            }
        }
        $result = $adb->pquery($query, $params);

        while ($collistrow = $adb->fetch_array($result)) {
            $access_fields[] = $collistrow['fieldname'];
        }
        if ($module == 'HelpDesk') {
            $access_fields[] = 'ticketid';
        }

        return $access_fields;
    }

    /** Function to get Escapedcolumns for the field in case of multiple parents.
     *  @ param $selectedfields : Type Array
     *  returns the case query for the escaped columns
     */
    public function getEscapedColumns($selectedfields)
    {
        [$tableName, $columnName, $moduleFieldLabel, $fieldName] = $selectedfields;
        [$moduleName, $fieldLabel] = explode('_', $moduleFieldLabel, 2);
        $fieldInfo = getFieldByVReportLabel($moduleName, $fieldLabel);
        if ($moduleName == 'ModComments' && $fieldName == 'creator') {
            $concatSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_usersModComments.first_name', 'last_name' => 'vtiger_usersModComments.last_name'], 'Users');
            $queryColumn = "trim(case when (vtiger_usersModComments.user_name not like '' and vtiger_crmentity.crmid!='') then " . $concatSql . ' end) AS ModComments_Creator';
            $this->queryPlanner->addTable('vtiger_usersModComments');
            $this->queryPlanner->addTable('vtiger_usersModComments');
        } else {
            if (($fieldInfo['uitype'] == '10' || isReferenceUITypeVReport($fieldInfo['uitype'])) && $fieldInfo['tablename'] != 'vtiger_inventoryproductrel' && $fieldInfo['uitype'] != '52' && $fieldInfo['uitype'] != '53') {
                $fieldSqlColumns = $this->getReferenceFieldColumnList($moduleName, $fieldInfo);
                if (count($fieldSqlColumns) > 0) {
                    $queryColumn = '(CASE WHEN ' . $tableName . '.' . $columnName . " NOT LIKE '' THEN (CASE";
                    foreach ($fieldSqlColumns as $columnSql) {
                        $queryColumn .= ' WHEN ' . $columnSql . " NOT LIKE '' THEN " . $columnSql;
                    }
                    $queryColumn .= " ELSE '' END) ELSE '' END) AS '" . decode_html($moduleFieldLabel) . "'";
                    $this->queryPlanner->addTable($tableName);
                }
            }
        }

        return $queryColumn;
    }

    /** Function to get selectedcolumns for the given reportid.
     *  @ param $reportid : Type Integer
     *  returns the query of columnlist for the selected columns
     */
    public function getSelectedColumnsList($reportid)
    {
        global $adb;
        global $modules;
        global $log;
        $ssql = 'select vtiger_selectcolumn.* from vtiger_vreport inner join vtiger_selectquery on vtiger_selectquery.queryid = vtiger_vreport.queryid';
        $ssql .= ' left join vtiger_selectcolumn on vtiger_selectcolumn.queryid = vtiger_selectquery.queryid where vtiger_vreport.reportid = ? ';
        $ssql .= ' order by vtiger_selectcolumn.columnindex';
        $result = $adb->pquery($ssql, [$reportid]);
        $noofrows = $adb->num_rows($result);
        if ($this->orderbylistsql != '') {
            $sSQL .= $this->orderbylistsql . ', ';
        }
        for ($i = 0; $i < $noofrows; ++$i) {
            $fieldcolname = $adb->query_result($result, $i, 'columnname');
            $ordercolumnsequal = true;
            if ($fieldcolname != '') {
                for ($j = 0; $j < count($this->orderbylistcolumns); ++$j) {
                    if ($this->orderbylistcolumns[$j] == $fieldcolname) {
                        $ordercolumnsequal = false;
                        break;
                    }
                    $ordercolumnsequal = true;
                }
                if ($ordercolumnsequal) {
                    $selectedfields = explode(':', $fieldcolname);
                    if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                        $selectedfields[0] = 'vtiger_crmentity';
                    }
                    $sSQLList[] = $selectedfields[0] . '.' . $selectedfields[1] . " '" . $selectedfields[2] . "'";
                }
            }
        }
        $sSQL .= implode(',', $sSQLList);
        $log->info('ReportRun :: Successfully returned getSelectedColumnsList' . $reportid);

        return $sSQL;
    }

    /** Function to get advanced comparator in query form for the given Comparator and value.
     *  @ param $comparator : Type String
     *  @ param $value : Type String
     *  returns the check query for the comparator
     */
    public function getAdvComparator($comparator, $value, $datatype = '', $columnName = '')
    {
        global $log;
        global $adb;
        global $default_charset;
        global $ogReport;
        $value = html_entity_decode(trim($value), ENT_QUOTES, $default_charset);
        $value_len = strlen($value);
        $is_field = false;
        if ($value_len > 1 && $value[0] == '$' && $value[$value_len - 1] == '$') {
            $temp = str_replace('$', '', $value);
            $is_field = true;
        }
        if ($datatype == 'C') {
            $value = str_replace('yes', '1', str_replace('no', '0', $value));
        }
        if ($is_field == true) {
            $value = $this->getFilterComparedField($temp);
        }
        if ($comparator == 'e' || $comparator == 'y') {
            if (trim($value) == 'NULL') {
                $rtvalue = ' is NULL';
            } else {
                if (trim($value) != '') {
                    $rtvalue = ' = ' . $adb->quote($value);
                } else {
                    if (trim($value) == '' && $datatype == 'V') {
                        $rtvalue = ' = ' . $adb->quote($value);
                    } else {
                        $rtvalue = ' is NULL';
                    }
                }
            }
        }
        if ($comparator == 'n' || $comparator == 'ny') {
            if (trim($value) == 'NULL') {
                $rtvalue = ' is NOT NULL';
            } else {
                if (trim($value) != '') {
                    if ($columnName) {
                        $rtvalue = ' <> ' . $adb->quote($value) . ' OR ' . $columnName . ' IS NULL ';
                    } else {
                        $rtvalue = ' <> ' . $adb->quote($value);
                    }
                } else {
                    if (trim($value) == '' && $datatype == 'V') {
                        $rtvalue = ' <> ' . $adb->quote($value);
                    } else {
                        $rtvalue = ' is NOT NULL';
                    }
                }
            }
        }
        if ($comparator == 's') {
            $rtvalue = " like '" . formatForSqlLike($value, 2, $is_field) . "'";
        }
        if ($comparator == 'ew') {
            $rtvalue = " like '" . formatForSqlLike($value, 1, $is_field) . "'";
        }
        if ($comparator == 'c') {
            $rtvalue = " like '" . formatForSqlLike($value, 0, $is_field) . "'";
        }
        if ($comparator == 'k') {
            $rtvalue = " not like '" . formatForSqlLike($value, 0, $is_field) . "'";
        }
        if ($comparator == 'l') {
            $rtvalue = ' < ' . $adb->quote($value);
        }
        if ($comparator == 'g') {
            $rtvalue = ' > ' . $adb->quote($value);
        }
        if ($comparator == 'm') {
            $rtvalue = ' <= ' . $adb->quote($value);
        }
        if ($comparator == 'h') {
            $rtvalue = ' >= ' . $adb->quote($value);
        }
        if ($comparator == 'b') {
            $rtvalue = ' < ' . $adb->quote($value);
        }
        if ($comparator == 'a') {
            $rtvalue = ' > ' . $adb->quote($value);
        }
        if ($is_field == true) {
            $rtvalue = str_replace("'", '', $rtvalue);
            $rtvalue = str_replace('\\', '', $rtvalue);
        }
        $log->info('ReportRun :: Successfully returned getAdvComparator');

        return $rtvalue;
    }

    /** Function to get field that is to be compared in query form for the given Comparator and field.
     *  @ param $field : field
     *  returns the value for the comparator
     */
    public function getFilterComparedField($field)
    {
        global $adb;
        global $ogReport;
        if (!empty($this->secondarymodule)) {
            $secModules = explode(':', $this->secondarymodule);
            foreach ($secModules as $secModule) {
                $secondary = CRMEntity::getInstance($secModule);
                $this->queryPlanner->addTable($secondary->table_name);
            }
        }
        $field = explode('#', $field);
        $module = $field[0];
        $fieldname = trim($field[1]);
        $tabid = getTabId($module);
        $field_query = $adb->pquery('SELECT tablename,columnname,typeofdata,fieldname,uitype FROM vtiger_field WHERE tabid = ? AND fieldname= ?', [$tabid, $fieldname]);
        $fieldtablename = $adb->query_result($field_query, 0, 'tablename');
        $fieldcolname = $adb->query_result($field_query, 0, 'columnname');
        $typeofdata = $adb->query_result($field_query, 0, 'typeofdata');
        $fieldtypeofdata = ChangeTypeOfData_Filter($fieldtablename, $fieldcolname, $typeofdata[0]);
        $uitype = $adb->query_result($field_query, 0, 'uitype');
        if ($uitype == 68 || $uitype == 59) {
            $fieldtypeofdata = 'V';
        }
        if ($fieldtablename == 'vtiger_crmentity' && $module != $this->primarymodule) {
            $fieldtablename = $fieldtablename . $module;
        }
        if ($fieldname == 'assigned_user_id') {
            $fieldtablename = 'vtiger_users' . $module;
            $fieldcolname = 'user_name';
        }
        if ($fieldtablename == 'vtiger_crmentity' && $fieldname == 'modifiedby') {
            $fieldtablename = 'vtiger_lastModifiedBy' . $module;
            $fieldcolname = 'user_name';
        }
        if ($fieldname == 'assigned_user_id1') {
            $fieldtablename = 'vtiger_usersRel1';
            $fieldcolname = 'user_name';
        }
        $value = $fieldtablename . '.' . $fieldcolname;
        $this->queryPlanner->addTable($fieldtablename);

        return $value;
    }

    /** Function to get the advanced filter columns for the reportid
     *  This function accepts the $reportid
     *  This function returns  $columnslist Array($columnname => $tablename:$columnname:$fieldlabel:$fieldname:$typeofdata=>$tablename.$columnname filtercriteria,
     * 					      $tablename1:$columnname1:$fieldlabel1:$fieldname1:$typeofdata1=>$tablename1.$columnname1 filtercriteria,
     * 					      					|
     * 					      $tablenamen:$columnnamen:$fieldlabeln:$fieldnamen:$typeofdatan=>$tablenamen.$columnnamen filtercriteria
     * 				      	     ).
     */
    public function getAdvFilterList($reportid, $forClickThrough = false)
    {
        global $adb;
        global $log;
        $currentUser = Users_Privileges_Model::getCurrentUserModel();
        $advft_criteria = [];
        $sqlgroupparent = 'SELECT * FROM vtiger_vreport_relcriteria_grouping_parent WHERE queryid = ? ORDER BY groupparentid';
        $groupparentresult = $adb->pquery($sqlgroupparent, [$reportid]);
        for ($groupParentIndex = 1; $rowGroupParent = $adb->fetchByAssoc($groupparentresult); ++$groupParentIndex) {
            $groupParentId = $rowGroupParent['groupparentid'];
            $groupParentCondition = $rowGroupParent['group_parent_condition'];
            $sql = 'SELECT groupid,group_condition FROM vtiger_vreport_relcriteria_grouping WHERE queryid = ? AND groupparentid = ? ORDER BY groupid';
            $groupsresult = $adb->pquery($sql, [$reportid, $groupParentId]);
            $i = 1;
            for ($j = 0; $relcriteriagroup = $adb->fetch_array($groupsresult); ++$i) {
                $groupId = $relcriteriagroup['groupid'];
                $groupCondition = $relcriteriagroup['group_condition'];
                $ssql = "select vtiger_vreport_relcriteria.* from vtiger_vreport\n\t\t\t\t\t\t\tinner join vtiger_vreport_relcriteria on vtiger_vreport_relcriteria.queryid = vtiger_vreport.queryid\n\t\t\t\t\t\t\tleft join vtiger_vreport_relcriteria_grouping on vtiger_vreport_relcriteria.queryid = vtiger_vreport_relcriteria_grouping.queryid AND vtiger_vreport_relcriteria_grouping.groupparentid = " . $groupParentId . "\n\t\t\t\t\t\t\t\t\tand vtiger_vreport_relcriteria.groupid = vtiger_vreport_relcriteria_grouping.groupid";
                $ssql .= ' where vtiger_vreport.reportid = ? AND vtiger_vreport_relcriteria.groupid = ? AND vtiger_vreport_relcriteria.groupparentid = ' . $groupParentId . ' order by vtiger_vreport_relcriteria.columnindex';
                $result = $adb->pquery($ssql, [$reportid, $groupId]);
                $noOfColumns = $adb->num_rows($result);
                if ($noOfColumns <= 0) {
                    continue;
                }

                while ($relcriteriarow = $adb->fetch_array($result)) {
                    $columnIndex = $relcriteriarow['columnindex'];
                    $criteria = [];
                    $criteria['columnname'] = html_entity_decode($relcriteriarow['columnname']);
                    $criteria['comparator'] = $relcriteriarow['comparator'];
                    $advfilterval = $relcriteriarow['value'];
                    $col = explode(':', $relcriteriarow['columnname']);
                    $criteria['value'] = $advfilterval;
                    $criteria['column_condition'] = $relcriteriarow['column_condition'];
                    $advft_criteria[$groupParentIndex][$i]['columns'][$j] = $criteria;
                    $advft_criteria[$groupParentIndex][$i]['condition'] = $groupCondition;
                    ++$j;
                    $this->queryPlanner->addTable($col[0]);
                }
                if (!empty($advft_criteria[$groupParentIndex][$i]['columns'][$j - 1]['column_condition'])) {
                    $advft_criteria[$groupParentIndex][$i]['columns'][$j - 1]['column_condition'] = '';
                }
            }
            if (!empty($advft_criteria[$groupParentIndex][$i - 1]['condition'])) {
                $advft_criteria[$groupParentIndex][$i - 1]['condition'] = '';
            }
            $advft_criteria[$groupParentIndex]['groupParentCondition'] = $groupParentCondition;
        }
        if ($_REQUEST['name'] == 'Gauge') {
            $queryWidgetFilterAssignedTo = $adb->pquery('SELECT `data` FROM `vtiger_module_vreportdashboard_widgets` WHERE id = ?', [$_REQUEST['widgetid']]);
        } else {
            $queryWidgetFilterAssignedTo = $adb->pquery('SELECT `data` FROM `vtiger_module_vreportdashboard_widgets` WHERE id = ? AND reportid = ?', [$_REQUEST['widgetid'], $_REQUEST['reportid']]);
        }
        $widgetFilterAssignedto = json_decode($adb->query_result($queryWidgetFilterAssignedTo, 0, 0))->filterAssignedto;
        $widgetFilterCreatedBy = json_decode($adb->query_result($queryWidgetFilterAssignedTo, 0, 0))->filterCreatedby;
        $checkAccountUrl = explode('=', end(explode('&', $_SERVER['HTTP_REFERER'])));
        $checkAccountUrl = $checkAccountUrl[0];
        if (!empty($_REQUEST['tabid']) && $_REQUEST['tabid'] != null || $checkAccountUrl == 'organization') {
            $resultDynamicFilter = $adb->pquery("SELECT vtiger_crmentity.label,\n\t\t\t\t\t\t\t\t\t\t\t\t\t\tvtiger_crmentity.deleted,\n\t\t\t\t\t\t\t\t\t\t\t\t\t\tvtiger_vreportdashboard_tabs.dynamic_filter_account,\n\t\t\t\t\t\t\t\t\t\t\t\t\t\tdynamic_filter_assignedto,dynamic_filter_createdby, dynamic_filter_date,dynamic_filter_type_date\n\t\t\t\t\t\t\t\t\t\t\t\t\t  FROM vtiger_vreportdashboard_tabs \n\t\t\t\t\t\t\t\t\t\t\t\t\t  LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_vreportdashboard_tabs.dynamic_filter_account\n\t\t\t\t\t\t\t\t\t\t\t\t\t  WHERE id = ?\n\t\t\t\t\t\t\t\t\t\t\t\t\t  AND ((dynamic_filter_account > 0 AND dynamic_filter_account IS NOT NULL)\n\t\t\t\t\t\t\t\t\t\t\t\t\t  OR dynamic_filter_assignedto IS NOT NULL OR dynamic_filter_createdby IS NOT NULL) LIMIT 1", [$_REQUEST['tabid']]);
            if ($adb->num_rows($resultDynamicFilter) > 0 || $widgetFilterAssignedto != '' && $widgetFilterAssignedto != null || $widgetFilterCreatedBy != '' && $widgetFilterCreatedBy != null) {
                if ($checkAccountUrl == 'organization') {
                    $accountId = explode('=', end(explode('&', $_SERVER['HTTP_REFERER'])));
                    $accountId = $accountId[1];
                    $queryGetAccount = $adb->pquery('SELECT * FROM `vtiger_account` WHERE accountid = ?', [$accountId]);
                }
                if ($adb->num_rows($queryGetAccount) > 0) {
                    $dynamicFilterAccount = $adb->query_result($queryGetAccount, 0, 'accountid');
                    $dynamicFilterAccountLabel = $adb->query_result($queryGetAccount, 0, 'accountname');
                } else {
                    $dynamicFilterAccount = $adb->query_result($resultDynamicFilter, 0, 'dynamic_filter_account');
                    $dynamicFilterAccountLabel = html_entity_decode($adb->query_result($resultDynamicFilter, 0, 'label'));
                }
                if ($widgetFilterAssignedto != '' && $widgetFilterAssignedto != null) {
                    $dynamicFilterAssignedTo = $widgetFilterAssignedto;
                } else {
                    $dynamicFilterAssignedTo = $adb->query_result($resultDynamicFilter, 0, 'dynamic_filter_assignedto');
                }
                if ($widgetFilterCreatedBy != '' && $widgetFilterCreatedBy != null) {
                    $dynamicFilterCreatedBy = $widgetFilterCreatedBy;
                } else {
                    $dynamicFilterCreatedBy = $adb->query_result($resultDynamicFilter, 0, 'dynamic_filter_createdby');
                }
                $dynamicFilterAssignedTo = $dynamicFilterAssignedTo == 0 && is_numeric($dynamicFilterAssignedTo) ? $currentUser->getId() : $dynamicFilterAssignedTo;
                $dynamicFilterCreatedBy = $dynamicFilterCreatedBy == 0 && is_numeric($dynamicFilterCreatedBy) ? $currentUser->getId() : $dynamicFilterCreatedBy;
                $dynamicFilterDate = $adb->query_result($resultDynamicFilter, '0', 'dynamic_filter_date');
                $dynamicFilterValueTypeDate = $adb->query_result($resultDynamicFilter, '0', 'dynamic_filter_type_date');
                $moduleModel = Vtiger_Module_Model::getInstance($this->primarymodule);
                $referencesFieldPrimaryModule = $moduleModel->getFieldsByType('reference');
                $countGroupAdv = count($advft_criteria);
                if ($noOfColumns > 0) {
                    $advft_criteria[$countGroupAdv]['groupParentCondition'] = 'and';
                }
                if ($dynamicFilterAccount) {
                    foreach ($referencesFieldPrimaryModule as $fieldName => $fieldModel) {
                        $moduleReference = $fieldModel->getReferenceList();
                        $moduleReference = $moduleReference[0];
                        if ($moduleReference == 'Accounts') {
                            $moduleName = $moduleModel->getName();
                            $fieldTableName = $fieldModel->get('table');
                            $fieldColumnName = $fieldModel->get('column');
                            $fieldLabel = str_replace(' ', '_', $fieldModel->get('label'));
                            $fieldName = $fieldModel->getName();
                            $fieldTypeData = explode('~', $fieldModel->get('typeofdata'));
                            $fieldTypeData = $fieldTypeData[0];
                            $columname = $fieldTableName . ':' . $fieldColumnName . ':' . $moduleName . '_' . $fieldLabel . ':' . $fieldName . ':' . $fieldTypeData;
                            $advft_criteria[$countGroupAdv + 1][1]['columns'][0]['columnname'] = $columname;
                            $advft_criteria[$countGroupAdv + 1][1]['columns'][0]['comparator'] = 'e';
                            $advft_criteria[$countGroupAdv + 1][1]['columns'][0]['value'] = $dynamicFilterAccountLabel;
                            $advft_criteria[$countGroupAdv + 1][1]['columns'][0]['column_condition'] = '';
                            $advft_criteria[$countGroupAdv + 1]['groupParentCondition'] = '';
                            break;
                        }
                    }
                }
                $countColumnAdv = count($advft_criteria[$countGroupAdv + 1][1]['columns']);
                if ($dynamicFilterAssignedTo) {
                    $dynamicFilterAssignedToName = $this->getAssignedToDynamicFilter($dynamicFilterAssignedTo);
                    $advft_criteria[$countGroupAdv + 1][1]['columns'][0]['column_condition'] = 'and';
                    $fieldAssignedToModel = Vtiger_Field_Model::getInstance('assigned_user_id', $moduleModel);
                    $moduleName = $moduleModel->getName();
                    $fieldTableName = 'vtiger_users' . $moduleName;
                    $fieldColumnName = 'user_name';
                    $fieldLabel = str_replace(' ', '_', $fieldAssignedToModel->get('label'));
                    $fieldName = $fieldAssignedToModel->getName();
                    $fieldTypeData = explode('~', $fieldAssignedToModel->get('typeofdata'));
                    $fieldTypeData = $fieldTypeData[0];
                    $columname = $fieldTableName . ':' . $fieldColumnName . ':' . $moduleName . '_' . $fieldLabel . ':' . $fieldName . ':' . $fieldTypeData;
                    $advft_criteria[$countGroupAdv + 1][1]['columns'][$countColumnAdv]['columnname'] = $columname;
                    $advft_criteria[$countGroupAdv + 1][1]['columns'][$countColumnAdv]['comparator'] = 'e';
                    $advft_criteria[$countGroupAdv + 1][1]['columns'][$countColumnAdv]['value'] = $dynamicFilterAssignedToName;
                    $advft_criteria[$countGroupAdv + 1][1]['columns'][$countColumnAdv]['column_condition'] = '';
                }
                $countColumnAdv = count($advft_criteria[$countGroupAdv + 1][1]['columns']);
                if ($dynamicFilterDate && $dynamicFilterValueTypeDate) {
                    $advft_criteria[$countGroupAdv + 1][1]['columns'][$countColumnAdv - 1]['column_condition'] = 'and';
                    $currentUserModel = Users_Record_Model::getCurrentUserModel();
                    $userPeferredDayOfTheWeek = $currentUserModel->get('dayoftheweek');
                    $dynamicFilterValueTypeDateModel = Vtiger_Field_Model::getInstance($dynamicFilterValueTypeDate, $moduleModel);
                    $fieldTableName = $dynamicFilterValueTypeDateModel->get('table');
                    $fieldColumnName = $dynamicFilterValueTypeDateModel->get('column');
                    $fieldLabel = str_replace(' ', '_', $dynamicFilterValueTypeDateModel->get('label'));
                    $fieldName = $dynamicFilterValueTypeDateModel->getName();
                    $fieldTypeData = explode('~', $dynamicFilterValueTypeDateModel->get('typeofdata'));
                    $fieldTypeData = $fieldTypeData[0];
                    $columname = $fieldTableName . ':' . $fieldColumnName . ':' . $moduleName . '_' . $fieldLabel . ':' . $fieldName . ':' . $fieldTypeData;
                    $dateValues = self::getDateForStdFilterBytype($dynamicFilterDate, $userPeferredDayOfTheWeek);
                    [$startDate, $endDate] = $dateValues;
                    $advft_criteria[$countGroupAdv + 1][1]['columns'][$countColumnAdv]['columnname'] = $columname;
                    $advft_criteria[$countGroupAdv + 1][1]['columns'][$countColumnAdv]['comparator'] = 'bw';
                    $advft_criteria[$countGroupAdv + 1][1]['columns'][$countColumnAdv]['value'] = $startDate . ',' . $endDate;
                    $advft_criteria[$countGroupAdv + 1][1]['columns'][$countColumnAdv]['column_condition'] = '';
                }
                if ($dynamicFilterCreatedBy) {
                    $advft_criteria[$countGroupAdv + 2]['dynamicFilterCreatedBy'] = $dynamicFilterCreatedBy;
                }
            }
        }

        return $advft_criteria;
    }

    protected static function getDateForStdFilterBytype($type, $userPeferredDayOfTheWeek = false)
    {
        $date = DateTimeField::convertToUserTimeZone(date('Y-m-d H:i:s'));
        $d = $date->format('d');
        $m = $date->format('m');
        $y = $date->format('Y');
        $today = date('Y-m-d', mktime(0, 0, 0, $m, $d, $y));
        $todayName = date('l', strtotime($today));
        $tomorrow = date('Y-m-d', mktime(0, 0, 0, $m, $d + 1, $y));
        $yesterday = date('Y-m-d', mktime(0, 0, 0, $m, $d - 1, $y));
        $currentmonth0 = date('Y-m-d', mktime(0, 0, 0, $m, '01', $y));
        $currentmonth1 = $date->format('Y-m-t');
        $lastmonth0 = date('Y-m-d', mktime(0, 0, 0, $m - 1, '01', $y));
        $lastmonth1 = date('Y-m-t', strtotime($lastmonth0));
        $nextmonth0 = date('Y-m-d', mktime(0, 0, 0, $m + 1, '01', $y));
        $nextmonth1 = date('Y-m-t', strtotime($nextmonth0));
        if (!$userPeferredDayOfTheWeek) {
            $userPeferredDayOfTheWeek = 'Sunday';
        }
        if ($todayName == $userPeferredDayOfTheWeek) {
            $lastweek0 = date('Y-m-d', strtotime('-1 week ' . $userPeferredDayOfTheWeek));
        } else {
            $lastweek0 = date('Y-m-d', strtotime('-2 week ' . $userPeferredDayOfTheWeek));
        }
        $prvDay = date('l', strtotime(date('Y-m-d', strtotime('-1 day', strtotime($lastweek0)))));
        $lastweek1 = date('Y-m-d', strtotime('-1 week ' . $prvDay));
        if ($todayName == $userPeferredDayOfTheWeek) {
            $thisweek0 = date('Y-m-d', strtotime('-0 week ' . $userPeferredDayOfTheWeek));
        } else {
            $thisweek0 = date('Y-m-d', strtotime('-1 week ' . $userPeferredDayOfTheWeek));
        }
        $prvDay = date('l', strtotime(date('Y-m-d', strtotime('-1 day', strtotime($thisweek0)))));
        $thisweek1 = date('Y-m-d', strtotime('this ' . $prvDay));
        if ($todayName == $userPeferredDayOfTheWeek) {
            $nextweek0 = date('Y-m-d', strtotime('+1 week ' . $userPeferredDayOfTheWeek));
        } else {
            $nextweek0 = date('Y-m-d', strtotime('this ' . $userPeferredDayOfTheWeek));
        }
        $prvDay = date('l', strtotime(date('Y-m-d', strtotime('-1 day', strtotime($nextweek0)))));
        $nextweek1 = date('Y-m-d', strtotime('+1 week ' . $prvDay));
        $next7days = date('Y-m-d', mktime(0, 0, 0, $m, $d + 6, $y));
        $next30days = date('Y-m-d', mktime(0, 0, 0, $m, $d + 29, $y));
        $next60days = date('Y-m-d', mktime(0, 0, 0, $m, $d + 59, $y));
        $next90days = date('Y-m-d', mktime(0, 0, 0, $m, $d + 89, $y));
        $next120days = date('Y-m-d', mktime(0, 0, 0, $m, $d + 119, $y));
        $last7days = date('Y-m-d', mktime(0, 0, 0, $m, $d - 6, $y));
        $last14days = date('Y-m-d', mktime(0, 0, 0, $m, $d - 13, $y));
        $last30days = date('Y-m-d', mktime(0, 0, 0, $m, $d - 29, $y));
        $last60days = date('Y-m-d', mktime(0, 0, 0, $m, $d - 59, $y));
        $last90days = date('Y-m-d', mktime(0, 0, 0, $m, $d - 89, $y));
        $last120days = date('Y-m-d', mktime(0, 0, 0, $m, $d - 119, $y));
        $currentFY0 = date('Y-m-d', mktime(0, 0, 0, '01', '01', $y));
        $currentFY1 = date('Y-m-t', mktime(0, 0, 0, '12', $d, $y));
        $lastFY0 = date('Y-m-d', mktime(0, 0, 0, '01', '01', $y - 1));
        $lastFY1 = date('Y-m-t', mktime(0, 0, 0, '12', $d, $y - 1));
        $nextFY0 = date('Y-m-d', mktime(0, 0, 0, '01', '01', $y + 1));
        $nextFY1 = date('Y-m-t', mktime(0, 0, 0, '12', $d, $y + 1));
        if ($m <= 3) {
            $cFq = date('Y-m-d', mktime(0, 0, 0, '01', '01', $y));
            $cFq1 = date('Y-m-d', mktime(0, 0, 0, '03', '31', $y));
            $nFq = date('Y-m-d', mktime(0, 0, 0, '04', '01', $y));
            $nFq1 = date('Y-m-d', mktime(0, 0, 0, '06', '30', $y));
            $pFq = date('Y-m-d', mktime(0, 0, 0, '10', '01', $y - 1));
            $pFq1 = date('Y-m-d', mktime(0, 0, 0, '12', '31', $y - 1));
        } else {
            if ($m > 3 && $m <= 6) {
                $cFq = date('Y-m-d', mktime(0, 0, 0, '04', '01', $y));
                $cFq1 = date('Y-m-d', mktime(0, 0, 0, '06', '30', $y));
                $nFq = date('Y-m-d', mktime(0, 0, 0, '07', '01', $y));
                $nFq1 = date('Y-m-d', mktime(0, 0, 0, '09', '30', $y));
                $pFq = date('Y-m-d', mktime(0, 0, 0, '01', '01', $y));
                $pFq1 = date('Y-m-d', mktime(0, 0, 0, '03', '31', $y));
            } else {
                if ($m > 6 && $m <= 9) {
                    $cFq = date('Y-m-d', mktime(0, 0, 0, '07', '01', $y));
                    $cFq1 = date('Y-m-d', mktime(0, 0, 0, '09', '30', $y));
                    $nFq = date('Y-m-d', mktime(0, 0, 0, '10', '01', $y));
                    $nFq1 = date('Y-m-d', mktime(0, 0, 0, '12', '31', $y));
                    $pFq = date('Y-m-d', mktime(0, 0, 0, '04', '01', $y));
                    $pFq1 = date('Y-m-d', mktime(0, 0, 0, '06', '30', $y));
                } else {
                    $cFq = date('Y-m-d', mktime(0, 0, 0, '10', '01', $y));
                    $cFq1 = date('Y-m-d', mktime(0, 0, 0, '12', '31', $y));
                    $nFq = date('Y-m-d', mktime(0, 0, 0, '01', '01', $y + 1));
                    $nFq1 = date('Y-m-d', mktime(0, 0, 0, '03', '31', $y + 1));
                    $pFq = date('Y-m-d', mktime(0, 0, 0, '07', '01', $y));
                    $pFq1 = date('Y-m-d', mktime(0, 0, 0, '09', '30', $y));
                }
            }
        }
        $dateValues = [];
        if ($type == 'today') {
            $dateValues[0] = $today;
            $dateValues[1] = $today;
        } else {
            if ($type == 'yesterday') {
                $dateValues[0] = $yesterday;
                $dateValues[1] = $yesterday;
            } else {
                if ($type == 'tomorrow') {
                    $dateValues[0] = $tomorrow;
                    $dateValues[1] = $tomorrow;
                } else {
                    if ($type == 'thisweek') {
                        $dateValues[0] = $thisweek0;
                        $dateValues[1] = $thisweek1;
                    } else {
                        if ($type == 'lastweek') {
                            $dateValues[0] = $lastweek0;
                            $dateValues[1] = $lastweek1;
                        } else {
                            if ($type == 'nextweek') {
                                $dateValues[0] = $nextweek0;
                                $dateValues[1] = $nextweek1;
                            } else {
                                if ($type == 'thismonth') {
                                    $dateValues[0] = $currentmonth0;
                                    $dateValues[1] = $currentmonth1;
                                } else {
                                    if ($type == 'lastmonth') {
                                        $dateValues[0] = $lastmonth0;
                                        $dateValues[1] = $lastmonth1;
                                    } else {
                                        if ($type == 'nextmonth') {
                                            $dateValues[0] = $nextmonth0;
                                            $dateValues[1] = $nextmonth1;
                                        } else {
                                            if ($type == 'next7days') {
                                                $dateValues[0] = $today;
                                                $dateValues[1] = $next7days;
                                            } else {
                                                if ($type == 'next30days') {
                                                    $dateValues[0] = $today;
                                                    $dateValues[1] = $next30days;
                                                } else {
                                                    if ($type == 'next60days') {
                                                        $dateValues[0] = $today;
                                                        $dateValues[1] = $next60days;
                                                    } else {
                                                        if ($type == 'next90days') {
                                                            $dateValues[0] = $today;
                                                            $dateValues[1] = $next90days;
                                                        } else {
                                                            if ($type == 'next120days') {
                                                                $dateValues[0] = $today;
                                                                $dateValues[1] = $next120days;
                                                            } else {
                                                                if ($type == 'last7days') {
                                                                    $dateValues[0] = $last7days;
                                                                    $dateValues[1] = $today;
                                                                } else {
                                                                    if ($type == 'last14days') {
                                                                        $dateValues[0] = $last14days;
                                                                        $dateValues[1] = $today;
                                                                    } else {
                                                                        if ($type == 'last30days') {
                                                                            $dateValues[0] = $last30days;
                                                                            $dateValues[1] = $today;
                                                                        } else {
                                                                            if ($type == 'last60days') {
                                                                                $dateValues[0] = $last60days;
                                                                                $dateValues[1] = $today;
                                                                            } else {
                                                                                if ($type == 'last90days') {
                                                                                    $dateValues[0] = $last90days;
                                                                                    $dateValues[1] = $today;
                                                                                } else {
                                                                                    if ($type == 'last120days') {
                                                                                        $dateValues[0] = $last120days;
                                                                                        $dateValues[1] = $today;
                                                                                    } else {
                                                                                        if ($type == 'thisfy') {
                                                                                            $dateValues[0] = $currentFY0;
                                                                                            $dateValues[1] = $currentFY1;
                                                                                        } else {
                                                                                            if ($type == 'prevfy') {
                                                                                                $dateValues[0] = $lastFY0;
                                                                                                $dateValues[1] = $lastFY1;
                                                                                            } else {
                                                                                                if ($type == 'nextfy') {
                                                                                                    $dateValues[0] = $nextFY0;
                                                                                                    $dateValues[1] = $nextFY1;
                                                                                                } else {
                                                                                                    if ($type == 'nextfq') {
                                                                                                        $dateValues[0] = $nFq;
                                                                                                        $dateValues[1] = $nFq1;
                                                                                                    } else {
                                                                                                        if ($type == 'prevfq') {
                                                                                                            $dateValues[0] = $pFq;
                                                                                                            $dateValues[1] = $pFq1;
                                                                                                        } else {
                                                                                                            if ($type == 'thisfq') {
                                                                                                                $dateValues[0] = $cFq;
                                                                                                                $dateValues[1] = $cFq1;
                                                                                                            } else {
                                                                                                                $dateValues[0] = '';
                                                                                                                $dateValues[1] = '';
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $dateValues;
    }

    public function getAssignedToDynamicFilter($recordId)
    {
        $db = PearDatabase::getInstance();
        $query = $db->pquery('SELECT `first_name`,`last_name` FROM `vtiger_users` WHERE id = ?', [$recordId]);
        $last_name = $db->query_result($query, 0, 'last_name');
        $first_name = $db->query_result($query, 0, 'first_name');
        $result = $first_name . ' ' . $last_name;
        if (!$result) {
            $query = $db->pquery('SELECT groupname FROM `vtiger_groups` WHERE groupid = ?', [$recordId]);
            $result = $db->query_result($query, 0, 0);
        }

        return $result;
    }

    public function generateAdvFilterSql($advfilterlists)
    {
        global $adb;
        $advfiltersql = '';
        $customView = new CustomView();
        $dateSpecificConditions = $customView->getStdFilterConditions();
        $specialDateComparators = ['yesterday', 'today', 'tomorrow'];
        foreach ($advfilterlists as $groupParentIndex => $advfilterlist) {
            foreach ($advfilterlist as $groupindex => $groupinfo) {
                if ($groupindex == 'dynamicFilterCreatedBy') {
                    if ($advfiltersql != '') {
                        $advfiltersql .= 'AND vtiger_crmentity.smcreatorid = ' . $groupinfo;
                    } else {
                        $advfiltersql .= 'vtiger_crmentity.smcreatorid = ' . $groupinfo;
                    }

                    continue;
                }
                if ($groupindex == 'groupParentCondition') {
                    $count_advfilterlist = 0;
                    if (is_array($advfilterlist)) {
                        $count_advfilterlist = count($advfilterlist);
                    }
                    $count_advfilterlists_groupParentIndex = 0;
                    if (is_array($advfilterlists[$groupParentIndex + 1])) {
                        $count_advfilterlists_groupParentIndex = count($advfilterlists[$groupParentIndex + 1]);
                    }
                    if ($count_advfilterlist == 1 || $count_advfilterlists_groupParentIndex == 1) {
                        continue;
                    }
                    if ($groupinfo != '') {
                        $groupinfo = strtoupper($groupinfo);
                        $advfiltersql = '(' . $advfiltersql . ') ' . $groupinfo;
                    } else {
                        continue;
                    }
                }
                $groupcondition = $groupinfo['condition'];
                $groupcolumns = $groupinfo['columns'];
                if (count($groupcolumns) > 0) {
                    $advfiltergroupsql = '';
                    foreach ($groupcolumns as $columnindex => $columninfo) {
                        $fieldcolname = $columninfo['columnname'];
                        $comparator = $columninfo['comparator'];
                        $value = $columninfo['value'];
                        $columncondition = $columninfo['column_condition'];
                        $advcolsql = [];
                        $selectedFields = explode(':', $fieldcolname);
                        $moduleFieldLabel = $selectedFields[2];
                        [$moduleName, $fieldLabel] = explode('_', $moduleFieldLabel, 2);
                        $emailTableName = '';
                        if ($moduleName == 'Emails' && $moduleName != $this->primarymodule && $selectedFields[0] == 'vtiger_activity') {
                            $emailTableName = 'vtiger_activityEmails';
                        }
                        if ($fieldcolname != '' && $comparator != '') {
                            if (in_array($comparator, $dateSpecificConditions)) {
                                if ($fieldcolname != 'none') {
                                    $selectedFields = explode(':', $fieldcolname);
                                    if ($selectedFields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                        $selectedFields[0] = 'vtiger_crmentity';
                                    }
                                    if ($comparator != 'custom') {
                                        [$startDate, $endDate] = $this->getStandarFiltersStartAndEndDate($comparator);
                                    } else {
                                        [$startDateTime, $endDateTime] = explode(',', $value);
                                        [$startDate, $startTime] = explode(' ', $startDateTime);
                                        [$endDate, $endTime] = explode(' ', $endDateTime);
                                    }
                                    $type = $selectedFields[4];
                                    if ($startDate != '0000-00-00' && $endDate != '0000-00-00' && $startDate != '' && $endDate != '') {
                                        if ($type == 'DT') {
                                            $startDateTime = new DateTimeField($startDate . ' ' . date('H:i:s'));
                                            $endDateTime = new DateTimeField($endDate . ' ' . date('H:i:s'));
                                            $userStartDate = $startDateTime->getDisplayDate() . ' 00:00:00';
                                            $userEndDate = $endDateTime->getDisplayDate() . ' 23:59:59';
                                        } else {
                                            if (in_array($comparator, $specialDateComparators)) {
                                                $startDateTime = new DateTimeField($startDate . ' ' . date('H:i:s'));
                                                $endDateTime = new DateTimeField($endDate . ' ' . date('H:i:s'));
                                                $userStartDate = $startDateTime->getDisplayDate();
                                                $userEndDate = $endDateTime->getDisplayDate();
                                            } else {
                                                $startDateTime = new DateTimeField($startDate);
                                                $endDateTime = new DateTimeField($endDate);
                                                $userStartDate = $startDateTime->getDisplayDate();
                                                $userEndDate = $endDateTime->getDisplayDate();
                                            }
                                        }
                                        $startDateTime = getValidDBInsertDateTimeValue($userStartDate);
                                        $endDateTime = getValidDBInsertDateTimeValue($userEndDate);
                                        if ($selectedFields[1] == 'birthday') {
                                            $tableColumnSql = 'DATE_FORMAT(' . $selectedFields[0] . '.' . $selectedFields[1] . ', "%m%d")';
                                            $startDateTime = "DATE_FORMAT('" . $startDateTime . "', '%m%d')";
                                            $endDateTime = "DATE_FORMAT('" . $endDateTime . "', '%m%d')";
                                        } else {
                                            if ($selectedFields[0] == 'vtiger_activity' && $selectedFields[1] == 'date_start') {
                                                $tableColumnSql = 'CAST((CONCAT(date_start, " ", time_start)) AS DATETIME)';
                                            } else {
                                                if (empty($emailTableName)) {
                                                    $tableColumnSql = $selectedFields[0] . '.' . $selectedFields[1];
                                                } else {
                                                    $tableColumnSql = $emailTableName . '.' . $selectedFields[1];
                                                }
                                            }
                                            $startDateTime = "'" . $startDateTime . "'";
                                            $endDateTime = "'" . $endDateTime . "'";
                                        }
                                        $advfiltergroupsql .= (string) $tableColumnSql . ' BETWEEN ' . $startDateTime . ' AND ' . $endDateTime;
                                        if (!empty($columncondition)) {
                                            $advfiltergroupsql .= ' ' . $columncondition . ' ';
                                        }
                                        $this->queryPlanner->addTable($selectedFields[0]);
                                    }
                                }

                                continue;
                            }
                            $selectedFields = explode(':', $fieldcolname);
                            $tempComparators = ['e', 'n', 'bw', 'a', 'b'];
                            $tempComparators = array_merge($tempComparators, Vtiger_Functions::getSpecialDateTimeCondtions());
                            if ($selectedFields[4] == 'DT' && in_array($comparator, $tempComparators)) {
                                if ($selectedFields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                    $selectedFields[0] = 'vtiger_crmentity';
                                }
                                if ($selectedFields[0] == 'vtiger_activity' && $selectedFields[1] == 'date_start') {
                                    $tableColumnSql = 'CAST((CONCAT(date_start, " ", time_start)) AS DATETIME)';
                                } else {
                                    if (empty($emailTableName)) {
                                        $tableColumnSql = $selectedFields[0] . '.' . $selectedFields[1];
                                    } else {
                                        $tableColumnSql = $emailTableName . '.' . $selectedFields[1];
                                    }
                                }
                                if ($value != null && $value != '') {
                                    if ($comparator == 'e' || $comparator == 'n') {
                                        $dateTimeComponents = explode(' ', $value);
                                        $dateTime = new DateTimeImmutable($dateTimeComponents[0] . ' 00:00:00');
                                        $date1 = $dateTime->format('Y-m-d H:i:s');
                                        $dateTime->modify('+1 days');
                                        $date2 = $dateTime->format('Y-m-d H:i:s');
                                        $tempDate = strtotime($date2) - 1;
                                        $date2 = date('Y-m-d H:i:s', $tempDate);
                                        $start = getValidDBInsertDateTimeValue($date1);
                                        $end = getValidDBInsertDateTimeValue($date2);
                                        $start = "'" . $start . "'";
                                        $end = "'" . $end . "'";
                                        if ($comparator == 'e') {
                                            $advfiltergroupsql .= (string) $tableColumnSql . ' BETWEEN ' . $start . ' AND ' . $end;
                                        } else {
                                            $advfiltergroupsql .= (string) $tableColumnSql . ' NOT BETWEEN ' . $start . ' AND ' . $end;
                                        }
                                    } else {
                                        if ($comparator == 'bw') {
                                            $values = explode(',', $value);
                                            $startDateTime = explode(' ', $values[0]);
                                            $endDateTime = explode(' ', $values[1]);
                                            $startDateTime = new DateTimeField($startDateTime[0] . ' ' . date('H:i:s'));
                                            $userStartDate = $startDateTime->getDisplayDate();
                                            $userStartDate = $userStartDate . ' 00:00:00';
                                            $start = getValidDBInsertDateTimeValue($userStartDate);
                                            $endDateTime = new DateTimeField($endDateTime[0] . ' ' . date('H:i:s'));
                                            $userEndDate = $endDateTime->getDisplayDate();
                                            $userEndDate = $userEndDate . ' 23:59:59';
                                            $end = getValidDBInsertDateTimeValue($userEndDate);
                                            $advfiltergroupsql .= (string) $tableColumnSql . " BETWEEN '" . $start . "' AND '" . $end . "'";
                                        } else {
                                            if (in_array($comparator, Vtiger_Functions::getSpecialDateConditions())) {
                                                $values = EnhancedQueryGenerator::getSpecialDateConditionValue($comparator, $value, $selectedFields[4]);
                                                $tableColumnSql = $selectedFields[0] . '.' . $selectedFields[1];
                                                $condtionQuery = EnhancedQueryGenerator::getSpecialDateConditionQuery($values['comparator'], $values['date']);
                                                $advfiltergroupsql .= 'date(' . $tableColumnSql . ') ' . $condtionQuery;
                                            } else {
                                                if (in_array($comparator, Vtiger_Functions::getSpecialTimeConditions())) {
                                                    $values = EnhancedQueryGenerator::getSpecialDateConditionValue($comparator, $value, $selectedFields[4]);
                                                    $condtionQuery = EnhancedQueryGenerator::getSpecialDateConditionQuery($values['comparator'], $values['date']);
                                                    $advfiltergroupsql .= (string) $tableColumnSql . ' ' . $condtionQuery;
                                                } else {
                                                    if ($comparator == 'a' || $comparator == 'b') {
                                                        $value = explode(' ', $value);
                                                        $dateTime = new DateTimeImmutable($value[0]);
                                                        if ($comparator == 'a') {
                                                            $modifiedDate = $dateTime->modify('+1 days');
                                                            $nextday = $modifiedDate->format('Y-m-d H:i:s');
                                                            $temp = strtotime($nextday) - 1;
                                                            $date = date('Y-m-d H:i:s', $temp);
                                                            $value = getValidDBInsertDateTimeValue($date);
                                                            $advfiltergroupsql .= (string) $tableColumnSql . " > '" . $value . "'";
                                                        } else {
                                                            $prevday = $dateTime->format('Y-m-d H:i:s');
                                                            $temp = strtotime($prevday) - 1;
                                                            $date = date('Y-m-d H:i:s', $temp);
                                                            $value = getValidDBInsertDateTimeValue($date);
                                                            $advfiltergroupsql .= (string) $tableColumnSql . " < '" . $value . "'";
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if (!empty($columncondition)) {
                                        $advfiltergroupsql .= ' ' . $columncondition . ' ';
                                    }
                                    $this->queryPlanner->addTable($selectedFields[0]);
                                } else {
                                    if ($value == '') {
                                        $sqlComparator = $this->getAdvComparator($comparator, $value, 'DT');
                                        if ($sqlComparator) {
                                            $advfiltergroupsql .= ' ' . $selectedFields[0] . '.' . $selectedFields[1] . $sqlComparator;
                                        } else {
                                            $advfiltergroupsql .= ' ' . $selectedFields[0] . '.' . $selectedFields[1] . " = '' ";
                                        }
                                    }
                                }

                                continue;
                            }
                            $selectedfields = explode(':', $fieldcolname);
                            $moduleFieldLabel = $selectedfields[2];
                            [$moduleName, $fieldLabel] = explode('_', $moduleFieldLabel, 2);
                            $fieldInfo = getFieldByVReportLabel($moduleName, $selectedfields[3], 'name');
                            $concatSql = getSqlForNameInDisplayFormat(['first_name' => $selectedfields[0] . '.first_name', 'last_name' => $selectedfields[0] . '.last_name'], 'Users');
                            if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                $selectedfields[0] = 'vtiger_crmentity';
                            }
                            if ($selectedfields[4] == 'C') {
                                if (strcasecmp(trim($value), 'yes') == 0) {
                                    $value = '1';
                                }
                                if (strcasecmp(trim($value), 'no') == 0) {
                                    $value = '0';
                                }
                            }
                            if (in_array($comparator, $dateSpecificConditions)) {
                                $customView = new CustomView($moduleName);
                                $columninfo['stdfilter'] = $columninfo['comparator'];
                                $valueComponents = explode(',', $columninfo['value']);
                                if ($comparator == 'custom') {
                                    if ($selectedfields[4] == 'DT') {
                                        $startDateTimeComponents = explode(' ', $valueComponents[0]);
                                        $endDateTimeComponents = explode(' ', $valueComponents[1]);
                                        $columninfo['startdate'] = DateTimeField::convertToDBFormat($startDateTimeComponents[0]);
                                        $columninfo['enddate'] = DateTimeField::convertToDBFormat($endDateTimeComponents[0]);
                                    } else {
                                        $columninfo['startdate'] = DateTimeField::convertToDBFormat($valueComponents[0]);
                                        $columninfo['enddate'] = DateTimeField::convertToDBFormat($valueComponents[1]);
                                    }
                                }
                                $dateFilterResolvedList = $customView->resolveDateFilterValue($columninfo);
                                $startDate = DateTimeField::convertToDBFormat($dateFilterResolvedList['startdate']);
                                $endDate = DateTimeField::convertToDBFormat($dateFilterResolvedList['enddate']);
                                $columninfo['value'] = $value = implode(',', [$startDate, $endDate]);
                                $comparator = 'bw';
                            }
                            $datatype = $selectedfields[4] ?? '';
                            $fieldDataType = '';
                            $fields = [];
                            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
                            if ($moduleModel) {
                                $fields = $moduleModel->getFields();
                                if ($fields && $selectedfields[3]) {
                                    $fieldModel = $fields[$selectedfields[3]];
                                    if ($fieldModel) {
                                        $fieldDataType = $fieldModel->getFieldDataType();
                                    }
                                }
                            }
                            $commaSeparatedFieldTypes = ['picklist', 'multipicklist', 'owner', 'date', 'datetime', 'time'];
                            if (in_array($fieldDataType, $commaSeparatedFieldTypes)) {
                                $valuearray = explode(',', trim($value));
                            } else {
                                $valuearray = [$value];
                            }
                            if (isset($valuearray) && count($valuearray) > 1 && $comparator != 'bw') {
                                $advcolumnsql = '';
                                for ($n = 0; $n < count($valuearray); ++$n) {
                                    $secondaryModules = explode(':', $this->secondarymodule);
                                    [$firstSecondaryModule, $secondSecondaryModule] = $secondaryModules;
                                    if (($selectedfields[0] == 'vtiger_users' . $this->primarymodule || $firstSecondaryModule && $selectedfields[0] == 'vtiger_users' . $firstSecondaryModule || $secondSecondaryModule && $selectedfields[0] == 'vtiger_users' . $secondSecondaryModule) && $selectedfields[1] == 'user_name') {
                                        $module_from_tablename = str_replace('vtiger_users', '', $selectedfields[0]);
                                        $advcolsql[] = ' (trim(' . $concatSql . ')' . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype) . ' or vtiger_groups' . $module_from_tablename . '.groupname ' . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype) . ')';
                                        $this->queryPlanner->addTable('vtiger_groups' . $module_from_tablename);
                                    } else {
                                        if ($selectedfields[1] == 'status') {
                                            if ($selectedfields[2] == 'Calendar_Status') {
                                                $advcolsql[] = "(case when (vtiger_activity.status not like '') then vtiger_activity.status else vtiger_activity.eventstatus end)" . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype);
                                            } else {
                                                if ($selectedfields[2] == 'HelpDesk_Status') {
                                                    $advcolsql[] = 'vtiger_troubletickets.status' . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype);
                                                } else {
                                                    if ($selectedfields[2] == 'Faq_Status') {
                                                        $advcolsql[] = 'vtiger_faq.status' . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype);
                                                    } else {
                                                        $advcolsql[] = $selectedfields[0] . '.' . $selectedfields[1] . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype);
                                                    }
                                                }
                                            }
                                        } else {
                                            if ($selectedfields[1] == 'description') {
                                                if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                                    $advcolsql[] = 'vtiger_crmentity.description' . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype);
                                                } else {
                                                    $advcolsql[] = $selectedfields[0] . '.' . $selectedfields[1] . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype);
                                                }
                                            } else {
                                                if ($selectedfields[2] == 'Quotes_Inventory_Manager') {
                                                    $advcolsql[] = 'trim(' . $concatSql . ')' . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype);
                                                } else {
                                                    if ($selectedfields[1] == 'modifiedby') {
                                                        $module_from_tablename = str_replace('vtiger_crmentity', '', $selectedfields[0]);
                                                        if ($module_from_tablename != '') {
                                                            $tableName = 'vtiger_lastModifiedBy' . $module_from_tablename;
                                                        } else {
                                                            $tableName = 'vtiger_lastModifiedBy' . $this->primarymodule;
                                                        }
                                                        $advcolsql[] = 'trim(' . getSqlForNameInDisplayFormat(['last_name' => (string) $tableName . '.last_name', 'first_name' => (string) $tableName . '.first_name'], 'Users') . ')' . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype);
                                                    } else {
                                                        if ($selectedfields[1] == 'smcreatorid') {
                                                            $module_from_tablename = str_replace('vtiger_crmentity', '', $selectedfields[0]);
                                                            if ($module_from_tablename != '') {
                                                                $tableName = 'vtiger_createdby' . $module_from_tablename;
                                                            } else {
                                                                $tableName = 'vtiger_createdby' . $this->primarymodule;
                                                            }
                                                            if ($moduleName == 'ModComments') {
                                                                $tableName = 'vtiger_users' . $moduleName;
                                                            }
                                                            $this->queryPlanner->addTable($tableName);
                                                            $advcolsql[] = 'trim(' . getSqlForNameInDisplayFormat(['last_name' => (string) $tableName . '.last_name', 'first_name' => (string) $tableName . '.first_name'], 'Users') . ')' . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype);
                                                        } else {
                                                            $advcolsql[] = $selectedfields[0] . '.' . $selectedfields[1] . $this->getAdvComparator($comparator, trim($valuearray[$n]), $datatype);
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                if ($comparator == 'n' || $comparator == 'k') {
                                    $advcolumnsql = implode(' and ', $advcolsql);
                                } else {
                                    $advcolumnsql = implode(' or ', $advcolsql);
                                }
                                $fieldvalue = ' (' . $advcolumnsql . ') ';
                            } else {
                                if ($selectedfields[1] == 'user_name') {
                                    if ($selectedfields[0] == 'vtiger_users' . $this->primarymodule) {
                                        $module_from_tablename = str_replace('vtiger_users', '', $selectedfields[0]);
                                        $fieldvalue = ' trim(case when (' . $selectedfields[0] . ".last_name NOT LIKE '') then " . $concatSql . ' else vtiger_groups' . $module_from_tablename . '.groupname end) ' . $this->getAdvComparator($comparator, trim($value), $datatype);
                                        $this->queryPlanner->addTable('vtiger_groups' . $module_from_tablename);
                                    } else {
                                        $secondaryModules = explode(':', $this->secondarymodule);
                                        $firstSecondaryModule = 'vtiger_users' . $secondaryModules[0];
                                        $secondSecondaryModule = 'vtiger_users' . $secondaryModules[1];
                                        if ($firstSecondaryModule && $firstSecondaryModule == $selectedfields[0] || $secondSecondaryModule && $secondSecondaryModule == $selectedfields[0]) {
                                            $module_from_tablename = str_replace('vtiger_users', '', $selectedfields[0]);
                                            $moduleInstance = CRMEntity::getInstance($module_from_tablename);
                                            $fieldvalue = ' trim(case when (' . $selectedfields[0] . ".last_name NOT LIKE '') then " . $concatSql . ' else vtiger_groups' . $module_from_tablename . '.groupname end) ' . $this->getAdvComparator($comparator, trim($value), $datatype);
                                            $this->queryPlanner->addTable('vtiger_groups' . $module_from_tablename);
                                            $this->queryPlanner->addTable($moduleInstance->table_name);
                                        }
                                    }
                                } else {
                                    if ($comparator == 'bw' && count($valuearray) == 2) {
                                        if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                            $fieldvalue = '(vtiger_crmentity.' . $selectedfields[1] . " between '" . trim($valuearray[0]) . "' and '" . trim($valuearray[1]) . "')";
                                        } else {
                                            $fieldvalue = '(' . $selectedfields[0] . '.' . $selectedfields[1] . " between '" . trim($valuearray[0]) . "' and '" . trim($valuearray[1]) . "')";
                                        }
                                    } else {
                                        if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                                            $fieldvalue = 'vtiger_crmentity.' . $selectedfields[1] . ' ' . $this->getAdvComparator($comparator, trim($value), $datatype);
                                        } else {
                                            if ($selectedfields[2] == 'Quotes_Inventory_Manager') {
                                                $concatSql = getSqlForNameInDisplayFormat(['first_name' => 'vtiger_usersRel1.first_name', 'last_name' => 'vtiger_usersRel1.last_name'], 'Users');
                                                $fieldvalue = 'trim( ' . $concatSql . ' )' . $this->getAdvComparator($comparator, trim($value), $datatype);
                                                $this->queryPlanner->addTable('vtiger_usersRel1');
                                            } else {
                                                if ($selectedfields[1] == 'modifiedby') {
                                                    $module_from_tablename = str_replace('vtiger_crmentity', '', $selectedfields[0]);
                                                    if ($module_from_tablename != '') {
                                                        $tableName = 'vtiger_lastModifiedBy' . $module_from_tablename;
                                                    } else {
                                                        $tableName = 'vtiger_lastModifiedBy' . $this->primarymodule;
                                                    }
                                                    $this->queryPlanner->addTable($tableName);
                                                    $fieldvalue = 'trim(' . getSqlForNameInDisplayFormat(['last_name' => (string) $tableName . '.last_name', 'first_name' => (string) $tableName . '.first_name'], 'Users') . ')' . $this->getAdvComparator($comparator, trim($value), $datatype);
                                                } else {
                                                    if ($selectedfields[1] == 'smcreatorid') {
                                                        $module_from_tablename = str_replace('vtiger_crmentity', '', $selectedfields[0]);
                                                        if ($module_from_tablename != '') {
                                                            $tableName = 'vtiger_createdby' . $module_from_tablename;
                                                        } else {
                                                            $tableName = 'vtiger_createdby' . $this->primarymodule;
                                                        }
                                                        if ($moduleName == 'ModComments') {
                                                            $tableName = 'vtiger_users' . $moduleName;
                                                        }
                                                        $this->queryPlanner->addTable($tableName);
                                                        $fieldvalue = 'trim(' . getSqlForNameInDisplayFormat(['last_name' => (string) $tableName . '.last_name', 'first_name' => (string) $tableName . '.first_name'], 'Users') . ')' . $this->getAdvComparator($comparator, trim($value), $datatype);
                                                    } else {
                                                        if ($selectedfields[0] == 'vtiger_activity' && ($selectedfields[1] == 'status' || $selectedfields[1] == 'eventstatus')) {
                                                            if ($comparator == 'y') {
                                                                $fieldvalue = "(case when (vtiger_activity.status not like '') then vtiger_activity.status\n\t\t\t\t\t\t\t\t\t\t\t\t\telse vtiger_activity.eventstatus end) IS NULL OR (case when (vtiger_activity.status not like '')\n\t\t\t\t\t\t\t\t\t\t\t\t\tthen vtiger_activity.status else vtiger_activity.eventstatus end) = ''";
                                                            } else {
                                                                $fieldvalue = "(case when (vtiger_activity.status not like '') then vtiger_activity.status\n\t\t\t\t\t\t\t\t\t\t\t\t\telse vtiger_activity.eventstatus end)" . $this->getAdvComparator($comparator, trim($value), $datatype);
                                                            }
                                                        } else {
                                                            if ($comparator == 'ny') {
                                                                if ($fieldInfo['uitype'] == '10' || isReferenceUITypeVReport($fieldInfo['uitype'])) {
                                                                    $fieldvalue = '(' . $selectedfields[0] . '.' . $selectedfields[1] . ' IS NOT NULL AND ' . $selectedfields[0] . '.' . $selectedfields[1] . " != '' AND " . $selectedfields[0] . '.' . $selectedfields[1] . "  != '0')";
                                                                } else {
                                                                    $fieldvalue = '(' . $selectedfields[0] . '.' . $selectedfields[1] . ' IS NOT NULL AND ' . $selectedfields[0] . '.' . $selectedfields[1] . " != '')";
                                                                }
                                                            } else {
                                                                if ($comparator == 'y' || $comparator == 'e' && (trim($value) == 'NULL' || trim($value) == '')) {
                                                                    if ($selectedfields[0] == 'vtiger_inventoryproductrel') {
                                                                        $selectedfields[0] = 'vtiger_inventoryproductreltmp' . $moduleName;
                                                                    }
                                                                    if ($fieldInfo['uitype'] == '10' || isReferenceUITypeVReport($fieldInfo['uitype'])) {
                                                                        $fieldvalue = '(' . $selectedfields[0] . '.' . $selectedfields[1] . ' IS NULL OR ' . $selectedfields[0] . '.' . $selectedfields[1] . " = '' OR " . $selectedfields[0] . '.' . $selectedfields[1] . " = '0')";
                                                                    } else {
                                                                        $fieldvalue = '(' . $selectedfields[0] . '.' . $selectedfields[1] . ' IS NULL OR ' . $selectedfields[0] . '.' . $selectedfields[1] . " = '')";
                                                                    }
                                                                } else {
                                                                    if ($selectedfields[0] == 'vtiger_inventoryproductrel') {
                                                                        $selectedfields[0] = $selectedfields[0] . 'tmp';
                                                                        if ($selectedfields[1] == 'productid') {
                                                                            $fieldvalue = '(vtiger_products' . $moduleName . '.productname ' . $this->getAdvComparator($comparator, trim($value), $datatype);
                                                                            $fieldvalue .= ' OR vtiger_service' . $moduleName . '.servicename ' . $this->getAdvComparator($comparator, trim($value), $datatype);
                                                                            $fieldvalue .= ')';
                                                                            $this->queryPlanner->addTable('vtiger_products' . $moduleName);
                                                                            $this->queryPlanner->addTable('vtiger_service' . $moduleName);
                                                                        } else {
                                                                            $selectedfields[0] = 'vtiger_inventoryproductreltmp' . $moduleName;
                                                                            $fieldvalue = $selectedfields[0] . '.' . $selectedfields[1] . $this->getAdvComparator($comparator, $value, $datatype);
                                                                        }
                                                                    } else {
                                                                        if ($fieldInfo['uitype'] == '10' || isReferenceUITypeVReport($fieldInfo['uitype'])) {
                                                                            $fieldSqlColumns = $this->getReferenceFieldColumnList($moduleName, $fieldInfo);
                                                                            $comparatorValue = $this->getAdvComparator($comparator, trim($value), $datatype, $fieldSqlColumns[0]);
                                                                            $fieldSqls = [];
                                                                            foreach ($fieldSqlColumns as $columnSql) {
                                                                                $fieldSqls[] = $columnSql . $comparatorValue;
                                                                            }
                                                                            $fieldvalue = ' (' . implode(' OR ', $fieldSqls) . ') ';
                                                                        } else {
                                                                            if (in_array($comparator, Vtiger_Functions::getSpecialDateConditions())) {
                                                                                $values = EnhancedQueryGenerator::getSpecialDateConditionValue($comparator, $value, $selectedFields[4]);
                                                                                $tableColumnSql = $selectedFields[0] . '.' . $selectedFields[1];
                                                                                $condtionQuery = EnhancedQueryGenerator::getSpecialDateConditionQuery($values['comparator'], $values['date']);
                                                                                $fieldvalue = 'date(' . $tableColumnSql . ') ' . $condtionQuery;
                                                                            } else {
                                                                                if (in_array($comparator, Vtiger_Functions::getSpecialTimeConditions())) {
                                                                                    $values = EnhancedQueryGenerator::getSpecialDateConditionValue($comparator, $value, $selectedFields[4]);
                                                                                    $condtionQuery = EnhancedQueryGenerator::getSpecialDateConditionQuery($values['comparator'], $values['date']);
                                                                                    $fieldvalue = (string) $tableColumnSql . ' ' . $condtionQuery;
                                                                                } else {
                                                                                    $selectFieldTableName = $selectedfields[0];
                                                                                    if (!empty($emailTableName)) {
                                                                                        $selectFieldTableName = $emailTableName;
                                                                                    }
                                                                                    $fieldvalue = $selectFieldTableName . '.' . $selectedfields[1] . $this->getAdvComparator($comparator, trim($value), $datatype);
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $advfiltergroupsql .= $fieldvalue;
                            if (!empty($columncondition)) {
                                $advfiltergroupsql .= ' ' . $columncondition . ' ';
                            }
                            $this->queryPlanner->addTable($selectedfields[0]);
                        }
                    }
                    if (trim($advfiltergroupsql) != '') {
                        $advfiltergroupsql = '( ' . $advfiltergroupsql . ' ) ';
                        if (!empty($groupcondition)) {
                            $advfiltergroupsql .= ' ' . $groupcondition . ' ';
                        }
                        $advfiltersql .= $advfiltergroupsql;
                    }
                }
            }
        }
        if (trim($advfiltersql) != '') {
            $advfiltersql = '(' . $advfiltersql . ')';
        }

        return $advfiltersql;
    }

    public function getAdvFilterSql($reportid)
    {
        if ($this->_advfiltersql !== false) {
            return $this->_advfiltersql;
        }
        global $log;
        $advfilterlist = $this->getAdvFilterList($reportid);
        $advfiltersql = $this->generateAdvFilterSql($advfilterlist);
        $this->_advfiltersql = $advfiltersql;
        $log->info('ReportRun :: Successfully returned getAdvFilterSql' . $reportid);

        return $advfiltersql;
    }

    /** Function to get the Standard filter columns for the reportid
     *  This function accepts the $reportid datatype Integer
     *  This function returns  $stdfilterlist Array($columnname => $tablename:$columnname:$fieldlabel:$fieldname:$typeofdata=>$tablename.$columnname filtercriteria,
     * 					      $tablename1:$columnname1:$fieldlabel1:$fieldname1:$typeofdata1=>$tablename1.$columnname1 filtercriteria,
     * 				      	     ).
     */
    public function getStdFilterList($reportid)
    {
        if ($this->_stdfilterlist !== false) {
            return $this->_stdfilterlist;
        }
        global $adb;
        global $log;
        $stdfilterlist = [];
        $stdfiltersql = 'select vtiger_vreportdatefilter.* from vtiger_vreport';
        $stdfiltersql .= ' inner join vtiger_vreportdatefilter on vtiger_vreport.reportid = vtiger_vreportdatefilter.datefilterid';
        $stdfiltersql .= ' where vtiger_vreport.reportid = ?';
        $result = $adb->pquery($stdfiltersql, [$reportid]);
        $stdfilterrow = $adb->fetch_array($result);
        if (isset($stdfilterrow)) {
            $fieldcolname = $stdfilterrow['datecolumnname'];
            $datefilter = $stdfilterrow['datefilter'];
            $startdate = $stdfilterrow['startdate'];
            $enddate = $stdfilterrow['enddate'];
            if ($fieldcolname != 'none') {
                $selectedfields = explode(':', $fieldcolname);
                if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                    $selectedfields[0] = 'vtiger_crmentity';
                }
                $moduleFieldLabel = $selectedfields[3];
                [$moduleName, $fieldLabel] = explode('_', $moduleFieldLabel, 2);
                $fieldInfo = getFieldByVReportLabel($moduleName, $fieldLabel);
                $typeOfData = $fieldInfo['typeofdata'];
                [$type, $typeOtherInfo] = explode('~', $typeOfData, 2);
                if ($datefilter != 'custom') {
                    $startenddate = $this->getStandarFiltersStartAndEndDate($datefilter);
                    [$startdate, $enddate] = $startenddate;
                }
                if ($startdate != '0000-00-00' && $enddate != '0000-00-00' && $startdate != '' && $enddate != '' && $selectedfields[0] != '' && $selectedfields[1] != '') {
                    $startDateTime = new DateTimeField($startdate . ' ' . date('H:i:s'));
                    $userStartDate = $startDateTime->getDisplayDate();
                    if ($type == 'DT') {
                        $userStartDate = $userStartDate . ' 00:00:00';
                    }
                    $startDateTime = getValidDBInsertDateTimeValue($userStartDate);
                    $endDateTime = new DateTimeField($enddate . ' ' . date('H:i:s'));
                    $userEndDate = $endDateTime->getDisplayDate();
                    if ($type == 'DT') {
                        $userEndDate = $userEndDate . ' 23:59:00';
                    }
                    $endDateTime = getValidDBInsertDateTimeValue($userEndDate);
                    if ($selectedfields[1] == 'birthday') {
                        $tableColumnSql = 'DATE_FORMAT(' . $selectedfields[0] . '.' . $selectedfields[1] . ", '%m%d')";
                        $startDateTime = "DATE_FORMAT('" . $startDateTime . "', '%m%d')";
                        $endDateTime = "DATE_FORMAT('" . $endDateTime . "', '%m%d')";
                    } else {
                        if ($selectedfields[0] == 'vtiger_activity' && $selectedfields[1] == 'date_start') {
                            $tableColumnSql = '';
                            $tableColumnSql = "CAST((CONCAT(date_start,' ',time_start)) AS DATETIME)";
                        } else {
                            $tableColumnSql = $selectedfields[0] . '.' . $selectedfields[1];
                        }
                        $startDateTime = "'" . $startDateTime . "'";
                        $endDateTime = "'" . $endDateTime . "'";
                    }
                    $stdfilterlist[$fieldcolname] = $tableColumnSql . ' between ' . $startDateTime . ' and ' . $endDateTime;
                    $this->queryPlanner->addTable($selectedfields[0]);
                }
            }
        }
        $this->_stdfilterlist = $stdfilterlist;
        $log->info('ReportRun :: Successfully returned getStdFilterList' . $reportid);

        return $stdfilterlist;
    }

    /** Function to get the RunTime filter columns for the given $filtercolumn,$filter,$startdate,$enddate.
     *  @ param $filtercolumn : Type String
     *  @ param $filter : Type String
     *  @ param $startdate: Type String
     *  @ param $enddate : Type String
     *  This function returns  $stdfilterlist Array($columnname => $tablename:$columnname:$fieldlabel=>$tablename.$columnname 'between' $startdate 'and' $enddate)
     */
    public function RunTimeFilter($filtercolumn, $filter, $startdate, $enddate)
    {
        if ($filtercolumn != 'none') {
            $selectedfields = explode(':', $filtercolumn);
            if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                $selectedfields[0] = 'vtiger_crmentity';
            }
            if ($filter == 'custom') {
                if ($startdate != '0000-00-00' && $enddate != '0000-00-00' && $startdate != '' && $enddate != '' && $selectedfields[0] != '' && $selectedfields[1] != '') {
                    $stdfilterlist[$filtercolumn] = $selectedfields[0] . '.' . $selectedfields[1] . " between '" . $startdate . " 00:00:00' and '" . $enddate . " 23:59:00'";
                }
            } else {
                if ($startdate != '' && $enddate != '') {
                    $startenddate = $this->getStandarFiltersStartAndEndDate($filter);
                    if ($startenddate[0] != '' && $startenddate[1] != '' && $selectedfields[0] != '' && $selectedfields[1] != '') {
                        $stdfilterlist[$filtercolumn] = $selectedfields[0] . '.' . $selectedfields[1] . " between '" . $startenddate[0] . " 00:00:00' and '" . $startenddate[1] . " 23:59:00'";
                    }
                }
            }
        }

        return $stdfilterlist;
    }

    /** Function to get the RunTime Advanced filter conditions.
     *  @ param $advft_criteria : Type Array
     *  @ param $advft_criteria_groups : Type Array
     *  This function returns  $advfiltersql
     */
    public function RunTimeAdvFilter($advft_criterias, $advft_criteria_groupss)
    {
        $adb = PearDatabase::getInstance();
        $advfilterlist = [];
        $advfiltersql = '';
        if (!empty($advft_criterias)) {
            foreach ($advft_criterias as $groupParentIndex => $advft_criteria) {
                foreach ($advft_criteria as $column_index => $column_condition) {
                    if ($column_index === 'groupParentCondition') {
                        $advfilterlist[$groupParentIndex]['groupParentCondition'] = $column_condition;

                        continue;
                    }
                    if (empty($column_condition)) {
                        continue;
                    }
                    $adv_filter_column = $column_condition['columnname'];
                    $adv_filter_comparator = $column_condition['comparator'];
                    $adv_filter_value = $column_condition['value'];
                    $adv_filter_column_condition = $column_condition['columncondition'];
                    $adv_filter_groupid = $column_condition['groupid'];
                    $column_info = explode(':', $adv_filter_column);
                    [$moduleFieldLabel, $fieldName] = $column_info;
                    [$module, $fieldLabel] = explode('_', $moduleFieldLabel, 2);
                    $fieldInfo = getFieldByVReportLabel($module, $fieldLabel);
                    $fieldType = null;
                    if (!empty($fieldInfo)) {
                        $field = WebserviceField::fromArray($adb, $fieldInfo);
                        $fieldType = $field->getFieldDataType();
                    }
                    if ($fieldType == 'currency') {
                        if ($field->getUIType() == '72') {
                            $adv_filter_value = CurrencyField::convertToDBFormat($adv_filter_value, null, true);
                        } else {
                            $adv_filter_value = CurrencyField::convertToDBFormat($adv_filter_value);
                        }
                    }
                    $specialDateConditions = Vtiger_Functions::getSpecialDateTimeCondtions();
                    $temp_val = explode(',', $adv_filter_value);
                    if (($column_info[4] == 'D' || $column_info[4] == 'T' && $column_info[1] != 'time_start' && $column_info[1] != 'time_end' || $column_info[4] == 'DT') && $column_info[4] != '' && $adv_filter_value != '' && !in_array($adv_filter_comparator, $specialDateConditions)) {
                        $val = [];
                        for ($x = 0; $x < count($temp_val); ++$x) {
                            if ($column_info[4] == 'D') {
                                $date = new DateTimeField(trim($temp_val[$x]));
                                $val[$x] = $date->getDBInsertDateValue();
                            } else {
                                if ($column_info[4] == 'DT') {
                                    $date = new DateTimeField(trim($temp_val[$x]));
                                    $val[$x] = $date->getDBInsertDateTimeValue();
                                } else {
                                    if ($fieldType == 'time') {
                                        $val[$x] = Vtiger_Time_UIType::getTimeValueWithSeconds($temp_val[$x]);
                                    } else {
                                        $date = new DateTimeField(trim($temp_val[$x]));
                                        $val[$x] = $date->getDBInsertTimeValue();
                                    }
                                }
                            }
                        }
                        $adv_filter_value = implode(',', $val);
                    }
                    $criteria = [];
                    $criteria['columnname'] = $adv_filter_column;
                    $criteria['comparator'] = $adv_filter_comparator;
                    $criteria['value'] = $adv_filter_value;
                    $criteria['column_condition'] = $adv_filter_column_condition;
                    $advfilterlist[$groupParentIndex][$adv_filter_groupid]['columns'][] = $criteria;
                }
            }
            foreach ($advft_criteria_groupss as $group_groupParent_index => $advft_criteria_groups) {
                foreach ($advft_criteria_groups as $group_index => $group_condition_info) {
                    if (empty($group_condition_info)) {
                        continue;
                    }
                    if (empty($advfilterlist[$group_groupParent_index][$group_index])) {
                        continue;
                    }
                    $advfilterlist[$group_groupParent_index][$group_index]['condition'] = $group_condition_info['groupcondition'];
                    $noOfGroupColumns = count($advfilterlist[$group_groupParent_index][$group_index]['columns']);
                    if (!empty($advfilterlist[$group_groupParent_index][$group_index]['columns'][$noOfGroupColumns - 1]['column_condition'])) {
                        $advfilterlist[$group_groupParent_index][$group_index]['columns'][$noOfGroupColumns - 1]['column_condition'] = '';
                    }
                }
            }
            $noOfGroups = count($advfilterlist);
            $advfiltersql = $this->generateAdvFilterSql($advfilterlist);
        }

        return $advfiltersql;
    }

    /** Function to get standardfilter for the given reportid.
     *  @ param $reportid : Type Integer
     *  returns the query of columnlist for the selected columns
     */
    public function getStandardCriterialSql($reportid)
    {
        global $adb;
        global $modules;
        global $log;
        $sreportstdfiltersql = 'select vtiger_vreportdatefilter.* from vtiger_vreport';
        $sreportstdfiltersql .= ' inner join vtiger_vreportdatefilter on vtiger_vreport.reportid = vtiger_vreportdatefilter.datefilterid';
        $sreportstdfiltersql .= ' where vtiger_vreport.reportid = ?';
        $result = $adb->pquery($sreportstdfiltersql, [$reportid]);
        $noofrows = $adb->num_rows($result);
        for ($i = 0; $i < $noofrows; ++$i) {
            $fieldcolname = $adb->query_result($result, $i, 'datecolumnname');
            $datefilter = $adb->query_result($result, $i, 'datefilter');
            $startdate = $adb->query_result($result, $i, 'startdate');
            $enddate = $adb->query_result($result, $i, 'enddate');
            if ($fieldcolname != 'none') {
                $selectedfields = explode(':', $fieldcolname);
                if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                    $selectedfields[0] = 'vtiger_crmentity';
                }
                if ($datefilter == 'custom') {
                    if ($startdate != '0000-00-00' && $enddate != '0000-00-00' && $selectedfields[0] != '' && $selectedfields[1] != '' && $startdate != '' && $enddate != '') {
                        $startDateTime = new DateTimeField($startdate . ' ' . date('H:i:s'));
                        $startdate = $startDateTime->getDisplayDate();
                        $endDateTime = new DateTimeField($enddate . ' ' . date('H:i:s'));
                        $enddate = $endDateTime->getDisplayDate();
                        $sSQL .= $selectedfields[0] . '.' . $selectedfields[1] . " between '" . $startdate . "' and '" . $enddate . "'";
                    }
                } else {
                    $startenddate = $this->getStandarFiltersStartAndEndDate($datefilter);
                    $startDateTime = new DateTimeField($startenddate[0] . ' ' . date('H:i:s'));
                    $startdate = $startDateTime->getDisplayDate();
                    $endDateTime = new DateTimeField($startenddate[1] . ' ' . date('H:i:s'));
                    $enddate = $endDateTime->getDisplayDate();
                    if ($startenddate[0] != '' && $startenddate[1] != '' && $selectedfields[0] != '' && $selectedfields[1] != '') {
                        $sSQL .= $selectedfields[0] . '.' . $selectedfields[1] . " between '" . $startdate . "' and '" . $enddate . "'";
                    }
                }
            }
        }
        $log->info('ReportRun :: Successfully returned getStandardCriterialSql' . $reportid);

        return $sSQL;
    }

    /** Function to get standardfilter startdate and enddate for the given type.
     *  @ param $type : Type String
     *  returns the $datevalue Array in the given format
     * 		$datevalue = Array(0=>$startdate,1=>$enddate)
     */
    public function getStandarFiltersStartAndEndDate($type)
    {
        global $current_user;
        $userPeferredDayOfTheWeek = $current_user->column_fields['dayoftheweek'];
        $today = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d'), date('Y')));
        $todayName = date('l', strtotime($today));
        $tomorrow = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')));
        $yesterday = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')));
        $currentmonth0 = date('Y-m-d', mktime(0, 0, 0, date('m'), '01', date('Y')));
        $currentmonth1 = date('Y-m-t');
        $lastmonth0 = date('Y-m-d', mktime(0, 0, 0, date('m') - 1, '01', date('Y')));
        $lastmonth1 = date('Y-m-t', strtotime('-1 Month'));
        $nextmonth0 = date('Y-m-d', mktime(0, 0, 0, date('m') + 1, '01', date('Y')));
        $nextmonth1 = date('Y-m-t', strtotime('+1 Month'));
        if ($todayName == $userPeferredDayOfTheWeek) {
            $lastweek0 = date('Y-m-d', strtotime('-1 week ' . $userPeferredDayOfTheWeek));
        } else {
            $lastweek0 = date('Y-m-d', strtotime('-2 week ' . $userPeferredDayOfTheWeek));
        }
        $prvDay = date('l', strtotime(date('Y-m-d', strtotime('-1 day', strtotime($lastweek0)))));
        $lastweek1 = date('Y-m-d', strtotime('-1 week ' . $prvDay));
        if ($todayName == $userPeferredDayOfTheWeek) {
            $thisweek0 = date('Y-m-d', strtotime('-0 week ' . $userPeferredDayOfTheWeek));
        } else {
            $thisweek0 = date('Y-m-d', strtotime('-1 week ' . $userPeferredDayOfTheWeek));
        }
        $prvDay = date('l', strtotime(date('Y-m-d', strtotime('-1 day', strtotime($thisweek0)))));
        $thisweek1 = date('Y-m-d', strtotime('this ' . $prvDay));
        if ($todayName == $userPeferredDayOfTheWeek) {
            $nextweek0 = date('Y-m-d', strtotime('+1 week ' . $userPeferredDayOfTheWeek));
        } else {
            $nextweek0 = date('Y-m-d', strtotime('this ' . $userPeferredDayOfTheWeek));
        }
        $prvDay = date('l', strtotime(date('Y-m-d', strtotime('-1 day', strtotime($nextweek0)))));
        $nextweek1 = date('Y-m-d', strtotime('+1 week ' . $prvDay));
        $next7days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 6, date('Y')));
        $next30days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 29, date('Y')));
        $next60days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 59, date('Y')));
        $next90days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 89, date('Y')));
        $next120days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 119, date('Y')));
        $last7days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 6, date('Y')));
        $last14days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 13, date('Y')));
        $last30days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 29, date('Y')));
        $last60days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 59, date('Y')));
        $last90days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 89, date('Y')));
        $last120days = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - 119, date('Y')));
        $currentFY0 = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y')));
        $currentFY1 = date('Y-m-t', mktime(0, 0, 0, '12', date('d'), date('Y')));
        $lastFY0 = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y') - 1));
        $lastFY1 = date('Y-m-t', mktime(0, 0, 0, '12', date('d'), date('Y') - 1));
        $nextFY0 = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y') + 1));
        $nextFY1 = date('Y-m-t', mktime(0, 0, 0, '12', date('d'), date('Y') + 1));
        if (date('m') <= 3) {
            $cFq = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y')));
            $cFq1 = date('Y-m-d', mktime(0, 0, 0, '03', '31', date('Y')));
            $nFq = date('Y-m-d', mktime(0, 0, 0, '04', '01', date('Y')));
            $nFq1 = date('Y-m-d', mktime(0, 0, 0, '06', '30', date('Y')));
            $pFq = date('Y-m-d', mktime(0, 0, 0, '10', '01', date('Y') - 1));
            $pFq1 = date('Y-m-d', mktime(0, 0, 0, '12', '31', date('Y') - 1));
        } else {
            if (date('m') > 3 && date('m') <= 6) {
                $pFq = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y')));
                $pFq1 = date('Y-m-d', mktime(0, 0, 0, '03', '31', date('Y')));
                $cFq = date('Y-m-d', mktime(0, 0, 0, '04', '01', date('Y')));
                $cFq1 = date('Y-m-d', mktime(0, 0, 0, '06', '30', date('Y')));
                $nFq = date('Y-m-d', mktime(0, 0, 0, '07', '01', date('Y')));
                $nFq1 = date('Y-m-d', mktime(0, 0, 0, '09', '30', date('Y')));
            } else {
                if (date('m') > 6 && date('m') <= 9) {
                    $nFq = date('Y-m-d', mktime(0, 0, 0, '10', '01', date('Y')));
                    $nFq1 = date('Y-m-d', mktime(0, 0, 0, '12', '31', date('Y')));
                    $pFq = date('Y-m-d', mktime(0, 0, 0, '04', '01', date('Y')));
                    $pFq1 = date('Y-m-d', mktime(0, 0, 0, '06', '30', date('Y')));
                    $cFq = date('Y-m-d', mktime(0, 0, 0, '07', '01', date('Y')));
                    $cFq1 = date('Y-m-d', mktime(0, 0, 0, '09', '30', date('Y')));
                } else {
                    if (date('m') > 9 && date('m') <= 12) {
                        $nFq = date('Y-m-d', mktime(0, 0, 0, '01', '01', date('Y') + 1));
                        $nFq1 = date('Y-m-d', mktime(0, 0, 0, '03', '31', date('Y') + 1));
                        $pFq = date('Y-m-d', mktime(0, 0, 0, '07', '01', date('Y')));
                        $pFq1 = date('Y-m-d', mktime(0, 0, 0, '09', '30', date('Y')));
                        $cFq = date('Y-m-d', mktime(0, 0, 0, '10', '01', date('Y')));
                        $cFq1 = date('Y-m-d', mktime(0, 0, 0, '12', '31', date('Y')));
                    }
                }
            }
        }
        if ($type == 'today') {
            $datevalue[0] = $today;
            $datevalue[1] = $today;
        } else {
            if ($type == 'yesterday') {
                $datevalue[0] = $yesterday;
                $datevalue[1] = $yesterday;
            } else {
                if ($type == 'tomorrow') {
                    $datevalue[0] = $tomorrow;
                    $datevalue[1] = $tomorrow;
                } else {
                    if ($type == 'thisweek') {
                        $datevalue[0] = $thisweek0;
                        $datevalue[1] = $thisweek1;
                    } else {
                        if ($type == 'lastweek') {
                            $datevalue[0] = $lastweek0;
                            $datevalue[1] = $lastweek1;
                        } else {
                            if ($type == 'nextweek') {
                                $datevalue[0] = $nextweek0;
                                $datevalue[1] = $nextweek1;
                            } else {
                                if ($type == 'thismonth') {
                                    $datevalue[0] = $currentmonth0;
                                    $datevalue[1] = $currentmonth1;
                                } else {
                                    if ($type == 'lastmonth') {
                                        $datevalue[0] = $lastmonth0;
                                        $datevalue[1] = $lastmonth1;
                                    } else {
                                        if ($type == 'nextmonth') {
                                            $datevalue[0] = $nextmonth0;
                                            $datevalue[1] = $nextmonth1;
                                        } else {
                                            if ($type == 'next7days') {
                                                $datevalue[0] = $today;
                                                $datevalue[1] = $next7days;
                                            } else {
                                                if ($type == 'next30days') {
                                                    $datevalue[0] = $today;
                                                    $datevalue[1] = $next30days;
                                                } else {
                                                    if ($type == 'next60days') {
                                                        $datevalue[0] = $today;
                                                        $datevalue[1] = $next60days;
                                                    } else {
                                                        if ($type == 'next90days') {
                                                            $datevalue[0] = $today;
                                                            $datevalue[1] = $next90days;
                                                        } else {
                                                            if ($type == 'next120days') {
                                                                $datevalue[0] = $today;
                                                                $datevalue[1] = $next120days;
                                                            } else {
                                                                if ($type == 'last7days') {
                                                                    $datevalue[0] = $last7days;
                                                                    $datevalue[1] = $today;
                                                                } else {
                                                                    if ($type == 'last14days') {
                                                                        $datevalue[0] = $last14days;
                                                                        $datevalue[1] = $today;
                                                                    } else {
                                                                        if ($type == 'last30days') {
                                                                            $datevalue[0] = $last30days;
                                                                            $datevalue[1] = $today;
                                                                        } else {
                                                                            if ($type == 'last60days') {
                                                                                $datevalue[0] = $last60days;
                                                                                $datevalue[1] = $today;
                                                                            } else {
                                                                                if ($type == 'last90days') {
                                                                                    $datevalue[0] = $last90days;
                                                                                    $datevalue[1] = $today;
                                                                                } else {
                                                                                    if ($type == 'last120days') {
                                                                                        $datevalue[0] = $last120days;
                                                                                        $datevalue[1] = $today;
                                                                                    } else {
                                                                                        if ($type == 'thisfy') {
                                                                                            $datevalue[0] = $currentFY0;
                                                                                            $datevalue[1] = $currentFY1;
                                                                                        } else {
                                                                                            if ($type == 'prevfy') {
                                                                                                $datevalue[0] = $lastFY0;
                                                                                                $datevalue[1] = $lastFY1;
                                                                                            } else {
                                                                                                if ($type == 'nextfy') {
                                                                                                    $datevalue[0] = $nextFY0;
                                                                                                    $datevalue[1] = $nextFY1;
                                                                                                } else {
                                                                                                    if ($type == 'nextfq') {
                                                                                                        $datevalue[0] = $nFq;
                                                                                                        $datevalue[1] = $nFq1;
                                                                                                    } else {
                                                                                                        if ($type == 'prevfq') {
                                                                                                            $datevalue[0] = $pFq;
                                                                                                            $datevalue[1] = $pFq1;
                                                                                                        } else {
                                                                                                            if ($type == 'thisfq') {
                                                                                                                $datevalue[0] = $cFq;
                                                                                                                $datevalue[1] = $cFq1;
                                                                                                            } else {
                                                                                                                $datevalue[0] = '';
                                                                                                                $datevalue[1] = '';
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $datevalue;
    }

    public function hasGroupingList()
    {
        global $adb;
        $result = $adb->pquery('SELECT 1 FROM vtiger_vreportsortcol WHERE reportid=? and columnname <> "none"', [$this->reportid]);

        return $result && $adb->num_rows($result) ? true : false;
    }

    /** Function to get getGroupingList for the given reportid.
     *  @ param $reportid : Type Integer
     *  returns the $grouplist Array in the following format
     *  		$grouplist = Array($tablename:$columnname:$fieldlabel:fieldname:typeofdata=>$tablename:$columnname $sorder,
     * 				   $tablename1:$columnname1:$fieldlabel1:fieldname1:typeofdata1=>$tablename1:$columnname1 $sorder,
     * 				   $tablename2:$columnname2:$fieldlabel2:fieldname2:typeofdata2=>$tablename2:$columnname2 $sorder)
     * This function also sets the return value in the class variable $this->groupbylist
     */
    public function getGroupingList($reportid)
    {
        global $adb;
        global $modules;
        global $log;
        if ($this->_groupinglist !== false) {
            return $this->_groupinglist;
        }
        $primaryModule = $this->primarymodule;
        $sreportsortsql = ' SELECT vtiger_vreportsortcol.*, vtiger_vreportgroupbycolumn.* FROM vtiger_vreport';
        $sreportsortsql .= ' inner join vtiger_vreportsortcol on vtiger_vreport.reportid = vtiger_vreportsortcol.reportid';
        $sreportsortsql .= ' LEFT JOIN vtiger_vreportgroupbycolumn ON (vtiger_vreport.reportid = vtiger_vreportgroupbycolumn.reportid AND vtiger_vreportsortcol.sortcolid = vtiger_vreportgroupbycolumn.sortid)';
        $sreportsortsql .= ' where vtiger_vreport.reportid =? AND vtiger_vreportsortcol.columnname IN (SELECT columnname from vtiger_selectcolumn WHERE queryid=?) order by vtiger_vreportsortcol.sortcolid';
        $result = $adb->pquery($sreportsortsql, [$reportid, $reportid]);
        $grouplist = [];
        $inventoryModules = getInventoryModules();

        while ($reportsortrow = $adb->fetch_array($result)) {
            $fieldcolname = $reportsortrow['columnname'];
            [$tablename, $colname, $module_field, $fieldname, $single] = explode(':', $fieldcolname);
            $sortorder = $reportsortrow['sortorder'];
            if ($sortorder == 'Ascending') {
                $sortorder = 'ASC';
            } else {
                if ($sortorder == 'Descending') {
                    $sortorder = 'DESC';
                }
            }
            if ($fieldcolname != 'none') {
                $selectedfields = explode(':', $fieldcolname);
                if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                    $selectedfields[0] = 'vtiger_crmentity';
                }
                if ($selectedfields[0] == 'vtiger_inventoryproductrel') {
                    [$moduleName, $field] = explode('_', $selectedfields[2], 2);
                    $selectedfields[0] = $selectedfields[0] . $moduleName;
                }
                if ($selectedfields[0] == 'vtiger_pricebookproductrel') {
                    [$moduleName, $field] = explode('_', $selectedfields[2], 2);
                    $selectedfields[0] = $selectedfields[0] . 'tmp' . $moduleName;
                }
                $sqlvalue = $selectedfields[0] . '.' . $selectedfields[1] . ' ' . $sortorder;
                if ($selectedfields[4] == 'D' && strtolower($reportsortrow['dategroupbycriteria']) != 'none') {
                    $groupField = $module_field;
                    $groupCriteria = $reportsortrow['dategroupbycriteria'];
                    if (in_array($groupCriteria, array_keys($this->groupByTimeParent))) {
                        $parentCriteria = $this->groupByTimeParent[$groupCriteria];
                        foreach ($parentCriteria as $criteria) {
                            $groupByCondition[] = $this->GetTimeCriteriaCondition($criteria, $groupField) . ' ' . $sortorder;
                        }
                    }
                    $groupByCondition[] = $this->GetTimeCriteriaCondition($groupCriteria, $groupField) . ' ' . $sortorder;
                    $sqlvalue = implode(', ', $groupByCondition);
                }
                $fieldModuleName = explode('_', $module_field);
                $fieldId = getFieldid(getTabid($fieldModuleName[0]), $fieldname);
                $fieldModel = Vtiger_Field_Model::getInstance($fieldId);
                if ($fieldModel && ($fieldModel->getFieldDataType() == 'reference' || $fieldModel->getFieldDataType() == 'owner')) {
                    $sqlvalue = $module_field . ' ' . $sortorder;
                }
                $grouplist[$fieldcolname] = $sqlvalue;
                $temp = explode('_', $selectedfields[2], 2);
                $module = $temp[0];
                if (in_array($module, $inventoryModules) && $fieldname == 'serviceid') {
                    $grouplist[$fieldcolname] = $sqlvalue;
                } else {
                    if ($primaryModule == 'PriceBooks' && $fieldname == 'listprice' && in_array($module, ['Products', 'Services'])) {
                        $grouplist[$fieldcolname] = $sqlvalue;
                    } else {
                        if (CheckFieldPermission($fieldname, $module) == 'true') {
                            $grouplist[$fieldcolname] = $sqlvalue;
                        } else {
                            $grouplist[$fieldcolname] = $selectedfields[0] . '.' . $selectedfields[1];
                        }
                    }
                }
                $this->queryPlanner->addTable($tablename);
            }
        }
        $this->_groupinglist = $grouplist;
        $log->info('ReportRun :: Successfully returned getGroupingList' . $reportid);

        return $grouplist;
    }

    /** function to replace special characters.
     *  @ param $selectedfield : type string
     *  this returns the string for grouplist
     */
    public function replaceSpecialChar($selectedfield)
    {
        $selectedfield = decode_html(decode_html($selectedfield));
        preg_match('/&/', $selectedfield, $matches);
        if (!empty($matches)) {
            $selectedfield = str_replace('&', 'and', $selectedfield);
        }

        return $selectedfield;
    }

    /** function to get the selectedorderbylist for the given reportid.
     *  @ param $reportid : type integer
     *  this returns the columns query for the sortorder columns
     *  this function also sets the return value in the class variable $this->orderbylistsql
     */
    public function getSelectedOrderbyList($reportid)
    {
        global $adb;
        global $modules;
        global $log;
        $sreportsortsql = 'select vtiger_vreportsortcol.* from vtiger_vreport';
        $sreportsortsql .= ' inner join vtiger_vreportsortcol on vtiger_vreport.reportid = vtiger_vreportsortcol.reportid';
        $sreportsortsql .= ' where vtiger_vreport.reportid =? order by vtiger_vreportsortcol.sortcolid';
        $result = $adb->pquery($sreportsortsql, [$reportid]);
        $noofrows = $adb->num_rows($result);
        for ($i = 0; $i < $noofrows; ++$i) {
            $fieldcolname = $adb->query_result($result, $i, 'columnname');
            $sortorder = $adb->query_result($result, $i, 'sortorder');
            if ($sortorder == 'Ascending') {
                $sortorder = 'ASC';
            } else {
                if ($sortorder == 'Descending') {
                    $sortorder = 'DESC';
                }
            }
            if ($fieldcolname != 'none') {
                $this->orderbylistcolumns[] = $fieldcolname;
                $n = $n + 1;
                $selectedfields = explode(':', $fieldcolname);
                if ($n > 1) {
                    $sSQL .= ', ';
                    $this->orderbylistsql .= ', ';
                }
                if ($selectedfields[0] == 'vtiger_crmentity' . $this->primarymodule) {
                    $selectedfields[0] = 'vtiger_crmentity';
                }
                $sSQL .= $selectedfields[0] . '.' . $selectedfields[1] . ' ' . $sortorder;
                $this->orderbylistsql .= $selectedfields[0] . '.' . $selectedfields[1] . ' ' . $selectedfields[2];
            }
        }
        $log->info('ReportRun :: Successfully returned getSelectedOrderbyList' . $reportid);

        return $sSQL;
    }

    /** function to get secondary Module for the given Primary module and secondary module.
     *  @ param $module : type String
     *  @ param $secmodule : type String
     *  this returns join query for the given secondary module
     */
    public function getRelatedModulesQuery($module, $secmodule)
    {
        global $log;
        global $current_user;
        $query = '';
        if ($secmodule != '') {
            $secondarymodule = explode(':', $secmodule);
            foreach ($secondarymodule as $key => $value) {
                if (!Vtiger_Module_Model::getInstance($value)) {
                    continue;
                }
                $foc = CRMEntity::getInstance($value);
                $this->queryPlanner->addTable('vtiger_crmentity' . $value);
                $focQuery = $foc->generateReportsSecQuery($module, $value, $this->queryPlanner);
                if ($value == 'ModComments') {
                    $focQuery .= ' LEFT JOIN vtiger_modcommentscf ON vtiger_modcommentscf.modcommentsid = vtiger_modcomments.modcommentsid';
                }
                if ($focQuery) {
                    if (count($secondarymodule) > 1) {
                        $query .= $focQuery . $this->getVReportsNonAdminAccessControlQuery($value, $current_user, $value);
                    } else {
                        $query .= $focQuery . getNonAdminAccessControlQuery($value, $current_user, $value);
                    }
                }
            }
            if ($this->queryPlanner->requireTable('vtiger_inventoryproductreltmp' . $value) && stripos($query, 'join vtiger_inventoryproductrel') === false) {
                $query .= ' LEFT JOIN vtiger_inventoryproductrel AS vtiger_inventoryproductreltmp' . $value . ' ON vtiger_inventoryproductreltmp' . $value . '.id = ' . $foc->table_name . '.' . $foc->table_index . ' ';
            }
        }
        $log->info('ReportRun :: Successfully returned getRelatedModulesQuery' . $secmodule);

        return $query;
    }

    /**
     * Non admin user not able to see the records of report even he has permission
     * Fix for Case :- Report with One Primary Module, and Two Secondary modules, let's say for one of the
     * secondary module, non-admin user don't have permission, then reports is not showing the record even
     * the user has permission for another seconday module.
     * @param type $module
     * @param type $user
     * @param type $scope
     * @return $query
     */
    public function getVReportsNonAdminAccessControlQuery($module, $user, $scope = '')
    {
        require 'user_privileges/user_privileges_' . $user->id . '.php';
        require 'user_privileges/sharing_privileges_' . $user->id . '.php';
        $query = ' ';
        $tabId = getTabid($module);
        if ($is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1 && $defaultOrgSharingPermission[$tabId] == 3) {
            $sharingRuleInfoVariable = $module . '_share_read_permission';
            $sharingRuleInfo = ${$sharingRuleInfoVariable};
            $sharedTabId = null;
            if ($module == 'Calendar') {
                $sharedTabId = $tabId;
                $tableName = 'vt_tmp_u' . $user->id . '_t' . $tabId;
            } else {
                if (!empty($sharingRuleInfo) && (count($sharingRuleInfo['ROLE']) > 0 || count($sharingRuleInfo['GROUP']) > 0)) {
                    $sharedTabId = $tabId;
                }
            }
            if (!empty($sharedTabId)) {
                $module = getTabModuleName($sharedTabId);
                if ($module == 'Calendar') {
                    $moduleInstance = CRMEntity::getInstance($module);
                    $query = $moduleInstance->getVReportsNonAdminAccessControlQuery($tableName, $tabId, $user, $current_user_parent_role_seq, $current_user_groups);
                } else {
                    $query = $this->getNonAdminAccessQuery($module, $user, $current_user_parent_role_seq, $current_user_groups);
                }
                $db = PearDatabase::getInstance();
                $result = $db->pquery($query, []);
                $rows = $db->num_rows($result);
                for ($i = 0; $i < $rows; ++$i) {
                    $ids[] = $db->query_result($result, $i, 'id');
                }
                if (!empty($ids)) {
                    $query = ' AND vtiger_crmentity' . $scope . '.smownerid IN (' . implode(',', $ids) . ') ';
                }
            }
        }

        return $query;
    }

    /** function to get report query for the given module.
     *  @ param $module : type String
     *  this returns join query for the given module
     */
    public function getVReportsQuery($module, $type = '')
    {
        global $log;
        global $current_user;
        global $adb;
        $secondary_module = "'";
        $secondary_module .= str_replace(':', "','", $this->secondarymodule);
        $secondary_module .= "'";
        if ($module == 'Leads') {
            $query = "from vtiger_leaddetails\n\t\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_leaddetails.leadid";
            if ($this->queryPlanner->requireTable('vtiger_leadsubdetails')) {
                $query .= "\tinner join vtiger_leadsubdetails on vtiger_leadsubdetails.leadsubscriptionid=vtiger_leaddetails.leadid";
            }
            if ($this->queryPlanner->requireTable('vtiger_leadaddress')) {
                $query .= "\tinner join vtiger_leadaddress on vtiger_leadaddress.leadaddressid=vtiger_leaddetails.leadid";
            }
            if ($this->queryPlanner->requireTable('vtiger_leadscf')) {
                $query .= ' inner join vtiger_leadscf on vtiger_leaddetails.leadid = vtiger_leadscf.leadid';
            }
            if ($this->queryPlanner->requireTable('vtiger_groupsLeads')) {
                $query .= "\tleft join vtiger_groups as vtiger_groupsLeads on vtiger_groupsLeads.groupid = vtiger_crmentity.smownerid";
            }
            if ($this->queryPlanner->requireTable('vtiger_usersLeads')) {
                $query .= ' left join vtiger_users as vtiger_usersLeads on vtiger_usersLeads.id = vtiger_crmentity.smownerid';
            }
            $query .= " left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\t\tleft join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid";
            if ($this->queryPlanner->requireTable('vtiger_lastModifiedByLeads')) {
                $query .= ' left join vtiger_users as vtiger_lastModifiedByLeads on vtiger_lastModifiedByLeads.id = vtiger_crmentity.modifiedby';
            }
            if ($this->queryPlanner->requireTable('vtiger_createdbyLeads')) {
                $query .= ' left join vtiger_users as vtiger_createdbyLeads on vtiger_createdbyLeads.id = vtiger_crmentity.smcreatorid';
            }
            $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
            $query .= $relquery . ' ';
            $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0 and vtiger_leaddetails.converted=0';
        } else {
            if ($module == 'Accounts') {
                $query = "from vtiger_account\n\t\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_account.accountid";
                if ($this->queryPlanner->requireTable('vtiger_accountbillads')) {
                    $query .= ' inner join vtiger_accountbillads on vtiger_account.accountid=vtiger_accountbillads.accountaddressid';
                }
                if ($this->queryPlanner->requireTable('vtiger_accountshipads')) {
                    $query .= ' inner join vtiger_accountshipads on vtiger_account.accountid=vtiger_accountshipads.accountaddressid';
                }
                if ($this->queryPlanner->requireTable('vtiger_accountscf')) {
                    $query .= ' inner join vtiger_accountscf on vtiger_account.accountid = vtiger_accountscf.accountid';
                }
                if ($this->queryPlanner->requireTable('vtiger_groupsAccounts')) {
                    $query .= ' left join vtiger_groups as vtiger_groupsAccounts on vtiger_groupsAccounts.groupid = vtiger_crmentity.smownerid';
                }
                if ($this->queryPlanner->requireTable('vtiger_accountAccounts')) {
                    $query .= "\tleft join vtiger_account as vtiger_accountAccounts on vtiger_accountAccounts.accountid = vtiger_account.parentid";
                }
                if ($this->queryPlanner->requireTable('vtiger_usersAccounts')) {
                    $query .= ' left join vtiger_users as vtiger_usersAccounts on vtiger_usersAccounts.id = vtiger_crmentity.smownerid';
                }
                $query .= " left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid\n\t\t\t\tleft join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid";
                if ($this->queryPlanner->requireTable('vtiger_lastModifiedByAccounts')) {
                    $query .= ' left join vtiger_users as vtiger_lastModifiedByAccounts on vtiger_lastModifiedByAccounts.id = vtiger_crmentity.modifiedby';
                }
                if ($this->queryPlanner->requireTable('vtiger_createdbyAccounts')) {
                    $query .= ' left join vtiger_users as vtiger_createdbyAccounts on vtiger_createdbyAccounts.id = vtiger_crmentity.smcreatorid';
                }
                $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                $query .= $relquery . ' ';
                $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0 ';
            } else {
                if ($module == 'Contacts') {
                    $query = "from vtiger_contactdetails\n\t\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid = vtiger_contactdetails.contactid";
                    if ($this->queryPlanner->requireTable('vtiger_contactaddress')) {
                        $query .= "\tinner join vtiger_contactaddress on vtiger_contactdetails.contactid = vtiger_contactaddress.contactaddressid";
                    }
                    if ($this->queryPlanner->requireTable('vtiger_customerdetails')) {
                        $query .= "\tinner join vtiger_customerdetails on vtiger_customerdetails.customerid = vtiger_contactdetails.contactid";
                    }
                    if ($this->queryPlanner->requireTable('vtiger_contactsubdetails')) {
                        $query .= "\tinner join vtiger_contactsubdetails on vtiger_contactdetails.contactid = vtiger_contactsubdetails.contactsubscriptionid";
                    }
                    if ($this->queryPlanner->requireTable('vtiger_contactscf')) {
                        $query .= "\tinner join vtiger_contactscf on vtiger_contactdetails.contactid = vtiger_contactscf.contactid";
                    }
                    if ($this->queryPlanner->requireTable('vtiger_groupsContacts')) {
                        $query .= ' left join vtiger_groups vtiger_groupsContacts on vtiger_groupsContacts.groupid = vtiger_crmentity.smownerid';
                    }
                    if ($this->queryPlanner->requireTable('vtiger_contactdetailsContacts')) {
                        $query .= "\tleft join vtiger_contactdetails as vtiger_contactdetailsContacts on vtiger_contactdetailsContacts.contactid = vtiger_contactdetails.reportsto";
                    }
                    if ($this->queryPlanner->requireTable('vtiger_accountContacts')) {
                        $query .= "\tleft join vtiger_account as vtiger_accountContacts on vtiger_accountContacts.accountid = vtiger_contactdetails.accountid";
                    }
                    if ($this->queryPlanner->requireTable('vtiger_usersContacts')) {
                        $query .= ' left join vtiger_users as vtiger_usersContacts on vtiger_usersContacts.id = vtiger_crmentity.smownerid';
                    }
                    $query .= " left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid\n\t\t\t\tleft join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid";
                    if ($this->queryPlanner->requireTable('vtiger_lastModifiedByContacts')) {
                        $query .= ' left join vtiger_users as vtiger_lastModifiedByContacts on vtiger_lastModifiedByContacts.id = vtiger_crmentity.modifiedby';
                    }
                    if ($this->queryPlanner->requireTable('vtiger_createdbyContacts')) {
                        $query .= ' left join vtiger_users as vtiger_createdbyContacts on vtiger_createdbyContacts.id = vtiger_crmentity.smcreatorid';
                    }
                    $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                    $query .= $relquery . ' ';
                    $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0';
                } else {
                    if ($module == 'Potentials') {
                        $query = "from vtiger_potential\n\t\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_potential.potentialid";
                        if ($this->queryPlanner->requireTable('vtiger_potentialscf')) {
                            $query .= ' inner join vtiger_potentialscf on vtiger_potentialscf.potentialid = vtiger_potential.potentialid';
                        }
                        if ($this->queryPlanner->requireTable('vtiger_accountPotentials')) {
                            $query .= ' left join vtiger_account as vtiger_accountPotentials on vtiger_potential.related_to = vtiger_accountPotentials.accountid';
                        }
                        if ($this->queryPlanner->requireTable('vtiger_contactdetailsPotentials')) {
                            $query .= ' left join vtiger_contactdetails as vtiger_contactdetailsPotentials on vtiger_potential.contact_id = vtiger_contactdetailsPotentials.contactid';
                        }
                        if ($this->queryPlanner->requireTable('vtiger_campaignPotentials')) {
                            $query .= ' left join vtiger_campaign as vtiger_campaignPotentials on vtiger_potential.campaignid = vtiger_campaignPotentials.campaignid';
                        }
                        if ($this->queryPlanner->requireTable('vtiger_groupsPotentials')) {
                            $query .= ' left join vtiger_groups vtiger_groupsPotentials on vtiger_groupsPotentials.groupid = vtiger_crmentity.smownerid';
                        }
                        if ($this->queryPlanner->requireTable('vtiger_usersPotentials')) {
                            $query .= ' left join vtiger_users as vtiger_usersPotentials on vtiger_usersPotentials.id = vtiger_crmentity.smownerid';
                        }
                        $query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
                        $query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
                        if ($this->queryPlanner->requireTable('vtiger_lastModifiedByPotentials')) {
                            $query .= ' left join vtiger_users as vtiger_lastModifiedByPotentials on vtiger_lastModifiedByPotentials.id = vtiger_crmentity.modifiedby';
                        }
                        if ($this->queryPlanner->requireTable('vtiger_createdbyPotentials')) {
                            $query .= ' left join vtiger_users as vtiger_createdbyPotentials on vtiger_createdbyPotentials.id = vtiger_crmentity.smcreatorid';
                        }
                        $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                        $query .= $relquery . ' ';
                        $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0 ';
                    } else {
                        if ($module == 'Products') {
                            $query .= ' from vtiger_products';
                            $query .= ' inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_products.productid';
                            if ($this->queryPlanner->requireTable('vtiger_productcf')) {
                                $query .= ' left join vtiger_productcf on vtiger_products.productid = vtiger_productcf.productid';
                            }
                            if ($this->queryPlanner->requireTable('vtiger_lastModifiedByProducts')) {
                                $query .= ' left join vtiger_users as vtiger_lastModifiedByProducts on vtiger_lastModifiedByProducts.id = vtiger_crmentity.modifiedby';
                            }
                            if ($this->queryPlanner->requireTable('vtiger_createdbyProducts')) {
                                $query .= ' left join vtiger_users as vtiger_createdbyProducts on vtiger_createdbyProducts.id = vtiger_crmentity.smcreatorid';
                            }
                            if ($this->queryPlanner->requireTable('vtiger_usersProducts')) {
                                $query .= ' left join vtiger_users as vtiger_usersProducts on vtiger_usersProducts.id = vtiger_crmentity.smownerid';
                            }
                            if ($this->queryPlanner->requireTable('vtiger_groupsProducts')) {
                                $query .= ' left join vtiger_groups as vtiger_groupsProducts on vtiger_groupsProducts.groupid = vtiger_crmentity.smownerid';
                            }
                            if ($this->queryPlanner->requireTable('vtiger_vendorRelProducts')) {
                                $query .= ' left join vtiger_vendor as vtiger_vendorRelProducts on vtiger_vendorRelProducts.vendorid = vtiger_products.vendor_id';
                            }
                            if ($this->queryPlanner->requireTable('innerProduct')) {
                                $query .= " LEFT JOIN (\n\t\t\t\t\t\tSELECT vtiger_products.productid,\n\t\t\t\t\t\t\t\t(CASE WHEN (vtiger_products.currency_id = 1 ) THEN vtiger_products.unit_price\n\t\t\t\t\t\t\t\t\tELSE (vtiger_products.unit_price / vtiger_currency_info.conversion_rate) END\n\t\t\t\t\t\t\t\t) AS actual_unit_price\n\t\t\t\t\t\tFROM vtiger_products\n\t\t\t\t\t\tLEFT JOIN vtiger_currency_info ON vtiger_products.currency_id = vtiger_currency_info.id\n\t\t\t\t\t\tLEFT JOIN vtiger_productcurrencyrel ON vtiger_products.productid = vtiger_productcurrencyrel.productid\n\t\t\t\t\t\tAND vtiger_productcurrencyrel.currencyid = " . $current_user->currency_id . "\n\t\t\t\t) AS innerProduct ON innerProduct.productid = vtiger_products.productid";
                            }
                            $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                            $query .= $relquery . ' ';
                            $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . "\n\t\t\t\twhere vtiger_crmentity.deleted=0";
                        } else {
                            if ($module == 'Services') {
                                $query .= ' from vtiger_service';
                                $query .= ' inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_service.serviceid';
                                if ($this->queryPlanner->requireTable('vtiger_servicecf')) {
                                    $query .= ' left join vtiger_servicecf on vtiger_service.serviceid = vtiger_servicecf.serviceid';
                                }
                                if ($this->queryPlanner->requireTable('innerService')) {
                                    $query .= " LEFT JOIN (\n\t\t\t\t\t\tSELECT vtiger_service.serviceid,\n\t\t\t\t\t\t\t\t(CASE WHEN (vtiger_service.currency_id = 1 ) THEN vtiger_service.unit_price\n\t\t\t\t\t\t\t\t\tELSE (vtiger_service.unit_price / vtiger_currency_info.conversion_rate) END\n\t\t\t\t\t\t\t\t) AS actual_unit_price\n\t\t\t\t\t\tFROM vtiger_service\n\t\t\t\t\t\tLEFT JOIN vtiger_currency_info ON vtiger_service.currency_id = vtiger_currency_info.id\n\n\t\t\t\t) AS innerService ON innerService.serviceid = vtiger_service.serviceid";
                                }
                                if ($this->queryPlanner->requireTable('vtiger_groupsServices')) {
                                    $query .= ' left join vtiger_groups as vtiger_groupsServices on vtiger_groupsServices.groupid = vtiger_crmentity.smownerid';
                                }
                                if ($this->queryPlanner->requireTable('vtiger_usersServices')) {
                                    $query .= ' left join vtiger_users as vtiger_usersServices on vtiger_usersServices.id = vtiger_crmentity.smownerid';
                                }
                                if ($this->queryPlanner->requireTable('vtiger_lastModifiedByServices')) {
                                    $query .= ' left join vtiger_users as vtiger_lastModifiedByServices on vtiger_lastModifiedByServices.id = vtiger_crmentity.modifiedby';
                                }
                                if ($this->queryPlanner->requireTable('vtiger_createdbyServices')) {
                                    $query .= ' left join vtiger_users as vtiger_createdbyServices on vtiger_createdbyServices.id = vtiger_crmentity.smcreatorid';
                                }
                                if ($this->queryPlanner->requireTable('vtiger_currency_info')) {
                                    $query .= ' LEFT JOIN vtiger_currency_info ON vtiger_currency_info.id = vtiger_salesorder.currency_id';
                                }
                                $query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
                                $query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
                                $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                                $query .= $relquery . ' ';
                                $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . "\n\t\t\t\twhere vtiger_crmentity.deleted=0";
                            } else {
                                if ($module == 'HelpDesk') {
                                    $matrix = $this->queryPlanner->newDependencyMatrix();
                                    $matrix->setDependency('vtiger_crmentityRelHelpDesk', ['vtiger_accountRelHelpDesk', 'vtiger_contactdetailsRelHelpDesk']);
                                    $query = 'from vtiger_troubletickets inner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_troubletickets.ticketid';
                                    if ($this->queryPlanner->requireTable('vtiger_ticketcf')) {
                                        $query .= ' inner join vtiger_ticketcf on vtiger_ticketcf.ticketid = vtiger_troubletickets.ticketid';
                                    }
                                    if ($this->queryPlanner->requireTable('vtiger_crmentityRelHelpDesk', $matrix)) {
                                        $query .= ' left join vtiger_crmentity as vtiger_crmentityRelHelpDesk on vtiger_crmentityRelHelpDesk.crmid = vtiger_troubletickets.parent_id';
                                    }
                                    if ($this->queryPlanner->requireTable('vtiger_accountRelHelpDesk')) {
                                        $query .= ' left join vtiger_account as vtiger_accountRelHelpDesk on vtiger_accountRelHelpDesk.accountid=vtiger_crmentityRelHelpDesk.crmid';
                                    }
                                    if ($this->queryPlanner->requireTable('vtiger_contactdetailsRelHelpDesk')) {
                                        $query .= ' left join vtiger_contactdetails as vtiger_contactdetailsRelHelpDesk on vtiger_contactdetailsRelHelpDesk.contactid= vtiger_troubletickets.contact_id';
                                    }
                                    if ($this->queryPlanner->requireTable('vtiger_productsRel')) {
                                        $query .= ' left join vtiger_products as vtiger_productsRel on vtiger_productsRel.productid = vtiger_troubletickets.product_id';
                                    }
                                    if ($this->queryPlanner->requireTable('vtiger_groupsHelpDesk')) {
                                        $query .= ' left join vtiger_groups as vtiger_groupsHelpDesk on vtiger_groupsHelpDesk.groupid = vtiger_crmentity.smownerid';
                                    }
                                    if ($this->queryPlanner->requireTable('vtiger_usersHelpDesk')) {
                                        $query .= ' left join vtiger_users as vtiger_usersHelpDesk on vtiger_crmentity.smownerid=vtiger_usersHelpDesk.id';
                                    }
                                    $query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
                                    $query .= ' left join vtiger_users on vtiger_crmentity.smownerid=vtiger_users.id';
                                    if ($this->queryPlanner->requireTable('vtiger_lastModifiedByHelpDesk')) {
                                        $query .= '  left join vtiger_users as vtiger_lastModifiedByHelpDesk on vtiger_lastModifiedByHelpDesk.id = vtiger_crmentity.modifiedby';
                                    }
                                    if ($this->queryPlanner->requireTable('vtiger_createdbyHelpDesk')) {
                                        $query .= ' left join vtiger_users as vtiger_createdbyHelpDesk on vtiger_createdbyHelpDesk.id = vtiger_crmentity.smcreatorid';
                                    }
                                    $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                                    $query .= $relquery . ' ';
                                    $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0 ';
                                } else {
                                    if ($module == 'Calendar') {
                                        $referenceModuleList = Vtiger_Util_Helper::getCalendarReferenceModulesList();
                                        $referenceTablesList = [];
                                        foreach ($referenceModuleList as $referenceModule) {
                                            $entityTableFieldNames = getEntityFieldNames($referenceModule);
                                            $entityTableName = $entityTableFieldNames['tablename'];
                                            $referenceTablesList[] = $entityTableName . 'RelCalendar';
                                        }
                                        $matrix = $this->queryPlanner->newDependencyMatrix();
                                        $matrix->setDependency('vtiger_cntactivityrel', ['vtiger_contactdetailsCalendar']);
                                        $matrix->setDependency('vtiger_seactivityrel', ['vtiger_crmentityRelCalendar']);
                                        $matrix->setDependency('vtiger_crmentityRelCalendar', $referenceTablesList);
                                        $query = "from vtiger_activity\n\t\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_activity.activityid";
                                        if ($this->queryPlanner->requireTable('vtiger_activitycf')) {
                                            $query .= ' left join vtiger_activitycf on vtiger_activitycf.activityid = vtiger_crmentity.crmid';
                                        }
                                        if ($this->queryPlanner->requireTable('vtiger_cntactivityrel', $matrix)) {
                                            $query .= ' left join vtiger_cntactivityrel on vtiger_cntactivityrel.activityid= vtiger_activity.activityid';
                                        }
                                        if ($this->queryPlanner->requireTable('vtiger_contactdetailsCalendar')) {
                                            $query .= ' left join vtiger_contactdetails as vtiger_contactdetailsCalendar on vtiger_contactdetailsCalendar.contactid= vtiger_cntactivityrel.contactid';
                                        }
                                        if ($this->queryPlanner->requireTable('vtiger_groupsCalendar')) {
                                            $query .= ' left join vtiger_groups as vtiger_groupsCalendar on vtiger_groupsCalendar.groupid = vtiger_crmentity.smownerid';
                                        }
                                        if ($this->queryPlanner->requireTable('vtiger_usersCalendar')) {
                                            $query .= ' left join vtiger_users as vtiger_usersCalendar on vtiger_usersCalendar.id = vtiger_crmentity.smownerid';
                                        }
                                        $query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
                                        $query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
                                        if ($this->queryPlanner->requireTable('vtiger_seactivityrel', $matrix)) {
                                            $query .= ' left join vtiger_seactivityrel on vtiger_seactivityrel.activityid = vtiger_activity.activityid';
                                        }
                                        if ($this->queryPlanner->requireTable('vtiger_activity_reminder')) {
                                            $query .= ' left join vtiger_activity_reminder on vtiger_activity_reminder.activity_id = vtiger_activity.activityid';
                                        }
                                        if ($this->queryPlanner->requireTable('vtiger_recurringevents')) {
                                            $query .= ' left join vtiger_recurringevents on vtiger_recurringevents.activityid = vtiger_activity.activityid';
                                        }
                                        if ($this->queryPlanner->requireTable('vtiger_crmentityRelCalendar', $matrix)) {
                                            $query .= ' left join vtiger_crmentity as vtiger_crmentityRelCalendar on vtiger_crmentityRelCalendar.crmid = vtiger_seactivityrel.crmid';
                                        }
                                        foreach ($referenceModuleList as $referenceModule) {
                                            $entityTableFieldNames = getEntityFieldNames($referenceModule);
                                            $entityTableName = $entityTableFieldNames['tablename'];
                                            $entityIdFieldName = $entityTableFieldNames['entityidfield'];
                                            $referenceTable = $entityTableName . 'RelCalendar';
                                            if ($this->queryPlanner->requireTable($referenceTable)) {
                                                $query .= ' LEFT JOIN ' . $entityTableName . ' AS ' . $referenceTable . ' ON ' . $referenceTable . '.' . $entityIdFieldName . ' = vtiger_crmentityRelCalendar.crmid';
                                            }
                                        }
                                        if ($this->queryPlanner->requireTable('vtiger_lastModifiedByCalendar')) {
                                            $query .= ' left join vtiger_users as vtiger_lastModifiedByCalendar on vtiger_lastModifiedByCalendar.id = vtiger_crmentity.modifiedby';
                                        }
                                        if ($this->queryPlanner->requireTable('vtiger_createdbyCalendar')) {
                                            $query .= ' left join vtiger_users as vtiger_createdbyCalendar on vtiger_createdbyCalendar.id = vtiger_crmentity.smcreatorid';
                                        }
                                        $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                                        $query .= $relquery . ' ';
                                        $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . " WHERE vtiger_crmentity.deleted=0 and (vtiger_activity.activitytype != 'Emails')";
                                    } else {
                                        if ($module == 'Quotes') {
                                            $matrix = $this->queryPlanner->newDependencyMatrix();
                                            $matrix->setDependency('vtiger_inventoryproductreltmpQuotes', ['vtiger_productsQuotes', 'vtiger_serviceQuotes']);
                                            $query = "from vtiger_quotes\n\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_quotes.quoteid";
                                            if ($this->queryPlanner->requireTable('vtiger_quotesbillads')) {
                                                $query .= ' inner join vtiger_quotesbillads on vtiger_quotes.quoteid=vtiger_quotesbillads.quotebilladdressid';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_quotesshipads')) {
                                                $query .= ' inner join vtiger_quotesshipads on vtiger_quotes.quoteid=vtiger_quotesshipads.quoteshipaddressid';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_currency_info' . $module)) {
                                                $query .= ' left join vtiger_currency_info as vtiger_currency_info' . $module . ' on vtiger_currency_info' . $module . '.id = vtiger_quotes.currency_id';
                                            }
                                            if ($type !== 'COLUMNSTOTOTAL' || $this->lineItemFieldsInCalculation == true) {
                                                if ($this->queryPlanner->requireTable('vtiger_inventoryproductreltmpQuotes', $matrix)) {
                                                    $query .= ' left join vtiger_inventoryproductrel as vtiger_inventoryproductreltmpQuotes on vtiger_quotes.quoteid = vtiger_inventoryproductreltmpQuotes.id';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_productsQuotes')) {
                                                    $query .= ' left join vtiger_products as vtiger_productsQuotes on vtiger_productsQuotes.productid = vtiger_inventoryproductreltmpQuotes.productid';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_serviceQuotes')) {
                                                    $query .= ' left join vtiger_service as vtiger_serviceQuotes on vtiger_serviceQuotes.serviceid = vtiger_inventoryproductreltmpQuotes.productid';
                                                }
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_quotescf')) {
                                                $query .= ' left join vtiger_quotescf on vtiger_quotes.quoteid = vtiger_quotescf.quoteid';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_groupsQuotes')) {
                                                $query .= ' left join vtiger_groups as vtiger_groupsQuotes on vtiger_groupsQuotes.groupid = vtiger_crmentity.smownerid';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_usersQuotes')) {
                                                $query .= ' left join vtiger_users as vtiger_usersQuotes on vtiger_usersQuotes.id = vtiger_crmentity.smownerid';
                                            }
                                            $query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
                                            $query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
                                            if ($this->queryPlanner->requireTable('vtiger_lastModifiedByQuotes')) {
                                                $query .= ' left join vtiger_users as vtiger_lastModifiedByQuotes on vtiger_lastModifiedByQuotes.id = vtiger_crmentity.modifiedby';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_createdbyQuotes')) {
                                                $query .= ' left join vtiger_users as vtiger_createdbyQuotes on vtiger_createdbyQuotes.id = vtiger_crmentity.smcreatorid';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_usersRel1')) {
                                                $query .= ' left join vtiger_users as vtiger_usersRel1 on vtiger_usersRel1.id = vtiger_quotes.inventorymanager';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_potentialRelQuotes')) {
                                                $query .= ' left join vtiger_potential as vtiger_potentialRelQuotes on vtiger_potentialRelQuotes.potentialid = vtiger_quotes.potentialid';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_contactdetailsQuotes')) {
                                                $query .= ' left join vtiger_contactdetails as vtiger_contactdetailsQuotes on vtiger_contactdetailsQuotes.contactid = vtiger_quotes.contactid';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_leaddetailsQuotes')) {
                                                $query .= ' left join vtiger_leaddetails as vtiger_leaddetailsQuotes on vtiger_leaddetailsQuotes.leadid = vtiger_quotes.contactid';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_accountQuotes')) {
                                                $query .= ' left join vtiger_account as vtiger_accountQuotes on vtiger_accountQuotes.accountid = vtiger_quotes.accountid';
                                            }
                                            if ($this->queryPlanner->requireTable('vtiger_currency_info')) {
                                                $query .= ' LEFT JOIN vtiger_currency_info ON vtiger_currency_info.id = vtiger_quotes.currency_id';
                                            }
                                            $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                                            $query .= $relquery . ' ';
                                            $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0';
                                        } else {
                                            if ($module == 'PurchaseOrder') {
                                                $matrix = $this->queryPlanner->newDependencyMatrix();
                                                $matrix->setDependency('vtiger_inventoryproductreltmpPurchaseOrder', ['vtiger_productsPurchaseOrder', 'vtiger_servicePurchaseOrder']);
                                                $query = "from vtiger_purchaseorder\n\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_purchaseorder.purchaseorderid";
                                                if ($this->queryPlanner->requireTable('vtiger_pobillads')) {
                                                    $query .= ' inner join vtiger_pobillads on vtiger_purchaseorder.purchaseorderid=vtiger_pobillads.pobilladdressid';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_poshipads')) {
                                                    $query .= ' inner join vtiger_poshipads on vtiger_purchaseorder.purchaseorderid=vtiger_poshipads.poshipaddressid';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_currency_info' . $module)) {
                                                    $query .= ' left join vtiger_currency_info as vtiger_currency_info' . $module . ' on vtiger_currency_info' . $module . '.id = vtiger_purchaseorder.currency_id';
                                                }
                                                if ($type !== 'COLUMNSTOTOTAL' || $this->lineItemFieldsInCalculation == true) {
                                                    if ($this->queryPlanner->requireTable('vtiger_inventoryproductreltmpPurchaseOrder', $matrix)) {
                                                        $query .= ' left join vtiger_inventoryproductrel as vtiger_inventoryproductreltmpPurchaseOrder on vtiger_purchaseorder.purchaseorderid = vtiger_inventoryproductreltmpPurchaseOrder.id';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_productsPurchaseOrder')) {
                                                        $query .= ' left join vtiger_products as vtiger_productsPurchaseOrder on vtiger_productsPurchaseOrder.productid = vtiger_inventoryproductreltmpPurchaseOrder.productid';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_servicePurchaseOrder')) {
                                                        $query .= ' left join vtiger_service as vtiger_servicePurchaseOrder on vtiger_servicePurchaseOrder.serviceid = vtiger_inventoryproductreltmpPurchaseOrder.productid';
                                                    }
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_purchaseordercf')) {
                                                    $query .= ' left join vtiger_purchaseordercf on vtiger_purchaseorder.purchaseorderid = vtiger_purchaseordercf.purchaseorderid';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_groupsPurchaseOrder')) {
                                                    $query .= ' left join vtiger_groups as vtiger_groupsPurchaseOrder on vtiger_groupsPurchaseOrder.groupid = vtiger_crmentity.smownerid';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_usersPurchaseOrder')) {
                                                    $query .= ' left join vtiger_users as vtiger_usersPurchaseOrder on vtiger_usersPurchaseOrder.id = vtiger_crmentity.smownerid';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_accountsPurchaseOrder')) {
                                                    $query .= ' left join vtiger_account as vtiger_accountsPurchaseOrder on vtiger_accountsPurchaseOrder.accountid = vtiger_purchaseorder.accountid';
                                                }
                                                $query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
                                                $query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
                                                if ($this->queryPlanner->requireTable('vtiger_lastModifiedByPurchaseOrder')) {
                                                    $query .= ' left join vtiger_users as vtiger_lastModifiedByPurchaseOrder on vtiger_lastModifiedByPurchaseOrder.id = vtiger_crmentity.modifiedby';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_createdbyPurchaseOrder')) {
                                                    $query .= ' left join vtiger_users as vtiger_createdbyPurchaseOrder on vtiger_createdbyPurchaseOrder.id = vtiger_crmentity.smcreatorid';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_vendorRelPurchaseOrder')) {
                                                    $query .= ' left join vtiger_vendor as vtiger_vendorRelPurchaseOrder on vtiger_vendorRelPurchaseOrder.vendorid = vtiger_purchaseorder.vendorid';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_contactdetailsPurchaseOrder')) {
                                                    $query .= ' left join vtiger_contactdetails as vtiger_contactdetailsPurchaseOrder on vtiger_contactdetailsPurchaseOrder.contactid = vtiger_purchaseorder.contactid';
                                                }
                                                if ($this->queryPlanner->requireTable('vtiger_currency_info')) {
                                                    $query .= ' LEFT JOIN vtiger_currency_info ON vtiger_currency_info.id = vtiger_purchaseorder.currency_id';
                                                }
                                                $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                                                $query .= $relquery . ' ';
                                                $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0';
                                            } else {
                                                if ($module == 'Invoice') {
                                                    $matrix = $this->queryPlanner->newDependencyMatrix();
                                                    $matrix->setDependency('vtiger_inventoryproductreltmpInvoice', ['vtiger_productsInvoice', 'vtiger_serviceInvoice']);
                                                    $query = "from vtiger_invoice\n\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_invoice.invoiceid";
                                                    if ($this->queryPlanner->requireTable('vtiger_invoicebillads')) {
                                                        $query .= ' inner join vtiger_invoicebillads on vtiger_invoice.invoiceid=vtiger_invoicebillads.invoicebilladdressid';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_invoiceshipads')) {
                                                        $query .= ' inner join vtiger_invoiceshipads on vtiger_invoice.invoiceid=vtiger_invoiceshipads.invoiceshipaddressid';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_currency_info' . $module)) {
                                                        $query .= ' left join vtiger_currency_info as vtiger_currency_info' . $module . ' on vtiger_currency_info' . $module . '.id = vtiger_invoice.currency_id';
                                                    }
                                                    if ($type !== 'COLUMNSTOTOTAL' || $this->lineItemFieldsInCalculation == true) {
                                                        if ($this->queryPlanner->requireTable('vtiger_inventoryproductreltmpInvoice', $matrix)) {
                                                            $query .= ' left join vtiger_inventoryproductrel as vtiger_inventoryproductreltmpInvoice on vtiger_invoice.invoiceid = vtiger_inventoryproductreltmpInvoice.id';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_productsInvoice')) {
                                                            $query .= ' left join vtiger_products as vtiger_productsInvoice on vtiger_productsInvoice.productid = vtiger_inventoryproductreltmpInvoice.productid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_serviceInvoice')) {
                                                            $query .= ' left join vtiger_service as vtiger_serviceInvoice on vtiger_serviceInvoice.serviceid = vtiger_inventoryproductreltmpInvoice.productid';
                                                        }
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_salesorderInvoice')) {
                                                        $query .= ' left join vtiger_salesorder as vtiger_salesorderInvoice on vtiger_salesorderInvoice.salesorderid=vtiger_invoice.salesorderid';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_invoicecf')) {
                                                        $query .= ' left join vtiger_invoicecf on vtiger_invoice.invoiceid = vtiger_invoicecf.invoiceid';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_groupsInvoice')) {
                                                        $query .= ' left join vtiger_groups as vtiger_groupsInvoice on vtiger_groupsInvoice.groupid = vtiger_crmentity.smownerid';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_usersInvoice')) {
                                                        $query .= ' left join vtiger_users as vtiger_usersInvoice on vtiger_usersInvoice.id = vtiger_crmentity.smownerid';
                                                    }
                                                    $query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
                                                    $query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
                                                    if ($this->queryPlanner->requireTable('vtiger_lastModifiedByInvoice')) {
                                                        $query .= ' left join vtiger_users as vtiger_lastModifiedByInvoice on vtiger_lastModifiedByInvoice.id = vtiger_crmentity.modifiedby';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_createdbyInvoice')) {
                                                        $query .= ' left join vtiger_users as vtiger_createdbyInvoice on vtiger_createdbyInvoice.id = vtiger_crmentity.smcreatorid';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_accountInvoice')) {
                                                        $query .= ' left join vtiger_account as vtiger_accountInvoice on vtiger_accountInvoice.accountid = vtiger_invoice.accountid';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_contactdetailsInvoice')) {
                                                        $query .= ' left join vtiger_contactdetails as vtiger_contactdetailsInvoice on vtiger_contactdetailsInvoice.contactid = vtiger_invoice.contactid';
                                                    }
                                                    if ($this->queryPlanner->requireTable('vtiger_currency_info')) {
                                                        $query .= ' LEFT JOIN vtiger_currency_info ON vtiger_currency_info.id = vtiger_invoice.currency_id';
                                                    }
                                                    $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                                                    $query .= $relquery . ' ';
                                                    $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0';
                                                } else {
                                                    if ($module == 'SalesOrder') {
                                                        $matrix = $this->queryPlanner->newDependencyMatrix();
                                                        $matrix->setDependency('vtiger_inventoryproductreltmpSalesOrder', ['vtiger_productsSalesOrder', 'vtiger_serviceSalesOrder']);
                                                        $query = "from vtiger_salesorder\n\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_salesorder.salesorderid";
                                                        if ($this->queryPlanner->requireTable('vtiger_sobillads')) {
                                                            $query .= ' inner join vtiger_sobillads on vtiger_salesorder.salesorderid=vtiger_sobillads.sobilladdressid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_soshipads')) {
                                                            $query .= ' inner join vtiger_soshipads on vtiger_salesorder.salesorderid=vtiger_soshipads.soshipaddressid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_currency_info' . $module)) {
                                                            $query .= ' left join vtiger_currency_info as vtiger_currency_info' . $module . ' on vtiger_currency_info' . $module . '.id = vtiger_salesorder.currency_id';
                                                        }
                                                        if ($type !== 'COLUMNSTOTOTAL' || $this->lineItemFieldsInCalculation == true) {
                                                            if ($this->queryPlanner->requireTable('vtiger_inventoryproductreltmpSalesOrder', $matrix)) {
                                                                $query .= ' left join vtiger_inventoryproductrel as vtiger_inventoryproductreltmpSalesOrder on vtiger_salesorder.salesorderid = vtiger_inventoryproductreltmpSalesOrder.id';
                                                            }
                                                            if ($this->queryPlanner->requireTable('vtiger_productsSalesOrder')) {
                                                                $query .= ' left join vtiger_products as vtiger_productsSalesOrder on vtiger_productsSalesOrder.productid = vtiger_inventoryproductreltmpSalesOrder.productid';
                                                            }
                                                            if ($this->queryPlanner->requireTable('vtiger_serviceSalesOrder')) {
                                                                $query .= ' left join vtiger_service as vtiger_serviceSalesOrder on vtiger_serviceSalesOrder.serviceid = vtiger_inventoryproductreltmpSalesOrder.productid';
                                                            }
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_salesordercf')) {
                                                            $query .= ' left join vtiger_salesordercf on vtiger_salesorder.salesorderid = vtiger_salesordercf.salesorderid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_contactdetailsSalesOrder')) {
                                                            $query .= ' left join vtiger_contactdetails as vtiger_contactdetailsSalesOrder on vtiger_contactdetailsSalesOrder.contactid = vtiger_salesorder.contactid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_quotesSalesOrder')) {
                                                            $query .= ' left join vtiger_quotes as vtiger_quotesSalesOrder on vtiger_quotesSalesOrder.quoteid = vtiger_salesorder.quoteid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_accountSalesOrder')) {
                                                            $query .= ' left join vtiger_account as vtiger_accountSalesOrder on vtiger_accountSalesOrder.accountid = vtiger_salesorder.accountid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_potentialRelSalesOrder')) {
                                                            $query .= ' left join vtiger_potential as vtiger_potentialRelSalesOrder on vtiger_potentialRelSalesOrder.potentialid = vtiger_salesorder.potentialid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_invoice_recurring_info')) {
                                                            $query .= ' left join vtiger_invoice_recurring_info on vtiger_invoice_recurring_info.salesorderid = vtiger_salesorder.salesorderid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_groupsSalesOrder')) {
                                                            $query .= ' left join vtiger_groups as vtiger_groupsSalesOrder on vtiger_groupsSalesOrder.groupid = vtiger_crmentity.smownerid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_usersSalesOrder')) {
                                                            $query .= ' left join vtiger_users as vtiger_usersSalesOrder on vtiger_usersSalesOrder.id = vtiger_crmentity.smownerid';
                                                        }
                                                        $query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
                                                        $query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
                                                        if ($this->queryPlanner->requireTable('vtiger_lastModifiedBySalesOrder')) {
                                                            $query .= ' left join vtiger_users as vtiger_lastModifiedBySalesOrder on vtiger_lastModifiedBySalesOrder.id = vtiger_crmentity.modifiedby';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_createdbySalesOrder')) {
                                                            $query .= ' left join vtiger_users as vtiger_createdbySalesOrder on vtiger_createdbySalesOrder.id = vtiger_crmentity.smcreatorid';
                                                        }
                                                        if ($this->queryPlanner->requireTable('vtiger_currency_info')) {
                                                            $query .= ' LEFT JOIN vtiger_currency_info ON vtiger_currency_info.id = vtiger_salesorder.currency_id';
                                                        }
                                                        $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                                                        $query .= $relquery . ' ';
                                                        $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0';
                                                    } else {
                                                        if ($module == 'Campaigns') {
                                                            $query = "from vtiger_campaign\n\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_campaign.campaignid";
                                                            if ($this->queryPlanner->requireTable('vtiger_campaignscf')) {
                                                                $query .= ' inner join vtiger_campaignscf as vtiger_campaignscf on vtiger_campaignscf.campaignid=vtiger_campaign.campaignid';
                                                            }
                                                            if ($this->queryPlanner->requireTable('vtiger_productsCampaigns')) {
                                                                $query .= ' left join vtiger_products as vtiger_productsCampaigns on vtiger_productsCampaigns.productid = vtiger_campaign.product_id';
                                                            }
                                                            if ($this->queryPlanner->requireTable('vtiger_groupsCampaigns')) {
                                                                $query .= ' left join vtiger_groups as vtiger_groupsCampaigns on vtiger_groupsCampaigns.groupid = vtiger_crmentity.smownerid';
                                                            }
                                                            if ($this->queryPlanner->requireTable('vtiger_usersCampaigns')) {
                                                                $query .= ' left join vtiger_users as vtiger_usersCampaigns on vtiger_usersCampaigns.id = vtiger_crmentity.smownerid';
                                                            }
                                                            $query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
                                                            $query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
                                                            if ($this->queryPlanner->requireTable('vtiger_lastModifiedBy' . $module)) {
                                                                $query .= ' left join vtiger_users as vtiger_lastModifiedBy' . $module . ' on vtiger_lastModifiedBy' . $module . '.id = vtiger_crmentity.modifiedby';
                                                            }
                                                            if ($this->queryPlanner->requireTable('vtiger_createdby' . $module)) {
                                                                $query .= ' left join vtiger_users as vtiger_createdby' . $module . ' on vtiger_createdby' . $module . '.id = vtiger_crmentity.smcreatorid';
                                                            }
                                                            $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                                                            $query .= $relquery . ' ';
                                                            $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0';
                                                        } else {
                                                            if ($module == 'Emails') {
                                                                $query = "from vtiger_activity\n\t\t\tINNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid AND vtiger_activity.activitytype = 'Emails'";
                                                                if ($this->queryPlanner->requireTable('vtiger_email_track')) {
                                                                    $query .= ' LEFT JOIN vtiger_email_track ON vtiger_email_track.mailid = vtiger_activity.activityid';
                                                                }
                                                                if ($this->queryPlanner->requireTable('vtiger_groupsEmails')) {
                                                                    $query .= ' LEFT JOIN vtiger_groups AS vtiger_groupsEmails ON vtiger_groupsEmails.groupid = vtiger_crmentity.smownerid';
                                                                }
                                                                if ($this->queryPlanner->requireTable('vtiger_usersEmails')) {
                                                                    $query .= ' LEFT JOIN vtiger_users AS vtiger_usersEmails ON vtiger_usersEmails.id = vtiger_crmentity.smownerid';
                                                                }
                                                                $query .= ' LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid';
                                                                $query .= ' LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid';
                                                                if ($this->queryPlanner->requireTable('vtiger_lastModifiedBy' . $module)) {
                                                                    $query .= ' LEFT JOIN vtiger_users AS vtiger_lastModifiedBy' . $module . ' ON vtiger_lastModifiedBy' . $module . '.id = vtiger_crmentity.modifiedby';
                                                                }
                                                                if ($this->queryPlanner->requireTable('vtiger_createdby' . $module)) {
                                                                    $query .= ' left join vtiger_users as vtiger_createdby' . $module . ' on vtiger_createdby' . $module . '.id = vtiger_crmentity.smcreatorid';
                                                                }
                                                                $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                                                                $query .= $relquery . ' ';
                                                                $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' WHERE vtiger_crmentity.deleted = 0';
                                                            } else {
                                                                if ($module == 'VTEItems') {
                                                                    $query = "from vtiger_vteitems\n\t\t\tinner join vtiger_crmentity on vtiger_crmentity.crmid=vtiger_vteitems.vteitemid";
                                                                    if ($this->queryPlanner->requireTable('vtiger_vteitemscf')) {
                                                                        $query .= ' inner join vtiger_vteitemscf as vtiger_vteitemscf on vtiger_vteitemscf.vteitemid=vtiger_vteitems.vteitemid';
                                                                    }
                                                                    if ($this->queryPlanner->requireTable('vtiger_groupsVTEItems')) {
                                                                        $query .= ' left join vtiger_groups as vtiger_groupsVTEItems on vtiger_groupsVTEItems.groupid = vtiger_crmentity.smownerid';
                                                                    }
                                                                    if ($this->queryPlanner->requireTable('vtiger_usersVTEItems')) {
                                                                        $query .= ' left join vtiger_users as vtiger_usersVTEItems on vtiger_usersVTEItems.id = vtiger_crmentity.smownerid';
                                                                    }
                                                                    if ($this->queryPlanner->requireTable('vtiger_quotescf') && !$this->queryPlanner->requireTable('vtiger_quotes')) {
                                                                        $query .= ' left join vtiger_quotescf on vtiger_vteitems.related_to = vtiger_quotescf.quoteid';
                                                                    }
                                                                    $query .= ' left join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid';
                                                                    $query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
                                                                    if ($this->queryPlanner->requireTable('vtiger_lastModifiedBy' . $module)) {
                                                                        $query .= ' left join vtiger_users as vtiger_lastModifiedBy' . $module . ' on vtiger_lastModifiedBy' . $module . '.id = vtiger_crmentity.modifiedby';
                                                                    }
                                                                    if ($this->queryPlanner->requireTable('vtiger_createdby' . $module)) {
                                                                        $query .= ' left join vtiger_users as vtiger_createdby' . $module . ' on vtiger_createdby' . $module . '.id = vtiger_crmentity.smcreatorid';
                                                                    }
                                                                    $relquery = $this->getVReportsUiType10Query($module, $this->queryPlanner);
                                                                    $query .= $relquery . ' ';
                                                                    $query .= ' ' . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' where vtiger_crmentity.deleted=0';
                                                                } else {
                                                                    if ($module != '') {
                                                                        $query = $this->generateVReportsQuery($module, $this->queryPlanner) . $this->getRelatedModulesQuery($module, $this->secondarymodule) . getNonAdminAccessControlQuery($this->primarymodule, $current_user) . ' WHERE vtiger_crmentity.deleted=0';
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $log->info('ReportRun :: Successfully returned getReportsQuery' . $module);
        $secondarymodule = explode(':', $this->secondarymodule);
        if (in_array('Calendar', $secondarymodule) || $module == 'Calendar') {
            $currentUserModel = Users_Record_Model::getCurrentUserModel();
            $tabId = getTabid('Calendar');
            $task_tableName = 'vt_tmp_u' . $currentUserModel->id . '_t' . $tabId . '_task';
            $event_tableName = 'vt_tmp_u' . $currentUserModel->id . '_t' . $tabId . '_events';
            if (!$currentUserModel->isAdminUser() && stripos($query, $event_tableName) && stripos($query, $task_tableName)) {
                $scope = '';
                if (in_array('Calendar', $secondarymodule)) {
                    $scope = 'Calendar';
                }
                $condition = $this->buildWhereClauseConditionForCalendar($scope);
                if ($condition) {
                    $query .= ' AND ' . $condition;
                }
            }
        }

        return $query;
    }

    /** function to get query for the given reportid,filterlist,type.
     *  @ param $reportid : Type integer
     *  @ param $filtersql : Type Array
     *  @ param $module : Type String
     *  this returns join query for the report
     */
    public function sGetSQLforReport($reportid, $filtersql, $type = '', $chartReport = false, $startLimit = false, $endLimit = false)
    {
        global $log;
        $columnlist = $this->getQueryColumnsList($reportid, $type);
        $groupslist = $this->getGroupingList($reportid);
        $groupTimeList = $this->getGroupByTimeList($reportid);
        $stdfilterlist = $this->getStdFilterList($reportid);
        $columnstotallist = $this->getColumnsTotal($reportid);
        $advfiltersql = $this->getAdvFilterSql($reportid);
        $this->totallist = $columnstotallist;
        $wheresql = '';
        global $current_user;
        $selectlist = $columnlist;
        if (isset($selectlist)) {
            $selectedcolumns = implode(', ', $selectlist);
            if ($chartReport == true) {
                $selectedcolumns .= ", count(*) AS 'groupby_count'";
            }
        }
        if (isset($groupslist)) {
            $groupsquery = implode(', ', $groupslist);
        }
        if (isset($groupTimeList)) {
            $groupTimeQuery = implode(', ', $groupTimeList);
        }
        if (isset($stdfilterlist)) {
            $stdfiltersql = implode(', ', $stdfilterlist);
        }
        if (isset($columnstotallist)) {
            $columnstotalsql = implode(', ', $columnstotallist);
        }
        if ($stdfiltersql != '') {
            $wheresql = ' and ' . $stdfiltersql;
        }
        if (isset($filtersql) && $filtersql !== false && $filtersql != '') {
            $advfiltersql = $filtersql;
        }
        if ($advfiltersql != '') {
            $wheresql .= ' and ' . $advfiltersql;
        }
        if ($this->_reportquery == false) {
            $reportquery = $this->getVReportsQuery($this->primarymodule, $type);
            $this->_reportquery = $reportquery;
        } else {
            $reportquery = $this->_reportquery;
        }
        $allColumnsRestricted = false;
        if ($type == 'COLUMNSTOTOTAL') {
            if ($columnstotalsql != '') {
                $reportquery = 'select ' . $columnstotalsql . ' ' . $reportquery . ' ' . $wheresql;
            }
        } else {
            if ($selectedcolumns == '') {
                $selectedcolumns = "''";
                $allColumnsRestricted = true;
            }
            $removeDistinct = false;
            foreach ($columnlist as $key => $value) {
                $tableList = explode(':', $key);
                if ($tableList[0] == 'vtiger_inventoryproductrel') {
                    $removeDistinct = true;
                    break;
                }
            }
            if ($removeDistinct) {
                $reportquery = 'SELECT ' . $selectedcolumns . ' ' . $reportquery . ' ' . $wheresql;
            } else {
                $reportquery = 'SELECT DISTINCT ' . $selectedcolumns . ' ' . $reportquery . ' ' . $wheresql;
            }
        }
        if ($this->primarymodule) {
            $instance = CRMEntity::getInstance($this->primarymodule);
            $this->table_name = $instance->table_name;
            $this->table_index = $instance->table_index;
        }
        $reportquery = $this->listQueryNonAdminChange($reportquery);
        if (trim($groupsquery) != '' && $type !== 'COLUMNSTOTOTAL') {
            if ($chartReport == true) {
                $reportquery .= 'group by ' . $this->GetFirstSortByField($reportid);
            } else {
                $reportquery .= ' order by ' . $groupsquery;
            }
        }
        if ($allColumnsRestricted) {
            $reportquery .= ' limit 0';
        } else {
            if ($startLimit !== false && $endLimit !== false) {
                $reportquery .= ' LIMIT ' . $startLimit . ', ' . $endLimit;
            }
        }
        preg_match('/&amp;/', $reportquery, $matches);
        if (!empty($matches)) {
            $report = str_replace('&amp;', '&', $reportquery);
            $reportquery = $this->replaceSpecialChar($report);
        }
        $log->info('ReportRun :: Successfully returned sGetSQLforReport' . $reportid);
        if (!$this->_tmptablesinitialized) {
            $this->queryPlanner->initializeTempTables();
            $this->_tmptablesinitialized = true;
        }

        return $reportquery;
    }

    /** function to get the report output in HTML,PDF,TOTAL,PRINT,PRINTTOTAL formats depends on the argument $outputformat.
     *  @ param $outputformat : Type String (valid parameters HTML,PDF,TOTAL,PRINT,PRINT_TOTAL)
     *  @ param $filtersql : Type String
     *  This returns HTML Report if $outputformat is HTML
     *  		Array for PDF if  $outputformat is PDF
     * 		HTML strings for TOTAL if $outputformat is TOTAL
     * 		Array for PRINT if $outputformat is PRINT
     * 		HTML strings for TOTAL fields  if $outputformat is PRINTTOTAL
     * 		HTML strings for
     */
    public function listQueryNonAdminChange($query, $scope = '')
    {
        if (strripos($query, ' WHERE ') !== false) {
            vtlib_setup_modulevars($this->moduleName, $this);
            $query = str_ireplace(' where ', ' WHERE ' . $this->table_name . '.' . $this->table_index . ' > 0  AND ', $query);
        }

        return $query;
    }

    public function GenerateReport($outputformat, $filtersql, $directOutput = false, $startLimit = false, $endLimit = false, $operation = false)
    {
        global $adb;
        global $current_user;
        global $php_max_execution_time;
        global $root_directory;
        global $modules;
        global $app_strings;
        global $mod_strings;
        global $current_language;
        require 'user_privileges/user_privileges_' . $current_user->id . '.php';
        $modules_selected = [];
        $modules_selected[] = $this->primarymodule;
        if (!empty($this->secondarymodule)) {
            $sec_modules = explode(':', $this->secondarymodule);
            for ($i = 0; $i < count($sec_modules); ++$i) {
                $modules_selected[] = $sec_modules[$i];
            }
        }
        $userCurrencyInfo = getCurrencySymbolandCRate($current_user->currency_id);
        $userCurrencySymbol = $userCurrencyInfo['symbol'];
        $referencefieldres = $adb->pquery('SELECT tabid, fieldlabel, uitype from vtiger_field WHERE uitype in (10,101,72)', []);
        if ($referencefieldres) {
            foreach ($referencefieldres as $referencefieldrow) {
                $uiType = $referencefieldrow['uitype'];
                $modprefixedlabel = getTabModuleName($referencefieldrow['tabid']) . ' ' . $referencefieldrow['fieldlabel'];
                $modprefixedlabel = str_replace(' ', '_', $modprefixedlabel);
                if ($uiType == 10 && !in_array($modprefixedlabel, $this->ui10_fields)) {
                    $this->ui10_fields[] = $modprefixedlabel;
                } else {
                    if ($uiType == 101 && !in_array($modprefixedlabel, $this->ui101_fields)) {
                        $this->ui101_fields[] = $modprefixedlabel;
                    } else {
                        if ($uiType == 72 && !in_array($modprefixedlabel, $this->ui72_fields)) {
                            $this->ui72_fields[] = $modprefixedlabel;
                        }
                    }
                }
            }
        }
        if ($outputformat == 'PDF') {
            $reportModel = VReports_Record_Model::getInstanceById($this->reportid);
            $pos = true;
            if ($reportModel != '' && $reportModel->get('reporttype') == 'sql') {
                $sSQL = html_entity_decode(html_entity_decode($reportModel->get('data'), ENT_QUOTES));
                if (isset($_REQUEST['excuteLimit'])) {
                    $sSQL .= ' LIMIT ' . $_REQUEST['excuteLimit'];
                }
                $fileName = $root_directory . '/test/vreports/vreports_sql.conf';
                $current = file_get_contents($fileName);
                $current .= $sSQL . "\n";
                file_put_contents($fileName, $current);
                $pos = strpos(strtoupper(trim($sSQL)), 'SELECT');
            } else {
                $sSQL = $this->sGetSQLforReport($this->reportid, $filtersql, $outputformat, false, $startLimit, $endLimit);
            }
            $strQuery = $adb->convert2sql($sSQL, []);
            if ($pos || isset($pos)) {
                $result = $adb->pquery($sSQL, []);
            }
            if ($is_admin == false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1) {
                $picklistarray = $this->getAccessPickListValues();
            }
            $noofrows = $adb->num_rows($result);
            $arr_val = [];
            if ($noofrows > 0) {
                $y = $adb->num_fields($result);
                $custom_field_values = $adb->fetch_array($result);
                $fieldsList = [];
                for ($i = 0; $i < $y; ++$i) {
                    $field = $adb->field_name($result, $i);
                    if ($reportModel != '' && $reportModel->get('reporttype') == 'sql') {
                        $translatedLabel = $field->name;
                    } else {
                        [$module, $fieldLabel] = explode('_', $field->name, 2);
                        $translatedLabel = getTranslatedString($fieldLabel, $module);
                        if ($fieldLabel == $translatedLabel) {
                            $translatedLabel = getTranslatedString(str_replace('_', ' ', $fieldLabel), $module);
                        } else {
                            $translatedLabel = str_replace('_', ' ', $translatedLabel);
                        }
                        if (strpos($fieldLabel, '_and_') !== false && $translatedLabel == str_replace('_', ' ', $fieldLabel)) {
                            $tempLabel = getTranslatedString(str_replace('and', '&', $translatedLabel), $module);
                            if ($tempLabel !== $translatedLabel) {
                                $translatedLabel = $tempLabel;
                            }
                        }
                        $moduleLabel = '';
                        if (in_array($module, $modules_selected)) {
                            $moduleLabel = getTranslatedString($module, $module);
                        }
                    }
                    $headerLabel = $translatedLabel;
                    if (!empty($this->secondarymodule) && $moduleLabel != '') {
                        $headerLabel = $moduleLabel . ' ' . $translatedLabel;
                    }
                    if (is_array($sec_modules) && in_array(str_replace('_LBL_ACTION', '', $field->name), $sec_modules)) {
                        continue;
                    }
                    $fieldsList[$i]['field'] = $field;
                    $fieldsList[$i]['headerlabel'] = $headerLabel;
                    $fieldsList[$i]['module'] = $module;
                    $fieldsList[$i]['fieldLabel'] = $translatedLabel;
                }
                $arraylists = [];
                $i = 0;
                foreach ($fieldsList as $k => $item) {
                    $fld = $item['field'];
                    $headerLabel = $item['headerlabel'];
                    $fieldvalue = getVReportFieldValue($this, $picklistarray, $fld, $custom_field_values, $i, $operation);
                    if ($fld->name == $this->primarymodule . '_LBL_ACTION' && $fieldvalue != '-' && $operation != 'ExcelExport') {
                        if ($this->primarymodule == 'ModComments') {
                            $fieldvalue = "<a href='index.php?module=" . getSalesEntityType($fieldvalue) . '&view=Detail&record=' . $fieldvalue . "' target='_blank'>" . getTranslatedString('LBL_VIEW_DETAILS', 'Reports') . '</a>';
                        } else {
                            $fieldvalue = "<a href='index.php?module=" . $this->primarymodule . '&view=Detail&record=' . $fieldvalue . "' target='_blank'>" . getTranslatedString('LBL_VIEW_DETAILS', 'VReports') . '</a>';
                        }
                    }
                    ++$i;
                    if (is_array($sec_modules) && in_array(str_replace('_LBL_ACTION', '', $fld->name), $sec_modules)) {
                        continue;
                    }
                    $arraylists[$headerLabel] = $fieldvalue;
                }
                $arr_val[] = $arraylists;
                set_time_limit($php_max_execution_time);
                if (!($custom_field_values = $adb->fetch_array($result))) {
                    $data['data'] = $arr_val;
                }
            }
            $data['count'] = $noofrows;
            $data['fields_list'] = $fieldsList;

            return $data;
        }
        if ($outputformat == 'TOTALXLS') {
            $escapedchars = ['_SUM', '_AVG', '_MIN', '_MAX'];
            $totalpdf = [];
            $sSQL = $this->sGetSQLforReport($this->reportid, $filtersql, 'COLUMNSTOTOTAL');
            $fieldLabelColumns = [];
            $columnsRename = [];
            if (isset($this->totallist) && $sSQL != '') {
                $result = $adb->query($sSQL);
                $y = $adb->num_fields($result);
                $custom_field_values = $adb->fetch_array($result);
                $mod_query_details = [];
                foreach ($this->totallist as $key => $value) {
                    $fieldlist = explode(':', $key);
                    $key = $fieldlist[1] . '_' . $fieldlist[2];
                    if (!isset($mod_query_details[$key]['modulename']) && !isset($mod_query_details[$key]['uitype'])) {
                        $mod_query = $adb->pquery('SELECT distinct(tabid) as tabid, uitype as uitype from vtiger_field where tablename = ? and columnname=?', [$fieldlist[1], $fieldlist[2]]);
                        $moduleName = getTabModuleName($adb->query_result($mod_query, 0, 'tabid'));
                        $mod_query_details[$key]['translatedmodulename'] = getTranslatedString($moduleName, $moduleName);
                        $mod_query_details[$key]['modulename'] = $moduleName;
                        $mod_query_details[$key]['uitype'] = $adb->query_result($mod_query, 0, 'uitype');
                    }
                    if ($adb->num_rows($mod_query) > 0) {
                        $module_name = $mod_query_details[$key]['modulename'];
                        $translatedModuleLabel = $mod_query_details[$key]['translatedmodulename'];
                        $fieldlabel = trim(str_replace($escapedchars, ' ', $fieldlist[3]));
                        $fieldlabel = str_replace('_', ' ', $fieldlabel);
                        if ($module_name) {
                            $field = $module_name . ' ' . getTranslatedString($fieldlabel, $module_name);
                        } else {
                            $field = getTranslatedString($fieldlabel);
                        }
                    }
                    if ($fieldlist[1] == 'vtiger_inventoryproductrel') {
                        $module_name = $this->primarymodule;
                    }
                    $uitype_arr[str_replace($escapedchars, ' ', $module_name . '_' . $fieldlist[3])] = $mod_query_details[$key]['uitype'];
                    $totclmnflds[str_replace($escapedchars, ' ', $module_name . '_' . $fieldlist[3])] = $field;
                    $fieldLabelColumns[str_replace(' ', '_', $field)] = $fieldlist[2];
                }
                for ($i = 0; $i < $y; ++$i) {
                    $fld = $adb->field_name($result, $i);
                    $keyhdr[$fld->name] = $custom_field_values[$i];
                }
                $rowcount = 0;
                foreach ($totclmnflds as $key => $value) {
                    $col_header = trim(str_replace($modules, ' ', $value));
                    $fld_name_1 = $this->primarymodule . '_' . trim($value);
                    $fld_name_2 = $this->secondarymodule . '_' . trim($value);
                    if ($uitype_arr[$key] == 71 || $uitype_arr[$key] == 72 || in_array($fld_name_1, $this->append_currency_symbol_to_value) || in_array($fld_name_2, $this->append_currency_symbol_to_value)) {
                        $col_header .= ' (' . $app_strings['LBL_IN'] . ' ' . $current_user->currency_symbol . ')';
                        $convert_price = true;
                    } else {
                        $convert_price = false;
                    }
                    $value = trim($key);
                    $arraykey = $value . '_SUM';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, false, true);
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                                if (in_array($uitype_arr[$key], [71, 72])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        } else {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true, true);
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                                if (in_array($uitype_arr[$key], [71, 72])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        }
                        $totalpdf[$rowcount][$arraykey] = $conv_value;
                    } else {
                        $totalpdf[$rowcount][$arraykey] = '';
                    }
                    $arraykey = $value . '_AVG';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, false, true);
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                                if (in_array($uitype_arr[$key], [71, 72])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        } else {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true, true);
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                                if (in_array($uitype_arr[$key], [71, 72])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        }
                        $totalpdf[$rowcount][$arraykey] = $conv_value;
                    } else {
                        $totalpdf[$rowcount][$arraykey] = '';
                    }
                    $arraykey = $value . '_MIN';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, false, true);
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                                if (in_array($uitype_arr[$key], [71, 72])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        } else {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true, true);
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                                if (in_array($uitype_arr[$key], [71, 72])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        }
                        $totalpdf[$rowcount][$arraykey] = $conv_value;
                    } else {
                        $totalpdf[$rowcount][$arraykey] = '';
                    }
                    $arraykey = $value . '_MAX';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, false, true);
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                                if (in_array($uitype_arr[$key], [71, 72])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        } else {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true, true);
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                                if (in_array($uitype_arr[$key], [71, 72])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        }
                        $totalpdf[$rowcount][$arraykey] = $conv_value;
                    } else {
                        $totalpdf[$rowcount][$arraykey] = '';
                    }
                    $columnNameField = $fieldLabelColumns[$value];
                    if (array_key_exists($columnNameField, $columnsRename)) {
                        $totalpdf[$rowcount]['rename'] = $columnsRename[$columnNameField]['rename'];
                        $totalpdf[$rowcount]['rename_status'] = $columnsRename[$columnNameField]['rename_status'];
                    } else {
                        $resultRenameColumns = $adb->pquery('SELECT column_rename,column_rename_status FROM vtiger_vreportsummary WHERE columnname LIKE ? AND reportsummaryid = ?', ['%' . $columnNameField . '%', $this->reportid]);

                        while ($rowRenameColum = $adb->fetchByAssoc($resultRenameColumns)) {
                            $totalpdf[$rowcount]['rename'] = $rowRenameColum['column_rename'];
                            $totalpdf[$rowcount]['rename_status'] = $rowRenameColum['column_rename_status'];
                            $columnsRename[$columnNameField]['rename'] = $rowRenameColum['column_rename'];
                            $columnsRename[$columnNameField]['rename_status'] = $rowRenameColum['column_rename_status'];
                        }
                    }
                    ++$rowcount;
                }
            }

            return $totalpdf;
        }
        if ($outputformat == 'XLS') {
            $escapedchars = ['_SUM', '_AVG', '_MIN', '_MAX'];
            $totalpdf = [];
            $sSQL = $this->sGetSQLforReport($this->reportid, $filtersql, 'COLUMNSTOTOTAL');
            if (isset($this->totallist) && $sSQL != '') {
                $result = $adb->query($sSQL);
                $y = $adb->num_fields($result);
                $custom_field_values = $adb->fetch_array($result);
                static $mod_query_details = [];
                foreach ($this->totallist as $key => $value) {
                    $fieldlist = explode(':', $key);
                    $key = $fieldlist[1] . '_' . $fieldlist[2];
                    if (!isset($mod_query_details[$this->reportid][$key]['modulename']) && !isset($mod_query_details[$this->reportid][$key]['uitype'])) {
                        $mod_query = $adb->pquery('SELECT DISTINCT(tabid) AS tabid, uitype AS uitype FROM vtiger_field WHERE tablename = ? AND columnname=?', [$fieldlist[1], $fieldlist[2]]);
                        $moduleName = getTabModuleName($adb->query_result($mod_query, 0, 'tabid'));
                        $mod_query_details[$this->reportid][$key]['translatedmodulename'] = getTranslatedString($moduleName, $moduleName);
                        $mod_query_details[$this->reportid][$key]['modulename'] = $moduleName;
                        $mod_query_details[$this->reportid][$key]['uitype'] = $adb->query_result($mod_query, 0, 'uitype');
                    }
                    if ($adb->num_rows($mod_query) > 0) {
                        $module_name = $mod_query_details[$this->reportid][$key]['modulename'];
                        $translatedModuleLabel = $mod_query_details[$this->reportid][$key]['translatedmodulename'];
                        $fieldlabel = trim(str_replace($escapedchars, ' ', $fieldlist[3]));
                        $fieldlabel = str_replace('_', ' ', $fieldlabel);
                        if ($module_name) {
                            $field = $translatedModuleLabel . ' ' . getTranslatedString($fieldlabel, $module_name);
                        } else {
                            $field = getTranslatedString($fieldlabel);
                        }
                    }
                    if ($fieldlist[1] == 'vtiger_inventoryproductrel') {
                        $module_name = $this->primarymodule;
                    }
                    $uitype_arr[str_replace($escapedchars, ' ', $module_name . '_' . $fieldlist[3])] = $mod_query_details[$this->reportid][$key]['uitype'];
                    $totclmnflds[str_replace($escapedchars, ' ', $module_name . '_' . $fieldlist[3])] = $field;
                }
                $sumcount = 0;
                $avgcount = 0;
                $mincount = 0;
                $maxcount = 0;
                for ($i = 0; $i < $y; ++$i) {
                    $fld = $adb->field_name($result, $i);
                    if (strpos($fld->name, '_SUM') !== false) {
                        ++$sumcount;
                    } else {
                        if (strpos($fld->name, '_AVG') !== false) {
                            ++$avgcount;
                        } else {
                            if (strpos($fld->name, '_MIN') !== false) {
                                ++$mincount;
                            } else {
                                if (strpos($fld->name, '_MAX') !== false) {
                                    ++$maxcount;
                                }
                            }
                        }
                    }
                    $keyhdr[decode_html($fld->name)] = $custom_field_values[$i];
                }
                $rowcount = 0;
                foreach ($totclmnflds as $key => $value) {
                    $col_header = trim(str_replace($modules, ' ', $value));
                    $fld_name_1 = $this->primarymodule . '_' . trim($value);
                    $fld_name_2 = $this->secondarymodule . '_' . trim($value);
                    if ($uitype_arr[$key] == 71 || $uitype_arr[$key] == 72 || $uitype_arr[$key] == 74 || in_array($fld_name_1, $this->append_currency_symbol_to_value) || in_array($fld_name_2, $this->append_currency_symbol_to_value)) {
                        $col_header .= ' (' . $app_strings['LBL_IN'] . ' ' . $current_user->currency_symbol . ')';
                        $convert_price = true;
                    } else {
                        $convert_price = false;
                    }
                    $value = trim($key);
                    $totalpdf[$rowcount]['Field Names'] = $col_header;
                    $originalkey = $value . '_SUM';
                    $arraykey = $this->replaceSpecialChar($value) . '_SUM';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, false, true);
                                if ($uitype_arr[$key] == 74) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                                if (in_array($uitype_arr[$key], [71, 72, 74])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        } else {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true, true);
                                if ($uitype_arr[$key] == 74) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                                if (in_array($uitype_arr[$key], [71, 72, 74])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        }
                        $totalpdf[$rowcount][$originalkey] = $conv_value;
                    } else {
                        if ($sumcount) {
                            $totalpdf[$rowcount][$originalkey] = '';
                        }
                    }
                    $originalkey = $value . '_AVG';
                    $arraykey = $this->replaceSpecialChar($value) . '_AVG';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, false, true);
                                if ($uitype_arr[$key] == 74) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                                if (in_array($uitype_arr[$key], [71, 72, 74])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        } else {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true, true);
                                if ($uitype_arr[$key] == 74) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                                if (in_array($uitype_arr[$key], [71, 72, 74])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        }
                        $totalpdf[$rowcount][$originalkey] = $conv_value;
                    } else {
                        if ($avgcount) {
                            $totalpdf[$rowcount][$originalkey] = '';
                        }
                    }
                    $originalkey = $value . '_MIN';
                    $arraykey = $this->replaceSpecialChar($value) . '_MIN';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, false, true);
                                if ($uitype_arr[$key] == 74) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                                if (in_array($uitype_arr[$key], [71, 72, 74])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        } else {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true, true);
                                if ($uitype_arr[$key] == 74) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                                if (in_array($uitype_arr[$key], [71, 72, 74])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        }
                        $totalpdf[$rowcount][$originalkey] = $conv_value;
                    } else {
                        if ($mincount) {
                            $totalpdf[$rowcount][$originalkey] = '';
                        }
                    }
                    $originalkey = $value . '_MAX';
                    $arraykey = $this->replaceSpecialChar($value) . '_MAX';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, false, true);
                                if ($uitype_arr[$key] == 74) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                                if (in_array($uitype_arr[$key], [71, 72, 74])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        } else {
                            if ($operation == 'ExcelExport') {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true, true);
                                if ($uitype_arr[$key] == 74) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            } else {
                                $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                                if (in_array($uitype_arr[$key], [71, 72, 74])) {
                                    $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                                }
                            }
                        }
                        $totalpdf[$rowcount][$originalkey] = $conv_value;
                    } else {
                        if ($maxcount) {
                            $totalpdf[$rowcount][$originalkey] = '';
                        }
                    }
                    ++$rowcount;
                }
                $totalpdf[$rowcount]['sumcount'] = $sumcount;
                $totalpdf[$rowcount]['avgcount'] = $avgcount;
                $totalpdf[$rowcount]['mincount'] = $mincount;
                $totalpdf[$rowcount]['maxcount'] = $maxcount;
            }

            return $totalpdf;
        }
        if ($outputformat == 'TOTALHTML') {
            $escapedchars = ['_SUM', '_AVG', '_MIN', '_MAX'];
            $sSQL = $this->sGetSQLforReport($this->reportid, $filtersql, 'COLUMNSTOTOTAL');
            static $modulename_cache = [];
            if (isset($this->totallist) && $sSQL != '') {
                $result = $adb->query($sSQL);
                $y = $adb->num_fields($result);
                $custom_field_values = $adb->fetch_array($result);
                $reportModule = 'VReports';
                $coltotalhtml .= "<table align='center' width='60%' cellpadding='3' cellspacing='0' border='0' class='rptTable'><tr><td class='rptCellLabel'>" . vtranslate('LBL_FIELD_NAMES', $reportModule) . "</td><td class='rptCellLabel'>" . vtranslate('LBL_SUM', $reportModule) . "</td><td class='rptCellLabel'>" . vtranslate('LBL_AVG', $reportModule) . "</td><td class='rptCellLabel'>" . vtranslate('LBL_MIN', $reportModule) . "</td><td class='rptCellLabel'>" . vtranslate('LBL_MAX', $reportModule) . '</td></tr>';
                if ($directOutput) {
                    echo $coltotalhtml;
                    $coltotalhtml = '';
                }
                foreach ($this->totallist as $key => $value) {
                    $fieldlist = explode(':', $key);
                    $module_name = null;
                    $cachekey = $fieldlist[1] . ':' . $fieldlist[2];
                    if (!isset($modulename_cache[$cachekey])) {
                        $mod_query = $adb->pquery('SELECT distinct(tabid) as tabid, uitype as uitype from vtiger_field where tablename = ? and columnname=?', [$fieldlist[1], $fieldlist[2]]);
                        if ($adb->num_rows($mod_query) > 0) {
                            $module_name = getTabModuleName($adb->query_result($mod_query, 0, 'tabid'));
                            $modulename_cache[$cachekey] = $module_name;
                        }
                    } else {
                        $module_name = $modulename_cache[$cachekey];
                    }
                    if ($module_name) {
                        $fieldlabel = trim(str_replace($escapedchars, ' ', $fieldlist[3]));
                        $fieldlabel = str_replace('_', ' ', $fieldlabel);
                        $field = getTranslatedString($module_name, $module_name) . ' ' . getTranslatedString($fieldlabel, $module_name);
                    } else {
                        $field = getTranslatedString($fieldlabel);
                    }
                    $uitype_arr[str_replace($escapedchars, ' ', $module_name . '_' . $fieldlist[3])] = $adb->query_result($mod_query, 0, 'uitype');
                    $totclmnflds[str_replace($escapedchars, ' ', $module_name . '_' . $fieldlist[3])] = $field;
                }
                for ($i = 0; $i < $y; ++$i) {
                    $fld = $adb->field_name($result, $i);
                    $keyhdr[$fld->name] = $custom_field_values[$i];
                }
                foreach ($totclmnflds as $key => $value) {
                    $coltotalhtml .= '<tr class="rptGrpHead" valign=top>';
                    $col_header = trim(str_replace($modules, ' ', $value));
                    $fld_name_1 = $this->primarymodule . '_' . trim($value);
                    $fld_name_2 = $this->secondarymodule . '_' . trim($value);
                    if ($uitype_arr[$key] == 71 || $uitype_arr[$key] == 72 || in_array($fld_name_1, $this->append_currency_symbol_to_value) || in_array($fld_name_2, $this->append_currency_symbol_to_value)) {
                        $col_header .= ' (' . $app_strings['LBL_IN'] . ' ' . $current_user->currency_symbol . ')';
                        $convert_price = true;
                    } else {
                        $convert_price = false;
                    }
                    $coltotalhtml .= '<td class="rptData">' . $col_header . '</td>';
                    $value = trim($key);
                    $arraykey = $value . '_SUM';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                        } else {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                        }
                        $coltotalhtml .= '<td class="rptTotal">' . $conv_value . '</td>';
                    } else {
                        $coltotalhtml .= '<td class="rptTotal">&nbsp;</td>';
                    }
                    $arraykey = $value . '_AVG';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                        } else {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                        }
                        $coltotalhtml .= '<td class="rptTotal">' . $conv_value . '</td>';
                    } else {
                        $coltotalhtml .= '<td class="rptTotal">&nbsp;</td>';
                    }
                    $arraykey = $value . '_MIN';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                        } else {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                        }
                        $coltotalhtml .= '<td class="rptTotal">' . $conv_value . '</td>';
                    } else {
                        $coltotalhtml .= '<td class="rptTotal">&nbsp;</td>';
                    }
                    $arraykey = $value . '_MAX';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                        } else {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                        }
                        $coltotalhtml .= '<td class="rptTotal">' . $conv_value . '</td>';
                    } else {
                        $coltotalhtml .= '<td class="rptTotal">&nbsp;</td>';
                    }
                    $coltotalhtml .= '<tr>';
                    if ($directOutput) {
                        echo $coltotalhtml;
                        $coltotalhtml = '';
                    }
                }
                $coltotalhtml .= '</table>';
                if ($directOutput) {
                    echo $coltotalhtml;
                    $coltotalhtml = '';
                }
            }

            return $coltotalhtml;
        }
        if ($outputformat == 'PRINT') {
            $reportData = $this->GenerateReport('PDF', $filtersql);
            if (is_array($reportData) && $reportData['count'] > 0) {
                $data = $reportData['data'];
                $noofrows = $reportData['count'];
                $firstRow = reset($data);
                $headers = array_keys($firstRow);
                foreach ($headers as $headerName) {
                    if ($headerName == 'ACTION' || $headerName == vtranslate('LBL_ACTION', $this->primarymodule) || $headerName == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || $headerName == vtranslate('LBL ACTION', $this->primarymodule) || $key == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                        continue;
                    }
                    $header .= '<th>' . $headerName . '</th>';
                }
                $groupslist = $this->getGroupingList($this->reportid);
                foreach ($groupslist as $reportFieldName => $reportFieldValue) {
                    $nameParts = explode(':', $reportFieldName);
                    [$groupFieldModuleName, $groupFieldName] = explode('_', $nameParts[2], 2);
                    $groupByFieldNames[] = vtranslate(str_replace('_', ' ', $groupFieldName), $groupFieldModuleName);
                }
                $count_groupByFieldNames = 0;
                if (is_array($groupByFieldNames)) {
                    $count_groupByFieldNames = count($groupByFieldNames);
                }
                if ($count_groupByFieldNames > 0) {
                    if ($count_groupByFieldNames == 1) {
                        $firstField = $groupByFieldNames[0];
                    } else {
                        if ($count_groupByFieldNames == 2) {
                            [$firstField, $secondField] = $groupByFieldNames;
                        } else {
                            if ($count_groupByFieldNames == 3) {
                                [$firstField, $secondField, $thirdField] = $groupByFieldNames;
                            }
                        }
                    }
                    $firstValue = ' ';
                    $secondValue = ' ';
                    $thirdValue = ' ';
                    foreach ($data as $key => $valueArray) {
                        $valtemplate .= '<tr>';
                        foreach ($valueArray as $fieldName => $fieldValue) {
                            if ($fieldName == 'ACTION' || $fieldName == vtranslate('LBL_ACTION', $this->primarymodule) || $fieldName == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || $fieldName == vtranslate('LBL ACTION', $this->primarymodule) || $fieldName == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                                continue;
                            }
                            if (($fieldName == $firstField || strstr($fieldName, $firstField)) && ($firstValue == $fieldValue || $firstValue == ' ')) {
                                if ($firstValue == ' ' || $fieldValue == '-') {
                                    $valtemplate .= "<td style='border-bottom: 0;'>" . $fieldValue . '</td>';
                                } else {
                                    $valtemplate .= "<td style='border-bottom: 0; border-top: 0;'>&nbsp;</td>";
                                }
                                if ($fieldValue != ' ') {
                                    $firstValue = $fieldValue;
                                }
                            } else {
                                if (($fieldName == $secondField || strstr($fieldName, $secondField)) && ($secondValue == $fieldValue || $secondValue == ' ')) {
                                    if ($secondValue == ' ' || $secondValue == '-') {
                                        $valtemplate .= "<td style='border-bottom: 0;'>" . $fieldValue . '</td>';
                                    } else {
                                        $valtemplate .= "<td style='border-bottom: 0; border-top: 0;'>&nbsp;</td>";
                                    }
                                    if ($fieldValue != ' ') {
                                        $secondValue = $fieldValue;
                                    }
                                } else {
                                    if (($fieldName == $thirdField || strstr($fieldName, $thirdField)) && ($thirdValue == $fieldValue || $thirdValue == ' ')) {
                                        if ($thirdValue == ' ' || $thirdValue == '-') {
                                            $valtemplate .= "<td style='border-bottom: 0;'>" . $fieldValue . '</td>';
                                        } else {
                                            $valtemplate .= "<td style='border-bottom: 0; border-top: 0;'>&nbsp;</td>";
                                        }
                                        if ($fieldValue != ' ') {
                                            $thirdValue = $fieldValue;
                                        }
                                    } else {
                                        $valtemplate .= "<td style='border-bottom: 0;'>" . $fieldValue . '</td>';
                                        if ($fieldName == $firstField || strstr($fieldName, $firstField)) {
                                            $firstValue = $fieldValue;
                                        } else {
                                            if ($fieldName == $secondField || strstr($fieldName, $secondField)) {
                                                $secondValue = $fieldValue;
                                            } else {
                                                if ($fieldName == $thirdField || strstr($fieldName, $thirdField)) {
                                                    $thirdValue = $fieldValue;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $valtemplate .= '</tr>';
                    }
                } else {
                    foreach ($data as $key => $values) {
                        $valtemplate .= '<tr>';
                        foreach ($values as $fieldName => $value) {
                            if ($fieldName == 'ACTION' || $fieldName == vtranslate('LBL_ACTION', $this->primarymodule) || $fieldName == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || $fieldName == vtranslate('LBL ACTION', $this->primarymodule) || $fieldName == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                                continue;
                            }
                            $valtemplate .= '<td>' . $value . '</td>';
                        }
                    }
                }
                $sHTML = '<thead>' . $header . '</thead><tbody>' . $valtemplate . '</tbody>';
                $return_data[] = $sHTML;
                $return_data[] = $noofrows;
            } else {
                $return_data = ['', 0];
            }

            return $return_data;
        }
        if ($outputformat == 'PRINT_TOTAL') {
            $escapedchars = ['_SUM', '_AVG', '_MIN', '_MAX'];
            $sSQL = $this->sGetSQLforReport($this->reportid, $filtersql, 'COLUMNSTOTOTAL');
            if (isset($this->totallist) && $sSQL != '') {
                $result = $adb->query($sSQL);
                $y = $adb->num_fields($result);
                $custom_field_values = $adb->fetch_array($result);
                $reportModule = 'VReports';
                $coltotalhtml .= "<br /><table align='center' width='60%' cellpadding='3' cellspacing='0' border='1' class='printReport'><tr><td class='rptCellLabel'><b>" . vtranslate('LBL_FIELD_NAMES', $reportModule) . '</b></td><td><b>' . vtranslate('LBL_SUM', $reportModule) . '</b></td><td><b>' . vtranslate('LBL_AVG', $reportModule) . '</b></td><td><b>' . vtranslate('LBL_MIN', $reportModule) . '</b></td><td><b>' . vtranslate('LBL_MAX', $reportModule) . '</b></td></tr>';
                if ($directOutput) {
                    echo $coltotalhtml;
                    $coltotalhtml = '';
                }

                foreach ($this->totallist as $key => $value) {
                    $fieldlist = explode(':', $key);
                    $detailsKey = implode('_', [$fieldlist[1], $fieldlist[2]]);
                    if (!isset($mod_query_details[$detailsKey]['modulename']) && !isset($mod_query_details[$detailsKey]['uitype'])) {
                        $mod_query = $adb->pquery('SELECT distinct(tabid) as tabid, uitype as uitype from vtiger_field where tablename = ? and columnname=?', [$fieldlist[1], $fieldlist[2]]);
                        $moduleName = getTabModuleName($adb->query_result($mod_query, 0, 'tabid'));
                        $mod_query_details[$detailsKey]['modulename'] = $moduleName;
                        $mod_query_details[$detailsKey]['translatedmodulename'] = getTranslatedString($moduleName, $moduleName);
                        $mod_query_details[$detailsKey]['uitype'] = $adb->query_result($mod_query, 0, 'uitype');
                    }
                    if ($adb->num_rows($mod_query) > 0) {
                        $module_name = $mod_query_details[$detailsKey]['modulename'];
                        $translated_moduleName = $mod_query_details[$detailsKey]['translatedmodulename'];
                        $fieldlabel = trim(str_replace($escapedchars, ' ', $fieldlist[3]));
                        $fieldlabel = str_replace('_', ' ', $fieldlabel);
                        if ($module_name) {
                            $field = $translated_moduleName . ' ' . getTranslatedString($fieldlabel, $module_name);
                        } else {
                            $field = getTranslatedString($fieldlabel);
                        }
                    }
                    $uitype_arr[str_replace($escapedchars, ' ', $module_name . '_' . $fieldlist[3])] = $mod_query_details[$detailsKey]['uitype'];
                    $totclmnflds[str_replace($escapedchars, ' ', $module_name . '_' . $fieldlist[3])] = $field;
                }
                for ($i = 0; $i < $y; ++$i) {
                    $fld = $adb->field_name($result, $i);
                    $keyhdr[$fld->name] = $custom_field_values[$i];
                }
                foreach ($totclmnflds as $key => $value) {
                    $coltotalhtml .= '<tr class="rptGrpHead">';
                    $col_header = getTranslatedString(trim(str_replace($modules, ' ', $value)));
                    $fld_name_1 = $this->primarymodule . '_' . trim($value);
                    $fld_name_2 = $this->secondarymodule . '_' . trim($value);
                    if (in_array($uitype_arr[$key], ['71', '72']) || in_array($fld_name_1, $this->append_currency_symbol_to_value) || in_array($fld_name_2, $this->append_currency_symbol_to_value)) {
                        $convert_price = true;
                    } else {
                        $convert_price = false;
                    }
                    $coltotalhtml .= '<td class="rptData">' . $col_header . '</td>';
                    $value = trim($key);
                    $arraykey = $value . '_SUM';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                            $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                        } else {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                        }
                        $coltotalhtml .= "<td class='rptTotal'>" . $conv_value . '</td>';
                    } else {
                        $coltotalhtml .= "<td class='rptTotal'>&nbsp;</td>";
                    }
                    $arraykey = $value . '_AVG';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                            $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                        } else {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                        }
                        $coltotalhtml .= "<td class='rptTotal'>" . $conv_value . '</td>';
                    } else {
                        $coltotalhtml .= "<td class='rptTotal'>&nbsp;</td>";
                    }
                    $arraykey = $value . '_MIN';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                            $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                        } else {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                        }
                        $coltotalhtml .= "<td class='rptTotal'>" . $conv_value . '</td>';
                    } else {
                        $coltotalhtml .= "<td class='rptTotal'>&nbsp;</td>";
                    }
                    $arraykey = $value . '_MAX';
                    if (isset($keyhdr[$arraykey])) {
                        if ($convert_price) {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey]);
                            $conv_value = CurrencyField::appendCurrencySymbol($conv_value, $userCurrencySymbol);
                        } else {
                            $conv_value = CurrencyField::convertToUserFormat($keyhdr[$arraykey], null, true);
                        }
                        $coltotalhtml .= "<td class='rptTotal'>" . $conv_value . '</td>';
                    } else {
                        $coltotalhtml .= "<td class='rptTotal'>&nbsp;</td>";
                    }
                    $coltotalhtml .= '</tr>';
                    if ($directOutput) {
                        echo $coltotalhtml;
                        $coltotalhtml = '';
                    }
                }
                $coltotalhtml .= '</table>';
                if ($directOutput) {
                    echo $coltotalhtml;
                    $coltotalhtml = '';
                }
            }

            return $coltotalhtml;
        }




    }

    public function getColumnsTotal($reportid)
    {
        if ($this->_columnstotallist !== false) {
            return $this->_columnstotallist;
        }
        global $adb;
        global $modules;
        global $log;
        global $current_user;
        static $modulename_cache = [];
        $query = 'select primarymodule,secondarymodules from vtiger_vreportmodules where reportmodulesid =?';
        $res = $adb->pquery($query, [$reportid]);
        $modrow = $adb->fetch_array($res);
        $premod = $modrow['primarymodule'];
        $secmod = $modrow['secondarymodules'];
        $coltotalsql = 'select vtiger_vreportsummary.* from vtiger_vreport';
        $coltotalsql .= ' inner join vtiger_vreportsummary on vtiger_vreport.reportid = vtiger_vreportsummary.reportsummaryid';
        $coltotalsql .= ' where vtiger_vreport.reportid =?';
        $result = $adb->pquery($coltotalsql, [$reportid]);

        while ($coltotalrow = $adb->fetch_array($result)) {
            $fieldcolname = $coltotalrow['columnname'];
            if ($fieldcolname != 'none') {
                $fieldlist = explode(':', $fieldcolname);
                [$field_tablename, $field_columnname] = $fieldlist;
                $cachekey = $field_tablename . ':' . $field_columnname;
                if (!isset($modulename_cache[$cachekey])) {
                    $mod_query = $adb->pquery('SELECT distinct(tabid) as tabid from vtiger_field where tablename = ? and columnname=?', [$fieldlist[1], $fieldlist[2]]);
                    if ($adb->num_rows($mod_query) > 0) {
                        $module_name = getTabModuleName($adb->query_result($mod_query, 0, 'tabid'));
                        $modulename_cache[$cachekey] = $module_name;
                    }
                } else {
                    $module_name = $modulename_cache[$cachekey];
                }
                $fieldlabel = trim($fieldlist[3]);
                if ($field_tablename == 'vtiger_inventoryproductrel') {
                    $field_columnalias = $premod . '_' . $fieldlist[3];
                } else {
                    if ($module_name) {
                        $field_columnalias = $module_name . '_' . $fieldlist[3];
                    } else {
                        $field_columnalias = $module_name . '_' . $fieldlist[3];
                    }
                }
                $field_permitted = false;
                if (CheckColumnPermission($field_tablename, $field_columnname, $premod) != 'false') {
                    $field_permitted = true;
                } else {
                    $mod = explode(':', $secmod);
                    foreach ($mod as $key) {
                        if (CheckColumnPermission($field_tablename, $field_columnname, $key) != 'false') {
                            $field_permitted = true;
                        }
                    }
                }
                $secondaryModules = explode(':', $secmod);
                if ($field_permitted === false && ($premod === 'Calendar' || in_array('Calendar', $secondaryModules)) && CheckColumnPermission($field_tablename, $field_columnname, 'Events') != 'false') {
                    $field_permitted = true;
                }
                if ($field_permitted == true) {
                    $field = $this->getColumnsTotalSQL($fieldlist, $premod);
                    if ($fieldlist[4] == 2) {
                        $stdfilterlist[$fieldcolname] = 'sum(' . $field . ") '" . $field_columnalias . "'";
                    }
                    if ($fieldlist[4] == 3) {
                        $stdfilterlist[$fieldcolname] = '(sum(' . $field . ")/count(*)) '" . $field_columnalias . "'";
                    }
                    if ($fieldlist[4] == 4) {
                        $stdfilterlist[$fieldcolname] = 'min(' . $field . ") '" . $field_columnalias . "'";
                    }
                    if ($fieldlist[4] == 5) {
                        $stdfilterlist[$fieldcolname] = 'max(' . $field . ") '" . $field_columnalias . "'";
                    }
                    $this->queryPlanner->addTable($field_tablename);
                }
            }
        }
        $this->_columnstotallist = $stdfilterlist;
        $log->info('ReportRun :: Successfully returned getColumnsTotal' . $reportid);

        return $stdfilterlist;
    }

    public function getColumnsTotalSQL($fieldlist, $premod)
    {
        if ($fieldlist[0] == 'cb') {
            [$field_tablename, $field_columnname] = $fieldlist;
        } else {
            [$field_tablename, $field_columnname] = $fieldlist;
            [$module, $fieldName] = explode('_', $fieldlist[2], 2);
        }
        $field = $field_tablename . '.' . $field_columnname;
        if ($field_tablename == 'vtiger_products' && $field_columnname == 'unit_price') {
            $field = ' innerProduct.actual_unit_price';
            $this->queryPlanner->addTable('innerProduct');
        }
        if ($field_tablename == 'vtiger_service' && $field_columnname == 'unit_price') {
            $field = ' innerService.actual_unit_price';
            $this->queryPlanner->addTable('innerService');
        }
        if (($field_tablename == 'vtiger_invoice' || $field_tablename == 'vtiger_quotes' || $field_tablename == 'vtiger_purchaseorder' || $field_tablename == 'vtiger_salesorder') && ($field_columnname == 'total' || $field_columnname == 'subtotal' || $field_columnname == 'discount_amount' || $field_columnname == 's_h_amount' || $field_columnname == 'paid' || $field_columnname == 'balance' || $field_columnname == 'received' || $field_columnname == 'adjustment' || $field_columnname == 'pre_tax_total')) {
            $field = ' ' . $field_tablename . '.' . $field_columnname . '/' . $field_tablename . '.conversion_rate ';
        }
        if ($field_tablename == 'vtiger_inventoryproductrel') {
            $this->lineItemFieldsInCalculation = true;
            $secondaryModules = explode(':', $this->secondarymodule);
            $inventoryModules = getInventoryModules();
            if (in_array($premod, $inventoryModules)) {
                $inventoryModuleInstance = CRMEntity::getInstance($premod);
                $inventoryModuleName = $premod;
            } else {
                foreach ($secondaryModules as $secondaryModule) {
                    if (in_array($secondaryModule, $inventoryModules)) {
                        $inventoryModuleName = $secondaryModule;
                        $inventoryModuleInstance = CRMEntity::getInstance($secondaryModule);
                        $secmodule = $secondaryModule;
                        break;
                    }
                }
            }
            $field = $field_tablename . 'tmp' . $inventoryModuleName . '.' . $field_columnname;
            $itemTableName = 'vtiger_inventoryproductreltmp' . $inventoryModuleName;
            $this->queryPlanner->addTable($itemTableName);
            if ($field_columnname == 'listprice') {
                $field = $field . '/' . $inventoryModuleInstance->table_name . '.conversion_rate';
            } else {
                if ($field_columnname == 'discount_amount') {
                    $field = ' CASE WHEN ' . $itemTableName . '.discount_amount is not null THEN ' . $itemTableName . '.discount_amount/' . $inventoryModuleInstance->table_name . '.conversion_rate WHEN ' . $itemTableName . '.discount_percent IS NOT NULL THEN (' . $itemTableName . '.listprice*' . $itemTableName . '.quantity*' . $itemTableName . '.discount_percent/100/' . $inventoryModuleInstance->table_name . '.conversion_rate) ELSE 0 END ';
                }
            }
        }

        return $field;
    }

    /** function to get query for the columns to total for the given reportid.
     *  @ param $reportid : Type integer
     *  This returns columnstoTotal query for the reportid
     */
    public function getColumnsToTotalColumns($reportid)
    {
        global $adb;
        global $modules;
        global $log;
        $sreportstdfiltersql = 'select vtiger_vreportsummary.* from vtiger_vreport';
        $sreportstdfiltersql .= ' inner join vtiger_vreportsummary on vtiger_vreport.reportid = vtiger_vreportsummary.reportsummaryid';
        $sreportstdfiltersql .= ' where vtiger_vreport.reportid =?';
        $result = $adb->pquery($sreportstdfiltersql, [$reportid]);
        $noofrows = $adb->num_rows($result);
        for ($i = 0; $i < $noofrows; ++$i) {
            $fieldcolname = $adb->query_result($result, $i, 'columnname');
            if ($fieldcolname != 'none') {
                $fieldlist = explode(':', $fieldcolname);
                if ($fieldlist[4] == 2) {
                    $sSQLList[] = 'sum(' . $fieldlist[1] . '.' . $fieldlist[2] . ') ' . $fieldlist[3];
                }
                if ($fieldlist[4] == 3) {
                    $sSQLList[] = 'avg(' . $fieldlist[1] . '.' . $fieldlist[2] . ') ' . $fieldlist[3];
                }
                if ($fieldlist[4] == 4) {
                    $sSQLList[] = 'min(' . $fieldlist[1] . '.' . $fieldlist[2] . ') ' . $fieldlist[3];
                }
                if ($fieldlist[4] == 5) {
                    $sSQLList[] = 'max(' . $fieldlist[1] . '.' . $fieldlist[2] . ') ' . $fieldlist[3];
                }
            }
        }
        if (isset($sSQLList)) {
            $sSQL = implode(',', $sSQLList);
        }
        $log->info('ReportRun :: Successfully returned getColumnsToTotalColumns' . $reportid);

        return $sSQL;
    }

    /** Function to convert the Report Header Names into i18n.
     *  @param $fldname: Type Varchar
     *  Returns Language Converted Header Strings
     * */
    public function getLstringforReportHeaders($fldname)
    {
        global $modules;
        global $current_language;
        global $current_user;
        global $app_strings;
        $rep_header = ltrim($fldname);
        $rep_header = decode_html($rep_header);
        $labelInfo = explode('_', $rep_header);
        $rep_module = $labelInfo[0];
        if (is_array($this->labelMapping) && !empty($this->labelMapping[$rep_header])) {
            $rep_header = $this->labelMapping[$rep_header];
        } else {
            if ($rep_module == 'LBL') {
                $rep_module = '';
            }
            array_shift($labelInfo);
            $fieldLabel = decode_html(implode('_', $labelInfo));
            $rep_header_temp = preg_replace('/\\s+/', '_', $fieldLabel);
            $rep_header = (string) $rep_module . ' ' . $fieldLabel;
        }
        $curr_symb = '';
        $fieldLabel = ltrim(str_replace($rep_module, '', $rep_header), '_');
        $fieldInfo = getFieldByVReportLabel($rep_module, $fieldLabel);
        if ($fieldInfo['uitype'] == '71') {
            $curr_symb = ' (' . $app_strings['LBL_IN'] . ' ' . $current_user->currency_symbol . ')';
        }
        $rep_header .= $curr_symb;

        return $rep_header;
    }

    /** Function to get picklist value array based on profile
     *          *  returns permitted fields in array format.
     * */
    public function getAccessPickListValues()
    {
        global $adb;
        global $current_user;
        $id = [getTabid($this->primarymodule)];
        if ($this->secondarymodule != '') {
            array_push($id, getTabid($this->secondarymodule));
        }
        $query = 'select fieldname,columnname,fieldid,fieldlabel,tabid,uitype from vtiger_field where tabid in(' . generateQuestionMarks($id) . ') and uitype in (15,33,55)';
        $result = $adb->pquery($query, $id);
        $roleid = $current_user->roleid;
        $roleids = $roleid;
        $temp_status = [];
        for ($i = 0; $i < $adb->num_rows($result); ++$i) {
            $fieldname = $adb->query_result($result, $i, 'fieldname');
            $fieldlabel = $adb->query_result($result, $i, 'fieldlabel');
            $tabid = $adb->query_result($result, $i, 'tabid');
            $uitype = $adb->query_result($result, $i, 'uitype');
            $fieldlabel1 = str_replace(' ', '_', $fieldlabel);
            $keyvalue = getTabModuleName($tabid) . '_' . $fieldlabel1;
            $fieldvalues = [];
            if (count($roleids) > 1) {
                $mulsel = 'select distinct ' . $fieldname . ' from vtiger_' . $fieldname . ' inner join vtiger_role2picklist on vtiger_role2picklist.picklistvalueid = vtiger_' . $fieldname . '.picklist_valueid where roleid in ("' . implode($roleids, '","') . '") and picklistvalueid in (select picklist_valueid from vtiger_' . $fieldname . ')';
            } else {
                $mulsel = 'select distinct ' . $fieldname . ' from vtiger_' . $fieldname . ' inner join vtiger_role2picklist on vtiger_role2picklist.picklistvalueid = vtiger_' . $fieldname . ".picklist_valueid where roleid ='" . $roleid . "' and picklistvalueid in (select picklist_valueid from vtiger_" . $fieldname . ')';
            }
            if ($fieldname != 'firstname') {
                $mulselresult = $adb->query($mulsel);
            }
            for ($j = 0; $j < $adb->num_rows($mulselresult); ++$j) {
                $fldvalue = $adb->query_result($mulselresult, $j, $fieldname);
                if (in_array($fldvalue, $fieldvalues)) {
                    continue;
                }
                $fieldvalues[] = $fldvalue;
            }
            $field_count = count($fieldvalues);
            if ($uitype == 15 && $field_count > 0 && ($fieldname == 'taskstatus' || $fieldname == 'eventstatus')) {
                $temp_count = count($temp_status[$keyvalue]);
                if ($temp_count > 0) {
                    for ($t = 0; $t < $field_count; ++$t) {
                        $temp_status[$keyvalue][$temp_count + $t] = $fieldvalues[$t];
                    }
                    $fieldvalues = $temp_status[$keyvalue];
                } else {
                    $temp_status[$keyvalue] = $fieldvalues;
                }
            }
            if ($uitype == 33) {
                $fieldlists[1][$keyvalue] = $fieldvalues;
            } else {
                if ($uitype == 55 && $fieldname == 'salutationtype') {
                    $fieldlists[$keyvalue] = $fieldvalues;
                } else {
                    if ($uitype == 15) {
                        $fieldlists[$keyvalue] = $fieldvalues;
                    }
                }
            }
        }

        return $fieldlists;
    }

    public function getReportPDF($filterlist = false)
    {
        require_once 'libraries/tcpdf/tcpdf.php';
        $reportData = $this->GenerateReport('PDF', $filterlist);
        $arr_val = $reportData['data'];
        if (isset($arr_val)) {
            foreach ($arr_val as $wkey => $warray_value) {
                foreach ($warray_value as $whd => $wvalue) {
                    if (strlen($wvalue) < strlen($whd)) {
                        $w_inner_array[] = strlen($whd);
                    } else {
                        $w_inner_array[] = strlen($wvalue);
                    }
                }
                $warr_val[] = $w_inner_array;
                unset($w_inner_array);
            }
            foreach ($warr_val[0] as $fkey => $fvalue) {
                foreach ($warr_val as $wkey => $wvalue) {
                    $f_inner_array[] = $warr_val[$wkey][$fkey];
                }
                sort($f_inner_array, 1);
                $farr_val[] = $f_inner_array;
                unset($f_inner_array);
            }
            foreach ($farr_val as $skkey => $skvalue) {
                if ($skvalue[count($arr_val) - 1] == 1) {
                    $col_width[] = $skvalue[count($arr_val) - 1] * 50;
                } else {
                    $col_width[] = $skvalue[count($arr_val) - 1] * 10 + 10;
                }
            }
            $count = 0;
            foreach ($arr_val[0] as $key => $value) {
                $headerHTML .= '<td width="' . $col_width[$count] . '" bgcolor="#DDDDDD"><b>' . $this->getLstringforReportHeaders($key) . '</b></td>';
                $count = $count + 1;
            }
            foreach ($arr_val as $key => $array_value) {
                $valueHTML = '';
                $count = 0;
                foreach ($array_value as $hd => $value) {
                    $valueHTML .= '<td width="' . $col_width[$count] . '">' . $value . '</td>';
                    $count = $count + 1;
                }
                $dataHTML .= '<tr>' . $valueHTML . '</tr>';
            }
        }
        $totalpdf = $this->GenerateReport('PRINT_TOTAL', $filterlist);
        $html = '<table border="0.5"><tr>' . $headerHTML . '</tr>' . $dataHTML . '<tr><td>' . $totalpdf . '</td></tr></table>';
        $columnlength = array_sum($col_width);
        if ($columnlength > 14_400) {
            exit('<br><br><center>' . $app_strings['LBL_PDF'] . " <a href='javascript:window.history.back()'>" . $app_strings['LBL_GO_BACK'] . '.</a></center>');
        }
        if ($columnlength <= 420) {
            $pdf = new TCPDF('P', 'mm', 'A5', true);
        } else {
            if ($columnlength >= 421 && $columnlength <= 1_120) {
                $pdf = new TCPDF('L', 'mm', 'A3', true);
            } else {
                if ($columnlength >= 1_121 && $columnlength <= 1_600) {
                    $pdf = new TCPDF('L', 'mm', 'A2', true);
                } else {
                    if ($columnlength >= 1_601 && $columnlength <= 2_200) {
                        $pdf = new TCPDF('L', 'mm', 'A1', true);
                    } else {
                        if ($columnlength >= 2_201 && $columnlength <= 3_370) {
                            $pdf = new TCPDF('L', 'mm', 'A0', true);
                        } else {
                            if ($columnlength >= 3_371 && $columnlength <= 4_690) {
                                $pdf = new TCPDF('L', 'mm', '2A0', true);
                            } else {
                                if ($columnlength >= 4_691 && $columnlength <= 6_490) {
                                    $pdf = new TCPDF('L', 'mm', '4A0', true);
                                } else {
                                    $columnhight = count($arr_val) * 15;
                                    $format = [$columnhight, $columnlength];
                                    $pdf = new TCPDF('L', 'mm', $format, true);
                                }
                            }
                        }
                    }
                }
            }
        }
        $pdf->SetMargins(10, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdf->setLanguageArray($l);
        $pdf->AddPage();
        $pdf->SetFillColor(224, 235, 255);
        $pdf->SetTextColor(0);
        $pdf->SetFont('FreeSerif', 'B', 14);
        $pdf->Cell($pdf->columnlength * 50, 10, getTranslatedString($oReport->reportname), 0, 0, 'C', 0);
        $pdf->Ln();
        $pdf->SetFont('FreeSerif', '', 10);
        $pdf->writeHTML($html);

        return $pdf;
    }

    public function writeReportToExcelFile($fileName, $filterlist = '')
    {
        global $currentModule;
        global $current_language;
        $mod_strings = return_module_language($current_language, $currentModule);
        require_once 'modules/VReports/PHPExcel/PHPExcel.php';
        $workbook = new PHPExcel();
        $worksheet = $workbook->setActiveSheetIndex(0);
        $reportData = $this->GenerateReport('PDF', $filterlist, false, false, false, 'ExcelExport');
        $arr_val = $reportData['data'];
        $totalxls = $this->GenerateReport('XLS', $filterlist, false, false, false, 'ExcelExport');
        $numericTypes = ['currency', 'double', 'integer', 'percentage'];
        $header_styles = ['fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'E1E0F7']]];
        if (isset($arr_val)) {
            $count = 0;
            $rowcount = 1;
            $arrayFirstRowValues = $arr_val[0];
            foreach ($arrayFirstRowValues as $key => $value) {
                if ($key == 'ACTION' || $key == vtranslate('LBL_ACTION', $this->primarymodule) || $key == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || $key == vtranslate('LBL ACTION', $this->primarymodule) || $key == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                    continue;
                }
                $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, decode_html($key), true);
                $worksheet->getStyleByColumnAndRow($count, $rowcount)->applyFromArray($header_styles);
                $count = $count + 1;
            }
            ++$rowcount;
            foreach ($arr_val as $key => $array_value) {
                $count = 0;
                foreach ($array_value as $hdr => $valueDataType) {
                    if (is_array($valueDataType)) {
                        $value = $valueDataType['value'];
                        $dataType = $valueDataType['type'];
                    } else {
                        $value = $valueDataType;
                        $dataType = '';
                    }
                    if ($hdr == 'ACTION' || $hdr == vtranslate('LBL_ACTION', $this->primarymodule) || $hdr == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || $hdr == vtranslate('LBL ACTION', $this->primarymodule) || $hdr == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                        continue;
                    }
                    $value = decode_html($value);
                    if (in_array($dataType, $numericTypes)) {
                        $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    } else {
                        $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $count = $count + 1;
                }
                ++$rowcount;
            }
            ++$rowcount;
            $count = 0;
            if (is_array($totalxls[0])) {
                foreach ($totalxls[0] as $key => $value) {
                    $exploedKey = explode('_', $key);
                    $chdr = end($exploedKey);
                    $translated_str = in_array($chdr, array_keys($mod_strings)) ? $mod_strings[$chdr] : $chdr;
                    $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $translated_str);
                    $worksheet->getStyleByColumnAndRow($count, $rowcount)->applyFromArray($header_styles);
                    $count = $count + 1;
                }
            }
            $ignoreValues = ['sumcount', 'avgcount', 'mincount', 'maxcount'];
            ++$rowcount;
            foreach ($totalxls as $key => $array_value) {
                $count = 0;
                foreach ($array_value as $hdr => $value) {
                    if (in_array($hdr, $ignoreValues)) {
                        continue;
                    }
                    $value = decode_html($value);
                    $excelDatatype = PHPExcel_Cell_DataType::TYPE_STRING;
                    if (is_numeric($value)) {
                        $excelDatatype = PHPExcel_Cell_DataType::TYPE_NUMERIC;
                    }
                    $worksheet->setCellValueExplicitByColumnAndRow($count, $key + $rowcount, $value, $excelDatatype);
                    $count = $count + 1;
                }
            }
        }
        ob_end_clean();
        ob_clean();
        $workbookWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel5');
        $workbookWriter->save($fileName);
    }

    public function writeReportToCSVFile($fileName, $filterlist = '')
    {
        global $currentModule;
        global $current_language;
        $mod_strings = return_module_language($current_language, $currentModule);
        $reportData = $this->GenerateReport('PDF', $filterlist);
        $arr_val = $reportData['data'];
        $fp = fopen($fileName, 'w+');
        if (isset($arr_val)) {
            $csv_values = [];
            $csv_values = array_map('decode_html', array_keys($arr_val[0]));
            $unsetValue = false;
            if (end($csv_values) == vtranslate('LBL_ACTION', $this->primarymodule) || end($csv_values) == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || end($csv_values) == vtranslate('LBL ACTION', $this->primarymodule) || end($csv_values) == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                unset($csv_values[count($csv_values) - 1]);
                $unsetValue = true;
            }
            fputcsv($fp, $csv_values);
            foreach ($arr_val as $key => $array_value) {
                if ($unsetValue) {
                    array_pop($array_value);
                }
                $csv_values = array_map('decode_html', array_values($array_value));
                fputcsv($fp, $csv_values);
            }
        }
        fclose($fp);
    }

    public function getGroupByTimeList($reportId)
    {
        global $adb;
        if ($this->_groupbycondition !== false) {
            return $this->_groupbycondition;
        }
        $groupByTimeQuery = 'SELECT * FROM vtiger_vreportgroupbycolumn WHERE reportid=?';
        $groupByTimeRes = $adb->pquery($groupByTimeQuery, [$reportId]);
        $num_rows = $adb->num_rows($groupByTimeRes);
        for ($i = 0; $i < $num_rows; ++$i) {
            $sortColName = $adb->query_result($groupByTimeRes, $i, 'sortcolname');
            [$tablename, $colname, $module_field, $fieldname, $single] = explode(':', $sortColName);
            $groupField = $module_field;
            $groupCriteria = $adb->query_result($groupByTimeRes, $i, 'dategroupbycriteria');
            if (in_array($groupCriteria, array_keys($this->groupByTimeParent))) {
                $parentCriteria = $this->groupByTimeParent[$groupCriteria];
                foreach ($parentCriteria as $criteria) {
                    $groupByCondition[] = $this->GetTimeCriteriaCondition($criteria, $groupField);
                }
            }
            $groupByCondition[] = $this->GetTimeCriteriaCondition($groupCriteria, $groupField);
            $this->queryPlanner->addTable($tablename);
        }
        $this->_groupbycondition = $groupByCondition;

        return $groupByCondition;
    }

    public function GetTimeCriteriaCondition($criteria, $dateField)
    {
        $condition = '';
        if (strtolower($criteria) == 'year') {
            $condition = 'DATE_FORMAT(' . $dateField . ", '%Y' )";
        } else {
            if (strtolower($criteria) == 'month') {
                $condition = 'CEIL(DATE_FORMAT(' . $dateField . ",'%m')%13)";
            } else {
                if (strtolower($criteria) == 'quarter') {
                    $condition = 'CEIL(DATE_FORMAT(' . $dateField . ",'%m')/3)";
                }
            }
        }

        return $condition;
    }

    public function GetFirstSortByField($reportid)
    {
        global $adb;
        $groupByField = '';
        $sortFieldQuery = "SELECT * FROM vtiger_vreportsortcol\n                            LEFT JOIN vtiger_vreportgroupbycolumn ON (vtiger_vreportsortcol.sortcolid = vtiger_vreportgroupbycolumn.sortid and vtiger_vreportsortcol.reportid = vtiger_vreportgroupbycolumn.reportid)\n                            WHERE columnname!='none' and vtiger_vreportsortcol.reportid=? ORDER By sortcolid";
        $sortFieldResult = $adb->pquery($sortFieldQuery, [$reportid]);
        $inventoryModules = getInventoryModules();
        if ($adb->num_rows($sortFieldResult) > 0) {
            $fieldcolname = $adb->query_result($sortFieldResult, 0, 'columnname');
            [$tablename, $colname, $module_field, $fieldname, $typeOfData] = explode(':', $fieldcolname);
            [$modulename, $fieldlabel] = explode('_', $module_field, 2);
            $groupByField = $module_field;
            if ($typeOfData == 'D') {
                $groupCriteria = $adb->query_result($sortFieldResult, 0, 'dategroupbycriteria');
                if (strtolower($groupCriteria) != 'none') {
                    if (in_array($groupCriteria, array_keys($this->groupByTimeParent))) {
                        $parentCriteria = $this->groupByTimeParent[$groupCriteria];
                        foreach ($parentCriteria as $criteria) {
                            $groupByCondition[] = $this->GetTimeCriteriaCondition($criteria, $groupByField);
                        }
                    }
                    $groupByCondition[] = $this->GetTimeCriteriaCondition($groupCriteria, $groupByField);
                    $groupByField = implode(', ', $groupByCondition);
                }
            } else {
                if (CheckFieldPermission($fieldname, $modulename) != 'true' && !(in_array($modulename, $inventoryModules) && $fieldname == 'serviceid')) {
                    $groupByField = $tablename . '.' . $colname;
                }
            }
        }

        return $groupByField;
    }

    public function getReferenceFieldColumnList($moduleName, $fieldInfo)
    {
        $adb = PearDatabase::getInstance();
        $columnsSqlList = [];
        $fieldInstance = WebserviceField::fromArray($adb, $fieldInfo);
        $referenceModuleList = $fieldInstance->getReferenceList(false);
        if (in_array('Calendar', $referenceModuleList) && in_array('Events', $referenceModuleList)) {
            $eventKey = array_keys($referenceModuleList, 'Events');
            unset($referenceModuleList[$eventKey[0]]);
        }
        $reportSecondaryModules = explode(':', $this->secondarymodule);
        if ($moduleName != $this->primarymodule && in_array($this->primarymodule, $referenceModuleList)) {
            $entityTableFieldNames = getEntityFieldNames($this->primarymodule);
            $entityTableName = $entityTableFieldNames['tablename'];
            $entityFieldNames = $entityTableFieldNames['fieldname'];
            $columnList = [];
            if (is_array($entityFieldNames)) {
                foreach ($entityFieldNames as $entityColumnName) {
                    $columnList[(string) $entityColumnName] = (string) $entityTableName . '.' . $entityColumnName;
                }
            } else {
                $columnList[] = (string) $entityTableName . '.' . $entityFieldNames;
            }
            if (count($columnList) > 1) {
                $columnSql = getSqlForNameInDisplayFormat($columnList, $this->primarymodule);
            } else {
                $columnSql = implode('', $columnList);
            }
            $columnsSqlList[] = $columnSql;
        } else {
            foreach ($referenceModuleList as $referenceModule) {
                if (!vtlib_isModuleActive($referenceModule)) {
                    continue;
                }
                $entityTableFieldNames = getEntityFieldNames($referenceModule);
                $entityTableName = $entityTableFieldNames['tablename'];
                $entityFieldNames = $entityTableFieldNames['fieldname'];
                $fieldName = $fieldInstance->getFieldName();
                $referenceTableName = '';
                $dependentTableName = '';
                if ($moduleName == 'Calendar' && $referenceModule == 'Contacts' && $fieldName == 'contact_id') {
                    $referenceTableName = 'vtiger_contactdetailsCalendar';
                } else {
                    if ($moduleName == 'Calendar' && $fieldName == 'parent_id') {
                        $referenceTableName = $entityTableName . 'RelCalendar';
                    } else {
                        if ($moduleName == 'HelpDesk' && $referenceModule == 'Accounts' && $fieldName == 'parent_id') {
                            $referenceTableName = 'vtiger_accountRelHelpDesk';
                        } else {
                            if ($moduleName == 'HelpDesk' && $referenceModule == 'Contacts' && $fieldName == 'contact_id') {
                                $referenceTableName = 'vtiger_contactdetailsRelHelpDesk';
                            } else {
                                if ($moduleName == 'HelpDesk' && $referenceModule == 'Products' && $fieldName == 'product_id') {
                                    $referenceTableName = 'vtiger_productsRel';
                                } else {
                                    if ($moduleName == 'Contacts' && $referenceModule == 'Accounts' && $fieldName == 'account_id') {
                                        $referenceTableName = 'vtiger_accountContacts';
                                    } else {
                                        if ($moduleName == 'Contacts' && $referenceModule == 'Contacts' && $fieldName == 'contact_id') {
                                            $referenceTableName = 'vtiger_contactdetailsContacts';
                                        } else {
                                            if ($moduleName == 'Accounts' && $referenceModule == 'Accounts' && $fieldName == 'account_id') {
                                                $referenceTableName = 'vtiger_accountAccounts';
                                            } else {
                                                if ($moduleName == 'Campaigns' && $referenceModule == 'Products' && $fieldName == 'product_id') {
                                                    $referenceTableName = 'vtiger_productsCampaigns';
                                                } else {
                                                    if ($moduleName == 'Faq' && $referenceModule == 'Products' && $fieldName == 'product_id') {
                                                        $referenceTableName = 'vtiger_productsFaq';
                                                    } else {
                                                        if ($moduleName == 'Invoice' && $referenceModule == 'SalesOrder' && $fieldName == 'salesorder_id') {
                                                            $referenceTableName = 'vtiger_salesorderInvoice';
                                                        } else {
                                                            if ($moduleName == 'Invoice' && $referenceModule == 'Contacts' && $fieldName == 'contact_id') {
                                                                $referenceTableName = 'vtiger_contactdetailsInvoice';
                                                            } else {
                                                                if ($moduleName == 'Invoice' && $referenceModule == 'Accounts' && $fieldName == 'account_id') {
                                                                    $referenceTableName = 'vtiger_accountInvoice';
                                                                } else {
                                                                    if ($moduleName == 'Potentials' && $referenceModule == 'Campaigns' && $fieldName == 'campaignid') {
                                                                        $referenceTableName = 'vtiger_campaignPotentials';
                                                                    } else {
                                                                        if ($moduleName == 'Products' && $referenceModule == 'Vendors' && $fieldName == 'vendor_id') {
                                                                            $referenceTableName = 'vtiger_vendorRelProducts';
                                                                        } else {
                                                                            if ($moduleName == 'PurchaseOrder' && $referenceModule == 'Contacts' && $fieldName == 'contact_id') {
                                                                                $referenceTableName = 'vtiger_contactdetailsPurchaseOrder';
                                                                            } else {
                                                                                if ($moduleName == 'PurchaseOrder' && $referenceModule == 'Accounts' && $fieldName == 'accountid') {
                                                                                    $referenceTableName = 'vtiger_accountsPurchaseOrder';
                                                                                } else {
                                                                                    if ($moduleName == 'PurchaseOrder' && $referenceModule == 'Vendors' && $fieldName == 'vendor_id') {
                                                                                        $referenceTableName = 'vtiger_vendorRelPurchaseOrder';
                                                                                    } else {
                                                                                        if ($moduleName == 'Subscription' && $referenceModule == 'Contacts' && $fieldName == 'contact_id') {
                                                                                            $referenceTableName = 'vtiger_contactdetailsSubscription';
                                                                                        } else {
                                                                                            if ($moduleName == 'Subscription' && $referenceModule == 'Accounts' && $fieldName == 'account_id') {
                                                                                                $referenceTableName = 'vtiger_accountsSubscription';
                                                                                            } else {
                                                                                                if ($moduleName == 'Subscription' && $referenceModule == 'Potentials' && $fieldName == 'potential_id') {
                                                                                                    $referenceTableName = 'vtiger_potentialSubscription';
                                                                                                } else {
                                                                                                    if ($moduleName == 'Quotes' && $referenceModule == 'Potentials' && $fieldName == 'potential_id') {
                                                                                                        $referenceTableName = 'vtiger_potentialRelQuotes';
                                                                                                    } else {
                                                                                                        if ($moduleName == 'Quotes' && $referenceModule == 'Accounts' && $fieldName == 'account_id') {
                                                                                                            $referenceTableName = 'vtiger_accountQuotes';
                                                                                                        } else {
                                                                                                            if ($moduleName == 'Quotes' && $referenceModule == 'Contacts' && $fieldName == 'contact_id') {
                                                                                                                $referenceTableName = 'vtiger_contactdetailsQuotes';
                                                                                                            } else {
                                                                                                                if ($moduleName == 'Quotes' && $referenceModule == 'Leads' && $fieldName == 'contact_id') {
                                                                                                                    $referenceTableName = 'vtiger_leaddetailsQuotes';
                                                                                                                } else {
                                                                                                                    if ($moduleName == 'SalesOrder' && $referenceModule == 'Potentials' && $fieldName == 'potential_id') {
                                                                                                                        $referenceTableName = 'vtiger_potentialRelSalesOrder';
                                                                                                                    } else {
                                                                                                                        if ($moduleName == 'SalesOrder' && $referenceModule == 'Accounts' && $fieldName == 'account_id') {
                                                                                                                            $referenceTableName = 'vtiger_accountSalesOrder';
                                                                                                                        } else {
                                                                                                                            if ($moduleName == 'SalesOrder' && $referenceModule == 'Contacts' && $fieldName == 'contact_id') {
                                                                                                                                $referenceTableName = 'vtiger_contactdetailsSalesOrder';
                                                                                                                            } else {
                                                                                                                                if ($moduleName == 'SalesOrder' && $referenceModule == 'Quotes' && $fieldName == 'quote_id') {
                                                                                                                                    $referenceTableName = 'vtiger_quotesSalesOrder';
                                                                                                                                } else {
                                                                                                                                    if ($moduleName == 'Potentials' && $referenceModule == 'Contacts' && $fieldName == 'contact_id') {
                                                                                                                                        $referenceTableName = 'vtiger_contactdetailsPotentials';
                                                                                                                                    } else {
                                                                                                                                        if ($moduleName == 'Potentials' && $referenceModule == 'Accounts' && $fieldName == 'related_to') {
                                                                                                                                            $referenceTableName = 'vtiger_accountPotentials';
                                                                                                                                        } else {
                                                                                                                                            if ($moduleName == 'ModComments' && $referenceModule == 'Users') {
                                                                                                                                                $referenceTableName = 'vtiger_usersModComments';
                                                                                                                                            } else {
                                                                                                                                                if (in_array($referenceModule, $reportSecondaryModules) && $fieldInstance->getUIType() != 10) {
                                                                                                                                                    $referenceTableName = (string) $entityTableName . 'Rel' . $referenceModule;
                                                                                                                                                    $dependentTableName = 'vtiger_crmentityRel' . $referenceModule . $fieldInstance->getFieldId();
                                                                                                                                                } else {
                                                                                                                                                    if (in_array($moduleName, $reportSecondaryModules) && $fieldInstance->getUIType() != 10) {
                                                                                                                                                        $referenceTableName = (string) $entityTableName . 'Rel' . $moduleName;
                                                                                                                                                        $dependentTableName = 'vtiger_crmentityRel' . $moduleName . $fieldInstance->getFieldId();
                                                                                                                                                    } else {
                                                                                                                                                        $referenceTableName = (string) $entityTableName . 'Rel' . $moduleName . $fieldInstance->getFieldId();
                                                                                                                                                        $dependentTableName = 'vtiger_crmentityRel' . $moduleName . $fieldInstance->getFieldId();
                                                                                                                                                    }
                                                                                                                                                }
                                                                                                                                            }
                                                                                                                                        }
                                                                                                                                    }
                                                                                                                                }
                                                                                                                            }
                                                                                                                        }
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $this->queryPlanner->addTable($referenceTableName);
                if (isset($dependentTableName)) {
                    $this->queryPlanner->addTable($dependentTableName);
                }
                $columnList = [];
                if (is_array($entityFieldNames)) {
                    foreach ($entityFieldNames as $entityColumnName) {
                        $columnList[(string) $entityColumnName] = (string) $referenceTableName . '.' . $entityColumnName;
                    }
                } else {
                    $columnList[] = (string) $referenceTableName . '.' . $entityFieldNames;
                }
                if (count($columnList) > 1) {
                    $columnSql = getSqlForNameInDisplayFormat($columnList, $referenceModule);
                } else {
                    $columnSql = implode('', $columnList);
                }
                if ($referenceModule == 'DocumentFolders' && $fieldInstance->getFieldName() == 'folderid') {
                    $columnSql = 'vtiger_attachmentsfolder.foldername';
                    $this->queryPlanner->addTable('vtiger_attachmentsfolder');
                }
                if ($referenceModule == 'Currency' && $fieldInstance->getFieldName() == 'currency_id') {
                    $columnSql = 'vtiger_currency_info' . $moduleName . '.currency_name';
                    $this->queryPlanner->addTable('vtiger_currency_info' . $moduleName);
                }
                $columnsSqlList[] = 'trim(' . $columnSql . ')';
            }
        }

        return $columnsSqlList;
    }

    public function getVReportsUiType10Query($module, $queryPlanner)
    {
        $adb = PearDatabase::getInstance();
        $relquery = '';
        $matrix = $queryPlanner->newDependencyMatrix();
        $params = [$module];
        if ($module == 'Calendar') {
            array_push($params, 'Events');
        }
        $fields_query = $adb->pquery('SELECT vtiger_field.fieldname,vtiger_field.tablename,vtiger_field.fieldid from vtiger_field INNER JOIN vtiger_tab on vtiger_tab.name IN (' . generateQuestionMarks($params) . ') WHERE vtiger_tab.tabid=vtiger_field.tabid AND vtiger_field.uitype IN (10) AND vtiger_field.presence IN (0,2)', $params);
        if ($adb->num_rows($fields_query) > 0) {
            for ($i = 0; $i < $adb->num_rows($fields_query); ++$i) {
                $field_name = $adb->query_result($fields_query, $i, 'fieldname');
                $field_id = $adb->query_result($fields_query, $i, 'fieldid');
                $tab_name = $adb->query_result($fields_query, $i, 'tablename');
                $ui10_modules_query = $adb->pquery('SELECT vtiger_fieldmodulerel.relmodule FROM vtiger_fieldmodulerel  INNER JOIN vtiger_tab ON vtiger_tab.name = vtiger_fieldmodulerel.relmodule WHERE fieldid=? AND vtiger_tab.`presence` = 0', [$field_id]);
                if ($adb->num_rows($ui10_modules_query) > 0) {
                    $crmentityRelModuleFieldTable = 'vtiger_crmentityRel' . $module . $field_id;
                    $crmentityRelModuleFieldTableDeps = [];
                    $calendarFlag = false;
                    for ($j = 0; $j < $adb->num_rows($ui10_modules_query); ++$j) {
                        $rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
                        if (vtlib_isModuleActive($rel_mod)) {
                            if ($rel_mod == 'Calendar') {
                                $calendarFlag = true;
                            }
                            if ($calendarFlag && $rel_mod == 'Events') {
                                continue;
                            }
                            $rel_obj = CRMEntity::getInstance($rel_mod);
                            vtlib_setup_modulevars($rel_mod, $rel_obj);
                            $rel_tab_name = $rel_obj->table_name;
                            $rel_tab_index = $rel_obj->table_index;
                            $crmentityRelModuleFieldTableDeps[] = $rel_tab_name . 'Rel' . $module . $field_id;
                        }
                    }
                    $matrix->setDependency($crmentityRelModuleFieldTable, $crmentityRelModuleFieldTableDeps);
                    $matrix->addDependency($tab_name, $crmentityRelModuleFieldTable);
                    if ($queryPlanner->requireTable($crmentityRelModuleFieldTable, $matrix)) {
                        $relquery .= ' LEFT JOIN vtiger_crmentity AS ' . $crmentityRelModuleFieldTable . ' ON ' . $crmentityRelModuleFieldTable . '.crmid = ' . $tab_name . '.' . $field_name . ' AND vtiger_crmentityRel' . $module . $field_id . '.deleted=0';
                    }
                    $calendarFlag = false;
                    for ($j = 0; $j < $adb->num_rows($ui10_modules_query); ++$j) {
                        $rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
                        if (vtlib_isModuleActive($rel_mod)) {
                            if ($rel_mod == 'Calendar') {
                                $calendarFlag = true;
                            }
                            if ($calendarFlag && $rel_mod == 'Events') {
                                continue;
                            }
                            $rel_obj = CRMEntity::getInstance($rel_mod);
                            vtlib_setup_modulevars($rel_mod, $rel_obj);
                            $rel_tab_name = $rel_obj->table_name;
                            $rel_tab_index = $rel_obj->table_index;
                            $rel_tab_name_rel_module_table_alias = $rel_tab_name . 'Rel' . $module . $field_id;
                            if ($queryPlanner->requireTable($rel_tab_name_rel_module_table_alias)) {
                                $relquery .= ' LEFT JOIN ' . $rel_tab_name . ' AS ' . $rel_tab_name_rel_module_table_alias . ' ON ' . $rel_tab_name_rel_module_table_alias . '.' . $rel_tab_index . ' = ' . $crmentityRelModuleFieldTable . '.crmid';
                            }
                        }
                    }
                }
            }
        }

        return $relquery;
    }

    public function generateVReportsSecQuery($module, $secmodule, $queryPlanner)
    {
        global $adb;
        $secondary = CRMEntity::getInstance($secmodule);
        vtlib_setup_modulevars($secmodule, $secondary);
        $tablename = $secondary->table_name;
        $tableindex = $secondary->table_index;
        $modulecftable = $secondary->customFieldTable[0];
        $modulecfindex = $secondary->customFieldTable[1];
        if (isset($modulecftable) && $queryPlanner->requireTable($modulecftable)) {
            $cfquery = 'left join ' . $modulecftable . ' as ' . $modulecftable . ' on ' . $modulecftable . '.' . $modulecfindex . '=' . $tablename . '.' . $tableindex;
        } else {
            $cfquery = '';
        }
        $relquery = '';
        $matrix = $queryPlanner->newDependencyMatrix();
        $fields_query = $adb->pquery('SELECT vtiger_field.fieldname,vtiger_field.columnname,vtiger_field.tablename,vtiger_field.fieldid,vtiger_field.uitype from vtiger_field INNER JOIN vtiger_tab on vtiger_tab.name = ? WHERE vtiger_tab.tabid=vtiger_field.tabid AND vtiger_field.uitype IN (10,73,57) and vtiger_field.presence in (0,2)', [$secmodule]);
        if ($adb->num_rows($fields_query) > 0) {
            for ($i = 0; $i < $adb->num_rows($fields_query); ++$i) {
                $field_name = $adb->query_result($fields_query, $i, 'fieldname');
                $field_column_name = $adb->query_result($fields_query, $i, 'columnname');
                $field_id = $adb->query_result($fields_query, $i, 'fieldid');
                $tab_name = $adb->query_result($fields_query, $i, 'tablename');
                $uitype = $adb->query_result($fields_query, $i, 'uitype');
                $ui10_modules_query = $adb->pquery('SELECT vtiger_fieldmodulerel.relmodule FROM vtiger_fieldmodulerel  INNER JOIN vtiger_tab ON vtiger_tab.name = vtiger_fieldmodulerel.relmodule WHERE fieldid=? AND vtiger_tab.`presence` = 0', [$field_id]);
                if ($adb->num_rows($ui10_modules_query) > 0 || $uitype == 73 || $uitype == 57) {
                    $crmentityRelSecModuleTable = 'vtiger_crmentityRel' . $secmodule . $field_id;
                    $crmentityRelSecModuleTableDeps = [];
                    for ($j = 0; $j < $adb->num_rows($ui10_modules_query); ++$j) {
                        $rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
                        $rel_obj = CRMEntity::getInstance($rel_mod);
                        vtlib_setup_modulevars($rel_mod, $rel_obj);
                        $rel_tab_name = $rel_obj->table_name;
                        $rel_tab_index = $rel_obj->table_index;
                        $crmentityRelSecModuleTableDeps[] = $rel_tab_name . 'Rel' . $secmodule;
                    }
                    if ($uitype == 73) {
                        $rel_mod = 'Accounts';
                        $rel_obj = CRMEntity::getInstance($rel_mod);
                        vtlib_setup_modulevars($rel_mod, $rel_obj);
                        $rel_tab_name = $rel_obj->table_name;
                        $rel_tab_index = $rel_obj->table_index;
                        $crmentityRelSecModuleTableDeps[] = $rel_tab_name . $secmodule;
                    } else {
                        if ($uitype == 57) {
                            $rel_mod = 'Contacts';
                            $rel_obj = CRMEntity::getInstance($rel_mod);
                            vtlib_setup_modulevars($rel_mod, $rel_obj);
                            $rel_tab_name = $rel_obj->table_name;
                            $rel_tab_index = $rel_obj->table_index;
                            $crmentityRelSecModuleTableDeps[] = $rel_tab_name . $secmodule;
                        }
                    }
                    $matrix->setDependency($crmentityRelSecModuleTable, $crmentityRelSecModuleTableDeps);
                    $matrix->addDependency($tab_name, $crmentityRelSecModuleTable);
                    if ($queryPlanner->requireTable($crmentityRelSecModuleTable, $matrix)) {
                        $relquery .= ' left join vtiger_crmentity as ' . $crmentityRelSecModuleTable . ' on ' . $crmentityRelSecModuleTable . '.crmid = ' . $tab_name . '.' . $field_column_name . ' and ' . $crmentityRelSecModuleTable . '.deleted=0';
                    }
                    for ($j = 0; $j < $adb->num_rows($ui10_modules_query); ++$j) {
                        $rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
                        $rel_obj = CRMEntity::getInstance($rel_mod);
                        vtlib_setup_modulevars($rel_mod, $rel_obj);
                        $rel_tab_name = $rel_obj->table_name;
                        $rel_tab_index = $rel_obj->table_index;
                        $rel_tab_name_rel_secmodule_table_alias = $rel_tab_name . 'Rel' . $secmodule . $field_id;
                        if ($queryPlanner->requireTable($rel_tab_name_rel_secmodule_table_alias)) {
                            $relquery .= ' left join ' . $rel_tab_name . ' as ' . $rel_tab_name_rel_secmodule_table_alias . ' on ' . $rel_tab_name_rel_secmodule_table_alias . '.' . $rel_tab_index . ' = ' . $crmentityRelSecModuleTable . '.crmid';
                        }
                    }
                    if ($uitype == 73) {
                        $rel_mod = 'Accounts';
                        $rel_obj = CRMEntity::getInstance($rel_mod);
                        vtlib_setup_modulevars($rel_mod, $rel_obj);
                        $rel_tab_name = $rel_obj->table_name;
                        $rel_tab_index = $rel_obj->table_index;
                        $rel_tab_name_rel_secmodule_table_alias = $rel_tab_name . $secmodule;
                        if ($queryPlanner->requireTable($rel_tab_name_rel_secmodule_table_alias)) {
                            $relquery .= ' left join ' . $rel_tab_name . ' as ' . $rel_tab_name_rel_secmodule_table_alias . ' on ' . $rel_tab_name_rel_secmodule_table_alias . '.' . $rel_tab_index . ' = ' . $crmentityRelSecModuleTable . '.crmid';
                        }
                    } else {
                        if ($uitype == 57) {
                            $rel_mod = 'Contacts';
                            $rel_obj = CRMEntity::getInstance($rel_mod);
                            vtlib_setup_modulevars($rel_mod, $rel_obj);
                            $rel_tab_name = $rel_obj->table_name;
                            $rel_tab_index = $rel_obj->table_index;
                            $rel_tab_name_rel_secmodule_table_alias = $rel_tab_name . $secmodule;
                            if ($queryPlanner->requireTable($rel_tab_name_rel_secmodule_table_alias)) {
                                $relquery .= ' left join ' . $rel_tab_name . ' as ' . $rel_tab_name_rel_secmodule_table_alias . ' on ' . $rel_tab_name_rel_secmodule_table_alias . '.' . $rel_tab_index . ' = ' . $crmentityRelSecModuleTable . '.crmid';
                            }
                        }
                    }
                }
            }
        }
        $matrix->setDependency('vtiger_crmentity' . $secmodule, ['vtiger_groups' . $secmodule, 'vtiger_users' . $secmodule, 'vtiger_lastModifiedBy' . $secmodule]);
        $matrix->addDependency($tablename, 'vtiger_crmentity' . $secmodule);
        if (!$queryPlanner->requireTable($tablename, $matrix) && !$queryPlanner->requireTable($modulecftable)) {
            return '';
        }
        $query = $this->getRelationQuery($module, $secmodule, (string) $tablename, (string) $tableindex, $queryPlanner);
        if ($queryPlanner->requireTable('vtiger_crmentity' . $secmodule, $matrix)) {
            $query .= ' left join vtiger_crmentity as vtiger_crmentity' . $secmodule . ' on vtiger_crmentity' . $secmodule . '.crmid = ' . $tablename . '.' . $tableindex . ' AND vtiger_crmentity' . $secmodule . '.deleted=0';
        }
        $query .= ' ' . $cfquery;
        if ($queryPlanner->requireTable('vtiger_groups' . $secmodule)) {
            $query .= ' left join vtiger_groups as vtiger_groups' . $secmodule . ' on vtiger_groups' . $secmodule . '.groupid = vtiger_crmentity' . $secmodule . '.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_users' . $secmodule)) {
            $query .= ' left join vtiger_users as vtiger_users' . $secmodule . ' on vtiger_users' . $secmodule . '.id = vtiger_crmentity' . $secmodule . '.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_lastModifiedBy' . $secmodule)) {
            $query .= ' left join vtiger_users as vtiger_lastModifiedBy' . $secmodule . ' on vtiger_lastModifiedBy' . $secmodule . '.id = vtiger_crmentity' . $secmodule . '.modifiedby';
        }
        if ($queryPlanner->requireTable('vtiger_createdby' . $secmodule)) {
            $query .= ' LEFT JOIN vtiger_users AS vtiger_createdby' . $secmodule . ' ON vtiger_createdby' . $secmodule . '.id=vtiger_crmentity.smcreatorid';
        }
        $query .= ' ' . $relquery;

        return $query;
    }

    public function generateVReportsQuery($module, $queryPlanner)
    {
        global $adb;
        $primary = CRMEntity::getInstance($module);
        vtlib_setup_modulevars($module, $primary);
        $moduletable = $primary->table_name;
        $moduleindex = $primary->table_index;
        $modulecftable = $primary->customFieldTable[0];
        $modulecfindex = $primary->customFieldTable[1];
        if (isset($modulecftable) && $queryPlanner->requireTable($modulecftable)) {
            $cfquery = 'inner join ' . $modulecftable . ' as ' . $modulecftable . ' on ' . $modulecftable . '.' . $modulecfindex . '=' . $moduletable . '.' . $moduleindex;
        } else {
            $cfquery = '';
        }
        $relquery = '';
        $matrix = $queryPlanner->newDependencyMatrix();
        $fields_query = $adb->pquery('SELECT vtiger_field.fieldname,vtiger_field.tablename,vtiger_field.fieldid from vtiger_field INNER JOIN vtiger_tab on vtiger_tab.name = ? WHERE vtiger_tab.tabid=vtiger_field.tabid AND vtiger_field.uitype IN (10) and vtiger_field.presence in (0,2)', [$module]);
        if ($adb->num_rows($fields_query) > 0) {
            for ($i = 0; $i < $adb->num_rows($fields_query); ++$i) {
                $field_name = $adb->query_result($fields_query, $i, 'fieldname');
                $field_id = $adb->query_result($fields_query, $i, 'fieldid');
                $tab_name = $adb->query_result($fields_query, $i, 'tablename');
                $ui10_modules_query = $adb->pquery('SELECT vtiger_fieldmodulerel.relmodule FROM vtiger_fieldmodulerel  INNER JOIN vtiger_tab ON vtiger_tab.name = vtiger_fieldmodulerel.relmodule WHERE fieldid=? AND vtiger_tab.`presence` = 0', [$field_id]);
                if ($adb->num_rows($ui10_modules_query) > 0) {
                    $crmentityRelModuleFieldTable = 'vtiger_crmentityRel' . $module . $field_id;
                    $crmentityRelModuleFieldTableDeps = [];
                    for ($j = 0; $j < $adb->num_rows($ui10_modules_query); ++$j) {
                        $rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
                        $rel_obj = CRMEntity::getInstance($rel_mod);
                        vtlib_setup_modulevars($rel_mod, $rel_obj);
                        $rel_tab_name = $rel_obj->table_name;
                        $rel_tab_index = $rel_obj->table_index;
                        $crmentityRelModuleFieldTableDeps[] = $rel_tab_name . 'Rel' . $module . $field_id;
                    }
                    $matrix->setDependency($crmentityRelModuleFieldTable, $crmentityRelModuleFieldTableDeps);
                    $matrix->addDependency($tab_name, $crmentityRelModuleFieldTable);
                    if ($queryPlanner->requireTable($crmentityRelModuleFieldTable, $matrix)) {
                        $relquery .= ' left join vtiger_crmentity as ' . $crmentityRelModuleFieldTable . ' on ' . $crmentityRelModuleFieldTable . '.crmid = ' . $tab_name . '.' . $field_name . ' and vtiger_crmentityRel' . $module . $field_id . '.deleted=0';
                    }
                    for ($j = 0; $j < $adb->num_rows($ui10_modules_query); ++$j) {
                        $rel_mod = $adb->query_result($ui10_modules_query, $j, 'relmodule');
                        $rel_obj = CRMEntity::getInstance($rel_mod);
                        vtlib_setup_modulevars($rel_mod, $rel_obj);
                        $rel_tab_name = $rel_obj->table_name;
                        $rel_tab_index = $rel_obj->table_index;
                        $rel_tab_name_rel_module_table_alias = $rel_tab_name . 'Rel' . $module . $field_id;
                        if ($queryPlanner->requireTable($rel_tab_name_rel_module_table_alias)) {
                            $relquery .= ' left join ' . $rel_tab_name . ' as ' . $rel_tab_name_rel_module_table_alias . '  on ' . $rel_tab_name_rel_module_table_alias . '.' . $rel_tab_index . ' = ' . $crmentityRelModuleFieldTable . '.crmid';
                        }
                        $customFieldTable = $rel_obj->customFieldTable;
                        $rel_tab_name_cf = $customFieldTable[0];
                        $rel_tabname_relmodule_cftable_alias = $rel_tab_name_cf . 'Rel' . $module . $field_id;
                        if ($queryPlanner->requireTable($rel_tabname_relmodule_cftable_alias)) {
                            $relquery .= ' left join ' . $rel_tab_name_cf . ' as ' . $rel_tabname_relmodule_cftable_alias . '  on ' . $rel_tabname_relmodule_cftable_alias . '.' . $rel_tab_index . ' = ' . $crmentityRelModuleFieldTable . '.crmid';
                        }
                    }
                }
            }
        }
        $query = 'from ' . $moduletable . ' inner join vtiger_crmentity on vtiger_crmentity.crmid=' . $moduletable . '.' . $moduleindex;
        $query .= ' ' . (string) $cfquery;
        if ($queryPlanner->requireTable('vtiger_groups' . $module)) {
            $query .= ' left join vtiger_groups as vtiger_groups' . $module . ' on vtiger_groups' . $module . '.groupid = vtiger_crmentity.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_users' . $module)) {
            $query .= ' left join vtiger_users as vtiger_users' . $module . ' on vtiger_users' . $module . '.id = vtiger_crmentity.smownerid';
        }
        if ($queryPlanner->requireTable('vtiger_lastModifiedBy' . $module)) {
            $query .= ' left join vtiger_users as vtiger_lastModifiedBy' . $module . ' on vtiger_lastModifiedBy' . $module . '.id = vtiger_crmentity.modifiedby';
        }
        if ($queryPlanner->requireTable('vtiger_createdby' . $module)) {
            $query .= ' LEFT JOIN vtiger_users AS vtiger_createdby' . $module . ' ON vtiger_createdby' . $module . '.id=vtiger_crmentity.smcreatorid';
        }
        $query .= "\tleft join vtiger_groups on vtiger_groups.groupid = vtiger_crmentity.smownerid";
        $query .= ' left join vtiger_users on vtiger_users.id = vtiger_crmentity.smownerid';
        $query .= ' ' . $relquery;

        return $query;
    }

    public function buildWhereClauseConditionForCalendar($scope = '')
    {
        $userModel = Users_Record_Model::getCurrentUserModel();
        require 'user_privileges/user_privileges_' . $userModel->id . '.php';
        $query = '';
        if ($profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1) {
            $tabId = getTabid('Calendar');
            $eventTempTable = 'vt_tmp_u' . $userModel->id . '_t' . $tabId . '_events' . $scope;
            $taskTempTable = 'vt_tmp_u' . $userModel->id . '_t' . $tabId . '_task' . $scope;
            $query = ' (' . $eventTempTable . '.shared IS NOT NULL OR ' . $taskTempTable . '.shared IS NOT NULL) ';
        }

        return $query;
    }

    public function createLetterRange($length)
    {
        $range = [];
        $letters = range('A', 'Z');
        for ($i = 0; $i < $length; ++$i) {
            $position = $i * 26;
            foreach ($letters as $ii => $letter) {
                ++$position;
                if ($position <= $length) {
                    $range[] = ($position > 26 ? $range[$i - 1] : '') . $letter;
                }
            }
        }

        return $range;
    }

    public function writeReportPivotToExcelFile($fileName, $filterlist = '')
    {
        global $currentModule;
        global $current_language;
        $mod_strings = return_module_language($current_language, $currentModule);
        require_once 'modules/VReports/PHPExcel/PHPExcel.php';
        require_once 'modules/VReports/PHPExcel/Pivot.php';
        $workbook = new PHPExcel();
        $worksheet = $workbook->setActiveSheetIndex(0);
        $recordId = $this->reportid;
        $reportModel = VReports_Record_Model::getInstanceById($recordId);
        $reportPivotModel = VReports_Pivot_Model::getInstanceById($reportModel);
        $reportData = $reportPivotModel->getData(true);
        $arrFields = $reportData['header']['field'];
        $arr_val = Pivot::factory($reportData['data'])->pivotOn(Zend_Json::decode($reportData['header']['xfields']))->addColumn(Zend_Json::decode($reportData['header']['yfields']), Zend_Json::decode($reportData['header']['zfields']))->fullTotal()->lineTotal()->fetch();
        $totalxls = $this->GenerateReport('XLS', $filterlist, false, false, false, 'ExcelExport');
        $numericTypes = ['currency', 'double', 'integer', 'percentage'];
        $header_styles = ['fill' => ['color' => ['rgb' => 'E1E0F7']], 'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER], 'font' => ['bold' => true]];
        if (isset($arr_val)) {
            $count = 0;
            $rowcount = 1;
            $arrayFirstRowValues = $arr_val[0];
            $alphabet = $this->createLetterRange(100);
            $arrHeadRow = [];
            $countCol = 1;
            foreach ($arrayFirstRowValues as $key => $value) {
                if ($key == 'ACTION' || $key == vtranslate('LBL_ACTION', $this->primarymodule) || $key == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || $key == vtranslate('LBL ACTION', $this->primarymodule) || $key == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                    continue;
                }
                $tempKey = array_search($key, array_column($arrFields, 'fieldname'));
                if ($tempKey) {
                    $key = $arrFields[$tempKey]['translatedLabel'];
                } else {
                    $key = preg_replace('/\\_+$/', '', $key);
                }
                $groupColumns = explode('|##|', $key);
                if (array_key_exists($groupColumns[0], $arrHeadRow)) {
                    ++$countCol;
                } else {
                    $countCol = 1;
                }
                $arrHeadRow[$groupColumns[0]] = $countCol;
                $count = $count + 1;
            }
            $count = 0;
            $start_letter = 0;
            foreach ($arrHeadRow as $key => $value) {
                $end_letter = $start_letter + $value - 1;
                $countRow = 1;
                if ($value == 1) {
                    $countRow = 2;
                }
                $worksheet->setCellValueExplicitByColumnAndRow($start_letter, $rowcount, decode_html($key), true)->mergeCells($alphabet[$start_letter] . '1:' . $alphabet[$end_letter] . $countRow);
                $worksheet->getStyleByColumnAndRow($start_letter, $rowcount)->applyFromArray($header_styles);
                $count = $count + 1;
                $start_letter = $end_letter + 1;
            }
            $count = 0;
            ++$rowcount;
            foreach ($arrayFirstRowValues as $key => $value) {
                if ($key == 'ACTION' || $key == vtranslate('LBL_ACTION', $this->primarymodule) || $key == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || $key == vtranslate('LBL ACTION', $this->primarymodule) || $key == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                    continue;
                }
                $tempKey = array_search($key, array_column($arrFields, 'fieldname'));
                if ($tempKey !== false) {
                    $key = $arrFields[$tempKey]['translatedLabel'];
                } else {
                    $key = preg_replace('/\\_+$/', '', $key);
                    $groupColumns = explode('|##|', $key);
                    $countRow = count($groupColumns);
                    if ($countRow > 1) {
                        $key = $groupColumns[1];
                        $key = ltrim($key, '_');
                    }
                    $tempKey = array_search($key, array_column($arrFields, 'fieldname'));
                    if ($tempKey !== false) {
                        $key = $arrFields[$tempKey]['translatedLabel'];
                    }
                }
                $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, decode_html($key), true);
                $worksheet->getStyleByColumnAndRow($count, $rowcount)->applyFromArray($header_styles);
                $count = $count + 1;
            }
            ++$rowcount;
            foreach ($arr_val as $key => $array_value) {
                $count = 0;
                foreach ($array_value as $hdr => $valueDataType) {
                    if (is_array($valueDataType)) {
                        $value = $valueDataType['value'];
                        $dataType = $valueDataType['type'];
                    } else {
                        $value = $valueDataType;
                        $dataType = '';
                    }
                    if ($hdr == 'ACTION' || $hdr == vtranslate('LBL_ACTION', $this->primarymodule) || $hdr == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || $hdr == vtranslate('LBL ACTION', $this->primarymodule) || $hdr == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                        continue;
                    }
                    $value = decode_html($value);
                    if (in_array($dataType, $numericTypes) || is_numeric($value) && $count != 0) {
                        $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                        $worksheet->getStyleByColumnAndRow($count, $rowcount)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                    } else {
                        $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $value, PHPExcel_Cell_DataType::TYPE_STRING);
                    }
                    $count = $count + 1;
                }
                ++$rowcount;
            }
            ++$rowcount;
            $count = 0;
            if (is_array($totalxls[0])) {
                foreach ($totalxls[0] as $key => $value) {
                    $exploedKey = explode('_', $key);
                    $chdr = end($exploedKey);
                    $translated_str = in_array($chdr, array_keys($mod_strings)) ? $mod_strings[$chdr] : $chdr;
                    $worksheet->setCellValueExplicitByColumnAndRow($count, $rowcount, $translated_str);
                    $worksheet->getStyleByColumnAndRow($count, $rowcount)->applyFromArray($header_styles);
                    $count = $count + 1;
                }
            }
            $ignoreValues = ['sumcount', 'avgcount', 'mincount', 'maxcount'];
            ++$rowcount;
            foreach ($totalxls as $key => $array_value) {
                $count = 0;
                foreach ($array_value as $hdr => $value) {
                    if (in_array($hdr, $ignoreValues)) {
                        continue;
                    }
                    $value = decode_html($value);
                    $excelDatatype = PHPExcel_Cell_DataType::TYPE_STRING;
                    if (is_numeric($value)) {
                        $excelDatatype = PHPExcel_Cell_DataType::TYPE_NUMERIC;
                    }
                    $worksheet->setCellValueExplicitByColumnAndRow($count, $key + $rowcount, $value, $excelDatatype);
                    $count = $count + 1;
                }
            }
        }
        ob_end_clean();
        ob_clean();
        $workbookWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel5');
        $workbookWriter->save($fileName);
    }

    public function writeReportPivotToCSVFile($fileName, $filterlist = '')
    {
        global $currentModule;
        global $current_language;
        $mod_strings = return_module_language($current_language, $currentModule);
        require_once 'modules/VReports/PHPExcel/Pivot.php';
        $recordId = $this->reportid;
        $reportModel = VReports_Record_Model::getInstanceById($recordId);
        $reportPivotModel = VReports_Pivot_Model::getInstanceById($reportModel);
        $reportData = $reportPivotModel->getData(true);
        $arrFields = $reportData['header']['field'];
        $arr_val = Pivot::factory($reportData['data'])->pivotOn(Zend_Json::decode($reportData['header']['xfields']))->addColumn(Zend_Json::decode($reportData['header']['yfields']), Zend_Json::decode($reportData['header']['zfields']))->fullTotal()->lineTotal()->fetch();
        $fp = fopen($fileName, 'w+');
        if (isset($arr_val)) {
            $csv_values = [];
            $unsetValue = false;
            $arrayFirstRowValues = $arr_val[0];
            $arrFirstrow = [];
            foreach ($arrayFirstRowValues as $key => $value) {
                if ($key == 'ACTION' || $key == vtranslate('LBL_ACTION', $this->primarymodule) || $key == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || $key == vtranslate('LBL ACTION', $this->primarymodule) || $key == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                    continue;
                }
                $tempKey = array_search($key, array_column($arrFields, 'fieldname'));
                if ($tempKey) {
                    $key = $arrFields[$tempKey]['translatedLabel'];
                } else {
                    $key = str_replace('record_count', '', $key);
                    $key = preg_replace('/\\_+$/', '', $key);
                }
                $arrFirstrow[] = decode_html($key);
            }
            $csv_values = array_map('decode_html', array_values($arrFirstrow));
            fputcsv($fp, $csv_values);
            $csv_values = array_map('decode_html', array_keys($arr_val[0]));
            if (end($csv_values) == vtranslate('LBL_ACTION', $this->primarymodule) || end($csv_values) == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL_ACTION', $this->primarymodule) || end($csv_values) == vtranslate('LBL ACTION', $this->primarymodule) || end($csv_values) == vtranslate($this->primarymodule, $this->primarymodule) . ' ' . vtranslate('LBL ACTION', $this->primarymodule)) {
                unset($csv_values[count($csv_values) - 1]);
                $unsetValue = true;
            }
            foreach ($arr_val as $key => $array_value) {
                if ($unsetValue) {
                    array_pop($array_value);
                }
                $csv_values = array_map('decode_html', array_values($array_value));
                fputcsv($fp, $csv_values);
            }
        }
        fclose($fp);
    }
}
class VReportRunQueryDependencyMatrix
{
    protected $matrix = [];

    protected $computedMatrix;

    public function setDependency($table, array $dependents)
    {
        $this->matrix[$table] = $dependents;
    }

    public function addDependency($table, $dependent)
    {
        if (isset($this->matrix[$table]) && !in_array($dependent, $this->matrix[$table])) {
            $this->matrix[$table][] = $dependent;
        } else {
            $this->setDependency($table, [$dependent]);
        }
    }

    public function getDependents($table)
    {
        $this->computeDependencies();

        return $this->computedMatrix[$table] ?? [];
    }

    protected function computeDependencies()
    {
        if ($this->computedMatrix !== null) {
            return null;
        }
        $this->computedMatrix = [];
        foreach ($this->matrix as $key => $values) {
            $this->computedMatrix[$key] = $this->computeDependencyForKey($key, $values);
        }
    }

    protected function computeDependencyForKey($key, $values)
    {
        $merged = [];
        foreach ($values as $value) {
            $merged[] = $value;
            if (isset($this->matrix[$value])) {
                $merged = array_merge($merged, $this->matrix[$value]);
            }
        }

        return $merged;
    }
}
class VReportRunQueryPlanner
{
    protected $disablePlanner = false;

    protected $tables = [];

    protected $tempTables = [];

    protected $tempTablesInitialized = false;

    protected $allowTempTables = true;

    protected $tempTablePrefix = 'vtiger_reptmptbl_';

    protected static $tempTableCounter = 0;

    protected $registeredCleanup = false;

    public $reportRun = false;

    public function addTable($table)
    {
        if (!empty($table)) {
            $this->tables[$table] = $table;
        }
    }

    public function requireTable($table, $dependencies = null)
    {
        if ($this->disablePlanner) {
            return true;
        }
        if (isset($this->tables[$table])) {
            return true;
        }
        if (is_array($dependencies)) {
            foreach ($dependencies as $dependentTable) {
                if (isset($this->tables[$dependentTable])) {
                    return true;
                }
            }
        } else {
            if ($dependencies instanceof VReportRunQueryDependencyMatrix) {
                $dependents = $dependencies->getDependents($table);
                if ($dependents) {
                    return count(array_intersect($this->tables, $dependents)) > 0;
                }
            }
        }

        return false;
    }

    public function getTables()
    {
        return $this->tables;
    }

    public function newDependencyMatrix()
    {
        return new VReportRunQueryDependencyMatrix();
    }

    public function registerTempTable($query, $keyColumns, $module = null)
    {
        if ($this->allowTempTables && !$this->disablePlanner) {
            global $current_user;
            $keyColumns = is_array($keyColumns) ? array_unique($keyColumns) : [$keyColumns];
            $uniqueName = null;
            foreach ($this->tempTables as $tmpUniqueName => $tmpTableInfo) {
                if (strcasecmp($query, $tmpTableInfo['query']) === 0 && $tmpTableInfo['module'] == $module) {
                    $tmpTableInfo['keycolumns'] = array_unique(array_merge($tmpTableInfo['keycolumns'], $keyColumns));
                    $uniqueName = $tmpUniqueName;
                    break;
                }
            }
            if ($uniqueName === null) {
                $uniqueName = $this->tempTablePrefix . str_replace('.', '', uniqid($current_user->id, true)) . self::$tempTableCounter++;
                $this->tempTables[$uniqueName] = ['query' => $query, 'keycolumns' => is_array($keyColumns) ? array_unique($keyColumns) : [$keyColumns], 'module' => $module];
            }

            return $uniqueName;
        }

        return '(' . $query . ')';

    }

    public function initializeTempTables()
    {
        global $adb;
        $oldDieOnError = $adb->dieOnError;
        $adb->dieOnError = false;
        foreach ($this->tempTables as $uniqueName => $tempTableInfo) {
            $reportConditions = $this->getReportConditions($tempTableInfo['module']);
            if ($tempTableInfo['module'] == 'Emails') {
                $query1 = sprintf('CREATE TEMPORARY TABLE %s AS %s', $uniqueName, $tempTableInfo['query']);
            } else {
                $query1 = sprintf('CREATE TEMPORARY TABLE %s AS %s %s', $uniqueName, $tempTableInfo['query'], $reportConditions);
            }
            $adb->pquery($query1, []);
            $keyColumns = $tempTableInfo['keycolumns'];
            foreach ($keyColumns as $keyColumn) {
                $query2 = sprintf('ALTER TABLE %s ADD INDEX (%s)', $uniqueName, $keyColumn);
                $adb->pquery($query2, []);
            }
        }
        $adb->dieOnError = $oldDieOnError;
        if (!$this->registeredCleanup) {
            register_shutdown_function([$this, 'cleanup']);
            $this->registeredCleanup = true;
        }
    }

    public function cleanup()
    {
        global $adb;
        $oldDieOnError = $adb->dieOnError;
        $adb->dieOnError = false;
        foreach ($this->tempTables as $uniqueName => $tempTableInfo) {
            $adb->pquery('DROP TABLE ' . $uniqueName, []);
        }
        $adb->dieOnError = $oldDieOnError;
        $this->tempTables = [];
    }

    /**
     * Function to get report condition query for generating temporary table based on condition given on report.
     * It generates condition query by considering fields of $module's base table or vtiger_crmentity table fields.
     * It doesn't add condition for reference fields in query.
     * @param string $module Module name for which temporary table is generated (Reports secondary module)
     * @return string returns condition query for generating temporary table
     */
    public function getReportConditions($module)
    {
        $db = PearDatabase::getInstance();
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        $moduleBaseTable = $moduleModel->get('basetable');
        $reportId = $this->reportRun->reportid;
        if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'generate') {
            $advanceFilter = $_REQUEST['advanced_filter'];
            $advfilterlist = transformAdvFilterListToDBFormatVReport(json_decode($advanceFilter, true));
        } else {
            $advfilterlist = $this->reportRun->getAdvFilterList($reportId);
        }
        $newAdvFilterList = [];
        $k = 0;
        foreach ($advfilterlist as $i => $columnConditions) {
            $conditionGroup = $advfilterlist[$i]['columns'];
            if (is_array($conditionGroup)) {
                reset($conditionGroup);
                $firstConditionKey = key($conditionGroup);
            } else {
                $firstConditionKey = 0;
            }
            $oldColumnCondition = $advfilterlist[$i]['columns'][$firstConditionKey]['column_condition'];
            foreach ($columnConditions['columns'] as $j => $condition) {
                $columnName = $condition['columnname'];
                $columnParts = explode(':', $columnName);
                [$moduleName, $fieldLabel] = explode('_', $columnParts[2], 2);
                $fieldInfo = getFieldByVReportLabel($moduleName, $columnParts[3], 'name');
                if (!empty($fieldInfo)) {
                    $fieldInstance = WebserviceField::fromArray($db, $fieldInfo);
                    $dataType = $fieldInstance->getFieldDataType();
                    $uiType = $fieldInfo['uitype'];
                    $fieldTable = $fieldInfo['tablename'];
                    $allowedTables = ['vtiger_crmentity', $moduleBaseTable];
                    $columnCondition = $advfilterlist[$i]['columns'][$j]['column_condition'];
                    if (!in_array($fieldTable, $allowedTables) || $moduleName != $module || isReferenceUITypeVReport($uiType) || $columnCondition == 'or' || $oldColumnCondition == 'or' || in_array($dataType, ['reference', 'multireference'])) {
                        $oldColumnCondition = $advfilterlist[$i]['columns'][$j]['column_condition'];
                    } else {
                        $columnParts[0] = $fieldTable;
                        $newAdvFilterList[$i]['columns'][$k]['columnname'] = implode(':', $columnParts);
                        $newAdvFilterList[$i]['columns'][$k]['comparator'] = $advfilterlist[$i]['columns'][$j]['comparator'];
                        $newAdvFilterList[$i]['columns'][$k]['value'] = $advfilterlist[$i]['columns'][$j]['value'];
                        $newAdvFilterList[$i]['columns'][$k++]['column_condition'] = $oldColumnCondition;
                    }
                }
            }
            $count_newAdvFilterList = 0;
            if (is_array($newAdvFilterList[$i])) {
                $count_newAdvFilterList = count($newAdvFilterList[$i]);
            }
            if ($count_newAdvFilterList) {
                $newAdvFilterList[$i]['condition'] = $advfilterlist[$i]['condition'];
            }
            if (isset($newAdvFilterList[$i]['columns'][$k - 1])) {
                $newAdvFilterList[$i]['columns'][$k - 1]['column_condition'] = '';
            }
            if ($count_newAdvFilterList != 2) {
                unset($newAdvFilterList[$i]);
            }
        }
        if (is_array($newAdvFilterList)) {
            end($newAdvFilterList);
        }
        $lastConditionsGrpKey = key($newAdvFilterList);
        $count_newAdvFilterList_lastConditionsGrpKey = 0;
        if (is_array($newAdvFilterList[$lastConditionsGrpKey])) {
            $count_newAdvFilterList_lastConditionsGrpKey = count($newAdvFilterList[$lastConditionsGrpKey]);
        }
        if ($count_newAdvFilterList_lastConditionsGrpKey) {
            $newAdvFilterList[$lastConditionsGrpKey]['condition'] = '';
        }
        $advfiltersql = $this->reportRun->generateAdvFilterSql($newAdvFilterList);
        if ($advfiltersql && !empty($advfiltersql)) {
            $advfiltersql = ' AND ' . $advfiltersql;
        }

        return $advfiltersql;
    }
}
