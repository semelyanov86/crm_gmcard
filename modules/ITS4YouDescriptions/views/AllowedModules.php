<?php
 

class ITS4YouDescriptions_AllowedModules_View extends Vtiger_Index_View
{
    protected $isInstalled = false;

    public function __construct()
    {
        parent::__construct();
        $class = explode('_', get_class($this));
        $this->isInstalled = true;
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        if (!$currentUserModel->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }
    }

    public function process(Vtiger_Request $request)
    {
        $this->getProcess($request);
    }

    
    public function getProcess(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $viewer->assign('SUPPORTED_MODULES', ITS4YouDescriptions_AllowedModules_Model::getSupportedModules(true));
        $viewer->assign('VIEW', $request->get('view'));
        $viewer->assign('MODULE_MODEL', $moduleModel);
        $viewer->assign('CURRENT_USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('CURRENT_MODULE', $request->get('module'));
        $viewer->view('AllowedModules.tpl', $moduleName);
    }

    
    public function getHeaderScripts(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $jsFileNames = array(
            '~/libraries/jquery/bootstrapswitch/js/bootstrap-switch.min.js',
            "modules.$moduleName.resources.AllowedModules",
        );

        return array_merge(parent::getHeaderScripts($request), $this->checkAndConvertJsScripts($jsFileNames));
    }

    
    public function getHeaderCss(Vtiger_Request $request)
    {
        $module = $request->getModule();
        $cssFileNames = array(
            '~/libraries/jquery/bootstrapswitch/css/bootstrap3/bootstrap-switch.min.css',
            '~layouts/' . Vtiger_Viewer::getDefaultLayoutName() . '/modules/' . $module . '/resources/AllowedModules.css',
        );

        return array_merge(parent::getHeaderCss($request), $this->checkAndConvertCssStyles($cssFileNames));
    }
} ?>
