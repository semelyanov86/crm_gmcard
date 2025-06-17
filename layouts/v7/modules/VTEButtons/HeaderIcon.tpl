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
    {if count($HEADERS) gt 0}
  .vteButtonQuickUpdate {
        border-radius: 2px;
        background-image: none !important;
        box-shadow: none !important;
        line-height: 18px;
        cursor: pointer;
        font-weight: 400;
        padding: 6px 16px !important;
        margin: 0px 4px!important;
        background-color: #FFFFFF !important;
    }
    .quickPreviewActions .icon-module.vicon-pagecount{
        top: 9px;
        position: absolute;
        left: 32px;
    }
    .quickPreviewActions .vteButtonQuickUpdate {
        padding: 6px 11px 6px 25px !important;
    }
    .quick-preview-modal  .vteButtonQuickUpdate {
        padding-top: 1px !important;
        padding-bottom: 1px !important;
    }
    .vtebuttons-header-block{
        float: left;
    }
    .c-header{
        padding-top: 5px;
        padding-left: 7px;
        left: 14%;
    }
    .quick-preview-modal .c-header {

        margin-left: 0;
        padding-top: 0;
        margin-top: -5px;
    }
    #div_vtebuttons{
        display: none;
    }
</style>
    <div class="{if !$VTEPROGRESSBAR && !$VTECUSTOMHEADER}col-lg-10{else}col-lg-5{/if} c-header" id="div_vtebuttons">
       {foreach key =key item=HEADER from=$HEADERS}
           <style>
            .p-o-vtebtn{$HEADER['vtebuttonsid']}:hover {
                background-color: #{$HEADER['color']}!important;
                color: #FFFFFF!important;
            }
           </style>
            <div class="vtebuttons-header-block" {if $key gt 3 } style="margin-top: 0px;"{/if} data-vtebuttonid="{$HEADER['vtebuttonsid']}">
                <div style="text-align: left;margin-top: 4px;">
                    <button type="button" class="vteButtonQuickUpdate p-o-vtebtn{$HEADER['vtebuttonsid']}" data-vtebuttonid="{$HEADER['vtebuttonsid']}" style="color: #{$HEADER['color']};border: thin solid #{$HEADER['color']} !important; ">
                        <i class="icon-module {$HEADER['icon']}" style="font-size: inherit;"></i>
                        &nbsp;{$HEADER['header']}</button>
                </div>
            </div>
        {/foreach}
    </div>
    {/if}
{/strip}