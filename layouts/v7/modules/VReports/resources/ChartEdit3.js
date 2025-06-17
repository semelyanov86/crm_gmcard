/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
VReports_Edit3_Js("VReports_ChartEdit3_Js",{

	registerFieldForChosen : function() {
		vtUtils.showSelect2ElementView(jQuery('#groupbyfield'));
		vtUtils.showSelect2ElementView(jQuery('#datafields'));
	},

	initSelectValues : function() {
		var groupByField = jQuery('#groupbyfield');
		var dataFields = jQuery('#datafields');
		var sortBy = jQuery('#sort_by');
        var sortByVal = jQuery('input[name=sort_by]').val();
        var orderByVal = jQuery('input[name=order_by]').val();
		var groupByFieldValue = jQuery('input[name=groupbyfield]').val();
		var dataFieldsValue = jQuery('input[name=datafields]').val();

		var groupByHTML = jQuery('#groupbyfield_element').clone().html();
		var dataFieldsHTML = jQuery('#datafields_element').clone().html();

		groupByField.html(groupByHTML);
		dataFields.html(dataFieldsHTML);

		if (orderByVal)
            $('select[name="order_by"]').val(orderByVal).select2();

		if(dataFieldsValue)
			dataFieldsValue = JSON.parse(dataFieldsValue);

		var selectedChartType = jQuery('input[name=charttype]').val();

		groupByField.select2().select2("val", groupByFieldValue);

		if(selectedChartType == 'pieChart' || selectedChartType == 'doughnutChart' || selectedChartType == 'funnelChart') {
			if(!dataFieldsValue){
				dataFieldsValue = dataFields.find('option:first').val();
			}
            if((selectedChartType == 'pieChart' || selectedChartType == 'doughnutChart')){
                if($('#display_label').find('.pie-label').length == 0){
                    var option = "<option class='pie-label' value='2' >Yes - Data (Percent)</option>"
					+"<option class='pie-label' value='3' selected >Yes - Data</option>"
					+ "<option class='pie-label' value='4' selected >Yes - Group Name</option>";
                    $('#display_label').append(option);
                    $('#display_label').find('[value="1"]').addClass('hide');
                }
                var displayLabelValue = $('#display_label').data('value');
                if(displayLabelValue != ''){
                    $('#display_label').select2('val',displayLabelValue);
				}else{
                    $('#display_label').select2('val',3);
                }
            }else{
                $('#display_label').find('.pie-label').remove();
                $('#display_label').find('[value="1"]').removeClass('hide');
                $('#display_label').select2('val',1);
			}
			dataFields.attr('multiple', false).select2().select2("val", dataFieldsValue[0]);
		} else if(dataFieldsValue && dataFieldsValue[0]) {
            $('#display_label').find('.pie-label').remove();
            $('#display_label').find('[value="1"]').removeClass('hide');
            $('#display_label').select2('val',1);
			dataFields.attr('multiple', true).select2({maximumSelectionSize: 3}).select2("val", dataFieldsValue);

			var option = '';
			var res = dataFields.find(':selected').toArray().map(item => item.text).join();
            $.each(dataFieldsValue, function (index, value) {
                option += '<option value='+value+'>'+res.split(",")[index]+'</option>';
            });
            if(sortBy && sortByVal){
                sortBy.attr('multiple', true).html(option);
                sortBy.append(groupByField.find(':selected').clone());
                sortBy.attr('multiple', true).select2("val", JSON.parse(sortByVal));
			}

		}

		if(selectedChartType) {
			jQuery('ul[name=charttab] li.active').removeClass('active');
			jQuery('ul[name=charttab] li a[data-type='+selectedChartType+']').addClass('active contentsBackground backgroundColor').trigger('click');
		} else {
			jQuery('ul[name=charttab] li a[data-type=pieChart]').addClass('contentsBackground backgroundColor').trigger('click'); // by default piechart should be selected
		}

		var primaryModule = jQuery('input[name="primary_module"]').val();
		var inventoryModules = ['Invoice', 'Quotes', 'PurchaseOrder', 'SalesOrder'];
		var secodaryModules = jQuery('input[name="secondary_modules"]').val();
		var secondaryIsInventory = false;
		inventoryModules.forEach(function (entry) {
			if (secodaryModules.indexOf(entry) != -1) {
				secondaryIsInventory = true;
			}
		});
		if ((jQuery.inArray(primaryModule, inventoryModules) !== -1 || secondaryIsInventory) && selectedChartType !== 'pieChart') {
			var reg = new RegExp(/vtiger_inventoryproductrel*/);
			if (dataFields.val() && reg.test(dataFields.val())) {
				jQuery('#datafields option').not('[value^="vtiger_inventoryproductrel"]').remove();
			} else {
				jQuery('#datafields option[value^="vtiger_inventoryproductrel"]').remove();
			}
		}
	}

},{
	initialize : function(container) {
		if(app.getViewName() == "PivotEdit"){
            this.setContainer(jQuery('#pivot_report_step4'));
		}else{
            if(typeof container == 'undefined') {
                container = jQuery('#chart_report_step3');
            }
            if(container.is('#chart_report_step3')) {
                this.setContainer(container);
            } else {
                this.setContainer(jQuery('#chart_report_step3'));
            }
		}
	},

	registerForChartTabClick : function() {
		var dataFields = jQuery('#datafields');
		var displayLabel = jQuery('#display_label');
		var thisInstance = this;

		jQuery('ul[name=charttab] li a').on('click', function(e){
			var chartType = jQuery(e.currentTarget).data('type');
			if(chartType == 'pieChart' || chartType == 'doughnutChart' || chartType == 'funnelChart') {
                displayLabel.select2('val',1);
				var dataFieldsValue = dataFields.val();
				var dataFieldsHTML = jQuery('#datafields_element').clone().html();
				dataFields.html(dataFieldsHTML);
				if(!dataFieldsValue){
					dataFieldsValue = dataFields.find('option:first').val();
				}
                if((chartType == 'pieChart' || chartType == 'doughnutChart')){
                    if($('#display_label').find('.pie-label').length == 0){
                        var option = "<option class='pie-label' value='2' >Yes - Data (Percent)</option>"
                            +"<option class='pie-label' value='3' selected >Yes - Data</option>"
                            + "<option class='pie-label' value='4' selected >Yes - Group Name</option>";
                        $('#display_label').append(option);
                        $('#display_label').find('[value="1"]').addClass('hide');
                    }
                    var displayLabelValue = $('#display_label').data('value');
                    if(displayLabelValue != ''){
                        $('#display_label').select2('val',displayLabelValue);
                    }else{
                        $('#display_label').select2('val',3);
                    }
                }else{
                    $('#display_label').find('.pie-label').remove();
                    $('#display_label').find('[value="1"]').removeClass('hide');
                    $('#display_label').select2('val',1);
                }
                dataFields.attr('multiple', false).select2().select2("val", dataFieldsValue);
                var rename_field = $('[name="rename_field_chart[]"]');
                $.each(rename_field, function (idx, val) {
                    var dataSelected = $(val).data('selected-chart');
                    if (valueFocus == null) {
                        $(val).closest('tr').remove();
                        return;
                    }
                    if (!valueFocus.includes(dataSelected)) {
                        $(val).closest('tr').remove();
                    }
                });
			} else {
				if(chartType == 'barFunnelChart'){
                    displayLabel.select2('val',0);
				}else{
                    displayLabel.select2('val',1);
				}
				if (thisInstance.isInventoryModule) {
					var reg = new RegExp(/vtiger_inventoryproductrel*/);
					if (dataFields.val() && reg.test(dataFields.val())) {
						jQuery('#datafields option').not('[value^="vtiger_inventoryproductrel"]').remove();
					} else {
						jQuery('#datafields option[value^="vtiger_inventoryproductrel"]').remove();
					}
				}
                $('#display_label').find('.pie-label').remove();
                $('#display_label').find('[value="1"]').removeClass('hide');
                $('#display_label').select2('val',1);
				dataFields.attr('multiple', true).select2({maximumSelectionSize: 3});
                var valueFocus = dataFields.val();
                var renameFIelds = $('#pivot_report_step4').find('.rename-field-translate');
                var rename_field = $('#pivot_report_step4').find('[name="rename_field"]');
                if (rename_field.length == 0 && valueFocus) {
                    var label = dataFields.find("option:selected").text();
                    renameFIelds.append(
                        '<tr>'+
                        '<td class="fieldLabel" name="{$RENAME->fieldname}">'+label+'</td>'+
                        '<td class="fieldValue"">'+
                        '<input type="text" data-selected="'+valueFocus+'" data-fieldlabel="'+label+'" data-fieldtype="string" class="inputElement" name="rename_field" value="">'+
                        '</td>'+
                        '</tr>'
                    );
                }

			}
            if(chartType == 'barChart' || chartType == 'horizontalBarChart' || chartType == 'stackedChart' || chartType == 'barFunnelChart'){
				$('.label-drawline').show();
				$('.input-drawline').show();
            }else{
                $('.label-drawline').hide();
                $('.input-drawline').hide();
			}
			jQuery('input[name=charttype]').val(chartType);
			jQuery('ul[name=charttab] li.active a').removeClass('contentsBackground backgroundColor');
			jQuery(this).addClass('contentsBackground backgroundColor');
		});
	},
    
     calculateValues : function(){
		//handled advanced filters saved values.
		var advfilterlist = jQuery('#advanced_filter','#chart_report_step2').val();// value from step2
		jQuery('#advanced_filter','#chart_report_step3').val(advfilterlist);
	},

	registerSubmitEvent : function() {
		var thisInstance = this;
		jQuery('#generateReport').on('click', function(e) {
			var legend = jQuery('#groupbyfield').val();
			var sector = jQuery('#datafields').val();
			var form = thisInstance.getContainer();
			if(sector && legend) {
				vtUtils.hideValidationMessage(jQuery('#s2id_groupbyfield'));
				vtUtils.hideValidationMessage(jQuery('#s2id_datafields'));
				form.submit();
			} else if(!legend){
				vtUtils.showValidationMessage(jQuery('#s2id_groupbyfield'), app.vtranslate('JS_PLEASE_SELECT_ATLEAST_ONE_OPTION'));
				e.preventDefault();
			}else if(!sector){
				vtUtils.showValidationMessage(jQuery('#s2id_datafields'), app.vtranslate('JS_PLEASE_SELECT_ATLEAST_ONE_OPTION'));
				e.preventDefault();
			}
		});
	},

    /**
	 * Function is used to limit the calculation for line item fields and inventory module fields.
	 * only one of these fields can be used at a time
	 */
	lineItemCalculationLimit: function () {
		var thisInstance = this;
		var dataFields = jQuery('#datafields');
		if (thisInstance.isInventoryModule()) {
			dataFields.on('change', function (e) {
				var value = dataFields.val();
				var reg = new RegExp(/vtiger_inventoryproductrel*/);
				var selectedChartType = jQuery('input[name=charttype]').val();
				if (selectedChartType !== 'pieChart' && selectedChartType !== 'doughnutChart' && selectedChartType !== 'funnelChart') {
					if (value && value.length > 0) {
						if (reg.test(value)) {
							// line item field selected remove module fields
							jQuery('#datafields option').not('[value^="vtiger_inventoryproductrel"]').remove();
						} else {
							jQuery('#datafields option[value^="vtiger_inventoryproductrel"]').remove();
						}
					} else {
						//If nothing is selected then reset it.
						var dataFieldsHTML = jQuery('#datafields_element').clone().html();
						dataFields.html(dataFieldsHTML);
					}
					thisInstance.displayLineItemFieldLimitationMessage();
				}
			});
		}
	},

	isInventoryModule: function () {
		var primaryModule = jQuery('input[name="primary_module"]').val();
		var inventoryModules = ['Invoice', 'Quotes', 'PurchaseOrder', 'SalesOrder'];
		// To limit the calculation fields if secondary module contains inventoryModule
		var secodaryModules = jQuery('input[name="secondary_modules"]').val();
		var secondaryIsInventory = false;
		inventoryModules.forEach(function (entry) {
			if (secodaryModules.indexOf(entry) != -1) {
				secondaryIsInventory = true;
			}
		});
		if (jQuery.inArray(primaryModule, inventoryModules) !== -1 || secondaryIsInventory) {
			return true;
		} else {
			return false;
		}
	},

	displayLineItemFieldLimitationMessage: function () {
		var message = app.vtranslate('JS_CALCULATION_LINE_ITEM_FIELDS_SELECTION_LIMITATION');
		if (jQuery('#calculationLimitationMessage').length == 0) {
			jQuery('#datafields').parent().append('<div id="calculationLimitationMessage" class="alert alert-info">' + message + '</div>');
		} else {
			jQuery('#calculationLimitationMessage').html(message);
		}
	},

	registerEvents : function(){
		this._super();
		this.calculateValues();
		this.registerForChartTabClick();
		this.lineItemCalculationLimit();
		VReports_ChartEdit3_Js.registerFieldForChosen();
		VReports_ChartEdit3_Js.initSelectValues();
	}
});