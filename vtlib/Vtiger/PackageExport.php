<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'vtlib/Vtiger/Module.php';
include_once 'vtlib/Vtiger/Menu.php';
include_once 'vtlib/Vtiger/Event.php';
include_once 'vtlib/Vtiger/Zip.php';
include_once 'vtlib/Vtiger/Cron.php';
/**
 * Provides API to package vtiger CRM module and associated files.
 */
class Vtiger_PackageExport
{
    public $_export_tmpdir = 'test/vtlib';

    public $_export_modulexml_filename;

    public $_export_modulexml_file;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (is_dir($this->_export_tmpdir) === false) {
            mkdir($this->_export_tmpdir);
        }
    }

    public function Vtiger_PackageExport()
    {
        self::__construct();
    }

    /** Output Handlers */
    public function openNode($node, $delimiter = "\n")
    {
        $this->__write("<{$node}>{$delimiter}");
    }


    public function closeNode($node, $delimiter = "\n")
    {
        $this->__write("</{$node}>{$delimiter}");
    }


    public function outputNode($value, $node = '')
    {
        if ($node != '') {
            $this->openNode($node, '');
        }
        $this->__write($value);
        if ($node != '') {
            $this->closeNode($node);
        }
    }


    public function __write($value)
    {
        fwrite($this->_export_modulexml_file, $value);
    }

    /**
     * Set the module.xml file path for this export and
     * return its temporary path.
     */
    public function __getManifestFilePath()
    {
        if (empty($this->_export_modulexml_filename)) {
            // Set the module xml filename to be written for exporting.
            $this->_export_modulexml_filename = 'manifest-' . time() . '.xml';
        }

        return "{$this->_export_tmpdir}/{$this->_export_modulexml_filename}";
    }

    /**
     * Initialize Export.
     */
    public function __initExport($module, $moduleInstance)
    {
        if ($moduleInstance->isentitytype) {
            // We will be including the file, so do a security check.
            Vtiger_Utils::checkFileAccessForInclusion("modules/{$module}/{$module}.php");
        }
        $this->_export_modulexml_file = fopen($this->__getManifestFilePath(), 'w');
        $this->__write("<?xml version='1.0'?>\n");
    }

    /**
     * Post export work.
     */
    public function __finishExport()
    {
        if (!empty($this->_export_modulexml_file)) {
            fclose($this->_export_modulexml_file);
            $this->_export_modulexml_file = null;
        }
    }

    /**
     * Clean up the temporary files created.
     */
    public function __cleanupExport()
    {
        if (!empty($this->_export_modulexml_filename)) {
            unlink($this->__getManifestFilePath());
        }
    }

    /**
     * Export Module as a zip file.
     * @param Vtiger_Module Instance of module
     * @param Path Output directory path
     * @param string Zipfilename to use
     * @param bool True for sending the output as download
     */
    public function export($moduleInstance, $todir = '', $zipfilename = '', $directDownload = false, $extra = false)
    {

        $module = $moduleInstance->name;

        $this->__initExport($module, $moduleInstance);

        // Call module export function
        $this->export_Module($moduleInstance);

        $this->__finishExport();

        // Export as Zip
        if ($zipfilename == '') {
            $zipfilename = "{$module}-" . date('YmdHis') . '.zip';
        }
        $zipfilename = "{$this->_export_tmpdir}/{$zipfilename}";

        $zip = new Vtiger_Zip($zipfilename);

        // Add manifest file
        $zip->addFile($this->__getManifestFilePath(), 'manifest.xml');

        // Copy module directory
        $zip->copyDirectoryFromDisk("modules/{$module}");

        // Copy Settings/module directory
        if (is_dir("modules/Settings/{$module}")) {
            $zip->copyDirectoryFromDisk("modules/Settings/{$module}", 'settings/');
        }

        // Copy cron files of the module (if any)
        if (is_dir("cron/modules/{$module}")) {
            $zip->copyDirectoryFromDisk("cron/modules/{$module}", 'cron');
        }

        // Copy module templates files
        if (is_dir("layouts/vlayout/modules/{$module}")) {
            $zip->copyDirectoryFromDisk("layouts/vlayout/modules/{$module}", "layouts/vlayout/modules/{$module}");
        }

        // Copy Settings module templates files, if any
        if (is_dir("layouts/vlayout/modules/Settings/{$module}")) {
            $zip->copyDirectoryFromDisk("layouts/vlayout/modules/Settings/{$module}", "layouts/vlayout/modules/Settings/{$module}");
        }

        if (is_dir("layouts/vlayout/skins/images/{$module}")) {
            $zip->copyDirectoryFromDisk("layouts/vlayout/skins/images/{$module}", 'images');
        }

        if (is_dir("layouts/v7/modules/{$module}")) {
            $zip->copyDirectoryFromDisk("layouts/v7/modules/{$module}", "layouts/v7/modules/{$module}");
        }

        if (is_dir("layouts/v7/modules/Settings/{$module}")) {
            $zip->copyDirectoryFromDisk("layouts/v7/modules/Settings/{$module}", "layouts/v7/modules/Settings/{$module}");
        }

        // Copy language files
        $this->__copyLanguageFiles($zip, $module);

        $zip->save();

        if ($todir) {
            copy($zipfilename, $todir);
        }

        if ($directDownload) {
            $zip->forceDownload($zipfilename);
            unlink($zipfilename);
        }
        $this->__cleanupExport();
    }

    /**
     * Function copies language files to zip.
     * @param <Vtiger_Zip> $zip
     * @param <String> $module
     */
    public function __copyLanguageFiles($zip, $module)
    {
        $languageFolder = 'languages';
        if ($dir = @opendir($languageFolder)) {		// open languages folder
            while (($langName = readdir($dir)) !== false) {
                if ($langName != '..' && $langName != '.' && is_dir($languageFolder . '/' . $langName)) {
                    $langDir = @opendir($languageFolder . '/' . $langName);		// open languages/en_us folder

                    while (($moduleLangFile = readdir($langDir))  !== false) {
                        $langFilePath = $languageFolder . '/' . $langName . '/' . $moduleLangFile;
                        if (is_file($langFilePath) && $moduleLangFile === $module . '.php') {	// check if languages/en_us/module.php file exists
                            $zip->copyFileFromDisk($languageFolder . '/' . $langName . '/', $languageFolder . '/' . $langName . '/', $moduleLangFile);
                        } elseif (is_dir($langFilePath) && $moduleLangFile == 'Settings') {
                            $settingsLangDir = @opendir($langFilePath);

                            while ($settingLangFileName = readdir($settingsLangDir)) {
                                $settingsLangFilePath = $languageFolder . '/' . $langName . '/' . $moduleLangFile . '/' . $settingLangFileName;
                                if (is_file($settingsLangFilePath) && $settingLangFileName === $module . '.php') {		// check if languages/en_us/Settings/module.php file exists
                                    $zip->copyFileFromDisk(
                                        $languageFolder . '/' . $langName . '/' . $moduleLangFile . '/',
                                        $languageFolder . '/' . $langName . '/' . $moduleLangFile . '/',
                                        $settingLangFileName,
                                    );
                                }
                            }
                            closedir($settingsLangDir);
                        }
                    }
                    closedir($langDir);
                }
            }
            closedir($dir);
        }
    }

    /**
     * Export vtiger dependencies.
     */
    public function export_Dependencies($moduleInstance)
    {
        global $vtiger_current_version, $adb;
        $moduleid = $moduleInstance->id;

        $sqlresult = $adb->pquery('SELECT * FROM vtiger_tab_info WHERE tabid = ?', [$moduleid]);
        $vtigerMinVersion = $vtiger_current_version;
        $vtigerMaxVersion = false;
        $noOfPreferences = $adb->num_rows($sqlresult);
        for ($i = 0; $i < $noOfPreferences; ++$i) {
            $prefName = $adb->query_result($sqlresult, $i, 'prefname');
            $prefValue = $adb->query_result($sqlresult, $i, 'prefvalue');
            if ($prefName == 'vtiger_min_version') {
                $vtigerMinVersion = $prefValue;
            }
            if ($prefName == 'vtiger_max_version') {
                $vtigerMaxVersion = $prefValue;
            }

        }

        $this->openNode('dependencies');
        $this->outputNode($vtigerMinVersion, 'vtiger_version');
        if ($vtigerMaxVersion !== false) {
            $this->outputNode($vtigerMaxVersion, 'vtiger_max_version');
        }
        $this->closeNode('dependencies');
    }

    /**
     * Export Module Handler.
     */
    public function export_Module($moduleInstance)
    {
        global $adb;

        $moduleid = $moduleInstance->id;
        $parent_name = '';

        $sqlresult = $adb->pquery('SELECT * FROM vtiger_parenttabrel WHERE tabid = ?', [$moduleid]);
        $parenttabid = $adb->query_result($sqlresult, 0, 'parenttabid');
        $menu = Vtiger_Menu::getInstance($parenttabid);
        if ($menu) {
            $parent_name = $menu->label;
        } else {
            $sqlresult = $adb->pquery('SELECT * FROM vtiger_app2tab WHERE tabid = ? LIMIT 1', [$moduleid]);
            if ($adb->num_rows($sqlresult)) {
                $parent_name = $adb->query_result($sqlresult, 0, 'appname');
            }
        }

        $sqlresult = $adb->pquery('SELECT * FROM vtiger_tab WHERE tabid = ?', [$moduleid]);
        $tabresultrow = $adb->fetch_array($sqlresult);

        $tabname = $tabresultrow['name'];
        $tablabel = $tabresultrow['tablabel'];
        $tabversion = $tabresultrow['version'] ?? false;

        $this->openNode('module');
        $this->outputNode(date('Y-m-d H:i:s'), 'exporttime');
        $this->outputNode($tabname, 'name');
        $this->outputNode($tablabel, 'label');
        if ($parent_name) {
            $this->outputNode($parent_name, 'parent');
        }

        if (!$moduleInstance->isentitytype) {
            $this->outputNode('extension', 'type');
        }

        if ($tabversion) {
            $this->outputNode($tabversion, 'version');
        }

        // Export dependency information
        $this->export_Dependencies($moduleInstance);

        // Export module tables
        $this->export_Tables($moduleInstance);

        // Export module blocks
        $this->export_Blocks($moduleInstance);

        // Export module filters
        $this->export_CustomViews($moduleInstance);

        // Export Sharing Access
        $this->export_SharingAccess($moduleInstance);

        // Export Events
        $this->export_Events($moduleInstance);

        // Export Actions
        $this->export_Actions($moduleInstance);

        // Export Related Lists
        $this->export_RelatedLists($moduleInstance);

        // Export Custom Links
        $this->export_CustomLinks($moduleInstance);

        // Export cronTasks
        $this->export_CronTasks($moduleInstance);

        $this->closeNode('module');
    }

    /**
     * Export module base and related tables.
     */
    public function export_Tables($moduleInstance)
    {

        $_exportedTables = [];

        $modulename = $moduleInstance->name;

        $this->openNode('tables');

        if ($moduleInstance->isentitytype) {
            $focus = CRMEntity::getInstance($modulename);

            // Setup required module variables which is need for vtlib API's
            vtlib_setup_modulevars($modulename, $focus);

            $tables =  [$focus->table_name];
            if (!empty($focus->groupTable)) {
                $tables[] = $focus->groupTable[0];
            }
            if (!empty($focus->customFieldTable)) {
                $tables[] = $focus->customFieldTable[0];
            }

            foreach ($tables as $table) {
                $this->openNode('table');
                $this->outputNode($table, 'name');
                $this->outputNode('<![CDATA[' . Vtiger_Utils::CreateTableSql($table) . ']]>', 'sql');
                $this->closeNode('table');

                $_exportedTables[] = $table;
            }

        }

        // Now export table information recorded in schema file
        if (file_exists("modules/{$modulename}/schema.xml")) {
            $schema = simplexml_load_file("modules/{$modulename}/schema.xml");

            if (!empty($schema->tables) && !empty($schema->tables->table)) {
                foreach ($schema->tables->table as $tablenode) {
                    $table = trim($tablenode->name);
                    if (!in_array($table, $_exportedTables)) {
                        $this->openNode('table');
                        $this->outputNode($table, 'name');
                        $this->outputNode('<![CDATA[' . Vtiger_Utils::CreateTableSql($table) . ']]>', 'sql');
                        $this->closeNode('table');

                        $_exportedTables[] = $table;
                    }
                }
            }
        }
        $this->closeNode('tables');
    }

    /**
     * Export module blocks with its related fields.
     */
    public function export_Blocks($moduleInstance)
    {
        global $adb;
        $sqlresult = $adb->pquery('SELECT * FROM vtiger_blocks WHERE tabid = ?', [$moduleInstance->id]);
        $resultrows = $adb->num_rows($sqlresult);

        if (empty($resultrows)) {
            return;
        }

        $this->openNode('blocks');
        for ($index = 0; $index < $resultrows; ++$index) {
            $blockid    = $adb->query_result($sqlresult, $index, 'blockid');
            $blocklabel = $adb->query_result($sqlresult, $index, 'blocklabel');

            $this->openNode('block');
            $this->outputNode($blocklabel, 'label');
            // Export fields associated with the block
            $this->export_Fields($moduleInstance, $blockid);
            $this->closeNode('block');
        }
        $this->closeNode('blocks');
    }

    /**
     * Export fields related to a module block.
     */
    public function export_Fields($moduleInstance, $blockid)
    {
        global $adb;

        $fieldresult = $adb->pquery('SELECT * FROM vtiger_field WHERE tabid=? AND block=?', [$moduleInstance->id, $blockid]);
        $fieldcount = $adb->num_rows($fieldresult);

        if (empty($fieldcount)) {
            return;
        }

        $entityresult = $adb->pquery('SELECT * FROM vtiger_entityname WHERE tabid=?', [$moduleInstance->id]);
        $entity_fieldname = $adb->query_result($entityresult, 0, 'fieldname');

        $this->openNode('fields');
        for ($index = 0; $index < $fieldcount; ++$index) {
            $this->openNode('field');
            $fieldresultrow = $adb->fetch_row($fieldresult);

            $fieldname = $fieldresultrow['fieldname'];
            $uitype = $fieldresultrow['uitype'];
            $fieldid = $fieldresultrow['fieldid'];

            $this->outputNode($fieldname, 'fieldname');
            $this->outputNode($uitype, 'uitype');
            $this->outputNode($fieldresultrow['columnname'], 'columnname');
            $this->outputNode($fieldresultrow['tablename'], 'tablename');
            $this->outputNode($fieldresultrow['generatedtype'], 'generatedtype');
            $this->outputNode($fieldresultrow['fieldlabel'], 'fieldlabel');
            $this->outputNode($fieldresultrow['readonly'], 'readonly');
            $this->outputNode($fieldresultrow['presence'], 'presence');
            $this->outputNode($fieldresultrow['defaultvalue'], 'defaultvalue');
            $this->outputNode($fieldresultrow['sequence'], 'sequence');
            $this->outputNode($fieldresultrow['maximumlength'], 'maximumlength');
            $this->outputNode($fieldresultrow['typeofdata'], 'typeofdata');
            $this->outputNode($fieldresultrow['quickcreate'], 'quickcreate');
            $this->outputNode($fieldresultrow['quickcreatesequence'], 'quickcreatesequence');
            $this->outputNode($fieldresultrow['displaytype'], 'displaytype');
            $this->outputNode($fieldresultrow['info_type'], 'info_type');
            $this->outputNode('<![CDATA[' . $fieldresultrow['helpinfo'] . ']]>', 'helpinfo');
            if (isset($fieldresultrow['masseditable'])) {
                $this->outputNode($fieldresultrow['masseditable'], 'masseditable');
            }
            if (isset($fieldresultrow['summaryfield'])) {
                $this->outputNode($fieldresultrow['summaryfield'], 'summaryfield');
            }
            // Export Entity Identifier Information
            if ($fieldname == $entity_fieldname) {
                $this->openNode('entityidentifier');
                $this->outputNode($adb->query_result($entityresult, 0, 'entityidfield'), 'entityidfield');
                $this->outputNode($adb->query_result($entityresult, 0, 'entityidcolumn'), 'entityidcolumn');
                $this->closeNode('entityidentifier');
            }
            $restrictedPicklist = ['hdnTaxType', 'region_id'];
            // Export picklist values for picklist fields
            if ($uitype == '15' || $uitype == '16' || $uitype == '111' || $uitype == '33' || $uitype == '55') {
                if ($uitype == '16') {
                    if (!in_array($fieldname, $restrictedPicklist)) {
                        $picklistvalues = vtlib_getPicklistValues($fieldname);
                    } else {
                        $this->openNode('picklistvalues');
                        $this->closeNode('picklistvalues');
                        $this->closeNode('field');

                        continue;
                    }
                } else {
                    $picklistvalues = vtlib_getPicklistValues_AccessibleToAll($fieldname);
                }
                $this->openNode('picklistvalues');
                foreach ($picklistvalues as $picklistvalue) {
                    $this->outputNode($picklistvalue, 'picklistvalue');
                }
                $this->closeNode('picklistvalues');
            }

            // Export field to module relations
            if ($uitype == '10') {
                $relatedmodres = $adb->pquery('SELECT * FROM vtiger_fieldmodulerel WHERE fieldid=?', [$fieldid]);
                $relatedmodcount = $adb->num_rows($relatedmodres);
                if ($relatedmodcount) {
                    $this->openNode('relatedmodules');
                    for ($relmodidx = 0; $relmodidx < $relatedmodcount; ++$relmodidx) {
                        $this->outputNode($adb->query_result($relatedmodres, $relmodidx, 'relmodule'), 'relatedmodule');
                    }
                    $this->closeNode('relatedmodules');
                }
            }

            $this->closeNode('field');

        }
        $this->closeNode('fields');
    }

    /**
     * Export Custom views of the module.
     */
    public function export_CustomViews($moduleInstance)
    {
        global $adb;

        $customviewres = $adb->pquery('SELECT * FROM vtiger_customview WHERE entitytype = ?', [$moduleInstance->name]);
        $customviewcount = $adb->num_rows($customviewres);

        if (empty($customviewcount)) {
            return;
        }

        $this->openNode('customviews');
        for ($cvindex = 0; $cvindex < $customviewcount; ++$cvindex) {

            $cvid = $adb->query_result($customviewres, $cvindex, 'cvid');

            $cvcolumnres = $adb->pquery('SELECT * FROM vtiger_cvcolumnlist WHERE cvid=?', [$cvid]);
            $cvcolumncount = $adb->num_rows($cvcolumnres);

            $this->openNode('customview');

            $setdefault = $adb->query_result($customviewres, $cvindex, 'setdefault');
            $setdefault = ($setdefault == 1) ? 'true' : 'false';

            $setmetrics = $adb->query_result($customviewres, $cvindex, 'setmetrics');
            $setmetrics = ($setmetrics == 1) ? 'true' : 'false';

            $this->outputNode($adb->query_result($customviewres, $cvindex, 'viewname'), 'viewname');
            $this->outputNode($setdefault, 'setdefault');
            $this->outputNode($setmetrics, 'setmetrics');

            $this->openNode('fields');
            for ($index = 0; $index < $cvcolumncount; ++$index) {
                $cvcolumnindex = $adb->query_result($cvcolumnres, $index, 'columnindex');
                $cvcolumnname = $adb->query_result($cvcolumnres, $index, 'columnname');
                $cvcolumnnames = explode(':', $cvcolumnname);
                $cvfieldname = $cvcolumnnames[2];

                $this->openNode('field');
                $this->outputNode($cvfieldname, 'fieldname');
                $this->outputNode($cvcolumnindex, 'columnindex');

                $cvcolumnruleres = $adb->pquery(
                    'SELECT * FROM vtiger_cvadvfilter WHERE cvid=? AND columnname=?',
                    [$cvid, $cvcolumnname],
                );
                $cvcolumnrulecount = $adb->num_rows($cvcolumnruleres);

                if ($cvcolumnrulecount) {
                    $this->openNode('rules');
                    for ($rindex = 0; $rindex < $cvcolumnrulecount; ++$rindex) {
                        $cvcolumnruleindex = $adb->query_result($cvcolumnruleres, $rindex, 'columnindex');
                        $cvcolumnrulecomp  = $adb->query_result($cvcolumnruleres, $rindex, 'comparator');
                        $cvcolumnrulevalue = $adb->query_result($cvcolumnruleres, $rindex, 'value');
                        $cvcolumnrulecomp  = Vtiger_Filter::translateComparator($cvcolumnrulecomp, true);

                        $this->openNode('rule');
                        $this->outputNode($cvcolumnruleindex, 'columnindex');
                        $this->outputNode($cvcolumnrulecomp, 'comparator');
                        $this->outputNode($cvcolumnrulevalue, 'value');
                        $this->closeNode('rule');

                    }
                    $this->closeNode('rules');
                }

                $this->closeNode('field');
            }
            $this->closeNode('fields');

            $this->closeNode('customview');
        }
        $this->closeNode('customviews');
    }

    /**
     * Export Sharing Access of the module.
     */
    public function export_SharingAccess($moduleInstance)
    {
        global $adb;

        $deforgshare = $adb->pquery('SELECT * FROM vtiger_def_org_share WHERE tabid=?', [$moduleInstance->id]);
        $deforgshareCount = $adb->num_rows($deforgshare);

        if (empty($deforgshareCount)) {
            return;
        }

        $this->openNode('sharingaccess');
        if ($deforgshareCount) {
            for ($index = 0; $index < $deforgshareCount; ++$index) {
                $permission = $adb->query_result($deforgshare, $index, 'permission');
                $permissiontext = '';
                if ($permission == '0') {
                    $permissiontext = 'public_readonly';
                }
                if ($permission == '1') {
                    $permissiontext = 'public_readwrite';
                }
                if ($permission == '2') {
                    $permissiontext = 'public_readwritedelete';
                }
                if ($permission == '3') {
                    $permissiontext = 'private';
                }

                $this->outputNode($permissiontext, 'default');
            }
        }
        $this->closeNode('sharingaccess');
    }

    /**
     * Export Events of the module.
     */
    public function export_Events($moduleInstance)
    {
        $events = Vtiger_Event::getAll($moduleInstance);
        if (!$events) {
            return;
        }

        $this->openNode('events');
        foreach ($events as $event) {
            $this->openNode('event');
            $this->outputNode($event->eventname, 'eventname');
            $this->outputNode('<![CDATA[' . $event->classname . ']]>', 'classname');
            $this->outputNode('<![CDATA[' . $event->filename . ']]>', 'filename');
            $this->outputNode('<![CDATA[' . $event->condition . ']]>', 'condition');
            $this->outputNode('<![CDATA[' . $event->dependent . ']]>', 'dependent');
            $this->closeNode('event');
        }
        $this->closeNode('events');
    }

    /**
     * Export actions (tools) associated with module.
     * TODO: Need to pickup values based on status for all user (profile).
     */
    public function export_Actions($moduleInstance)
    {

        if (!$moduleInstance->isentitytype) {
            return;
        }

        global $adb;
        $result = $adb->pquery('SELECT distinct(actionname) FROM vtiger_profile2utility, vtiger_actionmapping
			WHERE vtiger_profile2utility.activityid=vtiger_actionmapping.actionid and tabid=?', [$moduleInstance->id]);

        if ($adb->num_rows($result)) {
            $this->openNode('actions');

            while ($resultrow = $adb->fetch_array($result)) {
                $this->openNode('action');
                $this->outputNode('<![CDATA[' . $resultrow['actionname'] . ']]>', 'name');
                $this->outputNode('enabled', 'status');
                $this->closeNode('action');
            }
            $this->closeNode('actions');
        }
    }

    /**
     * Export related lists associated with module.
     */
    public function export_RelatedLists($moduleInstance)
    {

        if (!$moduleInstance->isentitytype) {
            return;
        }

        global $adb;
        $result = $adb->pquery('SELECT * FROM vtiger_relatedlists WHERE tabid = ?', [$moduleInstance->id]);
        if ($adb->num_rows($result)) {
            $this->openNode('relatedlists');

            for ($index = 0; $index < $adb->num_rows($result); ++$index) {
                $row = $adb->fetch_array($result);
                $this->openNode('relatedlist');

                $this->outputNode($row['name'], 'function');
                $this->outputNode($row['label'], 'label');
                $this->outputNode($row['sequence'], 'sequence');
                $this->outputNode($row['presence'], 'presence');

                $action_text = $row['actions'];
                if (!empty($action_text)) {
                    $this->openNode('actions');
                    $actions = explode(',', $action_text);
                    foreach ($actions as $action) {
                        $this->outputNode($action, 'action');
                    }
                    $this->closeNode('actions');
                }

                $relModuleInstance = Vtiger_Module::getInstance($row['related_tabid']);
                $this->outputNode($relModuleInstance->name, 'relatedmodule');

                $this->closeNode('relatedlist');
            }

            $this->closeNode('relatedlists');
        }
    }

    /**
     * Export custom links of the module.
     */
    public function export_CustomLinks($moduleInstance)
    {
        $customlinks = $moduleInstance->getLinksForExport();
        if (!empty($customlinks)) {
            $this->openNode('customlinks');
            foreach ($customlinks as $customlink) {
                $this->openNode('customlink');
                $this->outputNode($customlink->linktype, 'linktype');
                $this->outputNode($customlink->linklabel, 'linklabel');
                $this->outputNode("<![CDATA[{$customlink->linkurl}]]>", 'linkurl');
                $this->outputNode("<![CDATA[{$customlink->linkicon}]]>", 'linkicon');
                $this->outputNode($customlink->sequence, 'sequence');
                $this->outputNode("<![CDATA[{$customlink->handler_path}]]>", 'handler_path');
                $this->outputNode("<![CDATA[{$customlink->handler_class}]]>", 'handler_class');
                $this->outputNode("<![CDATA[{$customlink->handler}]]>", 'handler');
                $this->closeNode('customlink');
            }
            $this->closeNode('customlinks');
        }
    }

    /**
     * Export cron tasks for the module.
     */
    public function export_CronTasks($moduleInstance)
    {
        $cronTasks = Vtiger_Cron::listAllInstancesByModule($moduleInstance->name);
        $this->openNode('crons');
        foreach ($cronTasks as $cronTask) {
            $this->openNode('cron');
            $this->outputNode($cronTask->getName(), 'name');
            $this->outputNode($cronTask->getFrequency(), 'frequency');
            $this->outputNode($cronTask->getStatus(), 'status');
            $this->outputNode($cronTask->getHandlerFile(), 'handler');
            $this->outputNode($cronTask->getSequence(), 'sequence');
            $this->outputNode($cronTask->getDescription(), 'description');
            $this->closeNode('cron');
        }
        $this->closeNode('crons');
    }

    /**
     * Helper function to log messages.
     * @param string Message to log
     * @param bool true appends linebreak, false to avoid it
     */
    public static function log($message, $delim = true)
    {
        Vtiger_Utils::Log($message, $delim);
    }
}
