{*/* * *******************************************************************************
* The content of this file is subject to the Kanban View ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}
{strip}
<style>
    #primaryFieldValue ul li.select2-search-choice,.icon-move{
        cursor: move;
    }
</style>
<div class="modal-dialog">
    <div class="modal-content">
        <form class="form-horizontal" id="KanbanConfigure" name="KanbanConfigure" method="post" action="index.php">
            <input type="hidden" name="primaryFieldValue"/>
            <input type="hidden" name="otherField"/>
            <div class="modal-header">
                <div class="clearfix">
                    <div class="pull-right ">
                        <button type="button" class="close" aria-label="Close" data-dismiss="modal">
                            <span aria-hidden="true" class="fa fa-close"></span>
                        </button>
                    </div>
                    <h4 class="pull-left">{vtranslate('LBL_CONFIGURE_KANBAN_VIEW',$MODULE)}</h4>
                </div>
            </div>
            <div class="modal-body" >
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <label class="col-sm-4 control-label fieldLabel">
                            <strong>{vtranslate('LBL_DEFAULT_PAGE',$MODULE)}</strong>
                        </label>
                        <div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
                            <input style="top:7px" type="checkbox" name="isDefaultPage" {if $PRIMARY_SETTING.is_default_page eq 1} checked {/if}/>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <label class="col-sm-4 control-label fieldLabel">
                            <strong>{vtranslate('LBL_PRIMARY_FIELD',$MODULE)}</strong>
                            <span class="redColor">*</span>
                        </label>
                        <div class="fieldValue col-lg-3 col-md-3 col-sm-3 input-group">
                            <select class="select2" name="primaryField" data-rule-required="true">
                                {foreach item=PRIMARY_NAME from=$PRIMARY_FIELDS}
                                    <option value="{$PRIMARY_NAME['fieldid']}" {if $PRIMARY_NAME['fieldid'] eq  $PRIMARY_SETTING['primary_field']} selected {/if}>{$PRIMARY_NAME['fieldlabel']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-xs-12 input-group">
                    <div class="form-group">
                        <div class="fieldValue col-lg-9 col-md-9 col-sm-9 input-group center-block" id="primaryFieldValue">
                            <select id="primaryValueSelectElement" name="primaryFieldValue" multiple class="select2" data-rule-required="true">
                                {foreach item=PRIMARY_FIELD_VALUE key=PRIMARY_FIELD_KEY  from = $PRIMARY_SETTING['value'] }
                                    <option value="{$PRIMARY_FIELD_KEY}" {if $PRIMARY_SETTING['primary_value_setting'] AND in_array($PRIMARY_FIELD_KEY, $PRIMARY_SETTING['primary_value_setting'])} selected {/if}>{vtranslate($PRIMARY_FIELD_VALUE,'HelpDesk')}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="col-sm-12 col-xs-12">
                    <h4 class="textAlignCenter">{vtranslate('LBL_CONFIGURE_TILE_FIELDS',$MODULE)}</h4>
                </div>

                <div class="col-sm-6 col-xs-6 input-group center-block">
                    <div class="form-group">
                        <table class="table table-container listOtherField" style="table-layout: fixed;">
                            <colgroup>
                                <col style="width: 10%">
                                <col style="width: 80%">
                                <col style="width: 10%">
                            </colgroup>
                            {if $PRIMARY_SETTING['other_field']}
                                {foreach item = OTHER_FIELD from=$PRIMARY_SETTING['other_field'] }
                                    <tr class="otherField">
                                        <td width="5%" class="listViewEntryValue">
                                            <span style="line-height: 30px">
                                                <img src="layouts/v7/skins/images/drag.png" class="icon-move alignMiddle" title="Move to Change Priority">
                                            </span>
                                        </td>
                                        <td >{include file="FieldSelect.tpl"|@vtemplate_path:$MODULE MULTIPLE = 0 }</td>
                                        <td>
                                            <span class="otherFieldAction" style="line-height: 30px">
                                                <i title="Delete"  class="fa fa-trash alignMiddle deleteOtherField" style="cursor: pointer"></i>
                                            </span>
                                        </td>
                                    </tr>
                                {/foreach}
                            {else}
                                <tr class="otherField">
                                    <td width="5%" >
                                        <span style="line-height: 30px">
                                            <img src="layouts/v7/skins/images/drag.png" class="icon-move alignMiddle" title="Move to Change Priority">
                                        </span>
                                    </td>
                                    <td >{include file="FieldSelect.tpl"|@vtemplate_path:$MODULE MULTIPLE = 0 OTHER_FIELD = array() }</td>
                                    <td>
                                        <span class="otherFieldAction" style="line-height: 30px">
                                            <i title="Delete"  class="fa fa-trash alignMiddle deleteOtherField" style="cursor: pointer"></i>
                                        </span>
                                    </td>
                                </tr>
                            {/if}

                        </table>
                        <table class="hide fieldBasic">
                            <tr class="otherField">
                                <td>
                                    <span style="line-height: 30px">
                                        <img src="layouts/v7/skins/images/drag.png" class="icon-move alignMiddle" title="Move to Change Priority">
                                    </span>
                                </td>
                                <td>{include file="FieldSelect.tpl"|@vtemplate_path:$MODULE MULTIPLE = 0 NOCHOSEN=true OTHER_FIELD = array() }</td>
                                <td>
                                    <span class="otherFieldAction" style="line-height: 30px">
                                        <i title="Delete"  class="fa fa-trash alignMiddle deleteOtherField" style="cursor: pointer"></i>
                                    </span>
                                </td>
                            </tr>
                        </table>
                        <div class="col-sm-12 col-xs-12" >
                            <div class="form-group">
                                <span class="btn addButton btn-default btnAddMore">{vtranslate('LBL_CLICK_HERE_TO_ADD_MORE',$MODULE)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button class="btn btn-success" id="save_kanbanview_setting" type="button"><strong>{vtranslate('LBL_SAVE', $MODULE)}</strong></button>
            </div>
        </form>
    </div>
</div>
{/strip}
