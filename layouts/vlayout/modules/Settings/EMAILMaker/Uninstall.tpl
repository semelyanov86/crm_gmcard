{*<!--
/*********************************************************************************
* The content of this file is subject to the EMAILMaker
* ("License"); You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
* Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
* All Rights Reserved.
********************************************************************************/
-->*}
{strip}
    <div class="container" id="Uninstall_{$MODULE}_Container">
        <form name="profiles_privilegies" action="index.php" method="post" class="form-horizontal">
            <input type="hidden" name="module" value="{$MODULE}" />
            <input type="hidden" name="view" value="" />
            <input type="hidden" name="license_key_val" id="license_key_val" value="{$LICENSE}" />
            <div class="row">
                <div class="widget_header row-fluid">
                    <div class="span8">
                        <h3>{vtranslate('LBL_UNINSTALL',$QUALIFIED_MODULE)}</h3>
                        {vtranslate('LBL_UNINSTALL_DESC',$QUALIFIED_MODULE)}
                    </div><div class="span4">
                        <div class="pull-right">
                            <button id="ITS4YouUninstall_btn" type="button" class="btn btn-danger marginLeftZero">{vtranslate('LBL_UNINSTALL',$QUALIFIED_MODULE)}</button>
                        </div>
                    </div>
                </div>
                <br>
            </div>
            <div class="row">
                <div class="col-sm-12 col-md-12 col-lg-12">
                    <table class="table table-bordered table-condensed table-responsive themeTableColor">
                        <thead>
                        <tr class="blockHeader">
                            <th class="mediumWidthType" colspan="2">
                                <span class="alignMiddle">{vtranslate('LBL_UNINSTALL', $QUALIFIED_MODULE)}</span>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="medium" width="30%">
                                <label class="muted marginRight10px pull-right">Module</label>
                            </td>
                            <td style="border-left: none;" class="medium">
                                <span>{$MODULE}</span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
{/strip}