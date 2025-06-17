{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

{strip}
    <style type="text/css">
        .grid-stack {
            margin-bottom: 100px;
            background: white;
        }

        .grid-stack-item-content {
            color: #2c3e50;
            text-align: center;
            background-color: white;
            box-shadow: 1px 1px 10px 1px #acaaaa;
        }

        .panel_content{
            height: 85%;
        }
        .panel_header {
            text-align: left;
            padding-left: 3%;
            padding-right: 3%;
            border-bottom: 1px solid #d2d1d1;
        }
    </style>
    <div class='dashBoardTabContainer'>
        <input type="hidden" class="check-dynamicfilter" value="{$NOTIFICATION_DYNAMIC}">
        <div class="device-xs visible-xs"></div>
        <div class="device-sm visible-sm"></div>
        <div class="device-md visible-md"></div>
        <div class="device-lg visible-lg"></div>
        <div class="device-xl visible-xl"></div>
        <div class="dashBoardTabContents clearfix">
            <div class="grid-stack grid-stack-tab{$TABID}">
                {foreach from=$WIDGETS item=WIDGET name=count}
                <div class="grid-stack-item dashboardWidgetGridStack"
                     {if $WIDGET->get('min_height')}data-min-height="{$WIDGET->get('min_height')}"{/if}
                     {if $WIDGET->get('max_height')}data-max-height="{$WIDGET->get('max_height')}"{/if}
                     data-record="{$WIDGET->get('widgetid')}"
                     data-gs-x="{$WIDGET->getPositionX()}" data-gs-y="{$WIDGET->getPositionY()}"
                     data-gs-width="{$WIDGET->getWidth()}"
                     data-gs-height="{$WIDGET->getHeight()}"
                     data-url="{$WIDGET->getUrl()}"
                     data-url-detail="{$WIDGET->getUrlReportDetail()}"
                     data-url-edit="{$WIDGET->getUrlReportEdit()}"
                     data-url-delete="{$WIDGET->getDeleteUrl()}"
                     {if $WIDGET->get('linklabel')}
                         data-widget-type="{$WIDGET->get('linklabel')}">
                     {else}
                         data-widget-type="{$WIDGET->get('title')}">
                     {/if}
                    <div class="panel panel-default grid-stack-item-content">
                    </div>
                </div>
                {/foreach}
            </div>
        </div>
    </div>
{/strip}