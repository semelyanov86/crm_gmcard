{*<!--
/* ********************************************************************************
* The content of this file is subject to the Export To XLS ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */
-->*}
<div class="container-fluid">
    <div class="widget_header row-fluid">
        <h3>{vtranslate('VTEExportToXLS', 'VTEExportToXLS')}</h3>
    </div>
    <hr>
    <div class="clearfix"></div>
    <div class="summaryWidgetContainer" id="VTEExportToXLS_settings">
        <div class="row-fluid">
            <span class="span2"><h4>{vtranslate('LBL_ENABLE_MODULE', 'VTEExportToXLS')}</h4></span>
            <input type="checkbox" name="enable_module" id="enable_module" value="1" {if $ENABLE eq '1'}checked="" {/if}/>
        </div>
        <hr>
        <div class="row-fluid">
            <div class="span12">
                <span class="span2"><h4>{vtranslate('LBL_CUSTOM_FILE_NAME', 'VTEExportToXLS')}</h4></span>
                <input type="checkbox" name="custom_filename" id="custom_filename" value="1" {if $CUSTOM_FILENAME eq '1'}checked="" {/if}/>
            </div>
        </div>
        <div class="row-fluid" style="margin-top: 10px">
            <div class="span12">
                <span class="span2"><h4>{vtranslate('LBL_FILE_NAME', 'VTEExportToXLS')}</h4></span>
                <input style="width: 300px" class="inputElement" type="text" name="file_name" id="file_name" readonly value="{$FILE_NAME}"/>
            </div>
        </div>
        <div class="row-fluid" style="margin-top: 10px">
            <div class="span12">
                <span class="span2">
                    {vtranslate('LBL_MODULE_NAME', 'VTEExportToXLS')}
                </span>
                <input class="custom_fieldname" type="checkbox" {if strpos($FILE_NAME, '$module_name$') !== false} checked {/if} value="$module_name$">
            </div>
        </div>
        <div class="row-fluid" style="margin-top: 10px">
            <div class="span12">
                <span class="span2">
                    {vtranslate('LBL_PRIMARY_EMAIL', 'VTEExportToXLS')}
                </span>
                <input class="custom_fieldname" type="checkbox" {if strpos($FILE_NAME, '$user_email$') !== false} checked {/if} value="$user_email$">
            </div>
        </div>
        <div class="row-fluid" style="margin-top: 10px">
            <div class="span12">
                <span class="span2">
                    {vtranslate('LBL_CURRENT_DATE', 'VTEExportToXLS')}
                </span>
                <input class="custom_fieldname" type="checkbox" {if strpos($FILE_NAME, '$current_date$') !== false} checked {/if} value="$current_date$">
            </div>
        </div>
        <hr>
        <div class="row-fluid">
            <div class="span12">
                <span class="span2">
                    <h4>{vtranslate('LBL_DOWNLOAD_TO_SERVER', 'VTEExportToXLS')}</h4>
                </span>
                <input type="checkbox" name="download_to_server" id="download_to_server" value="1" {if $DOWNLOAD_TO_SERVER eq '1'}checked="" {/if}/>
                <i style="margin-left: 5px" class="icon-info-sign" data-toggle="tooltip" title="{$LBL_DOWNLOAD_TOOLTIP}"></i>{$LBL_DOWNLOAD_TOOLTIP}
            </div>
        </div>
    </div>
    <div class="clearfix"></div>
    <div>
        <div style="padding: 10px; text-align: justify; font-size: 14px; border: 1px solid #ececec; border-left: 5px solid #2a9bbc; border-radius: 5px; overflow: hidden;">
            <h4 style="color: #2a9bbc; margin: 0px -15px 10px -15px; padding: 0px 15px 8px 15px; border-bottom: 1px solid #ececec;"><i class="fa fa-info-circle"></i>&nbsp;&nbsp;{vtranslate('LBL_INFO_BLOCK', $QUALIFIED_MODULE)}</h4>
            {vtranslate('LBL_INFO_BLOCK_ON_SETTING_PAGE', $QUALIFIED_MODULE)}
        </div>
    </div>
</div>
