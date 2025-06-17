{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <div class="">
        <div class="reportsDetailHeader">
            <input type="hidden" name="date_filters" data-value='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($DATE_FILTERS))}' />
            {include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
            <div class="filterElements filterConditionsDiv{if !$REPORT_MODEL->isEditableBySharing() && !$IS_ADMIN} hide{/if}">
                <form name='chartDetailForm' id='chartDetailForm' method="POST">
                    <input type="hidden" name="module" value="{$MODULE}" />
                    <input type="hidden" name="action" value="ChartSave" />
                    <input type="hidden" name="recordId" id="recordId" value="{$RECORD}" />
                    <input type="hidden" name="reportname" value="{$REPORT_MODEL->get('reportname')}" />
                    <input type="hidden" name="folderid" value="{$REPORT_MODEL->get('folderid')}" />
                    <input type="hidden" name="reports_description" value="{$REPORT_MODEL->get('reports_description')}" />
                    <input type="hidden" name="primary_module" value="{$PRIMARY_MODULE}" />
                    <input type="hidden" name="secondary_modules" value={ZEND_JSON::encode($SECONDARY_MODULES)} />
                    <input type="hidden" name="advanced_filter" id="advanced_filter" value={ZEND_JSON::encode($ADVANCED_FILTERS)} />
                    <input type="hidden" name='groupbyfield' value={$CHART_MODEL->getGroupByField()} />
                    <input type="hidden" name='sort_by' {if $SORT_BY} value='{Zend_JSON::encode($SORT_BY)}' {else} value='[]' {/if}/>
                    <input type="hidden" name='limit' value='{$LIMIT}'/>
                    <input type="hidden" name='order_by' value='{$ORDER_BY}'/>
                    <input type="hidden" name='datafields' value={Zend_JSON::encode($CHART_MODEL->getDataFields())} />
                    <input type="hidden" name='charttype' value="{$CHART_MODEL->getChartType()}" />
                    <input type="hidden" name='formatlargenumber' value="{$CHART_MODEL->get('formatlargenumber')}" />
                    <input type="hidden" name='legendposition' id="legendposition" value="{$CHART_MODEL->getLegendPosition()}" />
                    <input type="hidden" name='displaygrid' id="displaygrid" value="{$CHART_MODEL->get('displaygrid')}" />
                    <input type="hidden" name='displaylabel' id="displaylabel" value="{$CHART_MODEL->get('displaylabel')}" />
                    <input type="hidden" name='formatlargenumber' id="formatlargenumber" value="{$CHART_MODEL->get('formatlargenumber')}" />
                    <input type="hidden" name='legendvalue' id="legendvalue" value="{$CHART_MODEL->get('legendvalue')}" />
                    <input type="hidden" name='drawline' id="drawline" value="{$CHART_MODEL->get('drawline')}" />
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>

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
                    <br/>
                    <div class="well filterConditionContainer bg-white">
                        <div>
                            <span class="cursorPointer header-text" name="modify_charts" data-val="{$filterConditionNotExists}">
                                <span>
                                    <i class="fa m-r-8 fa-chevron-right"></i>
                                    &nbsp;&nbsp;{vtranslate('LBL_MODIFY_CHART', $MODULE)}
                                </span>
                            </span>
                            <div id="chart-content-conditions" class='row hide' style="margin-top: 12px">
                                <span class="col-lg-3">
                                    <div><span>{vtranslate('LBL_SELECT_GROUP_BY_FIELD', $MODULE)}</span><span class="redColor">*</span></div><br>
                                    <div>
                                        <select id='groupbyfield' name='groupbyfield' class="col-lg-10" data-validation-engine="validate[required]" style='min-width:300px;'></select>
                                    </div>
                                </span>
                                <span class="col-lg-2">&nbsp;</span>
                                <span class="col-lg-3" style="padding: 0px!important;">
                                    <div><span>{vtranslate('LBL_SELECT_DATA_FIELD', $MODULE)}</span><span class="redColor">*</span></div><br>
                                    <div>
                                        <select id='datafields' name='datafields[]' class="col-lg-10" data-validation-engine="validate[required]" style='min-width:300px;'>
                                        </select></div>
                                </span>
                                <span class="col-lg-2">&nbsp;</span>
                                <span class="col-lg-2">
                                    <div><span>{vtranslate('LBL_LEGEND_POSITION', $MODULE)}</span></div><br>
                                    <div>
                                            <select id='legend_position' name='legendposition' style='min-width:100px;' class="select2">
                                                <option value="none" {if $CHART_MODEL->getLegendPosition() == 'top'}selected="selected"{/if}>{vtranslate('None', $MODULE)}</option>
                                                <option value="top" {if $CHART_MODEL->getLegendPosition() == 'top'}selected="selected"{/if}>{vtranslate('Top', $MODULE)}</option>
                                                <option value="left" {if $CHART_MODEL->getLegendPosition() == 'left'}selected="selected"{/if}>{vtranslate('Left', $MODULE)}</option>
                                                <option value="right" {if $CHART_MODEL->getLegendPosition() == 'right'}selected="selected"{/if}>{vtranslate('Right', $MODULE)}</option>
                                                <option value="bottom" {if $CHART_MODEL->getLegendPosition() == 'bottom'}selected="selected"{/if}>{vtranslate('Bottom', $MODULE)}</option>
                                            </select>
                                    </div>
                                    <br><br>
                                    <span id="advancedOptions" class="cursorPointer advanced-option" style="color: blue">{vtranslate('LBL_ADVANCED_OPTIONS', $MODULE)}</span>
                                </span>
                                {*//border:1px solid #ccc;*}
                                <span class="col-lg-2">&nbsp;</span><br><br><br><br><br><br><br><br>
                                <div class="col-lg-12">
                                    {include file="ChartReportHeaderAdvancedOptions.tpl"|vtemplate_path:$MODULE}
                                </div>
                            </div>
                            <br>

                            <div class='hide'>
                                {include file="chartReportHiddenContents.tpl"|vtemplate_path:$MODULE}
                            </div>
                        </div>
                        <br/>
                        {if is_array($SELECTED_ADVANCED_FILTER_FIELDS[1]['columns'])}
                            {assign var=SELECTED_ADVANCED_FILTER_FIELDS_1_COLUMNS value=count($SELECTED_ADVANCED_FILTER_FIELDS[1]['columns'])}
                        {else}
                            {assign var=SELECTED_ADVANCED_FILTER_FIELDS_1_COLUMNS value=0}
                        {/if}
                        {if is_array($SELECTED_ADVANCED_FILTER_FIELDS[2]['columns'])}
                            {assign var=SELECTED_ADVANCED_FILTER_FIELDS_2_COLUMNS value=count($SELECTED_ADVANCED_FILTER_FIELDS[2]['columns'])}
                        {else}
                            {assign var=SELECTED_ADVANCED_FILTER_FIELDS_2_COLUMNS value=0}
                        {/if}
                        {assign var=filterConditionNotExists value=($SELECTED_ADVANCED_FILTER_FIELDS_1_COLUMNS eq 0 and $SELECTED_ADVANCED_FILTER_FIELDS_2_COLUMNS eq 0)}
                        <span class="cursorPointer header-text" name="modify_condition" data-val="{$filterConditionNotExists}">
                            <span>
                                <i class="fa m-r-8 {if $filterConditionNotExists eq true} fa-chevron-right {else} fa-chevron-down {/if}"></i>
                                &nbsp;&nbsp;{vtranslate('LBL_MODIFY_CONDITION', $MODULE)}
                            </span>
                        </span>
                        <button type="button" class="button-header-vreport btn-add-group hide" name="addgroup" >
                            <i class="fa fa-plus"></i>&nbsp;&nbsp;{vtranslate('ADD_GROUP', $MODULE)}
                        </button>
                    <div id='filterContainer' class='{if $filterConditionNotExists eq true} hide {/if}'>
                        <br/>
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
                            {assign var=COUNT_SELECTED_ADVANCED_FILTER_FIELDS value=count($SELECTED_ADVANCED_FILTER_FIELDS)}
                        {else}
                            {assign var=COUNT_SELECTED_ADVANCED_FILTER_FIELDS value=0}
                        {/if}
                        {if $COUNT_SELECTED_ADVANCED_FILTER_FIELDS > 0}
                            {foreach item=SELECTED_ADVANCED_FILTER_FIELD key=GROUP_PARENT from=$SELECTED_ADVANCED_FILTER_FIELDS}
                                {include file='AdvanceFilter.tpl'|@vtemplate_path:$MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE ADVANCE_CRITERIA=$SELECTED_ADVANCED_FILTER_FIELD COLUMNNAME_API=getReportFilterColumnName}
                            {/foreach}
                        {else}
                            {include file='AdvanceFilter.tpl'|@vtemplate_path:$MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE COLUMNNAME_API=getReportFilterColumnName}
                        {/if}
                    </div>
                    </div>
                    {if $REPORT_MODEL->isEditableBySharing() || $IS_ADMIN}
                        <div class="row textAlignCenter reportActionButtons hide" style="height: 40px">
                            <button class="btn btn-primary generateReportChart" data-mode="save" value="{vtranslate('LBL_SAVE',$MODULE)}">
                                <strong>{vtranslate('LBL_SAVE',$MODULE)}</strong>
                            </button>
                        </div>
                    {/if}
            </div>
            </form>
        </div>
    </div>
    <div id="reportContentsDiv">
{/strip}