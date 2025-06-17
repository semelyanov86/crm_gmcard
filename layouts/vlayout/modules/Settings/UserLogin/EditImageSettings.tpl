{* /* * *******************************************************************************
 * The content of this file is subject to the VTE Custom User Login Page ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */ *}
{strip}
    <div class="vte-user-login-img-setting" style="width: 600px;padding: 10px;">
        <form action="index.php" method="post" id="vte-img-setting">
            <input type="hidden" name="module" value="UserLogin">
            <input type="hidden" name="parent" value="Settings">
            <input type="hidden" name="action" value="SaveImageSettings">

            <h3>{vtranslate('LBL_TITLE_IMG_SETTINGS', $QUALIFIED_MODULE)}</h3>
            <hr>

            <fieldset>
                <legend>{vtranslate('LBL_IMG_SETTINGS_LOGO', $QUALIFIED_MODULE)}</legend>
                <table class="table table-bordered blockContainer showInlineTable equalSplit">
                    <tr>
                        <td class="fieldLabel medium">
                            <label class="muted pull-right marginRight10px">{vtranslate('LBL_IMG_SETTINGS_LOGO_METHOD', $QUALIFIED_MODULE)}</label>
                        </td>
                        <td class="fieldValue medium">
                            <div class="row-fluid">
                                <select name="vte-logo-method">
                                    <option value="resize" {if $IMAGE_SETTINGS.logo.method eq 'resize'}selected=""{/if}>{vtranslate('LBL_IMG_SETTINGS_METHOD_RESIZE', $QUALIFIED_MODULE)}</option>
                                    <option value="crop" {if $IMAGE_SETTINGS.logo.method eq 'crop'}selected=""{/if}>{vtranslate('LBL_IMG_SETTINGS_METHOD_CROP', $QUALIFIED_MODULE)}</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldLabel medium">
                            <label class="muted pull-right marginRight10px">{vtranslate('LBL_IMG_SETTINGS_LOGO_WIDTH', $QUALIFIED_MODULE)}</label>
                        </td>
                        <td class="fieldValue medium">
                            <div class="row-fluid">
                                <input type="text" value="{$IMAGE_SETTINGS.logo.width}" name="vte-logo-width">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldLabel medium">
                            <label class="muted pull-right marginRight10px">{vtranslate('LBL_IMG_SETTINGS_LOGO_HEIGHT', $QUALIFIED_MODULE)}</label>
                        </td>
                        <td class="fieldValue medium">
                            <div class="row-fluid">
                                <input type="text" value="{$IMAGE_SETTINGS.logo.height}" name="vte-logo-height">
                            </div>
                        </td>
                    </tr>
                </table>
            </fieldset>
            <hr>
            <fieldset>
                <legend>{vtranslate('LBL_TITLE_IMG_SETTINGS_SLIDE', $QUALIFIED_MODULE)}</legend>
                <table class="table table-bordered blockContainer showInlineTable equalSplit">
                    <tr>
                        <td class="fieldLabel medium">
                            <label class="muted pull-right marginRight10px">{vtranslate('LBL_IMG_SETTINGS_SLIDE_METHOD', $QUALIFIED_MODULE)}</label>
                        </td>
                        <td class="fieldValue medium">
                            <div class="row-fluid">
                                <select name="vte-slide-method">
                                    <option value="resize" {if $IMAGE_SETTINGS.slide.method eq 'resize'}selected=""{/if}>{vtranslate('LBL_IMG_SETTINGS_METHOD_RESIZE', $QUALIFIED_MODULE)}</option>
                                    <option value="crop" {if $IMAGE_SETTINGS.slide.method eq 'crop'}selected=""{/if}>{vtranslate('LBL_IMG_SETTINGS_METHOD_CROP', $QUALIFIED_MODULE)}</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldLabel medium">
                            <label class="muted pull-right marginRight10px">{vtranslate('LBL_IMG_SETTINGS_SLIDE_WIDTH', $QUALIFIED_MODULE)}</label>
                        </td>
                        <td class="fieldValue medium">
                            <div class="row-fluid">
                                <input type="text" value="{$IMAGE_SETTINGS.slide.width}" name="vte-slide-width">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="fieldLabel medium">
                            <label class="muted pull-right marginRight10px">{vtranslate('LBL_IMG_SETTINGS_SLIDE_HEIGHT', $QUALIFIED_MODULE)}</label>
                        </td>
                        <td class="fieldValue medium">
                            <div class="row-fluid">
                                <input type="text" value="{$IMAGE_SETTINGS.slide.height}" name="vte-slide-height">
                            </div>
                        </td>
                    </tr>
                </table>
            </fieldset>
            <div class="clearfix"></div>
            <span class="pull-right"><button class="btn btn-success" type="submit" title="{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}"><strong>{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</strong></button></span>
        </form>
    </div>
{/strip}