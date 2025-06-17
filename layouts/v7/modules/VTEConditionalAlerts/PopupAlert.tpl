{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}

{strip}
    <div id="comnineTabContainer" class="modal-dialog" style='min-width:350px;'>
        <div class='modelContainer modal-content'>
            {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
            <div class="modal-body">
                <div class="form-group">
                    {foreach item=ACTION_ENTRY from=$ACTIONS name=action}
                        <h4>{$ACTION_ENTRY['action_title']}</h4>
                        <div class="alert-content" style="margin-bottom: -20px;">
                            <span>{$ACTION_ENTRY['description']}</span>
                        </div>
                        {if $ACTIONS|@count gt 1 && !$ACTION_ENTRY@last}
                            <br/>
                            <hr />
                            <br/>
                        {/if}
                        {if $ACTION_ENTRY['donot_allow_to_save'] eq 1}
                           <input type="hidden" name = "hd_donot_allow_to_save" class="hd_donot_allow_to_save" value="1" />
                        {/if}
                    {/foreach}
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" type="button" id="btnClosePopupAlert"><strong>Close</strong></button>
            </div>
        </div>
    </div>
{/strip}