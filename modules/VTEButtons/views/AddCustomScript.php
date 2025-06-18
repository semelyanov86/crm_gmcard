<?php

class VTEButtons_AddCustomScript_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        global $adb;
        $moduleName = $request->getModule();
        $qualifiedModuleName = $request->get('module');
        $moduleModel = VTEButtons_Module_Model::getInstance($qualifiedModuleName);
        $result = $adb->pquery('SELECT * FROM `vte_buttons_customjs`', []);
        if ($adb->num_rows($result)) {
            $isActive = $adb->query_result($result, 0, 'is_active');
            $custom_script = $adb->query_result($result, 0, 'custom_script');
        }
        $viewer = $this->getViewer($request);
        $viewer->assign('ISACTIVE', $isActive);
        $viewer->assign('CUSTOM_SCRIPT', $custom_script);
        $viewer->assign('MODULE', $moduleName);
        $viewer->assign('MODULE_MODEL', $moduleModel);
        $viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
        $viewer->view('CustomScript.tpl', $qualifiedModuleName);
    }
}
