{*/* * *******************************************************************************
* The content of this file is subject to the Kanban View ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}
{strip}
<select class="{if empty($NOCHOSEN)}select2{/if} col-sm-12 selectedOtherField" required="true"  {if $MULTIPLE} multiple {/if} style="min-width: 100px">
    <option value="none">{vtranslate('LBL_SELECT_FIELD')}</option>
    {foreach key=BLOCK_LABEL item=BLOCK_FIELDS from=$RECORD_STRUCTURE}
        {vtranslate($BLOCK_LABEL, $SOURCE_MODULE)}
        <optgroup label='{vtranslate($BLOCK_LABEL, $SOURCE_MODULE)}'>
            {foreach key=FIELD_NAME item=FIELD_MODEL from=$BLOCK_FIELDS}
                {if $FIELD_MODEL->getId() neq $PRIMARY_SETTING['primary_field']}
                    {assign var=FIELD_INFO value=$FIELD_MODEL->getFieldInfo()}
                    {assign var=MODULE_MODEL value=$FIELD_MODEL->getModule()}
                    {assign var="SPECIAL_VALIDATOR" value=$FIELD_MODEL->getValidator()}
                    {if !empty($COLUMNNAME_API)}
                        {assign var=columnNameApi value=$COLUMNNAME_API}
                    {else}
                        {assign var=columnNameApi value=getCustomViewColumnName}
                    {/if}
                    <option value="{$FIELD_MODEL->$columnNameApi()}" data-fieldtype="{$FIELD_MODEL->getFieldType()}" data-field-name="{$FIELD_NAME}"
                            {if !empty($OTHER_FIELD) AND decode_html($FIELD_MODEL->$columnNameApi()) eq $OTHER_FIELD}
                                {assign var=FIELD_TYPE value=$FIELD_MODEL->getFieldType()}
                                {assign var=SELECTED_FIELD_MODEL value=$FIELD_MODEL}
                                {if $FIELD_MODEL->getFieldDataType() == 'reference'}
                                    {$FIELD_TYPE='V'}
                                {/if}
                                {$FIELD_INFO['value'] = decode_html($CONDITION_INFO['value'])}
                                selected="selected"
                            {/if}
                            {if ($MODULE_MODEL->get('name') eq 'Calendar') && ($FIELD_NAME eq 'recurringtype')}
                                {assign var=PICKLIST_VALUES value = Calendar_Field_Model::getReccurencePicklistValues()}
                                {$FIELD_INFO['picklistvalues'] = $PICKLIST_VALUES}
                            {/if}
                            {if ($MODULE_MODEL->get('name') eq 'Calendar') && ($FIELD_NAME eq 'activitytype')}
                                {$FIELD_INFO['picklistvalues']['Task'] = vtranslate('Task', 'Calendar')}
                            {/if}
                            {if $FIELD_MODEL->getFieldDataType() eq 'reference'}
                                {assign var=referenceList value=$FIELD_MODEL->getWebserviceFieldObject()->getReferenceList()}
                                {if is_array($referenceList) && in_array('Users', $referenceList)}
                                    {assign var=USERSLIST value=array()}
                                    {assign var=CURRENT_USER_MODEL value = Users_Record_Model::getCurrentUserModel()}
                                    {assign var=ACCESSIBLE_USERS value = $CURRENT_USER_MODEL->getAccessibleUsers()}
                                    {foreach item=USER_NAME from=$ACCESSIBLE_USERS}
                                        {$USERSLIST[$USER_NAME] = $USER_NAME}
                                    {/foreach}
                                    {$FIELD_INFO['picklistvalues'] = $USERSLIST}
                                    {$FIELD_INFO['type'] = 'picklist'}
                                {/if}
                            {/if}
                            data-fieldinfo='{Vtiger_Util_Helper::toSafeHTML(ZEND_JSON::encode($FIELD_INFO))}'
                            {if !empty($SPECIAL_VALIDATOR)}data-validator='{Zend_Json::encode($SPECIAL_VALIDATOR)}'{/if}>
                        {if $SOURCE_MODULE neq $MODULE_MODEL->get('name')}
                            ({vtranslate($MODULE_MODEL->get('name'), $MODULE_MODEL->get('name'))})  {vtranslate($FIELD_MODEL->get('label'), $MODULE_MODEL->get('name'))}
                        {else}
                            {vtranslate($FIELD_MODEL->get('label'), $SOURCE_MODULE)}
                        {/if}
                    </option>
                {/if}
            {/foreach}
        </optgroup>
    {/foreach}
</select>