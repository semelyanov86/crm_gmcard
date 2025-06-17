/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger.Class("VTE_Conditional_Alerts_Js",{
},{
    fieldValuesCache: {},
    alerted:false,
    message_on_edit: [],
    /*
     * Function to register the change module filter
     */
    registerModuleFilterChange:function(){
        jQuery('#capModuleFilter').on('change',function(){
            var filter_value = jQuery(this).val();
            window.location.href = 'index.php?module=VTEConditionalAlerts&parent=Settings&view=ListAll&mode=listAll&ModuleFilter=' + filter_value;
        });
    },
    registerPagingAction:function(){
        var current_page = jQuery('#current_page').val();
        var filter_value = jQuery('#capModuleFilter').val();
        jQuery('#clfListViewNextPageButton').on('click',function(){
            window.location.href = 'index.php?module=VTEConditionalAlerts&parent=Settings&view=ListAll&mode=listAll&ModuleFilter=' + filter_value+'&page='+(parseInt(current_page) + 1);
        });
        jQuery('#clfListViewPreviousPageButton').on('click',function(){
            window.location.href = 'index.php?module=VTEConditionalAlerts&parent=Settings&view=ListAll&mode=listAll&ModuleFilter=' + filter_value+'&page='+(parseInt(current_page) - 1);
        });

    },
    registerDeleteAction:function(){
        var current_page = jQuery('#current_page').val();
        var filter_value = jQuery('#capModuleFilter').val();
        jQuery('.removeVTEConditionalAlert').on('click',function(){
            var recordId = jQuery(this).data('id');
            var message = app.vtranslate('Are you sure you want to delete this row?');
            app.helper.showConfirmationBox({'message' : message}).then(
                function(){
                    window.location.href = 'index.php?module=VTEConditionalAlerts&parent=Settings&view=ListAll&mode=delete&record='+recordId+'&ModuleFilter=' + filter_value+'&page='+current_page;
                },
                function(error, err){
                }
            );
        });
    },
    registerEditAction:function(){
        jQuery('.editVCA').on('click',function(){
            var url = jQuery(this).data('url');
            window.location.href = url;
        });
    },
    checkOnEdit:function(module,field_name_changed){
        var thisInstance = this;
        //to integrate with Custom View & Form
        var is_quickcreate = false;
        if(jQuery('#QuickCreate').length > 0) is_quickcreate = true;
        //if(jQuery("#hd_cap_info").length == 0) thisInstance.loadAlertPopupConfigForDetail(module);
        var hd_cap_info = jQuery("#hd_cap_info").val();
        var hd_record_info = jQuery("#hd_record_info").val();
        if(typeof  hd_cap_info !== 'undefined') {
            var cap_info = JSON.parse(hd_cap_info);
            var hd_record_info = JSON.parse(hd_record_info);
            if (cap_info.length > 0) {
                var show_alert = false;
                var alert_info = {};
                var list_action = [];
                var addActions = function(data) {
                    var index = -1;
                    for(var i = 0; i < list_action.length; i++) {
                        if(list_action[i].action_title === data.action_title) {
                            index = i;
                        }
                    }
                    if(index > -1) {
                        list_action[index] = data;
                    } else {
                        list_action.push(data)
                    }
                }
                jQuery.each(cap_info,function(k,v){
                    var all_condition = v.condition.all;
                    var any_condition = v.condition.any;
                    var actions = v.actions;
                    var condition_key = k;
                    if(thisInstance.checkBelongToCondition(all_condition,any_condition,field_name_changed)){
                        var check_condition = thisInstance.checkConditionToForm(all_condition,any_condition,field_name_changed,hd_record_info);
                        if (is_quickcreate) check_condition = thisInstance.checkConditionToForm(all_condition,any_condition,field_name_changed,'','QuickCreate');
                        if(check_condition){
                            if(actions.alert_while_edit == 1){
                                show_alert = true;
                                alert_info = actions;
                                addActions(actions);
                            }
                        }
                    }
                });
                if(list_action.length > 0){
                    if(is_quickcreate){
                        var html =      '<span>'+alert_info.description+'</span>';
                        thisInstance.showPopupOnQuickCreate('[name="'+field_name_changed+'"]',alert_info.action_title,html);
                    }
                    // else thisInstance.showPopupAlert(list_action);
                    else{

                        list_action.forEach(function(item, k){
                            var check_exist_con = thisInstance.message_on_edit.filter(function(action){
                                return (action.action_title == item.action_title && action.description == item.description) ;
                            }).length;
                            if(check_exist_con == 0){
                                thisInstance.message_on_edit.push(item);
                            }
                           
                        });
                    }
                }
            }
        }
    },

    checkOnQuickCreate:function(module, field_name_changed){
        var thisInstance = this;
        var editForm = jQuery("#QuickCreate");
        
        var params = {
            module : 'VTEConditionalAlerts',
            action : 'ActionAjax',
            async:false,
            mode : 'getConditionAlertForModule',
            current_module : module
        };
        app.request.post({'data': params}).then(
            function(err,data){
                if(err === null) {
                    if(!jQuery.isEmptyObject(data)){
                        jQuery.each(data.clf_info,function(k,v){
                            var all_condition = v.condition.all;
                            var any_condition = v.condition.any;
                            var actions = v.actions;
                            var check_condition = thisInstance.checkConditionToForm(all_condition, any_condition, field_name_changed, '', 'QuickCreate');
                            if (check_condition) {
                                if (actions.alert_while_edit == 1) {
                                    var modalTemplate = '<div class="modal fade mt-5" id="modalQuickCreate" tabindex="-1" aria-labelledby="modalLabelQuickCreate" aria-hidden="true">' +
                                        '<div class="modal-dialog">' +
                                            '<div class="modal-content">' +
                                                '<div class="modal-header">' +
                                                    '<h3 class="modal-title" id="modalLabelQuickCreate">Alert</h3>' +
                                                '</div>' +
                                                '<div class="modal-body">' + 
                                                    '<h4>' + actions.action_title + '</h4>' +
                                                    '<span>' + actions.description + '</span>' +
                                                '</div>' +
                                                '<div class="modal-footer">' +
                                                    '<button id="btnCloseModalQuickCreate" type="button" class="btn btn-success" data-dismiss="modal">' +
                                                        'Close' +
                                                    '</button>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>';
                                    editForm.append(modalTemplate);
                                    var modal = jQuery('#modalQuickCreate');
                                    var btnCloseModal = jQuery('#btnCloseModalQuickCreate');
                                    modal.on('show.mdb.modal', () => {
                                        modal.classList.add('custom-class');
                                    });
                                    modal.on('hidden.mdb.modal', () => {
                                        modal.classList.remove('custom-class');
                                    });
                                    btnCloseModal.click(function(){
                                        modal.addClass('hide');
                                        modal.remove();
                                    })
                                    modal.modal();
                                }
                            }
                        });
                    }
                }
            }
        );
    },

    checkFieldChangeOnDetail:function(module,field_name_changed){
        var thisInstance = this;
        var list_alert = [];
        var addActions = function(data) {
            var index = -1;
            for(var i = 0; i < list_alert.length; i++) {
                if(list_alert[i].action_title === data.action_title) {
                    index = i;
                }
            }
            if(index > -1) {
                list_alert[index] = data;
            } else {
                list_alert.push(data)
            }
        }
        //to integrate with Custom View & Form
        if(module == "CustomFormsViews"){
            var top_url = window.location.href.split('?');
            var array_url = thisInstance.getQueryParams(top_url[1]);
            module = array_url.currentModule;
        }
        var params = {
            module : 'VTEConditionalAlerts',
            action : 'ActionAjax',
            async:false,
            mode : 'getConditionAlertForModule',
            current_module : module
        };
        app.request.post({'data': params}).then(
            function(err,data){
                if(err === null) {
                    if(!jQuery.isEmptyObject(data)){
                        var show_alert = false;
                        var alert_info = {};
                        var record_info = data.record_info;
                        jQuery('<input>').attr({
                            type: 'hidden',
                            id: 'hd_cap_info',
                            value:JSON.stringify(data.clf_info)
                        }).appendTo(jQuery('#detailView'));
                        jQuery.each(data.clf_info,function(k,v){
                            var all_condition = v.condition.all;
                            var any_condition = v.condition.any;
                            var actions = v.actions;
                            if(thisInstance.checkBelongToCondition(all_condition,any_condition,'on_details')){
                                var check_condition = thisInstance.checkConditionToForm(all_condition,any_condition,field_name_changed);
                                var field_changed = jQuery('#detailView').find('[name="'+field_name_changed+'"]');
                                if(check_condition){
                                    if(actions.alert_while_edit == 1){
                                        addActions(actions);

                                    }
                                }
                            }
                        });
                        if(list_alert.length > 0){
                            thisInstance.showPopupAlert(list_alert,'detailView');
                            return false;
                        }
                    }
                }
            }
        );
    },
    regSaveFieldOnDetail:function(module,field_name_changed,save_btn){
        var thisInstance = this;
        var can_save = true;
        var show_alert = false;
        var alert_info = {};
        var list_alert = [];
        var addActions = function(data) {
            var index = -1;
            for(var i = 0; i < list_alert.length; i++) {
                if(list_alert[i].action_title === data.action_title) {
                    index = i;
                }
            }
            if(index > -1) {
                list_alert[index] = data;
            } else {
                list_alert.push(data)
            }
        }
        if(jQuery("#hd_cap_info").length == 0) thisInstance.loadAlertPopupConfigForDetail(module);
        var hd_cap_info = jQuery("#hd_cap_info").val();
        if(typeof  hd_cap_info !== 'undefined'){
            var clf_info = JSON.parse(hd_cap_info);
            if(clf_info.length > 0){
                jQuery.each(clf_info,function(k,v){
                    var all_condition = v.condition.all;
                    var any_condition = v.condition.any;
                    var actions = v.actions;
                    if(thisInstance.checkBelongToCondition(all_condition,any_condition,field_name_changed)){
                        var check_condition = thisInstance.checkConditionToForm(all_condition,any_condition,field_name_changed);
                        if(check_condition){
                            if(actions.donot_allow_to_save ==1){
                                //can_save = false;
                            }
                            if(actions.alert_on_save == 1 && actions.alert_while_edit == 0){
                                //addActions(actions);
                            }
                            //return false;
                        }
                    }
                });
                if(list_alert.length > 0){
                    thisInstance.showPopupAlert(list_alert,'detailView');
                }
            }
        }
        if(can_save){
            //thisInstance.overideAjaxEditSaveEvent(save_btn);
        }
        return false;
    },
    //overideAjaxEditSaveEvent:function(currentTarget){
    //    var detailInstance = Vtiger_Detail_Js.getInstance();
    //    var contentHolder = jQuery('.detailview-header');
    //    var currentTdElement = detailInstance.getInlineWrapper(currentTarget);
    //    var detailViewValue = jQuery('.value',currentTdElement);
    //    var editElement = jQuery('.edit',currentTdElement);
    //    var actionElement = jQuery('.editAction', currentTdElement);
    //    var fieldBasicData = jQuery('.fieldBasicData', editElement);
    //    var fieldName = fieldBasicData.data('name');
    //    var fieldType = fieldBasicData.data("type");
    //    var previousValue = jQuery.trim(fieldBasicData.data('displayvalue'));
    //
    //    var fieldElement = jQuery('[name="'+ fieldName +'"]', editElement);
    //    var ajaxEditNewValue = fieldElement.val();
    //
    //    // ajaxEditNewValue should be taken based on field Type
    //    if(fieldElement.is('input:checkbox')) {
    //        if(fieldElement.is(':checked')) {
    //            ajaxEditNewValue = '1';
    //        } else {
    //            ajaxEditNewValue = '0';
    //        }
    //        fieldElement = fieldElement.filter('[type="checkbox"]');
    //    } else if(fieldType == 'reference'){
    //        ajaxEditNewValue = fieldElement.attr('value');
    //    }
    //
    //    // prev Value should be taken based on field Type
    //    var customHandlingFields = ['owner','ownergroup','picklist','multipicklist','reference','boolean'];
    //    if(jQuery.inArray(fieldType, customHandlingFields) !== -1){
    //        previousValue = fieldBasicData.data('value');
    //    }
    //
    //    // Field Specific custom Handling
    //    if(fieldType === 'multipicklist'){
    //        var multiPicklistFieldName = fieldName.split('[]');
    //        fieldName = multiPicklistFieldName[0];
    //    }
    //
    //    var fieldValue = ajaxEditNewValue;
    //
    //    //Before saving ajax edit values we need to check if the value is changed then only we have to save
    //    if(previousValue == ajaxEditNewValue) {
    //        detailViewValue.css('display', 'inline-block');
    //        editElement.addClass('hide');
    //        editElement.removeClass('ajaxEdited');
    //        jQuery('.editAction').removeClass('hide');
    //        actionElement.show();
    //    }else{
    //        var fieldNameValueMap = {};
    //        fieldNameValueMap['value'] = fieldValue;
    //        fieldNameValueMap['field'] = fieldName;
    //        var form = currentTarget.closest('form');
    //        var params = {
    //            'ignore' : 'span.hide .inputElement,input[type="hidden"]',
    //            submitHandler : function(form){
    //                var preAjaxSaveEvent = jQuery.Event(Vtiger_Detail_Js.PreAjaxSaveEvent);
    //                app.event.trigger(preAjaxSaveEvent,{form:jQuery(form),tiggeredFiledInfo:fieldNameValueMap});
    //                if(preAjaxSaveEvent.isDefaultPrevented()) {
    //                    return false;
    //                }
    //
    //                jQuery(currentTdElement).find('.input-group-addon').addClass('disabled');
    //                app.helper.showProgress();
    //                detailInstance.saveFieldValues(fieldNameValueMap).then(function(response) {
    //                    app.helper.hideProgress();
    //                    var postSaveRecordDetails = response;
    //                    if(fieldBasicData.data('type') == 'picklist' && app.getModuleName() != 'Users') {
    //                        var color = postSaveRecordDetails[fieldName].colormap[postSaveRecordDetails[fieldName].value];
    //                        if(color) {
    //                            var contrast = app.helper.getColorContrast(color);
    //                            var textColor = (contrast === 'dark') ? 'white' : 'black';
    //                            var picklistHtml = '<span class="picklist-color" style="background-color: ' + color + '; color: '+ textColor + ';">' +
    //                                postSaveRecordDetails[fieldName].display_value +
    //                                '</span>';
    //                        } else {
    //                            var picklistHtml = '<span class="picklist-color">' +
    //                                postSaveRecordDetails[fieldName].display_value +
    //                                '</span>';
    //                        }
    //                        detailViewValue.html(picklistHtml);
    //                    } else if(fieldBasicData.data('type') == 'multipicklist' && app.getModuleName() != 'Users') {
    //                        var picklistHtml = '';
    //                        var rawPicklistValues = postSaveRecordDetails[fieldName].value;
    //                        rawPicklistValues = rawPicklistValues.split('|##|');
    //                        var picklistValues = postSaveRecordDetails[fieldName].display_value;
    //                        picklistValues = picklistValues.split(',');
    //                        for(var i=0; i< rawPicklistValues.length; i++) {
    //                            var color = postSaveRecordDetails[fieldName].colormap[rawPicklistValues[i].trim()];
    //                            if(color) {
    //                                var contrast = app.helper.getColorContrast(color);
    //                                var textColor = (contrast === 'dark') ? 'white' : 'black';
    //                                picklistHtml = picklistHtml +
    //                                '<span class="picklist-color" style="background-color: ' + color + '; color: '+ textColor + ';">' +
    //                                picklistValues[i] +
    //                                '</span>';
    //                            } else {
    //                                picklistHtml = picklistHtml +
    //                                '<span class="picklist-color">' +
    //                                picklistValues[i] +
    //                                '</span>';
    //                            }
    //                            if(picklistValues[i+1]!==undefined)
    //                                picklistHtml+=' , ';
    //                        }
    //                        detailViewValue.html(picklistHtml);
    //                    } else if(fieldBasicData.data('type') == 'currency' && app.getModuleName() != 'Users') {
    //                        detailViewValue.find('.currencyValue').html(postSaveRecordDetails[fieldName].display_value);
    //                        contentHolder.closest('.detailViewContainer').find('.detailview-header-block').find('.'+fieldName).html(postSaveRecordDetails[fieldName].display_value);
    //                    }else {
    //                        detailViewValue.html(postSaveRecordDetails[fieldName].display_value);
    //                        //update namefields displayvalue in header
    //                        if(contentHolder.hasClass('overlayDetail')) {
    //                            contentHolder.find('.overlayDetailHeader').find('.'+fieldName)
    //                                .html(postSaveRecordDetails[fieldName].display_value);
    //                        } else {
    //                            contentHolder.closest('.detailViewContainer').find('.detailview-header-block')
    //                                .find('.'+fieldName).html(postSaveRecordDetails[fieldName].display_value);
    //                        }
    //                    }
    //                    fieldBasicData.data('displayvalue',postSaveRecordDetails[fieldName].display_value);
    //                    fieldBasicData.data('value',postSaveRecordDetails[fieldName].value);
    //                    jQuery(currentTdElement).find('.input-group-addon').removeClass("disabled");
    //
    //                    detailViewValue.css('display', 'inline-block');
    //                    editElement.addClass('hide');
    //                    editElement.removeClass('ajaxEdited');
    //                    jQuery('.editAction').removeClass('hide');
    //                    actionElement.show();
    //                    var postAjaxSaveEvent = jQuery.Event(Vtiger_Detail_Js.PostAjaxSaveEvent);
    //                    app.event.trigger(postAjaxSaveEvent, fieldBasicData, postSaveRecordDetails, contentHolder);
    //                    //After saving source field value, If Target field value need to change by user, show the edit view of target field.
    //                    if(detailInstance.targetPicklistChange) {
    //                        var sourcePicklistname = detailInstance.sourcePicklistname;
    //                        detailInstance.targetPicklist.find('.editAction').trigger('click');
    //                        detailInstance.targetPicklistChange = false;
    //                        detailInstance.targetPicklist = false;
    //                        detailInstance.handlePickListDependencyMap(sourcePicklistname);
    //                        detailInstance.sourcePicklistname = false;
    //                    }
    //                });
    //            }
    //        };
    //        validateAndSubmitForm(form,params);
    //    }
    //},
    loadAlertPopupConfigForDetail:function(module){
        var thisInstance = this;
        thisInstance.message_on_edit = [];
        var record_id = jQuery('#recordId,input[name="record"]').val();
        //to integrate with Custom View & Form
        if(module == "CustomFormsViews"){
            var top_url = window.location.href.split('?');
            var array_url = thisInstance.getQueryParams(top_url[1]);
            module = array_url.currentModule;
        }
        var params = {
            module : 'VTEConditionalAlerts',
            action : 'ActionAjax',
            async:false,
            mode : 'getConditionAlertForModule',
            current_module : module,
            record_id:record_id
        };
        app.request.post({'data': params}).then(
            function(err,data){
                if(err === null) {
                    if(!jQuery.isEmptyObject(data)){
                        var record_info = data.record_info;
                        jQuery('<input>').attr({
                            type: 'hidden',
                            id: 'hd_cap_info',
                            value:JSON.stringify(data.clf_info)
                        }).appendTo(jQuery('#page'));
                        jQuery('<input>').attr({
                            type: 'hidden',
                            id: 'hd_record_info',
                            value:JSON.stringify(record_info)
                        }).appendTo(jQuery('#page'));
                        //#1122766 BEGIN
                        var clf_info = data.clf_info;
                        clf_info.forEach(function(item){
                            var all = item.condition.all;
                            all.forEach(function(item_all){
                                thisInstance.checkOnEdit(module,item_all.columnname);
                            });
                            var any = item.condition.any;
                            any.forEach(function(item_any){
                                thisInstance.checkOnEdit(module,item_any.columnname);
                            });
                            if(all.length == 0 && any.length == 0) thisInstance.checkOnEdit(module,"on_details");
                                });
                            if((thisInstance.message_on_edit).length > 0){
                                 thisInstance.showPopupAlert(thisInstance.message_on_edit);
                            }
                        //#1122766 END
                    }
                }
            }
        );
    },
    showPopupAlert: function(actions,form_submit) {
        if(jQuery('#btnClosePopupAlert').length==0) {
            var action_str = JSON.stringify(actions);
            jQuery.each(actions, function (key, value) {
                var description = value.description;
                var subStr = description.match(/\$(.*?)\$/g);
                if (subStr) {
                    jQuery.each(subStr, function (skey, svalue) {
                        svalue = svalue.replace(/\$/g, '');
                        var field_value = jQuery('[name="' + svalue + '"]').val();
                        action_str = action_str.replace('$' + svalue + '$', field_value);
                    })
                }
            });
            var thisInstance = this;
            var actionParams = {
                url: "index.php?module=VTEConditionalAlerts&view=PopupAlert",
                data: {'actions_list': action_str}
            };
            app.request.post(actionParams).then(
                function (err, data) {
                    if (err === null) {
                        var callBackFunction = function () {
                            thisInstance.closePupupAlert(form_submit, actions.donot_allow_to_save);
                        };
                        var params = {};
                        params.cb = callBackFunction;
                        app.helper.showModal(data, params);
                        return false;
                    }
                }
            );
        }
    },
    closePupupAlert : function (form_submit,donot_allow_to_save){
        var instance = this;
        jQuery('#btnClosePopupAlert').on("click",function(e) {
            if(form_submit !== "detailView"){
                app.helper.hideModal();
                if(donot_allow_to_save ==1){
                    jQuery(form_submit).find('.saveButton').removeAttr('disabled');
                    return false;
                }
                else{
                    if(typeof form_submit !== 'undefined'){
                        var num_of_error_field = 0;
                        jQuery.each(jQuery(self).find('.input-error'),function(){
                            if(jQuery(this).hasClass('select2-display-none')){
                                return;
                            } else {
                                if (jQuery(this).closest(".input-error").length == 0){
                                    num_of_error_field++;
                                }
                            }
                        });
                        if(num_of_error_field == 0) {
                            if(app.getModuleName()=='Invoice' || app.getModuleName()=='PurchaseOrder' || app.getModuleName()=='SalesOrder' || app.getModuleName()=='Quotes'){
                                var inventoryEditInstance = new Inventory_Edit_Js();
                                inventoryEditInstance.updateLineItemElementByOrder();
                                var taxMode = inventoryEditInstance.isIndividualTaxMode();
                                var elementsList = inventoryEditInstance.lineItemsHolder.find('.'+inventoryEditInstance.lineItemDetectingClass);
                                inventoryEditInstance.saveProductCount();
                                inventoryEditInstance.saveSubTotalValue();
                                inventoryEditInstance.saveTotalValue();
                                inventoryEditInstance.savePreTaxTotalValue();
                            }
                            form_submit.submit();
                        }
                    }
                }
            }
            else{
                app.helper.hideModal();
                return true;
            }
        });
    },
    showPopupOnQuickCreate : function(obj,action_title,html){
        var target_on_quick_form = jQuery("#QuickCreate").find(obj);
        var template = '<div class="popover" role="tooltip" style="background: red">' +
            '<style>' +
            '.popover.bottom > .arrow:after{border-bottom-color:red;2px solid #ddd}' +
            '.popover-content{font-size: 11px}' +
            '.popover-title{background: red;text-align:center;color:#f4f12e;font-weight: bold;}' +
            '.popover-content ul{padding: 5px 5px 0 10px}' +
            '.popover-content li{list-style-type: none}' +
            '.popover{border: 2px solid #ddd;z-index:99999999;color: #fff;box-shadow: 0 0 6px #000; -moz-box-shadow: 0 0 6px #000;-webkit-box-shadow: 0 0 6px #000; -o-box-shadow: 0 0 6px #000;padding: 4px 10px 4px 10px;border-radius: 6px; -moz-border-radius: 6px; -webkit-border-radius: 6px; -o-border-radius: 6px;}' +
            '</style><div class="arrow">' +
            '</div>' +
            '<h3 class="popover-title"></h3><div class="popover-content"></div></div>';
        target_on_quick_form.popover({
            content: html,
            title:action_title,
            animation : true,
            placement: 'auto bottom',
            html: true,
            template:template,
            container: 'body',
            trigger: 'focus'
        });
        jQuery(obj).popover('show');
        jQuery('.popover').on('click',function () {
            jQuery(obj).popover('hide');
        });
    },
    checkOnDetail:function(moduleName,requestMode,record_id){
        var thisInstance = this;
        //to integrate with Custom View & Form
        if(moduleName == "CustomFormsViews"){
            var top_url = window.location.href.split('?');
            var array_url = thisInstance.getQueryParams(top_url[1]);
            moduleName = array_url.currentModule;
        }
        var params = {
            module : 'VTEConditionalAlerts',
            action : 'ActionAjax',
            mode : 'getConditionAlertForModule',
            async:false,
            current_module : moduleName,
            record_id:record_id
        };
        app.request.post({'data': params}).then(
            function(err,data){
                if(err === null) {
                    if (!jQuery.isEmptyObject(data)) {
                        var record_info = data.record_info;
                        var show_alert = false;
                        var list_action = [];
                        var addActions = function(data) {
                            var index = -1;
                            for(var i = 0; i < list_action.length; i++) {
                                if(list_action[i].action_title === data.action_title) {
                                    index = i;
                                }
                            }
                            if(index > -1) {
                                list_action[index] = data;
                            } else {
                                list_action.push(data)
                            }
                        }
                        jQuery('<input>').attr({
                            type: 'hidden',
                            id: 'hd_cap_info',
                            value:JSON.stringify(data.clf_info)
                        }).appendTo(jQuery('#detailView'));
                        jQuery.each(data.clf_info,function(k,v) {
                            var all_condition = v.condition.all;
                            var any_condition = v.condition.any;
                            var actions = v.actions;
                            var check_condition = thisInstance.checkConditionToForm(all_condition, any_condition, 'on_details',record_info);
                            if (check_condition) {
                                jQuery.each(actions, function (index, value) {
                                    if(actions.alert_when_open == 1){
                                        addActions(actions);
                                    }
                                });
                            }
                        });
                        if(list_action.length > 0){
                            if(jQuery('#hd_alerted').length == 0){
                                thisInstance.showPopupAlert(list_action,'detailView');
                                jQuery('<input>').attr({
                                    type: 'hidden',
                                    id: 'hd_alerted',
                                    value:"[]"
                                }).appendTo(jQuery('#page'));
                            }
                        }
                    }
                }
            }
        );
    },
    checkCondition: function(form_element_value,comparator,field_value,field_name_changed,field_name, old_value){
        var thisInstace = this;
        switch(comparator) {
            case 'is':
                return (form_element_value == field_value);
                break;
            case 'is not':
                return (form_element_value != field_value);
                break;
            case 'contains':
                form_element_value = form_element_value.toString();
                return ( form_element_value.indexOf(field_value) != -1 );
                break;
            case 'does not contain':
                form_element_value = form_element_value.toString();
                return ( form_element_value.indexOf(field_value) == -1 );
                break;
            case 'starts with':
                return (form_element_value.startsWith(field_value));
                break;
            case 'ends with':
                return (form_element_value.endsWith(field_value));
                break;
            case 'is empty':
                return (form_element_value == '');
                break;
            case 'is not empty':
                return (form_element_value != '');
                break;
            case 'has changed':
                return (form_element_value != old_value);
                break;
            case 'has changed to':
                return (form_element_value == field_value);
                break;
            case 'equal to':
                return (parseFloat(form_element_value) == parseFloat(field_value));
                break;
            case 'less than':
                return (parseFloat(form_element_value) < parseFloat(field_value));
                break;
            case 'greater than':
                return (parseFloat(form_element_value) > parseFloat(field_value));
                break;
            case 'does not equal':
                return (parseFloat(form_element_value) != parseFloat(field_value));
                break;
            case 'less than or equal to':
                return (parseFloat(form_element_value) <= parseFloat(field_value));
                break;
            case 'greater than or equal to':
                return (parseFloat(form_element_value) >= parseFloat(field_value));
                break;
            //date
            case 'between':
                var arr_date = field_value.split(",");
                return ((thisInstace.newDate(form_element_value) >= thisInstace.newDate(arr_date[0])) && (thisInstace.newDate(form_element_value) <= thisInstace.newDate(arr_date[1])));
                break;
            case 'before':
                return (thisInstace.newDate(form_element_value) < thisInstace.newDate(field_value));
                break;
            case 'after':
                return (thisInstace.newDate(form_element_value) > thisInstace.newDate(field_value));
                break;
            case 'is today':
                return (thisInstace.newDate(form_element_value) == thisInstace.newDate());
                break;
            case 'less than days ago':
                if(!form_element_value) return false;
                var num_day = parseInt(field_value);
                var date_inputed = thisInstace.newDate(form_element_value);
                var today = new Date();
                var date_check = new Date(today.getFullYear(), today.getMonth(), today.getDate() - num_day);
                return (date_inputed >= date_check);
                break;
            case 'more than days ago':
                if(!form_element_value) return false;
                var num_day = parseInt(field_value);
                var date_inputed = thisInstace.newDate(form_element_value);
                var today = new Date();
                var date_check = new Date(today.getFullYear(), today.getMonth(), today.getDate() + num_day);
                return (date_inputed >= date_check);
                break;
            case 'days ago':
                if(!form_element_value) return false;
                var num_day = parseInt(field_value);
                var date_inputed = thisInstace.newDate(form_element_value);
                var today = new Date();
                var date_check = new Date(today.getFullYear(), today.getMonth(), today.getDate() - num_day);
                return (date_inputed > date_check);
                break;
            case 'days later':
                if(!form_element_value) return false;
                var num_day = parseInt(field_value);
                var date_inputed = thisInstace.newDate(form_element_value);
                var today = new Date();
                var date_check = new Date(today.getFullYear(), today.getMonth(), today.getDate() + num_day);
                return (date_inputed > date_check);
                break;
            case 'in less than':
                return (thisInstace.newDate(form_element_value) <= thisInstace.newDate(field_value));
                break;
            case 'in more than':
                return (thisInstace.newDate(form_element_value) >= thisInstace.newDate(field_value));
                break;

        }
    },
    checkBelongToCondition:function(all_condition,any_condition,field_name_to_check){
        var is_belong = false;
        jQuery.each(all_condition,function(key,value){
            if(field_name_to_check == value.columnname) is_belong = true;
        });
        jQuery.each(any_condition,function(key,value){
            if(field_name_to_check == value.columnname) is_belong = true;
        });
        if(field_name_to_check == "on_details" || field_name_to_check == "reference_field") is_belong = true;
        return is_belong;
    },
    newDate:function(_date){
        if(typeof _date === 'undefined' || !(_date)){
            var new_date = new Date();
            _date = new_date.getFullYear() +'-'+(new_date.getMonth() + 1) + '-' + new_date.getDate() ;
        }
        var _format = jQuery('body').data('user-dateformat');
        if(typeof _format === 'undefined') _format = 'yyyy-mm-dd';
        var _delimiter = "-";
        var formatLowerCase=_format.toLowerCase();
        var formatItems=formatLowerCase.split(_delimiter);
        var dateItems=_date.toString().split(_delimiter);
        var monthIndex=formatItems.indexOf("mm");
        var dayIndex=formatItems.indexOf("dd");
        var yearIndex=formatItems.indexOf("yyyy");
        var month=parseInt(dateItems[monthIndex]);
        month-=1;
        return new Date(dateItems[yearIndex],month,dateItems[dayIndex]);
    },
    //this function to check condition from config to dispay control on form
    checkConditionToForm:function(all_condition,any_condition,field_name_changed,record_info,mode){
        var record_info_def = record_info;
        var thisInstance = this;
        var is_all = true;
        var is_any = false;
        //if(all_condition.length == 0) is_all = false;
        if(all_condition.length == 0 && any_condition.length > 0 ) is_all = false;
        jQuery.each(all_condition,function(key,value){
            var field_name = value.columnname;
            var field_value =  value.value;
            var comparator =  value.comparator;
            var main_form = jQuery('#EditView,#detailView');
            var form_element = main_form.find('[name="'+field_name+'"]');
            if(field_name_changed == 'on_details'){
                main_form = jQuery('#detailView');
                form_element = main_form.find('[data-name="'+field_name+'"]');
            }
            if(mode == 'QuickCreate'){
                main_form = jQuery('#QuickCreate');
                form_element = main_form.find('[name="'+field_name+'"]');
            }
            if(typeof form_element == 'undefined' && field_name == 'total'){
                form_element = jQuery('#EditView').find('[name="grandTotal"]');
            }
            //#4445446
            if(form_element.length == 0){
                form_element = jQuery('[data-name="' +field_name+ '"]');
                if(form_element.length > 0){
                    form_element.val(form_element.attr('data-value'));
                }else{
                    var moduleName = app.getModuleName();
                    var value = record_info_def[field_name];
                    $('#'+moduleName+'_detailView_fieldValue_'+field_name).append('<input type="hidden" class="fieldBasicData" data-name="'+field_name+'" data-type="string" data-displayvalue="'+value+'" data-value="'+value+'" value="'+value+'">')
                    form_element = jQuery('[data-name="' +field_name+ '"]');
                }
            }
            //#4445446 end
            var form_element_value = form_element.val();
            if(field_name_changed == 'on_details') {
                form_element_value = form_element.data("value");
            }
            if(field_name_changed == 'reference_field' && form_element_value =='0') {
                form_element_value = '';
            }
            if(typeof form_element_value == 'undefined' && !jQuery.isEmptyObject(record_info)){
                form_element_value = record_info[field_name];
            }
            //for Multiple Value control
            if(form_element.attr('type') == 'hidden'){
                form_element = form_element.next();
                if(!form_element.is('input')){
                    form_element = form_element.find('select');
                    if(form_element.val()) form_element_value = form_element.val();
                }
                else{
                    if(form_element.attr('type') == 'checkbox'){
                        if (form_element.is(":checked"))
                        {
                            form_element_value = 1;
                        }
                        else{
                            form_element_value = 0;
                        }

                    }
                }
            }
            if(form_element.length == 0 && typeof form_element_value === 'undefined'){
                is_all = false;
                return;
            }
            var old_value = record_info[field_name]
            if (mode == 'QuickCreate' && field_name_changed == undefined) {
                old_value = '';
            } 
            var result = thisInstance.checkCondition(form_element_value,comparator,field_value,field_name_changed,field_name, old_value);
            if(!result){
                is_all = false;
                return false;
            }
        });
        jQuery.each(any_condition,function(key,value){
            var field_name = value.columnname;
            var field_value =  value.value;
            var comparator =  value.comparator;
            var form_element = jQuery('#EditView').find('[name="'+field_name+'"]');
            if(field_name_changed == 'on_details'){
                form_element = jQuery('#detailView').find('[name="'+field_name+'"]');
            }
            if(typeof form_element == 'undefined' && field_name == 'total'){
                form_element = jQuery('#detailView').find('[name="grandTotal"]');
            }
            //#4445446
            if(!form_element.length){
                form_element = jQuery('[data-name="' +field_name+ '"]');
                if(form_element.length > 0){
                    form_element.val(form_element.attr('data-value'));
                }else{
                    console.log(record_info);
                    var moduleName = app.getModuleName();
                    var value = record_info_def[field_name];
                    $('#'+moduleName+'_detailView_fieldValue_'+field_name).append('<input type="hidden" class="fieldBasicData" data-name="'+field_name+'" data-type="string" data-displayvalue="'+value+'" data-value="'+value+'" value="'+value+'">')
                    form_element = jQuery('[data-name="' +field_name+ '"]');
                }

            }
            //#4445446 end
            if(form_element.length == 0){
                is_any = false;
                return;
            }
            var form_element_value = form_element.val();
            if(typeof form_element_value == 'undefined' && field_name_changed == 'on_details'){
                var record_info = thisInstance.getRecordIdAndModule();
                if(typeof thisInstance.fieldValuesCache[field_name] == 'undefined') {
                    jQuery.each(record_info,function(key,value){
                        if(key == field_name) form_element_value = value;
                    });
                    thisInstance.fieldValuesCache[field_name] = form_element_value;
                }else{
                    form_element_value = thisInstance.fieldValuesCache[field_name];
                }
            }
            //for Multiple Value control
            if(form_element.attr('type') == 'hidden'){
                form_element = form_element.next();
                if(!form_element.is('input')){
                    form_element = form_element.find('select');
                    if(form_element.val()) form_element_value = form_element.val();
                }
                else{
                    if(form_element.attr('type') == 'checkbox'){
                        if (form_element.is(":checked"))
                        {
                            form_element_value = 1;
                        }
                        else{
                            form_element_value = 0;
                        }

                    }
                }
            }
            var result = thisInstance.checkCondition(form_element_value,comparator,field_value,field_name_changed,field_name);
            if(result){
                is_any = true;

            }
        });
        return is_all || is_any;
    },
    registerFormChange:function(module){
        var thisInstance = this;
        jQuery("#EditView").on("change","input,select,textarea", function () {
            var field_name = jQuery(this).attr('name');
            thisInstance.checkOnEdit(module,field_name);
        });
    },

    registerFieldChangeOnDetail:function(module){
        var thisInstance = this;
        jQuery("#detailView").on("change","input,select,textarea", function () {
            var field_name = jQuery(this).attr('name');
            thisInstance.checkFieldChangeOnDetail(module,field_name);
        });
        //jQuery("#detailView").on("click",".inlineAjaxSave", function (e) {
        //    e.preventDefault();
        //    e.stopPropagation();
        //    var thisButon = jQuery(e.currentTarget);
        //    var field_name = jQuery(this).closest('div').prev('input.inputElement').attr('name');
        //    thisInstance.regSaveFieldOnDetail(module,field_name,thisButon);
        //});
    },
    registerFieldChangeOnEdit:function(module){
        var thisInstance = this;
        jQuery("#EditView").on("change","input,textarea", function (e) {
            var ele = e.currentTarget;
            var fieldType = jQuery(ele).data('fieldtype');
            var eleValue = jQuery(ele).val();
            if(eleValue.length>0) {
                if(typeof fieldType !='undefined' && fieldType=='reference'){
                }else if (jQuery(ele).hasClass('sourceField')) {
                    module = app.getModuleName();
                    var related_module = jQuery(ele).closest('td').find('input[name="popupReferenceModule"]').val()
                    thisInstance.registerGetReferenceEvent(module, related_module, eleValue);
                } else {
                    thisInstance.loadAlertPopupConfigForDetail(module);
                }
            }
        });
        jQuery("#EditView").on("change","select", function (e) {
            if(e.val){
                thisInstance.loadAlertPopupConfigForDetail(module);
            }

        });
    },
    registerQuickCreateSubmit:function(module){
        var thisInstance = this;
        var editForm = jQuery("#QuickCreate");
        editForm.on("click","[name='saveButton']", function (e) {
            var valid = true;
            e.preventDefault();
            e.stopPropagation();
            jQuery('.popover').popover('hide');
            if(module == "CustomFormsViews"){
                var top_url = window.location.href.split('?');
                var array_url = thisInstance.getQueryParams(top_url[1]);
                module = array_url.currentModule;
            }
            var params = {
                module : 'VTEConditionalAlerts',
                action : 'ActionAjax',
                async:false,
                mode : 'getConditionAlertForModule',
                current_module : module
            };
            app.request.post({'data': params}).then(
                function(err,data){
                    if(err === null) {
                        if(!jQuery.isEmptyObject(data)){
                            jQuery.each(data.clf_info,function(k,v){
                                var all_condition = v.condition.all;
                                var any_condition = v.condition.any;
                                var actions = v.actions;
                                editForm.find("input,select,textarea").each(function(){
                                    var field_name = jQuery(this).attr('name');
                                    if(thisInstance.checkBelongToCondition(all_condition,any_condition,field_name)){
                                        var check_condition = thisInstance.checkConditionToForm(all_condition,any_condition,field_name,'');
                                        if(check_condition){
                                            if(actions.alert_on_save == 1){
                                                var html =      '<span>'+actions.description+'</span>';
                                                thisInstance.showPopupOnQuickCreate('[name="'+field_name+'"]',actions.action_title,html);
                                            }
                                            if(actions.donot_allow_to_save == 1){
                                                valid = false;
                                                return false;
                                            }
                                        }
                                    }
                                });
                            });
                            if(valid){
                                jQuery('.popover').popover('hide');
                                editForm.submit();
                            }
                        }
                    }
                }
            );
        });
    },
    /*registerFormSubmit:function(module){
     window.onbeforeunload = null;
     var thisInstance = this;
     var editForm = jQuery("#EditView");
     var alert_actions = {};
     editForm.unbind("submit");
     editForm.on("submit", function (e) {
     e.preventDefault();
     e.stopPropagation();
     //to integrate with Custom View & Form
     var self = this;
     var valid = true;
     var list_action = [];
     var addActions = function(data) {
     var index = -1;
     for(var i = 0; i < list_action.length; i++) {
     if(list_action[i].action_title === data.action_title) {
     index = i;
     }
     }
     if(index > -1) {
     list_action[index] = data;
     } else {
     list_action.push(data)
     }
     }
     if(module == "CustomFormsViews"){
     var top_url = window.location.href.split('?');
     var array_url = thisInstance.getQueryParams(top_url[1]);
     module = array_url.currentModule;
     }
     var params = {
     module : 'VTEConditionalAlerts',
     action : 'ActionAjax',
     async:false,
     mode : 'getConditionAlertForModule',
     current_module : module,
     record_id : jQuery("input[name='record']").val()
     };
     app.request.post({'data': params}).then(
     function(err,data){
     if(err === null) {
     if(!jQuery.isEmptyObject(data)){
     jQuery.each(data.clf_info,function(k,v){
     var all_condition = v.condition.all;
     var any_condition = v.condition.any;
     var actions = v.actions;
     editForm.find("input,select,textarea").each(function(){
     var field_name = jQuery(this).attr('name');
     if(jQuery(this).hasClass('sourceField')) field_name = "reference_field";
     var hd_record_info = jQuery("#hd_record_info").val();
     if(thisInstance.checkBelongToCondition(all_condition,any_condition,field_name)){
     var check_condition = thisInstance.checkConditionToForm(all_condition,any_condition,field_name,JSON.parse(hd_record_info));
     if(check_condition){
     if(actions.alert_on_save == 1){
     valid = false;
     addActions(actions);
     }
     if(actions.donot_allow_to_save == 1){
     valid = false;
     }
     }
     }
     });
     });
     }
     if(list_action.length > 0){
     thisInstance.showPopupAlert(list_action,self);
     }
     if(valid){
     var num_of_error_field = 0;
     jQuery.each(jQuery(self).find('.input-error'),function(){
     if(jQuery(this).hasClass('select2-display-none')){
     return;
     } else {
     if (jQuery(this).closest(".input-error").length == 0){
     num_of_error_field++;
     }
     }
     });
     if(num_of_error_field == 0) {
     if(app.getModuleName()=='Invoice' || app.getModuleName()=='PurchaseOrder' || app.getModuleName()=='SalesOrder' || app.getModuleName()=='Quotes'){
     var inventoryEditInstance = new Inventory_Edit_Js();
     inventoryEditInstance.updateLineItemElementByOrder();
     var taxMode = inventoryEditInstance.isIndividualTaxMode();
     var elementsList = inventoryEditInstance.lineItemsHolder.find('.'+inventoryEditInstance.lineItemDetectingClass);
     inventoryEditInstance.saveProductCount();
     inventoryEditInstance.saveSubTotalValue();
     inventoryEditInstance.saveTotalValue();
     inventoryEditInstance.savePreTaxTotalValue();
     }
     self.submit();
     }
     }
     else {
     jQuery("#EditView").find('.saveButton').removeAttr('disabled');
     }
     }
     }
     );
     });
     },*/
    getRecordIdAndModule: function(){
        var return_arr = [];
        var url = window.location.href.split('?');
        var array_url = this.getQueryParams(url[1]);
        return_arr.push(array_url.module);
        return_arr.push(array_url.record);
        return return_arr;
    },
    getQueryParams:function(qs) {
        if(typeof(qs) != 'undefined' ){
            qs = qs.toString().split('+').join(' ');
            var params = {},
                tokens,
                re = /[?&]?([^=]+)=([^&]*)/g;
            while (tokens = re.exec(qs)) {
                params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
            }
            return params;
        }
    },

    /*
     * Function to register the list view row click event
     */
    registerRowClickEvent: function(){
        var listViewContentDiv = jQuery('.listViewEntriesTable');
        listViewContentDiv.on('click','.listViewEntryValue',function(e){
            var editUrl = jQuery(this).closest('tr').data('recordurl');
            window.location.href = editUrl;
        });
    },
    registerPostReferenceEvent : function(container) {
        var thisInstance = this;
        var module = app.getModuleName();
        var eleSourceField = container.find('.sourceField');
        jQuery.each(eleSourceField, function (i, ele) {
            jQuery(ele).on(Vtiger_Edit_Js.popupSelectionEvent, function (e, data) {
                thisInstance.message_on_edit = [];
                // { source_module: "Accounts", record: "931", selectedName: "Organization 16" }
                var related_module = data.source_module;
                var record_id = data.record;
                var params = {
                    module: 'VTEConditionalAlerts',
                    action: 'ActionAjax',
                    async: false,
                    mode: 'getFieldValueOnConditionAlert',
                    current_module: module,
                    related_module: related_module,
                    record_id: record_id
                };
                app.request.post({'data': params}).then(
                    function (err, data) {
                        if (err === null) {
                            var hd_record_info = jQuery("#hd_record_info").val();
                            var record_info = JSON.parse(hd_record_info);
                            jQuery.each(data, function (id, value) {
                                jQuery.each(record_info, function (ri_id, ri_value) {
                                    if (ri_id == id) {
                                        value = $("<div/>").html(value).text();
                                        record_info[ri_id] = value;
                                    }
                                });
                            });
                            jQuery("#hd_record_info").val(JSON.stringify(record_info));
                            setTimeout(function () {
                                thisInstance.checkOnEdit(module, 'on_details');
                                if (thisInstance.message_on_edit.length > 0) {
                                    thisInstance.showPopupAlert(thisInstance.message_on_edit);
                                    thisInstance.message_on_edit = [];
                                    return false;
                                }
                            },10);
                        }
                    }
                )
            });
        });
    },
    registerFillReferenceEvent : function(container) {
        var thisInstance = this;
        var module = app.getModuleName();
        var eleSourceField = container.find('.sourceField');
        jQuery.each(eleSourceField, function (i, ele) {
            var referenceName = jQuery(ele).attr('name');
            var url_params = app.convertUrlToDataParams(window.location.href);
            var record = url_params[referenceName];
            var source_module = url_params.returnmodule;
            if(record != undefined && record > 0) {
                thisInstance.registerGetReferenceEvent(module, source_module, record);
            }
        });
    },
    registerGetReferenceEvent : function(sourceModule, relatedModule, recordId) {
        var thisInstance = this;
        if(recordId != undefined && recordId > 0) {
            thisInstance.message_on_edit = [];
            var params = {
                module: 'VTEConditionalAlerts',
                action: 'ActionAjax',
                async: false,
                mode: 'getFieldValueOnConditionAlert',
                current_module: sourceModule,
                related_module: relatedModule,
                record_id: recordId
            };
            app.request.post({'data': params}).then(
                function (err, data) {
                    if (err === null) {
                        var hd_record_info = jQuery("#hd_record_info").val();
                        var record_info = JSON.parse(hd_record_info);
                        jQuery.each(data, function (id, value) {
                            jQuery.each(record_info, function (ri_id, ri_value) {
                                if (ri_id == id) {
                                    record_info[ri_id] = value;
                                }
                            });
                        });
                        jQuery("#hd_record_info").val(JSON.stringify(record_info));
                        setTimeout(function () {
                            thisInstance.checkOnEdit(sourceModule, 'on_details');
                            if (thisInstance.message_on_edit.length > 0) {
                                thisInstance.showPopupAlert(thisInstance.message_on_edit);
                                thisInstance.message_on_edit = [];
                                return false;
                            }
                        }, 10);
                    }
                }
            )
        }
    },
    registerEvents : function(){
        this.registerModuleFilterChange();
        this.registerPagingAction();
        this.registerDeleteAction();
        this.registerEditAction();
    }

});
//On Page Load
jQuery(document).ready(function() {
    setTimeout(function () {
        initData_VTEConditionalAlerts();
    }, 2000);
});
function initData_VTEConditionalAlerts() {
    // Only load when loadHeaderScript=1 BEGIN #241208
    if (typeof VTECheckLoadHeaderScript == 'function') {
        if (!VTECheckLoadHeaderScript('VTEConditionalAlerts')) {
            return;
        }
    }
    // Only load when loadHeaderScript=1 END #241208

    var capInstance = new VTE_Conditional_Alerts_Js();
    capInstance.registerEvents();
    var view = app.view();
    var module = app.getModuleName();
    if(view == 'Edit' && module != 'VTEConditionalAlerts'){
        capInstance.loadAlertPopupConfigForDetail(module);
        capInstance.registerFormChange(module);
        //capInstance.registerFormSubmit(module);
        capInstance.registerFieldChangeOnEdit(module);
        capInstance.registerPostReferenceEvent(jQuery('#EditView'));
        if (typeof FieldAutofill_Js !== 'undefined') {
            setTimeout(function () {
                capInstance.registerFillReferenceEvent(jQuery('#EditView'));
            },1000);
        }
    }
    if(view == 'Detail'){
        var url = window.location.href.split('?');
        var array_url = capInstance.getQueryParams(url[1]);
        if(typeof array_url == 'undefined') return false;
        var request_mode = array_url.requestMode;
        var record_id = jQuery('#recordId').val();
        capInstance.checkOnDetail(module,request_mode,record_id);
        capInstance.registerFieldChangeOnDetail(module);
    }
}
// Listen post ajax event for add product action
jQuery( document ).ajaxComplete(function(event, xhr, settings) {
    var url = settings.data;
    if(typeof url == 'undefined' && settings.url) url = settings.url;
    var instance = new VTE_Conditional_Alerts_Js();
    var top_url = window.location.href.split('?');
    var array_url = instance.getQueryParams(top_url[1]);
    var ajax_url = instance.getQueryParams(url);
    if(typeof array_url == 'undefined' && typeof ajax_url == 'undefined' ) return false;
    //Add Alert for QuickCreate
    if(ajax_url.view == "QuickCreateAjax"){
        var qcModule = ajax_url.module;
        jQuery("#QuickCreate").on("change","input,select,textarea", function () {
            jQuery('#hd_alerted').remove();
            var field_name = jQuery(this).attr('name');
            instance.checkOnEdit(qcModule,field_name);
            instance.checkOnQuickCreate(qcModule, field_name);
        });
        instance.registerQuickCreateSubmit(qcModule);
        instance.checkOnQuickCreate(qcModule);
    }
    if (typeof FieldAutofill_Js !== 'undefined') {
        if(ajax_url.module == "VTEConditionalAlerts" && ajax_url.action == "ActionAjax" && ajax_url.mode == "getFieldValueOnConditionAlert") {
            setTimeout(function () {
                var view = app.view();
                var module = app.getModuleName();
                if (view == 'Edit' && module != 'VTEConditionalAlerts' && jQuery('#btnClosePopupAlert').length==0) {
                    instance.loadAlertPopupConfigForDetail(module);
                }
            }, 1000);
        }
    }

});
(function ( $ ) {

    $.fn.removeClassExtend = function ( removals, additions ) {

        var self = this;

        if ( removals.indexOf( '*' ) === -1 ) {
            // Use native jQuery methods if there is no wildcard matching
            self.removeClass( removals );
            return !additions ? self : self.addClass( additions );
        }

        var patt = new RegExp( '\\s' +
        removals.
            replace( /\*/g, '[A-Za-z0-9-_]+' ).
            split( ' ' ).
            join( '\\s|\\s' ) +
        '\\s', 'g' );

        self.each( function ( i, it ) {
            var cn = ' ' + it.className + ' ';
            while ( patt.test( cn ) ) {
                cn = cn.replace( patt, ' ' );
            }
            it.className = $.trim( cn );
        });

        return !additions ? self : self.addClass( additions );
    };

})( jQuery );