/* ********************************************************************************
 * The content of this file is subject to the Custom Header/Bills ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

Vtiger.Class("VTEButtons_Js", {
    instance: false,
    getInstance: function () {
        if (VTEButtons_Js.instance == false) {
            var instance = new VTEButtons_Js();
            VTEButtons_Js.instance = instance;
            return instance;
        }
        return VTEButtons_Js.instance;
    }
},{
    getVtigerVersion:function(){
        var version = '';
        var scripts = document.getElementsByTagName("script")
        for (var i = 0; i < scripts.length; ++i) {
            var src = scripts[i].src;
            if(src.indexOf('.js?v=')>-1){
                var versionTmp = src.split('js?v=');
                version = versionTmp[1];
                break;
            }else if(src.indexOf('.js?&v=')>-1){
                var versionTmp = src.split('.js?&v=');
                version = versionTmp[1];
                break;
            }
        }
        return version;
    },
    registerShowOnDetailView:function(){
        var self = this;
        var params = {};
        params['module'] = 'VTEButtons';
        params['view'] = 'HeaderIcon';
        params['record'] = app.getRecordId();
        params['moduleSelected'] = app.getModuleName();
        app.request.post({data:params}).then(
            function(err,data) {
                if(err == null){
                    var detailview_header = jQuery('.detailview-header .row:first');
                    $("#div_vtebuttons").remove();
                    detailview_header.after(data);
                    $("#div_vtebuttons").fadeIn(700);
                    self.registerEventsQuickUpdate();
                    var custom_header = $("#div_custome_header");
                    if(custom_header.length>0) {
                        $("#div_custome_header").insertAfter('#div_vtebuttons');
                        var detailview_header_w = detailview_header.width();
                        var offset = $("#div_vtebuttons").offset();
                        var offset1 = custom_header.offset();
                        if (offset.left != offset1.left) {
                            var left = detailview_header_w * 0.22 + offset.left - 45;
                            //$("#div_custome_header").css({'left': left + 'px'});
                        }
                    }
                    var height = $('.detailViewButtoncontainer').height();
                    //#5363930 begin
                    var ver = self.getVtigerVersion();
                    ver = parseInt(ver.substring(0,3).replace('.',''));
                    if(ver < 73){
                        if(height > 30){
                            $('#div_vtebuttons').css({'margin-left': '1px'});
                        }
                    }else{
                        if(height > 64){
                            $('#div_vtebuttons').css({'margin-left': '1px'});
                        }
                    }
                    //#5363930 end
                }
            },
            function(error) {
            }
        );

    },
    registerShowVTEButtons:function(){
        var self = this;
        var params = {};
        params['module'] = 'VTEButtons';
        params['view'] = 'HeaderIcon';
        params['record'] = app.getRecordId();
        params['moduleSelected'] = app.getModuleName();
        app.request.post({data:params}).then(
            function(err,data) {
                if(err == null){
                    var detailview_header = jQuery('.detailview-header .row:first');
                    detailview_header.after(data);
                    $("#div_vtebuttons").fadeIn(700);
                    var quickPreviewHeader = jQuery('.quickPreviewActions');
                    if(typeof quickPreviewHeader !='undefined') {
                        $(".quick-preview-modal #div_vtebuttons").remove();
                        quickPreviewHeader.append(data);
                        $(".quick-preview-modal #div_vtebuttons").css({'display': 'block'});
                    }
                    var custom_header = $("#div_custome_header");
                    if(custom_header.length>0) {
                        $("#div_custome_header").insertAfter('#div_vtebuttons');
                        var detailview_header_w = detailview_header.width();
                        var offset = $("#div_vtebuttons").offset();
                        var offset1 = custom_header.offset();
                        if (offset.left != offset1.left) {
                            var left = detailview_header_w * 0.22 + offset.left - 45;
                           //$("#div_custome_header").css({'left': left + 'px'});
                        }
                    }
                    var height = $('.detailViewButtoncontainer').height();
                    //#5363930 begin
                    var ver = self.getVtigerVersion();
                    ver = parseInt(ver.substring(0,3).replace('.',''));
                    if(ver < 73){
                        if(height > 30){
                            $('#div_vtebuttons').css({'margin-left': '1px'});
                        }
                    }else{
                        if(height > 64){
                            $('#div_vtebuttons').css({'margin-left': '1px'});
                        }
                    }
                    //#5363930 end
                }
            },
            function(error) {
            }
        );

    },
    registerEventsQuickUpdate:function(){
        var thisInstance = this;
        var postVTEButtonsSave  = function(data) {
            var viewHeight = jQuery('.detailview-header').height();
            jQuery('.detailview-header').css({'height':viewHeight+'px'})
            jQuery('#div_vtebuttons').remove();
            thisInstance.registerShowVTEButtons();
        }
        jQuery('body').off('click','.vteButtonQuickUpdate').on('click','.vteButtonQuickUpdate',function (e) {
            var target = jQuery(e.currentTarget);
            var source_module = app.getModuleName();
            var vtebuttonid = $(this).data('vtebuttonid');
            var params = {};
            params['module'] = 'VTEButtons';
            params['action'] = 'ActionAjax';
            params['mode'] = 'get_fields_update';
            params['source_module'] = source_module;
            params['vtebuttonid'] = vtebuttonid;
            app.request.post({data:params}).then(
                function(err,data) {
                    if(err == null){
                        if(data.automated_update_field == '' && data.field_name == ''){
                            app.helper.showAlertNotification({message:'You must select at least ONE field in "Fields on Popup" OR "Silent Field Update". You are NOT required to select both, only one of the two options is required. You can select both too.'});
                        }else if(data.automated_update_field != '' && data.field_name == ''){
                            var record = app.getRecordId();
                            var params = {};
                            params['module'] = 'VTEButtons';
                            params['action'] = 'ActionAjax';
                            params['mode'] = 'autoUpdate';
                            params['vtebuttons_id'] = vtebuttonid;
                            params['record'] = record;
                            params['source_module'] = source_module;
                            app.request.post({data:params}).then(function(err,data) {
                                if(err == null){
                                    app.helper.showSuccessNotification({message:data.label+' updated to '+data.value+'.'});
                                    $('li.active').trigger('click');
                                    thisInstance.registerShowOnDetailView();
                                }
                            });
                        }else{
                            var vteButtonId = vtebuttonid;
                            var viewEditUrl = "module=VTEButtons&view=QuickEditAjax&record="+app.getRecordId()+"&moduleEditName="+app.getModuleName()+"&vteButtonId="+vteButtonId;
                            var params= {'callbackFunction':postVTEButtonsSave,'noCache':true};
                            thisInstance.getVTEButtonsQuickEditForm(viewEditUrl, app.getModuleName(), params).then(function(data) {
                                thisInstance.handleVTEButtonsQuickEditData(data, params);
                                var form = jQuery("#vteButtonQuickEdit");
                                var Edit_Js = new Vtiger_Edit_Js();
                                Edit_Js.registerEventForPicklistDependencySetup(form);
                                Edit_Js.registerFileElementChangeEvent(form);
                                Edit_Js.registerAutoCompleteFields(form);
                                Edit_Js.registerClearReferenceSelectionEvent(form);
                                Edit_Js.referenceModulePopupRegisterEvent(form);
                                Edit_Js.registerPostReferenceEvent(Edit_Js.getEditViewContainer());
                                Edit_Js.registerEventForImageDelete();
                                Edit_Js.registerImageChangeEvent();
                                vtUtils.applyFieldElementsView(form);
                                app.helper.hideProgress();
                            });
                        }
                    }
                },
                function(error) {
                }
            );
        });
    },
    getVTEButtonsQuickEditForm: function(url, moduleName, params) {
        var thisInstance = this;
        var aDeferred = jQuery.Deferred();
        var requestParams;
        if (typeof params == 'undefined') {
            params = {};
        }
        if ((!params.noCache) || (typeof (params.noCache) == "undefined")) {
            if (typeof app.helper.quickCreateModuleCache['edit_'+moduleName] != 'undefined') {
                aDeferred.resolve(app.helper.quickCreateModuleCache['edit_'+moduleName]);
                return aDeferred.promise();
            }
        }
        requestParams = url;
        if (typeof params.data != "undefined") {
            var requestParams = {};
            requestParams['data'] = params.data;
            requestParams['url'] = url;
        }
        app.request.post({'data':requestParams}).then(
            function(err,data){
                if(err === null) {
                    if ((!params.noCache) || (typeof (params.noCache) == "undefined")) {
                        app.helper.quickCreateModuleCache['edit_'+moduleName] = data;
                    }
                    aDeferred.resolve(data);
                }else{
                }
            }
        );

        return aDeferred.promise();
    },
    handleVTEButtonsQuickEditData: function(data, params) {
        if (typeof params == 'undefined') {
            params = {};
        }
        var thisInstance = this;
        app.helper.showModal(data,{'cb' : function (data){
            var quickEditForm = data.find('form[name="vteButtonQuickEdit"]');
            app.event.trigger('post.vteButtonQuickEditForm.show',quickEditForm);
            var moduleName = quickEditForm.find('[name="module"]').val();
            var editViewInstance = Vtiger_Edit_Js.getInstanceByModuleName(moduleName);
            editViewInstance.registerBasicEvents(quickEditForm);
            quickEditForm.vtValidate(app.validationEngineOptions);

            if (typeof params.callbackPostShown != "undefined") {
                params.callbackPostShown(quickEditForm);
            }
            thisInstance.registerVTEButtonsPostLoadEvents(quickEditForm, params);
            var quickCreateContent = quickEditForm.find('.quickCreateContent');
            var quickCreateContentHeight = quickCreateContent.height();
            var contentHeight = parseInt(quickCreateContentHeight);
            if (contentHeight > 300) {
                app.helper.showVerticalScroll(quickCreateContent, {setHeight: '300px'});
            }
        }});
    },
    registerVTEButtonsPostLoadEvents: function(form, params) {
        var thisInstance = this;
        var submitSuccessCallbackFunction = params.callbackFunction;
        var goToFullFormCallBack = params.goToFullFormcallback;
        if (typeof submitSuccessCallbackFunction == 'undefined') {
            submitSuccessCallbackFunction = function() {
            };
        }
        form.find("button[name='vteButtonsSave']").on('click', function(e) {
            var form = jQuery(e.currentTarget).closest('form');
            var module = form.find('[name="module"]').val();
            var aDeferred = jQuery.Deferred();
            var params = {
                submitHandler: function (frm) {
                    jQuery("button[name='vteButtonsSave']").attr("disabled", "disabled");
                    if (this.numberOfInvalids() > 0) {
                        return false;
                    }
                    /*var e = jQuery.Event(Vtiger_Edit_Js.recordPresaveEvent);
                    app.event.trigger(e);
                    if (e.isDefaultPrevented()) {
                        return false;
                    }*/
                    var formData = jQuery(frm).serialize();
                    app.helper.showProgress();
                    app.request.post({data: formData}).then(function (err, data) {
                        if (!err) {
                            aDeferred.resolve(data);
                            var parentModule=app.getModuleName();
                            var viewname=app.getViewName();
                            if((module == parentModule) && (viewname=="List")){
                                var listinstance = new Vtiger_List_Js();
                                listinstance.getListViewRecords();
                            }
                            submitSuccessCallbackFunction(data);
                        } else {
                            app.helper.showErrorNotification({"message": err});
                        }
                        app.helper.hideModal();
                        app.helper.hideProgress();
                    });
                }
            };
            form.vtValidate(params);
            form.submit();
        });
    },
    registerEvents: function(){
        var self = this;
        self.registerShowOnDetailView();
        jQuery( document ).ajaxComplete(function(event, xhr, settings) {
            if(settings.url != undefined && settings.url.indexOf('module=CustomFormsViews&view=Detail') != -1 && settings.url.indexOf('mode=showDetailViewByMode') != -1){
                $('.vteButtonQuickUpdate').off('click');
                self.registerShowOnDetailView();
            }
        })

    }
});

jQuery(document).ready(function () {
	// Only load when loadHeaderScript=1 BEGIN #241208
	if (typeof VTECheckLoadHeaderScript == 'function') {
		if (!VTECheckLoadHeaderScript('VTEButtons')) {
			return;
		}
	}
	// Only load when loadHeaderScript=1 END #241208
	
    var moduleName = app.getModuleName();
    var viewName = app.getViewName();
    if(viewName == 'Detail'){
        var instance = new VTEButtons_Js();
        instance.registerEvents();
    }
});
var arrUrl = [];
jQuery(document).ajaxComplete(function(event, xhr, settings) {
    var url = settings.data;
    if (typeof url == 'undefined' && settings.url) url = settings.url;
    if (Object.prototype.toString.call(url) == '[object String]') {
        if (url.indexOf('module=VTEButtons') != -1 && url.indexOf('action=ActionAjax') != -1 && url.indexOf('mode=doUpdateFields') > -1) {
            var urlParams = app.convertUrlToDataParams(url);
            var source_module = app.getModuleName();
            var record = app.getRecordId();
            var params = {};
            params['module'] = 'VTEButtons';
            params['action'] = 'ActionAjax';
            params['mode'] = 'autoUpdate';
            params['vtebuttons_id'] = urlParams['vtebuttons_id'];
            params['record'] = record;
            params['source_module'] = urlParams['source_module'];

            app.request.post({
                data: params
            }).then(function(err, data) {
                if (err == null) {
                    if (data.label != '' && data.value != '') {
                        app.helper.showSuccessNotification({
                            message: data.label + ' updated to ' + data.value + '.'
                        });
                        var instance = new VTEButtons_Js();
                        if (source_module !== 'VReports') {
                            instance.registerShowOnDetailView();
                        }
                    }
                }
            });
            if(source_module == 'VReports'){
                var moduleMinilist = urlParams['source_module'];
                var refreshButton = $('.loadcompleted').find('[data-module-minilist="'+moduleMinilist+'"]').find('a[data-event="Refresh"]');
                refreshButton.trigger('click');
            }
            app.helper.showSuccessNotification({
                'message': 'Record Updated!'
            });
            $('li.active').trigger('click');
        }
        if (url.indexOf('module=VTEButtons') != -1 && url.indexOf('view=HeaderIcon') != -1) {
            setTimeout(function() {
                var detailview_header = jQuery('.detailview-header .row:first');
                var custom_header = $("#div_custome_header");
                if (custom_header.length > 0) {
                    $("#div_custome_header").insertAfter('#div_vtebuttons');
                    var detailview_header_w = detailview_header.width();
                    var offset = $("#div_vtebuttons").offset();
                    var offset1 = custom_header.offset();
                    if (offset.left != offset1.left) {
                        var left = detailview_header_w * 0.22 + offset.left - 45;
                        //$("#div_custome_header").css({'left': left + 'px'});
                    }
                }

                var vteProgressBar = $("#vteProgressBarMainContainer");
                if (vteProgressBar.length > 0) {
                    vteProgressBar.insertAfter('#div_vtebuttons');
                }
            }, 1000);
        }
        if (url.indexOf('view=RecordQuickPreview') != -1 || url.indexOf('view=ListViewQuickPreview') != -1) {
            console.log('hien thi nut');
            var thisInstance = this;
            var urlParams = app.convertUrlToDataParams(url);
            var record1 = urlParams.record;
            var moduleSelected = urlParams.module;
            var params6 = {};
            params6['module'] = 'VTEButtons';
            params6['view'] = 'HeaderIcon';
            params6['record'] = record1;
            // params['moduleSelected'] = app.getModuleName();
            params6['moduleSelected'] = moduleSelected;
            app.request.post({
                data: params6
            }).then(
                function(err, data) {
                    if (err == null) {
                        var preview_header = jQuery('.quickPreviewActions');
                        $(".quick-preview-modal #div_vtebuttons").remove();
                        preview_header.append(data);
                        $(".quick-preview-modal #div_vtebuttons").fadeIn(700);

                        var instance = new VTEButtons_Js();
                        var postVTEButtonsSave = function(data) {
                            jQuery('.quick-preview-modal #div_vtebuttons').remove();
                            instance.registerShowVTEButtons();
                        }

                        jQuery('body').off('click','.vteButtonQuickUpdate').on('click','.vteButtonQuickUpdate',function (e) {
                            var target = jQuery(e.currentTarget);
                            var source_module = app.getModuleName();
                            var vtebuttonid = $(this).data('vtebuttonid');
                            var params2 = {};
                            params2['module'] = 'VTEButtons';
                            params2['action'] = 'ActionAjax';
                            params2['mode'] = 'get_fields_update';
                            // params2['source_module'] = source_module;
                            params2['source_module'] = moduleSelected;
                            params2['vtebuttonid'] = vtebuttonid;
                            app.request.post({
                                data: params2
                            }).then(
                                function(err, data) {
                                    if (err == null) {
                                        if (data.automated_update_field == '' && data.field_name == '') {
                                            app.helper.showAlertNotification({
                                                message: 'You must select at least ONE field in "Fields on Popup" OR "Silent Field Update". You are NOT required to select both, only one of the two options is required. You can select both too.'
                                            });
                                        } else if (data.automated_update_field != '' && data.field_name == '') {
                                            var record = app.getRecordId();
                                            var params = {};
                                            params['module'] = 'VTEButtons';
                                            params['action'] = 'ActionAjax';
                                            params['mode'] = 'autoUpdate';
                                            params['vtebuttons_id'] = vtebuttonid;
                                            params['record'] = record1;
                                            // params['source_module'] = source_module;
                                            params['source_module'] = moduleSelected;
                                            app.request.post({
                                                data: params
                                            }).then(function(err, data) {
                                                if (err == null) {
                                                    app.helper.showSuccessNotification({
                                                        message: data.label + ' updated to ' + data.value + '.'
                                                    });
                                                    $('li.active').trigger('click');
                                                    $('.listViewEntries[data-id="' + record1 + '"] .quickView').trigger('click');
                                                }
                                            });
                                        } else {
                                            var vteButtonId = vtebuttonid;
                                            var viewEditUrl = "module=VTEButtons&view=QuickEditAjax&record=" + record1 + "&moduleEditName=" + moduleSelected + "&vteButtonId=" + vteButtonId;
                                            var params = {
                                                'callbackFunction': postVTEButtonsSave,
                                                'noCache': true
                                            };
                                            instance.getVTEButtonsQuickEditForm(viewEditUrl, moduleSelected, params).then(function(data) {
                                                instance.handleVTEButtonsQuickEditData(data, params);
                                                var form = jQuery("#vteButtonQuickEdit");
                                                app.event.trigger('post.vteButtonQuickEditForm.show',form);
                                                var Edit_Js = new Vtiger_Edit_Js();
                                                Edit_Js.registerEventForPicklistDependencySetup(form);
                                                Edit_Js.registerFileElementChangeEvent(form);
                                                Edit_Js.registerAutoCompleteFields(form);
                                                Edit_Js.registerClearReferenceSelectionEvent(form);
                                                Edit_Js.referenceModulePopupRegisterEvent(form);
                                                Edit_Js.registerPostReferenceEvent(Edit_Js.getEditViewContainer());
                                                Edit_Js.registerEventForImageDelete();
                                                Edit_Js.registerImageChangeEvent();
                                                vtUtils.applyFieldElementsView(form);
                                                app.helper.hideProgress();
                                                $('#vteButtonQuickEdit button.btn-success').on('click', function() {
                                                    $('#vteButtonQuickEdit button.btn-success').off('click');
                                                    var params3 = {};
                                                    params3['module'] = 'VTEButtons';
                                                    params3['action'] = 'ActionAjax';
                                                    params3['mode'] = 'autoUpdate';
                                                    params3['vtebuttons_id'] = vtebuttonid;
                                                    params3['record'] = record1;
                                                    params3['source_module'] = moduleSelected;
                                                    app.request.post({
                                                        data: params3
                                                    }).then(function(err, data) {
                                                        if (url.indexOf('view=ListViewQuickPreview') != -1) {
                                                            var element = jQuery(e.currentTarget);
                                                            var appName = element.data('app');
                                                            var self = this;
                                                            var params5 = {};
                                                            params5['module'] = moduleSelected;
                                                            params5['app'] = appName;
                                                            params5['record'] = record1;
                                                            params5['view'] = 'ListViewQuickPreview';
                                                            params5['navigation'] = 'true';
                                                            app.request.post({
                                                                data: params5
                                                            }).then(function(err, response) {});
                                                        }
                                                        app.request.post({
                                                            data: params
                                                        }).then(function(err, data) {
                                                            if (err == null) {
                                                                $('.listViewEntries[data-id="' + record1 + '"] .quickView').trigger('click');
                                                                $('.miniListContent .quickView[data-id="' + record1 + '"]').trigger('click');
                                                            }
                                                        });


                                                    });
                                                });
                                                if(typeof Control_Layout_Fields_Js !='undefined'){
                                                    var clfInstance = new Control_Layout_Fields_Js();
                                                    clfInstance.registerVTEButtonPopupEvents();
                                                }
                                            });
                                        }
                                        if(source_module == 'VReports'){
                                            var moduleMinilist = moduleSelected;
                                            var refreshButton = $('.loadcompleted').find('[data-module-minilist="'+moduleMinilist+'"]').find('a[data-event="Refresh"]');
                                            refreshButton.trigger('click');
                                        }
                                    }
                                },
                                function(error) {}
                            );
                        });


                        var custom_header = $("#div_custome_header");
                        if (custom_header.length > 0) {
                            $("#div_custome_header").insertAfter('#div_vtebuttons');
                            var preview_header_w = preview_header.width();
                            var offset = $("#div_vtebuttons").offset();
                            var offset1 = custom_header.offset();
                            if (offset.left != offset1.left) {
                                var left = preview_header_w * 0.22 + offset.left - 45;
                                $("#div_custome_header").css({'left': left + 'px'});
                            }
                        }

                        var vteProgressBar = $("#vteProgressBarMainContainer");
                        if (vteProgressBar.length > 0) {
                            vteProgressBar.insertAfter('#div_vtebuttons');
                        }
                    }
                },
                function(error) {}
            );
        }
    }
    $('.inlineAjaxSave').on('click', function() {
        jQuery(document).ajaxComplete(function(event, xhr, settings) {
            var url = settings.data;
            if (typeof url == 'undefined' && settings.url) url = settings.url;
            if (Object.prototype.toString.call(url) == '[object String]' && url.indexOf('action=SaveAjax') != -1 && jQuery.inArray(url,arrUrl) === -1) {
                arrUrl.push(url);
                var instance = new VTEButtons_Js();
                instance.registerShowOnDetailView();
            }
        });
    });
});