/* ********************************************************************************
 * The content of this file is subject to the VTEPayments("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
var Payment_Index_Js = {
    registerAddButtonPayment: function() {
        if (app.getModuleName() == 'Invoice' && app.getViewName() == 'Detail') {
            var spanBtnEdit = jQuery('#Invoice_detailView_basicAction_LBL_EDIT').parent();
            spanBtnEdit.parent().append('<span class="btn-group"><button class="btn btn-success" name="btnPayments" id="btnPayment"><strong>Payments</strong></button></span>');
        }
    },
    registerShowPaymentsForm: function() {
        var thisInstance = this;
        jQuery('.btn-group').on("click", '#btnPayment', function(e) {
            var invoiceid = jQuery('#recordId').val();
            var url = 'index.php?module=VTEPayments&view=ManagePayments&invoiceid=' + invoiceid
            thisInstance.showEditView(url);
        });
    },
    showEditView: function(url) {
        var thisInstance = this;
        var progressIndicatorElement = jQuery.progressIndicator();
        var actionParams = {
            "type": "POST",
            "url": url,
            "dataType": "html",
            "data": {}
        };
        AppConnector.request(actionParams).then(function(data) {
            progressIndicatorElement.progressIndicator({
                'mode': 'hide'
            });
            if (data) {
                var callBackFunction = function(data) {
                    var form = jQuery('#EditView');
                    var params = app.validationEngineOptions;
                    params.onValidationComplete = function(form, valid) {
                        if (valid) {
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
                app.showModalWindow(data, function(data) {
                    if (typeof callBackFunction == 'function') {
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
                    var old_status = status_cb.val();
                    if (old_status == '') {
                        status_cb.val(app.vtranslate('Paid'));
                        status_cb.trigger('liszt:updated');
                    }
                    var type_cb = jQuery("select[name='payment_type']");
                    var old_type = type_cb.val();
                    if (old_type == '') {
                        type_cb.val(app.vtranslate('Check'));
                        type_cb.trigger('liszt:updated');
                    }
                    var invoice_input = jQuery('#invoice_display');
                    var invoice_input_div = invoice_input.closest('div');
                    invoice_input_div.find('span:first').remove();
                    invoice_input_div.find('span:last').remove();
                    invoice_input.css('width', '92%');
                    jQuery('.showInlineTable:last').find('tbody').hide();
                    thisInstance.registerAmountTooltip();
                    thisInstance.registerApplyCredit();
                }, {
                    'width': '1000px'
                })
            }
        });
    },
    registerDateControl: function() {
        var date_control = jQuery("#VTEPayments_editView_fieldName_date");
        var date_format = date_control.data('date-format');
        var today = new Date();
        var formatted = app.getDateInVtigerFormat(date_format, today);
        date_control.val(formatted);
    },
    registerAmountTooltip: function() {
        var options = {
            content: function() {
                return jQuery('#popover-content').html();
            },
            html: true,
            placement: 'bottom',
            trigger: 'click'
        };
        jQuery(".lnk-popover").popover(options);
        jQuery('html').on('click', function(e) {
            if (typeof jQuery(e.target).data('original-title') == 'undefined' && !jQuery(e.target).parents().is('.popover.in')) {
                jQuery('[data-original-title]').popover('hide');
            }
        });
        jQuery('html').on('change', '.amount_pre_paid', function(e) {
            var target_ctrl = jQuery(e.target);
            var this_val = target_ctrl.val();
            var balance = jQuery('#balance_link').html();
            var cal_to_val = '';
            if (target_ctrl.attr('name') == 'total_pre_paid') {
                var total = target_ctrl.data('total');
                cal_to_val = parseFloat(total) * parseFloat(this_val) / 100;
            }
            if (target_ctrl.attr('name') == 'balance_pre_paid') {
                var balance = target_ctrl.data('balance');
                cal_to_val = parseFloat(balance) * parseFloat(this_val) / 100;
            }
            var pre_div = target_ctrl.closest('div').prev();
            pre_div.children('span').html(cal_to_val.toFixed(2));
        });
        jQuery('html').on('click', '.val_will_paid', function(e) {
            var target_ctrl = jQuery(e.target);
            var this_val = target_ctrl.html();
            jQuery('#VTEPayments_editView_fieldName_amount_paid').val(this_val);
            jQuery('[data-original-title]').popover('hide');
        });
        var themes_color = jQuery('#searchIcon').css("background-color");
        jQuery('.tbl-tooltip').css("background-color", themes_color);
    },
    registerBlockAnimationEvent: function(detailContentsHolder) {
        //var detailContentsHolder = jQuery('');
        detailContentsHolder.on('click', '.blockToggle', function(e) {
            var currentTarget = jQuery(e.currentTarget);
            var blockId = currentTarget.data('id');
            var closestBlock = currentTarget.closest('.showInlineTable');
            var bodyContents = closestBlock.find('tbody');
            var data = currentTarget.data();
            var module = app.getModuleName();
            var hideHandler = function() {
                bodyContents.hide('slow');
                app.cacheSet(module + '.' + blockId, 0)
            }
            var showHandler = function() {
                bodyContents.show();
                app.cacheSet(module + '.' + blockId, 1)
            }
            var data = currentTarget.data();
            if (data.mode == 'show') {
                hideHandler();
                currentTarget.hide();
                closestBlock.find("[data-mode='hide']").show();
            } else {
                showHandler();
                currentTarget.hide();
                closestBlock.find("[data-mode='show']").show();
            }
        });
    },
    ajaxSavePayment: function(form) {
        var progressInstance = jQuery.progressIndicator();
        var thisInstance = this;
        var actionParams = {
            "type": "POST",
            "url": "index.php?module=VTEPayments&action=SaveAjax",
            "dataType": "html",
            "data": form.serializeFormData()
        };
        AppConnector.request(actionParams).then(function(data) {
            if (data) {
                progressInstance.progressIndicator({
                    'mode': 'hide'
                });
                thisInstance.loadListPayments();
                thisInstance.updateGrandTotal();
                thisInstance.registerUpdateTooltipArea();
                thisInstance.resetForm(form);
                var m_params = {
                    text: app.vtranslate('Payments saved'),
                    type: 'success'
                }
                Vtiger_Helper_Js.showMessage(m_params);
            }
        }, function(error, err) {
            progressInstance.progressIndicator({
                'mode': 'hide'
            });
        });
        return false;
    },
    registerEditOnPopupPayment: function() {
        var thisInstance = this;
        jQuery('.edit-payment').on('click', function() {
            var progressIndicatorElement = jQuery.progressIndicator();
            var payment_id = jQuery(this).closest('tr').data('id');
            var url = 'index.php?module=VTEPayments&view=EditPayment&payment_id=' + payment_id;
            var actionParams = {
                "type": "POST",
                "url": url,
                "data": {}
            };
            AppConnector.request(actionParams).then(function(data) {
                if (data) {
                    var response = jQuery.parseJSON(data);
                    var results = jQuery.parseJSON(response.result);
                    jQuery.each(results, function(name, value) {
                        if (name == 'contact' && value != 0) {
                            var contact_id = results.contact;
                            var contact_name = results.contact_name;
                            var selectedItemData = {
                                'id': contact_id,
                                'label': contact_name,
                                'name': contact_name,
                                'value': contact_name
                            };
                            var element = jQuery("input[name='contact_display']");
                            var tdElement = element.closest('td');
                            var editInstance = Vtiger_Edit_Js.getInstance();
                            editInstance.setReferenceFieldValue(tdElement, selectedItemData);
                        } else if (name == 'organization' && value != 0) {
                            var account_id = results.organization;
                            var account_name = results.account_name;
                            var selectedItemData = {
                                'id': account_id,
                                'label': account_name,
                                'name': account_name,
                                'value': account_name
                            };
                            var element = jQuery("input[name='organization_display']");
                            var tdElement = element.closest('td');
                            var editInstance = Vtiger_Edit_Js.getInstance();
                            editInstance.setReferenceFieldValue(tdElement, selectedItemData);
                            jQuery("input[name='organization']").val(account_id);
                        } else if(name == 'assigned_user_id' && value !='' ){
                            $('[name="assigned_user_id"]').val(value);
                        }else {
                            jQuery("[name='" + name + "']").val(value);
                        }
                    });
                    jQuery("select").trigger('liszt:updated');
                    jQuery("#PaymentInfo").scrollTo(0);
                    jQuery("input[name='record']").val(payment_id);
                    progressIndicatorElement.progressIndicator({
                        'mode': 'hide'
                    });
                }
            });
        });
    },
    registerDelOnPopupPayment: function(paymentid) {
        var thisInstance = this;
        jQuery('.relationDelete').on("click", function(e) {
            var progressIndicatorElement = jQuery.progressIndicator();
            var payment_id = jQuery(this).closest('tr').data('id');
            var url = 'index.php?module=VTEPayments&action=DeleteAjax&record=' + payment_id;
            var actionParams = {
                "type": "POST",
                "url": url,
                "async": false,
                "data": {}
            };
            AppConnector.request(actionParams).then(function(data) {
                if (data) {
                    thisInstance.loadListPayments();
                    thisInstance.updateGrandTotal();
                    thisInstance.registerUpdateTooltipArea();
                    progressIndicatorElement.progressIndicator({
                        'mode': 'hide'
                    });
                    var m_params = {
                        text: app.vtranslate('Payment has been removed'),
                        type: 'success'
                    }
                    Vtiger_Helper_Js.showMessage(m_params);
                }
            });
        });
    },
    updateBalance: function() {
        var balance = 0;
        jQuery('table.tblPaymentListView tr.listViewEntries').each(function() {
            var td_val = jQuery(this).find('.amount_paid');
            balance += parseFloat(td_val.text());
        });
        jQuery('.pmBalance').html(balance.toFixed(2));
    },
    loadListPayments: function() {
        var thisInstance = this;
        //var progressIndicatorElement = jQuery.progressIndicator();
        var invoice_id = jQuery('#recordId').val();
        var url = 'index.php?module=VTEPayments&view=ListPayments&invoice_id=' + invoice_id;
        var actionParams = {
            "type": "POST",
            "url": url,
            "async": false,
            "dataType": "html",
            "data": {}
        };
        AppConnector.request(actionParams).then(function(data) {
            //progressIndicatorElement.progressIndicator({'mode' : 'hide'});
            if (data) {
                jQuery('.ListPayments').html(data);
                thisInstance.registerEditOnPopupPayment();
                thisInstance.registerDelOnPopupPayment();
                thisInstance.registerANChargingOnPopupPayment();
                thisInstance.registerShowANTransactions();
                thisInstance.registerApplyCredit();
            }
        });
    },
    updateGrandTotal: function() {
        var invoice_id = jQuery('#recordId').val();
        var total_amount = 0;
        var url = 'index.php?module=VTEPayments&action=Ajax&mode=updateGrandTotal';
        var actionParams = {
            "type": "POST",
            "url": url,
            "async": false,
            "data": {
                'total_amount': total_amount,
                'invoice_id': invoice_id
            }
        };
        AppConnector.request(actionParams).then(function(data) {
            if (data) {
                //var total = jQuery("#pmInvoiceTotal").text();
                var total = data.result.total;
                var total_amount = data.result.total_amount;
                //total_amount = parseFloat(data.result.total_amount);
                //var balance = total - total_amount;
                var balance = data.result.balance;
                jQuery('.balance_link').html(balance);
                jQuery('#pmReceived').html(total_amount);
                var invoice_detail_table = jQuery('#detailView').find('table:last');
                var invoice_detail_tr = invoice_detail_table.find('tr:last');
                var invoice_detail_balance = invoice_detail_tr.find('td:last');
                invoice_detail_balance.html('<span class="pull-right"> ' + balance + ' </span>');
                var invoice_detail_received = invoice_detail_tr.prev().find('td:last');
                invoice_detail_received.html('<span class="pull-right"> ' + total_amount + ' </span>');
                jQuery('.credit-available-amount').html(data.result.credit_available_amount);
            }
        });
    },
    resetForm: function(form) {
        form.trigger('reset');
        jQuery("input[name='record']").val('');
        var status_cb = jQuery("select[name='payment_status']");
        status_cb.val(app.vtranslate('Paid'));
        var status_type = jQuery("select[name='payment_type']");
        status_type.val(app.vtranslate('Check'));
        jQuery("select").trigger('liszt:updated');
        this.registerDateControl();
    },
    registerBalanceLinkClick: function() {
        jQuery('#balance_link').on('click', function() {
            var last_pm_id = jQuery('.tblPaymentListView tbody').children('tr:first').data('id');
            var balance = jQuery(this).html();
            if (typeof last_pm_id != 'undefined') {
                var progressIndicatorElement = jQuery.progressIndicator();
                var url = 'index.php?module=VTEPayments&view=EditPayment&payment_id=' + last_pm_id;
                var actionParams = {
                    "type": "POST",
                    "url": url,
                    "data": {}
                };
                AppConnector.request(actionParams).then(function(data) {
                    if (data) {
                        var response = jQuery.parseJSON(data);
                        jQuery.each(jQuery.parseJSON(response.result), function(name, value) {
                            jQuery("[name='" + name + "']").val(value);
                        });
                        jQuery("select").trigger('liszt:updated');
                        jQuery("#PaymentInfo").scrollTo(0);
                        jQuery("input[name='record']").val('');
                        jQuery("input[name='amount_paid']").val(balance);
                        progressIndicatorElement.progressIndicator({
                            'mode': 'hide'
                        });
                    }
                });
            } else {
                jQuery("input[name='amount_paid']").val(balance);
            }
        });
    },
    registerUpdateTooltipArea: function() {
        var invoice_id = jQuery('#recordId').val();
        if (invoice_id) {
            var url = 'index.php?module=VTEPayments&action=Ajax&mode=updateTooltipArea&invoice_id=' + invoice_id;
            var actionParams = {
                "type": "POST",
                "url": url,
                "dataType": "html",
                "async": false,
                "data": {}
            };
            AppConnector.request(actionParams).then(function(data) {
                if (data) {
                    jQuery("#popover-content").html(data);
                    var themes_color = jQuery('#searchIcon').css("background-color");
                    jQuery('.tbl-tooltip').css("background-color", themes_color);
                }
            });
        }
    },
    registerRowClick: function() {
        var thisInstance = this;
        jQuery('html').on('click', '.payment-row td:not(:last-child)', function() {
            if (!$(this).hasClass('apply-credit-td')) {
                var parent_tr = jQuery(this).closest('tr');
                if (!parent_tr.hasClass('has-anet-transaction')) {
                    var edit_link = parent_tr.find('.edit-payment');
                    edit_link.trigger('click');
                }
            }
        });
    },

    registerANChargingOnPopupPayment: function() {
        var thisInstance = this;
        if ($('#PaymentInfo .an-charging').length) {
            jQuery('#PaymentInfo .an-charging').unbind('click').on('click', function(event) {
                event.preventDefault();
                var progressIndicatorElement = jQuery.progressIndicator();
                var payment_id = $(this).data('record');
                var url = 'index.php?module=ANTransactions&view=Charging&mode=chargingMethod&payment_id=' + payment_id;
                app.showModalWindow(null, url, function(container) {
                    progressIndicatorElement.progressIndicator({
                        'mode': 'hide'
                    });
                    $(container).find('.cancelLink').on('click', function() {
                        app.hideModalWindow();
                        //$('#btnPayment').trigger('click');
                        var invoiceid = jQuery('#recordId').val();
                        var url = 'index.php?module=VTEPayments&view=ManagePayments&invoiceid=' + invoiceid
                        thisInstance.showEditView(url);
                    });
                    ANTransactions_Charging_Js.registerEvents(container);
                });
            });
        }
    },
    /*registerANChargingOnPopupPayment: function() {
        var thisInstance = this;
        if ($('#PaymentInfo .an-charging').length) {
            jQuery('#PaymentInfo .an-charging').unbind('click').on('click', function(event) {
                event.preventDefault();
                var progressIndicatorElement = jQuery.progressIndicator();
                var payment_id = $(this).data('record');
                var url = 'index.php?module=ANTransactions&view=Charging&payment_id=' + payment_id;
                app.showModalWindow(null, url, function(container) {
                    progressIndicatorElement.progressIndicator({
                        'mode': 'hide'
                    });
                    $(container).find('.cancelLink').on('click', function() {
                        app.hideModalWindow();
                        $('#btnPayment').trigger('click');
                    });
                    $(container).find('#submit-btn').unbind('click').on('click', function(event) {
                        event.preventDefault();
                        var element = $(this);
                        element.attr('disabled', true);
                        var headParent = element.closest('#an-charging-container-popup');
                        var an_payment_profile_id = headParent.find('#an-payment-profile-id').val();
                        var invoice_id = headParent.find('#charging-invoice-id').val();
                        var payment_id = headParent.find('#charging-payment-id').val();
                        var amount = headParent.find('#charging-amount').val();
                        if (an_payment_profile_id == '') {
                            Vtiger_Helper_Js.showPnotify(app.vtranslate('Please select an Payment Method.'));
                            element.removeAttr('disabled');
                            return false;
                        }
                        var params = {};
                        params['module'] = 'ANTransactions';
                        params['action'] = 'SaveTransaction';
                        params['invoice_id'] = invoice_id;
                        params['payment_id'] = payment_id;
                        params['an_payment_profile_id'] = an_payment_profile_id;
                        params['amount'] = amount;
                        var progressIndicatorElement = jQuery.progressIndicator();
                        var aDeferred = jQuery.Deferred();
                        AppConnector.request(params).then(function(data) {
                            progressIndicatorElement.progressIndicator({
                                'mode': 'hide'
                            });
                            $('#btnPayment').trigger('click');

                            //get transaction status
                            var transaction_id = data.result._recordId;
                            var params = {};
                            params['module'] = 'Vtiger';
                            params['action'] = 'GetData';
                            params['record'] = transaction_id;
                            params['source_module'] = 'ANTransactions';
                            aDeferred.resolve(data);
                            AppConnector.request(params).then(
                                function (response) {
                                    if(response.success) {
                                        var msg = response.result.data.an_description;
                                        if(response.result.data.request_status=='SUCCESS'){
                                            var msg_arr = msg.split('Message Detail :');
                                            var msg_params = {
                                                title : app.vtranslate('JS_MESSAGE'),
                                                text: msg_arr[1],
                                                animation: 'show',
                                                type: 'success'
                                            };
                                            Vtiger_Helper_Js.showPnotify(msg_params);
                                        }else{
                                            var msg_arr = msg.split('Error message :');
                                            var msg_params = {
                                                title : app.vtranslate('JS_MESSAGE'),
                                                text: msg_arr[1],
                                                animation: 'show',
                                                type: 'error'
                                            };
                                            Vtiger_Helper_Js.showPnotify(msg_params);
                                        }
                                    }else{
                                        var msg_params = {
                                            title : app.vtranslate('JS_MESSAGE'),
                                            text: app.vtranslate('Charged fail!'),
                                            animation: 'show',
                                            type: 'error'
                                        };
                                        Vtiger_Helper_Js.showPnotify(msg_params);
                                    }
                                }
                            );
                        }, function(error, err) {
                            ///
                        });
                        return aDeferred.promise();
                    });
                });
            });
        }
    },*/
    registerShowANTransactions: function() {
        if ($('#PaymentInfo .show-an-transactions').length) {
            $('#PaymentInfo .show-an-transactions').unbind('click').on('click', function(event) {
                event.preventDefault();
                var payment_id = $(this).data('record');
                if ($('#an-transactions-payment' + payment_id + '-container').length) {
                    $('#an-transactions-payment' + payment_id + '-container').toggleClass('hide');
                }
            })
        }
    },
    registerApplyCredit: function() {
        var thisInstance = this;
        $('.apply-credit').off('click').on('click', function() {
            var paymentid = $(this).closest('tr').data('id');
            $.ajax({
                url: 'index.php?module=VTEPayments&action=Ajax&mode=applyCredit&paymentid=' + paymentid,
                success: function(data) {
                    var content = '<div class="modelContainer apply-credit-popup"><div class="modal-header contentsBackground"><button title="Close" type="button" data-dismiss="modal" aria-hidden="true" class="close">x</button><h3>Apply Credit</h3></div><div class="warning" style="padding: 20px;text-align: center;">' + data + '</div><div class="modal-footer quickCreateActions"><button type="button" class="btn btn-success save-credit"><strong>Submit</strong></button><a class="cancelLink cancelLinkContainer pull-right" type="reset" data-dismiss="modal">Cancel</a></div></div>';
                    app.showModalWindow(content, {
                        'text-align': 'left'
                    }, false);

                    //
                    $('.available-credit').on('change', function() {
                        var payment_amount = $('.payment-amount').text();
                        payment_amount = parseFloat(payment_amount.replace(',', ''));
                        var selected_credit = $(this).val();
                        selected_credit = selected_credit.replace(',', '');
                        if(selected_credit == '') {
                            $('.credit-amount').text(0);
                            $('.credit-amount-applied').text(0);
                            $('.new-amount').text(0);
                            $('.remaining-credit').text(0);
                        } else {
                            selected_credit = selected_credit.split('|');
                            var credit_amount = parseFloat(selected_credit[1]);
                            var credit_amount_applied = payment_amount;
                            if(payment_amount > credit_amount) {
                                credit_amount_applied = credit_amount;
                            }
                            $('.credit-amount').text(_formatNumber(credit_amount));
                            $('.credit-amount-applied').text(_formatNumber(credit_amount_applied));
                            $('.new-amount').text(_formatNumber(payment_amount - credit_amount_applied));
                            $('.remaining-credit').text(_formatNumber(credit_amount - credit_amount_applied));
                        }
                    });
                    $('.save-credit').on('click', function() {
                        var current_paymentid = $('#paymentid').val();
                        var selected_credit = $('.available-credit').val();
                        selected_credit = selected_credit.split('|');
                        var credit_paymentid = selected_credit[0];
                        var credit_amount_applied = $('.credit-amount-applied').text();
                        var new_amount = $('.new-amount').text();
                        var remaining_credit = $('.remaining-credit').text();
                        if(credit_paymentid == '') {
                            alert('Please select available credit');
                            return false;
                        }
                        $.ajax({
                            url: 'index.php?module=VTEPayments&action=Ajax&mode=saveCredit&current_paymentid=' + current_paymentid +
                                '&credit_paymentid=' + credit_paymentid + '&credit_amount_applied=' + credit_amount_applied +
                                '&new_amount=' + new_amount + '&remaining_credit=' + remaining_credit,
                            success: function() {
                                //$('#btnPayment').trigger('click');
                                var invoiceid = jQuery('#recordId').val();
                                var url = 'index.php?module=VTEPayments&view=ManagePayments&invoiceid=' + invoiceid
                                thisInstance.showEditView(url);
                            }
                        });
                    });
                }
            });
        });
    },
    registerEvents: function() {
        if (app.getModuleName() == 'Invoice' && app.getViewName() == 'Detail') {
            //Payment_Index_Js.registerAddButtonPayment();
            //Payment_Index_Js.registerShowPaymentsForm();
            Payment_Index_Js.registerRowClick();
        }
    }
}
//On Page Load
jQuery(document).ready(function() {
    Payment_Index_Js.registerEvents();
    //Hide related link on right panel
    jQuery('li[data-label-key = "VTEPaymentsLinkMustHide"]').remove();
});
//extend jquery to scroll on div
jQuery.fn.scrollTo = function(target, options, callback) {
    if (typeof options == 'function' && arguments.length == 2) {
        callback = options;
        options = target;
    }
    var settings = jQuery.extend({
        scrollTarget: target,
        offsetTop: 50,
        duration: 500,
        easing: 'swing'
    }, options);
    return this.each(function() {
        var scrollPane = jQuery(this);
        var scrollTarget = (typeof settings.scrollTarget == "number") ? settings.scrollTarget : jQuery(settings.scrollTarget);
        var scrollY = (typeof scrollTarget == "number") ? scrollTarget : scrollTarget.offset().top + scrollPane.scrollTop() - parseInt(settings.offsetTop);
        scrollPane.animate({
            scrollTop: scrollY
        }, parseInt(settings.duration), settings.easing, function() {
            if (typeof callback == 'function') {
                callback.call(this);
            }
        });
    });
}

function _formatNumber(val) {
    var no_of_currency_decimals = $('#no_of_currency_decimals').val();
    if(!no_of_currency_decimals) no_of_currency_decimals = 2;
    return parseFloat(val).toFixed(no_of_currency_decimals);
}