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
/*
 * $Header: /advent/projects/wesat/vtiger_crm/sugarcrm/include/logging.php,v 1.1 2004/08/17 13:23:37 gjayakrishnan Exp $
 * Description:  Kicks off log4php.
 */

require_once 'config.php';

// Performance Optimization: Configure the log folder
@include_once 'config.performance.php';
require_once 'modules/Vtiger/helpers/Logger.php';
