<?php

/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

if (defined('VTIGER_UPGRADE')) {
    global $adb, $current_user;
    $db = PearDatabase::getInstance();

    $result = $db->pquery('SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype=?', ['98']);
    if (!$db->num_rows($result)) {
        $db->pquery('INSERT INTO vtiger_ws_fieldtype(uitype,fieldtype) VALUES(?, ?)', ['98', 'reference']);
    }

    $result = $db->pquery('SELECT fieldtypeid FROM vtiger_ws_fieldtype WHERE uitype=(SELECT DISTINCT uitype FROM vtiger_field WHERE fieldname=?)', ['modifiedby']);
    if ($db->num_rows($result)) {
        $fieldTypeId = $db->query_result($result, 0, 'fieldtypeid');
        $referenceResult = $db->pquery('SELECT * FROM vtiger_ws_referencetype WHERE fieldtypeid=?', [$fieldTypeId]);

        while ($rowData = $db->fetch_row($referenceResult)) {
            $type = $rowData['type'];
            if ($type != 'Users') {
                $db->pquery('DELETE FROM vtiger_ws_referencetype WHERE fieldtypeid=? AND type=?', [$fieldTypeId, $type]);
            }
        }
    }

    if (!Vtiger_Utils::CheckTable('vtiger_activity_recurring_info')) {
        $db->pquery('CREATE TABLE IF NOT EXISTS vtiger_activity_recurring_info(activityid INT(19) NOT NULL, recurrenceid INT(19) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=UTF8;', []);
    }

    $columns = $db->getColumnNames('vtiger_crmentity');
    if (!in_array('smgroupid', $columns)) {
        $db->pquery('ALTER TABLE vtiger_crmentity ADD COLUMN smgroupid INT(19)', []);
    }

    require_once 'modules/com_vtiger_workflow/VTWorkflowManager.inc';
    $result = $db->pquery('SELECT DISTINCT workflow_id FROM com_vtiger_workflows WHERE summary=?', ['Ticket Creation From Portal : Send Email to Record Owner and Contact']);
    if ($db->num_rows($result)) {
        $wfs = new VTWorkflowManager($db);
        $workflowModel = $wfs->retrieve($db->query_result($result, 0, 'workflow_id'));

        $selectedFields = [];
        $conditions = Zend_Json::decode(html_entity_decode($workflowModel->test));
        if ($conditions) {
            foreach ($conditions as $conditionKey => $condition) {
                if ($condition['fieldname'] == 'from_portal') {
                    $selectedFieldKeys[] = $conditionKey;
                }
            }
            foreach ($selectedFieldKeys as $key => $conditionKey) {
                if ($key) {
                    unset($conditions[$conditionKey]);
                }
            }
            $workflowModel->name = $workflowModel->description;
            $workflowModel->test = Zend_Json::encode($conditions);
            $wfs->save($workflowModel);
        }
    }

    $db->pquery('UPDATE vtiger_ws_entity SET handler_path=?, handler_class=? WHERE name IN("Products","Services")', ['include/Webservices/VtigerProductOperation.php', 'VtigerProductOperation']);
    $db->pquery('UPDATE vtiger_def_org_share SET editstatus=? WHERE tabid=?', [0, getTabid('Contacts')]);
    $db->pquery('UPDATE vtiger_settings_field SET name=? WHERE name=?', ['Configuration Editor', 'LBL_CONFIG_EDITOR']);
    $db->pquery('UPDATE vtiger_links SET linktype=? WHERE linklabel=?', ['DETAILVIEW', 'LBL_SHOW_ACCOUNT_HIERARCHY']);
    $db->pquery('UPDATE vtiger_field SET typeofdata=? WHERE fieldname IN (?, ?)', ['DT~O', 'createdtime', 'modifiedtime']);
    $db->pquery('UPDATE vtiger_field SET presence=0 WHERE columnname=? AND fieldname=?', ['emailoptout', 'emailoptout']);
    $db->pquery('UPDATE vtiger_field SET defaultvalue=? WHERE fieldname=?', ['1', 'discontinued']);
    $db->pquery('UPDATE vtiger_field SET defaultvalue=? WHERE fieldname=?', ['.', 'currency_decimal_separator']);
    $db->pquery('UPDATE vtiger_field SET defaultvalue=? WHERE fieldname=?', [',', 'currency_grouping_separator']);

    $lineItemModules = ['Products' => 'vtiger_products', 'Services' => 'vtiger_service'];
    foreach ($lineItemModules as $moduleName => $tableName) {
        $moduleInstance = Vtiger_Module::getInstance($moduleName);
        $blockInstance = Vtiger_Block::getInstance('LBL_PRICING_INFORMATION', $moduleInstance);
        if ($blockInstance) {
            $fieldInstance = Vtiger_Field::getInstance('purchase_cost', $moduleInstance);
            if (!$fieldInstance) {
                $fieldInstance = new Vtiger_Field();
                $fieldInstance->name		= 'purchase_cost';
                $fieldInstance->column		= 'purchase_cost';
                $fieldInstance->label		= 'Purchase Cost';
                $fieldInstance->columntype	= 'decimal(27,8)';
                $fieldInstance->table		= $tableName;
                $fieldInstance->typeofdata	= 'N~O';
                $fieldInstance->uitype		= '71';
                $fieldInstance->presence	= '0';

                $blockInstance->addField($fieldInstance);
            }
        }
    }

    $columns = $db->getColumnNames('vtiger_relatedlists');
    if (!in_array('relationfieldid', $columns)) {
        $db->pquery('ALTER TABLE vtiger_relatedlists ADD COLUMN relationfieldid INT(19)', []);
    }
    if (!in_array('source', $columns)) {
        $db->pquery('ALTER TABLE vtiger_relatedlists ADD COLUMN source VARCHAR(25)', []);
    }
    if (!in_array('relationtype', $columns)) {
        $db->pquery('ALTER TABLE vtiger_relatedlists ADD COLUMN relationtype VARCHAR(10)', []);
    }
    $result = $db->pquery('SELECT relation_id FROM vtiger_relatedlists ORDER BY relation_id DESC LIMIT 1', []);
    $db->pquery('UPDATE vtiger_relatedlists_seq SET id=?', [$db->query_result($result, 0, 'relation_id')]);

    $accountsTabId = getTabId('Accounts');
    $query = 'UPDATE vtiger_relatedlists INNER JOIN vtiger_tab ON vtiger_tab.tabid = vtiger_relatedlists.related_tabid  SET vtiger_relatedlists.name = ? 
          WHERE vtiger_relatedlists.name = ? AND vtiger_relatedlists.tabid = ? AND customized = 0';
    $db->pquery($query, ['get_merged_list', 'get_dependents_list', $accountsTabId]);

    $invoiceModuleInstance = Vtiger_Module::getInstance('Invoice');
    $blockInstance = Vtiger_Block::getInstance('LBL_INVOICE_INFORMATION', $invoiceModuleInstance);
    if ($blockInstance) {
        $fieldInstance = Vtiger_Field::getInstance('potential_id', $invoiceModuleInstance);
        if (!$fieldInstance) {
            $field = new Vtiger_Field();
            $field->name			= 'potential_id';
            $field->label			= 'Potential Name';
            $field->uitype			= 10;
            $field->typeofdata		= 'I~O';

            $blockInstance->addField($field);
            $field->setRelatedModules(['Potentials']);

            $oppModuleModel = Vtiger_Module_Model::getInstance('Potentials');
            $oppModuleModel->setRelatedlist($invoiceModuleInstance, 'Invoice', ['ADD'], 'get_dependents_list');
        }
    }

    $documentsModuleModel = Vtiger_Module_Model::getInstance('Documents');
    $noteContentFieldModel = Vtiger_Field_Model::getInstance('notecontent', $documentsModuleModel);
    if ($noteContentFieldModel) {
        $noteContentFieldModel->set('masseditable', '0');
        $noteContentFieldModel->save();
    }

    $userModuleModel = Vtiger_Module_Model::getInstance('Users');
    $defaultActivityTypeFieldModel = Vtiger_Field_Model::getInstance('defaultactivitytype', $userModuleModel);
    if ($defaultActivityTypeFieldModel) {
        $defaultActivityTypeFieldModel->set('defaultvalue', 'Call');
        $defaultActivityTypeFieldModel->save();
        $db->pquery('UPDATE vtiger_users SET defaultactivitytype=? WHERE defaultactivitytype=? OR defaultactivitytype IS NULL', ['Call', '']);
    }

    $defaultEventStatusFieldModel = Vtiger_Field_Model::getInstance('defaulteventstatus', $userModuleModel);
    if ($defaultEventStatusFieldModel) {
        $defaultEventStatusFieldModel->set('defaultvalue', 'Planned');
        $defaultEventStatusFieldModel->save();
        $db->pquery('UPDATE vtiger_users SET defaulteventstatus=? WHERE defaulteventstatus=? OR defaulteventstatus IS NULL', ['Planned', '']);
    }

    $moduleInstance = Vtiger_Module::getInstance('Users');
    $blockInstance = Vtiger_Block::getInstance('LBL_CALENDAR_SETTINGS', $moduleInstance);
    if ($blockInstance) {
        $fieldInstance = Vtiger_Field::getInstance('defaultcalendarview', $moduleInstance);
        if (!$fieldInstance) {
            $fieldInstance				= new Vtiger_Field();
            $fieldInstance->name		= 'defaultcalendarview';
            $fieldInstance->label		= 'Default Calendar View';
            $fieldInstance->table		= 'vtiger_users';
            $fieldInstance->column		= 'defaultcalendarview';
            $fieldInstance->uitype		= '16';
            $fieldInstance->presence	= '0';
            $fieldInstance->typeofdata	= 'V~O';
            $fieldInstance->columntype	= 'VARCHAR(100)';
            $fieldInstance->defaultvalue = 'MyCalendar';

            $blockInstance->addField($fieldInstance);
            $fieldInstance->setPicklistValues(['ListView', 'MyCalendar', 'SharedCalendar']);
            echo '<br>Default Calendar view field added <br>';
        }
    }

    $fieldInstance = Vtiger_Field_Model::getInstance('language', $moduleInstance);
    if ($fieldInstance) {
        $fieldInstance->set('defaultvalue', 'en_us');
        $fieldInstance->save();
    }

    $allUsers = Users_Record_Model::getAll(true);
    foreach ($allUsers as $userId => $userModel) {
        $db->pquery('UPDATE vtiger_users SET defaultcalendarview=? WHERE id=?', ['MyCalendar', $userId]);
    }
    echo 'Default calendar view updated for all active users <br>';

    $fieldNamesList = [];
    $updateQuery = 'UPDATE vtiger_field SET fieldlabel = CASE fieldname';
    $result = $db->pquery('SELECT taxname, taxlabel FROM vtiger_inventorytaxinfo', []);

    while ($row = $db->fetch_array($result)) {
        $fieldName = $row['taxname'];
        $fieldLabel = $row['taxlabel'];

        $updateQuery .= " WHEN '{$fieldName}' THEN '{$fieldLabel}' ";
        $fieldNamesList[] = $fieldName;
    }
    $updateQuery .= 'END WHERE fieldname in (' . generateQuestionMarks($fieldNamesList) . ')';

    $db->pquery($updateQuery, $fieldNamesList);
    $db->pquery('UPDATE vtiger_field SET fieldlabel=? WHERE displaytype=? AND fieldname=?', ['Item Discount Amount', 5, 'discount_amount']);

    $inventoryModules = getInventoryModules();
    foreach ($inventoryModules as $moduleName) {
        $tabId = getTabid($moduleName);
        $blockId = getBlockId($tabId, 'LBL_ITEM_DETAILS');
        $db->pquery('UPDATE vtiger_field SET displaytype=?, block=? WHERE tabid=? AND fieldname IN (?, ?)', [5, $blockId, $tabId, 'hdnDiscountAmount', 'hdnDiscountPercent']);
    }

    $itemFieldsName = ['image', 'purchase_cost', 'margin'];
    $itemFieldsLabel = ['Image', 'Purchase Cost', 'Margin'];
    $itemFieldsTypeOfData = ['V~O', 'N~O', 'N~O'];
    $itemFieldsDisplayType = ['56', '71', '71'];
    $itemFieldsDataType = ['VARCHAR(2)', 'decimal(27,8)', 'decimal(27,8)'];

    $fieldIdsList = [];
    foreach ($inventoryModules as $moduleName) {
        $moduleInstance = Vtiger_Module::getInstance($moduleName);
        $blockInstance = Vtiger_Block::getInstance('LBL_ITEM_DETAILS', $moduleInstance);

        for ($i = 0; $i < php7_count($itemFieldsName); ++$i) {
            $fieldName = $itemFieldsName[$i];

            if ($moduleName === 'PurchaseOrder' && $fieldName !== 'image') {
                continue;
            }

            $fieldInstance = Vtiger_Field::getInstance($fieldName, $moduleInstance);
            if (!$fieldInstance) {
                $fieldInstance = new Vtiger_Field();

                $fieldInstance->name		= $fieldName;
                $fieldInstance->column		= $fieldName;
                $fieldInstance->label		= $itemFieldsLabel[$i];
                $fieldInstance->columntype	= $itemFieldsDataType[$i];
                $fieldInstance->typeofdata	= $itemFieldsTypeOfData[$i];
                $fieldInstance->uitype		= $itemFieldsDisplayType[$i];
                $fieldInstance->table		= 'vtiger_inventoryproductrel';
                $fieldInstance->presence	= '1';
                $fieldInstance->readonly	= '0';
                $fieldInstance->displaytype = '5';
                $fieldInstance->masseditable = '0';

                $blockInstance->addField($fieldInstance);
                $fieldIdsList[] = $fieldInstance->id;
            }
        }
    }

    $columns = $db->getColumnNames('vtiger_products');
    if (!in_array('is_subproducts_viewable', $columns)) {
        $db->pquery('ALTER TABLE vtiger_products ADD COLUMN is_subproducts_viewable INT(1) DEFAULT 1', []);
    }
    $columns = $db->getColumnNames('vtiger_seproductsrel');
    if (!in_array('quantity', $columns)) {
        $db->pquery('ALTER TABLE vtiger_seproductsrel ADD COLUMN quantity INT(19) DEFAULT 1', []);
    }
    $columns = $db->getColumnNames('vtiger_inventorysubproductrel');
    if (!in_array('quantity', $columns)) {
        $db->pquery('ALTER TABLE vtiger_inventorysubproductrel ADD COLUMN quantity INT(19) DEFAULT 1', []);
    }

    $columns = $db->getColumnNames('vtiger_calendar_default_activitytypes');
    if (!in_array('isdefault', $columns)) {
        $db->pquery('ALTER TABLE vtiger_calendar_default_activitytypes ADD COLUMN isdefault INT(11) DEFAULT 1', []);
    }
    if (!in_array('conditions', $columns)) {
        $db->pquery('ALTER TABLE vtiger_calendar_default_activitytypes ADD COLUMN conditions VARCHAR(255) DEFAULT ""', []);
    }

    $updateList = [];
    $updateList[] = ['module' => 'Events', 'fieldname' => 'Events', 'newfieldname' => ['date_start', 'due_date']];
    $updateList[] = ['module' => 'Calendar', 'fieldname' => 'Tasks', 'newfieldname' => ['date_start', 'due_date']];
    $updateList[] = ['module' => 'Contacts', 'fieldname' => 'support_end_date', 'newfieldname' => ['support_end_date']];
    $updateList[] = ['module' => 'Contacts', 'fieldname' => 'birthday', 'newfieldname' => ['birthday']];
    $updateList[] = ['module' => 'Potentials', 'fieldname' => 'Potentials', 'newfieldname' => ['closingdate']];
    $updateList[] = ['module' => 'Invoice', 'fieldname' => 'Invoice', 'newfieldname' => ['duedate']];
    $updateList[] = ['module' => 'Project', 'fieldname' => 'Project', 'newfieldname' => ['startdate', 'targetenddate']];
    $updateList[] = ['module' => 'ProjectTask', 'fieldname' => 'Project Task', 'newfieldname' => ['startdate', 'enddate']];

    foreach ($updateList as $list) {
        $db->pquery('UPDATE vtiger_calendar_default_activitytypes SET fieldname=? WHERE module=? AND fieldname=? AND isdefault=?', [Zend_Json::encode($list['newfieldname']), $list['module'], $list['fieldname'], '1']);
    }

    $model = Settings_Vtiger_TermsAndConditions_Model::getInstance('Inventory');
    $tAndC = $model->getText();
    $db->pquery('DELETE FROM vtiger_inventory_tandc', []);

    $inventoryModules = getInventoryModules();
    foreach ($inventoryModules as $moduleName) {
        $model = Settings_Vtiger_TermsAndConditions_Model::getInstance($moduleName);
        $model->setText($tAndC);
        $model->setType($moduleName);
        $model->save();
    }

    $columns = $db->getColumnNames('vtiger_import_queue');
    if (!in_array('lineitem_currency_id', $columns)) {
        $db->pquery('ALTER TABLE vtiger_import_queue ADD COLUMN lineitem_currency_id INT(5)', []);
    }
    if (!in_array('paging', $columns)) {
        $db->pquery('ALTER TABLE vtiger_import_queue ADD COLUMN paging INT(1) DEFAULT 0', []);
    }

    $documentsInstance = Vtiger_Module::getInstance('Documents');
    if ($documentsInstance) {
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Contacts'), 'Contacts', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Accounts'), 'Accounts', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Potentials'), 'Potentials', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Leads'), 'Leads', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Products'), 'Products', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Services'), 'Services', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Project'), 'Project', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Assets'), 'Assets', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('ServiceContracts'), 'ServiceContracts', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Quotes'), 'Quotes', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Invoice'), 'Invoice', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('SalesOrder'), 'SalesOrder', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('PurchaseOrder'), 'PurchaseOrder', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('HelpDesk'), 'HelpDesk', true);
        $documentsInstance->setRelatedList(Vtiger_Module::getInstance('Faq'), 'Faq', true);
    }

    // Update relation field for existing relation ships
    $ignoreRelationFieldMapping = ['Emails'];
    $query = 'SELECT * FROM vtiger_relatedlists ORDER BY tabid ';
    $result = $db->pquery($query, []);
    $num_rows = $db->num_rows($result);
    $relationShipMapping = [];
    for ($i = 0; $i < $num_rows; ++$i) {
        $tabId = $db->query_result($result, $i, 'tabid');
        $relatedTabid = $db->query_result($result, $i, 'related_tabid');
        $relationId = $db->query_result($result, $i, 'relation_id');
        $primaryModuleInstance = Vtiger_Module_Model::getInstance($tabId);
        $relatedModuleInstance = Vtiger_Module_Model::getInstance($relatedTabid);

        if (empty($relatedModuleInstance)) {
            continue;
        }

        $primaryModuleName = $primaryModuleInstance->getName();
        $relatedModuleName = $relatedModuleInstance->getName();

        // $relatedModulesIgnored = $ignoreRelationFieldMapping[$primaryModuleName];
        if (in_array($relatedModuleName, $ignoreRelationFieldMapping)) {
            continue;
        }
        $relatedModuleReferenceFields = $relatedModuleInstance->getFieldsByType('reference');
        foreach ($relatedModuleReferenceFields as $fieldModel) {
            if ($fieldModel->isCustomField()) {
                // for custom reference field we cannot do relation ships so ignoring them
                continue;
            }
            $referenceList = $fieldModel->getReferenceList(false);
            if (in_array($primaryModuleName, $referenceList)) {
                $relationShipMapping[$primaryModuleName][$relatedModuleName] = $fieldModel->getName();
                $updateQuery = 'UPDATE vtiger_relatedlists SET relationfieldid=? WHERE relation_id=?';
                $db->pquery($updateQuery, [$fieldModel->getId(), $relationId]);
                break;
            }
        }
    }

    $columns = $db->getColumnNames('vtiger_links');
    if (!in_array('parent_link', $columns)) {
        $db->pquery('ALTER TABLE vtiger_links ADD COLUMN parent_link INT(19)', []);
    }

    $moduleName = 'Reports';
    $reportModel = Vtiger_Module_Model::getInstance($moduleName);
    $reportTabId = $reportModel->getId();
    Vtiger_Link::addLink($reportTabId, 'LISTVIEWBASIC', 'LBL_ADD_RECORD', '', '', '0');

    $reportAddRecordLink = $db->pquery('SELECT linkid FROM vtiger_links WHERE tabid=? AND linklabel=?', [$reportTabId, 'LBL_ADD_RECORD']);
    $parentLinkId = $db->query_result($reportAddRecordLink, 0, 'linkid');

    $reportModelHandler = ['path' => 'modules/Reports/models/Module.php', 'class' => 'Reports_Module_Model', 'method' => 'checkLinkAccess'];
    Vtiger_Link::addLink($reportTabId, 'LISTVIEWBASIC', 'LBL_DETAIL_REPORT', 'javascript:Reports_List_Js.addReport("' . $reportModel->getCreateRecordUrl() . '")', '', '0', $reportModelHandler, $parentLinkId);
    Vtiger_Link::addLink($reportTabId, 'LISTVIEWBASIC', 'LBL_CHARTS', 'javascript:Reports_List_Js.addReport("index.php?module=Reports&view=ChartEdit")', '', '0', $reportModelHandler, $parentLinkId);
    Vtiger_Link::addLink($reportTabId, 'LISTVIEWBASIC', 'LBL_ADD_FOLDER', 'javascript:Reports_List_Js.triggerAddFolder("' . $reportModel->getAddFolderUrl() . '")', '', '0', $reportModelHandler);

    $allFolders = Reports_Folder_Model::getAll();
    foreach ($allFolders as $folderId => $folderModel) {
        $folderModel->set('foldername', decode_html(vtranslate($folderModel->getName(), $moduleName)));
        $folderModel->set('folderdesc', decode_html(vtranslate($folderModel->get('folderdesc'), $moduleName)));
        $folderModel->save();
    }

    $columns = $db->getColumnNames('vtiger_schedulereports');
    if (!in_array('fileformat', $columns)) {
        $db->pquery('ALTER TABLE vtiger_schedulereports ADD COLUMN fileformat VARCHAR(10) DEFAULT "CSV"', []);
    }

    $modCommentsInstance = Vtiger_Module_Model::getInstance('ModComments');
    $modCommentsTabId = $modCommentsInstance->getId();

    $modCommentFieldInstance = Vtiger_Field_Model::getInstance('related_to', $modCommentsInstance);
    $modCommentFieldInstance->setRelatedModules(getInventoryModules());

    $refModulesList = $modCommentFieldInstance->getReferenceList();
    foreach ($refModulesList as $refModuleName) {
        $refModuleModel = Vtiger_Module_Model::getInstance($refModuleName);
        $refModuleTabId = $refModuleModel->getId();
        $db->pquery('UPDATE vtiger_relatedlists SET sequence=(sequence+1) WHERE tabid=?', [$refModuleTabId]);

        $query = 'SELECT 1 FROM vtiger_relatedlists WHERE tabid=? AND related_tabid =?';
        $result = $db->pquery($query, [$refModuleTabId, $modCommentsTabId]);
        if (!$db->num_rows($result)) {
            $db->pquery('INSERT INTO vtiger_relatedlists VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [$db->getUniqueID('vtiger_relatedlists'), $refModuleTabId, $modCommentsTabId, 'get_comments', '1', 'ModComments', '0', '', $fieldId, 'NULL', '1:N']);
        }
    }

    $columns = $db->getColumnNames('vtiger_modcomments');
    if (in_array('parent_comments', $columns)) {
        $db->pquery('ALTER TABLE vtiger_modcomments MODIFY parent_comments INT(19)', []);
    }
    if (in_array('customer', $columns)) {
        $db->pquery('ALTER TABLE vtiger_modcomments MODIFY customer INT(19)', []);
    }
    if (in_array('userid', $columns)) {
        $db->pquery('ALTER TABLE vtiger_modcomments MODIFY userid INT(19)', []);
    }

    $columns = $db->getColumnNames('vtiger_emailtemplates');
    if (!in_array('systemtemplate', $columns)) {
        $db->pquery('ALTER TABLE vtiger_emailtemplates ADD COLUMN systemtemplate INT(1) NOT NULL DEFAULT 0', []);
    }
    if (!in_array('templatepath', $columns)) {
        $db->pquery('ALTER TABLE vtiger_emailtemplates ADD COLUMN templatepath VARCHAR(100) AFTER templatename', []);
    }
    if (!in_array('module', $columns)) {
        $db->pquery('ALTER TABLE vtiger_emailtemplates ADD COLUMN module VARCHAR(100)', []);
    }

    $moduleName = 'Calendar';
    $reminderTemplateResult = $db->pquery('SELECT 1 FROM vtiger_emailtemplates WHERE subject=? AND systemtemplate=?', ['Reminder', '1']);
    if (!$db->num_rows($reminderTemplateResult)) {
        $body = '<p>' . vtranslate('LBL_REMINDER_NOTIFICATION', $moduleName) . '<br/>'
                . vtranslate('LBL_DETAILS_STRING', $moduleName) . ' :<br/> 
							&nbsp; ' . vtranslate('Subject', $moduleName) . ' : $events-subject$<br/> 
							&nbsp; ' . vtranslate('Start Date & Time', $moduleName) . ' : $events-date_start$<br/>
							&nbsp; ' . vtranslate('End Date & Time', $moduleName) . ' : $events-due_date$<br/> 
							&nbsp; ' . vtranslate('LBL_STATUS', $moduleName) . ' : $events-eventstatus$<br/> 
							&nbsp; ' . vtranslate('Location', $moduleName) . ' : $events-location$<br/> 
							&nbsp; ' . vtranslate('LBL_APP_DESCRIPTION', $moduleName) . ' : $events-description$<br/><br/> 
							<p/>';
        $db->pquery('INSERT INTO vtiger_emailtemplates(foldername,templatename,subject,description,body,systemtemplate,templateid) values(?,?,?,?,?,?,?)', ['Public', 'Activity Reminder', 'Reminder', 'Reminder', $body, '1', $db->getUniqueID('vtiger_emailtemplates')]);
    }

    // Creating new reminder block in calendar todo
    $calendarInstance = Vtiger_Module_Model::getInstance($moduleName);
    $tabId = $calendarInstance->getId();

    // Updates sequence of blocks available in users module.
    Vtiger_Block_Model::pushDown('1', $tabId);

    if (!Vtiger_Block_Model::checkDuplicate('LBL_REMINDER_INFORMATION', $tabId)) {
        $reminderBlock = new Vtiger_Block();
        $reminderBlock->label = 'LBL_REMINDER_INFORMATION';
        $reminderBlock->sequence = 2;
        $calendarInstance->addBlock($reminderBlock);
    }

    // updating block and displaytype for send reminder field
    $reminderBlockInstance = Vtiger_Block_Model::getInstance('LBL_REMINDER_INFORMATION', $calendarInstance);
    $db->pquery('UPDATE vtiger_field SET block=?, displaytype=? WHERE tabid=? AND fieldname=?', [$reminderBlockInstance->id, '1', $tabId, 'reminder_time']);

    // adding new reminder template for todo
    $reminderTemplate = $db->pquery('SELECT 1 FROM vtiger_emailtemplates WHERE subject=? AND systemtemplate=?', ['Activity Reminder', '1']);
    if (!$db->num_rows($reminderTemplate)) {
        $body = '<p>' . vtranslate('LBL_REMINDER_NOTIFICATION', $moduleName) . '<br/>'
                . vtranslate('LBL_DETAILS_STRING', $moduleName) . ' :<br/>
								&nbsp; ' . vtranslate('Subject', $moduleName) . ' : $calendar-subject$<br/>
								&nbsp; ' . vtranslate('Start Date & Time', $moduleName) . ' : $calendar-date_start$<br/>
								&nbsp; ' . vtranslate('Due Date', $moduleName) . ' : $calendar-due_date$<br/>
								&nbsp; ' . vtranslate('LBL_STATUS', $moduleName) . ' : $calendar-status$<br/>
								&nbsp; ' . vtranslate('Location', $moduleName) . ' : $calendar-location$<br/>
								&nbsp; ' . vtranslate('LBL_APP_DESCRIPTION', $moduleName) . ' : $calendar-description$<br/><br/>
								<p/>';
        $db->pquery('INSERT INTO vtiger_emailtemplates(foldername,templatename,subject,description,body,systemtemplate,templateid) values(?,?,?,?,?,?,?)', ['Public', 'ToDo Reminder', 'Activity Reminder', 'Reminder', $body, '1', $db->getUniqueID('vtiger_emailtemplates')]);
    }

    $inviteUsersTemplate = $db->pquery('SELECT 1 FROM vtiger_emailtemplates WHERE subject=?', ['Invitation']);
    if (!$db->num_rows($inviteUsersTemplate)) {
        $body = '<p>$invitee_name$,<br/><br/>'
                . vtranslate('LBL_ACTIVITY_INVITATION', $moduleName) . '<br/><br/>'
                . vtranslate('LBL_DETAILS_STRING', $moduleName) . ' :<br/>
								&nbsp; ' . vtranslate('Subject', $moduleName) . ' : $events-subject$<br/>
								&nbsp; ' . vtranslate('Start Date & Time', $moduleName) . ' : $events-date_start$<br/> 
								&nbsp; ' . vtranslate('End Date & Time', $moduleName) . ' : $events-due_date$<br/>
								&nbsp; ' . vtranslate('LBL_STATUS', $moduleName) . ' : $events-eventstatus$<br/>
								&nbsp; ' . vtranslate('Priority', $moduleName) . ' : $events-priority$<br/>
								&nbsp; ' . vtranslate('Related To', $moduleName) . ' : $events-crmid$<br/>
								&nbsp; ' . vtranslate('LBL_CONTACT_LIST', $moduleName) . ' : $events-contactid$<br/>
								&nbsp; ' . vtranslate('Location', $moduleName) . ' : $events-location$<br/>
								&nbsp; ' . vtranslate('LBL_APP_DESCRIPTION', $moduleName) . ' : $events-description$<br/><br/>
								' . vtranslate('LBL_REGARDS_STRING', $moduleName) . ',<br/>
								$current_user_name$
								<p/>';
        $db->pquery('INSERT INTO vtiger_emailtemplates(foldername,templatename,subject,description,body,systemtemplate,templateid) values(?,?,?,?,?,?,?)', ['Public', 'Invite Users', 'Invitation', 'Invite Users', $body, '1', $db->getUniqueID('vtiger_emailtemplates')]);
    }

    if (!Vtiger_Utils::CheckTable('vtiger_emailslookup')) {
        $query = 'CREATE TABLE vtiger_emailslookup(crmid int(20) DEFAULT NULL, 
						setype varchar(30) DEFAULT NULL, value varchar(100) DEFAULT NULL, 
						fieldid int(20) DEFAULT NULL, UNIQUE KEY emailslookup_crmid_setype_fieldname_uk (crmid,setype,fieldid),
						KEY emailslookup_fieldid_setype_idx (fieldid, setype), 
						CONSTRAINT emailslookup_crmid_fk FOREIGN KEY (crmid) REFERENCES vtiger_crmentity (crmid) ON DELETE CASCADE)';
        $db->pquery($query, []);
    }

    $EventManager = new VTEventsManager($db);
    $createEvent = 'vtiger.entity.aftersave';
    $handler_path = 'modules/Vtiger/handlers/EmailLookupHandler.php';
    $className = 'EmailLookupHandler';
    $EventManager->registerHandler($createEvent, $handler_path, $className, '', '["VTEntityDelta"]');

    $deleteEvent = 'vtiger.entity.afterdelete';
    $EventManager->registerHandler($deleteEvent, $handler_path, $className, '');

    $restoreEvent = 'vtiger.entity.afterrestore';
    $EventManager->registerHandler($restoreEvent, $handler_path, $className, '');

    $createBatchEvent = 'vtiger.batchevent.save';
    $EventManager->registerHandler($createBatchEvent, $handler_path, 'EmailLookupBatchHandler', '');

    $EmailsModuleModel = Vtiger_Module_Model::getInstance('Emails');
    $emailSupportedModulesList = $EmailsModuleModel->getEmailRelatedModules();

    $recordModel = new Emails_Record_Model();
    foreach ($emailSupportedModulesList as $module) {
        if ($module != 'Users') {
            $moduleInstance = CRMEntity::getInstance($module);

            $query = $moduleInstance->buildSearchQueryForFieldTypes(['13']);
            $moduleModel = Vtiger_Module_Model::getInstance($module);
            $emailFieldModels = $moduleModel->getFieldsByType('email');
            $emailFieldNames = array_keys($emailFieldModels);
            foreach ($emailFieldModels as $fieldName => $fieldModel) {
                $emailFieldIds[$fieldModel->get('name')] = $fieldModel->get('id');
            }
            $result = $db->pquery($query, []);

            $values['setype'] = $module;

            while ($row = $db->fetchByAssoc($result)) {
                $values['crmid'] = $row['id'];
                foreach ($row as $fieldName => $value) {
                    if (in_array($fieldName, $emailFieldNames) && !empty($value)) {
                        $fieldId = $emailFieldIds[$fieldName];
                        $values[$fieldId] = $value;
                        $recordModel->recieveEmailLookup($fieldId, $values);
                    }
                }
            }
        }
    }

    $massEditSql = 'UPDATE vtiger_field SET masseditable=0 WHERE fieldname IN(?,?,?,?)';
    $db->pquery($massEditSql, ['created_user_id', 'createdtime', 'modifiedtime', 'modifiedby']);

    $db->pquery('UPDATE vtiger_eventhandlers SET is_active = 1 WHERE handler_class=?', ['ModTrackerHandler']);
    Vtiger_Link_Model::deleteLink('0', 'DETAILVIEWBASIC', 'Print');

    $db->pquery('ALTER TABLE vtiger_emailtemplates MODIFY COLUMN subject VARCHAR(255)', []);
    $db->pquery('ALTER TABLE vtiger_activity MODIFY COLUMN subject VARCHAR(255)', []);

    // Start: Update Currency symbol for Egypt
    $db->pquery('UPDATE vtiger_currencies SET currency_symbol=? WHERE currency_name=?', ['E£', 'Egypt, Pounds']);
    $db->pquery('UPDATE vtiger_currency_info SET currency_symbol=? WHERE currency_name=?', ['E£', 'Egypt, Pounds']);

    // setting is_private value of comments to 0 if internal comments is not supported for that module
    $modCommentsInstance = Vtiger_Module::getInstance('ModComments');
    $blockInstance = Vtiger_Block::getInstance('LBL_MODCOMMENTS_INFORMATION', $modCommentsInstance);
    if ($blockInstance) {
        $fieldInstance = Vtiger_Field::getInstance('is_private', $modCommentsInstance);
        if (!$fieldInstance) {
            $fieldInstance				= new Vtiger_Field();
            $fieldInstance->name		= 'is_private';
            $fieldInstance->label		= 'Is Private';
            $fieldInstance->uitype		= 7;
            $fieldInstance->column		= 'is_private';
            $fieldInstance->columntype	= 'INT(1) DEFAULT 0';
            $fieldInstance->typeofdata	= 'I~O';
            $blockInstance->addField($fieldInstance);
        }
        unset($fieldInstance);

        $fieldInstance = Vtiger_Field::getInstance('filename', $modCommentsInstance);
        if (!$fieldInstance) {
            $fieldInstance = new Vtiger_Field();
            $fieldInstance->name		= 'filename';
            $fieldInstance->column		= 'filename';
            $fieldInstance->label		= 'Attachment';
            $fieldInstance->columntype	= 'VARCHAR(255)';
            $fieldInstance->table		= 'vtiger_modcomments';
            $fieldInstance->typeofdata	= 'V~O';
            $fieldInstance->uitype		= '61';
            $fieldInstance->presence	= '0';
            $blockInstance->addField($fieldInstance);
        }
        unset($fieldInstance);

        $fieldInstance = Vtiger_Field::getInstance('related_email_id', $modCommentsInstance);
        if (!$fieldInstance) {
            $fieldInstance = new Vtiger_Field();
            $fieldInstance->name		= 'related_email_id';
            $fieldInstance->label		= 'Related Email Id';
            $fieldInstance->uitype		= 1;
            $fieldInstance->column		= $fieldInstance->name;
            $fieldInstance->columntype	= 'INT(11)';
            $fieldInstance->typeofdata	= 'I~O';
            $fieldInstance->defaultvalue = 0;
            $blockInstance->addField($fieldInstance);
        }
        unset($fieldInstance);
    }

    $internalCommentModules = Vtiger_Functions::getPrivateCommentModules();
    $lastMaxCRMId = 0;

    while (true) {
        $commentsResult = $db->pquery('SELECT vtiger_modcomments.modcommentsid FROM vtiger_modcomments 
												LEFT JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_modcomments.related_to 
												WHERE (vtiger_crmentity.setype NOT IN (' . generateQuestionMarks($internalCommentModules) . ') 
												OR vtiger_crmentity.setype IS NULL) AND modcommentsid > ? LIMIT 500', array_merge($internalCommentModules, [$lastMaxCRMId]));
        if (!$db->num_rows($commentsResult)) {
            break;
        }

        $commentIds = [];

        while ($row = $db->fetch_array($commentsResult)) {
            $commentIds[] = $row['modcommentsid'];
        }

        if (php7_count($commentIds) > 0) {
            $db->pquery('UPDATE vtiger_modcomments SET is_private = 0 WHERE modcommentsid IN (' . generateQuestionMarks($commentIds) . ')', $commentIds);
        }

        $commentId = end($commentIds);
        if (intval($commentId) > $lastMaxCRMId) {
            $lastMaxCRMId = intval($commentId);
        }
        $commentsResult = null;
        unset($commentsResult);
    }

    // Start - Add Contact Name to Default filter of project
    $cvidQuery = $db->pquery('SELECT cvid FROM vtiger_customview where viewname=? AND entitytype=?', ['All', 'Project']);
    $row = $db->fetch_array($cvidQuery);
    if ($row['cvid']) {
        $columnNameCount = $db->pquery('SELECT 1 FROM vtiger_cvcolumnlist WHERE cvid=? and columnname=?', [$row['cvid'], 'vtiger_project:contactid:contactid:Project_Contact_Name:V']);
        if (!$db->num_rows($columnNameCount)) {
            $columnIndexQuery = $db->pquery('SELECT MAX(columnindex) AS columnindex FROM vtiger_cvcolumnlist WHERE cvid=?', [$row['cvid']]);
            $colIndex = $db->fetch_array($columnIndexQuery);
            $db->pquery('INSERT INTO vtiger_cvcolumnlist(cvid,columnindex,columnname) VALUES(?,?,?)', [$row['cvid'], $colIndex['columnindex'] + 11, 'vtiger_project:contactid:contactid:Project_Contact_Name:V']);
        }
    }
    // End

    $moduleSpecificHeaderFields = [
        'Accounts'			=> ['website', 'email1', 'phone'],
        'Contacts'			=> ['email', 'phone'],
        'Leads'				=> ['email', 'phone'],
        'Potentials'		=> ['related_to', 'email', 'amount', 'sales_stage'],
        'HelpDesk'			=> ['ticketpriorities'],
        'Invoice'			=> ['contact_id', 'account_id', 'assigned_user_id', 'invoicestatus'],
        'Products'			=> ['product_no', 'discontinued', 'qtyinstock', 'productcategory'],
        'Project'			=> ['linktoaccountscontacts', 'contactid'],
        'PurchaseOrder'		=> ['contact_id', 'assigned_user_id', 'postatus'],
        'Quotes'			=> ['account_id', 'contact_id', 'hdnGrandTotal', 'quotestage'],
        'SalesOrder'		=> ['contact_id', 'account_id', 'assigned_user_id', 'sostatus'],
        'Vendors'			=> ['website', 'email', 'phone'],
    ];
    $moduleTabIds = [];
    foreach ($moduleSpecificHeaderFields as $moduleName => $headerFields) {
        $tabid = getTabid($moduleName);
        if ($tabid) {
            $sql = 'UPDATE vtiger_field SET headerfield=?, summaryfield=? WHERE tabid=? AND fieldname IN (' . generateQuestionMarks($headerFields) . ')';
            $db->pquery($sql, array_merge([1, 0, $tabid], $headerFields));
        }
    }

    // Update Calendar time_start as mandatory.
    $updateQuery = 'UPDATE vtiger_field SET typeofdata=? WHERE fieldname=? AND tabid=?';
    $db->pquery($updateQuery, ['T~M', 'time_start', getTabid('Calendar')]);

    $ignoreModules = ['SMSNotifier', 'ModComments'];
    $result = $db->pquery('SELECT name FROM vtiger_tab WHERE isentitytype=? AND name NOT IN (' . generateQuestionMarks($ignoreModules) . ')', [1, $ignoreModules]);
    $modules = [];

    while ($row = $db->fetchByAssoc($result)) {
        $modules[] = $row['name'];
    }

    foreach ($modules as $module) {
        $moduleInstance = Vtiger_Module::getInstance($module);
        if ($moduleInstance) {
            $fieldInstance = Vtiger_Field::getInstance('source', $moduleInstance);
            if ($fieldInstance) {
                continue;
            }
            $blockQuery = 'SELECT blockid FROM vtiger_blocks WHERE tabid=? ORDER BY sequence LIMIT 1';
            $result = $db->pquery($blockQuery, [$moduleInstance->id]);
            $block = $db->query_result($result, 0, 'blockid');
            if ($block) {
                $blockInstance = Vtiger_Block::getInstance($block, $moduleInstance);
                $field = new Vtiger_Field();
                $field->name			= 'source';
                $field->label			= 'Source';
                $field->table			= 'vtiger_crmentity';
                $field->presence		= 2;
                $field->displaytype		= 2;
                $field->readonly		= 1;
                $field->uitype			= 1;
                $field->typeofdata		= 'V~O';
                $field->quickcreate		= 3;
                $field->masseditable	= 0;
                $blockInstance->addField($field);
            }
        }
    }

    $projectModule = Vtiger_Module_Model::getInstance('Project');
    $emailModule = Vtiger_Module_Model::getInstance('Emails');
    $projectModule->setRelatedList($emailModule, 'Emails', 'ADD', 'get_emails');

    $projectTaskModule = Vtiger_Module_Model::getInstance('ProjectTask');
    $projectTaskModule->setRelatedList($emailModule, 'Emails', 'ADD', 'get_emails');

    $sql = 'CREATE TABLE IF NOT EXISTS vtiger_emails_recipientprefs(`id` INT(11) NOT NULL AUTO_INCREMENT,`tabid` INT(11) NOT NULL,
				`prefs` VARCHAR(255) NULL DEFAULT NULL, `userid` INT(11), PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8';
    $db->pquery($sql, []);

    // To change the convert lead webserice operation parameters which was wrong earliear
    require_once 'include/Webservices/Utils.php';
    $convertLeadOperationQueryRes = $db->pquery('SELECT operationid FROM vtiger_ws_operation WHERE name=?', ['convertlead']);
    if ($db->num_rows($convertLeadOperationQueryRes)) {
        $operationId = $db->query_result($convertLeadOperationQueryRes, '0', 'operationid');
        $deleteParameterQuery = $db->pquery('DELETE FROM vtiger_ws_operation_parameters WHERE operationid=?', [$operationId]);
        vtws_addWebserviceOperationParam($operationId, 'element', 'encoded', 1);
    }

    // Start : Change fieldLabel of description field to Description - Project module.

    $fieldModel = Vtiger_Field_Model::getInstance('description', Vtiger_Module_Model::getInstance('Project'));
    $fieldModel->set('label', 'Description');
    $fieldModel->__update();

    $db->pquery('ALTER TABLE vtiger_mail_accounts MODIFY mail_password TEXT', []);

    // making priority as mandatory field in Tickets.
    $fieldInstance = Vtiger_Field_Model::getInstance('ticketpriorities', Vtiger_Module_Model::getInstance('HelpDesk'));
    $fieldInstance->set('typeofdata', 'V~M');
    $fieldInstance->save();

    if (Vtiger_Utils::CheckTable('vtiger_customerportal_tabs')) {
        $db->pquery('UPDATE vtiger_customerportal_tabs SET visible=? WHERE tabid IN(?,?)', [0, getTabid('Contacts'), getTabid('Accounts')]);
        $moduleId = getTabid('ServiceContracts');
        $db->pquery('DELETE FROM vtiger_customerportal_tabs WHERE tabid=?', [$moduleId]);
        $sequenceQuery = 'SELECT max(sequence) AS sequence FROM vtiger_customerportal_tabs';
        $seqResult = $db->pquery($sequenceQuery, []);
        $sequence = $db->query_result($seqResult, 0, 'sequence');
        $db->pquery('INSERT INTO vtiger_customerportal_tabs(tabid,visible,sequence) VALUES (?,?,?)', [$moduleId, 1, $sequence + 11]);
    }

    if (Vtiger_Utils::CheckTable('vtiger_customerportal_fields')) {
        $columns = $db->getColumnNames('vtiger_customerportal_fields');
        if (!in_array('fieldinfo', $columns)) {
            $db->pquery('ALTER TABLE vtiger_customerportal_fields CHANGE fieldid fieldinfo TEXT', []);
        }
        if (!in_array('records_visible', $columns)) {
            $db->pquery('ALTER TABLE vtiger_customerportal_fields CHANGE visible records_visible INT(1)', []);
        }

        $moduleModel = Settings_Vtiger_Module_Model::getInstance('Settings:CustomerPortal');
        $modules = $moduleModel->getModulesList();

        foreach ($modules as $tabid => $model) {
            $moduleModel = Vtiger_Module_Model::getInstance($model->getName());
            $allFields = $moduleModel->getFields();
            $mandatoryFields = [];
            foreach ($allFields as $key => $value) {
                if ($value->isMandatory() && $value->isViewableInDetailView()) {
                    $mandatoryFields[$value->name] = 1;
                }
            }
            if ($tabid == getTabid('HelpDesk')) {
                $mandatoryFields['description'] = 1;
                $mandatoryFields['product_id'] = 1;
                $mandatoryFields['ticketseverities'] = 1;
                $mandatoryFields['ticketcategories'] = 1;
            }
            if ($tabid == getTabid('Documents')) {
                $mandatoryFields['filename'] = 0;
            }
            $recordVisibilityQuery = 'SELECT prefvalue from vtiger_customerportal_prefs WHERE tabid=? AND prefkey=?';
            $recordVisibilityQueryResult = $db->pquery($recordVisibilityQuery, [$tabid, 'showrelatedinfo']);
            $visibilty = 1;
            if (!$db->num_rows($recordVisibilityQueryResult)) {
                $visibilty = $db->query_result($recordVisibilityQueryResult, 0, 'prefvalue');
            }
            $db->pquery('INSERT INTO vtiger_customerportal_fields(tabid,fieldinfo,records_visible) VALUES(?,?,?)', [$tabid, json_encode($mandatoryFields), $visibilty]);
        }
    }

    if (!Vtiger_Utils::CheckTable('vtiger_customerportal_relatedmoduleinfo')) {
        $db->pquery('CREATE TABLE vtiger_customerportal_relatedmoduleinfo(module INT(11),relatedmodules TEXT) ', []);
        $moduleModel = Settings_Vtiger_Module_Model::getInstance('Settings:CustomerPortal');
        $modules = $moduleModel->getModulesList();
        $oneOperation = ['Invoice', 'Quotes', 'Products', 'Services', 'Documents', 'Assets', 'ProjectMilestone', 'ServiceContracts'];
        $twoOperations = ['ProjectTask'];
        $fiveOperations = ['Project'];
        $threeOperations = ['HelpDesk'];
        $availableTwoOperations = [['name' => 'History', 'value' => 1], ['name' => 'ModComments', 'value' => 1]];
        $availableThreeOperations = [['name' => 'History', 'value' => 1], ['name' => 'ModComments', 'value' => 1], ['name' => 'Documents', 'value' => 1]];
        $availableOneOperations = [['name' => 'History', 'value' => 1]];
        $availableFourOperations = [['name' => 'History', 'value' => 1], ['name' => 'ModComments', 'value' => 1], ['name' => 'ProjectTask', 'value' => 1], ['name' => 'ProjectMilestone', 'value' => 1]];
        $availableFiveOperations = [['name' => 'History', 'value' => 1], ['name' => 'ModComments', 'value' => 1], ['name' => 'ProjectTask', 'value' => 1], ['name' => 'ProjectMilestone', 'value' => 1], ['name' => 'Documents', 'value' => 1]];

        foreach ($modules as $tabid => $model) {
            $moduleName = $model->getName();
            $tabid = getTabid($moduleName);
            if (in_array($moduleName, $oneOperation)) {
                $db->pquery('INSERT INTO vtiger_customerportal_relatedmoduleinfo(module,relatedmodules) VALUES(?,?)', [$tabid, json_encode($availableOneOperations)]);
            } elseif (in_array($moduleName, $threeOperations)) {
                $db->pquery('INSERT INTO vtiger_customerportal_relatedmoduleinfo(module,relatedmodules) VALUES(?,?)', [$tabid, json_encode($availableThreeOperations)]);
            } elseif (in_array($moduleName, $twoOperations)) {
                $db->pquery('INSERT INTO vtiger_customerportal_relatedmoduleinfo(module,relatedmodules) VALUES(?,?)', [$tabid, json_encode($availableTwoOperations)]);
            } elseif (in_array($moduleName, $fiveOperations)) {
                $db->pquery('INSERT INTO vtiger_customerportal_relatedmoduleinfo(module,relatedmodules) VALUES(?,?)', [$tabid, json_encode($availableFiveOperations)]);
            }
        }
    }

    $columns = $db->getColumnNames('vtiger_customerportal_relatedmoduleinfo');
    if (in_array('module', $columns)) {
        $db->pquery('ALTER TABLE vtiger_customerportal_relatedmoduleinfo CHANGE module tabid INT(19)', []);
        $db->pquery('ALTER TABLE vtiger_customerportal_relatedmoduleinfo ADD PRIMARY KEY(tabid)', []);
        $db->pquery('ALTER TABLE vtiger_customerportal_fields ADD PRIMARY KEY(tabid)', []);
    }

    if (!Vtiger_Utils::CheckTable('vtiger_customerportal_settings')) {
        $db->pquery('CREATE TABLE vtiger_customerportal_settings(id int, url VARCHAR(250),default_assignee INT(11),
							support_notification INT(11), announcement TEXT, shortcuts TEXT,widgets TEXT,charts TEXT)', []);
        $availableModules = ['Documents' => ['LBL_ADD_DOCUMENT' => 1], 'HelpDesk' => ['LBL_CREATE_TICKET' => 1, 'LBL_OPEN_TICKETS' => 1]];
        $availableWidgets = ['widgets' => ['HelpDesk' => 1, 'Documents' => 1, 'Faq' => 1]];
        $availableCharts = ['charts' => ['OpenTicketsByPriority' => 1, 'TicketsClosureTimeByPriority' => 1]];
        $encodedShortcuts = json_encode($availableModules);
        $encodedWidgets = json_encode($availableWidgets);
        $encodedCharts = json_encode($availableCharts);
        $db->pquery('INSERT INTO vtiger_customerportal_settings(id,default_assignee,shortcuts,widgets,charts) VALUES(?,?,?,?,?)', [1, 1, $encodedShortcuts, $encodedWidgets, $encodedCharts]);
    }

    $query = 'ALTER TABLE vtiger_portalinfo MODIFY user_password VARCHAR(255)';
    $db->pquery($query, []);

    // Enable mass edit for portal field under Contacts
    $contactsFieldInstance = Vtiger_Field_Model::getInstance('portal', Vtiger_Module_Model::getInstance('Contacts'));
    $contactsFieldInstance->set('masseditable', '1');
    $contactsFieldInstance->save();
    // Customer portal changes end

    $relatedWebservicesOperations = [
        [
            'name' => 'relatedtypes',
            'path' => 'include/Webservices/RelatedTypes.php',
            'method' => 'vtws_relatedtypes',
            'type' => 'GET',
            'params' => [
                ['name' => 'elementType', 'type' => 'string'],
            ],
        ],
        [
            'name' => 'retrieve_related',
            'path' => 'include/Webservices/RetrieveRelated.php',
            'method' => 'vtws_retrieve_related',
            'type' => 'GET',
            'params' => [
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'relatedType', 'type' => 'string'],
                ['name' => 'relatedLabel', 'type' => 'string'],
            ],
        ],
        [
            'name' => 'query_related',
            'path' => 'include/Webservices/QueryRelated.php',
            'method' => 'vtws_query_related',
            'type' => 'GET',
            'params' => [
                ['name' => 'query', 'type' => 'string'],
                ['name' => 'id', 'type' => 'string'],
                ['name' => 'relatedLabel', 'type' => 'string'],
            ],
        ],
    ];
    foreach ($relatedWebservicesOperations as $operation) {
        $rs = $db->pquery('SELECT 1 FROM vtiger_ws_operation WHERE name=?', [$operation['name']]);
        if (!$db->num_rows($rs)) {
            $operationId = vtws_addWebserviceOperation($operation['name'], $operation['path'], $operation['method'], $operation['type']);
            $sequence = 1;
            foreach ($operation['params'] as $param) {
                vtws_addWebserviceOperationParam($operationId, $param['name'], $param['type'], $sequence++);
            }
        }
    }
    // Change to modify shipping tax percent column type
    $db->pquery('ALTER TABLE vtiger_invoice MODIFY s_h_percent DECIMAL(25,8)', []);

    if (!Vtiger_Utils::CheckTable('vtiger_projecttask_status_color')) {
        $db->pquery('CREATE TABLE vtiger_projecttask_status_color (
									status varchar(255),
									defaultcolor varchar(50),
									color varchar(50),
									UNIQUE KEY status (status)) ENGINE=InnoDB DEFAULT CHARSET=utf8');
    }

    $statusColorMap = [
        'Open'			=> '#0099ff',
        'In Progress'	=> '#fdff00',
        'Completed'		=> '#3BBF67',
        'Deferred'		=> '#fbb11e',
        'Canceled'		=> '#660066'];

    foreach ($statusColorMap as $status => $color) {
        $db->pquery('INSERT INTO vtiger_projecttask_status_color(status,defaultcolor) VALUES(?,?) ON DUPLICATE KEY UPDATE defaultcolor=?', [$status, $color, $color]);
    }

    // Increasing Lead Status column size to 200 for Leads module
    $db->pquery('ALTER TABLE vtiger_leaddetails MODIFY leadstatus VARCHAR(200)', []);

    // Start : Increase tablabel and setype size
    $db->pquery('ALTER TABLE vtiger_tab MODIFY tablabel VARCHAR(100)', []);
    $db->pquery('ALTER TABLE vtiger_crmentity MODIFY setype VARCHAR(100)', []);

    // Changing type of data for Used Units and Total Units fields of Service Contracts module to Decimal
    $fields = ['total_units', 'used_units'];
    $serviceContractsModuleModel = Vtiger_Module_Model::getInstance('ServiceContracts');
    foreach ($fields as $field) {
        $fieldInstance = $serviceContractsModuleModel->getField($field);
        $typeOfData = 'NN~O';
        if ($fieldInstance->isMandatory()) {
            $typeOfData = 'NN~M';
        }
        $fieldInstance->set('typeofdata', $typeOfData);
        $fieldInstance->save();
    }

    $db->pquery('ALTER TABLE vtiger_webforms_field MODIFY COLUMN defaultvalue TEXT', []);

    // Rollup Comments Settings table
    if (!Vtiger_Utils::CheckTable('vtiger_rollupcomments_settings')) {
        Vtiger_Utils::CreateTable(
            'vtiger_rollupcomments_settings',
            "(`rollupid` INT(19) NOT NULL AUTO_INCREMENT,
				`userid` INT(19) NOT NULL,
				`tabid` INT(19) NOT NULL,
				`rollup_status` INT(2) NOT NULL DEFAULT '0',
				PRIMARY KEY (`rollupid`))",
            true,
        );
    }

    $modulesList = ['Products', 'Services'];
    foreach ($modulesList as $moduleName) {
        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        $taxFieldModel = Vtiger_Field_Model::getInstance('taxclass', $moduleModel);
        $taxFieldModel->set('label', 'Taxes');
        $taxFieldModel->set('quickcreate', 2);
        $taxFieldModel->save();
    }

    $columns = $db->getColumnNames('com_vtiger_workflowtask_queue');
    if (!in_array('relatedinfo', $columns)) {
        $db->pquery('ALTER TABLE com_vtiger_workflowtask_queue ADD COLUMN relatedinfo VARCHAR(255)', []);
    }

    $db->pquery('ALTER TABLE vtiger_freetagged_objects MODIFY module VARCHAR(100)', []);
    $db->pquery('ALTER TABLE vtiger_emailslookup MODIFY setype VARCHAR(100)', []);
    $db->pquery('ALTER TABLE vtiger_entityname MODIFY modulename VARCHAR(100)', []);
    $db->pquery('ALTER TABLE vtiger_modentity_num MODIFY semodule VARCHAR(100)', []);
    $db->pquery('ALTER TABLE vtiger_reportmodules MODIFY primarymodule VARCHAR(100)', []);

    $calendarModuleModel = Vtiger_Module_Model::getInstance('Calendar');
    $ProjectModuleModel = Vtiger_Module_Model::getInstance('Project');
    $relationModel = Vtiger_Relation_Model::getInstance($ProjectModuleModel, $calendarModuleModel, 'Activities');

    if ($relationModel !== false) {
        $fieldModel = $calendarModuleModel->getField('parent_id');
        $fieldId = $fieldModel->getId();

        $projectTabId = getTabid('Project');
        $calendarTabId = getTabid('Calendar');
        $result = $db->pquery('SELECT fieldtypeid FROM vtiger_ws_fieldtype WHERE uitype=?', [$fieldModel->get('uitype')]);
        $fieldType = $db->query_result($result, 0, 'fieldtypeid');

        $result = $db->pquery('SELECT 1 FROM vtiger_ws_referencetype WHERE fieldtypeid=? and type=?', [$fieldType, 'Project']);
        if (!$db->num_rows($result)) {
            $db->pquery('INSERT INTO vtiger_ws_referencetype(fieldtypeid,type) VALUES(?, ?)', [$fieldType, 'Project']);
        }

        if (!$relationModel->get('relationfieldid')) {
            $query = 'UPDATE vtiger_relatedlists SET relationfieldid=? ,name=?, relationtype=? WHERE tabid=? AND related_tabid=?';
            $db->pquery($query, [$fieldId, 'get_activities', '1:N', $projectTabId, $calendarTabId]);
        }

        // Migrate data from vtiger_crmentityrel to vtiger_seactivityrel
        $query = 'SELECT 1 FROM vtiger_crmentityrel WHERE module=? AND relmodule= ?';
        $result = $db->pquery($query, ['Project', 'Calendar']);
        if ($db->num_rows($result)) {
            $insertQuery = 'INSERT INTO vtiger_seactivityrel(crmid, activityid) values(?,?)';

            while ($data = $db->fetch_array($result)) {
                $db->pquery($insertQuery, [$data['crmid'], $data['relcrmid']]);
            }
            $db->pquery('DELETE FROM vtiger_crmentityrel WHERE module=? AND relmodule= ?', ['Project', 'Calendar']);
        }
    }

    $result = $db->pquery('SHOW INDEX FROM vtiger_crmentityrel WHERE key_name=?', ['crmid_idx']);
    if (!$db->num_rows($result)) {
        $db->pquery('ALTER TABLE vtiger_crmentityrel ADD INDEX crmid_idx(crmid)', []);
    }
    $result = $db->pquery('SHOW INDEX FROM vtiger_crmentityrel WHERE key_name=?', ['relcrmid_idx']);
    if (!$db->num_rows($result)) {
        $db->pquery('ALTER TABLE vtiger_crmentityrel ADD INDEX relcrmid_idx(relcrmid)', []);
    }

    // Start : Inactivate update_log field from ticket module
    $fieldModel = Vtiger_Field_Model::getInstance('update_log', Vtiger_Module_Model::getInstance('HelpDesk'));
    if ($fieldModel) {
        $fieldModel->set('presence', 1);
        $fieldModel->__update();
    }

    // Start : Project added as related tab for Potentials module.
    $projectModuleModel = Vtiger_Module_Model::getInstance('Project');
    $fieldModel = Vtiger_Field::getInstance('potentialid', $projectModuleModel);
    if ($fieldModel) {
        $fieldModel->setRelatedModules(['Potentials']);
        $result = $db->pquery('SELECT 1 FROM vtiger_relatedlists where tabid=? AND relationfieldid=? AND related_tabid=?', [getTabid('Potentials'), $fieldModel->id, getTabid('Project')]);
        if (!$db->num_rows($result)) {
            $potentialModuleModel = Vtiger_Module_Model::getInstance('Potentials');
            $potentialModuleModel->setRelatedList($projectModuleModel, 'Projects', ['ADD', 'SELECT'], 'get_dependents_list', $fieldModel->id);
        }
    }
    // End

    // Start : Change fieldLabel of description field to Description - ProjectMilestone module.
    $fieldModel = Vtiger_Field_Model::getInstance('description', Vtiger_Module_Model::getInstance('ProjectMilestone'));
    if ($fieldModel) {
        $fieldModel->set('label', 'Description');
        $fieldModel->__update();
    }
    // End

    $module = Vtiger_Module_Model::getInstance('Emails');
    $blocks = $module->getBlocks();
    $block = current($blocks);

    $field = new vtiger_field();
    $field->label = 'Click Count';
    $field->name = 'click_count';
    $field->table = 'vtiger_email_track';
    $field->column = 'click_count';
    $field->columntype = 'INT';
    $field->uitype = 25;
    $field->typeofdata = 'I~O';
    $field->displaytype = 3;
    $field->masseditable = 0;
    $field->quickcreate = 0;
    $field->defaultvalue = 0;
    $block->addfield($field);

    $criteria = ' MODIFY COLUMN click_count INT NOT NULL default 0';
    Vtiger_Utils::AlterTable('vtiger_email_track', $criteria);

    $em = new VTEventsManager($db);
    $em->registerHandler('vtiger.lead.convertlead', 'modules/Leads/handlers/LeadHandler.php', 'LeadHandler');

    Vtiger_Cache::flushModuleCache('Contacts');
    Vtiger_Cache::flushModuleCache('Leads');
    Vtiger_Cache::flushModuleCache('Emails');

    // Add create and edit to field to vtiger_customerportal_tabs to track Create and Edit permission of a module.
    $columns = $db->getColumnNames('vtiger_customerportal_tabs');
    if (!in_array('createrecord', $columns)) {
        $db->pquery('ALTER TABLE vtiger_customerportal_tabs ADD createrecord BOOLEAN NOT NULL DEFAULT FALSE', []);
    }
    if (!in_array('editrecord', $columns)) {
        $db->pquery('ALTER TABLE vtiger_customerportal_tabs ADD editrecord BOOLEAN NOT NULL DEFAULT FALSE', []);
    }

    // Update create and edit status for HelpDesk and Assets.
    $updateCreateEditStatusQuery = 'UPDATE vtiger_customerportal_tabs SET createrecord=?,editrecord=? WHERE tabid IN (?)';
    $db->pquery($updateCreateEditStatusQuery, [1, 1, getTabid('HelpDesk')]);
    $db->pquery($updateCreateEditStatusQuery, [0, 1, getTabid('Contacts')]);
    $db->pquery($updateCreateEditStatusQuery, [0, 1, getTabid('Accounts')]);
    $db->pquery($updateCreateEditStatusQuery, [1, 0, getTabid('Documents')]);
    $db->pquery($updateCreateEditStatusQuery, [0, 1, getTabid('Assets')]);

    $accessCountFieldModel = Vtiger_Field_Model::getInstance('access_count', Vtiger_Module_Model::getInstance('Emails'));
    if ($accessCountFieldModel) {
        $accessCountFieldModel->set('typeofdata', 'I~O');
        $accessCountFieldModel->__update();
        Vtiger_Cache::flushModuleCache('Emails');
    }

    // Adding Create Event and Create Todo workflow tasks for Project module.
    $taskResult = $db->pquery('SELECT id, modules FROM com_vtiger_workflow_tasktypes WHERE tasktypename IN (?, ?)', ['VTCreateTodoTask', 'VTCreateEventTask']);
    $taskResultCount = $db->num_rows($taskResult);
    for ($i = 0; $i < $taskResultCount; ++$i) {
        $taskId = $db->query_result($taskResult, $i, 'id');
        $modules = Zend_Json::decode(decode_html($db->query_result($taskResult, $i, 'modules')));
        $modules['include'][] = 'Project';
        $modulesJson = Zend_Json::encode($modules);
        $db->pquery('UPDATE com_vtiger_workflow_tasktypes SET modules=? WHERE id=?', [$modulesJson, $taskId]);
    }
    // End

    // Multiple attachment support for comments
    $db->pquery('ALTER TABLE vtiger_seattachmentsrel DROP PRIMARY KEY', []);
    $db->pquery('ALTER TABLE vtiger_seattachmentsrel ADD CONSTRAINT PRIMARY KEY (crmid,attachmentsid)', []);
    $db->pquery('ALTER TABLE vtiger_project MODIFY COLUMN projectid INT(19) PRIMARY KEY');

    if (!Vtiger_Utils::TableHasForeignKey('vtiger_seattachmentsrel', 'fk_2_vtiger_seattachmentsrel')) {
        $db->pquery('ALTER TABLE vtiger_seattachmentsrel ADD CONSTRAINT fk_2_vtiger_seattachmentsrel FOREIGN KEY (crmid) REFERENCES vtiger_crmentity(crmid) ON DELETE CASCADE', []);
    }

    if (!Vtiger_Utils::CheckTable('vtiger_wsapp_logs_basic')) {
        Vtiger_Utils::CreateTable(
            'vtiger_wsapp_logs_basic',
            '(`id` int(25) NOT NULL AUTO_INCREMENT,
				`extensiontabid` int(19) DEFAULT NULL,
				`module` varchar(50) NOT NULL,
				`sync_datetime` datetime NOT NULL,
				`app_create_count` int(11) DEFAULT NULL,
				`app_update_count` int(11) DEFAULT NULL,
				`app_delete_count` int(11) DEFAULT NULL,
				`app_skip_count` int(11) DEFAULT NULL,
				`vt_create_count` int(11) DEFAULT NULL,
				`vt_update_count` int(11) DEFAULT NULL,
				`vt_delete_count` int(11) DEFAULT NULL,
				`vt_skip_count` int(11) DEFAULT NULL,
				`userid` int(11) DEFAULT NULL,
				PRIMARY KEY (`id`))',
            true,
        );
    }

    if (!Vtiger_Utils::CheckTable('vtiger_wsapp_logs_details')) {
        Vtiger_Utils::CreateTable(
            'vtiger_wsapp_logs_details',
            '(`id` int(25) NOT NULL,
				`app_create_ids` mediumtext,
				`app_update_ids` mediumtext,
				`app_delete_ids` mediumtext,
				`app_skip_info` mediumtext,
				`vt_create_ids` mediumtext,
				`vt_update_ids` mediumtext,
				`vt_delete_ids` mediumtext,
				`vt_skip_info` mediumtext,
				KEY `vtiger_wsapp_logs_basic_ibfk_1` (`id`),
				CONSTRAINT `vtiger_wsapp_logs_basic_ibfk_1` FOREIGN KEY (`id`) REFERENCES `vtiger_wsapp_logs_basic` (`id`) ON DELETE CASCADE)',
            true,
        );
    }

    if (!Vtiger_Utils::CheckTable('vtiger_cv2users')) {
        Vtiger_Utils::CreateTable(
            'vtiger_cv2users',
            '(`cvid` int(25) NOT NULL,
				`userid` int(25) NOT NULL,
				KEY `vtiger_cv2users_ibfk_1` (`cvid`),
				CONSTRAINT `vtiger_customview_ibfk_1` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
				CONSTRAINT `vtiger_users_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE)',
            true,
        );
    }

    if (!Vtiger_Utils::CheckTable('vtiger_cv2group')) {
        Vtiger_Utils::CreateTable(
            'vtiger_cv2group',
            '(`cvid` int(25) NOT NULL,
				`groupid` int(25) NOT NULL,
				KEY `vtiger_cv2group_ibfk_1` (`cvid`),
				CONSTRAINT `vtiger_customview_ibfk_2` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
				CONSTRAINT `vtiger_groups_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `vtiger_groups` (`groupid`) ON DELETE CASCADE)',
            true,
        );
    }

    if (!Vtiger_Utils::CheckTable('vtiger_cv2role')) {
        Vtiger_Utils::CreateTable(
            'vtiger_cv2role',
            '(`cvid` int(25) NOT NULL,
				`roleid` varchar(255) NOT NULL,
				KEY `vtiger_cv2role_ibfk_1` (`cvid`),
				CONSTRAINT `vtiger_customview_ibfk_3` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
				CONSTRAINT `vtiger_role_ibfk_1` FOREIGN KEY (`roleid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE)',
            true,
        );
    }

    if (!Vtiger_Utils::CheckTable('vtiger_cv2rs')) {
        Vtiger_Utils::CreateTable(
            'vtiger_cv2rs',
            '(`cvid` int(25) NOT NULL,
				`rsid` varchar(255) NOT NULL,
				KEY `vtiger_cv2role_ibfk_1` (`cvid`),
				CONSTRAINT `vtiger_customview_ibfk_4` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
				CONSTRAINT `vtiger_rolesd_ibfk_1` FOREIGN KEY (`rsid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE)',
            true,
        );
    }

    // Rollup Comments Settings table
    if (!Vtiger_Utils::CheckTable('vtiger_rollupcomments_settings')) {
        Vtiger_Utils::CreateTable(
            'vtiger_rollupcomments_settings',
            "(`rollupid` int(19) NOT NULL AUTO_INCREMENT,
				`userid` int(19) NOT NULL,
				`tabid` int(19) NOT NULL,
				`rollup_status` int(2) NOT NULL DEFAULT '0',
				PRIMARY KEY (`rollupid`))",
            true,
        );
    }
    // END

    $transition_table_name = 'vtiger_picklist_transitions';
    if (!Vtiger_Utils::CheckTable($transition_table_name)) {
        Vtiger_Utils::CreateTable(
            $transition_table_name,
            '(fieldname VARCHAR(255) NOT NULL PRIMARY KEY,
				module VARCHAR(100) NOT NULL,
				transition_data VARCHAR(1000) NOT NULL)',
            true,
        );
    }

    // Invite users table mod to support status tracking
    $columns = $db->getColumnNames('vtiger_invitees');
    if (!in_array('status', $columns)) {
        $db->pquery('ALTER TABLE vtiger_invitees ADD COLUMN status VARCHAR(50) DEFAULT NULL', []);
    }

    $modules = [];
    $ignoreModules = ['SMSNotifier', 'ModComments', 'PBXManager'];
    $result = $db->pquery('SELECT name FROM vtiger_tab WHERE isentitytype=? AND name NOT IN (' . generateQuestionMarks($ignoreModules) . ')', [1, $ignoreModules]);

    while ($row = $db->fetchByAssoc($result)) {
        $modules[] = $row['name'];
    }

    foreach ($modules as $module) {
        $moduleUserSpecificTable = Vtiger_Functions::getUserSpecificTableName($module);
        if (!Vtiger_Utils::CheckTable($moduleUserSpecificTable)) {
            Vtiger_Utils::CreateTable(
                $moduleUserSpecificTable,
                '(`recordid` INT(25) NOT NULL,
					`userid` INT(25) NOT NULL)',
                true,
            );
        }
        $moduleInstance = Vtiger_Module::getInstance($module);
        if ($moduleInstance) {
            $fieldInstance = Vtiger_Field::getInstance('starred', $moduleInstance);
            if ($fieldInstance) {
                continue;
            }
            $blockQuery = 'SELECT blocklabel FROM vtiger_blocks WHERE tabid=? ORDER BY sequence LIMIT 1';
            $result = $db->pquery($blockQuery, [$moduleInstance->id]);
            $block = $db->query_result($result, 0, 'blocklabel');
            if ($block) {
                $blockInstance = Vtiger_Block::getInstance($block, $moduleInstance);
                if ($blockInstance) {
                    $field = new Vtiger_Field();
                    $field->name		= 'starred';
                    $field->label		= 'starred';
                    $field->table		= $moduleUserSpecificTable;
                    $field->presence	= 2;
                    $field->displaytype = 6;
                    $field->readonly	= 1;
                    $field->uitype		= 56;
                    $field->typeofdata	= 'C~O';
                    $field->quickcreate	= 3;
                    $field->masseditable = 0;
                    $blockInstance->addField($field);
                }
            }
        }
    }
    // User specific field - star feature

    $ignoreModules[] = 'Webmails';
    foreach ($modules as $module) {
        if (in_array($module, $ignoreModules)) {
            continue;
        }
        $moduleInstance = Vtiger_Module::getInstance($module);
        if ($moduleInstance) {
            $fieldInstance = Vtiger_Field::getInstance('tags', $moduleInstance);
            if ($fieldInstance) {
                continue;
            }
            $focus = CRMEntity::getInstance($module);
            $tableName = $focus->table_name;

            $blockQuery = 'SELECT blocklabel FROM vtiger_blocks WHERE tabid=? ORDER BY sequence LIMIT 1';
            $result = $db->pquery($blockQuery, [$moduleInstance->id]);
            $block = $db->query_result($result, 0, 'blocklabel');
            if ($block) {
                $blockInstance = Vtiger_Block::getInstance($block, $moduleInstance);
                if ($blockInstance) {
                    $field = new Vtiger_Field();
                    $field->name		= 'tags';
                    $field->label		= 'tags';
                    $field->table		= $tableName;
                    $field->presence	= 2;
                    $field->displaytype	= 6;
                    $field->readonly	= 1;
                    $field->uitype		= 1;
                    $field->typeofdata	= 'V~O';
                    $field->columntype	= 'VARCHAR(1)';
                    $field->quickcreate	= 3;
                    $field->masseditable = 0;
                    $blockInstance->addField($field);
                }
            }
        }
    }

    // Add column to track public and private for tags
    $columns = $db->getColumnNames('vtiger_freetags');
    if (!in_array('visibility', $columns)) {
        $db->pquery("ALTER TABLE vtiger_freetags ADD COLUMN visibility VARCHAR(100) NOT NULL DEFAULT 'PRIVATE'", []);
    }
    if (!in_array('owner', $columns)) {
        $db->pquery('ALTER TABLE vtiger_freetags ADD COLUMN owner INT(19) NOT NULL', []);
    }

    // remove ON update field property for tagged_on since below script will update details but we dont want to change time stamp
    // and we did not find any test case where we will update tagged object
    $db->pquery('ALTER TABLE vtiger_freetagged_objects MODIFY tagged_on timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP', []);

    $query = 'SELECT DISTINCT tagger_id,tag_id,tag FROM vtiger_freetagged_objects INNER JOIN vtiger_freetags ON vtiger_freetagged_objects.tag_id = vtiger_freetags.id';
    $result = $db->pquery($query, []);
    $num_rows = $db->num_rows($result);

    if ($num_rows > 0) {
        $tagOwners = [];
        $tagNamesList = [];
        $visibility = Vtiger_Tag_Model::PRIVATE_TYPE;
        for ($i = 0; $i < $num_rows; ++$i) {
            $row = $db->query_result_rowdata($result, $i);
            $tagId = $row['tag_id'];
            $tagOwners[$tagId][] = $row['tagger_id'];
            $tagNamesList[$tagId] = $row['tag'];
        }
        foreach ($tagOwners as $tagId => $ownerList) {
            $tagName = $tagNamesList[$tagId];
            foreach ($ownerList as $index => $ownerId) {
                // for frist user dont have create seperate tag.for rest of the users we need to create
                if ($index != 0) {
                    // creating new Tag
                    $newTagId = $db->getUniqueId('vtiger_freetags');
                    $db->pquery('INSERT INTO vtiger_freetags values(?,?,?,?,?)', [$newTagId, $tagName, $tagName, $visibility, $ownerId]);

                    // update all existing record tags to new tags
                    $db->pquery('UPDATE vtiger_freetagged_objects SET tag_id=? WHERE tag_id=? and tagger_id=?', [$newTagId, $tagId, $ownerId]);
                } else {
                    // update owner column for tag
                    $db->pquery('UPDATE vtiger_freetags SET owner=? WHERE id=?', [$ownerId, $tagId]);
                }
            }
        }
    }

    // Adding color column for picklists
    $fieldResult = $db->pquery('SELECT fieldname FROM vtiger_field WHERE uitype IN (?,?,?,?) AND tabid NOT IN (?)', ['15', '16', '33', '114', getTabid('Users')]);
    $fieldRows = $db->num_rows($fieldResult);
    $ignorePickListFields = ['hdnTaxType', 'email_flag'];

    for ($i = 0; $i < $fieldRows; ++$i) {
        $fieldName = $db->query_result($fieldResult, $i, 'fieldname');
        if (in_array($fieldName, $ignorePickListFields) || !Vtiger_Utils::CheckTable("vtiger_{$fieldName}")) {
            continue;
        }

        // Add column in vtiger_tab which will hold source
        $columns = $db->getColumnNames("vtiger_{$fieldName}");
        if (!in_array('color', $columns)) {
            $db->pquery("ALTER TABLE vtiger_{$fieldName} ADD COLUMN color VARCHAR(10)", []);
        }
    }

    // Removing color for users module
    $fieldResult = $db->pquery('SELECT fieldname FROM vtiger_field WHERE uitype IN (?,?,?,?) AND tabid IN (?)', ['15', '16', '33', '114', getTabid('Users')]);
    $fieldRows = $db->num_rows($fieldResult);

    for ($i = 0; $i < $fieldRows; ++$i) {
        $fieldName = $db->query_result($fieldResult, $i, 'fieldname');
        if (!Vtiger_Utils::CheckTable("vtiger_{$fieldName}")) {
            continue;
        }

        // Drop color column
        $columns = $db->getColumnNames("vtiger_{$fieldName}");
        if (in_array('color', $columns)) {
            $db->pquery("ALTER TABLE vtiger_{$fieldName} DROP COLUMN color", []);
        }
    }

    // Dashboard Widgets
    if (!Vtiger_Utils::CheckTable('vtiger_dashboard_tabs')) {
        Vtiger_Utils::CreateTable(
            'vtiger_dashboard_tabs',
            '(id int(19) primary key auto_increment,
				tabname VARCHAR(50),
				isdefault INT(1) DEFAULT 0,
				sequence INT(5) DEFAULT 2,
				appname VARCHAR(20),
				modulename VARCHAR(50),
				userid int(11),
				UNIQUE KEY(tabname,userid),
				FOREIGN KEY (userid) REFERENCES vtiger_users(id) ON DELETE CASCADE)',
            true,
        );
    }

    $users = Users_Record_Model::getAll();
    $userIds = array_keys($users);
    $defaultTabQuery = 'INSERT INTO vtiger_dashboard_tabs(tabname,userid) VALUES(?,?) ON DUPLICATE KEY UPDATE tabname=?, userid=?';
    foreach ($userIds as $userId) {
        $db->pquery($defaultTabQuery, ['Default', $userId, 'Default', $userId]);
    }

    $columns = $db->getColumnNames('vtiger_module_dashboard_widgets');
    if (!in_array('reportid', $columns)) {
        $db->pquery('ALTER TABLE vtiger_module_dashboard_widgets ADD COLUMN reportid INT(19) DEFAULT NULL', []);
    }
    if (!in_array('dashboardtabid', $columns)) {
        $result = $db->pquery('SELECT id FROM vtiger_dashboard_tabs WHERE userid=? AND tabname=?', [1, 'Default']);
        $defaultTabid = $db->query_result($result, 0, 'id');
        // Setting admin user default tabid to DEFAULT
        $db->pquery('ALTER TABLE vtiger_module_dashboard_widgets ADD COLUMN dashboardtabid INT(11)', []);

        // TODO : this will fail if there are any entries to vtiger_module_dashboard_widgets
        $db->pquery('ALTER TABLE vtiger_module_dashboard_widgets ADD CONSTRAINT FOREIGN KEY (dashboardtabid) REFERENCES vtiger_dashboard_tabs(id) ON DELETE CASCADE', []);
    }
    // End

    $result = $db->pquery('SELECT * FROM vtiger_module_dashboard_widgets', []);
    $num_rows = $db->num_rows($result);
    for ($i = 0; $i < $num_rows; ++$i) {
        $rowdata = $db->query_result_rowdata($result, $i);
        $result1 = $db->pquery('SELECT id FROM vtiger_dashboard_tabs WHERE userid=? AND tabname IN (?, ?)', [$rowdata['userid'], 'My Dashboard', 'Default']);
        if ($db->num_rows($result1) > 0) {
            $tabid = $db->query_result($result1, 0, 'id');
            $db->pquery('UPDATE vtiger_module_dashboard_widgets SET dashboardtabid=? WHERE id=? AND userid=?', [$tabid, $rowdata['id'], $rowdata['userid']]);
        }
    }

    // Adding color column for vtiger_salutationtype.
    $fieldResult = $db->pquery('SELECT fieldname FROM vtiger_field WHERE fieldname=? AND tabid NOT IN (?)', ['salutationtype', getTabid('Users')]);
    $fieldRows = $db->num_rows($fieldResult);

    for ($i = 0; $i < $fieldRows; ++$i) {
        $fieldName = $db->query_result($fieldResult, $i, 'fieldname');
        if (!Vtiger_Utils::CheckTable("vtiger_{$fieldName}")) {
            continue;
        }

        // Add column in vtiger_tab which will hold source
        $columns = $db->getColumnNames("vtiger_{$fieldName}");
        if (!in_array('color', $columns)) {
            $db->pquery("ALTER TABLE vtiger_{$fieldName} ADD COLUMN color VARCHAR(10)", []);
        }
    }

    // Adding Agenda view in default my calendar view settings
    $usersModuleModel = Vtiger_Module_Model::getInstance('Users');
    $activityViewFieldModel = Vtiger_Field_Model::getInstance('activity_view', $usersModuleModel);

    $existingActivityViewTypes = $activityViewFieldModel->getPicklistValues();
    $newActivityView = 'Agenda';
    if (!in_array($newActivityView, $existingActivityViewTypes)) {
        $activityViewFieldModel->setPicklistValues([$newActivityView]);
    }

    // deleting orphan picklist fields that were delete from vtiger_field table but not from vtiger_role2picklist table
    $deletedPicklistResult = $db->pquery('SELECT DISTINCT(picklistid) AS picklistid FROM vtiger_role2picklist 
								WHERE picklistid NOT IN (SELECT vtiger_picklist.picklistid FROM vtiger_picklist
										INNER JOIN vtiger_role2picklist ON vtiger_role2picklist.picklistid = vtiger_picklist.picklistid)', []);
    $rows = $db->num_rows($deletedPicklistResult);
    $deletablePicklists = [];
    for ($i = 0; $i < $rows; ++$i) {
        $deletablePicklists[] = $db->query_result($deletedPicklistResult, $i, 'picklistid');
    }
    if (php7_count($deletablePicklists)) {
        $db->pquery('DELETE FROM vtiger_role2picklist WHERE picklistid IN (' . generateQuestionMarks($deletablePicklists) . ')', [$deletablePicklists]);
    }

    // table name exceeds more than 50 characters.
    $db->pquery('ALTER TABLE vtiger_field MODIFY COLUMN tablename VARCHAR(100)', []);

    if (!Vtiger_Utils::CheckTable('vtiger_report_shareusers')) {
        Vtiger_Utils::CreateTable(
            'vtiger_report_shareusers',
            '(`reportid` int(25) NOT NULL,
				`userid` int(25) NOT NULL,
				KEY `vtiger_report_shareusers_ibfk_1` (`reportid`),
				CONSTRAINT `vtiger_reports_reportid_ibfk_1` FOREIGN KEY (`reportid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE,
				CONSTRAINT `vtiger_users_userid_ibfk_1` FOREIGN KEY (`userid`) REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE)',
            true,
        );
    }

    if (!Vtiger_Utils::CheckTable('vtiger_report_sharegroups')) {
        Vtiger_Utils::CreateTable(
            'vtiger_report_sharegroups',
            '(`reportid` int(25) NOT NULL,
				`groupid` int(25) NOT NULL,
				KEY `vtiger_report_sharegroups_ibfk_1` (`reportid`),
				CONSTRAINT `vtiger_report_reportid_ibfk_2` FOREIGN KEY (`reportid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE,
				CONSTRAINT `vtiger_groups_groupid_ibfk_1` FOREIGN KEY (`groupid`) REFERENCES `vtiger_groups` (`groupid`) ON DELETE CASCADE)',
            true,
        );
    }

    if (!Vtiger_Utils::CheckTable('vtiger_report_sharerole')) {
        Vtiger_Utils::CreateTable(
            'vtiger_report_sharerole',
            '(`reportid` int(25) NOT NULL,
				`roleid` varchar(255) NOT NULL,
				KEY `vtiger_report_sharerole_ibfk_1` (`reportid`),
				CONSTRAINT `vtiger_report_reportid_ibfk_3` FOREIGN KEY (`reportid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE,
				CONSTRAINT `vtiger_role_roleid_ibfk_1` FOREIGN KEY (`roleid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE)',
            true,
        );
    }

    if (!Vtiger_Utils::CheckTable('vtiger_report_sharers')) {
        Vtiger_Utils::CreateTable(
            'vtiger_report_sharers',
            '(`reportid` int(25) NOT NULL,
				`rsid` varchar(255) NOT NULL,
				KEY `vtiger_report_sharers_ibfk_1` (`reportid`),
				CONSTRAINT `vtiger_report_reportid_ibfk_4` FOREIGN KEY (`reportid`) REFERENCES `vtiger_report` (`reportid`) ON DELETE CASCADE,
				CONSTRAINT `vtiger_rolesd_rsid_ibfk_1` FOREIGN KEY (`rsid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE)',
            true,
        );
    }

    // Migrating existing relations to N:N or 1:N based on relation fieldid
    $query = "UPDATE vtiger_relatedlists SET relationtype='N:N' WHERE relationfieldid IS NULL";
    $result = $db->pquery($query, []);

    $query = "UPDATE vtiger_relatedlists SET relationtype='1:N' WHERE relationfieldid IS NOT NULL";
    $result = $db->pquery($query, []);

    // For Google Synchronization
    Vtiger_Link::addLink(getTabid('Contacts'), 'EXTENSIONLINK', 'Google', 'index.php?module=Contacts&view=Extension&extensionModule=Google&extensionView=Index');
    Vtiger_Link::addLink(getTabid('Calendar'), 'EXTENSIONLINK', 'Google', 'index.php?module=Calendar&view=Extension&extensionModule=Google&extensionView=Index');

    // Add enabled column in vtiger_google_sync_settings
    $colums = $db->getColumnNames('vtiger_google_sync_settings');
    if (!in_array('enabled', $colums)) {
        $query = 'ALTER TABLE vtiger_google_sync_settings ADD COLUMN enabled TINYINT(3) DEFAULT 1';
        $db->pquery($query, []);
    }

    $result = $db->pquery('UPDATE vtiger_tab SET parent=NULL WHERE name=?', ['ExtensionStore']);

    // Start: Tax Enhancements - Compound Taxes, Regional Taxes, Deducted Taxes, Other Charges
    // Creating regions table
    if (!Vtiger_Utils::checkTable('vtiger_taxregions')) {
        $db->pquery('CREATE TABLE vtiger_taxregions(regionid INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL)', []);
    }

    if (!Vtiger_Utils::checkTable('vtiger_inventorycharges')) {
        // Creating inventory charges table
        $sql = 'CREATE TABLE vtiger_inventorycharges(
					chargeid INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100) NOT NULL,
					format VARCHAR(10),
					type VARCHAR(10),
					value DECIMAL(12,5),
					regions TEXT,
					istaxable INT(1) NOT NULL DEFAULT 1,
					taxes VARCHAR(1024),
					deleted INT(1) NOT NULL DEFAULT 0
				)';
        $db->pquery($sql, []);

        $taxIdsList = [];
        $result = $db->pquery('SELECT taxid FROM vtiger_shippingtaxinfo', []);

        while ($rowData = $db->fetch_array($result)) {
            $taxIdsList[] = $rowData['taxid'];
        }

        $db->pquery('INSERT INTO vtiger_inventorycharges VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)', [1, 'Shipping & Handling', 'Flat', 'Fixed', 0, '[]', 1, ZEND_JSON::encode($taxIdsList), 0]);
    }

    if (!Vtiger_Utils::checkTable('vtiger_inventorychargesrel')) {
        // Creating inventory charges relation table
        $db->pquery('CREATE TABLE vtiger_inventorychargesrel(recordid INT(19) NOT NULL, charges TEXT)', []);

        $shippingTaxNamesList = [];
        $result = $db->pquery('SELECT taxid, taxname FROM vtiger_shippingtaxinfo', []);

        while ($rowData = $db->fetch_array($result)) {
            $shippingTaxNamesList[$rowData['taxid']] = $rowData['taxname'];
        }

        $tablesList = ['quoteid' => 'vtiger_quotes', 'purchaseorderid' => 'vtiger_purchaseorder', 'salesorderid' => 'vtiger_salesorder', 'invoiceid' => 'vtiger_invoice'];
        $isResultExists = false;

        $query = 'INSERT INTO vtiger_inventorychargesrel VALUES';
        foreach ($tablesList as $index => $tableName) {
            $sql = "SELECT vtiger_inventoryshippingrel.*, s_h_amount FROM vtiger_inventoryshippingrel
			INNER JOIN {$tableName} ON {$tableName}.{$index} = vtiger_inventoryshippingrel.id";

            $result = $db->pquery($sql, []);

            while ($rowData = $db->fetch_array($result)) {
                $isResultExists = true;
                $recordId = $rowData['id'];

                $taxesList = [];
                foreach ($shippingTaxNamesList as $taxId => $taxName) {
                    $taxesList[$taxId] = $rowData[$taxName];
                }

                $query .= "({$recordId}, '" . Zend_Json::encode([1 => ['value' => $rowData['s_h_amount'], 'taxes' => $taxesList]]) . "'), ";
            }
        }
        if ($isResultExists) {
            $db->pquery(rtrim($query, ', '), []);
        }
    }

    // Updating existing tax tables
    $taxTablesList = ['vtiger_inventorytaxinfo', 'vtiger_shippingtaxinfo'];
    foreach ($taxTablesList as $taxTable) {
        $columns = $db->getColumnNames($taxTable);
        if (!in_array('method', $columns)) {
            $db->pquery("ALTER TABLE {$taxTable} ADD COLUMN method VARCHAR(10)", []);
        }
        if (!in_array('type', $columns)) {
            $db->pquery("ALTER TABLE {$taxTable} ADD COLUMN type VARCHAR(10)", []);
        }
        if (!in_array('compoundon', $columns)) {
            $db->pquery("ALTER TABLE {$taxTable} ADD COLUMN compoundon VARCHAR(400)", []);
        }
        if (!in_array('regions', $columns)) {
            $db->pquery("ALTER TABLE {$taxTable} ADD COLUMN regions TEXT", []);
        }

        $db->pquery("UPDATE {$taxTable} SET method =?, type=?, compoundon=?, regions=?", ['Simple', 'Fixed', '[]', '[]']);
    }

    // Updating existing tax tables
    $columns = $db->getColumnNames('vtiger_producttaxrel');
    if (!in_array('regions', $columns)) {
        $db->pquery('ALTER TABLE vtiger_producttaxrel ADD COLUMN regions TEXT', []);
    }
    $db->pquery('UPDATE vtiger_producttaxrel SET regions=?', ['[]']);

    $modulesList = ['Quotes' => 'vtiger_quotes', 'PurchaseOrder' => 'vtiger_purchaseorder', 'SalesOrder' => 'vtiger_salesorder', 'Invoice' => 'vtiger_invoice'];
    $fieldName = 'region_id';

    foreach ($modulesList as $moduleName => $tableName) {
        // Updating existing inventory tax tables
        $columns = $db->getColumnNames($tableName);
        if (!in_array('compound_taxes_info', $columns)) {
            $db->pquery("ALTER TABLE {$tableName} ADD COLUMN compound_taxes_info TEXT", []);
        }
        $db->pquery('UPDATE ' . $tableName . ' SET compound_taxes_info=?', ['[]']);

        // creating new field in entity tables
        $moduleInstance = Vtiger_Module::getInstance($moduleName);
        $blockInstance = Vtiger_Block::getInstance('LBL_ITEM_DETAILS', $moduleInstance);

        $fieldInstance = Vtiger_Field::getInstance($fieldName, $moduleInstance);
        if (!$fieldInstance) {
            $fieldInstance = new Vtiger_Field();

            $fieldInstance->name = $fieldName;
            $fieldInstance->column		= $fieldName;
            $fieldInstance->table		= $tableName;
            $fieldInstance->label		= 'Tax Region';
            $fieldInstance->columntype	= 'int(19)';
            $fieldInstance->typeofdata	= 'N~O';
            $fieldInstance->uitype		= '16';
            $fieldInstance->readonly	= '0';
            $fieldInstance->displaytype	= '5';
            $fieldInstance->masseditable = '0';

            $blockInstance->addField($fieldInstance);
        }
    }
    // End: Tax Enhancements - Compound Taxes, Regional Taxes, Deducted Taxes, Other Charges

    if (!Vtiger_Utils::CheckTable('vtiger_app2tab')) {
        Vtiger_Utils::CreateTable('vtiger_app2tab', "(
			`tabid` INT(11) DEFAULT NULL,
			`appname` VARCHAR(20) DEFAULT NULL,
			`sequence` INT(11) DEFAULT NULL,
			`visible` TINYINT(3) DEFAULT '1',
			CONSTRAINT `vtiger_app2tab_fk_tab` FOREIGN KEY (`tabid`) REFERENCES `vtiger_tab` (`tabid`) ON DELETE CASCADE
			)", true);
    }

    $restrictedModules = ['ModComments'];
    $appsList = ['SALES'		=> ['Potentials', 'Quotes', 'Contacts', 'Accounts'],
        'PROJECT'	=> ['Project', 'ProjectTask', 'ProjectMilestone', 'Contacts', 'Accounts']];

    $menuModelsList = Vtiger_Module_Model::getEntityModules();
    $menuStructure = Vtiger_MenuStructure_Model::getInstanceFromMenuList($menuModelsList);
    $menuGroupedByParent = $menuStructure->getMenuGroupedByParent();
    $menuGroupedByParent = $menuStructure->regroupMenuByParent($menuGroupedByParent);
    foreach ($menuGroupedByParent as $app => $appModules) {
        $modules = [];
        if (isset($appsList[$app]) && $appsList[$app]) {
            $modules = $appsList[$app];
        }
        foreach ($appModules as $moduleName => $moduleModel) {
            if (!in_array($moduleName, $modules)) {
                $modules[] = $moduleName;
            }
        }
        foreach ($modules as $moduleName) {
            if (!in_array($moduleName, $restrictedModules)) {
                Settings_MenuEditor_Module_Model::addModuleToApp($moduleName, $app);
            }
        }
    }

    $tabIdResult = $db->pquery('SELECT tabid FROM vtiger_app2tab WHERE appname=? AND tabid=?', ['SALES', getTabid('SMSNotifier')]);
    $existingTabId = $db->query_result($tabIdResult, 0, 'tabid');
    if (!$existingTabId) {
        $seqResult = $db->pquery('SELECT max(sequence) as sequence FROM vtiger_app2tab WHERE appname=?', ['SALES']);
        $sequence = $db->query_result($seqResult, 0, 'sequence');
        $db->pquery('INSERT INTO vtiger_app2tab(tabid,appname,sequence,visible) values(?,?,?,?)', [getTabid('SMSNotifier'), 'SALES', $sequence + 11, 1]);
    }

    $tabIdResult = $db->pquery('SELECT tabid FROM vtiger_app2tab WHERE appname=? AND tabid=?', ['SUPPORT', getTabid('SMSNotifier')]);
    $existingTabId = $db->query_result($tabIdResult, 0, 'tabid');
    if (!$existingTabId) {
        $seqResult = $db->pquery('SELECT max(sequence) as sequence FROM vtiger_app2tab WHERE appname=?', ['SUPPORT']);
        $sequence = $db->query_result($seqResult, 0, 'sequence');
        $db->pquery('INSERT INTO vtiger_app2tab(tabid,appname,sequence,visible) values(?,?,?,?)', [getTabid('SMSNotifier'), 'SUPPORT', $sequence + 11, 1]);
    }

    $result = $db->pquery('SELECT tabid,name FROM vtiger_tab', []);
    $moduleTabIds = [];

    while ($row = $db->fetchByAssoc($result)) {
        $moduleName = $row['name'];
        $moduleTabIds[$moduleName] = $row['tabid'];
    }

    $defSequenceList = [
        'MARKETING'	=> [$moduleTabIds['Campaigns'],
            $moduleTabIds['Leads'],
            $moduleTabIds['Contacts'],
            $moduleTabIds['Accounts'],
        ],
        'SALES'		=> [$moduleTabIds['Potentials'],
            $moduleTabIds['Quotes'],
            $moduleTabIds['Invoice'],
            $moduleTabIds['Products'],
            $moduleTabIds['Services'],
            $moduleTabIds['SMSNotifier'],
            $moduleTabIds['Contacts'],
            $moduleTabIds['Accounts'],
        ],
        'SUPPORT'	=> [$moduleTabIds['Faq'],
            $moduleTabIds['ServiceContracts'],
            $moduleTabIds['Assets'],
            $moduleTabIds['SMSNotifier'],
            $moduleTabIds['Contacts'],
            $moduleTabIds['Accounts'],
        ],
        'INVENTORY'	=> [$moduleTabIds['Products'],
            $moduleTabIds['Services'],
            $moduleTabIds['PriceBooks'],
            $moduleTabIds['Invoice'],
            $moduleTabIds['SalesOrder'],
            $moduleTabIds['PurchaseOrder'],
            $moduleTabIds['Vendors'],
            $moduleTabIds['Contacts'],
            $moduleTabIds['Accounts'],
        ],
        'PROJECT'	=> [$moduleTabIds['Project'],
            $moduleTabIds['ProjectTask'],
            $moduleTabIds['ProjectMilestone'],
            $moduleTabIds['Contacts'],
            $moduleTabIds['Accounts'],
        ],
    ];

    foreach ($defSequenceList as $app => $sequence) {
        foreach ($sequence as $seq => $moduleTabId) {
            $params = [$moduleTabId, $app, $seq + 1];
            $db->pquery('UPDATE vtiger_app2tab SET sequence=? WHERE appname =? AND tabid=?', $params);
        }
    }

    $leadsModuleInstance = Vtiger_Module::getInstance('Leads');
    $quotesModuleInstance = Vtiger_Module::getInstance('Quotes');
    $leadsModuleInstance->unsetRelatedList($quotesModuleInstance, 'Quotes', 'get_quotes');

    $leadsTabId = getTabid('Leads');
    $quotesTabId = getTabid('Quotes');
    $query = 'SELECT 1 FROM vtiger_relatedlists WHERE tabid=? AND related_tabid =? AND name=? AND label=?';
    $params = [$leadsTabId, $quotesTabId, 'get_quotes', 'Quotes'];
    $result = $db->pquery($query, $params);
    if ($db->num_rows($result)) {
        $menuEditorModuleModel = new Settings_MenuEditor_Module_Model();
        $menuEditorModuleModel->addModuleToApp('Quotes', 'MARKETING');
    }

    $db->pquery('ALTER TABLE vtiger_cvstdfilter DROP PRIMARY KEY', []);
    if (Vtiger_Utils::TableHasForeignKey('vtiger_cvstdfilter', 'cvstdfilter_cvid_idx')) {
        $db->pquery('ALTER TABLE vtiger_cvstdfilter DROP FOREIGN KEY cvstdfilter_cvid_idx', []);
    }

    if (Vtiger_Utils::TableHasForeignKey('vtiger_cvstdfilter', 'fk_1_vtiger_cvstdfilter')) {
        $db->pquery('ALTER TABLE vtiger_cvstdfilter DROP FOREIGN KEY fk_1_vtiger_cvstdfilter', []);
    }
    $db->pquery('ALTER TABLE vtiger_cvstdfilter ADD CONSTRAINT fk_1_vtiger_cvstdfilter FOREIGN KEY (cvid) REFERENCES vtiger_customview(cvid) ON DELETE CASCADE', []);

    if (!Vtiger_Utils::TableHasForeignKey('vtiger_app2tab', 'vtiger_app2tab_fk_tab')) {
        $db->pquery('ALTER TABLE vtiger_app2tab ADD CONSTRAINT vtiger_app2tab_fk_tab FOREIGN KEY(tabid) REFERENCES vtiger_tab(tabid) ON DELETE CASCADE', []);
    }

    if (!Vtiger_Utils::CheckTable('vtiger_convertpotentialmapping')) {
        Vtiger_Utils::CreateTable(
            'vtiger_convertpotentialmapping',
            "(`cfmid` int(19) NOT NULL AUTO_INCREMENT,
				`potentialfid` int(19) NOT NULL,
				`projectfid` int(19) DEFAULT NULL,
				`editable` int(11) DEFAULT '1',
				PRIMARY KEY (`cfmid`)
				)",
            true,
        );
        $fieldMap = [
            ['potentialname', 'projectname', 0],
            ['description', 'description', 1],
        ];

        $potentialTab = getTabid('Potentials');
        $projectTab = getTabid('Project');
        $mapSql = 'INSERT INTO vtiger_convertpotentialmapping(potentialfid, projectfid, editable) values(?,?,?)';

        foreach ($fieldMap as $values) {
            $potentialfid = getFieldid($potentialTab, $values[0]);
            $projectfid = getFieldid($projectTab, $values[1]);
            $editable = $values[4] ?? 1;
            $db->pquery($mapSql, [$potentialfid, $projectfid, $editable]);
        }
    }

    $columns = $db->getColumnNames('vtiger_potential');
    if (!in_array('converted', $columns)) {
        $db->pquery('ALTER TABLE vtiger_potential ADD converted INT(1) NOT NULL DEFAULT 0', []);
    }

    $Vtiger_Utils_Log = true;
    $moduleArray = ['Project' => 'LBL_PROJECT_INFORMATION'];
    foreach ($moduleArray as $module => $block) {
        $moduleInstance = Vtiger_Module::getInstance($module);
        $blockInstance = Vtiger_Block::getInstance($block, $moduleInstance);

        $field = Vtiger_Field::getInstance('isconvertedfrompotential', $moduleInstance);
        if (!$field) {
            $field = new Vtiger_Field();
            $field->name		= 'isconvertedfrompotential';
            $field->label		= 'Is Converted From Opportunity';
            $field->uitype		= 56;
            $field->column		= 'isconvertedfrompotential';
            $field->displaytype	= 2;
            $field->columntype	= 'INT(1) NOT NULL DEFAULT 0';
            $field->typeofdata	= 'C~O';
            $blockInstance->addField($field);
        }
    }

    $projectInstance = Vtiger_Module::getInstance('Project');
    $calendarModule = Vtiger_Module::getInstance('Calendar');
    $projectInstance->setRelatedList($calendarModule, 'Activities', ['ADD']);

    $quotesModule = Vtiger_Module::getInstance('Quotes');
    $projectInstance->setRelatedList($quotesModule, 'Quotes', ['SELECT']);

    if (!Vtiger_Field::getInstance('potentialid', $projectInstance)) {
        $blockInstance = Vtiger_Block_Model::getInstance('LBL_PROJECT_INFORMATION', $projectInstance);
        $potentialField = new Vtiger_Field();
        $potentialField->name		= 'potentialid';
        $potentialField->label		= 'Potential Name';
        $potentialField->uitype		= 10;
        $potentialField->typeofdata	= 'I~O';

        $blockInstance->addField($potentialField);
        $potentialField->setRelatedModules(['Potentials']);
    }

    $productsInstance = Vtiger_Module_Model::getInstance('Products');
    $poInstance = Vtiger_Module_Model::getInstance('PurchaseOrder');
    $productsInstance->setRelatedList($poInstance, 'PurchaseOrder', false, 'get_purchase_orders');

    $modules = ['Potentials', 'Contacts', 'Accounts', 'Project'];
    foreach ($modules as $moduleName) {
        $tabId = getTabid($moduleName);
        if ($moduleName == 'Project') {
            $db->pquery('UPDATE vtiger_field SET displaytype=? WHERE fieldname=? AND tabid=?', [1, 'isconvertedfrompotential', $tabId]);
        } else {
            $db->pquery('UPDATE vtiger_field SET displaytype=? WHERE fieldname=? AND tabid=?', [1, 'isconvertedfromlead', $tabId]);
        }
        Vtiger_Cache::flushModuleCache($moduleName);
    }

    $db->pquery('DELETE FROM vtiger_links WHERE linktype=? AND handler_class=?', ['DETAILVIEWBASIC', 'Documents']);

    $columns = $db->getColumnNames('vtiger_mailmanager_mailrecord');
    if (!in_array('mfolder', $columns)) {
        $db->pquery('ALTER TABLE vtiger_mailmanager_mailrecord ADD COLUMN mfolder VARCHAR(250)', []);
        $duplicateResult = $db->pquery('SELECT muid FROM vtiger_mailmanager_mailrecord GROUP BY muid HAVING COUNT(muid) > ?', ['1']);
        $noOfDuplicate = $db->num_rows($duplicateResult);
        if ($noOfDuplicate) {
            $duplicateMuid = [];
            for ($i = 0; $i < $noOfDuplicate; ++$i) {
                $duplicateMuid[] = $db->query_result($duplicateResult, $i, 'muid');
            }
            $db->pquery('DELETE FROM vtiger_mailmanager_mailrecord WHERE muid IN (' . generateQuestionMarks($duplicateMuid) . ')', $duplicateMuid);
            $db->pquery('DELETE FROM vtiger_mailmanager_mailattachments WHERE muid IN (' . generateQuestionMarks($duplicateMuid) . ')', $duplicateMuid);
        }
    }

    $columns = $db->getColumnNames('vtiger_mailscanner');
    if (!in_array('scanfrom', $columns)) {
        $db->pquery('ALTER TABLE vtiger_mailscanner ADD COLUMN scanfrom VARCHAR(10) DEFAULT "ALL"', []);
    }

    if (Vtiger_Utils::CheckTable('vtiger_mailscanner_ids')) {
        $columns = $db->getColumnNames('vtiger_mailscanner_ids');
        if (!in_array('refids', $columns)) {
            $db->pquery('ALTER TABLE vtiger_mailscanner_ids ADD COLUMN refids MEDIUMTEXT', []);
        }
        $db->pquery('ALTER TABLE vtiger_mailscanner_ids ADD INDEX messageids_crmid_idx(crmid)', []);
    }

    $result = $db->pquery('SELECT templateid FROM vtiger_emailtemplates ORDER BY templateid DESC LIMIT 1', []);
    $db->pquery('UPDATE vtiger_emailtemplates_seq SET id=?', [$db->query_result($result, 0, 'templateid')]);

    // Migrating data missed in vtiger_settings_field from file to database.
    // Start:: user management block
    $userResult = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label=?', ['LBL_USER_MANAGEMENT']);
    if ($db->num_rows($userResult)) {
        $userManagementBlockId = $db->query_result($userResult, 0, 'blockid');
        $db->pquery('UPDATE vtiger_settings_blocks SET sequence=? WHERE blockid=?', [1, $userManagementBlockId]);
    } else {
        $userManagementBlockId = $db->getUniqueID('vtiger_settings_blocks');
        $db->pquery('INSERT INTO vtiger_settings_blocks(blockid, label, sequence) VALUES(?, ?, ?)', [$userManagementBlockId, 'LBL_USER_MANAGEMENT', 1]);
    }

    $userManagementFields = ['LBL_USERS'					=> 'index.php?module=Users&parent=Settings&view=List',
        'LBL_ROLES'					=> 'index.php?module=Roles&parent=Settings&view=Index',
        'LBL_PROFILES'				=> 'index.php?module=Profiles&parent=Settings&view=List',
        'LBL_SHARING_ACCESS'		=> 'index.php?module=SharingAccess&parent=Settings&view=Index',
        'USERGROUPLIST'				=> 'index.php?module=Groups&parent=Settings&view=List',
        'LBL_LOGIN_HISTORY_DETAILS'	=> 'index.php?module=LoginHistory&parent=Settings&view=List'];

    $userManagementSequence = 1;
    foreach ($userManagementFields as $fieldName => $linkTo) {
        $db->pquery('UPDATE vtiger_settings_field SET sequence=?, linkto=? WHERE name=? AND blockid=?', [$userManagementSequence++, $linkTo, $fieldName, $userManagementBlockId]);
    }
    // End:: user management block

    // Start:: module manager block
    $moduleManagerResult = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label=?', ['LBL_MODULE_MANAGER']);
    if ($db->num_rows($moduleManagerResult)) {
        $moduleManagerBlockId = $db->query_result($moduleManagerResult, 0, 'blockid');
        $db->pquery('UPDATE vtiger_settings_blocks SET sequence=? WHERE blockid=?', [2, $moduleManagerBlockId]);
    } else {
        $moduleManagerBlockId = $db->getUniqueID('vtiger_settings_blocks');
        $db->pquery('INSERT INTO vtiger_settings_blocks(blockid, label, sequence) VALUES(?, ?, ?)', [$moduleManagerBlockId, 'LBL_MODULE_MANAGER', 2]);
    }

    $moduleManagerFields = ['VTLIB_LBL_MODULE_MANAGER'		=> 'index.php?module=ModuleManager&parent=Settings&view=List',
        'LBL_EDIT_FIELDS'				=> 'index.php?module=LayoutEditor&parent=Settings&view=Index',
        'Labels Editor'					=> 'index.php?module=LanguageEditor&view=List',
        'LBL_CUSTOMIZE_MODENT_NUMBER'	=> 'index.php?module=Vtiger&parent=Settings&view=CustomRecordNumbering'];
    $moduleManagerSequence = 1;
    foreach ($moduleManagerFields as $fieldName => $linkTo) {
        $db->pquery('UPDATE vtiger_settings_field SET sequence=?, linkto=?, blockid=? WHERE name=?', [$moduleManagerSequence++, $linkTo, $moduleManagerBlockId, $fieldName]);
    }
    // End:: module manager block

    // Start:: automation block
    $automationResult = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label=?', ['LBL_AUTOMATION']);
    if ($db->num_rows($automationResult)) {
        $automationBlockId = $db->query_result($automationResult, 0, 'blockid');
        $db->pquery('UPDATE vtiger_settings_blocks SET sequence=? WHERE blockid=?', [3, $automationBlockId]);
    } else {
        $automationBlockId = $db->getUniqueID('vtiger_settings_blocks');
        $db->pquery('INSERT INTO vtiger_settings_blocks(blockid, label, sequence) VALUES(?, ?, ?)', [$automationBlockId, 'LBL_AUTOMATION', 3]);
    }

    $automationFields = ['Webforms'			=> 'index.php?module=Webforms&parent=Settings&view=List',
        'Scheduler'			=> 'index.php?module=CronTasks&parent=Settings&view=List',
        'LBL_LIST_WORKFLOWS' => 'index.php?module=Workflows&parent=Settings&view=List'];

    $automationSequence = 1;
    foreach ($automationFields as $fieldName => $linkTo) {
        $db->pquery('UPDATE vtiger_settings_field SET sequence=?, linkto=?, blockid=? WHERE name=?', [$automationSequence++, $linkTo, $automationBlockId, $fieldName]);
    }
    // End:: automation block

    // Start:: configuration block
    $configurationResult = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label=?', ['LBL_CONFIGURATION']);
    if ($db->num_rows($configurationResult)) {
        $configurationBlockId = $db->query_result($configurationResult, 0, 'blockid');
        $db->pquery('UPDATE vtiger_settings_blocks SET sequence=? WHERE blockid=?', [4, $configurationBlockId]);
    } else {
        $configurationBlockId = $db->getUniqueID('vtiger_settings_blocks');
        $db->pquery('INSERT INTO vtiger_settings_blocks(blockid, label, sequence) VALUES(?, ?, ?)', [$configurationBlockId, 'LBL_CONFIGURATION', 4]);
    }

    $configurationFields = ['LBL_COMPANY_DETAILS'		=> 'index.php?parent=Settings&module=Vtiger&view=CompanyDetails',
        'LBL_CUSTOMER_PORTAL'		=> 'index.php?module=CustomerPortal&parent=Settings&view=Index',
        'LBL_CURRENCY_SETTINGS'		=> 'index.php?parent=Settings&module=Currency&view=List',
        'LBL_MAIL_SERVER_SETTINGS'	=> 'index.php?parent=Settings&module=Vtiger&view=OutgoingServerDetail',
        'Configuration Editor'		=> 'index.php?module=Vtiger&parent=Settings&view=ConfigEditorDetail',
        'LBL_PICKLIST_EDITOR'		=> 'index.php?parent=Settings&module=Picklist&view=Index',
        'LBL_PICKLIST_DEPENDENCY'	=> 'index.php?parent=Settings&module=PickListDependency&view=List',
        'LBL_MENU_EDITOR'			=> 'index.php?module=MenuEditor&parent=Settings&view=Index'];

    $db->pquery('UPDATE vtiger_settings_field SET name=? WHERE name=?', ['LBL_PICKLIST_DEPENDENCY', 'LBL_PICKLIST_DEPENDENCY_SETUP']);
    $configurationSequence = 1;
    foreach ($configurationFields as $fieldName => $linkTo) {
        $db->pquery('UPDATE vtiger_settings_field SET sequence=?, linkto=?, blockid=? WHERE name=?', [$configurationSequence++, $linkTo, $configurationBlockId, $fieldName]);
    }
    // End:: configuration block

    // Start:: marketing sales block
    $marketingSalesResult = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label=?', ['LBL_MARKETING_SALES']);
    if ($db->num_rows($marketingSalesResult)) {
        $marketingSalesBlockId = $db->query_result($marketingSalesResult, 0, 'blockid');
        $db->pquery('UPDATE vtiger_settings_blocks SET sequence=? WHERE blockid=?', [5, $marketingSalesBlockId]);
    } else {
        $marketingSalesBlockId = $db->getUniqueID('vtiger_settings_blocks');
        $db->pquery('INSERT INTO vtiger_settings_blocks(blockid, label, sequence) VALUES(?, ?, ?)', [$marketingSalesBlockId, 'LBL_MARKETING_SALES', 5]);
    }

    $marketingSalesFields = ['LBL_LEAD_MAPPING'			=> 'index.php?parent=Settings&module=Leads&view=MappingDetail',
        'LBL_OPPORTUNITY_MAPPING'	=> 'index.php?parent=Settings&module=Potentials&view=MappingDetail'];

    $marketingSequence = 1;
    foreach ($marketingSalesFields as $fieldName => $linkTo) {
        $marketingFieldResult = $db->pquery('SELECT 1 FROM vtiger_settings_field WHERE name=?', [$fieldName]);
        if (!$db->num_rows($marketingFieldResult)) {
            $updateQuery = 'INSERT INTO vtiger_settings_field(fieldid,blockid,name,iconpath,description,linkto,sequence,active,pinned) VALUES(?,?,?,?,?,?,?,?,?)';
            $params = [$db->getUniqueID('vtiger_settings_field'), $marketingSalesBlockId, $fieldName, 'NULL', 'NULL', $linkTo, $marketingSequence++, 0, 1];
            $db->pquery($updateQuery, $params);
        }
    }
    // End:: marketing sales block

    // Start:: inventory block
    $inventoryResult = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label=?', ['LBL_INVENTORY']);
    if ($db->num_rows($inventoryResult)) {
        $inventoryBlockId = $db->query_result($inventoryResult, 0, 'blockid');
        $db->pquery('UPDATE vtiger_settings_blocks SET sequence=? WHERE blockid=?', [6, $inventoryBlockId]);
    } else {
        $inventoryBlockId = $db->getUniqueID('vtiger_settings_blocks');
        $db->pquery('INSERT INTO vtiger_settings_blocks(blockid, label, sequence) VALUES(?, ?, ?)', [$inventoryBlockId, 'LBL_INVENTORY', 6]);
    }

    $inventoryFields = ['LBL_TAX_SETTINGS'				=> 'index.php?module=Vtiger&parent=Settings&view=TaxIndex',
        'INVENTORYTERMSANDCONDITIONS'	=> 'index.php?parent=Settings&module=Vtiger&view=TermsAndConditionsEdit'];

    $inventorySequence = 1;
    foreach ($inventoryFields as $fieldName => $linkTo) {
        $db->pquery('UPDATE vtiger_settings_field SET sequence=?, linkto=?, blockid=? WHERE name=?', [$inventorySequence++, $linkTo, $inventoryBlockId, $fieldName]);
    }
    // End:: inventory block

    // Start:: mypreference block
    $myPreferenceResult = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label=?', ['LBL_MY_PREFERENCES']);
    if ($db->num_rows($myPreferenceResult)) {
        $myPreferenceBlockId = $db->query_result($myPreferenceResult, 0, 'blockid');
        $db->pquery('UPDATE vtiger_settings_blocks SET sequence=? WHERE blockid=?', [7, $myPreferenceBlockId]);
    } else {
        $myPreferenceBlockId = $db->getUniqueID('vtiger_settings_blocks');
        $db->pquery('INSERT INTO vtiger_settings_blocks(blockid,label,sequence) VALUES(?,?,?)', [$myPreferenceBlockId, 'LBL_MY_PREFERENCES', 7]);
    }

    $myPreferenceFields = ['My Preferences'	=> 'index.php?module=Users&view=PreferenceDetail&parent=Settings&record=1',
        'Calendar Settings' => 'index.php?module=Users&parent=Settings&view=Calendar&record=1',
        'LBL_MY_TAGS'		=> 'index.php?module=Tags&parent=Settings&view=List&record=1'];

    $myPreferenceSequence = 1;
    foreach ($myPreferenceFields as $fieldName => $linkTo) {
        $myPrefFieldResult = $db->pquery('SELECT 1 FROM vtiger_settings_field WHERE name=?', [$fieldName]);
        if (!$db->num_rows($myPrefFieldResult)) {
            $fieldQuery = 'INSERT INTO vtiger_settings_field(fieldid,blockid,name,iconpath,description,linkto,sequence,active,pinned) VALUES(?,?,?,?,?,?,?,?,?)';
            $params = [$db->getUniqueID('vtiger_settings_field'), $myPreferenceBlockId, $fieldName, 'NULL', 'NULL', $linkTo, $myPreferenceSequence++, 0, 1];
            $db->pquery($fieldQuery, $params);
        }
    }
    // End:: mypreference block

    // Start:: integrations block
    $integrationBlockResult = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label=?', ['LBL_INTEGRATION']);
    if ($db->num_rows($integrationBlockResult)) {
        $integrationBlockId = $db->query_result($integrationBlockResult, 0, 'blockid');
        $db->pquery('UPDATE vtiger_settings_blocks SET sequence=? WHERE blockid=?', [8, $integrationBlockId]);
    } else {
        $integrationBlockId = $db->getUniqueID('vtiger_settings_blocks');
        $db->pquery('INSERT INTO vtiger_settings_blocks(blockid, label, sequence) VALUES(?, ?, ?)', [$integrationBlockId, 'LBL_INTEGRATION', 8]);
    }
    // End:: integrations block

    // Start:: extensions block
    $extensionResult = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label=?', ['LBL_EXTENSIONS']);
    if ($db->num_rows($extensionResult)) {
        $extensionsBlockId = $db->query_result($extensionResult, 0, 'blockid');
        $db->pquery('UPDATE vtiger_settings_blocks SET sequence=? WHERE blockid=?', [9, $extensionsBlockId]);
    } else {
        $extensionsBlockId = $db->getUniqueID('vtiger_settings_blocks');
        $db->pquery('INSERT INTO vtiger_settings_blocks(blockid, label, sequence) VALUES(?, ?, ?)', [$extensionsBlockId, 'LBL_EXTENSIONS', 9]);
    }

    $extensionFields = ['LBL_EXTENSION_STORE'	=> 'index.php?module=ExtensionStore&parent=Settings&view=ExtensionStore',
        'LBL_GOOGLE'			=> 'index.php?module=Contacts&parent=Settings&view=Extension&extensionModule=Google&extensionView=Index&mode=settings'];

    $extSequence = 1;
    foreach ($extensionFields as $fieldName => $linkTo) {
        $extFieldResult = $db->pquery('SELECT 1 FROM vtiger_settings_field WHERE name=?', [$fieldName]);
        if (!$db->num_rows($extFieldResult)) {
            $fieldQuery = 'INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence, active, pinned) VALUES(?,?,?,?,?,?,?,?,?)';
            $params = [$db->getUniqueID('vtiger_settings_field'), $extensionsBlockId, $fieldName, 'NULL', 'NULL', $linkTo, $extSequence++, 0, 1];
            $db->pquery($fieldQuery, $params);
        }
    }
    // End:: extensions block

    // Deleting duplicate entries of blocks and Fields
    $blocksAndNameFields = [$userManagementBlockId => array_keys($userManagementFields),
        $moduleManagerBlockId => array_keys($moduleManagerFields),
        $automationBlockId => array_keys($automationFields),
        $configurationBlockId => array_keys($configurationFields),
        $inventoryBlockId => array_keys($inventoryFields)];

    foreach ($blocksAndNameFields as $blockId => $blockFields) {
        // Delete duplicate entries of block fields in other blocks.
        $db->pquery('DELETE FROM vtiger_settings_field WHERE name IN (' . generateQuestionMarks($blockFields) . ') AND blockid != ?', [$blockFields, $blockId]);

        // Delete non block fields in specific blocks
        $db->pquery('DELETE FROM vtiger_settings_field WHERE name NOT IN (' . generateQuestionMarks($blockFields) . ') AND blockid=?', [$blockFields, $blockId]);
    }

    // Deleting unused blocks from Settings page
    $unusedSettingsBlocks = ['LBL_STUDIO', 'LBL_COMMUNICATION_TEMPLATES'];
    $db->pquery('DELETE FROM vtiger_settings_blocks WHERE label IN (' . generateQuestionMarks($unusedSettingsBlocks) . ')', [$unusedSettingsBlocks]);
    echo 'Deleted unused blocks from settings page';

    // Update other settings block sequence to last
    $db->pquery('UPDATE vtiger_settings_blocks SET sequence=? WHERE label=?', ['10', 'LBL_OTHER_SETTINGS']);
    $otheBlockResult = $db->pquery('SELECT blockid FROM vtiger_settings_blocks WHERE label=?', ['LBL_OTHER_SETTINGS']);
    if ($db->num_rows($otheBlockResult) > 0) {
        $otherBlockId = $db->query_result($otheBlockResult, 0, 'blockid');
    }

    $duplicateOtherBlockFields = ['LBL_ANNOUNCEMENT'];
    $db->pquery('DELETE FROM vtiger_settings_field WHERE name IN (' . generateQuestionMarks($duplicateOtherBlockFields) . ') AND blockid=?', [$duplicateOtherBlockFields, $otherBlockId]);
    // Migration of data to vtiger_settings blocks and fields ends

    $result = $db->pquery('SELECT cvid, entitytype FROM vtiger_customview WHERE viewname=?', ['All']);
    if ($result && $db->num_rows($result) > 0) {
        while ($row = $db->fetch_array($result)) {
            $cvId = $row['cvid'];
            $cvModel = CustomView_Record_Model::getInstanceById($cvId);
            $cvSelectedFields = $cvModel->getSelectedFields();

            $moduleModel = Vtiger_Module_Model::getInstance($row['entitytype']);
            if ($moduleModel) {
                $fields = $moduleModel->getFields();
                $cvSelectedFieldModels = [];

                foreach ($fields as $fieldModel) {
                    $cvSelectedFieldModels[] = decode_html($fieldModel->getCustomViewColumnName());
                }

                foreach ($cvSelectedFields as $cvSelectedField) {
                    if (!in_array($cvSelectedField, $cvSelectedFieldModels)) {
                        $fieldData = explode(':', $cvSelectedField);
                        $fieldName = $fieldData[2];
                        $fieldInstance = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
                        if ($fieldInstance) {
                            $columnname = decode_html($fieldInstance->getCustomViewColumnName());
                            $db->pquery('UPDATE vtiger_cvcolumnlist SET columnname=? WHERE cvid=? AND columnname=?', [$columnname, $cvId, $cvSelectedField]);
                        }
                    }
                }
            }
        }
    }

    $skippedTablesForAll = ['vtiger_crmentity_user_field'];
    $skippedTables = ['Calendar' => ['vtiger_seactivityrel', 'vtiger_cntactivityrel', 'vtiger_salesmanactivityrel']];
    $allEntityModules = Vtiger_Module_Model::getEntityModules();
    $dbName = $db->dbName;
    foreach ($allEntityModules as $tabId => $moduleModel) {
        $moduleName = $moduleModel->getName();
        $baseTableName = $moduleModel->basetable;
        $baseTableIndex = $moduleModel->basetableid;

        if ($baseTableName) {
            // Checking foriegn key with vtiger_crmenity
            $query = 'SELECT 1 FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
							WHERE CONSTRAINT_SCHEMA=? AND CONSTRAINT_NAME LIKE ?
								AND TABLE_NAME=? AND COLUMN_NAME=?
								AND REFERENCED_TABLE_NAME=? AND REFERENCED_COLUMN_NAME=?';
            $checkIfConstraintExists = $db->pquery($query, [$dbName, '%fk%', $baseTableName, $baseTableIndex, 'vtiger_crmentity', 'crmid']);
            if ($db->num_rows($checkIfConstraintExists) < 1) {
                $db->pquery("ALTER TABLE {$baseTableName} ADD CONSTRAINT fk_crmid_{$baseTableName} FOREIGN KEY ({$baseTableIndex}) REFERENCES vtiger_crmentity (crmid) ON DELETE CASCADE", []);
            }

            $focus = CRMEntity::getInstance($moduleName);
            $relatedTables = $focus->tab_name_index;
            unset($relatedTables[$baseTableName], $relatedTables['vtiger_crmentity']);


            if (is_array($relatedTables)) {
                if (isset($skippedTables[$moduleName]) && $skippedTables[$moduleName]) {
                    $relatedTables = array_diff_key($relatedTables, array_flip($skippedTables[$moduleName]));
                }
                if ($skippedTablesForAll) {
                    $relatedTables = array_diff_key($relatedTables, array_flip($skippedTablesForAll));
                }

                // Checking foriegn key with base table
                foreach ($relatedTables as $tableName => $index) {
                    $referenceTable = $baseTableName;
                    $referenceColumn = $baseTableIndex;

                    if ($tableName == 'vtiger_producttaxrel' || $tableName == 'vtiger_inventoryproductrel') {
                        $referenceTable = 'vtiger_crmentity';
                        $referenceColumn = 'crmid';
                    }

                    $checkIfRelConstraintExists = $db->pquery($query, [$dbName, '%fk%', $tableName, $index, $referenceTable, $referenceColumn]);
                    if ($db->num_rows($checkIfRelConstraintExists) < 1) {
                        $newForiegnKey = "fk_{$referenceColumn}_{$tableName}";
                        $db->pquery("ALTER TABLE {$tableName} ADD CONSTRAINT {$newForiegnKey} FOREIGN KEY ({$index}) REFERENCES {$referenceTable} ({$referenceColumn}) ON DELETE CASCADE", []);
                    }
                }
            }
            $deleteQueryParams = [$moduleName];
            if ($baseTableName == 'vtiger_activity') {
                array_push($deleteQueryParams, 'Emails');
            }
            $db->pquery("DELETE FROM {$baseTableName} WHERE {$baseTableIndex} NOT IN (SELECT crmid FROM vtiger_crmentity WHERE setype in (" . generateQuestionMarks($deleteQueryParams) . '))', $deleteQueryParams);
        }
    }

    if (is_dir('modules/Vtiger/resources')) {
        rename('modules/Vtiger/resources', 'modules/Vtiger/resources_650');
    }

    // recalculate user files to finish
    RecalculateSharingRules();

    echo '<br>Successfully updated : <b>Vtiger7</b><br>';

}
