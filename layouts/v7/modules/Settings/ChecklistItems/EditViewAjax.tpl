{* *******************************************************************************
 * The content of this file is subject to the ChecklistItems ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ***************************************************************************** *}

{strip}
    <div class="modal-dialog modal-lg" style="min-width: 1260px;">
        <div class="modal-content">
            <form class="form-horizontal" id="vte-checklist-form" method="POST" action="index.php">
                <input type=hidden name="checklistid" id="checklistid" value="{$RECORD_ID}" />
                <input type="hidden" name="module" value="{$MODULE_NAME}" />
                <input type="hidden" value="Settings" name="parent" />
                <input type="hidden" name="action" value="SaveSettings" />
                <input type="hidden" id="textarea_id" value="0" />
                {if $RECORD_ID}
                    {assign var="TITLE" value= {vtranslate('LBL_EDIT_HEADER',$QUALIFIED_MODULE)}}
                {else}
                    {assign var="TITLE" value={vtranslate('LBL_NEW_HEADER',$QUALIFIED_MODULE)}}
                {/if}
                {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$TITLE}
                <div class="modal-body">
                    <div class="container-fluid" >
                        <div class="listViewContentDiv" id="listViewContents" style="height: 650px; overflow-y: auto; overflow-x: hidden; width: 1200px;">
                            <div class="marginBottom10px" >
                                <div class="fieldBlockContainer">
                                    <div class="row marginBottom10px">
                                        <div class="col-xs-4 col-sm-4 textAlignRight">{vtranslate('LBL_CHECKLIST_NAME',$QUALIFIED_MODULE)}</div>
                                        <div class="fieldValue col-xs-6 col-sm-6">
                                            <input type="text" name="checklistname" value="{$ENTITY.checklistname}" class="inputElement" />
                                        </div>
                                    </div>

                                    <div class="row marginBottom10px">
                                        <div class="col-xs-4 col-sm-4 textAlignRight">{vtranslate('LBL_MODULE_NAME',$QUALIFIED_MODULE)}</div>
                                        <div class="fieldValue col-xs-6 col-sm-6">
                                            <select name="modulename" class="select2 col-xs-6 col-sm-6">
                                                {foreach item=MODULE from=$LIST_MODULES}
                                                    <option value="{$MODULE.name}" {if $ENTITY.modulename eq $MODULE.name || $ACTIVE_MODULE eq $MODULE.name}selected{/if} >{$MODULE.tablabel}</option>
                                                {/foreach}
                                            </select>
                                        </div>
                                    </div>

                                    <div class="marginBottom10px items-list">
                                        <table width="100%" cellpadding="5" cellspacing="5" class="items-list-table">
                                            <tbody>
                                            {if $ENTITY.count_items gt 0}
                                                {foreach item=ITEM from=$ENTITY.items}
                                                    <tr class="checklist-item">
                                                        <td width="14" valign="middle"><i class="icon-move alignMiddle" title="Change ordering" data-record=""></i></td>
                                                        <td width="1172">
                                                            <table width="100%" cellpadding="5" cellspacing="5">
                                                                <tr>
                                                                    <td width="25%">
                                                                        <img title="{vtranslate('LBL_CATEGORY',$QUALIFIED_MODULE)}" data-url="layouts/v7/modules/Settings/ChecklistItems/info/category.html" class="icon-info category_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                                        <input type="hidden" name="itemid[]" value="{$ITEM.itemid}" />
                                                                        <input class="inputElement" type="text" name="category[]" value="{$ITEM.category}" placeholder="{vtranslate('LBL_CATEGORY',$QUALIFIED_MODULE)}" />
                                                                    </td>
                                                                    <td width="25%">
                                                                        <img title="{vtranslate('LBL_TITLE',$QUALIFIED_MODULE)}" data-url="layouts/v7/modules/Settings/ChecklistItems/info/title.html" class="icon-info title_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                                        <input class="inputElement" type="text" name="title[]" value="{$ITEM.title}" placeholder="{vtranslate('LBL_TITLE',$QUALIFIED_MODULE)}" />
                                                                    </td>
                                                                    <td width="25%">
                                                                        <img title="{vtranslate('LBL_ALLOW_UPLOAD',$QUALIFIED_MODULE)}" data-url="layouts/v7/modules/Settings/ChecklistItems/info/allow_upload.html" class="icon-info allow_upload_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                                        <label>{vtranslate('LBL_ALLOW_UPLOAD',$QUALIFIED_MODULE)}</label>
                                                                        <input type="checkbox" class="allow_upload" value="1" {if $ITEM.allow_upload eq 1}checked{/if} />
                                                                        <input type="hidden" name="allow_upload[]" class="allow_upload_value" value="{$ITEM.allow_upload}"  />
                                                                    </td>
                                                                    <td width="25%">
                                                                        <img title="{vtranslate('LBL_ALLOW_NOTE',$QUALIFIED_MODULE)}" data-url="layouts/v7/modules/Settings/ChecklistItems/info/allow_note.html" class="icon-info allow_note_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                                        <label>{vtranslate('LBL_ALLOW_NOTE',$QUALIFIED_MODULE)}</label>
                                                                        <input type="checkbox" class="allow_note" value="1" {if $ITEM.allow_note eq 1}checked{/if} />
                                                                        <input type="hidden" name="allow_note[]" class="allow_note_value" value="{$ITEM.allow_note}" />
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td colspan="4">
                                                                        <textarea name="description[]" class="description" placeholder="{vtranslate('LBL_DESCRIPTION',$QUALIFIED_MODULE)}" style="width: 90%; height: 75px;">{$ITEM.description}</textarea>
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td width="14" valign="middle">
                                                            <a data-itemid="{$ITEM.itemid}" data-url="" class="deleteButton" href="javascript: void(0);">
                                                                <i class="fa fa-trash alignMiddle" title="Delete"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                {/foreach}
                                            {else}
                                                <tr class="checklist-item">
                                                    <td width="14" valign="middle"><i class="icon-move alignMiddle" title="Change ordering" data-record=""></i></td>
                                                    <td width="1172">
                                                        <table width="100%" cellpadding="5" cellspacing="5">
                                                            <tr>
                                                                <td width="25%">
                                                                    <img title="{vtranslate('LBL_CATEGORY',$QUALIFIED_MODULE)}" data-url="layouts/v7/modules/Settings/ChecklistItems/info/category.html" class="icon-info category_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                                    <input class="inputElement" type="text" name="category[]" value="" placeholder="{vtranslate('LBL_CATEGORY',$QUALIFIED_MODULE)}" />
                                                                </td>
                                                                <td width="25%">
                                                                    <img title="{vtranslate('LBL_TITLE',$QUALIFIED_MODULE)}" data-url="layouts/v7/modules/Settings/ChecklistItems/info/title.html" class="icon-info title_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                                    <input class="inputElement" type="text" name="title[]" value="" placeholder="{vtranslate('LBL_TITLE',$QUALIFIED_MODULE)}" />
                                                                </td>
                                                                <td width="25%">
                                                                    <img title="{vtranslate('LBL_ALLOW_UPLOAD',$QUALIFIED_MODULE)}" data-url="layouts/v7/modules/Settings/ChecklistItems/info/allow_upload.html" class="icon-info allow_upload_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                                    <label>{vtranslate('LBL_ALLOW_UPLOAD',$QUALIFIED_MODULE)}</label>
                                                                    <input type="checkbox" class="allow_upload" value="1" checked />
                                                                    <input type="hidden" name="allow_upload[]" class="allow_upload_value" value="1"  />
                                                                </td>
                                                                <td width="25%">
                                                                    <img title="{vtranslate('LBL_ALLOW_NOTE',$QUALIFIED_MODULE)}" data-url="layouts/v7/modules/Settings/ChecklistItems/info/allow_note.html" class="icon-info allow_note_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                                    <label>{vtranslate('LBL_ALLOW_NOTE',$QUALIFIED_MODULE)}</label>
                                                                    <input type="checkbox" class="allow_note" value="1"  checked />
                                                                    <input type="hidden" name="allow_note[]" class="allow_note_value" value="1"  />
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="4">
                                                                    <textarea name="description[]" class="description" placeholder="{vtranslate('LBL_DESCRIPTION',$QUALIFIED_MODULE)}" style="width: 90%; height: 75px;"></textarea>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                    <td width="14" valign="middle">
                                                        <a data-url="" class="deleteButton" href="javascript: void(0);">
                                                            <i class="fa fa-trash alignMiddle" title="Delete"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            {/if}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="filterActions" style="padding: 10px 0;">
                            <button class="btn addButton btn-default pull-left marginRight10px" id="add-checklist-item" type="button"><i class="fa fa-plus"></i>&nbsp;<strong>{vtranslate('ADD_CHECKLIST_ITEM', $QUALIFIED_MODULE)}</strong></button>
                            <button class="btn btn-success pull-right" id="save-checklist" type="button"><strong>{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</strong></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
{/strip}