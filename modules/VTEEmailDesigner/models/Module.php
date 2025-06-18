<?php

class VTEEmailDesigner_Module_Model extends Vtiger_Module_Model
{
    public static $BROWSER_MERGE_TAG = '$custom-viewinbrowser$';

    public function getSettingLinks()
    {
        $settingsLinks[] = ['linktype' => 'MODULESETTING', 'linklabel' => 'Settings', 'linkurl' => 'index.php?module=VTEEmailDesigner&view=List', 'linkicon' => ''];
        $settingsLinks[] = ['linktype' => 'MODULESETTING', 'linklabel' => 'Uninstall', 'linkurl' => 'index.php?module=VTEEmailDesigner&parent=Settings&view=Uninstall', 'linkicon' => ''];

        return $settingsLinks;
    }

    public function getCreateViewUrl($record = '')
    {
        return 'index.php?module=VTEEmailDesigner&parent=Settings&view=Edit' . ($record != '' ? '&record=' . $record : '');
    }

    public function getCreatePreViewLink($record = '')
    {
        return 'index.php?module=VTEEmailDesigner&parent=Settings&view=Preview' . ($record != '' ? '&record=' . $record : '');
    }

    public function getSettingURL()
    {
        return 'index.php?module=VTEEmailDesigner&view=List';
    }

    public function getRelatedFieldName($module, $relModule)
    {
        global $adb;
        $sql = "SELECT fieldname FROM `vtiger_field` WHERE fieldid IN (SELECT fieldid from vtiger_fieldmodulerel WHERE module='" . $module . "' AND relmodule='" . $relModule . "')";
        $results = $adb->pquery($sql, []);
        if ($adb->num_rows($results) > 0) {
            $fieldname = $adb->query_result($results, 0, 'fieldname');
        }

        return $fieldname;
    }

    public function getModuleFields($module)
    {
        $values = [];
        $moduleModel = Vtiger_Module_Model::getInstance($module);
        $blockModelList = $moduleModel->getBlocks();
        foreach ($blockModelList as $blockLabel => $blockModel) {
            $fieldModelList = $blockModel->getFields();
            if (!empty($fieldModelList)) {
                foreach ($fieldModelList as $fieldName => $fieldModel) {
                    $values[$fieldName] = vtranslate($fieldModel->get('label'), $module);
                }
            }
        }

        return $values;
    }

    public function getBlocksByCat($catId = '')
    {
        global $adb;
        $sql = 'select * from vteemaildesigner_blocks where is_active=1 and cat_id=?';
        $res = $adb->pquery($sql, [$catId]);
        $data = [];

        while ($row = $adb->fetch_row($res)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Function to get Alphabet Search Field.
     */
    public function getAlphabetSearchField()
    {
        return 'templatename';
    }

    /**
     * Function to get the url for the Create Record view of the module.
     * @return <String> - url
     */
    public function getCreateRecordUrl()
    {
        return 'index.php?module=' . $this->get('name') . '&view=' . $this->getEditViewName();
    }

    /**
     * Function to save a given record model of the current module.
     * @param EmailtTemplates_Record_Model $recordModel
     * @return <integer> template id
     */
    public function saveRecord($recordModel)
    {
        $db = PearDatabase::getInstance();
        $recordId = $templateid = $recordModel->getId();
        $systemtemplate = $recordModel->get('systemtemplate');
        if (empty($systemtemplate)) {
            $systemtemplate = '0';
        }
        if (empty($templateid)) {
            $templateid = $db->getUniqueID('vtiger_emailtemplates');
            $sql = 'INSERT INTO vtiger_emailtemplates(templatename, subject, description, module, body, deleted, systemtemplate, templateid) VALUES (?,?,?,?,?,?,?,?)';
        } else {
            if ($systemtemplate) {
                $sql = 'UPDATE vtiger_emailtemplates SET templatename=?, description=?, module=?, body=?, deleted=?, systemtemplate=? WHERE templateid = ?';
            } else {
                $sql = 'UPDATE vtiger_emailtemplates SET templatename=?, subject=?, description=?, module=?, body=?, deleted=?, systemtemplate=? WHERE templateid = ?';
            }
        }
        if (!empty($recordId) && $systemtemplate) {
            $params = [decode_html($recordModel->get('templatename')), decode_html($recordModel->get('description')), $recordModel->get('module'), $recordModel->get('body'), 0, $systemtemplate, $templateid];
        } else {
            $params = [decode_html($recordModel->get('templatename')), decode_html($recordModel->get('subject')), decode_html($recordModel->get('description')), $recordModel->get('module'), $recordModel->get('body'), 0, $systemtemplate, $templateid];
        }
        $db->pquery($sql, $params);

        return $recordModel->setId($templateid);
    }

    /**
     * Function to delete the email template.
     */
    public function deleteRecord($recordModel)
    {
        $recordId = $recordModel->getId();
        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_emailtemplates WHERE templateid = ? AND systemtemplate = ? ', [$recordId, '0']);
        $db->pquery('DELETE FROM vteemaildesigner_template_blocks WHERE templateid = ? ', [$recordId]);
        require_once 'modules/VTEEmailDesigner/VTEEmailDesigner.php';
        $VTEEmailDesigner = new VTEEmailDesigner();
        $isTableExist = $VTEEmailDesigner->checkTableExist('vtiger_vteemailmarketing_emailtemplate');
        if ($isTableExist) {
            $db->pquery('DELETE FROM vtiger_vteemailmarketing_emailtemplate WHERE idtemplate=?', [$recordId]);
        }
    }

    /**
     * Function to delete all the email templates.
     */
    public function deleteAllRecords()
    {
        $db = PearDatabase::getInstance();
        $db->pquery('DELETE FROM vtiger_emailtemplates WHERE systemtemplate = ?', ['0']);
    }

    /**
     * Function to get Email template fields from modules.
     * @return <array> template fields
     */
    public function getAllModuleEmailTemplateFields()
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $allModuleList = $this->getAllModuleList();
        $allRelFields = [];
        foreach ($allModuleList as $index => $module) {
            if ($module == 'Users') {
                $fieldList = $this->getRelatedModuleFieldList($module, $currentUserModel);
                $fieldList[] = ['module' => $module, 'fieldname' => 'user_password_custom', 'columnname' => 'user_password_custom', 'fieldlabel' => 'Password'];
            } else {
                $fieldList = $this->getRelatedFields($module, $currentUserModel);
            }
            $allFields = [];
            foreach ($fieldList as $key => $field) {
                $option = [vtranslate($field['module'], $field['module']) . ':' . vtranslate($field['fieldlabel'], $field['module']), '$' . strtolower($field['module']) . '-' . $field['columnname'] . '$'];
                $allFields[] = $option;
                if (!empty($field['referencelist'])) {
                    foreach ($field['referencelist'] as $referenceList) {
                        foreach ($referenceList as $key => $relField) {
                            $relOption = [vtranslate($field['fieldlabel'], $field['module']) . ':(' . vtranslate($relField['module'], $relField['module']) . ')' . vtranslate($relField['fieldlabel'], $relField['module']), '$' . strtolower($field['module']) . '-' . $field['columnname'] . ':' . $relField['columnname'] . '$'];
                            $allRelFields[] = $relOption;
                        }
                    }
                }
            }
            if (is_array($allFields) && is_array($allRelFields)) {
                $allFields = array_merge($allFields, $allRelFields);
                $allRelFields = [];
            }
            $allOptions[$module] = $allFields;
            $allFields = [];
        }

        return $allOptions;
    }

    /**
     * Function retrives all company details merge tags and add to field array.
     * @return string
     */
    public function getCompanyMergeTagsInfo()
    {
        global $site_URL;
        $companyModuleModel = Settings_Vtiger_CompanyDetails_Model::getInstance();
        $basicFields = $companyModuleModel->companyBasicFields;
        $socialFields = $companyModuleModel->companySocialLinks;
        $qualifiedModule = 'Settings:Vtiger';
        $moduleName = vtranslate('LBL_COMPANY_DETAILS', $qualifiedModule);
        $allFields = [];
        $logoPath = $site_URL . '/' . $companyModuleModel->getLogoPath();
        foreach ($basicFields as $columnName => $value) {
            if ($columnName == 'logo') {
                $allFields[] = [$moduleName . ':' . vtranslate($columnName, $qualifiedModule), '$' . $columnName . '$'];
            } else {
                $allFields[] = [$moduleName . ':' . vtranslate($columnName, $qualifiedModule), '$' . strtolower('companydetails') . '-' . $columnName . '$'];
            }
        }
        foreach ($socialFields as $columnName => $value) {
            $url = $companyModuleModel->get($columnName);
            if ($columnName == 'website') {
                $websiteURL = $url;
                if (empty($url)) {
                    $websiteURL = $columnName;
                }
                $allFields[] = [$moduleName . ':' . vtranslate($columnName, $qualifiedModule), "<a target='_blank' href='" . $url . "'>" . $websiteURL . '</a>'];
            } else {
                $allFields[] = [$moduleName . ':' . vtranslate($columnName, $qualifiedModule), "<a target='_blank' href='" . $url . "'>" . $columnName . '</a>'];
            }
        }

        return $allFields;
    }

    /**
     * Function returns custom merge tags.
     * @return array
     */
    public function getCustomMergeTags()
    {
        $customMergeTags = [['Current Date', '$custom-currentdate$'], ['Current Time', '$custom-currenttime$'], ['View in browser', "<a target='_blank' href='" . self::$BROWSER_MERGE_TAG . "'>View in browser</a>"], ['CRM Detail View Url', '$custom-crmdetailviewurl$'], ['Portal Detail View Url', '$custom-portaldetailviewurl$'], ['Site Url', '$custom-siteurl$'], ['Portal Url', '$custom-portalurl$']];

        return $customMergeTags;
    }

    /**
     * Function to get module fields.
     * @param type $module
     * @param type $currentUserModel
     * @return <arrau>
     */
    public function getRelatedFields($module, $currentUserModel)
    {
        $handler = vtws_getModuleHandlerFromName($module, $currentUserModel);
        $meta = $handler->getMeta();
        $moduleFields = $meta->getModuleFields();
        $db = PearDatabase::getInstance();
        $fieldInfo = ['columnname' => 'id', 'fieldname' => 'id', 'fieldlabel' => vtranslate('LBL_RECORD_ID', $this->getName())];
        $recordIdField = WebserviceField::fromArray($db, $fieldInfo);
        $moduleFields[$recordIdField->getFieldName()] = $recordIdField;
        $returnData = [];
        foreach ($moduleFields as $key => $field) {
            if (!in_array($field->getPresence(), [0, 2])) {
                continue;
            }
            $referencelist = [];
            $relatedField = $field->getReferenceList();
            if ($field->getFieldName() == 'assigned_user_id') {
                $relModule = 'Users';
                $referencelist[] = $this->getRelatedModuleFieldList($relModule, $currentUserModel);
            }
            if (!empty($relatedField)) {
                foreach ($relatedField as $ind => $relModule) {
                    $referencelist[] = $this->getRelatedModuleFieldList($relModule, $currentUserModel);
                }
            }
            $returnData[] = ['module' => $module, 'fieldname' => $field->getFieldName(), 'columnname' => $field->getColumnName(), 'fieldlabel' => $field->getFieldLabelKey(), 'referencelist' => $referencelist];
        }

        return $returnData;
    }

    /**
     * Function to get related module fields.
     * @param type $relModule
     * @param type $user
     */
    public function getRelatedModuleFieldList($relModule, $user)
    {
        $handler = vtws_getModuleHandlerFromName($relModule, $user);
        $relMeta = $handler->getMeta();
        if (!$relMeta->isModuleEntity()) {
            return [];
        }
        $relModuleFields = $relMeta->getModuleFields();
        $relModuleFieldList = [];
        foreach ($relModuleFields as $relind => $relModuleField) {
            if (!in_array($relModuleField->getPresence(), [0, 2])) {
                continue;
            }
            if ($relModule == 'Users') {
                if (in_array($relModuleField->getFieldDataType(), ['string', 'phone', 'email', 'text'])) {
                    $skipFields = [98, 115, 116, 31, 32];
                    if (!in_array($relModuleField->getUIType(), $skipFields) && $relModuleField->getFieldName() != 'asterisk_extension') {
                        $relModuleFieldList[] = ['module' => $relModule, 'fieldname' => $relModuleField->getFieldName(), 'columnname' => $relModuleField->getColumnName(), 'fieldlabel' => $relModuleField->getFieldLabelKey()];
                    }
                }
            } else {
                $relModuleFieldList[] = ['module' => $relModule, 'fieldname' => $relModuleField->getFieldName(), 'columnname' => $relModuleField->getColumnName(), 'fieldlabel' => $relModuleField->getFieldLabelKey()];
            }
        }

        return $relModuleFieldList;
    }

    /**
     * Function to get module list which has the email field.
     * @return type
     */
    public function getAllModuleList()
    {
        $db = PearDatabase::getInstance();
        $query = "SELECT DISTINCT(name) AS modulename FROM vtiger_tab \r\n\t\t\t\t\tLEFT JOIN vtiger_field ON vtiger_field.tabid = vtiger_tab.tabid\r\n\t\t\t\t\tWHERE (vtiger_field.uitype = ? AND vtiger_tab.presence = ?) ";
        $params = ['13', 0];
        if (vtlib_isModuleActive('Calendar')) {
            $eventsTabid = getTabid('Events');
            $query .= ' OR vtiger_tab.tabid IN (?, ?)';
            array_push($params, $eventsTabid, getTabid('Calendar'));
        }
        $result = $db->pquery($query, $params);
        $num_rows = $db->num_rows($result);
        $moduleList = [];
        for ($i = 0; $i < $num_rows; ++$i) {
            $moduleList[] = $db->query_result($result, $i, 'modulename');
        }

        return $moduleList;
    }

    /**
     * Function to get the Quick Links for the module.
     * @param <Array> $linkParams
     * @return <Array> List of Vtiger_Link_Model instances
     */
    public function getSideBarLinks($linkParams)
    {
        $linkTypes = ['SIDEBARLINK', 'SIDEBARWIDGET'];
        $links = Vtiger_Link_Model::getAllByType($this->getId(), $linkTypes, $linkParams);
        $quickLinks = [['linktype' => 'SIDEBARLINK', 'linklabel' => 'LBL_RECORDS_LIST', 'linkurl' => $this->getDefaultUrl(), 'linkicon' => '']];
        foreach ($quickLinks as $quickLink) {
            $links['SIDEBARLINK'][] = Vtiger_Link_Model::getInstanceFromValues($quickLink);
        }

        return $links;
    }

    public function getRecordIds($skipRecords)
    {
        $db = PearDatabase::getInstance();
        $query = 'SELECT templateid FROM vtiger_emailtemplates WHERE templateid NOT IN (' . generateQuestionMarks($skipRecords) . ')';
        $result = $db->pquery($query, $skipRecords);
        $num_rows = $db->num_rows($result);
        for ($recordIds = []; $i < $num_rows; ++$i) {
            $recordIds[] = $db->query_result($result, $i, 'templateid');
        }

        return $recordIds;
    }

    /**
     * Funxtion to identify if the module supports quick search or not.
     */
    public function isQuickSearchEnabled()
    {
        return false;
    }

    public function getPopupUrl()
    {
        return 'module=EmailTemplates&view=Popup';
    }

    public function getBlocks()
    {
        if (empty($this->blocks)) {
            $blockLabelKeys = ['SINGLE_EmailTemplates', 'LBL_EMAIL_TEMPLATE'];
            $blocks = [];
            foreach ($blockLabelKeys as $blockIndex => $blockLabel) {
                $blockInstance = new VTEEmailDesigner_Block_Model();
                $blockInstance->set('blocklabel', $blockLabel)->set('sequence', $blockIndex + 1)->set('module', $this)->set('id', $blockIndex + 1);
                $blocks[$blockLabel] = $blockInstance;
            }
            $this->blocks = $blocks;
        }

        return parent::getBlocks();
    }

    public function getUtilityActionsNames()
    {
        return [];
    }

    /**
     * Function to get Module Header Links (for Vtiger7).
     * @return array
     */
    public function getModuleBasicLinks()
    {
        $createPermission = Users_Privileges_Model::isPermitted($this->getName(), 'CreateView');
        $moduleName = $this->getName();
        if ($createPermission) {
            $basicLinks[] = ['linktype' => 'BASIC', 'linklabel' => 'LBL_ADD_RECORD', 'linkurl' => $this->getCreateRecordUrl(), 'linkicon' => 'fa-plus'];
        }

        return $basicLinks;
    }

    public function isFilterColumnEnabled()
    {
        return false;
    }

    public function getModuleIcon()
    {
        $moduleName = $this->getName();
        $lowerModuleName = strtolower($moduleName);
        $title = vtranslate($moduleName, $moduleName);
        $moduleIcon = "<i class='vicon-emailtemplates' title='" . $title . "'></i>";

        return $moduleIcon;
    }

    public function getTemplatePath($templateid = '')
    {
        $db = PearDatabase::getInstance();
        $query = 'SELECT templatepath FROM vtiger_emailtemplates WHERE templateid =?';
        $result = $db->pquery($query, [$templateid]);
        if ($db->num_rows($result)) {
            $templatepath = $db->query_result($result, 0, 'templatepath');
        }

        return $templatepath;
    }
}
