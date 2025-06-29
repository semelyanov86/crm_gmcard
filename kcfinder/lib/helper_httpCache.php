<?php

/** This file is part of KCFinder project.
 *
 *      @desc HTTP cache helper class
 *   @version 2.21
 *    @author Pavel Tzonkov <pavelc@users.sourceforge.net>
 * @copyright 2010 KCFinder Project
 *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
 *      @see http://kcfinder.sunhater.com
 */
class httpCache
{
    public const DEFAULT_TYPE = 'text/html';
    public const DEFAULT_EXPIRE = 604_800; // in seconds

    /** Cache a file. The $type parameter might define the MIME type of the file
     * or path to magic file to autodetect the MIME type. If you skip $type
     * parameter the method will try with the default magic file. Autodetection
     * of MIME type requires Fileinfo PHP extension used in file::getMimeType().
     * @param string $file
     * @param string $type
     * @param int $expire
     */
    public static function file($file, $type = null, $expire = null, ?array $headers = null)
    {
        $mtime = @filemtime($file);
        if ($mtime !== false) {
            self::checkMTime($mtime);
        }

        if ($type === null) {
            $magic = ((substr($type, 0, 1) == '/') || preg_match('/^[a-z]\:/i', $type))
                ? $type : null;
            $type = file::getMimeType($file, $magic);
            if (!$type) {
                $type = null;
            }
        }

        self::content(@file_get_contents($file), $mtime, $type, $expire, $headers, false);
    }

    /** Cache the given $content with $mtime modification time.
     * @param binary $content
     * @param int $mtime
     * @param string $type
     * @param int $expire
     * @param bool $checkMTime */
    public static function content($content, $mtime, $type = null, $expire = null, ?array $headers = null, $checkMTime = true)
    {
        if ($checkMTime) {
            self::checkMTime($mtime);
        }
        if ($type === null) {
            $type = self::DEFAULT_TYPE;
        }
        if ($expire === null) {
            $expire = self::DEFAULT_EXPIRE;
        }
        $size = strlen($content);
        $expires = gmdate('D, d M Y H:i:s', time() + $expire) . ' GMT';
        header("Content-Type: {$type}");
        header("Expires: {$expires}");
        header("Cache-Control: max-age={$expire}");
        header('Pragma: !invalid');
        header("Content-Length: {$size}");
        if ($headers !== null) {
            foreach ($headers as $header) {
                header($header);
            }
        }
        echo $content;
    }

    /** Check if given modification time is newer than client-side one. If not,
     * the method will tell the client to get the content from its own cache.
     * Afterwards the script process will be terminated. This feature requires
     * the PHP to be configured as Apache module.
     * @param int $mtime */
    public static function checkMTime($mtime)
    {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

        $headers = function_exists('getallheaders')
            ? getallheaders()
            : (function_exists('apache_request_headers')
                ? apache_request_headers()
                : false);

        if (is_array($headers) && isset($headers['If-Modified-Since'])) {
            $client_mtime = explode(';', $headers['If-Modified-Since']);
            $client_mtime = strtotime($client_mtime[0]);
            if ($client_mtime >= $mtime) {
                header('HTTP/1.1 304 Not Modified');
                exit;
            }
        }
    }
}
