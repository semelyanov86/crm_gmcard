<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

/**
 * Calendar Field Model Class.
 */
class Calendar_Field_Model extends Vtiger_Field_Model
{
    /**
     * Function returns special validator for fields.
     * @return <Array>
     */
    public function getValidator()
    {
        $validator = [];
        $fieldName = $this->getName();

        switch ($fieldName) {
            case 'due_date':	$funcName = ['name' => 'greaterThanDependentField',
                'params' => ['date_start']];
                array_push($validator, $funcName);
                break;
            case 'eventstatus':	$funcName = ['name' => 'futureEventCannotBeHeld',
                'params' => ['date_start']];
                array_push($validator, $funcName);
                break;
                // NOTE: Letting user to add pre or post dated Event.
                /*case 'date_start' : $funcName = array('name'=>'greaterThanToday');
                                    array_push($validator, $funcName);
                                    break;*/
            case 'recurringtype':	$funcName = ['name' => 'greaterThanDependentRepeatTillDate',
                'params' => ['date_start']];
                array_push($validator, $funcName);
                break;

            default:	$validator = parent::getValidator();
                break;
        }

        return $validator;
    }

    /**
     * Function to get the Webservice Field data type.
     * @return <String> Data type of the field
     */
    public function getFieldDataType()
    {
        if ($this->getName() == 'date_start' || $this->getName() == 'due_date') {
            return 'datetime';
        }
        if ($this->get('uitype') == '30') {
            return 'reminder';
        }
        if ($this->getName() == 'recurringtype') {
            return 'recurrence';
        }
        if ($this->get('uitype') == '9') {
            return 'percentage';
        }
        $webserviceField = $this->getWebserviceFieldObject();

        return $webserviceField->getFieldDataType();
    }

    /**
     * Customize the display value for detail view.
     */
    public function getDisplayValue($value, $record = false, $recordInstance = false)
    {
        if ($recordInstance) {
            if ($this->getName() == 'date_start') {
                $dateTimeValue = $value . ' ' . $recordInstance->get('time_start');
                $value = $this->getUITypeModel()->getDisplayValue($dateTimeValue);
                [$startDate, $startTime] = explode(' ', $value);

                $currentUser = Users_Record_Model::getCurrentUserModel();
                if ($currentUser->get('hour_format') == '12') {
                    $startTime = Vtiger_Time_UIType::getTimeValueInAMorPM($startTime);
                }

                return $startDate . ' ' . $startTime;
            }
            if ($this->getName() == 'due_date') {
                $dateTimeValue = $value . ' ' . $recordInstance->get('time_end');
                $value = $this->getUITypeModel()->getDisplayValue($dateTimeValue);
                if (substr_count($value, ' ') == 0) {
                    $value .= ' ';
                } // if $startTime is missing then added a space to it to avoid undefined array key warning.
                [$startDate, $startTime] = explode(' ', $value);

                $currentUser = Users_Record_Model::getCurrentUserModel();
                if ($currentUser->get('hour_format') == '12') {
                    $startTime = Vtiger_Time_UIType::getTimeValueInAMorPM($startTime);
                }

                return $startDate . ' ' . $startTime;
            }
        }

        return parent::getDisplayValue($value, $record, $recordInstance);
    }

    /**
     * Function to get Edit view display value.
     * @param <String> Data base value
     * @return <String> value
     */
    public function getEditViewDisplayValue($value, $blockfields = false)
    {
        $fieldName = $this->getName();

        if ($fieldName == 'time_start' || $fieldName == 'time_end') {
            if ($blockfields && !empty($value)) {
                $dateField = ($fieldName == 'time_start' ? $blockfields['date_start'] : $blockfields['due_date']);
                $value = $dateField->get('fieldvalue') . ' ' . $value;
            }

            return $this->getUITypeModel()->getDisplayTimeDifferenceValue($fieldName, $value);
        }

        // Set the start date and end date
        if (empty($value)) {
            if ($fieldName === 'date_start') {
                return DateTimeField::convertToUserFormat(date('Y-m-d'));
            }
            if ($fieldName === 'due_date') {
                $currentUser = Users_Record_Model::getCurrentUserModel();
                $minutes = $currentUser->get('callduration');

                return DateTimeField::convertToUserFormat(date('Y-m-d', strtotime("+{$minutes} minutes")));
            }
        }

        return parent::getEditViewDisplayValue($value);
    }

    /**
     * Function which will give the picklist values for a recurrence field.
     * @return type -- array of values
     */
    public static function getReccurencePicklistValues()
    {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $fieldModel = Vtiger_Field_Model::getInstance('recurringtype', Vtiger_Module_Model::getInstance('Events'));
        if ($fieldModel->isRoleBased() && !$currentUser->isAdminUser()) {
            $userModel = Users_Record_Model::getCurrentUserModel();
            $picklistValues = Vtiger_Util_Helper::getRoleBasedPicklistValues('recurringtype', $userModel->get('roleid'));
        } else {
            $picklistValues = Vtiger_Util_Helper::getPickListValues('recurringtype');
        }
        foreach ($picklistValues as $value) {
            $fieldPickListValues[$value] = vtranslate($value, 'Events');
        }

        return $fieldPickListValues;
    }

    /**
     * Function to get the advanced filter option names by Field type.
     * @return <Array>
     */
    public static function getAdvancedFilterOpsByFieldType()
    {

        $filterOpsByFieldType = parent::getAdvancedFilterOpsByFieldType();
        $filterOpsByFieldType['O'] = ['e', 'n'];

        return $filterOpsByFieldType;
    }

    /**
     * Function which will check if empty piclist option should be given.
     */
    public function isEmptyPicklistOptionAllowed()
    {
        if ($this->getFieldName() == 'visibility') {
            return false;
        }

        return true;
    }
}
