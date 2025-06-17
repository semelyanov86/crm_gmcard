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
    <style>
        ul.select2-choices{
            border: 0 !important;
            border-bottom: 1px solid #aaa !important;
            background: #f9f9f9 !important;
            box-shadow : none !important;
            -webkit-box-shadow : none !important;
        }
        .select2-drop-active{
            border: 1px solid #aaa !important;
            /* border-top: none; */
        }
    </style>
    <form class="form-horizontal recordEditView" id="pivot_report_step3" method="post" action="index.php">
        <input type="hidden" name="module" value="{$MODULE}" >
        <input type="hidden" name="action" value="PivotSave" >
        <input type="hidden" name="view" value="PivotEdit" >
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
        <input type="hidden" name="mode" value="step4" >
        <input type="hidden" name='groupbyfield_rows' value='{Zend_JSON::encode($PIVOT_MODEL->getGroupByFieldRows())}' >
        <input type="hidden" name='groupbyfield_columns' value='{Zend_JSON::encode($PIVOT_MODEL->getGroupByFieldColumns())}' >
        <input type="hidden" name='datafields' value='{Zend_JSON::encode($PIVOT_MODEL->getDataFields())}'>

        <input type="hidden" name='showchart' value="0">
        <input type="hidden" name='charttype' value='{$PIVOT_MODEL->get('type')}'>
        <input type="hidden" name='drawline' value='{$PIVOT_MODEL->get('drawline')}'>
        <input type="hidden" name='formatlargenumber' value='{$PIVOT_MODEL->get('formatlargenumber')}'>
        <input type="hidden" name='legendvalue' value='{$PIVOT_MODEL->get('legendvalue')}'>
        <input type="hidden" name='datafields-chart' value='{Zend_JSON::encode($PIVOT_MODEL->get('datafields-chart'))}'>
        <input type="hidden" name='displaylabel' value='{$PIVOT_MODEL->get('displaylabel')}'>
        <input type="hidden" name='displaygrid' value='{$PIVOT_MODEL->get('displaygrid')}'>
        <input type="hidden" name='legendposition' value='{$PIVOT_MODEL->get('legendposition')}'>
        <input type="hidden" name='groupbyfield' value='{$PIVOT_MODEL->get('groupbyfield')}'>

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
        <textarea style="display: none" name="content_mail">{Vtiger_Util_Helper::toSafeHTML($REPORT_MODEL->get('content_mail'))}</textarea>
        <input type="hidden" name="signature" value='{ZEND_JSON::encode($REPORT_MODEL->get('signature'))}'>
        <input type="hidden" name="signature_user" value='{ZEND_JSON::encode($REPORT_MODEL->get('signature_user'))}'>
        <input type="hidden" name="schtypeid" value="{$REPORT_MODEL->get('schtypeid')}">
        <input type="hidden" name="renamedatavalue" value="{$REPORT_MODEL->get('rename_field')}">
        <input type="hidden" name="fileformat" value="{$REPORT_MODEL->get('fileformat')}">
        <div style="border:1px solid #ccc;padding:2%;">
            <div class="row">
                <div class="col-lg-12">
                    <h4><strong>{vtranslate('LBL_SELECT_PIVOT',$MODULE)}</strong></h4>
                    <br>
                    <div class="col-lg-4">
                        <h5>{vtranslate('LBL_SELECT_ROWS',$MODULE)}</h5>
                        <select class="col-lg-12" multiple id="groupbyfield_rows" name="groupbyfield_rows[]">
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <h5>{vtranslate('LBL_SELECT_COLUMNS',$MODULE)}</h5>
                        <select class="col-lg-12" multiple id="groupbyfield_columns" name="groupbyfield_columns[]">
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <h5>{vtranslate('LBL_SELECT_DATA_FIELDS',$MODULE)}</h5>
                        <select class="col-lg-12" multiple id="datafields-pivot" name="datafields[]">
                        </select>
                        <div class="col-lg-12" style="margin-top: 7px;">
                            <table class="table table-borderless rename-field-translate">
                                {foreach key=FIELD item=RENAME from=json_decode(html_entity_decode($REPORT_MODEL->get('rename_field')))}
                                    <tr>
                                        <td class="fieldLabel" name="{$RENAME->fieldname}">{$RENAME->fieldlabel}</td>
                                        <td class="fieldValue"">
                                        <input type="text" data-selected="{$RENAME->renameSelected}" data-fieldname="{$RENAME->fieldname}" data-fieldlabel="{$RENAME->fieldlabel}" data-fieldtype="string" class="inputElement" name="rename_field" value="{$RENAME->translatedLabel}">
                                        </td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12" style="margin-top: 50px">
                    <h4><strong>{vtranslate('Pivot Report Preview(Example)',$MODULE)}</strong></h4>
                    <br>
                    <div class="col-lg-9 col-lg-offset-3">
                        <img width="60%" src="{$SITE_URL}/layouts/v7/modules/VReports/resources/image/pivot_field.png">
                    </div>
                </div>
            </div>
            <div class="hide">
                {include file="PivotReportHiddenContents.tpl"|vtemplate_path:$MODULE}
            </div>
        </div>
        <br/>
        <div class="modal-overlay-footer border1px clearfix">
            <div class="row clearfix">
                <div class="textAlignCenter col-lg-12 col-md-12 col-sm-12 ">
                    <button type="button" class="btn btn-danger backStep"><strong>{vtranslate('LBL_BACK',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <button type="submit" class="btn btn-success " id="generatePivot"><strong>{vtranslate('LBL_GENERATE_REPORT',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <button type="submit" class="btn btn-primary nextStep" id="generateReport"><strong>{vtranslate('LBL_ADD_CHART_OPTIONAL',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <a class="cancelLink" onclick="window.history.back()">{vtranslate('LBL_CANCEL',$MODULE)}</a>
                </div>
            </div>
        </div>
    </form>

{/strip}
