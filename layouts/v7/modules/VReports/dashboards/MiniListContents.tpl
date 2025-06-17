{************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************}
{*<div style='padding-top: 0;margin-bottom: 2%;padding-right:15px;'>*}
<style type="text/css">
    .minilist-table .miniListContent:hover {
        background-color: #ffffff !important;
    }
</style>
<input type="hidden" id="widget_{$WIDGET->get('id')}_currentPage" value="{$CURRENT_PAGE}">
<input type="hidden" name="widgetId" value="{$WIDGET->get('id')}">
{* Comupte the nubmer of columns required *}
{assign var="SPANSIZE" value=12}
{assign var=TABLE_STYLE value=VReports_MiniList_Model::getStyleForTable($WIDGET->get('id'))}
{assign var=HEADER_COUNT value=$MINILIST_WIDGET_MODEL->getHeaderCount()}
{if $HEADER_COUNT}
    {assign var="SPANSIZE" value=12/$HEADER_COUNT}
{/if}
<table class="minilist-table" name="miniListTable" data-module-name="{$SELECTED_MODULE_NAME}" style="
{if $TABLE_STYLE['minilisttable_table']}
    {$TABLE_STYLE['minilisttable_table']}
{else}
        white-space: nowrap;
{/if}
        ">
    <input type="hidden" id="record-counts-listview" value="{$RECORD_COUNTS}">
    <thead>
    <tr class="minilist-table-tr" style="
    {if $TABLE_STYLE['minilisttable_tr']}
        {$TABLE_STYLE['minilisttable_tr']}
    {/if}
            ">
        {*<div class="row" style="padding:5px">*}
        <td class="fake-head" colspan="2"></td>
        {assign var=HEADER_FIELDS value=$MINILIST_WIDGET_MODEL->getHeaders()}
        {assign var=LISTVIEW_HEADERS value=$MINILIST_WIDGET_MODEL->getHeaders()}
        {include file="PicklistColorMap.tpl"|vtemplate_path:$BASE_MODULE}
        {foreach item=FIELD from=$HEADER_FIELDS}
            <td class="minilist-table-td" style="{if $FIELD->get('uitype') eq 72 || $FIELD->get('uitype') eq 7 ||
                        $FIELD->get('uitype') eq 83 || $FIELD->get('uitype') eq 9 || $FIELD->get('uitype') eq 71}
                            {if !$smarty.foreach.minilistWidgetModelRowHeaders.first}
                                    text-align: right;
                            {/if}
                        {/if}">
                <var class="italic_small_size">{vtranslate($FIELD->get('label'),$BASE_MODULE)}</var>
            </td>
        {/foreach}
        {*</div>*}
    </tr>
    </thead>

    {*    {assign var="MINILIST_WIDGET_RECORDS" value=$MINILIST_WIDGET_MODEL->getRecords()}*}
    <tbody>
    {if empty($MINILIST_WIDGET_RECORDS)}
        <tr>
            <td colspan="{$HEADER_COUNT+2}" style="text-align: center">{vtranslate('LBL_NO_RECORDS_FOUND')}</td>
        </tr>
    {/if}
    {if $MINILIST_WIDGET_RECORDS}
        {foreach item=RECORD from=$MINILIST_WIDGET_RECORDS}
            {assign var="RAW_DATA_MINILIST" value=$RECORD->getRawData()}
            {assign var="RECORD_ID" value=$RECORD->get('id')}
            {assign var="RECORD_COLOR" value=$MINILIST_RECORDS_COLOR[$RECORD_ID]}

            {if $RECORD->get('hrOnRow') eq true}
                <tr class="lineOnRow">
                    <td colspan="{$HEADER_COUNT+2}">
                        <hr>
                    </td>
                </tr>
            {/if}
            <tr class="miniListContent"
                data-id="{$RECORD->get('id')}" {if $RECORD_COLOR} style="background-color:{$RECORD_COLOR['bg_color']}; color: {$RECORD_COLOR['text_color']}" {/if}>
                {*<div class="row miniListContent" style="padding:5px">*}
                {foreach item=FIELD_MODEL key=FIELD_NAME from=$HEADER_FIELDS name="minilistWidgetModelRowHeaders"}
                    {if $smarty.foreach.minilistWidgetModelRowHeaders.first}
                        {include file="layouts/v7/modules/VReports/dashboards/MiniListRecordAction.tpl" WIDGET=$WIDGET}
                    {/if}
                    <td name="tdMinilistTable" class="col-lg-{$SPANSIZE} minilist-column"
                        title="{strip_tags({$RECORD->get($FIELD_NAME)})}"
                        style="overflow: unset !important;{if $TABLE_STYLE['minilisttable_td']}{$TABLE_STYLE['minilisttable_td']}{/if}{if $FIELD_MODEL->get('uitype') eq 72 || $FIELD_MODEL->get('uitype') eq 7 || $FIELD_MODEL->get('uitype') eq 83 || $FIELD_MODEL->get('uitype') eq 9 || $FIELD_MODEL->get('uitype') eq 71}{if !$smarty.foreach.minilistWidgetModelRowHeaders.first}text-align: right;{/if}{/if}"
                        data-field-name="{$FIELD_NAME}">
                        {assign var=UITYPE value=array(2,4,22,25,10,51,57,59,73,75,76,77,78,80,81)}
                        {if $FIELD_MODEL->get('uitype') eq '71' || ($FIELD_MODEL->get('uitype') eq '72' && $FIELD_MODEL->getName() eq 'unit_price')}
                            {assign var=CURRENCY_ID value=$USER_MODEL->get('currency_id')}
                            {if $FIELD_MODEL->get('uitype') eq '72' && $FIELD_NAME eq 'unit_price'}
                                {assign var=CURRENCY_ID value=getProductBaseCurrency($RECORD_ID, $RECORD->getModuleName())}
                            {/if}
                            {assign var=CURRENCY_INFO value=getCurrencySymbolandCRate($CURRENCY_ID)}
                            {if $RECORD->get($FIELD_NAME) neq NULL}
                                {CurrencyField::appendCurrencySymbol($RECORD->get($FIELD_NAME), $CURRENCY_INFO['symbol'])}&nbsp;
                            {/if}
                        {elseif $FIELD_MODEL->get('uitype') eq '15' || $FIELD_MODEL->get('uitype') eq '16'}
                            {assign var=PICKLIST_COLOR_MAP value=Settings_Picklist_Module_Model::getPicklistColorMap($FIELD_NAME, true)}
                            {assign var=PICKLIST_COLOR value=VReports_Module_Model::getPicklistColorByValue($FIELD_MODEL->getName(), $RECORD)}
                            {assign var=PICKLIST_TEXT_COLOR value=decode_html(Settings_Picklist_Module_Model::getTextColor($PICKLIST_COLOR))}
                            {assign var=PICKLIST_VALUES value=$FIELD_MODEL->getPicklistValues()}
                            <span class="dropdown editPickListFieldValue"><a class="dropdown-toggle"
                                                                             data-toggle="dropdown"
                                                                             aria-expanded="false"><i title="Edit"
                                                                                                      class="fa fa-arrow-down alignMiddle"
                                                                                                      style="color:white;margin-left: -12px;"></i></a><ul
                                        class="dropdown-menu"
                                        style="height: 200px;width: 100px;color: #15c!important;position: absolute;overflow: auto;top: 0;">{foreach item=PICKLIST_VALUE_DISPLAY key=PICKLIST_VALUE from=$PICKLIST_VALUES}
                                    <li data-picklist-value="{$PICKLIST_VALUE}"
                                        style="border: none;padding: 0;position: relative">
                                        <a>{$PICKLIST_VALUE_DISPLAY}</a></li>{/foreach}</ul>
                            </span>
                            <span
                                    style="margin-left: 1px; {if $RECORD_COLOR} color: {$RECORD_COLOR['text_color']} !important;{/if}"
                                    class="picklist-color picklist-{$FIELD_MODEL->getId()}-{Vtiger_Util_Helper::convertSpaceToHyphen($RECORD->get($FIELD_NAME))}">
                            {$RECORD->get($FIELD_NAME)}
                            </span>
                        {elseif $FIELD_MODEL->get('uitype') eq '33' || $FIELD_MODEL->getFieldDataType() eq 'multipicklist'}
                            {assign var=MULTI_RAW_PICKLIST_VALUES value=explode('|##|',{$RECORD->get($FIELD_NAME)})}
                            {assign var=MULTI_PICKLIST_VALUES value=explode(',',{$RECORD->get($FIELD_NAME)})}
                            {assign var=ALL_MULTI_PICKLIST_VALUES value=array_flip($FIELD_MODEL->getPicklistValues())}
                            {foreach item=MULTI_PICKLIST_VALUE key=MULTI_PICKLIST_INDEX from=$MULTI_PICKLIST_VALUES}
                                <span {if ($RECORD->get($FIELD_NAME)) neq ''} class="picklist-color picklist-{$FIELD_MODEL->getId()}-{Vtiger_Util_Helper::convertSpaceToHyphen(trim($ALL_MULTI_PICKLIST_VALUES[trim($MULTI_PICKLIST_VALUE)]))}"{/if} {if $RECORD_COLOR} style="color: {$RECORD_COLOR['text_color']} !important;" {/if} >{if trim($MULTI_PICKLIST_VALUES[$MULTI_PICKLIST_INDEX]) eq vtranslate('LBL_NOT_ACCESSIBLE', $MODULE)}
                                    <font color="red">{trim($MULTI_PICKLIST_VALUES[$MULTI_PICKLIST_INDEX])}</font>{else}{trim($MULTI_PICKLIST_VALUES[$MULTI_PICKLIST_INDEX])}{/if}{if !empty($MULTI_PICKLIST_VALUES[$MULTI_PICKLIST_INDEX + 1])},{/if}
                                </span>
                            {/foreach}
                        {elseif ($FIELD_MODEL->getFieldName() eq 'lastname' || in_array($FIELD_MODEL->get('uitype'),$UITYPE)) && $RECORD->get($FIELD_NAME) != '--'}
                            <a class="js-reference-display-value" {if $RECORD_COLOR} style="color: {$RECORD_COLOR['related_record_color']} !important;" {/if}
                               onclick='window.open("{$RECORD->getDetailViewUrl()}");return false;'>{strip_tags($RECORD->get($FIELD_NAME))}
                                &nbsp;</a>
                        {else}
                            {$RECORD->get($FIELD_NAME)}
                        {/if}
                    </td>
                {/foreach}
                {*</div>*}
            </tr>
        {/foreach}
    {/if}
    </tbody>
</table>
{if $MORE_EXISTS}
    <div class="moreLinkDiv" style="padding-top:10px;padding-bottom:5px;">
        <a class="miniListMoreLink" data-linkid="{$WIDGET->get('linkid')}" data-widgetid="{$WIDGET->get('id')}"
           onclick="VReports_DashBoard_Js.registerMoreClickEvent(event);">{vtranslate('LBL_MORE')}...</a>
    </div>
{/if}
{*</div>*}
