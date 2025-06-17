<?php

class Settings_EMAILMaker_License_View extends Settings_Vtiger_Index_View
{
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $settingLinks = array();
        foreach ($moduleModel->getSettingLinks() as $settingsLink) {
            $settingsLink['linklabel'] = sprintf(vtranslate($settingsLink['linklabel'], $moduleName),
                vtranslate($moduleName, $moduleName));
            $settingLinks['LISTVIEWSETTING'][] = Vtiger_Link_Model::getInstanceFromValues($settingsLink);
        }
        $viewer = $this->getViewer($request);
        $viewer->assign('LISTVIEW_LINKS', $settingLinks);
        parent::preProcess($request, false);
        if (6 !== (int) Vtiger_Version::current() && $display) {
            $this->preProcessDisplay($request);
        }
    }

    public function process(Vtiger_Request $request)
    {
        $this->initializeContents($request);
    }

    public function initializeContents(Vtiger_Request $request)
    {
        $request->set('parent', 'Settings');
        $moduleName = $request->getModule();
        $qualifiedModule = $request->getModule(false);
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $permission = $moduleModel->getLicensePermissions('Edit');
        $reportData = $moduleModel->licensePermissions;
        $installer = 'ITS4YouInstaller';
        $installerModel = Vtiger_Module_Model::getInstance($installer);
        $viewer = $this->getViewer($request);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
        $viewer->assign("URL", vglobal("site_URL"));
        $viewer->assign("DEFAULT_VIEW_URL", $moduleModel->getDefaultUrl());
        $viewer->assign('IS_ALLOWED', $permission);
        $viewer->assign('MODULE_MODEL', $moduleModel);
        if (isset($reportData['errors'])) {
            $viewer->assign("ERRORS", $reportData['errors']);
        }
        if (isset($reportData['info'])) {
            $viewer->assign("INFO", $reportData['info']);
        }
        if ($installerModel && $installerModel->isActive()) {
            $viewer->assign('IS_INSTALLER_ACTIVE', $installerModel->isActive());
            $viewer->assign('INSTALLER_MODEL', $installerModel);
        }
        $viewer->view('License.tpl', $qualifiedModule);
    }

    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        unset($headerScriptInstances['modules.Vtiger.resources.Edit']);
        unset($headerScriptInstances["modules.Settings.Vtiger.resources.Edit"]);
        unset($headerScriptInstances['modules.Inventory.resources.Edit']);
        unset($headerScriptInstances["modules.$moduleName.resources.Edit"]);
        unset($headerScriptInstances["modules.Settings.$moduleName.resources.Edit"]);
        $jsFileNames = array("modules.Settings.$moduleName.resources.License",);
        return array_merge($headerScriptInstances, $this->checkAndConvertJsScripts($jsFileNames));
    }

    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $layout = Vtiger_Viewer::getDefaultLayoutName();
        $cssFileNames = array('~/layouts/'.$layout.'/skins/marketing/style.css',);
        return array_merge($headerCssInstances, $this->checkAndConvertCssStyles($cssFileNames));
    }
} ?>