<div class="widget_header row-fluid" style="padding-bottom: 20px;">
    <div class="span12">
        <h3>
            <a href="index.php?module=ModuleManager&parent=Settings&view=List">&nbsp;{vtranslate('MODULE_MANAGEMENT',$MODULE_LBL)}</a>&nbsp;>&nbsp;{$MODULE_LBL}
        </h3>
    </div>
</div>
<form action="" enctype="multipart/form-data" method="POST"/>
    <div class="dupecheck-setting row-fluid">
        <div class="span12 select-modules" style="text-align: center; padding: 5px 0;">
            <h3>{vtranslate('MODULE_UNINSTALL_CONFIRM_MSG',$MODULE_LBL)}</h3>
        </div>
        
        <div class="span12 dcm-save-btn" style="text-align: center; padding: 10px 0;">
            <button class="btn btn-danger" type="submit"><strong>{vtranslate('UNINSTALL_BTN',$MODULE_LBL)}</strong></button>
            <button class="btn btn-success" type="button" onclick="javascript:window.history.back();"><strong>{vtranslate('CANCEL_BTN',$MODULE_LBL)}</strong></button>
        </div>
    </div>
    <input type="hidden" name="module" value="DuplicateCheckMerge" />
    <input type="hidden" name="action" value="uninstall" />    
</form>