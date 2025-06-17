{* *******************************************************************************
 * The content of this file is subject to the ChecklistItems ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ***************************************************************************** *}

<div class="container-fluid" id="vte-primary-box">
    <form class="form-inline" id="CustomView" name="CustomView" method="post" action="index.php">
        <input type=hidden name="checklistid" id="checklistid" value="{$RECORD_ID}" />
        <input type="hidden" name="module" value="{$MODULE_NAME}" />
        <input type="hidden" value="Settings" name="parent" />
        <input type="hidden" name="action" value="SaveSettings" />
        <input type="hidden" id="textarea_id" value="0" />

        <div class="row-fluid"  style="padding: 10px 0;">
            <h3 class="textAlignCenter">
                {if $RECORD_ID gt 0}
                    {vtranslate('LBL_EDIT_HEADER',$QUALIFIED_MODULE)}
                {else}
                    {vtranslate('LBL_NEW_HEADER',$QUALIFIED_MODULE)}
                {/if}
                <small aria-hidden="true" data-dismiss="modal" class="pull-right ui-checklist-closer" style="cursor: pointer;" title="{vtranslate('LBL_MODAL_CLOSE',$QUALIFIED_MODULE)}">x</small>
            </h3>
        </div>
        <hr>
        <div class="clearfix"></div>

        <div class="listViewContentDiv row-fluid" id="listViewContents" style="height: 450px; overflow-y: auto; width: 1200px;">
            <div class="marginBottom10px" >
                <div class="row-fluid">
                    <div class="row marginBottom10px">
                        <div class="span4 textAlignRight">{vtranslate('LBL_CHECKLIST_NAME',$QUALIFIED_MODULE)}</div>
                        <div class="fieldValue span6">
                            <input type="text" name="checklistname" value="{$ENTITY.checklistname}" class="input-large" />
                        </div>
                    </div>

                    <div class="row marginBottom10px">
                        <div class="span4 textAlignRight">{vtranslate('LBL_MODULE_NAME',$QUALIFIED_MODULE)}</div>
                        <div class="fieldValue span6">
                            <select name="modulename" class="chzn-select">
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
                                                    <img title="{vtranslate('LBL_CATEGORY',$QUALIFIED_MODULE)}" data-url="layouts/vlayout/modules/Settings/ChecklistItems/info/category.html" class="icon-info category_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                    <input type="text" name="category[]" value="{$ITEM.category}" placeholder="{vtranslate('LBL_CATEGORY',$QUALIFIED_MODULE)}" />
                                                </td>
                                                <td width="25%">
                                                    <img title="{vtranslate('LBL_TITLE',$QUALIFIED_MODULE)}" data-url="layouts/vlayout/modules/Settings/ChecklistItems/info/title.html" class="icon-info title_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                    <input type="text" name="title[]" value="{$ITEM.title}" placeholder="{vtranslate('LBL_TITLE',$QUALIFIED_MODULE)}" />
                                                </td>
                                                <td width="25%">
                                                    <img title="{vtranslate('LBL_ALLOW_UPLOAD',$QUALIFIED_MODULE)}" data-url="layouts/vlayout/modules/Settings/ChecklistItems/info/allow_upload.html" class="icon-info allow_upload_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                    <label>{vtranslate('LBL_ALLOW_UPLOAD',$QUALIFIED_MODULE)}</label>
                                                    <input type="checkbox" class="allow_upload" value="1" {if $ITEM.allow_upload eq 1}checked{/if} />
                                                    <input type="hidden" name="allow_upload[]" class="allow_upload_value" value="{$ITEM.allow_upload}"  />
                                                </td>
                                                <td width="25%">
                                                    <img title="{vtranslate('LBL_ALLOW_NOTE',$QUALIFIED_MODULE)}" data-url="layouts/vlayout/modules/Settings/ChecklistItems/info/allow_note.html" class="icon-info allow_note_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                    <label>{vtranslate('LBL_ALLOW_NOTE',$QUALIFIED_MODULE)}</label>
                                                    <input type="checkbox" class="allow_note" value="1" {if $ITEM.allow_note eq 1}checked{/if} />
                                                    <input type="hidden" name="allow_note[]" class="allow_note_value" value="{$ITEM.allow_note}" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="4">
                                                    <textarea name="description[]" class="description" placeholder="{vtranslate('LBL_DESCRIPTION',$QUALIFIED_MODULE)}" style="width: 800px; height: 75px;">{$ITEM.description}</textarea>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="14" valign="middle">
                                        <a data-url="" class="deleteButton" href="javascript: void(0);">
                                            <i class="icon-trash alignMiddle" title="Delete"></i>
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
                                                    <img title="{vtranslate('LBL_CATEGORY',$QUALIFIED_MODULE)}" data-url="layouts/vlayout/modules/Settings/ChecklistItems/info/category.html" class="icon-info category_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                    <input type="text" name="category[]" value="" placeholder="{vtranslate('LBL_CATEGORY',$QUALIFIED_MODULE)}" />
                                                </td>
                                                <td width="25%">
                                                    <img title="{vtranslate('LBL_TITLE',$QUALIFIED_MODULE)}" data-url="layouts/vlayout/modules/Settings/ChecklistItems/info/title.html" class="icon-info title_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                    <input type="text" name="title[]" value="" placeholder="{vtranslate('LBL_TITLE',$QUALIFIED_MODULE)}" />
                                                </td>
                                                <td width="25%">
                                                    <img title="{vtranslate('LBL_ALLOW_UPLOAD',$QUALIFIED_MODULE)}" data-url="layouts/vlayout/modules/Settings/ChecklistItems/info/allow_upload.html" class="icon-info allow_upload_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                    <label>{vtranslate('LBL_ALLOW_UPLOAD',$QUALIFIED_MODULE)}</label>
                                                    <input type="checkbox" class="allow_upload" value="1" checked />
                                                    <input type="hidden" name="allow_upload[]" class="allow_upload_value" value="1"  />
                                                </td>
                                                <td width="25%">
                                                    <img title="{vtranslate('LBL_ALLOW_NOTE',$QUALIFIED_MODULE)}" data-url="layouts/vlayout/modules/Settings/ChecklistItems/info/allow_note.html" class="icon-info allow_note_info" src="layouts/vlayout/modules/Settings/ChecklistItems/resources/info.png" width="16" height="16" />
                                                    <label>{vtranslate('LBL_ALLOW_NOTE',$QUALIFIED_MODULE)}</label>
                                                    <input type="checkbox" class="allow_note" value="1"  checked />
                                                    <input type="hidden" name="allow_note[]" class="allow_note_value" value="1"  />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="4">
                                                    <textarea name="description[]" class="description" placeholder="{vtranslate('LBL_DESCRIPTION',$QUALIFIED_MODULE)}" style="width: 800px; height: 75px;"></textarea>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="14" valign="middle">
                                        <a data-url="" class="deleteButton" href="javascript: void(0);">
                                            <i class="icon-trash alignMiddle" title="Delete"></i>
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
            <button class="btn addButton btn-add pull-left marginRight10px" id="add-checklist-item" type="button"><i class="icon-plus"></i>&nbsp;<strong>{vtranslate('ADD_CHECKLIST_ITEM', $QUALIFIED_MODULE)}</strong></button>
            <button class="btn btn-success pull-right" id="save-checklist" type="button"><strong>{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</strong></button>
        </div>
    </form>
</div>

