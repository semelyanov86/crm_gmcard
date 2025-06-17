{*/* * *******************************************************************************
* The content of this file is subject to the VTE Advance Menu ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

{strip}
	<div class="modal-dialog modal-lg addLinkContainer">
		<div class="modal-content">
			<input id="vte-advance-menu-id-add-new-modules" type="hidden" value="{$MENU_ID}" />
			<div class="modal-header" >
				<div class="clearfix">
					<div class="pull-right">
						<button type="button" class="close" aria-label="Close" data-dismiss="modal" style="color: inherit;">
							<span aria-hidden="true" class='fa fa-close'></span>
						</button>
					</div>
					<div class="">
						<h4 class="pull-left textOverflowEllipsis" style="word-break: break-all;max-width: 95%;">{vtranslate('LBL_ADD_NEW_MODULES_POPUP', $MODULE_NAME)}&nbsp;&nbsp;</h4>
					</div>
				</div>
			</div>
			<div class="modal-body form-horizontal">
				<ul>
				{foreach item=ENTITY_MODULE from=$NEW_ENTITY_MODULES}
					<li>{vtranslate($ENTITY_MODULE.tablabel, $ENTITY_MODULE.name)}</li>
				{/foreach}
				</ul>
				<p>{vtranslate('LBL_ADD_NEW_MODULES_POPUP_INFO', $MODULE_NAME)}</p>
			</div>
			<div class="modal-footer">
				<center>
					<button data-mode="addNewModulesToTools" class="btn btn-success" type="button"><strong>{vtranslate('LBL_ADD_NEW_MODULES_POPUP_BTN', $MODULE_NAME)}</strong></button>
					<button data-mode="doNotAddNewModules" class="btn" type="button"><strong>{vtranslate('LBL_DO_NOT_ADD_NEW_MODULES_POPUP_BTN', $MODULE_NAME)}</strong></button>
				</center>
			</div>
		</div>
	</div>
{/strip}