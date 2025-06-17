/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
VReports_Edit_Js("VReports_Edit3_Js",{},{
	
	step3Container : false,
	
	advanceFilterInstance : false,
	
	init : function() {
		this.initialize();
	},
	/**
	 * Function to get the container which holds all the report step3 elements
	 * @return jQuery object
	 */
	getContainer : function() {
		return this.step3Container;
	},

	/**
	 * Function to set the report step3 container
	 * @params : element - which represents the report step3 container
	 * @return : current instance
	 */
	setContainer : function(element) {
		this.step3Container = element;
		return this;
	},
	
	/**
	 * Function  to intialize the reports step3
	 */
	initialize : function(container) {
		if(typeof container == 'undefined') {
			container = jQuery('#report_step3');
		}
		
		if(container.is('#report_step3')) {
			this.setContainer(container);
		}else{
			this.setContainer(jQuery('#report_step3'));
		}
	},
	
	calculateValues : function(){
        var container = this.getContainer();
        this.advanceFilterInstance = VReports_AdvanceFilter_Js.getInstance(jQuery('.filterContainer:not(#conditionClone > .filterContainer)',container),false);
		//handled advanced filters saved values.
		var advfilterlist = this.advanceFilterInstance.getValues();
		jQuery('#advanced_filter').val(JSON.stringify(advfilterlist));
	},
	
	registerSubmitEvent : function(){
		var thisInstance = this;
		var form = this.getContainer();
		form.submit(function(e){
			thisInstance.calculateValues();
		});
	},
    registerEventAddGroup : function () {
        var thisInstance = this;
        jQuery('button[name=addgroup]').on('click',function () {
        	var container = thisInstance.getContainer();
        	var filterConditionContainer = container.find('#filter_conditions');
            var filterClone = container.find('div#conditionClone').clone();
            var filterContainer = container.find('.filterContainer:not(#conditionClone > .filterContainer)');
            var filterContainerLength = filterContainer.length;
            filterClone.find('.filterContainer').addClass('groupCondition'+filterContainerLength);
            filterClone.find('select.group-condition').attr('name','groupCondition'+filterContainerLength);
            filterClone.find('button.deleteGroup').attr('group-name','groupCondition'+filterContainerLength);
            filterClone.find('div.select2').remove();
            filterClone.find('.group-condition').addClass('group-condition'+filterContainerLength);
            filterConditionContainer.append(filterClone.html());
            vtUtils.showSelect2ElementView(filterConditionContainer.find('.group-condition'+filterContainerLength));
            VReports_AdvanceFilter_Js.getInstance(jQuery('.groupCondition'+filterContainerLength,container),true);
            thisInstance.registerEventRemoveGroup();
        });
    },
    registerEventRemoveGroup : function () {
        var thisInstance = this;
        jQuery('button.deleteGroup').on('click',function (e) {
            var currentTarget = $(e.currentTarget);
            var closestButtonDelete = currentTarget.closest('div.button-action');
            closestButtonDelete.next().remove();
            closestButtonDelete.remove();
            thisInstance.registerConditionBlockChangeEvent();
        });
    },
    registerConditionBlockChangeEvent : function() {
        jQuery('.reportsDetailHeader').find('#groupbyfield,#legend_position,#datafields,.columnname,.comparator,[name="columnname"],[name="comparator"]').on('change', function() {
            jQuery('.reportActionButtons').removeClass('hide');
        });
        jQuery('.fieldUiHolder').find('[data-value="value"]').on('change input', function() {
            jQuery('.reportActionButtons').removeClass('hide');
        });
        jQuery('.deleteCondition ').on('click', function() {
            jQuery('.reportActionButtons').removeClass('hide');
        });
        jQuery('.deleteGroup').on('click', function() {
            jQuery('.reportActionButtons').removeClass('hide');
        });
        jQuery(document).on('datepicker-change', function() {
            jQuery('.reportActionButtons').removeClass('hide');
        });
    },
	registerEvents : function(){
		var container = this.getContainer();
		vtUtils.applyFieldElementsView(container);
        this.advanceFilterInstance = VReports_AdvanceFilter_Js.getInstance(jQuery('.filterContainer:not(#conditionClone > .filterContainer)',container),true);
		this.registerSubmitEvent();
		this.registerEventAddGroup();
		this.registerEventRemoveGroup();
	}
});
	



