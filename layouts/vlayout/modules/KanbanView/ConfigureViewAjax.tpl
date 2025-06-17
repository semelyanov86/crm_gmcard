{*/* * *******************************************************************************
* The content of this file is subject to the Kanban View ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

<style>
    #primaryFieldValue ul li.select2-search-choice,.icon-move{
        cursor: move;
    }

</style>
<div class="container-fluid" >
    <form class="form-inline" id="KanbanConfigure" name="KanbanConfigure" method="post" action="index.php">
        <input type="hidden" name="primaryFieldValue"/>
        <input type="hidden" name="otherField"/>
        <div class="row-fluid"  style="padding: 10px 0;">
            <h3 class="textAlignCenter">
                {vtranslate('LBL_CONFIGURE_KANBAN_VIEW',$MODULE)}
                <small aria-hidden="true" data-dismiss="modal" class="pull-right ui-condition-color-closer" style="cursor: pointer;" title="{vtranslate('LBL_MODAL_CLOSE',$QUALIFIED_MODULE)}">x</small>
            </h3>
        </div>
        <hr>
        <div class="clearfix"></div>

        <div class="listViewContentDiv row-fluid" id="listViewContents" style="height: 492px; overflow-y: auto; width: 800px;">
            <div class="row marginBottom10px">
                <div class="row-fluid">
                    <div class="row marginBottom10px">
                        <div class="span4 textAlignRight">{vtranslate('LBL_DEFAULT_PAGE',$MODULE)}</div>
                        <div class="fieldValue span6">
                            <input type="checkbox" name="isDefaultPage" {if $PRIMARY_SETTING.is_default_page eq 1} checked {/if}/>
                        </div>
                    </div>
                    <div class="row marginBottom10px">
                        <div class="span4 textAlignRight">{vtranslate('LBL_PRIMARY_FIELD',$MODULE)} </div>
                        <div class="fieldValue span6" >
                            <select class="chzn-select" name="primaryField" required="true">
                                {foreach item=PRIMARY_NAME from=$PRIMARY_FIELDS}
                                    <option value="{$PRIMARY_NAME['fieldid']}" {if $PRIMARY_NAME['fieldid'] eq  $PRIMARY_SETTING['primary_field']} selected {/if}>{$PRIMARY_NAME['fieldlabel']}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="row-fluid">
                        <div class="span2 textAlignRight">&nbsp;</div>
                        <div class="fieldValue span7" id="primaryFieldValue" >
                            <select id="primaryValueSelectElement" name="primaryFieldValue" multiple class="select2" data-validation-engine="validate[required,funcCall[Vtiger_Base_Validator_Js.invokeValidation]]" style="width: 550px;">
                                {foreach item=PRIMARY_FIELD_VALUE key=PRIMARY_FIELD_KEY  from = $PRIMARY_SETTING['value'] }
                                    <option value="{$PRIMARY_FIELD_KEY}" {if $PRIMARY_SETTING['primary_value_setting'] AND in_array($PRIMARY_FIELD_KEY, $PRIMARY_SETTING['primary_value_setting'])} selected {/if}>{vtranslate($PRIMARY_FIELD_VALUE,$SOURCE_MODULE)}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="row-fluid">
                    <h4 class="textAlignCenter">{vtranslate('LBL_CONFIGURE_TILE_FIELDS',$MODULE)}</h4>
                </div>
                </div>

                <div class="row-fluid">
                    <div class="summaryWidgetContainer" style="width: 540px;margin: 10px auto;">
                        <div class="row marginBottom10px">
                            <div class="row span7 marginBottom10px ">
                                <table width="80%" class="table table-bordered listViewEntriesTable listOtherField">
                                    {if $PRIMARY_SETTING['other_field']}
                                        {foreach item = OTHER_FIELD from=$PRIMARY_SETTING['other_field'] }
                                            <tr class="otherField">
                                                <td width="5%" class="listViewEntryValue"><i class="icon-move alignMiddle" title="Move to Change Priority"></i></td>
                                                <td class="listViewEntryValue">{include file="FieldSelect.tpl"|@vtemplate_path:$MODULE MULTIPLE = 0 }</td>
                                                <td>
                                                    <span class=" pull-right otherFieldAction ">
                                                        <i title="Delete"  class="icon-trash alignMiddle deleteOtherField pull-right"></i>
                                                    </span>
                                                </td>
                                            </tr>
                                        {/foreach}
                                    {else}
                                        <tr class="otherField">
                                            <td width="5%" class="listViewEntryValue"><i class="icon-move alignMiddle" title="Move to Change Priority"></i></td>
                                            <td class="listViewEntryValue">{include file="FieldSelect.tpl"|@vtemplate_path:$MODULE MULTIPLE = 0 OTHER_FIELD = array() }</td>
                                            <td>
                                                <span class="pull-right">
                                                    <i title="Delete"  class="icon-trash alignMiddle deleteOtherField pull-right"></i>
                                                </span>
                                            </td>
                                        </tr>
                                    {/if}

                                </table>
                                <table class="hide fieldBasic">
                                    <tr class="otherField">
                                        <td width="5%" class="listViewEntryValue"><i class="icon-move alignMiddle" title="Move to Change Priority"></i></td>
                                        <td class="listViewEntryValue">{include file="FieldSelect.tpl"|@vtemplate_path:$MODULE MULTIPLE = 0 NOCHOSEN=true OTHER_FIELD = array() }</td>
                                        <td>
                                            <span class=" pull-right otherFieldAction ">
                                                <i title="Delete"  class="icon-trash alignMiddle deleteOtherField pull-right"></i>
                                            </span>
                                        </td>
                                    </tr>
                                </table>

                            </div>
                            <div class="span8" ><span class="btn btnAddMore">{vtranslate('LBL_CLICK_HERE_TO_ADD_MORE',$MODULE)}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="filterActions row" style="padding: 10px 0;">
            <button class="btn btn-success pull-right" id="save_kanbanview_setting" type="button"><strong>{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</strong></button>
        </div>
    </form>
</div>