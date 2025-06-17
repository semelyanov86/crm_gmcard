<?php

define("DS", DIRECTORY_SEPARATOR);
class VTEPayments_Uninstall_View extends Settings_Vtiger_Index_View
{
    /**
     * @param Vtiger_Request $request
     */
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $module = Vtiger_Module::getInstance($moduleName);
        echo "<div class=\"container-fluid\">";
        if (!$module) {
            echo "<div class=\"widget_header row-fluid\"><h3>" . vtranslate("Invalid module") . "</h3></div>";
            echo "<hr>";
        } else {
            echo "<div class=\"widget_header row-fluid\"><h3>" . $module->label . "</h3></div>";
            echo "<hr>";
            $message = $this->removeData($moduleName);
            echo $message;
            echo "Module was uninstalled.";
        }
        echo "<br>";
        echo "Back to <a href=\"index.php?module=ModuleManager&parent=Settings&view=List\">" . vtranslate("ModuleManager") . "</a>";
        echo "</div>";
    }
    public function removeData($moduleName)
    {
        global $adb;
        global $vtiger_current_version;
        $message = "";
        $message .= "&nbsp;&nbsp;- Delete vtiger_payments table.";
        $result = $adb->pquery("DROP TABLE vtiger_payments");
        $message .= $result ? " - DONE" : " - <b>ERROR</b>";
        $message .= "<br>";
        $message .= "&nbsp;&nbsp;- Delete vtiger_paymentscf table.";
        $result = $adb->pquery("DROP TABLE vtiger_paymentscf");
        $message .= $result ? " - DONE" : " - <b>ERROR</b>";
        $message .= "<br>";
        $message .= "&nbsp;&nbsp;- Delete vtiger_vtepayments_user_field table.";
        $result = $adb->pquery("DROP TABLE vtiger_vtepayments_user_field");
        $message .= $result ? " - DONE" : " - <b>ERROR</b>";
        $message .= "<br>";
        $message .= "&nbsp;&nbsp;- Delete vtiger_payment_status table.";
        $result = $adb->pquery("DROP TABLE vtiger_payment_status");
        $message .= $result ? " - DONE" : " - <b>ERROR</b>";
        $message .= "<br>";
        $message .= "&nbsp;&nbsp;- Delete vtiger_payment_status_seq table.";
        $result = $adb->pquery("DROP TABLE vtiger_payment_status_seq");
        $message .= $result ? " - DONE" : " - <b>ERROR</b>";
        $message .= "<br>";
        $message .= "&nbsp;&nbsp;- Delete vtiger_payment_type table.";
        $result = $adb->pquery("DROP TABLE vtiger_payment_type");
        $message .= $result ? " - DONE" : " - <b>ERROR</b>";
        $message .= "<br>";
        $message .= "&nbsp;&nbsp;- Delete vtiger_payment_type_seq table.";
        $result = $adb->pquery("DROP TABLE vtiger_payment_type_seq");
        $message .= $result ? " - DONE" : " - <b>ERROR</b>";
        $message .= "&nbsp;&nbsp;- Delete folder settings VTEPayments.";
        if (version_compare($vtiger_current_version, "7.0.0", ">=")) {
            $this->removeFolder("layouts/v7/modules/Settings/VTEPayments");
        }
        $result = $this->removeFolder("layouts/vlayout/modules/Settings/VTEPayments");
        $result = $this->removeFolder("modules/Settings/VTEPayments");
        $message .= $result ? " - DONE" : " - <b>ERROR</b>";
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
}

?>