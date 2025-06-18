<?php

define('DS', DIRECTORY_SEPARATOR);
class UserLogin_Uninstall_View extends Settings_Vtiger_Index_View
{
    public function process(Vtiger_Request $request)
    {
        global $adb;
        echo "<div class=\"container-fluid\">\r\n                <div class=\"widget_header row-fluid\">\r\n                    <h3>Custom Login Page</h3>\r\n                </div>\r\n                <hr>";
        $module = Vtiger_Module::getInstance('UserLogin');
        if ($module) {
            $module->delete();
        }
        $message = $this->removeData();
        echo $message;
        $res_template = $this->delete_folder('layouts/v7/modules/UserLogin');
        $res_template = $this->delete_folder('layouts/v7/modules/Settings/UserLogin');
        $res_template = $this->delete_folder('layouts/vlayout/modules/UserLogin');
        echo '&nbsp;&nbsp;- Delete Custom Login Page template folder';
        if ($res_template) {
            echo ' - DONE';
        } else {
            echo ' - <b>ERROR</b>';
        }
        echo '<br>';
        $res_module = $this->delete_folder('modules/Settings/UserLogin');
        $res_module = $this->delete_folder('modules/UserLogin');
        echo '&nbsp;&nbsp;- Delete Custom Login Page module folder';
        if ($res_module) {
            echo ' - DONE';
        } else {
            echo ' - <b>ERROR</b>';
        }
        echo '<br>Module was Uninstalled.</div>';
    }

    public function delete_folder($tmp_path)
    {
        if (!is_writeable($tmp_path) && is_dir($tmp_path) && isFileAccessible($tmp_path)) {
            chmod($tmp_path, 511);
        }
        $handle = opendir($tmp_path);

        while ($tmp = readdir($handle)) {
            if ($tmp != '..' && $tmp != '.' && $tmp != '') {
                if (is_writeable($tmp_path . DS . $tmp) && is_file($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                    unlink($tmp_path . DS . $tmp);
                } else {
                    if (!is_writeable($tmp_path . DS . $tmp) && is_file($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                        chmod($tmp_path . DS . $tmp, 438);
                        unlink($tmp_path . DS . $tmp);
                    }
                }
                if (is_writeable($tmp_path . DS . $tmp) && is_dir($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                    $this->delete_folder($tmp_path . DS . $tmp);
                } else {
                    if (!is_writeable($tmp_path . DS . $tmp) && is_dir($tmp_path . DS . $tmp) && isFileAccessible($tmp_path)) {
                        chmod($tmp_path . DS . $tmp, 511);
                        $this->delete_folder($tmp_path . DS . $tmp);
                    }
                }
            }
        }
        closedir($handle);
        rmdir($tmp_path);
        if (!is_dir($tmp_path)) {
            return true;
        }

        return false;
    }

    public function removeData()
    {
        global $adb;
        $message = '';
        $settingModel = new Settings_UserLogin_Settings_Model();
        $settingModel->Restore(false);
        $sql = 'DROP TABLE `vte_user_login`,vte_user_login_image_setting;';
        $result = $adb->pquery($sql, []);
        $adb->pquery('DELETE FROM vtiger_settings_field WHERE `name` = ?', ['Custom Login Page']);
        $message .= '&nbsp;&nbsp;- Delete User Login tables';
        if ($result) {
            $message .= ' - DONE';
        } else {
            $message .= ' - <b>ERROR</b>';
        }
        $message .= '<br>';

        return $message;
    }
}
