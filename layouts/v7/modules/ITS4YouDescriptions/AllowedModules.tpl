{*<!--
/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}

{strip}
    <form name="allowed_modules_form" id="updateAllowedModulesForm" action="index.php" method="post">
        <input type="hidden" name="module" value="ITS4YouDescriptions">
        <input type="hidden" name="action" value="AllowedModules">
        <div class="padding-left1per container-fluid settingsIndexPage">
            <div class="widget_header row-fluid settingsHeader">
                <h3><a href="index.php?module={$CURRENT_MODULE}&view=List">{vtranslate($CURRENT_MODULE, $CURRENT_MODULE)} {vtranslate('LBL_ALLOWED_MODULES', $CURRENT_MODULE)}</a></h3>
                <hr>
                <h4>{vtranslate('LBL_AVAILABLE_MODULES',$QUALIFIED_MODULE)}</h4>
            </div>
            <br>
            <div  id="CompanyDetailsContainer" class="{if !empty($ERROR_MESSAGE)}hide{/if}">
                <div class="row-fluid">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <td style="width: 200px;">{vtranslate('LBL_MODULE', $QUALIFIED_MODULE)}</td>
                                <td style="width: 100px;">{vtranslate('LBL_ACTION', $QUALIFIED_MODULE)}</td>
                                <td>{vtranslate('LBL_ALLOWED_FIELDS', $QUALIFIED_MODULE)}</td>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$SUPPORTED_MODULES item=SUPPORTED_MODULE}
                                {assign var=SUPPORTED_ID value=$SUPPORTED_MODULE['tabid']}
                                {assign var=SUPPORTED_NAME value=$SUPPORTED_MODULE['name']}
                                <tr>
                                    <td>
                                        <label for="allowed_{$SUPPORTED_ID}" style="width: 100%; font-weight: normal;">
                                            {vtranslate($SUPPORTED_NAME, $SUPPORTED_NAME)}
                                        </label>
                                    </td>
                                    <td>
                                        <input type="hidden" name="module_{$SUPPORTED_ID}" value="{$SUPPORTED_NAME}">
                                        <input class="switch" type="checkbox" name="allowed_{$SUPPORTED_ID}" value="{$SUPPORTED_ID}" id="allowed_{$SUPPORTED_ID}" {if $SUPPORTED_MODULE['checked']}checked{/if}>
                                    </td>
                                    <td>
                                        <select class="allowedFields inputElement select2 form-control select2-offscreen" data-allow-clear=true name="fields_{$SUPPORTED_ID}[]" id="fields_{$SUPPORTED_ID}" multiple="multiple">
                                            {foreach $SUPPORTED_MODULE['fields'] as $FIELD_DATA}
                                                {assign var=ALLOWED_FIELD value=$SUPPORTED_MODULE['allowed_fields']}
                                                <option value="{$FIELD_DATA['fieldname']}" {if $ALLOWED_FIELD and $ALLOWED_FIELD->isAllowed($FIELD_DATA['fieldname'])}selected{/if}>{vtranslate($FIELD_DATA['fieldlabel'], $MODULE_NAME)}</option>
                                            {/foreach}
                                        </select>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class='modal-overlay-footer clearfix'>
            <div class="row clearfix">
                <div class='textAlignCenter col-lg-12 col-md-12 col-sm-12 '>
                    <button type='submit' class='btn btn-success saveButton' >{vtranslate('LBL_SAVE', $MODULE)}</button>&nbsp;&nbsp;
                    <a class='cancelLink' type="reset" href="javascript:window.history.back();">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                </div>
            </div>
        </div>
    </form>
{/strip}