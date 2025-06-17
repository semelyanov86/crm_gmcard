/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger_Detail_Js("VReports_SqlReportDetail_Js",{},{
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
                var message = app.vtranslate('JS_SQL_REPORT_REMOVED_FROM_DASHBOARD', 'VReports') +" In Tab " + tabName;
                app.helper.showSuccessNotification({message:message});
                element.find('i').removeClass('vicon-unpin');
                element.find('i').addClass('vicon-pin');
                if(element.data('dashboardTabCount') >1) {
                    element.addClass('dropdown-toggle').attr('data-toggle','dropdown');
                }
                element.attr('title', app.vtranslate('JSLBL_PIN_SQL_REPORT_TO_DASHBOARD'));
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
                    message: app.vtranslate('JS_SQL_REPORT_ALREADY_PINNED_TO_DASHBOARD', 'VReports')
                };
                app.helper.showSuccessNotification(params);
            } else {
                var message = app.vtranslate('JS_SQL_REPORT_PINNED_TO_DASHBOARD', 'VReports') +" In Tab " + tabName;
                app.helper.showSuccessNotification({message:message});
                element.find('i').removeClass('vicon-pin');
                element.find('i').addClass('vicon-unpin');
                element.removeClass('dropdown-toggle').removeAttr('data-toggle');
                element.attr('title', app.vtranslate('JSLBL_UNPIN_SQL_REPORT_FROM_DASHBOARD'));
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
            var headerContainer = jQuery('div.reportsDetailHeader ');
            if(type.indexOf("Print") != -1){
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
            form.submit();
        })
    },

	registerEvents : function(){
        this._super();
        this.registerEventForPinChartToDashboard();
        this.registerEventsForActions();
	}
});