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
    <select name="sel_desc4you_{$FIELDNAME}" id="sel_desc4you_{$FIELDNAME}">
        {foreach from=$DESCRIPTIONS item=descriptionname key=descriptionid}
            <option value="{$descriptionid}">{$descriptionname}</option>
            {/foreach}
    </select>&nbsp;
    <input type="button" onclick="ITS4YouDescriptions_Editing_Js.replaceInTextarea('{$FIELDNAME}', '{$FORMODULE}');" value="Replace" class="btn btn-warning">&nbsp;
    <input type="button" onclick="ITS4YouDescriptions_Editing_Js.addToTextarea('{$FIELDNAME}', '{$FORMODULE}');" value="Add" class="btn btn-warning">&nbsp; &nbsp; &nbsp;
    {*<input type="button" onclick="ITS4YouDescriptions_Editing_Js.showModalEditWindow('{$FIELDNAME}');" value="Edit" class="btn btn-info">*}
{/strip}