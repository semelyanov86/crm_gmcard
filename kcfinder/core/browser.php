<?php

/** This file is part of KCFinder project.
 *
 *      @desc Browser actions class
 *   @version 2.21
 *    @author Pavel Tzonkov <pavelc@users.sourceforge.net>
 * @copyright 2010 KCFinder Project
 *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
 *      @see http://kcfinder.sunhater.com
 */
class browser extends uploader
{
    protected $action;

    protected $thumbsDir;

    protected $thumbsTypeDir;

    public function __construct()
    {
        parent::__construct();

        if (isset($this->post['dir'])) {
            $dir = $this->checkInputDir($this->post['dir'], true, false);
            if ($dir === false) {
                unset($this->post['dir']);
            }
            $this->post['dir'] = $dir;
        }

        if (isset($this->get['dir'])) {
            $dir = $this->checkInputDir($this->get['dir'], true, false);
            if ($dir === false) {
                unset($this->get['dir']);
            }
            $this->get['dir'] = $dir;
        }

        $thumbsDir = $this->config['uploadDir'] . '/' . $this->config['thumbsDir'];
        if ((
            !is_dir($thumbsDir)
                && !@mkdir($thumbsDir, $this->config['dirPerms'])
        )

            || !is_readable($thumbsDir)
            || !dir::isWritable($thumbsDir)
            || (
                !is_dir("{$thumbsDir}/{$this->type}")
                && !@mkdir("{$thumbsDir}/{$this->type}", $this->config['dirPerms'])
            )
        ) {
            $this->errorMsg('Cannot access or create thumbnails folder.');
        }

        $this->thumbsDir = $thumbsDir;
        $this->thumbsTypeDir = "{$thumbsDir}/{$this->type}";

        // Remove temporary zip downloads if exists
        $files = dir::content($this->config['uploadDir'], [
            'types' => 'file',
            'pattern' => '/^.*\.zip$/i',
        ]);

        if (is_array($files) && count($files)) {
            $time = time();
            foreach ($files as $file) {
                if (is_file($file) && ($time - filemtime($file) > 3_600)) {
                    unlink($file);
                }
            }
        }
    }

    public function action()
    {
        $act = $this->get['act'] ?? 'browser';
        if (!method_exists($this, "act_{$act}")) {
            $act = 'browser';
        }
        $this->action = $act;
        $method = "act_{$act}";

        if ($this->config['disabled']) {
            $message = $this->label("You don't have permissions to browse server.");
            if (in_array($act, ['browser', 'upload'])
                || (substr($act, 0, 8) == 'download')
            ) {
                $this->backMsg($message);
            } else {
                header("Content-Type: text/xml; charset={$this->charset}");
                exit($this->output(['message' => $message], 'error'));
            }
        }

        if (!isset($this->session['dir'])) {
            $this->session['dir'] = $this->type;
        } else {
            $type = $this->getTypeFromPath($this->session['dir']);
            $dir = $this->config['uploadDir'] . '/' . $this->session['dir'];
            if (($type != $this->type) || !is_dir($dir) || !is_readable($dir)) {
                $this->session['dir'] = $this->type;
            }
        }
        $this->session['dir'] = path::normalize($this->session['dir']);

        if ($act == 'browser') {
            header('X-UA-Compatible: chrome=1');
            header("Content-Type: text/html; charset={$this->charset}");
        } elseif (
            (substr($act, 0, 8) != 'download')
            && !in_array($act, ['thumb', 'upload'])
        ) {
            header("Content-Type: text/xml; charset={$this->charset}");
        } elseif ($act != 'thumb') {
            header("Content-Type: text/html; charset={$this->charset}");
        }

        $return = $this->{$method}();
        echo ($return === true)
            ? '<root></root>'
            : $return;
    }

    protected function act_browser()
    {
        if (isset($this->get['dir'])
            && is_dir("{$this->typeDir}/{$this->get['dir']}")
            && is_readable("{$this->typeDir}/{$this->get['dir']}")
        ) {
            $this->session['dir'] = path::normalize("{$this->type}/{$this->get['dir']}");
        }

        return $this->output();
    }

    protected function act_init()
    {
        $tree = $this->getDirInfo($this->typeDir);
        $tree['dirs'] = $this->getTree($this->session['dir']);
        if (!is_array($tree['dirs']) || !count($tree['dirs'])) {
            unset($tree['dirs']);
        }
        $tree = $this->xmlTree($tree);
        $files = $this->getFiles($this->session['dir']);
        $dirWritable = dir::isWritable("{$this->config['uploadDir']}/{$this->session['dir']}");
        $data = [
            'tree' => &$tree,
            'files' => &$files,
            'dirWritable' => $dirWritable,
        ];

        return $this->output($data);
    }

    protected function act_thumb()
    {
        if (!isset($this->get['file'])) {
            $this->sendDefaultThumb();
        }
        $file = $this->get['file'];
        if (basename($file) != $file) {
            $this->sendDefaultThumb();
        }
        $file = "{$this->thumbsDir}/{$this->session['dir']}/{$file}";
        if (!is_file($file) || !is_readable($file)) {
            $file = "{$this->config['uploadDir']}/{$this->session['dir']}/" . basename($file);
            if (!is_file($file) || !is_readable($file)) {
                $this->sendDefaultThumb($file);
            }
            $image = new gd($file);
            if ($image->init_error) {
                $this->sendDefaultThumb($file);
            }
            $browsable = [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_JPEG2000, IMAGETYPE_PNG];
            if (in_array($image->type, $browsable)
                && ($image->get_width() <= $this->config['thumbWidth'])
                && ($image->get_height() <= $this->config['thumbHeight'])
            ) {
                $type
                    = ($image->type == IMAGETYPE_GIF) ? 'gif' : (
                        ($image->type == IMAGETYPE_PNG) ? 'png' : 'jpeg'
                    );
                $type = "image/{$type}";
                httpCache::file($file, $type);
            } else {
                $this->sendDefaultThumb($file);
            }
        }
        httpCache::file($file, 'image/jpeg');
    }

    protected function act_expand()
    {
        return $this->output(['dirs' => $this->getDirs($this->postDir())]);
    }

    protected function act_chDir()
    {
        $this->postDir(); // Just for existing check
        $this->session['dir'] = $this->type . '/' . $this->post['dir'];
        $dirWritable = dir::isWritable("{$this->config['uploadDir']}/{$this->session['dir']}");

        return $this->output([
            'files' => $this->getFiles($this->session['dir']),
            'dirWritable' => $dirWritable,
        ]);
    }

    protected function act_newDir()
    {
        if ($this->config['readonly']
            || !isset($this->post['dir'])
            || !isset($this->post['newDir'])
        ) {
            $this->errorMsg('Unknown error.');
        }

        $dir = $this->postDir();
        $newDir = trim($this->post['newDir']);
        if (!strlen($newDir)) {
            $this->errorMsg('Please enter new folder name.');
        }
        if (preg_match('/[\/\\\]/s', $newDir)) {
            $this->errorMsg('Unallowable characters in folder name.');
        }
        if (substr($newDir, 0, 1) == '.') {
            $this->errorMsg("Folder name shouldn't begins with '.'");
        }
        if (file_exists("{$dir}/{$newDir}")) {
            $this->errorMsg('A file or folder with that name already exists.');
        }
        if (!@mkdir("{$dir}/{$newDir}", $this->config['dirPerms'])) {
            $this->errorMsg('Cannot create {dir} folder.', ['dir' => $newDir]);
        }

        return true;
    }

    protected function act_renameDir()
    {
        if ($this->config['readonly']
            || !isset($this->post['dir'])
            || !isset($this->post['newName'])
        ) {
            $this->errorMsg('Unknown error.');
        }

        $dir = $this->postDir();
        $newName = trim($this->post['newName']);
        if (!strlen($newName)) {
            $this->errorMsg('Please enter new folder name.');
        }
        if (preg_match('/[\/\\\]/s', $newName)) {
            $this->errorMsg('Unallowable characters in folder name.');
        }
        if (substr($newName, 0, 1) == '.') {
            $this->errorMsg("Folder name shouldn't begins with '.'");
        }
        if (!@rename($dir, dirname($dir) . "/{$newName}")) {
            $this->errorMsg('Cannot rename the folder.');
        }
        $thumbDir = "{$this->thumbsTypeDir}/{$this->post['dir']}";
        if (is_dir($thumbDir)) {
            @rename($thumbDir, dirname($thumbDir) . "/{$newName}");
        }

        return $this->output(['name' => $newName]);
    }

    protected function act_deleteDir()
    {
        if ($this->config['readonly']
            || !isset($this->post['dir'])
            || !strlen(trim($this->post['dir']))
        ) {
            $this->errorMsg('Unknown error.');
        }

        $dir = $this->postDir();

        if (!dir::isWritable($dir)) {
            $this->errorMsg('Cannot delete the folder.');
        }
        $result = !dir::prune($dir, false);
        if (is_array($result) && count($result)) {
            $this->errorMsg(
                'Failed to delete {count} files/folders.',
                ['count' => count($result)],
            );
        }
        $thumbDir = "{$this->thumbsTypeDir}/{$this->post['dir']}";
        if (is_dir($thumbDir)) {
            dir::prune($thumbDir);
        }

        return $this->output();
    }

    protected function act_upload()
    {
        if ($this->config['readonly'] || !isset($this->post['dir'])) {
            $this->errorMsg('Unknown error.');
        }

        $dir = $this->postDir();

        if (!dir::isWritable($dir)) {
            $this->errorMsg('Cannot access or write to upload folder.');
        }

        $message = $this->checkUploadedFile();

        if ($message !== true) {
            if (isset($this->file['tmp_name'])) {
                @unlink($this->file['tmp_name']);
            }
            $this->errorMsg($message);
        }

        $sanitizedFilename = file::sanitizeFileName($this->file['name']);
        $target = "{$dir}/" . file::getInexistantFilename($sanitizedFilename, $dir);

        if (!@move_uploaded_file($this->file['tmp_name'], $target)
            && !@rename($this->file['tmp_name'], $target)
            && !@copy($this->file['tmp_name'], $target)
        ) {
            @unlink($this->file['tmp_name']);
            $this->errorMsg('Cannot move uploaded file to target folder.');
        } elseif (function_exists('chmod')) {
            chmod($target, $this->config['filePerms']);
        }

        $this->makeThumb($target);

        return '/' . basename($target);
    }

    protected function act_download()
    {
        $dir = $this->postDir();
        if (!isset($this->post['dir'])
            || !isset($this->post['file'])
            || (false === ($file = "{$dir}/{$this->post['file']}"))
            || !file_exists($file) || !is_readable($file)
        ) {
            $this->errorMsg('Unknown error.');
        }

        if (!$this->filePathAccessible($file)) {
            $this->errorMsg('Invalid file location access.');
        }

        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '_', $this->post['file']) . '"');
        header('Content-Transfer-Encoding:­ binary');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    protected function act_rename()
    {
        $dir = $this->postDir();
        if ($this->config['readonly']
            || !isset($this->post['dir'])
            || !isset($this->post['file'])
            || !isset($this->post['newName'])
            || (false === ($file = "{$dir}/{$this->post['file']}"))
            || !file_exists($file) || !is_readable($file) || !file::isWritable($file)
        ) {
            $this->errorMsg('Unknown error.');
        }

        if (!$this->filePathAccessible($file)) {
            $this->errorMsg('Invalid file location access.');
        }

        $newName = trim($this->post['newName']);
        if (!strlen($newName)) {
            $this->errorMsg('Please enter new file name.');
        }
        if (preg_match('/[\/\\\]/s', $newName)) {
            $this->errorMsg('Unallowable characters in file name.');
        }
        if (substr($newName, 0, 1) == '.') {
            $this->errorMsg("File name shouldn't begins with '.'");
        }
        $newName = "{$dir}/{$newName}";
        if (file_exists($newName)) {
            $this->errorMsg('A file or folder with that name already exists.');
        }
        $ext = file::getExtension($newName);
        if (!$this->validateExtension($ext, $this->type)) {
            $this->errorMsg('Denied file extension.');
        }
        if (!@rename($file, $newName)) {
            $this->errorMsg('Unknown error.');
        }

        $thumbDir = "{$this->thumbsTypeDir}/{$this->post['dir']}";
        $thumbFile = "{$thumbDir}/{$this->post['file']}";

        if (file_exists($thumbFile)) {
            @rename($thumbFile, "{$thumbDir}/" . basename($newName));
        }

        return true;
    }

    protected function act_delete()
    {
        $dir = $this->postDir();

        $file = "{$dir}/{$this->post['file']}";
        if (!$this->filePathAccessible($file)) {
            $this->errorMsg('Invalid file location access.');
        }

        if ($this->config['readonly']
            || !isset($this->post['dir'])
            || !isset($this->post['file'])
            || (false === ($file = "{$dir}/{$this->post['file']}"))
            || !file_exists($file) || !is_readable($file) || !file::isWritable($file)
            || !@unlink($file)
        ) {
            $this->errorMsg('Unknown error.');
        }

        $thumb = "{$this->thumbsTypeDir}/{$this->post['dir']}/{$this->post['file']}";
        if (file_exists($thumb)) {
            @unlink($thumb);
        }

        return true;
    }

    protected function act_cp_cbd()
    {
        $dir = $this->postDir();
        if ($this->config['readonly']
            || !isset($this->post['dir'])
            || !is_dir($dir) || !is_readable($dir) || !dir::isWritable($dir)
            || !isset($this->post['files']) || !is_array($this->post['files'])
            || !count($this->post['files'])
        ) {
            $this->errorMsg('Unknown error.');
        }

        $error = [];
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if (substr($file, 0, 1) == '.') {
                continue;
            }
            $type = explode('/', $file);
            $type = $type[0];
            if ($type != $this->type) {
                continue;
            }
            $path = "{$this->config['uploadDir']}/{$file}";
            $base = basename($file);
            $replace = ['file' => $base];
            $ext = file::getExtension($base);
            if (!file_exists($path)) {
                $error[] = $this->label("The file '{file}' does not exist.", $replace);
            } elseif (substr($base, 0, 1) == '.') {
                $error[] = "{$base}: " . $this->label("File name shouldn't begins with '.'");
            } elseif (!$this->validateExtension($ext, $type)) {
                $error[] = "{$base}: " . $this->label('Denied file extension.');
            } elseif (file_exists("{$dir}/{$base}")) {
                $error[] = "{$base}: " . $this->label('A file or folder with that name already exists.');
            } elseif (!is_readable($path) || !is_file($path)) {
                $error[] = $this->label("Cannot read '{file}'.", $replace);
            } elseif (!@copy($path, "{$dir}/{$base}")) {
                $error[] = $this->label("Cannot copy '{file}'.", $replace);
            } else {
                if (function_exists('chmod')) {
                    @chmod("{$dir}/{$base}", $this->config['filePerms']);
                }
                $fromThumb = "{$this->thumbsDir}/{$file}";
                if (is_file($fromThumb) && is_readable($fromThumb)) {
                    $toThumb = "{$this->thumbsTypeDir}/{$this->post['dir']}";
                    if (!is_dir($toThumb)) {
                        @mkdir($toThumb, $this->config['dirPerms'], true);
                    }
                    $toThumb .= "/{$base}";
                    @copy($fromThumb, $toThumb);
                }
            }
        }
        if (count($error)) {
            return $this->output(['message' => $error], 'error');
        }

        return true;
    }

    protected function act_mv_cbd()
    {
        $dir = $this->postDir();
        if ($this->config['readonly']
            || !isset($this->post['dir'])
            || !is_dir($dir) || !is_readable($dir) || !dir::isWritable($dir)
            || !isset($this->post['files']) || !is_array($this->post['files'])
            || !count($this->post['files'])
        ) {
            $this->errorMsg('Unknown error.');
        }

        $error = [];
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if (substr($file, 0, 1) == '.') {
                continue;
            }
            $type = explode('/', $file);
            $type = $type[0];
            if ($type != $this->type) {
                continue;
            }
            $path = "{$this->config['uploadDir']}/{$file}";
            $base = basename($file);
            $replace = ['file' => $base];
            $ext = file::getExtension($base);
            if (!file_exists($path)) {
                $error[] = $this->label("The file '{file}' does not exist.", $replace);
            } elseif (substr($base, 0, 1) == '.') {
                $error[] = "{$base}: " . $this->label("File name shouldn't begins with '.'");
            } elseif (!$this->validateExtension($ext, $type)) {
                $error[] = "{$base}: " . $this->label('Denied file extension.');
            } elseif (file_exists("{$dir}/{$base}")) {
                $error[] = "{$base}: " . $this->label('A file or folder with that name already exists.');
            } elseif (!is_readable($path) || !is_file($path)) {
                $error[] = $this->label("Cannot read '{file}'.", $replace);
            } elseif (!file::isWritable($path) || !@rename($path, "{$dir}/{$base}")) {
                $error[] = $this->label("Cannot move '{file}'.", $replace);
            } else {
                if (function_exists('chmod')) {
                    @chmod("{$dir}/{$base}", $this->config['filePerms']);
                }
                $fromThumb = "{$this->thumbsDir}/{$file}";
                if (is_file($fromThumb) && is_readable($fromThumb)) {
                    $toThumb = "{$this->thumbsTypeDir}/{$this->post['dir']}";
                    if (!is_dir($toThumb)) {
                        @mkdir($toThumb, $this->config['dirPerms'], true);
                    }
                    $toThumb .= "/{$base}";
                    @rename($fromThumb, $toThumb);
                }
            }
        }
        if (count($error)) {
            return $this->output(['message' => $error], 'error');
        }

        return true;
    }

    protected function act_rm_cbd()
    {
        if ($this->config['readonly']
            || !isset($this->post['files'])
            || !is_array($this->post['files'])
            || !count($this->post['files'])
        ) {
            $this->errorMsg('Unknown error.');
        }

        $error = [];
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if (substr($file, 0, 1) == '.') {
                continue;
            }
            $type = explode('/', $file);
            $type = $type[0];
            if ($type != $this->type) {
                continue;
            }
            $path = "{$this->config['uploadDir']}/{$file}";
            $base = basename($file);
            $replace = ['file' => $base];
            if (!is_file($path)) {
                $error[] = $this->label("The file '{file}' does not exist.", $replace);
            } elseif (!@unlink($path)) {
                $error[] = $this->label("Cannot delete '{file}'.", $replace);
            } else {
                $thumb = "{$this->thumbsDir}/{$file}";
                if (is_file($thumb)) {
                    @unlink($thumb);
                }
            }
        }
        if (count($error)) {
            return $this->output(['message' => $error], 'error');
        }

        return true;
    }

    protected function act_downloadDir()
    {
        $dir = $this->postDir();
        if (!isset($this->post['dir']) || $this->config['denyZipDownload']) {
            $this->errorMsg('Unknown error.');
        }
        $filename = basename($dir) . '.zip';
        do {
            $file = md5(time() . session_id());
            $file = "{$this->config['uploadDir']}/{$file}.zip";
        } while (file_exists($file));
        new zipFolder($file, $dir);
        header('Content-Type: application/x-zip');
        header('Content-Disposition: attachment; filename="' . str_replace('"', '_', $filename) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        unlink($file);
        exit;
    }

    protected function act_downloadSelected()
    {
        $dir = $this->postDir();
        if (!isset($this->post['dir'])
            || !isset($this->post['files'])
            || !is_array($this->post['files'])
            || $this->config['denyZipDownload']
        ) {
            $this->errorMsg('Unknown error.');
        }

        $zipFiles = [];
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if ((substr($file, 0, 1) == '.') || (strpos($file, '/') !== false)) {
                continue;
            }
            $file = "{$dir}/{$file}";
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            $zipFiles[] = $file;
        }

        do {
            $file = md5(time() . session_id());
            $file = "{$this->config['uploadDir']}/{$file}.zip";
        } while (file_exists($file));

        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE);
        if ($res === true) {
            foreach ($zipFiles as $cfile) {
                $zip->addFile($cfile, basename($cfile));
            }
            $zip->close();
        }
        header('Content-Type: application/x-zip');
        header('Content-Disposition: attachment; filename="selected_files_' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        unlink($file);
        exit;
    }

    protected function act_downloadClipboard()
    {
        if (!isset($this->post['files'])
            || !is_array($this->post['files'])
            || $this->config['denyZipDownload']
        ) {
            $this->errorMsg('Unknown error.');
        }

        $zipFiles = [];
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if (substr($file, 0, 1) == '.') {
                continue;
            }
            $type = explode('/', $file);
            $type = $type[0];
            if ($type != $this->type) {
                continue;
            }
            $file = $this->config['uploadDir'] . "/{$file}";
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            $zipFiles[] = $file;
        }

        do {
            $file = md5(time() . session_id());
            $file = "{$this->config['uploadDir']}/{$file}.zip";
        } while (file_exists($file));

        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE);
        if ($res === true) {
            foreach ($zipFiles as $cfile) {
                $zip->addFile($cfile, basename($cfile));
            }
            $zip->close();
        }
        header('Content-Type: application/x-zip');
        header('Content-Disposition: attachment; filename="clipboard_' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        unlink($file);
        exit;
    }

    protected function sendDefaultThumb($file = null)
    {
        if ($file !== null) {
            $ext = file::getExtension($file);
            $thumb = "themes/{$this->config['theme']}/img/files/big/{$ext}.png";
        }
        if (!isset($thumb) || !file_exists($thumb)) {
            $thumb = "themes/{$this->config['theme']}/img/files/big/..png";
        }
        header('Content-Type: image/png');
        readfile($thumb);
        exit;
    }

    protected function getFiles($dir)
    {
        $thumbDir = "{$this->config['uploadDir']}/{$this->config['thumbsDir']}/{$dir}";
        $dir = "{$this->config['uploadDir']}/{$dir}";
        $return = [];
        $files = dir::content($dir, ['types' => 'file']);
        if ($files === false) {
            return $return;
        }

        foreach ($files as $file) {
            $this->makeThumb($file, false);
            $image = new gd($file);
            $image = !$image->init_error
                && ($image->get_width() <= $this->config['thumbWidth'])
                && ($image->get_height() <= $this->config['thumbHeight']);
            $stat = stat($file);
            if ($stat === false) {
                continue;
            }
            $name = basename($file);
            $ext = file::getExtension($file);
            $bigIcon = file_exists("themes/{$this->config['theme']}/img/files/big/{$ext}.png");
            $smallIcon = file_exists("themes/{$this->config['theme']}/img/files/small/{$ext}.png");
            $thumb = file_exists("{$thumbDir}/{$name}");
            $return[] = [
                'name' => stripcslashes($name),
                'size' => $stat['size'],
                'mtime' => $stat['mtime'],
                'date' => @strftime($this->dateTimeSmall, $stat['mtime']),
                'readable' => is_readable($file),
                'writable' => file::isWritable($file),
                'bigIcon' => $bigIcon,
                'smallIcon' => $smallIcon,
                'thumb' => $thumb,
                'smallThumb' => $image,
            ];
        }

        return $return;
    }

    protected function xmlTree(array $tree)
    {
        $xml = '<dir readable="' . ($tree['readable'] ? 'yes' : 'no') . '" writable="' . ($tree['writable'] ? 'yes' : 'no') . '" removable="' . ($tree['removable'] ? 'yes' : 'no') . '" hasDirs="' . ($tree['hasDirs'] ? 'yes' : 'no') . '"' . (isset($tree['current']) ? ' current="yes"' : '') . '><name>' . text::xmlData($tree['name']) . '</name>';
        if (isset($tree['dirs']) && is_array($tree['dirs']) && count($tree['dirs'])) {
            $xml .= '<dirs>';
            foreach ($tree['dirs'] as $dir) {
                $xml .= $this->xmlTree($dir);
            }
            $xml .= '</dirs>';
        }
        $xml .= '</dir>';

        return $xml;
    }

    protected function getTree($dir, $index = 0)
    {
        $path = explode('/', $dir);

        $pdir = '';
        for ($i = 0; $i <= $index && $i < count($path); ++$i) {
            $pdir .= "/{$path[$i]}";
        }
        if (strlen($pdir)) {
            $pdir = substr($pdir, 1);
        }

        $fdir = "{$this->config['uploadDir']}/{$pdir}";

        $dirs = $this->getDirs($fdir);

        if (is_array($dirs) && count($dirs) && ($index <= count($path) - 1)) {

            foreach ($dirs as $i => $cdir) {
                if ($cdir['hasDirs']
                    && (
                        ($index == count($path) - 1)
                        || ($cdir['name'] == $path[$index + 1])
                    )
                ) {
                    $dirs[$i]['dirs'] = $this->getTree($dir, $index + 1);
                    if (!is_array($dirs[$i]['dirs']) || !count($dirs[$i]['dirs'])) {
                        unset($dirs[$i]['dirs']);

                        continue;
                    }
                }
            }
        } else {
            return false;
        }

        return $dirs;
    }

    protected function postDir($existent = true)
    {
        $dir = $this->typeDir;
        if (isset($this->post['dir'])) {
            $dir .= '/' . $this->post['dir'];
        }
        if ($existent && (!is_dir($dir) || !is_readable($dir))) {
            $this->errorMsg('Inexistant or inaccessible folder.');
        }

        return $dir;
    }

    protected function getDir($existent = true)
    {
        $dir = $this->typeDir;
        if (isset($this->get['dir'])) {
            $dir .= '/' . $this->get['dir'];
        }
        if ($existent && (!is_dir($dir) || !is_readable($dir))) {
            $this->errorMsg('Inexistant or inaccessible folder.');
        }

        return $dir;
    }

    protected function getDirs($dir)
    {
        $dirs = dir::content($dir, ['types' => 'dir']);
        $return = [];
        if (is_array($dirs)) {
            $writable = dir::isWritable($dir);
            foreach ($dirs as $cdir) {
                $info = $this->getDirInfo($cdir);
                if ($info === false) {
                    continue;
                }
                $info['removable'] = $writable && $info['writable'];
                $return[] = $info;
            }
        }

        return $return;
    }

    protected function getDirInfo($dir, $removable = false)
    {
        if ((substr(basename($dir), 0, 1) == '.') || !is_dir($dir) || !is_readable($dir)) {
            return false;
        }
        $dirs = dir::content($dir, ['types' => 'dir']);
        if (is_array($dirs)) {
            foreach ($dirs as $key => $cdir) {
                if (substr(basename($cdir), 0, 1) == '.') {
                    unset($dirs[$key]);
                }
            }
            $hasDirs = count($dirs) ? true : false;
        } else {
            $hasDirs = false;
        }

        $writable = dir::isWritable($dir);
        $info = [
            'name' => stripslashes(basename($dir)),
            'readable' => is_readable($dir),
            'writable' => $writable,
            'removable' => $removable && $writable && dir::isWritable(dirname($dir)),
            'hasDirs' => $hasDirs,
        ];

        if ($dir == "{$this->config['uploadDir']}/{$this->session['dir']}") {
            $info['current'] = true;
        }

        return $info;
    }

    protected function output($data = null, $template = null)
    {
        if (!is_array($data)) {
            $data = [];
        }
        if ($template === null) {
            $template = $this->action;
        }

        if (file_exists("tpl/tpl_{$template}.php")) {
            ob_start();
            $eval = 'unset($data);unset($template);unset($eval);';
            $_ = $data;
            foreach (array_keys($data) as $key) {
                if (preg_match('/^[a-z\d_]+$/i', $key)) {
                    $eval .= "\${$key}=\$_['{$key}'];";
                }
            }
            $eval .= "unset(\$_);require \"tpl/tpl_{$template}.php\";";
            eval($eval);

            return ob_get_clean();
        }

        return '';
    }

    protected function errorMsg($message, ?array $data = null)
    {
        if (in_array($this->action, ['thumb', 'upload', 'download', 'downloadDir'])) {
            exit($this->label($message, $data));
        }
        if (($this->action === null) || ($this->action == 'browser')) {
            $this->backMsg($message, $data);
        } else {
            $message = $this->label($message, $data);
            exit($this->output(['message' => $message], 'error'));
        }
    }

    protected function filePathAccessible($file)
    {
        // Ensure the file operation is constrained to the uploadDir configured.
        $uploadDirPath = realpath($this->config['uploadDir']);
        $filePath = realpath($file);
        if (strpos($filePath, $uploadDirPath) !== 0) {
            return false;
        }

        return true;
    }
}
