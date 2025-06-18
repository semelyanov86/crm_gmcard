<?php

include_once 'modules/ModuleLinkCreator/models/ModuleLinkCreatorRecord.php';

/**
 * Class ModuleLinkCreator_Record_Model.
 */
class ModuleLinkCreator_Record_Model extends ModuleLinkCreator_ModuleLinkCreatorRecord_Model
{
    protected $table_name = 'vte_module_link_creator';

    protected $table_index = 'id';
    public const MODULE_TYPE_ENTITY = 1;
    public const MODULE_TYPE_EXTENSION = 2;

    /**
     * static enum: Model::function().
     *
     * @param int|null $value
     * @return string
     */
    public static function module_types($value = null)
    {
        $options = [self::MODULE_TYPE_ENTITY => vtranslate('Entity', 'ModuleLinkCreator'), self::MODULE_TYPE_EXTENSION => vtranslate('Extension', 'ModuleLinkCreator')];

        return self::enum($value, $options);
    }

    public static function module_fields()
    {
        $thisInstance = new ModuleLinkCreator_Record_Model();
        $fields = [['fieldname' => 'recordno', 'uitype' => 4, 'columnname' => 'recordno', 'tablename' => $thisInstance->table_name, 'generatedtype' => 1, 'fieldlabel' => 'Record Number', 'readonly' => 1, 'presence' => 2, 'defaultvalue' => null, 'sequence' => 0, 'maximumlength' => 100, 'typeofdata' => 'V~O', 'quickcreate' => 1, 'quickcreatesequence' => null, 'displaytype' => 1, 'info_type' => 'BAS', 'helpinfo' => '<![CDATA[]]>', 'masseditable' => 1, 'summaryfield' => 1, 'entityidentifier' => ['entityidfield' => 'id', 'entityidcolumn' => 'id']], ['fieldname' => 'name', 'uitype' => 1, 'columnname' => 'name', 'tablename' => $thisInstance->table_name, 'generatedtype' => 1, 'fieldlabel' => 'Name', 'readonly' => 1, 'presence' => 2, 'defaultvalue' => null, 'sequence' => 1, 'maximumlength' => 255, 'typeofdata' => 'V~O', 'quickcreate' => 1, 'quickcreatesequence' => null, 'displaytype' => 1, 'info_type' => 'BAS', 'helpinfo' => '<![CDATA[]]>', 'masseditable' => 1, 'summaryfield' => 1], ['fieldlabel' => 'Created Time'], ['fieldlabel' => 'Modified Time'], ['fieldlabel' => 'Assigned To'], ['fieldlabel' => 'Created By'], ['fieldlabel' => 'Last Modified By'], ['fieldlabel' => 'Description']];

        return $fields;
    }

    public static function module_module_list_view_filter_fields()
    {
        $fields = [['fieldlabel' => 'Name'], ['fieldlabel' => 'Assigned To'], ['fieldlabel' => 'Created Time'], ['fieldlabel' => 'Description']];

        return $fields;
    }

    public static function module_module_summary_fields()
    {
        $fields = [['fieldlabel' => 'Name'], ['fieldlabel' => 'Assigned To'], ['fieldlabel' => 'Created Time'], ['fieldlabel' => 'Description']];

        return $fields;
    }

    public static function module_quick_create_fields()
    {
        $fields = [['fieldlabel' => 'Name'], ['fieldlabel' => 'Assigned To'], ['fieldlabel' => 'Description']];

        return $fields;
    }

    public static function module_links()
    {
        $links = [['id' => 0, 'module_name' => 'Updates', 'module_label' => 'Updates'], ['id' => 0, 'module_name' => 'Comments', 'module_label' => 'Comments'], ['id' => 0, 'module_name' => 'Documents', 'module_label' => 'Documents'], ['id' => 0, 'module_name' => 'Activities', 'module_label' => 'Activities'], ['id' => 0, 'module_name' => 'Emails', 'module_label' => 'Emails']];

        return $links;
    }

    /**
     * Function to get the Detail View url for the record.
     * @return <String> - Record Detail View Url
     */
    public function getDetailViewUrl()
    {
        $module = $this->getModule();

        return 'index.php?module=ModuleLinkCreator&view=' . $module->getDetailViewName() . '&record=' . $this->getId();
    }

    /**
     * @return Vtiger_Record_Model
     */
    public function save($id = false, $data = false)
    {
        $adb = PearDatabase::getInstance();
        $sql = null;
        $params = [];
        $timestamp = date('Y-m-d H:i:s', time());
        $columnNames = ['status', 'created', 'updated', 'module_id', 'module_name', 'module_label', 'module_type', 'module_fields', 'module_list_view_filter_fields', 'module_summary_fields', 'module_quick_create_fields', 'module_links', 'description', 'singular_module_label'];
        if ($id) {
            $data = array_merge($data, ['updated' => $timestamp]);
            $sqlPart2 = '';
            foreach ($data as $name => $value) {
                if (in_array($name, $columnNames)) {
                    $sqlPart2 .= ' ' . $name . '=?,';
                }
                $params[] = $value;
            }
            $sqlPart2 = rtrim($sqlPart2, ',');
            $sqlPart3 = 'WHERE id=?';
            $params[] = $id;
            $sql = 'UPDATE vte_module_link_creator SET ' . $sqlPart2 . ' ' . $sqlPart3;
        } else {
            $data = array_merge($data, ['created' => $timestamp, 'updated' => $timestamp]);
            $sqlPart2 = ' (';
            $sqlPart3 = ' (';
            foreach ($data as $name => $value) {
                if (in_array($name, $columnNames)) {
                    $sqlPart2 .= ' ' . $name . ',';
                    $sqlPart3 .= '?,';
                }
                $params[] = $value;
            }
            $sqlPart2 = rtrim($sqlPart2, ',');
            $sqlPart2 .= ') ';
            $sqlPart3 = rtrim($sqlPart3, ',');
            $sqlPart3 .= ') ';
            $sql = 'INSERT INTO vte_module_link_creator ' . $sqlPart2 . ' VALUES ' . $sqlPart3;
        }
        if (!$adb->pquery($sql, $params)) {
            return null;
        }
        $recordId = $id ? $id : $adb->getLastInsertID();

        return $this->getById($recordId);
    }

    /**
     * @return array
     */
    public function findAll($options = [])
    {
        $adb = PearDatabase::getInstance();
        $instances = [];
        $rs = $adb->pquery('SELECT * FROM vte_module_link_creator WHERE status = ?', [self::STATUS_ENABLE]);
        if ($adb->num_rows($rs)) {
            while ($data = $adb->fetch_array($rs)) {
                $instances[] = new self($data);
            }
        }

        return $instances;
    }

    /**
     * @return Vtiger_Record_Model
     */
    public function getById($id)
    {
        $adb = PearDatabase::getInstance();
        $instances = [];
        $sql = 'SELECT * FROM vte_module_link_creator WHERE id = ? AND status = ? ORDER BY id LIMIT 1';
        $params = [$id, self::STATUS_ENABLE];
        $rs = $adb->pquery($sql, $params);
        if ($adb->num_rows($rs)) {
            while ($data = $adb->fetch_array($rs)) {
                $instances[] = new self($data);
            }
        }

        return count($instances) > 0 ? $instances[0] : null;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete($id = false)
    {
        $adb = PearDatabase::getInstance();
        $sql = 'UPDATE vte_module_link_creator SET status = ? WHERE id = ?';
        $params = [self::STATUS_DELETE, $id];
        $result = $adb->pquery($sql, $params);

        return $result ? true : false;
    }
}
