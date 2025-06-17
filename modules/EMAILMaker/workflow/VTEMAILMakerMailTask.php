<?php
require_once('modules/com_vtiger_workflow/VTTaskManager.inc');
require_once('modules/com_vtiger_workflow/VTEntityCache.inc');
require_once('modules/com_vtiger_workflow/VTWorkflowUtils.php');
require_once('modules/com_vtiger_workflow/VTEmailRecipientsTemplate.inc');
require_once('modules/Emails/mail.php');
require_once('modules/EMAILMaker/EMAILMaker.php');
require_once('modules/Emails/models/Mailer.php');

class VTEMAILMakerMailTask extends VTTask
{
    public $executeImmediately = false;
    public $template;
    public $template_language;
    public $contents;
    public $recepient;
    public $emailcc;
    public $emailbcc;
    public $fromEmail;
    public $template_field;
    public $parent;
    public $cache;
    public $signature;

    public function getFieldNames()
    {
        return array(
            'recepient', 'emailcc', 'emailbcc', 'fromEmail', 'template', 'template_language', 'template_field',
            'replyTo', 'signature'
        );
    }

    public function doTask($entity)
    {
        $EMAILMaker = Vtiger_Module_Model::getInstance('EMAILMaker');
        
        $current_user = Users_Record_Model::getCurrentUserModel();
        $adb = PearDatabase::getInstance();
        $sql0 = "select from_email_field from vtiger_systems where server_type=?";
        $result0 = $adb->pquery($sql0, array('email'));
        $from_email_field = $adb->query_result($result0, 0, 'from_email_field');
        $util = new VTWorkflowUtils();
        $admin = $util->adminUser();
        $module = $entity->getModuleName();
        $taskContents = Zend_Json::decode($this->getContents($entity));
        $from_email = $taskContents['fromEmail'];
        $from_name = $taskContents['fromName'];
        $cc_string = $taskContents['ccEmail'];
        $bcc_string = $taskContents['bccEmail'];
        $load_subject = $taskContents['subject'];
        $load_body = $taskContents['body'];
        $To_Emails = $taskContents['toEmails'];
        $Attachments = $taskContents['attachments'];
        $language = $taskContents['language'];
        $luserid = $taskContents['luserid'];
        $muserid = $taskContents['muserid'];
        $replyTo = $taskContents['replyTo'];
        $signature = $taskContents['signature'];
        $entityIdDetails = vtws_getIdComponents($entity->getId());
        $entityId = $entityIdDetails[1];
        $moduleName = 'Emails';
        $userId = $current_user->id;
        list($id3, $id) = explode("x", $entity->getId());
        foreach ($To_Emails as $Email_data) {
            $to_email = $Email_data["email"];
            $rmodule = $Email_data["module"];
            list($rid3, $rid) = explode("x", $Email_data["id"]);
            if (!empty($to_email)) {
                $emailFocus = CRMEntity::getInstance($moduleName);
                if ($rid != "") {
                    $cid = $rid;
                } else {
                    $cid = "0";
                }
                $cid .= "-".$luserid."-".$muserid;
                if (!isset($EMAILContentModel[$cid])) {
                    $EMAILContentModel[$cid] = EMAILMaker_EMAILContent_Model::getInstance($module, $id, $language, $rid,
                        $rmodule);
                    $EMAILContentModel[$cid]->setSubject($load_subject);
                    $EMAILContentModel[$cid]->setBody($load_body);
                    $EMAILContentModel[$cid]->set("luserid", $luserid);
                    $EMAILContentModel[$cid]->set("muserid", $muserid);
                    $EMAILContentModel[$cid]->getContent();
                }
                $subject = $EMAILContentModel[$cid]->getSubject();
                $d = "default_charset";
                $def_charset = vglobal($d);
                $subject = html_entity_decode($subject, ENT_QUOTES, $def_charset);
                $body = $EMAILContentModel[$cid]->getBody();
                if (empty($body) && empty($subject)) {
                    continue;
                }
                $preview_body = $EMAILContentModel[$cid]->getPreview();
                $processedContent = Emails_Mailer_Model::getProcessedContent($body);
                $mailerInstance = Emails_Mailer_Model::getInstance();
                $mailerInstance->isHTML(true);
                $processedContentWithURLS = $mailerInstance->convertToValidURL($processedContent);
                $emailFocus->column_fields['assigned_user_id'] = $userId;
                $emailFocus->column_fields['subject'] = $subject;
                $emailFocus->column_fields['description'] = $processedContentWithURLS;
                $emailFocus->column_fields['from_email'] = $from_email;
                $emailFocus->column_fields['saved_toid'] = $to_email;
                $emailFocus->column_fields['ccmail'] = $cc_string;
                $emailFocus->column_fields['bccmail'] = $bcc_string;
                $emailFocus->column_fields['parent_id'] = $entityId."@$userId|";
                $emailFocus->column_fields['email_flag'] = 'SENT';
                $emailFocus->column_fields['activitytype'] = $moduleName;
                $emailFocus->column_fields['date_start'] = date('Y-m-d');
                $emailFocus->column_fields['time_start'] = date('H:i:s');
                $emailFocus->column_fields['mode'] = '';
                $emailFocus->column_fields['id'] = '';
                $emailFocus->save($moduleName);
                $emailId = $emailFocus->id;
                if (count($Attachments) > 0) {
                    foreach ($Attachments as $document_id) {
                        $adb->pquery('replace into vtiger_seattachmentsrel values(?,?)', array($emailId, $document_id));
                    }
                }
                if ($rid != "") {
                    $adb->pquery('replace into vtiger_seactivityrel values(?,?)', array($rid, $emailId));
                }
                if ($entityId != "") {
                    $body .= $EMAILMaker->getTrackImageDetails($entityId, $emailId);
                }
                $replyToEmail = $from_email;
                if (isset($from_email_field) && $from_email_field != '') {
                    $from_email = $from_email_field;
                }
                $mailerInstance->From = $from_email;
                $mailerInstance->FromName = decode_html($from_name);
                if (!empty($replyTo)) {
                    $mailerInstance->AddReplyTo($replyTo);
                } else {
                    $mailerInstance->AddReplyTo($replyToEmail, $mailerInstance->FromName);
                }
                $mailerInstance->Subject = strip_tags(decode_html($subject));
                $mailerInstance->Body = decode_emptyspace_html($body);
                if ($signature) {
                    $mailerInstance->addSignature($userId);
                    if (!empty($mailerInstance->Signature)) {
                        $mailerInstance->Body .= decode_html($mailerInstance->Signature);
                    }
                }
                $mailerInstance->AddAddress($to_email);
                $mailerInstance = $EMAILMaker->addAllAttachments($mailerInstance, $emailId);
                $Email_Images = $EMAILContentModel[$cid]->getEmailImages();
                if (count($Email_Images) > 0) {
                    foreach ($Email_Images as $cid => $cdata) {
                        $mailerInstance->AddEmbeddedImage($cdata["path"], $cid, $cdata["name"]);
                    }
                }
                $ccs = empty($cc_string) ? array() : explode(',', $cc_string);
                $bccs = empty($bcc_string) ? array() : explode(',', $bcc_string);
                foreach ($ccs as $cc) {
                    $mailerInstance->AddCC($cc);
                }
                foreach ($bccs as $bcc) {
                    $mailerInstance->AddBCC($bcc);
                }
                $status = $mailerInstance->Send(true);
                if (!empty($emailId)) {
                    $emailFocus->setEmailAccessCountValue($emailId);
                }
                if (!$status) {
                    $emailFocus->trash($moduleName, $emailId);
                }
            }
        }
        $util->revertUser();
    }

    public function getContents($entity, $entityCache = false)
    {
        if (!$this->contents) {
            global $adb;
            $taskContents = array();
            $entityId = $entity->getId();
            $utils = new VTWorkflowUtils();
            $adminUser = $utils->adminUser();
            if (!$entityCache) {
                $entityCache = new VTEntityCache($adminUser);
            }
            $fromUserId = Users::getActiveAdminId();
            $entityOwnerId = $entity->get('assigned_user_id');
            if ($entityOwnerId) {
                list($moduleId, $fromUserId) = explode('x', $entityOwnerId);
            }
            $ownerEntity = $entityCache->forId($entityOwnerId);
            if ($ownerEntity->getModuleName() === 'Groups') {
                list($moduleId, $recordId) = vtws_getIdComponents($entityId);
                $fromUserId = Vtiger_Util_Helper::getCreator($recordId);
            }
            if ($this->fromEmail && !($ownerEntity->getModuleName() === 'Groups' && strpos($this->fromEmail,
                        'assigned_user_id : (Users) ') !== false)) {
                $et = new VTEmailRecipientsTemplate($this->fromEmail);
                $fromEmailDetails = $et->render($entityCache, $entityId);
                $fromEmailDecoded = html_entity_decode($fromEmailDetails);
                if (strpos($fromEmailDecoded, '<') && strpos($fromEmailDecoded, '>')) {
                    list($fromName, $fromEmail) = explode('<', $fromEmailDecoded);
                    list($fromEmail, $rest) = explode('>', $fromEmail);
                } else {
                    $fromName = "";
                    $fromEmail = $fromEmailDetails;
                }
            } else {
                $userObj = CRMEntity::getInstance('Users');
                $userObj->retrieveCurrentUserInfoFromFile($fromUserId);
                if ($userObj) {
                    $fromEmail = $userObj->email1;
                    $fromName = $userObj->user_name;
                } else {
                    $result = $adb->pquery('SELECT user_name, email1 FROM vtiger_users WHERE id = ?',
                        array($fromUserId));
                    $fromEmail = $adb->query_result($result, 0, 'email1');
                    $fromName = $adb->query_result($result, 0, 'user_name');
                }
            }
            if (!$fromEmail) {
                $utils->revertUser();
                return false;
            }
            $taskContents['fromEmail'] = $fromEmail;
            $taskContents['fromName'] = $fromName;
            if ($entity->getModuleName() === 'Events') {
                $contactId = $entity->get('contact_id');
                if ($contactId) {
                    $contactIds = '';
                    list($wsId, $recordId) = explode('x', $entityId);
                    $webserviceObject = VtigerWebserviceObject::fromName($adb, 'Contacts');
                    $result = $adb->pquery('SELECT contactid FROM vtiger_cntactivityrel WHERE activityid = ?',
                        array($recordId));
                    $numOfRows = $adb->num_rows($result);
                    for ($i = 0; $i < $numOfRows; $i++) {
                        $contactIds .= vtws_getId($webserviceObject->getEntityId(),
                                $adb->query_result($result, $i, 'contactid')).',';
                    }
                }
                $entity->set('contact_id', trim($contactIds, ','));
                $entityCache->cache[$entityId] = $entity;
            }
            $et = new VTEmailRecipientsTemplate($this->recepient);
            $toEmail = $et->render($entityCache, $entityId);
            $toEmails = $this->getRecipientEmails($entityCache, $entityId, $this->recepient);
            $ecct = new VTEmailRecipientsTemplate($this->emailcc);
            $ccEmail = $ecct->render($entityCache, $entityId);
            $ebcct = new VTEmailRecipientsTemplate($this->emailbcc);
            $bccEmail = $ebcct->render($entityCache, $entityId);
            if (strlen(trim($toEmail, "     
,")) == 0 && strlen(trim($ccEmail, "    
,")) == 0 && strlen(trim($bccEmail, "   
,")) == 0) {
                $utils->revertUser();
                return false;
            }
            $taskContents['toEmail'] = $toEmail;
            $taskContents['toEmails'] = $toEmails;
            $taskContents['ccEmail'] = $ccEmail;
            $taskContents['bccEmail'] = $bccEmail;
            global $email_maker_dynamic_template_wf;
            if ($email_maker_dynamic_template_wf === true) {
                if (isset($this->template_field) && !empty($this->template_field)) {
                    $value = $entity->data[$this->template_field];
                    $resultEmailMaker = $adb->pquery('SELECT * FROM vtiger_emakertemplates WHERE templatename = ? AND deleted = 0 ',
                        array($value));
                    $resultTemplateId = $adb->query_result($resultEmailMaker, 0, 'templateid');
                    $this->template = $resultTemplateId;
                }
            }
            $templateid = $this->template;
            $language = $this->template_language;
            $EMAILMaker = new EMAILMaker_EMAILMaker_Model();
            $emailtemplateResult = $EMAILMaker->GetDetailViewData($templateid, true);
            $taskContents['subject'] = $emailtemplateResult["subject"];
            $taskContents['body'] = $emailtemplateResult["body"];
            $Attachments = $EMAILMaker->GetAttachmentsData($templateid);
            $taskContents['attachments'] = $Attachments;
            $taskContents['language'] = $language;
            $luserid = "";
            if (isset($_SESSION['authenticated_user_id'])) {
                $luserid = $_SESSION['authenticated_user_id'];
            }
            $taskContents['luserid'] = $luserid;
            $modifiedbyId = $entity->get('modifiedby');
            list($moduleMuserId, $muserid) = explode('x', $modifiedbyId);
            $taskContents['muserid'] = $muserid;
            $taskContents['signature'] = $this->signature;
            $this->contents = $taskContents;
            $utils->revertUser();
        }
        if (is_array($this->contents)) {
            $this->contents = Zend_Json::encode($this->contents);
        }
        return $this->contents;
    }

    public function getRecipientEmails($entityCache, $entityId, $to_emails)
    {
        $this->cache = $entityCache;
        $this->parent = $this->cache->forId($entityId);
        $Recipients = array();
        $Emails = explode(",", $to_emails);
        foreach ($Emails as $email) {
            if ($email != "") {
                $Recipients_data = $this->parseEmail($email, $entityCache, $entityId);
                if ($Recipients_data) {
                    $Recipients = array_merge($Recipients_data, $Recipients);
                }
            }
        }
        return $Recipients;
    }

    private function parseEmail($to_email, $entityCache, $entityId)
    {
        preg_match('/\((\w+) : \(([_\w]+)\) (\w+)\)/', $to_email, $matches);
        if (count($matches) == 0) {
            $to_email_module = "";
            $to_email_id = "";
            $data = $this->parent->getData();
            if (substr($to_email, 0, 1) == '$') {
                $filename = substr($to_email, 1);
                if (isset($data[$filename])) {
                    if ($this->useValue($data, $filename)) {
                        $to_email_id = $this->parent->getId();
                        $to_email_module = $this->parent->getModuleName();
                        $to_email = $data[$filename];
                    }
                } else {
                    $et = new VTEmailRecipientsTemplate($to_email);
                    if (method_exists($et, 'renderArray')) {
                        return $et->renderArray($entityCache, $entityId);
                    } else {
                        $to_email = $et->render($entityCache, $entityId);
                    }
                }
            }
            return array(array("id" => $to_email_id, "module" => $to_email_module, "email" => $to_email));
        } else {
            list($full, $referenceField, $referenceModule, $fieldname) = $matches;
            $referenceId = $this->parent->get($referenceField);
            if ($referenceId == null) {
                return false;
            } else {
                if ($referenceField === 'contact_id') {
                    $referenceIdsList = explode(',', $referenceId);
                    $parts = array();
                    foreach ($referenceIdsList as $referenceId) {
                        $entity = $this->cache->forId($referenceId);
                        $to_email_module = $entity->getModuleName();
                        $data = $entity->getData();
                        if ($this->useValue($data, $fieldname)) {
                            $parts[] = array(
                                "id" => $referenceId, "module" => $to_email_module, "email" => $data[$fieldname]
                            );
                        }
                    }
                    return $parts;
                }
                $entity = $this->cache->forId($referenceId);
                if ($referenceModule === "Users" && $entity->getModuleName() == "Groups") {
                    list($groupEntityId, $groupId) = vtws_getIdComponents($referenceId);
                    require_once('include/utils/GetGroupUsers.php');
                    $ggu = new GetGroupUsers();
                    $ggu->getAllUsersInGroup($groupId);
                    $users = $ggu->group_users;
                    $parts = array();
                    foreach ($users as $userId) {
                        $refId = vtws_getWebserviceEntityId("Users", $userId);
                        $entity = $this->cache->forId($refId);
                        $data = $entity->getData();
                        if ($this->useValue($data, $fieldname)) {
                            $parts[] = array("id" => $userId, "module" => "Users", "email" => $data[$fieldname]);
                        }
                    }
                    return $parts;
                } elseif ($entity->getModuleName() === $referenceModule) {
                    $data = $entity->getData();
                    if ($this->useValue($data, $fieldname)) {
                        return array(
                            array(
                                "id" => $referenceId, "module" => $referenceModule, "email" => $data[$fieldname]
                            )
                        );
                    } else {
                        return false;
                    }
                }
            }
        }
        return false;
    }

    protected function useValue($data, $fieldname)
    {
        return !empty($data[$fieldname]) && $data['emailoptout'] == 0;
    }

    public function getTemplates($selected_module)
    {
        $orderby = "templateid";
        $dir = "asc";
        $c = "<div class='row-fluid'>";
        $EMAILMaker = new EMAILMaker_EMAILMaker_Model();
        $request = new Vtiger_Request($_REQUEST, $_REQUEST);
        $templates_data = $EMAILMaker->GetListviewData($orderby, $dir, $selected_module, false, $request);
        foreach ($templates_data as $tdata) {
            $templateid = $tdata["templateid"];
            if (!empty($tdata["category"]) || isset($fieldvalue[$templateid])) {
                $fieldvalue[$tdata["category"]][$templateid] = $tdata["name"];
            } else {
                $fieldvalue[$templateid] = $tdata["name"];
            }
        }
        return $fieldvalue;
    }

    public function getLanguages()
    {
        global $current_language;
        $langvalue = array();
        $currlang = array();
        $adb = PearDatabase::getInstance();
        $temp_res = $adb->pquery("SELECT label, prefix FROM vtiger_language WHERE active = ?", array('1'));
        while ($temp_row = $adb->fetchByAssoc($temp_res)) {
            $template_languages[$temp_row["prefix"]] = $temp_row["label"];
            if ($temp_row["prefix"] == $current_language) {
                $currlang[$temp_row["prefix"]] = $temp_row["label"];
            } else {
                $langvalue[$temp_row["prefix"]] = $temp_row["label"];
            }
        }
        $langvalue = (array) $currlang + (array) $langvalue;
        return $langvalue;
    }

    public function getModuleFields($sourceModule)
    {
        global $email_maker_dynamic_template_wf;
        if ($email_maker_dynamic_template_wf !== true) {
            $return = false;
        } else {
            require_once 'vtlib/Vtiger/Field.php';
            $moduleModel = Vtiger_Module_Model::getInstance($sourceModule);
            $fields = Vtiger_Field::getAllForModule($moduleModel);
            $fieldsArray = array();
            foreach ($fields as $field) {
                if ($field->displaytype == 1) {
                    $name = $field->name;
                    $label = $field->label;
                    $fieldsArray[$name] = $label;
                }
            }
            $return = $fieldsArray;
        }
        return $return;
    }
} ?>