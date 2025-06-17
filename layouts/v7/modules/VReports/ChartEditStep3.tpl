{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <form class="form-horizontal recordEditView padding1per" id="chart_report_step3" method="post" action="index.php">
        <input type="hidden" name="module" value="{$MODULE}" >
        <input type="hidden" name="action" value="ChartSave" >
        <input type="hidden" name="record" value="{$RECORD_ID}" >
        <input type="hidden" name="step" value="Step3" >
        <input type="hidden" name="reportname" value='{Vtiger_Util_Helper::toSafeHTML($REPORT_MODEL->get('reportname'))}' >
        {if $REPORT_MODEL->get('members')}
            <input type="hidden" name="members" value='{ZEND_JSON::encode($REPORT_MODEL->get('members'))}' />
        {/if}
        <input type="hidden" name="folderid" value="{$REPORT_MODEL->get('folderid')}" >
        <input type="hidden" name="reports_description" value='{Vtiger_Util_Helper::toSafeHTML($REPORT_MODEL->get('reports_description'))}' >
        <input type="hidden" name="primary_module" value="{$PRIMARY_MODULE}" >
        <input type="hidden" name="secondary_modules" value='{ZEND_JSON::encode($SECONDARY_MODULES)}' >
        <input type="hidden" name="isDuplicate" value="{$IS_DUPLICATE}" >
        <input type="hidden" name="advanced_filter" id="advanced_filter" value="" >
        <input type="hidden" class="step" value="3" >
        <input type="hidden" name='groupbyfield' value='{$CHART_MODEL->getGroupByField()}' >
        <input type="hidden" name='datafields' value='{Zend_JSON::encode($CHART_MODEL->getDataFields())}'>
        <input type="hidden" name='charttype' value='{$CHART_MODEL->getChartType()}'>
        <input type="hidden" name="enable_schedule" value="{$REPORT_MODEL->get('enable_schedule')}">
        <input type="hidden" name="schtime" value="{$REPORT_MODEL->get('schtime')}">
        <input type="hidden" name="schdate" value="{$REPORT_MODEL->get('schdate')}">
        <input type="hidden" name="schdayoftheweek" value='{ZEND_JSON::encode($REPORT_MODEL->get('schdayoftheweek'))}'>
        <input type="hidden" name="schdayofthemonth" value='{ZEND_JSON::encode($REPORT_MODEL->get('schdayofthemonth'))}'>
        <input type="hidden" name="schannualdates" value='{ZEND_JSON::encode($REPORT_MODEL->get('schannualdates'))}'>
        <input type="hidden" name="recipients" value='{ZEND_JSON::encode($REPORT_MODEL->get('recipients'))}'>
        <input type="hidden" name="specificemails" value={ZEND_JSON::encode($REPORT_MODEL->get('specificemails'))}>
        <input type="hidden" name="from_address" value={ZEND_JSON::encode($REPORT_MODEL->get('from_address'))}>
        <input type="hidden" name="subject_mail" value="{$REPORT_MODEL->get('subject_mail')}">
        <textarea style="display: none" name="content_mail">{$REPORT_MODEL->get('content_mail')}</textarea>
        <input type="hidden" name="signature" value='{ZEND_JSON::encode($REPORT_MODEL->get('signature'))}'>
        <input type="hidden" name="signature_user" value='{ZEND_JSON::encode($REPORT_MODEL->get('signature_user'))}'>
        <input type="hidden" name="schtypeid" value="{$REPORT_MODEL->get('schtypeid')}">
        <div style="border:1px solid #ccc;padding:1%;">
            <div><h4><strong>{vtranslate('LBL_SELECT_CHART_TYPE',$MODULE)}</strong></h4></div><br>
            <div>
                <ul class="nav nav-tabs charttabs" name="charttab" style="text-align:center;font-size:14px;font-weight: bold;margin:0 3%;border:0px">
                    <li class="active marginRight5px" >
                        <a data-type="pieChart" data-toggle="tab">
                            <img src="layouts/v7/modules/VReports/resources/image/pie.png" style="border:1px solid #ccc"/>
                            <div class="chartname">{vtranslate('LBL_PIE_CHART', $MODULE)}</div>
                        </a>
                    </li>
                    <li class="marginRight5px" >
                        <a data-type="doughnutChart" data-toggle="tab">
                            <img src="layouts/v7/modules/VReports/resources/image/doughnut.png" style="border:1px solid #ccc"/>
                            <div class="chartname">{vtranslate('LBL_DOUGHNUT_CHART', $MODULE)}</div>
                        </a>
                    </li>
                    <li class="marginRight5px">
                        <a data-type="barChart" data-toggle="tab">
                            <img src="layouts/v7/modules/VReports/resources/image/vbar.png" style="border:1px solid #ccc"/>
                            <div class="chartname">{vtranslate('LBL_VERTICAL_BAR_CHART', $MODULE)}</div>
                        </a>
                    </li>
                    <li class="marginRight5px">
                        <a data-type="horizontalBarChart" data-toggle="tab">
                            <img src="layouts/v7/modules/VReports/resources/image/hbar.png" style="border:1px solid #ccc"/>
                            <div class="chartname">{vtranslate('LBL_HORIZONTAL_BAR_CHART', $MODULE)}</div>
                        </a>
                    </li>
                    <li class="marginRight5px" >
                        <a data-type="lineChart" data-toggle="tab">
                            <img src="layouts/v7/modules/VReports/resources/image/line.png" style="border:1px solid #ccc"/>
                            <div class="chartname">{vtranslate('LBL_LINE_CHART', $MODULE)}</div>
                        </a>
                    </li>
                    <li class="marginRight5px" >
                        <a data-type="stackedChart" data-toggle="tab">
                            <img src="layouts/v7/modules/VReports/resources/image/stacked.png" style="border:1px solid #ccc"/>
                            <div class="chartname">{vtranslate('LBL_STACKED_CHART', $MODULE)}</div>
                        </a>
                    </li>
                    <li class="marginRight5px" >
                        <a data-type="funnelChart" data-toggle="tab">
                            <img src="layouts/v7/modules/VReports/resources/image/funnel.jpg" style="border:1px solid #ccc"/>
                            <div class="chartname">{vtranslate('LBL_FUNNEL_CHART', $MODULE)}</div>
                        </a>
                    </li>
                    <li class="marginRight5px" >
                        <a data-type="barFunnelChart" data-toggle="tab">
                            <img src="layouts/v7/modules/VReports/resources/image/barfunnel.png" style="border:1px solid #ccc"/>
                            <div class="chartname">{vtranslate('LBL_BAR_FUNNEL_CHART', $MODULE)}</div>
                        </a>
                    </li>
                </ul>
                <div class='tab-content contentsBackground' style="height:auto;padding:4%;border:1px solid #ccc; background-color: white;">
                    <br>
                    <div class="row tab-pane active">
                        <div>
                            <span class="col-lg-4">
                                <div><span>{vtranslate('LBL_SELECT_GROUP_BY_FIELD', $MODULE)}</span><span class="redColor">*</span></div><br>
                                <div class="row">
                                    <select id='groupbyfield' name='groupbyfield' class="validate[required]" data-validation-engine="validate[required]" style='min-width:300px;'></select>
                                </div>
                                <br>
                                <div><span>{vtranslate('LBL_LEGEND_POSITION', $MODULE)}</span></div><br>
                                <div class="row">
                                    <select id='legend_position' name='legendposition' style='min-width:300px;' class="select2">
                                        <option value="none" {if $CHART_MODEL->getLegendPosition() == 'top'}selected="selected"{/if}>{vtranslate('None', $MODULE)}</option>
                                        <option value="top" {if $CHART_MODEL->getLegendPosition() == 'top'}selected="selected"{/if}>{vtranslate('Top', $MODULE)}</option>
                                        <option value="left" {if $CHART_MODEL->getLegendPosition() == 'left'}selected="selected"{/if}>{vtranslate('Left', $MODULE)}</option>
                                        <option value="right" {if $CHART_MODEL->getLegendPosition() == 'right'}selected="selected"{/if}>{vtranslate('Right', $MODULE)}</option>
                                        <option value="bottom" {if $CHART_MODEL->getLegendPosition() == 'bottom'}selected="selected"{/if}>{vtranslate('Bottom', $MODULE)}</option>
                                    </select>
                                </div>
                                <br>
                                <div><span>{vtranslate('LBL_DISPLAY_GRID', $MODULE)}</span></div><br>
                                <div class="row">
                                    <select id='display_grid' name='displaygrid' style='min-width:300px;' class="select2">
                                        <option value="0" {if $CHART_MODEL->get('displaygrid') == '0'}selected="selected"{/if}>{vtranslate('No', $MODULE)}</option>
                                        <option value="1" {if $CHART_MODEL->get('displaygrid') == '1'}selected="selected"{/if}>{vtranslate('Yes', $MODULE)}</option>
                                    </select>
                                </div>
                                <br>
                                <div><span>{vtranslate('LBL_DISPLAY_LABEL', $MODULE)}</span></div><br>
                                <div class="row">
                                    <select id='display_label' data-value="{$CHART_MODEL->get('displaylabel')}" name='displaylabel' style='min-width:300px;' class="select2">
                                        <option value="0" {if $CHART_MODEL->get('displaylabel') == '0'}selected="selected"{/if}>{vtranslate('No', $MODULE)}</option>
                                        <option value="1" {if $CHART_MODEL->get('displaylabel') == '1' || $CHART_MODEL->get('displaylabel') != '0'}selected="selected"{/if}>{vtranslate('Yes', $MODULE)}</option>
                                    </select>
                                </div>
                            </span>
                            <span class="col-lg-2">&nbsp;</span>
                            <span class="col-lg-4">
                                <div><span>{vtranslate('LBL_SELECT_DATA_FIELD', $MODULE)}</span><span class="redColor">*</span></div><br>
                                <div class="row">
                                    <select id='datafields' name='datafields[]' class="validate[required]" data-validation-engine="validate[required]" style='min-width:300px;'>
                                    </select>
                                </div>
                                {*Show Legend Number*}
                                <br>
                                <div><span>{vtranslate('LBL_LEGEND_VALUE', $MODULE)}</span></div><br>
                                <div class="row">
                                    <select id='legendvalue' name='legendvalue' style='min-width:300px;' class="select2">
                                        <option value="0" {if $CHART_MODEL->get('legendvalue') == '0'}selected="selected"{/if}>{vtranslate('No', $MODULE)}</option>
                                        <option value="1" {if $CHART_MODEL->get('legendvalue') == '1'}selected="selected"{/if}>{vtranslate('Yes', $MODULE)}</option>
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
                                    <select id='formatlargenumber' name='formatlargenumber' style='min-width:300px;' class="select2">
                                        <option value="0" {if $CHART_MODEL->get('formatlargenumber') == '0'}selected="selected"{/if}>{vtranslate('No', $MODULE)}</option>
                                        <option value="1" {if $CHART_MODEL->get('formatlargenumber') == '1'}selected="selected"{/if}>{vtranslate('Yes', $MODULE)}</option>
                                        </select>
                                </div>
                                {*end custom format large numbers*}
                                {*Draw Horizontal Line*}
                                <br>
                                <div class="label-drawline" {if $CHART_MODEL->getChartType() != 'barChart' && $CHART_MODEL->getChartType() != 'horizontalBarChart' && $CHART_MODEL->getChartType() != 'stackedChart' && $CHART_MODEL->getChartType() != 'barFunnelChart'} style="display: none" {/if}><span>{vtranslate('Draw Horizontal Line', $MODULE)}</span></div><br>
                                <div class="row input-drawline" {if $CHART_MODEL->getChartType() != 'barChart' && $CHART_MODEL->getChartType() != 'horizontalBarChart' && $CHART_MODEL->getChartType() != 'stackedChart' && $CHART_MODEL->getChartType() != 'barFunnelChart'} style="display: none" {/if}>
                                    <input type="number" id='drawline' name='drawline' style='max-width:300px;' class="inputElement" value="{$CHART_MODEL->get('drawline')}"/>
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
                    </div>
                </div>
            </div>
            <div class='hide'>
                {include file="chartReportHiddenContents.tpl"|vtemplate_path:$MODULE}
            </div>
        </div>
        <br>
        <div class="modal-overlay-footer border1px clearfix">
            <div class="row clearfix">
                <div class="textAlignCenter col-lg-12 col-md-12 col-sm-12 ">
                    <button type="button" class="btn btn-danger backStep"><strong>{vtranslate('LBL_BACK',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <button type="button" class="btn btn-success" id="generateReport"><strong>{vtranslate('LBL_GENERATE_REPORT',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <a class="cancelLink" onclick="window.history.back()">{vtranslate('LBL_CANCEL',$MODULE)}</a>
                </div>
            </div>
        </div>
    </form>
{/strip}
