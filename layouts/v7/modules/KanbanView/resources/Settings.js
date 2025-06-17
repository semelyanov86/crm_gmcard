/* ********************************************************************************
 * The content of this file is subject to the Kanban View ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
Vtiger_Index_Js("Settings_KanbanView_Settings_Js",{
    instance:false,
    getInstance: function(){
        if(Settings_KanbanView_Settings_Js.instance == false){
            var instance = new Settings_KanbanView_Settings_Js();
            Settings_KanbanView_Settings_Js.instance = instance;
            return instance;
        }
        return Settings_KanbanView_Settings_Js.instance;
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

                AppConnector.request(params).then(
                    function(data) {
                        app.helper.hideProgress();
                        if(data.success) {
                            var message=data.result.message;
                            if(message !='Valid License') {
                                jQuery('#error_message').html(message);
                                jQuery('#error_message').show();
                            }else{
                                document.location.href="index.php?module=KanbanView&parent=Settings&view=Settings&mode=step3";
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
            var params = {};
            params.data = {
                module: app.getModuleName(),
                action: 'Activate',
                mode: 'valid'
            }
            app.request.post(params).then(
                function (err,data) {
                    if(err == null){
                        app.helper.hideProgress();
                        if (data == 'success') {
                            document.location.href = "index.php?module=KanbanView&parent=Settings&view=Settings";
                        }
                    }else{
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },
    /* For License page - End */

    registerEnableModuleEvent:function() {
        jQuery('.summaryWidgetContainer').find('#enable_module').change(function(e) {
            app.helper.showProgress();
            var element=e.currentTarget;
            var value=0;
            var text="Kanban View Disabled";
            if(element.checked) {
                value=1;
                text = "Kanban View Enabled";
            }
            var params = {};
            params.action = 'ActionAjax';
            params.module = 'KanbanView';
            params.value = value;
            params.mode = 'enableModule';
            app.request.post({data:params}).then(
                function(err,data){
                    if(err == null){
                        app.helper.hideProgress();
                        app.helper.showSuccessNotification({message:text});
                    }
                }
            );
        });
    },
    registerEvents: function(){
        this._super();
        this.registerEnableModuleEvent();
        /* For License page - Begin */
        this.registerActivateLicenseEvent();
        this.registerValidEvent();
        /* For License page - End */
    }
});