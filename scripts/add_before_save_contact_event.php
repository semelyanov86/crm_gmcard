<?php


$Vtiger_Utils_Log = true;

chdir(__DIR__ . '/../');
require_once 'vendor/autoload.php';
require_once 'includes/Loader.php';
require_once 'config.php';
include_once 'vtlib/Vtiger/Module.php';
require_once 'libraries/adodb_vtigerfix/adodb.inc.php';
require_once 'modules/com_vtiger_workflow/VTEntityMethodManager.inc';


global $adb;

$emm = new VTEventsManager($adb);
$emm->registerHandler(
    'vtiger.entity.aftersave',
    'modules/Contacts/handlers/SendUpdates.php',
    "SendUpdates"
);
echo 'We are done';