<?php

class VTEButtons_HeaderIcon_View extends Vtiger_IndexAjax_View
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
        $sql = "SELECT * FROM `vte_buttons_settings` WHERE module='" . $moduleSelected . "' AND active = 1 ORDER BY sequence";
        $results = $adb->pquery($sql, []);
        $header_array = [];

        while ($row = $adb->fetchByAssoc($results)) {
            $moduleName = $row['module'];
            $vtebuttonsId = $row['id'];
            $can_view = true;
            $moduleModel = new VTEButtons_Module_Model();
            $conditions = $moduleModel->getConditionalShowButtons($vtebuttonsId, $moduleName);
            if (!empty($conditions)) {
                foreach ($conditions as $condition) {
                    $can_view = $moduleModel->getRecordsByCondition($condition, $record_id);
                    $members = $moduleModel->checkIsMembers($condition, $record_id);
                    if (!empty($can_view) && $members == true) {
                        $header_array[] = ['vtebuttonsid' => $row['id'], 'module' => $row['module'], 'header' => $row['header'], 'icon' => $row['icon'], 'color' => $row['color'], 'sequence' => $row['sequence']];
                    }
                }
            }
        }
        $viewer->assign('HEADERS', $header_array);
        $VTECUSTOMHEADER = false;
        $VTEPROGRESSBAR = false;
        $sql = "SELECT * FROM `vte_custom_header_settings` WHERE module='" . $moduleSelected . "' AND active = 1";
        $re = $adb->pquery($sql, []);
        if ($adb->num_rows($re) > 0) {
            $VTECUSTOMHEADER = true;
        }
        $sql = "SELECT * FROM `vte_progressbar_settings` WHERE module='" . $moduleSelected . "' AND active = 1";
        $re = $adb->pquery($sql, []);
        if ($adb->num_rows($re) > 0) {
            $VTEPROGRESSBAR = true;
        }
        $viewer->assign('VTEPROGRESSBAR', $VTEPROGRESSBAR);
        $viewer->assign('VTECUSTOMHEADER', $VTECUSTOMHEADER);
        echo $viewer->view('HeaderIcon.tpl', $module, true);
    }

    public function extract_emails($str)
    {
        $regexp = '/([a-z0-9_\\.\\-])+\\@(([a-z0-9\\-])+\\.)+([a-z0-9]{2,4})+/i';
        preg_match_all($regexp, $str, $m);

        return $m[0] ?? [];
    }
}
