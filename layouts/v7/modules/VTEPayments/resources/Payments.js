/* ********************************************************************************
 * The content of this file is subject to the VTEPayments("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

var Payment_Index_Js = {

	registerAddButtonPayment : function() {
        if(app.getModuleName()=='Invoice' && app.getViewName()=='Detail'){
            var spanBtnEdit = jQuery('#Invoice_detailView_basicAction_LBL_EDIT').parent();
            spanBtnEdit.parent().append('<span class="btn-group"><button class="btn btn-success" name="btnPayments" id="btnPayment"><strong>Payments</strong></button></span>');
        }
	},

    registerShowPaymentsForm: function () {
        var thisInstance=this;
        jQuery('.btn-group').on("click",'#btnPayment', function(e) {
            var invoiceid = jQuery('#recordId').val();
            var url='index.php?module=VTEPayments&view=ManagePayments&invoiceid='+invoiceid
            thisInstance.showEditView(url);
        });
    },
    showEditView : function(url) {
        var thisInstance = this;
        app.helper.showProgress();
        var actionParams = {
            "url":url
        };
        app.request.post(actionParams).then(
            function (err, data) {
                if(err === null) {
                    app.helper.hideProgress();
                    var callBackFunction = function(data) {
                        var form = jQuery('#EditView');
                        var params = app.validationEngineOptions;
                        params.onValidationComplete = function(form, valid){
                            if(valid) {
                                var type_cb = jQuery("select[name='payment_type']");
                                var fieldTypeRequired = type_cb.data('rule-required');
                                if(fieldTypeRequired){
                                    var old_type= type_cb.val();
                                    if(old_type.length==0) {
                                        form.find("select[name='payment_type']").validationEngine('showPrompt', app.vtranslate('JS_REQUIRED_FIELD'), 'error', 'topLeft', true);
                                        return false;
                                    }else {
                                        form.find("select[name='payment_type']").validationEngine('hide');
                                    }
                                }

                                var status_cb = jQuery("select[name='payment_status']");
                                var fieldStatusRequired = status_cb.data('rule-required');
                                if(fieldStatusRequired){
                                    var old_status = status_cb.val();
                                    if(old_status.length==0) {
                                        form.find("select[name='payment_status']").validationEngine('showPrompt', app.vtranslate('JS_REQUIRED_FIELD'), 'error', 'topLeft', true);
                                        return false;
                                    }else {
                                        form.find("select[name='payment_status']").validationEngine('hide');
                                    }
                                }
                                thisInstance.ajaxSavePayment(form);
                                return valid;
                            }
                        }
                        form.validationEngine(params);
                        //thisInstance.saveFolder(form);
                        form.submit(function(e) {
                            e.preventDefault();
                        })
                    }
                    app.helper.showModal(data, {'cb' : function (data){
                        if(typeof callBackFunction == 'function'){
                            callBackFunction(data);
                        }
                        var form = jQuery('#EditView');
                        thisInstance.registerEditOnPopupPayment();
                        thisInstance.registerDelOnPopupPayment();
                        thisInstance.registerANChargingOnPopupPayment();
                        thisInstance.registerShowANTransactions();
                        thisInstance.registerBalanceLinkClick();
                        thisInstance.registerDateControl();
                        thisInstance.registerBlockAnimationEvent(form);
                        var editInstance = Vtiger_Edit_Js.getInstance();
                        editInstance.registerAutoCompleteFields(form);
                        editInstance.registerClearReferenceSelectionEvent(form);
                        editInstance.referenceModulePopupRegisterEvent(form);
                        var status_cb = jQuery("select[name='payment_status']");
                        /*var old_status = status_cb.val();
                        if(old_status == ''){
                            status_cb.val(app.vtranslate('Paid'));
                            status_cb.trigger('change');
                            status_cb.trigger('liszt:updated');
                        }*/
                        var type_cb = jQuery("select[name='payment_type']");
                        /*var old_type= type_cb.val();
                        if(old_type == ''){
                            type_cb.val(app.vtranslate('Check'));
                            type_cb.trigger('change');
                            type_cb.trigger('liszt:updated');
                        }*/

                        var invoice_input = jQuery('#invoice_display');
                        var invoice_input_div = invoice_input.closest('div');
                        invoice_input_div.find('span:first').remove();
                        invoice_input_div.find('span:last').remove();
                        // invoice_input.css('width','92%');
                        jQuery('.showInlineTable:last').find('tbody').hide();
                        thisInstance.registerAmountTooltip();
                    }, 'backdrop': true});
                }
                else {
                    app.helper.hideProgress();
                }
            }
        );
    },
    registerDateControl : function() {
        var date_control =  jQuery("#VTEPayments_editView_fieldName_date");
        var date_format = date_control.data('date-format');
        var today = new Date();
        var formatted = app.getDateInVtigerFormat(date_format,today);
        date_control.val( formatted);
    },
    registerAmountTooltip : function(){
        var options = {
            content: function(){
                return jQuery('#popover-content').html();
            },
            html: true,
            placement: 'bottom',
            trigger:'click'
        };
        jQuery(".lnk-popover").popover(options);
        jQuery('html').on('click', function(e) {
            if (typeof jQuery(e.target).data('original-title') == 'undefined' &&
                !jQuery(e.target).parents().is('.popover.in')) {
                jQuery('[data-original-title]').popover('hide');
            }
        });

        jQuery('html').on('change','.amount_pre_paid', function(e) {
            var target_ctrl = jQuery(e.target);
            var this_val = target_ctrl.val();
            var balance = jQuery('#balance_link').html();
            var cal_to_val = '';
            if(target_ctrl.attr('name') == 'total_pre_paid'){
                var total = target_ctrl.data('total');
                cal_to_val = parseFloat(total) * parseFloat(this_val)/100;
            }
            if(target_ctrl.attr('name') == 'balance_pre_paid'){
                var balance = target_ctrl.data('balance');
                cal_to_val = parseFloat(balance) * parseFloat(this_val)/100;
            }
            var pre_div = target_ctrl.closest('div').prev();
            pre_div.children('span').html(cal_to_val.toFixed(2));
        });
        jQuery('html').on('click','.val_will_paid', function(e) {
            var target_ctrl = jQuery(e.target);
            var this_val = target_ctrl.html();
            jQuery('#VTEPayments_editView_fieldName_amount_paid').val(this_val);
            jQuery('[data-original-title]').popover('hide');
        });
        var themes_color = jQuery('#searchIcon').css("background-color");
        jQuery('.tbl-tooltip').css("background-color",themes_color);
    },
    registerBlockAnimationEvent : function(detailContentsHolder){
        //var detailContentsHolder = jQuery('');
        detailContentsHolder.on('click','.blockToggle',function(e){
            var currentTarget =  jQuery(e.currentTarget);
            var blockId = currentTarget.data('id');
            var closestBlock = currentTarget.closest('.showInlineTable');
            var bodyContents = closestBlock.find('tbody');
            var data = currentTarget.data();
            var module = app.getModuleName();
            var hideHandler = function() {
                bodyContents.hide('slow');
                // app.cacheSet(module+'.'+blockId, 0)
            }
            var showHandler = function() {
                bodyContents.show();
                // app.cacheSet(module+'.'+blockId, 1)
            }
            var data = currentTarget.data();
            if(data.mode == 'show'){
                hideHandler();
                currentTarget.hide();
                closestBlock.find("[data-mode='show']").removeClass("hide");
                closestBlock.find("[data-mode='hide']").show();
            }else{
                showHandler();
                currentTarget.hide();
                closestBlock.find("[data-mode='show']").removeClass("hide");
                closestBlock.find("[data-mode='show']").show();
            }
        });

    },
    ajaxSavePayment:function(form) {
        app.helper.showProgress();
        var thisInstance = this;
        var actionParams = {
            "data": form.serializeFormData()
        };
        actionParams.data.mode = 'VTEPayments';
        actionParams.data.action = 'SaveAjax';
        app.request.post(actionParams).then(
            function (err, data) {
                if(err === null) {
                    app.helper.hideProgress();
                   thisInstance.loadListPayments();
                   thisInstance.updateGrandTotal();
                   thisInstance.registerUpdateTooltipArea();
                   thisInstance.resetForm(form);
                   var m_params = {
                       message: app.vtranslate('Payments saved')
                   }
                    app.helper.showSuccessNotification(m_params);
                }
                else {
                    app.helper.hideProgress();
                }
            }
        );
		return false;
    },
    registerEditOnPopupPayment:function(){
        var thisInstance=this;
        jQuery('.edit-payment').on('click',function(){
            app.helper.showProgress();
            var payment_id = jQuery(this).closest('tr').data('id');
            var url = 'index.php?module=VTEPayments&view=EditPayment&payment_id=' + payment_id;
            var actionParams = {
                "url":url
            };

            app.request.post(actionParams).then(
                function (err, data) {
                    if(err === null) {
                        // var response = jQuery.parseJSON(data);
                        // var results = jQuery.parseJSON(response.result);
                        var results = jQuery.parseJSON(data);
                        jQuery.each(results,function(name, value){
                            if(name == 'contact' && value != 0){
                                var contact_id = results.contact;
                                var contact_name = results.contact_name;
                                var selectedItemData = {'id':contact_id,'label':contact_name,'name':contact_name,'value':contact_name};
                                var element = jQuery("input[name='contact_display']");
                                var tdElement = element.closest('td');
                                var editInstance = Vtiger_Edit_Js.getInstance();
                                editInstance.setReferenceFieldValue(tdElement, selectedItemData);
                            }
                            else if(name == 'organization' && value != 0){
                                var account_id = results.organization;
                                var account_name = results.account_name;
                                var selectedItemData = {'id':account_id,'label':account_name,'name':account_name,'value':account_name};
                                var element = jQuery("input[name='organization_display']");
                                var tdElement = element.closest('td');
                                var editInstance = Vtiger_Edit_Js.getInstance();
                                editInstance.setReferenceFieldValue(tdElement, selectedItemData);
                                jQuery("input[name='organization']").val(account_id);
                            }else if(name == 'assigned_user_id' && value !='' ){
                                $('[name="assigned_user_id"]').val(value);
                            }
                            else{
                                jQuery( "[name='"+name+"']").val(value);
                            }
                        });
                        jQuery("select").trigger('liszt:updated');
                        jQuery("select").trigger('change');
                        jQuery("#PaymentInfo").scrollTo(0);
                        jQuery("input[name='record']").val(payment_id);
                        app.helper.hideProgress();
                    }
                    else {
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },
    registerDelOnPopupPayment: function (paymentid) {
        var thisInstance=this;
        jQuery('.relationDelete').on("click",function(e) {
            app.helper.showProgress();
            var payment_id = jQuery(this).closest('tr').data('id');
            var url = 'index.php?module=VTEPayments&action=DeleteAjax&record=' + payment_id;
            var actionParams = {
                "url":url
            };

            app.request.post(actionParams).then(
                function (err, data) {
                    app.helper.hideProgress();
                    if(err === null) {
                        thisInstance.loadListPayments();
                        thisInstance.updateGrandTotal();
                        thisInstance.registerUpdateTooltipArea();
                        app.helper.hideProgress();
                        var m_params = {
                            message: app.vtranslate('Payment has been removed'),
                        }
                        app.helper.showSuccessNotification(m_params);
                    }
                    else {
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },
    updateBalance:function(){
        var balance  = 0;
        jQuery('table.tblPaymentListView tr.listViewEntries').each(function(){
            var td_val = jQuery(this).find('.amount_paid');
            balance+= parseFloat(td_val.text());
        });
        jQuery('.pmBalance').html(balance.toFixed(2));
    },
    loadListPayments:function(){
        var thisInstance = this;
        app.helper.showProgress();
        var invoice_id =  jQuery('#recordId').val();
        var url = 'index.php?module=VTEPayments&view=ListPayments&invoice_id=' + invoice_id;
        var actionParams = {
            "url":url,
        };

        app.request.post(actionParams).then(
            function (err, data) {
                app.helper.hideProgress();
                if(err === null) {
                   jQuery('.ListPayments').html(data);
                   thisInstance.registerEditOnPopupPayment();
                   thisInstance.registerDelOnPopupPayment();
                   thisInstance.registerANChargingOnPopupPayment();
                   thisInstance.registerShowANTransactions();
                }
                else {
                    app.helper.hideProgress();
                }
            }
        );
    },
    updateGrandTotal:function(){
        var invoice_id = jQuery('#recordId').val();
        var total_amount = 0;
        var url = 'index.php?module=VTEPayments&action=Ajax&mode=updateGrandTotal';
        var actionParams = {
            "data" : {'total_amount':total_amount,'invoice_id':invoice_id,
                'module': 'VTEPayments',
                'action': 'Ajax',
                'mode': 'updateGrandTotal'}
        };

        app.request.post(actionParams).then(
            function (err, data) {
                if(err === null) {
                    //var total = jQuery("#pmInvoiceTotal").text();
                    var total =data.total;
                    var total_amount = data.total_amount;
                    //total_amount = parseFloat(data.total_amount);
                    //var balance = total - total_amount;
                    var balance = data.balance;
                    jQuery('.balance_link').html(balance);
                    jQuery('#pmReceived').html(total_amount);
                    var invoice_detail_table = jQuery('#detailView').find('table:last');
                    var invoice_detail_tr = invoice_detail_table.find('tr:last');
                    var invoice_detail_balance = invoice_detail_tr.find('td:last');
                    invoice_detail_balance.html('<span class="pull-right"> '+balance+' </span>');
                    var invoice_detail_received = invoice_detail_tr.prev().find('td:last');
                    invoice_detail_received.html('<span class="pull-right"> '+total_amount+' </span>');
                }
                else {
                    app.helper.hideProgress();
                }
            }
        );

    },
    resetForm:function(form){
        form.trigger('reset');
        jQuery("input[name='record']").val('');
        var status_cb = jQuery("select[name='payment_status']", form);
        status_cb.val('');
        var status_type = jQuery("select[name='payment_type']", form);
        status_type.val('');
        vtUtils.applyFieldElementsView(form);
        this.registerDateControl();
    },
    registerBalanceLinkClick: function(){
        jQuery('#balance_link').on('click',function(){
            var last_pm_id = jQuery('.tblPaymentListView tbody').children('tr:first').data('id');
            var balance = jQuery(this).html();
            if(typeof last_pm_id != 'undefined'){
                app.helper.showProgress();
                var url = 'index.php?module=VTEPayments&view=EditPayment&payment_id=' + last_pm_id;
                var actionParams = {
                    "url":url
                };

                app.request.post(actionParams).then(
                    function (err, data) {
                        if(err === null) {
                            // var response = jQuery.parseJSON(data);
                            // var results = jQuery.parseJSON(response.result);
                            var results = jQuery.parseJSON(data);
                            jQuery.each(results, function(name, value){
                                jQuery( "[name='"+name+"']").val(value);
                            });
                            jQuery("select").trigger('liszt:updated');
                            jQuery("#PaymentInfo").scrollTo(0);
                            jQuery("input[name='record']").val('');
                            jQuery( "input[name='amount_paid']").val(balance);
                            app.helper.hideProgress();
                        }
                        else {
                            app.helper.hideProgress();
                        }
                    }
                );
            }
            else{
                jQuery( "input[name='amount_paid']").val(balance);
            }
        });
    },
    registerUpdateTooltipArea: function(){
        var invoice_id = jQuery('#recordId').val();
        if(invoice_id){
            app.helper.showProgress();
            var url = 'index.php?module=VTEPayments&action=Ajax&mode=updateTooltipArea&invoice_id=' + invoice_id;
            var actionParams = {
                "url":url
            };

            app.request.post(actionParams).then(
                function (err, data) {
                    app.helper.hideProgress();
                    if(err === null) {
                        jQuery("#popover-content").html(data);
                        var themes_color = jQuery('#searchIcon').css("background-color");
                        jQuery('.tbl-tooltip').css("background-color",themes_color);
                    }
                    else {
                        app.helper.hideProgress();
                    }
                }
            );
        }
    },
    registerRowClick: function () {
        var thisInstance=this;
        jQuery('html').on('click','.payment-row td:not(:last-child)',function () {
            var parent_tr = jQuery(this).closest('tr');
            if(!parent_tr.hasClass('has-anet-transaction')){
                var edit_link = parent_tr.find('.edit-payment');
                edit_link.trigger('click');
            }
        });
    },
    registerANChargingOnPopupPayment:function(){
        var thisInstance=this;
        if($('#PaymentInfo .an-charging').length){
            jQuery('#PaymentInfo .an-charging').unbind('click').on('click',function(event){
                event.preventDefault();
                app.helper.hideModal();
                app.helper.showProgress();
                var payment_id = $(this).data('record');
                var url = 'index.php?module=ANTransactions&view=Charging&mode=chargingMethod&payment_id=' + payment_id;
                var actionParams = {
                    "url":url
                };
                app.request.post(actionParams).then(
                    function (err, data) {
                        if(err === null) {
                            app.helper.hideProgress();
                            app.helper.showModal(data, {'cb' : function (container){
                                $(container).find('.cancelLink').on('click', function(){
                                    app.helper.hideModal();
                                    $('#btnPayment').trigger('click');
                                });
                                $(container).find('.close').on('click', function(){
                                    app.helper.hideModal();
                                    $('#btnPayment').trigger('click');
                                });
                            }});
                        }
                        else {
                            app.helper.hideProgress();
                        }
                    }
                );
            });
        }
    },
    /*registerANChargingOnPopupPayment:function(){
        var thisInstance=this;
        if($('#PaymentInfo .an-charging').length){
            jQuery('#PaymentInfo .an-charging').unbind('click').on('click',function(event){
                event.preventDefault();
                app.helper.hideModal();
                app.helper.showProgress();
                var payment_id = $(this).data('record');
                var url = 'index.php?module=ANTransactions&view=Charging&payment_id=' + payment_id;
                var actionParams = {
                    "url":url
                };
                app.request.post(actionParams).then(
                    function (err, data) {
                        if(err === null) {
                            app.helper.hideProgress();
                            app.helper.showModal(data, {'cb' : function (container){
                                $(container).find('.cancelLink').on('click', function(){
                                    app.helper.hideModal();
                                    $('#btnPayment').trigger('click');
                                });
                                $(container).find('.close').on('click', function(){
                                    app.helper.hideModal();
                                    $('#btnPayment').trigger('click');
                                });
                                $(container).find('#submit-btn').unbind('click').on('click', function(event){
                                    app.helper.showProgress();
                                    event.preventDefault();
                                    var element = $(this);
                                    element.attr('disabled', true);
                                    var headParent = element.closest('#an-charging-container-popup');
                                    var an_payment_profile_id = headParent.find('#an-payment-profile-id').val();
                                    var invoice_id = headParent.find('#charging-invoice-id').val();
                                    var payment_id = headParent.find('#charging-payment-id').val();
                                    var amount = headParent.find('#charging-amount').val();
                                    if(an_payment_profile_id == ''){
                                        app.helper.showAlertNotification({title: app.vtranslate('Invalid'), message: app.vtranslate('Please select an Payment Method.')});
                                        element.removeAttr('disabled');
                                        app.helper.hideProgress();
                                        return false;
                                    }
                                    var params = {};
                                    params['module'] = 'ANTransactions';
                                    params['action'] = 'SaveTransaction';
                                    params['invoice_id'] = invoice_id;
                                    params['payment_id'] = payment_id;
                                    params['an_payment_profile_id'] = an_payment_profile_id;
                                    params['amount'] = amount;

                                    app.request.post({'data':params}).then(
                                        function (err, data) {
                                            app.helper.hideProgress();
                                            if(err === null) {
                                                app.helper.hideProgress();
                                                app.helper.hideModal();
                                                $('#btnPayment').trigger('click');

                                                //get transaction status
                                                var transaction_id = data._recordId;
                                                var params = {};
                                                params['module'] = 'Vtiger';
                                                params['action'] = 'GetData';
                                                params['record'] = transaction_id;
                                                params['source_module'] = 'ANTransactions';

                                                app.request.post({'data':params}).then(
                                                    function (err, response) {
                                                        if(err === null) {
                                                            var msg = response.data.an_description;
                                                            if(response.data.request_status=='SUCCESS'){
                                                                var msg_arr = msg.split('Message Detail :');
                                                                app.helper.showSuccessNotification({title: app.vtranslate('Success'), message: msg_arr[1]});
                                                            }else{
                                                                var msg_arr = msg.split('Error message :');
                                                                app.helper.showErrorNotification({title: app.vtranslate('Success'), message: msg_arr[1]});
                                                            }
                                                        }
                                                    }
                                                );
                                            }
                                            else {
                                                app.helper.hideProgress();
                                            }
                                        }
                                    );
                                });
                            }});
                        }
                        else {
                            app.helper.hideProgress();
                        }
                    }
                );
            });
        }
    },*/

    registerShowANTransactions: function(){
        if($('#PaymentInfo .show-an-transactions').length){
            $('#PaymentInfo .show-an-transactions').unbind('click').on('click', function(event){
                event.preventDefault();
                var payment_id = $(this).data('record');
                if($('#an-transactions-payment'+payment_id+'-container').length){
                    $('#an-transactions-payment'+payment_id+'-container').toggleClass('hide');
                }
            })
        }
    },

	registerEvents : function(){
        if(app.getModuleName()=='Invoice' && app.getViewName()=='Detail') {
            //Payment_Index_Js.registerAddButtonPayment();
            //Payment_Index_Js.registerShowPaymentsForm();
            Payment_Index_Js.registerRowClick();
        }
	}
}


//On Page Load
jQuery(document).ready(function() {
    // Only load when loadHeaderScript=1 BEGIN #241208
    if (typeof VTECheckLoadHeaderScript == 'function') {
        if (!VTECheckLoadHeaderScript('VTEPayments')) {
            return;
        }
    }
    // Only load when loadHeaderScript=1 END #241208

    Payment_Index_Js.registerEvents();
    //Hide related link on right panel
    //jQuery('li[data-label-key = "VTEPaymentsLinkMustHide"]').remove();
});
//extend jquery to scroll on div
jQuery.fn.scrollTo = function( target, options, callback ){
    if(typeof options == 'function' && arguments.length == 2){ callback = options; options = target; }
    var settings = jQuery.extend({
        scrollTarget  : target,
        offsetTop     : 50,
        duration      : 500,
        easing        : 'swing'
    }, options);
    return this.each(function(){
        var scrollPane = jQuery(this);
        var scrollTarget = (typeof settings.scrollTarget == "number") ? settings.scrollTarget : jQuery(settings.scrollTarget);
        var scrollY = (typeof scrollTarget == "number") ? scrollTarget : scrollTarget.offset().top + scrollPane.scrollTop() - parseInt(settings.offsetTop);
        scrollPane.animate({scrollTop : scrollY }, parseInt(settings.duration), settings.easing, function(){
            if (typeof callback == 'function') { callback.call(this); }
        });
    });
}
