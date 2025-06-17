{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
*
 ********************************************************************************/
-->*}
{strip}
<input type="hidden" id="view" value="{$VIEW}" />
<input type="hidden" id="pageStartRange" value="{$PAGING_MODEL->getRecordStartRange()}" />
<input type="hidden" id="pageEndRange" value="{$PAGING_MODEL->getRecordEndRange()}" />
<input type="hidden" id="previousPageExist" value="{$PAGING_MODEL->isPrevPageExists()}" />
<input type="hidden" id="nextPageExist" value="{$PAGING_MODEL->isNextPageExists()}" />
<input type="hidden" id="totalCount" value="{$LISTVIEW_COUNT}" />
<input type='hidden' value="{$PAGE_NUMBER}" id='pageNumber'>
<input type='hidden' value="{$PAGING_MODEL->getPageLimit()}" id='pageLimit'>
<input type="hidden" value="{$LISTVIEW_ENTRIES_COUNT}" id="noOfEntries">
<div class="listViewEntriesDiv contents-bottomscroll" style="padding-top: 10px;">
	<div class="bottomscroll-div">
	<input type="hidden" value="{$ORDER_BY}" id="orderBy">
	<input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
	<span class="listViewLoadingImageBlock hide modal noprint" id="loadingListViewModal">
		<img class="listViewLoadingImage" src="{vimage_path('loading.gif')}" alt="no-image" title="{vtranslate('LBL_LOADING', $MODULE)}"/>
		<p class="listViewLoadingMsg">{vtranslate('LBL_LOADING_LISTVIEW_CONTENTS', $MODULE)}........</p>
	</span>
	{assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
	<table class="table table-bordered listViewEntriesTable">
		<thead>
			<tr class="listViewHeaders">
                <th nowrap style="width: 20%">
                    <a href="javascript:void(0);" class="listViewHeaderValues" data-nextsortorderval="ASC" data-columnname="module">
                        {vtranslate('Module', $MODULE)}&nbsp;&nbsp;
                    </a>
                </th>
                <th nowrap style="width: 70%">
                    <a href="javascript:void(0);" class="listViewHeaderValues" data-nextsortorderval="ASC" data-columnname="descriptions">
                        {vtranslate('Descriptions', $MODULE)}&nbsp;&nbsp;
                    </a>
                </th>
				<th nowrap style="width: 10%;text-align: center">
				</th>
			</tr>
		</thead>
		{foreach item=LISTVIEW_ENTRY from=$LISTVIEW_ENTRIES name=listview}
		<tr class="listViewEntries" data-id='{$LISTVIEW_ENTRY['id']}' data-recordUrl='index.php?module=VTEConditionalAlerts&parent=Settings&view=Edit&record={$LISTVIEW_ENTRY['id']}' id="{$MODULE}_listView_row_{$smarty.foreach.listview.index+1}">
			<td class="listViewEntryValue {$WIDTHTYPE}" nowrap>
                {vtranslate($LISTVIEW_ENTRY['module'],$MODULE)}
			</td>
            <td class="listViewEntryValue {$WIDTHTYPE}" nowrap>
                {vtranslate($LISTVIEW_ENTRY['description'],$MODULE)}
            </td>
            <td class="medium" nowrap="">
                <div class="actions" style="text-align: center;">
                    <span class="actionImages">
                       <a class="editVCA" data-url="index.php?module=VTEConditionalAlerts&parent=Settings&view=Edit&record={$LISTVIEW_ENTRY['id']}">
                            <i class="glyphicon glyphicon-pencil alignMiddle" title="Edit"></i>
                       </a>
                       <a href="javascript:void(0);" class="removeVTEConditionalAlert" data-id = {$LISTVIEW_ENTRY['id']}>
                           <i class="glyphicon glyphicon-trash alignMiddle" title="Delete"></i>
                       </a>
                    </span>
                </div>
            </td>
		</tr>
		{/foreach}
	</table>

<!--added this div for Temporarily -->
{if $LISTVIEW_ENTRIES_COUNT eq '0'}
	<table class="emptyRecordsDiv">
		<tbody>
			<tr>
				<td>
					{assign var=SINGLE_MODULE value="SINGLE_$MODULE"}
					{vtranslate('LBL_NO')} {vtranslate($MODULE, $MODULE)} {vtranslate('LBL_FOUND')}.{if $IS_MODULE_EDITABLE} {vtranslate('LBL_CREATE')} <a href="{$MODULE_MODEL->getCreateRecordUrl()}">{vtranslate($SINGLE_MODULE, $MODULE)}</a>{/if}
				</td>
			</tr>
		</tbody>
	</table>
{/if}
</div>
</div>
{/strip}
