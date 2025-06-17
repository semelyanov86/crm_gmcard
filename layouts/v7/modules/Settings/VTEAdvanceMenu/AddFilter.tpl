{*/* * *******************************************************************************
* The content of this file is subject to the VTE Advance Menu ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

{strip}
	<div class="modal-dialog modal-sm addFilterContainer">
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
						<h4 class="pull-left textOverflowEllipsis" style="word-break: break-all;max-width: 95%;">{vtranslate('LBL_CREATE_FILTER', $QUALIFIED_MODULE)}&nbsp;&nbsp;</h4>
					</div>
				</div>
			</div>
			<div class="modal-body form-horizontal">
				<table class="table table-borderless">
					<tbody>
						<tr>
							<td class="fieldLabel alignMiddle">{vtranslate('LBL_SELECT_MODULE_LABEL', $QUALIFIED_MODULE)}</td>
							<td class="fieldValue">
								<select class="select2" name="source_module">
									<option value="">{vtranslate('LBL_SELECT_AN_OPTION', $QUALIFIED_MODULE)}</option>
									{foreach item=MODULE_NAME from=$ALL_VISIBLE_MODULES}
										<option value="{$MODULE_NAME}">{vtranslate($MODULE_NAME, $MODULE_NAME)}</option>
									{/foreach}
								</select>
							</td>
						</tr>
						<tr>
							<td class="fieldLabel alignMiddle">{vtranslate('LBL_SELECT_FILTER_LABEL', $QUALIFIED_MODULE)}</td>
							<td class="fieldValue">
								<select class="select2" name="filter">
									<option value="">{vtranslate('LBL_SELECT_AN_OPTION', $QUALIFIED_MODULE)}</option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			{include file="ModalFooter.tpl"|vtemplate_path:$QUALIFIED_MODULE}
		</div>
	</div>
{/strip}