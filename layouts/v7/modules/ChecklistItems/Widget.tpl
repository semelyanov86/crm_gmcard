{* ********************************************************************************
 * The content of this file is subject to the ChecklistItems ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** *}
{strip}
    <div class="modal-dialog">
        <div class="modal-content">
            {include file="ModalHeader.tpl"|vtemplate_path:$SOURCE_MODULE TITLE=vtranslate('Checklists', $SOURCE_MODULE)}
            <div class="modal-body">
                <div class="container-fluid" id="vte-checklist">
                    <ul class="nav nav-list">
                        {foreach item=CHECKLIST from=$CHECKLISTS}
                            {assign var=CHECKLIST_DETAIL value=$MODULE_MODEL->getChecklistDetails($SOURCE_RECORD, $CHECKLIST.checklistid)}
                            <li>
                                <a href="javascript:void(0);" class="checklist-name pull-left" data-record="{$CHECKLIST.checklistid}" style="width: 70%;">
                                    {$CHECKLIST.checklistname}
                                </a>
                                <a href="javascript:void(0);" class="pull-right"><i>{$CHECKLIST_DETAIL.status_date_display}</i></a>
                            </li>
                        {/foreach}
                    </ul>
                </div>
            </div>
        </div>
    </div>
{/strip}
