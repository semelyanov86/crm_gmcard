<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

function vtws_update($element, $user)
{

    global $log,$adb,$app_strings;

    // setting $app_strings
    if (empty($app_strings)) {
        $currentLanguage = Vtiger_Language_Handler::getLanguage();
        $moduleLanguageStrings = Vtiger_Language_Handler::getModuleStringsFromFile($currentLanguage);
        $app_strings = $moduleLanguageStrings['languageStrings'];
    }

    $idList = vtws_getIdComponents($element['id']);
    $webserviceObject = VtigerWebserviceObject::fromId($adb, $idList[0]);
    $handlerPath = $webserviceObject->getHandlerPath();
    $handlerClass = $webserviceObject->getHandlerClass();

    require_once $handlerPath;

    $handler = new $handlerClass($webserviceObject, $user, $adb, $log);
    $meta = $handler->getMeta();
    $entityName = $meta->getObjectEntityName($element['id']);

    $types = vtws_listtypes(null, $user);
    if (!in_array($entityName, $types['types'])) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Permission to perform the operation is denied');
    }

    if ($entityName !== $webserviceObject->getEntityName()) {
        throw new WebServiceException(WebServiceErrorCode::$INVALIDID, 'Id specified is incorrect');
    }

    if (!$meta->hasPermission(EntityMeta::$UPDATE, $element['id'])) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Permission to read given object is denied');
    }

    if (!$meta->exists($idList[1])) {
        throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND, 'Record you are trying to access is not found');
    }

    if ($meta->hasWriteAccess() !== true) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Permission to write is denied');
    }

    $referenceFields = $meta->getReferenceFieldDetails();
    foreach ($referenceFields as $fieldName => $details) {
        if (isset($element[$fieldName]) && strlen($element[$fieldName]) > 0) {
            $ids = vtws_getIdComponents($element[$fieldName]);
            $elemTypeId = $ids[0];
            $elemId = $ids[1];
            $referenceObject = VtigerWebserviceObject::fromId($adb, $elemTypeId);
            if (!in_array($referenceObject->getEntityName(), $details)) {
                throw new WebServiceException(
                    WebServiceErrorCode::$REFERENCEINVALID,
                    "Invalid reference specified for {$fieldName}",
                );
            }
            if ($referenceObject->getEntityName() == 'Users') {
                if (!$meta->hasAssignPrivilege($element[$fieldName])) {
                    throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Cannot assign record to the given user');
                }
            }
            if (!in_array($referenceObject->getEntityName(), $types['types']) && $referenceObject->getEntityName() != 'Users') {
                throw new WebServiceException(
                    WebServiceErrorCode::$ACCESSDENIED,
                    'Permission to access reference type is denied ' . $referenceObject->getEntityName(),
                );
            }
        } elseif (array_key_exists($fieldName, $element) && $element[$fieldName] !== null) {
            unset($element[$fieldName]);
        }
    }

    $meta->hasMandatoryFields($element);

    $ownerFields = $meta->getOwnerFields();
    if (is_array($ownerFields) && php7_sizeof($ownerFields) > 0) {
        foreach ($ownerFields as $ownerField) {
            if (isset($element[$ownerField]) && $element[$ownerField] !== null
                && !$meta->hasAssignPrivilege($element[$ownerField])) {
                throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, 'Cannot assign record to the given user');
            }
        }
    }

    $entity = $handler->update($element);
    VTWS_PreserveGlobal::flush();

    return $entity;
}
