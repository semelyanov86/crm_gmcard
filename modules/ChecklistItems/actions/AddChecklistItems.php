<?php

class ChecklistItems_AddChecklistItems_Action extends Vtiger_Save_Action
{
    public function checkPermission(Vtiger_Request $request)
    {
        return true;
    }
    public function __construct()
    {
        parent::__construct();
    }
    
    public function process(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $source_module = $request->get("source_module");
        $source_record = $request->get("source_record");
        $checklistid = $request->get("checklistid");
        $user = Users_Record_Model::getCurrentUserModel();
        if (empty($checklistid)) {
            $result = 0;
            $response = new Vtiger_Response();
            $response->setResult($result);
            $response->emit();
            exit;
        }
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $checklist_setting_items = $moduleModel->getChecklistItemsSetting($checklistid);
        foreach ($checklist_setting_items as $item) {
            $existingChecklistItemID = $moduleModel->GetExistingChecklistItemID($source_record, $checklistid, $item["itemid"], $item["title"]);
            if (0 < $existingChecklistItemID) {
                continue;
            }
            $recordModel = Vtiger_Record_Model::getCleanInstance("ChecklistItems");
            $recordModel->set("mode", "");
            $recordModel->set("title", $item["title"]);
            $recordModel->set("allow_upload", $item["allow_upload"]);
            $recordModel->set("allow_note", $item["allow_note"]);
            $recordModel->set("checklistname", $item["checklistname"]);
            $recordModel->set("category", $item["category"]);
            $recordModel->set("description", $item["description"]);
            $recordModel->set("checklistitem_status", "");
            $recordModel->set("assigned_user_id", $user->getId());
            $recordModel->set("parent_id", $source_record);
            $recordModel->set("settings_item_id", $item["itemid"]);
            $recordModel->save();
            $recordId = $recordModel->getId();
        }
        $result = 1;
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
        exit;
    }
}

?>