<?php

class VReports_SqlReportActions_Action extends VReports_TabularActions_Action
{
    public function __construct()
    {
        parent::__construct();
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    /**
     * Function to add the report chart to dashboard.
     */
    public function pinToDashboard(Vtiger_Request $request)
    {
        $db = PearDatabase::getInstance();
        $reportid = $request->get('reportid');
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $currentuserid = $currentUser->getId();
        $widgetTitle = $request->get('title');
        if (!$widgetTitle) {
            $widgetTitle = 'SqlReportWidget_' . $reportid;
        }
        $linkid = $request->get('linkid');
        $response = new Vtiger_Response();
        $dashBoardTabId = $request->get('dashBoardTabId');
        if (empty($dashBoardTabId)) {
            $dasbBoardModel = VReports_DashBoard_Model::getInstance('VReports');
            $defaultTab = $dasbBoardModel->getUserDefaultTab($currentUser->getId());
            $dashBoardTabId = $defaultTab['id'];
        }
        $query = 'SELECT 1 FROM vtiger_module_vreportdashboard_widgets WHERE reportid = ? AND userid = ? AND dashboardtabid = ?';
        $param = [$reportid, $currentuserid, $dashBoardTabId];
        $result = $db->pquery($query, $param);
        $numOfRows = $db->num_rows($result);
        if ($numOfRows >= 1) {
            $result = ['pinned' => false, 'duplicate' => true];
            $response->setResult($result);
            $response->emit();
        } else {
            if (!$linkid) {
                $linkid = VReports_Record_Model::getLinkId($reportid);
            }
            $query = 'INSERT INTO vtiger_module_vreportdashboard_widgets (userid,reportid,linkid,title,dashboardtabid) VALUES (?,?,?,?,?)';
            $param = [$currentuserid, $reportid, $linkid, $widgetTitle, $dashBoardTabId];
            $result = $db->pquery($query, $param);
            $widgetRecordModel = VReports_Widget_Model::getInstanceWithReportId($reportid, $dashBoardTabId);
            $dataUrl['url'] = $widgetRecordModel->getUrl();
            $dataUrl['urlDetail'] = $widgetRecordModel->getUrlReportDetail();
            $dataUrl['urlEdit'] = $widgetRecordModel->getUrlReportEdit();
            $dataUrl['urlDelete'] = $widgetRecordModel->getDeleteUrl();
            $result = ['pinned' => true, 'duplicate' => false, 'dataUrl' => $dataUrl];
            $response->setResult($result);
            $response->emit();
        }
    }
}
