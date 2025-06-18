<?php

class VTEEmailDesigner_MassDelete_Action extends Vtiger_Mass_Action
{
    public function checkPermission()
    {
        return true;
    }

    public function preProcess(Vtiger_Request $request)
    {
        return true;
    }

    public function postProcess(Vtiger_Request $request)
    {
        return true;
    }

    public function process(Vtiger_Request $request)
    {
        $db = PearDatabase::getInstance();
        $moduleName = $request->getModule();
        $recordModel = new VTEEmailDesigner_Record_Model();
        $recordModel->setModule($moduleName);
        $selectedIds = $request->get('selected_ids');
        $excludedIds = $request->get('excluded_ids');
        if ($selectedIds == 'all' && empty($excludedIds)) {
            $recordModel->deleteAllRecords();
        } else {
            $recordIds = $this->getRecordsListFromRequest($request, $recordModel);
            foreach ($recordIds as $recordId) {
                $sql = 'SELECT * FROM vtiger_emailtemplates WHERE templateid = ?';
                $params = [$recordId];
                $result = $db->pquery($sql, $params);
                if ($db->num_rows($result) > 0) {
                    $recordModel = VTEEmailDesigner_Record_Model::getInstanceById($recordId);
                    $recordModel->delete();
                }
            }
        }
        $response = new Vtiger_Response();
        $response->setResult(['module' => $moduleName]);
        $response->emit();
    }

    public function getRecordsListFromRequest(Vtiger_Request $request, $recordModel)
    {
        $selectedIds = $request->get('selected_ids');
        $excludedIds = $request->get('excluded_ids');
        if (!empty($selectedIds) && $selectedIds != 'all' && !empty($selectedIds) && count($selectedIds) > 0) {
            return $selectedIds;
        }
        if (!empty($excludedIds)) {
            $moduleModel = $recordModel->getModule();
            $recordIds = $moduleModel->getRecordIds($excludedIds);

            return $recordIds;
        }
    }
}
