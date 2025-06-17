<?php

class VTEExportToXLS_Module_Model extends Vtiger_Module_Model
{
    public function getSettingLinks()
    {
        $settingsLinks[] = array("linktype" => "MODULESETTING", "linklabel" => "Settings", "linkurl" => "index.php?module=VTEExportToXLS&parent=Settings&view=Settings", "linkicon" => "");
        $settingsLinks[] = array("linktype" => "MODULESETTING", "linklabel" => "Uninstall", "linkurl" => "index.php?module=VTEExportToXLS&parent=Settings&view=Uninstall", "linkicon" => "");
        return $settingsLinks;
    }
    public static function saveValue($data)
    {
        global $adb;
        $fieldname = $data->get("fieldname");
        $value = $data->get("value");
        $adb->pquery("UPDATE `vteexport_to_xls_settings` SET `" . $fieldname . "`=?", array($value));
        return true;
    }
    public static function enableDownload($request)
    {
        global $adb;
        $value = $request->get("value");
        if ($value) {
            $rootDirectory = vglobal("root_directory");
            $permissions = exec("ls -l " . $rootDirectory . "/config.inc.php | awk 'BEGIN {OFS=\":\"}{print \$3,\$4}'");
            $filepath = "storage/export_excel";
            if (!is_dir($filepath)) {
                mkdir($filepath);
                exec("chown -R " . $permissions . "  " . $filepath);
            }
            if (!is_dir($filepath)) {
                $message = vtranslate("LBL_NO_CREATE_FOLDER_PERMISSION", "VTEExportToXLS");
                return array("success" => 0, "message" => $message);
            }
        }
        $adb->pquery("UPDATE `vteexport_to_xls_settings` SET `download_to_server`=?", array($value));
        return array("success" => 1, "message" => vtranslate("LBL_UPDATED", "VTEExportToXLS"));
    }
}

?>