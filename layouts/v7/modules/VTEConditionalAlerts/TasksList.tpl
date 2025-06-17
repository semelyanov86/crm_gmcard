{*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************}
{strip}
	<br>
	<div class="row-fluid">
		<table class="table table-bordered table-condensed listViewEntriesTable">
			<thead>
				<tr class="listViewHeaders">
					<th width="10%">{vtranslate('LBL_ACTIVE','VTEConditionalAlerts')}</th>
					<th>{vtranslate('LBL_ACTION_TITLE','VTEConditionalAlerts')}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$TASK_LIST item=TASK}
					<tr class="listViewEntries">
						<td><input type="checkbox" class="taskStatus" data-statusurl="{$TASK['active_url']}" {if $TASK['active']} checked="" {/if} /></td>
						<td><a data-url="?module=VTEConditionalAlerts&parent=Settings&view=EditTask&selected_module={$SELECTED_MODULE}&cat_id={$TASK['cat_id']}&task_id={$TASK['id']}">{$TASK['action_title']}</a>
							<div class="pull-right actions">
								<span class="actionImages">
									<a data-url="?module=VTEConditionalAlerts&parent=Settings&view=EditTask&selected_module={$SELECTED_MODULE}&cat_id={$TASK['cat_id']}&task_id={$TASK['id']}">
										<i class="glyphicon glyphicon-pencil alignMiddle" title="{vtranslate('LBL_EDIT','VTEConditionalAlerts')}"></i>
									</a>&nbsp;&nbsp;
									<a class="deleteTask" data-id="{$TASK['id']}" data-deleteurl="{$TASK['remove_link']}">
										<i class="glyphicon glyphicon-trash alignMiddle" title="{vtranslate('LBL_DELETE','VTEConditionalAlerts')}"></i>
									</a>
								</span>
							</div>
						</td>
					<tr>
				{/foreach}
			</tbody>
		</table>
		{if empty($TASK_LIST)}
			<table class="emptyRecordsDiv">
				<tbody>
					<tr>
						<td>
							{vtranslate('LBL_NO_TASKS_ADDED','VTEConditionalAlerts')}
						</td>
					</tr>
				</tbody>
			</table>
		{/if}
	</div>
{/strip}