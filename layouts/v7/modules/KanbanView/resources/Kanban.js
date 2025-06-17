/* ********************************************************************************
 * The content of this file is subject to the Kanban View("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

Vtiger.Class("KanbanView_Js",{
    getSettingView : function (source_module,targetModule) {
        var params ={
            'module':'KanbanView',
            'view':'ConfigureViewAjax',
            'source_module': source_module
        };
        app.request.post({data:params}).then(
            function(err,data){
                if(err==null){
                    app.helper.showModal(data);
                    var thisinstance = new KanbanView_Js();
                    thisinstance.targetModule = targetModule;
                    thisinstance.source_module = source_module;
                    thisinstance.registerEvent();
                }
            }
        );
    },
     initData_KanbanView : function() {
    var viewTarget = app.view();
    var sPageURL = window.location.search.substring (1);
    //var targetModule = '';
    var targetModule = '';
    var cvid = false;
    var goback = false;
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == 'module') {
            targetModule = sParameterName[1];
        }
        else if (sParameterName[0] == 'source_module') {
            source_module = sParameterName[1];
        }else if(sParameterName[0] == 'cvid'){
            cvid = sParameterName[1];
        }else if(sParameterName[0] == 'goback'){
            goback = sParameterName[1];
        }
    }

    if(targetModule != 'KanbanView'){
        var source_module =targetModule
    }
    if(!targetModule  || !source_module){
        source_module = app.getModuleName();
    }
    if(viewTarget == 'List'){
        if(targetModule != 'KanbanView'){
            var objParams = {
                'module':'KanbanView',
                'action':'ActionAjax',
                'mode':'checkKanbanViewEnable',
                'source_module':source_module
            };
            app.request.post({data:objParams}).then(
                function(err,data){
                    if(err == null && data.isEnable == true){
                        jQuery('#appnav .navbar-nav').prepend(KanbanView_Js.kanbanButtonHtml);
                            url = 'index.php?module=KanbanView&view=Index';
                            var viewname = jQuery('.listViewFilter.active a').data('filter-id');
                            if(viewname != undefined){
                                url +='&viewname='+viewname;
                            }
                            var search_key = jQuery("#alphabetSearchKey").val();
                            url +='&search_key='+search_key+"&search_value=&search_params=";
                            var pageNumber = jQuery('#pageNumber').val();
                            url +='&page='+pageNumber;
                            var parent = app.getParentModuleName();
                            url +='&parent='+parent;
                            url +='&source_module='+source_module;
                            var appName = app.getAppName();
                            url +='&app='+appName;
                            window.location.href = url;
                    }
                }
            );
        }
    }else if(viewTarget =='Detail' && cvid != false){
        var url = 'index.php?module=KanbanView&view=Index';
        url +='&viewname='+cvid;
        url +='&source_module='+source_module;
        jQuery('#appnav .navbar-nav').prepend(KanbanView_Js.kanbanButtonHtml);
        if(url == undefined){
            url = 'index.php?module=KanbanView&view=Index';
            var viewname = jQuery('.listViewFilter.active a').data('filter-id');
            if(viewname != undefined){
                url +='&viewname='+viewname;
            }
            var search_key = jQuery("#alphabetSearchKey").val();
            url +='&search_key='+search_key+"&search_value=&search_params=";
            var pageNumber = jQuery('#pageNumber').val();
            url +='&page='+pageNumber;
            var parent = app.getParentModuleName();
            url +='&parent='+parent;
            url +='&source_module='+source_module;
            var appName = app.getAppName();
            url +='&app='+appName;
        }
        window.location.href = url;
    }

     if(targetModule != 'KanbanView'){
         var source_module =targetModule
     }
     if(!targetModule  || !source_module){
         source_module = app.getModuleName();
     }
     if(viewTarget == 'List'){
         if(targetModule != 'KanbanView'){
             var objParams = {
                 'module':'KanbanView',
                 'action':'ActionAjax',
                 'mode':'checkKanbanViewEnable',
                 'source_module':source_module
             };
             app.request.post({data:objParams}).then(
                 function(err,data){
                     if(err == null && data.isEnable == true){
                         jQuery('#appnav .navbar-nav').prepend(KanbanView_Js.kanbanButtonHtml);
                         registerEventsClickKanbanButton(source_module);
                     }
                 }
             );
         }
     }else if(viewTarget =='Detail' && cvid != false){
         var url = 'index.php?module=KanbanView&view=Index';
         url +='&viewname='+cvid;
         url +='&source_module='+source_module;
         jQuery('#appnav .navbar-nav').prepend(KanbanView_Js.kanbanButtonHtml);
         registerEventsClickKanbanButton(source_module,url);
     }
}

},{
    primaryFieldValue: false,
    /**
     * Function to get the MenuList select element
     */
    getMenuListSelectElement : function() {
        if(this.primaryFieldValue == false) {
            this.primaryFieldValue = jQuery('#primaryValueSelectElement');
        }
        return this.primaryFieldValue;
    },
    /**
     * Function to get the select2 element from the raw select element
     * @params: select element
     * @return : select2Element - corresponding select2 element
     */
    getSelect2ElementFromSelect : function(selectElement) {
        var selectId = selectElement.attr('id');
        //since select2 will add s2id_ to the id of select element
        var select2EleId = "s2id_"+selectId;
        return jQuery('#'+select2EleId);
    },
    /**
     * Function which will get the selected columns with order preserved
     * @return : array of selected values in order
     */
    getSelectedColumns : function() {
        var selectElement = this.getMenuListSelectElement();
        var select2Element = app.getSelect2ElementFromSelect(selectElement);

        var selectedValuesByOrder = {};
        var selectedOptions = selectElement.find('option:selected');
        var orderedSelect2Options = select2Element.find('li.select2-search-choice').find('div');
        var i = 1;
        orderedSelect2Options.each(function(index,element){
            var chosenOption = jQuery(element);
            selectedOptions.each(function(optionIndex, domOption){
                var option = jQuery(domOption);
                if(option.html() == chosenOption.html()) {
                    selectedValuesByOrder[i++] = option.val();
                    return false;
                }
            });
        });

        return selectedValuesByOrder;
    },

    registerEventAddMoreButton:function(){
        var thisInstance = this;
        jQuery('.btnAddMore').on('click',function(){
            var newEle = jQuery('.fieldBasic').find('.otherField').clone();
            jQuery('select',newEle).addClass('select2');
            jQuery('.listOtherField tbody').append(newEle);
            vtUtils.applyFieldElementsView(newEle);
            thisInstance.registerEventHoverDeleteOtherField();
        });
    },
    sortableRecords : function(){
        var thisInstance = this;
        var container = jQuery( ".listOtherField tbody" );
        container.sortable({
            handle: ".icon-move",
            cursor: "move",
            update: function( event, ui ) {
            }
        });
        container.disableSelection();
    },

    getOtherField:function(){
        var items = [];
        jQuery('#KanbanConfigure .listOtherField tbody').find('select.selectedOtherField').each(function(index, el){
            if(jQuery(this).val() != 'none'){
                items.push(jQuery(this).val());
            }
        });
        return items;
    },
    registerEventForSelectPrimaryField: function(){
        var thisInstance = this;
        var elementPrimaryField = jQuery('#KanbanConfigure select[name="primaryField"]');
        elementPrimaryField.on('change',function(){
            var primaryField = elementPrimaryField.val();
            params={
                'module':'KanbanView',
                'action':'ActionAjax',
                'mode':'getPrimaryValues',
                'source_module':  thisInstance.source_module,
                'primaryField':primaryField
            };
            app.helper.showProgress();
            app.request.post({data:params}).then(
                function(err,data){
                    if(err == null && data){
                        var elementOpt = "";
                        jQuery.each( data, function( key, value ) {
                            elementOpt += "<option value = '" + key + "'>" + value + "</option>";
                        });
                        jQuery('#s2id_primaryValueSelectElement').find('.select2-search-choice').remove();
                        jQuery('#primaryValueSelectElement').html(elementOpt);
                        jQuery('#primaryFieldValue').show();
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },
    registerEventForSelectPrimaryValue: function(){
        var thisInstance = this;
        var select2ChoiceElement = jQuery('#primaryFieldValue ul.select2-choices');
        select2ChoiceElement.sortable({
            'containment': select2ChoiceElement,
            start: function() { jQuery('#selectedMenus').select2("onSortStart"); },
            update: function() {
            }
        });

        jQuery('.select2-container').css('width','100%');
    },
    registerEventForSaveButton:function(){
        var thisInstance = this;
        jQuery('#save_kanbanview_setting').on('click',function(){
            var form = jQuery('#KanbanConfigure');
            var isDefaultPage = form.find('[name="isDefaultPage"]').prop('checked') ? 1:0;
            var params = {
                'module':'KanbanView',
                'action':'SaveAjax',
                'mode': 'saveKanbanViewSetting',
                'source_module':thisInstance.source_module,
                'primaryField':form.find('select[name="primaryField"]').val(),
                'primaryFieldValue':JSON.stringify(thisInstance.getSelectedColumns()),
                'otherField':thisInstance.getOtherField(),
                'isDefaultPage': isDefaultPage
            };
            app.helper.showProgress();
            app.request.post({data:params}).then(
                function(err,data){
                    if(err == null){
                        app.helper.hideProgress();
                        app.helper.hideModal();
                        if(thisInstance.targetModule == "KanbanView"){
                            window.location.reload();
                        }
                    }
                }
            );
        });
    },
    registerQuickCreatePostLoadEvents: function(form, params) {
        var thisInstance = this;
        var submitSuccessCallbackFunction = params.callbackFunction;
        var goToFullFormCallBack = params.goToFullFormcallback;
        if (typeof submitSuccessCallbackFunction == 'undefined') {
            submitSuccessCallbackFunction = function() {
            };
        }
        form.on('submit', function(e) {
            var form = jQuery(e.currentTarget);
            var module = form.find('[name="module"]').val();
            //Form should submit only once for multiple clicks also
            if (typeof form.data('submit') != "undefined") {
                return false;
            } else {
                var invalidFields = form.data('jqv').InvalidFields;

                if (invalidFields.length > 0) {
                    //If validation fails, form should submit again
                    form.removeData('submit');
                    form.closest('#globalmodal').find('.modal-header h3').progressIndicator({
                        'mode': 'hide'
                    });
                    e.preventDefault();
                    return;
                } else {
                    //Once the form is submiting add data attribute to that form element
                    form.data('submit', 'true');
                    form.closest('#globalmodal').find('.modal-header h3').progressIndicator({
                        smallLoadingImage: true,
                        imageContainerCss: {
                            display: 'inline',
                            'margin-left': '18%',
                            position: 'absolute'
                        }
                    });
                }

                var recordPreSaveEvent = jQuery.Event(Vtiger_Edit_Js.recordPreSave);
                form.trigger(recordPreSaveEvent, {
                    'value': 'edit',
                    'module': module
                });
                if (!(recordPreSaveEvent.isDefaultPrevented())) {
                    var targetInstance = thisInstance;
                    var moduleInstance = Vtiger_Edit_Js.getInstanceByModuleName(module);
                    if(typeof(moduleInstance.quickCreateSave) === 'function'){
                        targetInstance = moduleInstance;
                    }

                    targetInstance.quickCreateSave(form).then(
                        function(data) {
                            app.hideModalWindow();
                            //fix for Refresh list view after Quick create
                            var parentModule=thisInstance.source_module;
                            var viewname=app.getViewName();
                            if((module == parentModule) && (viewname=="List")){
                                var listinstance = new Vtiger_List_Js();
                                listinstance.getListViewRecords();
                            }
                            submitSuccessCallbackFunction(data);
                            var registeredCallBackList = thisInstance.quickCreateCallBacks;
                            for (var index = 0; index < registeredCallBackList.length; index++) {
                                var callBack = registeredCallBackList[index];
                                callBack({
                                    'data': data,
                                    'name': form.find('[name="module"]').val()
                                });
                            }
                        },
                        function(error, err) {
                        }
                    );
                } else {
                    //If validation fails in recordPreSaveEvent, form should submit again
                    form.removeData('submit');
                    form.closest('#globalmodal').find('.modal-header h3').progressIndicator({
                        'mode': 'hide'
                    });
                }
                e.preventDefault();
            }
        });

        form.find('#goToFullForm').on('click', function(e) {
            var form = jQuery(e.currentTarget).closest('form');
            var editViewUrl = jQuery(e.currentTarget).data('editViewUrl');
            if (typeof goToFullFormCallBack != "undefined") {
                goToFullFormCallBack(form);
            }
            thisInstance.quickCreateGoToFullForm(form, editViewUrl);
        });

        this.registerTabEventsInQuickCreate(form);
    },
    registerEventHoverDeleteOtherField: function () {
        jQuery('.deleteOtherField').on('click',function(){
            jQuery(this).closest('tr').remove();
        });
    },
    registerEvent: function(){
        var thisinstance= this;
        thisinstance.registerEventForSelectPrimaryField();
        thisinstance.registerEventForSelectPrimaryValue();
        thisinstance.registerEventAddMoreButton();
        thisinstance.sortableRecords();
        thisinstance.registerEventForSaveButton();
        thisinstance.registerEventHoverDeleteOtherField();

    }
});

jQuery(document).ready(function() {
    var linkKanban =  $(".module-extensions .listViewFilter").find("a[href^='javascript:KanbanView_Js.initData_KanbanView()']").attr('href');
    if(linkKanban != undefined) {
        $(".module-extensions .listViewFilter").find("a[href^='javascript:KanbanView_Js.initData_KanbanView()']").attr('href', linkKanban.split("&")[0]);
    }
});
// function initData_KanbanView() {
// // Only load when loadHeaderScript=1 BEGIN #241208
//     if (typeof VTECheckLoadHeaderScript == 'function') {
//         if (!VTECheckLoadHeaderScript('KanbanView')) {
//             return;
//         }
//     }
//     // Only load when loadHeaderScript=1 END #241208
//
//     var viewTarget = app.view();
//     var sPageURL = window.location.search.substring (1);
//     var targetModule = '';
//     var targetModule = '';
//     var cvid = false;
//     var goback = false;
//     var sURLVariables = sPageURL.split('&');
//     for (var i = 0; i < sURLVariables.length; i++) {
//         var sParameterName = sURLVariables[i].split('=');
//         if (sParameterName[0] == 'module') {
//             targetModule = sParameterName[1];
//         }
//         else if (sParameterName[0] == 'source_module') {
//             source_module = sParameterName[1];
//         }else if(sParameterName[0] == 'cvid'){
//             cvid = sParameterName[1];
//         }else if(sParameterName[0] == 'goback'){
//             goback = sParameterName[1];
//         }
//     }
//
//     if(targetModule != 'KanbanView'){
//         var source_module =targetModule
//     }
//     if(!targetModule  || !source_module){
//         source_module = app.getModuleName();
//     }
//     if(viewTarget == 'List'){
//         if(targetModule != 'KanbanView'){
//             var objParams = {
//                 'module':'KanbanView',
//                 'action':'ActionAjax',
//                 'mode':'checkKanbanViewEnable',
//                 'source_module':source_module
//             };
//             app.request.post({data:objParams}).then(
//                 function(err,data){
//                     if(err == null && data.isEnable == true){
//                             jQuery('#appnav .navbar-nav').prepend(KanbanView_Js.kanbanButtonHtml);
//                             registerEventsClickKanbanButton(source_module);
//                     }
//                 }
//             );
//         }
//     }else if(viewTarget =='Detail' && cvid != false){
//         var url = 'index.php?module=KanbanView&view=Index';
//         url +='&viewname='+cvid;
//         url +='&source_module='+source_module;
//         jQuery('#appnav .navbar-nav').prepend(KanbanView_Js.kanbanButtonHtml);
//         registerEventsClickKanbanButton(source_module,url);
//     }
// }


// }

function registerEventsClickKanbanButton(sourceModule,url) {
    var kanbanButton = jQuery('.listViewFilter');
    kanbanButton.on('click',function(){
        var focus = $(this);
        var kenbanText = focus.find('a').text();
        if(kenbanText == 'KanbanView'){
            if(url == undefined){
                url = 'index.php?module=KanbanView&view=Index';
                var viewname = jQuery('.listViewFilter.active a').data('filter-id');
                if(viewname != undefined){
                    url +='&viewname='+viewname;
                }
                var search_key = jQuery("#alphabetSearchKey").val();
                url +='&search_key='+search_key+"&search_value=&search_params=";
                var pageNumber = jQuery('#pageNumber').val();
                url +='&page='+pageNumber;
                var parent = app.getParentModuleName();
                url +='&parent='+parent;
                url +='&source_module='+sourceModule;
                var appName = app.getAppName();
                url +='&app='+appName;
            }
            window.location.href = url;
        }
    });
}