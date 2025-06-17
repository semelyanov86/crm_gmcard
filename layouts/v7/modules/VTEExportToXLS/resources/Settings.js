/* ********************************************************************************
 * The content of this file is subject to the Export To XLS ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
Vtiger.Class("VTEExportToXLS_Settings_Js",{
    instance:false,
    getInstance: function(){
        if(VTEExportToXLS_Settings_Js.instance == false){
            var instance = new VTEExportToXLS_Settings_Js();
            VTEExportToXLS_Settings_Js.instance = instance;
            return instance;
        }
        return VTEExportToXLS_Settings_Js.instance;
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
                app.helper.showAlertBox({message:"License Key cannot be empty"});
                aDeferred.reject();
                return aDeferred.promise();
            }else{
                app.helper.showProgress();
                var params = {};
                params['module'] = app.getModuleName();
                params['action'] = 'Activate';
                params['mode'] = 'activate';
                params['license'] = license_key.val();

                app.request.post({data:params}).then(
                    function(err,data) {
                        app.helper.hideProgress();
                        if(err == null){
                            var message=data['message'];
                            if(message !='Valid License') {
                                app.helper.hideProgress();
                                app.helper.hideModal();
                                app.helper.showAlertNotification({'message':data['message']});
                            }else{
                                document.location.href="index.php?module=VTEExportToXLS&parent=Settings&view=Settings&mode=step3";
                            }
                        }
                    },
                    function(error) {
                        app.helper.hideProgress();
                    }
                );
            }
        });
    },
    registerValidEvent: function () {
        jQuery(".installationContents").find('[name="btnFinish"]').click(function() {
            app.helper.showProgress();
            var data = {};
            data['module'] = 'VTEExportToXLS';
            data['action'] = 'Activate';
            data['mode'] = 'valid';
            app.request.post({data:data}).then(
                function (err,data) {
                    if(err == null){
                        app.helper.hideProgress();
                        if (data) {
                            document.location.href = "index.php?module=VTEExportToXLS&parent=Settings&view=Settings";
                        }
                    }
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
            app.request.post({
                data:params
            }).then(function(error,data){
                progressIndicatorElement.progressIndicator({'mode' : 'hide'});
                if(data){
                    app.helper.showSuccessNotification({
                        message : text
                    });
                }
            });
        });
    },

    registerEventForChangeCustomFileState: function () {
        jQuery('.summaryWidgetContainer').on('switchChange.bootstrapSwitch', "input[name='custom_filename']", function (e) {
            var currentElement = jQuery(e.currentTarget);
            var status = 0;
            if(currentElement.val() == 'on'){
                currentElement.attr('value','off');
                status = 0;
            } else {
                currentElement.attr('value','on');
                status = 1;
            }
            var params = {
                'module' : 'VTEExportToXLS',
                'action' : 'ActionAjax',
                'mode' : 'saveValue',
                'fieldname' : 'custom_filename',
                'value' : status
            };

            app.request.post({
                data:params
            }).then(function(error,data){
                if(data){
                    app.helper.showSuccessNotification({
                        message : app.vtranslate('Updated!')
                    });
                }
            });
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

            app.request.post({
                data:params
            }).then(function(error,data){
                if(data){
                    app.helper.showSuccessNotification({
                        message : app.vtranslate('Updated!')
                    });
                    jQuery('#file_name').val(file_name);
                }
            });

        });
    },
    registerEventForDownload : function (){
        jQuery('.summaryWidgetContainer').on('switchChange.bootstrapSwitch', "input[name='download_to_server']", function (e) {
            var currentElement = jQuery(e.currentTarget);
            var status = 0;
            if(currentElement.val() == 'on'){
                currentElement.attr('value','off');
                status = 0;
            } else {
                currentElement.attr('value','on');
                status = 1;
            }
            var params = {
                'module' : 'VTEExportToXLS',
                'action' : 'ActionAjax',
                'mode' : 'enableDownload',
                'value' : status,
            };

            app.request.post({
                data:params
            }).then(function(error,data){
                if(error) {
                    app.helper.showErrorNotification({"message":error.message});
                }else {
                    app.helper.showSuccessNotification({
                        message : data.message
                    });
                }
            });
        });
    },

    registerEvents: function(){
        jQuery("input[name='custom_filename']").bootstrapSwitch();
        jQuery("input[name='download_to_server']").bootstrapSwitch();
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
jQuery(document).ready(function() {
    var instance = new VTEExportToXLS_Settings_Js();
    instance.registerEvents();
    Vtiger_Index_Js.getInstance().registerEvents();
});
