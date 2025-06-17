{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <style>
        ul.select2-choices{
            border: 0 !important;
            border-bottom: 1px solid #aaa !important;
            background: #f9f9f9 !important;
            box-shadow : none !important;
            -webkit-box-shadow : none !important;
        }
        .select2-drop-active{
            border: 1px solid #aaa !important;
            /* border-top: none; */
        }
    </style>
    <div class="reportsDetailHeader">
        <input type="hidden" name="date_filters" data-value='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($DATE_FILTERS))}' />
        {include file="DetailViewActions.tpl"|vtemplate_path:$MODULE}
        <div class="filterElements filterConditionsDiv{if !$REPORT_MODEL->isEditableBySharing() && !$IS_ADMIN} hide{/if}">
            <form name='pivotDetailForm' id='pivotDetailForm' method="POST">
                <input type="hidden" name="module" value="{$MODULE}" />
                <input type="hidden" name="action" value="PivotSave" />
                <input type="hidden" name="recordId" id="recordId" value="{$RECORD}" />
                <input type="hidden" name="reportname" value="{$REPORT_MODEL->get('reportname')}" />
                <input type="hidden" name="folderid" value="{$REPORT_MODEL->get('folderid')}" />
                <input type="hidden" name="reports_description" value="{$REPORT_MODEL->get('reports_description')}" />
                <input type="hidden" name="primary_module" value="{$PRIMARY_MODULE}" />
                <input type="hidden" name="secondary_modules" value={ZEND_JSON::encode($SECONDARY_MODULES)} />
                <input type="hidden" name="advanced_filter" id="advanced_filter" value={ZEND_JSON::encode($ADVANCED_FILTERS)} />
                <input type="hidden" name='groupbyfield_rows' value={Zend_JSON::encode($PIVOT_MODEL->getGroupByFieldRows())} >
                <input type="hidden" name='groupbyfield_columns' value={Zend_JSON::encode($PIVOT_MODEL->getGroupByFieldColumns())} >
                <input type="hidden" name='sort_by' {if $SORT_BY} value='{Zend_JSON::encode($SORT_BY)}' {else} value='[]' {/if}/>
                <input type="hidden" name='limit' value='{$LIMIT}'/>
                <input type="hidden" name='order_by' value='{$ORDER_BY}'/>
                <input type="hidden" name='datafields' value={Zend_JSON::encode($PIVOT_MODEL->getDataFields())}>
                <input type="hidden" name='datafields-chart' value={Zend_JSON::encode($PIVOT_MODEL->get('datafields-chart'))}>
                <input type="hidden" name='groupbyfield' value={Zend_JSON::encode($PIVOT_MODEL->get('groupbyfield'))} >
                <input type="hidden" name='charttype' value="{$PIVOT_MODEL->get('type')}" />
                <input type="hidden" name='formatlargenumber' id="formatlargenumber" value="{$PIVOT_MODEL->get('formatlargenumber')}" />
                <input type="hidden" name='legendvalue' id="legendvalue" value="{$PIVOT_MODEL->get('legendvalue')}" />
                <input type="hidden" name='legendposition' id="legendposition" value="{$PIVOT_MODEL->get('legendposition')}" />
                <input type="hidden" name='displaygrid' id="displaygrid" value="{$PIVOT_MODEL->get('displaygrid')}" />
                <input type="hidden" name='displaylabel' id="displaylabel" value="{$PIVOT_MODEL->get('displaylabel')}" />
                <input type="hidden" name='drawline' id="drawline" value="{$PIVOT_MODEL->get('drawline')}" />
                {assign var=RECORD_STRUCTURE value=array()}
                {assign var=PRIMARY_MODULE_LABEL value=vtranslate($PRIMARY_MODULE, $PRIMARY_MODULE)}
                {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$PRIMARY_MODULE_RECORD_STRUCTURE}
                    {assign var=PRIMARY_MODULE_BLOCK_LABEL value=vtranslate($BLOCK_LABEL, $PRIMARY_MODULE)}
                    {assign var=key value="$PRIMARY_MODULE_LABEL $PRIMARY_MODULE_BLOCK_LABEL"}
                    {if $LINEITEM_FIELD_IN_CALCULATION eq false && $BLOCK_LABEL eq 'LBL_ITEM_DETAILS'}
                         {*dont show the line item fields block when Inventory fields are selected for calculations*}
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
                        <span class="cursorPointer header-text" name="modify_pivot_fields">
                            <span>
                                <i class="fa m-r-8 fa-chevron-right"></i>
                                &nbsp;&nbsp;{vtranslate('LBL_MODIFY_PIVOT_FIELDS', $MODULE)}
                            </span>
                        </span>
                        <div class='row hide' id="modify_pivot_fields">
                            <div class="col-lg-12">
                                <div class="col-lg-4">
                                    <h5>{vtranslate('LBL_SELECT_ROWS',$MODULE)}</h5>
                                    <select class="col-lg-12" multiple id="groupbyfield_rows" name="groupbyfield_rows[]">
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <h5>{vtranslate('LBL_SELECT_COLUMNS',$MODULE)}</h5>
                                    <select class="col-lg-12" multiple id="groupbyfield_columns" name="groupbyfield_columns[]">
                                    </select>
                                </div>
                                <div class="col-lg-4">
                                    <h5>{vtranslate('LBL_SELECT_DATA_FIELDS',$MODULE)}</h5>
                                    <select class="col-lg-12" multiple id="datafields" name="datafields[]">
                                    </select>
                                    <div class="col-lg-12" style="margin-top: 7px;">
                                        <table class="table table-borderless rename-field-translate">
                                            {foreach key=FIELD item=RENAME from=$RENAME_FIELDS}
                                            <tr>
                                                <td class="fieldLabel" name="{$RENAME->fieldname}">{$RENAME->fieldlabel}</td>
                                                <td class="fieldValue"">
                                                <input type="text" data-selected="{$RENAME->renameSelected}"  data-fieldname="{$RENAME->fieldname}" data-fieldlabel="{$RENAME->fieldlabel}" data-fieldtype="string" class="inputElement" name="rename_field" value="{$RENAME->translatedLabel}">
                                                </td>
                                            </tr>
                                            {/foreach}
                                        </table>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <h5>{vtranslate('LBL_SORT_BY',$MODULE)}</h5>
                                    <select class="col-lg-12 select2" multiple id="sort_by" name="sort_by[]" tabindex="-1">
                                    </select>
                                    <div class="col-lg-12" style="margin-top: 7px;">
                                        <table class="table table-borderless">
                                            <tr>
                                                <td class="fieldLabel">{vtranslate('LBL_LIMIT',$MODULE)}</td>
                                                <td class="fieldValue"">
                                                <input type="text" data-fieldtype="string" value="{$LIMIT}" class="inputElement" name="sort_limit">
                                                </td>
                                                <td class="fieldLabel">{vtranslate('LBL_ORDER',$MODULE)}</td>
                                                <td class="fieldValue">
                                                    <select class="col-lg-12 select2" id="order_by" name="order_by">
                                                        <option value="ASC" selected >ASC</option>
                                                        <option value="DESC">DESC</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class='hide'>
                            {include file="PivotReportHiddenContents.tpl"|vtemplate_path:$MODULE}
                        </div>
                    </div>
                    <br/>
                    <div>
                        {if is_array($SELECTED_ADVANCED_FILTER_FIELDS[1]['columns'])}
                            {assign var=COUNT_SELECTED_ADVANCED_FILTER_FIELDS_1_COLUMNS value=count($SELECTED_ADVANCED_FILTER_FIELDS[1]['columns'])}
                        {else}
                            {assign var=COUNT_SELECTED_ADVANCED_FILTER_FIELDS_1_COLUMNS value=0}
                        {/if}
                        {if is_array($SELECTED_ADVANCED_FILTER_FIELDS[2]['columns'])}
                            {assign var=COUNT_SELECTED_ADVANCED_FILTER_FIELDS_2_COLUMNS value=count($SELECTED_ADVANCED_FILTER_FIELDS[2]['columns'])}
                        {else}
                            {assign var=COUNT_SELECTED_ADVANCED_FILTER_FIELDS_2_COLUMNS value=0}
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
                </div>
                {if $REPORT_MODEL->isEditableBySharing() || $IS_ADMIN}
                    <div class="row textAlignCenter reportActionButtons hide">
                        <button class="btn btn-primary generateReportPivot" data-mode="save" value="{vtranslate('LBL_SAVE',$MODULE)}">
                            <strong>{vtranslate('LBL_GENERATE_REPORT',$MODULE)}</strong>
                        </button>
                    </div>
                {/if}
            </form>
        </div>
    </div>
    <div id="reportContentsDiv" style="margin-top: 30px">
{/strip}