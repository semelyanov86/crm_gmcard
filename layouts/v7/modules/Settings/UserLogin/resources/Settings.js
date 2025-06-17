/* * *******************************************************************************
 * The content of this file is subject to the VTE Custom User Login Page ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
 var Settings_UserLogin_Js = {

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
                                document.location.href="index.php?module=UserLogin&parent=Settings&view=Settings&mode=step3";
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
            params['module'] = app.getModuleName();
            params['action'] = 'Activate';
            params['mode'] = 'valid';

            AppConnector.request(params).then(
                function (data) {
                    app.helper.hideProgress();
                    if (data.success) {
                        document.location.href = "index.php?module=UserLogin&parent=Settings&view=Settings";
                    }
                },
                function (error) {
                    app.helper.hideProgress();
                }
            );
        });
    },
    registerEditBtn : function() {
        var thisInstance = this;
        jQuery('.editButton').on('click', function(event){
            event.preventDefault();
            var records = thisInstance.getRecordsSelected();
            if(records.length==0){
                alert(app.vtranslate('JS_PLEASE_SELECT_ONE_RECORD'));
            }else{
                window.location.assign('index.php?module=UserLogin&view=Edit&parent=Settings&record='+records[0]);
            }
        });
    },

     registerDeleteBtn : function() {
         var thisInstance = this;
         jQuery('.deleteButton').on('click', function(event){
             event.preventDefault();
             var records = thisInstance.getRecordsSelected();
             if(records.length==0){
                 alert(app.vtranslate('JS_PLEASE_SELECT_ONE_RECORD'));
             }else{
                 var params = {};
                 params['module'] = 'UserLogin';
                 params['parent'] = 'Settings';
                 params['action'] = 'DeleteAjax';
                 params['recordids'] = records;
                 var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
                 app.helper.showConfirmationBox({'message' : message}).then(function(data) {
                         var aDeferred = jQuery.Deferred();
                         app.helper.showProgress();
                         AppConnector.request(params).then(
                             function(data) {
                                 app.helper.hideProgress();
                                 aDeferred.resolve(data);
                                 window.location.reload();
                             },
                             function(error,err){
                                 app.helper.hideProgress();
                                 aDeferred.reject(error,err);
                             }
                         );
                         return aDeferred.promise();
                     },
                     function(error, err){
                     }
                 );
             }
         });
     },

     registerGenerateBtn : function() {
         var thisInstance = this;
         jQuery('.generateButton').on('click', function(event){
             event.preventDefault();
             var records = thisInstance.getRecordsSelected();
             if(records.length==0){
                alert(app.vtranslate('JS_PLEASE_SELECT_ONE_RECORD'));
             }else{
                 var url = jQuery(this).data('url')+'&record='+records[0];
                 var message = app.vtranslate('LBL_GENERATE_CONFIRMATION');
                 app.helper.showConfirmationBox({'message' : message}).then(function(data) {
                     var aDeferred = jQuery.Deferred();
                     app.helper.showProgress()
                     app.request.post({url: url}).then(
                         function(err,data) {
                             app.helper.hideProgress()
                             if(data === true){
                                 var params = {
                                     title: app.vtranslate('GENERATE_TITLE'),
                                     text: app.vtranslate('GENERATE_SUCCESS'),
                                     width: '35%',
                                     type: 'info'
                                 };
                             }else{
                                 var params = {
                                     title: app.vtranslate('GENERATE_TITLE'),
                                     text: app.vtranslate('GENERATE_FAIL'),
                                     width: '35%',
                                     type: 'error'
                                 };
                             }
                             Vtiger_Helper_Js.showPnotify(params);
                             aDeferred.resolve(data);
                         }
                     );
                     return aDeferred.promise();
                     },
                     function(error, err){
                     }
                 );
             }

         });
     },

     registerRestoreBtn : function() {
         var thisInstance = this;
         jQuery('.restoreButton').on('click', function(event){
             event.preventDefault();
             var url = jQuery(this).data('url');
             var message = app.vtranslate('LBL_RESTORE_CONFIRMATION');
             app.helper.showConfirmationBox({'message' : message}).then(
                 function(data) {
                     var aDeferred = jQuery.Deferred();
                     app.helper.showProgress();
                     app.request.post({url:url}).then(
                         function(err,data) {
                             app.helper.hideProgress();
                             if(data === true){
                                 var params = {
                                     title: app.vtranslate('RESTORE_TITLE'),
                                     text: app.vtranslate('RESTORE_SUCCESS'),
                                     width: '35%',
                                     type: 'info'
                                 };
                             }else{
                                 var params = {
                                     title: app.vtranslate('RESTORE_TITLE'),
                                     text: app.vtranslate('RESTORE_FAIL'),
                                     width: '35%',
                                     type: 'error'
                                 };
                             }
                             Vtiger_Helper_Js.showPnotify(params);
                             aDeferred.resolve(data);
                         }
                     );
                     return aDeferred.promise();
                 },
                 function(error, err){
                 }
             );
         });
     },

    getRecordsSelected : function(){
        var records = [];
        jQuery('.vte-user-login .listViewEntryValue input[type=checkbox]:checked').each(function() {
            records .push(jQuery(this).val());
        });
        return records;
    },

     registerImageSettingBtn : function() {
         var thisInstance = this;
         jQuery('.imgSetting').on('click', function(event){
             event.preventDefault();
             var url = jQuery(this).data('url');
             app.request.post({url:url}).then(
                 function (err,data) {
                     if(err == null){
                         app.helper.showModal(data, {cb : function(){
                             thisInstance.registerSaveImgSetting();
                         }});
                     }
                }
             );

         });
     },

     registerSaveImgSetting : function() {
         var form = jQuery('#vte-img-setting');
         form.submit(function(e) {
             jQuery.ajax({
                 type: "POST",
                 url: 'index.php',
                 data: form.serialize(), // serializes the form's elements.
                 success: function(data)
                 {
                     app.hideModalWindow();
                 }
             });
             e.preventDefault(); // avoid to execute the actual submit of the form.
         });
     },

     registerEvents : function() {
         /* For License page - Begin */
         this.registerActivateLicenseEvent();
         this.registerValidEvent();
         /* For License page - End */
        this.registerEditBtn();
        this.registerDeleteBtn();
        this.registerGenerateBtn();
        this.registerRestoreBtn();
        this.registerImageSettingBtn();
    }

}
Vtiger.Class("Settings_UserLogin_Settings_Js",{},{
    init : function() {
        this.addComponents();
        Settings_UserLogin_Js.registerEvents();
    },

    addComponents : function() {
        this.addModuleSpecificComponent('Index','Vtiger',app.getParentModuleName());
    }
});
//
// jQuery(document).ready(function(){
//     Settings_UserLogin_Js.registerEvents();
// });