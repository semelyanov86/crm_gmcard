{*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************}
{strip}
	{if $WIZARD_STEP eq 'step1' || $WIDGET_MODE eq 'Settings'}
		<div id="minilistWizardContainer" class='modelContainer modal-dialog'>
			<div class="modal-content">
				{if $WIDGET_NAME eq 'MiniList'}
					{assign var=HEADER_TITLE value={vtranslate('LBL_MINI_LIST', $MODULE)}}
				{elseif $WIDGET_NAME eq 'History'}
					{assign var=HEADER_TITLE value={vtranslate('LBL_HISTORY', $MODULE)}}
				{elseif $WIDGET_NAME eq 'KeyMetrics'}
					{assign var=HEADER_TITLE value={vtranslate('LBL_KEY_METRICS', $MODULE)}}
				{elseif $WIDGET_NAME eq 'Gauge'}
					{assign var=HEADER_TITLE value={vtranslate('LBL_GAUGE', $MODULE)}}
				{/if}
				{include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE=$HEADER_TITLE}
				<form name="form-setting" class="form-horizontal setting" method="post" action="javascript:;">
					<input type="hidden" name="module" value="{$MODULE}" />
					<input type="hidden" name="widgetType" value="{$WIDGET_TYPE}" />
					<input type="hidden" name="action" value="MassSave" />
					<input type="hidden" name="recordIdSetting" value="{$RECORD_ID}"/>
					<input type="hidden" name="widgetName" value="{$WIDGET_NAME}"/>
					<input type="hidden" name="selected_field" value='{if $SELECTED_FIELD}{$SELECTED_FIELD}{else}[]{/if}'/>
					{if $WIDGET_NAME eq 'MiniList'}
						<input type="hidden" name="selected_module" value="{$SELECTED_MODULE}"/>
						<input type="hidden" name="columnslist" id="columnslist" value='{$SELECTED_FIELD}'>
					{/if}
					<input type="hidden" name="selected_time" id="selected_time" value="{if $WIDGET}{$WIDGET->get('refresh_time')}{/if}">

					<table class="table no-border">
						<tbody>
						{if $WIDGET_MODE neq 'Settings'}
							<tr>
								<td class="col-lg-1"></td>
								<td class="fieldLabel col-lg-4"><label class="pull-right">{'LBL_SELECT_MODULE'|vtranslate}</label></td>
								<td class="fieldValue col-lg-5">
									<select name="module" style="width: 100%">
										<option></option>
										{assign var=TRANSLATED_MODULES_NAMES value=[]}
										{foreach from=$MODULES item=MODULE_MODEL key=MODULE_NAME}
											{$TRANSLATED_MODULE_NAMES.$MODULE_NAME = {vtranslate($MODULE_NAME, $MODULE_NAME)}}
											<option value="{$MODULE_NAME}">{vtranslate($MODULE_NAME, $MODULE_NAME)}</option>
										{/foreach}
									</select>
								</td>
								<td class="col-lg-4"></td>
							</tr>

							<tr>
								<td class="col-lg-1"></td>
								<td class="fieldLabel col-lg-4"><label class="pull-right">{'LBL_FILTER'|vtranslate}</label></td>
								<td class="fieldValue col-lg-5">
									<select name="filterid" style="width: 100%">
										<option></option>
									</select>
								</td>
								<td class="col-lg-4"></td>
							</tr>

							<tr>
								<td class="col-lg-1"></td>
								<td class="fieldLabel col-lg-4"><label class="pull-right">{'LBL_EDIT_FIELDS'|vtranslate}</label></td>
								<td class="fieldValue col-lg-5">
									<select name="fields" size="2" multiple="true" style="width: 100%">
										<option></option>
									</select>
								</td>
								<td class="col-lg-4"></td>
							</tr>
							{else}
							{if $WIDGET_NAME eq 'MiniList'}
								<tr>
									<td class="col-lg-1"></td>
									<td class="fieldLabel col-lg-4"><label class="pull-right">{'Fields & Layout'|vtranslate}</label></td>
									<td class="fieldValue col-lg-5">
										<select name="fields" class="select2" size="2" multiple="true" style="width: 100%">
											{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
												{*in case customer want to add option group for field, un comment line below*}
												<optgroup label='{vtranslate($BLOCK_LABEL)}'>
												{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
													 To not show star field in filter select view
													{if $FIELD_MODEL->getDisplayType() == '6'}
														{continue}
													{/if}
													{if $FIELD_MODEL->isMandatory()}
														{array_push($MANDATORY_FIELDS, $FIELD_MODEL->getCustomViewColumnName())}
													{/if}
													{assign var=FIELD_MODULE_NAME value=$FIELD_MODEL->getModule()->getName()}
													<option value="{$FIELD_NAME}" data-field-name="{$FIELD_NAME}"
															{if in_array(decode_html($FIELD_MODEL->getCustomViewColumnName()), $SELECTED_FIELDS)}
																selected
															{elseif (!$RECORD_ID) && ($FIELD_MODEL->isSummaryField() || $FIELD_MODEL->isHeaderField()) && ($FIELD_MODULE_NAME eq $SOURCE_MODULE) && (!(preg_match("/\([A-Za-z_0-9]* \; \([A-Za-z_0-9]*\) [A-Za-z_0-9]*\)/", $FIELD_NAME))) && $NUMBER_OF_COLUMNS_SELECTED < $MAX_ALLOWED_COLUMNS}
																selected
																{assign var=NUMBER_OF_COLUMNS_SELECTED value=$NUMBER_OF_COLUMNS_SELECTED + 1}
															{/if}
													>{Vtiger_Util_Helper::toSafeHTML(vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE))}
														{if $FIELD_MODEL->isMandatory() eq true} <span>*</span> {/if}
													</option>
												{/foreach}
												</optgroup>
											{/foreach}
										</select>
									</td>
									<td class="col-lg-4"></td>
								</tr>
								<tr>
									<td class="col-lg-1"></td>
									<td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_FIELD_ORDER','VReports')}</label></td>
									<td class="fieldValue col-lg-5">
										<div style="display: inline-block;width: 100%">
											<select name="orderby-field" class="select2" size="2" style="width: 63%">
												<option value="">Select an Option</option>
												{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
													{*in case customer want to add option group for field, un comment line below*}
													{*<optgroup label='{vtranslate($BLOCK_LABEL)}'>*}
													{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
														{* To not show star field in filter select view*}
														{if $FIELD_MODEL->getDisplayType() == '6'}
															{continue}
														{/if}
														{if $FIELD_MODEL->isMandatory()}
															{array_push($MANDATORY_FIELDS, $FIELD_MODEL->getCustomViewColumnName())}
														{/if}
														{assign var=FIELD_MODULE_NAME value=$FIELD_MODEL->getModule()->getName()}
														<option value="{$FIELD_NAME}" data-field-name="{$FIELD_NAME}"
																{if $SELECTED_ORDER_FIELD eq $FIELD_NAME}selected{/if}
														>{Vtiger_Util_Helper::toSafeHTML(vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE))}
														</option>
													{/foreach}
													{*</optgroup>*}
												{/foreach}
											</select> &nbsp;&nbsp;&nbsp;&nbsp;
											<select name="orderby-keyword" class="select2" size="2" style="width: 30%">
												<option value="ASC" selected >ASC</option>
												<option value="DESC" {if $SELECTED_ORDER_KEYWORD eq "DESC"} selected {/if}>DESC</option>
											</select>
										</div>
									</td>
									<td class="fieldValue col-lg-5">
										<input type="checkbox" name="checkboxDrawLine" class="hide" {if $SHOW_LINE_ON_ROW eq 'true'} checked{/if}>
									</td>
									<td class="col-lg-4"></td>
								</tr>
								<tr>
									<td class="col-lg-1"></td>
									<td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_FIELD_ORDER_1','VReports')}</label></td>
									<td class="fieldValue col-lg-5">
										<div style="display: inline-block;width: 100%">
											<select name="orderby-field-1" class="select2" size="2" style="width: 63%">
												<option value="">Select an Option</option>
												{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
													{*in case customer want to add option group for field, un comment line below*}
													{*<optgroup label='{vtranslate($BLOCK_LABEL)}'>*}
													{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
														{* To not show star field in filter select view*}
														{if $FIELD_MODEL->getDisplayType() == '6'}
															{continue}
														{/if}
														{if $FIELD_MODEL->isMandatory()}
															{array_push($MANDATORY_FIELDS, $FIELD_MODEL->getCustomViewColumnName())}
														{/if}
														{assign var=FIELD_MODULE_NAME value=$FIELD_MODEL->getModule()->getName()}
														<option value="{$FIELD_NAME}" data-field-name="{$FIELD_NAME}"
																{if $SELECTED_ORDER_FIELD_1 eq $FIELD_NAME}selected{/if}
														>{Vtiger_Util_Helper::toSafeHTML(vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE))}
														</option>
													{/foreach}
													{*</optgroup>*}
												{/foreach}
											</select> &nbsp;&nbsp;&nbsp;&nbsp;
											<select name="orderby-keyword-1" class="select2" size="2" style="width: 30%">
												<option value="ASC" selected >ASC</option>
												<option value="DESC" {if $SELECTED_ORDER_KEYWORD_1 eq "DESC"} selected {/if}>DESC</option>
											</select>
										</div>
									</td>
									<td class="fieldValue col-lg-5">
										<input type="checkbox" name="checkboxDrawLine1" class="hide" {if $SHOW_LINE_ON_ROW_1 eq 'true'} checked{/if}>
									</td>
									<td class="col-lg-4"></td>
								</tr>

							{elseif $WIDGET_NAME eq 'KeyMetrics'}
								<tr>
									<td class="col-lg-1"></td>
									<td class="fieldLabel col-lg-4"><label class="pull-right">{'List'|vtranslate}</label></td>
									<td class="fieldValue col-lg-5">
										{if $WIDGET_FORM eq 'Create'}
											<select name="fields" class="select2" size="2" multiple="true" style="width: 100%">
												{foreach key=MODULE_GROUP_ID item=MODULE_GROUP from=$ALL_KEY_METRICS_LIST}
													<optgroup label="{$MODULE_GROUP_ID}">
														{foreach key=RECORD_ID item=RECORD_VALUE from=$MODULE_GROUP}
															<option value="{$RECORD_ID}" data-field-name="{$RECORD_VALUE}">
																{$RECORD_VALUE}<br>
															</option>
														{/foreach}
													</optgroup>
												{/foreach}
											</select>
										{else}
											<select name="fields" class="select2" size="2" multiple="true" style="width: 100%">
												{foreach key=MODULE_GROUP_ID item=MODULE_GROUP from=$ALL_KEY_METRICS_LIST}
													<optgroup label="{$MODULE_GROUP_ID}">
														{foreach key=ALL_RECORD_ID item=ALL_RECORD_VALUE from=$MODULE_GROUP}
															<option {if in_array($ALL_RECORD_VALUE,$KEY_METRICS_LIST) && in_array($ALL_RECORD_ID,array_keys($KEY_METRICS_LIST)) }
																		selected
																	{/if} value="{$ALL_RECORD_ID}" data-field-name="{$ALL_RECORD_VALUE}">
																{$ALL_RECORD_VALUE}<br>
															</option>
														{/foreach}
													</optgroup>
												{/foreach}

												{foreach key=MODULE_GROUP_ID item=MODULE_GROUP from=$ALL_KEY_METRICS_LIST}
													<optgroup label="{$MODULE_GROUP_ID}">
														{foreach key=RECORD_ID item=RECORD_VALUE from=$MODULE_GROUP}
															<option {if in_array($RECORD_ID,$KEY_METRICS_LIST)}
																		selected
																	{/if} value="{$RECORD_ID}" data-field-name="{$RECORD_VALUE}">
																{$RECORD_VALUE}<br>
															</option>
														{/foreach}
													</optgroup>
												{/foreach}
											</select>
										{/if}

									</td>
									<td class="col-lg-4"></td>
								</tr>
							{/if}
						{/if}

						{if $WIDGET_NAME eq 'Gauge'}
							<tr>
								<td class="col-lg-1"></td>
								<td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_TARGET_REPORT','VReports')}</label></td>
								<td class="fieldValue col-lg-5">
									<select name="targetReport" style="width: 100%">
										<option></option>
										{foreach from=$ALL_DETAIL_REPORTS item=REPORT_NAME key=REPORT_ID}
											<option {if $TARGET_REPORT == $REPORT_ID} selected {/if} value="{$REPORT_ID}">{$REPORT_NAME}</option>
										{/foreach}
									</select>
								</td>
								<td class="col-lg-4"></td>
							</tr>

							<tr>
								<td class="col-lg-1"></td>
								<td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_DATA','VReports')}</label></td>
								<td class="fieldValue col-lg-5">
									<select name="dataReport" style="width: 100%">
										<option></option>
										{if $CALCULATION_FIELDS}
											{foreach from=$CALCULATION_FIELDS item=VTRANSLATE_FIELD_NAME key=FIELD_NAME}
												<option {if $DATA_GAUGE eq $FIELD_NAME} selected {/if} value="{$FIELD_NAME}">{$VTRANSLATE_FIELD_NAME}</option>
											{/foreach}
										{/if}
									</select>
								</td>
								<td class="col-lg-4"></td>
							</tr>
						{/if}

						<tr>
							<td class="col-lg-1"></td>
							<td class="fieldLabel col-lg-4"><label id="pick_color" class="pull-right {if $WIDGET_NAME neq 'KeyMetrics'}hideByStep{/if}">{vtranslate('LBL_PICK_COLOR','VReports')}</label></td>
							<td class="fieldValue col-lg-5">
								<div {if $WIDGET_NAME neq 'KeyMetrics'}class="hideByStep"{/if}  id="pick_color">
									<div class="div-inline">
										<div class="color-box" data-value="#8bc34a" style="background: #8bc34a"></div>
										<div class="color-box" data-value="#ffeb3b" style="background: #ffeb3b"></div>
										<div class="color-box" data-value="#ffc107" style="background: #ffc107"></div>
										<div class="color-box" data-value="#ff5722" style="background: #ff5722"></div>
										<div class="color-box" data-value="#e91e63" style="background: #e91e63"></div>

										<div class="color-box" data-value="#259b24" style="background: #259b24"></div>
										<div class="color-box" data-value="#cddc39" style="background: #cddc39"></div>
										<div class="color-box" data-value="#ff9800" style="background: #ff9800"></div>
										<div class="color-box" data-value="#e51c23" style="background: #e51c23"></div>
										<div class="color-box" data-value="#9c27b0" style="background: #9c27b0"></div>
									</div>
									<div class="div-inline">
										<div class="color-box" data-value="#4b0082" style="background: #4b0082"></div>
										<div class="color-box" data-value="#03a9f4" style="background: #03a9f4"></div>
										<div class="color-box" data-value="#00bcd4" style="background: #00bcd4"></div>
										<div class="color-box" data-value="#9e9e9e" style="background: #9e9e9e"></div>
										<div class="color-box" data-value="#607d8b" style="background: #607d8b"></div>

										<div class="color-box" data-value="#673ab7" style="background: #673ab7"></div>
										<div class="color-box" data-value="#5677fc" style="background: #5677fc"></div>
										<div class="color-box" data-value="#009688" style="background: #009688"></div>
										<div class="color-box" data-value="#795548" style="background: #795548"></div>
										<div class="color-box {if $WIDGET_FORM  eq 'Create'}selected-color-box{/if}" data-value="#212121" style="background: #212121"></div>
									</div>
									<input type="hidden" name="selectedColor" id="selectedColor" value="{if $WIDGET}{$WIDGET->get('pick_color')}{/if}">
									<br>
									<center><label><a onclick="VReports_DashBoard_Js.registerEventShowCustomColor()">Add custom color</a></label></center>
								</div>
							</td>
							<td class="col-lg-4"></td>
						</tr>
						{if $WIDGET_FORM != '' &&  ($WIDGET_NAME eq 'MiniList' || $WIDGET_NAME eq 'KeyMetrics' || $WIDGET_NAME eq 'History'|| $WIDGET_NAME eq 'Gauge')}
						<tr>
							<td class="col-lg-1"></td>
							<td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_TITLE','VReports')}</label></td>
							<td class="fieldValue col-lg-5" >
								<input type="text" class="inputElement" name="title_minilist" value="{if $WIDGET}{$WIDGET->get('title')}{/if}" placeholder="Custom title (optional)"/>
							</td>
							<td class="col-lg-4"></td>
						</tr>
						{/if}
						{if $WIDGET_NAME eq 'Gauge'}
							<tr>
								<td class="col-lg-1"></td>
								<td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_SUB_TITLE','VReports')}</label></td>
								<td class="fieldValue col-lg-5" >
									<input type="text" class="inputElement" name="sub_title" value="{if $WIDGET}{VReports_Gauge_Model::getValueByName($WIDGET,'sub_title')}{/if}" placeholder="Sub title (optional)"/>
								</td>
								<td class="col-lg-4"></td>
							</tr>
						{/if}
						<tr>
							<td class="col-lg-1"></td>
							<td class="fieldLabel col-lg-4"><label id="label-picklist-color" class="pull-right hideByStep">{vtranslate('LBL_PICK_COLOR','VReports')}</label></td>
							<td class="fieldValue col-lg-5" >
								<div id="div-picklist-color" class="hideByStep">
									<div class="colorPicker">
										<div class="colorpicker" id="collorpicker_271"
											 style="position: relative; display: block;">
											<div class="colorpicker_color" style="background-color: rgb(255, 0, 255);">
												<div>
													<div style="left: 117px; top: 24px;"></div>
												</div>
											</div>
											<div class="colorpicker_hue">
												<div style="top: 25px;"></div>
											</div>
											<div class="colorpicker_new_color"
												 style="background-color: rgb(214, 47, 214);"></div>
											<div class="colorpicker_current_color"
												 style="background-color: rgb(255, 255, 255);"></div>
											<div class="colorpicker_hex"><input type="text" maxlength="6" size="6"
																				aria-invalid="false"></div>
											<div class="colorpicker_rgb_r colorpicker_field"><input type="text"
																									maxlength="3"
																									size="3"><span></span>
											</div>
											<div class="colorpicker_rgb_g colorpicker_field"><input type="text"
																									maxlength="3"
																									size="3"><span></span>
											</div>
											<div class="colorpicker_rgb_b colorpicker_field"><input type="text"
																									maxlength="3"
																									size="3"><span></span>
											</div>
											<div class="colorpicker_hsb_h colorpicker_field"><input type="text"
																									maxlength="3"
																									size="3"><span></span>
											</div>
											<div class="colorpicker_hsb_s colorpicker_field"><input type="text"
																									maxlength="3"
																									size="3"><span></span>
											</div>
											<div class="colorpicker_hsb_b colorpicker_field"><input type="text"
																									maxlength="3"
																									size="3"><span></span>
											</div>
											<div class="colorpicker_submit"></div>
										</div>
									</div>
									<center><label><a onclick="VReports_DashBoard_Js.registerEventShowNomalColor()">Go back</a></label></center>
								</div>
								<input type="hidden" name="selectedColor" id="selectedColor" value="{if $WIDGET}{$WIDGET->get('pick_color')}{/if}">
							</td>
							<td class="col-lg-4"></td>
						</tr>

						{if $WIDGET_NAME eq 'KeyMetrics'}
							<tr>
								<td class="col-lg-1"></td>
								<td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_SHOW_EMPTY_RECORD','VReports')}</label></td>
								<td class="fieldValue col-lg-5" data-value="{$SHOW_EMPTY_VAL}">
									<select class="select2" name="empty_field" style="width: 100%">
										<option {if $SHOW_EMPTY_VAL eq 1} selected {/if} value="1">No</option>
										<option {if $SHOW_EMPTY_VAL eq 0} selected {/if} value="0">Yes</option>
									</select>
								</td>
								<td class="col-lg-4"></td>
							</tr>
						{/if}

						<tr>
							<td class="col-lg-1"></td>
							<td class="fieldLabel col-lg-4"><label id="refresh_time" class="pull-right{if $WIDGET_MODE neq 'Settings'}  hideByStep" {else}"{/if}">{vtranslate('LBL_REFRESH_TIME','VReports')}</label></td>
							<td class="fieldValue col-lg-5">
								<div id="refresh_time" {if $WIDGET_MODE neq 'Settings'}class="hideByStep"{/if}>
									<select class="select2" name="refresh_time" style="width: 100%">
										<option value="0">Select an option</option>
										<option value="30000">30 Seconds</option>
										<option value="60000">60 Seconds</option>
										<option value="180000">3 Minutes</option>
										<option value="300000">5 Minutes</option>
										<option value="600000">10 Minutes</option>
										<option value="900000">15 Minutes</option>
										<option value="1800000">30 Minutes</option>
										<option value="3600000">1 Hour</option>
										<option value="7200000">2 Hour</option>
									</select>
								</div>
							</td>
							<td class="col-lg-4"></td>
						</tr>
                        {if $WIDGET_MODE neq 'Settings' || $WIDGET_NAME eq 'MiniList' || $WIDGET_NAME eq 'Gauge'}
							{if $WIDGET_NAME eq 'MiniList'}
								<tr>
									<td class="col-lg-1"></td>
									<td class="fieldLabel col-lg-4"><label id="min-height-label" class="pull-right {if $WIDGET_MODE neq 'Settings'} hideByStep {/if}">{vtranslate('LBL_MIN_HEIGHT','VReports')}</label></td>
									<td class="fieldValue col-lg-5" >
										<div id="min-height-value" {if $WIDGET_MODE neq 'Settings'}class="hideByStep"{/if}>
											<input style="padding: 6px" type="number" max="15" min="2" class="inputElement" name="min_height" {if $WIDGET}value="{$WIDGET->get('min_height')}"{/if}/>
										</div>
									</td>
									<td class="col-lg-4"></td>
								</tr>
								<tr>
									<td class="col-lg-1"></td>
									<td class="fieldLabel col-lg-4"><label id="max-height-label" class="pull-right {if $WIDGET_MODE neq 'Settings'} hideByStep {/if}>">{vtranslate('LBL_MAX_HEIGHT','VReports')}</label></td>
									<td class="fieldValue col-lg-5" >
										<div id="max-height-value" {if $WIDGET_MODE neq 'Settings'}class="hideByStep"{/if}>
											<input style="padding: 6px" type="number" class="inputElement" name="max_height" {if $WIDGET}value="{$WIDGET->get('max_height')}"{/if}/>
										</div>
									</td>
									<td class="col-lg-4"></td>
								</tr>
							{/if}
							{if $WIDGET_FORM neq 'Create'}
								<tr>
									<td class="col-lg-1"></td>
									<td class="fieldLabel col-lg-4"><label id="assigned-to-label" class="pull-right {if $WIDGET_MODE neq 'Settings'} hideByStep {/if}>">{vtranslate('LBL_ASSIGNED_TO','VReports')}</label></td>
									<td class="fieldValue col-lg-5" >
										<div id="assigned-to" {if $WIDGET_MODE neq 'Settings'}class="hideByStep"{/if}>
											{assign var=ALL_ACTIVEUSER_LIST value=$USER_MODEL->getAccessibleUsers()}
											{assign var=ALL_ACTIVEGROUP_LIST value=$USER_MODEL->getAccessibleGroups()}
											<select class="inputElement select2" name="filter_assignedto">
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
									</td>
									<td class="col-lg-4"></td>
								</tr>
								<tr>
									<td class="col-lg-1"></td>
									<td class="fieldLabel col-lg-4"><label id="assigned-to-label" class="pull-right {if $WIDGET_MODE neq 'Settings'} hideByStep {/if}>">{vtranslate('Created By','VReports')}</label></td>
									<td class="fieldValue col-lg-5" >
										<div id="created-by" {if $WIDGET_MODE neq 'Settings'}class="hideByStep"{/if}>
											{assign var=ALL_ACTIVEUSER_LIST value=$USER_MODEL->getAccessibleUsers()}
											{assign var=ALL_ACTIVEGROUP_LIST value=$USER_MODEL->getAccessibleGroups()}
											<select class="inputElement select2" name="filter_createdby">
												<option value="">{vtranslate('LBL_SELECT_OPTION','Vtiger')}</option>
												<option value="0">{vtranslate('Logged in user','VReports')}</option>
												<optgroup label="{vtranslate('LBL_USERS')}">
													{foreach key=OWNER_ID item=OWNER_NAME from=$ALL_ACTIVEUSER_LIST}
														<option value="{$OWNER_ID}" data-picklistvalue= '{$OWNER_NAME}'>
															{$OWNER_NAME}
														</option>
													{/foreach}
												</optgroup>
											</select>
										</div>
									</td>
									<td class="col-lg-4"></td>
								</tr>
							{/if}
                        {/if}
						{if $WIDGET_NAME eq 'Gauge'}
							{include file="dashboards/AdditionalOptionSetting.tpl"|vtemplate_path:$MODULE}
						{/if}

						</tbody>
						<input type="hidden" id="translatedModuleNames" value='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($TRANSLATED_MODULE_NAMES))}'>
					</table>
					{if $WIDGET_MODE eq 'Settings'}
						<div class="modal-footer ">
							<center>
								<button class="btn btn-success" type="submit" name="saveSettingWidget"><strong>Save</strong></button>
								<a href="#" class="cancelLink" type="reset" data-dismiss="modal">Cancel</a></center>
						</div>
						{else}
						{include file='ModalFooter.tpl'|@vtemplate_path:$MODULE}
					{/if}

				</form>
			</div>
		</div>
	{elseif $WIZARD_STEP eq 'step2'}
		<option></option>
		{foreach from=$ALLFILTERS item=FILTERS key=FILTERGROUP}
			<optgroup label="{$FILTERGROUP}">
				{*<option name="AllField" value="AllField" class="text-bold">None</option>*}
				{foreach from=$FILTERS item=FILTER key=FILTERNAME}
					<option value="{$FILTER->getId()}">{$FILTER->get('viewname')}</option>
				{/foreach}
			</optgroup>
		{/foreach}
	{elseif $WIZARD_STEP eq 'step3'}
		<select name="fields" class="select2" size="2" multiple="true" style="width: 100%">
			{foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
				{*in case customer want to add option group for field, un comment line below*}
				<optgroup label='{vtranslate($BLOCK_LABEL)}'>
					{foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
						{*To not show star field in filter select view*}
						{if $FIELD_MODEL->getDisplayType() == '6'}
							{continue}
						{/if}
						{if $FIELD_MODEL->isMandatory()}
							{array_push($MANDATORY_FIELDS, $FIELD_MODEL->getCustomViewColumnName())}
						{/if}
						{assign var=FIELD_MODULE_NAME value=$FIELD_MODEL->getModule()->getName()}
						<option value="{$FIELD_NAME}" data-field-name="{$FIELD_NAME}"
								{if in_array($FIELD_NAME, $HEADER_FIELDS)}
									selected
								{elseif (!$RECORD_ID) && ($FIELD_MODEL->isSummaryField() || $FIELD_MODEL->isHeaderField()) && ($FIELD_MODULE_NAME eq $SOURCE_MODULE) && (!(preg_match("/\([A-Za-z_0-9]* \; \([A-Za-z_0-9]*\) [A-Za-z_0-9]*\)/", $FIELD_NAME))) && $NUMBER_OF_COLUMNS_SELECTED < $MAX_ALLOWED_COLUMNS}
									selected
									{assign var=NUMBER_OF_COLUMNS_SELECTED value=$NUMBER_OF_COLUMNS_SELECTED + 1}
								{/if}
						>{Vtiger_Util_Helper::toSafeHTML(vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE))}
							{if $FIELD_MODEL->isMandatory() eq true} <span>*</span> {/if}
						</option>
					{/foreach}
				</optgroup>
			{/foreach}
		</select>
	{elseif $GAUGE_WIZARD_STEP eq 'step2'}
		<option></option>
		{foreach from=$CALCULATION_FIELDS item=VTRANSLATE_FIELD_NAME key=FIELD_NAME}
			<option value="{$FIELD_NAME}">{$VTRANSLATE_FIELD_NAME}</option>
		{/foreach}
	{/if}
{/strip}