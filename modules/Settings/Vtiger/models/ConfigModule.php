<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Settings_Vtiger_ConfigModule_Model extends Settings_Vtiger_Module_Model
{
    public $fileName = 'config.inc.php';

    public $completeData;

    public $data;

    /**
     * Function to read config file.
     * @return <Array> The data of config file
     */
    public function readFile()
    {
        if (!$this->completeData) {
            $this->completeData = file_get_contents($this->fileName);
        }

        return $this->completeData;
    }

    /**
     * Function to get CompanyDetails Menu item.
     * @return menu item Model
     */
    public function getMenuItem()
    {
        $menuItem = Settings_Vtiger_MenuItem_Model::getInstance('Configuration Editor');

        return $menuItem;
    }

    /**
     * Function to get Edit view Url.
     * @return <String> Url
     */
    public function getEditViewUrl()
    {
        $menuItem = $this->getMenuItem();

        return '?module=Vtiger&parent=Settings&view=ConfigEditorEdit&block=' . $menuItem->get('blockid') . '&fieldid=' . $menuItem->get('fieldid');
    }

    /**
     * Function to get Detail view Url.
     * @return <String> Url
     */
    public function getDetailViewUrl()
    {
        $menuItem = $this->getMenuItem();

        return '?module=Vtiger&parent=Settings&view=ConfigEditorDetail&block=' . $menuItem->get('blockid') . '&fieldid=' . $menuItem->get('fieldid');
    }

    /**
     * Function to get Viewable data of config details.
     * @return <Array>
     */
    public function getViewableData()
    {
        if (!$this->getData()) {
            $fileContent = $this->readFile();
            $pattern = '/\$([^=]+)=([^;]+);/';
            $matches = null;
            $matchesFound = preg_match_all($pattern, $fileContent, $matches);
            $configContents = [];
            if ($matchesFound) {
                $configContents = $matches[0];
            }

            $data = [];
            $editableFileds = $this->getEditableFields();
            foreach ($editableFileds as $fieldName => $fieldDetails) {
                foreach ($configContents as $configContent) {
                    if (strpos($configContent, $fieldName)) {
                        $fieldValue = explode(' = ', $configContent);
                        $fieldValue = $fieldValue[1];
                        if ($fieldName === 'upload_maxsize') {
                            $fieldValue = trim($fieldValue, ' ;');
                            $fieldValue = round(number_format($fieldValue / 1_048_576, 2));
                        }

                        $data[$fieldName] = str_replace(';', '', str_replace("'", '', $fieldValue));
                        break;
                    }
                }
            }
            $this->setData($data);
        }

        return $this->getData();
    }

    /**
     * Function to get picklist values.
     * @param <String> $fieldName
     * @return <Array> list of module names
     */
    public function getPicklistValues($fieldName)
    {
        return ['true', 'false'];
    }

    /**
     * Function to get editable fields.
     * @return <Array> list of field names
     */
    public function getEditableFields()
    {
        return [
            'HELPDESK_SUPPORT_EMAIL_ID'		=> ['label' => 'LBL_HELPDESK_SUPPORT_EMAILID', 'fieldType' => 'input'],
            'HELPDESK_SUPPORT_NAME'			=> ['label' => 'LBL_HELPDESK_SUPPORT_NAME', 'fieldType' => 'input'],
            'upload_maxsize'				=> ['label' => 'LBL_MAX_UPLOAD_SIZE', 'fieldType' => 'input'],
            'listview_max_textlength'		=> ['label' => 'LBL_MAX_TEXT_LENGTH_IN_LISTVIEW', 'fieldType' => 'input'],
            'list_max_entries_per_page'		=> ['label' => 'LBL_MAX_ENTRIES_PER_PAGE_IN_LISTVIEW', 'fieldType' => 'input'],
        ];
    }

    /**
     * Function to save the data.
     */
    public function save()
    {
        $fileContent = $this->completeData;
        $updatedFields = $this->get('updatedFields');
        $validationInfo = $this->validateFieldValues($updatedFields);
        $editableFields = $this->getEditableFields();
        if ($validationInfo === true) {
            foreach ($updatedFields as $fieldName => $fieldValue) {
                if (!in_array($fieldName, array_keys($editableFields))) {
                    continue;
                }
                $patternString = "\$%s = '%s';";
                if ($fieldName === 'upload_maxsize') {
                    $fieldValue = $fieldValue * 1_048_576; // (1024 * 1024)
                    $patternString = '$%s = %s;';
                }
                if ($fieldName === 'list_max_entries_per_page' || $fieldName === 'listview_max_textlength') {
                    $fieldValue = intval($fieldValue);
                }
                $pattern = '/\$' . $fieldName . '[\s]+=([^;]+);/';
                $replacement = sprintf($patternString, $fieldName, ltrim($fieldValue, '0'));
                $fileContent = preg_replace($pattern, $replacement, $fileContent);
            }
            $filePointer = fopen($this->fileName, 'w');
            fwrite($filePointer, $fileContent);
            fclose($filePointer);
        }

        return $validationInfo;
    }

    /**
     * Function to validate the field values.
     * @param <Array> $updatedFields
     * @return <String> True/Error message
     */
    public function validateFieldValues($updatedFields)
    {
        if (array_key_exists('HELPDESK_SUPPORT_EMAIL_ID', $updatedFields) && !filter_var($updatedFields['HELPDESK_SUPPORT_EMAIL_ID'], FILTER_VALIDATE_EMAIL)) {
            return 'LBL_INVALID_EMAILID';
        }
        if (array_key_exists('HELPDESK_SUPPORT_NAME', $updatedFields) && preg_match('/[\'";?><]/', $updatedFields['HELPDESK_SUPPORT_NAME'])) {
            return 'LBL_INVALID_SUPPORT_NAME';
        }
        if ((array_key_exists('upload_maxsize', $updatedFields) &&  filter_var(ltrim($updatedFields['upload_maxsize'], '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]) === false) || (array_key_exists('list_max_entries_per_page', $updatedFields) &&  filter_var(ltrim($updatedFields['list_max_entries_per_page'], '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]) === false) || (array_key_exists('listview_max_textlength', $updatedFields) && filter_var(ltrim($updatedFields['listview_max_textlength'], '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]) === false)) {
            return 'LBL_INVALID_NUMBER';
        }

        return true;
    }

    /**
     * Function to get the instance of Config module model.
     * @return <Settings_Vtiger_ConfigModule_Model> $moduleModel
     */
    public static function getInstance()
    {
        $moduleModel = new self();
        $moduleModel->getViewableData();

        return $moduleModel;
    }
}
