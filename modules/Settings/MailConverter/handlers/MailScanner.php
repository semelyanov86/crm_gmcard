<?php

/*
 ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 */

require_once 'modules/Settings/MailConverter/handlers/MailBox.php';
require_once 'modules/Settings/MailConverter/handlers/MailAttachmentMIME.php';

/**
 * Mail Scanner provides the ability to scan through the given mailbox
 * applying the rules configured.
 */
class Vtiger_MailScanner
{
    // MailScanner information instance
    public $_scannerinfo = false;

    // Reference mailbox to use
    public $_mailbox = false;

    // Ignore scanning the folders always
    public $_generalIgnoreFolders = ['INBOX.Trash', 'INBOX.Drafts', '[Gmail]/Spam', '[Gmail]/Trash', '[Gmail]/Drafts', '[Gmail]/Important', '[Gmail]/Starred', '[Gmail]/Sent Mail', '[Gmail]/All Mail'];

    /** DEBUG functionality. */
    public $debug = false;

    public function log($message)
    {
        global $log;
        if ($log && $this->debug) {
            $log->debug($message);
        } elseif ($this->debug) {
            echo "{$message}\n";
        }
    }

    /**
     * Constructor.
     */
    public function __construct($scannerinfo)
    {
        $this->_scannerinfo = $scannerinfo;
    }

    /**
     * Get mailbox instance configured for the scan.
     */
    public function getMailBox()
    {
        if (!$this->_mailbox) {
            $this->_mailbox = new Vtiger_MailBox($this->_scannerinfo);
            $this->_mailbox->debug = $this->debug;
        }

        return $this->_mailbox;
    }

    /**
     * Start Scanning.
     */
    public function performScanNow()
    {
        // Check if rules exists to proceed
        $rules = $this->_scannerinfo->rules;

        if (empty($rules)) {
            $this->log('No rules setup for scanner [' . $this->_scannerinfo->scannername . "] SKIPING\n");

            return;
        }

        // Get mailbox instance to work with
        $mailbox = $this->getMailBox();
        $mailbox->connect();

        /** Loop through all the folders. */
        $folders = $mailbox->getFolders();
        if (!is_array($folders)) {
            return $folders;
        }

        // Build ignore folder list
        $ignoreFolders = [];
        $folderinfoList = $this->_scannerinfo->getFolderInfo();
        $allFolders = array_keys($folderinfoList);
        foreach ($folders as $folder) {
            if (!in_array($folder, $allFolders)) {
                $ignoreFolders[] = $folder;
            }
        }

        if ($folders) {
            $this->log('Folders found: ' . implode(',', $folders) . "\n");
        }

        foreach ($folders as $lookAtFolder) {
            // Skip folder scanning?
            if (in_array($lookAtFolder, $ignoreFolders)) {
                $this->log("\nIgnoring Folder: {$lookAtFolder}\n");

                continue;
            }
            // If a new folder has been added we should avoid scanning it
            if (!isset($folderinfoList[$lookAtFolder])) {
                $this->log("\nSkipping New Folder: {$lookAtFolder}\n");

                continue;
            }

            // Search for mail in the folder
            $mailsearch = $mailbox->search($lookAtFolder);
            $this->log($mailsearch ? "Total Mails Found in [{$lookAtFolder}]: " . php7_count($mailsearch) : "No Mails Found in [{$lookAtFolder}]");

            // No emails? Continue with next folder
            if (empty($mailsearch)) {
                continue;
            }

            // Loop through each of the email searched
            foreach ($mailsearch as $messageid) {
                // Fetch only header part first, based on account lookup fetch the body.
                $mailrecord = $mailbox->getMessage($messageid, false);
                $mailrecord->debug = $mailbox->debug;
                $mailrecord->log();

                // If the email is already scanned & rescanning is not set, skip it
                if ($this->isMessageScanned($mailrecord, $lookAtFolder)) {
                    $this->log("\nMessage already scanned [{$mailrecord->_subject}], IGNORING...\n");
                    unset($mailrecord);

                    continue;
                }

                // Apply rules configured for the mailbox
                $crmid = false;
                foreach ($rules as $mailscannerrule) {
                    $crmid = $this->applyRule($mailscannerrule, $mailrecord, $mailbox, $messageid);
                    if ($crmid) {
                        break; // Rule was successfully applied and action taken
                    }
                }
                // Mark the email message as scanned
                $this->markMessageScanned($mailrecord, $crmid);
                $mailbox->markMessage($messageid);

                /** Free the resources consumed. */
                unset($mailrecord);
            }
            /* Update lastscan for this folder and reset rescan flag */
            // TODO: Update lastscan only if all the mail searched was parsed successfully?
            $rescanFolderFlag = false;
            $this->updateLastScan($lookAtFolder, $rescanFolderFlag);
        }
        // Close the mailbox at end
        $mailbox->close();

        return true;
    }

    /**
     * Apply all the rules configured for a mailbox on the mailrecord.
     */
    public function applyRule($mailscannerrule, $mailrecord, $mailbox, $messageid)
    {
        // If no actions are set, don't proceed
        if (empty($mailscannerrule->actions)) {
            return false;
        }

        // Check if rule is defined for the body
        $bodyrule = $mailscannerrule->hasBodyRule();

        if ($bodyrule) {
            // We need the body part for rule evaluation
            $mailrecord->fetchBody($mailbox->_imap, $messageid);
        }

        // Apply rule to check if record matches the criteria
        $matchresult = $mailscannerrule->applyAll($mailrecord, $bodyrule);

        // If record matches the conditions fetch body to take action.
        $crmid = false;
        if ($matchresult) {
            $mailrecord->fetchBody($mailbox->_imap, $messageid);
            $crmid = $mailscannerrule->takeAction($this, $mailrecord, $matchresult);
        }

        // Return the CRMID
        return $crmid;
    }

    /**
     * Mark the email as scanned.
     */
    public function markMessageScanned($mailrecord, $crmid = false)
    {
        global $adb;
        if ($crmid === false) {
            $crmid = null;
        }
        // TODO Make sure we have unique entry
        $adb->pquery(
            'INSERT INTO vtiger_mailscanner_ids(scannerid, messageid, crmid) VALUES(?,?,?)',
            [$this->_scannerinfo->scannerid, $mailrecord->_uniqueid, $crmid],
        );
    }

    /**
     * Check if email was scanned.
     */
    public function isMessageScanned($mailrecord, $lookAtFolder)
    {
        global $adb;
        $messages = $adb->pquery(
            'SELECT 1 FROM vtiger_mailscanner_ids WHERE scannerid=? AND messageid=?',
            [$this->_scannerinfo->scannerid, $mailrecord->_uniqueid],
        );

        $folderRescan = $this->_scannerinfo->needRescan($lookAtFolder);
        $isScanned = false;

        if ($adb->num_rows($messages)) {
            $isScanned = true;

            // If folder is scheduled for rescan and earlier message was not acted upon?
            $relatedCRMId = $adb->query_result($messages, 0, 'crmid');

            if ($folderRescan && empty($relatedCRMId)) {
                $adb->pquery(
                    'DELETE FROM vtiger_mailscanner_ids WHERE scannerid=? AND messageid=?',
                    [$this->_scannerinfo->scannerid, $mailrecord->_uniqueid],
                );
                $isScanned = false;
            }
        }

        return $isScanned;
    }

    /**
     * Update last scan on the folder.
     */
    public function updateLastscan($folder)
    {
        $this->_scannerinfo->updateLastscan($folder);
    }

    /**
     * Convert string to integer value.
     * @returns false if given contain non-digits, else integer value
     */
    public function __toInteger($strvalue)
    {
        $ival = intval($strvalue);
        $intvalstr = "{$ival}";
        if (strlen($strvalue) == strlen($intvalstr)) {
            return $ival;
        }

        return false;
    }

    /** Lookup functionality. */
    public $_cachedContactIds = [];

    public $_cachedLeadIds = [];

    public $_cachedAccountIds = [];

    public $_cachedTicketIds = [];

    public $_cachedAccounts = [];

    public $_cachedContacts = [];

    public $_cachedLeads = [];

    public $_cachedTickets = [];

    /**
     * Lookup Contact record based on the email given.
     */
    public function LookupContact($email)
    {
        global $adb;
        if (isset($this->_cachedContactIds[$email]) && $this->_cachedContactIds[$email]) {
            $this->log("Reusing Cached Contact Id for email: {$email}");

            return $this->_cachedContactIds[$email];
        }
        $contactid = false;
        $contactres = $adb->pquery('SELECT contactid FROM vtiger_contactdetails INNER JOIN vtiger_crmentity ON crmid = contactid WHERE setype = ? AND email = ? AND deleted = ?', ['Contacts', $email, 0]);
        if ($adb->num_rows($contactres)) {
            $deleted = $adb->query_result($contactres, 0, 'deleted');
            if ($deleted != 1) {
                $contactid = $adb->query_result($contactres, 0, 'contactid');
            }
        }
        if ($contactid) {
            $this->log("Caching Contact Id found for email: {$email}");
            $this->_cachedContactIds[$email] = $contactid;
        } else {
            $this->log("No matching Contact found for email: {$email}");
        }

        return $contactid;
    }

    /**
     * Lookup Lead record based on the email given.
     */
    public function LookupLead($email)
    {
        global $adb;
        if ($this->_cachedLeadIds[$email]) {
            $this->log("Reusing Cached Lead Id for email: {$email}");

            return $this->_cachedLeadIds[$email];
        }
        $leadid = false;
        $leadres = $adb->pquery('SELECT leadid FROM vtiger_leaddetails INNER JOIN vtiger_crmentity ON crmid = leadid WHERE setype=? AND email = ? AND converted = ? AND deleted = ?', ['Leads', $email, 0, 0]);
        if ($adb->num_rows($leadres)) {
            $deleted = $adb->query_result($leadres, 0, 'deleted');
            if ($deleted != 1) {
                $leadid = $adb->query_result($leadres, 0, 'leadid');
            }
        }
        if ($leadid) {
            $this->log("Caching Lead Id found for email: {$email}");
            $this->_cachedLeadIds[$email] = $leadid;
        } else {
            $this->log("No matching Lead found for email: {$email}");
        }

        return $leadid;
    }

    /**
     * Lookup Account record based on the email given.
     */
    public function LookupAccount($email)
    {
        global $adb;
        if (isset($this->_cachedAccountIds[$email]) && $this->_cachedAccountIds[$email]) {
            $this->log("Reusing Cached Account Id for email: {$email}");

            return $this->_cachedAccountIds[$email];
        }

        $accountid = false;
        $accountres = $adb->pquery('SELECT accountid FROM vtiger_account INNER JOIN vtiger_crmentity ON crmid = accountid WHERE setype=? AND (email1 = ? OR email2 = ?) AND deleted = ?', ['Accounts', $email, $email, 0]);
        if ($adb->num_rows($accountres)) {
            $deleted = $adb->query_result($accountres, 0, 'deleted');
            if ($deleted != 1) {
                $accountid = $adb->query_result($accountres, 0, 'accountid');
            }
        }
        if ($accountid) {
            $this->log("Caching Account Id found for email: {$email}");
            $this->_cachedAccountIds[$email] = $accountid;
        } else {
            $this->log("No matching Account found for email: {$email}");
        }

        return $accountid;
    }

    /**
     * Lookup Ticket record based on the subject or id given.
     */
    public function LookupTicket($subjectOrId)
    {
        global $adb;

        $checkTicketId = $this->__toInteger($subjectOrId);
        if (!$checkTicketId) {
            $ticketres = $adb->pquery('SELECT ticketid FROM vtiger_troubletickets WHERE title = ? OR ticket_no = ?', [$subjectOrId, $subjectOrId]);
            if ($adb->num_rows($ticketres)) {
                $checkTicketId = $adb->query_result($ticketres, 0, 'ticketid');
            }
        }
        // Try with ticket_no before CRMID (case where ticket_no is also just number)
        if (!$checkTicketId) {
            $ticketres = $adb->pquery('SELECT ticketid FROM vtiger_troubletickets WHERE ticket_no = ?', [$subjectOrId]);
            if ($adb->num_rows($ticketres)) {
                $checkTicketId = $adb->query_result($ticketres, 0, 'ticketid');
            }
        }
        // Nothing found?
        if (!$checkTicketId) {
            return false;
        }

        if ($this->_cachedTicketIds[$checkTicketId]) {
            $this->log("Reusing Cached Ticket Id for: {$subjectOrId}");

            return $this->_cachedTicketIds[$checkTicketId];
        }

        // Verify ticket is not deleted
        $ticketid = false;
        if ($checkTicketId) {
            $crmres = $adb->pquery('SELECT setype, deleted FROM vtiger_crmentity WHERE crmid=?', [$checkTicketId]);
            if ($adb->num_rows($crmres)) {
                if ($adb->query_result($crmres, 0, 'setype') == 'HelpDesk'
                    && $adb->query_result($crmres, 0, 'deleted') == '0') {
                    $ticketid = $checkTicketId;
                }
            }
        }
        if ($ticketid) {
            $this->log("Caching Ticket Id found for: {$subjectOrId}");
            $this->_cachedTicketIds[$checkTicketId] = $ticketid;
        } else {
            $this->log("No matching Ticket found for: {$subjectOrId}");
        }

        return $ticketid;
    }

    /**
     * Get Account record information based on email.
     */
    public function GetAccountRecord($email, $accountid = false)
    {
        require_once 'modules/Accounts/Accounts.php';
        if (!$accountid) {
            $accountid = $this->LookupAccount($email);
        }
        $account_focus = false;
        if ($accountid) {
            if ($this->_cachedAccounts[$accountid]) {
                $account_focus = $this->_cachedAccounts[$accountid];
                $this->log('Reusing Cached Account [' . $account_focus->column_fields['accountname'] . ']');
            } else {
                $account_focus = CRMEntity::getInstance('Accounts');
                $account_focus->retrieve_entity_info($accountid, 'Accounts');
                $account_focus->id = $accountid;

                $this->log('Caching Account [' . $account_focus->column_fields['accountname'] . ']');
                $this->_cachedAccounts[$accountid] = $account_focus;
            }
        }

        return $account_focus;
    }

    /**
     * Get Contact record information based on email.
     */
    public function GetContactRecord($email, $contactid = false)
    {
        require_once 'modules/Contacts/Contacts.php';
        if (!$contactid) {
            $contactid = $this->LookupContact($email);
        }
        $contact_focus = false;
        if ($contactid) {
            if (isset($this->_cachedContacts[$contactid]) && $this->_cachedContacts[$contactid]) {
                $contact_focus = $this->_cachedContacts[$contactid];
                $this->log('Reusing Cached Contact [' . $contact_focus->column_fields['lastname']
                    . '-' . $contact_focus->column_fields['firstname'] . ']');
            } else {
                $contact_focus = CRMEntity::getInstance('Contacts');
                $contact_focus->retrieve_entity_info($contactid, 'Contacts');
                $contact_focus->id = $contactid;

                $this->log('Caching Contact [' . $contact_focus->column_fields['lastname']
                    . '-' . $contact_focus->column_fields['firstname'] . ']');
                $this->_cachedContacts[$contactid] = $contact_focus;
            }
        }

        return $contact_focus;
    }

    /**
     * Get Lead record information based on email.
     */
    public function GetLeadRecord($email)
    {
        require_once 'modules/Leads/Leads.php';
        $leadid = $this->LookupLead($email);
        $lead_focus = false;
        if ($leadid) {
            if ($this->_cachedLeads[$leadid]) {
                $lead_focus = $this->_cachedLeads[$leadid];
                $this->log('Reusing Cached Lead [' . $lead_focus->column_fields['lastname']
                        . '-' . $lead_focus->column_fields['firstname'] . ']');
            } else {
                $lead_focus = CRMEntity::getInstance('Leads');
                $lead_focus->retrieve_entity_info($leadid, 'Leads');
                $lead_focus->id = $leadid;

                $this->log('Caching Lead [' . $lead_focus->column_fields['lastname']
                        . '-' . $lead_focus->column_fields['firstname'] . ']');
                $this->_cachedLeads[$leadid] = $lead_focus;
            }
        }

        return $lead_focus;
    }

    /**
     * Lookup Contact or Account based on from email and with respect to given CRMID.
     */
    public function LookupContactOrAccount($fromemail, $checkWith)
    {
        $recordid = $this->LookupContact($fromemail);
        if ($checkWith['contact_id'] && $recordid != $checkWith['contact_id']) {
            $recordid = $this->LookupAccount($fromemail);
            if ($checkWith['parent_id'] && $recordid != $checkWith['parent_id']) {
                $recordid = false;
            }
        }

        return $recordid;
    }

    /**
     * Get Ticket record information based on subject or id.
     */
    public function GetTicketRecord($subjectOrId, $fromemail = false)
    {
        require_once 'modules/HelpDesk/HelpDesk.php';
        $ticketid = $this->LookupTicket($subjectOrId);
        $ticket_focus = false;
        if ($ticketid) {
            if ($this->_cachedTickets[$ticketid]) {
                $ticket_focus = $this->_cachedTickets[$ticketid];
                // Check the parentid association if specified.
                if ($fromemail && !$this->LookupContactOrAccount($fromemail, $ticket_focus->column_fields)) {
                    $ticket_focus = false;
                }
                if ($ticket_focus) {
                    $this->log('Reusing Cached Ticket [' . $ticket_focus->column_fields['ticket_title'] . ']');
                }
            } else {
                $ticket_focus = CRMEntity::getInstance('HelpDesk');
                $ticket_focus->retrieve_entity_info($ticketid, 'HelpDesk');
                $ticket_focus->id = $ticketid;
                // Check the parentid association if specified.
                if ($fromemail && !$this->LookupContactOrAccount($fromemail, $ticket_focus->column_fields)) {
                    $ticket_focus = false;
                }
                if ($ticket_focus) {
                    $this->log('Caching Ticket [' . $ticket_focus->column_fields['ticket_title'] . ']');
                    $this->_cachedTickets[$ticketid] = $ticket_focus;
                }
            }
        }

        return $ticket_focus;
    }

    public function getAccountId($contactId)
    {
        global $adb;
        $result = $adb->pquery('SELECT accountid FROM vtiger_contactdetails WHERE contactid=?', [$contactId]);
        $accountId = $adb->query_result($result, 0, 'accountid');

        return $accountId;
    }

    public function disableMailScanner()
    {
        global $adb;
        $scannerId = $this->_scannerinfo->scannerid;
        $adb->pquery('UPDATE vtiger_mailscanner SET isvalid=? WHERE scannerid=?', [0, $scannerId]);
    }
}
