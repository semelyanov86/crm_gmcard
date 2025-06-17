<style type="text/css">
    .qtip {
        z-index: 15005 !important;
    }
</style>
<div class="modal-dialog createFieldModal modelContainer ">
    <div class="modal-content">
        <form class="form-horizontal createCustomFieldForm form-modalAddWidget" >
            <div class="modal-header">
                <div class="clearfix">
                    <div class="pull-right ">
                        <button type="button" class="close" aria-label="Close" data-dismiss="modal">
                            <span aria-hidden="true" class="fa fa-close"></span>
                        </button>
                    </div>
                    <h4 class="pull-left">{vtranslate('Add Custom Script', $QUALIFIED_MODULE)}</h4>
                </div>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="control-label fieldLabel col-sm-2">{vtranslate('Active', $QUALIFIED_MODULE)}:</label>
                    <div class="controls col-sm-7" style="padding-top: 5px;">
                        <input name="isactive" class="span3" type="checkbox" value="1" {if $ISACTIVE=='1'}checked{/if}/></div>
                </div>
                <div class="form-group">
                    <label class="control-label fieldLabel col-sm-3">{vtranslate('Custom Script', $QUALIFIED_MODULE)}:</label>
                </div>
                <div class="form-group">
                    <div class="controls col-sm-12" style="padding-top: 5px;">
                        <textarea name="custom_script" row="10" style="width: 100%;height: 200px;">{$CUSTOM_SCRIPT}</textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button name='saveButton' class="btn btn-success saveButton"  aria-hidden="true" >{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</button>
            </div>
        </form>
    </div>
</div>