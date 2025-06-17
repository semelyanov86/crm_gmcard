/* ********************************************************************************
 * The content of this file is subject to the VTEPayments("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
Vtiger.Class("VTEPayments_List_Js",{},{
// Vtiger_List_Js("VTEPayments_List_Js", {}, {
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
                    function(err, data) {
                        if(err === null) {
                            var message=data.message;
                            if(message !='Valid License') {
                                app.helper.showErrorNotification({"message": message});
                            }else{
                                document.location.href="index.php?module=VTEPayments&view=List&mode=step3";
                            }
                            app.helper.hideProgress();
                        }
                        else {
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

            app.request.post({'data':params}).then(
                function (err, data) {
                    app.helper.hideProgress();
                    if(err === null) {
                        document.location.href = "index.php?module=VTEPayments&view=List";
                    }
                    else {
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },
    /* For License page - End */

    /**
     * Function to register events
     */
    registerEvents : function(){
        this._super();
        /* For License page - Begin */
        this.registerActivateLicenseEvent();
        this.registerValidEvent();
        /* For License page - End */
    }
});

jQuery(document).ready(function(){
    var instance = new VTEPayments_List_Js();
    instance.registerEvents();

    // Fix issue on list
    var Vtiger_List_Js_obj = new Vtiger_List_Js();
    Vtiger_List_Js_obj.intializeComponents();
    Vtiger_List_Js_obj.registerEvents();

    // Hide button Create new and Export
    $("#VTEPayments_listView_basicAction_LBL_ADD_RECORD").hide();
    $("#VTEPayments_basicAction_LBL_IMPORT").hide();

});