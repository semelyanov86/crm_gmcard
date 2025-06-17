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
<div id="addWidgetContainer" class="modelContainer modal-dialog">
	<div class="modal-content" style="width: 100%">
		<div class="table-addwidget-scroller">
			<table name="listAddWidget" class="table no-border">
				<thead>
					<tr>
						<td width="25%"><h4 class="lists-header">{vtranslate('LBL_STANDARD_WIDGETS',$MODULE_NAME)}</h4></td>
						<td width="25%"><h4 class="lists-header">{vtranslate('LBL_CHART_REPORTS',$MODULE_NAME)}</h4></td>
						<td width="25%"><h4 class="lists-header">{vtranslate('LBL_DETAIL_REPORTS',$MODULE_NAME)}</h4></td>
						<td width="25%"><h4 class="lists-header">{vtranslate('LBL_SHARED_REPORTS',$MODULE_NAME)}</h4></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="100%">
							<hr>
						</td>
					</tr>
					<tr>
						<td width="25%">
							{assign var="MINILISTWIDGET" value=""}
							{if $SELECTABLE_WIDGETS['other']}
								{foreach from=$SELECTABLE_WIDGETS['other'] item=WIDGET}
									{if $WIDGET->getName() != 'MiniList' && $MODULE_NAME == 'VReports'}
										<div class="chartReport">
											<a id="addWidget" class="filterName listViewFilterElipsis" name="{$WIDGET->getName()}" onclick="VReports_DashBoard_Js.addWidget(this, '{$WIDGET->getUrl()}')" href="javascript:void(0);"
											   data-linkid="{$WIDGET->get('linkid')}" data-name="{$WIDGET->getName()}" {if $WIDGET->getName() eq 'Gauge'}data-width = '2' data-height = '2'{else} data-width="{$WIDGET->getWidth()}" data-height="{$WIDGET->getHeight()}{/if}">
												{vtranslate($WIDGET->getTitle(), $MODULE_NAME)}</a>
										</div>
									{else}
										<div class="chartReport">
											<a id="addWidget" class="filterName listViewFilterElipsis" name="{$WIDGET->getName()}" onclick="VReports_DashBoard_Js.addMiniListWidget(this, '{$WIDGET->getUrl()}')" href="javascript:void(0);"
											   data-linkid="{$WIDGET->get('linkid')}" data-name="{$WIDGET->getName()}" data-width="{$WIDGET->getWidth()}" data-height="{$WIDGET->getHeight()}">
												{vtranslate($WIDGET->getTitle(), $MODULE_NAME)}</a>
										</div>
									{/if}
								{/foreach}
							{else}
								<var class="italic_small_size">No Records</var>
							{/if}
						</td>
						<td width="25%">
							{if $SELECTABLE_WIDGETS['myWidget']}
								{foreach item=FOLDER from=$FOLDERS name="folderview"}
									{assign var=VIEWNAME value={vtranslate($FOLDER->getName(),$MODULE)}}
									{assign var="FOLDERID" value=$FOLDER->getId()}
									<div data-filter-id={$FOLDERID}>
										<h6 class='filterName' data-filter-id={$FOLDERID}>
											{if {$VIEWNAME|strlen > 25} }{$VIEWNAME|substr:0:25}..{else}{$VIEWNAME}{/if}
										</h6>
										<ul class="chartReport myWidget lists-menu">
											{foreach from=$SELECTABLE_WIDGETS['myWidget'] item=WIDGET}
												{if $WIDGET->get('folderid') eq $FOLDERID}
													{if $WIDGET->getName() eq 'MiniList'}
														{assign var="MINILISTWIDGET" value=$WIDGET} {* Defer to display as a separate group *}
													{else}
														{if $WIDGET->get('report_type') eq 'Chart'}
															{assign var="ICON_CLASS" value='fa fa-pie-chart'}
														{elseif $WIDGET->get('report_type') eq 'Pivot'}
															{assign var="ICON_CLASS" value='fa fa-table'}
														{elseif $WIDGET->get('report_type') eq 'SqlReport'}
															{assign var="ICON_CLASS" value='vicon-list'}
														{/if}
														<li style="font-size:12px;" class="chartReport {if $WIDGET->get('is_show') == true}hide{/if}" data-id="{$WIDGET->get('reportid')}">
															<span class="{$ICON_CLASS}" style="font-size:9px;"></span>&nbsp;
															<a id="addWidget" class="filterName listViewFilterElipsis" onclick="VReports_DashBoard_Js.addWidget(this, '{$WIDGET->getUrl()}')" href="javascript:void(0);"
															   data-linkid="{$WIDGET->get('linkid')}" data-name="{$WIDGET->getName()}" data-width="{$WIDGET->getWidth()}" data-height="{$WIDGET->getHeight()}" title="{vtranslate($WIDGET->getTitle(), $MODULE_NAME)}">
																{if {vtranslate($WIDGET->getTitle(), $MODULE_NAME)|strlen > 25} }{vtranslate($WIDGET->getTitle(), $MODULE_NAME)|substr:0:25}...{else}{vtranslate($WIDGET->getTitle(), $MODULE_NAME)}{/if}</a>
														</li>
													{/if}
												{/if}
											{/foreach}
										</ul>
									</div>
								{/foreach}
							{else}
								<var class="italic_small_size">No Records</var>
							{/if}
						</td>
						<td width="25%">
							{if $SELECTABLE_WIDGETS['detail']}
								{foreach item=FOLDER from=$FOLDERS name="folderview"}
									{assign var=VIEWNAME value={vtranslate($FOLDER->getName(),$MODULE)}}
									{assign var="FOLDERID" value=$FOLDER->getId()}
									<div data-filter-id={$FOLDERID}>
										<h6 class='filterName' data-filter-id={$FOLDERID}>
											{if {$VIEWNAME|strlen > 25} }{$VIEWNAME|substr:0:25}..{else}{$VIEWNAME}{/if}
										</h6>
										<ul class="chartReport myWidget lists-menu">
											{assign var="ICON_CLASS" value='vicon-detailreport'}
											{foreach from=$SELECTABLE_WIDGETS['detail'] item=WIDGET}
												{if $WIDGET->get('folderid') eq $FOLDERID}
												<li style="font-size:12px;" class="chartReport"><span class="{$ICON_CLASS}" style="font-size:9px;"></span>
													<a id="addWidget" class="filterName listViewFilterElipsis" onclick="VReports_DashBoard_Js.addWidget(this, '{$WIDGET->getUrl()}')" href="javascript:void(0);"
													   data-linkid="{$WIDGET->get('linkid')}" data-name="{$WIDGET->getName()}" data-width="{$WIDGET->getWidth()}" data-height="{$WIDGET->getHeight()}"title="{vtranslate($WIDGET->getTitle(), $MODULE_NAME)}">
														{if {vtranslate($WIDGET->getTitle(), $MODULE_NAME)|strlen > 25} }{vtranslate($WIDGET->getTitle(), $MODULE_NAME)|substr:0:25}...{else}{vtranslate($WIDGET->getTitle(), $MODULE_NAME)}{/if}</a>
												</li>
												{/if}
											{/foreach}
										</ul>
									</div>
								{/foreach}
							{else}
								<var class="italic_small_size">No Records</var>
							{/if}
						</td>
						<td width="25%">
							{if $SELECTABLE_WIDGETS['share']}
								{if $WIDGET->get('report_type') eq 'Chart'}
									{assign var="ICON_CLASS" value='fa fa-pie-chart'}
								{elseif $WIDGET->get('report_type') eq 'Tabular'}
									{assign var="ICON_CLASS" value='vicon-detailreport'}
								{elseif $WIDGET->get('report_type') eq 'Pivot'}
									{assign var="ICON_CLASS" value='fa fa-table'}
								{elseif $WIDGET->get('report_type') eq 'SqlReport'}
									{assign var="ICON_CLASS" value='vicon-list'}
									{if $USER_NAME neq 'admin'}
										{assign var="BLOCK_LINK" value='block'}
									{/if}
									{assign var="ICON_CLASS" value='vicon-list'}
								{/if}
								{foreach item=FOLDER from=$FOLDERS name="folderview"}
									{assign var=VIEWNAME value={vtranslate($FOLDER->getName(),$MODULE)}}
									{assign var="FOLDERID" value=$FOLDER->getId()}
									<div data-filter-id={$FOLDERID}>
										<h6 class='filterName' data-filter-id={$FOLDERID}>
											{if {$VIEWNAME|strlen > 25} }{$VIEWNAME|substr:0:25}..{else}{$VIEWNAME}{/if}
										</h6>
										<ul class="chartReport myWidget lists-menu">
											{foreach from=$SELECTABLE_WIDGETS['share'] item=WIDGET}
												<li style="font-size:12px;" class="chartReport"><span class="{$ICON_CLASS}" style="font-size:9px;"></span>
													<a id="addWidget" class="filterName listViewFilterElipsis" onclick="VReports_DashBoard_Js.addWidget(this, '{$WIDGET->getUrl()}')" href="javascript:void(0);"
													   data-linkid="{$WIDGET->get('linkid')}" data-name="{$WIDGET->getName()}" data-width="{$WIDGET->getWidth()}" data-height="{$WIDGET->getHeight()}" title="{vtranslate($WIDGET->getTitle(), $MODULE_NAME)}">
														{if {vtranslate($WIDGET->getTitle(), $MODULE_NAME)|strlen > 25} }{vtranslate($WIDGET->getTitle(), $MODULE_NAME)|substr:0:25}...{else}{vtranslate($WIDGET->getTitle(), $MODULE_NAME)}{/if}</a>
												</li>
											{/foreach}
										</ul>
									</div>
								{/foreach}
							{else}
								<var class="italic_small_size">No Records</var>
							{/if}
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</div>
