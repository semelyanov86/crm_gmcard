<?php

include "modules/ModuleLinkCreator/ModuleLinkCreator.php";

/**
 * Class ModuleLinkCreator_Edit_View
 */
class ModuleLinkCreator_Edit_View extends Vtiger_Edit_View
{
    /**
     * @constructor
     */
    public function __construct()
    {
        parent::__construct();
    }
    
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
        global $current_user;
        global $vtiger_current_version;
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $primaryModule = $request->get("primary_module");
        $recordId = $request->get("record");
        $recordModel = new ModuleLinkCreator_Record_Model();
        $record = $recordModel->getById($recordId);
        $userProfile = array("user_name" => $current_user->user_name, "first_name" => $current_user->first_name, "last_name" => $current_user->last_name, "full_name" => $current_user->first_name . " " . $current_user->last_name, "email1" => $current_user->email1);
        $settings = array();
        $moduleTypeValue = ModuleLinkCreator_Record_Model::MODULE_TYPE_ENTITY;
        if ($record) {
            $moduleLinkCreatorSettingRecordModel = new ModuleLinkCreator_SettingRecord_Model();
            $objSettings = $moduleLinkCreatorSettingRecordModel->findByModuleId($record->get("module_id"));
            if ($objSettings) {
                $settings = array("module_id" => $objSettings->get("module_id"), "description" => $objSettings->get("description"));
            }
            $selectedModuleType = $record->get("module_type");
            if (!in_array($selectedModuleType, ModuleLinkCreator_Record_Model::module_types())) {
                $moduleTypeValue = $selectedModuleType;
            }
        } else {
            $record = Vtiger_Record_Model::getCleanInstance($moduleName);
            $record->set("module", $primaryModule);
        }
        if (version_compare($vtiger_current_version, "7.0.0", ">=")) {
            $input_lines = file_get_contents("layouts/v7/lib/vt-icons/style.css");
            preg_match_all("/(.vicon-[a-z0-9]+-[a-z0-9]+)|(.vicon-[a-z0-9_]+)|(\\\\[a-z0-9]+)/", $input_lines, $output_array);
            $output_array = $output_array[0];
            unset($output_array[0]);
            $arrResults = array_chunk($output_array, 2);
            $arrIconClasses = array();
            foreach ($arrResults as $cssDetail) {
                $arrIconClasses[str_replace(".", "", $cssDetail[0])] = $cssDetail[1];
            }
            $viewer->assign("LISTICONS", $arrIconClasses);
        }
        $viewer->assign("RECORD_ID", $recordId);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("RECORD", $record);
        $viewer->assign("SETTINGS", ModuleLinkCreator::jsonUnescapedSlashes(json_encode($settings, JSON_FORCE_OBJECT)));
        $viewer->assign("USER_PROFILE", $userProfile);
        $viewer->assign("CONFIG", ModuleLinkCreator::getConfig());
        $viewer->assign("MODULE_TYPES", ModuleLinkCreator_Record_Model::module_types());
        $viewer->assign("MOULE_TYPE_VALUE", $moduleTypeValue);
        $viewer->assign("MODULE_FIELDS", ModuleLinkCreator_Record_Model::module_fields());
        $viewer->assign("MODULE_LIST_VIEW_FILTER_FIELDS", ModuleLinkCreator_Record_Model::module_module_list_view_filter_fields());
        $viewer->assign("MODULE_SUMMARY_FIELDS", ModuleLinkCreator_Record_Model::module_module_summary_fields());
        $viewer->assign("MODULE_QUICK_CREATE_FIELDS", ModuleLinkCreator_Record_Model::module_quick_create_fields());
        $viewer->assign("MODULE_LINKS", ModuleLinkCreator_Record_Model::module_links());
        $viewer->view("EditView.tpl", $moduleName);
    }
    /**
     * Retrieves css styles that need to loaded in the page
     * @param Vtiger_Request $request - request model
     * @return <array> - array of Vtiger_CssScript_Model
     */
    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = array("~/layouts/vlayout/modules/ModuleLinkCreator/resources/CreateModule.css");
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);
        return $headerCssInstances;
    }
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $jsFileNames = array();
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}

?>