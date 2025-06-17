<?php
/* ********************************************************************************
 * The content of this file is subject to the ITS4YouDescriptions license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * */

class ITS4YouDescriptions_GetControllsForField_View extends Vtiger_BasicAjax_View
{
    public function checkPermission(Vtiger_Request $request) {}

    public function process(Vtiger_Request $request)
    {
        $fieldlabel = $request->get('fieldlabel');
        $fieldname = $request->get('field');
        $moduleName = $request->getModule();
        $formodule = $request->get('formodule');

        $Descriptions = ITS4YouDescriptions_Module_Model::getDescriptionsForModule($formodule, $fieldname, $fieldlabel);

        if (!empty($Descriptions)) {
            $viewer = $this->getViewer($request);
            $viewer->assign('DESCRIPTIONS', $Descriptions);
            $viewer->assign('FIELDNAME', $fieldname);
            $viewer->assign('FIELDLABEL', $fieldlabel);
            $viewer->assign('FORMODULE', $formodule);
            $viewer->view('GetControllsForField.tpl', $moduleName);
        }
    }
}
