<?php

/*
 * The contents of this file are subject to the SugarCRM Public License Version 1.1.2
 * ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an  "AS IS"  basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 * The Original Code is:  SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
*/

// Adjust error_reporting favourable to deployment.
version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED & E_ERROR) : error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED  & E_ERROR & ~E_STRICT); // PRODUCTION
// ini_set('display_errors','on'); version_compare(PHP_VERSION, '5.5.0') <= 0 ? error_reporting(E_WARNING & ~E_NOTICE & ~E_DEPRECATED) : error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);   // DEBUGGING
// ini_set('display_errors','on'); error_reporting(E_ALL); // STRICT DEVELOPMENT


include 'vtigerversion.php';

// more than 8MB memory needed for graphics
// memory limit default value = 64M
ini_set('memory_limit', '512M');

// show or hide calendar, world clock, calculator, chat and CKEditor
// Do NOT remove the quotes if you set these to false!
$CALENDAR_DISPLAY = 'true';
$USE_RTE = 'true';

// helpdesk support email id and support name (Example: 'support@vtiger.com' and 'vtiger support')
$HELPDESK_SUPPORT_EMAIL_ID = '_USER_SUPPORT_EMAIL_';
$HELPDESK_SUPPORT_NAME = 'your-support name';
$HELPDESK_SUPPORT_EMAIL_REPLY_ID = $HELPDESK_SUPPORT_EMAIL_ID;

/* database configuration
      db_server
      db_port
      db_hostname
      db_username
      db_password
      db_name
*/

$dbconfig['db_server'] = '_DBC_SERVER_';
$dbconfig['db_port'] = ':_DBC_PORT_';
$dbconfig['db_username'] = '_DBC_USER_';
$dbconfig['db_password'] = '_DBC_PASS_';
$dbconfig['db_name'] = '_DBC_NAME_';
$dbconfig['db_type'] = '_DBC_TYPE_';
$dbconfig['db_status'] = '_DB_STAT_';

// TODO: test if port is empty
// TODO: set db_hostname dependending on db_type
$dbconfig['db_hostname'] = $dbconfig['db_server'] . $dbconfig['db_port'];

// log_sql default value = false
$dbconfig['log_sql'] = false;

// persistent default value = true
$dbconfigoption['persistent'] = true;

// autofree default value = false
$dbconfigoption['autofree'] = false;

// debug default value = 0
$dbconfigoption['debug'] = 0;

// seqname_format default value = '%s_seq'
$dbconfigoption['seqname_format'] = '%s_seq';

// portability default value = 0
$dbconfigoption['portability'] = 0;

// ssl default value = false
$dbconfigoption['ssl'] = false;

$host_name = $dbconfig['db_hostname'];

$site_URL = '_SITE_URL_';

// url for customer portal (Example: http://vtiger.com/portal)
$PORTAL_URL = $site_URL . '/customerportal';
// root directory path
$root_directory = '_VT_ROOTDIR_';

// cache direcory path
$cache_dir = '_VT_CACHEDIR_';

// tmp_dir default value prepended by cache_dir = images/
$tmp_dir = '_VT_TMPDIR_';

// import_dir default value prepended by cache_dir = import/
$import_dir = 'cache/import/';

// upload_dir default value prepended by cache_dir = upload/
$upload_dir = '_VT_UPLOADDIR_';

// maximum file size for uploaded files in bytes also used when uploading import files
// upload_maxsize default value = 3000000
$upload_maxsize = 3_145_728; // 3MB

// flag to allow export functionality
// 'all' to allow anyone to use exports
// 'admin' to only allow admins to export
// 'none' to block exports completely
// allow_exports default value = all
$allow_exports = 'all';

// files with one of these extensions will have '.txt' appended to their filename on upload
// upload_badext default value = php, php3, php4, php5, pl, cgi, py, asp, cfm, js, vbs, html, htm
$upload_badext = ['php', 'php3', 'php4', 'php5', 'pl', 'cgi', 'py', 'asp', 'cfm', 'js', 'vbs', 'html', 'htm', 'exe', 'bin', 'bat', 'sh', 'dll', 'phps', 'phtml', 'xhtml', 'rb', 'msi', 'jsp', 'shtml', 'sth', 'shtm', 'htaccess'];

// list_max_entries_per_page default value = 20
$list_max_entries_per_page = '20';

// history_max_viewed default value = 5
$history_max_viewed = '5';

// default_action default value = index
$default_action = 'index';

// set default theme
// default_theme default value = blue
$default_theme = 'softed';

// default text that is placed initially in the login form for user name
// no default_user_name default value
$default_user_name = '';

// default text that is placed initially in the login form for password
// no default_password default value
$default_password = '';

// create user with default username and password
// create_default_user default value = false
$create_default_user = false;

// Master currency name
$currency_name = '_MASTER_CURRENCY_';

// default charset
// default charset default value = 'UTF-8' or 'ISO-8859-1'
$default_charset = '_VT_CHARSET_';

// default language
// default_language default value = en_us
$default_language = '_VT_DEFAULT_LANGUAGE_';

// Option to hide empty home blocks if no entries.
$display_empty_home_blocks = false;

// Disable Stat Tracking of vtiger CRM instance
$disable_stats_tracking = false;

// Generating Unique Application Key
$application_unique_key = '_VT_APP_UNIQKEY_';

// trim descriptions, titles in listviews to this value
$listview_max_textlength = 40;

// Maximum time limit for PHP script execution (in seconds)
$php_max_execution_time = 0;

// Set the default timezone as per your preference
$default_timezone = 'UTC';

/** If timezone is configured, try to set it */
if (isset($default_timezone) && function_exists('date_default_timezone_set')) {
    @date_default_timezone_set($default_timezone);
}

// Set the default layout
$default_layout = 'v7';

// Maximum Listview Fields Selection Size
$maxListFieldsSelectionSize = 15;

include_once 'config.security.php';
