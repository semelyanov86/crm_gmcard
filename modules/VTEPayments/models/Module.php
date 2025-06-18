<?php

class VTEPayments_Module_Model extends Vtiger_Module_Model
{
    public function getSettingLinks()
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $settingsLinks[] = ['linktype' => 'MODULESETTING', 'linklabel' => 'List Payments', 'linkurl' => 'index.php?module=VTEPayments&view=List', 'linkicon' => ''];
        if ($currentUser->isAdminUser()) {
            $settingsLinks[] = ['linktype' => 'MODULESETTING', 'linklabel' => 'Uninstall', 'linkurl' => 'index.php?module=VTEPayments&parent=Settings&view=Uninstall', 'linkicon' => ''];
        }

        return $settingsLinks;
    }

    public function getModuleIcon()
    {
        $moduleName = $this->getName();
        $lowerModuleName = strtolower($moduleName);
        $title = vtranslate($moduleName, $moduleName);
        $moduleIcon = "<i class='vicon-" . $lowerModuleName . "' title='" . $title . "'></i>";

        return $moduleIcon;
    }
}
