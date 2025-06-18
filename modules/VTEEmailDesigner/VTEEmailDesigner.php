<?php

require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';
require_once 'vtlib/Vtiger/Module.php';

class VTEEmailDesigner extends CRMEntity
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
            self::iniData();
            self::resetValid();
            self::defaultData();
            self::addFields();
        } else {
            if ($event_type == 'module.disabled') {
                self::removeWidgetTo();
            } else {
                if ($event_type == 'module.enabled') {
                    self::addWidgetTo();
                } else {
                    if ($event_type == 'module.preuninstall') {
                        self::removeWidgetTo();
                        self::removeValid();
                    } else {
                        if ($event_type != 'module.preupdate') {
                            if ($event_type == 'module.postupdate') {
                                self::removeWidgetTo();
                                self::addWidgetTo();
                                self::updateEmailDesignerBlocks();
                                self::iniData();
                                self::resetValid();
                                self::addFields();
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
        global $adb;
        global $vtiger_current_version;
        include_once 'vtlib/Vtiger/Module.php';
        $rs = $adb->pquery('SELECT * FROM `vtiger_settings_field` WHERE `name` = ?', ['Email Designer']);
        if ($adb->num_rows($rs) == 0) {
            $max_id = $adb->getUniqueID('vtiger_settings_field');
            $adb->pquery('INSERT INTO `vtiger_settings_field` (`fieldid`, `blockid`, `name`, `description`, `linkto`, `sequence`) VALUES (?, ?, ?, ?, ?, ?)', [$max_id, '4', 'Email Designer', 'Settings area for Email Designer', 'index.php?module=VTEEmailDesigner&view=List', $max_id]);
        }
        $rs = $adb->pquery('SELECT * FROM `vtiger_ws_entity` WHERE `name` = ?', ['VTEEmailDesigner']);
        if ($adb->num_rows($rs) == 0) {
            $adb->pquery("INSERT INTO `vtiger_ws_entity` (`name`, `handler_path`, `handler_class`, `ismodule`)            VALUES (?, 'include/Webservices/VtigerModuleOperation.php', 'VtigerModuleOperation', '1');", ['VTEEmailDesigner']);
        }
    }

    public static function removeWidgetTo()
    {
        global $adb;
        global $vtiger_current_version;
        include_once 'vtlib/Vtiger/Module.php';
        $adb->pquery('DELETE FROM vtiger_settings_field WHERE `name` = ?', ['Email Designer']);
        $adb->pquery('DELETE FROM vtiger_ws_entity WHERE `name` = ?', ['VTEEmailDesigner']);
    }

    public static function removeValid()
    {
        global $adb;
        $adb->pquery('DELETE FROM `vte_modules` WHERE module=?;', ['VTEEmailDesigner']);
    }

    public static function resetValid()
    {
        global $adb;
        $adb->pquery('DELETE FROM `vte_modules` WHERE module=?;', ['VTEEmailDesigner']);
        $adb->pquery('INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);', ['VTEEmailDesigner', '0']);
    }

    public static function defaultData()
    {
        global $adb;
        global $root_directory;
        global $dbconfig;
        $sql = "INSERT INTO `vteemaildesigner_block_category` (`id`, `name`)                VALUES (1,'Typography'), (2,'Media'), (3,'Layout'), (4,'Button'), (5,'Social'), (6,'Footer');";
        $adb->pquery($sql, []);
        $sql = 'SELECT templateid,body FROM `vtiger_emailtemplates`;';
        $result = $adb->pquery($sql, []);

        while ($row = $adb->fetch_row($result)) {
            $templateid = $row['templateid'];
            $body = "<table class=\"main\" width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"background-color: rgb(255, 255, 255); width: 600px; border-spacing: 0px; border-collapse: collapse; text-size-adjust: 100%;\" align=\"center\" data-last-type=\"background\" data-mce-selected=\"1\">\r\n                <tbody>\r\n                    <tr>\r\n                        <td class=\"element-content\" align=\"left\" style=\"padding: 10px 50px; font-family: Arial; font-size: 13px; color: rgb(0, 0, 0); line-height: 22px; border-collapse: collapse; text-size-adjust: 100%;\">\r\n                            <div contenteditable=\"true\" class=\"test-text element-contenteditable active\">" . decode_html($row['body']) . "</div>\r\n                        </td>\r\n                    </tr>\r\n                </tbody>\r\n            </table>";
            $adb->pquery('INSERT INTO `vteemaildesigner_template_blocks` (`templateid`, `blockid`, `content`) VALUES (?, ?, ?)', [$templateid, '3', $body]);
        }
    }

    public static function updateEmailDesignerBlocks()
    {
        global $adb;
        global $root_directory;
        global $site_URL;
        $adb->pquery('DELETE FROM vteemaildesigner_blocks', []);
        $adb->pquery('ALTER TABLE vteemaildesigner_blocks CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;', []);
        $script_path = $root_directory . 'modules/VTEEmailDesigner/data/vteemaildesigner_blocks.sql';
        $sql_vteemaildesigner_blocks = file_get_contents($script_path);
        $sql_vteemaildesigner_blocks = str_replace('$site_url$', $site_URL, $sql_vteemaildesigner_blocks);
        $adb->pquery($sql_vteemaildesigner_blocks, []);
    }

    public function checkTableExist($tableName)
    {
        global $adb;
        $sql = 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = ? AND table_name = ?';
        $res = $adb->pquery($sql, [$adb->dbName, $tableName]);
        if ($adb->num_rows($res) > 0) {
            return true;
        }

        return false;
    }

    public static function checkColumnExist($tableName, $columnName)
    {
        global $adb;
        $sql = 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = ? AND table_name = ? AND column_name = ?';
        $res = $adb->pquery($sql, [$adb->dbName, $tableName, $columnName]);
        if ($adb->num_rows($res) > 0) {
            return true;
        }

        return false;
    }

    public static function addFields()
    {
        global $adb;
        if (!self::checkColumnExist('vtiger_emailtemplates', 'bg_color')) {
            $adb->pquery('ALTER TABLE `vtiger_emailtemplates` ADD COLUMN `bg_color` VARCHAR(50) NULL ', []);
        }
        if (!self::checkColumnExist('vtiger_emailtemplates', 'email_width')) {
            $adb->pquery('ALTER TABLE `vtiger_emailtemplates` ADD COLUMN `email_width` VARCHAR(50) NULL ', []);
        }
        if (!self::checkColumnExist('vtiger_emailtemplates', 'bg_color_inner')) {
            $adb->pquery('ALTER TABLE `vtiger_emailtemplates` ADD COLUMN `bg_color_inner` VARCHAR(50) NULL ', []);
        }
    }
}
