<?php

/**
 * Class ModuleLinkCreator_ModuleLinkCreatorRecord_Model
 */
class ModuleLinkCreator_ModuleLinkCreatorRecord_Model extends Vtiger_Record_Model
{
    protected $table_name = "";
    protected $table_index = "id";
    const STATUS_DELETE = 0;
    const STATUS_ENABLE = 1;
    const STATUS_DISABLE = 0;
    /**
     * static enums
     * @access static
     *
     * @param mixed $value
     * @param array $options
     * @param string $default
     * @return string
     */
    public static function enum($value, $options, $default = "")
    {
        if ($value !== NULL) {
            if (array_key_exists($value, $options)) {
                return $options[$value];
            }
            return $default;
        }
        return $options;
    }
    /**
     * static enum: Model::function()
     *
     * @access static
     * @param integer|null $value
     * @return string
     */
    public static function statuses($value = NULL)
    {
        $options = array(self::STATUS_DELETE => vtranslate("Delete", "ModuleLinkCreator"), self::STATUS_ENABLE => vtranslate("Enable", "ModuleLinkCreator"), self::STATUS_DISABLE => vtranslate("Disable", "ModuleLinkCreator"));
        return self::enum($value, $options);
    }
    /**
     * Function to get the Detail View url for the record
     * @return <String> - Record Detail View Url
     */
    public function getDetailViewUrl()
    {
        $module = $this->getModule();
        return "index.php?module=Calendar&view=" . $module->getDetailViewName() . "&record=" . $this->getId();
    }
    /**
     * @param array $conditions
     * @return string
     */
    protected function parseConditions($conditions = array())
    {
        if (!$conditions || !is_array($conditions) || empty($conditions)) {
            return "";
        }
        $strCondition = "";
        $exampleConditions1 = array("id = 1", array("id" => 1, "template_id" => 2), "OR" => array("id" => 1, "template_id" => 2));
        $exampleConditions2 = array("AND" => array("id" => 1, "template_id" => 2), "OR" => array("id" => 1, "template_id" => 2, "AND" => array("id" => 1, "template_id" => 2)));
        foreach ($conditions as $key => $condition) {
            if (!$condition) {
                continue;
            }
            $key = uppercase($key);
            switch ($key) {
                case "AND":
                case "OR":
                    if (is_string($condition)) {
                        $condition = array($condition);
                    }
                    if (count($condition) <= 1) {
                        $strCondition .= " (" . $condition . ")";
                    } else {
                        $tmpCondition = "(";
                        foreach ($condition as $c) {
                            $tmpCondition .= " " . $c . " " . $key;
                        }
                        $tmpCondition = rtrim($tmpCondition, $key);
                        $tmpCondition .= ")";
                        $strCondition .= " " . $tmpCondition;
                    }
                    break;
                case "NOT":
                case "NOT IN":
                    if (is_string($condition)) {
                        $condition = rtrim($condition, "()");
                        $condition = array_map("trim", explode(",", $condition));
                    }
                    $strCondition = " " . $key . " " . implode(",", $condition) . ")";
                    break;
                default:
                    if (is_array($condition)) {
                        $strCondition .= $this->parseConditions($condition);
                    } else {
                        $strCondition .= " AND " . $condition;
                    }
                    break;
            }
        }
        return $strCondition;
    }
    /**
     * @param array $fields
     * @return string
     */
    protected function parseFields($fields = array())
    {
        $strField = "";
        if ($fields && is_array($fields) && !empty($fields)) {
            foreach ($fields as $field) {
                $strField .= " " . $field . ",";
            }
            $strField = rtrim($strField, ",");
        }
        return $strField;
    }
    /**
     * @param array $options
     * @return array
     */
    public function findAll($options = array())
    {
        $db = PearDatabase::getInstance();
        $instances = array();
        $fields = $options["fields"] ? $options["fields"] : array();
        $conditions = $options["conditions"] ? $options["conditions"] : array();
        $sql = "SELECT";
        $strField = $this->parseFields($fields);
        $sql .= $strField == "" ? " *" : $strField;
        $sql .= " FROM `" . $this->table_name . "` WHERE `deleted` != 1";
        if (!$conditions || empty($conditions)) {
            $sql .= !$conditions ? "" : $this->parseConditions();
        }
        $rs = $db->pquery($sql);
        if ($db->num_rows($rs)) {
            while ($data = $db->fetch_array($rs)) {
                $instances[] = new self($data);
            }
        }
        return $instances;
    }
}

?>