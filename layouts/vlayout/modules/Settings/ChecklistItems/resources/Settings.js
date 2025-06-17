/* ********************************************************************************
 * The content of this file is subject to the ChecklistItems ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

var Settings_ChecklistItems_Js = {
    /* For License page - Begin */
    init : function() {
        this.initiate();
    },
    /*
     * Function to initiate the step 1 instance
     */
    initiate : function(){
        var step=jQuery(".installationContents").find('.step').val();
        this.initiateStep(step);
    },
    /*
     * Function to initiate all the operations for a step
     * @params step value
     */
    initiateStep : function(stepVal) {
        var step = 'step'+stepVal;
        this.activateHeader(step);
    },

    activateHeader : function(step) {
        var headersContainer = jQuery('.crumbs ');
        headersContainer.find('.active').removeClass('active');
        jQuery('#'+step,headersContainer).addClass('active');
    },

    registerActivateLicenseEvent : function() {
        var aDeferred = jQuery.Deferred();
        jQuery(".installationContents").find('[name="btnActivate"]').click(function() {
            var license_key=jQuery('#license_key');
            if(license_key.val()=='') {
                errorMsg = "License Key cannot be empty";
                license_key.validationEngine('showPrompt', errorMsg , 'error','bottomLeft',true);
                aDeferred.reject();
                return aDeferred.promise();
            }else{
                var progressIndicatorElement = jQuery.progressIndicator({
                    'position' : 'html',
                    'blockInfo' : {
                        'enabled' : true
                    }
                });
                var params = {};
                params['module'] = app.getModuleName();
                params['action'] = 'Activate';
                params['mode'] = 'activate';
                params['license'] = license_key.val();

                AppConnector.request(params).then(
                    function(data) {
                        progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                        if(data.success) {
                            var message=data.result.message;
                            if(message !='Valid License') {
                                jQuery('#error_message').html(message);
                                jQuery('#error_message').show();
                            }else{
                                document.location.href="index.php?module=ChecklistItems&parent=Settings&view=Settings&mode=step3";
                            }
                        }
                    },
                    function(error) {
                        progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    }
                );
            }
        });
    },

    registerValidEvent: function () {
        jQuery(".installationContents").find('[name="btnFinish"]').click(function() {
            var progressIndicatorElement = jQuery.progressIndicator({
                'position' : 'html',
                'blockInfo' : {
                    'enabled' : true
                }
            });
            var params = {};
            params['module'] = app.getModuleName();
            params['action'] = 'Activate';
            params['mode'] = 'valid';

            AppConnector.request(params).then(
                function (data) {
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});
                    if (data.success) {
                        document.location.href = "index.php?module=ChecklistItems&parent=Settings&view=Settings";
                    }
                },
                function (error) {
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});
                }
            );
        });
    },
    /* For License page - End */

    ckEditorInstance: false,
    loading: false,

    registerEditBtn: function () {
        var thisInstance = this;
        jQuery('.editButton').on('click', function (event) {
            event.preventDefault();
            if(thisInstance.loading){
                return;
            }
            thisInstance.loading = true;
            var url = jQuery(this).data('url');
            app.showModalWindow(null, url, function () {
                thisInstance.loading = false;
                jQuery(document).find('.blockOverlay').unbind('click');
                app.registerEventForDatePickerFields();
                thisInstance.registerAddChecklistItem();
                thisInstance.registerCheckboxBtn();
                thisInstance.registerRemoveChecklistItem();
                thisInstance.sortableChecklistItems();
                thisInstance.registerCkEditor();
                thisInstance.registerTooltipEvents();
            });
			return false;
        });
    },

    registerNoneAdminUserEvent: function () {
        var thisInstance = this;
        jQuery('#none_user_permission').on('click', function (event) {
            //event.preventDefault();
            var allow = jQuery(this).is(':checked') ? 1 : 0;
            var aDeferred = jQuery.Deferred();
            var progressIndicatorElement = jQuery.progressIndicator({
                'position': 'html',
                'blockInfo': {
                    'enabled': true
                }
            });
            var params = {};
            params['module']='ChecklistItems';
            params['action']='UserPermissions';
            params['permissions']=allow;
            params['parent']='Settings';
            AppConnector.request(params).then(
                function (data) {
                    progressIndicatorElement.progressIndicator({
                        'mode': 'hide'
                    });
                    aDeferred.resolve(data);
                },
                function (error, err) {
                    progressIndicatorElement.progressIndicator({
                        'mode': 'hide'
                    });
                    aDeferred.reject(error, err);
                }
            );
            return aDeferred.promise();
        });
    },

    registerRemoveChecklistItem: function () {
        var thisInstance = this;
        jQuery('.deleteButton', '.items-list-table').on('click', function (event) {
            event.preventDefault();
            jQuery(this).closest('tr').remove();
        });
    },

    registerAddChecklistItem: function () {
        var thisInstance = this;
        jQuery('#add-checklist-item').on('click', function (event) {
            event.preventDefault();
            var containner = jQuery(this).closest('#vte-primary-box');
            var html = jQuery('.items-list-table tr:first', containner).clone();
            jQuery('input[type=text], textarea', html).val('');
            jQuery('input[type=text]', html).each(function () {
                if (jQuery(this).attr('name') == 'date[]') {
                    jQuery(this).removeAttr('readonly');
                    jQuery(this).addClass('dateField');
                }
            });
            jQuery('input[type=checkbox]', html).attr('checked', true);
            jQuery('input.allow_note_value', html).val(1);
            jQuery('input.allow_upload_value', html).val(1);
            var textarea_id = parseInt(jQuery('#textarea_id').val()) + 1;
            jQuery('.description', html).attr('id', 'desc_' + textarea_id);
            jQuery('#textarea_id').val(textarea_id);
            jQuery('.items-list-table tbody:first', containner).append(html);
            app.registerEventForDatePickerFields();
            thisInstance.registerRemoveChecklistItem();
            thisInstance.sortableChecklistItems();
            thisInstance.registerCkEditorSingle('desc_' + textarea_id);
            thisInstance.registerTooltipEvents();
        });
    },

    registerDeleteBtn: function () {
        var thisInstance = this;
        jQuery('.deleteButton', '#listViewContents').on('click', function (event) {
            event.preventDefault();
            var url = jQuery(this).data('url');
            var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
            Vtiger_Helper_Js.showConfirmationBox({'message': message}).then(function (data) {
                    var aDeferred = jQuery.Deferred();
                    var progressIndicatorElement = jQuery.progressIndicator({
                        'position': 'html',
                        'blockInfo': {
                            'enabled': true
                        }
                    });
                    AppConnector.request(url).then(
                        function (data) {
                            thisInstance.loadRecords().then(function () {
                                progressIndicatorElement.progressIndicator({
                                    'mode': 'hide'
                                });
                            });
                            aDeferred.resolve(data);
                        },
                        function (error, err) {
                            progressIndicatorElement.progressIndicator({
                                'mode': 'hide'
                            });
                            aDeferred.reject(error, err);
                        }
                    );
                    return aDeferred.promise();
                },
                function (error, err) {
                }
            );
        });
    },


    registerCloseBtn: function () {
        jQuery(document).on('click', '#CustomView .ui-checklist-closer', function (event) {
            event.preventDefault();
            app.hideModalWindow();
        });
    },

    registerCheckboxBtn: function () {
        jQuery(document).on('click', '#CustomView .allow_note, #CustomView .allow_upload', function (event) {

            if (jQuery(this).is(':checked')) {
                jQuery(this).parent().find('input[type=hidden]').val(1);
            } else {
                jQuery(this).parent().find('input[type=hidden]').val(0);
            }
        });
    },


    registerSaveBtn: function () {
        var thisInstance = this;
        jQuery(document).on('click', '#save-checklist', function (event) {
            event.preventDefault();
            var aDeferred = jQuery.Deferred();

            var form = jQuery('#CustomView');
            var textAreaElements = jQuery('.description', form);
            textAreaElements.each(function (index) {
                var element_id = jQuery(this).attr('id');
                var plainText = CKEDITOR.instances[element_id].getData();
                jQuery(this).val(plainText);
            });

            var formData = form.serialize();
            var progressIndicatorElement = jQuery.progressIndicator({
                'position': 'html',
                'blockInfo': {
                    'enabled': true
                }
            });
            AppConnector.request(formData).then(
                function (data) {
                    thisInstance.loadRecords().then(function () {
                        progressIndicatorElement.progressIndicator({
                            'mode': 'hide'
                        });
                        app.hideModalWindow();
                    });
                    aDeferred.resolve(data);
                },
                function (error, err) {
                    app.hideModalWindow();
                    aDeferred.reject(error, err);
                }
            );
            return aDeferred.promise();
        });
    },

    loadRecords: function () {
        var thisInstance = this;
        var aDeferred = jQuery.Deferred();
        var url = 'index.php?module=ChecklistItems&view=Settings&parent=Settings&ajax=true';
        AppConnector.request(url).then(
            function (data) {
                jQuery('.vte-checklist-items tbody').html(data);
                thisInstance.registerEditBtn();
                thisInstance.registerDeleteBtn();
                aDeferred.resolve(data);
            },
            function (error, err) {
                app.hideModalWindow();
                aDeferred.reject(error, err);
            }
        );
        return aDeferred.promise();
    },

    sortableRecords: function () {
        var thisInstance = this;
        var container = jQuery(".vte-checklist-items tbody");
        container.sortable({
            handle: ".icon-move",
            cursor: "move",
            update: function (event, ui) {
                var records = [];
                jQuery(this).find('.icon-move').each(function (index, el) {
                    records.push(jQuery(el).data('record'));
                });
                //update priority
                var aDeferred = jQuery.Deferred();
                var params = {};
                params['module'] = 'ChecklistItems';
                params['action'] = 'SortOrder';
                params['parent'] = 'Settings';
                params['records'] = records;
                AppConnector.request(params).then(
                    function (data) {
                        aDeferred.resolve(data);
                    },
                    function (error, err) {
                        aDeferred.reject(error, err);
                    }
                );
                return aDeferred.promise();
            }
        });
        container.disableSelection();
    },

    sortableChecklistItems: function () {
        var thisInstance = this;
        var container = jQuery(".items-list-table tbody");
        container.sortable({
            handle: ".icon-move",
            cursor: "move",
            start: function (event, ui)
            {
                var id_textarea = ui.item.find("textarea").attr("id");
                CKEDITOR.instances[id_textarea].destroy();
            },
            stop: function (event, ui)
            {
                var id_textarea = ui.item.find("textarea").attr("id");
                thisInstance.registerCkEditorSingle(id_textarea);
            }
        });
        container.disableSelection();
        container.find("input").bind('click.sortable mousedown.sortable',function(e){
            e.stopImmediatePropagation();
        });
    },

    unInstall: function () {
        var thisInstance = this;
        jQuery('#rel_uninstall_btn').on('click', function () {
            var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
            Vtiger_Helper_Js.showConfirmationBox({'message': message}).then(function (data) {
                app.showModalWindow(null, 'index.php?module=ChecklistItems&action=Uninstall&parent=Settings');
            });
        });
    },

    registerActiveBtn: function () {
        var thisInstance = this;
        jQuery(document).on('click', '.vte-checklist-items .checklist_status', function (event) {
            //update priority
            var aDeferred = jQuery.Deferred();
            var params = {};
            params['module'] = 'ChecklistItems';
            params['action'] = 'ChangeStatus';
            params['parent'] = 'Settings';
            params['record'] = jQuery(this).data('record');
            params['status'] = (jQuery(this).data('status') == 'Active') ? 'Inactive' : 'Active';
            AppConnector.request(params).then(
                function (data) {
                    aDeferred.resolve(data);
                    thisInstance.loadRecords();
                },
                function (error, err) {
                    aDeferred.reject(error, err);
                }
            );
            return aDeferred.promise();
        });
    },

    /**
     * Function to get ckEditorInstance
     */
    getckEditorInstance: function () {
        if (this.ckEditorInstance == false) {
            this.ckEditorInstance = new Vtiger_CkEditor_Js();
        }
        return this.ckEditorInstance;
    },

    registerCkEditor: function () {
        var textarea_id = 0;
        var container = jQuery('#vte-primary-box');
        var textAreaElements = jQuery('.description', container);
        var ckEditorInstance = this.getckEditorInstance();
        textAreaElements.each(function (index) {
            jQuery(this).attr('id', 'desc_' + index);
            textarea_id = index;
            var customConfig = {};
            customConfig['height'] = '75px';
            ckEditorInstance.loadCkEditor(jQuery(this), customConfig);

        });
        jQuery('#textarea_id').val(textarea_id);
    },

    registerCkEditorSingle: function (id) {
        var container = jQuery('#vte-primary-box');
        var textAreaElement = jQuery('#' + id, container);
        var tdElement = textAreaElement.closest('td');
        tdElement.find('div.cke').remove();
        var ckEditorInstance = this.getckEditorInstance();
        var customConfig = {};
        customConfig['height'] = '75px';
        ckEditorInstance.loadCkEditor(textAreaElement, customConfig);
    },

    infoPopup: function(url){
        app.showModalWindow(null, url);
    },

    /**
     * Function to trigger tooltip feature.
     */
    registerTooltipEvents: function() {
        var elementsInfo = jQuery(document).find('#vte-primary-box .icon-info');
        var lastPopovers = [];

        // Fetching reference fields often is not a good idea on a given page.
        // The caching is done based on the URL so we can reuse.
        var CACHE_ENABLED = true; // TODO - add cache timeout support.
        function prepareAndShowTooltipView() {
            hideAllTooltipViews();

            var el = jQuery(this);
            var url = el.data('url');
            console.log(url);
            if (url == '') {
                return;
            }

            // Rewrite URL to retrieve Tooltip view.
            //url = url.replace('view=', 'xview=') + '&view=TooltipAjax';

            var cachedView = CACHE_ENABLED ? jQuery('[data-url-cached="'+url+'"]') : null;
            if (cachedView && cachedView.length) {console.log('ccccccccccccccccc');
                showTooltip(el, cachedView.html());
            } else {
                jQuery.ajax({
                    url: url,
                    success: function(data){
                        cachedView = jQuery('<div>').css({display:'none'}).attr('data-url-cached', url);
                        cachedView.html(data);
                        jQuery('body').append(cachedView);
                        showTooltip(el, data);
                    }
                });
            }
        }

        function get_popover_placement(el) {
            var width = window.innerWidth;
            var left_pos = jQuery(el).offset().left;
            if (width - left_pos > 400) return 'right';
            return 'left';
        }

        function showTooltip(el, data) {
            var the_placement = get_popover_placement(el);
            el.popover({
                title: jQuery(el).attr('title'),
                trigger: 'manual',
                content: data,
                animation: false,
                placement:  the_placement,
                template: '<div class="popover popover-tooltip"><div class="arrow"></div><div class="popover-inner"><button name="vtTooltipClose" class="close" style="color:white;opacity:1;font-weight:lighter;position:relative;top:3px;right:3px;">x</button><h3 class="popover-title"></h3><div class="popover-content"><div></div></div></div></div>'
            });
            lastPopovers.push(el.popover('show'));
            registerToolTipDestroy();
        }

        function hideAllTooltipViews() {
            // Hide all previous popover
            var lastPopover = null;
            while (lastPopover = lastPopovers.pop()) {
                lastPopover.popover('hide');
            }
        }

        elementsInfo.each(function(index, el){
            jQuery(el).hoverIntent({
                interval: 100,
                sensitivity: 7,
                timeout: 10,
                over: prepareAndShowTooltipView,
                out: hideAllTooltipViews
            });
        });

        function registerToolTipDestroy() {
            jQuery('button[name="vtTooltipClose"]').on('click', function(e){
                var lastPopover = lastPopovers.pop();
                lastPopover.popover('hide');
            });
        }
    },

    registerEvents: function () {
        this.registerEditBtn();
        this.registerNoneAdminUserEvent();
        this.registerDeleteBtn();
        this.registerCloseBtn();
        this.registerSaveBtn();
        this.sortableRecords();
        this.registerActiveBtn();
        this.unInstall();
        /* For License page - Begin */
        this.init();
        this.registerActivateLicenseEvent();
        this.registerValidEvent();
        /* For License page - End */
    }

};
jQuery(document).ready(function () {
    Settings_ChecklistItems_Js.registerEvents();
});