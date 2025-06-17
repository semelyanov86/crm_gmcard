{*<!--
/*********************************************************************************
  ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
   * ("License"); You may not use this file except in compliance with the License
   * The Original Code is: vtiger CRM Open Source
   * The Initial Developer of the Original Code is vtiger.
   * Portions created by vtiger are Copyright (C) vtiger.
   * All Rights Reserved.
  *
 ********************************************************************************/
-->*}
{strip}
    {assign var=HEADER_COLOR value=$WIDGET->get('pick_color')}
    {assign var=HEADER_TEXT_COLOR value=VReports_Widget_Model::getTextColor($HEADER_COLOR)}
    <header data-url="{$WIDGET->getUrl()}" data-refresh-time="{$WIDGET->get('refresh_time')}" data-tabid="{$WIDGET->get('dashboardtabid')}" class="panel_header sticky_header" style="z-index: 1000; background-color: {if $HEADER_COLOR} {$HEADER_COLOR}; color: {$HEADER_TEXT_COLOR}; {else} #ffffff; {/if}">
        {include file="dashboards/WidgetHeader.tpl"|@vtemplate_path:$MODULE_NAME}
    </header>

    <div class="dashboardWidgetContent" style="padding-top:15px;text-align: left">
        {include file="dashboards/HistoryContents.tpl"|@vtemplate_path:$MODULE_NAME}
    </div>

    <div class="widgeticons dashBoardWidgetFooter panel_footer sticky_footer" style="top: 100%;text-align: left;display: none">
        <div class="filterContainer boxSizingBorderBox" style="background: whitesmoke">
            <div style="text-align: right;color: crimson" class="col-lg-12 pull-right"><a data-hide="hide" onclick="VReports_DashBoard_Js.registerFilterInitiater(this)">Close</a></div>
            <div class="row" style="margin-bottom: 10px;">
                <div class="col-sm-12">
                    <div class="col-lg-4">
                        <span><strong>{vtranslate('LBL_SHOW', $MODULE_NAME)}</strong></span>
                    </div>
                    <div class="col-lg-7">
                        {if $COMMENTS_MODULE_MODEL->isPermitted('DetailView')}
                            <label class="radio-group cursorPointer">
                                <input type="radio" name="historyType" class="widgetFilter reloadOnChange_checkbox cursorPointer"
                                       value="comments" {if $HISTORY_TYPE_CHECKBOX eq 'comments'} checked {/if}/> {vtranslate('LBL_COMMENTS', $MODULE_NAME)}
                            </label>
                            <br>
                        {/if}
                        <label class="radio-group cursorPointer">
                            <input type="radio" name="historyType" class="widgetFilter reloadOnChange_checkbox cursorPointer"
                                   value="updates" {if $HISTORY_TYPE_CHECKBOX eq 'updates'} checked {/if}/>
                            <span>{vtranslate('LBL_UPDATES', $MODULE_NAME)}</span>
                        </label><br>
                        <label class="radio-group cursorPointer">
                            <input type="radio" name="historyType" class="widgetFilter reloadOnChange_checkbox cursorPointer"
                                   value="all" {if $HISTORY_TYPE_CHECKBOX eq 'all'} checked {elseif $HISTORY_TYPE_CHECKBOX eq ''} checked {/if}/> {vtranslate('LBL_BOTH', $MODULE_NAME)}
                        </label>
                    </div>
                </div>
            </div>
            <div class="row" style="margin-bottom: 10px;">
                <div class="col-sm-12">
                    <div class="col-lg-4">
                        <span><strong>{vtranslate('LBL_GROUP_AND_SORT', $MODULE_NAME)}</strong></span>
                    </div>
                    <div class="col-lg-7">
                        <label class="radio-group cursorPointer">
                            <input type="checkbox" data-event="Group" name="groupAndSort" class="widgetFilter reloadOnChange_checkbox cursorPointer"{if $GROUP_AND_SORT eq '1'} checked {/if}/> {*{vtranslate('LBL_NO_NAME', $MODULE_NAME)}*}
                        </label>
                    </div>
                </div>
            </div>
            <div class="row" style="margin-bottom: 10px;">
                <div class="col-sm-12">
                <span class="col-lg-4">
                        <span>
                            <strong>{vtranslate('LBL_SELECT_DATE_RANGE', $MODULE_NAME)}</strong>
                        </span>
                </span>
                    <span class="col-lg-7">
                    <div class="input-daterange input-group dateRange widgetFilter" id="datepicker" name="modifiedtime">
                        <input type="text" class="input-sm form-control" name="start" style="height:30px;"/>
                        <span class="input-group-addon">to</span>
                        <input type="text" class="input-sm form-control" name="end" style="height:30px;"/>
                    </div>
                </span>
                </div>
            </div>
            <br>
            <div class="userList">
                {assign var=CURRENT_USER_ID value=$CURRENT_USER->getId()}
                {if $ACCESSIBLE_USERS|@count gt 1}
                    <select class="select2 widgetFilter col-lg-3 reloadOnChange_select" name="historyType" style="width: 100%">
                        {if $HISTORY_TYPE}
                            {if $HISTORY_TYPE eq 'all'}
                                <option value="all">{vtranslate('All', $MODULE_NAME)}</option>
                            {else}
                                <option value="{$HISTORY_TYPE}" selected>{$ACCESSIBLE_USERS[$HISTORY_TYPE]}</option>
                                <option value="all">{vtranslate('All', $MODULE_NAME)}</option>
                            {/if}
                            {foreach key=USER_ID from=$ACCESSIBLE_USERS item=USER_NAME}
                                {if $USER_NAME eq $ACCESSIBLE_USERS[$HISTORY_TYPE]}
                                    {continue}
                                {else}
                                <option value="{$USER_ID}">
                                    {if $USER_ID eq $CURRENT_USER_ID}
                                        {vtranslate('LBL_MINE',$MODULE_NAME)}
                                    {else}
                                        {$USER_NAME}
                                    {/if}
                                </option>
                                {/if}
                            {/foreach}
                        {else}
                            <option value="all" selected>{vtranslate('All', $MODULE_NAME)}</option>
                            {foreach key=USER_ID from=$ACCESSIBLE_USERS item=USER_NAME}
                                <option value="{$USER_ID}">
                                    {if $USER_ID eq $CURRENT_USER_ID}
                                        {vtranslate('LBL_MINE',$MODULE_NAME)}
                                    {else}
                                        {$USER_NAME}
                                    {/if}
                                </option>
                            {/foreach}
                        {/if}
                    </select>
                {else}
                    <center>{vtranslate('LBL_MY',$MODULE_NAME)} {vtranslate('History',$MODULE_NAME)}</center>
                {/if}
            </div>
        </div>
    </div>
{/strip}