{* /* * *******************************************************************************
 * The content of this file is subject to the VTE Custom User Login Page ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */ *}
{strip}
    <div class="container-fluid">
        <div class="contents">
            {assign var=WIDTHTYPE value=$CURRENT_USER_MODEL->get('rowheight')}
            <form id="UserLoginForm" class="form-horizontal" action="index.php" method="POST" enctype="multipart/form-data">
                <div class="widget_header row-fluid">
                    <div class="span8"><h3>{vtranslate('LBL_SETTING_HEADER', $QUALIFIED_MODULE)}</h3></div>
                    <div class="span4 btn-toolbar">
                        <div class="pull-right">
                            <button class="btn btn-success saveButton" type="button" title="{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}"><strong>{vtranslate('LBL_SAVE', $QUALIFIED_MODULE)}</strong></button>
                            <a type="reset" onclick="javascript:window.history.back();" class="cancelLink" title="{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}">{vtranslate('LBL_CANCEL', $QUALIFIED_MODULE)}</a>
                        </div>
                    </div>
                </div>
                <hr>

                <input type="hidden" name="action" value="Image" />
                <input type="hidden" name="parent" value="Settings" />
                <input type="hidden" name="module" value="UserLogin" />
                <input type="hidden" name="record" value="{$RECORD_ID}" />

                <table class="table table-bordered fieldBlockContainer">
                    <tbody>
                        <tr>
                            <td colspan="2">
                                <div class="alert alert-info" style="margin: 0;">{vtranslate('LBL_CRM_INFO', $QUALIFIED_MODULE)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    <span class="redColor">*</span>
                                    {vtranslate('LBL_HEADER_HEADER', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td style="border-left: none;">
                                <input type="text" name="header" class="inputElement fieldValue" style="width: 50%"  value="{$ENTITY.header}" />
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_DESCRIPTION', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <textarea name="description" class="inputElement fieldValue" rows="3" style="width: 100%" >{$ENTITY.description}</textarea>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_LOGO', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <input type="file" name="logo_box" value="" />
                                <input type="hidden" class="mode" value="logo" />
                                <div class="progress" style="display: none;">
                                    <div class="progress-bar"></div >
                                    <div class="percent"></div >
                                </div>
                                <div class="list_image">
                                    {if $RECORD_ID}
                                        <span class="img_uploaded">
                                            <img src="{$ENTITY.logo}">
                                            <input type="button" class="remove" value="&nbsp;X&nbsp;" title="Remove">
                                            <input type="hidden" name="logo" value="{$ENTITY.logo}">
                                        </span>
                                    {/if}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_COPYRIGHT', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <input type="text" class="inputElement fieldValue" name="copyright" style="width: 50%" value="{$ENTITY.copyright}" />
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="alert alert-info" style="margin: 0;">{vtranslate('LBL_SIDE_INFO', $QUALIFIED_MODULE)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_SLIDE', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <input type="file" name="images_box" value="" />
                                <input type="hidden" class="mode" value="images" />
                                <div class="progress" style="display: none;">
                                    <div class="progress-bar bg"></div >
                                    <div class="percent"></div >
                                </div>
                                <div class="list_image">
                                    {if $RECORD_ID}
                                        {foreach item=IMAGE from=$ENTITY.images}
                                        <span class="img_uploaded">
                                            <img src="{$IMAGE}">
                                            <input type="button" class="remove" value="&nbsp;X&nbsp;" title="Remove">
                                            <input type="hidden" name="images[]" value="{$IMAGE}">
                                        </span>
                                        {/foreach}
                                    {/if}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_SLIDE_TYPE', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <select name="slide_type" class="select2 inputElement fieldValue" >
                                    <option value="horizontal" {if $ENTITY.slide_type eq 'horizontal'}selected="" {/if}>Horizontal</option>
                                    <option value="vertical" {if $ENTITY.slide_type eq 'vertical'}selected="" {/if}>Vertical</option>
                                    <option value="fade" {if $ENTITY.slide_type eq 'fade'}selected="" {/if}>Fade</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_SLIDE_SPEED', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <input type="text" class="inputElement fieldValue" name="slide_speed" value="{if $ENTITY.slide_speed eq ''}1500{else}{$ENTITY.slide_speed}{/if}"/>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_SLIDE_EASING', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <select name="slide_easing" class="select2 inputElement fieldValue">
                                    <option value="linear" {if $ENTITY.slide_easing eq 'linear'}selected="" {/if}>Linear</option>
                                    <option value="swing" {if $ENTITY.slide_easing eq 'swing'}selected="" {/if}>Swing</option>
                                    <option value="easeInQuad" {if $ENTITY.slide_easing eq 'easeInQuad'}selected="" {/if}>In Quad</option>
                                    <option value="easeOutQuad" {if $ENTITY.slide_easing eq 'easeOutQuad'}selected="" {/if}>Out Quad</option>
                                    <option value="easeInOutQuad" {if $ENTITY.slide_easing eq 'easeInOutQuad'}selected="" {/if}>In Out Quad</option>
                                    <option value="easeInCubic" {if $ENTITY.slide_easing eq 'easeInCubic'}selected="" {/if}>In Cubic</option>
                                    <option value="easeOutCubic" {if $ENTITY.slide_easing eq 'easeOutCubic'}selected="" {/if}>Out Cubic</option>
                                    <option value="easeInOutCubic" {if $ENTITY.slide_easing eq 'easeInOutCubic'}selected="" {/if}>In Out Cubic</option>
                                    <option value="easeInQuart" {if $ENTITY.slide_easing eq 'easeInQuart'}selected="" {/if}>In Quart</option>
                                    <option value="easeOutQuart" {if $ENTITY.slide_easing eq 'easeOutQuart'}selected="" {/if}>Out Quart</option>
                                    <option value="easeInOutQuart" {if $ENTITY.slide_easing eq 'easeInOutQuart'}selected="" {/if}>In Out Quart</option>
                                    <option value="easeInQuint" {if $ENTITY.slide_easing eq 'easeInQuint'}selected="" {/if}>In Quint</option>
                                    <option value="easeOutQuint" {if $ENTITY.slide_easing eq 'easeOutQuint'}selected="" {/if}>Out Quint</option>
                                    <option value="easeInOutQuint" {if $ENTITY.slide_easing eq 'easeInOutQuint'}selected="" {/if}>In Out Quint</option>
                                    <option value="easeInExpo" {if $ENTITY.slide_easing eq 'easeInExpo'}selected="" {/if}>In Expo</option>
                                    <option value="easeOutExpo" {if $ENTITY.slide_easing eq 'easeOutExpo'}selected="" {/if}>Out Expo</option>
                                    <option value="easeInOutExpo" {if $ENTITY.slide_easing eq 'easeInOutExpo'}selected="" {/if}>In Out Expo</option>
                                    <option value="easeInSine" {if $ENTITY.slide_easing eq 'easeInSine'}selected="" {/if}>In Sine</option>
                                    <option value="easeOutSine" {if $ENTITY.slide_easing eq 'easeOutSine'}selected="" {/if}>Out Sine</option>
                                    <option value="easeInOutSine" {if $ENTITY.slide_easing eq 'easeInOutSine'}selected="" {/if}>In Out Sine</option>
                                    <option value="easeInCirc" {if $ENTITY.slide_easing eq 'easeInCirc'}selected="" {/if}>In Circ</option>
                                    <option value="easeOutCirc" {if $ENTITY.slide_easing eq 'easeOutCirc'}selected="" {/if}>Out Circ</option>
                                    <option value="easeInOutCirc" {if $ENTITY.slide_easing eq 'easeInOutCirc'}selected="" {/if}>In Out Circ</option>
                                    <option value="easeInElastic" {if $ENTITY.slide_easing eq 'easeInElastic'}selected="" {/if}>In Elastic</option>
                                    <option value="easeOutElastic" {if $ENTITY.slide_easing eq 'easeOutElastic'}selected="" {/if}>Out Elastic</option>
                                    <option value="easeInOutElastic" {if $ENTITY.slide_easing eq 'easeInOutElastic'}selected="" {/if}>In Out Elastic</option>
                                    <option value="easeInBack" {if $ENTITY.slide_easing eq 'easeInBack'}selected="" {/if}>In Back</option>
                                    <option value="easeOutBack" {if $ENTITY.slide_easing eq 'easeOutBack'}selected="" {/if}>Out Back</option>
                                    <option value="easeInOutBack" {if $ENTITY.slide_easing eq 'easeInOutBack'}selected="" {/if}>In Out Back</option>
                                    <option value="easeInBounce" {if $ENTITY.slide_easing eq 'easeInBounce'}selected="" {/if}>In Bounce</option>
                                    <option value="easeOutBounce" {if $ENTITY.slide_easing eq 'easeOutBounce'}selected="" {/if}>Out Bounce</option>
                                    <option value="easeInOutBounce" {if $ENTITY.slide_easing eq 'easeInOutBounce'}selected="" {/if}>In Out Bounce</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="alert alert-info" style="margin: 0;">{vtranslate('LBL_SOCIAL_INFO', $QUALIFIED_MODULE)}</div>
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_FACEBOOK', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <input type="text" class="inputElement fieldValue" name="social_facebook" value="{$ENTITY.social_facebook}" />
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_TWITTER', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <input type="text" class="inputElement fieldValue" name="social_twitter" value="{$ENTITY.social_twitter}" />
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_LINKEDIN', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <input type="text" class="inputElement fieldValue" name="social_linkedin" value="{$ENTITY.social_linkedin}" />
                            </td>
                        </tr>
                        <tr>
                            <td width="20%" class="{$WIDTHTYPE}" style="vertical-align: middle;">
                                <label class="muted pull-right marginRight10px">
                                    {vtranslate('LBL_HEADER_YOUTUBE', $QUALIFIED_MODULE)}
                                </label>
                            </td>
                            <td class="{$WIDTHTYPE}" style="border-left: none;">
                                <input type="text" class="inputElement fieldValue" name="social_youtube" value="{$ENTITY.social_youtube}" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>
    </div>
{/strip}