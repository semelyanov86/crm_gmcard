<?php

class VReports_Util_Helper
{
    public static function reFormatSiteUrl($url)
    {
        $url = trim($url);
        $url = rtrim($url, "/");
        $url = str_replace("//index.php", "/index.php", $url);
        return $url;
    }
}

?>