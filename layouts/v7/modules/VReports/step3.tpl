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
    <form class="form-horizontal recordEditView" id="report_step3" method="post" action="index.php">
        <input type="hidden" name="module" value="{$MODULE}" />
        <input type="hidden" name="action" value="Save" />
        <input type="hidden" name="record" value="{$RECORD_ID}" />
        <input type="hidden" name="reportname" value="{Vtiger_Util_Helper::toSafeHTML($REPORT_MODEL->get('reportname'))}" />
        {if $REPORT_MODEL->get('members')}
            <input type="hidden" name="members" value='{ZEND_JSON::encode($REPORT_MODEL->get('members'))}' />
        {/if}
        <input type="hidden" name="folderid" value="{$REPORT_MODEL->get('folderid')}" />
        <input type="hidden" name="reports_description" value='{Vtiger_Util_Helper::toSafeHTML($REPORT_MODEL->get('description'))}' />
        <input type="hidden" name="primary_module" value="{$PRIMARY_MODULE}" />
        <input type="hidden" name="secondary_modules" value='{ZEND_JSON::encode($SECONDARY_MODULES)}' />
        <input type="hidden" name="selected_fields" id="seleted_fields" value='{$REPORT_MODEL->get('selected_fields')}' />
        <input type="hidden" name="selected_sort_fields" id="selected_sort_fields" value='{$REPORT_MODEL->get('selected_sort_fields')}' />
        <input type="hidden" name="selected_calculation_fields"  value='{$REPORT_MODEL->get('calculation_fields')}' />
        <input type="hidden" name="rename_columns" value='{$REPORT_MODEL->get('rename_columns')}' />
        <input type="hidden" name="advanced_filter" id="advanced_filter" value="" />
        <input type="hidden" name="isDuplicate" value="{$IS_DUPLICATE}" />
        <input type="hidden" class="step" value="3" />
        <input type="hidden" name="enable_schedule" value="{$REPORT_MODEL->get('enable_schedule')}">
        <input type="hidden" name="schtime" value="{$REPORT_MODEL->get('schtime')}">
        <input type="hidden" name="schdate" value="{$REPORT_MODEL->get('schdate')}">
        <input type="hidden" name="schdayoftheweek" value='{ZEND_JSON::encode($REPORT_MODEL->get('schdayoftheweek'))}'>
        <input type="hidden" name="schdayofthemonth" value='{ZEND_JSON::encode($REPORT_MODEL->get('schdayofthemonth'))}'>
        <input type="hidden" name="schannualdates" value='{ZEND_JSON::encode($REPORT_MODEL->get('schannualdates'))}'>
        <input type="hidden" name="recipients" value='{ZEND_JSON::encode($REPORT_MODEL->get('recipients'))}'>
        <input type="hidden" name="specificemails" value="{$REPORT_MODEL->get('specificemails')}">
        <input type="hidden" name="from_address" value="{$REPORT_MODEL->get('from_address')}">
        <input type="hidden" name="subject_mail" value="{$REPORT_MODEL->get('subject_mail')}">
        <textarea style="display: none" name="content_mail">{$REPORT_MODEL->get('content_mail')}</textarea>
        <input type="hidden" name="signature" value='{ZEND_JSON::encode($REPORT_MODEL->get('signature'))}'>
        <input type="hidden" name="signature_user" value='{ZEND_JSON::encode($REPORT_MODEL->get('signature_user'))}'>
        <input type="hidden" name="schtypeid" value="{$REPORT_MODEL->get('schtypeid')}">
        <input type="hidden" name="fileformat" value="{$REPORT_MODEL->get('fileformat')}">

        <input type="hidden" name="date_filters" data-value='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($DATE_FILTERS))}' />
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
        <div style="border:1px solid #ccc;padding:2%;">
            <div class="row">

                <h4><strong>{vtranslate('LBL_CHOOSE_FILTER_CONDITIONS',$MODULE)}</strong></h4>
                <br>
                <span class="col-lg-6" style="margin-bottom: 10px">
                    <button type="button" class="button-header-vreport" name="addgroup">
                        <i class="fa fa-plus-circle"></i>
                        <strong>  {vtranslate('ADD_GROUP', $MODULE)}</strong>&nbsp;&nbsp;
                    </button>
                </span>
                <div id="conditionClone" style="display: none">
                    <div class="button-action" style="margin: 20px auto; width: 200px; display: inherit">
                        <select class="group-condition" style="height: 30px;width: 70px">
                            <option value="or">{vtranslate('LBL_OR',$MODULE)}</option>
                            <option value="and">{vtranslate('LBL_AND',$MODULE)}</option>
                        </select>
                        <button class="btn btn-default deleteGroup" style="margin-left: 15px">{vtranslate('LBL_DELETE_GROUP',$MODULE)}</button>
                    </div>
                    {include file='AdvanceFilter.tpl'|@vtemplate_path:$MODULE RECORD_STRUCTURE=$RECORD_STRUCTURE COLUMNNAME_API=getReportFilterColumnName}
                </div>
                <span class="col-lg-12" id="filter_conditions">
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
                </span>
            </div>
        </div>
        <br>
        <div class="modal-overlay-footer border1px clearfix">
            <div class="row clearfix">
                <div class="textAlignCenter col-lg-12 col-md-12 col-sm-12 ">
                    <button type="button" class="btn btn-danger backStep"><strong>{vtranslate('LBL_BACK',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <button type="submit" class="btn btn-success" id="generateReport"><strong>{vtranslate('LBL_GENERATE_REPORT',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <a class="cancelLink" onclick="window.history.back()">{vtranslate('LBL_CANCEL',$MODULE)}</a>
                </div>
            </div>
        </div>
    </form>
{/strip}
