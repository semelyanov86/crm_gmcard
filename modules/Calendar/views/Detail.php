<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Calendar_Detail_View extends Vtiger_Detail_View
{
    public function checkPermission(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $recordId = $request->get('record');

        parent::checkPermission($request);
        if ($recordId) {
            $activityModulesList = ['Calendar', 'Events'];
            $recordEntityName = getSalesEntityType($recordId);

            if (!in_array($recordEntityName, $activityModulesList) || !in_array($moduleName, $activityModulesList)) {
                throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
            }
        }

        return true;
    }

    public function preProcess(Vtiger_Request $request, $display = true)
    {
        parent::preProcess($request, false);

        $recordId = $request->get('record');
        $moduleName = $request->getModule();
        if (!empty($recordId)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId);
            $activityType = $recordModel->getType();
            if ($activityType == 'Events') {
                $moduleName = 'Events';
            }
        }
        $detailViewModel = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
        $recordModel = $detailViewModel->getRecord();
        $recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
        $summaryInfo = [];
        // Take first block information as summary information
        $stucturedValues = $recordStrucure->getStructure();
        foreach ($stucturedValues as $blockLabel => $fieldList) {
            $summaryInfo[$blockLabel] = $fieldList;
            break;
        }

        $detailViewLinkParams = ['MODULE' => $moduleName, 'RECORD' => $recordId];
        $detailViewLinks = $detailViewModel->getDetailViewLinks($detailViewLinkParams);
        $navigationInfo = ListViewSession::getListViewNavigation($recordId);

        $viewer = $this->getViewer($request);
        $viewer->assign('RECORD', $recordModel);
        $viewer->assign('NAVIGATION', $navigationInfo);

        // Intially make the prev and next records as null
        $prevRecordId = null;
        $nextRecordId = null;
        $found = false;
        if ($navigationInfo) {
            foreach ($navigationInfo as $page => $pageInfo) {
                foreach ($pageInfo as $index => $record) {
                    // If record found then next record in the interation
                    // will be next record
                    if ($found) {
                        $nextRecordId = $record;
                        break;
                    }
                    if ($record == $recordId) {
                        $found = true;
                    }
                    // If record not found then we are assiging previousRecordId
                    // assuming next record will get matched
                    if (!$found) {
                        $prevRecordId = $record;
                    }
                }
                // if record is found and next record is not calculated we need to perform iteration
                if ($found && !empty($nextRecordId)) {
                    break;
                }
            }
        }

        $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
        if (!empty($prevRecordId)) {
            $viewer->assign('PREVIOUS_RECORD_URL', $moduleModel->getDetailViewUrl($prevRecordId));
        }
        if (!empty($nextRecordId)) {
            $viewer->assign('NEXT_RECORD_URL', $moduleModel->getDetailViewUrl($nextRecordId));
        }

        $viewer->assign('MODULE_MODEL', $detailViewModel->getModule());
        $viewer->assign('DETAILVIEW_LINKS', $detailViewLinks);

        $viewer->assign('IS_EDITABLE', $detailViewModel->getRecord()->isEditable($moduleName));
        $viewer->assign('IS_DELETABLE', $detailViewModel->getRecord()->isDeletable($moduleName));

        $linkParams = ['MODULE' => $moduleName, 'ACTION' => $request->get('view')];
        $linkModels = $detailViewModel->getSideBarLinks($linkParams);

        $viewer->assign('QUICK_LINKS', $linkModels);
        $viewer->assign('NO_SUMMARY', true);
        $viewer->assign('MODULE_NAME', $moduleName);
        if ($display) {
            $this->preProcessDisplay($request);
        }
    }

    /**
     * Function shows the entire detail for the record.
     * @return <type>
     */
    public function showModuleDetailView(Vtiger_Request $request)
    {
        $recordId = $request->get('record');
        $moduleName = $request->getModule();

        if (!empty($recordId)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId);
            $activityType = $recordModel->getType();
            if ($activityType == 'Events') {
                $moduleName = 'Events';
            }
        }

        $detailViewModel = Vtiger_DetailView_Model::getInstance($moduleName, $recordId);
        $recordModel = $detailViewModel->getRecord();
        $recordStrucure = Vtiger_RecordStructure_Model::getInstanceFromRecordModel($recordModel, Vtiger_RecordStructure_Model::RECORD_STRUCTURE_MODE_DETAIL);
        $structuredValues = $recordStrucure->getStructure();
        $moduleModel = $recordModel->getModule();

        if ($moduleName == 'Events') {
            $relatedContacts = $recordModel->getRelatedContactInfo();
            foreach ($relatedContacts as $index => $contactInfo) {
                $contactRecordModel = Vtiger_Record_Model::getCleanInstance('Contacts');
                $contactRecordModel->setId($contactInfo['id']);
                $contactInfo['_model'] = $contactRecordModel;
                $relatedContacts[$index] = $contactInfo;
            }
        } else {
            $relatedContacts = [];
        }

        $viewer = $this->getViewer($request);
        $viewer->assign('RECORD', $recordModel);
        $viewer->assign('RECORD_STRUCTURE', $structuredValues);
        $viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
        $viewer->assign('RECORD_STRUCTURE_MODEL', $recordStrucure);
        $viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());
        $viewer->assign('MODULE_NAME', $moduleName);
        $viewer->assign('DAY_STARTS', '');
        $viewer->assign('RELATED_CONTACTS', $relatedContacts);
        $viewer->assign('IS_AJAX_ENABLED', $this->isAjaxEnabled($recordModel));
        $viewer->assign('RECURRING_INFORMATION', $recordModel->getRecurringDetails());

        $appName = !empty($request->get('app')) ? $request->get('app') : '';
        $viewer->assign('SELECTED_MENU_CATEGORY', $appName);
        $picklistDependencyDatasource = Vtiger_DependencyPicklist::getPicklistDependencyDatasource($moduleName);
        $viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', Vtiger_Functions::jsonEncode($picklistDependencyDatasource));

        if ($moduleName == 'Events') {
            $currentUser = Users_Record_Model::getCurrentUserModel();
            $accessibleUsers = $currentUser->getAccessibleUsers();
            $viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);
            $viewer->assign('INVITIES_SELECTED', $recordModel->getInvities());
            $viewer->assign('INVITEES_DETAILS', $recordModel->getInviteesDetails());
        }

        if ($request->get('displayMode') == 'overlay') {
            $viewer->assign('MODULE_MODEL', $moduleModel);
            $this->setModuleInfo($request, $moduleModel);
            $viewer->assign('MODULE', $request->getModule());

            return $viewer->view('OverlayDetailView.tpl', $moduleName);
        }

        return $viewer->view('DetailViewFullContents.tpl', $moduleName, true);

    }

    /**
     * Function shows basic detail for the record.
     * @param <type> $request
     */
    public function showModuleBasicView($request)
    {
        return $this->showModuleDetailView($request);
    }

    /**
     * Function to get Ajax is enabled or not.
     * @param Vtiger_Record_Model record model
     * @return <boolean> true/false
     */
    public function isAjaxEnabled($recordModel)
    {
        return false;
    }
}
