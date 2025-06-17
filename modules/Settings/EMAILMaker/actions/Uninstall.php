<?php
/* * *******************************************************************************
* The content of this file is subject to the EMAILMaker license.
* ("License"); You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
* Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
* All Rights Reserved.
* ****************************************************************************** */

class Settings_EMAILMaker_Uninstall_Action extends Settings_Vtiger_Basic_Action {

	function process(Vtiger_Request $request){
		$Vtiger_Utils_Log = true;
		include_once('vtlib/Vtiger/Module.php');
		$adb = PearDatabase::getInstance();
		$module = Vtiger_Module::getInstance('EMAILMaker');
		if ($module) {

			$module->delete();
			@shell_exec('rm -r modules/EMAILMaker');
			@shell_exec('rm -r layouts/vlayout/modules/EMAILMaker');
			@shell_exec('rm -r layouts/v7/modules/EMAILMaker');

			$Languages = array('ar_ae','cz_cz','de_de','en_gb','en_us','es_es','es_mx','fr_fr','hi_hi','hu_hu','it_it','nl_nl','pl_pl','pt_br','ro_ro','ru_ru','sk_sk','sv_se','tr_tr');

			foreach ($Languages AS $lang) {
				@shell_exec('rm -f languages/'.$lang.'/EMAILMaker.php');
			}

			$adb->pquery("DROP TABLE IF EXISTS vtiger_emakertemplates",array());

			$Tables = array('seq','attch','emails','sent','settings','relblocks','relblocks_seq','relblockcol','relblockcriteria',
				'relblockcriteria_g','relblockdatefilter','productbloc_tpl','ignorepicklistvalues','license','version',
				'profilespermissions','picklists','sharing','default_from','drips','drips_seq','drip_groups','drip_groups_seq',
				'delay','drip_tpls','drip_tpls_seq','sharing_drip','documents','userstatus','label_keys','label_vals',
				'images','relblocksortcol','me','contents');

			foreach ($Tables AS $table) {
				$adb->pquery("DROP TABLE IF EXISTS vtiger_emakertemplates_".$table,array());
			}

			$result = array('success' => true);
		} else {
			$result = array('success' => false);
		}
		ob_clean();
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
