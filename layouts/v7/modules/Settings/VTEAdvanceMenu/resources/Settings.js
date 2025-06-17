/* * *******************************************************************************
* The content of this file is subject to the VTE Advance Menu ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */
jQuery.Class('Settings_VTEAdvanceMenu_Js', {

}, {
    container: null,

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
                var errorMsg = "License Key cannot be empty";
                //license_key.validationEngine('showPrompt', errorMsg , 'error','bottomLeft',true);
                app.helper.showAlertNotification({
                    'message' : app.vtranslate(errorMsg)
                });
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
                params['parent'] = 'Settings';
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
                                app.helper.showAlertNotification({
                                    'message' : app.vtranslate(message)
                                });
                            }else{
                                document.location.href="index.php?module=VTEAdvanceMenu&parent=Settings&view=Settings&mode=step3";
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
            params['parent'] = 'Settings';
            params['mode'] = 'valid';

            AppConnector.request(params).then(
                function (data) {
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});
                    if (data.success) {
                        document.location.href = "index.php?module=VTEAdvanceMenu&parent=Settings&view=Settings";
                    }
                },
                function (error) {
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});
                }
            );
        });
    },
    /* For License page - End */

    setContainer: function(){
        this.container = $('.vte-advance-menu-container');
    },

    autoHeightBlock: function(){
        var essentials_block = this.container.find('.essentials-container');
        var other_block = this.container.find('.other-container');
        var highestCol = Math.max(essentials_block.outerHeight(),other_block.outerHeight());
        essentials_block.height(highestCol);
        other_block.height(highestCol);
        //console.log(highestCol);
    },

    registerSortModule : function() {
        var sortableElement = this.container;
        var thisInstance = this;
        var stopSorting = false;
        var move = false;
        sortableElement.sortable({
            items: '.vte-menu-item-sort',
            revert: true,
            receive: function (event, ui) {
                move = true;
                if (jQuery(ui.item).hasClass("noConnect")) {
                    stopSorting = true;
                    jQuery(ui.sender).sortable("cancel");
                }
            },
            over : function(event, ui){
                stopSorting = false;
            },
            stop: function(e, ui) {
                var element = jQuery(ui.item);
                var parent = element.closest('.row');
            },
            update: function (event, ui) {
                var element = jQuery(ui.item);
                var parent = element.closest('.row');
                var appname = parent.data('appname');

                parent.find('.VTEAdvanceMenuRemoveItem').attr('data-appname', appname);

                //set height of block
                thisInstance.autoHeightBlock();

                var group_menu = element.closest('.group-menu');
                thisInstance.updateMenuGroupItems(group_menu);
            },
        });
        sortableElement.disableSelection();
    },

    updateMenuGroupItems: function (group_menu) {
        if(typeof group_menu == 'undefined'){
            return;
        }

        var menu_id = group_menu.data('menuid');
        var group_id = group_menu.data('groupid');
        var menu_item_ids = [];
        group_menu.find('.vte-menu-item').each(function(){
           var item_id = $(this).data('itemid');
           if(item_id){
               menu_item_ids.push(item_id);
           }
        });

        if(menu_item_ids.length>0){
            var params = {
                module: app.getModuleName(),
                parent: app.getParentModuleName(),
                action: 'SaveAjax',
                mode: 'UpdateGroupMenu',
                menu_id: menu_id,
                group_id: group_id,
                item_ids: menu_item_ids
            }
            app.helper.showProgress();
            app.request.post({data: params}).then(function (err, data) {
                var cacheKey = 'vte-advance-menu-nav';
                app.storage.delete(cacheKey);
                app.helper.hideProgress();
            });
        }
    },

    registerAddMenu : function() {
        var thisInstance = this;
        this.container.on('click', '.add-button-container a', function(e) {
            var element = jQuery(e.currentTarget);
            var parent = element.closest('.add-button-container');
            var menu_id = parent.data('menuid');
            var group_id = parent.data('groupid');
            var mode = element.data('mode');
            if(mode=='showAddSeparator'){
                thisInstance.registerAddSeparatorSaveEvents(menu_id, group_id);
            }else {
                var params = {
                    module: app.getModuleName(),
                    parent: app.getParentModuleName(),
                    view: 'MenuAjax',
                    mode: mode,
                    menu_id: menu_id,
                    group_id: group_id
                }
                app.helper.showProgress();
                app.request.get({data: params}).then(function (err, data) {
                    app.helper.hideProgress();
                    app.helper.showModal(data, {
                        cb: function (data) {
                            if (mode == 'showAddModule') {
                                thisInstance.registerAddModulePreSaveEvents(data);
                            } else if (mode == 'showAddLink') {
                                thisInstance.registerAddLinkPreSaveEvents(data);
                            } else if (mode == 'showAddFilter') {
                                thisInstance.registerAddFilterPreSaveEvents(data);
                            }
                        }
                    });
                });
            }
        });
    },

    registerAddModulePreSaveEvents : function(data) {
        var self = this;
        var container = data.find('.addModuleContainer');
        self.setSaveButtonState(container);

        container.on('click', '.addModule', function(e){
            var element = jQuery(e.currentTarget);
            element.toggleClass('selectedModule');
            self.setSaveButtonState(container);
        });

        container.find('[type="submit"]').on('click', function(e) {
            var modulesContainer = container.find('.modulesContainer').not('.hide');
            var modules = modulesContainer.find('.addModule');
            var selectedModules = modules.filter('.selectedModule');
            if(!selectedModules.length) {
                app.helper.showAlertNotification({
                    'message' : app.vtranslate('JS_PLEASE_SELECT_A_MODULE')
                });
            } else {
                jQuery(this).attr('disabled','disabled');
                var menu_id = container.find('input[name=menu_id]').val();
                var group_id = container.find('input[name=group_id]').val();
                var sourceModules = [];
                selectedModules.each(function(i, element) {
                    var selectedModule = jQuery(element);
                    sourceModules.push(selectedModule.data('module'));
                });

                if(sourceModules.length) {
                    var params = {
                        module: app.getModuleName(),
                        parent: app.getParentModuleName(),
                        sourceModules: sourceModules,
                        menu_id: menu_id,
                        group_id: group_id,
                        action: 'SaveAjax',
                        mode: 'addModule'
                    };
                    app.helper.showProgress();
                    app.request.post({data: params}).then(function(err, data) {
                        var cacheKey = 'vte-advance-menu-nav';
                        app.storage.delete(cacheKey);
                        app.helper.showSuccessNotification({message: app.vtranslate('JS_MENU_ADD_SUCCESS')});
                        app.helper.hideProgress();
                        window.location.reload();
                    });

                    app.helper.hideModal();
                }
            }
        });
    },

    registerAddLinkPreSaveEvents : function(data) {
        var self = this;
        var container = data.find('.addLinkContainer');

        container.find('[type="submit"]').on('click', function(e) {
            var label = container.find('input[name=label]').val();
            var menu_url = container.find('input[name=menu_url]').val();
            var menu_id = container.find('input[name=menu_id]').val();
            var group_id = container.find('input[name=group_id]').val();

            if(!label) {
                app.helper.showAlertNotification({
                    'message' : app.vtranslate('JS_PLEASE_INSERT_MENU_NAME')
                });
            } else if(!menu_url){
                app.helper.showAlertNotification({
                    'message' : app.vtranslate('JS_PLEASE_INSERT_MENU_URL')
                });
            }else {
                $(this).attr('disabled','disabled');
                var params = {
                    module: app.getModuleName(),
                    parent: app.getParentModuleName(),
                    menu_id: menu_id,
                    group_id: group_id,
                    menu_label: label,
                    menu_link: menu_url,
                    action: 'SaveAjax',
                    mode: 'addLink'
                };
                app.helper.showProgress();
                app.request.post({data: params}).then(function(err, data) {
                    var cacheKey = 'vte-advance-menu-nav';
                    app.storage.delete(cacheKey);
                    app.helper.showSuccessNotification({message: app.vtranslate('JS_MENU_ADD_SUCCESS')});
                    app.helper.hideProgress();
                    window.location.reload();
                });

                app.helper.hideModal();
            }
        });
    },

    registerAddFilterPreSaveEvents : function(data) {
        var self = this;
        var container = data.find('.addFilterContainer');

        container.find('select[name=source_module]').on('change', function(e) {
            var source_module = $(this).val();
            var options = '<option value="">'+app.vtranslate('JS_SELECT_AN_OPTION')+'</option>';
            if(source_module != ''){
                var params = {
                    module: app.getModuleName(),
                    parent: app.getParentModuleName(),
                    source_module: source_module,
                    view: 'MenuAjax',
                    mode: 'getModuleFilter'
                };
                app.helper.showProgress();
                app.request.post({data: params}).then(function(err, data) {
                    app.helper.hideProgress();
                    if(err === null){
                        var filters = data;
                        var len = filters.length;
                        if(len>0){
                            for(var i = 0; i < len; i++){
                                var filter = filters[i];
                                options += '<option value="'+filter.cvid+'">'+filter.viewname+'</option>';
                            }
                        }
                    }
                    //console.log(options);
                    container.find('select[name=filter]').html(options).select2('destroy').val("").select2();;
                });
            }else {
                container.find('select[name=filter]').html(options).select2('destroy').val("").select2();;
            }
        });


        container.find('[type="submit"]').on('click', function(e) {
            var menu_id = container.find('input[name=menu_id]').val();
            var group_id = container.find('input[name=group_id]').val();
            var source_module = container.find('select[name=source_module]').val();
            var filter = container.find('select[name=filter]').val();
            if(!source_module) {
                app.helper.showAlertNotification({
                    'message' : app.vtranslate('JS_PLEASE_SELECT_MODULE')
                });
            } else if(!filter){
                app.helper.showAlertNotification({
                    'message' : app.vtranslate('JS_PLEASE_SELECT_FILTER')
                });
            } else {
                jQuery(this).attr('disabled','disabled');
                var params = {
                    module: app.getModuleName(),
                    parent: app.getParentModuleName(),
                    source_module: source_module,
                    filter: filter,
                    menu_id: menu_id,
                    group_id: group_id,
                    action: 'SaveAjax',
                    mode: 'addFilter'
                };
                app.helper.showProgress();
                app.request.post({data: params}).then(function(err, data) {
                    var cacheKey = 'vte-advance-menu-nav';
                    app.storage.delete(cacheKey);
                    app.helper.showSuccessNotification({message: app.vtranslate('JS_MENU_ADD_SUCCESS')});
                    app.helper.hideProgress();
                    window.location.reload();
                });

                app.helper.hideModal();
            }
        });
    },

    registerAddSeparatorSaveEvents : function(menu_id, group_id) {
        var params = {
            module: app.getModuleName(),
            parent: app.getParentModuleName(),
            menu_id: menu_id,
            group_id: group_id,
            action: 'SaveAjax',
            mode: 'addSeparator'
        };
        app.helper.showProgress();
        app.request.post({data: params}).then(function(err, data) {
            var cacheKey = 'vte-advance-menu-nav';
            app.storage.delete(cacheKey);
            app.helper.showSuccessNotification({message: app.vtranslate('JS_MENU_ADD_SUCCESS')});
            app.helper.hideProgress();
            window.location.reload();
        });
    },

    registerRemoveMenu : function() {
        this.container.find('.VTEAdvanceMenuRemoveItem').on('click', function(e) {
            var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
            var item_id = $(this).closest('.vte-menu-item').data('itemid');
            app.helper.showConfirmationBox({'message': message}).then(function (e) {
                var params = {
                    module: app.getModuleName(),
                    parent: app.getParentModuleName(),
                    item_id: item_id,
                    action: 'SaveAjax',
                    mode: 'removeMenuItem'
                };
                app.helper.showProgress();
                app.request.post({data: params}).then(function(err, data) {
                    var cacheKey = 'vte-advance-menu-nav';
                    app.storage.delete(cacheKey);
                    app.helper.showSuccessNotification({message: app.vtranslate('JS_MENU_ITEM_DELETE_SUCCESS')});
                    app.helper.hideProgress();
                    window.location.reload();
                });
            });
        });
    },

    setSaveButtonState : function(container) {
        if(!container.find('.modulesContainer .selectedModule').length) {
            container.find('[type="submit"]').attr('disabled','disabled');
        } else {
            container.find('[type="submit"]').removeAttr('disabled');
        }
    },

    registerEditGroup: function(){
        var thisInstance = this;
        this.container.find('.group-header .fa-pencil').on('click', function(e) {
            var group_id = $(this).data('groupid');
            var params = {
                module: app.getModuleName(),
                parent: app.getParentModuleName(),
                view: 'MenuAjax',
                mode: 'showEditGroup',
                group_id: group_id
            }
            app.helper.showProgress();
            app.request.post({data: params}).then(function (err, data) {
                app.helper.hideProgress();
                app.helper.showModal(data, {
                    cb: function (data) {
                        thisInstance.registerEditGroupPreSaveEvents(data);
                    }
                });
            });
        });
    },

    registerEditGroupPreSaveEvents: function (data) {
        var container = data.find('.editGroupContainer');
        container.find("[rel='tooltip']").tooltip({placement: 'right', 'container': '.editGroupContainer'});

        //icon preview
        container.find('input[name=icon_class]').keyup(function(e) {
            container.find('#icon-preview i').removeAttr('class').attr('class', $(this).val());
        });

        container.find('[type="submit"]').on('click', function(e) {
            var group_id = container.find('input[name=group_id]').val();
            var group_label = container.find('input[name=label]').val();
            var icon_class = container.find('input[name=icon_class]').val();
            jQuery(this).attr('disabled','disabled');
            var params = {
                module: app.getModuleName(),
                parent: app.getParentModuleName(),
                icon_class: icon_class,
                label: group_label,
                group_id: group_id,
                action: 'SaveAjax',
                mode: 'saveGroupDetail'
            };
            app.helper.showProgress();
            app.request.post({data: params}).then(function(err, data) {
                var cacheKey = 'vte-advance-menu-nav';
                app.storage.delete(cacheKey);
                app.helper.showSuccessNotification({message: app.vtranslate('JS_UPDATE_GROUP_SUCCESS')});
                app.helper.hideProgress();
                window.location.reload();
            });

            app.helper.hideModal();
        });
    },

    registerEvents : function() {
        this.registerActivateLicenseEvent();
        this.registerValidEvent();
        this.setContainer();
        this.autoHeightBlock();
        this.registerSortModule();
        this.registerAddMenu();
        this.registerRemoveMenu();
        this.registerEditGroup();
        var instance = new Vtiger_Index_Js();
        instance.registerAppTriggerEvent();
    }
});

window.onload = function() {
    var settingVTEAdvanceMenuInstance = new Settings_VTEAdvanceMenu_Js();
    settingVTEAdvanceMenuInstance.registerEvents();
};