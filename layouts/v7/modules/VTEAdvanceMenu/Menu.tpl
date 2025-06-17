{*/* * *******************************************************************************
* The content of this file is subject to the VTE Advance Menu ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

{assign var='ESSENTIALS' value=$MENU_SETTING.ESSENTIALS}
{assign var='MARKETING' value=$MENU_SETTING.MARKETING}
{assign var='SALES' value=$MENU_SETTING.SALES}
{assign var='SUPPORT' value=$MENU_SETTING.SUPPORT}
{assign var='INVENTORY' value=$MENU_SETTING.INVENTORY}
{assign var='PROJECTS' value=$MENU_SETTING.PROJECTS}
{assign var='TOOLS' value=$MENU_SETTING.TOOLS}
<div class="dropdown vte-advance-menu-nav col-lg-3 col-md-2 col-sm-2 col-xs-2 pull-right" style="padding: 0; margin-right: -15px;">
	<input type="hidden" id="vte-advance-menu-id" value="{$MENU_ID}" />
	<link type="text/css" rel="stylesheet" href="layouts/v7/modules/VTEAdvanceMenu/resources/VTEAdvanceMenu.css" media="screen">
	<button class="btn btn-primary dropdown-toggle vte-advance-menu-nav-btn" type="button" data-toggle="dropdown" style="display: none;">
		{vtranslate('LBL_MENU_BTN', $MODULE_NAME)}
	</button>
	<div class="dropdown-menu dropdown-menu-left">
		<div class="container-fluid" id="vte-advance-menu-nav-hide">
			<div class="row">
                {if $ESSENTIALS.items|count gt 0}
				<div class="col-lg-3 essentials-container">
					<div class="row">
						<div class="col-lg-12 group-header">
							<div class="row">
								<div class="col-lg-2">
									<i class="{$ESSENTIALS.icon_class}" aria-hidden="true"></i>
								</div>
								<div class="col-lg-10">
									<span class="group-name">{vtranslate($ESSENTIALS.label, $MODULE_NAME)}</span>
								</div>
							</div>
							<div class="row">
								<div class="divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
							</div>
						</div>
						<div class="col-lg-12 group-menu">
							<div class="row" data-appname="ESSENTIALS">
                                {foreach item=MENU_ITEM from=$ESSENTIALS.items}
                                    {if $MENU_ITEM.type eq 'Separator'}
										<div class="vte-menu-item textOverflowEllipsis  textAlignLeft divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
									{else}
										<div class="vte-menu-item textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">
											<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</a>
										</div>
									{/if}
                                {/foreach}
							</div>
						</div>
					</div>
				</div>
				{/if}
				<div class="col-lg-9 other-container">
					{if $MARKETING.items|count gt 0 || $SALES.items|count gt 0}
					<div class="row">
                        {if $MARKETING.items|count gt 0}
						<div class="col-lg-6 marketing-container">
							<div class="row">
								<div class="col-lg-12 group-header">
									<div class="row">
										<div class="col-lg-2">
											<i class="{$MARKETING.icon_class}" aria-hidden="true"></i>
										</div>
										<div class="col-lg-10">
											<span class="group-name">{vtranslate($MARKETING.label, $MODULE_NAME)}</span>
										</div>
									</div>
									<div class="divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
								</div>
								<div class="col-lg-12 group-menu">
									<div class="row" data-appname="MARKETING">
                                        {foreach item=MENU_ITEM from=$MARKETING.items}
                                            {if $MENU_ITEM.type eq 'Separator'}
												<div class="vte-menu-item col-lg-6" data-type="separator">
													<div class="textOverflowEllipsis  textAlignLeft divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
												</div>
                                            {else}
												<div class="vte-menu-item col-lg-6 textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">
                                                    {if $MENU_ITEM.type eq 'Module'}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</a>
                                                    {else}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</a>
                                                    {/if}
												</div>
                                            {/if}
                                        {/foreach}
									</div>
								</div>
							</div>
						</div>
						{/if}
                        {if $SALES.items|count gt 0}
						<div class="col-lg-6 sales-container">
							<div class="row">
								<div class="col-lg-12 group-header">
									<div class="row">
										<div class="col-lg-2">
											<i class="{$SALES.icon_class}" aria-hidden="true"></i>
										</div>
										<div class="col-lg-10">
											<span class="group-name">{vtranslate($SALES.label, $MODULE_NAME)}</span>
										</div>
									</div>
									<div class="divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
								</div>
								<div class="col-lg-12 group-menu">
									<div class="row" data-appname="SALES">
                                        {foreach item=MENU_ITEM from=$SALES.items}
                                            {if $MENU_ITEM.type eq 'Separator'}
												<div class="vte-menu-item col-lg-6" data-type="separator">
													<div class="textOverflowEllipsis  textAlignLeft divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
												</div>
                                            {else}
												<div class="vte-menu-item col-lg-6 textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">
                                                    {if $MENU_ITEM.type eq 'Module'}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</a>
                                                    {else}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</a>
                                                    {/if}
												</div>
                                            {/if}
                                        {/foreach}
									</div>
								</div>
							</div>
						</div>
						{/if}
					</div>
					{/if}
                    {if $SUPPORT.items|count gt 0 || $INVENTORY.items|count gt 0}
					<div class="row">
                        {if $SUPPORT.items|count gt 0}
						<div class="col-lg-6 support-container">
							<div class="row">
								<div class="col-lg-12 group-header">
									<div class="row">
										<div class="col-lg-2">
											<i class="{$SUPPORT.icon_class}" aria-hidden="true"></i>
										</div>
										<div class="col-lg-10">
											<span class="group-name">{vtranslate($SUPPORT.label, $MODULE_NAME)}</span>
										</div>
									</div>
									<div class="divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
								</div>
								<div class="col-lg-12 group-menu">
									<div class="row" data-appname="SUPPORT">
                                        {foreach item=MENU_ITEM from=$SUPPORT.items}
                                            {if $MENU_ITEM.type eq 'Separator'}
												<div class="vte-menu-item col-lg-6" data-type="separator">
													<div class="textOverflowEllipsis  textAlignLeft divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
												</div>
                                            {else}
												<div class="vte-menu-item col-lg-6 textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">
                                                    {if $MENU_ITEM.type eq 'Module'}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</a>
                                                    {else}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</a>
                                                    {/if}
												</div>
                                            {/if}
                                        {/foreach}
									</div>
								</div>
							</div>
						</div>
						{/if}
                        {if $INVENTORY.items|count gt 0}
						<div class="col-lg-6 inventory-container">
							<div class="row">
								<div class="col-lg-12 group-header">
									<div class="row">
										<div class="col-lg-2">
											<i class="{$INVENTORY.icon_class}" aria-hidden="true"></i>
										</div>
										<div class="col-lg-10">
											<span class="group-name">{vtranslate($INVENTORY.label, $MODULE_NAME)}</span>
										</div>
									</div>
									<div class="divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
								</div>
								<div class="col-lg-12 group-menu">
									<div class="row" data-appname="INVENTORY">
                                        {foreach item=MENU_ITEM from=$INVENTORY.items}
                                            {if $MENU_ITEM.type eq 'Separator'}
												<div class="vte-menu-item col-lg-6" data-type="separator">
													<div class="textOverflowEllipsis  textAlignLeft divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
												</div>
                                            {else}
												<div class="vte-menu-item col-lg-6 textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">
                                                    {if $MENU_ITEM.type eq 'Module'}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</a>
                                                    {else}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</a>
                                                    {/if}
												</div>
                                            {/if}
                                        {/foreach}
									</div>
								</div>
							</div>
						</div>
						{/if}
					</div>
					{/if}
                    {if $PROJECTS.items|count gt 0 || $TOOLS.items|count gt 0}
					<div class="row">
                        {if $PROJECTS.items|count gt 0}
						<div class="col-lg-6 projects-container">
							<div class="row">
								<div class="col-lg-12 group-header">
									<div class="row">
										<div class="col-lg-2">
											<i class="{$PROJECTS.icon_class}" aria-hidden="true"></i>
										</div>
										<div class="col-lg-10">
											<span class="group-name">{vtranslate($PROJECTS.label, $MODULE_NAME)}</span>
										</div>
									</div>
									<div class="divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
								</div>
								<div class="col-lg-12 group-menu">
									<div class="row" data-appname="PROJECTS">
                                        {foreach item=MENU_ITEM from=$PROJECTS.items}
                                            {if $MENU_ITEM.type eq 'Separator'}
												<div class="vte-menu-item col-lg-6" data-type="separator">
													<div class="textOverflowEllipsis  textAlignLeft divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
												</div>
                                            {else}
												<div class="vte-menu-item col-lg-6 textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">
                                                    {if $MENU_ITEM.type eq 'Module'}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</a>
                                                    {else}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</a>
                                                    {/if}
												</div>
                                            {/if}
                                        {/foreach}
									</div>
								</div>
							</div>
						</div>
						{/if}
                        {if $TOOLS.items|count gt 0}
						<div class="col-lg-6 tools-container">
							<div class="row">
								<div class="col-lg-12 group-header">
									<div class="row">
										<div class="col-lg-2">
											<i class="{$TOOLS.icon_class}" aria-hidden="true"></i>
										</div>
										<div class="col-lg-10">
											<span class="group-name">{vtranslate($TOOLS.label, $MODULE_NAME)}</span>
										</div>
									</div>
									<div class="divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
								</div>
								<div class="col-lg-12 group-menu">
									<div class="row" data-appname="TOOLS">
                                        {foreach item=MENU_ITEM from=$TOOLS.items}
                                            {if $MENU_ITEM.type eq 'Separator'}
												<div class="vte-menu-item col-lg-6" data-type="separator">
													<div class="textOverflowEllipsis  textAlignLeft divider" role="separator" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}"></div>
												</div>
                                            {else}
												<div class="vte-menu-item col-lg-6 textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">
                                                    {if $MENU_ITEM.type eq 'Module'}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</a>
                                                    {else}
														<a class="menu-item-name" href="{$MENU_ITEM.link}" {if $MENU_ITEM.type eq 'Link'}target="_blank" {/if} title="{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</a>
                                                    {/if}
												</div>
                                            {/if}
                                        {/foreach}
									</div>
								</div>
							</div>
						</div>
						{/if}
					</div>
					{/if}
				</div>
			</div>
		</div>
		{if $USER_MODEL->isAdminUser()}
		<div class="container-fluid vte-advance-menu-container-footer">
			<div class="row">
				<div class="col-lg-3 footer-left">
					<a href="index.php?module=VTEAdvanceMenu&parent=Settings&view=Settings" rel="tooltip" title="" data-original-title="{vtranslate('LBL_ICON_VTEADVANCEMENU_TOOLTIP_CONTENT', $MODULE_NAME)}">
						<i class="fa fa-bars" aria-hidden="true"></i>
					</a>
					<a href="index.php?module=Users&parent=Settings&view=List" rel="tooltip" title="" data-original-title="{vtranslate('LBL_ICON_USER_TOOLTIP_CONTENT', $MODULE_NAME)}">
						<i class="fa fa-user" aria-hidden="true"></i>
					</a>
					<a href="index.php?module=ModuleManager&parent=Settings&view=List" rel="tooltip" title="" data-original-title="{vtranslate('LBL_ICON_MODULE_TOOLTIP_CONTENT', $MODULE_NAME)}">
						<i class="fa fa-cog" aria-hidden="true"></i>
					</a>
					<a href="index.php?module=LayoutEditor&parent=Settings&view=Index" rel="tooltip" title="" data-original-title="{vtranslate('LBL_ICON_FIELD_LAYOUT_TOOLTIP_CONTENT', $MODULE_NAME)}">
						<i class="fa fa-columns" aria-hidden="true"></i>
					</a>
					<a href="index.php?module=Workflows&parent=Settings&view=List" rel="tooltip" title="" data-original-title="{vtranslate('LBL_ICON_WORKFLOW_TOOLTIP_CONTENT', $MODULE_NAME)}">
						<i class="fa fa-life-ring" aria-hidden="true"></i>
					</a>
					<a href="index.php?parent=Settings&module=Picklist&view=Index" rel="tooltip" title="" data-original-title="{vtranslate('LBL_ICON_PICKLIST_FIELD_TOOLTIP_CONTENT', $MODULE_NAME)}">
						<i class="fa fa-list-ul" aria-hidden="true"></i>
					</a>
					<a href="index.php?module=ExtensionStore&parent=Settings&view=ExtensionStore" rel="tooltip" data-original-title="{vtranslate('LBL_FOOTER_EXTENSION_STORE_LABEL', $MODULE_NAME)}">
						<i class="fa fa-shopping-cart" aria-hidden="true"></i>&nbsp;
					</a>
				</div>
				<div class="col-lg-5 footer-middle">
					{if $NUMBER_NEW_ENTITY_MODULES gt 0}
						<div class="new-modules-notification">
							<a href="javascript:void(0);" class="add-new-module-to-menu">{$NUMBER_NEW_ENTITY_MODULES}&nbsp;{vtranslate('LBL_FOOTER_MIDDLE_NEW_MODULES_INSTALLED', $MODULE_NAME)}</a>
						</div>
					{/if}
				</div>
				<div class="col-lg-4 footer-right">
					{if $VTE_STORE_MODULE_IS_ACTIVE}
					<a class="btn btn-default" href="index.php?module=VTEStore&parent=Settings&view=Settings">
						<i class="fa fa-shopping-cart" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate('LBL_FOOTER_EXTENSION_PACK_LABEL', $MODULE_NAME)}</a>
                    {/if}
					<a class="btn btn-default" href="index.php?module=Vtiger&parent=Settings&view=Index">
						<i class="fa fa-cogs" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate('LBL_FOOTER_SETTING_LABEL', $MODULE_NAME)}</a>
				</div>
			</div>
		</div>
		{/if}
	</div>
</div>