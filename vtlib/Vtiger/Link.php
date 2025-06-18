<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */
include_once 'vtlib/Vtiger/Utils.php';
include_once 'vtlib/Vtiger/Utils/StringTemplate.php';
include_once 'vtlib/Vtiger/LinkData.php';

/**
 * Provides API to handle custom links.
 */
#[AllowDynamicProperties]
class Vtiger_Link
{
    public $tabid;

    public $linkid;

    public $linktype;

    public $linklabel;

    public $linkurl;

    public $linkicon;

    public $sequence;

    public $status = false;

    public $handler_path;

    public $handler_class;

    public $handler;

    // Ignore module while selection
    public const IGNORE_MODULE = -1;

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Initialize this instance.
     */
    public function initialize($valuemap)
    {
        $this->tabid  = $valuemap['tabid'] ?? null;
        $this->linkid = $valuemap['linkid'] ?? null;
        $this->linktype = $valuemap['linktype'] ?? null;
        $this->linklabel = $valuemap['linklabel'] ?? null;
        $this->linkurl  = isset($valuemap['linkurl']) ? decode_html($valuemap['linkurl']) : null;
        $this->linkicon = isset($valuemap['linkicon']) ? decode_html($valuemap['linkicon']) : null;
        $this->sequence = $valuemap['sequence'] ?? null;
        $this->status   = $valuemap['status'] ?? null;
        $this->handler_path	= $valuemap['handler_path'] ?? null;
        $this->handler_class = $valuemap['handler_class'] ?? null;
        $this->handler		= $valuemap['handler'] ?? null;
        $this->parent_link	= $valuemap['parent_link'] ?? null;
    }

    /**
     * Get module name.
     */
    public function module()
    {
        if (!empty($this->tabid)) {
            return getTabModuleName($this->tabid);
        }

        return false;
    }

    /**
     * Get unique id for the insertion.
     */
    public static function __getUniqueId()
    {
        global $adb;

        return $adb->getUniqueID('vtiger_links');
    }

    /** Cache (Record) the schema changes to improve performance */
    public static $__cacheSchemaChanges = [];

    /**
     * Initialize the schema (tables).
     */
    public static function __initSchema()
    {
        /* vtiger_links is already core product table */
        /*if(empty(self::$__cacheSchemaChanges['vtiger_links'])) {
            if(!Vtiger_Utils::CheckTable('vtiger_links')) {
                Vtiger_Utils::CreateTable(
                    'vtiger_links',
                    '(linkid INT NOT NULL PRIMARY KEY,
                    tabid INT, linktype VARCHAR(20), linklabel VARCHAR(30), linkurl VARCHAR(255), linkicon VARCHAR(100), sequence INT, status INT(1) NOT NULL DEFAULT 1)',
                    true);
                Vtiger_Utils::ExecuteQuery(
                    'CREATE INDEX link_tabidtype_idx on vtiger_links(tabid,linktype)');
            }
            self::$__cacheSchemaChanges['vtiger_links'] = true;
        }*/
    }

    /**
     * Add link given module.
     * @param int Module ID
     * @param string Link Type (like DETAILVIEW). Useful for grouping based on pages.
     * @param string Label to display
     * @param string HREF value or URL to use for the link
     * @param string ICON to use on the display
     * @param int Order or sequence of displaying the link
     */
    public static function addLink($tabid, $type, $label, $url, $iconpath = '', $sequence = 0, $handlerInfo = null, $parentLink = null)
    {
        global $adb;
        self::__initSchema();
        $checkres = $adb->pquery(
            'SELECT linkid FROM vtiger_links WHERE tabid=? AND linktype=? AND linkurl=? AND linkicon=? AND linklabel=?',
            [$tabid, $type, $url, $iconpath, $label],
        );
        if (!$adb->num_rows($checkres)) {
            $uniqueid = self::__getUniqueId();
            $sql = 'INSERT INTO vtiger_links (linkid,tabid,linktype,linklabel,linkurl,linkicon,'
            . 'sequence';
            $params = [$uniqueid, $tabid, $type, $label, $url, $iconpath, intval($sequence)];
            if (!empty($handlerInfo)) {
                $sql .= ', handler_path, handler_class, handler';
                $params[] = $handlerInfo['path'] ?? null;
                $params[] = $handlerInfo['class'] ?? null;
                $params[] = $handlerInfo['method'] ?? null;
            }
            if (!empty($parentLink)) {
                $sql .= ',parent_link';
                $params[] = $parentLink;
            }
            $sql .= (') VALUES (' . generateQuestionMarks($params) . ')');
            $adb->pquery($sql, $params);
            self::log("Adding Link ({$type} - {$label}) ... DONE");
        }
    }

    /**
     * Delete link of the module.
     * @param int Module ID
     * @param string Link Type (like DETAILVIEW). Useful for grouping based on pages.
     * @param string Display label
     * @param string URL of link to lookup while deleting
     */
    public static function deleteLink($tabid, $type, $label, $url = false)
    {
        global $adb;
        self::__initSchema();
        if ($url) {
            $adb->pquery(
                'DELETE FROM vtiger_links WHERE tabid=? AND linktype=? AND linklabel=? AND linkurl=?',
                [$tabid, $type, $label, $url],
            );
            self::log("Deleting Link ({$type} - {$label} - {$url}) ... DONE");
        } else {
            $adb->pquery(
                'DELETE FROM vtiger_links WHERE tabid=? AND linktype=? AND linklabel=?',
                [$tabid, $type, $label],
            );
            self::log("Deleting Link ({$type} - {$label}) ... DONE");
        }
    }

    /**
     * Delete all links related to module.
     * @param int module ID
     */
    public static function deleteAll($tabid)
    {
        global $adb;
        self::__initSchema();
        $adb->pquery('DELETE FROM vtiger_links WHERE tabid=?', [$tabid]);
        self::log('Deleting Links ... DONE');
    }

    /**
     * Get all the links related to module.
     * @param int module ID
     */
    public static function getAll($tabid)
    {
        return self::getAllByType($tabid);
    }

    /**
     * Get all the link related to module based on type.
     * @param int Module ID
     * @param mixed String or List of types to select
     * @param Map Key-Value pair to use for formating the link url
     */
    public static function getAllByType($tabid, $type = false, $parameters = false)
    {
        global $adb, $current_user;
        self::__initSchema();

        $multitype = false;

        if ($type) {
            // Multiple link type selection?
            if (is_array($type)) {
                $multitype = true;
                if ($tabid === self::IGNORE_MODULE) {
                    $sql = 'SELECT * FROM vtiger_links WHERE linktype IN ('
                        . Vtiger_Utils::implodestr('?', php7_count($type), ',') . ') ';
                    $params = $type;
                    $permittedTabIdList = getPermittedModuleIdList();
                    if (php7_count($permittedTabIdList) > 0 && $current_user->is_admin !== 'on') {
                        array_push($permittedTabIdList, 0);	// Added to support one link for all modules
                        $sql .= ' and tabid IN ('
                            . Vtiger_Utils::implodestr('?', php7_count($permittedTabIdList), ',') . ')';
                        $params[] = $permittedTabIdList;
                    }
                    $result = $adb->pquery($sql, [$adb->flatten_array($params)]);
                } else {
                    $result = $adb->pquery(
                        'SELECT * FROM vtiger_links WHERE (tabid=? OR tabid=0) AND linktype IN ('
                        . Vtiger_Utils::implodestr('?', php7_count($type), ',') . ')',
                        [$tabid, $adb->flatten_array($type)],
                    );
                }
            } else {
                // Single link type selection
                if ($tabid === self::IGNORE_MODULE) {
                    $result = $adb->pquery('SELECT * FROM vtiger_links WHERE linktype=?', [$type]);
                } else {
                    $result = $adb->pquery('SELECT * FROM vtiger_links WHERE (tabid=? OR tabid=0) AND linktype=?', [$tabid, $type]);
                }
            }
        } else {
            $result = $adb->pquery('SELECT * FROM vtiger_links WHERE tabid=?', [$tabid]);
        }

        $strtemplate = new Vtiger_StringTemplate();
        if ($parameters) {
            foreach ($parameters as $key => $value) {
                $strtemplate->assign($key, $value);
            }
        }

        $instances = [];
        if ($multitype) {
            foreach ($type as $t) {
                $instances[$t] = [];
            }
        }

        while ($row = $adb->fetch_array($result)) {
            $instance = new self();
            $instance->initialize($row);
            if (!empty($row['handler_path']) && isFileAccessible($row['handler_path'])) {
                checkFileAccessForInclusion($row['handler_path']);
                require_once $row['handler_path'];
                $linkData = new Vtiger_LinkData($instance, $current_user);
                $ignore = call_user_func([$row['handler_class'], $row['handler']], $linkData);
                if (!$ignore) {
                    self::log('Ignoring Link ... ' . var_export($row, true));

                    continue;
                }
            }
            if ($parameters) {
                $instance->linkurl = $strtemplate->merge($instance->linkurl);
                $instance->linkicon = $strtemplate->merge($instance->linkicon);
            }
            if ($multitype) {
                $instances[$instance->linktype][] = $instance;
            } else {
                $instances[$instance->linktype] = $instance;
            }
        }

        return $instances;
    }

    /**
     * Extract the links of module for export.
     */
    public static function getAllForExport($tabid)
    {
        global $adb;
        $result = $adb->pquery('SELECT * FROM vtiger_links WHERE tabid=?', [$tabid]);
        $links  = [];

        while ($row = $adb->fetch_array($result)) {
            $instance = new self();
            $instance->initialize($row);
            $links[] = $instance;
        }

        return $links;
    }

    /**
     * Helper function to log messages.
     * @param string Message to log
     * @param bool true appends linebreak, false to avoid it
     */
    public static function log($message, $delimit = true)
    {
        Vtiger_Utils::Log($message, $delimit);
    }

    /**
     * Checks whether the user is admin or not.
     * @param Vtiger_LinkData $linkData
     * @return bool
     */
    public static function isAdmin($linkData)
    {
        $user = $linkData->getUser();

        return $user->is_admin == 'on' || $user->column_fields['is_admin'] == 'on';
    }

    public static function updateLink($tabId, $linkId, $linkInfo = [])
    {
        if ($linkInfo && is_array($linkInfo)) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery('SELECT 1 FROM vtiger_links WHERE tabid=? AND linkid=?', [$tabId, $linkId]);
            if ($db->num_rows($result)) {
                $columnsList = $db->getColumnNames('vtiger_links');
                $isColumnUpdate = false;

                $sql = 'UPDATE vtiger_links SET ';
                $params = [];
                foreach ($linkInfo as $column => $columnValue) {
                    if (in_array($column, $columnsList)) {
                        $columnValue = ($column == 'sequence') ? intval($columnValue) : $columnValue;
                        $column = Vtiger_Util_Helper::validateStringForSql($column);
                        $sql .= "{$column} = ?,";
                        array_push($params, $columnValue);
                        $isColumnUpdate = true;
                    }
                }

                if ($isColumnUpdate) {
                    $sql = trim($sql, ',') . ' WHERE tabid=? AND linkid=?';
                    array_push($params, $tabId, $linkId);
                    $db->pquery($sql, $params);
                }
            }
        }
    }
}
