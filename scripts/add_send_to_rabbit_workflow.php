<?php

$Vtiger_Utils_Log = true;

chdir(__DIR__ . '/../');
require_once 'vendor/autoload.php';
require_once 'includes/Loader.php';
require_once 'config.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'libraries/adodb_vtigerfix/adodb.inc.php';
require_once 'modules/com_vtiger_workflow/VTEntityMethodManager.inc';

global $adb;
$emm = new VTEntityMethodManager($adb);
$emm->addEntityMethod("Contacts", "Send to Rabbit", "modules/Contacts/workflow/SendToRabbit.php", "SendToRabbit");
echo 'we are done';