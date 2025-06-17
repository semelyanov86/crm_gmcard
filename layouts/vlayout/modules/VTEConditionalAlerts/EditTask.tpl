{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{literal}
    <style>
        .delTask{
            margin-left: -54px;
            margin-top: 7px;
        }
        .clf-padding-bottom1per{
            margin-top: 36px;
            /*padding-bottom: 1%;*/
        }
        .clf-padding-top{
            margin-bottom: 20px;
        }
        .addClfFieldBtn{
            margin-left: 20px;s
        }
        .option-row{
            margin-top: 10px!important;
        }
    </style>
{/literal}
{strip}
    <div class='modelContainer clf-container' id="addTaskContainer">
        <div class="modal-header contentsBackground">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h3>{vtranslate('LBL_ADD_TASKS_FOR_WORKFLOW', $QUALIFIED_MODULE)} -> {$TASK_INFO['name']}</h3>
        </div>
        <form class="form-horizontal" id="saveTask" method="post" action="index.php">
            <input type="hidden" name="module" value="{$MODULE}" />
            <input type="hidden" name="parent" value="Settings" />
            <input type="hidden" name="action" value="TaskAjax" />
            <input type="hidden" name="mode" value="Save" />
            <input type="hidden" name="for_clf" value="{$CLF_ID}" />
            <input type="hidden" name="selected_module_name" value="{$SELECTED_MODULE}" />
            <input type="hidden" name="task_id" value="{$TASK_ID}" />
            <input type="hidden" name="actions" id="actions" value="{$TASK_INFO['actions']}" />
            <div id="scrollContainer">
                <div class="modal-body tabbable">
                    <div class="row-fluid clf-padding-top">
						<span class="span8 row-fluid">
							<div class="span">{vtranslate('LBL_TASK_TITLE',$QUALIFIED_MODULE)}<span class="redColor">*</span></div>
							<div class="span9 row-fluid"><input name="name" class="span12" data-validation-engine='validate[required]' type="text" value="{$TASK_INFO['name']}" /></div>
						</span>
                        <span class="span">&nbsp;</span>
						<span class="span3 row-fluid">
							<div class="span3">{vtranslate('LBL_STATUS',$QUALIFIED_MODULE)}</div>
							<div class="span">
                                <input type="radio" name="active" class="alignTop" {if $TASK_INFO['active'] eq 1 || $TASK_INFO['active'] eq ''} checked="" {/if} value="true">&nbsp;{vtranslate('LBL_ACTIVE',$QUALIFIED_MODULE)}&nbsp;&nbsp;
                                <input type="radio" name="active" class="alignTop" {if $TASK_INFO['active'] neq 1 && $TASK_INFO['active'] neq ''} checked="" {/if} value="false" />&nbsp;{vtranslate('LBL_IN_ACTIVE',$QUALIFIED_MODULE)}
                            </div>
						</span>
                    </div>
                    <div class="taskTypeUi well">
                        <span class="span8 row-fluid">
                            <div class="span2">
                                <strong>Set Field Values</strong>
                            </div>
                        </span>
                        <span class="span8 row-fluid clf-container">
                            {foreach from=$TASK_INFO['actions'] item=ACTION}
                                <div class="option-row clf-padding-top">
                                        <span class="span4 useFieldContainer">
                                            <span name="{$SELECTED_MODULE_MODEL->get('name')}" class="useFieldElement">
                                                {assign var=MODULE_FIELDS value=$SELECTED_MODULE_MODEL->getFields()}
                                                <select class="chzn-select useField clfcb" data-placeholder="{vtranslate('LBL_USE_FIELD',$QUALIFIED_MODULE)}" style="min-width: 250px">
                                                    <optgroup>
                                                        {foreach from=$MODULE_FIELDS item=MODULE_FIELD}
                                                            {assign var=FIELD_TYPE value=$MODULE_FIELD -> get('typeofdata')}
                                                            {assign var=FIELD_ACTIVE value=$MODULE_FIELD -> get('presence')}
                                                            {assign var=FIELD_DISPLAY_TYPE value=$MODULE_FIELD -> get('displaytype')}
                                                            {$FIELD_ACTIVE}
                                                            {if $FIELD_ACTIVE != 1  && $FIELD_DISPLAY_TYPE == 1}
                                                                <option value="{$MODULE_FIELD->getName()}" {if $MODULE_FIELD->getName() eq $ACTION ->field} selected {/if}>{vtranslate($MODULE_FIELD->get('label'),$SELECTED_MODULE_MODEL->get('name'))}</option>
                                                            {/if}
                                                        {/foreach}
                                                    </optgroup>
                                                </select>
                                            </span>
                                        </span>
                                        <span class="fieldUiHolder span4">
                                            <select class="select2 fieldOption clfcb" style="min-width: 220px">
                                                {foreach key=KEY item=VALUE from=$FIELD_OPTIONS}
                                                    <option value="{$KEY}" {if $KEY eq $ACTION->option} selected {/if}>{vtranslate($VALUE,$SELECTED_MODULE_MODEL->get('name'))}</option>
                                                {/foreach}
                                            </select>
                                        </span>
                                        <span class="cursorPointer span">
                                        <i class="delTask deleteCondition icon-trash"></i>
                                        </span>
                                </div>
                                <br>
                            {/foreach}
                        </span>
                        <div>
                            <button class="btn addClfFieldBtn" type="button">Add Field</button>
                        </div>
                        <div class="basicAddFieldContainer hide clf-padding-top">
                            <span class="span4 useFieldContainer">
                                <span name="{$SELECTED_MODULE_MODEL->get('name')}" class="useFieldElement">
                                    {assign var=MODULE_FIELDS value=$SELECTED_MODULE_MODEL->getFields()}
                                    <select class="useField clfcb" data-placeholder="{vtranslate('LBL_USE_FIELD',$QUALIFIED_MODULE)}" style="min-width: 250px">
                                        <optgroup>
                                            {foreach from=$MODULE_FIELDS item=MODULE_FIELD}
                                                {assign var=FIELD_TYPE value=$MODULE_FIELD -> get('typeofdata')}
                                                {assign var=FIELD_ACTIVE value=$MODULE_FIELD -> get('presence')}
                                                {assign var=FIELD_DISPLAY_TYPE value=$MODULE_FIELD -> get('displaytype')}
                                                {$FIELD_ACTIVE}
                                                {if $FIELD_ACTIVE != 1  && $FIELD_DISPLAY_TYPE == 1}
                                                    <option value="{$MODULE_FIELD->getName()}" {if $MODULE_FIELD->getName() eq $ACTION ->field} selected {/if}>{vtranslate($MODULE_FIELD->get('label'),$SELECTED_MODULE_MODEL->get('name'))}</option>
                                                {/if}
                                            {/foreach}
                                        </optgroup>
                                    </select>
                                </span>
                            </span>
                            <span class="fieldUiHolder span4">
                                <select class="fieldOption clfcb" style="min-width: 220px">
                                    {foreach key=KEY item=VALUE from=$FIELD_OPTIONS}
                                        <option value="{$KEY}">{vtranslate($VALUE,$SELECTED_MODULE_MODEL->get('name'))}</option>
                                    {/foreach}
                                </select>
                            </span>
                            <span class="cursorPointer span">
                                <i class="delTask deleteCondition icon-trash"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            {include file='ModalFooter.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
        </form>
    </div>
{/strip}
