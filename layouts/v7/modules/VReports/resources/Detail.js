/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Detail_Js("VReports_Detail_Js",{},{
	advanceFilterInstance : false,
	detailViewContentHolder : false,
	HeaderContentsHolder : false, 
	
	detailViewForm : false,
	getForm : function() {
		if(this.detailViewForm == false) {
			this.detailViewForm = jQuery('form#detailView');
		}
	},
	
	getRecordId : function(){
		return app.getRecordId();
	},
	
	getContentHolder : function() {
		if(this.detailViewContentHolder == false) {
			this.detailViewContentHolder = jQuery('div.editViewPageDiv');
		}
		return this.detailViewContentHolder;
	},
	
	getHeaderContentsHolder : function(){
		if(this.HeaderContentsHolder == false) {
			this.HeaderContentsHolder = jQuery('div.reportsDetailHeader ');
		}
		return this.HeaderContentsHolder;
	},
	
	calculateValues : function(){
		var container = this.getContentHolder();
        this.advanceFilterInstance = VReports_AdvanceFilter_Js.getInstance(jQuery('.filterContainer:not(#conditionClone > .filterContainer)',container),false);
        //handled advanced filters saved values.
		var advfilterlist = this.advanceFilterInstance.getValues();
		return JSON.stringify(advfilterlist);
	},
		
	registerSaveOrGenerateReportEvent : function(){
		var thisInstance = this;
		jQuery('.generateReport').on('click',function(e){
            e.preventDefault();
			var advFilterCondition = thisInstance.calculateValues();
            var recordId = thisInstance.getRecordId();
            var currentMode = jQuery(e.currentTarget).data('mode');
            var postData = {
                'advanced_filter': advFilterCondition,
                'record' : recordId,
                'view' : "SaveAjax",
                'module' : app.getModuleName(),
                'mode' : currentMode
            };
			app.helper.showProgress();
			app.request.post({data:postData}).then(
				function(error,data){
					app.helper.hideProgress();
					thisInstance.getContentHolder().find('#reportContentsDiv').html(data);
					jQuery('.reportActionButtons').addClass('hide');
					// app.helper.showHorizontalScroll(jQuery('#reportDetails'));

					// To get total records count
					var count  = parseInt(jQuery('#updatedCount').val());
					thisInstance.generateReportCount(count);
				}
			);
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

    registerEventsForActions : function() {
      var thisInstance = this;
      jQuery('.reportActions').click(function(e){
        var element = jQuery(e.currentTarget);
        var href = element.data('href');
        var type = element.attr("name");
        var advFilterCondition = thisInstance.calculateValues();
        var headerContainer = thisInstance.getHeaderContentsHolder();
        if(type.indexOf("Print") != -1 || type.indexOf("PrintV2") != -1){
            var newEle = '<form action='+href+' method="POST" target="_blank">\n\
                    <input type = "hidden" name ="'+csrfMagicName+'"  value=\''+csrfMagicToken+'\'>\n\
                    <input type="hidden" value="" name="advanced_filter" id="advanced_filter" /></form>';
        }else{
            newEle = '<form action='+href+' method="POST">\n\
                    <input type = "hidden" name ="'+csrfMagicName+'"  value=\''+csrfMagicToken+'\'>\n\
                    <input type="hidden" value="" name="advanced_filter" id="advanced_filter" /></form>';
        }
        var ele = jQuery(newEle); 
        var form = ele.appendTo(headerContainer);
        form.find('#advanced_filter').val(advFilterCondition);
        form.submit();
      })  
    },
    
    generateReportCount : function(count){
      var thisInstance = this;  
      var advFilterCondition = thisInstance.calculateValues();
      var recordId = thisInstance.getRecordId();
      
      var reportLimit = parseInt(jQuery("#reportLimit").val());
      
        if(count < reportLimit){
            jQuery('#countValue').text(count);
            jQuery('#moreRecordsText').addClass('hide');
        }else{        
            jQuery('#countValue').html('<img src="layouts/v7/skins/images/loading.gif">');
            var params = {
                'module' : app.getModuleName(),
                'advanced_filter': advFilterCondition,
                'record' : recordId,
                'action' : "DetailAjax",
                'mode': "getRecordsCount"
            };
            jQuery('.generateReport').attr("disabled","disabled");
            app.request.post({data:params}).then(
                function(error,data){
                    jQuery('.generateReport').removeAttr("disabled");
                    var count = parseInt(data);
                    jQuery('#countValue').text(count);
                    if(count > reportLimit)
                        jQuery('#moreRecordsText').removeClass('hide');
                    else
                        jQuery('#moreRecordsText').addClass('hide');
                }
            );
        }
      
    },
	
	registerConditionBlockChangeEvent : function() {
		jQuery('.reportsDetailHeader').find('#groupbyfield,#legend_position,#datafields,#groupbyfield_rows,#groupbyfield_columns,#datafields,.columnname,.comparator,[name="columnname"],[name="comparator"]').on('change', function() {
			jQuery('.reportActionButtons').removeClass('hide');
		});
		jQuery('.reportsDetailHeader').find('button.deleteGroup,button.btn-add-group').on('click', function() {
			jQuery('.reportActionButtons').removeClass('hide');
		});
        jQuery('.reportsDetailHeader').on('change','input,select',function () {
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
	
	registerEventForModifyCondition : function() {
		jQuery('[name=modify_condition]').on('click', function(e) {
			var icon =  jQuery(e.currentTarget).find('i');
			var isClassExist = jQuery(icon).hasClass('fa-chevron-right');
			if(isClassExist) {
				jQuery(e.currentTarget).find('i:first').removeClass('fa-chevron-right').addClass('fa-chevron-down');
				jQuery('#filterContainer').removeClass('hide').show('slow');
				jQuery('button.btn-add-group').removeClass('hide');
			} else {
				jQuery(e.currentTarget).find('i:first').removeClass('fa-chevron-down').addClass('fa-chevron-right');
				jQuery('#filterContainer').addClass('hide').hide('slow');
                jQuery('button.btn-add-group').addClass('hide');

            }
			return false;
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
            filterClone.find('.group-condition').addClass('group-condition'+filterContainerLength);
            filterConditionContainer.append(filterClone.html());
            vtUtils.showSelect2ElementView(filterConditionContainer.find('.group-condition'+filterContainerLength));
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
	registerEvents : function(){
		this.registerSaveOrGenerateReportEvent();
        this.registerEventForPinChartToDashboard();
        this.registerEventsForActions();
		var container = this.getContentHolder();
		this.advanceFilterInstance = VReports_AdvanceFilter_Js.getInstance(jQuery('.filterContainer:not(#conditionClone > .filterContainer)',container),true);
        this.generateReportCount(parseInt(jQuery("#countValue").text()));
		this.registerConditionBlockChangeEvent();
		this.registerEventForModifyCondition();
		this.registerEventAddGroup();
		this.registerEventRemoveGroup();
	}
});