{*/* * *******************************************************************************
* The content of this file is subject to the Kanban View ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}
{strip}
<form id="detailView">

    {assign var=LEFTPANELHIDE value=$USER_MODEL->get('leftpanelhide')}
    <div class="essentials-toggle" title="{vtranslate('LBL_LEFT_PANEL_SHOW_HIDE', 'Vtiger')}">
        <span class="essentials-toggle-marker fa {if $LEFTPANELHIDE eq '1'}fa-chevron-right{else}fa-chevron-left{/if} cursorPointer"></span>
    </div>
    {if $FIELD_SETTING['primary_value_setting']}
        <style>
            .kbParentContainer{
                width: 100%;
                overflow-x:scroll;
            }
            .kbContainer{
                margin-left: 20px;
            }
        </style>
        <div class="kbParentContainer ">
            <div class="kbContainer ">
            <input id="kbSourceModule" type="hidden" value="{$KANBAN_SOURCE_MODULE}">
            <input type="hidden" id="primaryFieldName" value="{$PRIMARY_FIELD_SELECT}">
            <input type="hidden" id="primaryFieldId" value="{$FIELD_SETTING['primary_field']}">
            {if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
                <input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
            {/if}
                {foreach item=PRIMARY_FIELD_BLOCK  from=$FIELD_SETTING['primary_value_setting']}
                    <div class="kanbanBox">
                        <input type="hidden" name="primaryValue" value="{$PRIMARY_FIELD_BLOCK}"  >
                        <div class="kbBoxHeader">
                            <span class="kbBoxTitle">{vtranslate($PRIMARY_FIELD_BLOCK,'HelpDesk')}</span>
                            <span class="kbBoxIconTop"></span>
                        </div>
                        <div class="kbBoxContent">
                            {foreach item=RECORD_MODEL from=$LIST_RECORDS[$PRIMARY_FIELD_BLOCK]}
                                {assign var=BACKGROUND_CARD value= $RECORD_MODEL['RECORD']->get('kanban_color')}
                                {assign var=FONT_COLOR value= $RECORD_MODEL['RECORD']->get('font_color')}
                                <div class="kbBoxTask" {if !empty($BACKGROUND_CARD)}style="background:{$BACKGROUND_CARD} "{/if}>
                                    <input type="hidden" name="recordId" value="{$RECORD_MODEL['RECORD']->getId()}">
                                    <input type="hidden" name="sequence" value="{$RECORD_MODEL['sequence']}">
                                    <div class="kbTaskHeader">
                                        <span class="kbTaskTitle pull-left">
                                            <a href="index.php?module={$KANBAN_SOURCE_MODULE}&view=Detail&record={$RECORD_MODEL['RECORD']->getId()}&cvid={$CV_ID}" title="{$RECORD_MODEL['RECORD']->get($NAME_FIELD)}" {if !empty($FONT_COLOR)}style="color:{$FONT_COLOR} !important; "{/if}>
                                                {assign var=MODULE_MODEL value=$RECORD_MODEL['RECORD']->getModule()}
                                                {foreach item=NAME_FIELD from=$MODULE_MODEL->getNameFields()}
                                                    {assign var=FIELD_MODEL value=$MODULE_MODEL->getField($NAME_FIELD)}
                                                    {if $FIELD_MODEL->getPermissions()}
                                                            {$RECORD_MODEL['RECORD']->get($NAME_FIELD)}&nbsp;
                                                    {/if}
                                                {/foreach}
                                            </a>
                                        </span>
                                        <span class="kbEyeIcon pull-right">
                                            <a href="index.php?module={$KANBAN_SOURCE_MODULE}&view=Detail&record={$RECORD_MODEL['RECORD']->getId()}&cvid={$CV_ID}" title="{vtranslate('LBL_GO_TO_DETAIL_VIEW', 'KanbanView')}"><img src="layouts/v7/modules/KanbanView/images/eye.png" alt="Show more"/></a>
                                        </span>
                                        <span class="clearfix"></span>
                                    </div>
                                    <div class="kbTaskContent">
                                        {foreach item=FIELD_MODEL from=$ARR_SELECTED_FIELD_MODELS}
                                            {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue',$RECORD_MODEL['RECORD']->get($FIELD_MODEL->get('name')))}
                                            {assign var=FIELD_VALUE value=$FIELD_MODEL->get('fieldvalue')}
                                            {if $fieldDataType eq 'multipicklist'}
                                                {assign var=FIELD_DISPLAY_VALUE value=$FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue'))}
                                            {else}
                                                {assign var=FIELD_DISPLAY_VALUE value=Vtiger_Util_Helper::toSafeHTML($FIELD_MODEL->getDisplayValue($FIELD_MODEL->get('fieldvalue')))}
                                            {/if}

                                            {if $FIELD_MODEL->get('uitype') neq "83" }
                                                <div class="kbTaskSection1 fieldValue" data-field-name="{$FIELD_MODEL->getFieldName()}{if $FIELD_MODEL->get('uitype') eq '33'}[]{/if}" data-uitype = "{$FIELD_MODEL->get('uitype')}" data-record-id="{$RECORD_MODEL['RECORD']->getId()}" >
                                                    {if $FIELD_MODEL->get('uitype') eq '19' or $FIELD_MODEL->get('uitype') eq '21'}
                                                        <div class="kbLabelContainer" style="width: 100%;text-align: center;">
                                                            <span class="kbLabel" title="{vtranslate($FIELD_MODEL->get('label'),$KANBAN_SOURCE_MODULE)}" {if !empty($FONT_COLOR)}style="color:{$FONT_COLOR} !important; "{/if}>
                                                                {vtranslate($FIELD_MODEL->get('label'),$KANBAN_SOURCE_MODULE)}
                                                            </span>
                                                        </div>
                                                        <div class="kbValueContainer" id="{$KANBAN_SOURCE_MODULE}_detailView_fieldValue_{$FIELD_MODEL->getName()}" style="width: 100%; border: none; border-top: 1px solid #eaeaea;">
                                                            <span class="value pull-left" data-field-type="{$FIELD_MODEL->getFieldDataType()}" style="max-width: 95%;max-height: 60px; line-height: 20px;{if !empty($FONT_COLOR)}color:{$FONT_COLOR} !important; {/if}" title="{$FIELD_MODEL->getDisplayValue($RECORD_MODEL['RECORD']->get($FIELD_MODEL->get('name')))|strip_tags}">
                                                                {$FIELD_MODEL->getDisplayValue($RECORD_MODEL['RECORD']->get($FIELD_MODEL->get('name')))}
                                                            </span>
                                                            {if $FIELD_MODEL->isEditable() eq 'true' && ($FIELD_MODEL->getFieldDataType()!=Vtiger_Field_Model::REFERENCE_TYPE)}
                                                                <span class="hide edit pull-left">
                                                                    {if $fieldDataType eq 'multipicklist'}
                                                                        <input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}[]' data-type="{$FIELD_MODEL->getFieldDataType()}" data-displayvalue='{$FIELD_DISPLAY_VALUE}' data-value="{$FIELD_VALUE}" />
                                                                    {else}
                                                                        <input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}' data-type="{$FIELD_MODEL->getFieldDataType()}" data-displayvalue='{$FIELD_DISPLAY_VALUE}' data-value="{$FIELD_VALUE}" />
                                                                    {/if}
                                                                </span>
                                                                <span class="action pull-left"><a href="#" onclick="return false;" class="editAction fa fa-pencil"></a></span>
                                                            {/if}
                                                        </div>
                                                    {else}
                                                        <div class="kbLabelContainer">
                                                            <span class="kbLabel" title="{vtranslate($FIELD_MODEL->get('label'),$KANBAN_SOURCE_MODULE)}" {if !empty($FONT_COLOR)}style="color:{$FONT_COLOR} !important;"  {/if}>
                                                                {vtranslate($FIELD_MODEL->get('label'),$KANBAN_SOURCE_MODULE)}
                                                            </span>
                                                        </div>
                                                        <div class="kbValueContainer" id="{$KANBAN_SOURCE_MODULE}_detailView_fieldValue_{$FIELD_MODEL->getName()}">
                                                            <span class="value pull-left" data-field-type="{$FIELD_MODEL->getFieldDataType()}" {if !empty($FONT_COLOR)}style="color:{$FONT_COLOR} !important;" {/if} title="{$FIELD_MODEL->getDisplayValue($RECORD_MODEL['RECORD']->get($FIELD_MODEL->get('name')))|strip_tags}" >
                                                                {$FIELD_MODEL->getDisplayValue($RECORD_MODEL['RECORD']->get($FIELD_MODEL->get('name')))}
                                                            </span>
                                                            {if $FIELD_MODEL->isEditable() eq 'true' && ($FIELD_MODEL->getFieldDataType()!=Vtiger_Field_Model::REFERENCE_TYPE)}
                                                                <span class="hide edit pull-left">
                                                                    {if $fieldDataType eq 'multipicklist'}
                                                                        <input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}[]' data-type="{$FIELD_MODEL->getFieldDataType()}" data-displayvalue='{$FIELD_DISPLAY_VALUE}' data-value="{$FIELD_VALUE}" />
                                                                    {else}
                                                                        <input type="hidden" class="fieldBasicData" data-name='{$FIELD_MODEL->get('name')}' data-type="{$FIELD_MODEL->getFieldDataType()}" data-displayvalue='{$FIELD_DISPLAY_VALUE}' data-value="{$FIELD_VALUE}" />
                                                                    {/if}
                                                                </span>
                                                                <span class="action pull-left"><a href="#" onclick="return false;" class="editAction fa fa-pencil"></a></span>
                                                            {/if}
                                                        </div>
                                                    {/if}
                                                    <div class="clearfix"></div>
                                                </div>
                                            {/if}
                                        {/foreach}

                                    </div>
                                    <div class="kbTaskFooter">
                                        <span class="pull-right btnEditTaskl">
                                            <a href="javascript:void(0)" data-url="index.php?module=KanbanView&view=QuickEditAjax&record={$RECORD_MODEL['RECORD']->getId()}&source_module={$KANBAN_SOURCE_MODULE}" title="Edit" class="fa fa-pencil alignMiddle kbQuickEdit"></a>
                                        </span>
                                        <span class="clearfix"></span>
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                {/foreach}
            </div>
        </div>
    {/if}
</form>
{/strip}
