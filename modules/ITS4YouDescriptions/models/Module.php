<?php


class ITS4YouDescriptions_Module_Model extends Vtiger_Module_Model
{
    public static $mobileIcon = 'pencil';

    public $licensePermissions = [];

    public static function getDescriptionsForModule($module, $fieldname = '', $fieldlabel = '')
    {
        $output = [];
        $adb = PearDatabase::getInstance();
        $query = 'SELECT descriptionid, descriptionname FROM its4you_descriptions INNER JOIN vtiger_crmentity ON its4you_descriptions.descriptionid = vtiger_crmentity.crmid WHERE deleted = 0 ';

        if ($fieldname == 'terms_conditions' || $fieldlabel == 'Terms' || $fieldlabel == 'Terms & Conditions') {
            $query .= 'AND ((desc4youmodule=? OR desc4youmodule="Global") AND (desc4youfield="Terms & Conditions"))';
            $params = [$module];
        } elseif ($fieldname == 'description' || $fieldlabel == 'Description') {
            $query .= 'AND ((desc4youmodule=? OR desc4youmodule="Global") AND (desc4youfield="Description"))';
            $params = [$module];
        } else {
            $query .= 'AND (desc4youmodule=? AND (desc4youfield=? OR desc4youfield=""))';
            $params = [$module, $fieldlabel];
        }

        $res = $adb->pquery($query, $params);

        while ($row = $adb->fetchByAssoc($res)) {
            $output[$row['descriptionid']] = $row['descriptionname'];
        }

        return $output;
    }

    public function getLicensePermissions($type = 'List')
    {
        if (empty($this->name)) {
            $this->name = explode('_', get_class($this))[0];
        }
        $this->licensePermissions['info'] = 'OK';

        return 'OK';
    }

    public function getSettingLinks()
    {
        $settingsLinks = parent::getSettingLinks();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();

        if ($currentUserModel->isAdminUser()) {
            $settingsLinks[] = [
                'linktype' => 'LISTVIEWSETTING',
                'linklabel' => 'LBL_ALLOWED_MODULES',
                'linkurl' => 'index.php?module=ITS4YouDescriptions&view=AllowedModules',
            ];
            $settingsLinks[] = [
                'linktype' => 'LISTVIEWSETTING',
                'linklabel' => 'LBL_MODULE_REQUIREMENTS',
                'linkurl' => 'index.php?module=ITS4YouInstaller&parent=Settings&view=Requirements&mode=Module&sourceModule=ITS4YouDescriptions',
            ];
            $settingsLinks[] = [
                'linktype' => 'LISTVIEWSETTING',
                'linklabel' => 'LICENSE_SETTINGS',
                'linkurl' => 'index.php?module=ITS4YouInstaller&view=License&parent=Settings&sourceModule=ITS4YouDescriptions',
            ];
            $settingsLinks[] = [
                'linktype' => 'LISTVIEWSETTING',
                'linklabel' => 'LBL_UPGRADE',
                'linkurl' => 'index.php?module=ModuleManager&parent=Settings&view=ModuleImport&mode=importUserModuleStep1',
            ];
            $settingsLinks[] = [
                'linktype' => 'LISTVIEWSETTING',
                'linklabel' => 'LBL_UNINSTALL',
                'linkurl' => 'index.php?module=ITS4YouInstaller&view=Uninstall&parent=Settings&sourceModule=ITS4YouDescriptions',
            ];
        }

        return $settingsLinks;
    }

    public function getDatabaseTables()
    {
        return [
            'its4you_descriptions',
            'its4you_descriptionscf',
            'its4you_descriptions_settings',
            'its4you_descriptions_version',
            'its4you_descriptions_license',
            'vtiger_desc4youfield',
            'vtiger_desc4youmodule',
            'vtiger_desc4youfield_seq',
            'vtiger_desc4youmodule_seq',
        ];
    }

    public function getPicklistFields()
    {
        return [
            'desc4youfield',
            'desc4youmodule',
        ];
    }
}
