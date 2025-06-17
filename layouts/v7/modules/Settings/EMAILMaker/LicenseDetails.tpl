{*<!--
/*********************************************************************************
 * The content of this file is subject to the EMAILMaker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
********************************************************************************/
-->*}
{strip}
    {if $TRIAL_ERROR neq false}
        <div class="alert alert-danger displayInlineBlock">{vtranslate($TRIAL_ERROR, $QUALIFIED_MODULE)}</div>
    {/if}
    <br>
    <table class="table table-bordered table-condensed themeTableColor">
        <thead>
        <tr class="blockHeader">
            <th class="mediumWidthType" colspan="2">
                <span class="alignMiddle">{vtranslate('LBL_COMPANY_LICENSE_INFO', $QUALIFIED_MODULE)}</span>
            </th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('organizationname', 'Settings:Vtiger')}:</label></td>
            <td style="border-left: none;">
                <div class="pull-left" id="organizationname_label">{$COMPANY_DETAILS->get("organizationname")}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('address', 'Settings:Vtiger')}:</label></td>
            <td style="border-left: none;">
                <div class="pull-left" id="address_label">{$COMPANY_DETAILS->get("address")}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('city', 'Settings:Vtiger')}:</label></td>
            <td style="border-left: none;">
                <div class="pull-left" id="city_label">{$COMPANY_DETAILS->get("city")}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('state', 'Settings:Vtiger')}:</label></td>
            <td style="border-left: none;">
                <div class="pull-left" id="state_label">{$COMPANY_DETAILS->get("state")}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('country', 'Settings:Vtiger')}:</label></td>
            <td style="border-left: none;">
                <div class="pull-left" id="country_label">{$COMPANY_DETAILS->get("country")}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('code', 'Settings:Vtiger')}:</label></td>
            <td style="border-left: none;">
                <div class="pull-left" id="code_label">{$COMPANY_DETAILS->get("code")}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('vatid', 'Settings:Vtiger')}:</label></td>
            <td style="border-left: none;">
                <div class="pull-left" id="vatid_label">{$COMPANY_DETAILS->get("vatid")}</div>
            </td>
        </tr>
        </tbody>
    </table>
    <button type="button" id="company_button" class="btn btn-info" onclick="window.location.href='index.php?module=Vtiger&parent=Settings&view=CompanyDetails'">
        {vtranslate('LBL_CHANGE_COMPANY_INFORMATION',$QUALIFIED_MODULE)}
    </button>
    <br>
    <br>
    <table class="table table-bordered table-condensed themeTableColor">
        <thead>
        <tr class="blockHeader">
            <th colspan="2" class="mediumWidthType">
                <span class="alignMiddle">{vtranslate('LBL_MODULE_NAME', $QUALIFIED_MODULE)} {vtranslate('LBL_LICENSE', $QUALIFIED_MODULE)}</span>
            </th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('LBL_URL', $QUALIFIED_MODULE)}:</label></td>
            <td style="border-left: none;">
                <div class="pull-left" id="vatid_label">{$URL}</div>
            </td>
        </tr>
        <tr class="license_due_date_tr" {if $LICENSE_DUE_DATE eq "" || $LICENSE eq ""}style="display: none"{/if}>
            <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('LBL_LICENSE_DUE_DATE', $QUALIFIED_MODULE)}:</label></td>
            <td style="border-left: none;">
                <div class="pull-left license_due_date_val" id="vatid_label">{$LICENSE_DUE_DATE}</div>
            </td>
        </tr>
        <tr>
            <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('LBL_LICENSE_KEY', $QUALIFIED_MODULE)}:</label></td>
            <td style="border-left: none;">
                {if $STEP neq ""}
                    <div class="displayInlineBlock pull-left">
                        <input type="text" class="input-large inputElement" id="licensekey" name="licensekey" data-validation-engine="validate[required]">
                    </div>&nbsp;&nbsp;
                    <button type="submit" id="validate_button" class="btn btn-success">
                        <strong>{vtranslate('LBL_VALIDATE',$QUALIFIED_MODULE)}</strong>
                    </button>&nbsp;&nbsp;
                    <button type="button" id="order_button" class="btn btn-info" onclick="window.location.href='http://www.its4you.sk/en/vtiger-shop.html'">
                        {vtranslate('LBL_ORDER_NOW',$QUALIFIED_MODULE)}
                    </button>
                {else}
                    <div class="displayInlineBlock">
                        <div class="pull-left" id="license_key_label">{$LICENSE}</div>
                        &nbsp;&nbsp;
                    </div>
                    <div id="divgroup1" class="btn-group displayInlineBlock" {if $IS_INSTALLED}style="display:none"{/if}>
                        <button id="activate_license_btn" class="btn btn-success" title="{vtranslate('LBL_ACTIVATE_KEY_TITLE',$QUALIFIED_MODULE)}" type="button">
                            <strong>{vtranslate('LBL_ACTIVATE_KEY',$QUALIFIED_MODULE)}</strong>
                        </button>
                        &nbsp;&nbsp;
                    </div>
                    <div id="divgroup2" class="displayInlineBlock" {if (!$IS_INSTALLED) || ($LICENSE_EXPIRED && $TRIAL_URL)}style="display:none"{/if}>
                        <button id="deactivate_license_btn" type="button" class="btn btn-danger marginLeftZero">{vtranslate('LBL_DEACTIVATE',$QUALIFIED_MODULE)}</button>
                        &nbsp;&nbsp;
                    </div>
                {/if}
            </td>
        </tr>
        {if $LICENSE_EXPIRED && $TRIAL_URL}
            <tr>
                <td style="width: 25%"><label class="muted pull-right marginRight10px">{vtranslate('LBL_DESCRIPTION','Settings:ITS4YouInstaller')}</label></td>
                <td style="border-left: none;">{vtranslate('LBL_LICENSE_EXPIRED_DESCRIPTION','Settings:ITS4YouInstaller')}</td>
            </tr>
        {/if}
        </tbody>
    </table>
    {if $LICENSE_EXPIRED && $TRIAL_URL}
        <div style="text-align: center">
            <a href="{$TRIAL_URL}" target="_blank" class="btn btn-primary">
                {vtranslate('LBL_LICENSE_MANAGE','Settings:ITS4YouInstaller')}
            </a>
        </div>
    {/if}
{/strip}