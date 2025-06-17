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
{assign var=HEADER_COLOR value=$WIDGET->get('pick_color')}
{assign var=HEADER_TEXT_COLOR value=VReports_Widget_Model::getTextColor($HEADER_COLOR)}
<header data-url="{$WIDGET->getUrl()}" data-refresh-time="{$WIDGET->get('refresh_time')}" data-tabid="{$WIDGET->get('dashboardtabid')}" class="panel_header sticky_header" style="z-index: 1000; background-color: {if $HEADER_COLOR} {$HEADER_COLOR}; color: {$HEADER_TEXT_COLOR}; {else} #ffffff; {/if}">
    {include file="dashboards/WidgetHeader.tpl"|@vtemplate_path:$MODULE}
</header>
<div class="panel_content widget_id_{$WIDGET->get('id')}" style="height: 85%;padding-top: 10px">
    {include file="dashboards/DashBoardWidgetContents.tpl"|@vtemplate_path:$MODULE}
</div>
