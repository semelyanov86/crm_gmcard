<?php
/*********************************************************************************
 * The content of this file is subject to the EMAIL Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 ******************************************************************************* */

class EMAILMaker_Extensions_View extends Vtiger_Index_View
{
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        $EMAILMakerModel = new EMAILMaker_EMAILMaker_Model();
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $viewer->assign('QUALIFIED_MODULE', $moduleName);
        Vtiger_Basic_View::preProcess($request, false);
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
        $linkModels = $EMAILMakerModel->getSideBarLinks($linkParams);
        $viewer->assign('QUICK_LINKS', $linkModels);
        $viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('CURRENT_VIEW', $request->get('view'));
        if ($display) {
            $this->preProcessDisplay($request);
        }
    }

    public function process(Vtiger_Request $request)
    {
        EMAILMaker_Debugger_Model::GetInstance()->Init();

        $adb = PearDatabase::getInstance();
        $viewer = $this->getViewer($request);
        $extensions = array();

        $EMAILMakerModel = new EMAILMaker_EMAILMaker_Model();

        $link = "index.php?module=EMAILMaker&action=IndexAjax&mode=downloadFile&parenttab=Tools&extid=";
        $extname = "Workflow";
        $extensions[$extname]["label"] = "LBL_WORKFLOW";
        $extensions[$extname]["desc"] = "LBL_WORKFLOW_DESC";
        $extensions[$extname]["exinstall"] = "";
        $extensions[$extname]["manual"] = "";
        $extensions[$extname]["download"] = "";

        $control = $EMAILMakerModel->controlWorkflows();
        if ($control) {
            $extensions[$extname]["install_info"] = vtranslate("LBL_WORKFLOWS_ARE_ALREADY_INSTALLED", "EMAILMaker");
            $extensions[$extname]["install"] = "";
        } else {
            $extensions[$extname]["install_info"] = "";
            $extensions[$extname]["install"] = $link . $extname . "&type=install";
        }


        $currentLanguage = Vtiger_Language_Handler::getLanguage();
        $unsubscribe_file = "unsubscribeinfo/info_";
        if (file_exists("layouts/vlayout/modules/EMAILMaker/" . $unsubscribe_file . $currentLanguage . ".tpl")) {
            $unsubscribe_file = $unsubscribe_file . $currentLanguage . ".tpl";
        } else {
            $unsubscribe_file = $unsubscribe_file . "en_us.tpl";
        }
        $extname = "UnsubscribeEmail";
        $extensions[$extname]["label"] = "LBL_UNSUBSCRIBE_EMAIL";
        $extensions[$extname]["desc"] = "LBL_UNSUBSCRIBE_EMAIL_DESC";
        $extensions[$extname]["exinstall"] = "";
        $extensions[$extname]["manual"] = $unsubscribe_file;
        $extensions[$extname]["download"] = "https://www.its4you.sk/en/images/extensions/EmailMaker/src/UnsubscribeEmail.zip";


        $extname = "ITS4YouStyles";
        $extensions[$extname]["label"] = "ITS4YouStyles";
        $extensions[$extname]["desc"] = "LBL_ITS4YOUSTYLES_DESC";

        if (vtlib_isModuleActive("ITS4YouStyles")) {
            $extensions[$extname]["install_info"] = vtranslate("LBL_ITS4YOUSTYLES_ARE_ALREADY_INSTALLED", "EMAILMaker");
            $extensions[$extname]["install"] = "";
        } else {
            $extensions[$extname]["install_info"] = vtranslate("LBL_ITS4YOUSTYLES_INSTALL_INFO", "EMAILMaker");
            $extensions[$extname]["install"] = "index.php?module=ModuleManager&parent=Settings&view=ModuleImport&mode=importUserModuleStep1";
        }
        $extensions[$extname]["download"] = "https://www.its4you.sk/en/images/extensions/ITS4YouStyles/src/7x/ITS4YouStyles.zip";

        $viewer->assign("EXTENSIONS_ARR", $extensions);
        $download_error = $request->get('download_error');
        if (isset($download_error) && $download_error != "") {
            $viewer->assign("ERROR", "true");
        }
        $viewer->view('Extensions.tpl', 'EMAILMaker');
    }

    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();

        $jsFileNames = array(
            'modules.Vtiger.resources.Vtiger',
            "modules.$moduleName.resources.Extensions",
        );

        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}