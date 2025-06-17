<?php

if (!function_exists('pdfmakerGetEntityName')) {
    function pdfmakerGetEntityName($entityid)
    {
        global $adb;
        $row = $adb->fetchByAssoc($adb->pquery("SELECT setype FROM vtiger_crmentity WHERE crmid=?", array($entityid)));
        $return = getEntityName($row['setype'], array($entityid));
        return $return[$entityid];
    }
}
