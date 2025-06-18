<?php

require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';
require_once 'vtlib/Vtiger/Module.php';

class VTEExportToXLS extends CRMEntity
{
    /**
     * Invoked when special actions are performed on the module.
     * @param string Module name
     * @param string Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
     */
    public function vtlib_handler($modulename, $event_type)
    {
        if ($event_type == 'module.postinstall') {
            self::addWidgetTo();
            self::checkEnable();
            self::iniData();
            self::resetValid();
        } else {
            if ($event_type == 'module.disabled') {
                self::removeWidgetTo();
            } else {
                if ($event_type == 'module.enabled') {
                    self::removeWidgetTo();
                    self::addWidgetTo();
                } else {
                    if ($event_type == 'module.preuninstall') {
                        self::removeWidgetTo();
                        self::removeValid();
                    } else {
                        if ($event_type != 'module.preupdate') {
                            if ($event_type == 'module.postupdate') {
                                self::removeWidgetTo();
                                self::checkEnable();
                                self::addWidgetTo();
                                self::iniData();
                                self::resetValid();
                            }
                        }
                    }
                }
            }
        }
    }

    public static function iniData()
    {
        global $adb;
    }

    /**
     * Add header script to other module.
     * @return unknown_type
     */
    public static function addWidgetTo()
    {
        include_once 'vtlib/Vtiger/Module.php';
        include_once 'vtlib/Vtiger/Link.php';
        global $adb;
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $template_folder = 'layouts/vlayout';
        } else {
            $template_folder = 'layouts/v7';
        }
        $moduleNames = ['VTEExportToXLS' => [['tabid' => 0, 'widgetType' => 'LISTVIEW', 'widgetName' => vtranslate('Export To Excel', 'VTEExportToXLS'), 'link' => 'javascript: Vtiger_List_Js.triggerExportAction("index.php?module=VTEExportToXLS&sourceModule="+app.getModuleName()+"&view=Export");'], ['tabid' => 0, 'widgetType' => 'HEADERSCRIPT', 'widgetName' => 'VTEExportToXLS_RelatedList', 'link' => $template_folder . '/modules/VTEExportToXLS/resources/VTEExportToXLS_RelatedList.js']]];
        foreach ($moduleNames as $moduleName => $items) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                foreach ($items as $item) {
                    $tabid = $item['tabid'];
                    if ($tabid === false) {
                        $tabid = $module->getId();
                    }
                    Vtiger_Link::addLink($tabid, $item['widgetType'], $item['widgetName'], $item['link']);
                }
            }
        }
        $blockid = 4;
        $res = $adb->pquery("SELECT blockid FROM `vtiger_settings_blocks` WHERE label='LBL_OTHER_SETTINGS'", []);
        if ($adb->num_rows($res) > 0) {
            while ($row = $adb->fetch_row($res)) {
                $blockid = $row['blockid'];
            }
        }
        $adb->pquery('UPDATE vtiger_settings_field_seq SET id=(SELECT MAX(fieldid) FROM vtiger_settings_field)', []);
        $max_id = $adb->getUniqueID('vtiger_settings_field');
        $res = $adb->pquery("SELECT * FROM `vtiger_settings_field` WHERE name='Export To XLS'", []);
        if ($adb->num_rows($res) == 0) {
            $adb->pquery('INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES (?, ?, ?, ?, ?, ?)', [$max_id, $blockid, 'Export To XLS', 'Settings area for Export To XLS', 'index.php?module=VTEExportToXLS&parent=Settings&view=Settings', $max_id]);
        }
    }

    public static function removeWidgetTo()
    {
        include_once 'vtlib/Vtiger/Module.php';
        global $adb;
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $template_folder = 'layouts/vlayout';
        } else {
            $template_folder = 'layouts/v7';
        }
        $moduleNames = ['VTEExportToXLS' => [['tabid' => 0, 'widgetType' => 'LISTVIEW', 'widgetName' => vtranslate('LBL_EXPORT_TO_EXCEL', 'VTEExportToXLS'), 'link' => 'javascript: Vtiger_List_Js.triggerExportAction("index.php?module=VTEExportToXLS&sourceModule="+app.getModuleName()+"&view=Export");'], ['tabid' => 0, 'widgetType' => 'HEADERSCRIPT', 'widgetName' => 'VTEExportToXLS_RelatedList', 'link' => $template_folder . '/modules/VTEExportToXLS/resources/VTEExportToXLS_RelatedList.js']]];
        foreach ($moduleNames as $moduleName => $items) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                foreach ($items as $item) {
                    $tabid = $item['tabid'];
                    if ($tabid === false) {
                        $tabid = $module->getId();
                    }
                    Vtiger_Link::deleteLink($tabid, $item['widgetType'], $item['widgetName'], $item['link']);
                }
            }
        }
        $adb->pquery('DELETE FROM vtiger_settings_field WHERE `name` = ?', ['Export To XLS']);
        $adb->pquery('DELETE FROM vtiger_links WHERE `linklabel` = ?', ['VTEExportToXLSJs']);
        $adb->pquery('DELETE FROM vtiger_links WHERE `linkurl` = ?', ['javascript: Vtiger_List_Js.triggerExportAction("index.php?module=VTEExportToXLS&sourceModule="+app.getModuleName()+"&view=Export");']);
        $adb->pquery('DELETE FROM vtiger_links WHERE `linklabel` = ?', ['LBL_EXPORT_TO_EXCEL']);
        $adb->pquery('DELETE FROM vtiger_links WHERE `linklabel` = ?', ['VTEExportToXLS_RelatedList']);
    }

    public static function removeValid()
    {
        global $adb;
        $adb->pquery('DELETE FROM `vte_modules` WHERE module=?;', ['VTEExportToXLS']);
    }

    public static function resetValid()
    {
        global $adb;
        $adb->pquery('DELETE FROM `vte_modules` WHERE module=?;', ['VTEExportToXLS']);
        $adb->pquery('INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);', ['VTEExportToXLS', '0']);
    }

    public static function checkEnable()
    {
        global $adb;
        $rs = $adb->pquery('SELECT `enable` FROM `vteexport_to_xls_settings`;', []);
        if ($adb->num_rows($rs) == 0) {
            $adb->pquery("INSERT INTO `vteexport_to_xls_settings` (`enable`) VALUES ('0');", []);
        }
    }
}
