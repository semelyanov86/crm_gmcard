<?php

class ChecklistItems_UpdateChecklistItem_Action extends Vtiger_Save_Action
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
        $mode = $request->get("mode");
        if ($mode == "Status") {
            $this->updateStatus($request);
        } else {
            if ($mode == "AddComment") {
                $this->AddComment($request);
            } else {
                if ($mode == "DateTime") {
                    $this->updateDateTime($request);
                } else {
                    $result = false;
                    $response = new Vtiger_Response();
                    $response->setResult($result);
                    $response->emit();
                    exit;
                }
            }
        }
    }
    public function AddComment($request)
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $moduleName = $request->getModule();
        $record = $request->get("checklistitemsid");
        $commentcontent = $request->get("comment");
        $commentRecordModel = Vtiger_Record_Model::getCleanInstance("ModComments");
        $modelData = $commentRecordModel->getData();
        $commentRecordModel->set("mode", "");
        $commentRecordModel->set("commentcontent", $commentcontent);
        $commentRecordModel->set("related_to", $record);
        $commentRecordModel->set("assigned_user_id", $currentUserModel->getId());
        $commentRecordModel->set("userid", $currentUserModel->getId());
        $commentRecordModel->save();
        $result = "";
        if (0 < $commentRecordModel->getId()) {
            $createdtime = new DateTimeField($commentRecordModel->get("createdtime"));
            $result = "<li class=\"commentDetails\">";
            $result .= "<p>";
            $result .= $commentcontent;
            $result .= "</p>";
            $result .= "<p><small>";
            $result .= $currentUserModel->getDisplayName() . " | " . $createdtime->getDisplayDateTimeValue($currentUserModel);
            $result .= "</small></p>";
            $result .= "</li>";
        }
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
        exit;
    }
    public function updateStatus($request)
    {
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $currentDate = new DateTimeField("");
        $currDateDisplay = $currentDate->getDisplayDate($currentUserModel);
        $currTimeDisplay = $currentDate->getDisplayTime($currentUserModel);
        if ($currentUserModel->get("hour_format") == 12) {
            $currTimeDisplay = Vtiger_Time_UIType::getTimeValueInAMorPM($currTimeDisplay);
        }
        $currDateInsert = $currentDate->getDBInsertDateValue($currentUserModel);
        $currTimeInsert = $currentDate->getDBInsertTimeValue($currentUserModel);
        $moduleName = $request->getModule();
        $record = $request->get("record");
        $status = $request->get("status");
        $recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleName);
        $statusValue = "";
        if ($status == "Checked") {
            $statusValue = "Q";
        } else {
            if ($status == "Q") {
                $statusValue = "Excl";
            } else {
                if ($status == "Excl") {
                    $statusValue = "X";
                } else {
                    if ($status == "X") {
                        $statusValue = "";
                    } else {
                        $statusValue = "Checked";
                    }
                }
            }
        }
        $recordModel->set("checklistitem_status", $statusValue);
        $recordModel->set("status_date", $currDateInsert);
        $recordModel->set("status_time", $currTimeInsert);
        $recordModel->set("mode", "edit");
        $recordModel->save();
        $result = array("status" => $statusValue, "currDate" => $currDateDisplay, "currTime" => $currTimeDisplay);
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
        exit;
    }
    public function updateDateTime($request)
    {
        $newDateTime = date("Y-m-d h:i:s");
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $moduleName = $request->getModule();
        $record = $request->get("record");
        $datetime = $request->get("datetime");
        if ($datetime) {
            list($new_date, $new_time) = explode(" ", $datetime);
            $newTimeSeconds = Vtiger_Time_UIType::getTimeValueWithSeconds($new_time);
            $newDate = DateTimeField::convertToDBFormat($new_date, $currentUserModel);
            $newDateTime = $newDate . " " . $newTimeSeconds;
        }
        $currentDate = new DateTimeField($newDateTime);
        $currDateInsert = $currentDate->getDBInsertDateValue($currentUserModel);
        $currTimeInsert = $currentDate->getDBInsertTimeValue($currentUserModel);
        $recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleName);
        $recordModel->set("status_date", $currDateInsert);
        $recordModel->set("status_time", $currTimeInsert);
        $recordModel->set("mode", "edit");
        $recordModel->save();
        $datetimeObj = new DateTimeField($currDateInsert . " " . $currTimeInsert);
        $currDateDisplay = $datetimeObj->getDisplayDate($currentUserModel);
        $currTimeDisplay = $datetimeObj->getDisplayTime($currentUserModel);
        if ($currentUserModel->get("hour_format") == 12) {
            $currTimeDisplay = Vtiger_Time_UIType::getTimeValueInAMorPM($currTimeDisplay);
        }
        $result = array("currDate" => $currDateDisplay, "currTime" => $currTimeDisplay);
        $response = new Vtiger_Response();
        $response->setResult($result);
        $response->emit();
        exit;
    }
}

?>