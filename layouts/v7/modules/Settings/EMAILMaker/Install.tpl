{*<!--
/*********************************************************************************
 * The content of this file is subject to the EMAILMaker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
********************************************************************************/
-->*}
{strip}
    <div class="editContainer" style="padding-left: 2%;padding-right: 2%">
        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12">
                <h3>{vtranslate('LBL_MODULE_NAME',$QUALIFIED_MODULE)} {vtranslate('LBL_INSTALL',$QUALIFIED_MODULE)}</h3>
            </div>
        </div>
        <hr>
        <div class="row">
            {assign var=LABELS value = ["step1" => "LBL_VALIDATION", "step2" => "LBL_FINISH"]}
            {include file="BreadCrumbs.tpl"|vtemplate_path:$MODULE ACTIVESTEP=$STEP BREADCRUMB_LABELS=$LABELS MODULE=$QUALIFIED_MODULE}
        </div>
        <div class="clearfix"></div>
        <div class="installationContents">
            <div style="border:1px solid #ccc;padding:1%;{if $STEP neq "1"}display:none;{/if}" id="stepContent1">
                <form name="install" id="editLicense" method="POST" action="index.php" class="form-horizontal">
                    <input type="hidden" name="module" value="{$MODULE}"/>
                    <input type="hidden" name="view" value="List"/>
                    <input type="hidden" name="parent" value="Settings">
                    <div class="row">
                        <div class="col-sm-12 col-md-12 col-lg-12">
                            <h4><strong>{vtranslate('LBL_WELCOME',$QUALIFIED_MODULE)}</strong></h4>
                            <br>
                            <p>
                                {vtranslate('LBL_WELCOME_DESC',$QUALIFIED_MODULE)}<br>
                                {vtranslate('LBL_WELCOME_FINISH',$QUALIFIED_MODULE)}
                            </p>
                        </div>
                    </div>
                    <br>
                    <div class="row">
                        <div class="col-sm-12 col-md-12 col-lg-12">
                            <label><strong>{vtranslate('LBL_INSERT_KEY',$QUALIFIED_MODULE)}</strong></label>
                            <br>
                            <p>
                                {vtranslate('LBL_ONLINE_ASSURE',$QUALIFIED_MODULE)}
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 col-md-12 col-lg-12">
                            {include file='LicenseDetails.tpl'|@vtemplate_path:$QUALIFIED_MODULE}
                        </div>
                    </div>
                </form>
            </div>
            <div style="border:1px solid #ccc;padding:1%;{if $STEP neq "4"}display:none;{/if}" id="stepContent2">
                <div class="row">
                    <div class="col-sm-12 col-md-12 col-lg-12">
                        <h4><strong>{vtranslate('LBL_INSTALL_SUCCESS',$QUALIFIED_MODULE)}</strong></h4><br>
                        <div class="controls">
                            <a id="next_button" class="btn btn-success" href="{$DEFAULT_VIEW_URL}">
                                <strong>{vtranslate('LBL_FINISH',$QUALIFIED_MODULE)}</strong>
                            </a>&nbsp;&nbsp;
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script language="javascript" type="text/javascript">
        jQuery(document).ready(function () {
            var thisInstance = EMAILMaker_License_Js.getInstance();
            thisInstance.registerInstallEvents();
        });
    </script>
{/strip}