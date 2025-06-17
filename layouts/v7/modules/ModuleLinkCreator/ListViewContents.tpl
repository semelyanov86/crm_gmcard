{*<!--
/* ********************************************************************************
 * The content of this file is subject to the Module & Link Creator ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}
{strip}
    <div class="col-sm-12 col-xs-12 ">

        <div class="row" style="margin-bottom: 20px">
            <h3 style="text-align: center;margin-top: 0; margin-bottom: 20px">{vtranslate('Welcome To Module & Link Creator', $MODULE)}</h3>
            <div class="listViewActionsDiv row">
                <table style="margin: 0 auto;">
                    <tr>
                        <td>
                            <a id="Contacts_listView_basicAction_LBL_ADD_RECORD" target="_blank" href="index.php?module=ModuleLinkCreator&view=Edit" class="btn btn-default btn-warning">Custom Module</a>
                        </td>

                        {*<td>*}
                            {*<a  href="index.php?module={$MODULE}&parent=Settings&view=RelationshipOneOne" target="_blank" class="btn btn-default btn-warning">1:1 Relationship</a>*}
                        {*</td>*}
                        <td>
                            <a href="index.php?module=ModuleLinkCreator&parent=Settings&view=IndexRelatedFields" target="_blank" class="btn btn-default btn-warning">1:M Relationship</a>
                        </td>
                        <td>
                            <a href="index.php?module=ModuleLinkCreator&parent=Settings&view=RelationshipMM" target="_blank" class="btn btn-default btn-warning">M:M Relationship</a>
                        </td>
                        <td>
                            <a href="index.php?module=ModuleLinkCreator&parent=Settings&view=RelationshipOneNone" target="_blank" class="btn btn-default btn-warning">One Way Relationship</a>
                        </td>

                    </tr>
                </table>
            </div>
        </div>

        <div class="listViewContentDiv" id="listViewContents" style="position: relative; clear:both;">
            <div class="listViewEntriesDiv contents-bottomscroll">
                <div class="bottomscroll-div">
                    {assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
                    <table id="module-link-creator-list-table" class="table table-bordered listview-table" style="border-top: 1px solid #ddd">
                        <thead>
                        <tr class="listViewContentHeader">
                            {foreach item=LISTVIEW_HEADER key=COLUMNNAME from=$LISTVIEW_HEADERS}
                                <th nowrap {if $LISTVIEW_HEADER@last} colspan="2" {/if} style="text-align: right;">
                                    {if $COLUMNNAME != 'description'}
                                            {vtranslate($LISTVIEW_HEADER, $MODULE)}
                                    {else}
                                        {vtranslate('Icon', $MODULE)}
                                    {/if}
                                </th>
                            {/foreach}
                        </tr>
                        </thead>
                        <tbody class="overflow-y">
                        {foreach item=LISTVIEW_ENTRY from=$RECORDS name=listview}
                            <tr class="listViewEntries" data-id='{$LISTVIEW_ENTRY->get('id')}'
                                id="{$MODULE}_listView_row_{$smarty.foreach.listview.index+1}">

                                {foreach item=LISTVIEW_HEADER key=COLUMNNAME from=$LISTVIEW_HEADERS}
                                    {if $COLUMNNAME != 'description'}
                                        <td class="{if $COLUMNNAME == 'filename'}listViewEntryValue{/if} {$WIDTHTYPE}" nowrap data-column="{$COLUMNNAME}">
                                            {if $COLUMNNAME == 'filename'}
                                                <a href='index.php?module=ModuleLinkCreator&view=Edit&record={$LISTVIEW_ENTRY->get('id')}'>
                                                    {vtranslate($LISTVIEW_ENTRY->get($COLUMNNAME), $LISTVIEW_ENTRY->get($COLUMNNAME))}
                                                </a>
                                            {elseif $COLUMNNAME == 'module'}
                                                {vtranslate($LISTVIEW_ENTRY->get($COLUMNNAME), $LISTVIEW_ENTRY->get($COLUMNNAME))}
                                            {else}
                                                {$LISTVIEW_ENTRY->get($COLUMNNAME)}
                                            {/if}
                                        </td>
                                    {/if}
                                        {if $LISTVIEW_HEADER@last}
                                            <td nowrap class="{$WIDTHTYPE}">
                                                <div class="actions pull-right">
                                                <span class="actionImages">
                                                    {*<a class="downloadRecordButton" href="index.php?module=ModuleManager&parent=Settings&action=ModuleExport&mode=exportModule&forModule={$LISTVIEW_ENTRY->get('module_name')}">
                                                        <i title="{vtranslate('LBL_DOWNLOAD', $MODULE)}" class="fa fa-download alignMiddle"></i>
                                                    </a>*}
                                                    &nbsp;&nbsp;
                                                    {*<a href='index .php?module=ModuleLinkCreator&view=Edit&record={$LISTVIEW_ENTRY->get('id')}'>*}
                                                    {*<i title="{vtranslate('LBL_EDIT', $MODULE)}" class="icon-pencil alignMiddle"></i>*}
                                                    {*</a>*}
                                                    {*&nbsp;*}
                                                    {*<a data-link ="index.php?module=ModuleLinkCreator&action=ActionAjax&mode=delete&record={$LISTVIEW_ENTRY->get('id')}" class="deleteRecordModuleLinkCreator">
                                                        <i title="{vtranslate('LBL_DELETE', $MODULE)}" class="fa fa-trash alignMiddle"></i>
                                                    </a>*}
                                                    <i class="vicon-{strtolower($LISTVIEW_ENTRY->get('module_name'))}"></i>&nbsp;<button type="button" data-module="{$LISTVIEW_ENTRY->get('module_name')}" class="btn btn-default btn-update-icon" data-toggle="modal" data-target="#ModalIcons">Update Icon</button>
                                                </span>
                                                </div>
                                            </td>
                                        {/if}
                                {/foreach}
                            </tr>
                        {/foreach}
                        {if $LISTVIEW_ENTRIES_COUNT eq '0'}
                            <tr class="emptyRecordsDiv">
                                <td colspan="5">
                                    <div class="emptyRecordsContent">
                                        {assign var=SINGLE_MODULE value="SINGLE_$MODULE"}
                                        {vtranslate('LBL_NO')} {vtranslate($MODULE, $MODULE)} {vtranslate('LBL_FOUND')}
                                        .{if $IS_MODULE_EDITABLE} {vtranslate('LBL_CREATE')} <a
                                                href="index.php?module=ModuleLinkCreator&view=Edit">{vtranslate($SINGLE_MODULE, $MODULE)}</a>{/if}
                                    </div>
                                </td>
                            </tr>
                        {/if}
                        </tbody>
                    </table>
                    <!-- Modal -->
                    <div class="modal fade" id="ModalIcons" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle"
                         aria-hidden="true">
                        <div class="modal-dialog" role="document" style="width: 680px">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    <h4 class="modal-title">Modal Header</h4>
                                </div>
                                <div class="modal-body" style="max-height: 500px; overflow: scroll; overflow-x: hidden;">
                                    <div class="form">
                                        <input value="" type="hidden" id="selected_module"/>
                                        {assign var=LISTICONS_LENGTH value=(count($LISTICONS) -1)}
                                        {assign var=INDEX value = 0 }
                                        <table data-length="{$LISTICONS_LENGTH}" border="1px solid #cccccc">
                                            {foreach from = $LISTICONS item =val key=k }
                                                {assign var=MODE4OK value=(($INDEX mod 14) == 0)}
                                                {if $MODE4OK}
                                                    <tr>
                                                {/if}
                                                <td style="padding: 5px;" class="cell-icon">
                                                    <span class="{$k} icon-module" style="font-size: 30px; vertical-align: middle;" data-info="{$val}"></span>
                                                </td>
                                                {if ($INDEX mod 14) == 13 or $LISTICONS_LENGTH == $INDEX}
                                                    </tr>
                                                {/if}
                                                <input type="hidden" value="{$INDEX++}">

                                            {/foreach}

                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary btn-submit">Save</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--added this div for Temporarily -->
                    {*{if $LISTVIEW_ENTRIES_COUNT eq '0'}*}
                        {*<table class="emptyRecordsDiv">*}
                            {*<tbody>*}
                            {*<tr>*}
                                {*<td>*}

                                {*</td>*}
                            {*</tr>*}
                            {*</tbody>*}
                        {*</table>*}
                    {*{/if}*}
                </div>
            </div>
        </div>
    </div>
{/strip}
