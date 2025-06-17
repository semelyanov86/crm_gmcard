{*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}
{strip}
    <div class="installationContents" style="border:1px solid #ccc;padding:2%;">
        <form name="activateLicenseForm" action="index.php" method="post" id="controllayoutfields_step1" class="form-horizontal">
            <input type="hidden" name="module" value="VTEConditionalAlerts">
            <input type="hidden" name="view" value="Edit">
            <input type="hidden" name="mode" value="Step2" />
            <input type="hidden" name="parent" value="Settings" />
            <input type="hidden" class="step" value="1" />
            <input type="hidden" name="record" value="{$RECORDID}" />
            <div class="form-group">
            <label>
                    <strong>{vtranslate('LBL_STEP_1',$QUALIFIED_MODULE)}: {vtranslate('LBL_BASIC_DETAIL',$QUALIFIED_MODULE)}</strong>
                </label>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label">{vtranslate('LBL_SELECT_MODULE', $QUALIFIED_MODULE)}</label>
                    {if $MODE eq 'edit'}
                    <div class="col-sm-2 controls">
                        <input type='text' class="form-control" disabled='disabled' value="{$MODULE_MODEL['module']}" >
                        <input type='hidden' name='module_name' value="{$MODULE_MODEL['module']}" >
                    </div>
                    {else}
                <div class="col-sm-5 controls">
                <select class="chzn-select select2" id="moduleName" name="module_name" required="true" data-placeholder="Select Module..." style="width: 200px">
                            {foreach from=$ALL_MODULES key=TABID item=MODULE_MODEL}
                                <option value="{$MODULE_MODEL->getName()}" {if $SELECTED_MODULE == $MODULE_MODEL->getName()} selected {/if}>
                                    {if $MODULE_MODEL->getName() eq 'Calendar'}
                                        {vtranslate('LBL_TASK', $MODULE_MODEL->getName())}
                                    {else}
                                        {vtranslate($MODULE_MODEL->getName(), $MODULE_MODEL->getName())}
                                    {/if}
                                </option>
                            {/foreach}
                        </select>
                </div>
                    {/if}
            </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">{vtranslate('LBL_DESCRIPTION', $QUALIFIED_MODULE)}<span class="redColor">*</span> </label>
                        {if $MODE eq 'edit'}
                            <div class="col-sm-5 controls"> <input type="text" name="description" class="form-control" data-validation-engine='validate[required]' value="{$MODULE_MODEL['description']}" id="description" /></div>
                        {else}
                    <div class="col-sm-5 controls">
                        <input type="text" name="description" class="form-control" data-validation-engine='validate[required]' value="{$SELECTED_DES}" id="description" />
                    </div>
                        {/if}
                </div>
        <br>
        <div class="pull-right">
            <button class="btn btn-success" type="submit"><strong>{vtranslate('LBL_NEXT', $QUALIFIED_MODULE)}</strong></button>
            <a class="cancelLink" type="reset" onclick="javascript:window.history.back();">{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}</a>
        </div>
        <div class="clearfix"></div>
    </form>
</div>
{/strip}