/* ********************************************************************************
 * The content of this file is subject to the Module & Link Creator ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

/** @class ModuleLinkCreator_List_Js */
Vtiger_List_Js("ModuleLinkCreator_List_Js", {}, {
    /* For License page - Begin */
    init: function () {
        this.addComponents();
        // this.initiate();
    },
    /**
     * Function to initiate the step 1 instance
     */
    initiate: function () {
        var step = jQuery(".installationContents").find('.step').val();
        this.initiateStep(step);
    },
    /**
     * Function to initiate all the operations for a step
     * @params step value
     */
    initiateStep: function (stepVal) {
        var step = 'step' + stepVal;
        this.activateHeader(step);
    },

    activateHeader: function (step) {
        var headersContainer = jQuery('.crumbs ');
        headersContainer.find('.active').removeClass('active');
        jQuery('#' + step, headersContainer).addClass('active');
    },

    registerActivateLicenseEvent: function () {
        var aDeferred = jQuery.Deferred();
        jQuery(".installationContents").find('[name="btnActivate"]').click(function () {
            var license_key = jQuery('#license_key');
            if (license_key.val() == '') {
                var errorMsg = "License Key cannot be empty";
                license_key.validationEngine('showPrompt', errorMsg, 'error', 'bottomLeft', true);
                aDeferred.reject();
                return aDeferred.promise();
            } else {
                var progressIndicatorElement = jQuery.progressIndicator({
                    'position': 'html',
                    'blockInfo': {
                        'enabled': true
                    }
                });
                var params = {};
                params['module'] = app.getModuleName();
                params['action'] = 'Activate';
                params['mode'] = 'activate';
                params['license'] = license_key.val();

                AppConnector.request(params).then(
                    function (data) {
                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                        if (data.success) {
                            var message = data.result.message;
                            if (message != 'Valid License') {
                                jQuery('#error_message').html(message)
                                    .show();
                            } else {
                                document.location.href = "index.php?module=ModuleLinkCreator&view=List&mode=step3";
                            }
                        }
                    },
                    function (error) {
                        console.log('error =', error);
                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                    }
                );
            }
        });
    },

    registerValidEvent: function () {
        jQuery(".installationContents").find('[name="btnFinish"]').click(function () {
            var progressIndicatorElement = jQuery.progressIndicator({
                'position': 'html',
                'blockInfo': {
                    'enabled': true
                }
            });
            var params = {};
            params['module'] = app.getModuleName();
            params['action'] = 'Activate';
            params['mode'] = 'valid';

            AppConnector.request(params).then(
                function (data) {
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});
                    if (data.success) {
                        document.location.href = "index.php?module=ModuleLinkCreator&view=List";
                    }
                },
                function (error) {
                    console.log('error =', error);
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});
                }
            );
        });
    },
    /*
     * Function to register the list view delete record click event
     */
    registerDeleteRecordClickEventModuleLinkCreator: function(){
        var thisInstance = this;
        $('.deleteRecordModuleLinkCreator').on('click',function(e){
            var elem = jQuery(e.currentTarget);
            var link = elem.closest('.deleteRecordModuleLinkCreator').data('link');
            // alert(recordId);
            thisInstance.deleteRecord(link);
            e.stopPropagation();
        });
    },
    deleteRecord : function(link) {
        var listInstance = Vtiger_List_Js.getInstance();
        var message = app.vtranslate('JS_CONFIRM_DELETE');
        app.helper.showConfirmationBox({'message' : message}).then(
            function (e) {
                window.location.href = link;
            },
            function (error, err) {
            }
        );
    },
    registerEventUpdateIconButton : function () {
        $('.btn-update-icon').on('click',function(){
            var moduleName = $(this).data('module');
            $('#ModalIcons').find('#selected_module').val(moduleName);
        });
    },
    registerEventSelectIcons : function () {
        var modal = $("#ModalIcons");
        modal.find('.cell-icon').on('click',function () {
            var group = ".cell-icon";
            $(group).css("background", "#FFFFFF");
            $(group).removeClass("iconChecked");
            $(this).css("background", "cyan");
            $(this).addClass("iconChecked");
        })
        //submit icon module
        modal.find(".btn-submit").on('click', function () {
            var moduleName = $('#selected_module').val();
            if(moduleName){
                var spanIcon  = modal.find('.iconChecked').find('span');
                var dataInfo = spanIcon.data('info');
                var classspanIcon = spanIcon.attr('class');
                var spanSelected = $('.logo-module').find('#icon-module');
                spanSelected.removeClass();
                spanSelected.addClass(classspanIcon);
                modal.modal('toggle');
                var params = {};
                params['module'] = app.getModuleName();
                params['action'] = 'ActionAjax';
                params['mode'] = 'updateIcon';
                params['select_module'] = moduleName;
                params['icon'] = dataInfo;
                AppConnector.request(params).then(
                    function (data) {
                        window.location.reload(true);
                    },
                    function (error) {

                    }
                );
            }
        });
    },
    /* For License page - End */

    /**
     * Function to register events
     */
    registerEvents: function () {
        this._super();
        this.registerDeleteRecordClickEventModuleLinkCreator();
        /* For License page - Begin */
        this.registerActivateLicenseEvent();
        this.registerValidEvent();
        this.registerEventSelectIcons();
        this.registerEventUpdateIconButton();
        /* For License page - End */
    }
});