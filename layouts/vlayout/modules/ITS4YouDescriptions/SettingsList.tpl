{*
/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */
 *}
<div  id="settingsQuickWidgetContainer" style="margin:0px;padding:0px">
        <div class="widgetContainer" id="Settings_sideBar_LBL_OTHER_SETTINGS" > 
        {foreach item=SIDEBARLINK from=$QUICK_LINKS['SIDEBARLINK']}
            {assign var=SIDE_LINK_URL value=decode_html($SIDEBARLINK->getUrl())}
            {assign var="EXPLODED_PARSE_URL" value=explode('?',$SIDE_LINK_URL)}
            {assign var="COUNT_OF_EXPLODED_URL" value=count($EXPLODED_PARSE_URL)}
            {if $COUNT_OF_EXPLODED_URL gt 1}
                {assign var="EXPLODED_URL" value=$EXPLODED_PARSE_URL[$COUNT_OF_EXPLODED_URL-1]}
            {/if}
            {assign var="PARSE_URL" value=explode('&',$EXPLODED_URL)}
            {assign var="CURRENT_LINK_VIEW" value='view='|cat:$CURRENT_PVIEW}
            {assign var="LINK_LIST_VIEW" value=in_array($CURRENT_LINK_VIEW,$PARSE_URL)}
            {assign var="CURRENT_MODULE_NAME" value='module='|cat:$MODULE}
            {assign var="IS_LINK_MODULE_NAME" value=in_array($CURRENT_MODULE_NAME,$PARSE_URL)}
            <div class="{if $LINK_LIST_VIEW} selectedMenuItem selectedListItem{/if}" style='padding-left:10px;border-top:0px;padding-bottom: 5px'>
                <div class="row-fluid menuItem"  data-actionurl="">
                    <a href="{$SIDE_LINK_URL}" data-id="{$MODULE}_settingsBar_link_{Vtiger_Util_Helper::replaceSpaceWithUnderScores($SIDEBARLINK->getLabel())}" class="textOverflowEllipsis span9 menuItemLabel" data-menu-item="true" >{vtranslate($SIDEBARLINK->getLabel(), $MODULE)}</a>
                    <div class="clearfix"></div>
                </div>
            </div>
        {/foreach}
    </div>
</div>