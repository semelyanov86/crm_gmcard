{* ********************************************************************************
 * The content of this file is subject to the ChecklistItems ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** *}
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
