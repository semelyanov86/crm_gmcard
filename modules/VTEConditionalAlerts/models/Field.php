<?php

class VTEConditionalAlerts_Field_Model extends Vtiger_Field_Model
{
    /**
     * Function to get all the supported advanced filter operations.
     * @return <Array>
     */
    public static function getAdvancedFilterOptions()
    {
        return ['is' => 'is', 'contains' => 'contains', 'does not contain' => 'does not contain', 'starts with' => 'starts with', 'ends with' => 'ends with', 'is not' => 'is not', 'has changed to' => 'has changed to', 'is empty' => 'is empty', 'is not empty' => 'is not empty', 'less than' => 'less than', 'greater than' => 'greater than', 'does not equal' => 'does not equal', 'less than or equal to' => 'less than or equal to', 'greater than or equal to' => 'greater than or equal to', 'has changed' => 'has changed', 'before' => 'before', 'after' => 'after', 'between' => 'between', 'is added' => 'is added', 'is today' => 'is today', 'less than days ago' => 'less than days ago', 'more than days ago' => 'more than days ago', 'in less than' => 'in less than', 'in more than' => 'in more than', 'days ago' => 'days ago', 'days later' => 'days later', 'is not empty' => 'is not empty'];
    }

    /**
     * Function to get the advanced filter option names by Field type.
     * @return <Array>
     */
    public static function getAdvancedFilterOpsByFieldType()
    {
        return ['string' => ['is', 'contains', 'does not contain', 'starts with', 'ends with', 'has changed', 'is empty', 'is not empty'], 'salutation' => ['is', 'contains', 'does not contain', 'starts with', 'ends with', 'has changed', 'is empty', 'is not empty'], 'text' => ['is', 'contains', 'does not contain', 'starts with', 'ends with', 'has changed', 'is empty', 'is not empty'], 'url' => ['is', 'contains', 'does not contain', 'starts with', 'ends with', 'has changed', 'is empty', 'is not empty'], 'email' => ['is', 'contains', 'does not contain', 'starts with', 'ends with', 'has changed', 'is empty', 'is not empty'], 'phone' => ['is', 'contains', 'does not contain', 'starts with', 'ends with', 'has changed', 'is empty', 'is not empty'], 'integer' => ['equal to', 'less than', 'greater than', 'does not equal', 'less than or equal to', 'greater than or equal to', 'has changed'], 'double' => ['equal to', 'less than', 'greater than', 'does not equal', 'less than or equal to', 'greater than or equal to', 'has changed'], 'currency' => ['equal to', 'less than', 'greater than', 'does not equal', 'less than or equal to', 'greater than or equal to', 'has changed', 'is not empty'], 'picklist' => ['is', 'is not', 'has changed', 'has changed to', 'starts with', 'ends with', 'contains', 'does not contain', 'is empty', 'is not empty'], 'multipicklist' => ['is', 'is not', 'has changed', 'has changed to'], 'datetime' => ['is', 'is not', 'has changed', 'less than hours before', 'less than hours later', 'more than hours before', 'more than hours later', 'is not empty'], 'time' => ['is', 'is not', 'has changed', 'is not empty', 'is empty'], 'date' => ['is', 'is not', 'has changed', 'between', 'before', 'after', 'is today', 'less than days ago', 'more than days ago', 'in less than', 'in more than', 'days ago', 'days later', 'is not empty', 'is empty'], 'boolean' => ['is', 'is not', 'has changed'], 'reference' => ['has changed', 'is empty'], 'owner' => ['has changed', 'is', 'is not'], 'roles' => ['is', 'is not'], 'recurrence' => ['is', 'is not', 'has changed'], 'comment' => ['is added'], 'image' => ['is', 'is not', 'contains', 'does not contain', 'starts with', 'ends with', 'is empty', 'is not empty'], 'percentage' => ['equal to', 'less than', 'greater than', 'does not equal', 'less than or equal to', 'greater than or equal to', 'has changed', 'is not empty']];
    }

    /**
     * Function to get comment field which will useful in creating conditions.
     * @param <Vtiger_Module_Model> $moduleModel
     * @return <Vtiger_Field_Model>
     */
    public static function getCommentFieldForFilterConditions($moduleModel)
    {
        $commentField = new Vtiger_Field_Model();
        $commentField->set('name', '_VT_add_comment');
        $commentField->set('label', 'Comment');
        $commentField->setModule($moduleModel);
        $commentField->fieldDataType = 'comment';

        return $commentField;
    }

    /**
     * Function to get comment fields list which are useful in tasks.
     * @param <Vtiger_Module_Model> $moduleModel
     * @return <Array> list of Field models <Vtiger_Field_Model>
     */
    public static function getCommentFieldsListForTasks($moduleModel)
    {
        $commentsFieldsInfo = ['lastComment' => 'Last Comment', 'last5Comments' => 'Last 5 Comments', 'allComments' => 'All Comments'];
        $commentFieldModelsList = [];
        foreach ($commentsFieldsInfo as $fieldName => $fieldLabel) {
            $commentField = new Vtiger_Field_Model();
            $commentField->setModule($moduleModel);
            $commentField->set('name', $fieldName);
            $commentField->set('label', $fieldLabel);
            $commentFieldModelsList[$fieldName] = $commentField;
        }

        return $commentFieldModelsList;
    }

    public static function getRoles()
    {
        $adb = PearDatabase::getInstance();
        $rs = $adb->pquery('SELECT * FROM `vtiger_role`', []);
        $noOfEntries = $adb->num_rows($rs);
        $list_entries = [];
        for ($i = 0; $i <= $noOfEntries - 1; ++$i) {
            $row = $adb->query_result_rowdata($rs, $i);
            $list_entries[$row['roleid']] = $row['rolename'];
        }

        return $list_entries;
    }
}
