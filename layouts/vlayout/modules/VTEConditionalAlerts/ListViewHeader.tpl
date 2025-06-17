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
        <div class="row-fluid">
            <div class="span6">
                <h3>{vtranslate($MODULE,$QUALIFIED_MODULE)}</h3>
            </div>
            <div class="span6">

            </div>
        </div>
        <hr>
		<div class="row-fluid">
			<span class="span4 btn-toolbar">
				<button class="btn addButton" onclick='window.location.href="index.php?module=VTEConditionalAlerts&parent=Settings&view=Edit"'>
					<i class="icon-plus"></i>&nbsp;
					<strong>{vtranslate('LBL_NEW', $QUALIFIED_MODULE)} {vtranslate('LBL_WORKFLOW',$QUALIFIED_MODULE)}</strong>
				</button>
			</span>
			<span class="span4 btn-toolbar">
				<select class="chzn-select" id="capModuleFilter" name="capModuleFilter" >
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
			<span class="span4 btn-toolbar">
				{include file='ListViewActions.tpl'|vtemplate_path:'VTEConditionalAlerts'}
			</span>
		</div>
		<div class="clearfix"></div>
	</div>
	<div class="listViewContentDiv" id="listViewContents">
{/strip}
