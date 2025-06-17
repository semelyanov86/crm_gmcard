{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Reports/views/Detail.php *}

{* START YOUR IMPLEMENTATION FROM BELOW. Use {debug} for information *}
{strip}
    <div class="reportsDetailHeader">
        <input type="hidden" name="date_filters" data-value='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($DATE_FILTERS))}' />
        <input type="hidden" id="reportLimit" value="{$REPORT_LIMIT}" />
        <form id="detailView" onSubmit="return false;">
            <input type="hidden" name="date_filters" data-value='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($DATE_FILTERS))}' />
            {include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
            <br>
            <div class="well filterConditionContainer bg-white {if $REPORT_TYPE eq 'sql'} hide{/if}">
                <div>
                    {assign var=COUNT_SELECTED_ADVANCED_FILTER_FIELDS_1_COLUMNS value=0}
                    {assign var=COUNT_SELECTED_ADVANCED_FILTER_FIELDS_2_COLUMNS value=0}
                    {if is_array($SELECTED_ADVANCED_FILTER_FIELDS[1]['columns'])}
                        {assign var=COUNT_SELECTED_ADVANCED_FILTER_FIELDS_1_COLUMNS value=count($SELECTED_ADVANCED_FILTER_FIELDS[1]['columns'])}
                    {/if}
                    {if is_array($SELECTED_ADVANCED_FILTER_FIELDS[2]['columns'])}
                        {assign var=COUNT_SELECTED_ADVANCED_FILTER_FIELDS_2_COLUMNS value=count($SELECTED_ADVANCED_FILTER_FIELDS[2]['columns'])}
                    {/if}
                    {assign var=filterConditionNotExists value=($COUNT_SELECTED_ADVANCED_FILTER_FIELDS_1_COLUMNS eq 0 and $COUNT_SELECTED_ADVANCED_FILTER_FIELDS_2_COLUMNS eq 0)}
                    <span class="cursorPointer header-text" name="modify_condition" data-val="{$filterConditionNotExists}">
                        <span>
                            <i class="fa m-r-8 {if $filterConditionNotExists eq true} fa-chevron-right {else} fa-chevron-down {/if}"></i>
                            &nbsp;&nbsp;{vtranslate('LBL_MODIFY_CONDITION', $MODULE)}
                        </span>
                    </span>
                    <button type="button" class="button-header-vreport btn-add-group hide" name="addgroup" >
                        <i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('ADD_GROUP', $MODULE)}
                    </button>
                </div>

                <div id="filterContainer" class="filterElements filterConditionsDiv filterConditionContainer hide">
                    <br>
                    <input type="hidden" id="recordId" value="{$RECORD_ID}" />
                    {assign var=RECORD_STRUCTURE value=array()}
                    {assign var=PRIMARY_MODULE_LABEL value=vtranslate($PRIMARY_MODULE, $PRIMARY_MODULE)}
                    {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$PRIMARY_MODULE_RECORD_STRUCTURE}
                        {assign var=PRIMARY_MODULE_BLOCK_LABEL value=vtranslate($BLOCK_LABEL, $PRIMARY_MODULE)}
                        {assign var=key value="$PRIMARY_MODULE_LABEL $PRIMARY_MODULE_BLOCK_LABEL"}
                        {if $LINEITEM_FIELD_IN_CALCULATION eq false && $BLOCK_LABEL eq 'LBL_ITEM_DETAILS'}
                            {* dont show the line item fields block when Inventory fields are selected for calculations *}
                        {else}
                            {$RECORD_STRUCTURE[$key] = $BLOCK_FIELDS}
                        {/if}
                    {/foreach}
                    {foreach key=MODULE_LABEL item=SECONDARY_MODULE_RECORD_STRUCTURE from=$SECONDARY_MODULE_RECORD_STRUCTURES}
                        {assign var=SECONDARY_MODULE_LABEL value=vtranslate($MODULE_LABEL, $MODULE_LABEL)}
                        {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$SECONDARY_MODULE_RECORD_STRUCTURE}
                            {assign var=SECONDARY_MODULE_BLOCK_LABEL value=vtranslate($BLOCK_LABEL, $MODULE_LABEL)}
                            {assign var=key value="$SECONDARY_MODULE_LABEL $SECONDARY_MODULE_BLOCK_LABEL"}
                            {$RECORD_STRUCTURE[$key] = $BLOCK_FIELDS}
                        {/foreach}
                    {/foreach}
                    <div>
                        <div id="conditionClone" style="display: none">
                            <div class="button-action" style="margin: 20px auto; width: 200px; display: inherit">
                                <select class="group-condition" style="height: 30px;width: 70px">
                                    <option value="or">{vtranslate('LBL_OR',$MODULE)}</option>
                                    <option value="and">{vtranslate('LBL_AND',$MODULE)}</option>
                                </select>
                                <button type="button" class="btn btn-default deleteGroup" style="margin-left: 15px">{vtranslate('LBL_DELETE_GROUP',$MODULE)}</button>
                            </div>
                            {include file='AdvanceFilter.tpl'|@vtemplate_path:$MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE COLUMNNAME_API=getReportFilterColumnName}
                        </div>
                        {if is_array($SELECTED_ADVANCED_FILTER_FIELDS)}
                            {if count($SELECTED_ADVANCED_FILTER_FIELDS) > 0}
                                {foreach item=SELECTED_ADVANCED_FILTER_FIELD key=GROUP_PARENT from=$SELECTED_ADVANCED_FILTER_FIELDS}
                                    {include file='AdvanceFilter.tpl'|@vtemplate_path:$MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE ADVANCE_CRITERIA=$SELECTED_ADVANCED_FILTER_FIELD COLUMNNAME_API=getReportFilterColumnName}
                                {/foreach}
                            {else}
                                {include file='AdvanceFilter.tpl'|@vtemplate_path:$MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE COLUMNNAME_API=getReportFilterColumnName}
                            {/if}
                        {else}
                            {include file='AdvanceFilter.tpl'|@vtemplate_path:$MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE COLUMNNAME_API=getReportFilterColumnName}
                        {/if}
                    </div>
                    <br>
                </div>
            </div>
            <div class="row DivGenerateReports" >
                <div class="textAlignCenter reportActionButtons hide" style="margin-bottom: 20px">
                    <button class="btn btn-default generateReport" data-mode="generate" value="{vtranslate('LBL_GENERATE_NOW',$MODULE)}"/>
                    <strong>{vtranslate('LBL_GENERATE_NOW',$MODULE)}</strong>
                    </button>&nbsp;
                    {if $REPORT_MODEL->isEditableBySharing() || $IS_ADMIN}
                        <button class="btn btn-primary generateReport" data-mode="save" value="{vtranslate('LBL_SAVE',$MODULE)}"/>
                        <strong>{vtranslate('LBL_SAVE',$MODULE)}</strong>
                        </button>
                    {/if}
                </div>
            </div>
        </form>
    </div>
    <div id="reportContentsDiv">
{/strip}