<?php

class VReports_TabularReportWidget_Dashboard extends Vtiger_IndexAjax_View
{
    const REPORT_LIMIT = 500;
    public function process(Vtiger_Request $request)
    {
        $viewer = $this->getViewer($request);
        $moduleName = $request->getModule();
        $recordId = $request->get("reportid");
        $tabId = $request->get("tabid");
        $detailViewModel = VReports_DetailView_Model::getInstance($moduleName, $recordId);
        $reportModel = $detailViewModel->getRecord();
        $viewer->assign("REPORT_NAME", $reportModel->getName());
        $page = $request->get("page");
        $reportModel->setModule("VReports");
        $pagingModel = new Vtiger_Paging_Model();
        $pagingModel->set("page", $page);
        $pagingModel->set("limit", self::REPORT_LIMIT);
        $reportData = $reportModel->getReportData($pagingModel);
        $data = $reportData["data"];
        $calculation = $reportModel->getReportCalulationData();
        $count = $reportData["count"];
        $primaryModule = $reportModel->getPrimaryModule();
        $primaryModuleModel = Vtiger_Module_Model::getInstance($primaryModule);
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userPrivilegesModel = Users_Privileges_Model::getInstanceById($currentUser->getId());
        $permission = $userPrivilegesModel->hasModulePermission($primaryModuleModel->getId());
        if (!$permission) {
            $viewer->assign("MODULE", $primaryModule);
            $viewer->assign("MESSAGE", vtranslate("LBL_PERMISSION_DENIED"));
            $viewer->view("OperationNotPermitted.tpl", $primaryModule);
            exit;
        }
        $fieldsList = $reportData["fields_list"];
        $widget = VReports_Widget_Model::getInstanceWithReportId($recordId, $tabId);
        $widget->set("title", $reportModel->getName() . " (" . vtranslate($primaryModule, $primaryModule) . ")");
        $viewer->assign("WIDGET", $widget);
        $viewer->assign("REPORT_TYPE", "detail");
        $viewer->assign("CALCULATION_FIELDS", $calculation);
        $viewer->assign("DATA", $data);
        $viewer->assign("RECORD_ID", $recordId);
        $viewer->assign("PAGING_MODEL", $pagingModel);
        $viewer->assign("REPORT_MODEL", $reportModel);
        $viewer->assign("COUNT", $count);
        $viewer->assign("MODULE", $moduleName);
        $viewer->assign("HEADER_FIELDS_LIST", $fieldsList);
        $viewer->assign("REPORT_RUN_INSTANCE", VReportRun::getInstance($recordId));
        if (self::REPORT_LIMIT < count($data)) {
            $viewer->assign("LIMIT_EXCEEDED", true);
        }
        $content = $request->get("content");
        if (!empty($content)) {
            $viewer->view("dashboards/DashBoardWidgetContents.tpl", $moduleName);
        } else {
            $viewer->view("dashboards/DashBoardWidget.tpl", $moduleName);
        }
    }
}

?>