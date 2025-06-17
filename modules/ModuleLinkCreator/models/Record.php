<?php

include_once "modules/ModuleLinkCreator/models/ModuleLinkCreatorRecord.php";

/**
 * Class ModuleLinkCreator_Record_Model
 */
class ModuleLinkCreator_Record_Model extends ModuleLinkCreator_ModuleLinkCreatorRecord_Model
{
    protected $table_name = "vte_module_link_creator";
    protected $table_index = "id";
    const MODULE_TYPE_ENTITY = 1;
    const MODULE_TYPE_EXTENSION = 2;
    /**
     * static enum: Model::function()
     *
     * @access static
     * @param integer|null $value
     * @return string
     */
    public static function module_types($value = NULL)
    {
        $options = array(self::MODULE_TYPE_ENTITY => vtranslate("Entity", "ModuleLinkCreator"), self::MODULE_TYPE_EXTENSION => vtranslate("Extension", "ModuleLinkCreator"));
        return self::enum($value, $options);
    }
    public static function module_fields()
    {
        $thisInstance = new ModuleLinkCreator_Record_Model();
        $fields = array(array("fieldname" => "recordno", "uitype" => 4, "columnname" => "recordno", "tablename" => $thisInstance->table_name, "generatedtype" => 1, "fieldlabel" => "Record Number", "readonly" => 1, "presence" => 2, "defaultvalue" => NULL, "sequence" => 0, "maximumlength" => 100, "typeofdata" => "V~O", "quickcreate" => 1, "quickcreatesequence" => NULL, "displaytype" => 1, "info_type" => "BAS", "helpinfo" => "<![CDATA[]]>", "masseditable" => 1, "summaryfield" => 1, "entityidentifier" => array("entityidfield" => "id", "entityidcolumn" => "id")), array("fieldname" => "name", "uitype" => 1, "columnname" => "name", "tablename" => $thisInstance->table_name, "generatedtype" => 1, "fieldlabel" => "Name", "readonly" => 1, "presence" => 2, "defaultvalue" => NULL, "sequence" => 1, "maximumlength" => 255, "typeofdata" => "V~O", "quickcreate" => 1, "quickcreatesequence" => NULL, "displaytype" => 1, "info_type" => "BAS", "helpinfo" => "<![CDATA[]]>", "masseditable" => 1, "summaryfield" => 1), array("fieldlabel" => "Created Time"), array("fieldlabel" => "Modified Time"), array("fieldlabel" => "Assigned To"), array("fieldlabel" => "Created By"), array("fieldlabel" => "Last Modified By"), array("fieldlabel" => "Description"));
        return $fields;
    }
    public static function module_module_list_view_filter_fields()
    {
        $fields = array(array("fieldlabel" => "Name"), array("fieldlabel" => "Assigned To"), array("fieldlabel" => "Created Time"), array("fieldlabel" => "Description"));
        return $fields;
    }
    public static function module_module_summary_fields()
    {
        $fields = array(array("fieldlabel" => "Name"), array("fieldlabel" => "Assigned To"), array("fieldlabel" => "Created Time"), array("fieldlabel" => "Description"));
        return $fields;
    }
    public static function module_quick_create_fields()
    {
        $fields = array(array("fieldlabel" => "Name"), array("fieldlabel" => "Assigned To"), array("fieldlabel" => "Description"));
        return $fields;
    }
    public static function module_links()
    {
        $links = array(array("id" => 0, "module_name" => "Updates", "module_label" => "Updates"), array("id" => 0, "module_name" => "Comments", "module_label" => "Comments"), array("id" => 0, "module_name" => "Documents", "module_label" => "Documents"), array("id" => 0, "module_name" => "Activities", "module_label" => "Activities"), array("id" => 0, "module_name" => "Emails", "module_label" => "Emails"));
        return $links;
    }
    /**
     * Function to get the Detail View url for the record
     * @return <String> - Record Detail View Url
     */
    public function getDetailViewUrl()
    {
        $module = $this->getModule();
        return "index.php?module=ModuleLinkCreator&view=" . $module->getDetailViewName() . "&record=" . $this->getId();
    }
    /**
     * @param $id
     * @param $data
     * @return Vtiger_Record_Model
     */
    public function save($id = false, $data = false)
    {
        $adb = PearDatabase::getInstance();
        $sql = NULL;
        $params = array();
        $timestamp = date("Y-m-d H:i:s", time());
        $columnNames = array("status", "created", "updated", "module_id", "module_name", "module_label", "module_type", "module_fields", "module_list_view_filter_fields", "module_summary_fields", "module_quick_create_fields", "module_links", "description", "singular_module_label");
        if ($id) {
            $data = array_merge($data, array("updated" => $timestamp));
            $sqlPart2 = "";
            foreach ($data as $name => $value) {
                if (in_array($name, $columnNames)) {
                    $sqlPart2 .= " " . $name . "=?,";
                }
                $params[] = $value;
            }
            $sqlPart2 = rtrim($sqlPart2, ",");
            $sqlPart3 = "WHERE id=?";
            $params[] = $id;
            $sql = "UPDATE vte_module_link_creator SET " . $sqlPart2 . " " . $sqlPart3;
        } else {
            $data = array_merge($data, array("created" => $timestamp, "updated" => $timestamp));
            $sqlPart2 = " (";
            $sqlPart3 = " (";
            foreach ($data as $name => $value) {
                if (in_array($name, $columnNames)) {
                    $sqlPart2 .= " " . $name . ",";
                    $sqlPart3 .= "?,";
                }
                $params[] = $value;
            }
            $sqlPart2 = rtrim($sqlPart2, ",");
            $sqlPart2 .= ") ";
            $sqlPart3 = rtrim($sqlPart3, ",");
            $sqlPart3 .= ") ";
            $sql = "INSERT INTO vte_module_link_creator " . $sqlPart2 . " VALUES " . $sqlPart3;
        }
        if (!$adb->pquery($sql, $params)) {
            return NULL;
        }
        $recordId = $id ? $id : $adb->getLastInsertID();
        return $this->getById($recordId);
    }
    /**
     * @return array
     */
    public function findAll($options = array())
    {
        $adb = PearDatabase::getInstance();
        $instances = array();
        $rs = $adb->pquery("SELECT * FROM vte_module_link_creator WHERE status = ?", array(self::STATUS_ENABLE));
        if ($adb->num_rows($rs)) {
            while ($data = $adb->fetch_array($rs)) {
                $instances[] = new self($data);
            }
        }
        return $instances;
    }
    /**
     * @param $id
     * @return Vtiger_Record_Model
     */
    public function getById($id)
    {
        $adb = PearDatabase::getInstance();
        $instances = array();
        $sql = "SELECT * FROM vte_module_link_creator WHERE id = ? AND status = ? ORDER BY id LIMIT 1";
        $params = array($id, self::STATUS_ENABLE);
        $rs = $adb->pquery($sql, $params);
        if ($adb->num_rows($rs)) {
            while ($data = $adb->fetch_array($rs)) {
                $instances[] = new self($data);
            }
        }
        return 0 < count($instances) ? $instances[0] : NULL;
    }
    /**
     * @param int $id
     * @return bool
     */
    public function delete($id = false)
    {
        $adb = PearDatabase::getInstance();
        $sql = "UPDATE vte_module_link_creator SET status = ? WHERE id = ?";
        $params = array(self::STATUS_DELETE, $id);
        $result = $adb->pquery($sql, $params);
        return $result ? true : false;
    }
}

?>