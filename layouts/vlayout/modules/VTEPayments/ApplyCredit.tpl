<form class="form-horizontal" style="height: 210px;">
  <div class="form-group">
    <label><strong>Please select available credit</strong></label>
</div>
  <div class="form-group">
  	<input type="hidden" name="paymentid" id="paymentid" value="{$PAYMENTID}">
    <div>
      <select class="available-credit">
      	<option value="">Please select available credit</option>
      	{foreach from=$LIST_PAYMENTS item=payment}
      	<option value="{$payment.paymentid}|{$payment.amount_paid}">{$payment.invoice_no} - {$payment.payment_type} - ${$payment.amount_paid}</option>
      	{/foreach}
  		</select>
    </div>
  </div>
  <div class="form-group">
    <label class="col-sm-6 control-label">Payment Amount</label>
    <label class="col-sm-6 control-label" style="text-align: left;padding-left: 10px;">$<span class="payment-amount">{$PAYMENT_AMOUNT}</span></label>
	</div>
  <div class="form-group">
    <label class="col-sm-6 control-label">Credit Amount</label>
    <label class="col-sm-6 control-label" style="text-align: left;padding-left: 10px;">$<span class="credit-amount">{$ZERO_VALUE}</span></label>
    </div>
  <div class="form-group">
    <label class="col-sm-6 control-label">Credit Amount Applied</label>
    <label class="col-sm-6 control-label" style="text-align: left;padding-left: 10px;">$<span class="credit-amount-applied">{$ZERO_VALUE}</span></label>
    </div>
  <div class="form-group">
    <label class="col-sm-6 control-label">&nbsp;</label>
    <label class="col-sm-6 control-label" style="text-align: left;padding-left: 10px;"><hr></label>
    </div>
  <div class="form-group">
    <label class="col-sm-6 control-label">New Amount</label>
    <label class="col-sm-6 control-label" style="text-align: left;padding-left: 10px;">$<span class="new-amount">{$ZERO_VALUE}</span></label>
    </div>
  <div class="form-group">
    <label class="col-sm-6 control-label">Remaining Credit</label>
    <label class="col-sm-6 control-label" style="text-align: left;padding-left: 10px;">$<span class="remaining-credit">{$ZERO_VALUE}</span></label>
    </div>
  <br>
</form>