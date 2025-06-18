<?php

global $root_directory;
require_once $root_directory . 'modules/VTEExportToXLS/libraries/PHPExcel/PHPExcel.php';

class VTEExportToXLS_ExportRelatedList_Action extends Vtiger_Mass_Action
{
    private $moduleInstance;

    private $focus;

    private $picklistValues;

    private $fieldArray;

    private $fieldDataTypeCache = [];

    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->get('related_module');
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
        $related_module = $request->get('related_module');
        $this->moduleInstance = Vtiger_Module_Model::getInstance($related_module);
        $this->moduleFieldInstances = $this->moduleFieldInstances($related_module);
        $this->focus = CRMEntity::getInstance($related_module);
        $headers = $this->getRelatedHeaders($request);
        $query = $this->getExportQuery($request);
        $result = $db->pquery($query, []);
        $entries = [];

        while ($row = $db->fetchByAssoc($result)) {
            $entries[] = $this->sanitizeValues($row);
        }
        $this->outputxls($request, $headers, $entries);
    }

    public function get_related_model(Vtiger_Request $request)
    {
        $source_module = $request->get('source_module');
        $related_module = $request->get('related_module');
        $record = $request->get('record');
        $record_model = Vtiger_Record_Model::getInstanceById($record, $source_module);
        $related_model = Vtiger_RelationListView_Model::getInstance($record_model, $related_module);

        return $related_model;
    }

    public function getExportQuery(Vtiger_Request $request)
    {
        $related_model = $this->get_related_model($request);
        $query = $related_model->getRelationQuery();
        $mode = $request->get('mode');
        if (empty($mode) || $mode == 'ExportAllData') {
            return $query;
        }
        if ($mode == 'ExportSelectedRecords') {
            $selected_ids = $request->get('selected_ids');
            $query .= ' AND vtiger_crmentity.crmid IN (' . $selected_ids . ')';

            return $query;
        }
        if ($mode == 'ExportCurrentPage') {
            $selected_ids = $request->get('ids_in_page');
            $query .= ' AND vtiger_crmentity.crmid IN (' . $selected_ids . ')';

            return $query;
        }
    }

    public function getRelatedHeaders(Vtiger_Request $request)
    {
        $related_model = $this->get_related_model($request);
        $headers = $related_model->getHeaders();

        return $headers;
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

    public function outputxls($request, $headers, $entries)
    {
        global $adb;
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $related_module = $request->get('related_module');
        $rs = $adb->pquery('SELECT `download_to_server`,custom_filename, file_name FROM `vteexport_to_xls_settings`;', []);
        $isCustomFilename = $adb->query_result($rs, 0, 'custom_filename');
        $custom_file_name = $adb->query_result($rs, 0, 'file_name');
        $download_to_server = $adb->query_result($rs, 0, 'download_to_server');
        $fileName = $moduleLabel = str_replace(' ', '_', decode_html(vtranslate($related_module, $related_module)));
        if ($isCustomFilename) {
            $currentDate = DateTimeField::convertToUserFormat(date('Y-m-d'));
            if ($custom_file_name != '') {
                $custom_file_name = str_replace('$module_name$', $moduleLabel, $custom_file_name);
                $custom_file_name = str_replace('$user_email$', decode_html($currentUser->get('email1')), $custom_file_name);
                $custom_file_name = str_replace('$current_date$', $currentDate, $custom_file_name);
                $fileName = $custom_file_name;
            }
        }
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties();
        $letters = $this->mkRange('A', 'DZ');
        $count = 0;
        $styleArray = ['font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '606060']]];
        foreach ($headers as $field_model) {
            $cell_name = $letters[$count] . '1';
            ++$count;
            $value = vtranslate($field_model->get('label'), $related_module);
            $objPHPExcel->getActiveSheet()->SetCellValue($cell_name, $value);
            $objPHPExcel->getActiveSheet()->getStyle($cell_name)->applyFromArray($styleArray);
        }
        $column = 'A';
        $rowCount = 2;
        foreach ($entries as $row) {
            $line = preg_replace('/[a-zA-Z]+::::/', '', $row);
            $column = 'A';
            $col = 0;
            foreach ($line as $fieldName => $fieldValue) {
                $fieldValue = trim(vtranslate($fieldValue, $related_module));
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
                ++$col;
            }
            ++$rowCount;
        }
        foreach (range('A', 'DZ') as $columnID) {
            $objPHPExcel->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
        }
        $objPHPExcel->getActiveSheet()->setTitle(date('m-d-Y-h-i'));
        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        ob_end_clean();
        ob_clean();
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
        foreach ($arr as $fieldName => &$value) {
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
                        $ownerModel = Users_Record_Model::getInstanceById($value, 'Users');
                        $user_first_Name = $ownerModel->get('first_name');
                        $user_last_Name = $ownerModel->get('last_name');
                        $value = $user_first_Name . ' ' . $user_last_Name;
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
            } else {
                unset($arr[$fieldName]);

                continue;
            }
        }

        return $arr;
    }

    public function moduleFieldInstances($moduleName)
    {
        return $this->moduleInstance->getFields();
    }
}
