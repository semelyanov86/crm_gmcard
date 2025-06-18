<?php

class Settings_VTEAdvanceMenu_MenuAjax_View extends Settings_Vtiger_Index_View
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('showAddModule');
        $this->exposeMethod('showAddLink');
        $this->exposeMethod('showAddFilter');
        $this->exposeMethod('showAddSeparator');
        $this->exposeMethod('getModuleFilter');
        $this->exposeMethod('showEditGroup');
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->getMode();
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    public function showAddModule(Vtiger_Request $request)
    {
        $all_visible_modules = Settings_VTEAdvanceMenu_Module_Model::getAllVisibleModules();
        $viewer = $this->getViewer($request);
        $qualifiedModuleName = $request->getModule(false);
        $viewer->assign('ALL_VISIBLE_MODULES', $all_visible_modules);
        $viewer->assign('MENU_ID', $request->get('menu_id'));
        $viewer->assign('GROUP_ID', $request->get('group_id'));
        $viewer->assign('MODULE', $request->getModule());
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->view('AddModule.tpl', $qualifiedModuleName);
    }

    public function showAddLink(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $qualifiedModuleName = $request->getModule(false);
        $viewer->assign('MENU_ID', $request->get('menu_id'));
        $viewer->assign('GROUP_ID', $request->get('group_id'));
        $viewer->assign('MODULE', $request->getModule());
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->view('AddLink.tpl', $qualifiedModuleName);
    }

    public function showAddFilter(Vtiger_Request $request)
    {
        $all_visible_modules = Settings_VTEAdvanceMenu_Module_Model::getAllVisibleModules();
        $viewer = $this->getViewer($request);
        $qualifiedModuleName = $request->getModule(false);
        $viewer->assign('ALL_VISIBLE_MODULES', $all_visible_modules);
        $viewer->assign('MENU_ID', $request->get('menu_id'));
        $viewer->assign('GROUP_ID', $request->get('group_id'));
        $viewer->assign('MODULE', $request->getModule());
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->view('AddFilter.tpl', $qualifiedModuleName);
    }

    public function getModuleFilter(Vtiger_Request $request)
    {
        $source_module = $request->get('source_module');
        $instance = new Settings_VTEAdvanceMenu_Module_Model();
        $filters = $instance->getModuleFilter($source_module);
        $response = new Vtiger_Response();
        $response->setResult($filters);
        $response->emit();
    }

    public function showAddSeparator(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $qualifiedModuleName = $request->getModule(false);
        $appName = $request->get('appname');
        $viewer->assign('SELECTED_APP_NAME', $appName);
        $viewer->assign('MODULE', $request->getModule());
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->view('AddModule.tpl', $qualifiedModuleName);
    }

    public function showEditGroup(Vtiger_Request $request)
    {
        $group_id = $request->get('group_id');
        $instance = new Settings_VTEAdvanceMenu_Module_Model();
        $group_info = $instance->getGroupDetails($group_id);
        $viewer = $this->getViewer($request);
        $qualifiedModuleName = $request->getModule(false);
        $viewer->assign('GROUP_INFO', $group_info);
        $viewer->assign('MODULE', $request->getModule());
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        echo $viewer->view('EditGroup.tpl', $qualifiedModuleName);
    }
}
