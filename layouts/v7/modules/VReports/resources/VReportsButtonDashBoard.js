Vtiger.Class("VReports_ButtonDashBoard_Js", {
    currentInstance : false,
},{
    registerEditLink: function () {
        jQuery('#page').on('click', 'a[name="editlink"]', function (e) {
            var element = jQuery(e.currentTarget);
            var url = element.data('url');
            var listInstance = Vtiger_List_Js.getInstance();
            var postData = listInstance.getDefaultParams();
            for (var key in postData) {
                if (postData[key]) {
                    postData['return' + key] = postData[key];
                    delete postData[key];
                } else {
                    delete postData[key];
                }
            }
            e.preventDefault();
            e.stopPropagation();
            window.open(url);
        });
    },
    registerEventsQuickUpdate:function(){
        var thisInstance = this;
        jQuery(document).off('click','.vteButtonQuickUpdateVReport');
        jQuery(document).on('click','.vteButtonQuickUpdateVReport',function(e){
            var target = jQuery(e.currentTarget);
            var postVTEButtonsSave  = function(data) {
                var refreshButton = $('div[data-record="'+target.closest('ul').data('widgetid')+'"]').find('a[data-event="Refresh"]');
                refreshButton.trigger('click');
            };
            var source_module = $(this).data('module');
            var vtebuttonid = $(this).data('button-id');
            var source_recordId = $(this).data('record-id');
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
                            var params = {};
                            params['module'] = 'VTEButtons';
                            params['action'] = 'ActionAjax';
                            params['mode'] = 'autoUpdate';
                            params['vtebuttons_id'] = vtebuttonid;
                            params['record'] = source_recordId;
                            params['source_module'] = source_module;
                            app.request.post({data:params}).then(function(err,data) {
                                if(err == null){
                                    app.helper.showSuccessNotification({message:data.label+' updated to '+data.value+'.'});
                                    postVTEButtonsSave();
                                }
                            });
                        }else{
                            var vteButtonId = vtebuttonid;
                            var viewEditUrl = "module=VTEButtons&view=QuickEditAjax&record="+source_recordId+"&moduleEditName="+source_module+"&vteButtonId="+vteButtonId;
                            var params= {'callbackFunction': postVTEButtonsSave,'noCache':true};
                            thisInstance.getVTEButtonsQuickEditForm(viewEditUrl, source_module, params).then(function(data) {
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
                                postVTEButtonsSave();
                            });
                        }
                    }
                },
                function(error) {
                }
            );
        });
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
                    var e = jQuery.Event(Vtiger_Edit_Js.recordPresaveEvent);
                    app.event.trigger(e);
                    if (e.isDefaultPrevented()) {
                        return false;
                    }
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
    registerEvents : function () {
        var thisInstance = this;
        this.registerEventsQuickUpdate();
        this.registerEditLink();

    }
});
jQuery(document).ready(function () {
    // Only load when have VTEButtons
    var instance = new VReports_ButtonDashBoard_Js();
    instance.registerEvents();
});
