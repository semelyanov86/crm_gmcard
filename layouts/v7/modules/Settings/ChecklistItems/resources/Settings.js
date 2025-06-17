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
                app.helper.showProgress();
                var params = {};
                params['module'] = app.getModuleName();
                params['action'] = 'Activate';
                params['mode'] = 'activate';
                params['license'] = license_key.val();

                app.request.post({'data' : params}).then(
                    function(err,data){
                        if(err === null) {
                            app.helper.hideProgress();
                            if(data) {
                                var message=data.message;
                                if(message !='Valid License') {
                                    jQuery('#error_message').html(message);
                                    jQuery('#error_message').show();
                                }else{
                                    document.location.href="index.php?module=ChecklistItems&parent=Settings&view=Settings&mode=step3";
                                }
                            }
                        }else{
                            app.helper.hideProgress();;
                        }
                    }
                );
            }
        });
    },

    registerValidEvent: function () {
        jQuery(".installationContents").find('[name="btnFinish"]').click(function() {
            var params = {};
            params['module'] = app.getModuleName();
            params['action'] = 'Activate';
            params['mode'] = 'valid';
            app.helper.showProgress();
            app.request.post({'data' : params}).then(
                function(err,data){
                    if(err === null) {
                        app.helper.hideProgress();
                        if (data) {
                            document.location.href = "index.php?module=ChecklistItems&parent=Settings&view=Settings";
                        }
                    }else{
                        app.helper.hideProgress();
                    }
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
            var params = app.convertUrlToDataParams(url);
            app.helper.showProgress();
            app.request.post({data:params}).then(function(err,data){
                app.helper.hideProgress();
                if(err === null){
                    app.helper.showModal(data,{cb:function(container){
                        thisInstance.loading = false;
                        jQuery(document).find('.blockOverlay').unbind('click');
                        vtUtils.applyFieldElementsView(container);
                        thisInstance.registerAddChecklistItem(container);
                        thisInstance.registerCheckboxBtn(container);
                        thisInstance.registerRemoveChecklistItem(container);
                        thisInstance.sortableChecklistItems(container);
                        thisInstance.registerCkEditor(container);
                        thisInstance.registerIconPopoverEvent(container);
                        thisInstance.registerSaveBtn(container);
                    }});
                }
            });
			return false;
        });
    },

    registerNoneAdminUserEvent: function () {
        jQuery('#none_user_permission').on('click', function (event) {
            //event.preventDefault();
            var allow = jQuery(this).is(':checked') ? 1 : 0;
            var params = {};
            params['module']='ChecklistItems';
            params['action']='UserPermissions';
            params['permissions']=allow;
            params['parent']='Settings';

            app.helper.showProgress();
            app.request.post({'data' : params}).then(
                function(err,data){
                    if(err === null) {
                        app.helper.hideProgress();
                    }else{
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },

    registerRemoveChecklistItem: function (container) {
        var thisInstance = this;
        jQuery('.deleteButton', container).on('click', function (event) {
            event.preventDefault();
            jQuery(this).closest('tr').remove();
            var itemid = jQuery(this).data('itemid');
            var params = {};
            params['module'] = 'ChecklistItems';
            params['action'] = 'DeleteItem';
            params['parent'] = 'Settings';
            params['itemid'] = itemid;
            app.request.post({'data' : params}).then(
                function(err,data){
                    if(err === null) {

                    }else{

                    }
                }
            );
        });
    },

    registerAddChecklistItem: function (container) {
        var thisInstance = this;
        container.find('#add-checklist-item').on('click', function (event) {
            event.preventDefault();
            var html = jQuery('.items-list-table tr:first', container).clone();
            jQuery('input[type=text], textarea', html).val('');
            jQuery('input[type=hidden], value', html).val('');
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
            jQuery('.items-list-table tbody:first', container).append(html);
            vtUtils.applyFieldElementsView(html);
            thisInstance.registerRemoveChecklistItem(container);
            thisInstance.sortableChecklistItems(container);
            thisInstance.registerCkEditorSingle('desc_' + textarea_id, container);
            thisInstance.registerIconPopoverEvent(html);
        });
    },

    registerDeleteBtn: function () {
        var thisInstance = this;
        jQuery('.deleteButton', '#listViewContents').on('click', function (event) {
            event.preventDefault();
            var url = jQuery(this).data('url');
            var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
            app.helper.showConfirmationBox({'message': message}).then(function (data) {
                    var params = app.convertUrlToDataParams(url);
                    app.helper.showProgress();
                    app.request.post({'data' : params}).then(
                        function(err,data){
                            if(err === null) {
                                thisInstance.loadRecords().then(function () {
                                    app.helper.hideProgress();
                                });
                            }else{
                                app.helper.hideProgress();
                            }
                        }
                    );
                },
                function (error, err) {
                }
            );
        });
    },


    registerCloseBtn: function () {
        jQuery(document).on('click', '#CustomView .ui-checklist-closer', function (event) {
            event.preventDefault();
            app.helper.hideModal();
        });
    },

    registerCheckboxBtn: function (container) {
        jQuery('.allow_note, .allow_upload', container).on('click', function (event) {
            if (jQuery(this).is(':checked')) {
                jQuery(this).parent().find('input[type=hidden]').val(1);
            } else {
                jQuery(this).parent().find('input[type=hidden]').val(0);
            }
        });
    },


    registerSaveBtn: function (container) {
        var thisInstance = this;
        jQuery('#save-checklist', container).on('click', function (event) {
            event.preventDefault();

            var form = container.find('form');
            var textAreaElements = jQuery('.description', form);
            textAreaElements.each(function (index) {
                var element_id = jQuery(this).attr('id');
                var plainText = CKEDITOR.instances[element_id].getData();
                jQuery(this).val(plainText);
            });

            var formData = form.serialize();
            app.helper.showProgress();
            app.request.post({'data' : formData}).then(
                function(err,data){
                    if(err === null) {
                        thisInstance.loadRecords().then(function () {
                            app.helper.hideProgress();
                            app.helper.hideModal();
                        });
                    }else{
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },

    loadRecords: function () {
        var aDeferred = jQuery.Deferred();
        var thisInstance = this;
        var params = {};
        params['module'] = 'ChecklistItems';
        params['view'] = 'Settings';
        params['parent'] = 'Settings';
        params['ajax'] = true;
        app.helper.showProgress();
        app.request.post({'data' : params}).then(
            function(err,data){
                if(err === null) {
                    jQuery('.vte-checklist-items tbody').html(data);
                    thisInstance.registerEditBtn();
                    thisInstance.registerDeleteBtn();
                    app.helper.hideProgress();
                }else{
                    app.helper.hideProgress();
                    aDeferred.reject(error);
                }
                aDeferred.resolve(data);
            }
        );
        return aDeferred.promise();
    },

    sortableRecords: function () {
        var thisInstance = this;
        var container = jQuery(".vte-checklist-items tbody");
        container.sortable({
            handle: ".change-ordering",
            cursor: "move",
            update: function (event, ui) {
                var records = [];
                jQuery(this).find('.change-ordering').each(function (index, el) {
                    records.push(jQuery(el).data('record'));
                });
                //update priority
                var params = {};
                params['module'] = 'ChecklistItems';
                params['action'] = 'SortOrder';
                params['parent'] = 'Settings';
                params['records'] = records;

                app.helper.showProgress();
                app.request.post({'data' : params}).then(
                    function(err,data){
                        if(err === null) {
                            app.helper.hideProgress();
                        }else{
                            app.helper.hideProgress();
                        }
                    }
                );
            }
        });
        container.disableSelection();
    },

    sortableChecklistItems: function (modal_container) {
        var thisInstance = this;
        var container = modal_container.find(".items-list-table tbody");
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
            var params = {};
            params['module'] = 'ChecklistItems';
            params['action'] = 'ChangeStatus';
            params['parent'] = 'Settings';
            params['record'] = jQuery(this).data('record');
            params['status'] = (jQuery(this).data('status') == 'Active') ? 'Inactive' : 'Active';

            app.helper.showProgress();
            app.request.post({'data' : params}).then(
                function(err,data){
                    if(err === null) {
                        thisInstance.loadRecords().then(function () {
                            app.helper.hideProgress();
                        });
                    }else{
                        app.helper.hideProgress();
                    }
                }
            );
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

    registerCkEditor: function (container) {
        var textarea_id = 0;
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

    registerCkEditorSingle: function (id, container) {
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

    registerIconPopoverEvent: function (container) {
        container.find('.icon-info').each(function() {
            var element = $(this);
            var params = {
                'title': element.attr('title'),
                'content': '',
                'trigger': 'hover',
                'closeable': true,
                'placement': 'top',
                'animation': 'fade',
                'type': 'async'
            };
            $(element).webuiPopover(params);

        });
    },

    registerEvents: function () {
        this.registerEditBtn();
        this.registerNoneAdminUserEvent();
        this.registerDeleteBtn();
        this.registerCloseBtn();
        this.sortableRecords();
        this.registerActiveBtn();
        this.unInstall();
        /* For License page - Begin */
        this.init();
        this.registerActivateLicenseEvent();
        this.registerValidEvent();
        /* For License page - End */
        var instance = new Vtiger_Index_Js();
        instance.registerAppTriggerEvent();
    }

};
jQuery(document).ready(function () {
    Settings_ChecklistItems_Js.registerEvents();
});