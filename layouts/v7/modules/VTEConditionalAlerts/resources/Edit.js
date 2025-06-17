/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
Vtiger.Class("VTEConditionalAlerts_Edit_Js",{
},{
 	/*
	 * Function to get the value of the step
	 * returns 1 or 2 or 3
	 */
	getStepValue : function(){
		var container = this.getContainer();
		return jQuery('.step',container).val();
	},
	/*
	 * Function to activate the header based on the class
	 * @params class name
	 */
	activateHeader : function(step) {
		var headersContainer = jQuery('.crumbs ');
		headersContainer.find('.active').removeClass('active');
		jQuery('#step'+step,headersContainer).addClass('active');
	},
	/*
	 * Function to register the click event for next button
	 */
	registerFormSubmitEvent : function(form) {
		var thisInstance = this;
        form.on('submit',function(e){
            e.preventDefault();
            //var form = jQuery(e.currentTarget);
            var description = jQuery('#description',form);
            if(description.val().length === 0){
                var message = app.vtranslate('JS_REQUIRED_FIELD');
                description.validationEngine('showPrompt', message , 'error','topLeft',true);
                return false;
            } else{
                app.helper.showProgress();
                var url = 'index.php?module=VTEConditionalAlerts&view=Edit';
                jQuery('.step1Content').html(thisInstance.getContainer().find('form').html());
                var actionParams = {
                    "type":"POST",
                    "url":url,
                    "dataType":"html",
                    "data" : form.serialize()
                };
                app.request.post(actionParams).then(
                    function(err,data){
                        if(err === null) {
                            jQuery('.installationContents').html(data);
                            //var jsInstance = new VTEConditionalAlerts_AdvanceFilter_Js();
                            var step = thisInstance.getStepValue();
                            thisInstance.activateHeader(step);
                            thisInstance.getPopUp();
                            var jsStep2Instance = new VTEConditionalAlerts_Edit2_Js();
                            jsStep2Instance.registerEvents();
                            app.helper.hideProgress();

                            return false;
                        }else{
                            app.helper.hideProgress();

                        }
                    }
                );
            }
        });
	},
    getPopUp : function(container) {
        var thisInstance = this;
        if(typeof container == 'undefined') {
            container = thisInstance.getContainer();
        }
        container.on('click','.getPopupUi',function(e) {
            var fieldValueElement = jQuery(e.currentTarget);
            var fieldValue = fieldValueElement.val();
            var fieldUiHolder  = fieldValueElement.closest('.fieldUiHolder');
            var valueType = fieldUiHolder.find('[name="valuetype"]').val();
            if(valueType == '') {
                valueType = 'rawtext';
            }
            var conditionsContainer = fieldValueElement.closest('.conditionsContainer');
            var conditionRow = fieldValueElement.closest('.conditionRow');
            var clonedPopupUi = conditionsContainer.find('.popupUi').clone(true,true).removeClass('popupUi').addClass('clonedPopupUi')
            clonedPopupUi.find('select').addClass('select2');
            clonedPopupUi.find('.fieldValue').val(fieldValue);
            if(fieldValueElement.hasClass('date')){
                clonedPopupUi.find('.textType').find('option[value="rawtext"]').attr('data-ui','input');
                var dataFormat = fieldValueElement.data('date-format');
                if(valueType == 'rawtext') {
                    var value = fieldValueElement.val();
                } else {
                    value = '';
                }
                var clonedDateElement = '<input type="text" class="row dateField fieldValue col-lg-4" value="'+value+'" data-date-format="'+dataFormat+'" data-input="true" >'
                clonedPopupUi.find('.fieldValueContainer').prepend(clonedDateElement);
            } else if(fieldValueElement.hasClass('time')) {
                clonedPopupUi.find('.textType').find('option[value="rawtext"]').attr('data-ui','input');
                if(valueType == 'rawtext') {
                    var value = fieldValueElement.val();
                } else {
                    value = '';
                }
                var clonedTimeElement = '<input type="text" class="row timepicker-default fieldValue col-lg-4" value="'+value+'" data-input="true" >'
                clonedPopupUi.find('.fieldValueContainer').prepend(clonedTimeElement);
            } else if(fieldValueElement.hasClass('boolean')) {
                clonedPopupUi.find('.textType').find('option[value="rawtext"]').attr('data-ui','input');
                if(valueType == 'rawtext') {
                    var value = fieldValueElement.val();
                } else {
                    value = '';
                }
                var clonedBooleanElement = '<input type="checkbox" class="row fieldValue col-lg-4" value="'+value+'" data-input="true" >';
                clonedPopupUi.find('.fieldValueContainer').prepend(clonedBooleanElement);

                var fieldValue = clonedPopupUi.find('.fieldValueContainer input').val();
                if(value == 'true:boolean' || value == '') {
                    clonedPopupUi.find('.fieldValueContainer input').attr('checked', 'checked');
                } else {
                    clonedPopupUi.find('.fieldValueContainer input').removeAttr('checked');
                }
            }
            var callBackFunction = function(data) {
                data.find('.clonedPopupUi').removeClass('hide');
                var moduleNameElement = conditionRow.find('[name="modulename"]');
                if(moduleNameElement.length > 0){
                    var moduleName = moduleNameElement.val();
                    data.find('.useFieldElement').addClass('hide');
                    data.find('[name="'+moduleName+'"]').removeClass('hide');
                }
                // app.changeSelectElementView(data);
                // app.registerEventForDatePickerFields(data);
                // app.registerEventForTimeFields(data);
                vtUtils.applyFieldElementsView(data);

                thisInstance.postShowModalAction(data,valueType);
                thisInstance.registerChangeFieldEvent(data);
                thisInstance.registerSelectOptionEvent(data);
                thisInstance.registerPopUpSaveEvent(data,fieldUiHolder);
                thisInstance.registerRemoveModalEvent(data);
                data.find('.fieldValue').filter(':visible').trigger('focus');
            };
            conditionsContainer.find('.clonedPopUp').html(clonedPopupUi);
            jQuery('.clonedPopupUi').on('shown', function () {
                if(typeof callBackFunction == 'function'){
                    callBackFunction(jQuery('.clonedPopupUi',conditionsContainer));
                }
            });
            jQuery('.clonedPopUp',conditionsContainer).find('.clonedPopupUi').modal();
        });
    },
    registerRemoveModalEvent : function(data) {
        data.on('click','.closeModal',function(e) {
            data.modal('hide');
        });
    },
    postShowModalAction : function(data,valueType) {
        if(valueType == 'fieldname') {
            jQuery('.useFieldContainer',data).removeClass('hide');
            jQuery('.textType',data).val(valueType).trigger('liszt:updated');
        } else if(valueType == 'expression') {
            jQuery('.useFieldContainer',data).removeClass('hide');
            jQuery('.useFunctionContainer',data).removeClass('hide');
            jQuery('.textType',data).val(valueType).trigger('liszt:updated');
        }
        jQuery('#'+valueType+'_help',data).removeClass('hide');
        var uiType = jQuery('.textType',data).find('option:selected').data('ui');
        jQuery('.fieldValue',data).hide();
        jQuery('[data-'+uiType+']',data).show();
    },
    registerChangeFieldEvent : function(data) {
        jQuery('.textType',data).on('change',function(e){
            var valueType =  jQuery(e.currentTarget).val();
            var useFieldContainer = jQuery('.useFieldContainer',data);
            var useFunctionContainer = jQuery('.useFunctionContainer',data);
            var uiType = jQuery(e.currentTarget).find('option:selected').data('ui');
            jQuery('.fieldValue',data).hide();
            jQuery('[data-'+uiType+']',data).show();
            if(valueType == 'fieldname') {
                useFieldContainer.removeClass('hide');
                useFunctionContainer.addClass('hide');
            } else if(valueType == 'expression') {
                useFieldContainer.removeClass('hide');
                useFunctionContainer.removeClass('hide');
            } else {
                useFieldContainer.addClass('hide');
                useFunctionContainer.addClass('hide');
            }
            jQuery('.helpmessagebox',data).addClass('hide');
            jQuery('#'+valueType+'_help',data).removeClass('hide');
            data.find('.fieldValue').val('');
        });
    },

    registerSelectOptionEvent : function(data) {
        jQuery('.useField,.useFunction',data).on('change',function(e){
            var currentElement = jQuery(e.currentTarget);
            var newValue = currentElement.val();
            var oldValue  = data.find('.fieldValue').filter(':visible').val();
            if(currentElement.hasClass('useField')){
                if(oldValue != ''){
                    var concatenatedValue = oldValue+' '+newValue;
                } else {
                    concatenatedValue = newValue;
                }
            } else {
                concatenatedValue = oldValue+newValue;
            }
            data.find('.fieldValue').val(concatenatedValue);
            currentElement.val('').trigger('liszt:updated');
        });
    },
    registerPopUpSaveEvent : function(data,fieldUiHolder) {
        jQuery('[name="saveButton"]',data).on('click',function(e){
            var valueType = jQuery('.textType',data).val();

            fieldUiHolder.find('[name="valuetype"]').val(valueType);
            var fieldValueElement = fieldUiHolder.find('.getPopupUi');
            if(valueType != 'rawtext'){
                fieldValueElement.removeAttr('data-validation-engine');
                fieldValueElement.removeClass('validate[funcCall[Vtiger_Base_Validator_Js.invokeValidation]]');
            }else{
                fieldValueElement.addClass('validate[funcCall[Vtiger_Base_Validator_Js.invokeValidation]]');
                fieldValueElement.attr('data-validation-engine','validate[funcCall[Vtiger_Base_Validator_Js.invokeValidation]]');
            }
            var fieldType = data.find('.fieldValue').filter(':visible').attr('type');
            var fieldValue = data.find('.fieldValue').filter(':visible').val();
            //For checkbox field type, handling fieldValue
            if(fieldType == 'checkbox'){
                if(data.find('.fieldValue').filter(':visible').is(':checked')) {
                    fieldValue = 'true:boolean';
                } else {
                    fieldValue = 'false:boolean';
                }
            }
            fieldValueElement.val(fieldValue);
            data.modal('hide');
            fieldValueElement.validationEngine('hide');
        });
    },

    getContainer:function(){
        return jQuery('.installationContents');
    },
  	registerEvents : function(){
		var form = jQuery('.installationContents').find('form');
        var step = this.getStepValue();
        this.activateHeader(step);
		this.registerFormSubmitEvent(form);
	}
});
jQuery(document).ready(function(){
    var jsInstance = new VTEConditionalAlerts_Edit_Js();
    jsInstance.registerEvents();

    // Add css
    $("head").append("<style>#addTaskContainer{border: 1px solid #9c9c9c; box-shadow: 10px 10px 5px #9c9c9c;}</style>");
});
