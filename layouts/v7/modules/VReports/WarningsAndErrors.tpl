<div id="globalmodal">
    <div id="massEditContainer" class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header contentsBackground">
                <button aria-hidden="true" class="close " data-dismiss="modal" type="button"><span aria-hidden="true" class='fa fa-close'></span></button>
                <h4>{vtranslate('LBL_FIND_ERROR', $MODULE_NAME)}</h4>
            </div>
            <div class="slimScrollDiv">
                <div name="massEditContent">
                    <div class="modal-body tabbable">
                        <div >
                            {vtranslate('Recommended update module before fix error.',$MODULE_NAME)}
                        </div>
                        <br><br>
                        <div class="summaryWidgetContainer">
                            <div class="headerError">
                                <span style="font-size: 15px;"><strong>{vtranslate('Errors', $MODULE_NAME)}</strong></span>
                            </div>
                            <div class="summaryWidgetContainer inside">
                                <table>
                                    <caption><strong>{vtranslate('LBL_ERROR_DASHBOARD', $MODULE_NAME)}:</strong></caption>
                                    <tr class="findMissingLink">
                                        <td class="fieldLabel col-lg-4">
                                            <span class="errorLabel pull-right">{vtranslate('Dashboard Reports missing vtiger_links', $MODULE_NAME)} :</span>
                                        </td>
                                        <td class="fieldValue col-lg-5">
                                            {if !empty($DASHBOARD_MISSING_LINK)}
                                                <font color="red">{$COUNT_DASHBOARD_MISSING_LINK} </font>
                                                <a class="fixError" data-fix="findMissingLink">
                                                    {vtranslate('Click here to fix', $MODULE_NAME)}
                                                </a> &nbsp;&nbsp;
                                                <a class="showError" data-fix="findMissingLink">
                                                    {vtranslate('Click here to expand', $MODULE_NAME)}
                                                </a>
                                            {else}
                                                <font color="green">0</font>
                                            {/if}
                                        </td>
                                    </tr>
                                    <tr class="list-findMissingLink">
                                        <td colspan="100%">
                                            <ul class="findMissingLink hide">
                                                {foreach from=$RAW_VALUE_DASHBOARD_MISSING_LINK item=raw}
                                                    <li>
                                                        {vtranslate('linkId', $MODULE_NAME)} : {$raw['linkid']}<br>
                                                        {vtranslate('linklabel', $MODULE_NAME)} : {$raw['linklabel']}<br>
                                                        {vtranslate('query', $MODULE_NAME)} : {$raw['query']}<br>
                                                    </li>
                                                {/foreach}
                                            </ul>
                                        </td>
                                    </tr>

                                    <tr class="findMissingWidget">
                                        <td class="fieldLabel col-lg-4">
                                            <span class="errorLabel pull-right">{vtranslate('Widgets missing', $MODULE_NAME)} :</span>
                                        </td>
                                        <td class="fieldValue col-lg-5">
                                            {if !empty($DASHBOARD_ERROR_WIDGET)}
                                                <font color="red">{$COUNT_DASHBOARD_ERROR_WIDGET} </font>
                                                <a class="fixError" data-fix="findMissingWidget">
                                                    {vtranslate('Click here to fix', $MODULE_NAME)}
                                                </a> &nbsp;&nbsp;
                                                <a class="showError" data-fix="findMissingWidget">
                                                    {vtranslate('Click here to expand', $MODULE_NAME)}
                                                </a>
                                            {else}
                                                <font color="green">0</font>
                                            {/if}
                                        </td>
                                    </tr>
                                    <tr class="list-findMissingWidget">
                                        <td colspan="100%">
                                            <ul class="findMissingWidget hide">
                                                {foreach from=$DASHBOARD_ERROR_WIDGET item=raw}
                                                    <li>
                                                        {vtranslate('linkId', $MODULE_NAME)} : {$raw['linkid']}<br>
                                                        {vtranslate('title', $MODULE_NAME)} : {$raw['title']}<br>
                                                        {vtranslate('query', $MODULE_NAME)} : {$raw['query']}<br>
                                                    </li>
                                                {/foreach}
                                            </ul>
                                        </td>
                                    </tr>

                                    <tr class="findEmptyLink">
                                        <td class="fieldLabel col-lg-4">
                                            <span class="errorLabel pull-right">{vtranslate('Empty vtiger_link on dashboard', $MODULE_NAME)} :</span>
                                        </td>
                                        <td class="fieldValue col-lg-5">
                                            {if !empty($DASHBOARD_EMPTY_LINK['emptyLink'])}
                                                <font color="red">{$COUNT_DASHBOARD_EMPTY_LINK} </font>
                                                <a class="fixError" data-fix="findEmptyLink">
                                                    {vtranslate('Click here to fix', $MODULE_NAME)}
                                                </a> &nbsp;&nbsp;
                                                <a class="showError" data-fix="findEmptyLink">
                                                    {vtranslate('Click here to expand', $MODULE_NAME)}
                                                </a>
                                            {else}
                                                <font color="green">0</font>
                                            {/if}
                                        </td>
                                    </tr>
                                    <tr class="list-findEmptyLink">
                                        <td colspan="100%">
                                            <ul class="findEmptyLink hide">
                                                {foreach from=$DASHBOARD_EMPTY_LINK['emptyLink'] item=raw}
                                                    <li>
                                                        {vtranslate('linkId', $MODULE_NAME)} : {$raw['linkid']}<br>
                                                        {vtranslate('linkurl', $MODULE_NAME)} : {$raw['linkurl']}<br>
                                                    </li>
                                                {/foreach}
                                                <li>{vtranslate('query', $MODULE_NAME)} : {$DASHBOARD_EMPTY_LINK['query']}<br></li>
                                            </ul>
                                        </td>
                                    </tr>

                                    <tr class="findDefaultTab">
                                        <td class="fieldLabel col-lg-4">
                                            <span class="errorLabel pull-right">{vtranslate('Dashboard Missing Default tab', $MODULE_NAME)} :</span>
                                        </td>
                                        <td class="fieldValue col-lg-5">
                                            {if $DASHBOARD_MISSING_DEFAULT_TAB == false}
                                                <font color="red">Yes </font>
                                                <a class="fixError" data-fix="findDefaultTab">
                                                    {vtranslate('Click here to fix', $MODULE_NAME)}
                                                </a>
                                            {else}
                                                <font color="green">No</font>
                                            {/if}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <div class="needHelp">
                            Need help? Contact us - the support is free.<br>
                            Email: help@vtexperts.com<br>
                            Phone: +1 (818) 495-5557<br>
                            <a href="javascript:void(0);" onclick="window.open('https://v2.zopim.com/widget/livechat.html?&amp;key=1P1qFzYLykyIVMZJPNrXdyBilLpj662a=en', '_blank', 'location=yes,height=600,width=500,scrollbars=yes,status=yes');"> <img src="layouts/vlayout/modules/VTEStore/resources/images/livechat.png" style="height: 28px"></a><br>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="pull-right cancelLinkContainer" style="margin-top: 0px;"><a class="cancelLink" type="reset" data-dismiss="modal"><strong>{vtranslate('LBL_CLOSE', $MODULE)}</strong></a></div>
            </div>
        </div>
    </div>
</div>
