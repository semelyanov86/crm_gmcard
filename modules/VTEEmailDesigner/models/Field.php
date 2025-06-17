<?php

class VTEEmailDesigner_Field_Model extends Vtiger_Field_Model
{
    public static $allFields = false;
    public function isViewable()
    {
        return true;
    }
    public static function getAllForModule($moduleModel)
    {
        if (empty($allFields)) {
            $fieldsList = array();
            $firstBlockFields = array("templatename" => "LBL_TEMPLATE_NAME", "description" => "LBL_DESCRIPTION");
            $secondBlockFields = array("subject" => "LBL_SUBJECT");
            $blocks = $moduleModel->getBlocks();
            foreach ($firstBlockFields as $fieldName => $fieldLabel) {
                $fieldModel = new VTEEmailDesigner_Field_Model();
                $blockModel = $blocks["SINGLE_EmailTemplates"];
                $fieldModel->set("name", $fieldName)->set("label", $fieldLabel)->set("block", $blockModel);
                $fieldsList[$blockModel->get("id")][] = $fieldModel;
            }
            foreach ($secondBlockFields as $fieldName => $fieldLabel) {
                $fieldModel = new VTEEmailDesigner_Field_Model();
                $blockModel = $blocks["LBL_EMAIL_TEMPLATE"];
                $fieldModel->set("name", $fieldName)->set("label", $fieldLabel)->set("block", $blockModel);
                $fieldsList[$blockModel->get("id")][] = $fieldModel;
            }
            self::$allFields = $fieldsList;
        }
        return self::$allFields;
    }
    /**
     * Function to check if the field is named field of the module
     * @return <Boolean> - True/False
     */
    public function isNameField()
    {
        return false;
    }
}

?>