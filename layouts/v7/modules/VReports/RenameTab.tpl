{*+**********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.1
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
************************************************************************************}
{strip}
    <div class="modal-dialog modelContainer">
        {include file="ModalHeader.tpl"|vtemplate_path:$MODULE TITLE="{vtranslate('LBL_RENAME_TAB',$MODULE)}"}
        <div class="modal-content">
            <form id="renameTab" name="renameTab" method="post" action="index.php">
                <input type="hidden" name="module" value="{$MODULE}"/>
                <input type="hidden" name="action" value="DashboardActions"/>
                <input type="hidden" name="mode" value="renameTab"/>
                <input type="hidden" name="tabId" value="{$TAB_ID}"/>

                <div class="modal-body clearfix">
                    <div class="col-lg-4 col-lg-offset-1">
                        <label class="control-label pull-left marginTop5px">
                            {vtranslate('LBL_RENAME_TAB',$MODULE)}
                        </label>
                    </div>
                    <div class="col-lg-6">
                        <div>
                            <div class="input-group">
                                <input id="tabName" class="marginLeftZero autoComplete inputElement ui-autocomplete-input" value="{$TAB_NAME}">
                            </div>
                        </div>
                    </div>
                </div>
                {include file="ModalFooter.tpl"|vtemplate_path:$MODULE}
            </form>
        </div>
    </div>
{/strip}
