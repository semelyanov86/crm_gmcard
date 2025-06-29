<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Import_Queue_Action extends Vtiger_Action_Controller
{
    public static $IMPORT_STATUS_NONE = 0;

    public static $IMPORT_STATUS_SCHEDULED = 1;

    public static $IMPORT_STATUS_RUNNING = 2;

    public static $IMPORT_STATUS_HALTED = 3;

    public static $IMPORT_STATUS_COMPLETED = 4;

    public function __construct() {}

    public function process(Vtiger_Request $request) {}

    public static function add($request, $user)
    {
        $db = PearDatabase::getInstance();

        if (!Vtiger_Utils::CheckTable('vtiger_import_queue')) {
            Vtiger_Utils::CreateTable(
                'vtiger_import_queue',
                '(importid INT NOT NULL PRIMARY KEY,
								userid INT NOT NULL,
								tabid INT NOT NULL,
								field_mapping TEXT,
								default_values TEXT,
								merge_type INT,
								merge_fields TEXT,
								status INT default 0,
								lineitem_currency_id INT(5),
								paging INT(1))',
                true,
            );
        }

        if ($request->get('is_scheduled')) {
            $status = self::$IMPORT_STATUS_SCHEDULED;
        } else {
            $status = self::$IMPORT_STATUS_NONE;
        }

        if ($request->get('paging_enabled')) {
            $paging = 1;
        } else {
            $paging = 0;
        }

        $db->pquery(
            'INSERT INTO vtiger_import_queue VALUES(?,?,?,?,?,?,?,?,?,?)',
            [$db->getUniqueID('vtiger_import_queue'),
                $user->id,
                getTabid($request->get('module')),
                Zend_Json::encode($request->get('field_mapping')),
                Zend_Json::encode($request->get('default_values')),
                $request->get('merge_type'),
                Zend_Json::encode($request->get('merge_fields')),
                $status,
                $request->get('lineitem_currency'),
                $paging],
        );
    }

    public static function remove($importId)
    {
        $db = PearDatabase::getInstance();
        if (Vtiger_Utils::CheckTable('vtiger_import_queue')) {
            $db->pquery('DELETE FROM vtiger_import_queue WHERE importid=?', [$importId]);
        }
    }

    public static function removeForUser($user)
    {
        $db = PearDatabase::getInstance();
        if (Vtiger_Utils::CheckTable('vtiger_import_queue')) {
            $db->pquery('DELETE FROM vtiger_import_queue WHERE userid=?', [$user->id]);
        }
    }

    public static function getUserCurrentImportInfo($user)
    {
        $db = PearDatabase::getInstance();

        if (Vtiger_Utils::CheckTable('vtiger_import_queue')) {
            $queueResult = $db->pquery('SELECT * FROM vtiger_import_queue WHERE userid=? LIMIT 1', [$user->id]);

            if ($queueResult && $db->num_rows($queueResult) > 0) {
                $rowData = $db->raw_query_result_rowdata($queueResult, 0);

                return self::getImportInfoFromResult($rowData);
            }
        }

        return null;
    }

    public static function getImportInfo($module, $user)
    {
        $db = PearDatabase::getInstance();

        if (Vtiger_Utils::CheckTable('vtiger_import_queue')) {
            $queueResult = $db->pquery(
                'SELECT * FROM vtiger_import_queue WHERE tabid=? AND userid=?',
                [getTabid($module), $user->id],
            );

            if ($queueResult && $db->num_rows($queueResult) > 0) {
                $rowData = $db->raw_query_result_rowdata($queueResult, 0);

                return self::getImportInfoFromResult($rowData);
            }
        }

        return null;
    }

    public static function getImportInfoById($importId)
    {
        $db = PearDatabase::getInstance();

        if (Vtiger_Utils::CheckTable('vtiger_import_queue')) {
            $queueResult = $db->pquery('SELECT * FROM vtiger_import_queue WHERE importid=?', [$importId]);

            if ($queueResult && $db->num_rows($queueResult) > 0) {
                $rowData = $db->raw_query_result_rowdata($queueResult, 0);

                return self::getImportInfoFromResult($rowData);
            }
        }

        return null;
    }

    public static function getAll($status = false)
    {
        $db = PearDatabase::getInstance();

        $query = 'SELECT * FROM vtiger_import_queue';
        $params = [];
        if ($status !== false) {
            $query .= ' WHERE status = ?';
            array_push($params, $status);
        }
        $result = $db->pquery($query, $params);

        $noOfImports = $db->num_rows($result);
        $scheduledImports = [];
        for ($i = 0; $i < $noOfImports; ++$i) {
            $rowData = $db->raw_query_result_rowdata($result, $i);
            $scheduledImports[$rowData['importid']] = self::getImportInfoFromResult($rowData);
        }

        return $scheduledImports;
    }

    public static function getImportInfoFromResult($rowData)
    {
        return [
            'id' => $rowData['importid'],
            'module' => getTabModuleName($rowData['tabid']),
            'field_mapping' => Zend_Json::decode($rowData['field_mapping']),
            'default_values' => Zend_Json::decode($rowData['default_values']),
            'merge_type' => $rowData['merge_type'],
            'merge_fields' => Zend_Json::decode($rowData['merge_fields']),
            'user_id' => $rowData['userid'],
            'status' => $rowData['status'],
            'lineitem_currency_id' => $rowData['lineitem_currency_id'],
            'paging' => $rowData['paging'],
        ];
    }

    public static function updateStatus($importId, $status)
    {
        $db = PearDatabase::getInstance();
        $db->pquery('UPDATE vtiger_import_queue SET status=? WHERE importid=?', [$status, $importId]);
    }
}
