<?php

global $adb;
$sql = "ALTER TABLE `vte_buttons_settings` ADD `show_in_mobile`  int(1)";
$params = array();
$rs = $adb->pquery($sql, $params);

?>