<?php

/** This file is part of KCFinder project.
 *
 *      @desc Base configuration file
 *   @version 2.21
 *    @author Pavel Tzonkov <pavelc@users.sourceforge.net>
 * @copyright 2010 KCFinder Project
 *   @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 *   @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
 *      @see http://kcfinder.sunhater.com
 */

// IMPORTANT!!! Do not remove uncommented settings in this file even if
// you are using session configuration.
// See http://kcfinder.sunhater.com/install for setting descriptions

$_CONFIG = [

    'disabled' => true,
    'readonly' => false,
    'denyZipDownload' => true,

    'theme' => 'oxygen',

    'uploadURL' => 'upload',
    'uploadDir' => '',

    'dirPerms' => 0o755,
    'filePerms' => 0o644,

    'deniedExts' => 'exe com msi bat php phps phtml php3 php4 cgi pl', // UPDATED: http://kcfinder.sunhater.com/docs/install

    'types' => [

        // CKEditor & FCKEditor types
        'files'   =>  '',
        'flash'   =>  'swf',
        'images'  =>  '*img',

        // TinyMCE types
        'file'    =>  '',
        'media'   =>  'swf flv avi mpg mpeg qt mov wmv asf rm',
        'image'   =>  '*img',
    ],

    'mime_magic' => '',

    'maxImageWidth' => 0,
    'maxImageHeight' => 0,

    'thumbWidth' => 100,
    'thumbHeight' => 100,

    'thumbsDir' => '.thumbs',

    'jpegQuality' => 90,

    'cookieDomain' => '',
    'cookiePath' => '',
    'cookiePrefix' => 'KCFINDER_',

    // THE FOLLOWING SETTINGS CANNOT BE OVERRIDED WITH SESSION CONFIGURATION

    '_check4htaccess' => true,
    // '_tinyMCEPath' => "/tiny_mce",

    '_sessionVar' => &$_SESSION['KCFINDER'],
    // '_sessionLifetime' => 30,
    // '_sessionDir' => "/full/directory/path",

    // '_sessionDomain' => ".mysite.com",
    // '_sessionPath' => "/my/path",
];
