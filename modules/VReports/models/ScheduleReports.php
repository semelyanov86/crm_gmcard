<?php

require_once "vtlib/Vtiger/Cron.php";

class VReports_ScheduleReports_Model extends Vtiger_Base_Model
{
    public static $SCHEDULED_DAILY = 1;
    public static $SCHEDULED_WEEKLY = 2;
    public static $SCHEDULED_MONTHLY_BY_DATE = 3;
    public static $SCHEDULED_ANNUALLY = 4;
    public static $SCHEDULED_ON_SPECIFIC_DATE = 5;
    public static function getInstance()
    {
        return new self();
    }
    /**
     * Function returns the Scheduled Reports Model instance
     * @param <Number> $recordId
     * @return <Reports_ScehduleReports_Model>
     */
    public static function getInstanceById($recordId)
    {
        $db = PearDatabase::getInstance();
        $scheduledReportModel = new self();
        if (!empty($recordId)) {
            $scheduledReportResult = $db->pquery("SELECT * FROM vtiger_schedulevreports WHERE reportid = ?", array($recordId));
            if (0 < $db->num_rows($scheduledReportResult)) {
                $reportScheduleInfo = $db->query_result_rowdata($scheduledReportResult, 0);
                $reportScheduleInfo["specificemails"] = decode_html($reportScheduleInfo["specificemails"]);
                $reportScheduleInfo["schdate"] = decode_html($reportScheduleInfo["schdate"]);
                $reportScheduleInfo["schdayoftheweek"] = decode_html($reportScheduleInfo["schdayoftheweek"]);
                $reportScheduleInfo["schdayofthemonth"] = decode_html($reportScheduleInfo["schdayofthemonth"]);
                $reportScheduleInfo["schannualdates"] = decode_html($reportScheduleInfo["schannualdates"]);
                $reportScheduleInfo["recipients"] = decode_html($reportScheduleInfo["recipients"]);
                $reportScheduleInfo["from_address"] = decode_html($reportScheduleInfo["from_address"]);
                $reportScheduleInfo["subject_mail"] = decode_html($reportScheduleInfo["subject"]);
                $reportScheduleInfo["content_mail"] = $reportScheduleInfo["body"];
                $reportScheduleInfo["signature"] = $reportScheduleInfo["signature"];
                $reportScheduleInfo["signature_user"] = $reportScheduleInfo["signature_user"];
                $reportScheduleInfo["fileformat"] = $reportScheduleInfo["fileformat"];
                $scheduledReportModel->setData($reportScheduleInfo);
            }
        }
        return $scheduledReportModel;
    }
    /**
     * Function to save the  Scheduled Reports data
     */
    public function saveScheduleReport()
    {
        $adb = PearDatabase::getInstance();
        $reportid = $this->get("reportid");
        $scheduleid = $this->get("scheduleid");
        $schtime = $this->get("schtime");
        if (!preg_match("/^[0-2]\\d(:[0-5]\\d){1,2}\$/", $schtime) || 23 < substr($schtime, 0, 2)) {
            $schtime = "00:00";
        }
        $schtime .= ":00";
        $this->set("schtime", $schtime);
        $schdate = NULL;
        $schdayoftheweek = NULL;
        $schdayofthemonth = NULL;
        $schannualdates = NULL;
        if ($scheduleid == self::$SCHEDULED_ON_SPECIFIC_DATE) {
            $date = $this->get("schdate");
            $dateDBFormat = DateTimeField::convertToDBFormat($date);
            $nextTriggerTime = $dateDBFormat . " " . $schtime;
            $currentTime = date("Y-m-d H:i:s");
            $user = Users::getActiveAdminUser();
            $dateTime = new DateTimeField($nextTriggerTime);
            $nextTriggerTime = $dateTime->getDBInsertDateTimeValue($user);
            if ($currentTime < $nextTriggerTime) {
                $this->set("next_trigger_time", $nextTriggerTime);
            } else {
                $this->set("next_trigger_time", date("Y-m-d H:i:s", strtotime("+10 year")));
            }
            $schdate = Zend_Json::encode(array($dateDBFormat));
        } else {
            if ($scheduleid == self::$SCHEDULED_WEEKLY) {
                $schdayoftheweek = Zend_Json::encode($this->get("schdayoftheweek"));
                $this->set("schdayoftheweek", $schdayoftheweek);
            } else {
                if ($scheduleid == self::$SCHEDULED_MONTHLY_BY_DATE) {
                    $schdayofthemonth = Zend_Json::encode($this->get("schdayofthemonth"));
                    $this->set("schdayofthemonth", $schdayofthemonth);
                } else {
                    if ($scheduleid == self::$SCHEDULED_ANNUALLY) {
                        $schannualdates = Zend_Json::encode($this->get("schannualdates"));
                        $this->set("schannualdates", $schannualdates);
                    }
                }
            }
        }
        $recipients = Zend_Json::encode($this->get("recipients"));
        $specificemails = Zend_Json::encode($this->get("specificemails"));
        $from_address = $this->get("from_address");
        $subject = $this->get("subject_mail");
        $body = $this->get("content_mail");
        $signature = $this->get("signature");
        $signature_user = $this->get("signature_user");
        if ($signature != "Yes") {
            $signature_user = NULL;
        }
        $isReportScheduled = $this->get("isReportScheduled");
        $fileFormat = $this->get("fileformat");
        if ($scheduleid != self::$SCHEDULED_ON_SPECIFIC_DATE) {
            $nextTriggerTime = $this->getNextTriggerTime();
        }
        if ($isReportScheduled == "0" || $isReportScheduled == "" || $isReportScheduled == false) {
            $deleteScheduledReportSql = "DELETE FROM vtiger_schedulevreports WHERE reportid=?";
            $adb->pquery($deleteScheduledReportSql, array($reportid));
        } else {
            $checkScheduledResult = $adb->pquery("SELECT next_trigger_time FROM vtiger_schedulevreports WHERE reportid=?", array($reportid));
            if (0 < $adb->num_rows($checkScheduledResult)) {
                $scheduledReportSql = "UPDATE vtiger_schedulevreports SET scheduleid=?, recipients=?, schdate=?, schtime=?, schdayoftheweek=?, schdayofthemonth=?, schannualdates=?, specificemails=?, from_address=?, subject=?, body=?, next_trigger_time=?, fileformat = ?, signature = ?, signature_user =? WHERE reportid=?";
                $adb->pquery($scheduledReportSql, array($scheduleid, $recipients, $schdate, $schtime, $schdayoftheweek, $schdayofthemonth, $schannualdates, $specificemails, $from_address, $subject, $body, $nextTriggerTime, $fileFormat, $signature, $signature_user, $reportid));
            } else {
                $scheduleReportSql = "INSERT INTO vtiger_schedulevreports (reportid,scheduleid,recipients,schdate,schtime,schdayoftheweek,schdayofthemonth,schannualdates,next_trigger_time,specificemails,from_address,subject,body,fileformat,signature,signature_user) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $adb->pquery($scheduleReportSql, array($reportid, $scheduleid, $recipients, $schdate, $schtime, $schdayoftheweek, $schdayofthemonth, $schannualdates, $nextTriggerTime, $specificemails, $from_address, $subject, $body, $fileFormat, $signature, $signature_user));
            }
        }
    }
    public function getRecipientEmails()
    {
        $recipientsInfo = $this->get("recipients");
        if (!empty($recipientsInfo)) {
            $recipients = array();
            $recipientsInfo = Zend_Json::decode($recipientsInfo);
            foreach ($recipientsInfo as $key => $recipient) {
                if (strpos($recipient, "USER") !== false) {
                    $id = explode("::", $recipient);
                    $recipients["Users"][] = $id[1];
                } else {
                    if (strpos($recipient, "GROUP") !== false) {
                        $id = explode("::", $recipient);
                        $recipients["Groups"][] = $id[1];
                    } else {
                        if (strpos($recipient, "ROLE") !== false) {
                            $id = explode("::", $recipient);
                            $recipients["Roles"][] = $id[1];
                        }
                    }
                }
            }
        }
        $recipientsList = array();
        if (!empty($recipients)) {
            if (!empty($recipients["Users"])) {
                $recipientsList = array_merge($recipientsList, $recipients["Users"]);
            }
            if (!empty($recipients["Roles"])) {
                foreach ($recipients["Roles"] as $roleId) {
                    $roleUsers = getRoleUsers($roleId);
                    foreach ($roleUsers as $userId => $userName) {
                        array_push($recipientsList, $userId);
                    }
                }
            }
            if (!empty($recipients["Groups"])) {
                require_once "include/utils/GetGroupUsers.php";
                foreach ($recipients["Groups"] as $groupId) {
                    $userGroups = new GetGroupUsers();
                    $userGroups->getAllUsersInGroup($groupId);
                    GetGroupUsers::$groupIdsList = array();
                    $recipientsList = array_merge($recipientsList, $userGroups->group_users);
                }
            }
        }
        $recipientsList = array_unique($recipientsList);
        $recipientsEmails = array();
        if (!empty($recipientsList) && 0 < count($recipientsList)) {
            foreach ($recipientsList as $userId) {
                if (!Vtiger_Util_Helper::isUserDeleted($userId)) {
                    $userName = getUserFullName($userId);
                    $userEmail = getUserEmail($userId);
                    if (!in_array($userEmail, $recipientsEmails)) {
                        $recipientsEmails[$userName] = $userEmail;
                    }
                }
            }
        }
        $specificemails = explode(",", Zend_Json::decode($this->get("specificemails")));
        if (!empty($specificemails)) {
            $recipientsEmails = array_merge($recipientsEmails, $specificemails);
        }
        return $recipientsEmails;
    }
    public function sendEmail()
    {
        require_once "vtlib/Vtiger/Mailer.php";
        $db = PearDatabase::getInstance();
        $currentUserModel = Users_Record_Model::getCurrentUserModel();
        $vtigerMailer = new Vtiger_Mailer();
        $recipientEmails = $this->getRecipientEmails();
        if (defined("ALLOW_MODULE_LOGGING")) {
            Vtiger_Utils::ModuleLog("ScheduleReprots", $recipientEmails);
        }
        foreach ($recipientEmails as $name => $email) {
            $vtigerMailer->AddAddress($email, decode_html($name));
        }
        vimport("~modules/VReport/models/Record.php");
        $reportRecordModel = VReports_Record_Model::getInstanceById($this->get("reportid"));
        $currentTime = date("Y-m-d H:i:s");
        if (defined("ALLOW_MODULE_LOGGING")) {
            Vtiger_Utils::ModuleLog("ScheduleReprots Send Mail Start ::", $currentTime);
        }
        $reportname = decode_html($reportRecordModel->getName());
        $subject = $reportname;
        $body = $this->getEmailContent($reportRecordModel);
        if ($this->get("subject")) {
            $subject = decode_html($this->get("subject"));
        }
        if ($this->get("body")) {
            $body = $this->get("body");
        }
        if ($this->get("signature") == "Yes") {
            $userId = $this->get("signature_user");
            $userSignature = nl2br($db->query_result($db->pquery("select signature from vtiger_users where id=?", array($userId)), 0, "signature"));
            $body .= "<br><br>" . $userSignature;
        }
        if (defined("ALLOW_MODULE_LOGGING")) {
            Vtiger_Utils::ModuleLog("ScheduleReprot Name ::", $reportname);
        }
        $this->setFromEmailAddress($vtigerMailer);
        $vtigerMailer->Subject = $subject;
        $vtigerMailer->Body = decode_html($body);
        $plainBody = decode_html($vtigerMailer->Body);
        $plainBody = preg_replace(array("/<p>/i", "/<br>/i", "/<br \\/>/i"), array("\n", "\n", "\n"), $plainBody);
        $plainBody = strip_tags($plainBody);
        $plainBody = Emails_Mailer_Model::convertToAscii($plainBody);
        $vtigerMailer->AltBody = $plainBody;
        $vtigerMailer->IsHTML();
        $baseFileName = preg_replace("/[^\\p{L}\\p{N}\\s]+/u", "", $reportname);
        $oReportRun = VReportRun::getInstance($this->get("reportid"));
        $reportFormat = $this->get("fileformat");
        $attachments = array();
        $reportType = $reportRecordModel->get("reporttype");
        $rootDirectory = vglobal("root_directory");
        if ($reportType != "chart") {
            if ($reportType == "pivot") {
                if ($reportFormat == "CSV") {
                    $fileName = $baseFileName . ".csv";
                    $filePath = $rootDirectory . "storage/" . $fileName;
                    $attachments[$fileName] = $filePath;
                    $oReportRun->writeReportPivotToCSVFile($filePath);
                } else {
                    if ($reportFormat == "XLS") {
                        $fileName = $baseFileName . ".xls";
                        $filePath = $rootDirectory . "storage/" . $fileName;
                        $attachments[$fileName] = $filePath;
                        $oReportRun->writeReportPivotToExcelFile($filePath);
                    }
                }
            } else {
                if ($reportFormat == "CSV") {
                    $fileName = $baseFileName . ".csv";
                    $filePath = $rootDirectory . "storage/" . $fileName;
                    $attachments[$fileName] = $filePath;
                    $oReportRun->writeReportToCSVFile($filePath);
                } else {
                    if ($reportFormat == "XLS") {
                        $fileName = $baseFileName . ".xls";
                        $filePath = $rootDirectory . "storage/" . $fileName;
                        $attachments[$fileName] = $filePath;
                        $oReportRun->writeReportToExcelFile($filePath);
                    }
                }
            }
            foreach ($attachments as $attachmentName => $path) {
                $vtigerMailer->AddAttachment($path, decode_html($attachmentName));
            }
        }
        $status = $vtigerMailer->Send(true);
        $queryInsertVReportHistorySendMail = "INSERT INTO vtiger_vreportshistory(`reportid`,`reportname`,`date_sent`,`from_email`,`to_email`,`cc_email`,`bcc_email`,`email_subject`,`email_body`,`result`) VALUES(?,?,?,?,?,?,?,?,?,?)";
        $currentTimeSendMail = $this->ConvertTimeZone(date("Y-m-d H:i:s"));
        $params = array($reportRecordModel->get("reportid"), $reportRecordModel->get("reportname"), $currentTimeSendMail, $this->get("from_address"), implode(" |##| ", $this->getRecipientEmails()), "", "", $this->get("subject"), $this->get("body"), $status);
        $db->pquery($queryInsertVReportHistorySendMail, $params);
        if ($reportType != "chart") {
            foreach ($attachments as $attachmentName => $path) {
                unlink($path);
            }
        }
        return $status;
    }
    public function setFromEmailAddress($vtigerMailer)
    {
        $db = PearDatabase::getInstance();
        $smtpFromResult = $db->pquery("SELECT from_email_field FROM vtiger_systems WHERE server_type=?", array("email"));
        if ($db->num_rows($smtpFromResult)) {
            $fromEmail = decode_html($db->query_result($smtpFromResult, 0, "from_email_field"));
            $vtigerMailer->ConfigSenderInfo($fromEmail, $fromEmail);
        }
        if (empty($fromEmail)) {
            $user = CRMEntity::getInstance("Users");
            $current_user = $user->getActiveAdminUser();
            $fromEmail = $current_user->column_fields["email1"];
            $fromName = trim($current_user->column_fields["first_name"] . " " . $current_user->column_fields["last_name"]);
            $vtigerMailer->ConfigSenderInfo($fromEmail, $fromName);
        }
        $this->set("from_address", $fromEmail);
        return $vtigerMailer;
    }
    /**
     * Function gets the next trigger for the workflows
     * @global <String> $default_timezone
     * @return type
     */
    public function getNextTriggerTime()
    {
        require_once "modules/com_vtiger_workflow/VTWorkflowManager.inc";
        $default_timezone = vglobal("default_timezone");
        $admin = Users::getActiveAdminUser();
        $adminTimeZone = $admin->time_zone;
        @date_default_timezone_set($adminTimeZone);
        $scheduleType = $this->get("scheduleid");
        $nextTime = NULL;
        $workflow = new Workflow();
        if ($scheduleType == self::$SCHEDULED_DAILY) {
            @date_default_timezone_set($default_timezone);
            $adminDateTime = new DateTime("now", new DateTimeZone($adminTimeZone));
            $adminDate = $adminDateTime->format("Y-m-d");
            $schtime = $this->get("schtime");
            $timeZone = new DateTimeZone($default_timezone);
            $userTZ = new DateTimeZone($adminTimeZone);
            $datetime = new DateTime($adminDate . " " . $schtime, $userTZ);
            $datetime->setTimezone($timeZone);
            $schtimeToTimeZone = $datetime->format("Y-m-d H:i:s");
            $nextTime = $workflow->getNextTriggerTimeForDaily($schtimeToTimeZone);
        }
        if ($scheduleType == self::$SCHEDULED_WEEKLY) {
            $schtime = $this->get("schtime");
            $nextTime = $workflow->getNextTriggerTimeForWeekly($this->get("schdayoftheweek"), $schtime);
        }
        if ($scheduleType == self::$SCHEDULED_MONTHLY_BY_DATE) {
            $schtime = $this->get("schtime");
            $nextTime = $workflow->getNextTriggerTimeForMonthlyByDate($this->get("schdayofthemonth"), $schtime);
        }
        if ($scheduleType == self::$SCHEDULED_ANNUALLY) {
            $nextTime = $workflow->getNextTriggerTimeForAnnualDates($this->get("schannualdates"), $this->get("schtime"));
        }
        @date_default_timezone_set($default_timezone);
        if ($scheduleType != self::$SCHEDULED_ON_SPECIFIC_DATE && $scheduleType != self::$SCHEDULED_DAILY) {
            $dateTime = new DateTimeField($nextTime);
            $nextTime = $dateTime->getDBInsertDateTimeValue($admin);
        }
        return $nextTime;
    }
    public function updateNextTriggerTime()
    {
        $adb = PearDatabase::getInstance();
        $nextTriggerTime = $this->getNextTriggerTime();
        if (defined("ALLOW_MODULE_LOGGING")) {
            Vtiger_Utils::ModuleLog("ScheduleReprot Next Trigger Time >> ", $nextTriggerTime);
        }
        $adb->pquery("UPDATE vtiger_schedulevreports SET next_trigger_time=? WHERE reportid=?", array($nextTriggerTime, $this->get("reportid")));
        if (defined("ALLOW_MODULE_LOGGING")) {
            Vtiger_Utils::ModuleLog("ScheduleReprot", "Next Trigger Time updated");
        }
    }
    public static function getScheduledVReports()
    {
        $adb = PearDatabase::getInstance();
        $currentTimestamp = date("Y-m-d H:i:s");
        $query = "SELECT reportid FROM vtiger_schedulevreports\r\n\t\t\t\t\t\t\t\tINNER JOIN vtiger_vreportmodules ON vtiger_vreportmodules.reportmodulesid = vtiger_schedulevreports.reportid\r\n\t\t\t\t\t\t\t\tINNER JOIN vtiger_tab ON vtiger_tab.name = vtiger_vreportmodules.primarymodule AND presence = 0\r\n\t\t\t\t\t\t\t\tWHERE next_trigger_time <= ? AND next_trigger_time IS NOT NULL\r\n\t\t\t\tUNION \r\n\t\t\t\t\tSELECT vtiger_schedulevreports.reportid FROM vtiger_schedulevreports\r\n\t\t\t\t\tINNER JOIN vtiger_vreport ON vtiger_vreport.reportid=vtiger_schedulevreports.reportid\r\n\t\t\t\t\tWHERE reporttype='sql' AND next_trigger_time <= ? AND next_trigger_time IS NOT NULL";
        $result = $adb->pquery($query, array($currentTimestamp, $currentTimestamp));
        $scheduledReports = array();
        $noOfScheduledReports = $adb->num_rows($result);
        for ($i = 0; $i < $noOfScheduledReports; $i++) {
            $recordId = $adb->query_result($result, $i, "reportid");
            $scheduledReports[$recordId] = self::getInstanceById($recordId);
        }
        return $scheduledReports;
    }
    public static function runScheduledVReports()
    {
        vimport("~~modules/com_vtiger_workflow/VTWorkflowUtils.php");
        $util = new VTWorkflowUtils();
        $util->adminUser();
        global $currentModule;
        global $current_language;
        if (empty($currentModule)) {
            $currentModule = "VReports";
        }
        if (empty($current_language)) {
            $current_language = "en_us";
        }
        $scheduledReports = self::getScheduledVReports();
        foreach ($scheduledReports as $reportId => $scheduledReport) {
            $reportRecordModel = VReports_Record_Model::getInstanceById($reportId);
            $reportType = $reportRecordModel->get("reporttype");
            if ($reportType == "chart") {
                $status = $scheduledReport->sendEmail();
            } else {
                if ($reportType == "sql") {
                    $query = html_entity_decode(html_entity_decode($reportRecordModel->get("data"), ENT_QUOTES));
                    $query = strtolower($query);
                } else {
                    $query = $reportRecordModel->getVReportSQL("", "PDF");
                }
                $countQuery = $reportRecordModel->generateCountQuery($query);
                if (0 < $reportRecordModel->getVReportsCount($countQuery)) {
                    $status = $scheduledReport->sendEmail();
                }
            }
            if (defined("ALLOW_MODULE_LOGGING")) {
                Vtiger_Utils::ModuleLog("ScheduleVReport Send Mail Status ", $status);
            }
            $scheduledReport->updateNextTriggerTime();
        }
        $util->revertUser();
        return $status;
    }
    public function getEmailContent($reportRecordModel)
    {
        $site_URL = vglobal("site_URL");
        $site_URL = VReports_Util_Helper::reFormatSiteUrl($site_URL);
        $currentModule = vglobal("currentModule");
        $companydetails = getCompanyDetails();
        $logo = $site_URL . "/test/logo/" . $companydetails["logoname"];
        $body = "<table width=\"700\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" align=\"center\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; text-decoration: none; \">\r\n\t\t\t<tr>\r\n\t\t\t\t<td> </td>\r\n\t\t\t</tr>\r\n\t\t\t<tr>\r\n\t\t\t\t<td>\r\n\t\t\t\t<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\r\n\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t<td>\r\n\t\t\t\t\t\t\t<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\r\n\t\t\t\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t\t\t\t<td rowspan=\"4\" ><img height=\"30\" src=" . $logo . "></td>\r\n\t\t\t\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t\t</table>\r\n\t\t\t\t\t\t\t</td>\r\n\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t<td>\r\n\t\t\t\t\t\t\t<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: normal; color: rgb(0, 0, 0); background-color: rgb(255, 255, 255);\">\r\n\t\t\t\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t\t\t\t<td valign=\"top\">\r\n\t\t\t\t\t\t\t\t\t\t<table width=\"100%\" cellspacing=\"0\" cellpadding=\"5\" border=\"0\">\r\n\t\t\t\t\t\t\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t<td align=\"right\" style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; font-weight: bolder; text-decoration: none; color: rgb(66, 66, 253);\"> </td>\r\n\t\t\t\t\t\t\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t<td> </td>\r\n\t\t\t\t\t\t\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t<td style=\"font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal; text-align: justify; line-height: 20px;\"> " . vtranslate("LBL_AUTO_GENERATED_REPORT_EMAIL", $currentModule) . "</td>\r\n\t\t\t\t\t\t\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t<td align=\"center\">\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t<table width=\"75%\" cellspacing=\"0\" cellpadding=\"10\" border=\"0\" style=\"border: 2px solid rgb(180, 180, 179); background-color: rgb(226, 226, 225); font-family: Arial,Helvetica,sans-serif; font-size: 12px; color: rgb(0, 0, 0); font-weight: normal;\">\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t<td><b>" . vtranslate("LBL_REPORT_NAME", $currentModule) . " </b> : <font color=\"#990000\"><strong> <a href=" . $site_URL . "/" . $reportRecordModel->getDetailViewUrl() . ">" . $reportRecordModel->getName() . "</a></strong></font> </td>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t<tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t<td><b>" . vtranslate("LBL_DESCRIPTION", $currentModule) . " :</b> <font color=\"#990000\"><strong>" . $reportRecordModel->get("description") . "</strong></font> </td>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t</table>\r\n\t\t\t\t\t\t\t\t\t\t\t\t\t</td>\r\n\t\t\t\t\t\t\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t\t\t\t\t</table>\r\n\t\t\t\t\t\t\t\t\t\t</td>\r\n\t\t\t\t\t\t\t\t\t\t<td width=\"1%\" valign=\"top\"> </td>\r\n\t\t\t\t\t\t\t\t\t</tr>\r\n\t\t\t\t\t\t\t</table>\r\n\t\t\t\t\t\t\t</td>\r\n\t\t\t\t\t\t</tr>\r\n\t\t\t\t</table>\r\n\t\t\t\t</td>\r\n\t\t\t</tr>\r\n\t\t\t<tr>\r\n\t\t\t\t<td> </td>\r\n\t\t\t</tr>\r\n\t\t\t<tr>\r\n\t\t\t\t<td> </td>\r\n\t\t\t</tr>\r\n\t\t\t<tr>\r\n\t\t\t\t<td> </td>\r\n\t\t\t</tr>\r\n\t</table>";
        return $body;
    }
    public function getNextTriggerTimeInUserFormat()
    {
        $dateTime = new DateTimeField($this->get("next_trigger_time"));
        $nextTriggerTime = $dateTime->getDisplayDateTimeValue();
        $valueParts = explode(" ", $nextTriggerTime);
        $Vtiger_Time_UIType = new Vtiger_Time_UIType();
        $value = $valueParts[0] . " " . $Vtiger_Time_UIType->getDisplayValue($valueParts[1]);
        return $value;
    }
    public function ConvertTimeZone($datetime, $timezone = NULL)
    {
        global $default_timezone;
        if (!$timezone) {
            $timezone = $default_timezone;
        }
        $datetime = new DateTime($datetime);
        $datetime->setTimezone(new DateTimeZone($timezone));
        return $datetime->format("Y-m-d H:i:s");
    }
}

?>