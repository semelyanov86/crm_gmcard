<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Vtiger_ExportData_Action extends Vtiger_Mass_Action
{
    public $moduleCall = false;

    public function requiresPermission(Vtiger_Request $request)
    {
        $permissions = parent::requiresPermission($request);
        $permissions[] = ['module_parameter' => 'module', 'action' => 'Export'];
        if (!empty($request->get('source_module'))) {
            $permissions[] = ['module_parameter' => 'source_module', 'action' => 'Export'];
        }

        return $permissions;
    }

    /**
     * Function is called by the controller.
     */
    public function process(Vtiger_Request $request)
    {
        $this->ExportData($request);
    }

    private $moduleInstance;

    private $focus;

    /**
     * Function exports the data based on the mode.
     */
    public function ExportData(Vtiger_Request $request)
    {
        $db = PearDatabase::getInstance();
        $moduleName = $request->get('source_module');

        $this->moduleInstance = Vtiger_Module_Model::getInstance($moduleName);
        $this->moduleFieldInstances = $this->moduleFieldInstances($moduleName);
        $this->focus = CRMEntity::getInstance($moduleName);

        $query = $this->getExportQuery($request);
        $result = $db->pquery($query, []);

        $redirectedModules = ['Users', 'Calendar'];
        if ($request->getModule() != $moduleName && in_array($moduleName, $redirectedModules) && !$this->moduleCall) {
            $handlerClass = Vtiger_Loader::getComponentClassName('Action', 'ExportData', $moduleName);
            $handler = new $handlerClass();
            $handler->ExportData($request);

            return;
        }
        $translatedHeaders = $this->getHeaders();
        $entries = [];
        for ($j = 0; $j < $db->num_rows($result); ++$j) {
            $entries[] = $this->sanitizeValues($db->fetchByAssoc($result, $j));
        }

        $this->output($request, $translatedHeaders, $entries);
    }

    public function getHeaders()
    {
        $headers = [];
        // Query generator set this when generating the query
        if (!empty($this->accessibleFields)) {
            $accessiblePresenceValue = [0, 2];
            foreach ($this->accessibleFields as $fieldName) {
                $fieldModel = $this->moduleFieldInstances[$fieldName];
                // Check added as querygenerator is not checking this for admin users
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

        $queryGenerator = new EnhancedQueryGenerator($moduleName, $currentUser);
        $queryGenerator->initForCustomViewById($cvId);
        $fieldInstances = $this->moduleFieldInstances;

        $orderBy = $request->get('orderby');
        $orderByFieldModel = $fieldInstances[$orderBy] ?? '';
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
            if ($searchParams && php7_count($queryGenerator->getWhereFields())) {
                $glue = QueryGenerator::$AND;
            }
            $searchParams = array_merge($searchParams, $tagParams);
            $searchParams = Vtiger_Util_Helper::transferListSearchParamsToFilterCondition($searchParams, $this->moduleInstance);
            $queryGenerator->parseAdvFilterList($searchParams, $glue);

            if ($searchKey) {
                $queryGenerator->addUserSearchConditions(['search_field' => $searchKey, 'search_text' => $searchValue, 'operator' => $operator]);
            }

            if ($orderBy && $orderByFieldModel) {
                if ($orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::REFERENCE_TYPE || $orderByFieldModel->getFieldDataType() == Vtiger_Field_Model::OWNER_TYPE) {
                    $queryGenerator->addWhereField($orderBy);
                }
            }
        }

        /**
         *  For Documents if we select any document folder and mass deleted it should delete documents related to that
         *  particular folder only.
         */
        if ($moduleName == 'Documents') {
            $folderValue = $request->get('folder_value');
            if (!empty($folderValue)) {
                $queryGenerator->addCondition($request->get('folder_id'), $folderValue, 'e');
            }
        }

        $accessiblePresenceValue = [0, 2];
        foreach ($fieldInstances as $field) {
            // Check added as querygenerator is not checking this for admin users
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

        $this->accessibleFields = $queryGenerator->getFields();

        switch ($mode) {
            case 'ExportAllData':	if ($orderBy && $orderByFieldModel) {
                $query .= ' ORDER BY ' . $queryGenerator->getOrderByColumn($orderBy) . ' ' . $sortOrder;
            }
                break;

            case 'ExportCurrentPage':	$pagingModel = new Vtiger_Paging_Model();
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

            case 'ExportSelectedRecords':	$idList = $this->getRecordsListFromRequest($request);
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


            default:	break;
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

    /**
     * Function that create the exported file.
     * @param Vtiger_Request $request
     * @param <Array> $headers - output file header
     * @param <Array> $entries - outfput file data
     */
    public function output($request, $headers, $entries)
    {
        $moduleName = $request->get('source_module');
        $fileName = str_replace(' ', '_', decode_html(vtranslate($moduleName, $moduleName)));
        // for content disposition header comma should not be there in filename
        $fileName = str_replace(',', '_', $fileName);
        $exportType = $this->getExportContentType($request);

        header("Content-Disposition:attachment;filename={$fileName}.csv");
        header("Content-Type:{$exportType};charset=UTF-8");
        header('Expires: Mon, 31 Dec 2000 00:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: post-check=0, pre-check=0', false);

        ob_clean();
        $fp = fopen('php://output', 'a+');
        fputcsv($fp, $headers);

        foreach ($entries as $row) {
            fputcsv($fp, $row);
        }
    }

    private $picklistValues;

    private $fieldArray;

    private $fieldDataTypeCache = [];

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
                // In database we have same column name in two tables. - inventory modules only
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
            } else {
                unset($arr[$fieldName]);

                continue;
            }
            // Track if the value had quotes at beginning
            if (is_string($value)) {
                $beginsWithDoubleQuote = strpos($value, '"') === 0;
                $endsWithDoubleQuote = substr($value, -1) === '"' ? 1 : 0;
                $value = trim($value, '"');
            }

            $uitype = $fieldInfo->get('uitype');
            $fieldname = $fieldInfo->get('name');

            if (!isset($this->fieldDataTypeCache[$fieldName])) {
                $this->fieldDataTypeCache[$fieldName] = $fieldInfo->getFieldDataType();
            }
            $type = $this->fieldDataTypeCache[$fieldName];

            // Restore double quote now.
            if ($beginsWithDoubleQuote) {
                $value = "\"{$value}";
            }
            if ($endsWithDoubleQuote) {
                $value = "{$value}\"";
            }
            if ($fieldname != 'hdnTaxType' && ($uitype == 15 || $uitype == 16 || $uitype == 33)) {
                if (empty($this->picklistValues[$fieldname])) {
                    $this->picklistValues[$fieldname] = $this->fieldArray[$fieldname]->getPicklistValues();
                }
                // If the value being exported is accessible to current user
                // or the picklist is multiselect type.
                if ($uitype == 33 || $uitype == 16 || array_key_exists($value, $this->picklistValues[$fieldname])) {
                    // NOTE: multipicklist (uitype=33) values will be concatenated with |# delim
                    $value = trim($value);
                } else {
                    $value = '';
                }
            } elseif ($uitype == 52 || $type == 'owner') {
                $value = Vtiger_Util_Helper::getOwnerName($value);
            } elseif ($type == 'reference') {
                $value = isset($value) && $value ? trim($value) : '';
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
            } elseif ($uitype == 72 || $uitype == 71) {
                $value = CurrencyField::convertToUserFormat($value, null, true, true);
            } elseif ($uitype == 7 && $fieldInfo->get('typeofdata') == 'N~O' || $uitype == 9) {
                $value = decimalFormat($value);
            } elseif ($type == 'date') {
                if ($value && $value != '0000-00-00') {
                    $value = DateTimeField::convertToUserFormat($value);
                }
            } /**
            *  Handled Conversion of time as per custom field time format in exported file.
            */ elseif ($uitype == 14) {
                $timeUIObj = new Vtiger_Time_UIType();
                $value = $timeUIObj->getDisplayValue($value);
            } elseif ($type == 'datetime') {
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
            if ($moduleName == 'Documents' && $fieldname == 'description') {
                $value = strip_tags($value);
                $value = str_replace('&nbsp;', '', $value);
                array_push($new_arr, $value);
            }
        }

        return $arr;
    }

    public function moduleFieldInstances($moduleName)
    {
        return $this->moduleInstance->getFields();
    }
}
