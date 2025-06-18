<?php

/**
 * Class VTEEmailMarketing_Uninstall_View.
 */
class VReports_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $module = Vtiger_Module::getInstance($moduleName);
        echo '<div class="container-fluid">';
        if (!$module) {
            echo '<div class="widget_header row-fluid"><h3>' . vtranslate('Invalid module') . '</h3></div>';
            echo '<hr>';
        } else {
            echo '<div class="widget_header row-fluid"><h3>' . $module->label . '</h3></div>';
            echo '<hr>';
            $module->delete();
            $this->removeData($moduleName);
            $this->cleanFolder($moduleName);
            $this->cleanLanguage($moduleName);
            echo 'Module was uninstalled.';
        }
        echo '<br>';
        echo 'Back to <a href="index.php?module=ModuleManager&parent=Settings&view=List">' . vtranslate('ModuleManager') . '</a>';
        echo '</div>';
    }

    public function removeData($moduleName)
    {
        global $adb;
        $tabId = getTabid($moduleName);
        echo '&nbsp;&nbsp;- Delete vtevreports_settings table.';
        $result = $adb->pquery('DROP TABLE vtevreports_settings', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_schedulevreports table.';
        $result = $adb->pquery('DROP TABLE vtiger_schedulevreports', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreport_relcriteria table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreport_relcriteria', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreport_relcriteria_grouping table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreport_relcriteria_grouping', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreport_relcriteria_grouping_parent table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreport_relcriteria_grouping_parent', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreport_shareall table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreport_shareall', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreport_sharegroups table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreport_sharegroups', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreport_sharerole table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreport_sharerole', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreport_sharers table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreport_sharers', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreport_shareusers table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreport_shareusers', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportdatefilter table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportdatefilter', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportfilters table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportfilters', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportgroupbycolumn table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportgroupbycolumn', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportmodules table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportmodules', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportsharing table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportsharing', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportsortcol table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportsortcol', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportsummary table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportsummary', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreporttype table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreporttype', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_module_vreportdashboard_widgets table.';
        $result = $adb->pquery('DROP TABLE vtiger_module_vreportdashboard_widgets', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportdashboard_tabs table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportdashboard_tabs', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete record vtiger_vreport_sharerole.';
        $result = $adb->pquery('DELETE FROM vtiger_relatedlists WHERE related_tabid = ?', [$tabId]);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_scheduled_vreports table.';
        $result = $adb->pquery('DROP TABLE vtiger_scheduled_vreports', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportfolder table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportfolder', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportshistory table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportshistory', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreportdashboard_boards table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreportdashboard_boards', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreports_css_defaults table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreports_css_defaults', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vtiger_vreport table.';
        $result = $adb->pquery('DROP TABLE vtiger_vreport', []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>';
        $sql = 'DROP TABLE `vreports_settings`;';
        $result = $adb->pquery($sql, []);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>&nbsp;&nbsp;- Delete vreports_settings table.';
    }

    public function cleanFolder($moduleName)
    {
        echo '&nbsp;&nbsp;- Remove ' . $moduleName . ' template folder';
        $result = $this->removeFolder('layouts/v7/modules/' . $moduleName);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>';
        echo '&nbsp;&nbsp;- Remove ' . $moduleName . ' module folder';
        $result = $this->removeFolder('modules/' . $moduleName);
        echo $result ? ' - DONE' : ' - <b>ERROR</b>';
        echo '<br>';
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

    public function cleanLanguage($moduleName)
    {
        $files = glob('languages/*/' . $moduleName . '.php');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
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
}
