{* ********************************************************************************
 * The content of this file is subject to the ChecklistItems ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** *}

{strip}
    <link href="layouts/v7/modules/ChecklistItems/resources/ChecklistItems.css" type="text/css" rel="stylesheet"/>
    <script src="layouts/v7/modules/ChecklistItems/resources/jquery.form.js" type="text/javascript" ></script>

    <div class="modal-dialog" style="width: 1050px;">
        <div class="modal-content">
            <div class="modal-body">
                <div class="container-fluid" id="vte-checklist-details">
                    <input type="hidden" name="curr_date" value="{$CURR_DATE}" />
                    <input type="hidden" name="curr_time" value="{$CURR_TIME}" />
                    <span class="ui-helper-hidden-accessible"><input type="text"/></span>
                    {if $COUNT_ITEM gt 0}
                        {foreach item=ITEMS key=CATEGORYNAME from=$CHECKLIST_ITEMS}
                            <div class="checklist-name"><h3><a href="javascript:void(0);">{$CATEGORYNAME}</a></h3></div>
                            {foreach item=ITEM from=$ITEMS}
                                <div class="checklist-item" data-record="{$ITEM.checklistitemsid}" id="checklist-item{$ITEM.checklistitemsid}">
                                    <table width="100%">
                                        <tr>
                                            <td width="3%" valign="top">
                                                <span data-status="{$ITEM.checklistitem_status}" class="checklist-item-status-btn checklist-item-status-icon{$ITEM.checklistitem_status}">&nbsp;</span>
                                            </td>
                                            <td width="97%" valign="top">
                                                <div class="checklist-item-header">
                                                    <div class="pull-left checklist-item-title">
                                                        <a href="javascript:void(0);">{$ITEM.title}</a>
                                                    </div>
                                                    <div class="pull-right checklist-item-date">
                                                        <div class="input-append time">
                                                            <input type="text" placeholder="{vtranslate('INPUT_TIME', 'ChecklistItems')}" name="checklist_item_time" data-format="{$CURR_USER_MODEL->get('hour_format')}" class="timepicker-default input-small ui-timepicker-input" value="{$ITEM.status_time_display}" autocomplete="off">
                                                            {*<span class="add-on cursorPointer"><i class="icon-time"></i></span>*}
                                                        </div>
                                                        <div class="date">
                                                            <input type="text" placeholder="{vtranslate('INPUT_DATE', 'ChecklistItems')}" class="dateField input-small" name="checklist_item_date" data-date-format="{$CURR_USER_MODEL->get('date_format')}" value="{$ITEM.status_date_display}" >
                                                            {*<span class="add-on"><i class="icon-calendar"></i></span>*}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="checklist-item-content">
                                                    <div class="checklist-item-desc">
                                                        {$ITEM.description}
                                                    </div>
                                                    {if $ITEM.allow_upload eq 1 || $ITEM.allow_note eq 1}
                                                        <div class="checklist-item-related">
                                                            {if $ITEM.allow_upload eq 1}
                                                                <div id="document-related{$ITEM.checklistitemsid}" class="document-related">
                                                                    <form action="index.php?module=Documents&action=SaveAjax" method="post" class="checklist-upload-form" enctype="multipart/form-data">
                                                                        <input type="hidden" name="module" value="Documents">
                                                                        <input type="hidden" name="action" value="SaveAjax">
                                                                        <input type="hidden" name="sourceModule" value="ChecklistItems">
                                                                        <input type="hidden" name="sourceRecord" value="{$ITEM.checklistitemsid}">
                                                                        <input type="hidden" name="relationOperation" value="true">
                                                                        <input type="hidden" name="notes_title" value="">
                                                                        <input type="hidden" name="filelocationtype" value="I">
                                                                        <input type="file" name="filename" class="add-document"/>
                                                                        <a class="upload-file" href="javascript:void(0);">
                                                                            {vtranslate('UPLOAD_FILE', 'ChecklistItems')}
                                                                        </a>
                                                                        <div class="progress">
                                                                            <div class="progress-bar" role="progressbar" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" style="width:0%">
                                                                                <span class="sr-only">0%</span>
                                                                            </div>
                                                                        </div>

                                                                        <div class="checklist-item-documents">
                                                                            <ul class="nav nav-tabs nav-stacked">
                                                                                {if $ITEM.count_document gt 0}
                                                                                    {foreach item=DOCUMENT from=$ITEM.documents}
                                                                                        <li class="">
                                                                                            <div style="height: 100%; width: 100%;margin: 4px; padding: 9px 10px; border: 1px solid #ddd; border-radius: 4px 4px 0 0;">
                                                                                                <a href="index.php?module=Documents&action=DownloadFile&record={$DOCUMENT.crmid}&fileid={$DOCUMENT.attachmentsid}" style="border: none;">{$DOCUMENT.title}</a>
                                                                                                <span class="relationDelete pull-right" data-record="{$ITEM.checklistitemsid}" data-related-record="{$DOCUMENT.crmid}" style="cursor: pointer;"><i title="Delete" class="fa fa-trash alignMiddle"></i></span>
                                                                                            </div>
                                                                                        </li>
                                                                                    {/foreach}
                                                                                {/if}
                                                                            </ul>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            {/if}
                                                            {if $ITEM.allow_note eq 1}
                                                                <div id="comment-related{$ITEM.checklistitemsid}" class="comment-related">
                                                                    <div class="item-note-box">
                                                                        <a class="add-note" href="javascript:void(0);">
                                                                            {vtranslate('ADD_NOTE', 'ChecklistItems')}
                                                                        </a>
                                                                        <a class="show-all-notes pull-right" href="javascript:void(0);">
                                                                            {vtranslate('SHOW_ALL_NOTES', 'ChecklistItems')}
                                                                        </a>
                                                                        <div class="item-note-add">
                                                                            <textarea class="item-note-content" placeholder="{vtranslate('LBL_ADD_YOUR_COMMENT_HERE')}"></textarea>
                                                                            <button class="btn btn-success add-comment" type="button" name="submit{$ITEM.checklistitemsid}" data-record="{$ITEM.checklistitemsid}"><strong>{vtranslate('ADD_NOTE_BTN', 'ChecklistItems')}</strong></button>
                                                                        </div>
                                                                        <div class="item-note-list">
                                                                            <ul class="commentContainer">
                                                                                {if $ITEM.count_comment gt 0}
                                                                                    {foreach item=COMMENT from=$ITEM.comments}
                                                                                        <li class="commentDetails">
                                                                                            <p>{$COMMENT.commentcontent}</p>
                                                                                            <p><small>{$COMMENT.displayUserName} | {$COMMENT.displayDateTime}</small></p>
                                                                                        </li>
                                                                                    {/foreach}
                                                                                {/if}
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            {/if}
                                                        </div>
                                                    {/if}
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            {/foreach}
                        {/foreach}
                    {/if}
                </div>
            </div>
        </div>
    </div>
{/strip}