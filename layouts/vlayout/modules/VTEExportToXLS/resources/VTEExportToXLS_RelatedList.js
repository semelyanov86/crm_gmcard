/* ********************************************************************************
 * The content of this file is subject to the Progressbar/Bills ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

jQuery.Class("VTEExportToXLS_RelatedList_Js", {
    instance: false,
    getInstance: function () {
        if (VTEExportToXLS_RelatedList_Js.instance == false) {
            var instance = new VTEExportToXLS_RelatedList_Js();
            VTEExportToXLS_RelatedList_Js.instance = instance;
            return instance;
        }
        return VTEExportToXLS_RelatedList_Js.instance;
    }
},{
    addExportButonToRelatedList:function(){
        var self = this;
        var source_module = app.getModuleName();
        var record = app.getRecordId();
        var related_module = self.convertUrlToDataParams(window.location.href).relatedModule;
        var relatedHeader = $('div.relatedHeader');
        var params = {};
        params['module'] = 'VTEExportToXLS';
        params['view'] = 'RelatedExportForm';
        params['source_module'] = source_module;
        params['related_module'] = related_module;
        params['record'] = record;
        AppConnector.request({data:params}).then(
            function(data) {
                if(typeof data !== undefined){
                    relatedHeader.find('div.btn-toolbar:nth-child(1) div:nth-child(1) div.btn-group:nth-child(1)').append(data);
                    var button  = $('.openModalExportToExcelButton');
                    if(button != undefined){
                        button.on('click',function(){
                            var selected_ids = new Array();
                            var ids_in_page = new Array();
                            var listViewEntriesCheckBox = $('.listViewEntriesCheckBox');
                            listViewEntriesCheckBox.each(function(k,item){
                                if(item.checked == true){
                                    selected_ids.push(item.value);
                                }
                                ids_in_page.push(item.value);
                            });
                            selected_ids = selected_ids.join();
                            ids_in_page = ids_in_page.join();
                            var page = $('.relatedContainer [name="currentPageNum"]').val();
                            var params = {};
                            params['module'] = 'VTEExportToXLS';
                            params['page'] = page;
                            params['view'] = 'RelatedExportModal';
                            params['sourceModule'] = source_module;
                            params['related_module'] = related_module;
                            params['selected_ids'] = selected_ids;
                            params['ids_in_page'] = ids_in_page;
                            params['record'] = record;
                            app.request.post({data:params}).then(function(err,data) {
                                app.helper.loadPageContentOverlay(data).then(function (container) {
                                    container.find('form#exportForm').on('submit', function () {
                                        jQuery(this).find('button[type="submit"]').attr('disabled', 'disabled');
                                        self.hidePageContentOverlay();
                                    });
                                });
                            });
                        });
                    }
                }
            },
            function(error) {
            }
        );
    },
    convertUrlToDataParams: function (url) {
    var params = {};
    if (typeof url !== 'undefined' && url.indexOf('?') !== -1) {
        var urlSplit = url.split('?');
        url = urlSplit[1];
    }
    var queryParameters = url.split('&');
    for (var index = 0; index < queryParameters.length; index++) {
        var queryParam = queryParameters[index];
        var queryParamComponents = queryParam.split('=');
        params[queryParamComponents[0]] = queryParamComponents[1];
    }
    return params;
    },
    hidePageContentOverlay : function() {
        var aDeferred = new jQuery.Deferred();
        var overlayPageContent = $('#overlayPageContent');
        overlayPageContent.one('hidden.bs.modal', function() {
            overlayPageContent.find('.data').html('');
            aDeferred.resolve();
        })
        $('#overlayPageContent').modal('hide');
        return aDeferred.promise();
    },
    registerEvents: function(){
        var self = this;
        if(window.location.href.indexOf('mode=showRelatedList') != -1){
            self.addExportButonToRelatedList();
        }
        jQuery(document).ajaxComplete(function(event, request, settings){
            if(settings.url != undefined){
                if(settings.url.indexOf('view=Detail') != -1 && settings.url.indexOf('mode=showRelatedList') != -1){
                    self.addExportButonToRelatedList();
                }
            }
        });
    }
});

jQuery(document).ready(function () {
    // Only load when loadHeaderScript=1 BEGIN #241208
    if (typeof VTECheckLoadHeaderScript == 'function') {
        if (!VTECheckLoadHeaderScript('VTEExportToXLS_RelatedList')) {
            return;
        }
    }
    // Only load when loadHeaderScript=1 END #241208

    var viewName = app.getViewName();
    if(viewName == 'Detail'){
        var instance = new VTEExportToXLS_RelatedList_Js();
        instance.registerEvents();
    }
});
