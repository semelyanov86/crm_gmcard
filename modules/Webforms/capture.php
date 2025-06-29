<?php

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */
// Switch the working directory to base
chdir(dirname(__FILE__) . '/../..');

require_once 'vendor/autoload.php';
require_once 'include/Zend/Json.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'include/utils/VtlibUtils.php';
require_once 'include/Webservices/Create.php';
require_once 'modules/Webforms/model/WebformsModel.php';
require_once 'modules/Webforms/model/WebformsFieldModel.php';
require_once 'include/QueryGenerator/QueryGenerator.php';
require_once 'includes/runtime/EntryPoint.php';
require_once 'includes/main/WebUI.php';
require_once 'include/Webservices/AddRelated.php';
require_once 'modules/Webforms/config.captcha.php';

class Webform_Capture
{
    public function captureNow($request)
    {
        $isURLEncodeEnabled = $request['urlencodeenable'];
        $currentLanguage = Vtiger_Language_Handler::getLanguage();
        $moduleLanguageStrings = Vtiger_Language_Handler::getModuleStringsFromFile($currentLanguage);
        vglobal('app_strings', $moduleLanguageStrings['languageStrings']);

        $returnURL = false;

        try {
            if (!vtlib_isModuleActive('Webforms')) {
                throw new Exception('webforms is not active');
            }

            $webform = Webforms_Model::retrieveWithPublicId(vtlib_purify($request['publicid']));
            if (empty($webform)) {
                throw new Exception('Webform not found.');
            }

            $webformSettingsRecord = Settings_Webforms_Record_Model::getInstanceById($webform->getId(), 'Settings:Webforms');
            if ($webformSettingsRecord->isCaptchaEnabled()) {
                $this->validateRecaptcha($request['g-recaptcha-response']);
            }

            $returnURL = $webform->getReturnUrl();
            $roundrobin = $webform->getRoundrobin();

            // Retrieve user information
            $user = CRMEntity::getInstance('Users');
            $user->id = $user->getActiveAdminId();
            $user->retrieve_entity_info($user->id, 'Users');

            // Prepare the parametets
            $parameters = [];
            $webformFields = $webform->getFields();
            foreach ($webformFields as $webformField) {
                if ($webformField->getDefaultValue() != null) {
                    $parameters[$webformField->getFieldName()] = decode_html($webformField->getDefaultValue());
                } else {
                    // If urlencode is enabled then skipping decoding field names
                    if ($isURLEncodeEnabled == 1) {
                        $webformNeutralizedField = $webformField->getNeutralizedField();
                    } else {
                        $webformNeutralizedField = html_entity_decode($webformField->getNeutralizedField(), ENT_COMPAT, 'UTF-8');
                    }

                    if (isset($request[$webformField->getFieldName()])) {
                        $webformNeutralizedField = $webformField->getFieldName();
                    }
                    if (is_array(vtlib_purify($request[$webformNeutralizedField]))) {
                        $fieldData = implode(' |##| ', vtlib_purify($request[$webformNeutralizedField]));
                    } else {
                        $fieldData = vtlib_purify($request[$webformNeutralizedField]);
                        $fieldData = decode_html($fieldData);
                    }

                    $parameters[$webformField->getFieldName()] = stripslashes($fieldData);
                }
                if ($webformField->getRequired()) {
                    if (!isset($parameters[$webformField->getFieldName()])) {
                        throw new Exception('Required fields not filled');
                    }
                }
            }

            if ($roundrobin) {
                $ownerId = $webform->getRoundrobinOwnerId();
                $ownerType = vtws_getOwnerType($ownerId);
                $parameters['assigned_user_id'] = vtws_getWebserviceEntityId($ownerType, $ownerId);
            } else {
                $ownerId = $webform->getOwnerId();
                $ownerType = vtws_getOwnerType($ownerId);
                $parameters['assigned_user_id'] = vtws_getWebserviceEntityId($ownerType, $ownerId);
            }

            $moduleModel = Vtiger_Module_Model::getInstance($webform->getTargetModule());
            $fieldInstances = Vtiger_Field_Model::getAllForModule($moduleModel);
            foreach ($fieldInstances as $blockInstance) {
                foreach ($blockInstance as $fieldInstance) {
                    $fieldName = $fieldInstance->getName();
                    if ($fieldInstance->get('uitype') == 56 && $fieldInstance->getDefaultFieldValue() == '') {
                        $defaultValue = $request[$fieldName];
                    } elseif (empty($parameters[$fieldName])) {
                        $defaultValue = $fieldInstance->getDefaultFieldValue();
                        if ($defaultValue) {
                            $parameters[$fieldName] = decode_html($defaultValue);
                        }
                    } elseif ($fieldInstance->get('uitype') == 71 || $fieldInstance->get('uitype') == 72) {
                        // ignore comma(,) if it is currency field
                        $parameters[$fieldName] = str_replace(',', '', $parameters[$fieldName]);
                    }
                }
            }

            // New field added to show Record Source
            $parameters['source'] = 'Webform';

            // Create the record
            $record = vtws_create($webform->getTargetModule(), $parameters, $user);
            $webform->createDocuments($record);

            $this->sendResponse($returnURL, 'ok');

            return;
        } catch (DuplicateException $e) {
            $sourceModule = $webform->getTargetModule();
            $mailBody = vtranslate('LBL_DUPLICATION_FAILURE_FROM_WEBFORMS', $sourceModule, vtranslate('SINGLE_' . $sourceModule, $sourceModule), $webform->getName(), vtranslate('SINGLE_' . $sourceModule, $sourceModule));

            $userModel = Users_Record_Model::getInstanceFromPreferenceFile($user->id);
            sendMailToUserOnDuplicationPrevention($sourceModule, $parameters, $mailBody, $userModel);

            $this->sendResponse($returnURL, false, $e->getMessage());

            return;
        } catch (Exception $e) {
            $this->sendResponse($returnURL, false, $e->getMessage());

            return;
        }
    }

    protected function sendResponse($url, $success = false, $failure = false)
    {
        if (empty($url)) {
            if ($success) {
                $response = Zend_Json::encode(['success' => true, 'result' => $success]);
            } else {
                $response = Zend_Json::encode(['success' => false, 'error' => ['message' => $failure]]);
            }

            // Support JSONP
            if (!empty($_REQUEST['callback'])) {
                $callback = vtlib_purify($_REQUEST['callback']);
                echo sprintf('%s(%s)', $callback, $response);
            } else {
                echo $response;
            }
        } else {
            $pos = strpos($url, 'http');
            if ($pos !== false) {
                header(sprintf('Location: %s?%s=%s', $url, $success ? 'success' : 'error', $success ? $success : $failure));
            } else {
                header(sprintf('Location: http://%s?%s=%s', $url, $success ? 'success' : 'error', $success ? $success : $failure));
            }
        }
    }

    private function validateRecaptcha($recaptchaResponse)
    {
        $recaptchaValidation = $this->postCaptcha($recaptchaResponse);

        if (!$recaptchaValidation['success']) {
            throw new Exception('Please verify you are not a robot.');
        }
    }

    private function postCaptcha($recaptchaResponse)
    {
        global $captchaConfig;

        $fields_string = '';
        $fields = [
            'secret' => $captchaConfig['VTIGER_RECAPTCHA_PRIVATE_KEY'],
            'response' => $recaptchaResponse,
        ];
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }

        $fields_string = rtrim($fields_string, '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
        curl_setopt($ch, CURLOPT_POST, php7_count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result, true);
    }
}

// NOTE: Take care of stripping slashes...
$webformCapture = new Webform_Capture();
$request = vtlib_purify($_REQUEST);
$isURLEncodeEnabled = $request['urlencodeenable'];
// Do urldecode conversion only if urlencode is enabled in a form.
if ($isURLEncodeEnabled == 1) {
    $requestParameters = [];
    // Decoding the form element name attributes.
    foreach ($request as $key => $value) {
        $requestParameters[urldecode($key)] = $value;
    }
    // Replacing space with underscore to make request parameters equal to webform fields
    $neutralizedParameters = [];
    foreach ($requestParameters as $key => $value) {
        $modifiedKey = str_replace(' ', '_', $key);
        $neutralizedParameters[$modifiedKey] = $value;
    }
    $webformCapture->captureNow($neutralizedParameters);
} else {
    $webformCapture->captureNow($request);
}
