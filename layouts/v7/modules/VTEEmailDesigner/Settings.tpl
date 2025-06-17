{*<!--
/* ********************************************************************************
* The content of this file is subject to the Custom Header/Bills ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */
-->*}
<div class="elements-db" style="display: none;">
    <div class="tab-elements element-tab active">
        <ul class="elements-accordion">
            {foreach key=BLOCK_LABEL_KEY item=BLOCK_ITEM from=$BLOCK_CATEGORY}
                <li class="elements-accordion-item" data-type="{$BLOCK_ITEM['name']}"><a class="elements-accordion-item-title">{$BLOCK_ITEM['name']}</a>
                    <div class="elements-accordion-item-content">
                        <ul class="elements-list">
                            {assign var=ITEMS value=$MODULE_MODEL->getBlocksByCat({$BLOCK_ITEM['id']})}
                            {foreach key=ITEM_KEY item=ITEM from=$ITEMS}
                                <li>
                                    <div class="elements-list-item">
                                        <div class="preview">
                                            <div class="elements-item-icon">
                                                <i class="{$ITEM['icon']}"></i>
                                            </div>
                                            <div class="elements-item-name">
                                                {$ITEM['name']}
                                            </div>
                                        </div>
                                        <div class="view">
                                            <div class="sortable-row">
                                                <div class="sortable-row-container">
                                                    <div class="sortable-row-actions">
                                                        <div class="row-move row-action">
                                                            <i class="fa fa-arrows-alt"></i>
                                                        </div>
                                                        <div class="row-remove row-action">
                                                            <i class="fa fa-remove"></i>
                                                        </div>
                                                        <div class="row-duplicate row-action">
                                                            <i class="fa fa-files-o"></i>
                                                        </div>
                                                        <div class="row-code row-action">
                                                            <i class="fa fa-code"></i>
                                                        </div>
                                                    </div>
                                                    {assign var=property value=","|explode:$ITEM['property']}
                                                    <div class="sortable-row-content" data-id="{$ITEM['id']}" data-types="{$ITEM['property']}" data-last-type="{$property[0]}">
                                                        {decode_html($ITEM['html']|replace:'[site-url]':$ACTUAL_LINK)}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            {/foreach}
                        </ul>
                    </div>
                </li>
            {/foreach}
        </ul>
    </div>
</div>
<div class="editor">
</div>
<div id="previewModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Preview</h4>
            </div>
            <div class="modal-body">
                <div class="">
                    <label for="">URL : </label> <span class="preview_url"></span>
                </div>
                <iframe id="previewModalFrame" width="100%" height="400px"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<div id="demp"></div>

