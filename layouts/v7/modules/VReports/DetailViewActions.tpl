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
	<div class="listViewPageDiv">
		<div class="reportHeader">
			<div class="row">
				<div class="col-lg-4 detailViewButtoncontainer">
					<div class="btn-toolbar">
						<div class="btn-group">
						{foreach item=DETAILVIEW_LINK from=$DETAILVIEW_ACTIONS}
							{assign var=LINK_URL value=$DETAILVIEW_LINK->getUrl()}
							{assign var=LINK_NAME value=$DETAILVIEW_LINK->getLabel()}
							{assign var=LINK_ICON_CLASS value=$DETAILVIEW_LINK->get('linkiconclass')}
								{if $LINK_ICON_CLASS eq 'vtGlyph vticon-attach'}
									<div class="btn-group">
								{/if}
								<button {if $LINK_URL} onclick='window.location.href = "{$LINK_URL}"' {/if} type="button" 
										class="cursorPointer btn btn-default {$DETAILVIEW_LINK->get('customclass')} dropdown-toggle"
										title="{$LINK_NAME}" data-toggle="dropdown"
											data-dashboard-tab-count='{count($DASHBOARD_TABS)}'>
									{if $LINK_NAME} {$LINK_NAME}{/if}
									{if $LINK_ICON_CLASS}
										{if $LINK_ICON_CLASS eq 'icon-pencil'}&nbsp;&nbsp;&nbsp;{/if}
										<i class="fa {if $LINK_ICON_CLASS eq 'icon-pencil'}fa-pencil{elseif $LINK_ICON_CLASS eq 'vtGlyph vticon-attach'}
										vicon-pin{/if}" style="font-size: 13px;"></i>
									{/if}
								</button>
									{if $LINK_ICON_CLASS eq 'vtGlyph vticon-attach'}
										<ul class='dropdown-menu dashBoardTabMenu'>
											<li class="dropdown-header popover-title">
												{vtranslate('LBL_DASHBOARD',$MODULE)}
											</li>
											{foreach from=$DASHBOARD_TABS item=TAB_INFO}
												<li class='dashBoardTab' data-tab-name="{$TAB_INFO.tabname}" data-tab-id='{$TAB_INFO.id}'>
													<a href='javascript:void(0)'> {$TAB_INFO.tabname} <i class="fa {if $REPORT_MODEL->isPinnedToDashboard($TAB_INFO.id)}vicon-unpin{else}vicon-pin{/if}" style="font-size: 13px;float:right;margin-top: 3%;"></i></a>
												</li>
											{/foreach}
										</ul>
									{/if}
								 {if $LINK_ICON_CLASS eq 'vtGlyph vticon-attach'}
									</div>
								 {/if}
								{/foreach}
						</div>
					</div>
				</div>
				<div class="col-lg-4 textAlignCenter">
					<h3 name="reportName" class="marginTop0px">{$REPORT_NAME}</h3>
					{if $REPORT_MODEL->getReportType() == 'tabular' || $REPORT_MODEL->getReportType() == 'summary'}
						<div id="noOfRecords">{vtranslate('LBL_NO_OF_RECORDS',$MODULE)} <span id="countValue">{$COUNT}</span></div>
						{if $COUNT > $REPORT_LIMIT}
							<span class="redColor" id="moreRecordsText"> ({vtranslate('LBL_MORE_RECORDS_TXT',$MODULE)})</span>
						{else}
							<span class="redColor hide" id="moreRecordsText"> ({vtranslate('LBL_MORE_RECORDS_TXT',$MODULE)})</span>
						{/if}
					{/if}
				</div>
				<div class='col-lg-4 detailViewButtoncontainer'>
					<span class="pull-right">
						<div class="btn-toolbar">
							<div class="btn-group">
								{foreach item=DETAILVIEW_LINK from=$DETAILVIEW_LINKS}
									{assign var=LINKNAME value=$DETAILVIEW_LINK->getLabel()}
									<button class="btn btn-default {if $DETAILVIEW_LINK->get('mode')}"{else}reportActions"{/if}
										{if $DETAILVIEW_LINK->get('mode')}
											data-mode="{$DETAILVIEW_LINK->get('mode')}" onclick="{$DETAILVIEW_LINK->get('onlick')}"
										{else}
											name="{$LINKNAME}" data-href="{$DETAILVIEW_LINK->getUrl()}&source={$REPORT_MODEL->getReportType()}
										{/if}">
											{$LINKNAME}
									</button>
								{/foreach}
							</div>
						</div>
					</span>
				</div>
			</div>
		</div>
	</div>
{/strip}