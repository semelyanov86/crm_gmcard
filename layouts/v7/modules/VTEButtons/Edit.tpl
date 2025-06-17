{*<!-- /* ********************************************************************
************ * The content of this file is subject to the Custom Header/Bills
("License"); * You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com * Portions
created by VTExperts.com. are Copyright(C) VTExperts.com. * All Rights
Reserved. *
******************************************************************************
*/ -->*} <style>     
#header-colorpicker{
    position: relative;
    width: 36px;
    height: 36px;
    background:
    url('layouts/v7/modules/VTEButtons/resources/select.png');    
}     
#header-colorpicker p {
    position: absolute;    
    top: 3px;        
    left:3px;        
    width: 30px;        
    height: 30px;         
    background: url('layouts/v7/modules/VTEButtons/resources/select.png') center;
    cursor: pointer;    
}   
.header-input-text{        
   padding-left: 5px;
}    
.rcorners{        
     border-radius: 2px;    
     padding: 5px 10px;
     width: auto;    
     font-size: 13px;   
     float: right;   
}     
label{
    font-family: 'OpenSans-Regular', sans-serif !important;   
    font-weight: 500 !important;
}
.popover {
    max-width: 600px;
    width: 400px;
}

</style>
{strip}
<div class="container-fluid WidgetsManage">
    <link type="text/css" rel="stylesheet" href="libraries/jquery/colorpicker/css/colorpicker.css" media="screen">
    <script type="text/javascript" src="libraries/jquery/colorpicker/js/colorpicker.js"></script>
    <div class="widget_header row">
        <div class="col-sm-6"><h4 style="margin-top: 0px !important; margin-bottom: 0px !important"><label>{vtranslate('Button Details', 'VTEButtons')}</label>
        </div>
    </div>
    <hr style="margin-top: 5px !important">
    <div class="clearfix"></div>
    <form id="EditVTEButtons" action="index.php" method="post" name="EditVTEButtons">
        <div class="editViewPageDiv">
            <input type="hidden" name="module" id="module" value="{$MODULE}">
            <input type="hidden" name="action" value="SaveAjax" />
            <input type="hidden" name="record" id="record" value="{$RECORD}">
            <input type="hidden" id="stdfilterlist" name="stdfilterlist" value=""/>
            <input type="hidden" id="advfilterlist" name="advfilterlist" value=""/>
            <input type="hidden" id="strfieldslist" name="strfieldslist" value=""/>
            <div class="col-sm-12 col-xs-12">
                <div class="col-sm-6 col-xs-6 form-horizontal">
                    <div class="form-group">
                        <label for="custom_expenses_module" class="control-label col-sm-3">
                            <span>{vtranslate('Module', 'VTEButtons')}</span>
                            <span class="redColor">*</span>
                        </label>
                        <div class="col-sm-8">
                            <select class="inputElement select2" id="custom_module" name="custom_module" data-rule-required="true">
                                <option value="">{vtranslate('Select an Option', 'VTEButtons')}</option>
                                {assign var='EXCLUDE_MODULE_ARRAY' value=','|explode:"Quotes,PurchaseOrder,SalesOrder,Services,Products"}
                                {foreach item=MODULE_VALUES from=$ALL_MODULES}
                                {* {if in_array($MODULE_VALUES->name, $EXCLUDE_MODULE_ARRAY)}
                                {continue}
                                {/if}*}
                                <option value="{$MODULE_VALUES->name}" {if $MODULE_VALUES->name eq $RECORDENTRIES['module']}selected{/if}>{vtranslate($MODULE_VALUES->label,$MODULE_VALUES->name)}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="custom_expenses_name" class="control-label col-sm-3">
                            <span>{vtranslate('Button Title', 'VTEButtons')}</span>
                            <span class="redColor">*</span>
                        </label>
                        <div class="col-sm-8">
                            <input class="inputElement header-input-text" id="header" name="header" value="{$RECORDENTRIES['header']}" data-rule-required="true">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="custom_expenses_quantity" class="control-label col-sm-3">
                            <span>{vtranslate('Icon/Label', 'VTEButtons')}</span>
                            <span class="redColor">*</span>
                        </label>
                        <div class="col-sm-8 icon-section">
                            <button type="button" class="btn btn-primary btnicon" data-toggle="modal"
                            data-target="#ModalIcons">
                            {vtranslate('Select Icon', 'VTEButtons')}
                        </button>
                        <span class="icon-module {$RECORDENTRIES['icon']}" id="icon-module" style="font-size: 30px; vertical-align: middle; padding-left: 11px;"></span>
                        <input type="hidden" id="icon" name="icon" value="{$RECORDENTRIES['icon']}">
                        <span id="header-colorpicker">
                            <p style="background-color: #{if $RECORDENTRIES['color'] !=''}{$RECORDENTRIES['color']}{else}1969e8{/if};margin-left: 9px;margin-top: -8px;;"></p>
                        </span>
                        <div class="header-div header-preview-section" style="float: right;">
                            <div class="rcorners" style="float:left;border: 2px solid #{if $RECORDENTRIES['color'] !=''}{$RECORDENTRIES['color']}{else}1969e8{/if};color:#{if $RECORDENTRIES['color'] !=''}{$RECORDENTRIES['color']}{else}1969e8{/if}; ">
                                <span id="icon-span" class="icon-module {$RECORDENTRIES['icon']}" style="font-size: 17px;color: #{$RECORDENTRIES['color']};"></span>
                                <span class="l-header"
                                style="vertical-align: left; padding-left: 11px;">{$RECORDENTRIES['header']}</span>
                            </div>
                        </div>
                        <input type="hidden" id="color" name="color" value="{if $RECORDENTRIES['color'] !=''}{$RECORDENTRIES['color']}{else}1969e8{/if}">
                    </div>
                </div>
                <div class="form-group">
                    <label for="sequence" class="control-label col-sm-3">
                        <span>{vtranslate('Sequence', 'VTEButtons')}</span>
                    </label>
                    <div class="col-sm-8">
                        <input class="inputElement header-input-text" id="sequence" name="sequence" value="{$RECORDENTRIES['sequence']}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-sm-3">
                        <span>{vtranslate('LBL_SHOW_IN_MOBILE', 'VTEButtons')}</span>
                    </label>
                    <div class="col-sm-8">
                        <select class="inputElement select2" id="show_in_mobile" name="show_in_mobile">
                            <option value="1" selected="selected">{vtranslate('YES', 'VTEButtons')}</option>
                            <option value="0" {if $RECORDENTRIES['show_in_mobile'] eq 0} selected {/if} >{vtranslate('NO', 'VTEButtons')}</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="active" class="control-label col-sm-3">
                        <span>{vtranslate('Active', 'VTEButtons')}</span>
                    </label>
                    <div class="col-sm-8">
                        <select class="inputElement select2" id="active" name="active">
                            <option value="Active" selected="selected">Active</option>
                            <option value="Inactive" {if $RECORDENTRIES['active'] eq 'Inactive'}selected="" {/if}>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xs-6 custom-header-info">
                <div class="label-info">
                    <h5>
                        <span class="glyphicon glyphicon-info-sign"></span> Info
                    </h5>
                </div>
                <span>
                    Once the module is configured, the buttons will show up on the record detail view.<br><br>
                    <b>Button Title:</b> Name of button (i.e Update Address).<br><br>
                    <b>Icon/Label:</b> Icon will be displayed in front of button/value.<br><br>
                    <b>Sequence:</b> You can sequence in which buttons show up.<br><br>
                    <b>Status:</b> Turn this button on or off.<br><br>
                    <b>Fields:</b> Select fields to be displayed when then button is clicked.<br><br>
                    <b>Condition (optional):</b> Specify condition when the button should be shown. For example, show button "Update Address" if Billing Street, City, State is empty.
                </span>
            </div>
        </div>
        <div class="modal-overlay-footer clearfix">
            <div class="row clearfix">
                <div class="textAlignCenter col-lg-12 col-md-12 col-sm-12 ">
                    <button type="button" class="btn btn-success buttonSave">Save</button>&nbsp;&nbsp;
                    <a class="cancelLink" href="javascript:history.back()" type="reset">Cancel</a>
                </div>
            </div>
        </div>
    </div>
    <div class="widget_header row">
        <div class="col-sm-6"><h4 style="margin-top: 0px !important; margin-bottom: 0px !important"><label>{vtranslate('Modal Popup', 'VTEButtons')}</label>
        </div>
    </div>
    <hr style="margin-top: 5px !important">
    <div class="clearfix"></div>
    <div class="editViewPageDiv">
        <div class="col-sm-12 col-xs-12 form-horizontal">

            <div class="form-group fields-in-popup " style="padding-left: 15px;">
                <label for="custom_expenses_quantity" class="control-label col-sm-2">
                    <span>{vtranslate('Fields on Popup', 'VTEButtons')}</span>&nbsp;&nbsp;<a class="info-icon" data-toggle="popover" data-placement="top" style="color: #333" data-content="Fields selected will be displayed when the button is clicked. If you leave this blank, then only the 'Silent Field Update' will be updated (automatically without any popup). <br><br>*If you select fields here + 'Silent Field', then you will be able to set values for the fields on the popup plus the 'Silent Field' will be set automatically (based on configuration)." href="javascript:void(0)" data-original-title="" title="" aria-describedby="popover613897">
                        <i class="fa fa fa-info-circle"></i>
                    </a>
                </label>
                <div class="col-sm-10 columnsSelectDiv">
                    {if is_array($RECORDENTRIES['field_name'])}
                        {assign var=RECORDENTRIES_FIELD_NAME value=$RECORDENTRIES['field_name']}
                    {else}
                        {assign var=RECORDENTRIES_FIELD_NAME value=array()}
                    {/if}
                    <select data-placeholder="Select columns" multiple class="select2 columnsSelect select2-offscreen" id="field_name" name="field_name" style="width:100%;">
                        {foreach item=FIELD_NAME from=$RECORDENTRIES_FIELD_NAME}
                        {assign var=CUR_FIELD_LABEL value={$MODULE_MODEL->getFieldLabel({$SELECTED_MODULE_NAME},{$FIELD_NAME})}}
                        <option value="{$FIELD_NAME}" data-field-name="{$FIELD_NAME}" selected>{vtranslate($CUR_FIELD_LABEL, $SOURCE_MODULE)}</option>
                        {/foreach}
                        {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
                        {if $BLOCK_LABEL eq 'LBL_ITEM_DETAILS'}{continue}{/if}
                        <optgroup label='{vtranslate($BLOCK_LABEL, $SOURCE_MODULE)}'>
                            {foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
                            {if in_array($FIELD_NAME, $RECORDENTRIES_FIELD_NAME)}
                            {continue}
                            {/if}
                            {assign var=CUR_FIELD_NAME value=$FIELD_NAME|substr:0:6}
                            {if $FIELD_MODEL->get('uitype') eq "72" || $FIELD_MODEL->get('uitype') eq "83" || $CUR_FIELD_NAME eq "cf_acf" || $FIELD_NAME eq 'cf_team'|| $FIELD_NAME eq 'cf_teammembers'}{continue}{/if}
                            {if $FIELD_MODEL->isEditable() eq true}
                            <option value="{$FIELD_NAME}" data-field-name="{$FIELD_NAME}">{vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE)}</option>
                            {/if}
                            {/foreach}
                        </optgroup>
                        {/foreach}
                    </select>
                    {if $RECORDENTRIES_FIELD_NAME !=''}
                    <input type="hidden" name="selected_fields" value="{$RECORDENTRIES_FIELD_NAME}">
                    {/if}
                </div>
            </div>
            <div class="form-group fields-in-automated-update " style="padding-left: 15px;">
                <label for="custom_expenses_quantity" class="control-label col-sm-2">
                    <span>{vtranslate('Silent Field Update', 'VTEButtons')}</span>&nbsp;&nbsp;<a class="info-icon" data-toggle="popover" data-placement="top" style="color: #333" data-content='If you select Silent Field and do not select any "Fields on popup", then the popup will not be shown and the silent field will be updated as soon as the button is clicked. (It&#39;s basically a button to set silent field value).<br><br>*If you select Silent Field and SELECT additional fields on "Fields on Popup", then you will be able to set values for the fields on the popup plus the "Silent Field" will be set automatically (only when save is clicked on popup).' href="javascript:void(0)" data-original-title="" title="" aria-describedby="popover613897">
                        <i class="fa fa fa-info-circle"></i>
                    </a>
                </label>
                <div class="col-sm-10 columnsSelectDiv">
                    <div class="row">
                        <div class="col-lg-6 col-sm-6">
                            <select id="automated_update_picklist_field" name="automated_update_picklist_field" class="select2 select2-offscreen" style="width: 200px;">
                                <option value="">Select an option..</option>
                                {if !empty($PICKLIST_FIELDS)}
                                {foreach from=$PICKLIST_FIELDS item=PICKLIST_FIELD_LABEL key=PICKLIST_FIELD_NAME}
                                <option value="{$PICKLIST_FIELD_NAME}" {if $PICKLIST_FIELD_NAME == $RECORDENTRIES['automated_update_field']}selected{/if}>{$PICKLIST_FIELD_LABEL}</option>
                                {/foreach}
                                {/if}
                            </select>
                        </div>
                        <div class="col-lg-6 col-sm-6">
                            <select id="automated_update_picklist_value" name="automated_update_picklist_value" class="select2 select2-offscreen" style="width: 200px;">
                                <option value="">Select an option..</option>
                                {if !empty($SELECTED_PICKLIST_FIELD_VALUES)}
                                {foreach from=$SELECTED_PICKLIST_FIELD_VALUES item=VALUE key=DISPLAY}
                                <option value="{$VALUE}" {if $VALUE == $RECORDENTRIES['automated_update_value']}selected{/if}>{$DISPLAY}</option>
                                {/foreach}
                                {/if}
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group marginBottom10px" style="padding-left: 15px;">
                <label for="custom_expenses_quantity" class="control-label col-sm-2">
                    <span>{vtranslate('Condition (optional)', 'VTEButtons')}</span>&nbsp;&nbsp;<a class="info-icon" data-toggle="popover" data-placement="top" style="color: #333" data-content='Buttons can be shown or hidden based on certain condition. You can set the condition to only show "Close Ticket" button if ticket.status is "In Progress".. (very flexible).' href="javascript:void(0)" data-original-title="" title="" aria-describedby="popover613897">
                        <i class="fa fa fa-info-circle"></i>
                    </a>
                </label>
                <div class="col-sm-10 row ">
                    <div class="col-sm-12 vte-advancefilter">
                        <div class="" id="table-conditions" >
                            {include file=vtemplate_path('AdvanceFilter.tpl','VTEButtons') MODULE='VTEButtons'}
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <label class="col-lg-2 col-md-2 col-sm-2 control-label">
                    <span>Condition (permission based - optional)</span>&nbsp;&nbsp;<a class="info-icon" data-toggle="popover" data-placement="top" style="color: #333" data-content='Buttons can be shown or hidden based on certain condition. You can set the condition to only show "Close Ticket" button if ticket.status is "In Progress".. (very flexible).' href="javascript:void(0)" data-original-title="" title="" aria-describedby="popover613897">
                        <i class="fa fa fa-info-circle"></i>
                    </a>
                </label>
                <div class="fieldValue col-lg-10 col-md-10 col-sm-10">
                    <div class="row">
                        {assign var="GROUP_MEMBERS" value=$RECORD_MODEL->getMembers()}
                        {if !is_array($SELECTED_MEMBERS)}
                            {assign var="SELECTED_MEMBERS" value=array()}
                        {/if}
                        <div class="col-lg-8 col-md-8 col-sm-8">
                            <select id="memberList" class="select2 inputElement" multiple="true" name="members[]" data-rule-required="true" data-placeholder="{vtranslate('LBL_ADD_USERS_ROLES', $QUALIFIED_MODULE)}" >
                                {foreach from=$MEMBER_GROUPS key=GROUP_LABEL item=ALL_GROUP_MEMBERS}
                                    <optgroup label="{vtranslate({$GROUP_LABEL}, $QUALIFIED_MODULE)}" class="{$GROUP_LABEL}">
                                        {foreach from=$ALL_GROUP_MEMBERS item=MEMBER}
                                            {if $MEMBER->getName() neq $RECORD_MODEL->getName()}
                                                <option value="{$MEMBER->getId()}" data-member-type="{$GROUP_LABEL}"  {if in_array($MEMBER->getId(),$SELECTED_MEMBERS)} selected="true"{/if}>{trim($MEMBER->getName())}</option>
                                            {/if}
                                        {/foreach}
                                    </optgroup>
                                {/foreach}
                            </select>
                        </div>
                        <div class="groupMembersColors col-lg-3 col-md-3 col-sm-6">
                            <ul class="liStyleNone">
                                <li class="Users textAlignCenter">{vtranslate('LBL_USERS', $QUALIFIED_MODULE)}</li>
                                <li class="Groups textAlignCenter">{vtranslate('LBL_GROUPS', $QUALIFIED_MODULE)}</li>
                                <li class="Roles textAlignCenter">{vtranslate('LBL_ROLES', $QUALIFIED_MODULE)}</li>
                                <li class="RoleAndSubordinates textAlignCenter">{vtranslate('LBL_ROLEANDSUBORDINATE', $QUALIFIED_MODULE)}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="clearfix" style="margin-top: 50px"></div>
        </div>
    </div>
</form>
</div>
{/strip}
<!-- Modal -->
<div class="modal fade" id="ModalIcons" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle"
aria-hidden="true">
<div class="modal-dialog" role="document" style="width: 650px">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Modal Header</h4>
        </div>
        <div class="modal-body">
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