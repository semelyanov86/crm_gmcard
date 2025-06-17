<?php

class CustomModule_Util_Helper
{
    public function checkPermis()
    {
        $message = "<div class=\"alert alert-danger\" id=\"error_message\">You need to validate the extension \"VTE Custom Module Builder\" <br>before it can be use</div><div class=\"row\"><ul style=\"padding-left: 10px;\"><li>Email: &nbsp;&nbsp;<a style=\"color: #0088cc; text-decoration:none;\" href=\"mailto:Support@VTExperts.com\">Support@VTExperts.com</a></li><li>Phone: &nbsp;&nbsp;<span>+1 (818) 495-5557</span></li><li>Chat: &nbsp;&nbsp;Available on <a style=\"color: #0088cc; text-decoration:none;\" href=\"http://www.vtexperts.com\" target=\"_blank\">http://www.VTExperts.com</a></li></ul></div>";
        $licFile = "test/ModuleLinkCreator.vte";
        if (!file_exists($licFile)) {
            if (self::isActiveModuleLinkCreator()) {
                return true;
            }
            throw new AppException(vtranslate($message));
        }
        if (self::checkLicense($licFile)) {
            return true;
        }
        if (self::isActiveModuleLinkCreator()) {
            return true;
        }
        throw new AppException(vtranslate($message));
    }
    public function isActiveModuleLinkCreator()
    {
        $moduleLinkCreatorTabId = (int) getTabid("ModuleLinkCreator");
        if (0 < $moduleLinkCreatorTabId && file_exists("modules/ModuleLinkCreator/ModuleLinkCreator.php")) {
            $moduleLinkCreater = Vtiger_Module_Model::getInstance("ModuleLinkCreator");
            if ($moduleLinkCreater && $moduleLinkCreater->isActive()) {
                if ($moduleLinkCreater->vteLicense()) {
                    return true;
                }
                return false;
            }
            return false;
        }
        return false;
    }
    public function checkLicense($licFile)
    {
        global $site_URL;
        if (substr($site_URL, -1) != "/") {
            $site_URL .= "/";
        }
        $input = self::decrypt(file_get_contents($licFile));
        $module = self::gssX($input, "<module>", "</module>");
        $site_url = self::gssX($input, "<site_url>", "</site_url>");
        $license = self::gssX($input, "<license>", "</license>");
        $expiration_date = self::gssX($input, "<expiration_date>", "</expiration_date>");
        if ($expiration_date < date("Y-m-d") || strtolower($module) != strtolower("ModuleLinkCreator") || self::urlClean(strtolower($site_url)) != self::urlClean(strtolower($site_URL))) {
            return false;
        }
        return true;
    }
    public function decrypt($str)
    {
        $str = base64_decode(urldecode($str));
        $result = "";
        $key = "VTE is encrypting its files to prevent unauthorized distribution";
        for ($i = 0; $i < strlen($str); $i++) {
            $char = substr($str, $i, 1);
            $keychar = substr($key, $i % strlen($key) - 1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }
        return $result;
    }
    public function gssX($str_All, $start_str = "included in output", $end_str = "included in output")
    {
        $str_return = "";
        $start_str_match_post = strpos($str_All, $start_str);
        if ($start_str_match_post !== false) {
            $end_str_match_post = strpos($str_All, $end_str, $start_str_match_post);
            if ($end_str_match_post !== false) {
                $start_str_get = $start_str_match_post;
                $length_str_get = $end_str_match_post + strlen($end_str) - $start_str_get;
                $str_return = substr($str_All, $start_str_get, $length_str_get);
            }
        }
        $str_return = substr($str_return, strlen($start_str));
        $len = strlen($str_return) - strlen($end_str);
        $str_return = substr($str_return, 0, $len);
        return $str_return;
    }
    public function urlClean($string)
    {
        $string = str_replace("https://", "", $string);
        $string = str_replace("HTTPS://", "", $string);
        $string = str_replace("http://", "", $string);
        $string = str_replace("HTTP://", "", $string);
        if (strtolower(substr($string, 0, 4)) == "www.") {
            $string = substr($string, 4);
        }
        return $string;
    }
}

?>