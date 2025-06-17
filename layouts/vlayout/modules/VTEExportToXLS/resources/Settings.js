/* ********************************************************************************
 * The content of this file is subject to the Export To XLS ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
jQuery.Class("VTEExportToXLS_Settings_Js",{
    editInstance:false,
    getInstance: function(){
        if(VTEExportToXLS_Settings_Js.editInstance == false){
            var instance = new VTEExportToXLS_Settings_Js();
            VTEExportToXLS_Settings_Js.editInstance = instance;
            return instance;
        }
        return VTEExportToXLS_Settings_Js.editInstance;
    }
},{
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
                errorMsg = "License Key cannot be empty";
                license_key.validationEngine('showPrompt', errorMsg , 'error','bottomLeft',true);
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
                            }else{
                                document.location.href="index.php?module=VTEExportToXLS&parent=Settings&view=Settings&mode=step3";
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
            params['mode'] = 'valid';

            AppConnector.request(params).then(
                function (data) {
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});
                    if (data.success) {
                        document.location.href = "index.php?module=VTEExportToXLS&parent=Settings&view=Settings";
                    }
                },
                function (error) {
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});
                }
            );
        });
    },
    /* For License page - End */
    registerEnableModuleEvent:function() {
        jQuery('.summaryWidgetContainer').find('#enable_module').change(function(e) {
            var progressIndicatorElement = jQuery.progressIndicator({
                'position' : 'html',
                'blockInfo' : {
                    'enabled' : true
                }
            });

            var element=e.currentTarget;
            var value=0;
            var text="Export To XLS Disabled";
            if(element.checked) {
                value=1;
                text = "Export To XLS Enabled";
            }
            var params = {};
            params.action = 'ActionAjax';
            params.module = 'VTEExportToXLS';
            params.value = value;
            params.mode = 'enableModule';
            AppConnector.request(params).then(
                function(data){
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    var params = {};
                    params['text'] = text;
                    Settings_Vtiger_Index_Js.showMessage(params);
                },
                function(error){
                    //TODO : Handle error
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                }
            );
        });
    },
    /* For License page - End */

    registerEventForChangeCustomFileState: function () {
        jQuery('.summaryWidgetContainer').find('#custom_filename').change(function(e) {
            var progressIndicatorElement = jQuery.progressIndicator({
                'position': 'html',
                'blockInfo': {
                    'enabled': true
                }
            });
            var element=e.currentTarget;
            var status=0;
            if(element.checked) {
                status=1;
            }
            var params = {
                'module' : 'VTEExportToXLS',
                'action' : 'ActionAjax',
                'mode' : 'saveValue',
                'fieldname' : 'custom_filename',
                'value' : status
            };
            AppConnector.request(params).then(
                function(data){
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    var params = {};
                    params['text'] = 'Updated!';
                    Settings_Vtiger_Index_Js.showMessage(params);
                },
                function(error){
                    //TODO : Handle error
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                }
            );
        });
    },
    registerEventForChangeFileName : function () {
        jQuery('input.custom_fieldname').change(function (e) {
            var file_name = '';
            jQuery('input.custom_fieldname').each(function (idx, elm) {
                if (jQuery(elm).is(':checked')) {
                    file_name += jQuery(elm).val() +'-';
                }
            });
            file_name = file_name.substring(0, file_name.length-1);
            var params = {
                'module' : 'VTEExportToXLS',
                'action' : 'ActionAjax',
                'mode' : 'saveValue',
                'fieldname' : 'file_name',
                'value' : file_name
            };

            AppConnector.request(params).then(
                function(data){
                    var params = {};
                    params['text'] = 'Updated!';
                    Settings_Vtiger_Index_Js.showMessage(params);
                    jQuery('#file_name').val(file_name);
                },
                function(error){
                    //TODO : Handle error
                }
            );
        });
    },
    registerEventForDownload: function () {
        jQuery('.summaryWidgetContainer').find('#download_to_server').change(function(e) {
            var progressIndicatorElement = jQuery.progressIndicator({
                'position': 'html',
                'blockInfo': {
                    'enabled': true
                }
            });
            var element=e.currentTarget;
            var status=0;
            if(element.checked) {
                status=1;
            }
            var params = {
                'module' : 'VTEExportToXLS',
                'action' : 'ActionAjax',
                'mode' : 'enableDownload',
                'value' : status,
            };
            AppConnector.request(params).then(
                function(data){
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                    if(data.success && data.result) {
                        var params = {};
                        params['text'] = data.result.message;
                        Settings_Vtiger_Index_Js.showMessage(params);
                    }else {
                        var params = {};
                        params['type'] = 'error';
                        params['text'] = data.error.message;
                        Settings_Vtiger_Index_Js.showMessage(params);
                    }
                },
                function(error){
                    //TODO : Handle error
                    progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                }
            );
        });
    },
    /**
     * Function which will handle the registrations for the elements
     */
    registerEvents : function() {
        this.registerEnableModuleEvent();
        this.registerEventForChangeCustomFileState();
        this.registerEventForChangeFileName();
        this.registerEventForDownload();
        /* For License page - Begin */
        this.registerActivateLicenseEvent();
        this.registerValidEvent();
        /* For License page - End */
    }
});
