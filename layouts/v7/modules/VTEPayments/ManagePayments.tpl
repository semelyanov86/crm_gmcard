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
    <div id="massEditContainer" class="modal-dialog" style="width: 1001px; margin-top: 10px;">
        <div id="massEdit" class="modal-content">
            <div class="modal-header contentsBackground">
                <button type="button" class="close " data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>{vtranslate('Manage Payments', 'VTEPayments')} {if $RECORD}:{/if}</h3>
            </div>
            <div id="PaymentInfo" style="max-height: calc(100vh - 100px); overflow-y:auto">
                {include file="EditViewBlocks.tpl"|@vtemplate_path:$MODULE}
            </div>
            <div id="ListPayments" class="ListPayments">
                {include file="ListPayments.tpl"|@vtemplate_path:$MODULE}
            </div>
            <div style="padding: 0px 20px; margin: 0 auto;">
                <table class="table-total" cellpadding="3px">
                    <tr style="text-align: right;">
                        <td style="text-align: right;width: 90%;">{vtranslate('Received:   ', 'VTEPayments')}</td>
                        <td style="text-align: right;"> <span id="pmReceived">{$RECEIVED}</span></td>
                    </tr>
                    <tr style="text-align: right;">
                        <td style="text-align: right;width: 90%;">{vtranslate('Balance: ', 'VTEPayments')}</td>
                        <td style="text-align: right;">
                            <span id="pmBalance" class="pmBalance">
                                <a href="javascript:void(0);" id="balance_link" class="balance_link">
                                    {$BALANCE}
                                </a>
                            </span>
                        </td>
                    </tr>
                    <tr style="text-align: right;">
                        <td style="text-align: right;width: 90%;">{vtranslate('Invoice Total:', 'VTEPayments')}</td>
                        <td style="text-align: right;"> <span id="pmInvoiceTotal">{$TOTAL}</span></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <link type="text/css" rel="stylesheet" href="libraries/jquery/posabsolute-jQuery-Validation-Engine/css/validationEngine.jquery.css" />
    <script src="libraries/jquery/posabsolute-jQuery-Validation-Engine/js/jquery.validationEngine.js"></script>
{/strip}