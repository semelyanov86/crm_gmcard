<?php

class VTEButtons_Edit_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
    }
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    public function preProcess(Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request);
        $adb = PearDatabase::getInstance();
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign("QUALIFIED_MODULE", $module);
    }
    public function process(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $adb = PearDatabase::getInstance();
        $mode = $request->getMode();
                if ($mode) {
                    $this->{$mode}($request);
                } else {
                    $this->renderSettingsUI($request);
                }
    }
    public function step2(Vtiger_Request $request)
    {
        global $site_URL;
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->assign("SITE_URL", $site_URL);
        $viewer->view("Step2.tpl", $module);
    }
    public function step3(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $viewer = $this->getViewer($request);
        $viewer->view("Step3.tpl", $module);
    }
    public function renderSettingsUI(Vtiger_Request $request)
    {
        global $vtiger_current_version;
        $record = $request->get("record");
        $module = $request->getModule();
        $moduleModels = Vtiger_Module_Model::getEntityModules();
        $VTEModuleModel = Vtiger_Module_Model::getInstance($module);
        $entityModuleModels = array();
        foreach ($moduleModels as $tabId => $moduleModel) {
            $entityModuleModels[$tabId] = $moduleModel;
        }
        $allModules = $entityModuleModels;
        $viewer = $this->getViewer($request);
        $viewer->assign("MODULE", $module);
        $product_module_model = Vtiger_Module_Model::getInstance("Products");
        $service_module_model = Vtiger_Module_Model::getInstance("Services");
        $viewer->assign("PRODUCT_MODULE", $product_module_model);
        $viewer->assign("SERVICE_MODULE", $service_module_model);
        $viewer->assign("ALL_MODULES", $allModules);
        $advance_criteria = NULL;
        $selected_members = array();
        if (0 < $record) {
            $module_model = Vtiger_Module_Model::getInstance($module);
            $Entries = $module_model->getlistViewEntries("id=" . $record);
            $recordentries = $Entries[0];
            $selectd_module = Vtiger_Module_Model::getInstance($recordentries["module"]);
            $recordStructureModel = VTEButtons_RecordStructure_Model::getInstanceForModule($selectd_module);
            $recordStructure = $recordStructureModel->getStructure();
            $picklist_fields = array();
            $selected_module_fields = $selectd_module->getFields();
            foreach ($selected_module_fields as $field_obj) {
                $field_type = $field_obj->getFieldDataType();
                $field_name = $field_obj->get("name");
                $field_label = $field_obj->get("label");
                if ($field_type == "picklist") {
                    $picklist_fields[$field_name] = vtranslate($field_label, $recordentries["module"]);
                }
            }
            $viewer->assign("PICKLIST_FIELDS", $picklist_fields);
            if (!empty($recordentries["automated_update_field"])) {
                $source_moduleModel = Vtiger_Module_Model::getInstance($recordentries["module"]);
                $field_model = Vtiger_Field_Model::getInstance($recordentries["automated_update_field"], $source_moduleModel);
                $values = $field_model->getPicklistValues();
                $viewer->assign("SELECTED_PICKLIST_FIELD_VALUES", $values);
            }
            $active_module = $recordentries["module"];
            $advance_criteria = json_decode(html_entity_decode($recordentries["conditions"], ENT_QUOTES), true);
            $advanceFilterOpsByFieldType = Vtiger_Field_Model::getAdvancedFilterOpsByFieldType();
            $viewer->assign("ADVANCED_FILTER_OPTIONS", Vtiger_Field_Model::getAdvancedFilterOptions());
            $viewer->assign("ADVANCED_FILTER_OPTIONS_BY_TYPE", $advanceFilterOpsByFieldType);
            $dateFilters = Vtiger_Field_Model::getDateFilterTypes();
            foreach ($dateFilters as $comparatorKey => $comparatorInfo) {
                $comparatorInfo["startdate"] = DateTimeField::convertToUserFormat($comparatorInfo["startdate"]);
                $comparatorInfo["enddate"] = DateTimeField::convertToUserFormat($comparatorInfo["enddate"]);
                $comparatorInfo["label"] = vtranslate($comparatorInfo["label"], $active_module);
                $dateFilters[$comparatorKey] = $comparatorInfo;
            }
            $members = stripslashes(html_entity_decode($recordentries["members"]));
            $selected_members = json_decode($members);
            $viewer->assign("DATE_FILTERS", $dateFilters);
            $viewer->assign("ADVANCE_CRITERIA", $advance_criteria);
            $viewer->assign("SOURCE_MODULE", $active_module);
            $viewer->assign("MODULE_MODEL", $module_model);
            $viewer->assign("SELECTED_MODULE_NAME", $recordentries["module"]);
            $viewer->assign("RECORD", $record);
            $viewer->assign("RECORDENTRIES", $recordentries);
            $viewer->assign("RECORD_STRUCTURE", $recordStructure);
            $viewer->assign("SELECTED_MEMBERS", $selected_members);
        }
        $recordModel = new Settings_Groups_Record_Model();
        $viewer->assign("MEMBER_GROUPS", Settings_Groups_Member_Model::getAll());
        $viewer->assign("RECORD_MODEL", $recordModel);
        if (version_compare($vtiger_current_version, "7.0.0", ">=")) {
            $input_lines = file_get_contents("layouts/v7/lib/vt-icons/style.css");
            preg_match_all("/(.vicon-[a-z]+)|(\\\\[a-z0-9]+)/", $input_lines, $output_array);
            $output_array = $output_array[0];
            unset($output_array[0]);
            $arrResults = array_chunk($output_array, 2);
            $arrIconClasses = array();
            foreach ($arrResults as $cssDetail) {
                $arrIconClasses[str_replace(".", "", $cssDetail[0])] = $cssDetail[1];
            }
            $viewer->assign("LISTICONS", $arrIconClasses);
        }
        echo $viewer->view("Edit.tpl", $module, true);
    }
    public function getHeaderCss(Vtiger_Request $request)
    {
        $headerCssInstances = parent::getHeaderCss($request);
        $cssFileNames = array("~layouts/v7/modules/VTEButtons/resources/style.css", "~/libraries/jquery/bootstrapswitch/css/bootstrap3/bootstrap-switch.min.css");
        $cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
        $headerCssInstances = array_merge($headerCssInstances, $cssInstances);
        return $headerCssInstances;
    }
    /**
     * Function to get the list of Script models to be included
     * @param Vtiger_Request $request
     * @return <Array> - List of Vtiger_JsScript_Model instances
     */
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $headerScriptInstances = parent::getHeaderScripts($request);
        $moduleName = $request->getModule();
        $jsFileNames = array("modules.VTEButtons.resources.Edit", "modules." . $moduleName . ".resources.AdvanceFilter");
        $jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
        $headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
        return $headerScriptInstances;
    }
}

?>