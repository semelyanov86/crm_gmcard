{*<!--
/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}
{strip}
    <form name="allowed_modules_form" id="updateAllowedModulesForm" action="index.php">
        <input type="hidden" name="module" value="ITS4YouDescriptions">
        <input type="hidden" name="action" value="SaveAllowedModules">
        <div class="padding-left1per container-fluid settingsIndexPage">
            <div class="widget_header row-fluid settingsHeader">
                <h3><a href="index.php?module={$CURRENT_MODULE}&view=List">{vtranslate('ITS4YouDescriptions', $CURRENT_MODULE)} {vtranslate('LBL_ALLOWED_MODULES', $CURRENT_MODULE)}</a></h3>
                <hr>
            </div>
            {include file="ModalFooter.tpl"|@vtemplate_path:$CURRENT_MODULE}
            <div  id="CompanyDetailsContainer" class="{if !empty($ERROR_MESSAGE)}hide{/if}">
                <div class="row-fluid">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="blockHeader">
                                <th colspan="2"><strong>{vtranslate('LBL_AVAILABLE_MODULES',$QUALIFIED_MODULE)}</strong></th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$SUPPORTED_MODULES item=module}
                                <tr>
                                    <td><input type="checkbox" name="allowed_{$module.tabid}" id="allowed_{$module.tabid}" {if $module.desc4youmoduleid}checked{/if}>
                                    <td>{$module.name|@getTranslatedString:$module.name}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>

                </div>
            </div>
            {include file="ModalFooter.tpl"|@vtemplate_path:$CURRENT_MODULE}
        </div>
    </form>
{/strip}