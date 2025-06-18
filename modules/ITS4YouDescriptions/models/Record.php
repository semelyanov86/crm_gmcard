<?php

/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

class ITS4YouDescriptions_Record_Model extends Vtiger_Record_Model
{
    public static function getTemplatesForModule($moduleName)
    {
        $adb = PearDatabase::getInstance();
        $Templates = [];
        $res = $adb->pquery("SELECT descriptionid, descriptionname FROM its4you_descriptions INNER JOIN vtiger_crmentity ON crmid=descriptionid AND deleted=0 WHERE desc4youmodule IN ('Global', ?)", [$moduleName]);
        $num_rows = $adb->num_rows($res);

        while ($row = $adb->fetchByAssoc($res)) {
            if ($num_rows == 1) {
                $row['is_default'] = 1;
            }
            $Templates[$row['descriptionid']] = $row;
        }

        return $Templates;
    }

    /**
     * @param string $moduleName
     * @return array
     */
    public static function getTextareasForModule($moduleName)
    {
        $textAreas = [];

        if (ITS4YouDescriptions_AllowedModules_Model::isAllowed($moduleName)) {
            $textAreas = ITS4YouDescriptions_AllowedModules_Model::getTextareaFields($moduleName);
        }

        return $textAreas;
    }

    /**
     * @param string $name
     * @return array
     */
    public static function getQueryDataForModule($name)
    {
        $sql = ITS4YouDescriptions_Record_Model::getFieldLabelQuery();

        if ($name === 'Calendar') {
            $sql .= ' AND (tabid=? OR tabid=?)';
            $params = [
                getTabId($name),
                getTabId('Events'),
            ];
        } else {
            $sql .= ' AND tabid=?';
            $params = [getTabId($name)];
        }

        return [
            'query' => $sql,
            'params' => $params,
        ];
    }

    /**
     * @return string
     */
    public static function getFieldLabelQuery()
    {
        return "SELECT tabid, fieldid, fieldname, fieldlabel FROM vtiger_field WHERE uitype IN ('19','20','21') AND displaytype='1' AND fieldname NOT IN ('bill_street', 'ship_street', 'lane', 'mailingstreet', 'otherstreet', 'comment', 'street', 'update_log') ";
    }

    public static function getTemplateDescription($templateid)
    {
        $adb = PearDatabase::getInstance();

        $return = '';
        $res = $adb->pquery('SELECT description FROM vtiger_crmentity WHERE crmid=? AND deleted=0', [$templateid]);

        if ($adb->num_rows($res) === 1) {
            $row = $adb->fetchByAssoc($res);
            $return = html_entity_decode($row['description'], ENT_COMPAT, 'utf-8');
        }

        return $return;
    }

    public function get($key)
    {
        $value = parent::get($key);

        if ($key === 'description') {
            return decode_html($value);
        }

        return $value;
    }

    /**
     * @throws Exception
     */
    public function updateSettingFields()
    {
        $moduleName = $this->get('desc4youmodule');

        if ($moduleName !== 'Global') {
            $allowedFields = ITS4YouDescriptions_AllowedFields_Model::getInstance($moduleName);
            $fields = $allowedFields->getFields();
            $fields[] = $this->getFieldByLabel($this->get('desc4youfield'));
            $allowedFields->set('fields', array_unique(array_filter($fields)));
            $allowedFields->save();
        }
    }

    /**
     * @throws Exception
     */
    public function save()
    {
        $this->getModule()->saveRecord($this);
        $this->updateSettingFields();
    }

    public function getFieldByLabel($label)
    {
        $adb = PearDatabase::getInstance();
        $result = $adb->pquery('SELECT fieldname FROM vtiger_field WHERE tabid=? AND fieldlabel LIKE ?', [getTabid($this->get('desc4youmodule')), '%' . $label . '%']);

        return (string) $adb->query_result($result, 0, 'fieldname');
    }
}
