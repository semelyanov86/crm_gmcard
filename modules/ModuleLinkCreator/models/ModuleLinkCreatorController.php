<?php

include_once 'vtlib/tools/console.php';

/**
 * Class ModuleLinkCreatorConsole_Module_Model.
 */
class ModuleLinkCreator_ModuleController extends Vtiger_Tools_Console_ModuleController
{
    /**
     * ModuleLinkCreator_ModuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    protected function createFiles(Vtiger_Module $module, Vtiger_Field $entityField)
    {
        global $vtiger_current_version;
        $targetpath = 'modules/' . $module->name;
        if (!is_file($targetpath)) {
            mkdir($targetpath);
            if (version_compare($vtiger_current_version, '7.0.0', '<')) {
                $templatepath = 'modules/ModuleLinkCreator/resources/ModuleDir/6.0.0';
            } else {
                $templatepath = 'modules/ModuleLinkCreator/resources/ModuleDir/7.0.0';
            }
            $moduleFileContents = file_get_contents($templatepath . '/ModuleName.php');
            $replacevars = ['__ModuleName__' => $module->name, '<modulename>' => strtolower($module->name), '<entityfieldlabel>' => $entityField->label, '<entitycolumn>' => $entityField->column, '<entityfieldname>' => $entityField->name];
            foreach ($replacevars as $key => $value) {
                $moduleFileContents = str_replace($key, $value, $moduleFileContents);
            }
            file_put_contents($targetpath . '/' . $module->name . '.php', $moduleFileContents);
            $helperPath = (string) $targetpath . '/helpers';
            if (!is_file($helperPath)) {
                mkdir($helperPath);
                $helperFileContents = file_get_contents('modules/ModuleLinkCreator/helpers/Util.php');
                file_put_contents($helperPath . '/Util.php', $helperFileContents);
            }
        }
    }

    /**
     * Create new module base on console.
     *
     * @param string $moduleName
     * @param string $parent - Default is "Tools"
     * @param string $entityfieldlabel
     * @return Vtiger_Module
     */
    public function createModule($moduleName, $parent, $entityfieldlabel, $singularModuleLabel, $base_permissions = 0)
    {
        global $vtiger_current_version;
        global $adb;
        if ($parent == 'PROJECT') {
            $parent = 'Support';
        }
        $moduleInformation['name'] = $moduleName;
        $moduleInformation['parent'] = $parent;
        $moduleInformation['entityfieldlabel'] = $entityfieldlabel;
        $moduleInformation['singular_module_label'] = $singularModuleLabel;
        $moduleInformation['base_permissions'] = $base_permissions;
        $this->create($moduleInformation);
        $lcasemodname = strtolower($moduleName);
        $primaryKey = $lcasemodname . 'id';
        $module_basetable = 'vtiger_' . $lcasemodname;
        if (Vtiger_Utils::CheckTable($module_basetable)) {
            $adb->pquery('ALTER TABLE ' . $module_basetable . ' DROP PRIMARY KEY, ADD PRIMARY KEY (' . $primaryKey . ')', []);
        }
        if (!version_compare($vtiger_current_version, '7.0.0', '<')) {
            $moduleUserSpecificTable = Vtiger_Functions::getUserSpecificTableName($moduleName);
            if (!Vtiger_Utils::CheckTable($moduleUserSpecificTable)) {
                Vtiger_Utils::CreateTable($moduleUserSpecificTable, "(`recordid` INT(19) NOT NULL,\r\n\t\t\t\t\t   `userid` INT(19) NOT NULL,\r\n\t\t\t\t\t   Index `record_user_idx` (`recordid`, `userid`)\r\n\t\t\t\t\t\t)", true);
            }
        }

        return Vtiger_Module::getInstance($moduleName);
    }

    public function makeModuleAvailableForAdmin($moduleInstance)
    {
        global $adb;
        $actionids = [];
        $result = $adb->pquery('SELECT actionid from vtiger_actionmapping WHERE actionname IN (?,?,?,?,?,?)', ['Save', 'EditView', 'CreateView', 'Delete', 'index', 'DetailView']);
        for ($index = 0; $index < $adb->num_rows($result); ++$index) {
            $actionids[] = $adb->query_result($result, $index, 'actionid');
        }
        $profileids = [];
        $result = $adb->pquery('SELECT profileid FROM vtiger_profile', []);
        for ($index = 0; $index < $adb->num_rows($result); ++$index) {
            $profileids[] = $adb->query_result($result, $index, 'profileid');
        }
        $adb->pquery('DELETE FROM vtiger_profile2tab WHERE tabid=?', [$moduleInstance->id]);
        $adb->pquery('DELETE FROM vtiger_profile2standardpermissions WHERE tabid=?', [$moduleInstance->id]);
        foreach ($profileids as $profileid) {
            $adb->pquery('INSERT INTO vtiger_profile2tab (profileid, tabid, permissions) VALUES (?,?,?)', [$profileid, $moduleInstance->id, 1]);
            if ($moduleInstance->isentitytype) {
                foreach ($actionids as $actionid) {
                    $adb->pquery('INSERT INTO vtiger_profile2standardpermissions (profileid, tabid, Operation, permissions) VALUES(?,?,?,?)', [$profileid, $moduleInstance->id, $actionid, 1]);
                }
            }
        }
    }

    protected function create($moduleInformation)
    {
        global $vtiger_current_version;
        $moduleInformation['entityfieldname'] = strtolower($this->toAlphaNumeric($moduleInformation['entityfieldlabel']));
        $module = new Vtiger_Module();
        $module->name = $moduleInformation['name'];
        $module->parent = $moduleInformation['parent'];
        $module->save();
        $module->singularModuleLabel = $moduleInformation['singular_module_label'];
        $lcasemodname = strtolower($module->name);
        $basetable = 'vtiger_' . $lcasemodname;
        $basetableid = $lcasemodname . 'id';
        $module->initTables($basetable, $basetableid);
        if ($moduleInformation['base_permissions'] == 1) {
            $this->makeModuleAvailableForAdmin($module);
        }
        $block = new Vtiger_Block();
        $block->label = 'LBL_' . strtoupper($module->name) . '_INFORMATION';
        $module->addBlock($block);
        $blockcf = new Vtiger_Block();
        $blockcf->label = 'LBL_CUSTOM_INFORMATION';
        $module->addBlock($blockcf);
        $field1 = new Vtiger_Field();
        $field1->name = $moduleInformation['entityfieldname'];
        $field1->label = $moduleInformation['entityfieldlabel'];
        $field1->uitype = 2;
        $field1->table = $basetable;
        $field1->column = $field1->name;
        $field1->summaryfield = 1;
        $field1->columntype = 'VARCHAR(255)';
        $field1->typeofdata = 'V~M';
        $block->addField($field1);
        $module->setEntityIdentifier($field1);
        $field2 = new Vtiger_Field();
        $field2->name = 'assigned_user_id';
        $field2->label = 'Assigned To';
        $field2->table = 'vtiger_crmentity';
        $field2->column = 'smownerid';
        $field2->uitype = 53;
        $field2->typeofdata = 'V~M';
        $block->addField($field2);
        $field3 = new Vtiger_Field();
        $field3->name = 'createdtime';
        $field3->label = 'Created Time';
        $field3->table = 'vtiger_crmentity';
        $field3->column = 'createdtime';
        $field3->uitype = 70;
        $field3->typeofdata = 'T~O';
        $field3->displaytype = 2;
        $block->addField($field3);
        $field4 = new Vtiger_Field();
        $field4->name = 'modifiedtime';
        $field4->label = 'Modified Time';
        $field4->table = 'vtiger_crmentity';
        $field4->column = 'modifiedtime';
        $field4->uitype = 70;
        $field4->typeofdata = 'T~O';
        $field4->displaytype = 2;
        $block->addField($field4);
        $filter1 = new Vtiger_Filter();
        $filter1->name = 'All';
        $filter1->isdefault = true;
        $module->addFilter($filter1);
        $filter1->addField($field1)->addField($field2, 1)->addField($field3, 2);
        $module->setDefaultSharing();
        $module->enableTools(['Import', 'Export', 'Merge']);
        $module->initWebservice();
        $this->createFiles($module, $field1);
        if (version_compare($vtiger_current_version, '7.0.0', '>=')) {
            Settings_MenuEditor_Module_Model::addModuleToApp($module->name, $module->parent);
        }
    }

    /**
     * @param array $moduleFields
     *             Example:
     *             $fieldBlocks = array(
     *                  'Applications' => array(                        // module name
     *                      'LBL_APPLICATIONS_INFORMATION' => array(    // block name
     *                          'cf_application_status' => array(        // field name
     *                              'label' => 'Application Status',    // label
     *                              'table' => 'vtiger_applicationscf',    // table
     *                              'uitype' => 16,                        // type
     *                              'picklistvalues' => array('Yes', 'No')    // (option) if uitype is picklist: 15|16|33
     *                          )
     *                      )
     *                  )
     *              );
     */
    public function createFields(Vtiger_Module $module, $moduleFields = [])
    {
        global $adb;
        global $vtiger_current_version;
        foreach ($moduleFields as $moduleName => $blockNames) {
            foreach ($blockNames as $blockName => $arrFieldInfo) {
                $blockInstance = Vtiger_Block::getInstance($blockName, $module);
                if (!$blockInstance) {
                    $blockInstance = new Vtiger_Block();
                    $blockInstance->label = $blockName;
                    $module->addBlock($blockInstance);
                }
                $rsFieldSequence = $adb->pquery('SELECT sequence FROM `vtiger_field` WHERE block = ? ORDER BY sequence DESC LIMIT 0,1', [$blockInstance->id]);
                $sequence = $adb->query_result($rsFieldSequence, 'sequence', 0);
                foreach ($arrFieldInfo as $fieldName => $fieldInfo) {
                    $fieldInstance = Vtiger_Field::getInstance($fieldName, $module);
                    if (!$fieldInstance) {
                        $fieldInstance = new Vtiger_Field();
                    }
                    $fieldInstance->name = $fieldName;
                    $fieldInstance->label = isset($fieldInfo['label']) && $fieldInfo['label'] ? $fieldInfo['label'] : $fieldName;
                    $fieldInstance->uitype = isset($fieldInfo['uitype']) && $fieldInfo['uitype'] ? $fieldInfo['uitype'] : 1;
                    if (isset($fieldInfo['columnname']) && $fieldInfo['columnname']) {
                        $fieldInstance->column = $fieldInfo['columnname'];
                    } else {
                        $fieldInstance->column = $fieldInstance->name;
                    }
                    if (isset($fieldInfo['columntype']) && $fieldInfo['columntype']) {
                        $fieldInstance->columntype = $fieldInfo['columntype'];
                    } else {
                        $fieldInstance->columntype = 'VARCHAR(100)';
                    }
                    if (isset($fieldInfo['typeofdata']) && $fieldInfo['typeofdata']) {
                        $fieldInstance->typeofdata = $fieldInfo['typeofdata'];
                    }
                    if (isset($fieldInfo['table']) && $fieldInfo['table']) {
                        $fieldInstance->table = $fieldInfo['table'];
                        if (version_compare($vtiger_current_version, '7.0.0', '>=') && version_compare($vtiger_current_version, '7.1', '<')) {
                            $this->createTableStarred($moduleName, $fieldInstance->table);
                        }
                    } else {
                        $fieldInstance->table = $module->basetable;
                    }
                    if (isset($fieldInfo['helpinfo'])) {
                        $fieldInstance->helpinfo = $fieldInfo['helpinfo'];
                    }
                    if (isset($fieldInfo['summaryfield'])) {
                        $fieldInstance->summaryfield = $fieldInfo['summaryfield'];
                    }
                    if (isset($fieldInfo['masseditable'])) {
                        $fieldInstance->masseditable = $fieldInfo['masseditable'];
                    }
                    if (isset($fieldInfo['displaytype'])) {
                        $fieldInstance->displaytype = $fieldInfo['displaytype'];
                    }
                    if (isset($fieldInfo['generatedtype'])) {
                        $fieldInstance->generatedtype = $fieldInfo['generatedtype'];
                    }
                    if (isset($fieldInfo['readonly'])) {
                        $fieldInstance->readonly = $fieldInfo['readonly'];
                    }
                    if (isset($fieldInfo['presence'])) {
                        $fieldInstance->presence = $fieldInfo['presence'];
                    }
                    if (isset($fieldInfo['defaultvalue'])) {
                        $fieldInstance->defaultvalue = $fieldInfo['defaultvalue'];
                    }
                    if (isset($fieldInfo['maximumlength'])) {
                        $fieldInstance->maximumlength = $fieldInfo['maximumlength'];
                    }
                    if (isset($fieldInfo['quickcreate'])) {
                        $fieldInstance->quickcreate = $fieldInfo['quickcreate'];
                    }
                    if (isset($fieldInfo['quickcreatesequence'])) {
                        $fieldInstance->quicksequence = $fieldInfo['quickcreatesequence'];
                    }
                    if (isset($fieldInfo['info_type'])) {
                        $fieldInstance->info_type = $fieldInfo['info_type'];
                    }
                    $fieldInstance->sequence = ++$sequence;
                    $blockInstance->addField($fieldInstance);
                    if ($fieldInfo['uitype'] == 15 || $fieldInfo['uitype'] == 16 || $fieldInfo['uitype'] == 33) {
                        $fieldInstance->setPicklistValues($fieldInfo['picklistvalues']);
                    } else {
                        if ($fieldInfo['uitype'] == 10) {
                            $fieldInstance->setRelatedModules([$fieldInfo['related_to_module']]);
                        }
                    }
                    if (isset($fieldInfo['filter']) && $fieldInfo['filter'] && isset($fieldInfo['filter']['name']) && $fieldInfo['filter']['name']) {
                        $filterName = $fieldInfo['filter']['name'];
                        $filterInstance = Vtiger_Filter::getInstance($filterName, $module);
                        if (!$filterInstance) {
                            $filterInstance = new Vtiger_Filter();
                            $filterInstance->name = $filterName;
                            $filterInstance->isdefault = $fieldInfo['filter']['isdefault'] ? $fieldInfo['filter']['isdefault'] : false;
                            $module->addFilter($filterInstance);
                            $filterInstance->addField($fieldInstance);
                        } else {
                            $rsFieldSequence = $adb->pquery('SELECT columnindex FROM `vtiger_cvcolumnlist` WHERE cvid = ? ORDER BY columnindex DESC LIMIT 0,1', [$filterInstance->id]);
                            $sequence = $adb->query_result($rsFieldSequence, 'columnindex', 0);
                            $filterInstance->addField($fieldInstance, $sequence + 1);
                        }
                    }
                }
            }
        }
    }

    public function createCustomViews(Vtiger_Module $module, $icons = '')
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $tempDir = 'vlayout';
            $version = '6.0.0';
        } else {
            $tempDir = 'v7';
            $version = '7.0.0';
        }
        $targetpath = 'modules/' . $module->name . '/views';
        if (!is_file($targetpath)) {
            mkdir($targetpath);
        }
        $files = [];
        $this->findFiles('modules/ModuleLinkCreator/resources/ModuleDir/' . $version . '/views', false, $files);
        foreach ($files as $file) {
            $filename = basename($file, true);
            $moduleFileContents = file_get_contents($file);
            $replacevars = ['__ModuleName__' => $module->name, '<modulename>' => strtolower($module->name)];
            foreach ($replacevars as $key => $value) {
                $moduleFileContents = str_replace($key, $value, $moduleFileContents);
            }
            file_put_contents((string) $targetpath . '/' . $filename, $moduleFileContents);
        }
        $moduleLayout = 'layouts/' . $tempDir . '/modules/' . $module->name;
        if (!is_file($moduleLayout)) {
            mkdir($moduleLayout);
        }
        $files = [];
        $this->findOnlyDirFiles('modules/ModuleLinkCreator/resources/ModuleDir/' . $version . '/templates', false, $files);
        foreach ($files as $file) {
            $filename = basename($file, true);
            $moduleFileContents = file_get_contents($file);
            file_put_contents((string) $moduleLayout . '/' . $filename, $moduleFileContents);
        }
        $moduleLayoutResources = 'layouts/' . $tempDir . '/modules/' . $module->name . '/resources';
        if (!is_file($moduleLayoutResources)) {
            mkdir($moduleLayoutResources);
        }
        $files = [];
        $this->findFiles('modules/ModuleLinkCreator/resources/ModuleDir/' . $version . '/templates/resources', false, $files);
        foreach ($files as $file) {
            $filename = basename($file, true);
            $moduleFileContents = file_get_contents($file);
            $replacevars = ['<modulename>' => $module->name, 'moduleNameIcon' => strtolower($module->name), 'content-icon-module' => $icons];
            foreach ($replacevars as $key => $value) {
                $moduleFileContents = str_replace($key, $value, $moduleFileContents);
            }
            file_put_contents((string) $moduleLayoutResources . '/' . $filename, $moduleFileContents);
        }
    }

    public function createCustomModels(Vtiger_Module $module)
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $version = '6.0.0';
        } else {
            $version = '7.0.0';
        }
        $targetpath = 'modules/' . $module->name . '/models';
        if (!is_file($targetpath)) {
            mkdir($targetpath);
        }
        $files = [];
        $this->findFiles('modules/ModuleLinkCreator/resources/ModuleDir/' . $version . '/models', false, $files);
        foreach ($files as $file) {
            $filename = basename($file, true);
            $moduleFileContents = file_get_contents($file);
            $replacevars = ['__ModuleName__' => $module->name, '<modulename>' => strtolower($module->name)];
            foreach ($replacevars as $key => $value) {
                $moduleFileContents = str_replace($key, $value, $moduleFileContents);
            }
            file_put_contents((string) $targetpath . '/' . $filename, $moduleFileContents);
        }
    }

    /**
     * @param string $moduleName
     * @param array $tables
     * @return bool
     */
    public function cleanDatabase($moduleName, $tables = [])
    {
        global $adb;
        foreach ($tables as $table) {
            $adb->pquery('DROP TABLE ?', [$table]);
        }
        $adb->pquery('DELETE FROM `vtiger_crmentity`  WHERE setype=?', [$moduleName]);
        $adb->pquery('DELETE a.* FROM  `vtiger_blocks` a INNER JOIN `vtiger_tab` b ON a.tabid = b.tabid WHERE b.name =? ', [$moduleName]);

        return true;
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public function cleanFolder($moduleName)
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $tempDir = 'vlayout';
        } else {
            $tempDir = 'v7';
        }
        $this->removeFolder('layouts/' . $tempDir . '/modules/' . $moduleName);
        $this->removeFolder('modules/' . $moduleName);

        return true;
    }

    /**
     * @return bool
     */
    public function removeFolder($path)
    {
        if (!isFileAccessible($path) || !is_dir($path)) {
            return false;
        }
        if (!is_writeable($path)) {
            chmod($path, 511);
        }
        $handle = opendir($path);

        while ($tmp = readdir($handle)) {
            if ($tmp == '..' || $tmp == '.') {
                continue;
            }
            $tmpPath = $path . DS . $tmp;
            if (is_file($tmpPath)) {
                if (!is_writeable($tmpPath)) {
                    chmod($tmpPath, 438);
                }
                unlink($tmpPath);
            } else {
                if (is_dir($tmpPath)) {
                    if (!is_writeable($tmpPath)) {
                        chmod($tmpPath, 511);
                    }
                    $this->removeFolder($tmpPath);
                }
            }
        }
        closedir($handle);
        rmdir($path);

        return !is_dir($path);
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public function cleanLanguage($moduleName)
    {
        $files = glob('languages/*/' . $moduleName . '.php');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * @see http://stackoverflow.com/questions/7288029/php-delete-directory-that-is-not-empty
     */
    public function rmdir_recursive($dir)
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $tmpFile = (string) $dir . '/' . $file;
            if (is_dir($tmpFile)) {
                $this->rmdir_recursive($tmpFile);
            } else {
                unlink($tmpFile);
            }
        }
        rmdir($dir);
    }

    /**
     * @param string $moduleName
     * @return bool
     */
    public function uninstallModule($moduleName)
    {
        global $adb;
        $module = Vtiger_Module::getInstance($moduleName);
        $rs = $adb->pquery('SELECT * FROM `vtiger_fieldmodulerel` WHERE `relmodule` =?', [$moduleName]);
        $listFieldModule = [];
        if ($adb->num_rows($rs) > 0) {
            while ($row = $adb->fetchByAssoc($rs)) {
                if ($row['module'] != 'ModComments') {
                    $listFieldModule[] = $row['fieldid'];
                }
            }
        }
        foreach ($listFieldModule as $field) {
            $adb->pquery("UPDATE `vtiger_field` SET `presence`='1' WHERE `fieldid`=?", [$field]);
            $adb->pquery('DELETE FROM `vtiger_fieldmodulerel` WHERE `fieldid`=?', [$field]);
        }
        if ($module) {
            $lowerModuleName = strtolower($module->name);
            $module->delete();
            $tables = [];
            $tables[] = $lowerModuleName;
            $tables[] = $lowerModuleName . 'cf';
            $this->cleanDatabase($moduleName, $tables);
            $this->cleanFolder($moduleName);
            $this->cleanLanguage($moduleName);
            $this->removeHandle($moduleName);
        }

        return true;
    }

    /**
     * @param string $sourceModule
     * @param string $prefix
     * @param int $sequenceNumber
     * @return array
     */
    public function customizeRecordNumbering($sourceModule, $prefix = 'NO', $sequenceNumber = 1)
    {
        $moduleModel = Settings_Vtiger_CustomRecordNumberingModule_Model::getInstance($sourceModule);
        $moduleModel->set('prefix', $prefix);
        $moduleModel->set('sequenceNumber', $sequenceNumber);
        $result = $moduleModel->setModuleSequence();

        return $result;
    }

    /**
     * @param string $moduleName
     * @param int $fieldTypeId
     */
    public function addModuleRelatedToForEvents($moduleName, $fieldTypeId)
    {
        global $adb;
        $sqlCheckProject = 'SELECT * FROM `vtiger_ws_referencetype` WHERE fieldtypeid = ? AND type = ?';
        $rsCheckProject = $adb->pquery($sqlCheckProject, [$fieldTypeId, $moduleName]);
        if ($adb->num_rows($rsCheckProject) < 1) {
            $adb->pquery('INSERT INTO `vtiger_ws_referencetype` (`fieldtypeid`, `type`) VALUES (?, ?)', [$fieldTypeId, $moduleName]);
        }
    }

    private function findOnlyDirFiles($dir, $file_pattern, &$files)
    {
        $items = glob($dir . '/*.tpl', GLOB_NOSORT);
        foreach ($items as $item) {
            if (is_file($item)) {
                if (!$file_pattern || preg_match('/' . $file_pattern . '/', $item)) {
                    $files[] = $item;
                }
            } else {
                if (is_dir($item) && $dir != $item) {
                    $this->findFiles($item, $file_pattern, $files);
                }
            }
        }
    }

    /**
     * @param string $moduleName
     */
    private function removeHandle($moduleName)
    {
        include_once 'include/events/VTEventsManager.inc';
        global $adb;
        $em = new VTEventsManager($adb);
        $em->unregisterHandler((string) $moduleName . 'Handler');
    }

    public function createTableStarred($moduleName, $tablename)
    {
        global $adb;
        $fieldKeyName = strtolower($moduleName) . 'id';
        if (!Vtiger_Utils::CheckTable($tablename)) {
            $sql = 'CREATE TABLE `' . $tablename . "`  (\r\n                      `recordid` int(25) NOT NULL,\r\n                      `userid` int(25) NOT NULL,\r\n                      `starred` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,\r\n                      INDEX `fk_" . $fieldKeyName . '_' . $tablename . "`(`recordid`) USING BTREE,\r\n                      CONSTRAINT `fk_" . $fieldKeyName . '_' . $tablename . '` FOREIGN KEY (`recordid`) REFERENCES `vtiger_{strtolower(' . $moduleName . ')}` (`' . $fieldKeyName . "`) ON DELETE CASCADE ON UPDATE RESTRICT\r\n                    ) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;";
            $adb->pquery($sql, []);
        }
    }
}
/**
 * Class ModuleLinkCreatorConsole_LanguageController.
 */
class ModuleLinkCreatorConsole_LanguageController extends Vtiger_Tools_Console_LanguageController
{
    /**
     * ModuleLinkCreatorConsole_LanguageController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $moduleName
     * @param array $languageStrings
     * @param array $jsLanguageStrings
     */
    public function createLanguage($moduleName, $languageStrings = [], $jsLanguageStrings = [])
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $version = '6.0.0';
        } else {
            $version = '7.0.0';
        }
        $baseLanguageStrings = ['LBL_CUSTOM_INFORMATION' => 'Custom Information'];
        $baseJsLanguageStrings = [];
        if ($languageStrings && !empty($languageStrings)) {
            $baseLanguageStrings = array_merge($baseLanguageStrings, $languageStrings);
        }
        if ($jsLanguageStrings && !empty($jsLanguageStrings)) {
            $baseJsLanguageStrings = array_merge($baseJsLanguageStrings, $jsLanguageStrings);
        }
        $files = [];
        $this->findFiles('modules/ModuleLinkCreator/resources/ModuleDir/' . $version . '/languages', '.php$', $files);
        foreach ($files as $file) {
            $filename = basename($file, true);
            $dir = substr($file, 0, strpos($file, $filename));
            $tmp = explode('/', rtrim($dir, '/'));
            $code = $tmp[count($tmp) - 1];
            $newDir = 'languages/' . $code;
            if (!file_exists($newDir)) {
                mkdir($newDir);
            }
            $contents = file_get_contents($file);
            $contents = str_replace("'<languageStrings>'", var_export($baseLanguageStrings, true), $contents);
            $contents = str_replace("'<jsLanguageStrings>'", var_export($baseJsLanguageStrings, true), $contents);
            file_put_contents((string) $newDir . '/' . $moduleName . '.php', $contents);
        }
    }
}
