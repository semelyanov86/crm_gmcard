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
                                 document.location.href="index.php?module=UserLogin&parent=Settings&view=Settings&mode=step3";
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
                         document.location.href = "index.php?module=UserLogin&parent=Settings&view=Settings";
                     }
                 },
                 function (error) {
                     progressIndicatorElement.progressIndicator({'mode': 'hide'});
                 }
             );
         });
     },
     /* For License page - End */

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
                 params['record'] = records;
                 var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
                 Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(function(data) {
                         var aDeferred = jQuery.Deferred();
                         var progressIndicatorElement = jQuery.progressIndicator({
                             'position' : 'html',
                             'blockInfo' : {
                                 'enabled' : true
                             }
                         });
                         AppConnector.request(params).then(
                             function(data) {
                                 progressIndicatorElement.progressIndicator({
                                     'mode' : 'hide'
                                 });
                                 aDeferred.resolve(data);
                                 window.location.reload();
                             },
                             function(error,err){
                                 progressIndicatorElement.progressIndicator({
                                     'mode' : 'hide'
                                 });
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
                 Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(function(data) {
                         var aDeferred = jQuery.Deferred();
                         var progressIndicatorElement = jQuery.progressIndicator({
                             'position' : 'html',
                             'blockInfo' : {
                                 'enabled' : true
                             }
                         });
                         AppConnector.request(url).then(
                             function(data) {
                                 progressIndicatorElement.progressIndicator({
                                     'mode' : 'hide'
                                 });
                                 if(data.result === true){
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
                             },
                             function(error,err){
                                 progressIndicatorElement.progressIndicator({
                                     'mode' : 'hide'
                                 });
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

     registerRestoreBtn : function() {
         var thisInstance = this;
         jQuery('.restoreButton').on('click', function(event){
             event.preventDefault();
             var url = jQuery(this).data('url');
             var message = app.vtranslate('LBL_RESTORE_CONFIRMATION');
             Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(function(data) {
                     var aDeferred = jQuery.Deferred();
                     var progressIndicatorElement = jQuery.progressIndicator({
                         'position' : 'html',
                         'blockInfo' : {
                             'enabled' : true
                         }
                     });
                     AppConnector.request(url).then(
                         function(data) {
                             progressIndicatorElement.progressIndicator({
                                 'mode' : 'hide'
                             });
                             if(data.result === true){
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
                         },
                         function(error,err){
                             progressIndicatorElement.progressIndicator({
                                 'mode' : 'hide'
                             });
                             aDeferred.reject(error,err);
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
             app.showModalWindow(null, url, function(){
                 thisInstance.registerSaveImgSetting();
             });
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
        this.registerEditBtn();
        this.registerDeleteBtn();
        this.registerGenerateBtn();
        this.registerRestoreBtn();
        this.registerImageSettingBtn();
        /* For License page - Begin */
        this.registerActivateLicenseEvent();
        this.registerValidEvent();
        /* For License page - End */
    }

}

jQuery(document).ready(function(){
    Settings_UserLogin_Js.registerEvents();
});