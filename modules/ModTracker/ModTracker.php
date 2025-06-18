<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'vtlib/Vtiger/Event.php';
include_once 'include/Webservices/GetUpdates.php';

#[AllowDynamicProperties]
class ModTracker
{
    /**
     * Constant variables which indicates the status of the changed record.
     */
    public static $UPDATED = '0';

    public static $DELETED = '1';

    public static $CREATED = '2';

    public static $RESTORED = '3';

    public static $LINK = '4';

    public static $UNLINK = '5';

    /* Entry point will invoke this function no need to act on */
    public function track_view($user_id, $current_module, $id = '') {}

    /**
     * Invoked when special actions are performed on the module.
     * @param string Module name
     * @param string Event Type
     */
    public function vtlib_handler($moduleName, $eventType)
    {
        global $adb, $currentModule;

        $modtrackerModule = Vtiger_Module::getInstance($currentModule);

        // Shouldn't assign any thing to variable if we are not going use that
        $this->getModTrackerEnabledModules();

        if ($eventType == 'module.postinstall') {
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
				VALUES (?,?,?,?,?,?,?)', [$fieldid, $blockid, 'ModTracker', 'set-IcoLoginHistory.gif', 'LBL_MODTRACKER_DESCRIPTION',
                'index.php?module=ModTracker&action=BasicSettings&parenttab=Settings&formodule=ModTracker', $seq]);

        } elseif ($eventType == 'module.disabled') {

            $em = new VTEventsManager($adb);
            $em->setHandlerInActive('ModTrackerHandler');

            // De-register Common Javascript
            $modtrackerModule->deleteLink('HEADERSCRIPT', 'ModTrackerCommon_JS');

        } elseif ($eventType == 'module.enabled') {
            $em = new VTEventsManager($adb);
            $em->setHandlerActive('ModTrackerHandler');

            // Register Common Javascript
            $modtrackerModule->addLink('HEADERSCRIPT', 'ModTrackerCommon_JS', 'modules/ModTracker/ModTrackerCommon.js');

        } elseif ($eventType == 'module.preuninstall') {
            // TODO Handle actions when this module is about to be deleted.
        } elseif ($eventType == 'module.preupdate') {
            // TODO Handle actions before this module is updated.
        } elseif ($eventType == 'module.postupdate') {
            // TODO Handle actions after this module is updated.
        }
    }

    /**
     * function gives an array of module names for which modtracking is enabled.
     */
    public function getModTrackerEnabledModules()
    {
        global $adb;
        $modules = [];
        $moduleResult = $adb->pquery('SELECT * FROM vtiger_modtracker_tabs', []);
        for ($i = 0; $i < $adb->num_rows($moduleResult); ++$i) {
            $tabId = $adb->query_result($moduleResult, $i, 'tabid');
            $visible = $adb->query_result($moduleResult, $i, 'visible');
            self::updateCache($tabId, $visible);
            if ($visible == 1) {
                $modules[] = getTabModuleName($tabId);
            }
        }

        return $modules;
    }

    // cache variable
    public static $__cache_modtracker = [];

    /**
     *Invoked to disable tracking for the module.
     * @param int $tabid
     */
    public static function disableTrackingForModule($tabid)
    {
        global $adb;
        if (!self::isModulePresent($tabid)) {
            $adb->pquery('INSERT INTO vtiger_modtracker_tabs VALUES(?,?)', [$tabid, 0]);
            self::updateCache($tabid, 0);
        } else {
            $adb->pquery('UPDATE vtiger_modtracker_tabs SET visible = 0 WHERE tabid = ?', [$tabid]);
            self::updateCache($tabid, 0);
        }
        if (self::isModtrackerLinkPresent($tabid)) {
            $moduleInstance = Vtiger_Module::getInstance($tabid);
            $moduleInstance->deleteLink('DETAILVIEWBASIC', 'View History');
        }
    }

    /**
     *Invoked to enable tracking for the module.
     * @param int $tabid
     */
    public static function enableTrackingForModule($tabid)
    {
        global $adb;
        if (!self::isModulePresent($tabid)) {
            // Shouldn't assign any thing to variable if we are not going use that
            $adb->pquery('INSERT INTO vtiger_modtracker_tabs VALUES(?,?)', [$tabid, 1]);
            self::updateCache($tabid, 1);
        } else {
            // Shouldn't assign any thing to variable if we are not going use that
            $adb->pquery('UPDATE vtiger_modtracker_tabs SET visible = 1 WHERE tabid = ?', [$tabid]);
            self::updateCache($tabid, 1);
        }
        if (!self::isModTrackerLinkPresent($tabid)) {
            $moduleInstance = Vtiger_Module::getInstance($tabid);
            $moduleInstance->addLink(
                'DETAILVIEWBASIC',
                'View History',
                "javascript:ModTrackerCommon.showhistory('\$RECORD\$')",
                '',
                '',
                ['path' => 'modules/ModTracker/ModTracker.php', 'class' => 'ModTracker', 'method' => 'isViewPermitted'],
            );
        }
    }

    /**
     *Invoked to check if tracking is enabled or disabled for the module.
     * @param string $modulename
     */
    public static function isTrackingEnabledForModule($modulename)
    {
        global $adb;
        $tabid = getTabid($modulename);
        if (!self::getVisibilityForModule($tabid) || self::getVisibilityForModule($tabid) !== 0) {
            $query = $adb->pquery('SELECT * FROM vtiger_modtracker_tabs WHERE vtiger_modtracker_tabs.visible = 1
								   AND vtiger_modtracker_tabs.tabid=?', [$tabid]);
            $rows = $adb->num_rows($query);

            // We are not using this variable. No need.
            // $visible=$adb->query_result($query,0,'visible');
            if ($rows < 1) {
                self::updateCache($tabid, 0);

                return false;
            }
            self::updateCache($tabid, 1);

            return true;

        } elseif (self::getVisibilityForModule($tabid) === 0) {
            return false;
        }

        return true;

    }

    /**
     *Invoked to check if the module is present in the table or not.
     * @param int $tabid
     */
    public static function isModulePresent($tabid)
    {
        global $adb;
        if (!self::checkModuleInModTrackerCache($tabid)) {
            $query = $adb->pquery('SELECT * FROM vtiger_modtracker_tabs WHERE tabid = ?', [$tabid]);
            $rows = $adb->num_rows($query);
            if ($rows) {
                $tabid = $adb->query_result($query, 0, 'tabid');
                $visible = $adb->query_result($query, 0, 'visible');
                self::updateCache($tabid, $visible);

                return true;
            }

            return false;
        }

        return true;
    }

    /**
     *Invoked to check if ModTracker links are enabled for the module.
     * @param int $tabid
     */
    public static function isModtrackerLinkPresent($tabid)
    {
        global $adb;
        $query1 = $adb->pquery("SELECT * FROM vtiger_links WHERE linktype='DETAILVIEWBASIC' AND
							  linklabel = 'View History' AND tabid = ?", [$tabid]);
        $row = $adb->num_rows($query1);
        if ($row >= 1) {
            return true;
        }

        return false;
    }

    /**
     *Invoked to update cache.
     * @param int $tabid
     * @param bool $visible
     */
    public static function updateCache($tabid, $visible)
    {
        self::$__cache_modtracker[$tabid] = [
            'tabid'		=> $tabid,
            'visible'	=> $visible,
        ];
    }

    /**
     *Invoked to check the ModTracker cache.
     * @param int $tabid
     */
    public static function checkModuleInModTrackerCache($tabid)
    {
        if (isset(self::$__cache_modtracker[$tabid])) {
            return true;
        }

        return false;
    }

    /**
     *Invoked to fetch the visibility for the module from the cache.
     * @param int $tabid
     */
    public static function getVisibilityForModule($tabid)
    {
        if (isset(self::$__cache_modtracker[$tabid])) {
            return self::$__cache_modtracker[$tabid]['visible'];
        }

        return false;
    }

    /**
     * Get the list of changed record after $mtime.
     * @param <type> $mtime
     * @param <type> $user
     * @param <type> $limit
     */
    public function getChangedRecords($uniqueId, $mtime, $limit = 100)
    {
        global $current_user, $adb;
        $datetime = date('Y-m-d H:i:s', $mtime);

        $accessibleModules = $this->getModTrackerEnabledModules();

        if (empty($accessibleModules)) {
            throw new Exception('Modtracker not enabled for any modules');
        }

        $query = 'SELECT id, module, modifiedtime, vtiger_crmentity.crmid, smownerid, vtiger_modtracker_basic.status
                FROM vtiger_modtracker_basic
                INNER JOIN vtiger_crmentity ON vtiger_modtracker_basic.crmid = vtiger_crmentity.crmid
                    AND vtiger_modtracker_basic.changedon = vtiger_crmentity.modifiedtime
                WHERE id > ? AND changedon >= ? AND module IN(' . generateQuestionMarks($accessibleModules) . ')
                ORDER BY id';

        $params = [$uniqueId, $datetime];
        foreach ($accessibleModules as $entityModule) {
            $params[] = $entityModule;
        }

        if ($limit != false) {
            $query .= " LIMIT {$limit}";
        }

        $result = $adb->pquery($query, $params);

        $modTime = [];
        $rows = $adb->num_rows($result);

        for ($i = 0; $i < $rows; ++$i) {
            $status = $adb->query_result($result, $i, 'status');

            $record['uniqueid']     = $adb->query_result($result, $i, 'id');
            $record['modifiedtime'] = $adb->query_result($result, $i, 'modifiedtime');
            $record['module']       = $adb->query_result($result, $i, 'module');
            $record['crmid']        = $adb->query_result($result, $i, 'crmid');
            $record['assigneduserid'] = $adb->query_result($result, $i, 'smownerid');

            if ($status == ModTracker::$DELETED) {
                $deletedRecords[] = $record;
            } elseif ($status == ModTracker::$CREATED) {
                $createdRecords[] = $record;
            } elseif ($status == ModTracker::$UPDATED) {
                $updatedRecords[] = $record;
            }

            $modTime[]              = $record['modifiedtime'];
            $uniqueIds[]            = $record['uniqueid'];
        }

        if (!empty($uniqueIds)) {
            $maxUniqueId = max($uniqueIds);
        }

        if (empty($maxUniqueId)) {
            $maxUniqueId = $uniqueId;
        }

        if (!empty($modTime)) {
            $maxModifiedTime = max($modTime);
        }
        if (!$maxModifiedTime) {
            $maxModifiedTime = $datetime;
        }

        $output['created'] = $createdRecords;
        $output['updated'] = $updatedRecords;
        $output['deleted'] = $deletedRecords;

        $moreQuery = 'SELECT * FROM vtiger_modtracker_basic WHERE id > ? AND changedon >= ? AND module
            IN(' . generateQuestionMarks($accessibleModules) . ')';

        $param = [$maxUniqueId, $maxModifiedTime];
        foreach ($accessibleModules as $entityModule) {
            $param[] = $entityModule;
        }

        $result = $adb->pquery($moreQuery, $param);

        if ($adb->num_rows($result) > 0) {
            $output['more'] = true;
        } else {
            $output['more'] = false;
        }

        $output['uniqueid'] = $maxUniqueId;

        if (!$maxModifiedTime) {
            $modifiedtime = $mtime;
        } else {
            $modifiedtime = vtws_getSeconds($maxModifiedTime);
        }
        if (is_string($modifiedtime)) {
            $modifiedtime = intval($modifiedtime);
        }
        $output['lastModifiedTime'] = $modifiedtime;

        return $output;
    }

    public static function getRecordFieldChanges($crmid, $time, $decodeHTML = false)
    {
        global $adb;

        $date = date('Y-m-d H:i:s', $time);

        $fieldResult = $adb->pquery('SELECT * FROM vtiger_modtracker_detail
                        INNER JOIN vtiger_modtracker_basic ON vtiger_modtracker_basic.id = vtiger_modtracker_detail.id
                        WHERE crmid = ? AND changedon >= ?', [$crmid, $date]);
        for ($i = 0; $i < $adb->num_rows($fieldResult); ++$i) {
            $fieldName = $adb->query_result($fieldResult, $i, 'fieldname');
            if ($fieldName == 'record_id' || $fieldName == 'record_module'
                    || $fieldName == 'createdtime') {
                continue;
            }

            $field['postvalue'] = $adb->query_result($fieldResult, $i, 'postvalue');
            $field['prevalue'] = $adb->query_result($fieldResult, $i, 'prevalue');
            if ($decodeHTML) {
                $field['postvalue'] = decode_html($field['postvalue']);
                $field['prevalue'] = decode_html($field['prevalue']);
            }
            $fields[$fieldName] = $field;
        }

        return $fields;
    }

    public static function isViewPermitted($linkData)
    {
        $moduleName = $linkData->getModule();
        $recordId = $linkData->getInputParameter('record');
        if (isPermitted($moduleName, 'DetailView', $recordId) == 'yes') {
            return true;
        }

        return false;
    }

    public static function trackRelation($sourceModule, $sourceId, $targetModule, $targetId, $type)
    {
        global $adb, $current_user;
        $currentTime = date('Y-m-d H:i:s');

        $id = $adb->getUniqueId('vtiger_modtracker_basic');
        $adb->pquery(
            'INSERT INTO vtiger_modtracker_basic(id, crmid, module, whodid, changedon, status) VALUES(?,?,?,?,?,?)',
            [$id, $sourceId, $sourceModule, $current_user->id, $currentTime, $type],
        );

        $adb->pquery('INSERT INTO vtiger_modtracker_relations(id, targetmodule, targetid, changedon)
            VALUES(?,?,?,?)', [$id, $targetModule, $targetId, $currentTime]);
    }

    public static function linkRelation($sourceModule, $sourceId, $targetModule, $targetId)
    {
        self::trackRelation($sourceModule, $sourceId, $targetModule, $targetId, self::$LINK);
    }

    public static function unLinkRelation($sourceModule, $sourceId, $targetModule, $targetId)
    {
        self::trackRelation($sourceModule, $sourceId, $targetModule, $targetId, self::$UNLINK);
    }
}
