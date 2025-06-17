<?php

class VReports_MiniListWizard_View extends Vtiger_MiniListWizard_View
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function process(Vtiger_Request $request)
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("WIDGET_NAME", "MiniList");
        $viewer->assign("WIZARD_STEP", $request->get("step"));
        $viewer->assign("USER_MODEL", Users_Record_Model::getCurrentUserModel());
        switch ($request->get("step")) {
            case "step1":
                $modules = Vtiger_Module_Model::getSearchableModules();
                unset($modules["ModComments"]);
                $viewer->assign("MODULES", $modules);
                break;
            case "step2":
                $selectedModule = $request->get("selectedModule");
                $filters = CustomView_Record_Model::getAllByGroup($selectedModule, false);
                $viewer->assign("ALLFILTERS", $filters);
                break;
            case "step3":
                $selectedModule = $request->get("selectedModule");
                $filterid = $request->get("filterid");
                $moduleModel = Vtiger_Module_Model::getInstance($selectedModule);
                $recordStructureInstance = Vtiger_RecordStructure_Model::getInstanceForModule($moduleModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_FILTER);
                $recordStructure = $recordStructureInstance->getStructure();
                $viewer->assign("RECORD_STRUCTURE", $recordStructure);
                $viewer->assign("FILTER", $filterid);
                $viewer->assign("SELECTED_MODULE", $selectedModule);
                $db = PearDatabase::getInstance();
                $generator = new EnhancedQueryGenerator($selectedModule, $currentUser);
                $generator->initForCustomViewById($filterid);
                $listviewController = new ListViewController($db, $currentUser, $generator);
                $moduleFields = $generator->getModuleFields();
                $fields = $generator->getFields();
                $headerFields = array();
                foreach ($fields as $fieldName) {
                    if (array_key_exists($fieldName, $moduleFields)) {
                        $fieldModel = $moduleFields[$fieldName];
                        if ($fieldModel->getPresence() == 1) {
                            continue;
                        }
                        $headerFields[] = $fieldName;
                    }
                }
                $viewer->assign("HEADER_FIELDS", $headerFields);
                $viewer->assign("LIST_VIEW_CONTROLLER", $listviewController);
                $viewer->assign("SELECTED_MODULE", $selectedModule);
                break;
        }
        $viewer->view("dashboards/MiniListWizard.tpl", $moduleName);
    }
}

?>