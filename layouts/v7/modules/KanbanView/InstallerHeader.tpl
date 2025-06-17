{*/* * *******************************************************************************
* The content of this file is subject to the Kanban View ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */*}
{strip}
    <div class="editContainer" style="padding-left: 2%;padding-right: 2%">
    <div class="row">
        {assign var=LABELS value = ["step1" => "LBL_REQUIREMENTS", "step2" => "LBL_LICENSING", "step3" => "LBL_COMPLETE"]}
        {include file="BreadCrumbs.tpl"|vtemplate_path:$MODULE ACTIVESTEP=1 BREADCRUMB_LABELS=$LABELS MODULE=$QUALIFIED_MODULE}
    </div>
    <div class="clearfix"></div>
{/strip}