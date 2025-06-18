<?php

/*
 * The content of this file is subject to the EMAIL Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 ******************************************************************************* */

class EMAILMaker_SendEmail_View extends Vtiger_Action_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('sending');
    }

    public function checkPermission(Vtiger_Request $request)
    {
        // $moduleName = $request->getModule();

        // if (!Users_Privileges_Model::isPermitted($moduleName, 'EditView')){
        //        throw new AppException('LBL_PERMISSION_DENIED');
        // }
    }

    public function sending(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $viewer = $this->getViewer($request);
        $success = true;
        $esentid = $request->get('esentid');
        $message = $this->getSendingMsg($esentid);

        $viewer->assign('SUCCESS', $success);
        $viewer->assign('MESSAGE', $message);
        $viewer->view('SendEmailResult.tpl', $moduleName);
    }

    public function getSendingMsg($esentid)
    {
        $adb = PearDatabase::getInstance();
        $result = $adb->pquery('SELECT total_emails FROM vtiger_emakertemplates_sent WHERE esentid = ?', [$esentid]);
        $total_emails = $adb->query_result($result, 0, 'total_emails');
        $result2 = $adb->pquery("SELECT count(emailid) as total FROM vtiger_emakertemplates_emails WHERE status = '1' AND esentid = ?", [$esentid]);
        $sent_emails = $adb->query_result($result2, 0, 'total');

        if ($sent_emails == $total_emails) {
            if ($total_emails > 1) {
                $title = 'LBL_EMAILS_HAS_BEEN_SENT';
            } else {
                $title = 'LBL_EMAIL_HAS_BEEN_SENT';
            }
        } else {
            $title = 'LBL_EMAILS_DISTRIBUTION';
        }

        $content = $sent_emails . ' ' . vtranslate('LBL_EMAILS_SENT_FROM', 'EMAILMaker') . ' ' . $total_emails;

        return ['id' => $esentid, 'title' => vtranslate($title, 'EMAILMaker'), 'content' => $content];
    }
}
