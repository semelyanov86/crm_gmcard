{*<!--
/* ********************************************************************************
 * The content of this file is subject to the VTEPayments("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}
{literal}
    <style>
        .popover{
            max-width: 1000px;
        }
        .tbl-tooltip{
            width: 350px;
            border-style: none;
            color: #333;
            /*background-color: rgb(229, 20, 0);*/
            -moz-border-radius: 5px;
            -webkit-border-radius: 5px;
            border-radius: 5px;
            display: inline-table;
        }
        .popover-content{
            width: 340px;
            padding: 0;
        }
        .tbl-tooltip-div{
            width: 100%;
            float:left;
            margin-left: 10px;
            margin-top: 10px;
        }
        .dv-pop-hd {
            width:50%;
            float:left;
            text-align: center;
            font-size: 150%;
            font-weight: bold;
        }
        .dv-pop-item {
            width:25%;
            float:left;
        }
        .amount_pre_paid{
            height: 12px;
            width: 22px;
            border: 0!important;
            outline: 0!important;
            background: transparent;
            border-bottom: 2px solid #333 !important;
            color: #333!important;
        }
        .val_will_paid{
            cursor: pointer;
        }
        .val_will_paid:hover{
            font-weight: bold;
        }
    </style>
{/literal}
<div>
        <div class="tbl-tooltip">
            <div class="tbl-tooltip-div">
                <div class="dv-pop-hd"> {vtranslate('Total','VTEPayments')}</div>
                <div class="dv-pop-hd"> {vtranslate('Balance','VTEPayments')}</div>
            </div>
            <div  class="tbl-tooltip-div">
                <div class="dv-pop-item"> {$CURRENCY}&nbsp;&nbsp;&nbsp; <span class="val_will_paid"></span></div>
                <div class="dv-pop-item"> (&nbsp;<input type="text" value="" name="total_pre_paid" data-total = {$TOTAL_ORG} class="input-medium currencyField amount_pre_paid" />%&nbsp;)</div>
                <div class="dv-pop-item"> {$CURRENCY}&nbsp;&nbsp;&nbsp; <span class="val_will_paid"></span></div>
                <div class="dv-pop-item"> (&nbsp;<input type="text" value="" name="balance_pre_paid" data-balance = {$BALANCE_ORG} class="input-medium currencyField amount_pre_paid" />%&nbsp;)</div>
            </div>
            {foreach key=AMOUNT_KEY item=AMOUNTS from=$AMOUNTS_TOOLTIP}
                <div  class="tbl-tooltip-div">
                        <div class="dv-pop-item"> {$CURRENCY}&nbsp;&nbsp;&nbsp; <span class="val_will_paid">{$AMOUNTS_TOOLTIP[$AMOUNT_KEY]['total']}</span></div>
                        <div class="dv-pop-item"> ({$AMOUNT_KEY})</div>
                        <div class="dv-pop-item"> {$CURRENCY}&nbsp;&nbsp;&nbsp; <span class="val_will_paid">{$AMOUNTS_TOOLTIP[$AMOUNT_KEY]['balance']}</span></div>
                        <div class="dv-pop-item"> ({$AMOUNT_KEY})</div>
                </div>
            {/foreach}
        </div>
</div>
