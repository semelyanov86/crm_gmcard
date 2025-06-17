{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}
{strip}
<div class="listViewPageDiv">
	<div class="listViewTopMenuDiv">
		<div class="widget_header clearfix">
			<h3>{vtranslate($MODULE,$QUALIFIED_MODULE)}</h3>
		</div>
		<hr>
		<div class="clearfix"></div>
		<div class="row">
			<span class="col-lg-4">
				<button class="btn addButton" onclick='window.location.href="index.php?module=VTEConditionalAlerts&parent=Settings&view=Edit"'>
					<i class="fa fa-plus fa-lg"></i>&nbsp;
					<strong>{vtranslate('LBL_NEW', $QUALIFIED_MODULE)} {vtranslate('LBL_WORKFLOW',$QUALIFIED_MODULE)}</strong>
				</button>
			</span>
			<span class="col-lg-4">
				<select class="chzn-select select2" id="capModuleFilter" name="capModuleFilter" style="width: 200px;">
					<option value="">{vtranslate('LBL_ALL', $QUALIFIED_MODULE)}</option>
					{foreach item=MODULE_MODEL key=TAB_ID from=$ALL_MODULES}
						<option {if $SELECTED_MODULE_FILTER eq $MODULE_MODEL->getName()} selected="" {/if} value="{$MODULE_MODEL->getName()}">
							{if $MODULE_MODEL->getName() eq 'Calendar'}
								{vtranslate('LBL_TASK', $MODULE_MODEL->getName())}
							{else}
								{vtranslate($MODULE_MODEL->getName(),$MODULE_MODEL->getName())}
							{/if}
						</option>
					{/foreach}
				</select>
			</span>
			<span class="col-lg-4">
				{include file='ListViewActions.tpl'|vtemplate_path:'VTEConditionalAlerts'}
			</span>
		</div>
		<div class="clearfix"></div>
	</div>
	<div class="listViewContentDiv" id="listViewContents">
{/strip}
