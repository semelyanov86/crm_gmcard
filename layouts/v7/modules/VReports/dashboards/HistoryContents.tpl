{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*************************************************************************************}

<div name="historyContents" style="padding:5px;float: left;width: 100%">
    <input type="hidden" name="widgetId" value="{$WIDGET->get('id')}">
    {if $HISTORIES neq false}
        {assign var=OLD_PARENT_ID value=''}
        {foreach key=$index item=HISTORY from=$HISTORIES}
            {assign var=MODELNAME value=get_class($HISTORY)}
            {if $MODELNAME == 'ModTracker_Record_Model'}
                {assign var=USER value=$HISTORY->getModifiedBy()}
                {assign var=TIME value=$HISTORY->getActivityTime()}
                {assign var=PARENT value=$HISTORY->getParent()}
                {assign var=PARENT_ID value=$PARENT->getId()}
                {assign var=MOD_NAME value=$HISTORY->getParent()->getModule()->getName()}
                {assign var=SINGLE_MODULE_NAME value='SINGLE_'|cat:$MOD_NAME}
                {assign var=TRANSLATED_MODULE_NAME value = vtranslate($MOD_NAME ,$MOD_NAME)}
                {assign var=PROCEED value= TRUE}
                {if ($HISTORY->isRelationLink()) or ($HISTORY->isRelationUnLink())}
                    {assign var=RELATION value=$HISTORY->getRelationInstance()}
                    {if !($RELATION->getLinkedRecord())}
                        {assign var=PROCEED value= FALSE}
                    {/if}
                {/if}
                {if $PROCEED}
                    {if $PARENT_ID != $OLD_PARENT_ID && $OLD_PARENT_ID != ''}<hr>{/if}
                    <div class="row entry clearfix history_widget" style="text-align: left"  data-history-id="{$HISTORY->getId()}">
                        <div class='col-lg-1 pull-left'>
                            {assign var=VT_ICON value=$MOD_NAME}
                            {if $MOD_NAME eq "Events"}
                                {assign var="TRANSLATED_MODULE_NAME" value="Calendar"}
                                {assign var=VT_ICON value="Calendar"}
                            {else if $MOD_NAME eq "Calendar"}
                                {assign var=VT_ICON value="Task"}
                            {/if}
                            <span>{$HISTORY->getParent()->getModule()->getModuleIcon($VT_ICON)}</span>&nbsp;&nbsp;
                        </div>
                        <div class="col-lg-11 pull-left">
                            {assign var=DETAILVIEW_URL value=$PARENT->getDetailViewUrl()}
                            {if $HISTORY->isUpdate()}
                                {assign var=FIELDS value=$HISTORY->getFieldInstances()}
                                <div>
                                    <div><b>{$USER->getName()}</b> {vtranslate('LBL_UPDATED')} <a target="_blank"
                                                                                                  class="cursorPointer reference-display-value"
                                                                                                  href="{$DETAILVIEW_URL}">
                                            {$PARENT->getName()}</a>
                                        <a class="quickView fa fa-eye icon action history-widget"
                                           data-module-name="{$HISTORY->getParent()->getModuleName()}"
                                           style="padding-right: 5px;display: none"
                                           data-id="{$HISTORY->getParent()->get('id')}" data-app="TOOLS"
                                           title="Quick View" target="_blank"></a>
                                        {if $TIME}
                                            <p class="pull-right muted" style="padding-right:10px;">
                                                <small style="padding-right: 5px"
                                                       title="{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}">{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}
                                                    ({Vtiger_Util_Helper::formatDateDiffInStrings("$TIME")})
                                                </small>
                                            </p>
                                        {/if}
                                    </div>
                                    {foreach from=$FIELDS key=INDEX item=FIELD}
                                        {if $INDEX lt 3}
                                            {if $FIELD && $FIELD->getFieldInstance() && $FIELD->getFieldInstance()->isViewableInDetailView()}
                                                <div>
                                                    <i>{vtranslate($FIELD->getName(), $FIELD->getModuleName())}</i>
                                                    {if $FIELD->get('prevalue') neq '' && $FIELD->get('postvalue') neq '' && !($FIELD->getFieldInstance()->getFieldDataType() eq 'reference' && ($FIELD->get('postvalue') eq '0' || $FIELD->get('prevalue') eq '0'))}
                                                        &nbsp;{vtranslate('LBL_FROM')}
                                                        <b>{Vtiger_Util_Helper::toVtiger6SafeHTML($FIELD->getDisplayValue(decode_html($FIELD->get('prevalue'))))}</b>
                                                    {else if $FIELD->get('postvalue') eq '' || ($FIELD->getFieldInstance()->getFieldDataType() eq 'reference' && $FIELD->get('postvalue') eq '0')}
                                                        &nbsp;
                                                        <b> {vtranslate('LBL_DELETED')} </b>
                                                        (
                                                        <del>{Vtiger_Util_Helper::toVtiger6SafeHTML($FIELD->getDisplayValue(decode_html($FIELD->get('prevalue'))))}</del>
                                                        )
                                                    {else}
                                                        &nbsp;{vtranslate('LBL_CHANGED')}
                                                    {/if}
                                                    {if $FIELD->get('postvalue') neq '' && !($FIELD->getFieldInstance()->getFieldDataType() eq 'reference' && $FIELD->get('postvalue') eq '0')}
                                                        {vtranslate('LBL_TO')}
                                                        <b>{Vtiger_Util_Helper::toVtiger6SafeHTML($FIELD->getDisplayValue(decode_html($FIELD->get('postvalue'))))}</b>
                                                    {/if}
                                                </div>
                                            {/if}
                                        {else}
                                            {*<a href="{$PARENT->getUpdatesUrl()}">{vtranslate('LBL_MORE')}</a>*}
                                            {break}
                                        {/if}
                                    {/foreach}
                                </div>
                            {else if $HISTORY->get('status') eq 6 || $HISTORY->get('status') eq 7}
                                {assign var=EMAIL value=VReports_History_Model::getRelatedRecordForHistory($HISTORY)}
                                {assign var=DATA_MODULE value=VReports_History_Model::getModuleNameForHistory($HISTORY->getParent()->get('parent_id'))}
                                <div>
                                    <b>{$USER->getName()}</b>
                                    {if $HISTORY->get('status') eq 6}
                                        &nbsp;&nbsp;<span class="glyphicon glyphicon-arrow-left"></span>&nbsp;&nbsp;
                                        {vtranslate('received')}
                                        <a target="_blank"
                                           class="cursorPointer reference-display-value" class="cursorPointer"
                                                {if stripos($DETAILVIEW_URL, 'javascript:')===0} onclick='{$DETAILVIEW_URL|substr:strlen("javascript:")}'
                                                {else} href='{$DETAILVIEW_URL}' {/if}>{$PARENT->getName()}</a>
                                        {vtranslate('from')}
                                        {if $EMAIL['data'] eq 'relation'}
                                            <a target="_blank" class="cursorPointer reference-display-value"
                                               href="{$EMAIL['url']}" data-url=""> {$EMAIL['label']}</a>
                                            <a class="quickView fa fa-eye icon action history-widget"
                                               data-module-name="{$DATA_MODULE}"
                                               style="padding-right: 5px;display: none"
                                               data-id="{$HISTORY->getParent()->get('parent_id')}" data-app="TOOLS"
                                               title="Quick View" target="_blank"></a>
                                        {else}
                                            <a target="_blank" class="cursorPointer reference-display-value"> {$EMAIL['from_email']}</a>
                                        {/if}
                                        {if $EMAIL['email_body_plain']}
                                            <div style="width: 8h0%"><i>"{$EMAIL['email_body_plain']}"</i></div>
                                        {/if}

                                    {elseif $HISTORY->get('status') eq 7}
                                        {vtranslate('sent')}&nbsp;&nbsp;<span class="glyphicon glyphicon-arrow-right"></span>&nbsp;&nbsp;
                                        <a target="_blank" class="cursorPointer reference-display-value"
                                           class="cursorPointer"
                                                {if stripos($DETAILVIEW_URL, 'javascript:')===0} onclick='{$DETAILVIEW_URL|substr:strlen("javascript:")}'
                                                {else} onclick='window.location.href="{$DETAILVIEW_URL}"' {/if}>{$PARENT->getName()}</a>
                                        {vtranslate('to')}
                                        {if $EMAIL['data'] eq 'relation'}
                                            <a target="_blank" class="cursorPointer reference-display-value"
                                               href="{$EMAIL['url']}" data-url=""> {$EMAIL['label']}</a>
                                            <a class="quickView fa fa-eye icon action history-widget"
                                               data-module-name="{$DATA_MODULE}"
                                               style="padding-right: 5px;display: none"
                                               data-id="{$HISTORY->getParent()->get('parent_id')}" data-app="TOOLS"
                                               title="Quick View" target="_blank"></a>
                                        {else}
                                            <a target="_blank" class="cursorPointer reference-display-value"> {$EMAIL['from_email']}</a>
                                        {/if}
                                        {if $EMAIL['email_body_plain']}
                                            <div style="width: 8h0%"><i>"{$EMAIL['email_body_plain']}"</i></div>
                                        {/if}
                                    {/if}
                                    {if $TIME}
                                        <p class="pull-right muted" style="padding-right:10px;">
                                            <small style="padding-right: 5px"
                                                   title="{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}">{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}
                                                ({Vtiger_Util_Helper::formatDateDiffInStrings("$TIME")})
                                            </small>
                                        </p>
                                    {/if}
                                </div>
                            {else if $HISTORY->isCreate()}
                                <div>
                                    <b>{$USER->getName()}</b> {vtranslate('LBL_ADDED')}
                                    <a target="_blank" class="cursorPointer reference-display-value"
                                       class="cursorPointer" {if stripos($DETAILVIEW_URL, 'javascript:')===0} onclick='{$DETAILVIEW_URL|substr:strlen("javascript:")}' {else} onclick='window.location.href="{$DETAILVIEW_URL}"' {/if}>{$PARENT->getName()}</a>
                                    <a class="quickView fa fa-eye icon action history-widget"
                                       data-module-name="{$HISTORY->getParent()->getModuleName()}"
                                       style="padding-right: 5px;display: none"
                                       data-id="{$HISTORY->getParent()->get('id')}" data-app="TOOLS"
                                       title="Quick View" target="_blank"></a>
                                    {if $TIME}
                                        <p class="pull-right muted" style="padding-right:10px;">
                                            <small style="padding-right: 5px"
                                                   title="{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}">{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}
                                                ({Vtiger_Util_Helper::formatDateDiffInStrings("$TIME")})
                                            </small>
                                        </p>
                                    {/if}
                                </div>
                            {else if ($HISTORY->isRelationLink() || $HISTORY->isRelationUnLink())}
                                {assign var=RELATION value=$HISTORY->getRelationInstance()}
                                {assign var=LINKED_RECORD_DETAIL_URL value=$RELATION->getLinkedRecord()->getDetailViewUrl()}
                                {assign var=LINKED_RECORD_DETAIL_URL value=str_replace('"',"'",$LINKED_RECORD_DETAIL_URL)}
                                {assign var=PARENT_DETAIL_URL value=$RELATION->getParent()->getParent()->getDetailViewUrl()}
                                <div>
                                    <b>{$USER->getName()}</b>
                                    {if $HISTORY->isRelationLink()}
                                        {vtranslate('LBL_ADDED', $MODULE_NAME)}
                                    {else}
                                        {vtranslate('LBL_REMOVED', $MODULE_NAME)}
                                    {/if}
                                    {if $RELATION->getLinkedRecord()->getModuleName() eq 'Calendar'}
                                        {if isPermitted('Calendar', 'DetailView', $RELATION->getLinkedRecord()->getId()) eq 'yes'}
                                            <a target="_blank" class="cursorPointer reference-display-value"
                                               href="{$LINKED_RECORD_DETAIL_URL}">{$RELATION->getLinkedRecord()->getName()}</a>
                                        {else}
                                            {vtranslate($RELATION->getLinkedRecord()->getModuleName(), $RELATION->getLinkedRecord()->getModuleName())}
                                        {/if}
                                    {else if $RELATION->getLinkedRecord()->getModuleName() == 'ModComments'}
                                        <i>"{if (strlen($RELATION->getLinkedRecord()->getName())) > 50}
                                            {substr($RELATION->getLinkedRecord()->getName(),0,50)}...
                                            {else}
                                            {$RELATION->getLinkedRecord()->getName()}
                                            {/if}"</i>
                                    {else}
                                        <a target="_blank" class="cursorPointer reference-display-value"
                                           href="{$LINKED_RECORD_DETAIL_URL}">{$RELATION->getLinkedRecord()->getName()}</a>
                                    {/if}{vtranslate('LBL_FOR')} <a target="_blank"
                                                                    class="cursorPointer reference-display-value"
                                                                    href="{$PARENT_DETAIL_URL}">
                                        {$RELATION->getParent()->getParent()->getName()}</a>
                                    <a class="quickView fa fa-eye icon action history-widget"
                                       data-module-name="{$HISTORY->getParent()->getModuleName()}"
                                       style="padding-right: 5px;display: none"
                                       data-id="{$HISTORY->getParent()->get('id')}" data-app="TOOLS"
                                       title="Quick View" target="_blank"></a>
                                    {if $TIME}
                                        <p class="pull-right muted" style="padding-right:10px;">
                                            <small style="padding-right: 5px"
                                                   title="{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}">{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}
                                                ({Vtiger_Util_Helper::formatDateDiffInStrings("$TIME")})
                                            </small>
                                        </p>
                                    {/if}
                                </div>
                            {else if $HISTORY->isRestore()}
                                <div>
                                    <b>{$USER->getName()}</b> {vtranslate('LBL_RESTORED')} <a target="_blank"
                                                                                              class="cursorPointer reference-display-value"
                                                                                              href="{$DETAILVIEW_URL}">
                                        {$PARENT->getName()}</a>
                                    <a class="quickView fa fa-eye icon action history-widget"
                                       data-module-name="{$HISTORY->getParent()->getModuleName()}"
                                       style="padding-right: 5px;display: none"
                                       data-id="{$HISTORY->getParent()->get('id')}" data-app="TOOLS"
                                       title="Quick View" target="_blank"></a>
                                    {if $TIME}
                                        <p class="pull-right muted" style="padding-right:10px;">
                                            <small style="padding-right: 5px"
                                                   title="{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}">{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}
                                                ({Vtiger_Util_Helper::formatDateDiffInStrings("$TIME")})
                                            </small>
                                        </p>
                                    {/if}
                                </div>
                            {else if $HISTORY->isDelete()}
                                <div>
                                    <b>{$USER->getName()}</b> {vtranslate('LBL_DELETED')}
                                    <strong> {$PARENT->getName()}</strong>
                                    {if $TIME}
                                        <p class="pull-right muted" style="padding-right:10px;">
                                            <small style="padding-right: 5px"
                                                   title="{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}">{Vtiger_Util_Helper::formatDateTimeIntoDayString("$TIME")}
                                                ({Vtiger_Util_Helper::formatDateDiffInStrings("$TIME")})
                                            </small>
                                        </p>
                                    {/if}
                                </div>
                            {/if}
                        </div>
                    </div>
                {/if}
            {elseif $MODELNAME == 'ModComments_Record_Model'}
                {assign var=PARENT_ID value=$HISTORY->get('parentid')}
                {if $PARENT_ID != $OLD_PARENT_ID && $OLD_PARENT_ID != ''}<hr>{/if}
                <div class="row history_widget" style="text-align: left;"  data-history-id="{VReports_History_Model::getIdForComment($HISTORY)}">
                    <div class="col-lg-1 pull-left" style="margin-top:5px">
                        <span><i class="vicon-{$HISTORY->get('parentmodule')|lower}"
                                 title={$HISTORY->get('parentmodule')}></i></span>
                    </div>
                    <div class="col-lg-11 pull-left" style="margin-top:5px">
                        {assign var=COMMENT_TIME value=$HISTORY->getCommentedTime()}
                        <div>
                            <b {if $HISTORY->get('customer') neq '0'} style="color: #9c27b0" {/if}>{$HISTORY->getCommentedByName()}</b> {vtranslate('LBL_COMMENTED')} {vtranslate('LBL_ON')}
                            <a class="textOverflowEllipsis reference-display-value"
                               href="{$HISTORY->getParentRecordModel()->getDetailViewUrl()}">{$HISTORY->getParentRecordModel()->getName()}</a>
                            <a class="quickView fa fa-eye icon action history-widget"
                               data-module-name="{$HISTORY->get('parentmodule')}"
                               style="padding-right: 5px;display: none"
                               data-id="{$HISTORY->get('parentid')}" data-app="TOOLS"
                               title="Quick View" target="_blank"></a>
                            <p class="pull-right muted" style="padding-right:10px;">
                                <small style="padding-right: 5px"
                                       title="{Vtiger_Util_Helper::formatDateTimeIntoDayString("$COMMENT_TIME")}">{Vtiger_Util_Helper::formatDateTimeIntoDayString("$COMMENT_TIME")}
                                    ({Vtiger_Util_Helper::formatDateDiffInStrings("$COMMENT_TIME")})
                                </small>
                            </p>
                        </div>
                        <div style="width: 80%"><i>"{nl2br(trim($HISTORY->get('commentcontent')))}"</i></div>
                    </div>
                </div>
            {/if}
            {assign var=OLD_PARENT_ID value=$PARENT_ID}
        {/foreach}
        {if $NEXTPAGE}
            <div class="row history_widget">
                <div class="col-lg-12">
                    <center><a href="javascript:;" class="load-more" data-page="{$PAGE}"
                               data-nextpage="{$NEXTPAGE}">{vtranslate('LBL_MORE')}...</a></center>
                </div>
            </div>
        {/if}

    {else}
        <span class="noDataMsg">
			{if $HISTORY_TYPE eq 'updates'}
                {vtranslate('LBL_NO_UPDATES', $MODULE_NAME)}
            {elseif $HISTORY_TYPE eq 'comments'}
                {vtranslate('LBL_NO_COMMENTS', $MODULE_NAME)}
            {else}
                {vtranslate('LBL_NO_UPDATES_OR_COMMENTS', $MODULE_NAME)}
            {/if}
		</span>
    {/if}
</div>
