<?php

class UserLogin_Module_Model extends Vtiger_Module_Model
{
    public $user;

    public $db;

    public function __construct()
    {
        $this->user = Users_Record_Model::getCurrentUserModel();
        $this->db = PearDatabase::getInstance();
    }

    /**
     * Function to get Settings links for admin user.
     * @return array
     */
    public function getSettingLinks()
    {
        $settingsLinks = parent::getSettingLinks();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if ($currentUserModel->isAdminUser()) {
            $settingsLinks[] = ['linktype' => 'LISTVIEWSETTING', 'linklabel' => 'Settings', 'linkurl' => 'index.php?module=UserLogin&view=Settings&parent=Settings', 'linkicon' => ''];
            $settingsLinks[] = ['linktype' => 'LISTVIEWSETTING', 'linklabel' => 'Uninstall', 'linkurl' => 'index.php?module=UserLogin&view=Uninstall&parent=Settings', 'linkicon' => ''];
        }

        return $settingsLinks;
    }
}
