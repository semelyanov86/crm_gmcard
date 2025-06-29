/* ********************************************************************************
 * The content of this file is subject to the Global Search ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

Vtiger.Class("VTEEmailDesigner_Settings_Js",{
    instance:false,
    getInstance: function(){
        if(VTEEmailDesigner_Settings_Js.instance == false){
            var instance = new VTEEmailDesigner_Settings_Js();
            VTEEmailDesigner_Settings_Js.instance = instance;
            return instance;
        }
        return VTEEmailDesigner_Settings_Js.instance;
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

                app.request.post({'data' : params}).then(
                    function(err,data){
                        if(err === null) {
                            app.helper.hideProgress();
                            if(data) {
                                var message=data['message'];
                                if(message !='Valid License') {
                                    app.helper.hideProgress();
                                    app.helper.hideModal();
                                    app.helper.showAlertNotification({'message':data['message']});
                                }else{
                                    window.location.reload();
                                }
                            }
                        }else{
                            app.helper.hideProgress();
                        }
                    }
                );
            }
        });
    },

    registerValidEvent: function () {
        jQuery(".installationContents").find('[name="btnFinish"]').click(function() {
            app.helper.showProgress();
            var params = {};
            params['module'] = app.getModuleName();
            params['action'] = 'Activate';
            params['mode'] = 'valid';

            app.request.post({'data' : params}).then(function(err,data){
                if(err === null) {
                    app.helper.hideProgress();
                    if (data) {
                        window.location.reload();
                    }
                    }else{
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },
    /* For License page - End */

    registerSaveSettings:function() {
        jQuery("#team_settings").on("click",".btnSaveSettings", function(e) {
            app.helper.showProgress();
            var form=jQuery("#team_settings").find("#Settings");
            var saveUrl = form.serializeFormData();
            app.request.post({'data' : saveUrl}).then(
                function(err,data){
                    if(err === null) {
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },

    /**
     * Function which will handle the registrations for the elements
     */
    registerEvents : function() {
        this._super();
        /* For License page - Begin */
        this.registerActivateLicenseEvent();
        this.registerValidEvent();
        /* For License page - End */
    }
});

jQuery(document).ready(function() {
    var instance = new VTEEmailDesigner_Settings_Js();
    instance.registerEvents();
    Vtiger_Index_Js.getInstance().registerEvents();
});