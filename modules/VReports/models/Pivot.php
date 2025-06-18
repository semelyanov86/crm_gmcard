<?php

/**
 * Date: 8/9/18
 * Time: 11:19 AM.
 */
class VReports_Pivot_Model extends Vtiger_Base_Model
{
    public $sort;

    public $limit;

    public $order;

    public static function getInstanceById($reportModel)
    {
        $self = new self();
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT * FROM vtiger_vreporttype WHERE reportid = ?', [$reportModel->getId()]);
        $data = $db->query_result($result, 0, 'data');
        $sort = $db->query_result($result, 0, 'sort_by');
        $limit = $db->query_result($result, 0, 'limit');
        $order = $db->query_result($result, 0, 'order_by');
        if (!empty($data)) {
            $decodeData = Zend_Json::decode(decode_html($data));
            $decodeSort = Zend_Json::decode(decode_html($sort));
            $self->sort = $decodeSort;
            $self->limit = $limit;
            $self->order = $order;
            $self->setData($decodeData);
            $self->setParent($reportModel);
            $self->setId($reportModel->getId());
        }

        return $self;
    }

    public function getId()
    {
        return $this->get('reportid');
    }

    public function setId($id)
    {
        $this->set('reportid', $id);
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getChartType()
    {
        $type = $this->get('type');
        if (empty($type)) {
            $type = 'pieChart';
        }

        return $type;
    }

    public function getGroupByFieldRows()
    {
        return $this->get('groupbyfield_rows');
    }

    public function getGroupByFieldColumns()
    {
        return $this->get('groupbyfield_columns');
    }

    public function getDataFields()
    {
        return $this->get('datafields');
    }

    public function getData($nonHtml = false)
    {
        $pivotModel = new Base_Pivot($this);

        return $pivotModel->generateData($nonHtml);
    }

    public function getRenameField($recordId)
    {
        global $adb;
        $rename_field_result = $adb->pquery('SELECT rename_field  FROM vtiger_vreporttype WHERE reportid = ?', [$recordId]);
        $row = $adb->fetchByAssoc($rename_field_result, 0);
        $rename_fields = json_decode(html_entity_decode($row['rename_field']));

        return $rename_fields;
    }
}
class Base_Pivot extends Vtiger_Base_Model
{
    protected $regexSpecialCharacter = "/([~`!@#\$%^&*()\\_+=\\[\\]{}|\\<>;:'\\\",?])/";

    public function __construct($parent)
    {
        $this->setParent($parent);
        $this->setReportRunObject();
        $this->setQueryColumns($this->getParent()->getDataFields());
        $this->setGroupByRows($this->getParent()->getGroupByFieldRows());
        $this->setGroupByColumns($this->getParent()->getGroupByFieldColumns());
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getReportModel()
    {
        $parent = $this->getParent();

        return $parent->getParent();
    }

    public function isRecordCount()
    {
        return $this->isRecordCount;
    }

    public function setRecordCount()
    {
        $this->isRecordCount = true;
    }

    public function setReportRunObject()
    {
        $pivotModel = $this->getParent();
        $reportModel = $pivotModel->getParent();
        $this->reportRun = VReportRun::getInstance($reportModel->get('reportid'));
    }

    public function getReportRunObject()
    {
        return $this->reportRun;
    }

    public function getFieldModelByReportColumnName($column)
    {
        $fieldInfo = explode(':', $column);
        $moduleFieldLabelInfo = explode('_', $fieldInfo[2]);
        $moduleName = $moduleFieldLabelInfo[0];
        $fieldName = $fieldInfo[3];
        if ($moduleName && $fieldName) {
            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
            $fieldInstance = $moduleModel->getField($fieldName);
            if ($moduleName == 'Calendar' && !$fieldInstance) {
                $moduleModel = Vtiger_Module_Model::getInstance('Events');

                return $moduleModel->getField($fieldName);
            }

            return $fieldInstance;
        }

        return false;
    }

    public function getQueryColumnsByFieldModel()
    {
        return $this->fieldModels;
    }

    public function setQueryColumns($columns)
    {
        if ($columns && is_string($columns)) {
            $columns = [$columns];
        }
        if (is_array($columns)) {
            foreach ($columns as $column) {
                if ($column == 'count(*)') {
                    $this->setRecordCount();
                } else {
                    $fieldModel = $this->getFieldModelByReportColumnName($column);
                    $columnInfo = explode(':', $column);
                    $referenceFieldReportColumnSQL = $this->getReportRunObject()->getEscapedColumns($columnInfo);
                    $aggregateFunction = $columnInfo[5];
                    if (empty($referenceFieldReportColumnSQL)) {
                        $reportColumnSQL = $this->getReportTotalColumnSQL($columnInfo);
                        $reportColumnSQLInfo = explode(' AS ', $reportColumnSQL);
                        if ($aggregateFunction == 'AVG') {
                            $label = '`' . $this->reportRun->replaceSpecialChar($reportColumnSQLInfo[1]) . '_AVG`';
                            $reportColumn = '(SUM(' . $reportColumnSQLInfo[0] . ')/COUNT(*)) AS ' . $label;
                        } else {
                            $label = '`' . $this->reportRun->replaceSpecialChar($reportColumnSQLInfo[1]) . '_' . $aggregateFunction . '`';
                            $reportColumn = $aggregateFunction . '(' . $reportColumnSQLInfo[0] . ') AS ' . $label;
                        }
                        $fieldModel->set('reportcolumn', $reportColumn);
                        $fieldModel->set('reportlabel', $this->reportRun->replaceSpecialChar($label));
                    } else {
                        $reportColumn = $referenceFieldReportColumnSQL;
                        $groupColumnSQLInfo = explode(' AS ', $referenceFieldReportColumnSQL);
                        $fieldModel->set('reportlabel', $this->reportRun->replaceSpecialChar($groupColumnSQLInfo[1]));
                        $fieldModel->set('reportcolumn', $this->reportRun->replaceSpecialChar($reportColumn));
                    }
                    $fieldModel->set('reportcolumninfo', $column);
                    if ($fieldModel) {
                        $fieldModels[] = $fieldModel;
                    }
                }
            }
        }
        if ($fieldModels) {
            $this->fieldModels = $fieldModels;
        }
    }

    public function setGroupByRows($columns)
    {
        if ($columns && is_string($columns)) {
            $columns = [$columns];
        }
        if (is_array($columns)) {
            foreach ($columns as $column) {
                $fieldModel = $this->getFieldModelByReportColumnName($column);
                if ($fieldModel) {
                    $columnInfo = explode(':', $column);
                    $referenceFieldReportColumnSQL = $this->getReportRunObject()->getEscapedColumns($columnInfo);
                    if (empty($referenceFieldReportColumnSQL)) {
                        $reportColumnSQL = $this->getReportColumnSQL($columnInfo);
                        $fieldModel->set('reportcolumn', $this->reportRun->replaceSpecialChar($reportColumnSQL));
                        if ($columnInfo[4] == 'D' || $columnInfo[4] == 'DT') {
                            $reportColumnSQLInfo = explode(' AS ', $reportColumnSQL);
                            $fieldModel->set('reportlabel', trim($this->reportRun->replaceSpecialChar($reportColumnSQLInfo[1]), "'"));
                        } else {
                            $fieldModel->set('reportlabel', $this->reportRun->replaceSpecialChar($columnInfo[2]));
                        }
                    } else {
                        $groupColumnSQLInfo = explode(' AS ', $referenceFieldReportColumnSQL);
                        $fieldModel->set('reportlabel', trim($this->reportRun->replaceSpecialChar($groupColumnSQLInfo[1]), "'"));
                        $fieldModel->set('reportcolumn', $this->reportRun->replaceSpecialChar($referenceFieldReportColumnSQL));
                    }
                    $fieldModel->set('reportcolumninfo', $column);
                    $fieldModels[] = $fieldModel;
                }
            }
        }
        if ($fieldModels) {
            $this->groupByRowsFieldModels = $fieldModels;
        }
    }

    public function setGroupByColumns($columns)
    {
        if ($columns && is_string($columns)) {
            $columns = [$columns];
        }
        if (is_array($columns)) {
            foreach ($columns as $column) {
                $fieldModel = $this->getFieldModelByReportColumnName($column);
                if ($fieldModel) {
                    $columnInfo = explode(':', $column);
                    $referenceFieldReportColumnSQL = $this->getReportRunObject()->getEscapedColumns($columnInfo);
                    if (empty($referenceFieldReportColumnSQL)) {
                        $reportColumnSQL = $this->getReportColumnSQL($columnInfo);
                        $fieldModel->set('reportcolumn', $this->reportRun->replaceSpecialChar($reportColumnSQL));
                        if ($columnInfo[4] == 'D' || $columnInfo[4] == 'DT') {
                            $reportColumnSQLInfo = explode(' AS ', $reportColumnSQL);
                            $fieldModel->set('reportlabel', trim($this->reportRun->replaceSpecialChar($reportColumnSQLInfo[1]), "'"));
                        } else {
                            $fieldModel->set('reportlabel', $this->reportRun->replaceSpecialChar($columnInfo[2]));
                        }
                    } else {
                        $groupColumnSQLInfo = explode(' AS ', $referenceFieldReportColumnSQL);
                        $fieldModel->set('reportlabel', trim($this->reportRun->replaceSpecialChar($groupColumnSQLInfo[1]), "'"));
                        $fieldModel->set('reportcolumn', $this->reportRun->replaceSpecialChar($referenceFieldReportColumnSQL));
                    }
                    $fieldModel->set('reportcolumninfo', $column);
                    $fieldModels[] = $fieldModel;
                }
            }
        }
        if ($fieldModels) {
            $this->groupByColumnsFieldModels = $fieldModels;
        }
    }

    public function getGroupbyRowsByFieldModel()
    {
        return $this->groupByRowsFieldModels;
    }

    public function getGroupbyColumnsByFieldModel()
    {
        return $this->groupByColumnsFieldModels;
    }

    /**
     * Function returns sql column for group by fields.
     * @param <Array> $selectedfields - field info report format
     * @return <String>
     */
    public function getReportColumnSQL($selectedfields)
    {
        $reportRunObject = $this->getReportRunObject();
        $append_currency_symbol_to_value = $reportRunObject->append_currency_symbol_to_value;
        $reportRunObject->append_currency_symbol_to_value = [];
        $columnSQL = $reportRunObject->getColumnSQL($selectedfields);
        $reportRunObject->append_currency_symbol_to_value = $append_currency_symbol_to_value;

        return $columnSQL;
    }

    /**
     * Function returns sql column for data fields.
     * @param <Array> $fieldInfo - field info report format
     * @return <string>
     */
    public function getReportTotalColumnSQL($fieldInfo)
    {
        $primaryModule = $this->getPrimaryModule();
        $columnTotalSQL = $this->getReportRunObject()->getColumnsTotalSQL($fieldInfo, $primaryModule) . ' AS ' . $fieldInfo[2];

        return $columnTotalSQL;
    }

    /**
     * Function returns labels for aggregate functions.
     * @param type $aggregateFunction
     * @return string
     */
    public function getAggregateFunctionLabel($aggregateFunction)
    {
        switch ($aggregateFunction) {
            case 'SUM':
                return 'LBL_TOTAL_SUM_OF';
            case 'AVG':
                return 'LBL_AVG_OF';
            case 'MIN':
                return 'LBL_MIN_OF';
            case 'MAX':
                return 'LBL_MAX_OF';
        }
    }

    /**
     * Function returns translated label for the field from report label
     * Report label format MODULE_FIELD_LABEL eg:Leads_Lead_Source.
     * @param <String> $column
     */
    public function getTranslatedLabelFromReportLabel($column)
    {
        $columnLabelInfo = explode('_', trim($column, '`'));
        $columnLabelInfo = array_diff($columnLabelInfo, ['SUM', 'MIN', 'MAX', 'AVG']);

        return vtranslate(implode(' ', array_slice($columnLabelInfo, 1)), $columnLabelInfo[0]);
    }

    /**
     * Function returns primary module of the report.
     * @return <String>
     */
    public function getPrimaryModule()
    {
        $chartModel = $this->getParent();
        $reportModel = $chartModel->getParent();
        $primaryModule = $reportModel->getPrimaryModule();

        return $primaryModule;
    }

    /**
     * Function returns list view url of the Primary module.
     * @return <String>
     */
    public function getBaseModuleListViewURL()
    {
        $primaryModule = $this->getPrimaryModule();
        $primaryModuleModel = Vtiger_Module_Model::getInstance($primaryModule);
        $listURL = $primaryModuleModel->getListViewUrlWithAllFilter();

        return $listURL;
    }

    public function generateData($nonHtml)
    {
        $db = PearDatabase::getInstance();
        $pivotSQL = $this->getQuery();
        $result = $db->pquery($pivotSQL, []);
        $rows = $db->num_rows($result);
        $dataGeneratePivot = [];
        $queryColumnsByFieldModel = $this->getQueryColumnsByFieldModel();
        $iHeaderFields = 0;
        if (is_array($queryColumnsByFieldModel)) {
            foreach ($queryColumnsByFieldModel as $field) {
                $sectors[] = trim(strtolower($field->get('reportlabel')), '`');
                $sectorColumnsField = $field;
                $fieldName = trim(strtolower($field->get('reportlabel')), '`');
                [$module, $fieldLabel] = explode('_', trim($field->get('reportlabel'), '`'), 2);
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
                if ($this->getReportRunObject()->secondarymodule != '') {
                    $translatedLabel = getTranslatedString($module, $module) . ' ' . $translatedLabel;
                }
                $dataGeneratePivot['header']['field'][$iHeaderFields]['fieldname'] = $fieldName;
                $dataGeneratePivot['header']['field'][$iHeaderFields]['fieldlabel'] = $translatedLabel;
                $dataGeneratePivot['header']['field'][$iHeaderFields]['translatedLabel'] = preg_replace($this->regexSpecialCharacter, '\\\\${1}', $translatedLabel);
                $dataGeneratePivot['header']['fieldLabel']['data'][] = preg_replace($this->regexSpecialCharacter, '\\\\${1}', $translatedLabel);
                ++$iHeaderFields;
            }
        }
        if ($this->isRecordCount()) {
            $sectors[] = strtolower('RECORD_COUNT');
            $fieldName = strtolower('RECORD_COUNT');
            $translatedLabel = 'RECORD COUNT';
            $dataGeneratePivot['header']['field'][$iHeaderFields]['fieldname'] = $fieldName;
            $dataGeneratePivot['header']['field'][$iHeaderFields]['fieldlabel'] = $translatedLabel;
            $dataGeneratePivot['header']['field'][$iHeaderFields]['translatedLabel'] = $translatedLabel;
            $dataGeneratePivot['header']['fieldLabel']['data'][] = $translatedLabel;
            ++$iHeaderFields;
        }
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $currencyRateAndSymbol = getCurrencySymbolandCRate($currentUserModel->currency_id);
        $groupByColumnsByFieldModel = $this->getGroupbyColumnsByFieldModel();
        if (is_array($groupByColumnsByFieldModel)) {
            foreach ($groupByColumnsByFieldModel as $groupField) {
                $groupByColumns[] = $groupField->get('reportlabel');
                $groupColumns[] = $groupByColumns;
                $groupColumnsField[] = $groupField;
                if (($groupField->getFieldDataType() == 'picklist' || $groupField->getFieldDataType() == 'multipicklist') && vtws_isRoleBasedPicklist($groupField->getName())) {
                    $currentUserModel = Users_Record_Model::getCurrentUserModel();
                    $valuePicklistField = getAssignedPicklistValues($groupField->getName(), $currentUserModel->getRole(), $db);
                    if ($groupField->getFieldDataType() == 'multipicklist') {
                        $dataGeneratePivot['multiplePicklistField']['field'][] = strtolower($groupField->get('reportlabel'));
                    }
                    $picklistvaluesmap['columns'][$groupField->getName()] = $valuePicklistField;
                }
                if ($groupField->getFieldDataType() == 'multireference') {
                    $dataGeneratePivot['multiplePicklistField']['field'][] = strtolower($groupField->get('reportlabel'));
                }
                if ($groupField->getFieldDataType() != 'multipicklist' && $groupField->getFieldDataType() != 'multireference') {
                    $dataGeneratePivot['nonMultiPicklist'][] = strtolower($groupField->get('reportlabel'));
                }
                [$module, $fieldLabel] = explode('_', trim($groupField->get('reportlabel'), '`'), 2);
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
                if ($this->getReportRunObject()->secondarymodule != '') {
                    $translatedLabel = getTranslatedString($module, $module) . ' ' . $translatedLabel;
                }
                $yfieldsArr[] = strtolower($groupField->get('reportlabel'));
                $fieldName = strtolower($groupField->get('reportlabel'));
                $fieldLabel = $groupField->get('reportlabel');
                $dataGeneratePivot['header']['field'][$iHeaderFields]['fieldname'] = $fieldName;
                $dataGeneratePivot['header']['field'][$iHeaderFields]['fieldlabel'] = $fieldLabel;
                $dataGeneratePivot['header']['field'][$iHeaderFields]['translatedLabel'] = preg_replace($this->regexSpecialCharacter, '\\\\${1}', $translatedLabel);
                $dataGeneratePivot['header']['fieldLabel']['columns'][] = preg_replace($this->regexSpecialCharacter, '\\\\${1}', $translatedLabel);
                ++$iHeaderFields;
            }
        }
        $groupByRowsByFieldModel = $this->getGroupbyRowsByFieldModel();
        if (is_array($groupByRowsByFieldModel)) {
            foreach ($groupByRowsByFieldModel as $groupField) {
                $groupByRows[] = $groupField->get('reportlabel');
                $groupRows[] = $groupByRows;
                $groupRowsField[] = $groupField;
                if (($groupField->getFieldDataType() == 'picklist' || $groupField->getFieldDataType() == 'multipicklist') && vtws_isRoleBasedPicklist($groupField->getName())) {
                    $currentUserModel = Users_Record_Model::getCurrentUserModel();
                    $valuePicklistField = getAssignedPicklistValues($groupField->getName(), $currentUserModel->getRole(), $db);
                    if ($groupField->getFieldDataType() == 'multipicklist') {
                        $dataGeneratePivot['multiplePicklistField']['field'][] = strtolower($groupField->get('reportlabel'));
                    }
                    $picklistvaluesmap['rows'][$groupField->getName()] = $valuePicklistField;
                }
                if ($groupField->getFieldDataType() == 'multireference') {
                    $dataGeneratePivot['multiplePicklistField']['field'][] = strtolower($groupField->get('reportlabel'));
                }
                if ($groupField->getFieldDataType() != 'multipicklist' && $groupField->getFieldDataType() != 'multireference') {
                    $dataGeneratePivot['nonMultiPicklist'][] = strtolower($groupField->get('reportlabel'));
                }
                [$module, $fieldLabel] = explode('_', trim($groupField->get('reportlabel'), '`'), 2);
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
                if ($this->getReportRunObject()->secondarymodule != '') {
                    $translatedLabel = getTranslatedString($module, $module) . ' ' . $translatedLabel;
                }
                $xfieldsArr[] = strtolower($groupField->get('reportlabel'));
                $fieldName = strtolower($groupField->get('reportlabel'));
                $fieldLabel = $groupField->get('reportlabel');
                $dataGeneratePivot['header']['field'][$iHeaderFields]['fieldname'] = $fieldName;
                $dataGeneratePivot['header']['field'][$iHeaderFields]['fieldlabel'] = $fieldLabel;
                $dataGeneratePivot['header']['field'][$iHeaderFields]['translatedLabel'] = preg_replace($this->regexSpecialCharacter, '\\\\${1}', $translatedLabel);
                $dataGeneratePivot['header']['fieldLabel']['rows'][] = preg_replace($this->regexSpecialCharacter, '\\\\${1}', $translatedLabel);
                ++$iHeaderFields;
            }
        }
        $xfields .= '["' . implode('","', $xfieldsArr) . '"]';
        $yfields .= '["' . implode('","', $yfieldsArr) . '"]';
        $zfields .= '["' . implode('","', $sectors) . '"]';
        $dataGeneratePivot['header']['xfields'] = $xfields;
        $dataGeneratePivot['header']['yfields'] = $yfields;
        $dataGeneratePivot['header']['zfields'] = $zfields;
        for ($i = 0; $i < $rows; ++$i) {
            $row = $db->query_result_rowdata($result, $i);
            $row[1] = decode_html($row[1]);
            foreach ($sectors as $key => $sector) {
                $fieldreportlabel = $sector;
                $fieldreportvalue = (float) $row[$sector];
                $identified = 'false';
                if ($sector != 'record_count' && $sectorColumnsField) {
                    if ($sectorColumnsField->get('uitype') == '71' || $sectorColumnsField->get('uitype') == '72') {
                        $fieldreportvalue = (float) $row[$sector];
                        if (!$nonHtml) {
                            $fieldreportvalue = CurrencyField::convertToUserFormat($fieldreportvalue);
                        } else {
                            $fieldreportvalue = CurrencyField::convertFromDollar($fieldreportvalue, $currencyRateAndSymbol['rate']);
                        }
                        $identified = 'true';
                    } else {
                        if ($sectorColumnsField->getFieldDataType() == 'double') {
                            $fieldreportvalue = (float) $row[$sector];
                        } else {
                            $fieldreportvalue = (float) $sectorColumnsField->getDisplayValue($row[$sector]);
                        }
                    }
                }
                $dataGeneratePivot['data'][$i][$fieldreportlabel] = $fieldreportvalue;
                if ($identified == 'true') {
                    $dataGeneratePivot['data'][$i]['currency_field'][] = $fieldreportlabel;
                }
            }
            if ($groupColumnsField) {
                foreach ($groupColumnsField as $key => $columsFieldModel) {
                    $fieldDataType = $columsFieldModel->getFieldDataType();
                    if ($fieldDataType == 'picklist') {
                        if (vtws_isRoleBasedPicklist($columsFieldModel->getName()) && !in_array($row[strtolower($columsFieldModel->get('reportlabel'))], $picklistvaluesmap['columns'][$columsFieldModel->getName()])) {
                            $labelColumn = 'na';
                        }
                        $labelColumn = vtranslate($row[strtolower($groupColumns[$key])], $columsFieldModel->getModuleName());
                    } else {
                        if ($fieldDataType == 'multipicklist') {
                            $multiPicklistValue = $row[strtolower($groupColumns[$key])];
                            $multiPicklistValues = explode(' |##| ', $multiPicklistValue);
                            foreach ($multiPicklistValues as $multiPicklistValue) {
                                $labelList[] = vtranslate($multiPicklistValue, $columsFieldModel->getModuleName());
                            }
                            $labelColumn = implode(' |##| ', $labelList);
                            unset($labelList);
                        } else {
                            if ($fieldDataType == 'multireference') {
                                $multiPicklistValue = $row[strtolower($groupColumns[$key])];
                                $multiPicklistValues = explode('|##|', $multiPicklistValue);
                                foreach ($multiPicklistValues as $multiPicklistValue) {
                                    if ($this->isDeletedRecord($multiPicklistValue)) {
                                        continue;
                                    }
                                    $recordModel = Vtiger_Record_Model::getInstanceById($multiPicklistValue);
                                    $labelList[] = $recordModel->getName();
                                }
                                $labelColumn = implode(' |##| ', $labelList);
                                unset($labelList);
                            } else {
                                if ($fieldDataType == 'date') {
                                    if ($row[strtolower($columsFieldModel->get('reportlabel'))]) {
                                        $tempVal = $this->getParent()->getGroupByFieldColumns();
                                        $groupByDataField = explode(':', $tempVal[$key]);
                                        if ($groupByDataField[5] == 'M' || $groupByDataField[5] == 'Y' || $groupByDataField[5] == 'MY' || $groupByDataField[5] == 'W') {
                                            if ($groupByDataField[5] == 'D') {
                                                $dateTimeByUser = DateTimeField::convertToUserTimeZone(date('Y-m-d H:i:s', strtotime($row[strtolower($columsFieldModel->get('reportlabel'))])))->format('Y-m-d H:i:s');
                                                $dateTimeByUserFormat = DateTimeField::convertToUserFormat($dateTimeByUser);
                                                [$labelDate, $labelTime] = explode(' ', $dateTimeByUserFormat);
                                                $labelColumn = $labelDate;
                                            } else {
                                                $labelColumn = $row[strtolower($columsFieldModel->get('reportlabel'))];
                                            }
                                        } else {
                                            $labelColumn = Vtiger_Date_UIType::getDisplayDateValue($row[strtolower($columsFieldModel->get('reportlabel'))]);
                                        }
                                    } else {
                                        $labelColumn = '--';
                                    }
                                } else {
                                    if ($fieldDataType == 'datetime') {
                                        if ($row[strtolower($columsFieldModel->get('reportlabel'))]) {
                                            $tempVal = $this->getParent()->getGroupByFieldColumns();
                                            $groupByDataField = explode(':', $tempVal[$key]);
                                            if ($groupByDataField[5] == 'M' || $groupByDataField[5] == 'Y' || $groupByDataField[5] == 'MY' || $groupByDataField[5] == 'W' || $groupByDataField[5] == 'D') {
                                                if ($groupByDataField[5] == 'D') {
                                                    $dateTimeByUser = DateTimeField::convertToUserTimeZone(date('Y-m-d H:i:s', strtotime($row[strtolower($columsFieldModel->get('reportlabel'))])))->format('Y-m-d H:i:s');
                                                    $dateTimeByUserFormat = DateTimeField::convertToUserFormat($dateTimeByUser);
                                                    [$labelDate, $labelTime] = explode(' ', $dateTimeByUserFormat);
                                                    $labelColumn = $labelDate;
                                                } else {
                                                    $labelColumn = $row[strtolower($columsFieldModel->get('reportlabel'))];
                                                }
                                            } else {
                                                $dateTimeByUser = DateTimeField::convertToUserTimeZone(date('Y-m-d H:i:s', strtotime($row[strtolower($columsFieldModel->get('reportlabel'))])))->format('Y-m-d H:i:s');
                                                $dateTimeByUserFormat = DateTimeField::convertToUserFormat($dateTimeByUser);
                                                [$labelDate, $labelTime] = explode(' ', $dateTimeByUserFormat);
                                                $currentUser = Users_Record_Model::getCurrentUserModel();
                                                if ($currentUser->get('hour_format') == '12') {
                                                    $labelTime = Vtiger_Time_UIType::getTimeValueInAMorPM($labelTime);
                                                }
                                                $labelTime = $labelDate . ' ' . $labelTime;
                                                $labelColumn = $labelTime;
                                            }
                                        } else {
                                            $labelColumn = '--';
                                        }
                                    } else {
                                        $labelColumn = decode_html(str_replace('\\', '\\\\', $row[strtolower($groupColumns[$key])]));
                                    }
                                }
                            }
                        }
                    }
                    $fieldreportvalue = mb_strlen($labelColumn, 'UTF-8') > 30 && $fieldDataType != 'multipicklist' && $fieldDataType != 'multireference' ? mb_substr($labelColumn, 0, 30, 'UTF-8') . '..' : $labelColumn;
                    $fieldreportlabel = strtolower($columsFieldModel->get('reportlabel'));
                    $dataGeneratePivot['data'][$i][$fieldreportlabel] = preg_replace($this->regexSpecialCharacter, '\\\\${1}', $fieldreportvalue);
                }
            }
            if ($groupRowsField) {
                foreach ($groupRowsField as $key => $rowsFieldModel) {
                    $fieldDataType = $rowsFieldModel->getFieldDataType();
                    if ($fieldDataType == 'picklist') {
                        if (vtws_isRoleBasedPicklist($rowsFieldModel->getName()) && !in_array($row[strtolower($rowsFieldModel->get('reportlabel'))], $picklistvaluesmap['rows'][$rowsFieldModel->getName()])) {
                            $labelRow = 'na';
                        }
                        $labelRow = vtranslate($row[strtolower($groupRows[$key])], $rowsFieldModel->getModuleName());
                    } else {
                        if ($fieldDataType == 'multipicklist') {
                            $multiPicklistValue = $row[strtolower($groupRows[$key])];
                            $multiPicklistValues = explode(' |##| ', $multiPicklistValue);
                            foreach ($multiPicklistValues as $multiPicklistValue) {
                                $labelList[] = vtranslate($multiPicklistValue, $rowsFieldModel->getModuleName());
                            }
                            $labelRow = implode(' |##| ', $labelList);
                            unset($labelList);
                        } else {
                            if ($fieldDataType == 'multireference') {
                                $multiPicklistValue = $row[strtolower($groupRows[$key])];
                                $multiPicklistValues = explode('|##|', $multiPicklistValue);
                                foreach ($multiPicklistValues as $multiPicklistValue) {
                                    if ($this->isDeletedRecord($multiPicklistValue)) {
                                        continue;
                                    }
                                    $recordModel = Vtiger_Record_Model::getInstanceById($multiPicklistValue);
                                    $labelList[] = $recordModel->getName();
                                }
                                $labelRow = implode(' |##| ', $labelList);
                                unset($labelList);
                            } else {
                                if ($fieldDataType == 'date') {
                                    if ($row[strtolower($rowsFieldModel->get('reportlabel'))]) {
                                        $tempVal = $this->getParent()->getGroupByFieldRows();
                                        $groupByDataField = explode(':', $tempVal[$key]);
                                        if ($groupByDataField[5] == 'M' || $groupByDataField[5] == 'Y' || $groupByDataField[5] == 'MY' || $groupByDataField[5] == 'W' || $groupByDataField[5] == 'D') {
                                            if ($groupByDataField[5] == 'D') {
                                                $dateTimeByUser = DateTimeField::convertToUserTimeZone(date('Y-m-d H:i:s', strtotime($row[strtolower($rowsFieldModel->get('reportlabel'))])))->format('Y-m-d H:i:s');
                                                $dateTimeByUserFormat = DateTimeField::convertToUserFormat($dateTimeByUser);
                                                [$labelDate, $labelTime] = explode(' ', $dateTimeByUserFormat);
                                                $labelRow = $labelDate;
                                            } else {
                                                $labelRow = $row[strtolower($rowsFieldModel->get('reportlabel'))];
                                            }
                                        } else {
                                            $labelRow = Vtiger_Date_UIType::getDisplayDateValue($row[strtolower($rowsFieldModel->get('reportlabel'))]);
                                        }
                                    } else {
                                        $labelRow = '--';
                                    }
                                } else {
                                    if ($fieldDataType == 'datetime') {
                                        if ($row[strtolower($rowsFieldModel->get('reportlabel'))]) {
                                            $tempVal = $this->getParent()->getGroupByFieldRows();
                                            $groupByDataField = explode(':', $tempVal[$key]);
                                            if ($groupByDataField[5] == 'M' || $groupByDataField[5] == 'Y' || $groupByDataField[5] == 'MY' || $groupByDataField[5] == 'W' || $groupByDataField[5] == 'D') {
                                                if ($groupByDataField[5] == 'D') {
                                                    $dateTimeByUser = DateTimeField::convertToUserTimeZone(date('Y-m-d H:i:s', strtotime($row[strtolower($rowsFieldModel->get('reportlabel'))])))->format('Y-m-d H:i:s');
                                                    $dateTimeByUserFormat = DateTimeField::convertToUserFormat($dateTimeByUser);
                                                    [$labelDate, $labelTime] = explode(' ', $dateTimeByUserFormat);
                                                    $labelRow = $labelDate;
                                                } else {
                                                    $labelRow = $row[strtolower($rowsFieldModel->get('reportlabel'))];
                                                }
                                            } else {
                                                $dateTimeByUser = DateTimeField::convertToUserTimeZone(date('Y-m-d H:i:s', strtotime($row[strtolower($rowsFieldModel->get('reportlabel'))])))->format('Y-m-d H:i:s');
                                                $dateTimeByUserFormat = DateTimeField::convertToUserFormat($dateTimeByUser);
                                                [$labelDate, $labelTime] = explode(' ', $dateTimeByUserFormat);
                                                $currentUser = Users_Record_Model::getCurrentUserModel();
                                                if ($currentUser->get('hour_format') == '12') {
                                                    $labelTime = Vtiger_Time_UIType::getTimeValueInAMorPM($labelTime);
                                                }
                                                $labelTime = $labelDate . ' ' . $labelTime;
                                                $labelRow = $labelTime;
                                            }
                                        } else {
                                            $labelRow = '--';
                                        }
                                    } else {
                                        $labelRow = decode_html(str_replace('\\', '\\\\', $row[strtolower($groupRows[$key])]));
                                    }
                                }
                            }
                        }
                    }
                    $fieldreportvalue = mb_strlen($labelRow, 'UTF-8') > 30 && $fieldDataType != 'multipicklist' && $fieldDataType != 'multireference' ? mb_substr($labelRow, 0, 30, 'UTF-8') . '..' : $labelRow;
                    $fieldreportlabel = strtolower($rowsFieldModel->get('reportlabel'));
                    $dataGeneratePivot['data'][$i][$fieldreportlabel] = preg_replace($this->regexSpecialCharacter, '\\\\${1}', $fieldreportvalue);
                }
            }
        }
        $dataGeneratePivot['dataField'] = $sectors;
        if ($nonHtml) {
            return $dataGeneratePivot;
        }
        $html = $this->generatePivot($dataGeneratePivot);

        return $html;
    }

    public function getQuery()
    {
        $pivotModel = $this->getParent();
        $reportModel = $pivotModel->getParent();
        $advFilterSql = $reportModel->getAdvancedFilterSQL();
        $arrTables = ['vtiger_users', 'vtiger_groups', 'vtiger_lastModifiedBy', 'vtiger_createdby'];
        $groupByRowsByFieldModel = $this->getGroupbyRowsByFieldModel();
        if (is_array($groupByRowsByFieldModel)) {
            foreach ($groupByRowsByFieldModel as $groupField) {
                $fieldModule = $groupField->getModule();
                $moduleName = $groupField->getModuleName();
                $this->reportRun->queryPlanner->addTable($fieldModule->basetable);
                $this->reportRun->queryPlanner->addTable($groupField->get('table'));
                $groupByColumns[] = '`' . $groupField->get('reportlabel') . '`';
                $reportcolumn = $groupField->get('reportcolumn');
                foreach ($arrTables as $table) {
                    if ($this->reportRun->queryPlanner->requireTable($table . $moduleName)) {
                        $reportcolumn = str_replace($table . $moduleName, $table, $reportcolumn);
                    }
                }
                $columns[] = $reportcolumn;
                if ($pivotModel->sort) {
                    foreach ($pivotModel->sort as $index => $item) {
                        if ($item == $groupField->get('reportcolumninfo')) {
                            $pivotModel->sort[$index] = $groupField->get('reportlabel');
                        }
                    }
                }
            }
        }
        $groupByColumnsByFieldModel = $this->getGroupbyColumnsByFieldModel();
        if (is_array($groupByColumnsByFieldModel)) {
            foreach ($groupByColumnsByFieldModel as $groupField) {
                $fieldModule = $groupField->getModule();
                $this->reportRun->queryPlanner->addTable($fieldModule->basetable);
                $this->reportRun->queryPlanner->addTable($groupField->get('table'));
                $fieldDataType = $groupField->getFieldDataType();
                if ($fieldDataType == 'date') {
                    array_unshift($groupByColumns, '`' . $groupField->get('reportlabel') . '`');
                } else {
                    $groupByColumns[] = '`' . $groupField->get('reportlabel') . '`';
                }
                $reportcolumn = $groupField->get('reportcolumn');
                foreach ($arrTables as $table) {
                    if ($this->reportRun->queryPlanner->requireTable($table . $moduleName)) {
                        $reportcolumn = str_replace($table . $moduleName, $table, $reportcolumn);
                    }
                }
                $columns[] = $reportcolumn;
            }
        }
        $queryColumnsByFieldModel = $this->getQueryColumnsByFieldModel();
        if (is_array($queryColumnsByFieldModel)) {
            foreach ($queryColumnsByFieldModel as $field) {
                $this->reportRun->queryPlanner->addTable($field->get('table'));
                $columns[] = $field->get('reportcolumn');
            }
        }
        $sqlTemp = explode(' from ', $this->reportRun->sGetSQLforReport($reportModel->getId(), $advFilterSql, 'PDF'), 2);
        $sqlCondition1 = preg_split('/where/i', $sqlTemp[1]);
        $sql = $this->reportRun->getVReportsQuery($moduleName, '');
        $splitSql = preg_split('/where/i', $sql);
        $columnLabels = [];
        $pivotSQL = 'SELECT ';
        if ($columns && is_array($columns)) {
            $columnLabels = array_merge($columnLabels, (array) $groupByColumns);
            $pivotSQL .= implode(',', $columns);
        }
        if ($this->isRecordCount()) {
            $pivotSQL .= ' ,count(*) AS RECORD_COUNT';
        }
        $pivotSQL .= ' ' . $splitSql[0] . ' WHERE ' . $sqlCondition1[1];
        if ($groupByColumns && is_array($groupByColumns)) {
            $pivotSQL .= ' GROUP BY ' . implode(',', $groupByColumns);
        }
        if ($pivotModel->sort) {
            $pivotSQL .= ' ORDER BY ' . implode(',', $pivotModel->sort);
            if ($pivotModel->order) {
                $pivotSQL .= ' ' . $pivotModel->order;
            }
        } else {
            if ($this->getParent()->parent->reportRun->orderByMonthWeekDate) {
                $orderByDate = array_unique($this->getParent()->parent->reportRun->orderByMonthWeekDate);
                $pivotSQL .= ' ORDER BY ' . implode(',', $orderByDate);
            }
        }
        if ($pivotModel->limit > 0) {
            $pivotSQL .= ' LIMIT ' . $pivotModel->limit;
        }

        return $pivotSQL;
    }

    /**
     * Function generate links.
     * @param <String> $field - fieldname
     * @param <Decimal> $value - value
     * @return <String>
     */
    public function generateLink($field, $value)
    {
        $reportRunObject = $this->getReportRunObject();
        $chartModel = $this->getParent();
        $reportModel = $chartModel->getParent();
        $filter = $reportRunObject->getAdvFilterList($reportModel->getId(), true);
        $comparator = 'e';
        $dataFieldInfo = @explode(':', $field);
        if (($dataFieldInfo[4] == 'D' || $dataFieldInfo[4] == 'DT') && !empty($dataFieldInfo[5])) {
            $dataValue = explode(' ', $value);
            if (count($dataValue) > 1) {
                $comparator = 'bw';
                if ($dataFieldInfo[4] == 'D') {
                    $value = date('Y-m-d', strtotime($value)) . ',' . date('Y-m-d', strtotime('last day of' . $value));
                } else {
                    $value = date('Y-m-d H:i:s', strtotime($value)) . ',' . date('Y-m-d', strtotime('last day of' . $value)) . ' 23:59:59';
                }
            } else {
                $comparator = 'bw';
                if ($dataFieldInfo[4] == 'D') {
                    $value = date('Y-m-d', strtotime('first day of JANUARY ' . $value)) . ',' . date('Y-m-d', strtotime('last day of DECEMBER ' . $value));
                } else {
                    $value = date('Y-m-d H:i:s', strtotime('first day of JANUARY ' . $value)) . ',' . date('Y-m-d', strtotime('last day of DECEMBER ' . $value)) . ' 23:59:59';
                }
            }
        } else {
            if ($dataFieldInfo[4] == 'DT') {
                $value = Vtiger_Date_UIType::getDisplayDateTimeValue($value);
            }
        }
        if (empty($value)) {
            $comparator = 'empty';
        }
        $advancedFilterConditions = $reportModel->transformToNewAdvancedFilter();
        if (count($advancedFilterConditions[1]['columns']) < 1) {
            $groupCondition = [];
            $groupCondition['columns'][] = ['columnname' => $field, 'comparator' => $comparator, 'value' => $value, 'column_condition' => ''];
            array_unshift($filter, $groupCondition);
        } else {
            $filter[1]['columns'][] = ['columnname' => $field, 'comparator' => $comparator, 'value' => $value, 'column_condition' => ''];
        }
        foreach ($filter as $index => $filterInfo) {
            foreach ($filterInfo['columns'] as $i => $column) {
                if ($column) {
                    $fieldInfo = @explode(':', $column['columnname']);
                    $filter[$index]['columns'][$i]['columnname'] = $fieldInfo[3];
                }
            }
        }
        $listSearchParams = [];
        $i = 0;
        if ($filter) {
            foreach ($filter as $index => $filterInfo) {
                foreach ($filterInfo['columns'] as $j => $column) {
                    if ($column) {
                        $listSearchParams[$i][] = [$column['columnname'], $column['comparator'], urlencode(escapeSlashes($column['value']))];
                    }
                }
                ++$i;
            }
        }
        $baseModuleListLink = $this->getBaseModuleListViewURL();

        return $baseModuleListLink . '&search_params=' . json_encode($listSearchParams) . '&nolistcache=1';
    }

    /**
     * Function generates graph label.
     * @return <String>
     */
    public function getGraphLabel()
    {
        return $this->getReportModel()->getName();
    }

    public function getDataTypes()
    {
        $chartModel = $this->getParent();
        $selectedDataFields = $chartModel->get('datafields');
        $dataTypes = [];
        foreach ($selectedDataFields as $dataField) {
            [$tableName, $columnName, $moduleField, $fieldName, $single] = explode(':', $dataField);
            [$relModuleName, $fieldLabel] = explode('_', $moduleField);
            $relModuleModel = Vtiger_Module_Model::getInstance($relModuleName);
            $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $relModuleModel);
            if ($fieldModel) {
                $dataTypes[] = $fieldModel->getFieldDataType();
            } else {
                $dataTypes[] = '';
            }
        }

        return $dataTypes;
    }

    public function getRenameField($recordId)
    {
        global $adb;
        $rename_field_result = $adb->pquery('SELECT rename_field  FROM vtiger_vreporttype WHERE reportid = ?', [$recordId]);
        $row = $adb->fetchByAssoc($rename_field_result, 0);
        $rename_fields = json_decode(html_entity_decode($row['rename_field']));

        return $rename_fields;
    }

    public function generatePivot($data)
    {
        $recordId = $this->getParent()->get('reportid');
        $xfields = '';
        $yfields = '';
        $zfields = '';
        $currentDate = str_replace('-', '', date('Y-m-d'));
        $htmlExportPivotTop = '<script type="text/javascript">';
        $htmlExportPivotTop .= '(function() {';
        $htmlExportPivotTop .= 'var model = function(voter, party, precinct, ageGroup, lastVoted, yearsReg, ballotStatus) {';
        $htmlExportPivotTop .= 'this.voter = voter;';
        $htmlExportPivotTop .= 'this.party = party;';
        $htmlExportPivotTop .= 'this.precinct = precinct;';
        $htmlExportPivotTop .= 'this.ageGroup = ageGroup;';
        $htmlExportPivotTop .= 'this.lastVoted = lastVoted;';
        $htmlExportPivotTop .= 'this.yearsReg = yearsReg;';
        $htmlExportPivotTop .= 'this.ballotStatus = ballotStatus;';
        $htmlExportPivotTop .= '};';
        $htmlExportPivotTop .= 'window.pivot = {};';
        $htmlExportPivotTop .= 'window.pivot.data = [];';
        $htmlExportPivotTop .= 'var data = getData();';
        $htmlExportPivotTop .= "for(var t=0;t<1;t++) {\n            for(var j = 0;j < data.length; j++) {\n                window.pivot.data.push(data[j]);\n            }\n        }";
        $htmlExportPivotTop .= "function getData() {\n                return [";
        $htmlExportPivotBottom = '<script type="text/javascript">';
        $htmlExportPivotBottom .= "var config = {\n                                    dataSource: window.pivot.data,\n                                    dataHeadersLocation: 'columns',\n                                    theme: 'blue',\n                                    grandTotal: {\n                                        rowsvisible: true,\n                                        columnsvisible: true\n                                    },\n                                    subTotal: {\n                                        visible: false,\n                                        collapsed: true,\n                                        collapsible: true\n                                    },";
        $htmlExportPivotBottom .= 'fields: [';
        $html = '<script type="text/javascript">';
        $html .= '$(document).ready(function() {';
        if ($this->getParent()->get('widgetId')) {
            $widgetId = $this->getParent()->get('widgetId');
            $html .= '$(".pivot-widget-' . $widgetId . '").jbPivot({';
        } else {
            $html .= '$("#reportDetails").jbPivot({';
        }
        $html .= 'fields: {';
        $rename_fields = $this->getRenameField($recordId);
        $renameDataArr = [];
        foreach ($data['header']['field'] as $keyField => $field) {
            $fieldName = $field['fieldname'];
            if ($rename_fields) {
                foreach ($rename_fields as $key_rename_field => $rename_field) {
                    if ($fieldName == $rename_field->fieldname) {
                        if (trim($rename_field->translatedLabel) != '') {
                            $fieldLabel = $rename_field->translatedLabel;
                        } else {
                            $fieldLabel = $field['fieldlabel'];
                        }
                        $renameDataArr[] = $fieldLabel;
                        $fieldLabelExport = $fieldLabel;
                        break;
                    }
                    $fieldLabel = $field['fieldlabel'];
                    $fieldLabelExport = $field['translatedLabel'];
                }
            } else {
                $fieldLabel = $field['fieldlabel'];
                $fieldLabelExport = $field['translatedLabel'];
            }
            $html .= "'" . $fieldName . "': {field: '" . $fieldName . "', showAll: true, agregateType: 'sum',label:'" . $fieldLabel . "'},";
            $htmlExportPivotBottom .= "{name:'" . $keyField . "',caption:'" . $fieldLabelExport . "',},";
        }
        $html .= '},';
        $xfields .= $data['header']['xfields'];
        $yfields .= $data['header']['yfields'];
        $zfields .= $data['header']['zfields'];
        $html .= 'xfields: ' . $xfields . ',';
        $html .= 'yfields: ' . $yfields . ',';
        $html .= 'zfields: ' . $zfields . ',';
        $html .= 'data: [';
        if ($data['multiplePicklistField'] && count($data['multiplePicklistField']) > 0) {
            $dataMappingMultiplePicklist = $this->generateDataMultiplePicklist($data);
            foreach ($dataMappingMultiplePicklist as $keyDataMappingMultiplePicklist => $dataRecords) {
                $htmlExportPivotTop .= '[';
                $html .= '{';
                foreach ($dataRecords as $field => $value) {
                    $html .= "'" . $field . "' : '" . $value . "',";
                    $htmlExportPivotTop .= "'" . $value . "',";
                }
                $html .= '},';
                $htmlExportPivotTop .= '],';
            }
        } else {
            foreach ($data['data'] as $key => $fieldData) {
                $htmlExportPivotTop .= '[';
                $html .= '{';
                foreach ($fieldData as $field => $value) {
                    if ($field == 'currency_field') {
                        continue;
                    }
                    $html .= "'" . $field . "' : '" . $value . "',";
                    $htmlExportPivotTop .= "'" . $value . "',";
                }
                if ($fieldData['currency_field']) {
                    $html .= "'currency_field' : " . json_encode($fieldData['currency_field']);
                }
                $html .= '},';
                $htmlExportPivotTop .= '],';
            }
        }
        $html .= "],summary: true,l_all: 'All',";
        $html .= '})';
        $html .= '})';
        $html .= '</script>';
        $htmlExportPivotBottom .= '],';
        $rows = "rows: ['" . implode("','", $data['header']['fieldLabel']['rows']) . "'],";
        $columns = "columns: ['" . implode("','", $data['header']['fieldLabel']['columns']) . "'],";
        $dataExportPivot = "data: ['" . implode("','", $renameDataArr) . "'],";
        $htmlExportPivotBottom .= $rows . $columns . $dataExportPivot;
        $htmlExportPivotBottom .= '};';
        $htmlExportPivotBottom .= 'var pgridwidget = new orb.pgridwidget(config);</script>';
        $htmlExportPivotTop .= ']}';
        $htmlExportPivotTop .= '}());</script>';
        $htmlExportPivot = $htmlExportPivotTop . $htmlExportPivotBody . $htmlExportPivotBottom;
        $htmlPivot['pivot'] = $html;
        $htmlPivot['export'] = $htmlExportPivot;

        return $htmlPivot;
    }

    public function generateDataMultiplePicklist($data)
    {
        $ArrayDataMapping = [];
        foreach ($data['data'] as $dataKey => $dataDetail) {
            foreach ($data['multiplePicklistField'] as $picklistFields) {
                if (count($picklistFields) > 1) {
                    for ($ii = count($picklistFields) - 1; $ii >= 0; --$ii) {
                        $picklistFieldName = $picklistFields[$ii];
                        $parentPicklistFieldName = $picklistFields[$ii - 1];
                        $picklistFieldValueName = (string) $picklistFieldName . $dataKey . '_array';
                        $parentPicklistFieldValueName = (string) $parentPicklistFieldName . $dataKey . '_array';
                        ${$parentPicklistFieldValueName} = [];
                        if ($ii == count($picklistFields) - 1) {
                            ${$picklistFieldValueName} = [];
                            $picklistValue = $dataDetail[$picklistFieldName];
                            if ($picklistValue != '') {
                                $picklistValue = explode(' |##| ', $picklistValue);
                                $lastChildData = [];
                                foreach ($picklistValue as $plValue) {
                                    if (!in_array($plValue, array_keys(${$picklistFieldValueName}))) {
                                        foreach ($data['dataField'] as $dataFieldName) {
                                            $lastChildData[$plValue][$dataFieldName] = $dataDetail[$dataFieldName];
                                        }
                                        foreach ($data['nonMultiPicklist'] as $dataFieldName) {
                                            $lastChildData[$plValue][$dataFieldName] = $dataDetail[$dataFieldName];
                                        }
                                    }
                                }
                                ${$picklistFieldValueName} = $lastChildData;
                            } else {
                                if (!in_array('', array_keys(${$picklistFieldValueName}))) {
                                    foreach ($data['dataField'] as $dataFieldName) {
                                        $lastChildData[''][$dataFieldName] = $dataDetail[$dataFieldName];
                                    }
                                    foreach ($data['nonMultiPicklist'] as $dataFieldName) {
                                        $lastChildData[''][$dataFieldName] = $dataDetail[$dataFieldName];
                                    }
                                }
                                ${$picklistFieldValueName} = $lastChildData;
                            }
                        }
                        $parentPicklistValue = $dataDetail[$parentPicklistFieldName];
                        if ($parentPicklistValue != '') {
                            $parentTemp_array = [];
                            $picklistValue = explode(' |##| ', $parentPicklistValue);
                            foreach ($picklistValue as $plValue) {
                                $parentTemp_array[$plValue] = ${$picklistFieldValueName};
                            }
                            ${$parentPicklistFieldValueName} = $parentTemp_array;
                        } else {
                            $parentTemp_array = ['' => ${$picklistFieldValueName}];
                            ${$parentPicklistFieldValueName} = $parentTemp_array;
                        }
                        if ($ii == 1) {
                            array_push($ArrayDataMapping, ${$parentPicklistFieldValueName});
                        }
                    }
                } else {
                    $picklistFieldName = $picklistFields[0];
                    $picklistFieldValueName = (string) $picklistFieldName . $dataKey . '_array';
                    ${$picklistFieldValueName} = [];
                    $picklistValue = $dataDetail[$picklistFieldName];
                    if ($picklistValue != '') {
                        $picklistValue = explode(' |##| ', $picklistValue);
                        $lastChildData = [];
                        foreach ($picklistValue as $plValue) {
                            if (!in_array($plValue, array_keys(${$picklistFieldValueName}))) {
                                foreach ($data['dataField'] as $dataFieldName) {
                                    $lastChildData[$plValue][$dataFieldName] = $dataDetail[$dataFieldName];
                                }
                                foreach ($data['nonMultiPicklist'] as $dataFieldName) {
                                    $lastChildData[$plValue][$dataFieldName] = $dataDetail[$dataFieldName];
                                }
                            }
                        }
                        ${$picklistFieldValueName} = $lastChildData;
                    } else {
                        if (!in_array('', array_keys(${$picklistFieldValueName}))) {
                            foreach ($data['dataField'] as $dataFieldName) {
                                $lastChildData[''][$dataFieldName] = $dataDetail[$dataFieldName];
                            }
                            foreach ($data['nonMultiPicklist'] as $dataFieldName) {
                                $lastChildData[''][$dataFieldName] = $dataDetail[$dataFieldName];
                            }
                        }
                        ${$picklistFieldValueName} = $lastChildData;
                    }
                    array_push($ArrayDataMapping, ${$picklistFieldValueName});
                }
            }
        }
        $dataGenerate = [];
        $multiplePicklistFields = $data['multiplePicklistField']['field'];
        $i = 0;
        foreach ($ArrayDataMapping as $keyArrayDataMapping => $dataField1) {
            foreach ($dataField1 as $valueField1 => $dataField2) {
                if (is_array($dataField2)) {
                    foreach ($dataField2 as $valueField2 => $dataField3) {
                        if (is_array($dataField3)) {
                            foreach ($dataField3 as $valueField3 => $dataField4) {
                                if (is_array($dataField4)) {
                                    foreach ($dataField4 as $valueField4 => $dataField5) {
                                        if (is_array($dataField5)) {
                                            foreach ($dataField5 as $valueField5 => $dataField6) {
                                                if (is_array($dataField6)) {
                                                    foreach ($dataField6 as $valueField6 => $dataField7) {
                                                        if (!is_array($dataField7)) {
                                                            $dataGenerate[$i] = $dataField6;
                                                            $dataGenerate[$i][$multiplePicklistFields[0]] = $valueField1;
                                                            $dataGenerate[$i][$multiplePicklistFields[1]] = $valueField2;
                                                            $dataGenerate[$i][$multiplePicklistFields[2]] = $valueField3;
                                                            $dataGenerate[$i][$multiplePicklistFields[3]] = $valueField4;
                                                            $dataGenerate[$i][$multiplePicklistFields[4]] = $valueField5;
                                                            ++$i;
                                                            break;
                                                        }
                                                    }
                                                } else {
                                                    $dataGenerate[$i] = $dataField5;
                                                    $dataGenerate[$i][$multiplePicklistFields[0]] = $valueField1;
                                                    $dataGenerate[$i][$multiplePicklistFields[1]] = $valueField2;
                                                    $dataGenerate[$i][$multiplePicklistFields[2]] = $valueField3;
                                                    $dataGenerate[$i][$multiplePicklistFields[3]] = $valueField4;
                                                    ++$i;
                                                    break;
                                                }
                                            }
                                        } else {
                                            $dataGenerate[$i] = $dataField4;
                                            $dataGenerate[$i][$multiplePicklistFields[0]] = $valueField1;
                                            $dataGenerate[$i][$multiplePicklistFields[1]] = $valueField2;
                                            $dataGenerate[$i][$multiplePicklistFields[2]] = $valueField3;
                                            ++$i;
                                            break;
                                        }
                                    }
                                } else {
                                    $dataGenerate[$i] = $dataField3;
                                    $dataGenerate[$i][$multiplePicklistFields[0]] = $valueField1;
                                    $dataGenerate[$i][$multiplePicklistFields[1]] = $valueField2;
                                    ++$i;
                                    break;
                                }
                            }
                        } else {
                            $dataGenerate[$i] = $dataField2;
                            $dataGenerate[$i][$multiplePicklistFields[0]] = $valueField1;
                            ++$i;
                            break;
                        }
                    }
                }
            }
        }

        return $dataGenerate;
    }

    public function isDeletedRecord($crmId)
    {
        global $adb;
        if (trim($crmId) != '') {
            $rs = $adb->pquery('SELECT * FROM `vtiger_crmentity` WHERE crmid =? AND deleted=1', [$crmId]);
            if ($adb->num_rows($rs) > 0) {
                return true;
            }

            return false;
        }

        return true;
    }
}
