/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
VTEConditionalAlerts_Edit_Js("VTEConditionalAlerts_Edit2_Js",{},{
	
	step2Container : false,
	
	advanceFilterInstance : false,
    selectedCondition:[],
	
	init : function() {
		this.initialize();
	},
	/**
	 * Function to get the container which holds all the reports step1 elements
	 * @return jQuery object
	 */
	getContainer : function() {
		return this.step2Container;
	},

	/**
	 * Function to set the reports step1 container
	 * @params : element - which represents the reports step1 container
	 * @return : current instance
	 */
	setContainer : function(element) {
		this.step2Container = element;
		return this;
	},
	
	/**
	 * Function  to intialize the reports step1
	 */
	initialize : function(container) {
		if(typeof container == 'undefined') {
			container = jQuery('#VTEConditionalAlerts_step2');
		}
		if(container.is('#VTEConditionalAlerts_step2')) {
			this.setContainer(container);
		}else{
			this.setContainer(jQuery('#VTEConditionalAlerts_step2'));
		}
        this.selectedCondition = this.getSelectedCondition();
	},
	
	calculateValues : function(){
		//handled advanced filters saved values.
		var enableFilterElement = jQuery('#enableAdvanceFilters');
		if(enableFilterElement.length > 0 && enableFilterElement.is(':checked') == false) {
			jQuery('#advanced_filter').val(jQuery('#olderConditions').val());
		} else {
			//jQuery('[name="filtersavedinnew"]').val("6");
			var advfilterlist = this.advanceFilterInstance.getValues();
			jQuery('#advanced_filter').val(JSON.stringify(advfilterlist));
		}
	},
	registerEnableFilterOption : function() {
		jQuery('[name="conditionstype"]').on('change',function(e) {
			var advanceFilterContainer = jQuery('#advanceFilterContainer');
			var currentRadioButtonElement = jQuery(e.currentTarget);
			if(currentRadioButtonElement.hasClass('recreate')){
				if(currentRadioButtonElement.is(':checked')){
					advanceFilterContainer.removeClass('zeroOpacity');
				}
			} else {
				advanceFilterContainer.addClass('zeroOpacity');
			}
		});
	},
    /**
     * Function to add new condition row
     * @params : condtionGroupElement - group where condtion need to be added
     * @return : current instance
     */
    addNewCondition : function(){
        jQuery('.btnAddConditionClf').click('click',function(e){
             var element = jQuery(e.currentTarget);
            var conditionGroup = element.closest('div.conditionGroup');
            var basicElement = jQuery('.basic',conditionGroup);
            var newRowElement = basicElement.find('.conditionRow').clone();
            jQuery('select',newRowElement).addClass('select2');
            var conditionList = jQuery('.conditionList', conditionGroup);
            conditionList.append(newRowElement);
            //change in to chosen elements
            // app.changeSelectElementView(newRowElement);
            vtUtils.applyFieldElementsView(newRowElement);
            return this;
        });

    },
    registerSelectColunmNameValue: function(){
        var thisInstance = this;
        jQuery('.allConditionContainer ').find('select[name="columnname"]').on('change',function(){
            var selected_vals = thisInstance.selectedCondition;
            var this_val = jQuery(this).val();
            if(selected_vals.length > 0 && jQuery.inArray(this_val,selected_vals) !==  -1){
                var message = app.vtranslate('The field has been choice');
                var params = {
                    text: message,
                    type: 'error'
                }
                app.helper.showSuccessNotification(params);
                jQuery(this).val('none');
                jQuery(this).trigger('liszt:updated');
                return false;
            }
            else{
                thisInstance.selectedCondition.push(this_val);
                //Add for role condition- By pham #132163
                if(this_val == "vtiger_user2role:roleid:roleid:Users_Role:V")
                {
                    var roles = jQuery("#roles").data("value");
                    var parent_div = jQuery(this).closest(".conditionRow");
                    var comparator_box = parent_div.find('select[name="comparator"]');
                    comparator_box.html($('<option>', {value: 'is', text: 'Is'}));
                    comparator_box.trigger('liszt:updated');
                    var fieldUiHolder = parent_div.find('.fieldUiHolder');
                    var selectList = jQuery("<select></select>").attr("id", "user_role_id").attr("name", "user_role_id").attr("data-value","value");
                    jQuery.each(roles, function( index, value ) {
                        selectList.append("<option value='"+index+"'>" + value + "</option>");
                    });
                    fieldUiHolder.html(selectList);
                    jQuery('#user_role_id').chosen();
                    return false;
                }
            }
        });
        //Remove condition click
        jQuery('#VTEConditionalAlerts_step2').on('click','.deleteCondition',function(e) {
            var row_delete_btn = jQuery(e.currentTarget).closest('.conditionRow');
            var this_field = row_delete_btn.find('select[name="columnname"]');
            var this_val = this_field.val();
            var index = thisInstance.selectedCondition.indexOf(this_val);
            thisInstance.selectedCondition.splice(index, 1);
            row_delete_btn.remove();
        })
    },
    getSelectedCondition: function(){
        var list_selected = [];
        jQuery('.allConditionContainer ').find('select[name="columnname"]').each(function(){
            if(jQuery(this).val()!='none') list_selected.push(jQuery(this).val());
        });
        return list_selected;
    },
    registerFormSubmit: function(){
        var thisInstance = this;
        var form_step2 = jQuery('.installationContents').find('form');
        form_step2.on('submit',function(e){
            e.preventDefault();
            thisInstance.calculateValues();
            var formData = form_step2.serializeFormData();
            jQuery('.step2Content').html(form_step2.html());
            app.helper.showProgress();

            app.request.post({data:formData}).then(
                function(err,data){
                    if(err === null) {
                        if(data) {
                            app.helper.showSuccessNotification({message : app.vtranslate('Conditional Alerts/Popups had been saved successfully')});
                            var workflowRecordElement = jQuery('[name="record"]',form);
                            if(workflowRecordElement.val() == '') {
                                workflowRecordElement.val(data.id);
                            }
                            var params = {
                                module : app.getModuleName(),
                                parent : app.getParentModuleName(),
                                view : 'Edit',
                                mode : 'Step3',
                                selected_module : data.selected_module,
                                record : data.id
                            }
                            app.request.post({'data':params}).then(function(err,data) {
                                //aDeferred.resolve(data);
                                jQuery('.installationContents').html(data);
                                thisInstance.activateHeader(3);
                                var jsStep3Instance = new VTEConditionalAlerts_Edit3_Js();
                                jsStep3Instance.registerEvents();
                                return false;
                            });
                        }
                        app.helper.hideProgress();
                    }else{
                        return false;
                    }
                }
            );
        });
    },
    registerBackButton:function(){
        var container = this.getContainer();
        container.on('click','.backStep',function(e){
            var actionParams = {
                "type":"POST",
                "module":"VTEConditionalAlerts",
                "view":"Edit",
                "mode" : "step1",
                "record":jQuery('[name="record"]').val(),
                "selected_module" : jQuery('[name="selected_module"]').val(),
                "descriptions" : jQuery('[name="descriptions"]').val(),
                "dataType":"html"
            };
            app.request.post({'data':actionParams}).then(
                function(data) {
                    if(data) {
                        jQuery('.installationContents').html(data);
                        var jsStep1Instance = new VTEConditionalAlerts_Edit_Js();
                        // app.changeSelectElementView(jQuery('.installationContents'));
                        vtUtils.applyFieldElementsView(jQuery('.installationContents'));
                        var step = jsStep1Instance.getStepValue();
                        jsStep1Instance.activateHeader(step);
                        jsStep1Instance.registerEvents();
                        return false;
                    }
                }
            );
        });
    },
    registerDisplayRolesCondition:function(){
        jQuery('.allConditionContainer ').find('select[name="columnname"]').each(function(){
            var this_val = jQuery(this).val();
            if(this_val == "vtiger_user2role:roleid:roleid:Users_Role:V"){
                //console.log(this_val);
                var roles = jQuery("#roles").data("value");
                var parent_div = jQuery(this).closest(".conditionRow");
                var comparator_box = parent_div.find('select[name="comparator"]');
                comparator_box.html($('<option>', {value: 'is', text: 'Is'}));
                comparator_box.trigger('liszt:updated');
                var fieldUiHolder = parent_div.find('.fieldUiHolder');
                var selectList = jQuery("<select></select>").attr("id", "user_role_id").attr("name", "user_role_id").attr("data-value","value");
                var savedConditions = jQuery("#savedConditions").val();
                var list_condition = jQuery.parseJSON(savedConditions);
                var saved_role = "";
                jQuery.each(list_condition, function( index, value ) {
                    //console.log(index);
                    jQuery.each(value.columns, function( index_child, value_child ) {
                        var columnname = value_child.columnname;
                        if(columnname == "vtiger_user2role:roleid:roleid:Users_Role:V"){
                            saved_role = value_child.value;
                        }
                    });
                });

                jQuery.each(roles, function( index, value ) {
                    selectList.append("<option value='"+index+"'>" + value + "</option>");
                });
                if(saved_role != ""){
                    selectList.val(saved_role);
                }
                fieldUiHolder.html(selectList);
                jQuery('#user_role_id').chosen();
                return false;
            }
        });
    },
	registerEvents : function(){
		var container = this.getContainer();
		// app.changeSelectElementView(container);
        vtUtils.applyFieldElementsView(container);
        this.advanceFilterInstance = Vtiger_AdvanceFilter_Js.getInstance(jQuery('.filterContainer',container));
		this.registerSelectColunmNameValue();
        this.registerFormSubmit();
        this.addNewCondition();
        this.registerBackButton();
        this.registerDisplayRolesCondition();
	}
});


