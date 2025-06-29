<?php

/*+********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

#[AllowDynamicProperties]
class CustomerPortal
{
    /**
     * Invoked when special actions are performed on the module.
     * @param string Module name
     * @param string Event Type
     */
    public function vtlib_handler($moduleName, $eventType)
    {

        require_once 'include/utils/utils.php';
        global $adb,$mod_strings;

        if ($eventType == 'module.postinstall') {
            $portalModules = ['HelpDesk', 'Faq', 'Invoice', 'Quotes', 'Products', 'Services', 'Documents',
                'Contacts', 'Accounts', 'Project', 'ProjectTask', 'ProjectMilestone', 'Assets'];

            $query = 'SELECT max(sequence) AS max_tabseq FROM vtiger_customerportal_tabs';
            $res = $adb->pquery($query, []);
            $tabseq = $adb->query_result($res, 0, 'max_tabseq');
            $i = ++$tabseq;
            foreach ($portalModules as $module) {
                $tabIdResult = $adb->pquery('SELECT tabid FROM vtiger_tab WHERE name=?', [$module]);
                $tabId = $adb->query_result($tabIdResult, 0, 'tabid');
                if ($tabId) {
                    ++$i;
                    $adb->pquery('INSERT INTO vtiger_customerportal_tabs(tabid,visible,sequence) VALUES (?, ?, ?)', [$tabId, 1, $i]);
                    $adb->pquery('INSERT INTO vtiger_customerportal_prefs(tabid,prefkey,prefvalue) VALUES (?, ?, ?)', [$tabId, 'showrelatedinfo', 1]);
                }
            }

            $adb->pquery('INSERT INTO vtiger_customerportal_prefs(tabid,prefkey,prefvalue) VALUES (?, ?, ?)', [0, 'userid', 1]);
            $adb->pquery('INSERT INTO vtiger_customerportal_prefs(tabid,prefkey,prefvalue) VALUES (?, ?, ?)', [0, 'defaultassignee', 1]);

            // Mark the module as Standard module
            $adb->pquery('UPDATE vtiger_tab SET customized=0 WHERE name=?', [$moduleName]);

            $fieldid = $adb->getUniqueID('vtiger_settings_field');
            $blockid = getSettingsBlockId('LBL_OTHER_SETTINGS');
            $seq_res = $adb->pquery('SELECT max(sequence) AS max_seq FROM vtiger_settings_field WHERE blockid = ?', [$blockid]);
            if ($adb->num_rows($seq_res) > 0) {
                $cur_seq = $adb->query_result($seq_res, 0, 'max_seq');
                if ($cur_seq != null) {
                    $seq = $cur_seq + 1;
                }
            }

            $adb->pquery('INSERT INTO vtiger_settings_field(fieldid, blockid, name, iconpath, description, linkto, sequence)
				VALUES (?,?,?,?,?,?,?)', [$fieldid, $blockid, 'LBL_CUSTOMER_PORTAL', 'portal_icon.png', 'PORTAL_EXTENSION_DESCRIPTION', 'index.php?module=CustomerPortal&action=index&parenttab=Settings', $seq]);


        } elseif ($eventType == 'module.disabled') {
            // TODO Handle actions when this module is disabled.
        } elseif ($eventType == 'module.enabled') {
            // TODO Handle actions when this module is enabled.
        } elseif ($eventType == 'module.preuninstall') {
            // TODO Handle actions when this module is about to be deleted.
        } elseif ($eventType == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } elseif ($eventType == 'module.postupdate') {
            // TODO Handle actions after this module is updated.
        }
    }
}
