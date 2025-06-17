{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <div class='tab-content contentsBackground hide' style="height:auto; background-color: white;">
        <br>
        <div class="row tab-pane active">
            <div>
                <div class="col-lg-3">
                    <div><span>{vtranslate('LBL_SORT_BY',$MODULE)}</span></div><br>
                    <div>
                        <select class="col-lg-10 select2" multiple id="sort_by" name="sort_by[]" tabindex="-1" style="min-width:300px;"></select>
                    </div>
                    <br>
                    <br>
                    <br>
                    <div>
                        <div class="col-lg-6" style="padding-left: 0px!important;">{vtranslate('LBL_LIMIT',$MODULE)}</div>
                        <div class="col-lg-3"></div>
                        <div class="col-lg-6">{vtranslate('LBL_ORDER',$MODULE)}</div>
                    </div>
                    <br>
                    <div>
                        <div class="col-lg-6" style="padding-left: 0px!important;">
                            <input type="text" data-fieldtype="string" value="{$LIMIT}" class="inputElement" style="max-width: 150px" name="sort_limit">
                        </div>
                        <div class="col-lg-3"></div>
                        <div class="col-lg-6">
                            <select class="col-lg-12 select2" id="order_by" name="order_by" style="max-width: 100px">
                                <option value="ASC" selected >ASC</option>
                                <option value="DESC">DESC</option>
                            </select>
                        </div>
                    </div>
                </div>
                <span class="col-lg-2">&nbsp;</span>
                <span class="col-lg-3">
                    <div><span>{vtranslate('LBL_DISPLAY_GRID', $MODULE)}</span></div><br>
                    <div class="row">
                        <select id='display_grid' name='displaygrid'
                                style='min-width:300px;' class="select2 col-lg-10">
                            <option value="0"
                                    {if $CHART_MODEL->get('displaygrid') == '0'}selected="selected"{/if}>{vtranslate('No', $MODULE)}</option>
                            <option value="1"
                                    {if $CHART_MODEL->get('displaygrid') == '1'}selected="selected"{/if}>{vtranslate('Yes', $MODULE)}</option>
                            </select>
                    </div>
                    <br>
                    <div><span>{vtranslate('LBL_DISPLAY_LABEL', $MODULE)}</span></div><br>
                    <div class="row">
                        <select id='display_label_chart'
                                name='displaylabel' style='min-width:300px;'
                                class="select2 col-lg-10">
                            <option value="0"
                                    {if $CHART_MODEL->get('displaylabel') == '0'}selected="selected"{/if}>{vtranslate('No', $MODULE)}</option>
                            <option value="1" {if $CHART_MODEL->get('displaylabel') == '1' }selected="selected"{/if}>{vtranslate('Yes', $MODULE)}</option>
                            {if $CHART_MODEL->get('type') == 'pieChart' || $CHART_MODEL->get('type') == 'doughnutChart'}
                                <option class='pie-label' value='2' {if $CHART_MODEL->get('displaylabel') == '2' }selected="selected"{/if}>Yes - Data (Percent)</option>
                                <option class='pie-label' value='3' {if $CHART_MODEL->get('displaylabel') == '3' }selected="selected"{/if}>Yes - Data</option>
                                <option class='pie-label' value='4' {if $CHART_MODEL->get('displaylabel') == '4' }selected="selected"{/if}>Yes - Group Name</option>
                            {/if}
                        </select>
                    </div>
                </span>
                <span class="col-lg-1">&nbsp;</span>
                <span class="col-lg-3">
                    {*Show Legend Number*}
                    <div><span>{vtranslate('LBL_LEGEND_VALUE', $MODULE)}</span></div><br>
                    <div class="row">
                        <select id='legendvalue' name='legendvalue' style='min-width:300px;'
                                class="select2">
                            <option value="0"
                                    {if $CHART_MODEL->get('legendvalue') == '0'}selected="selected"{/if}>{vtranslate('No', $MODULE)}</option>
                            <option value="1"
                                    {if $CHART_MODEL->get('legendvalue') == '1'}selected="selected"{/if}>{vtranslate('Yes', $MODULE)}</option>
                            <option value="2" {if $CHART_MODEL->get('legendvalue') == '2'}selected="selected"{/if}>{vtranslate('Yes - Value (Percentage)', $MODULE)}</option>
                            <option value="3" {if $CHART_MODEL->get('legendvalue') == '3'}selected="selected"{/if}>{vtranslate('Yes - Value', $MODULE)}</option>
                            <option value="4" {if $CHART_MODEL->get('legendvalue') == '4'}selected="selected"{/if}>{vtranslate('Yes - Percentage', $MODULE)}</option>
                        </select>
                    </div>
                    {*end cShow Legend Number*}
                    {*custom format large numbers*}
                    <br>
                    <div><span>{vtranslate('LBL_FOTMAT_LARGE_MUNBER', $MODULE)}</span></div><br>
                    <div class="row">
                        <select id='formatlargenumber' name='formatlargenumber'
                                style='min-width:300px;' class="select2">
                            <option value="0"
                                    {if $CHART_MODEL->get('formatlargenumber') == '0'}selected="selected"{/if}>{vtranslate('No', $MODULE)}</option>
                            <option value="1"
                                    {if $CHART_MODEL->get('formatlargenumber') == '1'}selected="selected"{/if}>{vtranslate('Yes', $MODULE)}</option>
                            </select>
                    </div>
                    {*end custom format large numbers*}
                    {*Draw Horizontal Line*}
                    <br>
                    <div class="label-drawline" {if $CHART_MODEL->getChartType() != 'barChart' && $CHART_MODEL->getChartType() != 'horizontalBarChart' && $CHART_MODEL->getChartType() != 'stackedChart' && $CHART_MODEL->getChartType() != 'barFunnelChart'} style="display: none" {/if}><span>{vtranslate('Draw Horizontal Line', $MODULE)}</span></div><br>
                    <div class="row input-drawline" {if $CHART_MODEL->getChartType() != 'barChart' && $CHART_MODEL->getChartType() != 'horizontalBarChart' && $CHART_MODEL->getChartType() != 'stackedChart' && $CHART_MODEL->getChartType() != 'barFunnelChart'} style="display: none" {/if}>
                        <input type="number" id='drawline' name='drawline'
                               style='max-width:300px;' class="inputElement"
                               value="{$CHART_MODEL->get('drawline')}"/>
                    </div>
                    {*end Draw Horizontal Line*}
                </span>
            </div>
        </div>
        <br><br>
        <div class='row alert-info' style="padding: 20px;">
            <span class='span alert-info'>
                <span>
                    <i class="fa fa-info-circle"></i>&nbsp;&nbsp;&nbsp;
                    {vtranslate('LBL_PLEASE_SELECT_ATLEAST_ONE_GROUP_FIELD_AND_DATA_FIELD', $MODULE)}
                    {vtranslate('LBL_FOR_BAR_GRAPH_AND_LINE_GRAPH_SELECT_3_MAX_DATA_FIELDS', $MODULE)}
                </span>
            </span>
        </div>
    </div>
{/strip}
