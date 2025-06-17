{*<!--
/* ********************************************************************************
* The content of this file is subject to the Custom Header/Bills ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */
-->*}
<div class="main-container clearfix" style="position: inherit!important;">
    <div id="modnavigator" class="module-nav editViewModNavigator" >
        <div class="hidden-xs hidden-sm mod-switcher-container" >
            {include file="partials/Menubar.tpl"|vtemplate_path:$MODULE}
        </div>
    </div>
    <div class="elements-db" style="display: none;">
        <div class="tab-detail element-tab active">
            <ul class="elements-accordion">
                <li class="elements-accordion-item" data-type="Email Template">
                    <a class="elements-accordion-item-title">Email Template</a>
                    <div class="elements-accordion-item-content" style="display: block;">
                        <div class="emailtemplates-content-box ">
                            <input id="emailtemplateid" name="emailtemplateid" value="" type="hidden">
                            <input id="isDuplicate" name="isDuplicate" value="{$isDuplicate}" type="hidden">
                            <label>{vtranslate('LBL_TEMPLATE_NAME', $MODULE)} <span class="redColor">*</span></label>
                            <input id="EmailTemplates_editView_fieldName_templatename" name="templatename" value="" type="text" class="inputElement" >
                            <hr style="border-top: none;">
                            <label>{vtranslate('LBL_SUBJECT', $MODULE)} <span class="redColor">*</span></label>
                            <input id="EmailTemplates_editView_fieldName_subject" name="subject" value="" type="text" class="inputElement" >
                            <hr style="border-top: none;">
                            <label>{vtranslate('LBL_DESCRIPTION', $MODULE)} </label>
                            <textarea id="description" name="description" value="" type="text" class="inputElement" row="5" style="height: 150px;"></textarea>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <div class="tab-preview element-tab">
            <ul class="elements-accordion">
                <li class="elements-accordion-item" data-type="Preview">
                    <a class="elements-accordion-item-title">Preview</a>
                </li>
            </ul>
        </div>
        <div class="tab-elements element-tab">
            <ul class="elements-accordion">
                {foreach key=BLOCK_LABEL_KEY item=BLOCK_ITEM from=$BLOCK_CATEGORY}
                    <li class="elements-accordion-item" data-type="{$BLOCK_ITEM['name']}"><a class="elements-accordion-item-title">{$BLOCK_ITEM['name']}</a>
                        <div class="elements-accordion-item-content">
                            <ul class="elements-list">
                                {assign var=ITEMS value=$MODULE_MODEL->getBlocksByCat({$BLOCK_ITEM['id']})}
                                {foreach key=ITEM_KEY item=ITEM from=$ITEMS}
                                    <li>
                                        <div class="elements-list-item">
                                            <div class="preview">
                                                <div class="elements-item-icon">
                                                    <i class="{$ITEM['icon']}"></i>
                                                </div>
                                                <div class="elements-item-name">
                                                    {$ITEM['name']}
                                                </div>
                                            </div>
                                            <div class="view">
                                                <div class="sortable-row">
                                                    <div class="sortable-row-container">
                                                        <div class="sortable-row-actions">
                                                            <div class="row-move row-action">
                                                                <i class="fa fa-arrows-alt"></i>
                                                            </div>
                                                            <div class="row-remove row-action">
                                                                <i class="fa fa-remove"></i>
                                                            </div>
                                                            <div class="row-duplicate row-action">
                                                                <i class="fa fa-files-o"></i>
                                                            </div>
                                                            <div class="row-code row-action">
                                                                <i class="fa fa-code"></i>
                                                            </div>
                                                        </div>
                                                        {assign var=property value=","|explode:$ITEM['property']}
                                                        <div class="sortable-row-content" data-id="{$ITEM['id']}" data-types="{$ITEM['property']}" data-last-type="{$property[0]}">
                                                            {decode_html($ITEM['html']|replace:'[site-url]':$ACTUAL_LINK)}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                {/foreach}
                            </ul>
                        </div>
                    </li>
                {/foreach}
            </ul>
        </div>
    </div>
    {*elementsHTML END*}
    {*elementsProperty START*}
    <div class="elements-property" style="display: none">
        <div class="tab-property element-tab">
            <ul class="elements-accordion">
                <li class="elements-accordion-item" data-type="bg-general">
                    <a class="elements-accordion-item-title"> {vtranslate('propertyBG', $MODULE)}</a>
                    <div class="elements-accordion-item-content clearfix">
                        <input type="hidden" name="bg_color_inner" value="" />
                        <div>Outer backspaceground color :<br/><div id="bg-color-outer" class="bg-color bg-item" setting-type="background-color"> <i class="fa fa-adjust"></i> </div></div>
                        <div style="margin-top:34px">Inner background color :<br/><div id="bg-color-inner" class="bg-color bg-item" setting-type="background-color"> <i class="fa fa-adjust"></i> </div></div>
                    </div>
                </li>
                <li class="elements-accordion-item" data-type="color-line">
                    <a class="elements-accordion-item-title">Line</a>
                    <div class="elements-accordion-item-content clearfix">
                        <div>Line color:<div id="color-line" class="bg-color bg-item" setting-type="background-color" style="float:unset;"> <i class="fa fa-adjust"></i> </div></div>
                        <div>Line height:<div class=" element-boxs clearfix " style="height:unset">        <div class="small-boxs col-sm-6"> <div class="row">   <input type="text" class="form-control number line-height" style="height: 30px;">   </div></div> </div></div>
                    </div>
                </li>
                <li class="elements-accordion-item" data-type="background">
                    <a class="elements-accordion-item-title"> {vtranslate('propertyBG', $MODULE)}</a>
                    <div class="elements-accordion-item-content clearfix">
                        <div id="bg-color" class="bg-color bg-item" setting-type="background-color"> <i class="fa fa-adjust"></i> </div>

                    </div>
                </li>
                <li class="elements-accordion-item" data-type="padding">
                    <a class="elements-accordion-item-title"> {vtranslate('propertyPadding', $MODULE)}</a>
                    <div class="elements-accordion-item-content">
                        <div class=" element-boxs clearfix ">
                            <div class="big-box col-sm-6 ">
                                <input type="text" class="form-control padding all" setting-type="padding">
                            </div>
                            <div class="small-boxs col-sm-6">
                                <div class="row">
                                    <input type="text" class="form-control padding number" setting-type="padding-top">
                                </div>
                                <div class="row clearfix">
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control padding number" setting-type="padding-left">
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control padding number" setting-type="padding-right">
                                    </div>
                                </div>
                                <div class="row">
                                    <input type="text" class="form-control padding number" setting-type="padding-bottom">
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="elements-accordion-item" data-type="border-radius">
                    <a class="elements-accordion-item-title"> {vtranslate('propertyBorderRadius', $MODULE)}</a>
                    <div class="elements-accordion-item-content">
                        <div class=" element-boxs border-radius-box clearfix ">
                            <div class="big-box col-sm-6 ">
                                <input type="text" class="form-control border-radius all" setting-type="border-radius">
                            </div>
                            <div class="small-boxs col-sm-6">
                                <div class="row clearfix">
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control border-radius" setting-type="border-top-left-radius">
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control border-radius" setting-type="border-top-right-radius">
                                    </div>
                                </div>
                                <div class="row clearfix margin">
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control border-radius" setting-type="border-bottom-left-radius">
                                    </div>
                                    <div class="col-sm-6">
                                        <input type="text" class="form-control border-radius" setting-type="border-bottom-right-radius">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="elements-accordion-item" data-type="text-style">
                    <a class="elements-accordion-item-title"> {vtranslate('propertyTextStyle', $MODULE)}</a>
                    <div class="elements-accordion-item-content">
                        <div class="element-boxs text-style-box clearfix ">
                            <div class="element-font-family col-sm-8">
                                <select class="form-control font-family" setting-type="font-family">
                                    <option value="Arial">Arial</option>
                                    <option value="Helvetica">Helvetica</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Verdana">Verdana</option>
                                    <option value="Tahoma">Tahoma</option>
                                    <option value="Calibri">Calibri</option>
                                </select>
                            </div>
                            <div class="element-font-size col-sm-4">
                                <input type="text" name="name" class="form-control number" value="14" setting-type="font-size" />
                            </div>
                            <div class="icon-boxs text-icons clearfix">
                                <div class="icon-box-item fontStyle" setting-type="font-style" setting-value="italic">
                                    <i class="fa fa-italic"></i>
                                </div>
                                <div class="icon-box-item active underline " setting-type="text-decoration" setting-value="underline">
                                    <i class="fa fa-underline"></i>
                                </div>
                                <div class="icon-box-item line " setting-type="text-decoration" setting-value="line-through">
                                    <i class="fa fa-strikethrough"></i>
                                </div>
                            </div>
                            <div class="icon-boxs align-icons clearfix">
                                <div class="icon-box-item left active">
                                    <i class="fa fa-align-left"></i>
                                </div>
                                <div class="icon-box-item center ">
                                    <i class="fa fa-align-center"></i>
                                </div>
                                <div class="icon-box-item right">
                                    <i class="fa fa-align-right"></i>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                            <div class="icon-boxs text-icons ">
                                <div id="text-color" class="icon-box-item text-color" setting-type="color">
                                </div>
                                Text Color
                            </div>
                            <div class="icon-boxs font-icons clearfix">
                                <div class="icon-box-item" setting-type="bold">
                                    <i class="fa fa-bold"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="elements-accordion-item" data-type="social-content">
                    <a class="elements-accordion-item-title"> {vtranslate('propertySocialContent', $MODULE)}</a>
                    <div class="elements-accordion-item-content">
                        <div class="social-content-box clearfix">
                            <div data-social-type="instagram">
                                <label class="small-title">Instagram</label>
                                <input type="text" name="name" value="#" class="social-input" />
                                <label class="checkbox-title">
                                    <input type="checkbox" name="name" /> Show
                                </label>
                            </div>
                            <div data-social-type="pinterest">
                                <label class="small-title">Pinterest</label>
                                <input type="text" name="name" value="#" class="social-input" />
                                <label class="checkbox-title">
                                    <input type="checkbox" name="name" /> Show
                                </label>
                            </div>
                            <div data-social-type="google-plus">
                                <label class="small-title">Google+</label>
                                <input type="text" name="name" value="#" class="social-input" />
                                <label class="checkbox-title">
                                    <input type="checkbox" name="name" checked /> Show
                                </label>
                            </div>
                            <div data-social-type="facebook">
                                <label class="small-title">Facebook</label>
                                <input type="text" name="name" value="#" class="social-input" />
                                <label class="checkbox-title">
                                    <input type="checkbox" name="name" checked /> Show
                                </label>
                            </div>
                            <div data-social-type="twitter">
                                <label class="small-title">Twitter</label>
                                <input type="text" name="name" value="#" class="social-input" />
                                <label class="checkbox-title">
                                    <input type="checkbox" name="name" checked /> Show
                                </label>
                            </div>
                            <div data-social-type="linkedin">
                                <label class="small-title">Linkedin</label>
                                <input type="text" name="name" value="#" class="social-input" />
                                <label class="checkbox-title">
                                    <input type="checkbox" name="name" checked /> Show
                                </label>
                            </div>
                            <div data-social-type="youtube">
                                <label class="small-title">Youtube</label>
                                <input type="text" name="name" value="#" class="social-input" />
                                <label class="checkbox-title">
                                    <input type="checkbox" name="name" checked /> Show
                                </label>
                            </div>
                            <div data-social-type="skype">
                                <label class="small-title">Skype</label>
                                <input type="text" name="name" value="#" class="social-input" />
                                <label class="checkbox-title">
                                    <input type="checkbox" name="name" checked /> Show
                                </label>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="elements-accordion-item" data-type="youtube-frame">
                    <a class="elements-accordion-item-title">Youtube</a>
                    <div class="elements-accordion-item-content">
                        <div class="social-content-box ">
                            <label>Youtube Url</label>
                            <input type="text" class=" youtube" setting-type=""><span class="muted" style="font-size: 13px;">Example: https://www.youtube.com/watch?v=_eThAg5OrHQ</span>
                        </div>
                    </div>
                </li>
                <li class="elements-accordion-item" data-type="width">
                    <a class="elements-accordion-item-title"> {vtranslate('propertyEmailWidth', $MODULE)}</a>
                    <div class="elements-accordion-item-content">
                        <div class="social-content-box ">
                            <label>Width</label>
                            <input type="text" class="email-width number" setting-type="">
                            <span class="help"> {vtranslate('propertyEmailWidthHelp', $MODULE)}</span>
                        </div>
                    </div>
                </li>
                <li class="elements-accordion-item" data-type="image-settings">
                    <a class="elements-accordion-item-title"> {vtranslate('propertyImageSettings', $MODULE)}</a>
                    <div class="elements-accordion-item-content">
                        <div class="social-content-box ">
                            <div class="change-image"> {vtranslate('propertyChangeImage', $MODULE)}</div>
                            <label>Image width</label>
                            <input type="text" class="image-width  image-size " setting-type="" >
                            <label>Image height</label>
                            <input type="text" class="image-height  image-size" setting-type="">
                            <label>Choose image</label>
                            <div class="fileUploadBtn btn btn-primary" style="width: 100%;border-radius: 3px;">
                                <span><i class="fa fa-laptop"></i> Upload</span>
                                <input type="file" class="inputElement" name="fileToUpload" id="fileToUpload">
                            </div>
                            <label>Image URL</label>
                            <input type="text" class="image-url  image-size" setting-type="">
                        </div>
                    </div>
                </li>
                <li class="elements-accordion-item" data-type="hyperlink">
                    <a class="elements-accordion-item-title"> {vtranslate('propertyHyperlink', $MODULE)}</a>
                    <div class="elements-accordion-item-content">
                        <div class="social-content-box ">
                            <label>Url</label>
                            <input type="text" class="hyperlink-url" setting-type="">
                        </div>
                    </div>
                </li>
                <li class="elements-accordion-item" data-type="button">
                    <a class="elements-accordion-item-title"> {vtranslate('propertyButton', $MODULE)}</a>
                    <div class="elements-accordion-item-content">
                        <div class="social-content-box ">
                            <label>Text</label>
                            <input type="text" class="button-text" setting-type="">
                        </div>
                        <div class="social-content-box ">
                            <label>Hyperlink</label>
                            <input type="text" class="button-hyperlink" setting-type="">
                        </div>
                        <div class="social-content-box ">
                            <label>Text color</label><br>
                            <div id="button-text-color" class="bg-color bg-item" setting-type="">
                                <i class="fa fa-adjust"></i>
                            </div>
                        </div><br>
                        <div class="social-content-box ">
                            <br><label>Background color</label><br>
                            <div id="button-bg-color" class="bg-color bg-item" setting-type="">
                                <i class="fa fa-adjust"></i>
                            </div>
                        </div>
                        <div class="social-content-box "><br><br>

                            <label class="checkbox-title"><input type="checkbox" name="button-full-width" checked class="button-full-width"> Full width</label>
                        </div>
                    </div>
                </li>
                <li class="elements-accordion-item " data-type="email-template">
                    <a class="elements-accordion-item-title">Merge Fields</a>
                    <div class="elements-accordion-item-content" style="display: block!important;">
                        <div class="emailtemplates-content-box ">
                            <input id="contentSelectionStart" name="contentSelectionStart" value="" type="hidden">
                            <label>{vtranslate('LBL_SELECT_FIELD_TYPE', $MODULE)} {*<span class="redColor">*</span>*}</label>
                            <span class="filterContainer" >
                            <input type=hidden name="moduleFields" data-value='{Vtiger_Functions::jsonEncode($ALL_FIELDS)}' />
                            <span class="conditionRow">
                            <select class="inputElement" name="modulename" {*data-rule-required="true"*}>
                                <option value="">{vtranslate('LBL_SELECT_MODULE',$MODULE)}</option>
                                {foreach key=MODULENAME item=FIELDS from=$ALL_FIELDS}
                                    <option value="{$MODULENAME}" {if $RECORD->get('module') eq $MODULENAME}selected{/if}>{vtranslate($MODULENAME, $MODULENAME)}</option>
                                {/foreach}
                            </select>
                            </span>
                            </span>
                            <hr style="border-top: none;">
                            <label>{vtranslate('LBL_FIELDS', $MODULE)}</label><br>
                            <select class="inputElement" id="templateFields" name="templateFields" style="width: 85%!important;">
                                <option value="">{vtranslate('LBL_NONE',$MODULE)}</option>
                            </select>
                            <button type="button" class="btn btn-default btn-sm" id="btnTemplateFields">
                                <span class="glyphicon glyphicon-arrow-right"></span>
                            </button>
                            <hr style="border-top: none;">
                            <label>{vtranslate('LBL_GENERAL_FIELDS', $MODULE)}</label>
                            <select class="inputElement" id="generalFields" name="generalFields" style="width: 85%!important;">
                                <option value="">{vtranslate('LBL_NONE',$MODULE)}</option>
                                <optgroup label="{vtranslate('LBL_COMPANY_DETAILS','Settings:Vtiger')}">
                                    {foreach key=index item=COMPANY_FIELD from=$COMPANY_FIELDS}
                                        <option value="{{$COMPANY_FIELD[1]}}">{$COMPANY_FIELD[0]}</option>
                                    {/foreach}
                                </optgroup>
                                <optgroup label="{vtranslate('LBL_GENERAL_FIELDS', $MODULE)}">
                                    {foreach key=index item=GENERAL_FIELD from=$GENERAL_FIELDS}
                                        <option value="{$GENERAL_FIELD[1]}">{$GENERAL_FIELD[0]}</option>
                                    {/foreach}
                                </optgroup>
                            </select>
                            <button type="button" class="btn btn-default btn-sm" id="btnGeneralFields">
                                <span class="glyphicon glyphicon-arrow-right"></span>
                            </button>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
    {*elementsProperty END*}
    <textarea name="base64image" style="display: none !important;"></textarea>
    <div class="editor">
    </div>
    <div id="previewModal" class="modal fade" role="dialog">
        <div class="modal-dialog modal-lg">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Preview</h4>
                </div>
                <div class="modal-body">
                    <div class="">
                        <label for="">URL : </label> <span class="preview_url"></span>
                    </div>
                    <iframe id="previewModalFrame" width="100%" height="400px"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div id="demp"></div>
</div>
