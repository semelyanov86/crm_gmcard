{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}

{*<input type='hidden' name='charttype' value="{$CHART_TYPE}" />*}
{*<input type='hidden' name='data' value='{Vtiger_Functions::jsonEncode($DATA)}' />*}
{*<input type='hidden' name='clickthrough' value="{$CLICK_THROUGH}" />*}
<input type='hidden' name='displaylabel' value="{$REPORT_CHART_MODEL->get('displaylabel')}" />

<br>
<style>
    #chartjs-tooltip {
        opacity: 1;
        position: absolute;
        background: rgba(0, 0, 0,1);
        color: white;
        border-radius: 3px;
        -webkit-transition: all .1s ease;
        transition: all .1s ease;
        pointer-events: none;
        -webkit-transform: translate(-50%, 0);
        transform: translate(-50%, 0);
    }
</style>
<div class="device-xs visible-xs"></div>
<div class="device-sm visible-sm"></div>
<div class="device-md visible-md"></div>
<div class="device-lg visible-lg"></div>
<div class="device-xl visible-xl"></div>

<div class="dashboardWidgetContent" style="">
    <div class="grid-stack">
            <div class="grid-stack-item dashboardWidgetGridStack"
                 {if $POSITION}
                     data-gs-x={$POSITION->x}
                     data-gs-y={$POSITION->y}
                     data-gs-width={$POSITION->width}
                     data-gs-height={$POSITION->height}
                 {else}
                     data-gs-x="3"
                     data-gs-y="0"
                     data-gs-width="6"
                     data-gs-height="6"
                 {/if}
                 >
                <div class="panel panel-default grid-stack-item-content">
                    <div id='chartcontent' name='chartcontent' style="width: 100%;height: 100%;" data-mode='Reports'>
                        <input type="hidden" name="datachart" value="{$DATA_CHART}"/>
                        <canvas id="chart-area"></canvas>
                        {$DATA}
                    </div>
                </div>
            </div>
    </div>
</div>
<br>

