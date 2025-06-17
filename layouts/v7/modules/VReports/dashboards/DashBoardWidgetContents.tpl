{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is: vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{if !empty($DATA)}
	{if $REPORT_TYPE eq 'detail'}
		{include file="ReportContents.tpl"|@vtemplate_path:$MODULE}
	{else}
		{if $REPORT_TYPE eq 'pivot'}
			<input type="hidden" class="decimal_separator" value="{$CURRENT_USER->currency_decimal_separator}">
			<input type="hidden" class="grouping_separator" value="{$CURRENT_USER->currency_grouping_separator}">
			<input type="hidden" class="no_of_currency_decimals" value="{$CURRENT_USER->no_of_currency_decimals}">
			<input type="hidden" class="truncate_trailing_zeros" value="{$CURRENT_USER->truncate_trailing_zeros}">
			<input type="hidden" class="currency_symbol_placement" value="{$CURRENT_USER->currency_symbol_placement}">
			<input type="hidden" class="currency_grouping_pattern" value="{$CURRENT_USER->currency_grouping_pattern}">
			<input type="hidden" class="currency_symbol" value="{$CURRENT_USER->currency_symbol}">
			<div id="reportDetails" class="pivot-widget-{$WIDGET->get('id')}" style="overflow-x: auto; padding:20px">
			</div>
		{else}
			<canvas id="chart-area-{$WIDGET->get('id')}">
			</canvas>
		{/if}
		{$DATA}
		{if $DATA_CHART}
			<canvas id="chart-area-{$WIDGET->get('id')}">
			</canvas>
			{$DATA_CHART}
		{/if}
	{/if}
{else}
	<span class="noDataMsg" style="position: relative; top: 115.5px; left: 119px;">
		{vtranslate('LBL_NO')} {vtranslate($PRIMARY_MODULE, $MODULE)} {vtranslate('LBL_MATCHED_THIS_CRITERIA')}
	</span>
{/if}