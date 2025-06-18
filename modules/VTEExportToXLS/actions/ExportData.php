<?php

global $root_directory;
require_once $root_directory . 'modules/VTEExportToXLS/libraries/PHPExcel/PHPExcel.php';

class VTEExportToXLS_ExportData_Action extends Vtiger_Mass_Action
{
    private $moduleInstance;

    private $focus;

    private $picklistValues;

    private $fieldArray;

    private $fieldDataTypeCache = [];

    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->get('source_module');
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModuleActionPermission($moduleModel->getId(), 'Export')) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
    }

    /**
     * Function is called by the controller.
     */
    public function process(Vtiger_Request $request)
    {
        $this->ExportData($request);
    }

    /**
     * Function exports the data based on the mode.
     */
    public function ExportData(Vtiger_Request $request)
    {
        $db = PearDatabase::getInstance();
        $moduleName = $request->get('source_module');
        $cvId = $request->get('viewname');
        $listViewModel = Vtiger_ListView_Model::getInstance($moduleName, $cvId);
        $listViewHeaders = $listViewModel->getListViewHeaders();
        $listViewHeaders_label = [];
        foreach ($listViewHeaders as $item) {
            if (strpos($item->get('name'), ';') === false) {
                $getLabelModuleName = $moduleName;
                $field_name = $item->get('name');
            } else {
                $rel_field = $item->get('name');
                $rel_field = str_replace(';', '', $rel_field);
                $rel_field = str_replace('(', '', $rel_field);
                $rel_field = str_replace(')', '', $rel_field);
                $rel_field = explode(' ', $rel_field);
                $getLabelModuleName = $rel_field[2];
                $field_name = $rel_field[0] . ';' . $rel_field[2] . ';' . $rel_field[3];
            }
            $listViewHeaders_label[$field_name] = vtranslate($item->get('label'), $getLabelModuleName);
        }
        $this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
        $this->moduleFieldInstances = $this->moduleFieldInstances($moduleName);
        $this->focus = CRMEntity::getInstance($moduleName);
        $query = $this->getExportQuery($request);
        $result = $db->pquery($query, []);
        $translatedHeaders = $this->getHeaders();
        $entries = [];
        for ($j = 0; $j < $db->num_rows($result); ++$j) {
            $entries[] = $this->sanitizeValues($db->fetchByAssoc($result, $j));
        }
        $this->outputxls($request, $translatedHeaders, $entries, $listViewHeaders_label);
    }

    public function getHeaders()
    {
        $headers = [];
        if (!empty($this->accessibleFields)) {
            $accessiblePresenceValue = [0, 2];
            foreach ($this->accessibleFields as $fieldName) {
                $fieldModel = $this->moduleFieldInstances[$fieldName];
                $presence = $fieldModel->get('presence');
                if (in_array($presence, $accessiblePresenceValue) && $fieldModel->get('displaytype') != '6') {
                    $headers[] = $fieldModel->get('label');
                }
            }
        } else {
            foreach ($this->moduleFieldInstances as $field) {
                $headers[] = $field->get('label');
            }
        }
        $translatedHeaders = [];
        foreach ($headers as $header) {
            $translatedHeaders[] = vtranslate(html_entity_decode($header, ENT_QUOTES), $this->moduleInstance->getName());
        }
        $translatedHeaders = array_map('decode_html', $translatedHeaders);

        return $translatedHeaders;
    }

    public function getAdditionalQueryModules()
    {
        return array_merge(getInventoryModules(), ['Products', 'Services', 'PriceBooks']);
    }

    /**
     * Function that generates Export Query based on the mode.
     * @return <String> export query
     */
    public function getExportQuery(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $mode = $request->getMode();
        $cvId = $request->get('viewname');
        $moduleName = $request->get('source_module');
        global $adb;
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $queryGenerator = new QueryGenerator($moduleName, $currentUser);
        } else {
            $queryGenerator = new EnhancedQueryGenerator($moduleName, $currentUser);
        }
        $queryGenerator->initForCustomViewById($cvId);
        $fieldInstances = $this->moduleFieldInstances;
        $orderBy = $request->get('orderby');
        $orderByFieldModel = $fieldInstances[$orderBy];
        $sortOrder = $request->get('sortorder');
        if ($mode !== 'ExportAllData') {
            $operator = $request->get('operator');
            $searchKey = $request->get('search_key');
            $searchValue = $request->get('search_value');
            $tagParams = $request->get('tag_params');
            if (!$tagParams) {
                $tagParams = [];
            }
            $searchParams = $request->get('search_params');
            if (!$searchParams) {
                $searchParams = [];
            }
            $glue = '';
            if ($searchParams && count($queryGenerator->getWhereFields())) {
                $glue = QueryGenerator::$AND;
            }
            $searchParams = array_merge($searchParams, $tagParams);
            $searchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParams, $this->moduleInstance);
            $queryGenerator->parseAdvFilterList($searchParams, $glue);
            if ($searchKey) {
                $queryGenerator->addUserSearchConditions(['search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator]);
            }
            if ($orderBy && $orderByFieldModel && ($orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE || $orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE)) {
                $queryGenerator->addWhereField($orderBy);
            }
        }
        if ($moduleName == 'Documents') {
            $folderValue = $request->get('folder_value');
            if (!empty($folderValue)) {
                $queryGenerator->addCondition($request->get('folder_id'), $folderValue, 'e');
            }
        }
        $accessiblePresenceValue = [0, 2];
        foreach ($fieldInstances as $field) {
            $presence = $field->get('presence');
            if (in_array($presence, $accessiblePresenceValue) && $field->get('displaytype') != '6') {
                $fields[] = $field->getName();
            }
        }
        $queryGenerator->setFields($fields);
        $query = $queryGenerator->getQuery();
        $additionalModules = $this->getAdditionalQueryModules();
        if (in_array($moduleName, $additionalModules)) {
            $query = $this->moduleInstance->getExportQuery($this->focus, $query);
        }
        $query = str_replace('SELECT', 'SELECT vtiger_crmentity.crmid,', $query);
        $this->accessibleFields = $queryGenerator->getFields();
        switch ($mode) {
            case 'ExportAllData':
                if ($orderBy && $orderByFieldModel) {
                    $query .= ' ORDER BY ' . $queryGenerator->getOrderByColumn($orderBy) . ' ' . $sortOrder;
                }
                break;
            case 'ExportCurrentPage':
                $pagingModel = new Vtiger_Paging_Model();
                $limit = $pagingModel->getPageLimit();
                $currentPage = $request->get('page');
                if (empty($currentPage)) {
                    $currentPage = 1;
                }
                $currentPageStart = ($currentPage - 1) * $limit;
                if ($currentPageStart < 0) {
                    $currentPageStart = 0;
                }
                if ($orderBy && $orderByFieldModel) {
                    $query .= ' ORDER BY ' . $queryGenerator->getOrderByColumn($orderBy) . ' ' . $sortOrder;
                }
                $query .= ' LIMIT ' . $currentPageStart . ',' . $limit;
                break;
            case 'ExportSelectedRecords':
                $idList = $this->getRecordsListFromRequest($request);
                $baseTable = $this->moduleInstance->get('basetable');
                $baseTableColumnId = $this->moduleInstance->get('basetableid');
                if (!empty($idList)) {
                    if (!empty($baseTable) && !empty($baseTableColumnId)) {
                        $idList = implode(',', $idList);
                        $query .= ' AND ' . $baseTable . '.' . $baseTableColumnId . ' IN (' . $idList . ')';
                    }
                } else {
                    $query .= ' AND ' . $baseTable . '.' . $baseTableColumnId . ' NOT IN (' . implode(',', $request->get('excluded_ids')) . ')';
                }
                if ($orderBy && $orderByFieldModel) {
                    $query .= ' ORDER BY ' . $queryGenerator->getOrderByColumn($orderBy) . ' ' . $sortOrder;
                }
                break;

            default:
                break;
        }

        return $query;
    }

    /**
     * Function returns the export type - This can be extended to support different file exports.
     * @return <String>
     */
    public function getExportContentType(Vtiger_Request $request)
    {
        $type = $request->get('export_type');
        if (empty($type)) {
            return 'text/csv';
        }
    }

    public function mkRange($start, $end)
    {
        $count = $this->strToInt($end) - $this->strToInt($start);
        $r = [];
        do {
            $r[] = $start++;
        } while ($count--);

        return $r;
    }

    public function strToInt($str)
    {
        $str = strrev($str);
        $dec = 0;
        for ($i = 0; $i < strlen($str); ++$i) {
            $dec += (base_convert($str[$i], 36, 10) - 9) * pow(26, $i);
        }

        return $dec;
    }

    public function outputxls($request, $headers, $entries, $listViewHeaders_label)
    {
        global $adb;
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $moduleName = $request->get('source_module');
        $relatedModule = $request->get('relatedModule');
        $export_from = $request->get('export_from');
        $list_column_name = '';
        $rs = $adb->pquery('SELECT `download_to_server`,custom_filename, file_name FROM `vteexport_to_xls_settings`;', []);
        $isCustomFilename = $adb->query_result($rs, 0, 'custom_filename');
        $custom_file_name = $adb->query_result($rs, 0, 'file_name');
        $download_to_server = $adb->query_result($rs, 0, 'download_to_server');
        if ($export_from == 'relatedList') {
            $list_column_name = $request->get('column_list_name');
            $list_column_name = explode(',', $list_column_name);
            $fileName = $moduleLabel = str_replace(' ', '_', decode_html(vtranslate($relatedModule, $relatedModule)));
        } else {
            $fileName = $moduleLabel = str_replace(' ', '_', decode_html(vtranslate($moduleName, $moduleName)));
        }
        if ($isCustomFilename) {
            $currentDate = DateTimeField::convertToUserFormat(date('Y-m-d'));
            if ($custom_file_name != '') {
                $custom_file_name = str_replace('$module_name$', $moduleLabel, $custom_file_name);
                $custom_file_name = str_replace('$user_email$', decode_html($currentUser->get('email1')), $custom_file_name);
                $custom_file_name = str_replace('$current_date$', $currentDate, $custom_file_name);
                $fileName = $custom_file_name;
            }
        }
        $exportType = $this->getExportContentType($request);
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties();
        $letters = $this->mkRange('A', 'DZ');
        $count = 0;
        $cell_name = '';
        $styleArray = ['font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '606060']]];
        $show_columns = [];
        foreach ($listViewHeaders_label as $key => $tittle) {
            $show_columns[] = $key;
            $cell_name = $letters[$count] . '1';
            ++$count;
            $value = $tittle;
            $objPHPExcel->getActiveSheet()->SetCellValue($cell_name, $value);
            $objPHPExcel->getActiveSheet()->getStyle($cell_name)->applyFromArray($styleArray);
        }
        $column = 'A';
        $rowCount = 2;
        if ($export_from == 'relatedList') {
            foreach ($entries as $row) {
                $lines = [];
                foreach ($list_column_name as $name) {
                    $value = preg_replace('/[a-zA-Z]+::::/', '', $row[$name]);
                    $value = trim(vtranslate($value, $moduleName));
                    $lines[$name] = $value;
                }
                $column = 'A';
                $col = 0;
                foreach ($lines as $fieldName => $fieldValue) {
                    if (in_array($col, $show_columns)) {
                        if (empty($fieldName)) {
                            continue;
                        }
                        $cell = $objPHPExcel->getActiveSheet()->getCell($column . $rowCount);
                        $fieldInfo = $this->fieldArray[$fieldName];
                        $uitype = $fieldInfo->get('uitype');
                        if ($uitype == 72 || $uitype == 71 || $uitype == 7 && $fieldInfo->get('typeofdata') == 'N~O' || $uitype == 9) {
                            $cell->setValueExplicit($fieldValue, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                            $objPHPExcel->getActiveSheet()->getStyle($column . $rowCount)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        } else {
                            $cell->setValueExplicit($fieldValue, PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                        ++$column;
                    }
                    ++$col;
                }
                ++$rowCount;
            }
        } else {
            foreach ($entries as $row) {
                $line = preg_replace('/[a-zA-Z]+::::/', '', $row);
                $column = 'A';
                foreach ($listViewHeaders_label as $key => $tittle) {
                    if (in_array($key, array_keys($line))) {
                        $fieldName = $key;
                        $fieldValue = $line[$fieldName];
                        $fieldValue = trim(vtranslate($fieldValue, $moduleName));
                        if (empty($fieldName)) {
                            continue;
                        }
                        $cell = $objPHPExcel->getActiveSheet()->getCell($column . $rowCount);
                        $fieldInfo = $this->fieldArray[$fieldName];
                        $uitype = $fieldInfo->get('uitype');
                        if ($uitype == 72 || $uitype == 71 || $uitype == 7 && $fieldInfo->get('typeofdata') == 'N~O' || $uitype == 9) {
                            $cell->setValueExplicit($fieldValue, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                            $objPHPExcel->getActiveSheet()->getStyle($column . $rowCount)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                        } else {
                            $cell->setValueExplicit($fieldValue, PHPExcel_Cell_DataType::TYPE_STRING);
                        }
                        ++$column;
                    } else {
                        if (strpos($key, ';') !== false) {
                            $recordId = $line['crmid'];
                            $recordModel = Vtiger_Record_Model::getInstanceById($recordId);
                            $fieldName = explode(';', $key);
                            [$rel_fieldName, $parentModule] = $fieldName;
                            $parentModuleModel = Vtiger_Module_Model::getInstance($parentModule);
                            $parentModuleField = $fieldName[2];
                            if (empty($parentModuleField)) {
                                continue;
                            }
                            $parentRecord = $recordModel->get($rel_fieldName);
                            if ($parentRecord > 0) {
                                $sql1_check = 'SELECT * FROM `vtiger_crmentity` WHERE crmid = ? and deleted = 0;';
                                $re = $adb->pquery($sql1_check, [$parentRecord]);
                                if ($adb->num_rows($re) > 0) {
                                    $parentRecordModel = Vtiger_Record_Model::getInstanceById($parentRecord, $parentModule);
                                    $parentFieldValue = $parentRecordModel->get($parentModuleField);
                                    $fieldInfo = $parentRecordModel->getField($parentModuleField);
                                    $uitype = $fieldInfo->get('uitype');
                                    if (!$this->fieldDataTypeCache[$parentModuleField]) {
                                        $this->fieldDataTypeCache[$parentModuleField] = $fieldInfo->getFieldDataType();
                                    }
                                    $type = $this->fieldDataTypeCache[$parentModuleField];
                                    if ($uitype == 52 || $type == 'owner') {
                                        $sql = 'SELECT * FROM vtiger_users WHERE id = ' . $parentFieldValue . ';';
                                        $re = $adb->pquery($sql, []);
                                        if ($adb->num_rows($re) > 0) {
                                            $ownerModel = Users_Record_Model::getInstanceById($parentFieldValue, 'Users');
                                            $user_first_Name = $ownerModel->get('first_name');
                                            $user_last_Name = $ownerModel->get('last_name');
                                            $parentFieldValue = $user_first_Name . ' ' . $user_last_Name;
                                        } else {
                                            $group_record_model = Settings_Groups_Record_Model::getInstance($parentFieldValue);
                                            $groupname = $group_record_model->get('groupname');
                                            $parentFieldValue = $groupname;
                                        }
                                    } else {
                                        $parentFieldValue = trim(vtranslate($parentFieldValue, $parentModule));
                                    }
                                    $cell = $objPHPExcel->getActiveSheet()->getCell($column . $rowCount);
                                    $fieldInfo = Vtiger_Field_Model::getInstance($parentModuleField, $parentModuleModel);
                                    $uitype = $fieldInfo->get('uitype');
                                    if ($uitype == 72 || $uitype == 71 || $uitype == 7 && $fieldInfo->get('typeofdata') == 'N~O' || $uitype == 9) {
                                        $cell->setValueExplicit($parentFieldValue, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                                        $objPHPExcel->getActiveSheet()->getStyle($column . $rowCount)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                                    } else {
                                        if ($uitype == 10 || $fieldInfo->getFieldDataType() == 'reference') {
                                            if ($parentFieldValue > 0) {
                                                $sql = 'SELECT * FROM vtiger_crmentity WHERE crmid = ? AND deleted = 0;';
                                                $re = $adb->pquery($sql, [$parentFieldValue]);
                                                if ($adb->num_rows($re) > 0) {
                                                    $record_model = Vtiger_Record_Model::getInstanceById($parentFieldValue);
                                                    if ($record_model) {
                                                        $parentFieldValue = $record_model->get('label');
                                                        $cell->setValueExplicit($parentFieldValue, PHPExcel_Cell_DataType::TYPE_STRING);
                                                    } else {
                                                        $cell->setValueExplicit('', PHPExcel_Cell_DataType::TYPE_STRING);
                                                    }
                                                } else {
                                                    $cell->setValueExplicit('', PHPExcel_Cell_DataType::TYPE_STRING);
                                                }
                                            } else {
                                                $cell->setValueExplicit('', PHPExcel_Cell_DataType::TYPE_STRING);
                                            }
                                        } else {
                                            $parentFieldValue_export = $parentFieldValue;
                                            $parentFieldValue = $fieldInfo->getDisplayValue($parentFieldValue);
                                            $cell->setValueExplicit($parentFieldValue_export, PHPExcel_Cell_DataType::TYPE_STRING);
                                        }
                                    }
                                }
                            }
                            ++$column;
                        }
                    }
                }
                ++$rowCount;
            }
        }
        foreach (range('A', 'DZ') as $columnID) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
        }
        $objPHPExcel->getActiveSheet()->setTitle(date('m-d-Y-h-i'));
        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_clean();
        ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fileName . '.xlsx"');
        header('Cache-Control: max-age=1');
        header('Pragma: public');
        if ($download_to_server) {
            $pathOfFile = 'storage/export_excel/' . $fileName . '.xlsx';
            $objWriter->save($pathOfFile);
            header('Content-Length: ' . filesize($pathOfFile));
            readfile($pathOfFile);
        } else {
            $objWriter->save('php://output');
        }
        exit;
    }

    /**
     * this function takes in an array of values for an user and sanitizes it for export.
     * @param array $arr - the array of values
     */
    public function sanitizeValues($arr)
    {
        $db = PearDatabase::getInstance();
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $roleid = $currentUser->get('roleid');
        if (empty($this->fieldArray)) {
            $this->fieldArray = $this->moduleFieldInstances;
            foreach ($this->fieldArray as $fieldName => $fieldObj) {
                if ($fieldObj->get('table') == 'vtiger_inventoryproductrel' && ($fieldName == 'discount_amount' || $fieldName == 'discount_percent')) {
                    $fieldName = 'item_' . $fieldName;
                    $this->fieldArray[$fieldName] = $fieldObj;
                } else {
                    $columnName = $fieldObj->get('column');
                    $this->fieldArray[$columnName] = $fieldObj;
                }
            }
        }
        $moduleName = $this->moduleInstance->getName();
        $re_arr = [];
        foreach ($arr as $fieldName => $value) {
            if ($fieldName == 'crmid') {
                $re_arr['crmid'] = $value;
            }
            if (isset($this->fieldArray[$fieldName])) {
                $fieldInfo = $this->fieldArray[$fieldName];
                $beginsWithDoubleQuote = strpos($value, '"') === 0;
                $endsWithDoubleQuote = substr($value, -1) === '"' ? 1 : 0;
                $value = trim($value, '"');
                $uitype = $fieldInfo->get('uitype');
                $fieldname = $fieldInfo->get('name');
                if (!$this->fieldDataTypeCache[$fieldName]) {
                    $this->fieldDataTypeCache[$fieldName] = $fieldInfo->getFieldDataType();
                }
                $type = $this->fieldDataTypeCache[$fieldName];
                if ($beginsWithDoubleQuote) {
                    $value = '"' . $value;
                }
                if ($endsWithDoubleQuote) {
                    $value = (string) $value . '"';
                }
                if ($fieldname != 'hdnTaxType' && ($uitype == 15 || $uitype == 16 || $uitype == 33)) {
                    if (empty($this->picklistValues[$fieldname])) {
                        $this->picklistValues[$fieldname] = $this->fieldArray[$fieldname]->getPicklistValues();
                    }
                    if ($uitype == 33 || $uitype == 16 || array_key_exists($value, $this->picklistValues[$fieldname])) {
                        $value = trim($value);
                    } else {
                        $value = '';
                    }
                } else {
                    if ($uitype == 52 || $type == 'owner') {
                        $sql = 'SELECT * FROM vtiger_users WHERE id = ' . $value . ';';
                        $re = $db->pquery($sql, []);
                        if ($db->num_rows($re) > 0) {
                            $ownerModel = Users_Record_Model::getInstanceById($value, 'Users');
                            if ($ownerModel && $ownerModel != null) {
                                $user_first_Name = $ownerModel->get('first_name');
                                $user_last_Name = $ownerModel->get('last_name');
                                $value = $user_first_Name . ' ' . $user_last_Name;
                            }
                        } else {
                            $sql = 'SELECT groupname FROM vtiger_groups WHERE groupid = ' . $value;
                            $re = $db->pquery($sql, []);
                            if ($db->num_rows($re) > 0) {
                                $groupname = $db->query_result($re, 0, 'groupname');
                                $value = $groupname;
                            } else {
                                $value = '';
                            }
                        }
                    } else {
                        if ($type == 'reference') {
                            $value = trim($value);
                            if (!empty($value)) {
                                $parent_module = getSalesEntityType($value);
                                $displayValueArray = getEntityName($parent_module, $value);
                                if (!empty($displayValueArray)) {
                                    foreach ($displayValueArray as $k => $v) {
                                        $displayValue = $v;
                                    }
                                }
                                if (!empty($parent_module) && !empty($displayValue)) {
                                    $value = $parent_module . '::::' . $displayValue;
                                } else {
                                    $value = '';
                                }
                            } else {
                                $value = '';
                            }
                        } else {
                            if ($uitype == 72 || $uitype == 71) {
                                $value = CurrencyField::convertToUserFormat($value, null, true, true);
                            } else {
                                if ($uitype == 7 && $fieldInfo->get('typeofdata') == 'N~O' || $uitype == 9) {
                                    $value = decimalFormat($value);
                                } else {
                                    if ($type == 'date') {
                                        if ($value && $value != '0000-00-00') {
                                            $value = DateTimeField::convertToUserFormat($value);
                                        }
                                    } else {
                                        if ($type == 'datetime') {
                                            if ($moduleName == 'Calendar' && in_array($fieldName, ['date_start', 'due_date'])) {
                                                $timeField = 'time_start';
                                                if ($fieldName === 'due_date') {
                                                    $timeField = 'time_end';
                                                }
                                                $value = $value . ' ' . $arr[$timeField];
                                            }
                                            if (trim($value) && $value != '0000-00-00 00:00:00') {
                                                $value = Vtiger_Datetime_UIType::getDisplayDateTimeValue($value);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ($moduleName == 'Documents' && $fieldname == 'description') {
                    $value = strip_tags($value);
                    $value = str_replace('&nbsp;', '', $value);
                    array_push($new_arr, $value);
                }
                $re_arr[$fieldname] = $value;
            } else {
                unset($arr[$fieldName]);

                continue;
            }
        }

        return $re_arr;
    }

    public function moduleFieldInstances($moduleName)
    {
        return $this->moduleInstance->getFields();
    }
}
