<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class ModTracker_Record_Model extends Vtiger_Record_Model
{
    public const UPDATE = 0;
    public const DELETE = 1;
    public const CREATE = 2;
    public const RESTORE = 3;
    public const LINK = 4;
    public const UNLINK = 5;

    /**
     * Function to get the history of updates on a record.
     * @param <type> $record - Record model
     * @param <type> $limit - number of latest changes that need to retrieved
     * @return <array> - list of  ModTracker_Record_Model
     */
    public static function getUpdates($parentRecordId, $pagingModel, $moduleName)
    {
        if ($moduleName == 'Calendar') {
            if (getActivityType($parentRecordId) != 'Task') {
                $moduleName = 'Events';
            }
        }
        $db = PearDatabase::getInstance();
        $recordInstances = [];

        $startIndex = $pagingModel->getStartIndex();
        $pageLimit = $pagingModel->getPageLimit();

        $listQuery = 'SELECT * FROM vtiger_modtracker_basic WHERE crmid = ? AND module = ? '
                        . " ORDER BY changedon DESC LIMIT {$startIndex}, {$pageLimit}";

        $result = $db->pquery($listQuery, [$parentRecordId, $moduleName]);
        $rows = $db->num_rows($result);

        for ($i = 0; $i < $rows; ++$i) {
            $row = $db->query_result_rowdata($result, $i);
            $recordInstance = new self();
            $recordInstance->setData($row)->setParent($row['crmid'], $row['module']);
            $recordInstances[] = $recordInstance;
        }

        return $recordInstances;
    }

    public function setParent($id, $moduleName)
    {
        if (!Vtiger_Util_Helper::checkRecordExistance($id)) {
            $this->parent = Vtiger_Record_Model::getInstanceById($id, $moduleName);
        } else {
            $this->parent = Vtiger_Record_Model::getCleanInstance($moduleName);
            $this->parent->id = $id;
            $this->parent->setId($id);
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function checkStatus($callerStatus)
    {
        $status = $this->get('status');
        if ($status == $callerStatus) {
            return true;
        }

        return false;
    }

    public function isCreate()
    {
        return $this->checkStatus(self::CREATE);
    }

    public function isUpdate()
    {
        return $this->checkStatus(self::UPDATE);
    }

    public function isDelete()
    {
        return $this->checkStatus(self::DELETE);
    }

    public function isRestore()
    {
        return $this->checkStatus(self::RESTORE);
    }

    public function isRelationLink()
    {
        return $this->checkStatus(self::LINK);
    }

    public function isRelationUnLink()
    {
        return $this->checkStatus(self::UNLINK);
    }

    public function getModifiedBy()
    {
        $changeUserId = $this->get('whodid');

        return Users_Record_Model::getInstanceById($changeUserId, 'Users');
    }

    public function getActivityTime()
    {
        return $this->get('changedon');
    }

    public function getFieldInstances()
    {
        $id = $this->get('id');
        $db = PearDatabase::getInstance();

        $fieldInstances = [];
        if ($this->isCreate() || $this->isUpdate()) {
            $result = $db->pquery('SELECT * FROM vtiger_modtracker_detail WHERE id = ?', [$id]);
            $rows = $db->num_rows($result);
            for ($i = 0; $i < $rows; ++$i) {
                $data = $db->query_result_rowdata($result, $i);
                $row = array_map('decode_html', $data);

                if ($row['fieldname'] == 'record_id' || $row['fieldname'] == 'record_module') {
                    continue;
                }

                $fieldModel = Vtiger_Field_Model::getInstance($row['fieldname'], $this->getParent()->getModule());
                if (!$fieldModel) {
                    continue;
                }

                $fieldInstance = new ModTracker_Field_Model();
                $fieldInstance->setData($row)->setParent($this)->setFieldInstance($fieldModel);
                $fieldInstances[] = $fieldInstance;
            }
        }

        return $fieldInstances;
    }

    public function getRelationInstance()
    {
        $id = $this->get('id');
        $db = PearDatabase::getInstance();

        if ($this->isRelationLink() || $this->isRelationUnLink()) {
            $result = $db->pquery('SELECT * FROM vtiger_modtracker_relations WHERE id = ?', [$id]);
            $row = $db->query_result_rowdata($result, 0);
            $relationInstance = new ModTracker_Relation_Model();
            $relationInstance->setData($row)->setParent($this);
        }

        return $relationInstance;
    }

    public static function getTotalRecordCount($recordId)
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT COUNT(*) AS count FROM vtiger_modtracker_basic WHERE crmid = ?', [$recordId]);

        return $db->query_result($result, 0, 'count');
    }
}
