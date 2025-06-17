<?php

/**
 * Class ModuleLinkCreator_IndexRelatedFields_View
 */
class ModuleLinkCreator_RelationshipOneNone_View extends Vtiger_Index_View
{
    public function checkPermission(Vtiger_Request $request)
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED", "Vtiger"));
        }
    }
    /**
     * @param Vtiger_Request $request
     */
    public function process(Vtiger_Request $request)
    {
        parent::preProcess($request, false);
        $viewer = $this->getViewer($request);
        $entityModules = Vtiger_Module_Model::getEntityModules();
        $restrictedModules = array("Emails", "Calendar", "Faq", "Events", "Webmails", "ModComments", "SMSNotifier", "PBXManager");
        $modules = array();
        foreach ($entityModules as $entityModule) {
            if (!in_array($entityModule->name, $restrictedModules)) {
                array_push($modules, $entityModule->name);
            }
        }
        $viewer->assign("ENTITY_MODULES", $modules);
        $viewer->view("RelationshipOneNone.tpl", $request->getModule());
    }
    /**
     * Retrieves headers scripts that need to loaded in the page
     * @param Vtiger_Request $request - request model
     * @return <array> - array of Vtiger_JsScript_Model
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, "7.0.0", "<")) {
            $template_folder = "layouts/vlayout";
        } else {
            $template_folder = "layouts/v7";
        }
        $headerScriptInstances = parent::getHeaderScripts($request);
        $jsFileNames = array("~/" . $template_folder . "/modules/ModuleLinkCreator/resources/RelatedFields.js");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}

?>