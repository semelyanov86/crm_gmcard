<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Users_Save_Action extends Vtiger_Save_Action
{
    public function requiresPermission(Vtiger_Request $request)
    {
        return [];
    }

    public function checkPermission(Vtiger_Request $request)
    {
        $allowed = parent::checkPermission($request);
        if ($allowed) {
            $moduleName = $request->getModule();
            $record = $request->get('record');
            $recordModel = Vtiger_Record_Model::getInstanceById($record, $moduleName);
            $currentUserModel = Users_Record_Model::getCurrentUserModel();
            // Deny access if not administrator or account-owner or self
            if (!$currentUserModel->isAdminUser()) {
                if (empty($record)) {
                    $allowed = false;
                } elseif ($currentUserModel->get('id') != $recordModel->getId()) {
                    $allowed = false;
                }
            }
        }
        if (!$allowed) {
            throw new AppException('LBL_PERMISSION_DENIED');
        }

        return $allowed;
    }

    /**
     * Function to get the record model based on the request parameters.
     * @return Vtiger_Record_Model or Module specific Record Model instance
     */
    public function getRecordModelFromRequest(Vtiger_Request $request)
    {
        $moduleName = $request->getModule();
        $recordId = $request->get('record');
        $currentUserModel = Users_Record_Model::getCurrentUserModel();

        if (!empty($recordId)) {
            $recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
            $modelData = $recordModel->getData();
            $recordModel->set('id', $recordId);
            $sharedType = $request->get('sharedtype');
            if (!empty($sharedType)) {
                $recordModel->set('calendarsharedtype', $request->get('sharedtype'));
            }
            $recordModel->set('mode', 'edit');
        } else {
            $recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
            $modelData = $recordModel->getData();
            $recordModel->set('mode', '');
        }

        foreach ($modelData as $fieldName => $value) {
            $requestFieldExists = $request->has($fieldName);
            if (!$requestFieldExists) {
                continue;
            }
            $fieldValue = $request->get($fieldName, null);
            if ($fieldName === 'is_admin' && (!$currentUserModel->isAdminUser() || !$fieldValue)) {
                $fieldValue = 'off';
            }
            // to not update is_owner from ui
            if ($fieldName == 'is_owner') {
                $fieldValue = null;
            }
            if ($fieldName == 'roleid' && !$currentUserModel->isAdminUser()) {
                $fieldValue = null;
            }
            if ($fieldName == 'signature' && $fieldValue !== null) {
                $purifiedContent = vtlib_purify(decode_html($fieldValue));
                // Purify malicious html event attributes
                $fieldValue = purifyHtmlEventAttributes(decode_html($purifiedContent), true);
            }
            if ($fieldValue !== null) {
                if (!is_array($fieldValue)) {
                    $fieldValue = trim($fieldValue);
                }
                $recordModel->set($fieldName, $fieldValue);
            }
        }
        $homePageComponents = $recordModel->getHomePageComponents();
        $selectedHomePageComponents = $request->get('homepage_components', []);
        foreach ($homePageComponents as $key => $value) {
            if (in_array($key, $selectedHomePageComponents)) {
                $request->setGlobal($key, $key);
            } else {
                $request->setGlobal($key, '');
            }
        }
        if ($request->has('tagcloudview')) {
            // Tag cloud save
            $tagCloud = $request->get('tagcloudview');
            if ($tagCloud == 'on') {
                $recordModel->set('tagcloud', 0);
            } else {
                $recordModel->set('tagcloud', 1);
            }
        }

        return $recordModel;
    }

    protected function checkRestrictedValueChange(Vtiger_Request $request)
    {
        // NOTE: to be repeated in SaveAjax.php

        if ($request->has('user_name') || $request->has('user_password') || $request->has('accesskey')) {
            // should use separate actions.
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
        }

        if ($request->get('field', '') == 'status' || $request->has('status')) {
            $currentUserModel = Users_Record_Model::getCurrentUserModel();
            // only admin (not self) can change status.
            if (!$currentUserModel->isAdminUser()) {
                throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
            }
            $recordId = $request->get('record');
            if ($recordId == $currentUserModel->getId()) {
                throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
            }
        }
    }

    public function process(Vtiger_Request $request)
    {
        $result = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);
        $_FILES = $result['imagename'];

        $recordId = $request->get('record');
        if (!$recordId) {
            $module = $request->getModule();
            $userName = $request->get('user_name');
            $userModuleModel = Users_Module_Model::getCleanInstance($module);
            $status = $userModuleModel->checkDuplicateUser($userName);
            if ($status == true) {
                throw new AppException(vtranslate('LBL_DUPLICATE_USER_EXISTS', $module));
            }
        } else {
            $this->checkRestrictedValueChange($request);
        }

        $recordModel = $this->saveRecord($request);

        if ($request->get('relationOperation')) {
            $parentRecordModel = Vtiger_Record_Model::getInstanceById($request->get('sourceRecord'), $request->get('sourceModule'));
            $loadUrl = $parentRecordModel->getDetailViewUrl();
        } elseif ($request->get('isPreference')) {
            $loadUrl =  $recordModel->getPreferenceDetailViewUrl();
        } elseif ($request->get('returnmodule') && $request->get('returnview')) {
            $loadUrl = 'index.php?' . $request->getReturnURL();
        } elseif ($request->get('mode') == 'Calendar') {
            $loadUrl = $recordModel->getCalendarSettingsDetailViewUrl();
        } else {
            $loadUrl = $recordModel->getDetailViewUrl();
        }

        header("Location: {$loadUrl}");
    }
}
