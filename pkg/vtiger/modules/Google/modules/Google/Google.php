<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
require_once 'vtlib/Vtiger/Module.php';
require_once 'include/events/include.inc';

#[AllowDynamicProperties]
class Google
{
    public const module = 'Google';

    public $LBL_GOOGLE = 'LBL_GOOGLE';

    /**
     * Invoked when special actions are to be performed on the module.
     * @param string Module name
     * @param string Event Type
     */
    public function vtlib_handler($moduleName, $eventType)
    {
        $adb = PearDatabase::getInstance();
        $forModules = ['Contacts', 'Leads', 'Accounts'];
        $syncModules = ['Contacts' => 'Google Contacts', 'Calendar' => 'Google Calendar'];

        if ($eventType == 'module.postinstall') {
            $adb->pquery('UPDATE vtiger_tab SET customized=0 WHERE name=?', [$moduleName]);
            $this->addMapWidget($forModules);
            $this->addWidgetforSync($syncModules);
        } elseif ($eventType == 'module.disabled') {
            $this->removeMapWidget($forModules);
            $this->removeWidgetforSync($syncModules);
            $adb->pquery('UPDATE vtiger_settings_field SET active=1 WHERE name=?', [$this->LBL_GOOGLE]);
        } elseif ($eventType == 'module.enabled') {
            $this->addMapWidget($forModules);
            $this->addWidgetforSync($syncModules);
            $adb->pquery('UPDATE vtiger_settings_field SET active=0 WHERE name=?', [$this->LBL_GOOGLE]);
        } elseif ($eventType == 'module.preuninstall') {
            $this->removeMapWidget($forModules);
            $this->removeWidgetforSync($syncModules);
        } elseif ($eventType == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } elseif ($eventType == 'module.postupdate') {

        }
    }

    /**
     * Add widget to other module.
     * @param array $moduleNames
     * @param string $widgetType
     * @param string $widgetName
     */
    public function addMapWidget($moduleNames, $widgetType = 'DETAILVIEWSIDEBARWIDGET', $widgetName = 'Google Map')
    {
        if (empty($moduleNames)) {
            return;
        }

        if (is_string($moduleNames)) {
            $moduleNames = [$moduleNames];
        }

        foreach ($moduleNames as $moduleName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->addLink($widgetType, $widgetName, 'module=Google&view=Map&mode=showMap&viewtype=detail', '', '', '');
            }
        }
    }

    /**
     * Remove widget from other modules.
     * @param array $moduleNames
     * @param string $widgetType
     * @param string $widgetName
     */
    public function removeMapWidget($moduleNames, $widgetType = 'DETAILVIEWSIDEBARWIDGET', $widgetName = 'Google Map')
    {
        if (empty($moduleNames)) {
            return;
        }

        if (is_string($moduleNames)) {
            $moduleNames = [$moduleNames];
        }

        foreach ($moduleNames as $moduleName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->deleteLink($widgetType, $widgetName, 'module=Google&view=Map&mode=showMap&viewtype=detail');
            }
        }
    }

    /**
     * Add widget to other module.
     * @param string $widgetType
     */
    public function addWidgetforSync($moduleNames, $widgetType = 'LISTVIEWSIDEBARWIDGET')
    {
        if (empty($moduleNames)) {
            return;
        }

        if (is_string($moduleNames)) {
            $moduleNames = [$moduleNames];
        }

        foreach ($moduleNames as $moduleName => $widgetName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->addLink($widgetType, $widgetName, "module=Google&view=List&sourcemodule={$moduleName}", '', '', '');
            }
        }
    }

    /**
     * Remove widget from other modules.
     * @param string $widgetType
     */
    public function removeWidgetforSync($moduleNames, $widgetType = 'LISTVIEWSIDEBARWIDGET')
    {
        if (empty($moduleNames)) {
            return;
        }

        if (is_string($moduleNames)) {
            $moduleNames = [$moduleNames];
        }

        foreach ($moduleNames as $moduleName => $widgetName) {
            $module = Vtiger_Module::getInstance($moduleName);
            if ($module) {
                $module->deleteLink($widgetType, $widgetName);
            }
        }
    }
}
