<?php

include 'modules/ModuleLinkCreator/ModuleLinkCreator.php';
include 'modules/ModuleLinkCreator/models/ModuleLinkCreatorController.php';

/**
 * Class ModuleLinkCreator_Save_Action.
 */
class ModuleLinkCreator_Save_Action extends Vtiger_Action_Controller
{
    /**
     * @constructor
     */
    public function __construct()
    {
        parent::__construct();
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
        global $adb;
        global $vtiger_current_version;
        $params = [];
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $parent = 'Tools';
        } else {
            $parent = 'Marketing';
        }
        $menu_placement = $request->get('menu_placement');
        if (!empty($menu_placement)) {
            $menu_placement = ucfirst($menu_placement);
            $parent = $menu_placement;
        }
        $base_permissions = $request->get('base_permissions');
        $record = $request->get('record');
        if ($request->has('module_name')) {
            $params['module_name'] = $request->get('module_name');
        }
        if ($request->has('module_label')) {
            $params['module_label'] = $request->get('module_label');
        }
        if ($request->has('singular_module_label')) {
            $params['singular_module_label'] = $request->get('singular_module_label');
        }
        if ($request->has('module_type')) {
            $params['module_type'] = $request->get('module_type');
        }
        if ($request->has('module_fields')) {
            $params['module_fields'] = $request->get('module_fields') ? json_encode($request->get('module_fields')) : null;
        }
        if ($request->has('module_list_view_filter_fields')) {
            $params['module_list_view_filter_fields'] = $request->get('module_list_view_filter_fields') ? json_encode($request->get('module_list_view_filter_fields')) : null;
        }
        if ($request->has('module_summary_fields')) {
            $params['module_summary_fields'] = $request->get('module_summary_fields') ? json_encode($request->get('module_summary_fields')) : null;
        }
        if ($request->has('module_quick_create_fields')) {
            $params['module_quick_create_fields'] = $request->get('module_quick_create_fields') ? json_encode($request->get('module_quick_create_fields')) : null;
        }
        if ($request->has('module_links')) {
            $params['module_links'] = $request->get('module_links') ? json_encode($request->get('module_links')) : null;
        }
        if ($request->has('description')) {
            $params['description'] = $request->get('description');
        }
        $icons = '';
        if ($request->has('data-icon-module')) {
            $icons = $request->get('data-icon-module');
            if (substr($icons, 0, 1) !== '\\') {
                $icons = '\\' . $icons;
            }
        }
        $VTEMobileModel = Vtiger_Module_Model::getInstance('VTEMobile');
        if ($VTEMobileModel && $VTEMobileModel->isActive()) {
            if ($icons == '') {
                $icons = '\\e65c';
            }
            $icoinModule = PHP_EOL . '.vicon-' . strtolower($params['module_name']) . ":before {\r\n                                content: \"" . $icons . "\";\r\n                            }";
            file_put_contents('layouts/v7/modules/VTEMobile/simple/resources/libs/Vtiger-icons/style.css', $icoinModule, FILE_APPEND);
        }
        $valueName = $params['module_name'];
        $firstChar = $valueName[0];
        if (is_numeric($firstChar)) {
            header('Location:index.php?module=ModuleLinkCreator&view=Edit');
        }
        set_time_limit(0);
        $moduleController = new ModuleLinkCreator_ModuleController();
        $module = $moduleController->createModule($params['module_name'], $parent, 'Name', $params['singular_module_label'], $base_permissions);
        if ($module) {
            if (version_compare($vtiger_current_version, '7.0.0', '>=')) {
                Settings_MenuEditor_Module_Model::addModuleToApp($params['module_name'], $parent);
                vtws_addDefaultModuleTypeEntity($params['module_name']);
            }
            $module->initWebservice();
            $baseBlockLabel = 'LBL_' . strtoupper($module->name) . '_INFORMATION';
            $lowerModuleName = strtolower($module->name);
            $tblCrmentity = 'vtiger_crmentity';
            $module_basetable = 'vtiger_' . $lowerModuleName;
            $singular_module_label = $params['singular_module_label'];
            if ($singular_module_label == '') {
                $singular_module_label = $module->label;
            }
            $otherFields = [$module->name => [$baseBlockLabel => [(string) $lowerModuleName . 'no' => ['label' => (string) $singular_module_label . ' No', 'table' => $module_basetable, 'uitype' => 4, 'summaryfield' => 0, 'masseditable' => 0], 'created_user_id' => ['label' => vtranslate('Created By'), 'uitype' => 52, 'columnname' => 'smcreatorid', 'summaryfield' => 0, 'masseditable' => 0, 'table' => $tblCrmentity, 'displaytype' => 2], 'modifiedby' => ['label' => vtranslate('Modified By'), 'uitype' => 52, 'presence' => 0, 'summaryfield' => 0, 'masseditable' => 0, 'table' => $tblCrmentity, 'displaytype' => 2]], 'LBL_DESCRIPTION' => ['description' => ['label' => vtranslate('Description'), 'uitype' => 19, 'table' => $tblCrmentity, 'quickcreate' => 0, 'summaryfield' => 1, 'quickcreatesequence' => 3, 'filter' => ['name' => 'All', 'isdefault' => false]]]]];
            if (version_compare($vtiger_current_version, '7.0.0', '>=')) {
                $otherFields[$module->name][$baseBlockLabel]['tags'] = ['label' => vtranslate('tags'), 'uitype' => 1, 'readonly' => 1, 'presence' => 2, 'summaryfield' => 0, 'masseditable' => 0, 'columntype' => 'varchar(1)', 'table' => $module_basetable, 'typeofdata' => 'V~O', 'quickcreate' => 3, 'displaytype' => 6];
                $tableStarred = 'vtiger_' . strtolower($module->name) . '_user_field';
                if (version_compare($vtiger_current_version, '7.1', '>=')) {
                    $tableStarred = 'vtiger_crmentity_user_field';
                }
                $otherFields[$module->name][$baseBlockLabel]['starred'] = ['label' => vtranslate('starred'), 'uitype' => 56, 'presence' => 2, 'readonly' => 1, 'summaryfield' => 0, 'masseditable' => 0, 'columntype' => 'varchar(1)', 'table' => $tableStarred, 'typeofdata' => 'C~O', 'quickcreate' => '3', 'displaytype' => 6];
                $otherFields[$module->name][$baseBlockLabel]['source'] = ['label' => 'Source', 'uitype' => 1, 'presence' => 2, 'readonly' => 1, 'masseditable' => 0, 'table' => 'vtiger_crmentity', 'typeofdata' => 'V~O', 'quickcreate' => '3', 'displaytype' => 2];
            }
            $moduleController->createFields($module, $otherFields);
            $tableNameDelete = $module->basetable . '_user_field';
            if (version_compare($vtiger_current_version, '7.1', '>=') && version_compare($vtiger_current_version, '7.2.0', '<')) {
                $adb->pquery('DROP table ' . $tableNameDelete);
            }
            $prefix = 'NO';
            if (strlen($module->name) >= 2) {
                $prefix = substr($module->name, 0, 2);
                $prefix = strtoupper($prefix);
            }
            $moduleController->customizeRecordNumbering($module->name, $prefix, 1);
            $languageStrings = [$params['module_name'] => $params['module_label'], 'SINGLE_' . $params['module_name'] => $params['singular_module_label'], (string) $params['module_name'] . ' ID' => (string) $params['singular_module_label'] . ' ID', $baseBlockLabel => (string) $params['singular_module_label'] . ' Information', 'Created Time' => 'Created At', 'Modified Time' => 'Modified At', 'Modified By' => 'Modified By', 'LBL_DESCRIPTION' => 'Description', 'LBL_ADD_RECORD' => 'Add ' . $params['singular_module_label']];
            $jsLanguageStrings = [];
            $languageController = new ModuleLinkCreatorConsole_LanguageController();
            $languageController->createLanguage($params['module_name'], $languageStrings, $jsLanguageStrings);
            $moduleController->createCustomViews($module, $icons);
            $moduleController->createCustomModels($module);
            $commentInstance = Vtiger_Module::getInstance('ModComments');
            $commentRelatedToFieldInstance = Vtiger_Field::getInstance('related_to', $commentInstance);
            if ($commentRelatedToFieldInstance) {
                $commentRelatedToFieldInstance->setRelatedModules([$module->name]);
                if (version_compare($vtiger_current_version, '7.0.0', '>=')) {
                    $module->setRelatedList($commentInstance, 'ModComments', false, 'get_comments');
                }
            }
            $moduleDocuments = Vtiger_Module::getInstance('Documents');
            $module->setRelatedList($moduleDocuments, 'Documents', ['ADD', 'SELECT'], 'get_attachments');
            $moduleCalendar = Vtiger_Module::getInstance('Calendar');
            $parentIDFieldModel = Vtiger_Field_Model::getInstance('parent_id', $moduleCalendar);
            $module->setRelatedList($moduleCalendar, 'Activities', ['ADD'], 'get_activities', $parentIDFieldModel->getId());
            $adb->pquery('INSERT INTO `vtiger_fieldmodulerel` (`fieldid`, `module`, `relmodule`) VALUES (?, ?, ?)', [$parentIDFieldModel->getId(), $params['module_name'], 'Calendar']);
            $moduleEmails = Vtiger_Module::getInstance('Emails');
            $module->setRelatedList($moduleEmails, 'Emails', ['ADD'], 'get_emails');
            $activityFieldTypeId = 34;
            $moduleController->addModuleRelatedToForEvents($module->name, $activityFieldTypeId);
            $result = $adb->pquery('SELECT * FROM com_vtiger_workflow_tasktypes WHERE tasktypename IN (?,?);', ['VTCreateTodoTask', 'VTCreateEventTask']);
            if ($adb->num_rows($result) > 0) {
                while ($rowTask = $adb->fetch_array($result)) {
                    $id = $rowTask['id'];
                    $tasktypename = $rowTask['tasktypename'];
                    $moduleslist = $rowTask['modules'];
                    $modules = Zend_Json::decode(html_entity_decode($moduleslist));
                    $includeModules = $modules['include'];
                    $excludeModules = $modules['exclude'];
                    $includeModules[] = $module->name;
                    $moduleslist = Zend_Json::encode(['include' => $includeModules, 'exclude' => $excludeModules]);
                    $adb->pquery('update `com_vtiger_workflow_tasktypes` set `modules`=? where `id`=? AND `tasktypename`=? LIMIT 1;', [$moduleslist, $id, $tasktypename]);
                }
            }
            require_once 'modules/ModTracker/ModTracker.php';
            ModTracker::enableTrackingForModule($module->id);
            $recordModel = new ModuleLinkCreator_Record_Model();
            $record = $record ? $record : 0;
            $params['module_id'] = $module->getId();
            $recordModel->save($record, $params);
            $settingRecordModel = new ModuleLinkCreator_SettingRecord_Model();
            $settings = [];
            $settingRecordModel->saveByModule($params['module_id'], $settings);
            $sql = "UPDATE `vtiger_field` SET `summaryfield`='1' WHERE fieldname IN ('name','assigned_user_id') AND tabid = (SELECT tabid FROM vtiger_tab WHERE name = '" . $module->name . "');";
            $adb->query($sql);
            $sql1 = "UPDATE `vtiger_field` SET `masseditable`='0',typeofdata='DT~O' WHERE fieldname IN ('createdtime','modifiedtime') AND tabid =?";
            $adb->pquery($sql1, [$module->id]);
            $customBlock = Vtiger_Block_Model::getInstance('LBL_CUSTOM_INFORMATION', $module);
            $customBlockFields = ['created_user_id', 'createdtime', 'modifiedby', 'modifiedtime', 'source'];
            if ($customBlock) {
                $sql2 = "UPDATE `vtiger_field` SET `block`=? WHERE fieldname IN ('created_user_id','createdtime','modifiedby','modifiedtime','source') AND tabid = ?";
                $adb->pquery($sql2, [$customBlock->id, $module->id]);
                $sql3 = 'UPDATE `vtiger_blocks` SET `display_status` = 0, sequence = 5 WHERE `blockid` = ? AND tabid = ?';
                $adb->pquery($sql3, [$customBlock->id, $module->id]);
                $idx = 1;
                foreach ($customBlockFields as $fieldName) {
                    $sql4 = 'UPDATE `vtiger_field` SET `sequence`=? WHERE fieldname = ? AND tabid = ?';
                    $adb->pquery($sql4, [$idx, $fieldName, $module->id]);
                    ++$idx;
                }
            }
        }
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $template_folder = 'layouts/vlayout';
        } else {
            $template_folder = 'layouts/v7';
            $css_widgetType = 'HEADERCSS';
            $css_widgetName = $params['module_name'];
            $css_link = (string) $template_folder . '/modules/' . $params['module_name'] . '/resources/Styles.css';
            if ($module) {
                $module->addLink($css_widgetType, $css_widgetName, $css_link);
            }
        }
        header('Location:index.php?module=ModuleLinkCreator&view=List');
    }
}
