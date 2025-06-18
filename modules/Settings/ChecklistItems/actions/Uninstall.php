<?php

class Settings_ChecklistItems_Uninstall_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (!$currentUser->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }

        return true;
    }

    public function process(Vtiger_Request $request)
    {
        require_once dirname(dirname(__FILE__)) . '/models/UnInstall.php';
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $unIntallInstance = new UnInstall($moduleName);
        $customQueries = [];
        $unIntallInstance->setCustomQuery($customQueries);
        $links = [['linktype' => 'DETAILVIEWBASIC', 'linklabel' => 'Checklist Items'], ['linktype' => 'HEADERSCRIPT', 'linklabel' => 'ChecklistItemsJS']];
        $unIntallInstance->setLinks($links);
        $moduleModel->delete();
        $pathStructure = $unIntallInstance->getModuleStructure();
        foreach ($pathStructure as $path) {
            if ($path['type'] == 'folder') {
                $unIntallInstance->deleteFolder(trim($path['path']));
            } else {
                $unIntallInstance->deleteFile(trim($path['path']));
            }
        }
        $queries = $unIntallInstance->getModuleQueries();
        $unIntallInstance->removeDataFromDB($queries);
        header('Location: index.php?module=ModuleManager&parent=Settings&view=List');
    }
}
