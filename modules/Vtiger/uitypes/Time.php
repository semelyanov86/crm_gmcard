<?php

/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 */

class Vtiger_Time_UIType extends Vtiger_Base_UIType
{
    /**
     * Function to get the Template name for the current UI Type object.
     * @return <String> - Template Name
     */
    public function getTemplateName()
    {
        return 'uitypes/Time.tpl';
    }

    /**
     * Function to get display value for time.
     * @param <String> time
     * @return <String> time
     */
    public static function getDisplayTimeValue($time, $record = false, $recordInstance = false)
    {
        $date = new DateTimeField($time);

        return $date->getDisplayTime();
    }

    /**
     * Function to get time value in AM/PM format.
     * @param <String> $time
     * @return <String> time
     */
    public static function getTimeValueInAMorPM($time)
    {
        if ($time) {
            if (substr_count($time, ':') < 2) {
                $time .= ':';
            } /* to overcome notice of missing index 2 (seconds) below */
            [$hours, $minutes, $seconds] = explode(':', $time);
            $format = vtranslate('PM');

            if ($hours > 12) {
                $hours = (int) $hours - 12;
            } elseif ($hours < 12) {
                $format = vtranslate('AM');
            }

            // If hours zero then we need to make it as 12 AM
            if ($hours == '00') {
                $hours = '12';
                $format = vtranslate('AM');
            }

            return "{$hours}:{$minutes} {$format}";
        }

        return '';

    }

    /**
     * Function to get Time value with seconds.
     * @param <String> $time hh:mm[:ss] [AM|PM]
     * @return <String> time
     */
    public static function getTimeValueWithSeconds($time)
    {
        if ($time) {
            $timeDetails = explode(' ', $time);

            if (substr_count($timeDetails[0], ':') < 2) {
                $timeDetails[0] .= ':';
            } // adding : if seconds value is missing to avoid undefined array key error
            [$hours, $minutes, $seconds] = explode(':', $timeDetails[0]);

            // If pm exists and if it not 12 then we need to make it to 24 hour format
            if (isset($timeDetails[1]) && $timeDetails[1] === 'PM' && $hours != '12') {
                $hours = $hours + 12;
            }

            if (isset($timeDetails[1]) && $timeDetails[1] === 'AM' && $hours == '12') {
                $hours = '00';
            }

            if (empty($seconds)) {
                $seconds = '00';
            }

            return "{$hours}:{$minutes}:{$seconds}";
        }

        return '';

    }

    /**
     * Function to get the Display Value, for the current field type with given DB Insert Value.
     * @param <Object> $value
     * @return $value
     */
    public function getDisplayValue($value, $record = false, $recordInstance = false)
    {
        $userModel = Users_Privileges_Model::getCurrentUserModel();
        if ($userModel->get('hour_format') == '12') {
            return self::getTimeValueInAMorPM($value);
        }

        return $value;
    }

    /**
     * Helper static function.
     */
    public static function getDisplayValueUserFormat($value, $record = false, $recordInstance = false)
    {
        $instance = new static();

        return $instance->getDisplayValue($value, $record, $recordInstance);
    }

    /**
     * Function to get the display value in edit view.
     * @return converted value
     */
    public function getEditViewDisplayValue($value)
    {
        return self::getTimeValueInAMorPM($value);
    }

    public function getListSearchTemplateName()
    {
        return 'uitypes/TimeFieldSearchView.tpl';
    }

    public function getDBInsertValue($value)
    {
        if (preg_match('/AM|PM/', $value)) {
            $value = Vtiger_Time_UIType::getTimeValueWithSeconds($value);
        }

        return $value;
    }
}
