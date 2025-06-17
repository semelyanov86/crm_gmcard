{*<!--
/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}
<div class="row-fluid">
    <div class="span10">
        <ul class="nav nav-list">
            <li><strong>{'Choose_field'|@getTranslatedString:$CURRENT_MODULE}:</strong></li>
            <li>
                <select name="description_fields" id="description_fields" class="detailedViewTextBox" style="width:120%;" size="{$TEXTAREAS_COUNT}">
                    {foreach name="fieldsForeach" from="$TEXTAREAS" item="fieldArr" key="fieldid"}
                        {if $fieldArr.is_default eq '1'}
                            <option value="{$fieldid}" selected="selected">{$fieldArr.fieldlabel|@getTranslatedString:$SOURCE_MODULE}</option>
                        {else}
                            <option value="{$fieldid}">{$fieldArr.fieldlabel|@getTranslatedString:$SOURCE_MODULE}</option>
                        {/if}
                    {/foreach}
                </select>
            </li>
            {*<li><a href="javascript:;" onclick="alert(jQuery('#description_fields').val());return false;" class="webMnu">{'LBL_EDIT'|@getTranslatedString}</a></li>*}
    </div>
    <br clear="all"/>
    <div id="its4youdescriptions_editdiv" style="display:none;"></div>
</div>
