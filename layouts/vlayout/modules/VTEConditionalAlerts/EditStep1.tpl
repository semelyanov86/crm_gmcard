{*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}
{strip}
    <div class="controlLayoutFieldsContents" style="padding-left: 3%;padding-right: 3%">
        <form name="EditVTEConditionalAlerts" action="index.php" method="post" id="controllayoutfields_step1" class="form-horizontal">
            <input type="hidden" name="module" value="VTEConditionalAlerts">
            <input type="hidden" name="view" value="Edit">
            <input type="hidden" name="mode" value="Step2" />
            <input type="hidden" name="parent" value="Settings" />
            <input type="hidden" class="step" value="1" />
            <input type="hidden" name="record" value="{$RECORDID}" />

            <div class="padding1per" style="border:1px solid #ccc;">
                <label>
                    <strong>{vtranslate('LBL_STEP_1',$QUALIFIED_MODULE)}: {vtranslate('LBL_BASIC_DETAIL',$QUALIFIED_MODULE)}</strong>
                </label>
                <br>
                <div class="control-group">
                    <div class="control-label">
                        {vtranslate('LBL_SELECT_MODULE', $QUALIFIED_MODULE)}
                    </div>
                    <div class="controls">
                        {if $MODE eq 'edit'}
                            <input type='text' disabled='disabled' value="{$MODULE_MODEL['module']}" >
                            <input type='hidden' name='module_name' value="{$MODULE_MODEL['module']}" >
                        {else}
                            <select class="chzn-select" id="moduleName" name="module_name" required="true" data-placeholder="Select Module...">
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
                        {/if}
                    </div>
                </div>
                <div class="control-group">
                    <div class="control-label">
                        {vtranslate('LBL_DESCRIPTION', $QUALIFIED_MODULE)}<span class="redColor">*</span>
                    </div>
                    <div class="controls">
                        {if $MODE eq 'edit'}
                            <input type="text" name="description" class="span5" data-validation-engine='validate[required]' value="{$MODULE_MODEL['description']}" id="description" />
                        {else}
                            <input type="text" name="description" class="span5" data-validation-engine='validate[required]' value="{$SELECTED_DES}" id="description" />
                        {/if}
                    </div>
                </div>
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