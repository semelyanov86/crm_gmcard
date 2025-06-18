<?php

ini_set('soap.wsdl_cache_enabled', 0);
/**
 * Created by PhpStorm.
 * User: DinhNguyen
 * Date: 11/15/2014
 * Time: 11:12 PM.
 */
class VTELicense
{
    public $module = '';

    public $cypher = 'VTE is encrypting its files to prevent unauthorized distribution';

    public $featurestring = '';

    public $result = '';

    public $message = '';

    public $expires = '';

    public $valid = true;

    public $file = '';

    public $site_url = '';

    public $license = '';

    public $server = '';

    public function VTELicense($module = '')
    {
        global $_REQUEST;
        global $currentModule;
        global $root_directory;
        global $site_URL;
        if (substr($site_URL, -1) != '/') {
            $site_URL .= '/';
        }
        $this->site_url = $site_URL;
        if ($module != '') {
            $this->module = $module;
        }
        if ($this->module == '') {
            $this->module = $currentModule;
        }
        if ($this->file == '') {
            if (substr($root_directory, -1) != '/' && substr($root_directory, -1) != '\\') {
                $root_directory .= '/';
            }
            $this->file = $root_directory . 'test/' . $this->module . '.vte';
        }
        if (file_exists($this->file)) {
            if ($this->readLicenseFile() === false) {
                $this->validate();
            }
        } else {
            if ($url == 'cron') {
                exit('License not found');
            }
            $this->install();
        }

        return true;
    }

    public function install()
    {
        global $_POST;
        global $root_directory;
        global $site_URL;
        $errormsg = '&nbsp;';
        if (isset($_POST['vteRegister'])) {
            $site_url = $_POST['site_url'];
            $license = $_POST['license'];
            $this->site_url = $site_url;
            $this->license = $license;
            $this->checkValidate();
            if ($this->result == 'bad' || $this->result == 'invalid') {
                if ($this->message != '') {
                    $errormsg = 'License Failed with message: ' . $this->message . '<br>';
                } else {
                    $errormsg = 'Invalid License<br>';
                }
                $errormsg .= "Please try again or contact <a href='http://www.vtexperts.com/' target='_new'>vTiger Experts</a> for assistance.";
            } else {
                $this->createVTEFile($this->module, $this->site_url, $this->license, '', $this->message, $this->expires);

                return true;
            }
        }
        if ($errormsg == '&nbsp;' && $this->message != '') {
            $errormsg = htmlspecialchars_decode(htmlspecialchars_decode($this->message));
        }
        if ($this->url != '') {
            $urlstring = "action='" . $this->url . "'";
        } else {
            $urlstring = '';
        }
        $dirname = strtolower(dirname(__FILE__));
        echo "        <head><title>VTE Module Registration</title></hear>\r\n\t\t\t<br /><br /><br /><div align=\"center\">\r\n\t\t\t<h2>Welcome to " . $this->module . " Registration</h2>\r\n\t\t\t<br />\r\n\t\t\t<form method='post' " . $urlstring . ">\r\n\t\t\t<input type='hidden' name='vteRegister' value='true'>\r\n\t\t\t<table border=\"0\">\r\n\t\t\t<tr align=\"left\"><td colspan='2'>Thank you for Purchasing " . $this->module . "</td></tr>\r\n\t\t\t<tr><td colspan='2'>You are required to active the extension before it can be used. Please enter the licnse provided by VTExperts.</td></tr>\r\n\t\t\t<tr><td colspan='2'>&nbsp;</td></tr>\r\n\t\t\t<tr><td>vTiger Url:</td><td><input type=\"hidden\" name=\"site_url\" value=\"" . $site_URL . "\"/><a href='" . $site_URL . "' target=\"_blank\">" . $site_URL . "</a></td></tr>\r\n\t\t\t<tr><td>License:</td><td><input type=\"text\" name=\"license\" style=\"width:400px;\"/></td></tr>\r\n\t\t\t<tr><td colspan='2' align='center'><b>" . $errormsg . "</b></td></tr>\r\n\t\t\t<tr><td colspan='2' align='center'><input type='submit' value='Activate'/></td></tr>\r\n\t\t\t<tr><td colspan='2' align='center'>&nbsp;</td></tr>\r\n\t\t\t<tr><td colspan='2' align='left'>*If you are not able to active the license - please contact us at via chat on <a href=\"http://www.vtexperts.com\" target=\"_blank\">http://www.VTExperts.com</a>,</td></tr>\r\n\t\t\t<tr><td colspan='2' align='left'> call us +1 (818) 495-5557 or simple send an email at <a href=\"javascript:void(0);\" target=\"_top\" onclick=\"sendMail('" . $this->module . "','" . $site_URL . "');\">\r\nSend Mail</a>.\r\n<br />\r\n            <textarea style=\"display:none;\"  id=\"email_body\">Having trouble activating the " . $this->module . ', my vTiger URL is ' . $site_URL . ". \n\nThe license I'm using is:\n\nThe error message I'm getting is:\n\nThanks!</textarea>\r\n            </td></tr>\r\n\t\t\t</table>\r\n\t\t\t</form>\r\n            <script>\r\n                function sendMail(module,site_URL) {\r\n                    var subject=\"VTExperts Module Registration - \"+module;\r\n                    var body=document.getElementById(\"email_body\").value;\r\n                    var mailToLink = \"mailto:Support@VTExperts.com?Subject=\"+subject+\"&body=\" + encodeURIComponent(body);\r\n                    window.open(mailToLink);\r\n                }\r\n            </script>\r\n\t\t\t</div>";
        exit;
    }

    public function readLicenseFile()
    {
        global $root_directory;
        global $site_URL;
        if (substr($site_URL, -1) != '/') {
            $site_URL .= '/';
        }
        $input = $this->decrypt(file_get_contents($this->file));
        $module = $this->gssX($input, '<module>', '</module>');
        $site_url = $this->gssX($input, '<site_url>', '</site_url>');
        $license = $this->gssX($input, '<license>', '</license>');
        $expiration_date = $this->gssX($input, '<expiration_date>', '</expiration_date>');
        if (substr($root_directory, -1) != '/' && substr($root_directory, -1) != '\\') {
            $root_directory .= '/';
        }
        if (strtolower($module) != strtolower($this->module) || $this->urlClean(strtolower($site_url)) != $this->urlClean(strtolower($this->site_url))) {
            return false;
        }
        if ($expiration_date == '0000-00-00' || date('Y-m-d') <= $expiration_date) {
            $this->result = 'ok';

            return true;
        }

        try {
            $data = "<data>\r\n                <license>" . $license . "</license>\r\n                <site_url>" . $site_url . "</site_url>\r\n                <module>" . $module . "</module>\r\n                <uri>" . $_SERVER['REQUEST_URI'] . "</uri>\r\n                </data>";
            $client = new SoapClient('http://license.vtexperts.com/license/soap.php?wsdl', ['trace' => 1, 'exceptions' => 0, 'stream_context' => stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]])]);
            $arr = $client->validate($data);
            $this->result = $arr['result'];
            $this->message = $arr['message'];
        } catch (Exception $exception) {
            $this->result = 'bad';
            $this->message = "Unable to connect to licensing service. Please either check the server's internet connection, or proceed with offline licensing.<br>";
        }
        if ($this->result == 'ok' || $this->result == 'valid') {
            return true;
        }
        $this->install();

        return false;
    }

    public function validate()
    {
        return true;
    }

    public function checkValidate()
    {
        global $site_URL;
        global $root_directory;
        $data = "<data>\r\n\t\t<license>" . $this->license . "</license>\r\n\t\t<site_url>" . $this->site_url . "</site_url>\r\n\t\t<module>" . $this->module . "</module>\r\n\t\t<uri>" . $_SERVER['REQUEST_URI'] . "</uri>\r\n\t\t</data>";

        try {
            $client = new SoapClient('http://license.vtexperts.com/license/soap.php?wsdl', ['trace' => 1, 'exceptions' => 0, 'stream_context' => stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]])]);
            $arr = $client->validate($data);
            $this->result = $arr['result'];
            $this->message = $arr['message'];
        } catch (Exception $exception) {
            $this->result = 'bad';
            $this->message = "Unable to connect to licensing service. Please either check the server's internet connection, or proceed with offline licensing.<br>";
        }
    }

    public function createVTEFile($module, $site_url, $license)
    {
        global $site_URL;
        global $root_directory;
        if (substr($site_URL, -1) != '/') {
            $site_URL .= '/';
        }
        $filename = $this->file;
        $dirname = $root_directory;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $string = "<data>\r\n\t<module>" . $module . "</module>\r\n\t<site_url>" . $site_url . "</site_url>\r\n\t<license>" . $license . "</license>\r\n</data>";
        $data = $this->encrypt($string);
        $this->write_file($filename, $data);
    }

    public function encrypt($str)
    {
        $key = $this->cypher;
        for ($i = 0; $i < strlen($str); ++$i) {
            $char = substr($str, $i, 1);
            $keychar = substr($key, $i % strlen($key) - 1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        }

        return urlencode(base64_encode($result));
    }

    public function decrypt($str)
    {
        $str = base64_decode(urldecode($str));
        $result = '';
        $key = $this->cypher;
        for ($i = 0; $i < strlen($str); ++$i) {
            $char = substr($str, $i, 1);
            $keychar = substr($key, $i % strlen($key) - 1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }

        return $result;
    }

    public function write_file($filename, $content)
    {
        if (!file_exists($filename)) {
            $fh = fopen($filename, 'w');
            fclose($fh);
        }
        if (is_writable($filename)) {
            if (!($handle = fopen($filename, 'a'))) {
                echo 'Cannot open file (' . $filename . ')';
                exit;
            }
            if (!fwrite($handle, $content)) {
                echo 'Cannot write to file (' . $filename . ')';
                exit;
            }
            fclose($handle);
        } else {
            echo 'The file ' . $filename . ' is not writable';
        }
    }

    public function urlClean($string)
    {
        $string = str_replace('https://', '', $string);
        $string = str_replace('HTTPS://', '', $string);
        $string = str_replace('http://', '', $string);
        $string = str_replace('HTTP://', '', $string);
        if (strtolower(substr($string, 0, 4)) == 'www.') {
            $string = substr($string, 4);
        }

        return $string;
    }

    public function slashClean($string)
    {
        $string = str_replace('\\', '', $string);
        $string = str_replace('/', '', $string);

        return $string;
    }

    public function gssX($str_All, $start_str = 'included in output', $end_str = 'included in output')
    {
        $str_return = '';
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
}
