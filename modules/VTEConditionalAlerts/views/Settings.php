<?php

class VTEConditionalAlerts_Settings_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        $redirectUrl = 'index.php?module=VTEConditionalAlerts&parent=Settings&view=ListAll&mode=listAll';
        header('Location: ' . $redirectUrl);
    }
}
