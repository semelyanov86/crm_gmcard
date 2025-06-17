{*/* 
********************************************************************************
* The content of this file is subject to the VTEPayments ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** 
*/*}
    <literal>
        <style>
            .tblPaymentListView{
                margin-left: 20px;
                width: 96%;
            }
        </style>
    <literal>
{strip}
    <table class="table table-bordered listViewEntriesTable tblPaymentListView">
        <thead>
        <tr class="myBlockHeader listViewHeaders">
            <th nowrap="">{vtranslate('Reference', 'VTEPayments')}</th>
            <th nowrap="">{vtranslate('Date', 'VTEPayments')}</th>
            <th nowrap="">{vtranslate('Status', 'VTEPayments')}</th>
            <th nowrap="">{vtranslate('Type', 'VTEPayments')}</th>
            <th nowrap="">{vtranslate('Description', 'VTEPayments')}</th>
            <th nowrap="" colspan="2">{vtranslate('Amount', 'VTEPayments')}</th>
        </tr>
        </thead>
        <tbody>
        {foreach item=payment from=$PAYMENTS}
            <tr class="listViewEntries payment-row {if is_array($payment.an_transactions) && $payment.an_transactions|count gt 0}has-anet-transaction{/if}"  data-id="{$payment.paymentid}">
                <td nowrap="" class="medium">{$payment.reference}</td>
                <td nowrap="" class="medium">{$payment.date}</td>
                <td nowrap="" class="medium status">{$payment.payment_status}</td>
                <td nowrap="" class="medium">{$payment.payment_type}</td>
                <td nowrap="" class="medium">{$payment.description}</td>
                <td nowrap="" class="medium amount_paid">{$payment.amount_paid}</td>
                <td nowrap="" class="medium">
                    <div class="pull-right actions">
                        <span class="actionImages">
                        {if $AN_INTEGRATE_STATUS}
                            {if $payment.an_transactions_count gt 0}
                            <button type="button" class="show-an-transactions btn btn-info" data-record="{$payment.paymentid}" title="{vtranslate('LBL_SHOW_AN_TRANSACTIONS', 'VTEPayments')}" style="margin-right: 2px; padding: 0 5px;">
                                <small style="font-size: 75%;">{vtranslate('LBL_SHOW_AN_TRANSACTION_DETAIL_BTN', 'VTEPayments')}</small>
                            </button>
                            {/if}
                            {if $payment.an_transactions_count eq 0}
                            <a href="javascript:void(0);" class="an-charging" data-record="{$payment.paymentid}">
                                <img style="vertical-align: middle; margin-right: 2px;" src="layouts/v7/modules/VTEPayments/resources/img/card1.png" title="{vtranslate('LBL_CHARGE_CUSTOMER_PROFILE', 'VTEPayments')}" />
                            </a>
                            {/if}
                            {if $payment.an_transactions_count eq 0}
                            <a href="javascript:void(0);" class="edit-payment" id="pm_{$payment.paymentid}"><i class="fa fa-pencil edit_info" title="Edit"></i></a>
                            <a class="relationDelete"><i class="fa fa-trash alignMiddle" title="Delete"></i></a>
                            {/if}
                        {else}
                            <a href="javascript:void(0);" class="edit-payment" id="pm_{$payment.paymentid}"><i class="fa fa-pencil edit_info" title="Edit"></i></a>
                            <a class="relationDelete"><i class="fa fa-trash delete_server" title="Delete"></i></a>
                        {/if}
                        </span>
                    </div>
                </td>
            </tr>
            {*begin show transactions*}
            {if $AN_INTEGRATE_STATUS}
                {if is_array($payment.an_transactions) && $payment.an_transactions|count gt 0}
                <tr id="an-transactions-payment{$payment.paymentid}-container" class="hide">
                    <td colspan="7">
                        <table class="table table-bordered listViewEntriesTable tblPaymentListView" style="margin: 0; width: 100%;">
                            <thead>
                            <tr class="myBlockHeader listViewHeaders">
                                <th nowrap="">{vtranslate('LBL_AN_TRANSACTION_ID', 'VTEPayments')}</th>
                                <th nowrap="">{vtranslate('LBL_AN_TRANSACTION_DATE', 'VTEPayments')}</th>
                                <th nowrap="">{vtranslate('LBL_AN_TRANSACTION_STATUS', 'VTEPayments')}</th>
                                <th nowrap="">{vtranslate('LBL_AN_TRANSACTION_AMOUNT', 'VTEPayments')}</th>
                                <th nowrap="">{vtranslate('LBL_AN_TRANSACTION_DETAIL', 'VTEPayments')}</th>
                            </tr>
                            </thead>
                            <tbody>
                                {foreach item=TRANSACTION from=$payment.an_transactions}
                                    <tr class="listViewEntries" style="background-color: #{if $TRANSACTION.request_status eq 'ERROR'}D27575{else}E5F5D7{/if};">
                                        <td nowrap="" class="medium">{$TRANSACTION.an_id}</td>
                                        <td nowrap="" class="medium">{$TRANSACTION.an_date}</td>
                                        <td nowrap="" class="medium">{$TRANSACTION.request_status}: {$TRANSACTION.an_status}</td>
                                        <td nowrap="" class="medium">{$TRANSACTION.amount_display}</td>
                                        <td class="medium"><div>{$TRANSACTION.an_description}</div></td>
                                    </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </td>
                </tr>
                {/if}
            {/if}
            {*end show transactions*}
        {/foreach}
        </tbody>
    </table>
{/strip}