/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

VReports_Detail_Js("VReports_TabularDetail_Js", {
	
	/**
	 * Function used to display message when there is no data from the server
	 */
	displayNoDataMessage: function () {
		$('#chartcontent').html('<div><h3>' + app.vtranslate('JS_NO_TABULAR_DATA_AVAILABLE') + '</h3></div>').css(
				{'text-align': 'center', 'position': 'relative', 'top': '100px','width':'100%'});
	},
	
	/**
	 * Function returns if there is no data from the server
	 */
	isEmptyData: function () {
		var jsonData = jQuery('input[name=data]').val();
		var data = JSON.parse(jsonData);
		var values = data['values'];
		if (jsonData == '' || values == '') {
			return true;
		}
		return false;
	}
	
}, {
	
	/**
	 * Function returns instance of the chart type
	 */
	getInstance: function () {
		var chartType = jQuery('input[name=charttype]').val();
		var chartClassName = chartType.toLowerCase().replace(/\b[a-z]/g, function(letter) {
			return letter.toUpperCase();
		});
		var chartClass = window["VReport_" + chartClassName + "_Js"];

		var instance = false;
		if (typeof chartClass != 'undefined')
			instance = new chartClass();
		return instance;
	},
	
	registerSaveOrGenerateReportEvent: function () {
		var thisInstance = this;
		jQuery('.generateReportChart').on('click', function (e) {
			var advFilterCondition = thisInstance.calculateValues();
			var recordId = thisInstance.getRecordId();
			var currentMode = jQuery(e.currentTarget).data('mode');
			var groupByField = jQuery('#groupbyfield').val();
			var dataField = jQuery('#datafields').val();
			var legendposition = jQuery('#legend_position').val();
            var displaygrid = jQuery('#displaygrid').val();
            var displaylabel = jQuery('#displaylabel').val();
			if(dataField == null || dataField == '') {
				vtUtils.showValidationMessage(jQuery('#datafields').parent().find('.select2-choices'), app.vtranslate('JS_REQUIRED_FIELD'));
				return false;
			} else {
				vtUtils.hideValidationMessage(jQuery('#datafields').parent().find('.select2-choices'));
			}
			
			if(groupByField == null || groupByField == "") {
				vtUtils.showValidationMessage(jQuery('#groupbyfield').parent().find('.select2-container'), app.vtranslate('JS_REQUIRED_FIELD'));
				return false;
			} else {
				vtUtils.hideValidationMessage(jQuery('#groupbyfield').parent().find('.select2-container'));
			}

			var postData = {
				'advanced_filter': advFilterCondition,
				'record': recordId,
				'view': "TabularSaveAjax",
				'module': app.getModuleName(),
				'mode': currentMode,
				'charttype': jQuery('input[name=charttype]').val(),
				'groupbyfield': groupByField,
				'datafields': dataField,
				'legendposition': legendposition,
                'displaygrid': displaygrid,
                'displaylabel': displaylabel,
			};

			var reportChartContents = thisInstance.getContentHolder().find('#reportContentsDiv');
			app.helper.showProgress();
			e.preventDefault();
			app.request.post({data: postData}).then(
					function (error, data) {
						app.helper.hideProgress();
						reportChartContents.html(data);
                        var checkData = thisInstance.registerEventForChartGeneration();
                        if(checkData == true) {
                            $('#chartcontent').css('width','50%');
                            var ctx = document.getElementById('chart-area').getContext('2d');
                            window.myChart = new Chart(ctx, config);
                        }
						jQuery('.reportActionButtons').addClass('hide');
					}
			);
		});
	},
	
	registerEventForChartGeneration: function () {
		var dataChart = jQuery('input[name="datachart"]').val();
		if(!dataChart){
			VReports_ChartDetail_Js.displayNoDataMessage();
			return false;
        }
        return true;
	},
	
	savePinToDashBoard : function(element,customParams) {
		var recordId = this.getRecordId();
		var widgetTitle = 'TabularReportWidget_' + recordId;
		var tabName = element.data('tab-name');
		var params = {
				module: 'VReports',
				action: 'TabularActions',
				mode: 'pinToDashboard',
				reportid: recordId,
				title: widgetTitle
		};
		params = jQuery.extend(params, customParams);
		app.request.post({data: params}).then(function (error,data) {
			if (data.duplicate) {
				var params = {
						message: app.vtranslate('JS_CHART_ALREADY_PINNED_TO_DASHBOARD', 'VReports')
				};
				app.helper.showSuccessNotification(params);
			} else {
				var message = app.vtranslate('JS_CHART_PINNED_TO_DASHBOARD', 'VReports') +" In Tab " + tabName;
				app.helper.showSuccessNotification({message:message});
				element.find('i').removeClass('vicon-pin');
				element.find('i').addClass('vicon-unpin');
				element.removeClass('dropdown-toggle').removeAttr('data-toggle');
				element.attr('title', app.vtranslate('JSLBL_UNPIN_CHART_FROM_DASHBOARD'));
			}
		});
	},
	unpinFromDashboard : function (element,customParams) {
		var thisInstance = this;
        var recordId = thisInstance.getRecordId();
        var tabName = element.data('tab-name');
        var params = {
			module: 'VReports',
			action: 'TabularActions',
			mode: 'unpinFromDashboard',
			reportid: recordId
		};
        params = jQuery.extend(params, customParams);
        app.request.post({data: params}).then(function (error,data) {
			if(data.unpinned) {
				var message = app.vtranslate('JS_CHART_REMOVED_FROM_DASHBOARD', 'VReports') +" In Tab " + tabName;
				app.helper.showSuccessNotification({message:message});
				element.find('i').removeClass('vicon-unpin');
				element.find('i').addClass('vicon-pin');
				if(element.data('dashboardTabCount') >1) {
					element.addClass('dropdown-toggle').attr('data-toggle','dropdown');
				}
				element.attr('title', app.vtranslate('JSLBL_PIN_CHART_TO_DASHBOARD'));
			}
		});
    },

	registerEventForPinChartToDashboard: function () {
		var thisInstance = this;
		jQuery('button.pinToDashboard').closest('.btn-group').find('.dashBoardTab').on('click',function(e){
			var element = jQuery(e.currentTarget);
			var dashBoardTabId = jQuery(e.currentTarget).data('tabId');
			var pinned = element.find('i').hasClass('vicon-pin');
			if(pinned){
                thisInstance.savePinToDashBoard(element,{'dashBoardTabId':dashBoardTabId});
            }else{
                thisInstance.unpinFromDashboard(element,{'dashBoardTabId':dashBoardTabId});
			}
		});
	},

    registerEventAddGroup : function () {
        var thisInstance = this;
        jQuery('button[name=addgroup]').on('click',function () {
            var filterConditionContainer = jQuery('#filterContainer');
            var filterContainer = filterConditionContainer.find('.filterContainer:not(#conditionClone > .filterContainer)');
            var filterClone = filterConditionContainer.find('div#conditionClone').clone();
            var filterContainerLength = filterContainer.length;
            filterClone.find('.filterContainer').addClass('groupCondition'+filterContainerLength);
            filterClone.find('select.group-condition').attr('name','groupCondition'+filterContainerLength);
            filterClone.find('button.deleteGroup').attr('group-name','groupCondition'+filterContainerLength);
            filterClone.find('div.select2').remove();
            filterConditionContainer.append(filterClone.html());
            var container = thisInstance.getContentHolder();
            VReports_AdvanceFilter_Js.getInstance(jQuery('.groupCondition'+filterContainerLength,container),true);
            thisInstance.registerEventRemoveGroup();
            thisInstance.registerConditionBlockChangeEvent();

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

    registerEventForModifyChartCondition : function() {
        jQuery('[name=modify_charts]').on('click', function(e) {
            var icon =  jQuery(e.currentTarget).find('i');
            var isClassExist = jQuery(icon).hasClass('fa-chevron-right');
            if(isClassExist) {
                jQuery(e.currentTarget).find('i:first').removeClass('fa-chevron-right').addClass('fa-chevron-down');
                jQuery('#chart-content-conditions').removeClass('hide').show('slow');
            } else {
                jQuery(e.currentTarget).find('i:first').removeClass('fa-chevron-down').addClass('fa-chevron-right');
                jQuery('#chart-content-conditions').addClass('hide').hide('slow');
            }
            return false;
        });
    },

	registerEvents: function () {
		this._super();
		this.registerEventForChartGeneration();
		this.registerEventForModifyChartCondition();
		VReports_ChartEdit3_Js.registerFieldForChosen();
		VReports_ChartEdit3_Js.initSelectValues();
		this.registerEventForPinChartToDashboard();
		var chartEditInstance = new VReports_ChartEdit3_Js();
		chartEditInstance.lineItemCalculationLimit();
	}
});
