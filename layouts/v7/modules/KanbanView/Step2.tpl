{*/* * *******************************************************************************
* The content of this file is subject to the Kanban View ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}
{strip}
    <div class="installationContents" style="border:1px solid #ccc;padding:2%;">
        <form name="activateLicenseForm" action="index.php" method="post" id="installation_step2" class="form-horizontal">
            <input type="hidden" class="step" value="2" />


            <div class="row">
                <label>
                    <strong>{vtranslate('LBL_WELCOME',$QUALIFIED_MODULE)} {vtranslate('MODULE_LBL',$QUALIFIED_MODULE)} {vtranslate('LBL_INSTALLATION_WIZARD',$QUALIFIED_MODULE)}</strong>
                </label>
            </div>
            <div class="clearfix">&nbsp;</div>
            <div class="row">
                <div>
                    <span>
                        {vtranslate('LBL_YOU_ARE_REQUIRED_VALIDATE',$QUALIFIED_MODULE)}
                    </span>
                </div>
            </div>
            <div class="row" style="margin-bottom:10px; margin-top: 5px">
                <span class="col-lg-1">
                    <strong>{vtranslate('LBL_VTIGER_URL',$QUALIFIED_MODULE)}</strong>
                </span>
                <span class="col-lg-4">
                    {$SITE_URL}
                </span>
            </div>
            <div class="row" style="margin-bottom:10px; margin-top: 5px">
                <span class="col-lg-1"><span class="redColor">*</span><strong>{vtranslate('LBL_LICENSE_KEY',$QUALIFIED_MODULE)}</strong></span>
                <span class="col-lg-4"><input type="text" id="license_key" name="license_key" value="" data-validation-engine="validate[required]" class="inputElement" name="summary"></span>
            </div>
            {if $VTELICENSE->result eq 'bad' || $VTELICENSE->result eq 'invalid'}
                <div class="alert alert-danger" id="error_message">
                    {$VTELICENSE->message}
                </div>
            {/if}


            <div class="row">
                <div><span>{vtranslate('LBL_HAVE_TROUBLE',$QUALIFIED_MODULE)} {vtranslate('LBL_CONTACT_US',$QUALIFIED_MODULE)}</span></div>
            </div>
            <div class="row">
                <ul style="padding-left: 10px;">
                    <li>{vtranslate('LBL_EMAIL',$QUALIFIED_MODULE)}: &nbsp;&nbsp;<a style="color: #0088cc; text-decoration:none;" href="mailto:Support@VTExperts.com">Support@VTExperts.com</a></li>
                    <li>{vtranslate('LBL_PHONE',$QUALIFIED_MODULE)}: &nbsp;&nbsp;<span>+1 (818) 495-5557</span></li>
                    <li>{vtranslate('LBL_CHAT',$QUALIFIED_MODULE)}: &nbsp;&nbsp;{vtranslate('LBL_AVAILABLE_ON',$QUALIFIED_MODULE)} <a style="color: #0088cc; text-decoration:none;" href="http://www.vtexperts.com" target="_blank">http://www.VTExperts.com</a></li>
                </ul>
            </div>

            <div class="row">
                <center>
                <button class="btn btn-success" name="btnActivate" type="button"><strong>{vtranslate('LBL_ACTIVATE', $QUALIFIED_MODULE)}</strong></button>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <button class="btn btn-info" name="btnOrder" type="button" onclick="window.open('http://www.vtexperts.com/extension/vtiger-duplicate-record-check-merge/')"><strong>{vtranslate('LBL_ORDER_NOW', $QUALIFIED_MODULE)}</strong></button>
                </center>
            </div>
        </div>
        <div class="clearfix"></div>
    </form>
</div>
{/strip}