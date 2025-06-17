{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
    <tr>
        <td class="col-lg-1"></td>
        <td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_DATA_COLOR','VReports')}</label></td>
        <td class="fieldValue col-lg-5" data-value="{$SHOW_EMPTY_VAL}">
            <div class="div_pick_color">
                <div class="div-inline">
                    <div class="s-color-box" data-value="#8bc34a" style="background: #8bc34a"></div>
                    <div class="s-color-box" data-value="#ffeb3b" style="background: #ffeb3b"></div>
                    <div class="s-color-box" data-value="#ffc107" style="background: #ffc107"></div>
                    <div class="s-color-box" data-value="#ff5722" style="background: #ff5722"></div>
                    <div class="s-color-box" data-value="#e91e63" style="background: #e91e63"></div>

                    <div class="s-color-box" data-value="#259b24" style="background: #259b24"></div>
                    <div class="s-color-box" data-value="#cddc39" style="background: #cddc39"></div>
                    <div class="s-color-box" data-value="#ff9800" style="background: #ff9800"></div>
                    <div class="s-color-box" data-value="#e51c23" style="background: #e51c23"></div>
                    <div class="s-color-box" data-value="#9c27b0" style="background: #9c27b0"></div>
                </div>
                <div class="div-inline">
                    <div class="s-color-box" data-value="#4b0082" style="background: #4b0082"></div>
                    <div class="s-color-box" data-value="#03a9f4" style="background: #03a9f4"></div>
                    <div class="s-color-box" data-value="#00bcd4" style="background: #00bcd4"></div>
                    <div class="s-color-box" data-value="#9e9e9e" style="background: #9e9e9e"></div>
                    <div class="s-color-box" data-value="#607d8b" style="background: #607d8b"></div>

                    <div class="s-color-box" data-value="#673ab7" style="background: #673ab7"></div>
                    <div class="s-color-box" data-value="#5677fc" style="background: #5677fc"></div>
                    <div class="s-color-box" data-value="#009688" style="background: #009688"></div>
                    <div class="s-color-box" data-value="#795548" style="background: #795548"></div>
                    <div class="s-color-box {if $WIDGET_FORM  eq 'Create'}selected-color-box{/if}" data-value="#212121" style="background: #212121"></div>
                </div>
                <input class="inputElement" type="hidden" name="dataColor" value="{if $WIDGET}{VReports_Gauge_Model::getValueByName($WIDGET,'dataColor')}{/if}"/>
                <br>
                <center><label><a onclick="VReports_DashBoard_Js.registerEventSetCustomColor(this)">Add custom color</a></label></center>
            </div>
            <div class="custom-picklist-color hideByStep">
                <div class="customColorPicker">
                    <div class="colorpicker" id="collorpicker_data" style="position: relative; display: block;">
                        <div class="colorpicker_color" style="background-color: rgb(255, 0, 255);">
                            <div>
                                <div style="left: 117px; top: 24px;"></div>
                            </div>
                        </div>
                        <div class="colorpicker_hue">
                            <div style="top: 25px;"></div>
                        </div>
                        <div class="colorpicker_new_color" style="background-color: rgb(214, 47, 214);"></div>
                        <div class="colorpicker_current_color" style="background-color: rgb(255, 255, 255);"></div>
                        <div class="colorpicker_hex"><input type="text" maxlength="6" size="6" aria-invalid="false"></div>
                        <div class="colorpicker_rgb_r colorpicker_field"><input type="text" maxlength="3" size="3"><span></span></div>
                        <div class="colorpicker_rgb_g colorpicker_field"><input type="text" maxlength="3" size="3"><span></span></div>
                        <div class="colorpicker_rgb_b colorpicker_field"><input type="text" maxlength="3"size="3"><span></span></div>
                        <div class="colorpicker_hsb_h colorpicker_field"><input type="text" maxlength="3" size="3"><span></span></div>
                        <div class="colorpicker_hsb_s colorpicker_field"><input type="text" maxlength="3" size="3"><span></span></div>
                        <div class="colorpicker_hsb_b colorpicker_field"><input type="text" maxlength="3" size="3"><span></span></div>
                        <div class="colorpicker_submit"></div>
                    </div>
                </div>
                <center><label><a onclick="VReports_DashBoard_Js.registerEventHideCustomColor(this)">Go back</a></label></center>
            </div>
        </td>
        <td class="col-lg-4"></td>
    </tr>

    <tr>
        <td class="col-lg-1"></td>
        <td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_BACKGROUND_COLOR','VReports')}</label></td>
        <td class="fieldValue col-lg-5" data-value="{$SHOW_EMPTY_VAL}">
            <div class="div_pick_color">
                <div class="div-inline">
                    <div class="s-color-box" data-value="#8bc34a" style="background: #8bc34a"></div>
                    <div class="s-color-box" data-value="#ffeb3b" style="background: #ffeb3b"></div>
                    <div class="s-color-box" data-value="#ffc107" style="background: #ffc107"></div>
                    <div class="s-color-box" data-value="#ff5722" style="background: #ff5722"></div>
                    <div class="s-color-box" data-value="#e91e63" style="background: #e91e63"></div>

                    <div class="s-color-box" data-value="#259b24" style="background: #259b24"></div>
                    <div class="s-color-box" data-value="#cddc39" style="background: #cddc39"></div>
                    <div class="s-color-box" data-value="#ff9800" style="background: #ff9800"></div>
                    <div class="s-color-box" data-value="#e51c23" style="background: #e51c23"></div>
                    <div class="s-color-box" data-value="#9c27b0" style="background: #9c27b0"></div>
                </div>
                <div class="div-inline">
                    <div class="s-color-box" data-value="#4b0082" style="background: #4b0082"></div>
                    <div class="s-color-box" data-value="#03a9f4" style="background: #03a9f4"></div>
                    <div class="s-color-box" data-value="#00bcd4" style="background: #00bcd4"></div>
                    <div class="s-color-box" data-value="#9e9e9e" style="background: #9e9e9e"></div>
                    <div class="s-color-box" data-value="#607d8b" style="background: #607d8b"></div>

                    <div class="s-color-box" data-value="#673ab7" style="background: #673ab7"></div>
                    <div class="s-color-box" data-value="#5677fc" style="background: #5677fc"></div>
                    <div class="s-color-box" data-value="#009688" style="background: #009688"></div>
                    <div class="s-color-box" data-value="#795548" style="background: #795548"></div>
                    <div class="s-color-box {if $WIDGET_FORM  eq 'Create'}selected-color-box{/if}" data-value="#212121" style="background: #212121"></div>
                </div>
                <input class="inputElement" type="hidden" name="backgroundColor" value="{if $WIDGET}{VReports_Gauge_Model::getValueByName($WIDGET,'backgroundColor')}{/if}"/>
                <br>
                <center><label><a onclick="VReports_DashBoard_Js.registerEventSetCustomColor(this)">Add custom color</a></label></center>
            </div>
            <div class="custom-picklist-color hideByStep">
                <div class="customColorPicker">
                    <div class="colorpicker" id="collorpicker_data" style="position: relative; display: block;">
                        <div class="colorpicker_color" style="background-color: rgb(255, 0, 255);">
                            <div>
                                <div style="left: 117px; top: 24px;"></div>
                            </div>
                        </div>
                        <div class="colorpicker_hue">
                            <div style="top: 25px;"></div>
                        </div>
                        <div class="colorpicker_new_color" style="background-color: rgb(214, 47, 214);"></div>
                        <div class="colorpicker_current_color" style="background-color: rgb(255, 255, 255);"></div>
                        <div class="colorpicker_hex"><input type="text" maxlength="6" size="6" aria-invalid="false"></div>
                        <div class="colorpicker_rgb_r colorpicker_field"><input type="text" maxlength="3" size="3"><span></span></div>
                        <div class="colorpicker_rgb_g colorpicker_field"><input type="text" maxlength="3" size="3"><span></span></div>
                        <div class="colorpicker_rgb_b colorpicker_field"><input type="text" maxlength="3"size="3"><span></span></div>
                        <div class="colorpicker_hsb_h colorpicker_field"><input type="text" maxlength="3" size="3"><span></span></div>
                        <div class="colorpicker_hsb_s colorpicker_field"><input type="text" maxlength="3" size="3"><span></span></div>
                        <div class="colorpicker_hsb_b colorpicker_field"><input type="text" maxlength="3" size="3"><span></span></div>
                        <div class="colorpicker_submit"></div>
                    </div>
                </div>
                <center><label><a onclick="VReports_DashBoard_Js.registerEventHideCustomColor(this)">Go back</a></label></center>
            </div>

        </td>
        <td class="col-lg-4"></td>
    </tr>
    <tr>
        <td class="col-lg-1"></td>
        <td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_FONT_SIZE','VReports')} <i>(px)</i></label></td>
        <td class="fieldValue col-lg-5" data-value="{$SHOW_EMPTY_VAL}">
            <input class="inputElement" type="text" name="fontSize" value="{if $WIDGET}{VReports_Gauge_Model::getValueByName($WIDGET,'fontSize')}{else}50{/if}" placeholder="{vtranslate('LBL_FONT_SIZE','VReports')}"/>

        </td>
        <td class="col-lg-4"></td>
    </tr>

    <tr>
        <td class="col-lg-1"></td>
        <td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_DECIMAL','VReports')}</label></td>
        <td class="fieldValue col-lg-5" data-value="{$SHOW_EMPTY_VAL}">
            <input style="padding: 8px" class="inputElement" type="number" name="decimal" min="0" max="3" value="{if $WIDGET}{VReports_Gauge_Model::getValueByName($WIDGET,'decimal')}{/if}" placeholder="Decimals (optional)"/>
        </td>
        <td class="col-lg-4"></td>
    </tr>

    <tr>
        <td class="col-lg-1"></td>
        <td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_FOTMAT_LARGE_MUNBER','VReports')}</label></td>
        <td class="fieldValue col-lg-5" data-value="{$SHOW_EMPTY_VAL}">
            {if $WIDGET}
                {assign var="SELECT_VALUE" value=VReports_Gauge_Model::getValueByName($WIDGET,'formatLargeNumber')}
            {/if}
            <select  class="select2-choice" style="width: 100%" name="formatLargeNumber">
                <option {if $SELECT_VALUE eq '0'} selected{/if} value="0">No</option>
                <option {if $SELECT_VALUE eq '1'} selected{/if} value="1">Yes</option>
            </select>
        </td>
        <td class="col-lg-4"></td>
    </tr>

    <tr>
        <td class="col-lg-1"></td>
        <td class="fieldLabel col-lg-4"><label class="pull-right">{vtranslate('LBL_ICON','VReports')}</label></td>
        <td class="fieldValue col-lg-5" data-value="{$SHOW_EMPTY_VAL}">
            <a class="btn btn-primary" popup-open="popup-box-icons" onclick="VReports_DashBoard_Js.registerEventShowBoxIcons(this)">Select Icon</a>&nbsp;<span class="{if $WIDGET}{VReports_Gauge_Model::getValueByName($WIDGET,'icon')}{/if}" id="icon-selected" style="font-size: 30px; vertical-align: middle;"></span>
            {assign var=LISTICONS_LENGTH value=(count($LISTICONS) -1)}
            {assign var=INDEX value = 0 }
            <div class="gauge-popup-icons" popup-name="popup-box-icons">
                <div class="gauge-popup-icons-content">
                    <table data-length="{$LISTICONS_LENGTH}" border="1px solid #cccccc">
                        {foreach from = $LISTICONS item =val key=k }
                            {assign var=MODE4OK value=(($INDEX mod 14) == 0)}
                            {if $MODE4OK}
                                <tr>
                            {/if}
                            <td style="padding: 5px;" class="cell-icon">
                                <span class="{$k} icon-gauge" style="font-size: 30px; vertical-align: middle;" data-info="{$k}"></span>
                            </td>
                            {if ($INDEX mod 14) == 13 or $LISTICONS_LENGTH == $INDEX}
                                </tr>
                            {/if}
                            <input type="hidden" value="{$INDEX++}">
                        {/foreach}
                    </table>
                    <a class="close-box-icons-button" popup-close="popup-box-icons" href="javascript:void(0)">x</a>
                </div>
            </div>
            <input type="hidden" class="inputElement" name="icon" value="{if $WIDGET}{VReports_Gauge_Model::getValueByName($WIDGET,'icon')}{/if}" placeholder="Icon (optional)"/>
        </td>
        <td class="col-lg-4"></td>
    </tr>
{/strip}