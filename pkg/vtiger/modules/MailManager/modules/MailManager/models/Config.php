<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class MailManager_Config_Model
{
    public static $MAILMANAGER_CONFIG = [
        // Max upload limit in bytes
        'MAXUPLOADLIMIT' => 5_242_880,

        // Max Download Limit in Bytes, as the files are encoded the file size increases
        // so the limit is set to close to 7MB
        'MAXDOWNLOADLIMIT' => 7_000_000,

        // Increase the memory_limit for larger attachments
        'MEMORY_LIMIT'	=> '256M',
    ];

    /**
     * Get configuration parameter configured value or default one.
     */
    public static function get($key, $defvalue = false)
    {
        if (isset(self::$MAILMANAGER_CONFIG)) {
            if (isset(self::$MAILMANAGER_CONFIG[$key])) {
                return self::$MAILMANAGER_CONFIG[$key];
            }
        }

        return $defvalue;
    }
}
