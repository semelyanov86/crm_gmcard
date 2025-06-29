<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

vimport('~~modules/Settings/MailConverter/handlers/MailScannerAction.php');
vimport('~~modules/Settings/MailConverter/handlers/MailScannerRule.php');
require_once 'include/events/SqlResultIterator.inc';


class Settings_MailConverter_RuleRecord_Model extends Settings_Vtiger_Record_Model
{
    public $assignedTo = false;

    public $cc = false;

    public $bcc = false;

    public $actions = [];

    /**
     * Function to get Id of this record instance.
     * @return <Integer> Id
     */
    public function getId()
    {
        return $this->get('ruleid');
    }

    /**
     * Function to get Name.
     */
    public function getName()
    {
        return '';
    }

    /**
     * Function to get List of fields for this record.
     * @return <Array> List of fields
     */
    public function getFields()
    {
        return ['fromaddress', 'toaddress', 'cc', 'bcc', 'subjectop', 'subject', 'bodyop', 'body', 'matchusing'];
    }

    /**
     * Function to get Scanner id of this record.
     * @return <Integer> Scanner id
     */
    public function getScannerId()
    {
        return $this->get('scannerid');
    }

    /**
     * Function to get Default url of this record.
     * @return <String> Url
     */
    public function getDefaultUrl()
    {
        return 'index.php?module=MailConverter&parent=Settings&record=' . $this->getId() . '&scannerId=' . $this->getScannerId();
    }

    /**
     * Function to get Edit view url of this record.
     * @return <String> Url
     */
    public function getEditViewUrl()
    {
        $url = $this->getDefaultUrl() . '&view=EditRule';

        return 'javascript:Settings_MailConverter_Index_Js.triggerRuleEdit("' . $url . '")';
    }

    /**
     * Function to get Delete Url.
     * @return <String> Url
     */
    public function getDeleteUrl()
    {
        $url = $this->getDefaultUrl() . '&action=DeleteRule';

        return 'javascript:Settings_MailConverter_Index_Js.triggerDeleteRule(this,"' . $url . '")';
    }

    /**
     * Function to get record links.
     * @return <Array> List of link models <Vtiger_Link_Model>
     */
    public function getRecordLinks()
    {
        $qualifiedModuleName = 'Settings::MailConverter';
        $recordLinks = [
            [
                'linktype' => 'LISTVIEW',
                'linklabel' => vtranslate('LBL_EDIT', $qualifiedModuleName) . ' ' . vtranslate('RULE', $qualifiedModuleName),
                'linkurl' => $this->getEditViewUrl(),
                'linkicon' => 'fa fa-pencil',
            ],
            [
                'linktype' => 'LISTVIEW',
                'linklabel' => vtranslate('LBL_DELETE', $qualifiedModuleName) . ' ' . vtranslate('RULE', $qualifiedModuleName),
                'linkurl' => $this->getDeleteUrl(),
                'linkicon' => 'fa fa-trash',
            ],
        ];
        foreach ($recordLinks as $recordLink) {
            $links[] = Vtiger_Link_Model::getInstanceFromValues($recordLink);
        }

        return $links;
    }

    /**
     * Function to get Actions of this record.
     * @return <Array> List of actions <Vtiger_MailScannerAction>
     */
    public function getActions()
    {
        $ruleId = $this->getId();
        if (!$this->actions && $ruleId) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT actionid FROM vtiger_mailscanner_ruleactions WHERE ruleid = ?', [$ruleId]);
            $numOfRows = $db->num_rows($result);

            for ($i = 0; $i < $numOfRows; ++$i) {
                $actionId = $db->query_result($result, $i, 'actionid');
                $this->actions[$actionId] = new Vtiger_MailScannerAction($actionId);
            }
        }

        return $this->actions;
    }

    /**
     * Function to Delete this record.
     */
    public function delete()
    {
        $rule = new Vtiger_MailScannerRule($this->getId());
        $rule->delete();
    }

    /**
     * Function to save the record.
     */
    public function save()
    {
        $recordId = $this->getId();
        $ruleModel = new Vtiger_MailScannerRule($recordId);
        $fieldsList = $this->getFields();
        $ruleModel->scannerid = $this->get('scannerid');
        $ruleModel->assigned_to = $this->assignedTo;
        $ruleModel->cc = $this->cc;
        $ruleModel->bcc = $this->bcc;
        foreach ($fieldsList as $fieldName) {
            $ruleModel->{$fieldName} = $this->get($fieldName);
        }
        // Saving the Rule data
        $ruleModel->update();
        $this->set('ruleid', $ruleModel->ruleid);

        $actionString = $this->get('action');
        $newActionString = $this->get('newAction');
        if ($actionString != $newActionString) {
            $actionId = '';
            $actions = $this->getActions();
            $actionModel = '';
            if ($actions) {
                $actionModel = reset($actions);
                $actionId = $actionModel->actionid;
            }
            // Svaing the Action info
            $ruleModel->updateAction($actionId, str_replace('_', ',', $newActionString));
        }

        return $ruleModel->ruleid;
    }

    // Static functions Started

    /**
     * Function to get Clean instance.
     * @param <Integer> $scannerId
     * @return <Settings_MailConverter_RuleRecord_Model>
     */
    public static function getCleanInstance($scannerId)
    {
        $recordModel = new self();

        return $recordModel->set('scannerid', $scannerId);
    }

    /**
     * Function to get Instance of this class using by record id.
     * @param <Integer> $recordId
     * @return <Settings_MailConverter_RuleRecord_Model>
     */
    public static function getInstanceById($recordId)
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT * FROM vtiger_mailscanner_rules WHERE ruleid = ?', [$recordId]);
        if ($db->num_rows($result)) {
            $recordModel = new self();
            $recordModel->setData($db->query_result_rowdata($result));
            $actions = $recordModel->getActions();
            $action = reset($actions);

            return $recordModel->set('action', isset($action->actiontext) ? str_replace(',', '_', $action->actiontext) : '');
        }

        return false;
    }

    /**
     * Function to get List of mail scanner rule records.
     * @param <Integer> $scannerId
     * @return <Array> List of rule record models <Settings_MailConverter_RuleRecord_Model>
     */
    public static function getAll($scannerId)
    {
        $db = PearDatabase::getInstance();
        $ruleModelsList = [];

        $result = $db->pquery('SELECT * FROM vtiger_mailscanner_rules WHERE scannerid = ? ORDER BY sequence', [$scannerId]);
        $numOfRows = $db->num_rows($result);
        for ($i = 0; $i < $numOfRows; ++$i) {
            $rowData = $db->query_result_rowdata($result, $i);
            $ruleModel = new self();
            $ruleModel->setData($rowData);
            $actions = $ruleModel->getActions();
            $action = reset($actions);
            $ruleModel->set('action', isset($action->actiontext) ? str_replace(',', '_', $action->actiontext) : '');
            $assignedTo = Settings_MailConverter_RuleRecord_Model::getAssignedTo($rowData['scannerid'], $rowData['ruleid']);
            $ruleModel->set('assigned_to', $assignedTo[1]);
            $ruleModelsList[$rowData['ruleid']] = $ruleModel;
        }

        return $ruleModelsList;
    }

    /**
     * Function to get mail scanner rule record.
     * @param <Integer> $scannerId
     * @return <Array> List of rule record models <Settings_MailConverter_RuleRecord_Model>
     */
    public static function getRule($scannerId, $ruleId)
    {
        $db = PearDatabase::getInstance();

        $result = $db->pquery('SELECT * FROM vtiger_mailscanner_rules WHERE scannerid = ? AND ruleid = ?', [$scannerId, $ruleId]);
        if ($db->num_rows($result)) {
            $rowData = $db->query_result_rowdata($result);
            $ruleModel = new self();
            $ruleModel->setData($rowData);
            $assignedTo = Settings_MailConverter_RuleRecord_Model::getAssignedTo($scannerId, $ruleId);
            $ruleModel->set('assigned_to', $assignedTo[1]);
            $actions = $ruleModel->getActions();
            $action = reset($actions);

            return $ruleModel->set('action', isset($action->actiontext) ? str_replace(',', '_', $action->actiontext) : '');
        }

        return false;
    }

    /**
     * Function to get Default conditions.
     * @return <Array> List of default conditions
     */
    public static function getDefaultConditions()
    {
        return ['Contains', 'Not Contains', 'Equals', 'Not Equals', 'Begins With', 'Ends With', 'Regex'];
    }

    /**
     * Function to get Default actions.
     * @return <Array> List of default actions
     */
    public static function getDefaultActions()
    {
        return ['CREATE_HelpDesk_FROM', 'UPDATE_HelpDesk_SUBJECT', 'LINK_Contacts_FROM', 'LINK_Contacts_TO', 'LINK_Leads_FROM', 'LINK_Leads_TO', 'LINK_Accounts_FROM', 'LINK_Accounts_TO'];
    }

    public static function getCustomActions()
    {
        $db = PearDatabase::getInstance();

        $result = $db->pquery('SELECT module_name,method_name FROM vtiger_mailscanner_entitymethod WHERE 1=1', []);
        $it = new SqlResultIterator($db, $result);
        $methodNames = [];
        foreach ($it as $row) {
            unset($method_name);
            $method_name = $row->method_name;
            $module_name = $row->module_name;

            $methodNames[] = $method_name . '_' . $module_name . '_FROM';
            $methodNames[] = $method_name . '_' . $module_name . '_TO';
            $methodNames[] = $method_name . '_' . $module_name . '_SUBJECT';

        }

        return $methodNames;
    }

    public static function getAssignedTo($scannerId, $ruleId)
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT assigned_to FROM vtiger_mailscanner_rules WHERE scannerid = ? AND ruleid = ?', [$scannerId, $ruleId]);
        $id = $db->query_result($result, 0, 'assigned_to');
        if (empty($id)) {
            global $current_user;
            $id = $current_user->id;
        }
        $assignedUserName = getUserFullName($id);
        if (empty($assignedUserName)) {
            $groupInfo = getGroupName($id);
            $assignedUserName = $groupInfo[0];
        }

        return [$id, $assignedUserName];
    }
}
