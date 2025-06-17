{*/* * *******************************************************************************
* The content of this file is subject to the Kanban View ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}
{include file="Header.tpl"|vtemplate_path:$MODULE}
{include file="BasicHeader.tpl"|vtemplate_path:$MODULE}
<div class="bodyContents">
	<div class="mainContainer row-fluid">
        <div class="contentsBox contentsDiv">
        <div class="listViewActionsDiv row-fluid">
            <input id="kbParentModule" type="hidden" value="{$KANBAN_PARENT_MODULE}">
            <span class="span4">&nbsp;</span>
            <span class="btn-toolbar span4">
                <span class="customFilterMainSpan btn-group">
                    {if $CUSTOM_VIEWS|@count gt 0}

                        <select id="customFilter" style="width:350px;">
                            {foreach key=GROUP_LABEL item=GROUP_CUSTOM_VIEWS from=$CUSTOM_VIEWS}
                                <optgroup label=' {if $GROUP_LABEL eq 'Mine'} &nbsp; {else if} {vtranslate($GROUP_LABEL)} {/if}' >
                                    {foreach item="CUSTOM_VIEW" from=$GROUP_CUSTOM_VIEWS}
                                        <option  data-editurl="{$CUSTOM_VIEW->getEditUrl()}" data-deleteurl="{$CUSTOM_VIEW->getDeleteUrl()}" data-approveurl="{$CUSTOM_VIEW->getApproveUrl()}" data-denyurl="{$CUSTOM_VIEW->getDenyUrl()}" data-editable="{$CUSTOM_VIEW->isEditable()}" data-deletable="{$CUSTOM_VIEW->isDeletable()}" data-pending="{$CUSTOM_VIEW->isPending()}" data-public="{$CUSTOM_VIEW->isPublic() && $CURRENT_USER_MODEL->isAdminUser()}" id="filterOptionId_{$CUSTOM_VIEW->get('cvid')}" value="{$CUSTOM_VIEW->get('cvid')}" data-id="{$CUSTOM_VIEW->get('cvid')}" {if $VIEWID neq '' && $VIEWID neq '0'  && $VIEWID == $CUSTOM_VIEW->getId()} selected="selected" {elseif ($VIEWID == '' or $VIEWID == '0')&& $CUSTOM_VIEW->isDefault() eq 'true'} selected="selected" {/if} class="filterOptionId_{$CUSTOM_VIEW->get('cvid')}">{if $CUSTOM_VIEW->get('viewname') eq 'All'}{vtranslate($CUSTOM_VIEW->get('viewname'), $MODULE)} {vtranslate($KANBAN_PARENT_MODULE, KANBAN_PARENT_MODULE)}{else}{vtranslate($CUSTOM_VIEW->get('viewname'), $MODULE)}{/if}{if $GROUP_LABEL neq 'Mine'} [ {$CUSTOM_VIEW->getOwnerName()} ]  {/if}</option>
                                    {/foreach}
                                </optgroup>
                            {/foreach}
                        </select>
                        <span class="filterActionsDiv hide">
                            <hr>
                            <ul class="filterActions">
                                <li data-value="create" id="createFilter" data-createurl="{$CUSTOM_VIEW->getCreateUrl()}"><i class="icon-plus-sign"></i> {vtranslate('LBL_CREATE_NEW_FILTER')}</li>
                            </ul>
                        </span>
                        <img class="filterImage" src="{'filter.png'|vimage_path}" style="display:none;height:13px;margin-right:2px;vertical-align: middle;">
                    {else}
                        <input type="hidden" value="0" id="customFilter" />
                    {/if}
                </span>
            </span>
            <span class="span3" style = "width: 27.5%;padding-top: 10px;" >
                <button class="btn pull-right" onclick="KanbanView_Js.getSettingView('{$KANBAN_PARENT_MODULE}','KanbanView')">{vtranslate('LBL_CONFIGURE_KANBAN_VIEW', 'KanbanView')}</button>
                <a class="btn pull-right" style="margin-right: 15px;" href="index.php?module={$KANBAN_PARENT_MODULE}&view=List&viewname={$VIEWID}"> {vtranslate('LBL_GO_BACK_TO_LISTVIEW', 'KanbanView')}</a>
            </span>
        <span class="hide filterActionImages pull-right">
            <i title="{vtranslate('LBL_DENY', $MODULE)}" data-value="deny" class="icon-ban-circle alignMiddle denyFilter filterActionImage pull-right"></i>
            <i title="{vtranslate('LBL_APPROVE', $MODULE)}" data-value="approve" class="icon-ok alignMiddle approveFilter filterActionImage pull-right"></i>
            <i title="{vtranslate('LBL_DELETE', $MODULE)}" data-value="delete" class="icon-trash alignMiddle deleteFilter filterActionImage pull-right"></i>
            <i title="{vtranslate('LBL_EDIT', $MODULE)}" data-value="edit" class="icon-pencil alignMiddle editFilter filterActionImage pull-right"></i>
        </span>
    </div>
