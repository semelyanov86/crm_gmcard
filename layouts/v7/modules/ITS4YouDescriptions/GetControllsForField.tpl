{*<!--
/* * *******************************************************************************
 * The content of this file is subject to the ITS4YouDescriptions license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}
{strip}
    <div class="table">
        <select name="sel_desc4you_{$FIELDNAME}" class="select2" id="sel_desc4you_{$FIELDNAME}" style="min-width: 200px; width: 40%; max-width: 400px;">
            {html_options  options=$DESCRIPTIONS}
        </select>&nbsp;&nbsp;
        <input type="hidden" name="fieldname" value="{$FIELDNAME}">
        <input type="hidden" name="fieldlabel" value="{$FIELDLABEL}">
        <input type="hidden" name="formodule" value="{$FORMODULE}">
        <input type="button" value="Replace" class="btn btn-success desc4you-success" style="margin-top: -3px;">&nbsp;
        <input type="button" value="Add" class="btn btn-success desc4you-success" style="margin-top: -3px;">
    </div>
{/strip}