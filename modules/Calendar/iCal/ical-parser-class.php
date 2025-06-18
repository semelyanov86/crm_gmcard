<?php

class iCal
{
    public $folders;

    public function __construct()
    {
        $this->folders = 'cache/import/';
    }

    public function iCal()
    {
        self::__construct();
    }

    public function iCalReader($filename, $root_directory = '')
    {
        $iCaltoArray = $this->iCalDecoder($filename, $root_directory);

        return $iCaltoArray;
    }

    public function iCalDecoder($file, $root_directory)
    {
        $ical = file_get_contents($root_directory . $this->folders . $file);
        preg_match_all('/BEGIN:VEVENT.*?END:VEVENT/si', $ical, $eventresult, PREG_PATTERN_ORDER);
        preg_match_all('/BEGIN:VTODO.*?END:VTODO/si', $ical, $todoresult, PREG_PATTERN_ORDER);
        for ($i = 0; $i < php7_count($eventresult[0]); ++$i) {
            $tmpbyline = explode("\r\n", $eventresult[0][$i]);
            $begin = false;
            $key = null;
            foreach ($tmpbyline as $item) {
                $tmpholderarray = explode(':', $item, 2);

                if (php7_count($tmpholderarray) > 1) {
                    if ($tmpholderarray[0] == 'BEGIN') {
                        if ($begin == false) {
                            $begin = true;
                            $majorarray['TYPE'] = $tmpholderarray[1];
                        } else {
                            $majorarray[$tmpholderarray[1]] = [];
                            $key = $tmpholderarray[1];
                        }
                    } elseif ($tmpholderarray[0] == 'END') {
                        if (!empty($key)) {
                            $key = null;
                        }
                    } else {
                        if (!empty($key)) {
                            $majorarray[$key][$tmpholderarray[0]] = $tmpholderarray[1];
                        } else {
                            $majorarray[$tmpholderarray[0]] = $tmpholderarray[1];
                        }
                    }
                }

            }
            $icalarray[] = $majorarray;
            unset($majorarray);
        }

        for ($i = 0; $i < php7_count($todoresult[0]); ++$i) {
            $tmpbyline = explode("\r\n", $todoresult[0][$i]);
            $begin = false;
            $key = null;
            foreach ($tmpbyline as $item) {
                $tmpholderarray = explode(':', $item);

                if (php7_count($tmpholderarray) > 1) {
                    if ($tmpholderarray[0] == 'BEGIN') {
                        if ($begin == false) {
                            $begin = true;
                            $majorarray['TYPE'] = $tmpholderarray[1];
                        } else {
                            $majorarray[$tmpholderarray[1]] = [];
                            $key = $tmpholderarray[1];
                        }
                    } elseif ($tmpholderarray[0] == 'END') {
                        if (!empty($key)) {
                            $key = null;
                        }
                    } else {
                        if (!empty($key)) {
                            $majorarray[$key][$tmpholderarray[0]] = $tmpholderarray[1];
                        } else {
                            $majorarray[$tmpholderarray[0]] = $tmpholderarray[1];
                        }
                    }
                }

            }
            $icalarray[] = $majorarray;
            unset($majorarray);
        }

        return $icalarray;
    }
}
