{* ********************************************************************************
 * The content of this file is subject to the ChecklistItems ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** *}
 
<div class="container-fluid">
    <div class="contentHeader row-fluid">
        <h3 class="span8 textOverflowEllipsis">
            <a href="index.php?module=ModuleManager&parent=Settings&view=List">&nbsp;{vtranslate('MODULE_MANAGEMENT',$QUALIFIED_MODULE)}</a>&nbsp;>&nbsp;{vtranslate('LBL_SETTING_HEADER', $QUALIFIED_MODULE)}
        </h3>
    </div>
    <hr>
    <div class="clearfix"></div>

    <div class="listViewContentDiv row-fluid" id="listViewContents">
        <div class="marginBottom10px">
            <span class="row btn-toolbar">
                <button type="button" data-url="index.php?module=ChecklistItems&view=EditViewAjax&parent=Settings" class="btn addButton editButton">
                    <i class="icon-plus"></i>&nbsp;
                    <strong>{vtranslate('LBL_ADD_MORE_BTN', $QUALIFIED_MODULE)}</strong>
                </button>
                {if $CURRENT_USER->isAdminUser()}
                <span class="pull-right">
                    <label for="none_user_permission" style="display: inline;">{vtranslate('LBL_ALLOW_NONE_USER_CREATE', $QUALIFIED_MODULE)}</label>
                    <input type="checkbox" id="none_user_permission" {if $USER_PERMISSION eq 1}checked="true"{/if} value="1" style="display: inline; margin-left: 5px;"/>
                </span>
                {/if}
            </span>
        </div>
        <div class="marginBottom10px">
            <table class="table table-bordered listViewEntriesTable vte-checklist-items">
                <thead>
                    <tr class="listViewHeaders">
                        <th class="medium"></th>
                        <th class="medium">{vtranslate('LBL_CHECKLIST_NAME_HEADER', $QUALIFIED_MODULE)}</th>
                        <th class="medium">{vtranslate('LBL_MODULE_NAME_HEADER', $QUALIFIED_MODULE)}</th>
                        <th class="medium">{vtranslate('LBL_CREATED_DATE_HEADER', $QUALIFIED_MODULE)}</th>
                        <th class="medium" colspan="2">{vtranslate('LBL_STATUS_HEADER', $QUALIFIED_MODULE)}</th>
                    </tr>
                </thead>
                <tbody>
                    {if $COUNT_ENTITY gt 0}
                        {foreach item=ENTITY from=$ENTITIES}
                            <tr>
                                <td class="listViewEntryValue" width="5%">
                                    <i class="icon-move alignMiddle" title="{vtranslate('LBL_MOVE_BTN', $QUALIFIED_MODULE)}" data-record="{$ENTITY.checklistid}"></i>
                                </td>
                                <td class="listViewEntryValue" width="45%">
                                    <a class="editButton" href="javascript:void(0)" data-url="index.php?module=ChecklistItems&view=EditViewAjax&parent=Settings&record={$ENTITY.checklistid}">
                                    {$ENTITY.checklistname}
                                    </a>
                                </td>
                                <td class="listViewEntryValue" width="15%">{vtranslate($ENTITY.modulename, $ENTITY.modulename)}</td>
                                <td class="listViewEntryValue" width="20%">{$ENTITY.createddate}</td>
                                <td class="listViewEntryValue" width="10%">
                                    <a href="javascript:void(0);" class="checklist_status" data-status="{$ENTITY.status}" data-record="{$ENTITY.checklistid}" title="{if $ENTITY.status eq 'Active'}{vtranslate('LBL_INACTIVE_BTN', $QUALIFIED_MODULE)}{else}{vtranslate('LBL_ACTIVE_BTN', $QUALIFIED_MODULE)}{/if}">
                                        {$ENTITY.status}
                                    </a>
                                </td>
                                <td class="listViewEntryValue" width="5%">
                                    <div class="actions pull-right">
                                        <span class="actionImages">
                                            <a data-url="index.php?module=ChecklistItems&view=EditViewAjax&parent=Settings&record={$ENTITY.checklistid}&modulename={$ENTITY.modulename}" class="editButton" href="javascript: void(0);">
                                                <i class="icon-pencil alignMiddle" title="{vtranslate('LBL_EDIT_BTN', $QUALIFIED_MODULE)}"></i>
                                            </a>
                                            <a data-url="index.php?module=ChecklistItems&action=DeleteAjax&parent=Settings&record={$ENTITY.checklistid}&modulename={$ENTITY.modulename}" class="deleteButton" href="javascript: void(0);">
                                                <i class="icon-trash alignMiddle" title="{vtranslate('LBL_DELETE_BTN', $QUALIFIED_MODULE)}"></i>
                                            </a>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                    {/if}
                </tbody>
            </table>
        </div>
    </div>
</div>

