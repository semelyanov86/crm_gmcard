/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

VReports_Detail_Js("VReports_ChartDetail_Js", {
    gridstack : false,
    selectGroupByField: '',
    selectDataField : '',
    setGridstack : function () {
        var options = {
            float: false
        };
        $(".grid-stack").gridstack(options);
        var gridstack = $(".grid-stack").data("gridstack");
        this.gridstack = gridstack;
    },
	/**
	 * Function used to display message when there is no data from the server
	 */
	displayNoDataMessage: function () {
		$('#chartcontent').html('<div><h3>' + app.vtranslate('JS_NO_CHART_DATA_AVAILABLE') + '</h3></div>').css(
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
	},

    printAndExportChat: function (e) {
	    var target = jQuery(e);
	    var mode = target.data('mode');
        var chartCanvas = document.querySelector('#chart-area');
        if (chartCanvas) {
            var canvas_img = chartCanvas.toDataURL("image/png",1.0);
            var chartName =  jQuery('h3[name="reportName"]').html();
            var pdf = new jsPDF('landscape','in', 'letter');
            pdf.text(5, 1, chartName,'center');
            pdf.addImage(canvas_img, 'png', .5, 1.75, 10, 5);

            if (mode == 'print') {
                pdf.autoPrint();
                var blob = pdf.output('bloburl');
                window.open(blob);
            } if (mode == 'pdf') {
                pdf.save(chartName+".pdf");
            }
        }
        else {
            var message = app.vtranslate('JS_DATA_NOT_AVAILABLE', 'VReports');
            app.helper.showErrorNotification({message:message});
        }
    },

}, {
    init : function() {
        this._super();
        VReports_ChartDetail_Js.currentInstance = this;
    },
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
    registerUpdateSelectElementEventForData: function() {
        var thisInstance = this;
        jQuery("#datafields").on("change", function(e) {
            var uiUl = jQuery(e.currentTarget).siblings('div').find('ul');
            thisInstance.registerPushValueToSortBy(uiUl);
            $("#sort_by").select2({maximumSelectionSize: 3});
        })
	},

    registerUpdateSelectElementEventForGroup: function() {
        var thisInstance = this;
        jQuery("#groupbyfield").on("change", function(e) {
            var uiUl = jQuery(e.currentTarget).siblings('div').find('a');
            thisInstance.registerPushValueToSortBy(uiUl);
            $("#sort_by").select2({maximumSelectionSize: 3});
        })
	},
    //same like pivot, but not, can't call pivot function here
    getDifference : function arr_diff (a1, a2) {
        var a = [], diff = [];
        for (var i = 0; i < a1.length; i++) {
            a[a1[i]] = true;
        }
        for (var i = 0; i < a2.length; i++) {
            if (a[a2[i]]) {
                delete a[a2[i]];
            } else {
                a[a2[i]] = true;
            }
        }
        for (var k in a) {
            diff.push(k);
        }
        return diff;
    },

    registerPushValueToSortBy: function (element){
	    var thisInstance = this;
        var parentElement = element.parent();
        var select = parentElement.siblings('select');
        var identified=  select.attr('id');
        if (identified == 'groupbyfield') {
            if (typeof VReports_ChartDetail_Js.currentInstance.selectGroupByField == "string"){
                VReports_ChartDetail_Js.currentInstance.selectGroupByField= JSON.parse('[" '+ VReports_ChartDetail_Js.currentInstance.selectGroupByField +' "]')
            }
            var selectedData = VReports_ChartDetail_Js.currentInstance.selectGroupByField;
        } else if (identified == 'datafields'){
            var selectedData = VReports_ChartDetail_Js.currentInstance.selectDataField;
        }
        var data = select.val();
        if (typeof data == 'string'){
            data = JSON.parse('[" '+ data +' "]');
        }
        if (data == null) {
            difference = data;
        } else {
            var difference = thisInstance.getDifference(data,selectedData);
        }
        var sortBy = jQuery("#sort_by");
        var sortByVal = [] ;
        var i = 0;
        jQuery("#sort_by option").each(function () {
            sortByVal[i] = $(this).val();
            i++;
        });
        var valueOrder = Array();
        var res = select.find(':selected').toArray().map(item => item.text).join();
        $.each(data, function (index, value) {
            if (sortByVal.indexOf(value) > -1 ){
                return;
            }else {
                valueOrder += '<option value='+value+'>'+res.split(",")[index]+'</option>';
            }
        });
        if (selectedData.length > data.length){
            $.each(difference,function (index,value) {
                $('#sort_by').find('option[value="'+value+'"]').remove();
            });
        } else if(identified == 'groupbyfield') {
            $.each(selectedData,function (index,value) {
                $('#sort_by').find('option[value="'+value.replace(/\s/g, "")+'"]').remove();
            });
        }
        sortBy.append(valueOrder);
        if (identified == 'groupbyfield') {
            VReports_ChartDetail_Js.currentInstance.selectGroupByField = data;
        } else if (identified == 'datafields'){
            VReports_ChartDetail_Js.currentInstance.selectDataField = data;
        }

    },

	registerSaveOrGenerateReportEvent: function () {
		var thisInstance = this;
		jQuery('.generateReportChart').on('click', function (e) {
			var advFilterCondition = thisInstance.calculateValues();
			var recordId = thisInstance.getRecordId();
			var currentMode = jQuery(e.currentTarget).data('mode');
			var groupByField = jQuery('#groupbyfield').val();
			var dataField = jQuery('#datafields').val();
			var sortBy = jQuery('#sort_by').val();
			var limit = jQuery('input[name="sort_limit"]').val();
			var orderBy = jQuery('select[name="order_by"]').val();
			var legendposition = jQuery('#legend_position').val();
            var displaygrid = jQuery('#display_grid').val();
            var displaylabel = jQuery('select#display_label_chart').val();
            var formatlargenumber = jQuery('#chart-content-conditions').find('#formatlargenumber').val();
            var legendvalue = jQuery('#chart-content-conditions').find('#legendvalue').val();
            var drawline = jQuery('#chart-content-conditions').find('#drawline').val();
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
				'view': "ChartSaveAjax",
				'module': app.getModuleName(),
				'mode': currentMode,
				'charttype': jQuery('input[name=charttype]').val(),
				'groupbyfield': groupByField,
				'datafields': dataField,
				'sort_by': sortBy,
				'limit': limit,
				'order_by': orderBy,
				'legendposition': legendposition,
                'displaygrid': displaygrid,
                'displaylabel': displaylabel,
                'formatlargenumber': formatlargenumber,
                'legendvalue': legendvalue,
                'drawline': drawline,
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
                            var ctx = document.getElementById('chart-area').getContext('2d');
                            window.myChart = new Chart(ctx, config);
                        }
						jQuery('.reportActionButtons').addClass('hide');
                        VReports_ChartDetail_Js.setGridstack();
                        thisInstance.resizeGridStack();
                        thisInstance.resizeGrid();
                        thisInstance.registerSavePositionReportEvent();
                        window.myChart.resize();
					}
			);
		});
	},
	registerSavePositionReportEvent: function () {
        var thisInstance = this;
        jQuery('.grid-stack').on('change',function (e) {
            var recordId = thisInstance.getRecordId();
            var gridStack = $('.grid-stack');
            var gridStackItem = gridStack.find('.grid-stack-item');
            var x = gridStackItem.attr('data-gs-x');
            var y = gridStackItem.attr('data-gs-y');
            var width = gridStackItem.attr('data-gs-width');
            var height = gridStackItem.attr('data-gs-height');
            var postData={
                module: 'VReports',
                'action': "ChartActions",
                'mode': 'savePosition',
                'x':x,
                'y':y,
                'width':width,
                'height':height,
                'record': recordId,
            }
            app.helper.showProgress();
            e.preventDefault();
            app.request.post({data: postData}).then(
                function (error, data) {
                    app.helper.hideProgress();
                    var params = {
                        message: app.vtranslate('JS_SAVE_POSITION_SUCCESS', 'Reports')
                    };
                    app.helper.showSuccessNotification(params);
                }
            );
        })
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
		var widgetTitle = 'ChartReportWidget_' + recordId;
		var tabName = element.data('tab-name');
        var gridStack = $('.grid-stack');
        var gridStackItem = gridStack.find('.grid-stack-item');
        var width = gridStackItem.attr('data-gs-width');
        var height = gridStackItem.attr('data-gs-height');
		var params = {
				module: 'VReports',
				action: 'ChartActions',
				mode: 'pinToDashboard',
				reportid: recordId,
				title: widgetTitle,
                width:width,
    			height:height,
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
			action: 'ChartActions',
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
        var thisInstance = this;
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
        jQuery('#advancedOptions').on('click', function(e) {
            var currentTarget = jQuery(e.currentTarget);
            var advancedOptions = currentTarget.closest('.filterConditionContainer ').find('.contentsBackground.tab-content');
            if (advancedOptions.hasClass('hide')){
                advancedOptions.removeClass('hide')
                thisInstance.updatePicklistForAdvancedOptions();
            } else {
                advancedOptions.addClass('hide')
            }
            return false;
        });
    },

    updatePicklistForAdvancedOptions : function(){
        var selectedChartType = $('input[name="charttype"]').val();
        var displaylabel = $('select[name="displaylabel"]');
        var displaylabelVal = $('input[name="displaylabel"]').val();
        if((selectedChartType == 'pieChart' || selectedChartType == 'doughnutChart')){
            if(displaylabel.find('.pie-label').length == 0){
                var option = "<option class='pie-label' value='2' >Yes - Data (Percent)</option>"
                    +"<option class='pie-label' value='3' >Yes - Data</option>"
                    + "<option class='pie-label' value='4' >Yes - Group Name</option>";
                displaylabel.append(option);
            }
            if(displaylabelVal != ''){
                displaylabel.select2('val',displaylabelVal);
            }
        }
    },

    waitForFinalEvent : function () {
        var b = {};
        return function (c, d, a) {
            a || (a = "I am a banana!");
            b[a] && clearTimeout(b[a]);
            b[a] = setTimeout(c, d)
        }
    },
    resizeGridStack : function () {
        var thisInstance = this;
        $(window).resize(function () {
                thisInstance.resizeGrid();
        });
    },
    resizeGrid : function () {
        var isBreakpoint = function(alias) {
            return $('.device-' + alias).is(':visible');
        };
        var grid = VReports_ChartDetail_Js.gridstack;
        if (isBreakpoint('xs')) {
            $('#grid-size').text('One column mode');
        } else if (isBreakpoint('sm')) {
            grid.setGridWidth(3);
            $('#grid-size').text(3);
        } else if (isBreakpoint('md')) {
            grid.setGridWidth(6);
            $('#grid-size').text(6);
        } else if (isBreakpoint('lg')) {
            grid.setGridWidth(12);
            $('#grid-size').text(12);
        }
    },
    widgetPostLoad : 'Vtiger.Widget.PostLoad',
    fullDateString : new Date(),
	registerEvents: function () {
		this._super();
		this.init();
		this.registerEventForChartGeneration();
        this.registerUpdateSelectElementEventForData();
        this.registerUpdateSelectElementEventForGroup();
		this.registerEventForModifyChartCondition();
		VReports_ChartEdit3_Js.registerFieldForChosen();
		VReports_ChartEdit3_Js.initSelectValues();
		var chartEditInstance = new VReports_ChartEdit3_Js();
		chartEditInstance.lineItemCalculationLimit();
        VReports_ChartDetail_Js.setGridstack();
        this.selectGroupByField = jQuery("#groupbyfield").val();
        this.selectDataField = jQuery("#datafields").val();
        this.resizeGridStack();
        this.resizeGrid();
        this.registerSavePositionReportEvent();
	}
});
jQuery(document).ready(function () {
    jQuery('.generateReportChart').trigger('click');
});
