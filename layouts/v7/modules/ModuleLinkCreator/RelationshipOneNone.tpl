{*<!--
/* ********************************************************************************
 * The content of this file is subject to the Module & Link Creator ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}

{strip}
    <div class="viewContent">
        <div class="col-sm-12 col-xs-12 content-area">
            <form class="form-horizontal fieldBlockContainer" method="post" action="index.php" onsubmit="return false;">
                <input type="hidden" name="relationshipOneNone" value="true">
                <div class="contentHeader row">
                    <h3 class="col-sm-8 col-xs-8 textOverflowEllipsis"
                        title="{vtranslate('add_new_related_field_explain', $MODULE)}">{vtranslate('LBL_CREATEING_ONE_WAY', $MODULE)}</h3>
                    <span class="col-sm-4 col-xs-4 text-right">
                        {*<button class="btn btn-success" type="submit">*}
                            {*<strong>{vtranslate('LBL_SAVE', $MODULE)}</strong>*}
                        {*</button>*}
                        {*<a class="cancelLink"*}
                           {*href="index.php?module={$MODULE}&view=List">{vtranslate('LBL_CANCEL', $MODULE)}</a>*}
                    </span>
                </div>

                {*<div class="contentHeader">*}
                    {*<div class="alert alert-warning">{vtranslate('notice1M', $MODULE)}</div>*}
                {*</div>*}

                <table class="table table-bordered listview-table" style="border-top: 1px solid #ddd;">
                    <thead>
                    <tr>
                        <th colspan="4">{vtranslate('LBL_CREATEING_ONE_WAY', $MODULE)}</th>
                    </tr>
                    </thead>

                    <tbody>
                    <tr>
                        <td class="fieldLabel medium" style="width: 20%">
                            <label class="muted pull-right marginRight10px">{vtranslate('LBL_RELATED_FIELD_MODULE', $MODULE)}</label>
                        </td>
                        <td class="fieldValue medium">
                            <div class="row-fluid">
                                <select name="module2" id="module2" class="select2 span10" style="width: 200px">
                                    <option value="-">{vtranslate('LBL_SELECT', $MODULE)}</option>
                                    {foreach from=$ENTITY_MODULES item=MODULE1}
                                        <option value="{$MODULE1}">{vtranslate($MODULE1,$MODULE1)}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldLabel medium">
                            <label class="muted pull-right marginRight10px">{vtranslate('LBL_NEW_RELATED_FIELD', $MODULE)}</label>
                        </td>
                        <td class="fieldValue medium">
                            <div class="row-fluid">
                                <input type="text" id="field_label" class="inputElement">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldLabel medium">
                            <label class="muted pull-right marginRight10px">{vtranslate('LBL_ADD_RELATED_FIELD', $MODULE)}</label>
                        </td>
                        <td class="fieldValue medium">
                            <div class="row-fluid" >
                                <select name="module1" id="module1" class="select2 span10" style="width: 200px">
                                    <option value="-">{vtranslate('LBL_SELECT', $MODULE)}</option>
                                    {foreach from=$ENTITY_MODULES item=MODULE1}
                                        <option value="{$MODULE1}">{vtranslate($MODULE1,$MODULE1)}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </td>
                    </tr>



                    <tr>

                        <td class="fieldLabel medium">
                            <label class="muted pull-right marginRight10px">{vtranslate('Place field in Block', $MODULE)} <span class="redColor">*</span></label>
                        </td>
                        <td class="fieldValue medium">
                            <div class="row-fluid">
                                <select name="block" id="block" class="select2 span10 required" style="width: 200px">
                                    <option value="-">{vtranslate('LBL_SELECT', $MODULE)}</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <br><br>
                <div id="error_notice" class="alert alert-error notices related-field-creator-notices"
                     style="display:none;">
                    {vtranslate('fail', $MODULE)}
                </div>
                <div id="success_message" class="alert alert-success notices related-field-creator-notices"
                     style="display:none;">
                    {vtranslate('works', $MODULE)}
                </div>
                <div id="duplicate_error" class="alert alert-error notices related-field-creator-notices"
                     style="display:none;">
                    {vtranslate('duplicated-error', $MODULE)}
                </div>
                <div id="field-already-there" class="alert alert-error notices related-field-creator-notices"
                     style="display:none;">
                    {vtranslate('field-already-there', $MODULE)}
                </div>

                <div class="row-fluid">
                    <div class="pull-right">
                        <button id="add_related_field" class="btn btn-success" type="submit">
                            <strong>{vtranslate('LBL_SAVE', $MODULE)}</strong>
                        </button>
                        <a class="cancelLink"
                           href="index.php?module={$MODULE}&view=List">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                    </div>
                </div>
            </form>

            <br>

            <div class="row-fluid">
                <table id="table-oneNone" class="table table-bordered listViewEntriesTable">
                    <caption style="font-weight: bold; font-size: 18px; padding: 10px; text-align: left;">
                        {vtranslate('All One Way Relations')}</caption>
                    <thead>
                    <tr class="listViewHeaders">
                        <th>#</th>
                        <th>{vtranslate('Field Name', $MODULE)}</th>
                        <th>{vtranslate('Related Module', $MODULE)}</th>
                        <th colspan="2">{vtranslate('Primary Module (Where field is placed)', $MODULE)}</th>
                    </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>

            <br>

        </div>
    </div>
{/strip}