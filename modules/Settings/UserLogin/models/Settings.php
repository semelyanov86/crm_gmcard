<?php

class Settings_UserLogin_Settings_Model extends Vtiger_Base_Model
{
    public $user;

    public $db;

    public function __construct()
    {
        global $current_user;
        $this->user = $current_user;
        $this->db = PearDatabase::getInstance();
    }

    public function getData()
    {
        $settings = [];
        $query = 'SELECT * FROM vte_user_login ORDER BY `id` DESC';
        $result = $this->db->pquery($query, []);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $row['images'] = json_decode(html_entity_decode($row['images']));
                $settings[] = $row;
            }
        }

        return $settings;
    }

    public function getDataDetails($record)
    {
        $data = [];
        $query = 'SELECT * FROM vte_user_login WHERE `id` = ?';
        $result = $this->db->pquery($query, [$record]);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $row['images'] = json_decode(html_entity_decode($row['images']));
                $data = $row;
            }
        }

        return $data;
    }

    public function deleteRecord($request)
    {
        $recordIds = $request->get('recordids', 0);
        if (empty($recordIds)) {
            return false;
        }
        $this->db->pquery('DELETE FROM vte_user_login WHERE vte_user_login.id IN(' . implode(',', $recordIds) . ')', []);

        return true;
    }

    public function getRecord($recordId)
    {
        $recordData = [];
        $query = 'SELECT * FROM vte_user_login WHERE vte_user_login.id = ?';
        $result = $this->db->pquery($query, [$recordId]);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $recordData = $row;
            }
        }

        return $recordData;
    }

    public function save($request)
    {
        $recordId = $request->get('record', 0);
        if ($recordId > 0) {
            $this->updateSetting($request);
        } else {
            $recordId = $this->addSetting($request);
        }
    }

    public function updateSetting($request)
    {
        $recordId = $request->get('record');
        $header = $request->get('header');
        $description = $request->get('description');
        $social_facebook = $request->get('social_facebook');
        $social_twitter = $request->get('social_twitter');
        $social_linkedin = $request->get('social_linkedin');
        $social_youtube = $request->get('social_youtube');
        $copyright = $request->get('copyright');
        $slide_type = $request->get('slide_type');
        $slide_speed = $request->get('slide_speed');
        $slide_easing = $request->get('slide_easing');
        $logo = $request->get('logo');
        $images = $request->get('images');
        $imageLists = [];
        foreach ($images as $image) {
            if ($image != '') {
                array_push($imageLists, $image);
            }
        }
        $this->db->pquery("UPDATE vte_user_login\n\t\t\t\t\t\t\tSET `header` = ?, `description` = ?, `social_facebook` = ?, `social_twitter` = ?, `social_linkedin` = ?, `social_youtube` = ?, `copyright` = ?, `slide_type` = ?, `slide_speed` = ?, `slide_easing` = ?, `logo` = ?, `images` = ? WHERE `id` = ?", [$header, $description, $social_facebook, $social_twitter, $social_linkedin, $social_youtube, $copyright, $slide_type, $slide_speed, $slide_easing, $logo, Zend_Json::encode($imageLists), $recordId]);

        return $recordId;
    }

    public function addSetting($request)
    {
        $header = $request->get('header');
        $description = $request->get('description');
        $social_facebook = $request->get('social_facebook');
        $social_twitter = $request->get('social_twitter');
        $social_linkedin = $request->get('social_linkedin');
        $social_youtube = $request->get('social_youtube');
        $copyright = $request->get('copyright');
        $slide_type = $request->get('slide_type');
        $slide_speed = $request->get('slide_speed');
        $slide_easing = $request->get('slide_easing');
        $logo = $request->get('logo');
        $images = $request->get('images');
        $imageLists = [];
        foreach ($images as $image) {
            if ($image != '') {
                array_push($imageLists, $image);
            }
        }
        $this->db->pquery("INSERT INTO vte_user_login(`header`, `description`, `social_facebook`, `social_twitter`, `social_linkedin`, `social_youtube`, `copyright`, `slide_type`, `slide_speed`, `slide_easing`, `logo`, `images`)\n                            VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [$header, $description, $social_facebook, $social_twitter, $social_linkedin, $social_youtube, $copyright, $slide_type, $slide_speed, $slide_easing, $logo, Zend_Json::encode($imageLists)]);

        return $this->db->getLastInsertID();
    }

    public function uploadImage($data)
    {
        global $root_directory;
        $imageInfo = $_FILES[$data->get('mode') . '_box'];
        $mode = $data->get('mode');
        if ($mode == 'image') {
            $mode = 'slide';
        }
        $imageSetting = $this->getImageSettings();
        $method = 'resize';
        $max_width = 665;
        $max_height = 667;
        if (!empty($imageSetting) && isset($imageSetting[$mode])) {
            $method = $imageSetting[$mode]['method'];
            $max_width = $imageSetting[$mode]['width'];
            $max_height = $imageSetting[$mode]['height'];
        }
        $allowed_image_types = ['image/pjpeg' => 'jpg', 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/x-png' => 'png', 'image/gif' => 'gif'];
        $newFile = '';
        $dst = $root_directory . 'test/logo/';
        if (isset($imageInfo['name']) && $imageInfo['name'] != '' && $imageInfo['error'] == 0) {
            $file_tmp = $imageInfo['tmp_name'];
            $file_type = $imageInfo['type'];
            $filename = basename($imageInfo['name']);
            $file_ext = strtolower(substr($filename, strrpos($filename, '.') + 1));
            if (isset($allowed_image_types[$file_type]) && $allowed_image_types[$file_type] == $file_ext) {
                $newFile = $dst . $filename;
                if (move_uploaded_file($file_tmp, $newFile) === true) {
                    [$imagewidth, $imageheight, $imageType] = getimagesize($newFile);
                    if ($method == 'crop') {
                        $imageType = image_type_to_mime_type($imageType);
                        $original_aspect = $imagewidth / $imageheight;
                        $thumb_aspect = $max_width / $max_height;
                        if ($thumb_aspect <= $original_aspect) {
                            $new_height = $max_height;
                            $new_width = ceil($imagewidth / ($imageheight / $max_height));
                        } else {
                            $new_width = $max_width;
                            $new_height = ceil($imageheight / ($imagewidth / $max_width));
                        }
                        $newImage = imagecreatetruecolor($max_width, $max_height);
                        switch ($imageType) {
                            case 'image/gif':
                                $source = imagecreatefromgif($newFile);
                                break;
                            case 'image/pjpeg':
                            case 'image/jpeg':
                            case 'image/jpg':
                                $source = imagecreatefromjpeg($newFile);
                                break;
                            case 'image/png':
                            case 'image/x-png':
                                imagealphablending($newImage, false);
                                imagesavealpha($newImage, true);
                                $source = imagecreatefrompng($newFile);
                                break;
                        }
                        imagecopyresampled($newImage, $source, 0 - ($new_width - $max_width) / 2, 0 - ($new_height - $max_height) / 2, 0, 0, $new_width, $new_height, $imagewidth, $imageheight);
                        switch ($imageType) {
                            case 'image/gif':
                                imagegif($newImage, $newFile);
                                break;
                            case 'image/pjpeg':
                            case 'image/jpeg':
                            case 'image/jpg':
                                imagejpeg($newImage, $newFile, 90);
                                break;
                            case 'image/png':
                            case 'image/x-png':
                                imagepng($newImage, $newFile);
                                break;
                        }
                    } else {
                        if ($max_width < $imagewidth) {
                            $scale = $max_width / $imagewidth;
                        } else {
                            $scale = 1;
                        }
                        $imageType = image_type_to_mime_type($imageType);
                        $newImageWidth = ceil($imagewidth * $scale);
                        $newImageHeight = ceil($imageheight * $scale);
                        $newImage = imagecreatetruecolor($newImageWidth, $newImageHeight);
                        switch ($imageType) {
                            case 'image/gif':
                                $source = imagecreatefromgif($newFile);
                                break;
                            case 'image/pjpeg':
                            case 'image/jpeg':
                            case 'image/jpg':
                                $source = imagecreatefromjpeg($newFile);
                                break;
                            case 'image/png':
                            case 'image/x-png':
                                imagealphablending($newImage, false);
                                imagesavealpha($newImage, true);
                                $source = imagecreatefrompng($newFile);
                                break;
                        }
                        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newImageWidth, $newImageHeight, $imagewidth, $imageheight);
                        switch ($imageType) {
                            case 'image/gif':
                                imagegif($newImage, $newFile);
                                break;
                            case 'image/pjpeg':
                            case 'image/jpeg':
                            case 'image/jpg':
                                imagejpeg($newImage, $newFile, 90);
                                break;
                            case 'image/png':
                            case 'image/x-png':
                                imagepng($newImage, $newFile);
                                break;
                        }
                    }
                }
            }
        }
        if ($root_directory != '/') {
            $newFile = str_replace($root_directory, '', $newFile);
        }

        return $newFile;
    }

    public function Generate($request)
    {
        global $root_directory;
        global $vtiger_current_version;
        $record = $request->get('record');
        $recordDetail = $this->getDataDetails($record);
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $temp_folder = 'vlayout';
            $temp_file = 'Login.Custom.';
        } else {
            $temp_folder = 'v7';
            $temp_file = 'Login.';
        }
        $content = file_get_contents($root_directory . 'layouts/' . $temp_folder . '/modules/UserLogin/' . $temp_file . 'tpl');
        $slide_html = '';
        if (!empty($recordDetail['images'])) {
            foreach ($recordDetail['images'] as $k => $image) {
                $slide_html .= "<li>\n                                <div id=\"slide" . $k . "\" class=\"slide\">\n                                    <img class=\"pull-left\" src=\"" . $image . "\">\n                                </div>\n                            </li>";
            }
        }
        $social_icons = '';
        if ($recordDetail['social_facebook']) {
            $social_icons .= '<a class="social" target="_blank" href="' . $recordDetail['social_facebook'] . "\">\n                                            <i class=\"icon-social-facebook icons\"></i>\n                                        </a>";
        }
        if ($recordDetail['social_twitter']) {
            $social_icons .= '<a class="social" target="_blank" href="' . $recordDetail['social_twitter'] . "\">\n                                            <i class=\"icon-social-twitter icons\"></i>\n                                        </a>";
        }
        if ($recordDetail['social_linkedin']) {
            $social_icons .= '<a class="social" target="_blank" href="' . $recordDetail['social_linkedin'] . "\">\n                                            <i class=\"icon-social-linkedin icons\"></i>\n                                        </a>";
        }
        if ($recordDetail['social_youtube']) {
            $social_icons .= '<a class="social" target="_blank" href="' . $recordDetail['social_youtube'] . "\">\n                                            <i class=\"icon-social-youtube icons\"></i>\n                                        </a>";
        }
        $pattern = ['$USER_LOGIN_CUSTOM_LOGO$', '$USER_LOGIN_CUSTOM_SLIDE_TYPE$', '$USER_LOGIN_CUSTOM_SLIDE_SPEED$', '$USER_LOGIN_CUSTOM_SLIDE_EASING$', '$USER_LOGIN_CUSTOM_SLIDE_IMAGES$', '$USER_LOGIN_CUSTOM_HEADER$', '$USER_LOGIN_CUSTOM_DESCRIPTION$', '$USER_LOGIN_CUSTOM_SOCIAL_ICONS$', '$USER_LOGIN_CUSTOM_COPYRIGHT$'];
        $replace = [$recordDetail['logo'], $recordDetail['slide_type'], $recordDetail['slide_speed'], $recordDetail['slide_easing'], $slide_html, $recordDetail['header'], $recordDetail['description'], $social_icons, $recordDetail['copyright']];
        $content = str_replace($pattern, $replace, $content);
        if (file_exists($root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . 'tpl')) {
            if (is_writeable($root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . 'tpl')) {
                copy($root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . 'tpl', $root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . strtotime('now') . '.tpl');
                $fp = fopen($root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . 'tpl', 'w');
                fwrite($fp, $content);
                fclose($fp);
            } else {
                return false;
            }
        } else {
            if (is_writeable($root_directory . 'layouts/' . $temp_folder . '/modules/Users')) {
                $fp = fopen($root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . 'tpl', 'w');
                fwrite($fp, $content);
                fclose($fp);
            } else {
                return false;
            }
        }

        return true;
    }

    public function Restore($request)
    {
        global $root_directory;
        global $vtiger_current_version;
        if (version_compare($vtiger_current_version, '7.0.0', '<')) {
            $temp_folder = 'vlayout';
            $temp_file = 'Login.Custom.';
        } else {
            $temp_folder = 'v7';
            $temp_file = 'Login.';
        }
        if (file_exists($root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . 'tpl')) {
            if (is_writeable($root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . 'tpl')) {
                copy($root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . 'tpl', $root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . strtotime('now') . '.tpl');
                unlink($root_directory . 'layouts/' . $temp_folder . '/modules/Users/' . $temp_file . 'tpl');
                if ($temp_folder == 'v7') {
                    $bak_files = glob($root_directory . 'layouts/v7/modules/Users/Login.*.tpl');
                    $org_file = $bak_files[0];
                    if (file_exists($org_file)) {
                        copy($org_file, $root_directory . 'layouts/v7/modules/Users/Login.tpl');
                    }
                }

                return true;
            }

            return false;
        }

        return false;
    }

    public function SaveImageSettings($data)
    {
        $logo_method = $data->get('vte-logo-method', 'resize');
        $logo_width = (int) $data->get('vte-logo-width', 150);
        if ($logo_width == 0) {
            $logo_width = 150;
        }
        $logo_height = (int) $data->get('vte-logo-height', 150);
        if ($logo_height == 0) {
            $logo_height = 150;
        }
        $slide_method = $data->get('vte-slide-method', 'resize');
        $slide_width = (int) $data->get('vte-slide-width', 665);
        if ($slide_width == 0) {
            $slide_width = 665;
        }
        $slide_height = (int) $data->get('vte-slide-height', 667);
        if ($slide_height == 0) {
            $slide_height = 667;
        }
        $logo_result = $this->db->pquery('SELECT * FROM vte_user_login_image_setting WHERE `type`=?', ['logo']);
        $logo_params = [$logo_method, $logo_width, $logo_height, 'logo'];
        if ($this->db->num_rows($logo_result)) {
            $this->db->pquery('UPDATE vte_user_login_image_setting SET `method` = ?, `width` = ?, `height` = ? WHERE `type`=?', $logo_params);
        } else {
            $this->db->pquery('INSERT INTO vte_user_login_image_setting(`method`, `width`, `height`, `type`) VALUES (?,?,?,?)', $logo_params);
        }
        $slide_result = $this->db->pquery('SELECT * FROM vte_user_login_image_setting WHERE `type`=?', ['slide']);
        $slide_params = [$slide_method, $slide_width, $slide_height, 'slide'];
        if ($this->db->num_rows($slide_result)) {
            $this->db->pquery('UPDATE vte_user_login_image_setting SET `method` = ?, `width` = ?, `height` = ? WHERE `type`=?', $slide_params);
        } else {
            $this->db->pquery('INSERT INTO vte_user_login_image_setting(`method`, `width`, `height`, `type`) VALUES (?,?,?,?)', $slide_params);
        }

        return true;
    }

    public function getImageSettings()
    {
        $data = [];
        $result = $this->db->pquery('SELECT * FROM vte_user_login_image_setting');
        if ($this->db->num_rows($result)) {
            while ($row = $this->db->fetchByAssoc($result)) {
                $data[$row['type']] = $row;
            }
        }

        return $data;
    }
}
