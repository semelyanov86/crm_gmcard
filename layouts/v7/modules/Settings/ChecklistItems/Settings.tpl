{* ********************************************************************************
 * The content of this file is subject to the ChecklistItems ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** *}
 
<div class="col-sm-12 col-xs-12" id="checklist_settings">
    <div class="contentHeader row">
        <h4 class="col-xs-12 col-sm-12 textOverflowEllipsis">
            <a href="index.php?module=ModuleManager&parent=Settings&view=List">&nbsp;{vtranslate('MODULE_MANAGEMENT',$QUALIFIED_MODULE)}</a>&nbsp;>&nbsp;{vtranslate('LBL_SETTING_HEADER', $QUALIFIED_MODULE)}
        </h4>
    </div>
    <hr>
    <div class="clearfix"></div>

    <div class="listViewContentDiv row" id="listViewContents">
        <div class="marginBottom10px  col-xs-12 col-sm-12">
            <div class="row">
                <span class="col-xs-6 col-sm-6">
                    <button type="button" data-url="index.php?module=ChecklistItems&view=EditViewAjax&parent=Settings" class="btn addButton editButton btn-default">
                        <i class="fa fa-plus"></i>&nbsp;
                        <strong>{vtranslate('LBL_ADD_MORE_BTN', $QUALIFIED_MODULE)}</strong>
                    </button>
                </span>
                {if $CURRENT_USER->isAdminUser()}
                    <span class="col-xs-6 col-sm-6">
                        <input class="pull-right" type="checkbox" id="none_user_permission" {if $USER_PERMISSION eq 1}checked="true"{/if} value="1" style="display: inline; margin-left: 5px;"/>
                        <label class="pull-right" for="none_user_permission" style="display: inline;">{vtranslate('LBL_ALLOW_NONE_USER_CREATE', $QUALIFIED_MODULE)}</label>
                    </span>
                {/if}
            </div>
        </div>
        <div class="marginBottom10px col-xs-12 col-sm-12">
            <div class="table-container">
                <table class="table listview-table  vte-checklist-items">
                    <thead>
                        <tr class="size-row">
                            <th class="floatThead-col"></th>
                            <th class="floatThead-col">{vtranslate('LBL_CHECKLIST_NAME_HEADER', $QUALIFIED_MODULE)}</th>
                            <th class="floatThead-col">{vtranslate('LBL_MODULE_NAME_HEADER', $QUALIFIED_MODULE)}</th>
                            <th class="floatThead-col">{vtranslate('LBL_CREATED_DATE_HEADER', $QUALIFIED_MODULE)}</th>
                            <th class="floatThead-col" colspan="2">{vtranslate('LBL_STATUS_HEADER', $QUALIFIED_MODULE)}</th>
                        </tr>
                    </thead>
                    <tbody class="overflow-y">
                        {if $COUNT_ENTITY gt 0}
                            {foreach item=ENTITY from=$ENTITIES}
                                <tr class="listViewEntries">
                                    <td class="listViewEntryValue" width="5%">
                                        <i class="fa fa-arrows change-ordering alignMiddle" title="{vtranslate('LBL_MOVE_BTN', $QUALIFIED_MODULE)}" data-record="{$ENTITY.checklistid}"></i>
                                    </td>
                                    <td class="listViewEntryValue" width="45%">
                                        <span class="fieldValue">
                                            <span class="value">
                                                <a class="editButton" href="javascript:void(0)" data-url="index.php?module=ChecklistItems&view=EditViewAjax&parent=Settings&record={$ENTITY.checklistid}">
                                                    {$ENTITY.checklistname}
                                                </a>
                                            </span>
                                        </span>
                                    </td>
                                    <td class="listViewEntryValue" width="15%">
                                        <span class="fieldValue">
                                            <span class="value">
                                                {vtranslate($ENTITY.modulename, $ENTITY.modulename)}
                                            </span>
                                        </span>
                                    </td>
                                    <td class="listViewEntryValue" width="20%">
                                        <span class="fieldValue">
                                            <span class="value">
                                                {$ENTITY.createddate}
                                            </span>
                                        </span>
                                    </td>
                                    <td class="listViewEntryValue" width="10%">
                                        <span class="fieldValue">
                                            <span class="value">
                                                 <a href="javascript:void(0);" class="checklist_status" data-status="{$ENTITY.status}" data-record="{$ENTITY.checklistid}" title="{if $ENTITY.status eq 'Active'}{vtranslate('LBL_INACTIVE_BTN', $QUALIFIED_MODULE)}{else}{vtranslate('LBL_ACTIVE_BTN', $QUALIFIED_MODULE)}{/if}">
                                                    {$ENTITY.status}
                                                </a>
                                            </span>
                                        </span>
                                    </td>
                                    <td class="listViewEntryValue" width="5%">
                                        <div class="actions pull-right">
                                            <span class="actionImages">
                                                <a data-url="index.php?module=ChecklistItems&view=EditViewAjax&parent=Settings&record={$ENTITY.checklistid}&modulename={$ENTITY.modulename}" class="editButton" href="javascript: void(0);">
                                                    <i class="fa fa-pencil alignMiddle" title="{vtranslate('LBL_EDIT_BTN', $QUALIFIED_MODULE)}"></i>
                                                </a>
                                                <a data-url="index.php?module=ChecklistItems&action=DeleteAjax&parent=Settings&record={$ENTITY.checklistid}&modulename={$ENTITY.modulename}" class="deleteButton" href="javascript: void(0);">
                                                    <i class="fa fa-trash alignMiddle" title="{vtranslate('LBL_DELETE_BTN', $QUALIFIED_MODULE)}"></i>
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
</div>

