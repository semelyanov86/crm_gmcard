<?php

define("DS", DIRECTORY_SEPARATOR);
/**
 * Class ModuleLinkCreator_Uninstall_View
 */
class ModuleLinkCreator_Uninstall_View extends Settings_Vtiger_Index_View
{
    /**
     * @param Vtiger_Request $request
     */
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleLabel = vtranslate($moduleName, $moduleName);
        echo "<div class=\"container-fluid\"><div class=\"widget_header row-fluid\"><h3>" . $moduleLabel . "</h3></div><hr>";
        $module = Vtiger_Module::getInstance($moduleName);
        if ($module) {
            $module->delete();
        }
        $this->removeData();
        $this->cleanFolder($moduleName);
        $this->cleanLanguage($moduleName);
        echo "Module was Uninstalled.</div>";
    }
    /**
     * @param $moduleName
     */
    public function removeData()
    {
        global $adb;
        $moduleName = "ModuleLinkCreator";
        echo "&nbsp;&nbsp;- Delete vte_module_link_creator_settings table.";
        $result = $adb->pquery("DROP TABLE vte_module_link_creator_settings");
        echo $result ? " - DONE" : " - <b>ERROR</b>";
        echo "<br>&nbsp;&nbsp;- Delete Custom Module Builder from vtiger_ws_entity table.";
        $result = $adb->pquery("DELETE FROM `vtiger_crmentity` WHERE `setype`=?", array($moduleName));
        echo $result ? " - DONE" : " - <b>ERROR</b>";
        echo "<br>&nbsp;&nbsp;- Delete vte_module_link_creator table.";
        $result = $adb->pquery("DROP TABLE vte_module_link_creator");
        echo $result ? " - DONE" : " - <b>ERROR</b>";
        echo "<br>&nbsp;&nbsp;- Delete Custom Module Builder from vtiger_ws_entity table.";
        $result = $adb->pquery("DELETE FROM `vtiger_ws_entity` WHERE `name`=?", array($moduleName));
        echo $result ? " - DONE" : " - <b>ERROR</b>";
        echo "<br>&nbsp;&nbsp;- Delete Custom Module Builder from vtiger_settings_field table.";
        $result = $adb->pquery("DELETE FROM `vtiger_settings_field` WHERE `name`=?", array("Custom Module Builder"));
        echo $result ? " - DONE" : " - <b>ERROR</b>";
        echo "<br>";
    }
    /**
     * @param $moduleName
     */
    public function cleanFolder($moduleName)
    {
        global $adb;
        global $vtiger_current_version;
        echo "&nbsp;&nbsp;- Remove " . $moduleName . " template folder";
        $result = $this->removeFolder("layouts/vlayout/modules/" . $moduleName);
        echo $result ? " - DONE" : " - <b>ERROR</b>";
        echo "<br>";
        echo "&nbsp;&nbsp;- Remove " . $moduleName . " module folder";
        $result = $this->removeFolder("modules/" . $moduleName);
        echo $result ? " - DONE" : " - <b>ERROR</b>";
        echo "<br>";
        if (version_compare($vtiger_current_version, "7.0.0", ">=")) {
            $result = $this->removeFolder("layouts/v7/modules/" . $moduleName);
        }
    }
    /**
     * @param $path
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
            if ($tmp == ".." || $tmp == ".") {
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
     * @param $moduleName
     */
    public function cleanLanguage($moduleName)
    {
        $files = glob("languages/*/" . $moduleName . ".php");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    /**
     * @param $moduleName
     */
    public function cleanLicense($moduleName)
    {
        $file = "test/" . $moduleName . ".php";
        if (is_file($file)) {
            unlink($file);
        }
    }
    /**
     * @param $moduleName
     */
    public function cleanStorage($moduleName)
    {
        $dir = "storage/" . $moduleName;
        $this->rmdir_recursive($dir);
    }
    /**
     * @link http://stackoverflow.com/questions/7288029/php-delete-directory-that-is-not-empty
     * @param $dir
     */
    public function rmdir_recursive($dir)
    {
        foreach (scandir($dir) as $file) {
            if ("." === $file || ".." === $file) {
                continue;
            }
            $tmpFile = (string) $dir . "/" . $file;
            if (is_dir($tmpFile)) {
                $this->rmdir_recursive($tmpFile);
            } else {
                unlink($tmpFile);
            }
        }
        rmdir($dir);
    }
}

?>