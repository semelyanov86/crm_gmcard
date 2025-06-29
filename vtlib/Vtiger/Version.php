<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'vtigerversion.php';

/**
 * Provides utility APIs to work with Vtiger Version detection.
 */
class Vtiger_Version
{
    /**
     * Get current version of vtiger in use.
     */
    public static function current()
    {
        global $vtiger_current_version;

        return $vtiger_current_version;
    }

    /**
     * Check current version of vtiger with given version.
     * @param string Version against which comparision to be done
     * @param string Condition like ( '=', '!=', '<', '<=', '>', '>=')
     */
    public static function check($with_version, $condition = '=')
    {
        $current_version = self::current();
        // xml node is passed to this method sometimes
        if (!is_string($with_version)) {
            $with_version = (string) $with_version;
        }
        $with_version = self::getUpperLimitVersion($with_version);

        return version_compare($current_version, $with_version, $condition);
    }

    public static function endsWith($string, $endString)
    {
        $strLen = strlen($string);
        $endStrLen = strlen($endString);
        if ($endStrLen > $strLen) {
            return false;
        }

        return substr_compare($string, $endString, -$endStrLen) === 0;
    }

    public static function getUpperLimitVersion($version)
    {
        if (!self::endsWith($version, '.*')) {
            return $version;
        }

        $version = rtrim($version, '.*');
        $lastVersionPartIndex = strrpos($version, '.');
        if ($lastVersionPartIndex === false) {
            $version = ((int) $version) + 1;
        } else {
            $lastVersionPart = substr($version, $lastVersionPartIndex + 1, strlen($version));
            $upgradedVersionPart = ((int) $lastVersionPart) + 1;
            $version = substr($version, 0, $lastVersionPartIndex + 1) . $upgradedVersionPart;
        }

        return $version;
    }
}
