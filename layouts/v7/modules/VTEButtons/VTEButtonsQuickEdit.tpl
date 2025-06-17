<div class="modal-dialog modal-lg">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" aria-label="Close" data-dismiss="modal"><span aria-hidden="true" class="fa fa-close"></span></button>
            <h4>{vtranslate($SINGLE_MODULE, $MODULE)} {vtranslate('LBL_EDIT')}</h4>
        </div>
        <form id="vteButtonQuickEdit" name="vteButtonQuickEdit" action="index.php" enctype="multipart/form-data">
            <div class="modal-body">
                {if !empty($PICKIST_DEPENDENCY_DATASOURCE)}
                    <input type="hidden" name="picklistDependency" value='{Vtiger_Util_Helper::toSafeHTML($PICKIST_DEPENDENCY_DATASOURCE)}' />
                {/if}
                <input type="hidden" name="module" value="VTEButtons">
                <input type="hidden" name="vtebuttons_id" value="{$VTEBUTTONS_ID}">
                <input type="hidden" name="source_module" value="{$MODULE_NAME}">
                <input type="hidden" name="record" id="recordId" value="{$RECORD_ID}">
                <input type="hidden" name="action" value="ActionAjax">
                <input type="hidden" name="mode" value="doUpdateFields">
                {assign var="MODULE" value=$MODULE_NAME}
                <div class="fieldBlockContainer">
                    <table class="table table-borderless">
                        <tr>
                            {assign var=COUNTER value=0}
                            {foreach item =FIELD_NAME from=$ADD_FIELDS}
                            {assign var=FIELD_MODEL value=$ALL_FIELDS.$FIELD_NAME}
                            {assign var=DEFAULT_VALUE value=$RECORD_MODEL->get($FIELD_NAME)}
                            {assign var=FIELD_MODEL value=$FIELD_MODEL->set('fieldvalue', $DEFAULT_VALUE)}
                            {assign var="isReferenceField" value=$FIELD_MODEL->getFieldDataType()}
                            {assign var="refrenceList" value=$FIELD_MODEL->getReferenceList()}
                            {assign var="refrenceListCount" value=count($refrenceList)}
                            {if $FIELD_MODEL->isEditable() eq true}
                                {if $FIELD_MODEL->get('uitype') eq "19"}
                                    {if $COUNTER eq '1'}
                                        <td></td><td></td></tr><tr>
                                        {assign var=COUNTER value=0}
                                    {/if}
                                {/if}
                                {if $COUNTER eq 2}
                                    </tr><tr>
                                    {assign var=COUNTER value=1}
                                {else}
                                    {assign var=COUNTER value=$COUNTER+1}
                                {/if}
                                <td class="fieldLabel alignMiddle">
                                    {if $isReferenceField eq "reference"}
                                        {if $refrenceListCount > 1}
                                            {assign var="DISPLAYID" value=$FIELD_MODEL->get('fieldvalue')}
                                            {assign var="REFERENCED_MODULE_STRUCTURE" value=$FIELD_MODEL->getUITypeModel()->getReferenceModule($DISPLAYID)}
                                            {if !empty($REFERENCED_MODULE_STRUCTURE)}
                                                {assign var="REFERENCED_MODULE_NAME" value=$REFERENCED_MODULE_STRUCTURE->get('name')}
                                            {/if}
                                            <select style="width: 140px;" class="select2 referenceModulesList">
                                                {foreach key=index item=value from=$refrenceList}
                                                    <option value="{$value}" {if $value eq $REFERENCED_MODULE_NAME} selected {/if}>{vtranslate($value, $value)}</option>
                                                {/foreach}
                                            </select>
                                        {else}
                                            {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                                        {/if}
                                    {else if $FIELD_MODEL->get('uitype') eq "83"}
                                        {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE) COUNTER=$COUNTER MODULE=$MODULE}
                                        {if $TAXCLASS_DETAILS}
                                            {assign 'taxCount' count($TAXCLASS_DETAILS)%2}
                                            {if $taxCount eq 0}
                                                {if $COUNTER eq 2}
                                                    {assign var=COUNTER value=1}
                                                {else}
                                                    {assign var=COUNTER value=2}
                                                {/if}
                                            {/if}
                                        {/if}
                                    {else}
                                        {if $MODULE eq 'Documents' && $FIELD_MODEL->get('label') eq 'File Name'}
                                            {assign var=FILE_LOCATION_TYPE_FIELD value=$RECORD_STRUCTURE['LBL_FILE_INFORMATION']['filelocationtype']}
                                            {if $FILE_LOCATION_TYPE_FIELD}
                                                {if $FILE_LOCATION_TYPE_FIELD->get('fieldvalue') eq 'E'}
                                                    {vtranslate("LBL_FILE_URL", $MODULE)}&nbsp;<span class="redColor">*</span>
                                                {else}
                                                    {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                                                {/if}
                                            {else}
                                                {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                                            {/if}
                                        {else}
                                            {vtranslate($FIELD_MODEL->get('label'), $MODULE)}
                                        {/if}
                                    {/if}
                                    &nbsp;{if $FIELD_MODEL->isMandatory() eq true} <span class="redColor">*</span> {/if}
                                </td>
                                {if $FIELD_MODEL->get('uitype') neq '83'}
                                    <td class="fieldValue" {if $FIELD_MODEL->getFieldDataType() eq 'boolean'} style="width:25%" {/if} {if $FIELD_MODEL->get('uitype') eq '19'} colspan="3" {assign var=COUNTER value=$COUNTER+1} {/if}>
                                      {include file=vtemplate_path($FIELD_MODEL->getUITypeModel()->getTemplateName(),$MODULE)}
                                    </td>
                                {/if}
                            {/if}
                            {/foreach}
                            {*If their are odd number of fields in edit then border top is missing so adding the check*}
                            {if $COUNTER is odd}
                                <td></td>
                                <td></td>
                            {/if}
                        </tr>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <a class="cancelLink cancelLinkContainer pull-right" type="reset" data-dismiss="modal">{vtranslate('LBL_CANCEL', $MODULE_NAME)}</a>
                <button class="btn btn-success" type="button" name="vteButtonsSave" ><strong>{vtranslate('LBL_SAVE', $MODULE)}</strong></button>
            </div>
        </form>
    </div>
</div>