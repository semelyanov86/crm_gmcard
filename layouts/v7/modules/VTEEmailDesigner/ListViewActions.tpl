{*<!--
/*+***********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************/
-->*}
{strip}
    {assign var=LISTVIEW_MASSACTIONS_1 value=array()}
    <div id="listview-actions" class="listview-actions-container">
        {foreach item=LIST_MASSACTION from=$LISTVIEW_MASSACTIONS name=massActions}
            {if $LIST_MASSACTION->getLabel() eq 'LBL_EDIT'}
                {assign var=editAction value=$LIST_MASSACTION}
            {else if $LIST_MASSACTION->getLabel() eq 'LBL_DELETE'}
                {assign var=deleteAction value=$LIST_MASSACTION}
            {else if $LIST_MASSACTION->getLabel() eq 'LBL_ADD_COMMENT'}
                {assign var=commentAction value=$LIST_MASSACTION}
            {else}
                {$a = array_push($LISTVIEW_MASSACTIONS_1, $LIST_MASSACTION)}
                {* $a is added as its print the index of the array, need to find a way around it *}
            {/if}
        {/foreach}
        <div class = "row">
            <div class="btn-toolbar col-md-3" role="group" aria-label="...">
                <div class="btn-group" role="group" aria-label="...">
                    <button type="button" class="btn btn-default viewType" title="{vtranslate('LBL_LIST_VIEW',$MODULE)}" data-mode="list" {if $VIEWTYPE eq 'list'} disabled="disabled" {/if}><i class="fa fa-th-list"></i></button>
                    <button type="button" class="btn btn-default viewType" title="{vtranslate('LBL_THUMBNAIL_VIEW',$MODULE)}" data-mode="grid" {if $VIEWTYPE eq 'grid'} disabled="disabled" {/if}><i class="fa fa-th-large"></i></button>
                </div>
                <div class="btn-group">
                {if $editAction}
                    <button type="button" class="btn btn-default" id={$MODULE}_listView_massAction_{$editAction->getLabel()}
                            {if stripos($editAction->getUrl(), 'javascript:')===0} href="javascript:void(0);" onclick='{$editAction->getUrl()|substr:strlen("javascript:")}'{else} href='{$editAction->getUrl()}' {/if} title="{vtranslate('LBL_EDIT', $MODULE)}">
                        <i class="fa fa-pencil"></i>
                    </button>
                {/if}
                {if $deleteAction}
                    <button type="button" class="btn btn-default {if $VIEWTYPE eq 'grid'}hide{/if}" id={$MODULE}_listView_massAction_{$deleteAction->getLabel()}
                            {if stripos($deleteAction->getUrl(), 'javascript:')===0} href="javascript:void(0);" onclick='{$deleteAction->getUrl()|substr:strlen("javascript:")}'{else} href='{$deleteAction->getUrl()}' {/if} title="{vtranslate('LBL_DELETE', $MODULE)}">
                        <i class="fa fa-trash"></i>
                    </button>
                {/if}
                {if $commentAction}
                    <button type="button" class="btn btn-default" id="{$MODULE}_listView_massAction_{$commentAction->getLabel()}"
                            onclick="Vtiger_List_Js.triggerMassAction('{$commentAction->getUrl()}')" title="{vtranslate('LBL_COMMENT', $MODULE)}">
                        <i class="fa fa-comment"></i>
                    </button>
                {/if}
            </div>
            </div>
            <div class='col-md-6'>
                {if $LISTVIEW_ENTRIES_COUNT eq '0' and $REQUEST_INSTANCE->isAjax()}
                    {if $smarty.session.lvs.$MODULE.viewname}
                        {assign var=VIEWID value=$smarty.session.lvs.$MODULE.viewname}
                    {/if}
                    {if $VIEWID}
                        {foreach item=FILTER_TYPES from=$CUSTOM_VIEWS}
                            {foreach item=FILTERS from=$FILTER_TYPES}
                                {if $FILTERS->get('cvid') eq $VIEWID}
                                    {assign var=CVNAME value=$FILTERS->get('viewname')}
                                    {break}
                                {/if}
                            {/foreach}
                        {/foreach}
                        {assign var=DEFAULT_FILTER_URL value=$MODULE_MODEL->getDefaultUrl()}
                        {assign var=DEFAULT_FILTER_ID value=$MODULE_MODEL->getDefaultCustomFilter()}
                        {if $DEFAULT_FILTER_ID}
                            {assign var=DEFAULT_FILTER_URL value=$MODULE_MODEL->getListViewUrl()|cat:"&viewname="|cat:$DEFAULT_FILTER_ID}
                        {/if}
                        {if $CVNAME neq 'All'}
                            <div>{vtranslate('LBL_DISPLAYING_RESULTS',$MODULE)} {vtranslate('LBL_FROM',$MODULE)} <b>{$CVNAME}</b>. <a style="color:blue" href='{$DEFAULT_FILTER_URL}'>{vtranslate('LBL_SEARCH_IN',$MODULE)} {vtranslate('ALL',$MODULE)} {vtranslate($MODULE, $MODULE)}</a> </div>
                        {/if}
                    {/if}
                {/if}
                <div id="selectAllMsgDiv" class="hide" style = "height:30px;">
                    <center><a href="#">{vtranslate('LBL_SELECT_ALL',$MODULE)}&nbsp;{vtranslate($MODULE ,$MODULE)}&nbsp;(<span id="totalRecordsCount" value=""></span>)</a></center>
                </div>
                <div id="deSelectAllMsgDiv" class="hide" style = "height:30px;">
                    <center><a href="#">{vtranslate('LBL_DESELECT_ALL_RECORDS',$MODULE)}</a></center>
                </div>
            </div>
            <div class="col-md-3">
                {assign var=RECORD_COUNT value=$LISTVIEW_ENTRIES_COUNT}
                {include file="Pagination.tpl"|vtemplate_path:$MODULE SHOWPAGEJUMP=true}
            </div>
        </div>
     </div>
{/strip}