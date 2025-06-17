<?php

class VTEEmailDesigner_Block_Model extends Vtiger_Block_Model
{
    public function getFields()
    {
        if (empty($this->fields)) {
            $moduleFields = VTEEmailDesigner_Field_Model::getAllForModule($this->module);
            $this->fields = array();
            $fieldsList = $moduleFields[$this->id];
            if (!is_array($moduleFields[$this->id])) {
                $moduleFields[$this->id] = array();
            }
            foreach ($moduleFields[$this->id] as $field) {
                $this->fields[$field->get("name")] = $field;
            }
        }
        return $this->fields;
    }
}

?>