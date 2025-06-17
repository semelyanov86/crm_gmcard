<?php

/**
 * Function to get the field information from module name and field label
 */
function getFieldByVReportLabel($module, $label, $mode = "label")
{
    $cacheLabel = VTCacheUtils::getReportFieldByLabel($module, $label);
    if ($cacheLabel) {
        return $cacheLabel;
    }
    getColumnFields($module);
    $cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
    $label = decode_html($label);
    if ($module == "Calendar") {
        $cachedEventsFields = VTCacheUtils::lookupFieldInfo_Module("Events");
        if ($cachedEventsFields) {
            if (empty($cachedModuleFields)) {
                $cachedModuleFields = $cachedEventsFields;
            } else {
                $cachedModuleFields = array_merge($cachedModuleFields, $cachedEventsFields);
            }
        }
        if ($label == "Start_Date_and_Time") {
            $label = "Start_Date_&_Time";
        }
    }
    if (empty($cachedModuleFields)) {
        return NULL;
    }
    foreach ($cachedModuleFields as $fieldInfo) {
        if ($mode == "name") {
            $fieldLabel = $fieldInfo["fieldname"];
        } else {
            $fieldLabel = str_replace(" ", "_", $fieldInfo["fieldlabel"]);
        }
        $fieldLabel = decode_html($fieldLabel);
        if ($label == $fieldLabel) {
            VTCacheUtils::setReportFieldByLabel($module, $label, $fieldInfo);
            return $fieldInfo;
        }
    }
}
function isReferenceUITypeVReport($uitype)
{
    static $options = array("101", "116", "117", "26", "357", "50", "51", "52", "53", "57", "58", "59", "66", "68", "73", "75", "76", "77", "78", "80", "81");
    if (in_array($uitype, $options)) {
        return true;
    }
    return false;
}
function IsDateFieldVReport($reportColDetails)
{
    list($tablename, $colname, $module_field, $fieldname, $typeOfData) = explode(":", $reportColDetails);
    if ($typeOfData == "D") {
        return true;
    }
    return false;
}
/**
 *
 * @global Users $current_user
 * @param ReportRun $report
 * @param Array $picklistArray
 * @param ADOFieldObject $dbField
 * @param Array $valueArray
 * @param String $fieldName
 * @return String
 */
function getVReportFieldValue($report, $picklistArray, $dbField, $valueArray, $fieldName, $operation = false)
{
    global $current_user;
    global $default_charset;
    $db = PearDatabase::getInstance();
    $value = $valueArray[$fieldName];
    $fld_type = $dbField->type;
    list($module, $fieldLabel) = explode("_", $dbField->name, 2);
    $fieldInfo = getfieldbyvreportlabel($module, $fieldLabel);
    $fieldType = NULL;
    $fieldvalue = $value;
    if (!empty($fieldInfo)) {
        $field = WebserviceField::fromArray($db, $fieldInfo);
        $fieldType = $field->getFieldDataType();
    }
    if ($report->primarymodule == "PriceBooks" && $fieldLabel == "List_Price") {
        $fieldInfo = array("tabid" => getTabid($module), "fieldid" => "", "fieldname" => "listprice", "fieldlabel" => "List Price", "columnname" => "listprice", "tablename" => "vtiger_pricebookproductrel", "uitype" => 72, "typeofdata" => "Currency", "presence" => 0);
        $field = WebserviceField::fromArray($db, $fieldInfo);
        $fieldType = $field->getFieldDataType();
    }
    if (is_object($field) && $field->getUIType() == 401) {
        if ($value) {
            $value = explode("_", $value);
            $module = "RecurringInvoice";
            $frequency = ucfirst($value[0]);
            if ($frequency == "Monthly") {
                $fieldvalue = vtranslate("LBL_MONTHLY", $module, $value[1], vtranslate($value[2], $module));
            } else {
                if ($frequency == "Yearly") {
                    $fieldvalue = vtranslate("LBL_YEARLY", $module, vtranslate(ucfirst($value[1]), $module), vtranslate($value[2], $module));
                } else {
                    if ($frequency == "Weekly") {
                        $fieldvalue = vtranslate("LBL_WEEKLY", $module, vtranslate(ucfirst($value[1])));
                    } else {
                        if ($frequency == "Daily") {
                            $fieldvalue = vtranslate($frequency, $module);
                        }
                    }
                }
            }
        }
    } else {
        if ($fieldType == "currency" && $value != "") {
            if ($field->getUIType() == "72") {
                $curid_value = explode("::", $value);
                if (1 < count($curid_value)) {
                    list($currency_id, $currency_value) = $curid_value;
                } else {
                    $currency_value = $value;
                }
                $cur_sym_rate = getCurrencySymbolandCRate($currency_id);
                if ($value != "") {
                    if ($dbField->name == "Products_Unit_Price" && $currency_id != 1) {
                        $currency_value = (double) $cur_sym_rate["rate"] * (double) $currency_value;
                    }
                    if ($operation == "ExcelExport") {
                        $fieldvalue = $currency_value;
                    } else {
                        $formattedCurrencyValue = CurrencyField::convertToUserFormat($currency_value, NULL, true);
                        $fieldvalue = CurrencyField::appendCurrencySymbol($formattedCurrencyValue, $cur_sym_rate["symbol"]);
                    }
                }
            } else {
                if ($operation == "ExcelExport") {
                    $currencyField = new CurrencyField($value);
                    $fieldvalue = $currencyField->getDisplayValue(NULL, false, true);
                } else {
                    $currencyField = new CurrencyField($value);
                    $userCurrencyInfo = getCurrencySymbolandCRate($current_user->currency_id);
                    $fieldvalue = CurrencyField::appendCurrencySymbol($currencyField->getDisplayValue(), $userCurrencyInfo["symbol"]);
                }
            }
        } else {
            if ($dbField->name == "PurchaseOrder_Currency" || $dbField->name == "SalesOrder_Currency" || $dbField->name == "Invoice_Currency" || $dbField->name == "Quotes_Currency" || $dbField->name == "PriceBooks_Currency") {
                if ($value != "") {
                    $fieldvalue = getTranslatedCurrencyString($value);
                }
            } else {
                if (in_array($dbField->name, $report->ui101_fields) && !empty($value)) {
                    $entityNames = getEntityName("Users", $value);
                    $fieldvalue = $entityNames[$value];
                } else {
                    if ($fieldType == "date" && !empty($value)) {
                        if ($module == "Calendar" && ($field->getFieldName() == "due_date" || $field->getFieldName() == "date_start")) {
                            if ($field->getFieldName() == "due_date") {
                                $endTime = $valueArray["calendar_end_time"];
                                if (empty($endTime)) {
                                    $recordId = $valueArray["calendar_id"];
                                    $endTime = getSingleFieldValue("vtiger_activity", "time_end", "activityid", $recordId);
                                }
                                $date = new DateTimeField($value . " " . $endTime);
                                $fieldvalue = $date->getDisplayDate();
                            } else {
                                $date = new DateTimeField($fieldvalue);
                                $fieldvalue = $date->getDisplayDateTimeValue();
                            }
                        } else {
                            $date = new DateTimeField($fieldvalue);
                            $fieldvalue = $date->getDisplayDate();
                        }
                    } else {
                        if ($fieldType == "datetime" && !empty($value)) {
                            $date = new DateTimeField($value);
                            $fieldvalue = $date->getDisplayDateTimeValue();
                        } else {
                            if ($fieldType == "time" && !empty($value) && $field->getFieldName() != "duration_hours") {
                                if ($field->getFieldName() == "time_start" || $field->getFieldName() == "time_end") {
                                    $date = new DateTimeField($value);
                                    $fieldvalue = $date->getDisplayTime();
                                } else {
                                    $userModel = Users_Privileges_Model::getCurrentUserModel();
                                    if ($userModel->get("hour_format") == "12") {
                                        $value = Vtiger_Time_UIType::getTimeValueInAMorPM($value);
                                    }
                                    $fieldvalue = $value;
                                }
                            } else {
                                if ($fieldType == "picklist" && !empty($value)) {
                                    if (is_array($picklistArray)) {
                                        if (is_array($picklistArray[$dbField->name]) && $field->getFieldName() != "activitytype" && !in_array($value, $picklistArray[$dbField->name])) {
                                            $fieldvalue = $app_strings["LBL_NOT_ACCESSIBLE"];
                                        } else {
                                            $fieldvalue = getTranslatedString($value, $module);
                                        }
                                    } else {
                                        $fieldvalue = getTranslatedString($value, $module);
                                    }
                                } else {
                                    if ($fieldType == "multipicklist" && !empty($value)) {
                                        if (is_array($picklistArray[1])) {
                                            $valueList = explode(" |##| ", $value);
                                            $translatedValueList = array();
                                            foreach ($valueList as $value) {
                                                if (is_array($picklistArray[1][$dbField->name]) && !in_array($value, $picklistArray[1][$dbField->name])) {
                                                    $translatedValueList[] = $app_strings["LBL_NOT_ACCESSIBLE"];
                                                } else {
                                                    $translatedValueList[] = getTranslatedString($value, $module);
                                                }
                                            }
                                        }
                                        if (!is_array($picklistArray[1]) || !is_array($picklistArray[1][$dbField->name])) {
                                            $fieldvalue = str_replace(" |##| ", ", ", $value);
                                        } else {
                                            implode(", ", $translatedValueList);
                                        }
                                    } else {
                                        if ($fieldType == "double" && $operation != "ExcelExport") {
                                            if ($current_user->truncate_trailing_zeros == true) {
                                                $fieldvalue = decimalFormat($fieldvalue);
                                            }
                                        } else {
                                            if ($fieldType == "owner" && is_numeric($fieldvalue)) {
                                                $_record_model = Users_Record_Model::getInstanceById($fieldvalue, "Users");
                                                $fieldvalue = $_record_model->get("first_name") . " " . $_record_model->get("last_name");
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
    if ($fieldType == "currency" && $value == "" && $operation != "ExcelExport") {
        $currencyField = new CurrencyField($value);
        $fieldvalue = $currencyField->getDisplayValue();
        return $fieldvalue;
    }
    if ($fieldvalue == "" && $operation != "ExcelExport") {
        return "";
    }
    $fieldvalue = str_replace("<", "&lt;", $fieldvalue);
    $fieldvalue = str_replace(">", "&gt;", $fieldvalue);
    $fieldvalue = decode_html($fieldvalue);
    if (stristr($fieldvalue, "|##|") && empty($fieldType)) {
        $fieldvalue = str_ireplace(" |##| ", ", ", $fieldvalue);
    } else {
        if ($fld_type == "date" && empty($fieldType)) {
            $fieldvalue = DateTimeField::convertToUserFormat($fieldvalue);
        } else {
            if ($fld_type == "datetime" && empty($fieldType)) {
                $date = new DateTimeField($fieldvalue);
                $fieldvalue = $date->getDisplayDateTimeValue();
            }
        }
    }
    if ($fieldInfo["uitype"] == "19" && ($module == "Documents" || $module == "Emails" || strpos($fieldName, "cf_acf_rtf_") != -1)) {
        return $fieldvalue;
    }
    if ($operation == "ExcelExport") {
        return array("value" => htmlentities($fieldvalue, ENT_QUOTES, $default_charset), "type" => $fieldType);
    }
    return htmlentities($fieldvalue, ENT_QUOTES, $default_charset);
}
function transformAdvFilterListToDBFormatVReport($advFilterList)
{
    $db = PearDatabase::getInstance();
    foreach ($advFilterList as $k => $columnConditions) {
        foreach ($columnConditions["columns"] as $j => $columnCondition) {
            if (empty($columnCondition)) {
                continue;
            }
            $advFilterColumn = $columnCondition["columnname"];
            $advFilterComparator = $columnCondition["comparator"];
            $advFilterValue = $columnCondition["value"];
            $columnInfo = explode(":", $advFilterColumn);
            $moduleFieldLabel = $columnInfo[2];
            list($module, $fieldLabel) = explode("_", $moduleFieldLabel, 2);
            $fieldInfo = getfieldbyvreportlabel($module, $fieldLabel);
            $fieldType = NULL;
            if (!empty($fieldInfo)) {
                $field = WebserviceField::fromArray($db, $fieldInfo);
                $fieldType = $field->getFieldDataType();
            }
            if ($fieldType == "currency") {
                if ($field->getUIType() == "72") {
                    $advFilterValue = Vtiger_Currency_UIType::convertToDBFormat($advFilterValue, NULL, true);
                } else {
                    $advFilterValue = Vtiger_Currency_UIType::convertToDBFormat($advFilterValue);
                }
            }
            $specialDateConditions = Vtiger_Functions::getSpecialDateTimeCondtions();
            $tempVal = explode(",", $advFilterValue);
            if (($columnInfo[4] == "D" || $columnInfo[4] == "T" && $columnInfo[1] != "time_start" && $columnInfo[1] != "time_end" || $columnInfo[4] == "DT") && $columnInfo[4] != "" && $advFilterValue != "" && !in_array($advFilterComparator, $specialDateConditions)) {
                $val = array();
                for ($i = 0; $i < count($tempVal); $i++) {
                    if (trim($tempVal[$i]) != "") {
                        $date = new DateTimeField(trim($tempVal[$i]));
                        if ($columnInfo[4] == "D") {
                            $val[$i] = DateTimeField::convertToDBFormat(trim($tempVal[$i]));
                        } else {
                            if ($columnInfo[4] == "DT") {
                                $values = explode(" ", $tempVal[$i]);
                                $date = new DateTimeField($values[0]);
                                $val[$i] = $date->getDBInsertDateValue();
                            } else {
                                if ($fieldType == "time") {
                                    $val[$i] = Vtiger_Time_UIType::getTimeValueWithSeconds($tempVal[$i]);
                                } else {
                                    $val[$i] = $date->getDBInsertTimeValue();
                                }
                            }
                        }
                    }
                }
                $advFilterValue = implode(",", $val);
            }
            $advFilterList[$k]["columns"][$j]["value"] = $advFilterValue;
        }
    }
    return $advFilterList;
}
function getVReportSearchCondition($searchParams, $filterId)
{
    if (!empty($searchParams)) {
        $db = PearDatabase::getInstance();
        $params = array();
        $conditionQuery = "";
        if ($filterId == false) {
            $conditionQuery .= " WHERE ";
        } else {
            $conditionQuery .= " AND ";
        }
        $conditionQuery .= " (( ";
        foreach ($searchParams as $i => $condition) {
            list($fieldName, $searchValue) = $condition;
            if ($fieldName == "reportname" || $fieldName == "description") {
                $conditionQuery .= " vtiger_vreport." . $fieldName . " LIKE ? ";
                array_push($params, "%" . $searchValue . "%");
            } else {
                if ($fieldName == "reporttype" || $fieldName == "foldername" || $fieldName == "owner") {
                    $searchValue = explode(",", $searchValue);
                    if ($fieldName == "foldername") {
                        $fieldName = "folderid";
                    }
                    if ($fieldName == "reporttype" && in_array("tabular", $searchValue)) {
                        array_push($searchValue, "summary");
                    }
                    $conditionQuery .= " vtiger_vreport." . $fieldName . " IN (" . generateQuestionMarks($searchValue) . ") ";
                    foreach ($searchValue as $value) {
                        array_push($params, $value);
                    }
                } else {
                    if ($fieldName == "primarymodule") {
                        $searchValue = explode(",", $searchValue);
                        $conditionQuery .= " vtiger_vreportmodules." . $fieldName . " IN (" . generateQuestionMarks($searchValue) . ") ";
                        foreach ($searchValue as $value) {
                            array_push($params, $value);
                        }
                    }
                }
            }
            if ($i < count($searchParams) - 1) {
                $conditionQuery .= " AND ";
            }
        }
        $conditionQuery .= " ) ";
        $conditionQuery .= ") ";
        return $db->convert2Sql($conditionQuery, $params);
    } else {
        return false;
    }
}

?>