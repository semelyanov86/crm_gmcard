{* ********************************************************************************
 * The content of this file is subject to the VTEPayments ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** *}
<div class="container-fluid">
    <form name="vteFormUninstall" action="" method="post">
        <input type="hidden" name="module" value="{$MODULE_NAME}" />
        <input type="hidden" name="parent" value="{$PARENT_MODULE}" />
        <input type="hidden" name="action" value="Uninstall" />
        <div class="contentHeader row-fluid">
            <h3 class="span8 textOverflowEllipsis">
                <a href="index.php?module=ModuleManager&parent=Settings&view=List">&nbsp;{vtranslate('LBL_MODULE_MANAGER', $QUALIFIED_MODULE)}</a>&nbsp;>&nbsp;{vtranslate($MODULE_NAME, $QUALIFIED_MODULE)}
            </h3>
        </div>
        <hr>
        <div class="clearfix"></div>

        <div class="listViewContentDiv row-fluid" id="listViewContents">
            <div class="contents tabbable">
                <ul class="nav nav-tabs">
                    <li class="active"><a data-toggle="tab" href="#folders_files"><strong>{vtranslate('LBL_FOLDERS_AND_FILES', $QUALIFIED_MODULE)}</strong></a></li>
                    <li><a data-toggle="tab" href="#queries_statement"><strong>{vtranslate('LBL_QUERIES_STATEMENT', $QUALIFIED_MODULE)}</strong></a></li>
                </ul>
                <div class="tab-content layoutContent padding20 themeTableColor overflowVisible">
                    <div class="tab-pane active" id="folders_files">
                        {$TREE_HTML}
                    </div>
                    <div class="tab-pane" id="queries_statement">
                        {$QUERY_HTML}
                    </div>
                </div>
            </div>

            <div class="marginBottom10px">
                <button type="button" id="vte-uninstall" class="btn btn-danger"><strong>{vtranslate('LBL_UNINSTALL', $QUALIFIED_MODULE)}</strong></button>
                <button class="btn btn-info" type="button" onclick="javascript:window.history.back();"><strong>{vtranslate('LBL_CANCEL',$QUALIFIED_MODULE)}</strong></button>
            </div>

        </div>
    </form>
</div>
{literal}
    <script type="text/javascript">
        jQuery( document ).ready(function() {
            jQuery('#vte-uninstall').on('click', function(e){
                e.preventDefault();
                var form = jQuery(this).closest('form');
                var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
                Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(
                    function(e) {
                        form.submit();
                    },
                    function(error, err){
                    }
                );
            });
        });
    </script>
{/literal}

