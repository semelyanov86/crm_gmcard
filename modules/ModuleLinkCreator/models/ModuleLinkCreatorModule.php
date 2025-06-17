<?php

/**
 * Class ModuleLinkCreatorModule_Module_Model
 */
class ModuleLinkCreatorModule_Module_Model extends Vtiger_Module_Model
{
    /**
     * Function to get Settings links for admin user
     * @return Array
     */
    public function getSettingLinks()
    {
        $settingsLinks = parent::getSettingLinks();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if ($currentUserModel->isAdminUser()) {
        }
        return $settingsLinks;
    }
}

?>