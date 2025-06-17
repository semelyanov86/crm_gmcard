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
			app.helper.showProgress();
			app.request.post({'url':params}).then(
				function(err,data){
					if(err === null) {
						var callBackFunction = function(data) {
							// app.helper.showScrollBar(jQuery('#addTaskContainer').find('#scrollContainer'),{
							// 	height : '450px'
							// });
							var taskType = jQuery('#taskType').val();
							var functionName = 'register'+taskType+'Events';
							if(typeof thisInstance[functionName] != 'undefined' ) {
								thisInstance[functionName].apply(thisInstance);
							}
                            jQuery(".switch-btn").bootstrapSwitch();
                            var ckEditorInstance = new Vtiger_CkEditor_Js();
                            ckEditorInstance.loadCkEditor(jQuery('#description'));
							thisInstance.registerSaveTaskSubmitEvent(taskType);
							// jQuery('#saveTask').validationEngine(app.validationEngineOptions);
							thisInstance.registerUseThisFieldEvent();
							//thisInstance.registerCheckSelectDateEvent();
						};
						// app.showModalWindow(data,function(){
						// 	if(typeof callBackFunction == 'function') {
						// 		callBackFunction(data)
						// 	}
						// },{'min-width' : '900px'});
						app.helper.showModal(data,{'cb' : function(){
							if(typeof callBackFunction == 'function') {
								callBackFunction(data);
								app.helper.hideProgress();

							}
						}});
					}else{
					}
				}
			);
		});
	},
	registerSaveTaskSubmitEvent : function(taskType) {
		var thisInstance = this;
		jQuery('#saveTask').on('submit',function(e) {
			e.preventDefault();
			var form = jQuery(e.currentTarget);
			
			var action_title = jQuery('[name="action_title"]',form);

			if(action_title.val().length === 0){
				var message = app.vtranslate('JS_REQUIRED_FIELD');
                action_title.validationEngine('showPrompt', message , 'error','topLeft',true);
				return false;
			}
            var donot_allow_to_save = jQuery("input[name='donot_allow_to_save']").bootstrapSwitch('state');
            if(donot_allow_to_save === true)  jQuery("input[name='donot_allow_to_save']").val(1);
            else jQuery("input[name='donot_allow_to_save']").val(0);

            var alert_while_edit = jQuery("input[name='alert_while_edit']").bootstrapSwitch('state');
            if(alert_while_edit === true)  jQuery("input[name='alert_while_edit']").val(1);
            else jQuery("input[name='alert_while_edit']").val(0);

            var alert_when_open = jQuery("input[name='alert_when_open']").bootstrapSwitch('state');
            if(alert_when_open === true)  jQuery("input[name='alert_when_open']").val(1);
            else jQuery("input[name='alert_when_open']").val(0);

            var alert_on_save = jQuery("input[name='alert_on_save']").bootstrapSwitch('state');
            if(alert_on_save === true)  jQuery("input[name='alert_on_save']").val(1);
            else jQuery("input[name='alert_on_save']").val(0);

            var params  = form.serializeFormData();
            var des = CKEDITOR.instances['description'].getData();
            params.description  = des;
            app.request.post({data:params}).then(
                function(err,data){
                    if(err === null) {
                        if(data){
                            thisInstance.getTaskList();
                            app.helper.hideProgress();
                            jQuery('.myModal').modal('hide');
                            jQuery('#addMoreTaskForAlertPopup').addClass('hide');
                        }
                    }else{
                        app.helper.hideProgress();
                        // to do
                    }
                }
            );
			// }
		})
	},
    registerUseThisFieldEvent : function(data) {
        jQuery('.useThisField').on('change',function(e){
            var currentElement = jQuery(e.currentTarget);
            var newValue = currentElement.val();
            CKEDITOR.instances['description'].insertText('$' + newValue +'$');
        });
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
		};
		app.helper.showProgress();

		app.request.post({'data': params}).then(
			function(err,data){
				if(err === null) {
					jQuery('#taskListContainer').html(data);
					app.helper.hideProgress();
				}else{
					// to do
				}
			}

		);
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
			app.helper.showProgress();

			app.request.post({'url': url}).then(
				function(err,data){
					if(err === null) {
						if(data == "ok") {
							var params = {
								title : app.vtranslate('JS_MESSAGE'),
								text: app.vtranslate(massage),
								animation: 'show',
								type: 'success'
							};
							// Vtiger_Helper_Js.showPnotify(params);
							app.helper.showInfoMessage(params)
							var new_url = url.split("active");
							currentStatusElement.data('statusurl',new_url[0] + "active="+status);
						}
						app.helper.hideProgress();
					}else{
						// to do
					}
				}
			);
			e.stopImmediatePropagation();
		});
	},
	
	registerTaskDeleteEvent : function() {
		var thisInstance = this;
		var container = this.getContainer();
		container.on('click','.deleteTask',function(e) {
			var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
			app.helper.showConfirmationBox({
				'message' : message
			}).then(
				function() {
					var currentElement = jQuery(e.currentTarget);
					var deleteUrl = currentElement.data('deleteurl');
					app.request.post({'url':deleteUrl}).then(
						function(err,data){
							if(err === null) {
								if(data == 'ok'){
									thisInstance.getTaskList();
									var params = {
										title : app.vtranslate('JS_MESSAGE'),
										text: app.vtranslate('Task deleted successfully'),
										animation: 'show',
										type: 'success'
									};
									// Vtiger_Helper_Js.showPnotify(params);
									app.helper.showInfoMessage(params)
                                    jQuery('#addMoreTaskForAlertPopup').removeClass('hide');
								}
							}else{
								// to do
							}
						}
					);
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
        jQuery('html').on('click','.addCapBtn',function(){
            var parent_div = jQuery('#saveTask').find('span.clf-container');
            var new_row = jQuery('.basicAddFieldContainer').clone().removeClass('basicAddFieldContainer hide').addClass('option-row');
            var field_select_box = jQuery('select.useField',new_row);
            thisInstance.registerFilterSelectBox(field_select_box);
            field_select_box.addClass('select2');
            jQuery('select.fieldOption',new_row).addClass('select2');

            parent_div.append(new_row);
            parent_div.append('<br>');
            //change in to chosen elements
            // app.changeSelectElementView(new_row);
            // app.showSelect2ElementView(new_row.find('.select2'));
			vtUtils.applyFieldElementsView(new_row);

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
            app.request.post({'data':actionParams}).then(
				function(err,data){
					if(err === null) {
						if(data) {
							jQuery('.installationContents').html(data);
							var step1Instance = new VTEConditionalAlerts_Edit_Js();
							var step = step1Instance.getStepValue();
							step1Instance.activateHeader(step);
							var jsStep2Instance = new VTEConditionalAlerts_Edit2_Js();
							jsStep2Instance.registerEvents();
							return false;
						}
					}else{
						// to do
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