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
{strip}
	{assign var=ALL_CONDITION_CRITERIA value=$ADVANCE_CRITERIA[1] }
	{assign var=ANY_CONDITION_CRITERIA value=$ADVANCE_CRITERIA[2] }
	{assign var=GROUP_PARENT_CONDITION value=$ADVANCE_CRITERIA['groupParentCondition'] }
	{if empty($ALL_CONDITION_CRITERIA) }
		{assign var=ALL_CONDITION_CRITERIA value=array()}
	{/if}
	{if empty($ANY_CONDITION_CRITERIA) }
		{assign var=ANY_CONDITION_CRITERIA value=array()}
	{/if}


<div class="filterContainer filterElements filterConditionContainer filterConditionsDiv">
	<input type="hidden" name="date_filters" data-value='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($DATE_FILTERS))}' />
	<input type=hidden name="advanceFilterOpsByFieldType" data-value='{ZEND_JSON::encode($ADVANCED_FILTER_OPTIONS_BY_TYPE)}' />
	{foreach key=ADVANCE_FILTER_OPTION_KEY item=ADVANCE_FILTER_OPTION from=$ADVANCED_FILTER_OPTIONS}
		{$ADVANCED_FILTER_OPTIONS[$ADVANCE_FILTER_OPTION_KEY] = vtranslate($ADVANCE_FILTER_OPTION, $MODULE)}
	{/foreach}
	<input type=hidden name="advanceFilterOptions" data-value='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($ADVANCED_FILTER_OPTIONS))}' />
    <div class="allConditionContainer conditionGroup contentsBackground" style="padding-bottom:15px;">
        <div class="header row-fluid" style="margin-bottom: 30px">
			<span class="col-lg-3 col-md-3">
				<strong>{vtranslate('LBL_ALL_CONDITIONS',$MODULE)}</strong>
				&nbsp;&nbsp;({vtranslate('LBL_ALL_CONDITIONS_DESC',$MODULE)})
			</span>

			<span class="col-lg-9 col-md-9 addCondition">
				<button type="button" class="button-header-vreport"><i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_CONDITION',$MODULE)}</button>
			</span>
		</div>
		<div class="contents">
			<div class="conditionList">
			 {foreach item=CONDITION_INFO from=$ALL_CONDITION_CRITERIA['columns']}
				{include file='AdvanceFilterCondition.tpl'|@vtemplate_path:$QUALIFIED_MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE CONDITION_INFO=$CONDITION_INFO MODULE=$MODULE}
			{/foreach}
				{if is_array($ALL_CONDITION_CRITERIA)}
					{assign var=COUNT_ALL_CONDITION_CRITERIA value=count($ALL_CONDITION_CRITERIA)}
				{else}
					{assign var=COUNT_ALL_CONDITION_CRITERIA value=0}
				{/if}
			{if $COUNT_ALL_CONDITION_CRITERIA eq 0}
				{include file='AdvanceFilterCondition.tpl'|@vtemplate_path:$QUALIFIED_MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE=$MODULE CONDITION_INFO=array()}
			{/if}
			</div>
			<div class="hide basic">
				{include file='AdvanceFilterCondition.tpl'|@vtemplate_path:$QUALIFIED_MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE CONDITION_INFO=array() MODULE=$MODULE NOCHOSEN=true}
			</div>
			<div class="groupCondition">
				{assign var=GROUP_CONDITION value=$ALL_CONDITION_CRITERIA['condition']}
				{if empty($GROUP_CONDITION)}
					{assign var=GROUP_CONDITION value="and"}
				{/if}
				<input type="hidden" name="condition" value="{$GROUP_CONDITION}" />
			</div>
		</div>
	</div>
	<div class="anyConditionContainer conditionGroup contentsBackground">
		<div class="header row-fluid" style="margin-bottom: 30px">
			<span class="col-lg-3 col-md-3">
				<strong>{vtranslate('LBL_ANY_CONDITIONS',$MODULE)}</strong>
				&nbsp;&nbsp;({vtranslate('LBL_ANY_CONDITIONS_DESC',$MODULE)})
			</span>
			<span class="col-lg-9 col-md-9 addCondition">
				<button type="button" class="button-header-vreport"><i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_CONDITION',$MODULE)}</button>
			</span>
		</div>
		<div class="contents">
			<div class="conditionList">
			{foreach item=CONDITION_INFO from=$ANY_CONDITION_CRITERIA['columns']}
				{include file='AdvanceFilterCondition.tpl'|@vtemplate_path:$QUALIFIED_MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE CONDITION_INFO=$CONDITION_INFO MODULE=$MODULE CONDITION="or"}
			{/foreach}
				{if is_array($ALL_CONDITION_CRITERIA)}
					{assign var=COUNT_ALL_CONDITION_CRITERIA value=count($ALL_CONDITION_CRITERIA)}
				{else}
					{assign var=COUNT_ALL_CONDITION_CRITERIA value=0}
				{/if}
			{if $COUNT_ALL_CONDITION_CRITERIA eq 0}
				{include file='AdvanceFilterCondition.tpl'|@vtemplate_path:$QUALIFIED_MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE=$MODULE CONDITION_INFO=array() CONDITION="or"}
			{/if}
			</div>
			<div class="hide basic">
				{include file='AdvanceFilterCondition.tpl'|@vtemplate_path:$QUALIFIED_MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE MODULE=$MODULE CONDITION_INFO=array() CONDITION="or" NOCHOSEN=true}
			</div>
		</div>
	</div>
</div>
{if $GROUP_PARENT_CONDITION neq ''}
	<div class="button-action" style="margin: 20px auto; width: 200px; display: inherit">
		<select class="group-condition select2" style="height: 30px;width: 70px">
			<option value="or" {if $GROUP_PARENT_CONDITION eq 'or'}selected="selected"{/if}>{vtranslate('LBL_OR',$MODULE)}</option>
			<option value="and" {if $GROUP_PARENT_CONDITION eq 'and'}selected="selected"{/if}>{vtranslate('LBL_AND',$MODULE)}</option>
		</select>
		<button class="btn btn-default deleteGroup" style="margin-left: 15px">{vtranslate('LBL_DELETE_GROUP',$MODULE)}</button>
	</div>
{/if}
{/strip}