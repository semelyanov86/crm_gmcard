{************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************}
{strip}
    <input type="hidden" name="widgetId" value="{$WIDGET->get('id')}">
    <table style="width: 100%;white-space: nowrap;text-align: left">
        {foreach item=KEYMETRIC from=$KEYMETRICS}
            {if $KEYMETRIC.count neq 0 || $KEYMETRIC.km_show_empty_val neq 1}
            <tr style="height: 20px; font-size: 12px">
                <td style="width: 85%;padding-left: 10px" class="textOverflowEllipsis">
                    <a class="reference-display-value"
                       href="?module={$KEYMETRIC.module}&view=List&viewname={$KEYMETRIC.id}">{$KEYMETRIC.name}</a>
                </td>
                <td style="width: 15%;" class="textOverflowEllipsis">
                    <span>{$KEYMETRIC.count}</span>
                </td>
            </tr>
            {/if}

            {*<div style="padding-bottom:6px;" class="row-fluid">*}
            {*<div class="col-lg-6">*}
            {*<a class="p ull-left reference-display-value"*}
            {*href="?module={$KEYMETRIC.module}&view=List&viewname={$KEYMETRIC.id}">{$KEYMETRIC.name}</a>*}
            {*</div>*}
            {*<div class="col-lg-6">*}
            {*<span class="pull-right">{$KEYMETRIC.count}</span>*}
            {*</div>*}
            {*</div>*}
        {/foreach}
    </table>
{/strip}
