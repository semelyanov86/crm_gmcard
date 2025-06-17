<?php

include_once "vtlib/Vtiger/Module.php";

class VTEEmailDesigner_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        ini_set("display_errors", "on");
        echo "<div class=\"container-fluid\">\r\n                <div class=\"widget_header row-fluid\">\r\n                    <h3>Email Designer</h3>\r\n                </div>\r\n                <hr>";
        $moduleName = "VTEEmailDesigner";
        $this->removeData();
        $this->cleanFolder($moduleName);
        $this->cleanLanguage($moduleName);
        $module = Vtiger_Module::getInstance($moduleName);
        if ($module) {
            $module->delete();
        }
        echo "Module was uninstalled.<br>";
        echo "Back to <a href=\"index.php?module=ModuleManager&parent=Settings&view=List\">" . vtranslate("ModuleManager") . "</a>";
        echo "</div>";
    }
    public function removeData()
    {
        global $adb;
        $adb->pquery("DROP TABLE `vteemaildesigner_block_category`", array());
        $adb->pquery("DROP TABLE `vteemaildesigner_blocks`", array());
        $result = $adb->pquery("DROP TABLE `vteemaildesigner_template_blocks`", array());
        echo "&nbsp;&nbsp;- Delete Email Designer tables";
        echo $result ? " - DONE" : " - <b>ERROR</b>";
        echo "<br>";
    }
    public function cleanFolder($moduleName)
    {
        echo "&nbsp;&nbsp;- Remove " . $moduleName . " template folder";
        $result = $this->removeFolder("layouts/v7/modules/" . $moduleName);
        echo $result ? " - DONE" : " - <b>ERROR</b>";
        echo "<br>";
        echo "&nbsp;&nbsp;- Remove " . $moduleName . " module folder";
        $result = $this->removeFolder("modules/" . $moduleName);
        echo $result ? " - DONE" : " - <b>ERROR</b>";
        echo "<br>";
    }
    public function cleanLanguage($moduleName)
    {
        $files = glob("languages/*/" . $moduleName . ".php");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
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