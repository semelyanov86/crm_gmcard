{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{* modules/Vtiger/views/DashBoard.php *}
    
{strip}
<input type="hidden" id="userDateFormat" value="{$CURRENT_USER->get('date_format')}" />
<div class="dashBoardContainer clearfix">
        <div class="tabContainer">
            <ul class="nav nav-tabs tabs sortable container-fluid">
                {foreach key=index item=TAB_DATA from=$DASHBOARD_TABS}
                    <li class="{if $TAB_DATA["id"] eq $SELECTED_TAB}active{/if} dashboardTab" data-tabid="{$TAB_DATA["id"]}" data-tabname="{$TAB_DATA["tabname"]}">
                        <a data-toggle="tab" href="#tab_{$TAB_DATA["id"]}">
                            <div>
                                <span class="name textOverflowEllipsis" value="{$TAB_DATA["tabname"]}" style="width:10%">
                                    <strong>{$TAB_DATA["tabname"]}</strong>
                                </span>
                                <span class="editTabName hide">
                                    <input type="text" name="tabName"/>
                                </span>
                                <i class="fa fa-bars moveTab hide"></i>
                            </div>
                        </a>
                    </li>
                {/foreach}
                <div class="moreSettings pull-right {if $IS_SHARED eq true && !$CURRENT_USER->isAdminUser()}hide{/if}" >
                    <button class="btn btn-success saveFieldSequence hide" id="savePositionWidgets">{vtranslate('LBL_SAVE_LAYOUT','VReports')}</button>
                    <span class="dropdown dashBoardDropDown">
                    {*{include file="dashboards/DashBoardHeader.tpl"|vtemplate_path:$MODULE_NAME DASHBOARDHEADER_TITLE=vtranslate($MODULE, $MODULE)}*}
                        <button class="btn btn-default dropdown dashBoardDropDown" id="openAddWidget" {if !$MODULE_PERMISSION} disabled="disabled" {/if} style="margin-right: 10px">{vtranslate('LBL_ADD_WIDGET','VReports')}</button>
                    </span>
                    <span class="dropdown dashBoardDropDown">
                        <button class="btn btn-default reArrangeTabs dropdown-toggle" type="button" data-toggle="dropdown">{vtranslate('LBL_MORE',$MODULE)}
                            &nbsp;&nbsp;<span class="caret"></span></button>
                        <ul class="dropdown-menu dropdown-menu-right moreDashBoards" style="margin-top: 21%;">

                            <li style="font-weight: bold;padding: 4px 6px;">{vtranslate('LBL_WIDGETS',$MODULE_NAME)}</li>
                            <li><a class = "editWidgets" href="#">{vtranslate('LBL_EDIT_WIDGETS',$MODULE)}</a></li>
                            <li><a class = "dynamicFilter" href="#">{vtranslate('LBL_DYNAMIC_FILTER',$MODULE)}</a></li>
                            <li class="divider"></li>

                            <li style="font-weight: bold;padding: 4px 6px;">{vtranslate('LBL_TABS',$MODULE_NAME)}</li>
                            <li id="newDashBoardLi"{if is_array($DASHBOARD_TABS)}{if count($DASHBOARD_TABS) eq $DASHBOARD_TABS_LIMIT}class="disabled"{/if}{/if}>
                                <a data-action="add" class="addNewDashBoard" href="#">{vtranslate('LBL_ADD_NEW_TAB',$MODULE)}</a>
                            </li>
                            <li><a class = "renameTabs" href="#">{vtranslate('LBL_RENAME_TAB',$MODULE)}</a></li>
                            <li><a data-action="duplicate" class = "addNewDashBoard" href="#">{vtranslate('LBL_DUPLICATE_TAB',$MODULE)}</a></li>
                            <li><a class = "deleteTab" href="#">{vtranslate('LBL_DELETE_TAB',$MODULE)}</a></li>
                            <li><a class = "reArrangeTabs" href="#">{vtranslate('LBL_REARRANGE_DASHBOARD_TABS',$MODULE)}</a></li>
                            <li class="divider"></li>

                            <li style="font-weight: bold;padding: 4px 6px;">{vtranslate('LBL_BOARDS',$MODULE_NAME)}</li>
                            <li><a class = "addBoards" href="#">{vtranslate('LBL_ADD_NEW_BOARD',$MODULE)}</a></li>
                            <li><a class = "editBoards" href="#">{vtranslate('LBL_EDIT_BOARD',$MODULE)}</a></li>
                            <li><a class = "deleteBoard" href="#">{vtranslate('LBL_DELETE_BOARD',$MODULE)}</a></li>
                        </ul>
                    </span>
                    <span class="notification-dynamic" rel="tooltip" data-original-title="Dynamic Filter is active for this tab">

                    </span>
                    <button class="btn-success updateSequence pull-right hide">{vtranslate('LBL_SAVE_ORDER',$MODULE)}</button>
                </div>
            </ul>
            <div class="tab-content" data-boardid="{$SELECTED_BOARD}">
                {foreach key=index item=TAB_DATA from=$DASHBOARD_TABS}
                    <div id="tab_{$TAB_DATA["id"]}" data-tabid="{$TAB_DATA["id"]}" data-tabname="{$TAB_DATA["tabname"]}" class="tab-pane fade {if $TAB_DATA["id"] eq $SELECTED_TAB}in active{/if}">
                        {if $TAB_DATA["id"] eq $SELECTED_TAB}
                            {include file="dashboards/DashBoardTabContents.tpl"|vtemplate_path:$MODULE TABID=$TABID}
                        {/if}
                    </div>
                {/foreach}
            </div>
        </div>
</div>
{/strip}