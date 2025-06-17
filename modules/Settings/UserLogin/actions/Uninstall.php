<?php

class Settings_UserLogin_Uninstall_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
        return true;
    }
    public function process(Vtiger_Request $request)
    {
        require_once dirname(dirname(__FILE__)) . "/models/UnInstall.php";
        $moduleName = $request->getModule();
        $unIntallInstance = new UnInstall($moduleName);
        $customQueries = array();
        $customQueries[] = "TRUNCATE TABLE vte_user_login;";
        $customQueries[] = "DELETE FROM vtiger_settings_field WHERE `name` LIKE 'Custom Login Page'";
        $unIntallInstance->setCustomQuery($customQueries);
        $links = array();
        $unIntallInstance->setLinks($links);
        $pathStructure = $unIntallInstance->getModuleStructure();
        foreach ($pathStructure as $path) {
            if ($path["type"] == "folder") {
                $unIntallInstance->deleteFolder(trim($path["path"]));
            } else {
                $unIntallInstance->deleteFile(trim($path["path"]));
            }
        }
        $queries = $unIntallInstance->getModuleQueries();
        $unIntallInstance->removeDataFromDB($queries);
        header("Location: index.php?module=ModuleManager&parent=Settings&view=List");
    }
}

?>