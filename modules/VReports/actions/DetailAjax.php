<?php

class VReports_DetailAjax_Action extends Vtiger_BasicAjax_Action
{
    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod("getRecordsCount");
    }
    public function process(Vtiger_Request $request)
    {
        $mode = $request->get("mode");
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }
    /**
     * Function to get related Records count from this relation
     * @param <Vtiger_Request> $request
     * @return <Number> Number of record from this relation
     */
    public function getRecordsCount(Vtiger_Request $request)
    {
        $record = $request->get("record");
        $reportModel = VReports_Record_Model::getInstanceById($record);
        $reportModel->setModule("VReports");
        $reportModel->set("advancedFilter", $request->get("advanced_filter"));
        $advFilterSql = $reportModel->getAdvancedFilterSQL();
        $query = $reportModel->getVReportSQL($advFilterSql, "PDF");
        $countQuery = $reportModel->generateCountQuery($query);
        $count = $reportModel->getVReportsCount($countQuery);
        $response = new Vtiger_Response();
        $response->setResult($count);
        $response->emit();
    }
}

?>