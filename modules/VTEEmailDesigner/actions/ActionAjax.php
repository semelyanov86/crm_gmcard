<?php

class VTEEmailDesigner_ActionAjax_Action extends Vtiger_Action_Controller
{
    public function checkPermission(Vtiger_Request $request) {}

    public function __construct()
    {
        parent::__construct();
        $this->exposeMethod('enableModule');
        $this->exposeMethod('checkEnable');
        $this->exposeMethod('updateSequence');
        $this->exposeMethod('UpdateBlockInfo');
        $this->exposeMethod('loadTemplates');
        $this->exposeMethod('getTemplateBlocks');
        $this->exposeMethod('doSaveTemplates');
        $this->exposeMethod('doDeleteTemplate');
        $this->exposeMethod('doExportTemplate');
        $this->exposeMethod('uploadImageLocal');
    }

    public function process(Vtiger_Request $request)
    {
        $mode = $request->get('mode');
        if (!empty($mode)) {
            $this->invokeExposedMethod($mode, $request);
        }
    }

    public function enableModule(Vtiger_Request $request)
    {
        global $adb;
        $value = $request->get('value');
        $adb->pquery('UPDATE `vte_custom_header_settings` SET `active`=?', [$value]);
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(['result' => 'success']);
        $response->emit();
    }

    public function checkEnable(Vtiger_Request $request)
    {
        global $adb;
        $rs = $adb->pquery('SELECT `enable` FROM `vte_custom_header_settings`;', []);
        $enable = $adb->query_result($rs, 0, 'active');
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);
        $response->setResult(['enable' => $enable]);
        $response->emit();
    }

    public function UpdateBlockInfo(Vtiger_Request $request)
    {
        global $adb;
        $blockId = $request->get('block_id');
        $sql = 'UPDATE `vteemaildesigner_blocks` SET used_count=used_count+1 WHERE id=?';
        $adb->pquery($sql, [$blockId]);
        $response = new Vtiger_Response();
        $response->setResult('success');
        $response->emit();
    }

    public function doDeleteTemplate(Vtiger_Request $request)
    {
        global $adb;
        $templateid = $request->get('templateid');
        $adb->pquery('DELETE FROM vtiger_emailtemplates WHERE templateid=?', [$templateid]);
        $adb->pquery('DELETE FROM vteemaildesigner_template_blocks WHERE templateid=?', [$templateid]);
        $response = new Vtiger_Response();
        $response->setResult('success');
        $response->emit();
    }

    public function loadTemplates(Vtiger_Request $request)
    {
        global $adb;
        $sql = 'SELECT * FROM `vtiger_emailtemplates` WHERE deleted=0';
        $res = $adb->pquery($sql, []);
        $numRows = $adb->num_rows($res);
        $data = [];
        if ($numRows == -1) {
            $data['code'] = -1;
        }
        if ($numRows == 0) {
            $data['code'] = 1;
        }
        if ($numRows > 0) {
            while ($row = $adb->fetch_row($res)) {
                $rows[] = $row;
            }
        }
        $data['code'] = 0;
        $data['files'] = $rows;
        $response = new Vtiger_Response();
        $response->setResult(['response' => $data]);
        $response->emit();
    }

    public function getTemplateBlocks(Vtiger_Request $request)
    {
        global $adb;
        $tempId = $request->get('templateid');
        $sql = 'SELECT tb.*,b.`property` FROM `vteemaildesigner_template_blocks` as tb INNER JOIN vteemaildesigner_blocks as b on tb.`blockid`=b.`id` WHERE tb.templateid=' . $tempId;
        $res = $adb->pquery($sql, []);
        $numRows = $adb->num_rows($res);
        $data = [];
        if ($numRows == -1) {
            $data['code'] = -1;
        }
        if ($numRows == 0) {
            $data['code'] = 1;
        }
        if ($numRows > 0) {
            while ($row = $adb->fetch_row($res)) {
                $rows[] = $row;
            }
        }
        $data['code'] = 0;
        $data['template'] = $this->getTemplateDetails($tempId);
        $data['blocks'] = $rows;
        $response = new Vtiger_Response();
        $response->setResult(['response' => $data]);
        $response->emit();
    }

    public function getTemplateDetails($tempId)
    {
        global $adb;
        $sql = 'SELECT * FROM `vtiger_emailtemplates` WHERE templateid=' . $tempId;
        $res = $adb->pquery($sql, []);
        $data = [];

        while ($row = $adb->fetch_row($res)) {
            $data[] = $row;
        }

        return $data;
    }

    public function doSaveTemplates(Vtiger_Request $request)
    {
        global $adb;
        $tempId = $request->get('templateid');
        $name = $request->get('name');
        $subject = $request->get('subject');
        $description = $request->get('description');
        $module = $request->get('source_module');
        $bg_color = $request->get('bg_color');
        $email_width = $request->get('email_width');
        $bg_color_inner = $request->get('bg_color_inner');
        $contentArr = $_POST['contentArr'];
        $base64Image = $request->get('base64image');
        $thumbnail = 'thumb_template_' . $tempId . '.png';
        $flag = $request->get('flag');
        if ($base64Image != '') {
            $thumbnailUrl = $this->base64_to_jpeg($base64Image, 'test/template_imgfiles/thumb_template_' . $tempId . '.png');
        } else {
            $thumbnailUrl = $request->get('thumbnailUrl');
        }
        if (!empty($tempId)) {
            $adb->pquery('UPDATE `vtiger_emailtemplates` SET `templatename`=?, `subject`=?, description=?, `module`=?, `templatepath`=?, bg_color=?, email_width=?, bg_color_inner=? WHERE templateid=?', [$name, $subject, $description, $module, $thumbnail, $bg_color, $email_width, $bg_color_inner, $tempId]);
            $adb->pquery('DELETE FROM vteemaildesigner_template_blocks WHERE templateid=?', [$tempId]);
        } else {
            $tempId = $adb->getUniqueID('vtiger_emailtemplates');
            $adb->pquery('INSERT INTO  `vtiger_emailtemplates` (`templateid`,`templatename`, `subject`, description, `module`,`templatepath`, bg_color, email_width, bg_color_inner) VALUES (?,?, ?, ?, ?, ?, ?, ?, ?)', [$tempId, $name, $subject, $description, $module, '', $bg_color, $email_width, $bg_color_inner]);
        }
        if ($bg_color && $bg_color != '') {
            $body = '<table width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:' . $bg_color . '"><tbody><tr><td>';
        } else {
            $body = '';
        }
        for ($i = 0; $i < sizeof($contentArr); ++$i) {
            if (isset($contentArr[$i]['id'])) {
                $body .= $contentArr[$i]['content'];
                $adb->pquery('INSERT INTO  `vteemaildesigner_template_blocks` (`templateid`,`blockid`,`content`) VALUES (?, ?, ?)', [$tempId, $contentArr[$i]['id'], $contentArr[$i]['content']]);
            }
        }
        if ($bg_color && $bg_color != '') {
            $body .= '</td></tr></tbody></table>';
        }
        $adb->pquery('UPDATE `vtiger_emailtemplates` SET body=? WHERE templateid=?', [$body, $tempId]);
        require_once 'modules/VTEEmailDesigner/VTEEmailDesigner.php';
        $VTEEmailDesigner = new VTEEmailDesigner();
        $isTableExist = $VTEEmailDesigner->checkTableExist('vtiger_vteemailmarketing_emailtemplate');
        if ($isTableExist) {
            $rs_check = $adb->pquery('SELECT count(*) as count_data FROM vtiger_vteemailmarketing_emailtemplate WHERE idtemplate=?', [$tempId]);
            if ($adb->query_result($rs_check, 0, 'count_data') == 0) {
                $adb->pquery('INSERT INTO vtiger_vteemailmarketing_emailtemplate (idtemplate) VALUES (?)', [$tempId]);
            }
        }
        $response = new Vtiger_Response();
        $response->setResult(['templateid' => $tempId]);
        $response->emit();
    }

    public function doExportTemplate(Vtiger_Request $request)
    {
        global $adb;
        $html = $_REQUEST['html'];
        $response = $this->createHtmlandZipFile($html);
        echo json_encode($response);
    }

    public function createHtmlandZipFile($html = '')
    {
        global $adb;
        global $root_directory;
        global $site_URL;
        global $current_user;
        $todayh = getdate();
        $filename = 'email-editor-' . $todayh['seconds'] . $todayh['minutes'] . $todayh['hours'] . $todayh['mday'] . $todayh['mon'] . $todayh['year'];
        $rootDirectory = str_replace('\\', '/', $root_directory);
        $rootDirectory = rtrim($rootDirectory, '/');
        if (!is_dir($rootDirectory . '/test')) {
            mkdir($rootDirectory . '/test');
        }
        if (!is_dir($rootDirectory . '/test/VTEEmailDesigner')) {
            mkdir($rootDirectory . '/test/VTEEmailDesigner');
        }
        $newHtmlFilename = $rootDirectory . '/test/VTEEmailDesigner/' . $filename . '.html';
        $zipFilename = $rootDirectory . '/test/VTEEmailDesigner/' . $filename . '.zip';
        $zipFileUrl = $site_URL . 'test/VTEEmailDesigner/' . $filename . '.zip';
        $htmlFileUrl = $site_URL . 'test/VTEEmailDesigner/' . $filename . '.html';
        $file = $root_directory . 'layouts/v7/modules/VTEEmailDesigner/resources/assets/template.html';
        $templateContent = file_get_contents($file, true);
        $new_content = $html;
        $new_content = str_replace('#view_web', $htmlFileUrl, $new_content);
        $content = str_replace('[email-body]', $new_content, $templateContent);
        $fp = fopen($newHtmlFilename, 'wb');
        fwrite($fp, $content);
        fclose($fp);
        $zip = new ZipArchive();
        $zip->open($zipFilename, ZipArchive::CREATE);
        $zip->addFile($newHtmlFilename);
        $zip->close();
        $response = [];
        $response['code'] = 0;
        $response['url'] = $zipFileUrl;
        $response['preview_url'] = $htmlFileUrl;
        $response['html'] = $new_content;

        return $response;
    }

    public function base64_to_jpeg($base64_string, $output_file)
    {
        $ifp = fopen($output_file, 'wb');
        $data = explode(',', $base64_string);
        fwrite($ifp, base64_decode($data[1]));
        fclose($ifp);

        return $output_file;
    }

    public function uploadImageLocal(Vtiger_Request $request)
    {
        global $site_URL;
        if (!file_exists('test/VTEEmailDesigner/images')) {
            mkdir('test/VTEEmailDesigner/images/', 511, true);
            $target_dir = 'test/VTEEmailDesigner/images/';
        } else {
            $target_dir = 'test/VTEEmailDesigner/images/';
        }
        $target_file = $target_dir . basename($_FILES['file']['name']);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check = getimagesize($_FILES['file']['tmp_name']);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            $messager = 'File is not an image.';
            $uploadOk = 0;
        }
        if (file_exists($target_file)) {
            for ($i = 1; file_exists($target_dir . $i . '_' . basename($_FILES['file']['name'])); ++$i);

            $target_file = $target_dir . $i . '_' . basename($_FILES['file']['name']);
        }
        if ($_FILES['file']['size'] > 5_242_880) {
            $messager = 'Sorry, your file is too large.';
            $uploadOk = 0;
        }
        if ($imageFileType != 'jpg' && $imageFileType != 'png' && $imageFileType != 'jpeg' && $imageFileType != 'gif') {
            $messager = 'Sorry, only JPG, JPEG, PNG & GIF files are allowed.';
            $uploadOk = 0;
        }
        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
                $messager_succes = 'The file ' . basename($_FILES['file']['name']) . ' has been uploaded.';
            } else {
                $messager = 'Sorry, there was an error uploading your file.';
            }
        }
        $data_arr = [];
        $data_arr['file'] = rtrim($site_URL, '/') . '/' . $target_file;
        $data_arr['messager'] = $messager;
        $data_arr['messager_succes'] = $messager_succes;
        $response = new Vtiger_Response();
        $response->setResult(['data_arr' => $data_arr]);
        $response->emit();
    }
}
