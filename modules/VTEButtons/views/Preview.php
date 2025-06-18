<?php

class VTEButtons_Preview_View extends Vtiger_IndexAjax_View
{
    public function __construct()
    {
        parent::__construct();
    }

    public function process(Vtiger_Request $request)
    {
        $moduleSelected = $request->get('moduleSelected');
        $module = 'VTEButtons';
        $record_id = $request->get('record');
        $viewer = $this->getViewer($request);
        global $adb;
        $sql = "SELECT * FROM `vte_buttons_settings` WHERE id='" . $record_id . "'";
        $results = $adb->pquery($sql, []);
        $header = [];

        while ($row = $adb->fetchByAssoc($results)) {
            $header = $row;
        }
        $viewer->assign('HEADER', $header);
        echo $viewer->view('Preview.tpl', $module, true);
    }
}
