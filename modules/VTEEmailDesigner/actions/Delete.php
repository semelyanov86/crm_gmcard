<?php

class VTEEmailDesigner_Delete_Action extends Vtiger_Delete_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $recordId = $request->get('record');
        $ajaxDelete = $request->get('ajaxDelete');
        $recordModel = EmailTemplates_Record_Model::getInstanceById($recordId);
        $moduleModel = $recordModel->getModule();
        $recordModel->delete($recordId);
        $listViewUrl = 'index.php?module=VTEEmailDesigner&view=List';
        $response = new Vtiger_Response();
        if ($recordModel->isSystemTemplate()) {
            $response->setError('502', vtranslate('LBL_NO_PERMISSIONS_TO_DELETE_SYSTEM_TEMPLATE', $moduleName));
        } else {
            if ($ajaxDelete) {
                $response->setResult($listViewUrl);
            } else {
                header('Location: ' . $listViewUrl);
            }
        }

        return $response;
    }
}
