{* /* * *******************************************************************************
 * The content of this file is subject to the VTE Custom User Login Page ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */ *}

<div class="container-fluid vte-user-login">
    <div class="contentHeader row-fluid">
        <h3 class="span8 textOverflowEllipsis">
            {vtranslate('LBL_SETTING_HEADER', $QUALIFIED_MODULE)}
        </h3>
    </div>
    <hr>
    <div class="clearfix"></div>

    <div class="listViewContentDiv" id="listViewContents">
        <div class="marginBottom10px">
            <span class="row">
                <a href="index.php?module=UserLogin&view=Edit&parent=Settings" class="pull-left btn btn-default">
                    <i class="fa fa-plus"></i>&nbsp;
                    <strong>{vtranslate('LBL_ADD_MORE_BTN', $QUALIFIED_MODULE)}</strong>
                </a>
                <a href="javascript:void(0);" data-url="index.php?module=UserLogin&view=Edit&parent=Settings" class="pull-left btn btn-default btn-info editButton">
                    <i class="fa fa-edit"></i>&nbsp;
                    <strong>{vtranslate('LBL_EDIT_BTN', $QUALIFIED_MODULE)}</strong>
                </a>
                <a href="javascript:void(0);" data-url="index.php?module=UserLogin&action=DeleteAjax&parent=Settings" class="pull-left btn btn-danger deleteButton">
                    <i class="fa fa-trash"></i>&nbsp;
                    <strong>{vtranslate('LBL_DELETE_BTN', $QUALIFIED_MODULE)}</strong>
                </a>
                <a href="javascript:void(0);" data-url="index.php?module=UserLogin&view=EditImageSettings&parent=Settings" class="pull-right btn btn-info imgSetting">
                    <i class="fa fa-cog"></i>&nbsp;
                    <strong>{vtranslate('LBL_IMGSETTING_BTN', $QUALIFIED_MODULE)}</strong>
                </a>
                <a href="javascript:void(0);" data-url="index.php?module=UserLogin&action=Restore&parent=Settings" class="pull-right btn btn-warning restoreButton">
                    <i class="fa fa-refresh"></i>&nbsp;
                    <strong>{vtranslate('LBL_RESTORE_BTN', $QUALIFIED_MODULE)}</strong>
                </a>
                <a href="javascript:void(0);" data-url="index.php?module=UserLogin&action=Generate&parent=Settings" class="pull-right btn btn-primary generateButton">
                    <i class="fa fa-user"></i>&nbsp;
                    <strong>{vtranslate('LBL_GENERATE_BTN', $QUALIFIED_MODULE)}</strong>
                </a>
            </span>
        </div>
        <div class="marginBottom10px">
            <table class="table table-bordered listViewEntriesTable vte-related-record-count">
                <thead>
                    <tr class="listViewHeaders">
                        <th width="5%">#</th>
                        <th class="medium">{vtranslate('LBL_HEADER_HEADER', $QUALIFIED_MODULE)}</th>
                        <th class="medium alignCenter">{vtranslate('LBL_HEADER_LOGO', $QUALIFIED_MODULE)}</th>
                        <th class="medium alignCenter">{vtranslate('LBL_HEADER_SOCIAL', $QUALIFIED_MODULE)}</th>
                        <th class="medium">{vtranslate('LBL_HEADER_COPYRIGHT', $QUALIFIED_MODULE)}</th>
                        <th class="medium alignCenter">{vtranslate('LBL_HEADER_SLIDE', $QUALIFIED_MODULE)}</th>
                    </tr>
                </thead>
                <tbody>
                    {if $COUNT_ENTITY gt 0}
                        {foreach item=ENTITY from=$ENTITIES}
                            <tr>
                                <td class="listViewEntryValue" >
                                    <input type="checkbox" value="{$ENTITY.id}" />
                                </td>
                                <td class="listViewEntryValue" >
                                    <a href="index.php?module=UserLogin&view=Edit&parent=Settings&record={$ENTITY.id}">
                                        {$ENTITY.header}
                                    </a>
                                </td>
                                <td class="listViewEntryValue alignCenter">
                                    {if $ENTITY.logo}
                                    <img src="{$ENTITY.logo}" width="50" />
                                    {/if}
                                </td>
                                <td class="listViewEntryValue alignCenter">
                                    {if $ENTITY.social_facebook}
                                        <a href="{$ENTITY.social_facebook}" target="_blank" class="social">
                                            <i class="icon-social-facebook icons"></i>
                                        </a>
                                    {/if}
                                    {if $ENTITY.social_twitter}
                                        <a href="{$ENTITY.social_twitter}" target="_blank" class="social">
                                            <i class="icon-social-twitter icons"></i>
                                        </a>
                                    {/if}
                                    {if $ENTITY.social_linkedin}
                                        <a href="{$ENTITY.social_linkedin}" target="_blank" class="social">
                                            <i class="icon-social-linkedin icons"></i>
                                        </a>
                                    {/if}
                                    {if $ENTITY.social_youtube}
                                        <a href="{$ENTITY.social_youtube}" target="_blank" class="social">
                                            <i class="icon-social-youtube icons"></i>
                                        </a>
                                    {/if}
                                </td>
                                <td class="listViewEntryValue">{$ENTITY.copyright}</td>
                                <td class="listViewEntryValue alignCenter">
                                    {foreach item=IMAGE from=$ENTITY.images}
                                        <img src="{$IMAGE}" width="50" />
                                    {/foreach}
                                </td>
                            </tr>
                        {/foreach}
                    {/if}
                </tbody>
            </table>
        </div>
    </div>
</div>

