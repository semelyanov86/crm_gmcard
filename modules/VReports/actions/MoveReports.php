<?php

class VReports_MoveReports_Action extends Vtiger_Mass_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $moduleModel = VReports_Module_Model::getInstance($moduleName);
        $currentUserPriviligesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
        if (!$currentUserPriviligesModel->hasModulePermission($moduleModel->getId())) {
            throw new AppException(vtranslate("LBL_PERMISSION_DENIED"));
        }
    }
    public function process(Vtiger_Request $request)
    {
        $parentModule = "VReports";
        $reportIdsList = VReports_Record_Model::getRecordsListFromRequest($request);
        $folderId = $request->get("folderid");
        $viewname = $request->get("viewname");
        if ($folderId == $viewname) {
            $sameTargetFolder = 1;
        }
        if (!empty($reportIdsList)) {
            foreach ($reportIdsList as $reportId) {
                $reportModel = VReports_Record_Model::getInstanceById($reportId);
                if (!$reportModel->isDefault() && $reportModel->isEditable() && $reportModel->isEditableBySharing()) {
                    $reportModel->move($folderId);
                } else {
                    $reportsMoveDenied[] = vtranslate($reportModel->getName(), $parentModule);
                }
            }
        }
        $response = new Vtiger_Response();
        if ($sameTargetFolder) {
            $result = array("success" => false, "message" => vtranslate("LBL_SAME_SOURCE_AND_TARGET_FOLDER", $parentModule));
        } else {
            if (empty($reportsMoveDenied)) {
                $result = array("success" => true, "message" => vtranslate("LBL_REPORTS_MOVED_SUCCESSFULLY", $parentModule));
            } else {
                $result = array("success" => false, "message" => vtranslate("LBL_DENIED_REPORTS", $parentModule), "denied" => $reportsMoveDenied);
            }
        }
        $response->setResult($result);
        $response->emit();
    }
}

?>