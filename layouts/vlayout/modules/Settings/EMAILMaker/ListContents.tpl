{*<!--
/* * *******************************************************************************
 * The content of this file is subject to the EMAIL Maker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}
{strip}
<div id="popupPageContainer" class="popupBackgroundColor">
	<div class="emailTemplatesContainer">
		<h3>{vtranslate($MODULE,$QUALIFIED_MODULE)}</h3>
		<hr>
		<div style="padding:0 10px">
			<table class="table table-bordered table-condensed">
				<thead>
					<tr class="listViewHeaders">
						<th>
							<a>{vtranslate('LBL_TEMPLATE_NAME',$QUALIFIED_MODULE)}</a>
						</th>
						<th>
							<a>{vtranslate('LBL_MODULENAMES','EMAILMaker')}</a>
						</th>
                                                <th>
							<a>{vtranslate('LBL_SUBJECT',$QUALIFIED_MODULE)}</a>
						</th>
						<th>
							<a>{vtranslate('LBL_DESCRIPTION',$QUALIFIED_MODULE)}</a>
						</th>
					</tr>
				</thead>
				{foreach item=EMAIL_TEMPLATE from=$EMAIL_TEMPLATES}
				<tr class="listViewEntries" data-type="{$EMAIL_TEMPLATE->get('type')}" data-id="{$EMAIL_TEMPLATE->get('templateid')}" data-name="{$EMAIL_TEMPLATE->get('subject')}" data-info="{$EMAIL_TEMPLATE->get('body')}">
					<td><a class="cursorPointer">{vtranslate($EMAIL_TEMPLATE->get('templatename',$QUALIFIED_MODULE))}</a></td>
					<td><a class="cursorPointer">{vtranslate($EMAIL_TEMPLATE->get('module',$QUALIFIED_MODULE))}</a></td>
                                        <td><a class="cursorPointer">{vtranslate($EMAIL_TEMPLATE->get('subject',$QUALIFIED_MODULE))}</a></td>
					<td>{vtranslate($EMAIL_TEMPLATE->get('description',$QUALIFIED_MODULE))}</td>
				</tr>
				{/foreach}
			</table>
		</div>
	</div>
		<input type="hidden" class="triggerEventName" value="{$smarty.request.triggerEventName}"/>
</div>
{/strip}