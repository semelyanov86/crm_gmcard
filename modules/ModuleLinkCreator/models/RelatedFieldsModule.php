<?php

include_once "modules/ModuleLinkCreator/models/ModuleLinkCreatorModule.php";

/**
 * Class ModuleLinkCreator_RelatedFieldsModule_Model
 */
class ModuleLinkCreator_RelatedFieldModule_Model extends ModuleLinkCreatorModule_Module_Model
{
    /**
     * Function that validates is a related list already exists between the two modules
     *
     * @param string $module1 Related Module
     * @param string $module2 Module
     * @param string $addRelatedList Whether the users request or not to create a new related list
     * @return boolean Return false is a new related list should not be created
     */
    public function validateRelatedList($module1, $module2, $addRelatedList)
    {
        $db = PearDatabase::getInstance();
        include_once "vtlib/Vtiger/Module.php";
        $module1 = Vtiger_Module::getInstance($module1);
        $module2 = Vtiger_Module::getInstance($module2);
        $sql = "SELECT * FROM `vtiger_relatedlists` WHERE `tabid`= ? AND `related_tabid`= ?";
        $result = $db->pquery($sql, array($module2->id, $module1->id));
        if (0 < $db->num_rows($result) && $addRelatedList == "new") {
            return false;
        }
        return true;
    }
    public function validateFieldLabel($module1, $fieldLabel)
    {
        $db = PearDatabase::getInstance();
        include_once "vtlib/Vtiger/Module.php";
        $module1 = Vtiger_Module::getInstance($module1);
        $rs = $db->pquery("SELECT `field`.fieldname FROM `vtiger_field` AS `field`\r\n                        INNER JOIN `vtiger_tab` AS `tab` ON field.tabid = `tab`.tabid\r\n                        where field.tabid = ? AND field.fieldlabel = ?", array($module1->id, $fieldLabel));
        if (0 < $db->num_rows($rs)) {
            return false;
        }
        return true;
    }
    /**
     * Function that add the required field. Vtlib
     *
     * @param Vtiger_Request $request
     * @return int
     */
    public function addField(Vtiger_Request $request, $actionRelatedList = false)
    {
        include_once "vtlib/Vtiger/Module.php";
        $module1 = $request->get("module1");
        $module2 = $request->get("module2");
        $block = $request->get("block");
        $fieldLabel = $request->get("field_label");
        $module = Vtiger_Module::getInstance($module1);
        $module1Class = Vtiger_Module::getClassInstance($module1);
        $block1 = Vtiger_Block::getInstance($block, $module);
        $columnName = "";
        if ($actionRelatedList == true) {
            $columnName = "cf_nrl_" . strtolower($module2) . rand(1, 1000) . "_id";
        } else {
            $columnName = "cf_" . strtolower($module2) . "_id";
        }
        $fieldName = $columnName;
        $field1 = new Vtiger_Field();
        $field1->label = $fieldLabel;
        $field1->name = $fieldName;
        $field1->table = $module1Class->table_name;
        $field1->column = $fieldName;
        $field1->generatedtype = 2;
        $field1->columntype = "INT(10)";
        $field1->uitype = 10;
        $field1->typeofdata = "I~O";
        $block1->addField($field1);
        $field1->setRelatedModules(array($module2));
        $block1->save($module);
        return $field1->id;
    }
    /**
     * @param $module1
     * @param $module2
     * @param $relListLabel
     */
    public function addRelatedList($module1, $module2, $relListLabel, $actions)
    {
        include_once "vtlib/Vtiger/Module.php";
        $module = Vtiger_Module::getInstance($module2);
        $module->setRelatedList(Vtiger_Module::getInstance($module1), $relListLabel, $actions, "get_dependents_list");
    }
    /**
     * Function that validates is a related field is already there - UIType 10 type fields
     *
     * @param string $module1
     * @param string $module2
     * @return boolean returns true is a related field is already there
     */
    public function validateField($module1, $module2)
    {
        $db = PearDatabase::getInstance();
        $sql = "SELECT vtiger_tab.tablabel as module1, vtiger_field.fieldlabel, vtiger_field.presence, vtiger_fieldmodulerel.relmodule as module2\r\n                    FROM vtiger_field \r\n                    LEFT JOIN vtiger_fieldmodulerel ON vtiger_field.fieldid = vtiger_fieldmodulerel.fieldid\r\n                    INNER JOIN vtiger_tab ON vtiger_field.tabid = vtiger_tab.tabid\r\n                    WHERE uitype IN (10)\r\n                    AND vtiger_tab.tablabel = ?\r\n                    AND vtiger_fieldmodulerel.relmodule = ?";
        $result = $db->pquery($sql, array($module1, $module2));
        if (0 < $db->num_rows($result)) {
            return true;
        }
        return false;
    }
    /**
     * Function that validates is a related field is already there - Custom UIType type fields
     *
     * @param string $module1
     * @param string $module2
     * @return boolean returns true is a related field is already there
     */
    public function validateCustomBuildFields($module1, $module2)
    {
        $db = PearDatabase::getInstance();
        $relatedUITypes = array("Accounts" => "73,51", "Contacts" => "57", "Users" => "101", "SalesOrder" => "80", "Potentials" => "76", "Products" => "59", "Vendors" => "75", "Campaigns" => "58");
        if (array_key_exists($module2, $relatedUITypes)) {
            $customRelatedUITYpe = $relatedUITypes[$module2];
            $sql = "SELECT vtiger_tab.tablabel as module1, vtiger_field.fieldlabel, vtiger_field.presence\r\n                    FROM vtiger_field \r\n                    INNER JOIN vtiger_tab ON vtiger_field.tabid = vtiger_tab.tabid";
            if ($module2 == "Accounts") {
                $sql .= " WHERE uitype IN (73,51) AND vtiger_tab.tablabel = ?";
                $result = $db->pquery($sql, array($module1));
            } else {
                $sql .= " WHERE uitype = ? AND vtiger_tab.tablabel = ?";
                $result = $db->pquery($sql, array($customRelatedUITYpe, $module1));
            }
            if (0 < $db->num_rows($result)) {
                return true;
            }
            return false;
        }
        return false;
    }
    /**
     * @param $module2
     * @param $module1
     * @param $relListLabel
     * @return bool|mixed|string
     * @throws Exception
     */
    public function getRLId($module2, $module1, $relListLabel)
    {
        $db = PearDatabase::getInstance();
        $module1 = Vtiger_Module::getInstance($module1);
        $module2 = Vtiger_Module::getInstance($module2);
        $sql = "SELECT relation_id FROM vtiger_relatedlists WHERE tabid=? AND related_tabid=? AND label=?";
        $result = $db->pquery($sql, array($module2->id, $module1->id, $relListLabel));
        if (0 < $db->num_rows($result)) {
            return $db->query_result($result, 0, "relation_id");
        }
        return false;
    }
}

?>