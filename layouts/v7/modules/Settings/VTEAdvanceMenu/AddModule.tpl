{*/* * *******************************************************************************
* The content of this file is subject to the VTE Advance Menu ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

{strip}
	<div class="modal-dialog modal-lg addModuleContainer">
		<div class="modal-content">
			<input id="menu_id" type="hidden" name="menu_id" value="{$MENU_ID}" />
			<input id="group_id" type="hidden" name="group_id" value="{$GROUP_ID}" />
			<div class="modal-header" >
				<div class="clearfix">
					<div class="pull-right">
						<button type="button" class="close" aria-label="Close" data-dismiss="modal" style="color: inherit;">
							<span aria-hidden="true" class='fa fa-close'></span>
						</button>
					</div>
					<div class="">
						<h4 class="pull-left textOverflowEllipsis" style="word-break: break-all;max-width: 95%;">{vtranslate('LBL_ADD_MODULE', $QUALIFIED_MODULE)}&nbsp;&nbsp;</h4>
					</div>
				</div>
			</div>
			<div class="modal-body form-horizontal">
				<div class="row modulesContainer {if $APP_NAME neq $SELECTED_APP_NAME} hide {/if}" data-appname="{$APP_NAME}">
					<div class="col-lg-12 col-md-12 col-sm-12">
                        {if count($ALL_VISIBLE_MODULES) gt 0}
                            {foreach item=MODULE_NAME from=$ALL_VISIBLE_MODULES}
								<span class="btn-group" style="margin-bottom: 10px; margin-left: 25px; margin-right: -15px;">
										<buttton class="btn addButton btn-default module-buttons addModule" data-module="{$MODULE_NAME}" style="text-transform: inherit;margin-right:15px">{vtranslate($MODULE_NAME, $MODULE_NAME)}&nbsp;&nbsp;
											<i class="fa fa-plus"></i>
										</buttton>
									</span>
                            {/foreach}
                        {else}
							<h5>
								<center>
                                    {vtranslate('LBL_NO', $QUALIFIED_MODULE)} {vtranslate('LBL_MODULES', $QUALIFIED_MODULE)} {vtranslate('LBL_FOUND', $QUALIFIED_MODULE)}.</h4>
								</center>
							</h5>
                        {/if}
					</div>
				</div>
			</div>
			{include file="ModalFooter.tpl"|vtemplate_path:$QUALIFIED_MODULE}
		</div>
	</div>
{/strip}