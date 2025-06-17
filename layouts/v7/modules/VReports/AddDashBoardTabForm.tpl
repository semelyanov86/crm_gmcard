{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <div class="modal-dialog modelContainer">
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE="{if $TYPE_ACTION eq 'add'}{vtranslate('LBL_ADD_DASHBOARD',$MODULE)}{else}{vtranslate('LBL_DUPLICATE_TAB',$MODULE)}{/if}"}
        <div class="modal-content">
            <form id="AddDashBoardTab" name="AddDashBoardTab" method="post" action="index.php">
                <input type="hidden" name="module" value="{$MODULE}"/>
                <input type="hidden" name="action" value="DashboardActions"/>
                <input type="hidden" name="mode" value="{if $TYPE_ACTION eq 'add'}addTab{else}duplicateTab{/if}"/>
                <input type="hidden" name="boardid" value="{$BOARDID}"/>
                <div class="modal-body clearfix">
                    <div class="col-lg-5">
                        <label class="control-label pull-right marginTop5px">
                            {vtranslate('LBL_TAB_NAME',$MODULE)}&nbsp;<span class="redColor">*</span>
                        </label>
                    </div>
                    <div class="col-lg-6">
                        <input type="text" name="tabName" data-rule-required="true" size="25" class="inputElement" maxlength='30' {if $TAB_NAME} value="{$TAB_NAME} {/if}"/>
                    </div>
                    <br><br><br>
                    <div class="col-lg-5">
                        <label class="control-label pull-right marginTop5px">
                            {vtranslate('Board',$MODULE)}
                        </label>
                    </div>
                    <div class="col-lg-6">
                        <select  id="slDashBoardBoard" name="slDashBoardBoard" class="inputElement select2 slDashBoardBoard" style="width: 100%">
                            <option selected value="">{vtranslate('LBL_CURRENT_BOARD',$MODULE)}</option>
                            {foreach from=$DASHBOARD_BOARD  item=ROW}
                                <option value="{$ROW['id']}">{$ROW['boardname']}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="col-lg-12" style='margin-top: 10px; padding: 5px;'>
                        <div class="alert-info">
                            <center>
                                <i class="fa fa-info-circle"></i>&nbsp;&nbsp;
                                {vtranslate('LBL_MAX_CHARACTERS_ALLOWED_DASHBOARD', $MODULE)}
                            </center></div>
                    </div>
                </div>
                {include file="ModalFooter.tpl"|vtemplate_path:$MODULE}
            </form>
        </div>
    </div>
{/strip}
