/* * *******************************************************************************
* The content of this file is subject to the VTE Advance Menu ("License");
* You may not use this file except in compliance with the License
* The Initial Developer of the Original Code is VTExperts.com
* Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
* All Rights Reserved.
* ****************************************************************************** */
jQuery.Class('Vtiger_VTEAdvanceMenu_Js', {}, {
    set_balance_height_block: false,

    initialize: function(){
        var thisInstance = this;
        var container = $('.global-nav .app-navigator-container>.row');
        var cacheKey = 'vte-advance-menu-nav';
        var nsCacheKey = app.storage.NSKey(cacheKey);
        if(container.find('.vte-advance-menu-nav').length==0){
            var menuElementData = app.storage.get(cacheKey);
            var menuElement = $(menuElementData);
            if(menuElementData != null && menuElementData !=''){
                if(menuElement.hasClass('vte-advance-menu-nav')) {
                    container.find('.logo-container').removeClass('col-lg-9 col-md-9 col-sm-9 col-xs-9');
                    container.append(menuElementData);
                    thisInstance.removeDupeSeparator(container);
                    thisInstance.disableHidMenuWhenClickInSide(container);
                    container.find('.vte-advance-menu-nav').find("[rel='tooltip']").tooltip({placement: 'top', 'container': '.vte-advance-menu-nav'});
                    thisInstance.autoHeightBlock(container);
                    thisInstance.addNewModulesPopup(container);
                    thisInstance.fadeInMenuButton(container);
                }
            }else{
                var aDeferred = jQuery.Deferred();
                var params = {
                    module: 'VTEAdvanceMenu',
                    view: 'GetMenu'
                };
                app.request.post({data: params}).then(function(err, data) {
                    app.storage.set(cacheKey, data);
                    jQuery.jStorage.setTTL(nsCacheKey, 3*24*60*60*1000); // expires in 3 days
                    var menuElement = $(data);
                    if(menuElement.hasClass('vte-advance-menu-nav')) {
                        container.find('.logo-container').removeClass('col-lg-9 col-md-9 col-sm-9 col-xs-9');
                        container.append(data);
                        thisInstance.removeDupeSeparator(container);
                        thisInstance.disableHidMenuWhenClickInSide(container);
                        container.find('.vte-advance-menu-nav').find("[rel='tooltip']").tooltip({placement: 'top', 'container': '.vte-advance-menu-nav'});
                        thisInstance.autoHeightBlock(container);
                        thisInstance.addNewModulesPopup(container);
                        thisInstance.fadeInMenuButton(container);
                    }
                });
                return aDeferred.promise();
            }
        }
    },

    fadeInMenuButton: function (container) {
        $('.vte-advance-menu-nav-btn', container).fadeIn(1000);
    },

    addNewModulesPopup: function (container) {
        var thisInstance = this;
        if(container.find('.footer-middle .add-new-module-to-menu')){
            container.find('.footer-middle .add-new-module-to-menu').unbind('click').on('click', function () {
                var params = {
                    module: 'VTEAdvanceMenu',
                    view: 'NewModulesForm',
                    menu_id: container.find('#vte-advance-menu-id').val(),
                }
                app.helper.showProgress();
                app.request.post({data: params}).then(function (err, data) {
                    app.helper.hideProgress();
                    app.helper.showModal(data, {
                        cb: function (data) {
                            thisInstance.registerAddNewModules(data);
                        }
                    });
                });
            })
        }
    },

    registerAddNewModules: function (data) {
        var menu_id = data.find('#vte-advance-menu-id-add-new-modules').val();
        data.find('.modal-footer button').on('click', function () {
            var mode = $(this).data('mode');
            var params = {
                module: 'VTEAdvanceMenu',
                action: 'MenuAjax',
                mode: mode,
                menu_id: menu_id,
            }
            app.helper.showProgress();
            app.request.post({data: params}).then(function (err, data) {
                if(err==null) {
                    var cacheKey = 'vte-advance-menu-nav';
                    app.storage.delete(cacheKey);
                    window.location.reload();
                }
            });
        });
    },

    autoHeightBlock: function (container) {
        var thisInstance = this;
        container.find('.vte-advance-menu-nav').on('shown.bs.dropdown', function () {
            if(thisInstance.set_balance_height_block === false) {
                var essentials_block = container.find('.essentials-container');
                var other_block = container.find('.other-container');
                var essentials_block_height = essentials_block.height();
                var other_block_height = other_block.height();
                if(other_block_height<essentials_block_height){
                    other_block_height = essentials_block_height;
                }
                other_block.height(other_block_height);
                thisInstance.set_balance_height_block = true;
            }
        });
    },

    disableHidMenuWhenClickInSide: function (container) {
        container.on('click', '.dropdown-menu', function (e) {
            e.stopPropagation();
        });
    },

    removeDupeSeparator: function (container) {
        if(container.find('.vte-advance-menu-nav .essentials-container').length>0){
            if(container.find('.vte-advance-menu-nav .essentials-container .group-menu .vte-menu-item').length>0){
                var menu_items = container.find('.vte-advance-menu-nav .essentials-container .group-menu .vte-menu-item');
                for(var i=1; i<menu_items.length; i++){
                    var pre_menu_item = $(menu_items[i-1]);
                    var current_menu_item = $(menu_items[i]);
                    if(current_menu_item.hasClass('divider') && pre_menu_item.hasClass('divider')){
                        current_menu_item.remove();
                    }
                }
            }
        }
        if(container.find('.vte-advance-menu-nav .other-container').length>0){
            if(container.find('.vte-advance-menu-nav .other-container .marketing-container .group-menu .vte-menu-item').length>0){
                var menu_items = container.find('.vte-advance-menu-nav .other-container .marketing-container .group-menu .vte-menu-item');
                for(var i=2; i<menu_items.length; i++){
                    var pre_menu_item = $(menu_items[i-2]);
                    var current_menu_item = $(menu_items[i]);
                    if(current_menu_item.data('type')=='separator' && pre_menu_item.data('type')=='separator'){
                        current_menu_item.remove();
                    }
                }
            }
            if(container.find('.vte-advance-menu-nav .other-container .sales-container .group-menu .vte-menu-item').length>0){
                var menu_items = container.find('.vte-advance-menu-nav .other-container .sales-container .group-menu .vte-menu-item');
                for(var i=2; i<menu_items.length; i++){
                    var pre_menu_item = $(menu_items[i-2]);
                    var current_menu_item = $(menu_items[i]);
                    if(current_menu_item.data('type')=='separator' && pre_menu_item.data('type')=='separator'){
                        current_menu_item.remove();
                    }
                }
            }
            if(container.find('.vte-advance-menu-nav .other-container .support-container .group-menu .vte-menu-item').length>0){
                var menu_items = container.find('.vte-advance-menu-nav .other-container .support-container .group-menu .vte-menu-item');
                for(var i=2; i<menu_items.length; i++){
                    var pre_menu_item = $(menu_items[i-2]);
                    var current_menu_item = $(menu_items[i]);
                    if(current_menu_item.data('type')=='separator' && pre_menu_item.data('type')=='separator'){
                        current_menu_item.remove();
                    }
                }
            }
            if(container.find('.vte-advance-menu-nav .other-container .inventory-container .group-menu .vte-menu-item').length>0){
                var menu_items = container.find('.vte-advance-menu-nav .other-container .inventory-container .group-menu .vte-menu-item');
                for(var i=2; i<menu_items.length; i++){
                    var pre_menu_item = $(menu_items[i-2]);
                    var current_menu_item = $(menu_items[i]);
                    if(current_menu_item.data('type')=='separator' && pre_menu_item.data('type')=='separator'){
                        current_menu_item.remove();
                    }
                }
            }
            if(container.find('.vte-advance-menu-nav .other-container .projects-container .group-menu .vte-menu-item').length>0){
                var menu_items = container.find('.vte-advance-menu-nav .other-container .projects-container .group-menu .vte-menu-item');
                for(var i=2; i<menu_items.length; i++){
                    var pre_menu_item = $(menu_items[i-2]);
                    var current_menu_item = $(menu_items[i]);
                    if(current_menu_item.data('type')=='separator' && pre_menu_item.data('type')=='separator'){
                        current_menu_item.remove();
                    }
                }
            }
            if(container.find('.vte-advance-menu-nav .other-container .tools-container .group-menu .vte-menu-item').length>0){
                var menu_items = container.find('.vte-advance-menu-nav .other-container .tools-container .group-menu .vte-menu-item');
                for(var i=2; i<menu_items.length; i++){
                    var pre_menu_item = $(menu_items[i-2]);
                    var current_menu_item = $(menu_items[i]);
                    if(current_menu_item.data('type')=='separator' && pre_menu_item.data('type')=='separator'){
                        current_menu_item.remove();
                    }
                }
            }
        }
    }

});

$(document).ready(function () {
    var vteAdvancMenuInstance = new Vtiger_VTEAdvanceMenu_Js();
    vteAdvancMenuInstance.initialize();
});