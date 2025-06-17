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
    <div class="main-container">
        <div class="editViewPageDiv viewContent editViewContents">
            <div class="col-sm-12 col-xs-12 content-area">
                <div id="js_currentUser" class="hide noprint">{Zend_Json::encode($USER_PROFILE)}</div>
                <div id="js_config" class="hide noprint">{Zend_Json::encode($CONFIG)}</div>
                <div id="js_settings" class="hide noprint">{Zend_Json::encode($SETTINGS)}</div>

                <form class="form-horizontal fieldBlockContainer" id="EditView" name="EditView" method="post"
                      action="index.php"
                      enctype="multipart/form-data">
                    <input type="hidden" name="module" value="{$MODULE}">
                    <input type="hidden" name="record" value="{$RECORD_ID}">
                    <input type="hidden" name="action" value="Save">
                    <div class="contentHeader row">
                        <h3 class="col-sm-8 col-xs-8 textOverflowEllipsis">{vtranslate($MODULE, $MODULE)}</h3>
                <span class="col-sm-4 col-xs-4 text-right">
                    <button class="btn btn-success" type="submit" disabled="disabled">
                        <strong>{vtranslate('LBL_SAVE', $MODULE)}</strong>
                    </button>
                    <a class="cancelLink"
                       href="index.php?module={$MODULE}&view=List">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                </span>
                    </div>

                    <div class="contentHeader row-fluid">
                        <div class="alert alert-warning">
                            {vtranslate('LBL_NOTE', $MODULE)}
                        </div>
                    </div>

                    <table id="module-link-creator-edit-table" class="table table-bordered listview-table"
                           style="border-top: 1px solid #ddd;">
                        <thead>
                        <tr>
                            <th colspan="4">{vtranslate('LBL_MODULE_DETAILS', $MODULE)}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="fieldLabel medium">
                                <label class="muted pull-right marginRight10px">
                                    <span class="redColor">*</span> {vtranslate('LBL_MODULE_LABEL', $MODULE)}
                                </label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid">
                            <span class="span10">
                                <input id="{$MODULE}_editView_fieldName_module_label"
                                       type="text" class="inputElement nameField"
                                       name="module_label" required="required"
                                       value="CM{$RECORD->get('module_label')}"

                                        {if $RECORD_ID} readonly="readonly" {/if}/>
                            </span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldLabel medium">
                                <label class="muted pull-right marginRight10px">
                                    <span class="redColor">*</span> {vtranslate('LBL_SINGULAR_MODULE_LABEL', $MODULE)}
                                </label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid">
                            <span class="span10">
                                <input id="{$MODULE}_editView_singular_module_label"
                                       type="text" class="inputElement nameField"
                                       name="singular_module_label" required="required"
                                       value="CM{$RECORD->get('singular_module_label')}"

                                        {if $RECORD_ID} readonly="readonly" {/if}/>
                            </span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldLabel medium">
                                <label class="muted pull-right marginRight10px">{vtranslate('LBL_MODULE_NAME', $MODULE)}</label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid">
                            <span class="span10">
                                <input id="{$MODULE}_editView_fieldName_module_name"
                                       type="text" class="inputElement nameField"
                                       name="module_name" readonly="readonly" required="required"
                                       value="CM{$RECORD->get('module_name')}">
                            </span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldLabel medium" style="vertical-align: middle">
                                <label class="muted pull-right marginRight10px">
                                    <span class="redColor">*</span> {vtranslate('LBL_BASE_PERMISSIONS', $MODULE)}
                                </label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid ">
                                    <select id="base_permissions" name="base_permissions">
                                        <option value="">{vtranslate('LBL_SELECT', $MODULE)}</option>
                                        <option value="0">{vtranslate('LBL_AVAIL_EVERYONE', $MODULE)}</option>
                                        <option value="1">{vtranslate('LBL_AVAIL_ADMINISTRATOR', $MODULE)}</option>
                                    </select>
                                    <a href="#" data-html="true" style="margin-left: 20px" class="hover-tooltip" data-toggle="tooltip" data-placement="bottom"
                                       title='{vtranslate('LBL_BASE_PERMISSIONS_TOOLTIP', $MODULE)}'><i class="fa fa-question-circle"></i></a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldLabel medium" style="vertical-align: middle">
                                <label class="muted pull-right marginRight10px">{vtranslate('LBL_ICON', $MODULE)}</label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid logo-module">
                                    <span class="span10" style="margin-right: 10px">
                                        <!-- Button trigger modal -->
                                        <button type="button" class="btn btn-primary" data-toggle="modal"
                                                data-target="#ModalIcons">
                                            Select Icon
                                        </button>
                                    </span>
                                    <span class="icon-module" id="icon-module" style="font-size: 30px; vertical-align: middle;"></span>
                                    <input type="hidden" name="data-icon-module" data-info="">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldLabel medium" style="vertical-align: middle">
                                <label class="muted pull-right marginRight10px">{vtranslate('Menu Placement', $MODULE)}</label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid ">
                                    <select id="Menu_Placement" name="menu_placement">
                                        <option value="MARKETING">MARKETING</option>
                                        <option value="SALES">SALES</option>
                                        <option value="INVENTORY">INVENTORY</option>
                                        <option value="TOOLS">TOOLS</option>
                                        <option value="SUPPORT">SUPPORT</option>
                                        <option value="PROJECT">PROJECTS</option>
                                    </select>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td class="fieldLabel medium">
                                <label class="muted pull-right marginRight10px">{vtranslate('LBL_MODULE_TYPE', $MODULE)}</label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid">
                            <span class="span10">
                                <select name="module_type" id="{$MODULE}_editView_fieldName_module_type"
                                        class="select2" disabled="disabled" required="required">
                                    {foreach key=ID item=LABEL from=$MODULE_TYPES}
                                        <option value="{$ID}" {if $MOULE_TYPE_VALUE eq $ID} selected="selected" {/if}>
                                            {vtranslate($LABEL, $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldLabel medium">
                                <label class="muted pull-right marginRight10px">{vtranslate('LBL_FIELDS', $MODULE)}</label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid">
                            <span class="span10">
                                <select name="module_fields[]" id="{$MODULE}_editView_fieldName_module_fields"
                                        class="select2" disabled="disabled" multiple="multiple">
                                    {foreach key=KEY item=ITEM from=$MODULE_FIELDS}
                                        <option value="{$KEY}" data-info='{Zend_Json::encode($ITEM)}'
                                                selected="selected">
                                            {vtranslate($ITEM['fieldlabel'], $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldLabel medium">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_LIST_VIEW_FILTER_FIELDS', $MODULE)}
                                </label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid">
                            <span class="span10">
                                <select id="{$MODULE}_editView_fieldName_module_list_view_filter_fields"
                                        name="module_list_view_filter_fields[]"
                                        class="select2" disabled="disabled" multiple="multiple">
                                    {foreach key=KEY item=ITEM from=$MODULE_LIST_VIEW_FILTER_FIELDS}
                                        <option value="{$KEY}" data-info='{Zend_Json::encode($ITEM)}'
                                                selected="selected">
                                            {vtranslate($ITEM['fieldlabel'], $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldLabel medium">
                                <label class="muted pull-right marginRight10px">{vtranslate('LBL_SUMMARY_FIELDS', $MODULE)}</label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid">
                            <span class="span10">
                                <select id="{$MODULE}_editView_fieldName_module_summary_fields"
                                        name="module_summary_fields[]"
                                        class="select2" disabled="disabled" multiple="multiple">
                                    {foreach key=KEY item=ITEM from=$MODULE_SUMMARY_FIELDS}
                                        <option value="{$KEY}" data-info='{Zend_Json::encode($ITEM)}'
                                                selected="selected">
                                            {vtranslate($ITEM['fieldlabel'], $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldLabel medium">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_QUICK_CREATE_FIELDS', $MODULE)}
                                </label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid">
                            <span class="span10">
                                <select id="{$MODULE}_editView_fieldName_module_quick_create_fields"
                                        name="module_quick_create_fields[]"
                                        class="select2" disabled="disabled" multiple="multiple">
                                    {foreach key=KEY item=ITEM from=$MODULE_QUICK_CREATE_FIELDS}
                                        <option value="{$KEY}" data-info='{Zend_Json::encode($ITEM)}'
                                                selected="selected">
                                            {vtranslate($ITEM['fieldlabel'], $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="fieldLabel medium">
                                <label class="muted pull-right marginRight10px">{vtranslate('LBL_LINKED_MODULES', $MODULE)}</label>
                            </td>
                            <td class="fieldValue medium">
                                <div class="row-fluid">
                            <span class="span10">
                                <select id="{$MODULE}_editView_fieldName_module_links" name="module_links[]"
                                        class="select2" disabled="disabled" multiple="multiple">
                                    {foreach key=KEY item=ITEM from=$MODULE_LINKS}
                                        <option value="{$ITEM['module_name']}" data-info='{Zend_Json::encode($ITEM)}'
                                                selected="selected">
                                            {vtranslate($ITEM['module_label'], $MODULE)}
                                        </option>
                                    {/foreach}
                                </select>
                            </span>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <br>

                    <div class="row-fluid">
                        <div class="pull-right">
                            <button class="btn btn-success" type="submit" disabled="disabled">
                                <strong>{vtranslate('LBL_SAVE', $MODULE)}</strong>
                            </button>
                            <a class="cancelLink" type="reset"
                               onclick="window.history.back();">{vtranslate('LBL_CANCEL', $MODULE)}</a>
                            <div class="clearfix"></div>
                        </div>
                        <br>
                </form>
            </div>
        </div>
    </div>
{/strip}
<style>
    .cell-icon:hover {
        background: #AAAAAA;
    }
</style>


<!-- Modal -->
<div class="modal fade" id="ModalIcons" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle"
     aria-hidden="true">
    <div class="modal-dialog" role="document" style="width: 680px">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Modal Header</h4>
            </div>
            <div class="modal-body" style="max-height: 500px; overflow: scroll; overflow-x: hidden;">
                <div class="form">
                    {assign var=LISTICONS_LENGTH value=(count($LISTICONS) -1)}
                    {assign var=INDEX value = 0 }
                    <table data-length="{$LISTICONS_LENGTH}" border="1px solid #cccccc">
                        {foreach from = $LISTICONS item =val key=k }
                            {assign var=MODE4OK value=(($INDEX mod 14) == 0)}
                            {if $MODE4OK}
                                <tr>
                            {/if}
                            <td style="padding: 5px;" class="cell-icon">
                                <span class="{$k} icon-module" style="font-size: 30px; vertical-align: middle;" data-info="{$val}"></span>
                            </td>
                            {if ($INDEX mod 14) == 13 or $LISTICONS_LENGTH == $INDEX}
                                </tr>
                            {/if}
                            <input type="hidden" value="{$INDEX++}">

                        {/foreach}

                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-submit">Save</button>
            </div>
        </div>
    </div>
</div>
