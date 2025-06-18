<?php

include_once 'modules/ModuleLinkCreator/ModuleLinkCreator.php';
include_once 'modules/ModuleLinkCreator/models/RelatedFieldsModule.php';
require_once 'include/utils/utils.php';
require_once 'include/utils/CommonUtils.php';
require_once 'includes/Loader.php';
vimport('includes.runtime.EntryPoint');

/**
 * Class ModuleLinkCreator_ActionAjax_Action.
 */
class ModuleLinkCreator_ActionAjax_Action extends Vtiger_Action_Controller
{
    /**
     * @constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('save');
        $this->exposeMethod('save_setting');
        $this->exposeMethod('checkModule');
        $this->exposeMethod('getBlocks');
        $this->exposeMethod('saveRelatedFields');
        $this->exposeMethod('saveRelationship11');
        $this->exposeMethod('saveRelationshipMM');
        $this->exposeMethod('getRelations');
        $this->exposeMethod('deleteRelations');
        $this->exposeMethod('getSingleModuleName');
        $this->exposeMethod('updateIcon');
    }

    /**
     * @return bool
     */
    public function checkPermission(Vtiger_Request $request)
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    public function save(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $data = [];
        $params = [];
        $recordModel = ModuleLinkCreator_Record_Model::getCleanInstance($module);
        $record = $request->get('record');
        if ($request->has('module_name')) {
            $params['module_name'] = $request->get('module_name');
        }
        if ($request->has('description')) {
            $params['description'] = $request->get('description');
        }
        $template = $recordModel->save($record, $params);
        $id = $template->getId();
        if (!$id) {
            $response->setError(200, vtranslate('LBL_FAILURE', $module));

            return $response->emit();
        }
        $data['id'] = $id;
        $data['message'] = vtranslate('LBL_SUCCESSFUL', $module);
        $response->setResult($data);

        return $response->emit();
    }

    public function save_setting(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $data = [];
        $params = [];
        $recordModel = new ModuleLinkCreator_SettingRecord_Model();
        $record = $request->get('record');
        if ($request->has('description')) {
            $params['description'] = $request->get('description');
        }
        $id = $recordModel->saveByModule($record, $params);
        if (!$id) {
            $response->setError(200, vtranslate('LBL_FAILURE', $module));

            return $response->emit();
        }
        $data['id'] = $id;
        $data['message'] = vtranslate('LBL_SUCCESSFUL', $module);
        $response->setResult($data);

        return $response->emit();
    }

    public function delete(Vtiger_Request $request)
    {
        include 'modules/ModuleLinkCreator/models/ModuleLinkCreatorController.php';
        $recordModel = new ModuleLinkCreator_Record_Model();
        $recordId = $request->get('record');
        $module = $recordModel->getById($recordId);
        if ($module) {
            $moduleName = $module->get('module_name');
            $success = $recordModel->delete($recordId);
            if ($success) {
                $settingRecordModel = new ModuleLinkCreator_SettingRecord_Model();
                $settingRecordModel->deleteByModule($module->get('module_id'));
                $moduleController = new ModuleLinkCreator_ModuleController();
                $moduleController->uninstallModule($moduleName);
            }
        }
        header('Location: index.php?module=ModuleLinkCreator&view=List');
    }

    public function checkModule(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $data = [];
        $sourceModuleName = $request->get('source_module');
        $sourceModule = Vtiger_Module::getInstance($sourceModuleName);
        $dirPath = 'modules/';
        $dirs = array_filter(glob($dirPath . '*'), 'is_dir');
        $folders = [];
        foreach ($dirs as $dir) {
            $folders[] = str_replace($dirPath, '', $dir);
        }
        if (!$sourceModule && !in_array($sourceModuleName, $folders)) {
            $response->setError(0, vtranslate('LBL_VALID_MODULE', $moduleName));

            return $response->emit();
        }
        $data['message'] = vtranslate('LBL_EXIST_MODULE', $moduleName);
        $response->setResult($data);

        return $response->emit();
    }

    /**
     * @throws Exception
     */
    public function buildOptionsArray($result, $selectedModule)
    {
        $db = PearDatabase::getInstance();
        $optionArray = null;
        for ($i = 0; $i < $db->num_rows($result); ++$i) {
            $optionArray[$db->query_result($result, $i, 'blocklabel')] = getTranslatedString($db->query_result($result, $i, 'blocklabel'), $selectedModule);
        }

        return $optionArray;
    }

    public function getBlocks(Vtiger_Request $request)
    {
        $db = PearDatabase::getInstance();
        $selectedModule = $request->get('module1');
        $sql = "SELECT blocklabel FROM vtiger_blocks\r\n                INNER JOIN vtiger_tab ON vtiger_blocks.tabid=vtiger_tab.tabid\r\n                WHERE vtiger_tab.name=?";
        $result = $db->pquery($sql, [$selectedModule]);
        if ($db->num_rows($result) > 0) {
            $htmlOptions = $this->buildOptionsArray($result, $selectedModule);
            $fieldsResponse['result'] = 'ok';
            $fieldsResponse['options'] = $htmlOptions;
        } else {
            $fieldsResponse['result'] = 'fail';
        }
        $response = new Vtiger_Response();
        $response->setResult($fieldsResponse);
        $response->emit();
    }

    public function saveRelatedFields(Vtiger_Request $request)
    {
        global $vtiger_current_version;
        global $adb;
        $module1 = $request->get('module1');
        $module2 = $request->get('module2');
        $actionAdd = $request->get('action_add');
        $actionRelatedList = $request->get('not_related_list');
        $relatedFieldModel = new ModuleLinkCreator_RelatedFieldModule_Model();
        if (strlen($request->get('field_label')) > 50) {
            $fieldsResponse['result'] = 'fail';
            $fieldsResponse['message'] = vtranslate('CHARACTERS_LONG', $request->get('module'));
        } else {
            if (!$relatedFieldModel->validateFieldLabel($module1, $request->get('field_label'))) {
                $fieldsResponse['result'] = 'fail';
                $fieldsResponse['message'] = vtranslate('DUPLICATED_FIELD_LABEL', $request->get('module'));
            } else {
                if ($actionAdd == 'true' && !$relatedFieldModel->validateRelatedList($module1, $module2, 'new') && $actionRelatedList != 'true') {
                    $fieldsResponse['result'] = 'fail';
                    $fieldsResponse['message'] = vtranslate('DUPLICATED', $request->get('module'));
                } else {
                    if (($relatedFieldModel->validateField($module1, $module2) || $relatedFieldModel->validateCustomBuildFields($module1, $module2)) && $actionRelatedList != 'true') {
                        $fieldsResponse['result'] = 'fail';
                        $fieldsResponse['message'] = vtranslate('FIELD_ALREADY_THERE', $request->get('module'));
                    } else {
                        try {
                            if ($module1 != 'Events' && $module1 != 'Calendar') {
                                $fieldId = $relatedFieldModel->addField($request, $actionRelatedList);
                            }
                            $actions = '';
                            if ($actionAdd == 'true') {
                                $actions .= trim($actions) != '' ? ',add' : 'add';
                            }
                            $module12 = Vtiger_Module::getInstance($module1);
                            $module21 = Vtiger_Module::getInstance($module2);
                            if ($actionRelatedList == 'undefined' || $actionRelatedList != true) {
                                if ($module1 == 'Events' || $module1 == 'Calendar') {
                                    $module21->setRelatedList($module12, vtranslate('Activities'), ['ADD'], 'get_activities');
                                    $activityFieldTypeId = 34;
                                    $sqlCheckProject = 'SELECT * FROM `vtiger_ws_referencetype` WHERE fieldtypeid = ? AND type = ?';
                                    $rsCheckProject = $adb->pquery($sqlCheckProject, [$activityFieldTypeId, $module21->name]);
                                    if ($adb->num_rows($rsCheckProject) < 1) {
                                        $adb->pquery('INSERT INTO `vtiger_ws_referencetype` (`fieldtypeid`, `type`) VALUES (?, ?)', [$activityFieldTypeId, $module21->name]);
                                    }
                                } else {
                                    if (version_compare($vtiger_current_version, '7.0.0', '>=')) {
                                        $module21->setRelatedList($module12, $module1, [$actions], 'get_dependents_list', $fieldId);
                                        $maxId = $adb->pquery('SELECT max(relation_id) as relation_id FROM `vtiger_relatedlists` ', []);
                                        $adb->pquery("UPDATE `vtiger_relatedlists` SET `relationtype` = '1:N' WHERE `relation_id` = ?", [$adb->query_result($maxId, 0, 'relation_id')]);
                                    } else {
                                        $module21->setRelatedList($module12, $module1, [$actions], 'get_dependents_list');
                                    }
                                }
                            }
                            if ($fieldId) {
                                $sql = 'UPDATE `vtiger_field` SET summaryfield = 1 WHERE fieldid = ' . $fieldId . ';';
                                $adb->pquery($sql, []);
                                $sql = 'SELECT tablename,columnname FROM `vtiger_field` WHERE fieldid = ' . $fieldId . ';';
                                $results = $adb->pquery($sql, []);
                                if ($adb->num_rows($results) > 0) {
                                    $tablename = $adb->query_result($results, 0, 'tablename');
                                    $columnname = $adb->query_result($results, 0, 'columnname');
                                    $sql = 'ALTER TABLE `' . $tablename . '` MODIFY COLUMN `' . $columnname . '`  int(19) NULL DEFAULT NULL;';
                                    $adb->pquery($sql, []);
                                    $sql = 'ALTER TABLE `' . $tablename . '` ADD INDEX `' . $columnname . '_index` (`' . $columnname . '`) USING BTREE ;';
                                    $adb->pquery($sql, []);
                                }
                            }
                            $fieldsResponse['result'] = 'ok';
                            $fieldsResponse['message'] = vtranslate('SUCCESS', $request->get('module'));
                        } catch (Exception $exc) {
                            $fieldsResponse['result'] = 'fail';
                            $fieldsResponse['message'] = $exc->getTraceAsString();
                        }
                    }
                }
            }
        }
        $response = new Vtiger_Response();
        $response->setResult($fieldsResponse);
        $response->emit();
    }

    public function saveRelationship11(Vtiger_Request $request)
    {
        $module1 = $request->get('module1');
        $module2 = $request->get('module2');
        $txtModule12 = $request->get('txtmodule12');
        $txtModule21 = $request->get('txtmodule21');
        $moduleModel1 = CRMEntity::getInstance($module1);
        $moduleModel2 = CRMEntity::getInstance($module2);
        $initData = [$module1 => ['LBL_CUSTOM_INFORMATION' => ['cf_' . strtolower($module2) . '_id' => ['label' => $txtModule21, 'table' => $moduleModel1->table_name, 'uitype' => 10, 'related_to_module' => $module2]]], $module2 => ['LBL_CUSTOM_INFORMATION' => ['cf_' . strtolower($module1) . '_id' => ['label' => $txtModule12, 'table' => $moduleModel2->table_name, 'uitype' => 10, 'related_to_module' => $module1]]]];
        foreach ($initData as $moduleName => $blocks) {
            foreach ($blocks as $blockName => $fields) {
                $module = Vtiger_Module::getInstance($moduleName);
                $block = Vtiger_Block::getInstance($blockName, $module);
                if (!$block && $blockName) {
                    $block = new Vtiger_Block();
                    $block->label = $blockName;
                    $block->__create($module);
                }
                $adb = PearDatabase::getInstance();
                $currFieldSeqRs = $adb->pquery('SELECT sequence FROM `vtiger_field` WHERE block = ? ORDER BY sequence DESC LIMIT 0,1', [$block->id]);
                $sequence = $adb->query_result($currFieldSeqRs, 'sequence', 0);
                foreach ($fields as $name => $field) {
                    $existField = Vtiger_Field::getInstance($name, $module);
                    if (!$existField && $name && $field['table']) {
                        ++$sequence;
                        $newField = new Vtiger_Field();
                        $newField->name = $name;
                        $newField->label = $field['label'];
                        $newField->table = $field['table'];
                        $newField->uitype = $field['uitype'];
                        if ($field['uitype'] == 15 || $field['uitype'] == 16 || $field['uitype'] == '33') {
                            $newField->setPicklistValues($field['picklistvalues']);
                        }
                        $newField->sequence = $sequence;
                        $newField->__create($block);
                        if ($field['uitype'] == 10) {
                            $newField->setRelatedModules([$field['related_to_module']]);
                            $fieldId = $newField->id;
                            $sql = 'UPDATE `vtiger_field` SET summaryfield = 1 WHERE fieldid = ' . $fieldId . ';';
                            $adb->pquery($sql, []);
                            $sql = 'SELECT tablename,columnname FROM `vtiger_field` WHERE fieldid = ' . $fieldId . ';';
                            $results = $adb->pquery($sql, []);
                            if ($adb->num_rows($results) > 0) {
                                $tablename = $adb->query_result($results, 0, 'tablename');
                                $columnname = $adb->query_result($results, 0, 'columnname');
                                $sql = 'ALTER TABLE `' . $tablename . '` MODIFY COLUMN `' . $columnname . '`  int(19) NULL DEFAULT NULL;';
                                $adb->pquery($sql, []);
                                $sql = 'ALTER TABLE `' . $tablename . '` ADD INDEX `' . $columnname . '_index` (`' . $columnname . '`) USING BTREE ;';
                                $adb->pquery($sql, []);
                            }
                        }
                    }
                }
            }
        }
        $response = new Vtiger_Response();
        $response->emit();
    }

    public function saveRelationshipMM(Vtiger_Request $request)
    {
        $adb = PearDatabase::getInstance();
        $module1 = $request->get('module1');
        $module2 = $request->get('module2');
        $txtModule12 = $request->get('txtmodule12');
        $txtModule21 = $request->get('txtmodule21');
        $data = [];
        $response = new Vtiger_Response();

        try {
            $module12 = Vtiger_Module::getInstance($module1);
            $module21 = Vtiger_Module::getInstance($module2);
            $sql = 'SELECT * FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? AND name = ?';
            $rs = $adb->pquery($sql, [$module12->getId(), $module21->getId(), 'get_related_list']);
            if ($adb->num_rows($rs) == 0) {
                $module12->setRelatedList($module21, $txtModule21, ['ADD', 'SELECT'], 'get_related_list');
                $maxId = $adb->pquery('SELECT max(relation_id) as relation_id FROM `vtiger_relatedlists` ', []);
                $adb->pquery("UPDATE `vtiger_relatedlists` SET `relationtype` = 'N:N' WHERE `relation_id` = ?", [$adb->query_result($maxId, 0, 'relation_id')]);
                $module21->setRelatedList($module12, $txtModule12, ['ADD', 'SELECT'], 'get_related_list');
                $maxId = $adb->pquery('SELECT max(relation_id) as relation_id FROM `vtiger_relatedlists` ', []);
                $adb->pquery("UPDATE `vtiger_relatedlists` SET `relationtype` = 'N:N' WHERE `relation_id` = ?", [$adb->query_result($maxId, 0, 'relation_id')]);
                $data['message'] = vtranslate('SUCCESS', $request->get('module'));
            } else {
                $data['message'] = vtranslate('DUPLICATED', $request->get('module'));
            }
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }
        $response->setResult($data);
        $response->emit();
    }

    public function getRelations(Vtiger_Request $request)
    {
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $relationType = $request->get('relation_type');
        $moduleModel = Vtiger_Module_Model::getInstance('ModuleLinkCreator');
        $data = [];
        if ($relationType == '1-1') {
            $data = $moduleModel->getRelation_1_1();
        } else {
            if ($relationType == '1-M') {
                $data = $moduleModel->getRelation_1_M();
            } else {
                if ($relationType == 'M-M') {
                    $data = $moduleModel->getRelation_M_M();
                } else {
                    if ($relationType == '1-None') {
                        $data = $moduleModel->getRelation_1_None();
                    }
                }
            }
        }
        $response->setResult($data);

        return $response->emit();
    }

    public function deleteRelations(Vtiger_Request $request)
    {
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $relations = $request->get('relations');
        $relationType = $request->get('relation_type');
        foreach ($relations as $rel) {
            $moduleModel = Vtiger_Module_Model::getInstance($rel['tabid']);
            if ($moduleModel) {
                if ($relationType == '1-1') {
                    $fieldModel = Vtiger_Field_Model::getInstance($rel['fieldid'], $moduleModel);
                    if ($fieldModel) {
                        $unsetModuleNames = [$rel['related_tab_name']];
                        $fieldModel->unsetRelatedModules($unsetModuleNames);
                        $fieldModel->delete();
                    }
                } else {
                    if ($relationType == '1-M') {
                        $relatedModuleModel = Vtiger_Module_Model::getInstance($rel['related_tabid']);
                        $moduleModel->unsetRelatedList($relatedModuleModel, $rel['label'], $rel['name']);
                    } else {
                        if ($relationType == 'M-M') {
                            $relatedModuleModel = Vtiger_Module_Model::getInstance($rel['related_tabid']);
                            $moduleModel->unsetRelatedList($relatedModuleModel, $rel['label']);
                        }
                    }
                }
            }
        }
        if ($relationType == '1-None') {
            $linkCreatorRecordModel = new ModuleLinkCreator_SettingRecord_Model();
            $fieldId = $request->get('fieldid');
            $linkCreatorRecordModel->deleteRelationshipOneNone($fieldId);
        }

        return $response->emit();
    }

    public function getSingleModuleName(Vtiger_Request $request)
    {
        $sourceModule = $request->get('sourceModule');
        $SingleModuleName = vtranslate('SINGLE_' . $sourceModule, $sourceModule);
        echo $SingleModuleName;
    }

    public function updateIcon(Vtiger_Request $request)
    {
        global $vtiger_current_version;
        $select_module = $request->get('select_module');
        $icon = $request->get('icon');
        if ($select_module) {
            if (version_compare($vtiger_current_version, '7.0.0', '>=')) {
                $patch = 'layouts/v7';
            } else {
                $patch = 'layouts/vlayout';
            }
            $file = $patch . '/modules/' . $select_module . '/resources/Styles.css';
            if (is_file($file)) {
                $contents = file_get_contents($file);
                $pattern = '/content\\: \\"(.)*\\"\\;/i';
                $replace = 'content: "' . $icon . '";';
                $contents = preg_replace($pattern, $replace, $contents);
                file_put_contents($file, $contents);
            }
        }
    }
}
