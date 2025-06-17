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
<div class="vte-advance-menu-container container-fluid listViewPageDiv detailViewContainer" id="listViewContent">
    <div class="row">
        <div class=" vt-default-callout vt-info-callout">
            <h4 class="vt-callout-header"><span class="fa fa-info-circle"></span>{vtranslate('LBL_INFO', $QUALIFIED_MODULE)}</h4>
            <p>{vtranslate('LBL_INFO_DETAILS', $QUALIFIED_MODULE)}</p>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 essentials-container">
            <div class="row">
                <div class="col-lg-12 group-header">
                    <div class="row app-DEFAULT">
                        <i class="fa fa-pencil" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}" data-groupid="{$ESSENTIALS.groupid}"></i>
                        <div class="col-lg-2">
                            <i class="{$ESSENTIALS.icon_class}" aria-hidden="true"></i>
                        </div>
                        <div class="col-lg-10">
                            <span class="group-name">{vtranslate($ESSENTIALS.label, $QUALIFIED_MODULE)}</span>
                            <div class="dropdown pull-right add-button-container" data-appname="ESSENTIALS" data-menuid="{$ESSENTIALS.menuid}" data-groupid="{$ESSENTIALS.groupid}">
                                <button class="btn addButton btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-plus" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_BTN', $QUALIFIED_MODULE)}<span class="caret"></span></button>
                                <ul class="dropdown-menu">
                                    <li><a href="#" data-mode="showAddModule">{vtranslate('LBL_ADD_MODULE', $QUALIFIED_MODULE)}</a></li>
                                    <li><a href="#" data-mode="showAddLink">{vtranslate('LBL_ADD_LINK', $QUALIFIED_MODULE)}</a></li>
                                    <li><a href="#" data-mode="showAddFilter">{vtranslate('LBL_ADD_FILTER', $QUALIFIED_MODULE)}</a></li>
                                    <li><a href="#" data-mode="showAddSeparator">{vtranslate('LBL_ADD_SEPARATOR', $QUALIFIED_MODULE)}</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12 group-menu" data-menuid="{$ESSENTIALS.menuid}" data-groupid="{$ESSENTIALS.groupid}">
                    <div class="row" data-appname="ESSENTIALS">
                        {foreach item=MENU_ITEM from=$ESSENTIALS.items}
                            <div class="vte-menu-item-sort">
                                <div class="vte-menu-item noConnect " data-itemid="{$MENU_ITEM.itemid}" data-module="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}" data-type="{$MENU_ITEM.type}">
                                    <i data-appname="ESSENTIALS" class="fa fa-times pull-right whiteIcon VTEAdvanceMenuRemoveItem" style="margin: 5%;"></i>
                                    <div class="VTEAdvanceMenuItem VTEAdvanceMenuModuleItem">
                                        <span class="pull-left marginRight10px ">
                                            <img class="alignMiddle cursorDrag" src="layouts/v7/skins/images/drag.png">
                                        </span>
                                        <span>
                                            <i class="vicon-{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)|lower} marginRight10px pull-left"></i>
                                        </span>
                                        {if $MENU_ITEM.type eq 'Module'}
                                            <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</div>
                                        {else}
                                            <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</div>
                                        {/if}
                                    </div>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-9 other-container">
            <div class="row">
                <div class="col-lg-6 marketing-container">
                    <div class="row">
                        <div class="col-lg-12 group-header">
                            <div class="row app-MARKETING">
                                <i class="fa fa-pencil" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}" data-groupid="{$MARKETING.groupid}"></i>
                                <div class="col-lg-2">
                                    <i class="{$MARKETING.icon_class}" aria-hidden="true"></i>
                                </div>
                                <div class="col-lg-10">
                                    <span class="group-name">{vtranslate($MARKETING.label, $QUALIFIED_MODULE)}</span>
                                    <div class="dropdown pull-right add-button-container" data-appname="MARKETING" data-menuid="{$MARKETING.menuid}" data-groupid="{$MARKETING.groupid}">
                                        <button class="btn addButton btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fa fa-plus" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_BTN', $QUALIFIED_MODULE)}<span class="caret"></span></button>
                                        <ul class="dropdown-menu">
                                            <li><a href="#" data-mode="showAddModule">{vtranslate('LBL_ADD_MODULE', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddLink">{vtranslate('LBL_ADD_LINK', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddFilter">{vtranslate('LBL_ADD_FILTER', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddSeparator">{vtranslate('LBL_ADD_SEPARATOR', $QUALIFIED_MODULE)}</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 group-menu" data-menuid="{$MARKETING.menuid}" data-groupid="{$MARKETING.groupid}">
                            <div class="row" data-appname="MARKETING">
                                {foreach item=MENU_ITEM from=$MARKETING.items}
                                    <div class="vte-menu-item-sort">
                                        <div class="vte-menu-item noConnect " data-itemid="{$MENU_ITEM.itemid}" data-module="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}" data-type="{$MENU_ITEM.type}">
                                            <i data-appname="MARKETING" class="fa fa-times pull-right whiteIcon VTEAdvanceMenuRemoveItem" style="margin: 5%;"></i>
                                            <div class="VTEAdvanceMenuItem VTEAdvanceMenuModuleItem">
                                            <span class="pull-left marginRight10px ">
                                                <img class="alignMiddle cursorDrag" src="layouts/v7/skins/images/drag.png">
                                            </span>
                                                <span>
                                                <i class="vicon-{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)|lower} marginRight10px pull-left"></i>
                                            </span>
                                                {if $MENU_ITEM.type eq 'Module'}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</div>
                                                {else}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</div>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 sales-container">
                    <div class="row">
                        <div class="col-lg-12 group-header">
                            <div class="row app-SALES">
                                <i class="fa fa-pencil" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}" data-groupid="{$SALES.groupid}"></i>
                                <div class="col-lg-2">
                                    <i class="{$SALES.icon_class}" aria-hidden="true"></i>
                                </div>
                                <div class="col-lg-10">
                                    <span class="group-name">{vtranslate($SALES.label, $QUALIFIED_MODULE)}</span>
                                    <div class="dropdown pull-right add-button-container" data-appname="SALES" data-menuid="{$SALES.menuid}" data-groupid="{$SALES.groupid}">
                                        <button class="btn addButton btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fa fa-plus" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_BTN', $QUALIFIED_MODULE)}<span class="caret"></span></button>
                                        <ul class="dropdown-menu">
                                            <li><a href="#" data-mode="showAddModule">{vtranslate('LBL_ADD_MODULE', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddLink">{vtranslate('LBL_ADD_LINK', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddFilter">{vtranslate('LBL_ADD_FILTER', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddSeparator">{vtranslate('LBL_ADD_SEPARATOR', $QUALIFIED_MODULE)}</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 group-menu" data-menuid="{$SALES.menuid}" data-groupid="{$SALES.groupid}">
                            <div class="row" data-appname="SALES">
                                {foreach item=MENU_ITEM from=$SALES.items}
                                    <div class=" vte-menu-item-sort">
                                        <div class="vte-menu-item noConnect " data-itemid="{$MENU_ITEM.itemid}" data-module="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}" data-type="{$MENU_ITEM.type}">
                                            <i data-appname="SALES" class="fa fa-times pull-right whiteIcon VTEAdvanceMenuRemoveItem" style="margin: 5%;"></i>
                                            <div class="VTEAdvanceMenuItem VTEAdvanceMenuModuleItem">
                                            <span class="pull-left marginRight10px ">
                                                <img class="alignMiddle cursorDrag" src="layouts/v7/skins/images/drag.png">
                                            </span>
                                                <span>
                                                <i class="vicon-{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)|lower} marginRight10px pull-left"></i>
                                            </span>
                                                {if $MENU_ITEM.type eq 'Module'}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</div>
                                                {else}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</div>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 support-container">
                    <div class="row">
                        <div class="col-lg-12 group-header">
                            <div class="row app-SUPPORT">
                                <i class="fa fa-pencil" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}" data-groupid="{$SUPPORT.groupid}"></i>
                                <div class="col-lg-2">
                                    <i class="{$SUPPORT.icon_class}" aria-hidden="true"></i>
                                </div>
                                <div class="col-lg-10">
                                    <span class="group-name">{vtranslate($SUPPORT.label, $QUALIFIED_MODULE)}</span>
                                    <div class="dropdown pull-right add-button-container" data-appname="SUPPORT" data-menuid="{$SUPPORT.menuid}" data-groupid="{$SUPPORT.groupid}">
                                        <button class="btn addButton btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fa fa-plus" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_BTN', $QUALIFIED_MODULE)}<span class="caret"></span></button>
                                        <ul class="dropdown-menu">
                                            <li><a href="#" data-mode="showAddModule">{vtranslate('LBL_ADD_MODULE', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddLink">{vtranslate('LBL_ADD_LINK', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddFilter">{vtranslate('LBL_ADD_FILTER', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddSeparator">{vtranslate('LBL_ADD_SEPARATOR', $QUALIFIED_MODULE)}</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 group-menu" data-menuid="{$SUPPORT.menuid}" data-groupid="{$SUPPORT.groupid}">
                            <div class="row" data-appname="SUPPORT">
                                {foreach item=MENU_ITEM from=$SUPPORT.items}
                                    <div class="vte-menu-item-sort">
                                        <div class="vte-menu-item noConnect " data-itemid="{$MENU_ITEM.itemid}" data-module="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}" data-type="{$MENU_ITEM.type}">
                                            <i data-appname="SUPPORT" class="fa fa-times pull-right whiteIcon VTEAdvanceMenuRemoveItem" style="margin: 5%;"></i>
                                            <div class="VTEAdvanceMenuItem VTEAdvanceMenuModuleItem">
                                            <span class="pull-left marginRight10px ">
                                                <img class="alignMiddle cursorDrag" src="layouts/v7/skins/images/drag.png">
                                            </span>
                                                <span>
                                                <i class="vicon-{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)|lower} marginRight10px pull-left"></i>
                                            </span>
                                                {if $MENU_ITEM.type eq 'Module'}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</div>
                                                {else}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</div>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 inventory-container">
                    <div class="row">
                        <div class="col-lg-12 group-header">
                            <div class="row app-INVENTORY">
                                <i class="fa fa-pencil" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}" data-groupid="{$INVENTORY.groupid}"></i>
                                <div class="col-lg-2">
                                    <i class="{$INVENTORY.icon_class}" aria-hidden="true"></i>
                                </div>
                                <div class="col-lg-10">
                                    <span class="group-name">{vtranslate($INVENTORY.label, $QUALIFIED_MODULE)}</span>
                                    <div class="dropdown pull-right add-button-container" data-appname="INVENTORY" data-menuid="{$INVENTORY.menuid}" data-groupid="{$INVENTORY.groupid}">
                                        <button class="btn addButton btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fa fa-plus" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_BTN', $QUALIFIED_MODULE)}<span class="caret"></span></button>
                                        <ul class="dropdown-menu">
                                            <li><a href="#" data-mode="showAddModule">{vtranslate('LBL_ADD_MODULE', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddLink">{vtranslate('LBL_ADD_LINK', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddFilter">{vtranslate('LBL_ADD_FILTER', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddSeparator">{vtranslate('LBL_ADD_SEPARATOR', $QUALIFIED_MODULE)}</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 group-menu" data-menuid="{$INVENTORY.menuid}" data-groupid="{$INVENTORY.groupid}">
                            <div class="row" data-appname="INVENTORY">
                                {foreach item=MENU_ITEM from=$INVENTORY.items}
                                    <div class="vte-menu-item-sort">
                                        <div class="vte-menu-item noConnect " data-itemid="{$MENU_ITEM.itemid}" data-module="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}" data-type="{$MENU_ITEM.type}">
                                            <i data-appname="INVENTORY" class="fa fa-times pull-right whiteIcon VTEAdvanceMenuRemoveItem" style="margin: 5%;"></i>
                                            <div class="VTEAdvanceMenuItem VTEAdvanceMenuModuleItem">
                                            <span class="pull-left marginRight10px ">
                                                <img class="alignMiddle cursorDrag" src="layouts/v7/skins/images/drag.png">
                                            </span>
                                                <span>
                                                <i class="vicon-{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)|lower} marginRight10px pull-left"></i>
                                            </span>
                                                {if $MENU_ITEM.type eq 'Module'}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</div>
                                                {else}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</div>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-6 projects-container">
                    <div class="row">
                        <div class="col-lg-12 group-header">
                            <div class="row app-PROJECT">
                                <i class="fa fa-pencil" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}" data-groupid="{$PROJECTS.groupid}"></i>
                                <div class="col-lg-2">
                                    <i class="{$PROJECTS.icon_class}" aria-hidden="true"></i>
                                </div>
                                <div class="col-lg-10">
                                    <span class="group-name">{vtranslate($PROJECTS.label, $QUALIFIED_MODULE)}</span>
                                    <div class="dropdown pull-right add-button-container" data-appname="PROJECTS" data-menuid="{$PROJECTS.menuid}" data-groupid="{$PROJECTS.groupid}">
                                        <button class="btn addButton btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fa fa-plus" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_BTN', $QUALIFIED_MODULE)}<span class="caret"></span></button>
                                        <ul class="dropdown-menu">
                                            <li><a href="#" data-mode="showAddModule">{vtranslate('LBL_ADD_MODULE', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddLink">{vtranslate('LBL_ADD_LINK', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddFilter">{vtranslate('LBL_ADD_FILTER', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddSeparator">{vtranslate('LBL_ADD_SEPARATOR', $QUALIFIED_MODULE)}</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 group-menu" data-menuid="{$PROJECTS.menuid}" data-groupid="{$PROJECTS.groupid}">
                            <div class="row" data-appname="PROJECTS">
                                {foreach item=MENU_ITEM from=$PROJECTS.items}
                                    <div class="vte-menu-item-sort">
                                        <div class="vte-menu-item noConnect " data-itemid="{$MENU_ITEM.itemid}" data-module="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}" data-type="{$MENU_ITEM.type}">
                                            <i data-appname="PROJECTS" class="fa fa-times pull-right whiteIcon VTEAdvanceMenuRemoveItem" style="margin: 5%;"></i>
                                            <div class="VTEAdvanceMenuItem VTEAdvanceMenuModuleItem">
                                            <span class="pull-left marginRight10px ">
                                                <img class="alignMiddle cursorDrag" src="layouts/v7/skins/images/drag.png">
                                            </span>
                                                <span>
                                                <i class="vicon-{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)|lower} marginRight10px pull-left"></i>
                                            </span>
                                                {if $MENU_ITEM.type eq 'Module'}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</div>
                                                {else}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</div>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 tools-container">
                    <div class="row">
                        <div class="col-lg-12 group-header">
                            <div class="row app-DEFAULT">
                                <i class="fa fa-pencil" title="{vtranslate('LBL_EDIT', $QUALIFIED_MODULE)}" data-groupid="{$TOOLS.groupid}"></i>
                                <div class="col-lg-2">
                                    <i class="{$TOOLS.icon_class}" aria-hidden="true"></i>
                                </div>
                                <div class="col-lg-10">
                                    <span class="group-name">{vtranslate($TOOLS.label, $QUALIFIED_MODULE)}</span>
                                    <div class="dropdown pull-right add-button-container" data-appname="TOOLS" data-menuid="{$TOOLS.menuid}" data-groupid="{$TOOLS.groupid}">
                                        <button class="btn addButton btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fa fa-plus" aria-hidden="true"></i>&nbsp;&nbsp;{vtranslate('LBL_ADD_BTN', $QUALIFIED_MODULE)}<span class="caret"></span></button>
                                        <ul class="dropdown-menu">
                                            <li><a href="#" data-mode="showAddModule">{vtranslate('LBL_ADD_MODULE', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddLink">{vtranslate('LBL_ADD_LINK', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddFilter">{vtranslate('LBL_ADD_FILTER', $QUALIFIED_MODULE)}</a></li>
                                            <li><a href="#" data-mode="showAddSeparator">{vtranslate('LBL_ADD_SEPARATOR', $QUALIFIED_MODULE)}</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 group-menu" data-menuid="{$TOOLS.menuid}" data-groupid="{$TOOLS.groupid}">
                            <div class="row" data-appname="TOOLS">
                                {foreach item=MENU_ITEM from=$TOOLS.items}
                                    <div class="vte-menu-item-sort">
                                        <div class="vte-menu-item noConnect " data-itemid="{$MENU_ITEM.itemid}" data-module="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}" data-type="{$MENU_ITEM.type}">
                                            <i data-appname="TOOLS" class="fa fa-times pull-right whiteIcon VTEAdvanceMenuRemoveItem" style="margin: 5%;"></i>
                                            <div class="VTEAdvanceMenuItem VTEAdvanceMenuModuleItem">
                                            <span class="pull-left marginRight10px ">
                                                <img class="alignMiddle cursorDrag" src="layouts/v7/skins/images/drag.png">
                                            </span>
                                                <span>
                                                <i class="vicon-{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)|lower} marginRight10px pull-left"></i>
                                            </span>
                                                {if $MENU_ITEM.type eq 'Module'}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}</div>
                                                {else}
                                                    <div class="textOverflowEllipsis  textAlignLeft" title="{vtranslate($MENU_ITEM.module, $MENU_ITEM.module)}">{vtranslate($MENU_ITEM.label, $MENU_ITEM.module)}</div>
                                                {/if}
                                            </div>
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
