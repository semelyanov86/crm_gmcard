<?php

/*
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 */

class vt_DateTime
{
    public $second = 0;

    public $minute = 0;

    public $hour = 0;

    public $z_hour = '00';

    public $day;

    public $z_day;

    public $week;

    public $month;

    public $z_month;

    public $year;

    public $dayofweek;

    public $dayofyear;

    public $daysinmonth;

    public $daysinyear;

    public $dayofweek_inshort;

    public $dayofweek_inlong;

    public $month_inshort;

    public $month_inlong;

    public $ts;

    public $offset;

    public $format;

    public $tz;

    public $ts_def;

    /**
     * Constructor for vt_DateTime class.
     * @param array  $timearr - collection of string
     * @param string $check   - check string
     */
    public function __construct(&$timearr, $check)
    {
        if (! isset($timearr) || php7_count($timearr) == 0) {
            $this->setDateTime(null);
        } elseif (isset($timearr['ts'])) {
            $this->setDateTime($timearr['ts']);
        } else {
            if (isset($timearr['hour']) && $timearr['hour'] !== '') {
                $this->hour = $timearr['hour'];
            }
            if (isset($timearr['min']) && $timearr['min'] !== '') {
                $this->minute = $timearr['min'];
            }
            if (isset($timearr['sec']) && $timearr['sec']  !== '') {
                $this->second = $timearr['sec'];
            }
            if (isset($timearr['day']) && $timearr['day'] !== '') {
                $this->day = $timearr['day'];
            }
            if (isset($timearr['week']) && $timearr['week'] !== '') {
                $this->week = $timearr['week'];
            }
            if (isset($timearr['month']) && $timearr['month'] !== '') {
                $this->month = $timearr['month'];
            }
            if (isset($timearr['year']) && $timearr['year'] >= 1_970) {
                $this->year = $timearr['year'];
            } else {
                return null;
            }
        }
        if ($check) {
            $this->getDateTime();
        }
    }

    public function vt_DateTime(&$timearr, $check)
    {
        self::__construct($timearr, $check);
    }

    /**
     * function to get date and time using index.
     * @param int       $index - number between 0 to 23
     * @param string        $day   - date
     * @param string        $month - month
     * @param string        $year  - year
     * return vt_DateTime obj  $datetimevalue
     */
    public function getTodayDatetimebyIndex($index, $day = '', $month = '', $year = '')
    {
        if ($day === '') {
            $day = $this->day;
        }
        if ($month === '') {
            $month = $this->month;
        }
        if ($year === '') {
            $year = $this->year;
        }
        $day_array = [];

        if ($index < 0 || $index > 23) {
            exit('hour is invalid');
        }

        $day_array['hour'] = $index;
        $day_array['min'] = 0;
        $day_array['day'] = $day;
        $day_array['month'] = $month;
        $day_array['year'] = $year;
        $datetimevalue = new vt_DateTime($day_array, true);

        return $datetimevalue;
    }

    /**
     * function to get days in week using index.
     * @param int       $index - number between 1 to 7
     * return vt_DateTime obj  $datetimevalue
     */
    public function getThisweekDaysbyIndex($index)
    {
        $week_array = [];
        if ($index < 1 || $index > 7) {
            exit('day is invalid');
        }
        $week_array['day'] = $this->day + ($index - $this->dayofweek);
        $week_array['month'] = $this->month;
        $week_array['year'] = $this->year;
        $datetimevalue = new vt_DateTime($week_array, true);

        return $datetimevalue;
    }

    /**
     * function to get days in month using index.
     *
     * This function will be deprecated.
     * The newer version is getThisMonthsDayByIndex() and should be used wherever possible
     *
     * @param int       $index - number between 0 to 42
     * @param string        $day   - date
     * @param string        $month - month
     * @param string        $year  - year
     * return vt_DateTime obj  $datetimevalue
     */
    public function getThismonthDaysbyIndex($index, $day = '', $month = '', $year = '')
    {
        if ($day == '') {
            $day = $index + 1;
        }
        if ($month == '') {
            $month = $this->month;
        }
        if ($year == '') {
            $year = $this->year;
        }
        $month_array = [];
        $month_array['day'] = $day;
        $month_array['month'] = $month;
        $month_array['year'] = $year;
        $datetimevalue = new vt_DateTime($month_array, true);

        return $datetimevalue;
    }

    /**
     * function to get months in year using index.
     * @param int       $index - number between 0 to 11
     * return vt_DateTime obj  $datetimevalue
     */
    public function getThisyearMonthsbyIndex($index)
    {
        $year_array = [];
        $year_array['day'] = 1;
        if ($index < 0 || $index > 11) {
            exit('month is invalid');
        }
        $year_array['month'] = $index + 1;
        $year_array['year'] = $this->year;
        $datetimevalue = new vt_DateTime($year_array, true);

        return $datetimevalue;
    }

    /**
     * function to get hour end time
     * return vt_DateTime obj  $datetimevalue.
     */
    public function getHourendtime()
    {
        $date_array = [];
        $date_array['hour'] = $this->hour;
        $date_array['min'] = 59;
        $date_array['day'] = $this->day;
        $date_array['sec'] = 59;
        $date_array['month'] = $this->month;
        $date_array['year'] = $this->year;
        $datetimevalue = new vt_DateTime($date_array, true);

        return $datetimevalue;
    }

    /**
     * function to get day end time
     * return vt_DateTime obj  $datetimevalue.
     */
    public function getDayendtime()
    {
        $date_array = [];
        $date_array['hour'] = 23;
        $date_array['min'] = 59;
        $date_array['sec'] = 59;
        $date_array['day'] = $this->day;
        $date_array['month'] = $this->month;
        $date_array['year'] = $this->year;
        $datetimevalue = new vt_DateTime($date_array, true);

        return $datetimevalue;
    }

    /**
     * function to get month end time
     * return vt_DateTime obj  $datetimevalue.
     */
    public function getMonthendtime()
    {
        $date_array = [];
        $date_array['hour'] = 23;
        $date_array['min'] = 59;
        $date_array['sec'] = 59;
        $date_array['day'] = $this->daysinmonth;
        $date_array['month'] = $this->month;
        $date_array['year'] = $this->year;
        $datetimevalue = new vt_DateTime($date_array, true);

        return $datetimevalue;
    }

    /**
     * function to get day of week
     * return string $this->day  - day (eg: Monday).
     */
    public function get_Date()
    {
        return $this->day;
    }

    /**
     * function to get month name in short
     * return string $this->month_inshort  - month name (eg: Jan).
     */
    public function getmonthName_inshort()
    {
        return $this->month_inshort;
    }

    /**
     * function to get month
     * return string $this->month  - month name.
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * function to get year.
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * function to get the number of days in a month.
     */
    public function getDaysInMonth()
    {
        return $this->daysinmonth;
    }

    /**
     * function to get month name
     * return string $this->month_inlong  - month name.
     */
    public function getmonthName()
    {
        return $this->month_inlong;
    }

    /**
     * function to get day of week
     * return string $this->dayofweek_inlong  - day of week.
     */
    public function getdayofWeek()
    {
        return $this->dayofweek_inlong;
    }

    /**
     * function to get day of week in short
     * return string $this->dayofweek_inshort  - day of week (eg: Mon).
     */
    public function getdayofWeek_inshort()
    {
        return $this->dayofweek_inshort;
    }

    /**
     * function to set values for vt_DateTime object.
     * @param int   $ts  - Time stamp
     */
    public function setDateTime($ts)
    {
        global $mod_strings;
        if (empty($ts)) {
            $ts = time();
        }

        $this->ts = $ts;
        $this->ts_def = $this->ts;
        $date_string = date('i::G::H::j::d::t::N::z::L::W::n::m::Y::Z::T::s', $ts);

        [$this->minute, $this->hour, $this->z_hour, $this->day, $this->z_day, $this->daysinmonth, $this->dayofweek, $this->dayofyear, $is_leap, $this->week, $this->month, $this->z_month, $this->year, $this->offset, $this->tz, $this->second] = explode('::', $date_string);

        $this->dayofweek_inshort = $mod_strings['cal_weekdays_short'][$this->dayofweek - 1];
        $this->dayofweek_inlong = $mod_strings['cal_weekdays_long'][$this->dayofweek - 1];
        $this->month_inshort = $mod_strings['cal_month_short'][$this->month];
        $this->month_inlong = $mod_strings['cal_month_long'][$this->month];

        $this->daysinyear = 365;

        if ($is_leap == 1) {
            ++$this->daysinyear;
        }
    }

    /**
     * function to get values from vt_DateTime object.
     */
    public function getDateTime()
    {
        global $mod_strings;
        $hour = 0;
        $minute = 0;
        $second = 0;
        $day = 1;
        $month = 1;
        $year = 1_970;

        if (isset($this->second) && $this->second !== '') {
            $second = $this->second;
        }
        if (isset($this->minute) && $this->minute !== '') {
            $minute = $this->minute;
        }
        if (isset($this->hour) && $this->hour !== '') {
            $hour = $this->hour;
        }
        if (isset($this->day) && $this->day !== '') {
            $day = $this->day;
        }
        if (isset($this->month) && $this->month !== '') {
            $month = $this->month;
        }

        if (isset($this->year) && $this->year !== '') {
            $year = $this->year;
        } else {
            exit('year was not set');
        }
        if (empty($hour) && $hour !== 0) {
            $hour = 0;
        }
        $this->ts = mktime($hour, $minute, $second, $month, $day, $year);
        $this->setDateTime($this->ts);
    }

    /**
     * function to get mysql formatted date
     * return formatted date in string format.
     */
    public function get_formatted_date()
    {
        $date = $this->year . '-' . $this->z_month . '-' . $this->z_day;

        return DateTimeField::convertToUserFormat($date);
    }

    /**
     * Function to get formatted date in users time zone.
     * @return <Date>
     */
    public function get_userTimezone_formatted_date()
    {
        $dateTimeInUserFormat = DateTimeField::convertToUserTimeZone($this->get_DB_formatted_date() . ' ' . $this->get_formatted_time());

        return $dateTimeInUserFormat->format('Y-m-d');
    }

    /**
     * @return Date
     */
    public function get_DB_formatted_date()
    {
        return $this->year . '-' . $this->z_month . '-' . $this->z_day;
    }

    /**
     * function to get mysql formatted time
     * return formatted time in string format.
     */
    public function get_formatted_time()
    {
        $hour = $this->z_hour;
        $min = $this->minute;
        if (empty($hour)) {
            $hour = '00';
        }
        if (empty($min)) {
            $min = '00';
        }

        return $hour . ':' . $min;
    }

    /**
     * function to get date depends on mode value.
     * @param string $mode  - 'increment' or 'decrement'
     * return vt_DateTime obj
     */
    public function get_changed_day($mode)
    {
        if ($mode == 'increment') {
            $day = $this->day + 1;
        } else {
            $day = $this->day - 1;
        }
        $date_data = ['day' => $day,
            'month' => $this->month,
            'year' => $this->year,
        ];

        return new vt_DateTime($date_data, true);
    }

    /**
     * function to get changed week depends on mode value.
     * @param string $mode  - 'increment' or 'decrement'
     * return vt_DateTime obj
     */
    public function get_first_day_of_changed_week($mode)
    {
        $first_day = $this->getThisweekDaysbyIndex(1);
        if ($mode == 'increment') {
            $day = $first_day->day + 7;
        } else {
            $day = $first_day->day - 7;
        }
        $date_data = ['day' => $day,
            'month' => $first_day->month,
            'year' => $first_day->year,
        ];

        return new vt_DateTime($date_data, true);
    }

    /**
     * function to get month depends on mode value.
     * @param string $mode  - 'increment' or 'decrement'
     * return vt_DateTime obj
     */
    public function get_first_day_of_changed_month($mode)
    {
        $tmpDate['day'] = $this->day;
        $tmpDate['month'] = $this->month;
        $tmpDate['year'] = $this->year;

        if (is_array($arr) && !empty($arr)) {
            $tmpDate['year'] = $arr[0];
            $tmpDate['month'] = $arr[1];
            $tmpDate['day'] = $arr[2];
        }

        if ($mode == 'increment') {
            $month = $tmpDate['month'] + 1;
            $year = $tmpDate['year'];
        } else {
            if ($tmpDate['month'] == 1) {
                $month = 12;
                $year = $tmpDate['year'] - 1;
            } else {
                $month = $tmpDate['month'] - 1;
                $year = $tmpDate['year'];
            }
        }
        $date_data = [
            'day' => 1,
            'month' => $month,
            'year' => $year,
        ];

        return new vt_DateTime($date_data, true);
    }

    /**
     * function to get year depends on mode value.
     * @param string $mode  - 'increment' or 'decrement'
     * return vt_DateTime obj
     */
    public function get_first_day_of_changed_year($mode)
    {
        if ($mode == 'increment') {
            $year = $this->year + 1;
        } else {
            $year = $this->year - 1;
        }
        $date_data = ['day' => 1,
            'month' => 1,
            'year' => $year,
        ];

        return new vt_DateTime($date_data, true);
    }

    /**
     * function to get date string
     * return date string.
     */
    public function get_date_str()
    {
        $array = [];
        if (isset($this->hour) && $this->hour != '') {
            array_push($array, 'hour=' . $this->hour);
        }
        if (isset($this->day) && $this->day != '') {
            array_push($array, 'day=' . $this->day);
        }
        if (isset($this->month) && $this->month) {
            array_push($array, 'month=' . $this->month);
        }
        if (isset($this->year) && $this->year != '') {
            array_push($array, 'year=' . $this->year);
        }

        return  '&' . implode('&', $array);
    }

    /**
     * function to get days in month using index.
     *
     * This is the newer version of the function getThismonthDaysbyIndex().
     * This should be used whereever possible
     *
     * @param int       $index - number between 0 to 42
     */
    public function getThisMonthsDayByIndex($index)
    {
        $day = $index;
        $month = $this->month;
        $year = $this->year;
        $month_array = [];
        $month_array['day'] = $day;
        $month_array['month'] = $month;
        $month_array['year'] = $year;
        $datetimevalue = new vt_DateTime($month_array,true);

        return $datetimevalue;
    }
}
