<?php
/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class ITS4YouDescriptions_GetDescriptionContent_Action extends Vtiger_BasicAjax_Action
{
    public function checkPermission(Vtiger_Request $request) {}

    public function process(Vtiger_Request $request)
    {
        $return = ITS4YouDescriptions_Record_Model::getTemplateDescription($request->get('descriptionid'));
        $output = ['result' => $return, 'modulename' => $request->get('formodule'), 'fieldname' => $request->get('affected_textarea')];

        $response = new Vtiger_Response();
        $response->setResult($output);
        $response->emit();
    }
}
