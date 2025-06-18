<?php

/*
 * The content of this file is subject to the EMAIL Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 ******************************************************************************* */

class EMAILMaker_Record_Model extends Vtiger_Record_Model
{
    public static function getInstanceById($templateId, $module = null)
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT vtiger_emakertemplates_displayed.*, vtiger_emakertemplates.*  FROM vtiger_emakertemplates 
                                    LEFT JOIN vtiger_emakertemplates_displayed ON vtiger_emakertemplates_displayed.templateid = vtiger_emakertemplates.templateid 
                                    WHERE vtiger_emakertemplates.templateid = ?', [$templateId]);
        if ($db->num_rows($result) > 0) {
            $row = $db->query_result_rowdata($result, 0);
            $recordModel = new self();
            $row['label'] = $row['templatename'];

            return $recordModel->setData($row)->setId($templateId)->setModule($row['module'] != '' ? $row['module'] : 'EMAILMaker');
        }

        return null;
    }

    public function setId($value)
    {
        return $this->set('templateid', $value);
    }

    public function delete()
    {
        $this->getModule()->deleteRecord($this);
    }

    public function deleteAllRecords()
    {
        $this->getModule()->deleteAllRecords();
    }

    public function getEmailTemplateFields()
    {
        return $this->getModule()->getAllModuleEmailTemplateFields();
    }

    public function getTemplateData($record)
    {
        return $this->getModule()->getTemplateData($record);
    }

    /**
     *  Functions returns delete url.
     * @return string - delete url
     */
    public function getDeleteUrl()
    {
        return 'index.php?module=EMAILMaker&action=Delete&record=' . $this->getId();
    }

    public function getId()
    {
        return $this->get('templateid');
    }

    /**
     * Function to get the Edit View url for the record.
     * @return <String> - Record Edit View Url
     */
    public function getEditViewUrl()
    {
        return 'index.php?module=EMAILMaker&view=Edit&record=' . $this->getId();
    }

    /**
     * Funtion to get Duplicate Record Url.
     * @return <String>
     */
    public function getDuplicateRecordUrl()
    {
        return 'index.php?module=EMAILMaker&view=Edit&record=' . $this->getId() . '&isDuplicate=true';

    }

    public function getDetailViewUrl()
    {
        $module = $this->getModule();

        return 'index.php?module=EMAILMaker&view=' . $module->getDetailViewName() . '&record=' . $this->getId();
    }

    public function getName()
    {
        return $this->get('templatename');
    }

    public function isDeleted()
    {
        if ($this->get('deleted') == '1') {
            return true;
        }

        return false;

    }

    /**
     * Function returns valuetype of the field filter.
     * @return <String>
     */
    public function getFieldFilterValueType($fieldname)
    {
        $conditions = $this->get('conditions');
        if (!empty($conditions) && is_array($conditions)) {
            foreach ($conditions as $filter) {
                if ($fieldname == $filter['fieldname']) {
                    return $filter['valuetype'];
                }
            }
        }

        return false;
    }

    public function updateDisplayConditions($conditions, $displayed_value)
    {
        $adb = PearDatabase::getInstance();
        $templateid = $this->getId();
        $adb->pquery('DELETE FROM vtiger_emakertemplates_displayed WHERE templateid=?', [$templateid]);

        $conditions = $this->transformAdvanceFilterToEMAILMakerFilter($conditions);

        $display_conditions = Zend_Json::encode($conditions);


        $adb->pquery('INSERT INTO vtiger_emakertemplates_displayed (templateid,displayed,conditions) VALUES (?,?,?)', [$templateid, $displayed_value, $display_conditions]);

        return true;
    }

    public function transformAdvanceFilterToEMAILMakerFilter($conditions)
    {
        $wfCondition = [];

        if (!empty($conditions)) {
            foreach ($conditions as $index => $condition) {
                $columns = $condition['columns'];
                if ($index == '1' && empty($columns)) {
                    $wfCondition[] = [
                        'fieldname' => '',
                        'operation' => '',
                        'value' => '',
                        'valuetype' => '',
                        'joincondition' => '',
                        'groupid' => '0',
                    ];
                }
                if (!empty($columns) && is_array($columns)) {
                    foreach ($columns as $column) {
                        $wfCondition[] = [
                            'fieldname' => $column['columnname'],
                            'operation' => $column['comparator'],
                            'value' => $column['value'],
                            'valuetype' => $column['valuetype'],
                            'joincondition' => $column['column_condition'],
                            'groupjoin' => $condition['condition'],
                            'groupid' => $column['groupid'],
                        ];
                    }
                }
            }
        }

        return $wfCondition;
    }

    public function getConditonDisplayValue()
    {
        $conditionList = [];
        $displayed = $this->get('displayed');
        $conditions = $this->get('conditions');
        $moduleName = $this->get('module');
        if (!empty($conditions)) {
            $PDFMaker_Display_Model = new EMAILMaker_Display_Model();
            $conditionList = $PDFMaker_Display_Model->getConditionsForDetail($displayed, $conditions, $moduleName);
        }

        return $conditionList;
    }

    /**
     * @param int $templateId
     * @return string
     * @throws Exception
     */
    public static function getDefaultFromEmail($templateId)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $adb = PearDatabase::getInstance();
        $result_lfn = $adb->pquery(
            'SELECT fieldname FROM vtiger_emakertemplates_default_from WHERE templateid = ? AND userid = ?',
            [$templateId, $currentUser->id],
        );

        return $adb->query_result($result_lfn, 0, 'fieldname');
    }

    /**
     * @return string
     */
    public static function getIgnorePicklistValues()
    {
        $adb = PearDatabase::getInstance();
        $ignore_picklist_values = '';
        $result = $adb->pquery('SELECT value FROM vtiger_emakertemplates_ignorepicklistvalues', []);

        if ($adb->num_rows($result)) {
            $values = [];

            while ($row = $adb->fetchByAssoc($result)) {
                $values[] = $row['value'];
            }

            $ignore_picklist_values = implode(', ', $values);
        }

        return $ignore_picklist_values;
    }

    /**
     * @return array
     */
    public static function getDecimalSettings()
    {
        $current_user = Users_Record_Model::getCurrentUserModel();
        $adb = PearDatabase::getInstance();
        $result = $adb->pquery('SELECT * FROM vtiger_emakertemplates_settings', []);

        if ($adb->num_rows($result)) {
            $settingsResult = $adb->fetchByAssoc($result, 0);

            return [
                'point' => $settingsResult['decimal_point'],
                'decimals' => $settingsResult['decimals'],
                'thousands' => ($settingsResult['thousands_separator'] != 'sp' ? $settingsResult['thousands_separator'] : ' '),
            ];
        }

        $thousands_separator = $current_user->currency_grouping_separator;

        return [
            'point' => $current_user->currency_decimal_separator,
            'decimals' => $current_user->no_of_currency_decimals,
            'thousands' => ($thousands_separator != 'sp' ? $thousands_separator : ' '),
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    public static function getCompanyImages()
    {
        global $site_URL;

        $adb = PearDatabase::getInstance();
        $result = $adb->pquery('SELECT * FROM vtiger_organizationdetails', []);
        $row = $adb->query_result_rowdata($result);
        $path = $site_URL . '/test/logo/';
        $images = [
            'logoname' => decode_html($row['logoname']),
            'headername' => decode_html($row['headername']),
            'stamp_signature' => $row['stamp_signature'],
        ];

        if (isset($images['logoname'])) {
            $images['logoname_img'] = '<img src="' . $path . $images['logoname'] . '">';
        }

        if (isset($images['headername'])) {
            $images['headername_img'] = '<img src="' . $path . $images['headername'] . '">';
        }

        if (isset($images['stamp_signature'])) {
            $images['stamp_signature_img'] = '<img src="' . $path . $images['stamp_signature'] . '">';
        }

        return $images;
    }
}
