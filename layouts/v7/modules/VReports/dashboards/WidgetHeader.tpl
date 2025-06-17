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
    <style>
        .widget-header-title{
            overflow: hidden;
            display: inline-block;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

    </style>
    <div class="widget-header-vreport">
    <span class="widget-header-title" style="width: 70%">
        <span>
            {if $WIDGET->get('title')}
                {$WIDGET->get('title')}
            {else}
                {$TITLE}
            {/if}
        </span>
        {if $WIDGET->get('refresh_time') gt 0}
        <span style="margin-left: 50px; font-size: smaller">
            <i>Refreshed: {date('H:i:s', strtotime($WIDGET->get('last_refreshed_time')))}</i>
        </span>
        {/if}
    </span>
    <span class="widget-header-right" style="text-align: right;width: 30%">
        {if $WIDGET_NAME == 'MiniList'}
            <span class="page-number hide">
                        <span class="page-numbers" >1 to {$RECORD_COUNTS}</span>&nbsp;
                            <input type="hidden" name="page_limit" value="{$PAGE_LIMIT}">
                            <span class="totalNumberOfRecords cursorPointer" title="Click for this list size">of
                                &nbsp;<i class="fa fa-question showTotalCountIcon" onclick="VReports_DashBoard_Js.eventShowCount(this,{$WIDGET->get('id')})"></i>
                            </span>&nbsp;&nbsp;&nbsp;
                    </span>
        {/if}
        {if $WIDGET->get('shared') != true}
            {*replace icon setting -> wrench in history widget*}
            {if $TITLE == 'History'}
                <a class="fa fa-wrench action-widget-header hide" hspace="2" border="0" align="absmiddle" title="Edit" alt="Edit" data-event="Edit" data-show="show" onclick="VReports_DashBoard_Js.registerFilterInitiater(this)"></a>&nbsp;&nbsp;
                <a class="fa fa-cog action-widget-header hide" hspace="2" border="0" align="absmiddle" title="Setting" alt="Setting" data-event="Setting" onclick="VReports_DashBoard_Js.eventActionHeaderWidget(this)"></a>&nbsp;&nbsp;
            {else}
                <a class="fa fa-cog action-widget-header hide" hspace="2" border="0" align="absmiddle" title="Setting" alt="Setting" data-event="Setting" onclick="VReports_DashBoard_Js.eventActionHeaderWidget(this)"></a>&nbsp;&nbsp;
            {/if}
        {/if}
            <a class="fa fa-refresh action-widget-header hide" hspace="2" border="0" align="absmiddle" title="Refresh" alt="Refresh" data-event="Refresh" onclick="VReports_DashBoard_Js.eventActionHeaderWidget(this)"></a>&nbsp;&nbsp;
        {if $WIDGET->get('shared') != true}
            {if $WIDGET->get('report') == true}
                <a class="fa fa-eye action-widget-header hide" data-event="detail" onclick="VReports_DashBoard_Js.eventActionHeaderWidget(this)" style="margin-right: 5px;"></a>
                {if !$WIDGET->get('nonOwner')}
                    <a class="fa fa-pencil action-widget-header hide" data-event="edit" onclick="VReports_DashBoard_Js.eventActionHeaderWidget(this)" style="margin-right: 5px; "></a>
                {/if}
            {/if}
                {if !$WIDGET->get('nonOwner')}
                    <a class="fa fa-close action-widget-header hide" data-id="{{$WIDGET->get('reportid')}}" data-event="delete" onclick="VReports_DashBoard_Js.eventActionHeaderWidget(this)"></a>
                {/if}
        {/if}
        </span>
    </div>
{/strip}
