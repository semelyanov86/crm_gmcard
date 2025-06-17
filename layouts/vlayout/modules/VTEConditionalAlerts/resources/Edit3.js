/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
VTEConditionalAlerts_Edit_Js("VTEConditionalAlerts_Edit3_Js",{},{
	
	step3Container : false,
	
	advanceFilterInstance : false,
	
	ckEditorInstance : false,
	
	fieldValueMap : false,
	
	init : function() {
		this.initialize();
	},
	/**
	 * Function to get the container which holds all the reports step1 elements
	 * @return jQuery object
	 */
	getContainer : function() {
		return this.step3Container;
	},

	/**
	 * Function to set the reports step1 container
	 * @params : element - which represents the reports step1 container
	 * @return : current instance
	 */
	setContainer : function(element) {
		this.step3Container = element;
		return this;
	},
	
	/**
	 * Function  to intialize the reports step1
	 */
	initialize : function(container) {
		if(typeof container == 'undefined') {
			container = jQuery('#clf_step3');
		}
		if(container.is('#clf_step3')) {
			this.setContainer(container);
		}else{
			this.setContainer(jQuery('#clf_step3'));
		}
	},
	
	registerEditTaskEvent : function() {
		var thisInstance = this;
		var container = this.getContainer();
		container.on('click','[data-url]',function(e) {
			var currentElement = jQuery(e.currentTarget);
			var params = currentElement.data('url');
			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			AppConnector.request(params).then(function(data) {
				var callBackFunction = function(data) {
					app.showScrollBar(jQuery('#addTaskContainer').find('#scrollContainer'),{
						height : '450px'
					});
					var taskType = jQuery('#taskType').val();
					var functionName = 'register'+taskType+'Events';
					if(typeof thisInstance[functionName] != 'undefined' ) {
						thisInstance[functionName].apply(thisInstance);
					}
					thisInstance.registerSaveTaskSubmitEvent(taskType);
					jQuery('#saveTask').validationEngine(app.validationEngineOptions);
					//thisInstance.registerFillTaskFieldsEvent();
					//thisInstance.registerCheckSelectDateEvent();
				}
				app.showModalWindow(data,function(){
					if(typeof callBackFunction == 'function') {
						callBackFunction(data)
					}
				},{'min-width' : '900px'});
			});
		});
	},
	registerSaveTaskSubmitEvent : function(taskType) {
		var thisInstance = this;
		jQuery('#saveTask').on('submit',function(e) {
			var form = jQuery(e.currentTarget);
			var validationResult = form.validationEngine('validate');
            var checkDuplicateFieldsSelected = thisInstance.checkDuplicateFieldsSelected();
			if(validationResult == true && checkDuplicateFieldsSelected == true) {
				var customValidationFunctionName = taskType+'CustomValidation';
				if(typeof thisInstance[customValidationFunctionName] != 'undefined') {
					 var result = thisInstance[customValidationFunctionName].apply(thisInstance);
					 if(result != true) {
						 var params = {
							title : app.vtranslate('JS_MESSAGE'),
							text: result,
							animation: 'show',
							type: 'error'
						}
						Vtiger_Helper_Js.showPnotify(params);
						e.preventDefault();
						return;
					 }
				}
				var preSaveActionFunctionName = 'preSave'+taskType;
				if(typeof thisInstance[preSaveActionFunctionName] != 'undefined' ) {
					thisInstance[preSaveActionFunctionName].apply(thisInstance,[taskType]);
				}
				var params  = form.serializeFormData();
				params.options = thisInstance.getFieldOptionValue();
				AppConnector.request(params).then(function(data){
					if(data.result){
						thisInstance.getTaskList();
						app.hideModalWindow();
					}
				});
			}
			e.preventDefault();
		})
	},
	getFieldOptionValue:function(){
        var listOfFields = [];
        jQuery( ".option-row" ).each(function() {
           var field_selected = jQuery(this).find('select.useField').val();
           var option_selected = jQuery(this).find('select.fieldOption').val();
           var singleField = {}
           singleField['field'] = field_selected;
           singleField['option'] = option_selected;
           listOfFields.push(singleField);
        });
        return JSON.stringify(listOfFields);
    },
	checkDuplicateFieldsSelected : function() {
		var selectedFieldNames = jQuery('#saveTask').find('.option-row').find('select.useField');
		var result = true;
		var failureMessage = app.vtranslate('JS_SAME_FIELDS_SELECTED_MORE_THAN_ONCE');
		jQuery.each(selectedFieldNames, function(i, ele) {
			var fieldName = jQuery(ele).attr("value");
			var fields = jQuery("[name="+fieldName+"]").not(':hidden');
			if(fields.length > 1) {
				result = failureMessage;
				return false;
			}
		});
		return result;
	},
	/**
	 * Function to check if the field selected is empty field
	 * @params : select element which represents the field
	 * @return : boolean true/false
	 */
	isEmptyFieldSelected : function(fieldSelect) {
		var selectedOption = fieldSelect.find('option:selected');
		//assumption that empty field will be having value none
		if(selectedOption.val() == 'none'){
			return true;
		}
		return false;
	},
    registerFilterSelectBox:function(select_box){
        var listOfFields = [];
        jQuery( ".option-row" ).each(function() {
            var field_selected = jQuery(this).find('select.useField').val();
            select_box.find('[value="'+field_selected+'"]').remove();
        });
    },
	getTaskList : function() {
		var container = this.getContainer();
        var selected_module = jQuery('[name="selected_module_name"]',container).val();
		var params = {
			module : app.getModuleName(),
			parent : app.getParentModuleName(),
			view : 'TasksList',
			record : jQuery('[name="record"]',container).val(),
            selected_module_name : jQuery('[name="selected_module_name"]',container).val()
		}
		var progressIndicatorElement = jQuery.progressIndicator({
			'position' : 'html',
			'blockInfo' : {
				'enabled' : true
			}
		});
		AppConnector.request(params).then(function(data){
			jQuery('#taskListContainer').html(data);
			progressIndicatorElement.progressIndicator({mode : 'hide'});
		});
	},
	registerTaskStatusChangeEvent : function() {
		var container = this.getContainer();
        var massage = 'Task had been active successfully';
        var status = '1';
		container.on('change','.taskStatus',function(e) {
			var currentStatusElement = jQuery(e.currentTarget);
			var url = currentStatusElement.data('statusurl');
			if(!currentStatusElement.is(':checked')){
                massage = 'Task had been in-active successfully';
                status = '0';
			}
            else{
                massage = 'Task had been active successfully';
                status = '1';
            }
			var progressIndicatorElement = jQuery.progressIndicator({
				'position' : 'html',
				'blockInfo' : {
					'enabled' : true
				}
			});
			AppConnector.request(url).then(function(data) {
				if(data.result == "ok") {
					var params = {
						title : app.vtranslate('JS_MESSAGE'),
						text: app.vtranslate(massage),
						animation: 'show',
						type: 'success'
					};
					Vtiger_Helper_Js.showPnotify(params);
                    var new_url = url.split("active");
                    currentStatusElement.data('statusurl',new_url[0] + "active="+status);
				}
				progressIndicatorElement.progressIndicator({mode : 'hide'});
			});
			e.stopImmediatePropagation();
		});
	},
	
	registerTaskDeleteEvent : function() {
		var thisInstance = this;
		var container = this.getContainer();
		container.on('click','.deleteTask',function(e) {
			var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
			Vtiger_Helper_Js.showConfirmationBox({
				'message' : message
			}).then(
				function() {
					var currentElement = jQuery(e.currentTarget);
					var deleteUrl = currentElement.data('deleteurl');
					AppConnector.request(deleteUrl).then(function(data){
						if(data.result == 'ok'){
							thisInstance.getTaskList();
							var params = {
								title : app.vtranslate('JS_MESSAGE'),
								text: app.vtranslate('Task deleted successfully'),
								animation: 'show',
								type: 'success'
							};
							Vtiger_Helper_Js.showPnotify(params);
						}
					});
				});
		});
	},

	registerFillTaskFieldsEvent: function() {
		jQuery('#saveTask').on('change','.task-fields',function(e) {
			var currentElement = jQuery(e.currentTarget);
			var inputElement = currentElement.closest('.row-fluid').find('.fields');
			var oldValue = inputElement.val();
			var newValue = oldValue+currentElement.val();
			inputElement.val(newValue);
		})
	},
	registerDeleteConditionEvent : function() {
		jQuery('#saveTask').on('click','.deleteCondition',function(e) {
			jQuery(e.currentTarget).closest('.conditionRow').remove();
		})
	},

	getModuleName : function() {
		return app.getModuleName();
	},

    registerAddTask:function(){
        var thisInstance = this;
        jQuery('html').on('click','.addClfFieldBtn',function(){
            var parent_div = jQuery('#saveTask').find('span.clf-container');
            var new_row = jQuery('.basicAddFieldContainer').clone().removeClass('basicAddFieldContainer hide').addClass('option-row');
            var field_select_box = jQuery('select.useField',new_row);
            thisInstance.registerFilterSelectBox(field_select_box);
            field_select_box.addClass('chzn-select');
            jQuery('select.fieldOption',new_row).addClass('select2');

            parent_div.append(new_row);
            parent_div.append('<br>');
            //change in to chosen elements
            app.changeSelectElementView(new_row);
            app.showSelect2ElementView(new_row.find('.select2'));

        });
    },
    registerDelTask:function(){
        jQuery('html').on('click','.delTask',function(){
            var parent_div = jQuery(this).closest('div');
            parent_div.remove();
        });
    },
    registerBackTo2Button:function(){
        var container = this.getContainer();
        container.on('click','.backStep',function(e){
            var actionParams = {
                "type":"POST",
                "module":"VTEConditionalAlerts",
                "view":"Edit",
                "mode" : "step2",
                "record":jQuery('[name="record"]').val(),
                "dataType":"html"
            };
            AppConnector.request(actionParams).then(
                function(data) {
                    if(data) {
                        jQuery('.controlLayoutFieldsContents').html(data);
                        var step1Instance = new VTEConditionalAlerts_Edit_Js();
                        var step = step1Instance.getStepValue();
                        step1Instance.activateHeader(step);
                        var jsStep2Instance = new VTEConditionalAlerts_Edit2_Js();
                        jsStep2Instance.registerEvents();
                        return false;
                    }
                }
            );
        });
    },
	registerEvents : function(){
		var container = this.getContainer();
		//app.changeSelectElementView(container);
		this.registerEditTaskEvent();
		this.registerTaskStatusChangeEvent();
		this.registerTaskDeleteEvent();
        this.registerAddTask();
        this.registerDelTask();
        this.registerBackTo2Button();
	}
});