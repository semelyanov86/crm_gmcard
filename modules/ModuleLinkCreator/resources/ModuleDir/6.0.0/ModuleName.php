<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'modules/Vtiger/CRMEntity.php';

class __ModuleName__ extends Vtiger_CRMEntity {
    var $db, $log;
	var $table_name = 'vtiger_<modulename>';
	var $table_index= '<modulename>id';
	var $related_tables = Array ('vtiger_<modulename>cf' => Array ( '<modulename>id', 'vtiger_<modulename>', '<modulename>id' ),);


	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_<modulename>cf', '<modulename>id');

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_<modulename>', 'vtiger_<modulename>cf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_<modulename>' => '<modulename>id',
		'vtiger_<modulename>cf'=>'<modulename>id');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'<entityfieldlabel>' => Array('<modulename>', '<entitycolumn>'),
		'Assigned To' => Array('crmentity','smownerid')
	);
	var $list_fields_name = Array (
		/* Format: Field Label => fieldname */
		'<entityfieldlabel>' => '<entityfieldname>',
		'Assigned To' => 'assigned_user_id',
	);

	// Make the field link to detail view
	var $list_link_field = '<entityfieldname>';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename, columnname) */
		// tablename should not have prefix 'vtiger_'
		'<entityfieldlabel>' => Array('<modulename>', '<entitycolumn>'),
		'Assigned To' => Array('vtiger_crmentity','assigned_user_id'),
	);
	var $search_fields_name = Array (
		/* Format: Field Label => fieldname */
		'<entityfieldlabel>' => '<entityfieldname>',
		'Assigned To' => 'assigned_user_id',
	);

	// For Popup window record selection
	var $popup_fields = Array ('<entityfieldname>');

	// For Alphabetical search
	var $def_basicsearch_col = '<entityfieldname>';

	// Column value to use on detail view record text display
	var $def_detailview_recname = '<entityfieldname>';

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('<entityfieldname>','assigned_user_id');

	var $default_order_by = '<entityfieldname>';
	var $default_sort_order='ASC';

    function __ModuleName__() {
        global $log;
        $this->column_fields = getColumnFields(get_class($this));
        $this->db = PearDatabase::getInstance();
        $this->log = $log;
    }

	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {
		global $adb;
 		if($eventType == 'module.postinstall') {
			// TODO Handle actions after this module is installed.
			$this->init($moduleName);
		} else if($eventType == 'module.disabled') {
			// TODO Handle actions before this module is being uninstalled.
		} else if($eventType == 'module.enabled') {
			// TODO Handle actions before this module is being uninstalled.
		} else if($eventType == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
 	}

    function get_emails($id, $cur_tab_id, $rel_tab_id, $actions=false) {
        global $log, $singlepane_view,$currentModule,$current_user;
        $log->debug("Entering get_emails(".$id.") method ...");
        $this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
        require_once("modules/$related_module/$related_module.php");
        $other = new $related_module();
        vtlib_setup_modulevars($related_module, $other);
        $singular_modname = vtlib_toSingular($related_module);

        $parenttab = getParentTab();

        if($singlepane_view == 'true')
            $returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
        else
            $returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

        $button = '';

        $button .= '<input type="hidden" name="email_directing_module"><input type="hidden" name="record">';

        if($actions) {
            if(is_string($actions)) $actions = explode(',', strtoupper($actions));
            if(in_array('SELECT', $actions) && isPermitted($related_module,4, '') == 'yes') {
                $button .= "<input title='".getTranslatedString('LBL_SELECT')." ". getTranslatedString($related_module). "' class='crmbutton small edit' type='button' onclick=\"return window.open('index.php?module=$related_module&return_module=$currentModule&action=Popup&popuptype=detailview&select=enable&form=EditView&form_submit=false&recordid=$id&parenttab=$parenttab','test','width=640,height=602,resizable=0,scrollbars=0');\" value='". getTranslatedString('LBL_SELECT'). " " . getTranslatedString($related_module) ."'>&nbsp;";
            }
            if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
                $button .= "<input title='". getTranslatedString('LBL_ADD_NEW')." ". getTranslatedString($singular_modname)."' accessyKey='F' class='crmbutton small create' onclick='fnvshobj(this,\"sendmail_cont\");sendmail(\"$this_module\",$id);' type='button' name='button' value='". getTranslatedString('LBL_ADD_NEW')." ". getTranslatedString($singular_modname)."'></td>";
            }
        }

        $userNameSql = getSqlForNameInDisplayFormat(array('first_name'=>
            'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'), 'Users');
        $query = "select case when (vtiger_users.user_name not like '') then $userNameSql else vtiger_groups.groupname end as user_name," .
            " vtiger_activity.activityid, vtiger_activity.subject, vtiger_activity.activitytype, vtiger_crmentity.modifiedtime," .
            " vtiger_crmentity.crmid, vtiger_crmentity.smownerid, vtiger_activity.date_start, vtiger_activity.time_start, vtiger_seactivityrel.crmid as parent_id " .
            " from vtiger_activity, vtiger_seactivityrel, vtiger_<modulename>, vtiger_users, vtiger_crmentity" .
            " left join vtiger_groups on vtiger_groups.groupid=vtiger_crmentity.smownerid" .
            " where vtiger_seactivityrel.activityid = vtiger_activity.activityid" .
            " and vtiger_<modulename>.<modulename>id = vtiger_seactivityrel.crmid and vtiger_users.id=vtiger_crmentity.smownerid" .
            " and vtiger_crmentity.crmid = vtiger_activity.activityid  and vtiger_<modulename>.<modulename>id = ".$id." and" .
            " vtiger_activity.activitytype='Emails' and vtiger_crmentity.deleted = 0";

        $return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

        if($return_value == null) $return_value = Array();
        $return_value['CUSTOM_BUTTON'] = $button;

        $log->debug("Exiting get_emails method ...");
        return $return_value;
    }
	/**
	 * When install module
	 * @param $moduleName
	 */
	public function init($moduleName) {
		$module = Vtiger_Module::getInstance($moduleName);

		// Enable Activities
		$activityFieldTypeId = 34;
		$this->addModuleRelatedToForEvents($module->name, $activityFieldTypeId);

		// Enable ModTracker
		require_once 'modules/ModTracker/ModTracker.php';
		ModTracker::enableTrackingForModule($module->id);

		// Enable Comments
		$commentInstance = Vtiger_Module::getInstance('ModComments');
		$commentRelatedToFieldInstance = Vtiger_Field::getInstance('related_to', $commentInstance);
		$commentRelatedToFieldInstance->setRelatedModules(array($module->name));

		// Customize Record Numbering
		$prefix = 'NO';
		if (strlen($module->name) >= 2) {
			$prefix = substr($module->name, 0, 2);
			$prefix = strtoupper($prefix);
		}
		$this->customizeRecordNumbering($module->name, $prefix, 1);

	}

	/**
	 * @param string $moduleName
	 * @param int $fieldTypeId
	 */
	public function addModuleRelatedToForEvents($moduleName, $fieldTypeId)
	{
		global $adb;

		$sqlCheckProject = "SELECT * FROM `vtiger_ws_referencetype` WHERE fieldtypeid = ? AND type = ?";
		$rsCheckProject = $adb->pquery($sqlCheckProject, array($fieldTypeId, $moduleName));
		if ($adb->num_rows($rsCheckProject) < 1) {
			$adb->pquery("INSERT INTO `vtiger_ws_referencetype` (`fieldtypeid`, `type`) VALUES (?, ?)",
				array($fieldTypeId, $moduleName));
		}
	}

	/**
	 * @param string $sourceModule
	 * @param string $prefix
	 * @param int $sequenceNumber
	 * @return array
	 */
	public function customizeRecordNumbering($sourceModule, $prefix = 'NO', $sequenceNumber = 1)
	{
		$moduleModel = Settings_Vtiger_CustomRecordNumberingModule_Model::getInstance($sourceModule);
		$moduleModel->set('prefix', $prefix);
		$moduleModel->set('sequenceNumber', $sequenceNumber);

		$result = $moduleModel->setModuleSequence();

		return $result;
	}

}