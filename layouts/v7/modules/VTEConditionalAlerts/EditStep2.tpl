{*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************}
{strip}
	<form name="EditVTEConditionalAlerts" action="index.php" method="post" id="VTEConditionalAlerts_step2" class="form-horizontal" >
		<input type="hidden" name="module" value="VTEConditionalAlerts" />
		<input type="hidden" name="action" value="Save" />
		<input type="hidden" name="parent" value="Settings" />
		<input type="hidden" class="step" value="2" />
        <input type="hidden" name="record" value='{$RECORD}' />
        <input type="hidden" name="conditions" id="advanced_filter" value='' />
        <input type="hidden" id="olderConditions" value='' />
        <input type="hidden" id="savedConditions" value='{$OLD_FILTER}' />
        <input type="hidden" name="descriptions" value='{$DESCRIPTIONS}' />
        <input type="hidden" name="selected_module" value='{$SELECTED_MODULE_NAME}' />
        {*<input type="hidden" id="olderConditions" value='{ZEND_JSON::encode($WORKFLOW_MODEL->get('conditions'))}' />*}
		<div class="row-fluid">
				{if $IS_FILTER_SAVED_NEW == false}
					<div class="alert alert-info">
						{vtranslate('LBL_CREATED_IN_OLD_LOOK_CANNOT_BE_EDITED',$QUALIFIED_MODULE)}
					</div>
					<div class="row-fluid">
						<span class="col-lg-6"><input type="radio" name="conditionstype" class="alignMiddle" checked=""/>&nbsp;&nbsp;<span class="alignMiddle">{vtranslate('LBL_USE_EXISTING_CONDITIONS',$QUALIFIED_MODULE)}</span></span>
						<span class="col-lg-6"><input type="radio" id="enableAdvanceFilters" name="conditionstype" class="alignMiddle recreate"/>&nbsp;&nbsp;<span class="alignMiddle">{vtranslate('LBL_RECREATE_CONDITIONS',$QUALIFIED_MODULE)}</span></span>
					</div><br>
				{/if}
				<div id="advanceFilterContainer" {if $IS_FILTER_SAVED_NEW == false} class="zeroOpacity conditionsContainer padding1per" {else} class="conditionsContainer padding1per" {/if}>
					<h5 class="padding-bottom1per"><strong>{vtranslate('LBL_CHOOSE_FILTER_CONDITIONS',$MODULE)}</strong></h5>
					{include file='AdvanceFilter.tpl'|@vtemplate_path:$QUALIFIED_MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE}
                    {include file="FieldExpressions.tpl"|@vtemplate_path:$QUALIFIED_MODULE}
				</div>
			</div><br>
			<div class="pull-right">
				<button class="btn btn-danger backStep" type="button"><strong>{vtranslate('LBL_BACK', $QUALIFIED_MODULE)}</strong></button>&nbsp;&nbsp;
				<button class="btn btn-success" type="submit"><strong>{vtranslate('LBL_NEXT', $QUALIFIED_MODULE)}</strong></button>
				<a class="cancelLink" type="reset" onclick="javascript:window.history.back();">{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}</a>
			</div>
			<br><br>

	</form>
{/strip}