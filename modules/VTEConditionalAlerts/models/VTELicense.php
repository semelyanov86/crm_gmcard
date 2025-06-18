<?php

class VTEConditionalAlerts_VTELicense_Model
{
    public $module = '';

    public $cypher = 'VTE is encrypting its files to prevent unauthorized distribution';

    public $result = '';

    public $message = '';

    public $valid = true;

    public $file = '';

    public $site_url = '';

    public $license = '';

    public function __construct($module = '')
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
    }

    public function getLicenseInfo()
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
        $date_created = $this->gssX($input, '<date_created>', '</date_created>');
        if (substr($root_directory, -1) != '/' && substr($root_directory, -1) != '\\') {
            $root_directory .= '/';
        }
        if (strtolower($module) != strtolower($this->module) || $this->urlClean(strtolower($site_url)) != $this->urlClean(strtolower($this->site_url))) {
            return false;
        }

        return ['module' => $module, 'site_url' => $site_url, 'license' => $license, 'expiration_date' => $expiration_date, 'date_created' => $date_created];
    }

    public function readLicenseFile()
    {
        return true;
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
            $client = new SoapClient('http://license.vtexperts.com/license/soap.php?wsdl', ['trace' => 1, 'exceptions' => 0, 'cache_wsdl' => WSDL_CACHE_NONE, 'stream_context' => stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]])]);
            $arr = $client->validate($data);
            $this->result = $arr['result'];
            $this->message = $arr['message'];
            $this->expiration_date = $arr['expiration_date'];
            $this->date_created = $arr['date_created'];
            $this->createVTEFile($module, $site_url, $license);
        } catch (Exception $exception) {
            $this->result = 'bad';
            $this->message = "Unable to connect to licensing service. Please either check the server's internet connection, or proceed with offline licensing.<br>";
        }
        if ($this->message != '') {
            $errormsg = 'License Failed with message: ' . $this->message . '<br>';
        } else {
            $errormsg = 'Invalid License<br>';
        }
        $errormsg .= "Please try again or contact <a href='http://www.vtexperts.com/' target='_new'>vTiger Experts</a> for assistance.";
        $this->message = $errormsg;
        if ($this->result == 'ok' || $this->result == 'valid') {
            return true;
        }

        return false;
    }

    public function validate()
    {
        return true;
        if (file_exists($this->file)) {
            $this->readLicenseFile();
        } else {
            $this->checkValidate();
        }
        if ($this->result == 'ok' || $this->result == 'valid') {
            return true;
        }
        if ($this->RegenerateLicense()) {
            return true;
        }
        global $adb;
        $adb->pquery('DELETE FROM `vte_modules` WHERE module=?;', ['VTEConditionalAlerts']);
        $adb->pquery('INSERT INTO `vte_modules` (`module`, `valid`) VALUES (?, ?);', ['VTEConditionalAlerts', '0']);

        return false;
    }

    public function RegenerateLicense()
    {
        global $site_URL;
        $VTEStoreTabid = getTabid('VTEStore');
        if ($VTEStoreTabid > 0 && file_exists('modules/VTEStore/models/VTEModule.php')) {
            require_once 'modules/VTEStore/models/VTEModule.php';
            if (class_exists('VTEStore_VTEModule_Model')) {
                $modelInstance = new VTEStore_VTEModule_Model();
                if (method_exists($modelInstance, 'regenerateLicense') && is_callable([$modelInstance, 'regenerateLicense'])) {
                    $session_site_url = VTEStore_Util_Helper::reFormatVtigerUrl($site_URL);
                    if (!$_SESSION[$session_site_url]['customerLogined']) {
                        $db = PearDatabase::getInstance();
                        $sql = 'SELECT * FROM vtestore_user';
                        $res = $db->pquery($sql, []);
                        if ($db->num_rows($res) > 0) {
                            $options = [];
                            $options['username'] = $db->query_result($res, 0, 'username');
                            $options['password'] = $db->query_result($res, 0, 'password');
                            $options['vtiger_url'] = $site_URL;
                            $modelInstance->login($options);
                        }
                    }
                    $extensionName = $this->module;
                    $moduleInfo = ['moduleName' => $extensionName];
                    $serverResponse = $modelInstance->regenerateLicense($moduleInfo);
                    $error = $serverResponse['error'];
                    if ($error == '0') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function releaseLicense($info)
    {
        return true;
    }

    public function activateLicense($data)
    {
        global $_POST;
        global $root_directory;
        global $site_URL;
        $site_url = $data['site_url'];
        $license = $data['license'];
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
            $this->message = $errormsg;
        } else {
            $this->createVTEFile($this->module, $this->site_url, $this->license);

            return true;
        }
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
            $this->expiration_date = $arr['expiration_date'];
            $this->date_created = $arr['date_created'];
        } catch (Exception $exception) {
            $this->result = 'bad';
            $this->message = "Unable to connect to licensing service. Please either check the server's internet connection, or proceed with offline licensing.<br>";
        }
    }

    public function createVTEFile($module, $site_url, $license)
    {
        global $site_URL;
        global $root_directory;
        $expiration_date = $this->expiration_date;
        $date_created = $this->date_created;
        if (substr($site_URL, -1) != '/') {
            $site_URL .= '/';
        }
        $filename = $this->file;
        $dirname = $root_directory;
        if (file_exists($filename)) {
            unlink($filename);
        }
        $string = "<data>\r\n\t<module>" . $module . "</module>\r\n\t<site_url>" . $site_url . "</site_url>\r\n\t<license>" . $license . "</license>\r\n\t<expiration_date>" . $expiration_date . "</expiration_date>\r\n\t<date_created>" . $date_created . "</date_created>\r\n</data>";
        $data = $this->encrypt($string);
        $this->write_file($filename, $data);
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

    public function encrypt($str)
    {
        $key = $this->cypher;
        $result = '';
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
