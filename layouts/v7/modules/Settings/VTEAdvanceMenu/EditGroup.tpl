{*/* * *******************************************************************************
* The content of this file is subject to the VTE Advance Menu ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}

{strip}
	<div class="modal-dialog modal-lg editGroupContainer">
		<div class="modal-content">
			<input id="group_id" type="hidden" name="group_id" value="{$GROUP_INFO.groupid}" />
			<div class="modal-header" >
				<div class="clearfix">
					<div class="pull-right">
						<button type="button" class="close" aria-label="Close" data-dismiss="modal" style="color: inherit;">
							<span aria-hidden="true" class='fa fa-close'></span>
						</button>
					</div>
					<div class="">
						<h4 class="pull-left textOverflowEllipsis" style="word-break: break-all;max-width: 95%;">{vtranslate('LBL_EDIT_GROUP', $QUALIFIED_MODULE)}&nbsp;&nbsp;</h4>
					</div>
				</div>
			</div>
			<div class="modal-body form-horizontal">
				<table class="table table-borderless">
					<tbody>
						<tr>
							<td class="fieldLabel alignMiddle">{vtranslate('LBL_GROUP_LABEL', $QUALIFIED_MODULE)}</td>
							<td class="fieldValue">
								<input type="text" value="{$GROUP_INFO.label}" name="label" class="input-xxlarge inputElement" />
							</td>
						</tr>
						<tr>
							<td class="fieldLabel alignMiddle">
								{vtranslate('LBL_GROUP_ICON_CLASS', $QUALIFIED_MODULE)}&nbsp;&nbsp;
								<a href="#" rel="tooltip" title="" data-original-title="{vtranslate('LBL_GROUP_ICON_CLASS_INFO', $QUALIFIED_MODULE)}">
									<i class="fa fa-info-circle"></i>
								</a>
							</td>
							<td class="fieldValue">
								<input type="text" value="{$GROUP_INFO.icon_class}" name="icon_class" class="input-xxlarge inputElement" />
								<br />
								<p id="icon-preview">
									<span>{vtranslate('LBL_ICON_PREVIEW', $QUALIFIED_MODULE)}:&nbsp;&nbsp;&nbsp;&nbsp;</span>
									<i class="{$GROUP_INFO.icon_class}" aria-hidden="true"></i>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			{include file="ModalFooter.tpl"|vtemplate_path:$QUALIFIED_MODULE}
		</div>
	</div>
{/strip}