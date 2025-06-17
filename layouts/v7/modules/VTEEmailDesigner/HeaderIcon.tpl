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
    .rcorners2 {
        border-radius: 5px;
        padding: 10px;
        width: 40px;
        height: 40px;
        float: left;
    }
    .header-div{
        float: left;
        width: 25%;
    }
    .c-header{
        padding-top: 5px;
        margin-left: -22%;
    }
    #div_custome_header{
        display: none;
    }
</style>
    <div class="col-lg-6 c-header" id="div_custome_header" >
       {foreach key = key item=HEADER from=$HEADERS}
            <div class="header-div" {if $key gt 3 } style="margin-top: 5px;"{/if}>
                <div class="rcorners2" style="border: 2px solid #{$HEADER['color']};">
                    <span class="icon-module {$HEADER['icon']}" style="font-size: 17px;color: #{$HEADER['color']};"></span>
                </div>
                <div style="text-align: left;margin-top: 4px;">
                    <span class="l-header muted" style="vertical-align: left; padding-left: 11px;">
                        {if $HEADER['header']|count_characters:true gt 15}
                            {mb_substr(trim($HEADER['header']),0,14)}...
                        {else}
                            {$HEADER['header']}
                        {/if}
                    </span>
                </div>
                <div  style="text-align: left;">
                    <span class="l-value" style="vertical-align: left; padding-left: 11px;text-align: left;">
                        {if $HEADER['field_value']|count_characters:true gt 15 && !$HEADER['field_value']|strstr:'</a>'}
                            {mb_substr(trim($HEADER['field_value']),0,14)}...
                        {else}
                            {$HEADER['field_value']}
                        {/if}
                    </span>
                </div>
            </div>

        {/foreach}
    </div>
{/strip}