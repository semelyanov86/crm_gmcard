{*<!--
/* ********************************************************************************
* The content of this file is subject to the Export To XLS ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */
-->*}
<div class="container-fluid WidgetsManage">
    <div class="widget_header row">
        <div class="col-sm-6"><h4><label>{vtranslate('VTEExportToXLS', 'VTEExportToXLS')}</label>
        </div>
    </div>
    <hr>
    <div class="clearfix"></div>
    <div class="summaryWidgetContainer">
        <div class="row-fluid">
            <span class="span2"><h4>{vtranslate('LBL_ENABLE_MODULE', 'VTEExportToXLS')}</h4></span>
            <input type="checkbox" name="enable_module" id="enable_module" value="1" {if $ENABLE eq '1'}checked="" {/if}/>
        </div>
        <hr>
        <div class="row">
            <div class="col-lg-12">
                <span class="col-lg-2" style="font-size: 14px;">{vtranslate('LBL_CUSTOM_FILE_NAME', 'VTEExportToXLS')}</span>
                <span class="col-lg-4">
                    <input style="opacity: 0;" {if $CUSTOM_FILENAME} checked value="on" {else} value="off"{/if} data-on-color="success" type="checkbox" name="custom_filename" id="custom_filename">
                </span>
            </div>
            <div class="col-lg-12" style="margin-top: 10px">
                <span class="col-lg-2" style="font-size: 14px;">{vtranslate('LBL_FILE_NAME', 'VTEExportToXLS')}</span>
                <span class="col-lg-4">
                    <input class="inputElement" type="text" name="file_name" id="file_name" readonly value="{$FILE_NAME}"/>
                </span>
            </div>
            <div class="col-lg-12" style="margin-top: 10px">
                <span class="col-lg-1">
                    {vtranslate('LBL_MODULE_NAME', 'VTEExportToXLS')}
                </span>
                <span class="col-lg-1">
                    <input class="custom_fieldname" type="checkbox" {if strpos($FILE_NAME, '$module_name$') !== false} checked {/if} value="$module_name$">
                </span>
            </div>
            <div class="col-lg-12" style="margin-top: 10px">
                <span class="col-lg-1">
                    {vtranslate('LBL_PRIMARY_EMAIL', 'VTEExportToXLS')}
                </span>
                <span class="col-lg-1">
                    <input class="custom_fieldname" type="checkbox" {if strpos($FILE_NAME, '$user_email$') !== false} checked {/if} value="$user_email$">
                </span>
            </div>
            <div class="col-lg-12" style="margin-top: 10px">
                <span class="col-lg-1">
                    {vtranslate('LBL_CURRENT_DATE', 'VTEExportToXLS')}
                </span>
                <span class="col-lg-1">
                    <input class="custom_fieldname" type="checkbox" {if strpos($FILE_NAME, '$current_date$') !== false} checked {/if} value="$current_date$">
                </span>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-lg-12">
                <span class="col-lg-2" style="font-size: 14px;">{vtranslate('LBL_DOWNLOAD_TO_SERVER', 'VTEExportToXLS')}</span>
                <span class="col-lg-4">
                    <input style="opacity: 0;" {if $DOWNLOAD_TO_SERVER} checked value="on" {else} value="off"{/if} data-on-color="success" type="checkbox" name="download_to_server" id="download_to_server">
                    <span style="margin-left: 10px" data-toggle="tooltip" title="" data-original-title="{$LBL_DOWNLOAD_TOOLTIP}"><i class="fa fa-exclamation-circle" data-name="mandatory" data-enable-value="M" data-disable-value="O" readonly="readonly"></i></span>
                </span>
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
