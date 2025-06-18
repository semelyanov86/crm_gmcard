<?php

chdir('../../../');
require_once 'include/utils/utils.php';
require_once 'include/utils/CommonUtils.php';
require_once 'includes/Loader.php';
vimport('includes.runtime.EntryPoint');
vimport('includes.runtime.Globals');
require_once 'modules/VReports/models/ScheduleReports.php';
VReports_ScheduleReports_Model::runScheduledVReports();
