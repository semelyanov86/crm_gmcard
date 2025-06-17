{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <div class="modal-dialog modelContainer">
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE="{vtranslate('LBL_DYNAMIC_FILTER',$MODULE)}"}
        <div class="modal-content">
            <form id="EditDynamicFilters" name="EditDynamicFilters" method="post" action="index.php">
                <input type="hidden" name="module" value="{$MODULE}"/>
                <input type="hidden" name="action" value="DashboardActions"/>
                <input type="hidden" name="mode" value="saveDynamicFilter"/>
                <input type="hidden" name="dashboardId" value="{$DASHBOARD_ID}"/>

                <div class="modal-body clearfix">
                    <div class="col-lg-4 col-lg-offset-1">
                        <label class="control-label pull-left marginTop5px">
                            {vtranslate('LBL_ORGANIZATIONS',$MODULE)}
                        </label>
                    </div>
                    <div class="col-lg-6">
                        <div>
                            <div class="input-group">
                                <input name="dynamic_filter_accountid" type="hidden" value="{if $ACCOUNT_ID} {$ACCOUNT_ID} {else} {$RECORD_DATA['dynamic_filter_account']}{/if}" class="sourceField">
                                <input class="marginLeftZero autoComplete inputElement ui-autocomplete-input"
                                       id="dynamic_filter_account_display"
                                       style="padding-left:10px !important ; font-size:15px ; cursor:not-allowed"
                                       readonly="readonly"
                                       value="{if $ACCOUNT_DISPLAY} {$ACCOUNT_DISPLAY} {else} {$RECORD_DATA['dynamic_filter_account_display']}{/if}">
                                <span id="clear-related-record" class="input-group-addon cursorPointer" title="Delete" {if $RECORD_DATA['dynamic_filter_account'] == NULL || empty($RECORD_DATA['dynamic_filter_account'])} style="display: none"{/if}>
                                    <i class="fa fa-close"></i>
                                </span>
                                <span class="input-group-addon relatedPopup cursorPointer" title="Select">
                                    <i class="fa fa-search"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12"><br/></div>
                    <div class="col-lg-4 col-lg-offset-1">
                        <label class="control-label pull-left marginTop5px">
                            {vtranslate('LBL_ASSIGNED_TO',$MODULE)}
                        </label>
                    </div>
                    <div class="col-lg-6">
                        {assign var=ALL_ACTIVEUSER_LIST value=$USER_MODEL->getAccessibleUsers()}
                        {assign var=ALL_ACTIVEGROUP_LIST value=$USER_MODEL->getAccessibleGroups()}
                        {assign var=ASSIGNED_TO_VALUE value=$RECORD_DATA['dynamic_filter_assignedto']}
                        <select class="inputElement select2" name="dynamic_filter_assignedto">
                            <option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
                            <option value="0" {if $ASSIGNED_TO_VALUE eq '0'} selected {/if}>{vtranslate('Logged in user','VReports')}</option>
                            <optgroup label="{vtranslate('LBL_USERS')}">
                                {foreach key=OWNER_ID item=OWNER_NAME from=$ALL_ACTIVEUSER_LIST}
                                    <option value="{$OWNER_ID}" data-picklistvalue= '{$OWNER_NAME}' {if $ASSIGNED_TO_VALUE eq $OWNER_ID} selected {/if}>
                                        {$OWNER_NAME}
                                    </option>
                                {/foreach}
                            </optgroup>
                            <optgroup label="{vtranslate('LBL_GROUPS')}">
                                {foreach key=OWNER_ID item=OWNER_NAME from=$ALL_ACTIVEGROUP_LIST}
                                    <option value="{$OWNER_ID}" data-picklistvalue= '{$OWNER_NAME}' {if $ASSIGNED_TO_VALUE eq $OWNER_ID} selected {/if}>
                                        {$OWNER_NAME}
                                    </option>
                                {/foreach}
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-lg-12"><br/></div>
                    <div class="col-lg-4 col-lg-offset-1">
                        <label class="control-label pull-left marginTop5px">
                            {vtranslate('Created By',$MODULE)}
                        </label>
                    </div>
                    <div class="col-lg-6">
                        {assign var=ALL_ACTIVEUSER_LIST value=$USER_MODEL->getAccessibleUsers()}
                        {assign var=ALL_ACTIVEGROUP_LIST value=$USER_MODEL->getAccessibleGroups()}
                        {assign var=CREATED_BY value=$RECORD_DATA['dynamic_filter_createdby']}
                        <select class="inputElement select2" name="dynamic_filter_createdby">
                            <option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
                            <option value="0" {if $CREATED_BY eq '0'} selected {/if}>{vtranslate('Logged in user','VReports')}</option>
                            <optgroup label="{vtranslate('LBL_USERS')}">
                                {foreach key=OWNER_ID item=OWNER_NAME from=$ALL_ACTIVEUSER_LIST}
                                    <option value="{$OWNER_ID}" data-picklistvalue= '{$OWNER_NAME}' {if $CREATED_BY eq $OWNER_ID} selected {/if}>
                                        {$OWNER_NAME}
                                    </option>
                                {/foreach}
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-lg-12"><br/></div>
                    <div class="col-lg-4 col-lg-offset-1">
                        <label class="control-label pull-left marginTop5px">
                            {vtranslate('LBL_DATE',$MODULE)}
                        </label>
                    </div>
                    <div class="col-lg-6">
                        <select class="{if empty($NOCHOSEN)}select2{/if} col-lg-12" name="dynamic_filter_date">
                            <option value="">{vtranslate('LBL_NONE',$MODULE)}</option>
                            {assign var=DATE_FILTER_CONDITIONS value=array_keys($DATE_FILTERS)}
                            {foreach item=DATE_FILTER_CONDITION from=$DATE_FILTER_CONDITIONS}
                                <option value="{$DATE_FILTER_CONDITION}"
                                        {if $DATE_FILTER_CONDITION eq $RECORD_DATA['dynamic_filter_date']}
                                            selected
                                        {/if}
                                >{vtranslate($DATE_FILTERS[$DATE_FILTER_CONDITION]['label'])}</option>
                            {/foreach}
                        </select>
                    </div>
                    <div class="col-lg-12"><br/></div>
                    <div class="col-lg-4 col-lg-offset-1">

                    </div>
                    <div class="col-lg-6">
                        <label class="radioOption inline">
                            <input class="radioOption" type="radio" name="value_type_date" value="createdtime" {if $RECORD_DATA['dynamic_filter_type_date'] eq 'createdtime' || $RECORD_DATA['dynamic_filter_type_date'] eq ''} checked {/if}/>
                                 &nbsp;&nbsp;&nbsp;{vtranslate('LBL_CREATE_TIME',$MODULE)}
                        </label>
                        <label class="radioOption inline" style="margin-left: 20px;">
                            <input class="radioOption" type="radio" name="value_type_date" value="modifiedtime"  {if $RECORD_DATA['dynamic_filter_type_date'] eq 'modifiedtime'} checked {/if}/>
                                &nbsp;&nbsp;&nbsp;{vtranslate('LBL_MODIFIED_TIME',$MODULE)}
                        </label>
                    </div>
                </div>
                {include file="ModalFooter.tpl"|vtemplate_path:$MODULE}
            </form>
        </div>
    </div>
{/strip}
