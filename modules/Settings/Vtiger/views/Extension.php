<?php

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * */

class Settings_Vtiger_Extension_View extends Settings_Vtiger_Index_View
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->get('extensionModule');

        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if (empty($moduleModel)) {
            throw new AppException(vtranslate('LBL_HANDLER_NOT_FOUND'));
        }

        $userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        $permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());
        if (!$permission) {
            throw new AppException(vtranslate($moduleName, $moduleName) . ' ' . vtranslate('LBL_NOT_ACCESSIBLE'));
        }

        return true;
    }

    /**
     * Function to get extension view instance from view name and module.
     * @param <Vtiger_Request> $request
     * @return $extensionViewClass
     */
    public function getExtensionViewInstance(Vtiger_Request $request)
    {
        $extensionModule = $request->get('extensionModule');
        $extensionView = $request->get('extensionView');
        $extensionViewClass = Vtiger_Loader::getComponentClassName('view', $extensionView, $extensionModule);
        $extensionViewInstance = new $extensionViewClass();

        return $extensionViewInstance;
    }

    /**
     * Function to get the extension links for a module.
     * @param <string> $module
     * @return <array> $links
     */
    public static function getExtensionLinks($module)
    {
        $linkParams = ['MODULE' => $module, 'ACTION' => 'LIST'];
        $links = Vtiger_Link_Model::getAllByType(getTabid($module), ['EXTENSIONLINK'], $linkParams);

        return $links['EXTENSIONLINK'];
    }

    public function preProcess(Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request, false);
        $viewer = $this->getViewer($request);
        $viewer->assign('EXTENSION_MODULE', $request->get('extensionModule'));
        $viewer->assign('EXTENSION_VIEW', $request->get('extensionView'));
        $viewer->assign('VIEWID', '');

        if ($display) {
            $this->preProcessDisplay($request);
        }
    }

    public function process(Vtiger_Request $request)
    {
        $extensionViewInstance = $this->getExtensionViewInstance($request);
        $extensionViewInstance->process($request);
    }

    /**
     * Function to get the list of Script models to be included.
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $extensionViewInstance = $this->getExtensionViewInstance($request);

        $jsFileNames = [
            'modules.Vtiger.resources.Extension',
            'modules.Vtiger.resources.ExtensionCommon',
            '~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/lib/jquery/floatThead/jquery.floatThead.js',
            '~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/lib/jquery/perfect-scrollbar/js/perfect-scrollbar.jquery.js',
        ];

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        $jsScriptInstances = $extensionViewInstance->getHeaderScripts($request);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);

        return $headerScriptInstances;
    }
}
