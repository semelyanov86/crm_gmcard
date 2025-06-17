/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

jQuery.Class("VTEConditionalAlerts_List_Js",{
    
    triggerCreate : function(url) {
        var selectedModule = jQuery('#moduleFilter').val();
        if(selectedModule.length > 0) {
            url += '&source_module='+selectedModule
        }
        window.location.href = url;
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
								document.location.href="index.php?module=VTEConditionalAlerts&parent=Settings&view=ListAll&mode=step3";
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
						document.location.href = "index.php?module=VTEConditionalAlerts&parent=Settings&view=ListAll";
					}
				},
				function (error) {
					progressIndicatorElement.progressIndicator({'mode': 'hide'});
				}
			);
		});
	},
	/* For License page - End */
	registerFilterChangeEvent : function() {
		var thisInstance = this;
		jQuery('#moduleFilter').on('change',function(e){
			jQuery('#pageNumber').val("1");
			jQuery('#pageToJump').val('1');
			jQuery('#orderBy').val('');
			jQuery("#sortOrder").val('');
			var params = {
				module : app.getModuleName(),
				parent : app.getParentModuleName(),
				sourceModule : jQuery(e.currentTarget).val()
			};
			//Make the select all count as empty
			jQuery('#recordsCount').val('');
			//Make total number of pages as empty
			jQuery('#totalPageCount').text("");
			thisInstance.getListViewRecords(params).then(
				function(data){
					thisInstance.updatePagination();
				}
			);
		});
	},

	registerEvents : function() {
		/* For License page - Begin */
		this.registerActivateLicenseEvent();
		this.registerValidEvent();
		/* For License page - End */
	}
});

jQuery(document).ready(function(){
	var instance = new VTEConditionalAlerts_List_Js();
	instance.registerEvents();
});