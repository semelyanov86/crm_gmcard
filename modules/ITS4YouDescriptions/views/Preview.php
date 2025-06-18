<?php

/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class ITS4YouDescriptions_Preview_View extends Vtiger_BasicAjax_View
{
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->get('module');

        $viewer = $this->getViewer($request);
        $viewer->view('Preview.tpl', $moduleName);
    }
}
