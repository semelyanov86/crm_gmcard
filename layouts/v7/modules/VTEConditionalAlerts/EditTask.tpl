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
        .cap-padding-bottom1per{
            margin-top: 36px;
            /*padding-bottom: 1%;*/
        }
        .cap-padding-top{
            margin-bottom: 20px;
        }
        .addCapBtn{
            margin-left: 20px;s
        }
        .option-row{
            margin-top: 10px!important;
        }
        .item-child-row{
            margin-top: 5px;
        }
    </style>
    <link type="text/css" rel="stylesheet" href="libraries/jquery/bootstrapswitch/css/bootstrap2/bootstrap-switch.min.css?v=7.0.0" media="screen" />
    <script type="text/javascript" src="libraries/jquery/bootstrapswitch/js/bootstrap-switch.min.js?v=7.0.0"></script>
{/literal}
{strip}
    <div class='modal-dialog modal-lg cap-container' id="addTaskContainer">
        <div class="modal-header contentsBackground">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h3>{vtranslate('LBL_ADD_TASKS_FOR_WORKFLOW', $QUALIFIED_MODULE)} -> {$TASK_INFO['action_title']}</h3>
        </div>
        <form class="form-horizontal" id="saveTask" method="post" action="index.php">
            <input type="hidden" name="module" value="{$MODULE}" />
            <input type="hidden" name="parent" value="Settings" />
            <input type="hidden" name="action" value="TaskAjax" />
            <input type="hidden" name="mode" value="Save" />
            <input type="hidden" name="for_cap" value="{$CAT_ID}" />
            <input type="hidden" name="selected_module_name" value="{$SELECTED_MODULE}" />
            <input type="hidden" name="task_id" value="{$TASK_ID}" />
            <input type="hidden" name="actions" id="actions" value="{$TASK_INFO['actions']}" />
            <div id="scrollContainer">
                <div class="modal-body tabbable">
                    <div class="row cap-padding-top">
						<span class="col-lg-12 row" style="margin-bottom: 10px;">
							<div class="col-lg-2">{vtranslate('LBL_ACTION_TITLE',$QUALIFIED_MODULE)}<span class="redColor">*</span></div>
							<div class="col-lg-10 row">
                                <input name="action_title" class="form-control" type="text" value="{$TASK_INFO['action_title']}" />
                            </div>
						</span>
                        <span class="col-lg-12 row item-child-row">
							<div class="col-lg-3">{vtranslate('LBL_ALERT_WHILE_EDIT',$QUALIFIED_MODULE)}</div>
							<div class="col-lg-2 row">
                                 <input style="opacity: 0;" type="checkbox"
                                        {if $TASK_INFO['alert_while_edit']} checked value='1' {else} value='0' {/if} class ='cursorPointer bootstrap-switch switch-btn' name="alert_while_edit"
                                       data-on-text="{vtranslate('LBL_YES', $QUALIFIED_MODULE)}" data-off-text="{vtranslate('LBL_NO', $QUALIFIED_MODULE)}" data-on-color="primary"  />
                            </div>
						</span>
                         <span class="col-lg-12 row item-child-row">
							<div class="col-lg-3">{vtranslate('LBL_ALERT_WHEN_OPEN',$QUALIFIED_MODULE)}</div>
							<div class="col-lg-2 row">
                                <input style="opacity: 0;" type="checkbox"
                                        {if $TASK_INFO['alert_when_open']} checked value='1' {else} value='0' {/if} class ='cursorPointer bootstrap-switch switch-btn' name="alert_when_open"
                                       data-on-text="{vtranslate('LBL_YES', $QUALIFIED_MODULE)}" data-off-text="{vtranslate('LBL_NO', $QUALIFIED_MODULE)}" data-on-color="primary"  />
                            </div>
						</span>
                        <span class="col-lg-12 row item-child-row">
							<div class="col-lg-3" style="color: #808080">{vtranslate('LBL_ALERT_ON_SAVE',$QUALIFIED_MODULE)}</div>
							<div class="col-lg-4 row  hide">
                                <input style="opacity: 0;" type="checkbox" disabled
                                        {*{if $TASK_INFO['alert_on_save']} checked value='1' {else} value='0' {/if}*} value='0' class ='cursorPointer bootstrap-switch switch-btn' name="alert_on_save"
                                       data-on-text="{vtranslate('LBL_YES', $QUALIFIED_MODULE)}" data-off-text="{vtranslate('LBL_NO', $QUALIFIED_MODULE)}" data-on-color="primary"  /><span style="color: #808080">&nbsp;{vtranslate('(Temporary not available)', $QUALIFIED_MODULE)}</span>
                            </div>
						</span>
                        <span class="col-lg-12 row item-child-row">
							<div class="col-lg-3"  style="color: #808080">{vtranslate('LBL_DONOT_ALLOW_TO_SAVE',$QUALIFIED_MODULE)}</div>
							<div class="col-lg-4 row  hide">
                                <input style="opacity: 0;" type="checkbox" disabled
                                        {*{if $TASK_INFO['donot_allow_to_save']} checked value='1' {else}{/if}*} value='0'  class ='cursorPointer bootstrap-switch switch-btn' name="donot_allow_to_save"
                                       data-on-text="{vtranslate('LBL_YES', $QUALIFIED_MODULE)}" data-off-text="{vtranslate('LBL_NO', $QUALIFIED_MODULE)}" data-on-color="primary"  /><span style="color: #808080">&nbsp;{vtranslate('(Temporary not available)', $QUALIFIED_MODULE)}</span>
                            </div>
                            <div class="col-lg-2" style="float: right;margin-right: 60px;">
                                <span name="{$SELECTED_MODULE_MODEL->get('name')}" class="useFieldElement">
                                    {*{assign var=MODULE_FIELDS value=$SELECTED_MODULE_MODEL->getFields()}*}
                                    {*<select class="useThisField select2" data-placeholder="{vtranslate('LBL_USE_FIELD',$QUALIFIED_MODULE)}" style="width: 185px;">*}
                                        {*<option></option>*}
                                        {*{foreach from=$MODULE_FIELDS item=MODULE_FIELD}*}
                                            {*<option value="{$MODULE_FIELD->getName()}">{vtranslate($MODULE_FIELD->get('label'),$SELECTED_MODULE)}</option>*}
                                        {*{/foreach}*}
                                    {*</select>*}
                                    <select class="useThisField select2" data-placeholder="{vtranslate('LBL_USE_FIELD',$QUALIFIED_MODULE)}" style="width: 185px;">
                                        <option>{vtranslate('add Field', $QUALIFIED_MODULE)}</option>
                                        {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
                                            <optgroup label='{vtranslate($BLOCK_LABEL, $SELECTED_MODULE)}'>
                                                {foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
                                                    {assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
                                                    {assign var=MODULE_MODEL value=$FIELD_MODEL->getModule()}
                                                    {assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
                                                    {if !empty($COLUMNNAME_API)}
                                                        {assign var=columnNameApi value=$COLUMNNAME_API}
                                                    {else}
                                                        {assign var=columnNameApi value=getCustomViewColumnName}
                                                    {/if}
                                                    <option value="{$FIELD_MODEL->$columnNameApi()}" data-fieldtype="{$FIELD_MODEL->getFieldType()}" data-field-name="{$FIELD_NAME}"
                                                            {if ($MODULE_MODEL->get('name') eq 'Events') and ($FIELD_NAME eq 'recurringtype')}
                                                                {assign var=PICKLIST_VALUES value = Calendar_Field_Model::getReccurencePicklistValues()}
                                                                {$FIELD_INFO['picklistvalues'] = $PICKLIST_VALUES}
                                                            {/if}
                                                            data-fieldinfo='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($FIELD_INFO))}'
                                                            {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if}>
                                                        {if $SELECTED_MODULE neq $MODULE_MODEL->get('name')}
                                                            ({vtranslate($MODULE_MODEL->get('name'), $MODULE_MODEL->get('name'))})  {vtranslate($FIELD_MODEL->get('label'), $MODULE_MODEL->get('name'))}
                                                        {else}
                                                            {vtranslate($FIELD_MODEL->get('label'), $SELECTED_MODULE)}
                                                        {/if}
                                                    </option>
                                                {/foreach}
                                            </optgroup>
                                        {/foreach}
                                    </select>
                                </span>
                            </div>
						</span>
                        <span class="col-lg-12 row item-child-row" style="margin:10px;">
							<div class="col-lg-12 row">
                                <textarea name="description" id="description"  class="input-xxlarge">{$TASK_INFO['description']}</textarea>
                            </div>
						</span>
                    </div>

                </div>
            </div>
            {include file='ModalFooter.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
        </form>
    </div>
{/strip}
