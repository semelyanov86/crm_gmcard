<?php

/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class ITS4YouDescriptions_AllowedFields_Model extends Vtiger_Base_Model
{
    /**
     * @var PearDatabase
     */
    protected $db;

    /**
     * @param string $module
     * @return ITS4YouDescriptions_AllowedFields_Model
     * @throws Exception
     */
    public static function getInstance($module)
    {
        $self = new self();
        $self->db = PearDatabase::getInstance();
        $self->set('module', $module);
        $self->retrieveData();

        return $self;
    }

    /**
     * @throws Exception
     */
    public function retrieveData()
    {
        $result = $this->db->pquery(
            'SELECT * FROM its4you_descriptions_settings WHERE module_name=?',
            [$this->get('module')],
        );

        foreach ((array) $this->db->query_result_rowdata($result) as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @return array
     */
    public function getFieldsData()
    {
        $result = $this->getFieldsResult();
        $fields = [];

        while ($row = $this->db->fetchByAssoc($result)) {
            $row['fieldlabel'] = html_entity_decode($row['fieldlabel'], ENT_COMPAT, 'utf-8');
            $fields[] = $row;
        }

        return $fields;
    }

    /**
     * @return array
     */
    public function getUniqueFieldsData()
    {
        $values = $this->getFieldsData();
        $usedFields = [];
        $uniqueValues = [];

        foreach ($values as $key => $value) {
            if (!in_array($value['fieldlabel'], $usedFields)) {
                $usedFields[] = $value['fieldlabel'];
                $uniqueValues[] = $value;
            }
        }

        return $uniqueValues;
    }

    public function getUniqueFieldsDataById($record)
    {
        $recordModel = Vtiger_Record_Model::getInstanceById($record);
        $uniqueValues = $this->getUniqueFieldsData();

        foreach ($uniqueValues as $key => $uniqueValue) {
            $fieldValue = decode_html($recordModel->getDisplayValue($uniqueValue['fieldname']));
            $uniqueValues[$key]['fieldvalue'] = $fieldValue;
        }

        return $uniqueValues;
    }

    public function getFieldsResult()
    {
        $queryData = ITS4YouDescriptions_Record_Model::getQueryDataForModule($this->get('module'));
        $sql = $queryData['query'];
        $params = $queryData['params'];

        if (!$this->has('fields')) {
            $params[] = 'description';
            $params[] = 'terms_conditions';
            $sql .= ' AND fieldname IN (?,?) ';
        } else {
            $fields = $this->getFields();
            $fieldsSql = [];

            foreach ($fields as $field) {
                $params[] = $field;
                $fieldsSql[] = ' fieldname = ? ';
            }

            $sql .= ' AND (' . implode(' OR ', $fieldsSql) . ')';
        }

        return $this->db->pquery($sql, $params);
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return explode(',', $this->get('fields'));
    }

    /**
     * @param string $field
     * @return bool
     */
    public function isAllowed($field)
    {
        return in_array($field, $this->getFieldsName());
    }

    /**
     * @return array
     */
    public function getFieldsName()
    {
        $result = $this->getFieldsResult();
        $fields = [];

        while ($row = $this->db->fetchByAssoc($result)) {
            $fields[$row['fieldid']] = $row['fieldname'];
        }

        return $fields;
    }

    /**
     * @throws Exception
     */
    public function save()
    {
        $params = [
            implode(',', (array) $this->get('fields')),
            $this->get('module'),
        ];

        if ($this->get('id')) {
            $sql = 'UPDATE its4you_descriptions_settings SET fields=? WHERE module_name=?';
        } else {
            $sql = 'INSERT INTO its4you_descriptions_settings (fields, module_name) VALUES (?,?)';
        }

        $this->db->pquery($sql, $params);
        $this->retrieveData();
    }
}
