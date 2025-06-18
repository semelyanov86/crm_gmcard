<?php

/*
 * The content of this file is subject to the EMAILMaker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 ***************************************************************************** */

class Settings_EMAILMaker_Uninstall_View extends Settings_Vtiger_Index_View
{
    /**
     * @param bool $display
     */
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $settingLinks = [];

        foreach ($moduleModel->getSettingLinks() as $settingsLink) {
            $settingsLink['linklabel'] = sprintf(vtranslate($settingsLink['linklabel'], $moduleName), vtranslate($moduleName, $moduleName));
            $settingLinks['LISTVIEWSETTING'][] = Vtiger_Link_Model::getInstanceFromValues($settingsLink);
        }

        $viewer = $this->getViewer($request);
        $viewer->assign('LISTVIEW_LINKS', $settingLinks);

        parent::preProcess($request, false);

        if ((int) Vtiger_Version::current() !== 6 && $display) {
            $this->preProcessDisplay($request);
        }
    }

    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $qualifiedModule = $request->getModule(false);

        $viewer = $this->getViewer($request);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModule);

        $viewer->view('Uninstall.tpl', $qualifiedModule);
    }

    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();

        unset($headerScriptInstances['modules.Vtiger.resources.Edit'], $headerScriptInstances['modules.Settings.Vtiger.resources.Edit'], $headerScriptInstances['modules.Inventory.resources.Edit'], $headerScriptInstances["modules.{$moduleName}.resources.Edit"], $headerScriptInstances["modules.Settings.{$moduleName}.resources.Edit"]);





        $jsFileNames = [
            'modules.Settings.' . $moduleName . '.resources.Uninstall',
        ];

        return array_merge($headerScriptInstances, $this->checkAndConvertJsScripts($jsFileNames));
    }

    /**
     * @return array
     */
    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $layout = Vtiger_Viewer::getDefaultLayoutName();
        $cssFileNames = [
            '~/layouts/' . $layout . '/skins/marketing/style.css',
        ];

        return array_merge($headerCssInstances, $this->checkAndConvertCssStyles($cssFileNames));
    }
}
