{*/*
/* ********************************************************************************
 * The content of this file is subject to the VTEPayments("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
*/*}
<literal>
    <style>
        /*.pmBalance{*/
            /*font-weight: bold;*/
        /*}*/
        #pmReceived,#pmInvoiceTotal{
            margin-left: 3px;
        }
        .table-total{
            font-size: 120%;
            width: 100%;
            margin-top: 10px;
            margin-bottom: 10px;
        }
    </style>
    <literal>
{strip}
    <div id="massEditContainer">
        <div id="massEdit">
            <input type="hidden" id="no_of_currency_decimals" value="{$USER_MODEL->get('no_of_currency_decimals')}">
            <div class="modal-header contentsBackground">
                <button type="button" class="close " data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>{vtranslate('Manage Payments', 'VTEPayments')} {if $RECORD}:{/if}</h3>
            </div>
            <div id="PaymentInfo" style="max-height: 550px; overflow:auto">
                {include file="EditViewBlocks.tpl"|@vtemplate_path:$MODULE}
            </div>
            <div id="ListPayments" class="ListPayments">
                {include file="ListPayments.tpl"|@vtemplate_path:$MODULE}
            </div>
            <div class="container-fluid">
                <table class="table-total" cellpadding="3px">
                    <tr style="text-align: right;">
                        <td>&nbsp;</td>
                        <td style="text-align: right;width: 90%;">{vtranslate('Received:   ', 'VTEPayments')}</td>
                        <td style="text-align: right;"> <span id="pmReceived">{$RECEIVED}</span></td>
                    </tr>
                    <tr style="text-align: right;">
                        <td style="text-align: left;width: 50%;">{vtranslate('Credit Available:   ', 'VTEPayments')} <span class="credit-available-amount">{$CREDIT_AVAILABLE_AMOUNT}</span></td>
                        <td style="text-align: right;width: 40%;">{vtranslate('Balance: ', 'VTEPayments')}</td>
                        <td style="text-align: right;">
                            <span id="pmBalance" class="pmBalance">
                                <a href="javascript:void(0);" id="balance_link" class="balance_link">
                                    {$BALANCE}
                                </a>
                            </span>
                        </td>
                    </tr>
                    <tr style="text-align: right;">
                        <td>&nbsp;</td>
                        <td style="text-align: right;width: 90%;">{vtranslate('Invoice Total:', 'VTEPayments')}</td>
                        <td style="text-align: right;"> <span id="pmInvoiceTotal">{$TOTAL}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
{/strip}