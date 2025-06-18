<?php

class VTEConditionalAlerts_PopupAlert_View extends Vtiger_IndexAjax_View
{
    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $actions = $request->get('actions_list');
        $viewer->assign('HEADER_TITLE', vtranslate('LBL_ALERT', 'VTEConditionalAlerts'));
        $viewer->assign('ACTIONS', $actions);
        $viewer->assign('MODULE', $moduleName);
        $viewer->view('PopupAlert.tpl', $moduleName);
    }
}
