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
    <form class="form-horizontal recordEditView" id="report_step2" method="post" action="index.php">
        <input type="hidden" name="module" value="{$MODULE}" />
        <input type="hidden" name="action" value="Save" />
        <input type="hidden" name="record" value="{$RECORD_ID}" />
        <input type="hidden" name="reportname" value="{$REPORT_MODEL->get('reportname')}" />
        <input type="hidden" name="reporttype" value="sql" />
        {if $REPORT_MODEL->get('members')}
            <input type="hidden" name="members" value='{ZEND_JSON::encode($REPORT_MODEL->get('members'))}' />
        {/if}
        <input type="hidden" name="folderid" value="{$REPORT_MODEL->get('folderid')}" />
        <input type="hidden" name="description" value="{$REPORT_MODEL->get('description')}" />
        <input type="hidden" name="primary_module" value="{$PRIMARY_MODULE}" />
        <input type="hidden" name="secondary_modules" value='{ZEND_JSON::encode($SECONDARY_MODULES)}' />
        <input type="hidden" name="selected_fields" id="seleted_fields" value='{ZEND_JSON::encode($SELECTED_FIELDS)}' />
        <input type="hidden" name="selected_sort_fields" id="selected_sort_fields" value="" />
        <input type="hidden" name="calculation_fields" id="calculation_fields" value="" />
        <input type="hidden" name="isDuplicate" value="{$IS_DUPLICATE}" />

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
        <input type="hidden" name="fileformat" value="{$REPORT_MODEL->get('fileformat')}">

        <input type="hidden" class="step" value="2" />
        <div class="" style="border:1px solid #ccc;padding:4%;">
            <div class="form-group">
                <label>{vtranslate('LBL_QUERY',$MODULE)}</label>
                <textarea style="height: 150px" type="text" cols="50" rows="3" class="inputElement" name="report_query">{html_entity_decode(html_entity_decode($REPORT_MODEL->get('data')))}</textarea>
            </div>
        </div>
        <div class="modal-overlay-footer border1px clearfix">
            <div class="row clearfix">
                <div class="textAlignCenter col-lg-12 col-md-12 col-sm-12 ">
                    <button type="button" class="btn btn-danger backStep"><strong>{vtranslate('LBL_BACK',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <button type="submit" class="btn btn-success" id="generateReport"><strong>{vtranslate('LBL_GENERATE_REPORT',$MODULE)}</strong></button>&nbsp;&nbsp;
                    <button type="submit" class="btn btn-primary" id="generateReport" name="excuteLimit" value="10"><strong>{vtranslate('LBL_EXECUTE_LIMIT_10',$MODULE)}</strong></button>
                    <a class="cancelLink" onclick="window.history.back()">{vtranslate('LBL_CANCEL',$MODULE)}</a>
                </div>
            </div>
        </div>
        <br><br>
    </form>
{/strip}
