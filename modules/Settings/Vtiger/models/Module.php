<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

/*
 * Settings Module Model Class
 */
class Settings_Vtiger_Module_Model extends Vtiger_Base_Model
{
    public $baseTable = 'vtiger_settings_field';

    public $baseIndex = 'fieldid';

    public $listFields = ['name' => 'Name', 'description' => 'Description'];

    public $nameFields = ['name'];

    public $name = 'Vtiger';

    public $listFieldModels;

    public function getName($includeParentIfExists = false)
    {
        if ($includeParentIfExists) {
            return  $this->getParentName() . ':' . $this->name;
        }

        return $this->name;
    }

    public function getParentName()
    {
        return 'Settings';
    }

    public function getBaseTable()
    {
        return $this->baseTable;
    }

    public function getBaseIndex()
    {
        return $this->baseIndex;
    }

    public function setListFields($fieldNames)
    {
        $this->listFields = $fieldNames;

        return $this;
    }

    public function getListFields()
    {
        if (!$this->listFieldModels) {
            $fields = $this->listFields;
            $fieldObjects = [];
            foreach ($fields as $fieldName => $fieldLabel) {
                $fieldObjects[$fieldName] = new Vtiger_Base_Model(['name' => $fieldName, 'label' => $fieldLabel]);
            }
            $this->listFieldModels = $fieldObjects;
        }

        return $this->listFieldModels;
    }

    /**
     * Function to get name fields of this module.
     * @return <Array> list field names
     */
    public function getNameFields()
    {
        return $this->nameFields;
    }

    /**
     * Function to get field using field name.
     * @param <String> $fieldName
     * @return <Field_Model>
     */
    public function getField($fieldName)
    {
        return new Vtiger_Base_Model(['name' => $fieldName, 'label' => $fieldName]);
    }

    public function hasCreatePermissions()
    {
        return true;
    }

    /**
     * Function to get all the Settings menus.
     * @return <Array> - List of Settings_Vtiger_Menu_Model instances
     */
    public function getMenus()
    {
        return Settings_Vtiger_Menu_Model::getAll();
    }

    /**
     * Function to get all the Settings menu items for the given menu.
     * @return <Array> - List of Settings_Vtiger_MenuItem_Model instances
     */
    public function getMenuItems($menu = false)
    {
        $menuModel = false;
        if ($menu) {
            $menuModel = Settings_Vtiger_Menu_Model::getInstance($menu);
        }

        return Settings_Vtiger_MenuItem_Model::getAll($menuModel);
    }

    public function isPagingSupported()
    {
        return true;
    }

    /**
     * Function to get the instance of Settings module model.
     * @return Settings_Vtiger_Module_Model instance
     */
    public static function getInstance()
    {
        [$name] = func_num_args() > 0 ? func_get_args() : '';
        if (empty($name)) {
            $name = 'Settings:Vtiger';
        }
        $modelClassName = Vtiger_Loader::getComponentClassName('Model', 'Module', $name);

        return new $modelClassName();
    }

    /**
     * Function to get Index view Url.
     * @return <String> URL
     */
    public function getIndexViewUrl()
    {
        return 'index.php?module=' . $this->getName() . '&parent=' . $this->getParentName() . '&view=Index';
    }

    /*
     * Function to get supported utility actions for a module
     */
    public function getUtilityActionsNames()
    {
        return [];
    }

    /**
     * Fucntion to get the settings menu item for vtiger7.
     * @return <array> $settingsMenItems
     */
    public static function getSettingsMenuItem()
    {
        $settingsModel = Settings_Vtiger_Module_Model::getInstance();
        $menuModels = $settingsModel->getMenus();

        // Specific change for Vtiger7
        $settingsMenItems = [];
        foreach ($menuModels as $menuModel) {
            $menuItems = $menuModel->getMenuItems();
            foreach ($menuItems as $menuItem) {
                $settingsMenItems[$menuItem->get('name')] = $menuItem;
            }
        }

        return $settingsMenItems;
    }

    public static function getActiveBlockName($request)
    {
        $finalResult = [];
        $view = $request->get('view');
        $moduleName = $request->getModule();
        $qualifiedModuleName = $request->getModule(false);

        $arrayParams = [];
        $whereCondition = 'linkto LIKE ?  ';
        $arrayParams[] = "%{$moduleName}%";
        if ($moduleName != 'LanguageEditor') {
            $whereCondition .= "AND (linkto LIKE '%parent=Settings%' OR linkto LIKE '%parenttab=Settings%')";
        }

        $db = PearDatabase::getInstance();
        $query = "SELECT vtiger_settings_blocks.label AS blockname, vtiger_settings_field.name AS menu FROM vtiger_settings_blocks
					INNER JOIN vtiger_settings_field ON vtiger_settings_field.blockid=vtiger_settings_blocks.blockid
					WHERE {$whereCondition}";
        $result = $db->pquery($query, $arrayParams);
        $numOfRows = $db->num_rows($result);
        if ($numOfRows == 1) {
            $finalResult = ['block' => $db->query_result($result, 0, 'blockname'),
                'menu'	=> $db->query_result($result, 0, 'menu')];
        } elseif ($numOfRows > 1) {
            $query = "{$query} AND linkto LIKE ? ";
            $arrayParams[] = "%view={$view}%";
            $result = $db->pquery($query, $arrayParams);
            $numOfRows = $db->num_rows($result);
            if ($numOfRows == 1) {
                $finalResult = ['block' => $db->query_result($result, 0, 'blockname'),
                    'menu'	=> $db->query_result($result, 0, 'menu')];
            }
        }

        if (!$finalResult) {
            if ($moduleName === 'Users') {
                $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
            } else {
                $moduleModel = Settings_Vtiger_Module_Model::getInstance($qualifiedModuleName);
            }
            $finalResult = $moduleModel->getSettingsActiveBlock($view);
        }

        return $finalResult;
    }

    public function getSettingsActiveBlock($viewName)
    {
        $blocksList = ['OutgoingServerEdit' => ['block' => 'LBL_CONFIGURATION', 'menu' => 'LBL_MAIL_SERVER_SETTINGS']];

        return $blocksList[$viewName] ?? null;
    }

    public function getModuleIcon()
    {
        $moduleName = $this->getName();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if ($moduleModel) {
            $moduleIcon = $moduleModel->getModuleIcon();
        } else {
            $lowerModuleName = strtolower($moduleName);
            $title = vtranslate($moduleName, $moduleName);
            $moduleIcon = "<i class='vicon-{$lowerModuleName}' title='{$title}'></i>";
        }

        return $moduleIcon;
    }

    public static function getSettingsMenuListForNonAdmin()
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $myTagSettingsUrl = $currentUser->getMyTagSettingsListUrl();

        $settingsMenuList = ['LBL_MY_PREFERENCES'	=> ['My Preferences'	=> '',
            'Calendar Settings' => '',
            'LBL_MY_TAGS'		=> $myTagSettingsUrl],
            'LBL_EXTENSIONS'	=> ['LBL_GOOGLE'		=> 'index.php?module=Contacts&parent=Settings&view=Extension&extensionModule=Google&extensionView=Index&mode=settings'],
        ];
        if (!vtlib_isModuleActive('Google')) {
            unset($settingsMenuList['LBL_EXTENSIONS']['LBL_GOOGLE']);
        }

        return $settingsMenuList;
    }
}
