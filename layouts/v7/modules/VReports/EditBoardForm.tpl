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
        {if $DELETE eq true}
            {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE="{vtranslate('LBL_DELETE_BOARD',$MODULE)}"}
            {else}
            {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE="{vtranslate('LBL_EDIT_BOARD',$MODULE)}"}
        {/if}
        <div class="modal-content">
            <form id="EditBoard" name="EditBoard" method="post" action="index.php">
                <input type="hidden" name="module" value="{$MODULE}"/>
                <input type="hidden" name="action" value="DashboardActions"/>
                {if $DELETE eq true}
                    <input type="hidden" name="mode" value="deleteBoard"/>
                    {else}
                    <input type="hidden" name="mode" value="EditBoard"/>
                {/if}

                {if $EDITVIEW eq true}
                <div class="modal-body clearfix">
                    <div class='row board-edit'>
                        <div class='form-group'>
                            <label class='col-lg-5 control-label textAlignRight'>{vtranslate('LBL_BOARD_NAME',$MODULE)}</label>
                            <div class="col-lg-7">
                                <input type="text" name="boardName" data-rule-required="true" size="25" class="inputElement" maxlength='30'/>
                            </div>
                        </div>
                        <div class="col-lg-12" style='margin-top: 10px; padding: 5px;'>
                            <div class="alert-info">
                                <center>
                                    <i class="fa fa-info-circle"></i>&nbsp;&nbsp;
                                    {vtranslate('LBL_MAX_CHARACTERS_ALLOWED_DASHBOARD', $MODULE)}
                                </center></div>
                        </div>
                    </div>
                </div>
                {elseif $DELETE eq true}
                    {assign var=BUTTON_NAME value={vtranslate('LBL_DELETE', $MODULE)}}
                    <div class="modal-body clearfix">
                        <div class="col-lg-5">
                            <label class="control-label pull-right marginTop5px">
                                {vtranslate('LBL_SELECT_BOARD_NAME',$MODULE)}&nbsp;<span class="redColor">*</span>
                            </label>
                        </div>
                        <div class="col-lg-7">
                            <select name="select-board" class="col-lg-12 select2-container select2">
                                {foreach from=$ALL_BOARDS key=KEY item=BOARD}
                                    <option value="{$BOARD['id']}">{$BOARD['boardname']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                {else}
                    <div class="modal-body clearfix">
                        <div class="col-lg-5">
                            <label class="control-label pull-right marginTop5px">
                                {vtranslate('LBL_SELECT_BOARD_NAME',$MODULE)}&nbsp;<span class="redColor">*</span>
                            </label>
                        </div>
                        <div class="col-lg-7">
                            <select name="select-board" class="col-lg-12 select2-container select2">
                                {foreach from=$ALL_BOARDS key=KEY item=BOARD}
                                    <option value="{$BOARD['id']}">{$BOARD['boardname']}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-12">
                            <hr>
                        </div>
                        <div class='row board-edit'>
                            <div class='form-group'>
                                <label class='col-lg-5 control-label textAlignRight'>{vtranslate('LBL_BOARD_NAME',$MODULE)}</label>
                                <div class="col-lg-7">
                                    <input type="text" name="boardName" data-rule-required="true" size="25" class="inputElement" maxlength='30'/>
                                </div>
                            </div>
                            <div class="col-lg-12" style='margin-top: 10px; padding: 5px;'>
                                <div class="alert-info">
                                    <center>
                                        <i class="fa fa-info-circle"></i>&nbsp;&nbsp;
                                        {vtranslate('LBL_MAX_CHARACTERS_ALLOWED_DASHBOARD', $MODULE)}
                                    </center></div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <br>
                        </div>
                        <div class='row board-edit'>
                            <div class='form-group'>
                                <label class='col-lg-5 control-label textAlignRight'>{vtranslate('LBL_SHARE_BOARD',$MODULE)}</label>
                                <div class='col-lg-7'>
                                    <select id="memberList" class="col-lg-12 select2-container select2 members " multiple="true" name="members[]" data-placeholder="{vtranslate('LBL_ADD_USERS_ROLES', $MODULE)}">
                                        <option data-member-type="ShareAll" value="ShareAll:1" {if $SELECTED_MEMBERS_SHARE_ALL eq 1} selected="true" {/if}>{vtranslate('Everyone', $MODULE)}</option>
                                        {foreach from=$MEMBER_GROUPS key=GROUP_LABEL item=ALL_GROUP_MEMBERS}
                                            <optgroup label="{vtranslate($GROUP_LABEL, $MODULE)}">
                                                {foreach from=$ALL_GROUP_MEMBERS item=MEMBER}
                                                    <option value="{$MEMBER->getId()}"  data-member-type="{$GROUP_LABEL}" {if isset($SELECTED_MEMBERS_GROUP[$GROUP_LABEL][$MEMBER->getId()])}selected="true"{/if}>{$MEMBER->getName()}</option>
                                                {/foreach}
                                            </optgroup>
                                        {/foreach}
                                    </select>
                                </div>
                                {if $IS_ADMIN eq 'on'}
                                <div class="col-lg-12">
                                    <br>
                                </div>
                                <label class='col-lg-5 control-label textAlignRight'>{vtranslate('LBL_DEFAULT_TO_EVERYONE',$MODULE)}</label>
                                <div class='col-lg-7'>
                                    <input name="defaultToEveryone" type="checkbox">
                                </div>
                                {/if}
                            </div>
                        </div>
                    </div>
                {/if}

                {include file="ModalFooter.tpl"|vtemplate_path:$MODULE}
            </form>
        </div>
    </div>
{/strip}
