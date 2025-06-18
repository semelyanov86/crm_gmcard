<?php

define('DS', DIRECTORY_SEPARATOR);
include_once 'vtlib/Vtiger/Module.php';
class VTEAdvanceMenu_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function removeData()
    {
        global $adb;
        $message = '';
        $result = $adb->pquery('DROP TABLE IF EXISTS vte_advance_menu_settings_groups;', []);
        $result = $adb->pquery('DROP TABLE IF EXISTS vte_advance_menu_settings_menu;', []);
        $result = $adb->pquery('DROP TABLE IF EXISTS vte_advance_menu_settings_menu_groups_rel;', []);
        $result = $adb->pquery('DROP TABLE IF EXISTS vte_advance_menu_settings_menu_items;', []);
        $message .= '&nbsp;&nbsp;- Delete Advanced Menu Manager tables';
        if ($result) {
            $message .= ' - DONE';
        } else {
            $message .= ' - <b>ERROR</b>';
        }
        $message .= '<br>';
        echo '&nbsp;&nbsp;- Delete folder layouts settings VTEAdvanceMenu.';
        $result = $this->removeFolder('layouts/v7/modules/Settings/VTEAdvanceMenu');
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '&nbsp;&nbsp;- Delete folder modules settings VTEAdvanceMenu.';
        $result = $this->removeFolder('modules/Settings/VTEAdvanceMenu');
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';

        return $message;
    }

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
}
