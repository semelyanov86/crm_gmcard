<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

/**
 * Class __ModuleName___Uninstall_View
 */
class __ModuleName___Uninstall_View extends Settings_Vtiger_Index_View
{

    /**
     * @param Vtiger_Request $request
     */
    function process(Vtiger_Request $request)
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

            // Uninstall module
            $module->delete();

            // Clean & Clear
            $this->cleanDatabase($moduleName);
            $this->cleanFolder($moduleName);
            $this->cleanLanguage($moduleName);

            echo "Module was uninstalled.";
        }

        echo '<br>';
        echo 'Back to <a href="index.php?module=ModuleManager&parent=Settings&view=List">' . vtranslate('ModuleManager') . '</a>';
        echo '</div>';
    }

    /**
     * @param $moduleName
     */
    function cleanDatabase($moduleName)
    {
        global $adb;

        // vtiger_<modulename>cf table
        echo "&nbsp;&nbsp;- Delete vtiger_<modulename>cf table.";
        $result = $adb->pquery("DROP TABLE vtiger_<modulename>cf");
        echo ($result) ? " - DONE" : " - <b>ERROR</b>";
        echo '<br>';

        // vtiger_<modulename> table
        echo "&nbsp;&nbsp;- Delete vtiger_<modulename> table.";
        $result = $adb->pquery("DROP TABLE vtiger_<modulename>");
        echo ($result) ? " - DONE" : " - <b>ERROR</b>";
        echo '<br>';
    }

    /**
     * @param $moduleName
     */
    function cleanFolder($moduleName)
    {
        echo "&nbsp;&nbsp;- Remove " . $moduleName . " template folder";
        $result = $this->removeFolder('layouts/vlayout/modules/' . $moduleName);
        echo ($result) ? " - DONE" : " - <b>ERROR</b>";
        echo '<br>';

        echo "&nbsp;&nbsp;- Remove " . $moduleName . " module folder";
        $result = $this->removeFolder('modules/' . $moduleName);
        echo ($result) ? " - DONE" : " - <b>ERROR</b>";
        echo '<br>';
    }

    /**
     * @param $path
     * @return bool
     */
    function removeFolder($path)
    {
        if (!isFileAccessible($path) || !is_dir($path)) {
            return false;
        }

        if (!is_writeable($path)) {
            chmod($path, 0777);
        }

        $handle = opendir($path);

        while ($tmp = readdir($handle)) {
            if ($tmp == '..' || $tmp == '.') {
                continue;
            }

            $tmpPath = $path . DS . $tmp;

            if (is_file($tmpPath)) {
                if (!is_writeable($tmpPath)) {
                    chmod($tmpPath, 0666);
                }

                unlink($tmpPath);
            } else if (is_dir($tmpPath)) {
                if (!is_writeable($tmpPath)) {
                    chmod($tmpPath, 0777);
                }

                $this->removeFolder($tmpPath);
            }
        }

        closedir($handle);
        rmdir($path);

        return !is_dir($path);
    }

    /**
     * @param $moduleName
     */
    function cleanLanguage($moduleName)
    {
        $files = glob("languages/*/{$moduleName}.php"); // get all file names

        foreach ($files as $file) { // iterate files
            if (is_file($file)) {
                unlink($file); // delete file
            }
        }
    }

    /**
     * @link http://stackoverflow.com/questions/7288029/php-delete-directory-that-is-not-empty
     * @param $dir
     */
    function rmdir_recursive($dir)
    {
        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file)
                continue;

            $tmpFile = "$dir/$file";

            if (is_dir($tmpFile))
                $this->rmdir_recursive($tmpFile);
            else
                unlink($tmpFile);
        }

        rmdir($dir);
    }

}