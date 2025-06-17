Vtiger.Class("VReports_DashBoard_Js", {
    gridstack : false,
    currentInstance : false,
    dashboardTabsLimit : 1000,
    intervalVal : [],
    setGridstack : function (tabid) {
        var options = {
            float: false,
            staticGrid : true,
        };
        $('#tab_'+tabid).find(".grid-stack").gridstack(options);
        var gridstack = $('#tab_'+tabid).find(".grid-stack").data("gridstack");
        this.gridstack = gridstack;
    },

    getUserDateFormat : function() {
        return jQuery('#userDateFormat').val();
    },

    makePerfectScrollbar: function(widgetContainer) {
        widgetContainer.perfectScrollbar();
        widgetContainer.bind('scroll',function () {
            var element = $(this);
            var header = element.find('header');
            var widthWidget = element.width() + 'px';
            var scrollHorizontal = element.find('div.ps-scrollbar-x-rail').css('left');
            header.css('width','calc(' + widthWidget + ' + '+ scrollHorizontal +')');
            header.find('span:first').css('padding-left',scrollHorizontal);
        });
    },

    getColumnListSelect2Element : function() {
        return this.columnListSelect2Element = jQuery('select[name="fields"]').closest('td').find('div.select2-container');
    },

    addWidget : function(element, url) {
        var thisInstance = this;
        var element = jQuery(element);
        var linkId = element.data('linkid');
        var miniListDentified = element.data('name');
        // After adding widget, we should remove that widget from Add Widget drop down menu from active tab
        var activeTabId = VReports_DashBoard_Js.currentInstance.getActiveTabId();
        jQuery('a[data-linkid="'+linkId+'"]',"#tab_"+activeTabId).parent().hide();
        var width = element.data('width');
        var height = element.data('height');
        if (miniListDentified == 'KeyMetrics') {
            url += '&modeWidget=add&tabid='+activeTabId;
            app.helper.showProgress();
            app.request.post({"url":"index.php?module=VReports&view=KeyMetricsWizard&widgetName=KeyMetrics"}).then(function (err, res) {
                app.helper.hideProgress();
                app.helper.showModal(res);
                var widgetContainer = $('form[name="form-setting"]');
                $('div#collorpicker_271').hide();
                var footer = jQuery('.modal-footer', res);
                footer.show();
                thisInstance.registerColorPickerEvent(widgetContainer);
                thisInstance.registerEventShowNomalColor(widgetContainer);
                thisInstance.registerEventShowBoxColor(container);
                thisInstance.makeColumnListSortable();
                $('[name="saveSettingWidget"]').on("click",function () {
                    $("[name='saveSettingWidget']").attr('disabled', 'disabled');
                    var selectedColor = $('input[name="selectedColor"]').val();
                    if (selectedColor) url += '&color='+selectedColor;
                    var showEmptyVal = $('select[name="empty_field"]').val();
                    if (showEmptyVal) url += '&showemptyval='+showEmptyVal;
                    var refresh_time = $('select[name="refresh_time"]').val();
                    if (refresh_time) url += '&time='+refresh_time;
                    var selectValueElements = $('select[name="fields"]').select2('data');
                    var selectedValues = [];
                    for(i=0; i<selectValueElements.length; i++) {
                        selectedValues.push(selectValueElements[i].id);
                    }
                    data = {fields:selectedValues};
                    var str = "";
                    for (var key in data) {
                        if (str != "") {
                            str += "&";
                        }
                        str += key + "=" + encodeURIComponent(data[key]);
                    }
                    url += '&'+str;
                    app.request.post({"url": url}).then(function (err, res) {
                        app.helper.hideModal();
                        var widgetContainer = jQuery('<div linkId="'+ linkId +'" class="grid-stack-item dashboardWidgetGridStack new dashboardWidget loadcompleted" id="'+ linkId +'" data-name="'+name+'"><div class="panel panel-default grid-stack-item-content"></div></div>');
                        element.show();
                        widgetContainer.data('url',url);
                        VReports_DashBoard_Js.gridstack.addWidget(widgetContainer,0,0, 2, 2);
                        widgetContainer.find('div.panel').html(res);
                        VReports_DashBoard_Js.currentInstance.loadWidget(widgetContainer);
                        VReports_DashBoard_Js.gridstack.movable(widgetContainer,true);
                        VReports_DashBoard_Js.gridstack.resizable(widgetContainer,true);
                        VReports_DashBoard_Js.currentInstance.showButtonSavePositionWidgets(widgetContainer);
                        app.event.trigger("post.DashBoardTab.registerEvent");
                    });
                });
            });
        }
        else if (miniListDentified == 'History'){
            url += '&modeWidget=add&tabid='+activeTabId;
            app.helper.showProgress();
            app.request.post({"url": url}).then(function (err, res) {
                app.helper.hideProgress();
                var widgetContainer = jQuery('<div linkId="'+ linkId +'" class="grid-stack-item dashboardWidgetGridStack new dashboardWidget loadcompleted" id="'+ linkId +'" data-name="'+name+'"><div class="panel panel-default grid-stack-item-content"></div></div>');
                element.show();
                widgetContainer.data('url',url);
                VReports_DashBoard_Js.gridstack.addWidget(widgetContainer,0,0, width, height);
                thisInstance.makePerfectScrollbar(widgetContainer);
                widgetContainer.find('div.panel').html(res);
                VReports_DashBoard_Js.currentInstance.loadWidget(widgetContainer);
                VReports_DashBoard_Js.currentInstance.showButtonSavePositionWidgets(widgetContainer);
                VReports_DashBoard_Js.gridstack.movable(widgetContainer,true);
                VReports_DashBoard_Js.gridstack.resizable(widgetContainer,true);
                $('select[name="historyType"]').select2();
                var makeTargetBlank = widgetContainer.find('a.reference-display-value');
                if (makeTargetBlank.length>0){
                    makeTargetBlank.attr('target', '_blank');
                }
                app.event.trigger("post.DashBoardTab.registerEvent");
            });
        }
        else if (miniListDentified == 'Gauge'){
            app.helper.showProgress();
            app.request.post({"url":"index.php?module=VReports&view=GaugeWizard&widgetName=Gauge&step=step1" }).then(function (err, res) {
                app.helper.hideProgress();
                app.helper.showModal(res,{"cb": function (data) {
                    var wizardContainer = jQuery(data);
                    var form = jQuery('form', wizardContainer);
                    if (form.find('input[name="widgetName"]').val() == "KeyMetrics") {return;}
                    var targetReportSelectDOM = jQuery('select[name="targetReport"]', wizardContainer);
                    var dataReportSelectDOM = jQuery('select[name="dataReport"]', wizardContainer);
                    var formatLargeNumberDOM = jQuery('select[name="formatLargeNumber"]', wizardContainer);
                    var targetReportSelect2 = vtUtils.showSelect2ElementView(targetReportSelectDOM, {
                        placeholder: app.vtranslate('JS_SELECT_REPORT','VReports')
                    });
                    var dataReportSelect2 = vtUtils.showSelect2ElementView(dataReportSelectDOM, {});
                    vtUtils.showSelect2ElementView(formatLargeNumberDOM, {});
                    var footer = jQuery('.modal-footer', wizardContainer);
                    var addditionalOptionTable = form.find('table[name="addditionalOption"]');
                    dataReportSelectDOM.closest('tr').hide();
                    addditionalOptionTable.hide();
                    footer.hide();
                    targetReportSelect2.change(function () {
                        if (!targetReportSelect2.val()) return;
                        var moduleNameSelect2Params = {
                            module: 'VReports',
                            view: 'GaugeWizard',
                            step: 'step2',
                            selectedReport: targetReportSelect2.val()
                        };
                        app.request.post({"data": moduleNameSelect2Params}).then(function (err, res) {
                            dataReportSelectDOM.empty().html(res).trigger('change');
                            dataReportSelect2.closest('tr').show();
                            addditionalOptionTable.show();
                            footer.show();
                        });
                    });
                }});
                var widgetContainer = $('form[name="form-setting"]');
                $('div#collorpicker_271').hide();
                thisInstance.registerColorPickerEvent(widgetContainer);
                thisInstance.registerEventShowNomalColor(widgetContainer);
                thisInstance.registerEventShowBoxColor(widgetContainer);
                $('[name="saveSettingWidget"]').on("click",function () {
                    $("[name='saveSettingWidget']").attr('disabled', 'disabled');
                    var targetReport = $('select[name="targetReport"]').val();
                    var dataGauge = $('select[name="dataReport"]').val();
                    var sub_title = $('input[name="sub_title"]').val();
                    var dataColor = $('input[name="dataColor"]').val();
                    var backgroundColor = $('input[name="backgroundColor"]').val();
                    var decimal = $('input[name="decimal"]').val();
                    var formatLargeNumber = $('select[name="formatLargeNumber"]').val();
                    var icon = $('input[name="icon"]').val();
                    var fontSize = $('input[name="fontSize"]').val();
                    var data = [];
                    data = {targetReport:targetReport,dataGauge:dataGauge,dataColor:dataColor,sub_title:sub_title,backgroundColor:backgroundColor,decimal:decimal,formatLargeNumber:formatLargeNumber,icon:icon,fontSize:fontSize};
                    if (dataGauge) url += '&data='+JSON.stringify(data);
                    var selectedColor = $('input[name="selectedColor"]').val();
                    if (selectedColor) url += '&color='+selectedColor;
                    var refresh_time = $('select[name="refresh_time"]').val();
                    if (refresh_time) url += '&time='+refresh_time;
                    var title_minilist = $('input[name="title_minilist"]').val();
                    if (title_minilist) url += '&title='+title_minilist;

                    url += '&modeWidget=add&tabid='+activeTabId;
                    app.request.post({"url": url}).then(function (err, res) {
                        app.helper.hideModal();
                        var widgetContainer = jQuery('<div linkId="'+ linkId +'" class="grid-stack-item dashboardWidgetGridStack new dashboardWidget loadcompleted" id="'+ linkId +'" data-name="'+name+'"><div class="panel panel-default grid-stack-item-content"></div></div>');
                        element.show();
                        widgetContainer.data('url',url);
                        VReports_DashBoard_Js.gridstack.addWidget(widgetContainer,0,0, 2, 2);
                        widgetContainer.find('div.panel').html(res);
                        VReports_DashBoard_Js.currentInstance.loadWidget(widgetContainer);
                        VReports_DashBoard_Js.gridstack.movable(widgetContainer,true);
                        VReports_DashBoard_Js.gridstack.resizable(widgetContainer,true);
                        VReports_DashBoard_Js.currentInstance.showButtonSavePositionWidgets(widgetContainer);
                        app.event.trigger("post.DashBoardTab.registerEvent");
                    });
                });
            });
        }
        else {
            url += '&dashBoardTabId='+activeTabId;
            app.request.post({"url": url}).then(function (err, res) {
                if(res.pinned == true){
                    var widgetContainer = jQuery('<div class="grid-stack-item dashboardWidgetGridStack new loadcompleted"><div class="panel panel-default grid-stack-item-content"></div></div>');
                    var dataUrl = res.dataUrl;
                    widgetContainer.data('record', dataUrl['widgetId']);
                    widgetContainer.data('url', dataUrl['url']);
                    widgetContainer.attr('data-url-detail', dataUrl['urlDetail']);
                    widgetContainer.attr('data-url-edit', dataUrl['urlEdit']);
                    widgetContainer.attr('data-url-delete', dataUrl['urlDelete']);
                    element.closest('li').addClass('hide');
                    VReports_DashBoard_Js.gridstack.addWidget(widgetContainer,0,0, width, height);
                    VReports_DashBoard_Js.currentInstance.loadWidget(widgetContainer);
                    VReports_DashBoard_Js.gridstack.movable(widgetContainer,true);
                    VReports_DashBoard_Js.gridstack.resizable(widgetContainer,true);
                    VReports_DashBoard_Js.currentInstance.showButtonSavePositionWidgets(widgetContainer);
                    // element.remove();
                    app.event.trigger("post.DashBoardTab.registerEvent");
                }else if(res.pinned == false && res.duplicate == true){
                    app.helper.showAlertNotification({'message': app.vtranslate('JS_REPORT_EXIST_IN_THIS_TAB')});
                }
            });
        }
    },

    addMiniListWidget: function(element, url) {
        // 1. Show popup window for selection (module, filter, fields)
        // 2. Compute the dynamic mini-list widget url
        // 3. Add widget with URL to the page.
        element = jQuery(element);
        var instance = this;
        app.request.post({"url": "index.php?module=VReports&view=MiniListWizard&step=step1"}).then(function (err, res) {
            if (err == null) {
                setTimeout(function () {
                    app.helper.showModal(res, {"cb": function (data) {
                            var wizardContainer = jQuery(data);
                            var form = jQuery('form', wizardContainer);
                            if (form.find('input[name="widgetName"]').val() == "KeyMetrics") {return;}
                            var moduleNameSelectDOM = jQuery('select[name="module"]', wizardContainer);
                            var filteridSelectDOM = jQuery('select[name="filterid"]', wizardContainer);
                            var fieldsSelectDOM = jQuery('select[name="fields"]', wizardContainer);

                    var moduleNameSelect2 = vtUtils.showSelect2ElementView(moduleNameSelectDOM, {
                        placeholder: app.vtranslate('JS_SELECT_MODULE')
                    });
                    var filteridSelect2 = vtUtils.showSelect2ElementView(filteridSelectDOM, {
                        placeholder: app.vtranslate('JS_PLEASE_SELECT_ATLEAST_ONE_OPTION')
                    });
                    var fieldsSelect2 = vtUtils.showSelect2ElementView(fieldsSelectDOM, {
                        placeholder: app.vtranslate('JS_PLEASE_SELECT_ATLEAST_ONE_OPTION'),
                        closeOnSelect: true,
                        // maximumSelectionSize: 2
                    });
                    var footer = jQuery('.modal-footer', wizardContainer);

                    filteridSelectDOM.closest('tr').hide();
                    fieldsSelectDOM.closest('tr').hide();
                    footer.hide();

                    moduleNameSelect2.change(function () {
                        if (!moduleNameSelect2.val()) return;

                        var moduleNameSelect2Params = {
                            module: 'VReports',
                            view: 'MiniListWizard',
                            step: 'step2',
                            selectedModule: moduleNameSelect2.val()
                        };

                        app.request.post({"data": moduleNameSelect2Params}).then(function (err, res) {
                            filteridSelectDOM.empty().html(res).trigger('change');
                            filteridSelect2.closest('tr').show();
                            fieldsSelect2.closest('tr').hide();
                            footer.hide();
                            fieldsSelect2.closest('tbody').find('label#pick_color').hide();
                            fieldsSelect2.closest('tbody').find('div#pick_color').hide();
                            fieldsSelect2.closest('tbody').find('label#refresh_time').hide();
                            fieldsSelect2.closest('tbody').find('div#refresh_time').hide();

                            fieldsSelect2.closest('tbody').find('#min-height-label').hide();
                            fieldsSelect2.closest('tbody').find('#min-height-value').hide();
                            fieldsSelect2.closest('tbody').find('#max-height-label').hide();
                            fieldsSelect2.closest('tbody').find('#max-height-value').hide();
                        })
                    });
                    filteridSelect2.change(function () {
                        if (!filteridSelect2.val()) return;

                        var selectedModule = moduleNameSelect2.val();
                        var filteridSelect2Params = {
                            module: 'VReports',
                            view: 'MiniListWizard',
                            step: 'step3',
                            selectedModule: selectedModule,
                            filterid: filteridSelect2.val()
                        };

                        app.request.post({"data": filteridSelect2Params}).then(function (err, res) {
                            fieldsSelectDOM.empty().html(res).trigger('change');
                            var filterId = $('[name="filterid"]').val();
                            var translatedModuleNames = JSON.parse(jQuery("#minilistWizardContainer").find("#translatedModuleNames").val());
                            var fieldsLabelText = app.vtranslate('JS_EDIT_FIELDS', translatedModuleNames[selectedModule], translatedModuleNames[selectedModule]);
                            fieldsSelect2.closest('tr').find('.fieldLabel label').text(fieldsLabelText);
                            fieldsSelect2.closest('tr').show();
                            // show mess when select not None option
                            if(filterId != 'AllField'){
                                fieldsSelect2.closest('tbody').find('#showMessWhenSelectFillter').show();
                            }else{
                                fieldsSelect2.closest('tbody').find('#picklist-color').hide();
                            }

                            // show pick color
                            fieldsSelect2.closest('tbody').find('label#pick_color').show();
                            fieldsSelect2.closest('tbody').find('div#pick_color').show();
                            fieldsSelect2.closest('tbody').find('label#refresh_time').show();
                            fieldsSelect2.closest('tbody').find('div#refresh_time').show();

                            fieldsSelect2.closest('tbody').find('#min-height-label').show();
                            fieldsSelect2.closest('tbody').find('#min-height-value').show();
                            fieldsSelect2.closest('tbody').find('#max-height-label').show();
                            fieldsSelect2.closest('tbody').find('#max-height-value').show();
                            instance.saveSelectedColor();
                            instance.makeColumnListSortable();
                        });
                    });
                    fieldsSelect2.change(function () {
                        if (!fieldsSelect2.val()) {
                            footer.hide();
                        } else {
                            footer.show();
                        }
                    });

                    form.submit(function (e) {
                        e.preventDefault();
                        //To disable savebutton after one submit to prevent multiple submits
                        $("[name='saveButton']").attr('disabled', 'disabled');
                        var selectedModule = moduleNameSelect2.val();
                        var selectedFilterId = filteridSelect2.val();
                        var selectedFields1 = fieldsSelect2.select2('data');
                        var selectedFields = [];
                        var i=0;
                        $(selectedFields1).each(function () {
                            selectedFields.push(selectedFields1[i].id);
                            i++;
                        });
                        var minHeight = 5;
                        var maxHeight = 5;
                        if($('[name="min_height"]').val() != '' && $('[name="max_height"]').val() != ''){
                            minHeight = parseInt($('[name="min_height"]').val());
                            maxHeight = parseInt($('[name="max_height"]').val());
                            if(minHeight > 15){
                                app.helper.showErrorNotification({'message': app.vtranslate('JS_MIN_HEIGHT_GT_15')});
                                jQuery("[name='saveButton']").removeAttr('disabled');
                                return false;
                            }
                            if(minHeight < 2){
                                app.helper.showErrorNotification({'message': app.vtranslate('JS_MIN_HEIGHT_LT_2')});
                                jQuery("[name='saveButton']").removeAttr('disabled');
                                return false;
                            }
                            if(minHeight > maxHeight){
                                app.helper.showErrorNotification({'message': app.vtranslate('JS_MIN_HEIGHT_GT_MAX_HEIGHT')});
                                jQuery("[name='saveButton']").removeAttr('disabled');
                                return false;
                            }
                        }

                        var selectedColor = $('input[name="selectedColor"]').val();
                        var refresh_time = $('select[name="refresh_time"]').val();
                        if (typeof selectedFields != 'object') selectedFields = [selectedFields];
                        // TODO mandatory field validation

                                finializeAdd(selectedModule, selectedFilterId, selectedFields, selectedColor, refresh_time,minHeight,maxHeight);
                            });
                        }});
                }, 500);

            }

        });
        function finializeAdd(moduleName, filterid, fields, selectedColor, refresh_time, minHeight, maxHeight) {
            var data = {
                module: moduleName,
            };
            if (typeof fields != 'object') fields = [fields];
            data['fields'] = fields;
            url += '&filterid='+filterid+'&data=' + JSON.stringify(data)+'&color=' + selectedColor+'&refresh_time=' + refresh_time+'&min_height=' + minHeight+'&max_height=' + maxHeight;
            var linkId = element.data('linkid');
            var name = element.data('name');
            var widgetContainer = jQuery('<div linkId="'+ linkId +'" data-min-height="'+minHeight+'" data-max-height="'+maxHeight+'" class="grid-stack-item dashboardWidgetGridStack new dashboardWidget loadcompleted" id="'+ linkId +"-" + filterid +'" data-name="'+name+'"><div class="panel panel-default grid-stack-item-content"></div></div>');
            widgetContainer.data('url', url);
            var width = element.data('width');
            var height = element.data('height');
            VReports_DashBoard_Js.gridstack.addWidget(widgetContainer,0,0,width, height);
            VReports_DashBoard_Js.currentInstance.loadWidget(widgetContainer);
            VReports_DashBoard_Js.gridstack.movable(widgetContainer,true);
            VReports_DashBoard_Js.gridstack.resizable(widgetContainer,true);
            app.helper.hideModal();
            app.event.trigger("post.DashBoardTab.registerEvent");
            VReports_DashBoard_Js.currentInstance.showButtonSavePositionWidgets(widgetContainer);
        }
    },

    saveSelectedColor: function (){
        $('div.color-box').on('click',function () {
            $('div.color-box').removeClass('selected-color-box');
            var instance = $(this);
            instance.addClass("selected-color-box");
            var color = instance.data('value');
            $('input[name="selectedColor"]').val(color);
        });
    },
    saveChangePickColor: function (){
        $('div.s-color-box').on('click',function () {
            var instance = $(this);
            var elPickColor = instance.closest('td');
            instance.closest('.pick_color').find('div.s-color-box').removeClass('selected-color-box');
            instance.addClass("selected-color-box");
            var color = instance.data('value');
            elPickColor.find('input').val(color);
        });
    },
    registerSaveSettingWidget: function(widgetId,urlParams,parentGrid){
        var thisInstance = this;
        $('button[name="saveSettingWidget"]').on('click',function () {
            var selectedModule = $('input[name="selected_module"]').val();
            var targetReport = $('select[name="targetReport"]').val();
            var dataGauge = $('select[name="dataReport"]').val();
            var selectValueElements = $('select[name="fields"]').select2('data');
            var widgetName = $('input[name="widgetName"]').val();
            var showEmptyVal = $('select[name="empty_field"]').val();
            var showLineOnRow = $('input[name="checkboxDrawLine"]').prop("checked");
            var showLineOnRow1 = $('input[name="checkboxDrawLine1"]').prop("checked");
            var filterAssignedto = $('[name="filter_assignedto"]').val();
            var filterCreatedby = $('[name="filter_createdby"]').val();
            var selectedOrderField = '';
            var selectedOrderKeyword = '';
            var selectedOrderField1 = '';
            var selectedOrderKeyword1 = '';
            var selectedValues = [];
            if($('select[name="orderby-field"]').length > 0){
                selectedOrderField = $('select[name="orderby-field"]').val();
            }
            if($('select[name="orderby-field-1"]').length > 0){
                selectedOrderField1 = $('select[name="orderby-field-1"]').val();
            }
            if($('select[name="orderby-keyword"]').length > 0){
                selectedOrderKeyword = $('select[name="orderby-keyword"]').val();
            }
            if($('select[name="orderby-keyword-1"]').length > 0){
                selectedOrderKeyword1 = $('select[name="orderby-keyword-1"]').val();
            }
            for(i=0; i<selectValueElements.length; i++) {
                selectedValues.push(selectValueElements[i].id);
            }
            var selectValues = selectedValues;
            if (!selectValues && parentGrid.find('select[name="fields"]').length > 0){
                return;
            }
            jQuery('input[name="columnslist"]',parentGrid).val(selectValues);
            var data = [];
            var minHeight = (parentGrid.data('min-height')) ? parentGrid.data('min-height') : 5;
            var maxHeight = (parentGrid.data('max-height')) ? parentGrid.data('max-height') : 5;
            if(selectedModule){
                data = {module:selectedModule, fields:selectValues, orderField:selectedOrderField, orderKeyword:selectedOrderKeyword,orderField1:selectedOrderField1,orderKeyword1:selectedOrderKeyword1,showLineOnRow:showLineOnRow,showLineOnRow1:showLineOnRow1,filterAssignedto:filterAssignedto,filterCreatedby:filterCreatedby};
            }else if (widgetName == 'KeyMetrics') {
                data = {fields:selectValues};
            }else if (widgetName == 'Gauge') {
                var sub_title = $('input[name="sub_title"]').val();
                var dataColor = $('input[name="dataColor"]').val();
                var backgroundColor = $('input[name="backgroundColor"]').val();
                var decimal = $('input[name="decimal"]').val();
                var formatLargeNumber = $('select[name="formatLargeNumber"]').val();
                var icon = $('input[name="icon"]').val();
                var fontSize = $('input[name="fontSize"]').val();
                minHeight = 2;maxHeight = 2;
                data = {targetReport:targetReport,dataGauge:dataGauge,dataColor:dataColor,sub_title:sub_title,backgroundColor:backgroundColor,decimal:decimal,formatLargeNumber:formatLargeNumber,icon:icon,filterAssignedto:filterAssignedto,filterCreatedby:filterCreatedby,fontSize:fontSize};
            }else {
                data = {filterAssignedto:filterAssignedto};
            }
            var title_widget = $('input[name="title_minilist"]').val();
            var selectedColor = $('input[name="selectedColor"]').val();
            var timeRefresh = $('select[name="refresh_time"]').val();
            if (typeof timeRefresh == 'undefined') {
                timeRefresh = 0;
            }
            if($('[name="min_height"]').val() != '' && $('[name="max_height"]').val() != ''){
                minHeight = parseInt($('[name="min_height"]').val());
                maxHeight = parseInt($('[name="max_height"]').val());
                if(minHeight < 2){
                    app.helper.showErrorNotification({'message': app.vtranslate('JS_MIN_HEIGHT_LT_2')});
                    jQuery("[name='saveButton']").removeAttr('disabled');
                    return false;
                }
                if(minHeight > 15){
                    app.helper.showErrorNotification({'message': app.vtranslate('JS_MIN_HEIGHT_GT_15')});
                    return false;
                }
                if(minHeight > maxHeight){
                    app.helper.showErrorNotification({'message': app.vtranslate('JS_MIN_HEIGHT_GT_MAX_HEIGHT')});
                    return false;
                }
            }else{
                minHeight = '';
                maxHeight = '';
            }

            var params = {
                'titleWidget': title_widget,
                'widgetRecord' : widgetId,
                'widgetName' : widgetName,
                'data' : data,
                'selectedColor' : selectedColor,
                'timeRefresh' : timeRefresh,
                'minHeight' : minHeight,
                'maxHeight' : maxHeight,
                'showEmptyVal' : showEmptyVal,
                'mode' : 'saveSettingWidget',
                'action' : 'WidgetActions',
                'module' : 'VReports',
            };
            app.helper.showProgress();
            app.request.post({data:params}).then(function () {
                app.request.post({"url":urlParams}).then(function(err,data){
                    app.helper.hideProgress();
                    var miniListId = $(data).find('[name="widgetId"]').val();
                    if(miniListId){
                        parentGrid.attr('data-record',miniListId);
                    }
                    var html = parentGrid.find('div.panel.panel-default.grid-stack-item-content.ui-draggable-handle');
                    html.html(data);
                    thisInstance.currentInstance.registerFilterChangeEvent();
                    thisInstance.currentInstance.registerFilter(html);
                    $('select[name="historyType"]').select2();
                    var makeTargetBlank = html.find('a.reference-display-value');
                    if (makeTargetBlank.length>0){
                        makeTargetBlank.attr('target', '_blank');
                    }
                    app.helper.hideModal();
                    var time = html.find('header.panel_header').data('refresh-time');
                    parentGrid.attr('data-min-height',minHeight);
                    parentGrid.attr('data-max-height',maxHeight);

                    var indexnodes = 0;
                    var nodes = thisInstance.gridstack.grid.nodes;
                    $(nodes).each(function (index,value) {
                        if($(this.el[0]).data('record') == miniListId){
                            indexnodes = index;

                        }
                    });
                    if(minHeight != '' && maxHeight != ''){
                        if(jQuery(parentGrid).data('widget-type') == 'Mini List VReports' || widgetName == 'MiniList'){
                            var currentWidgetHeight = jQuery(parentGrid).height();
                            var tableHeight = jQuery(parentGrid).find('table').height();
                            if(tableHeight + 29 + 10 > currentWidgetHeight){
                                var height = Math.ceil((((tableHeight + 29 + 10) - 60) / 80) + 1);
                                if(height > minHeight && height <= maxHeight){
                                    thisInstance.gridstack.resize(thisInstance.gridstack.grid.nodes[indexnodes].el,thisInstance.gridstack.grid.nodes[indexnodes].width,height);
                                }else if(height >= maxHeight){
                                    thisInstance.gridstack.resize(thisInstance.gridstack.grid.nodes[indexnodes].el,thisInstance.gridstack.grid.nodes[indexnodes].width,maxHeight);
                                }else if (height < minHeight){
                                    thisInstance.gridstack.resize(thisInstance.gridstack.grid.nodes[indexnodes].el,thisInstance.gridstack.grid.nodes[indexnodes].width,minHeight);
                                }
                            }else{
                                thisInstance.gridstack.resize(thisInstance.gridstack.grid.nodes[indexnodes].el,thisInstance.gridstack.grid.nodes[indexnodes].width,minHeight);
                            }
                        }
                    }

                    clearInterval(thisInstance.intervalVal[miniListId]);
                    thisInstance.intervalVal[miniListId] = setInterval(function () {
                        thisInstance.currentInstance.registerEventAutoRefresh(html);
                    },time);
                    var obj = new VReports_DashBoard_Js();
                    obj.registerPreviewRecord();
                    });
            });
        });
    },

    registerColorPickerEvent : function(container) {
        var thisInstance = this;
        var colorPickerDiv = container.find('.colorPicker');
        var selectedColorElement = container.find('[name=selectedColor]');
        app.helper.initializeColorPicker(colorPickerDiv, {}, function(hsb, hex, rgb) {
            var selectedColorCode = '#'+hex;
            selectedColorElement.val(selectedColorCode);
        });
        var color = selectedColorElement.val();
        if(!color) {
            color = '#212121';
            selectedColorElement.val(color);
        }
        colorPickerDiv.ColorPickerSetColor(color);
        thisInstance.registerEventGaugeSelectIcons();
    },
    registerEventShowBoxColor : function(){
        var thisInstance = this;
        var container = $('.form-horizontal');
        container.find('#custom-picklist-color').hide();
        thisInstance.saveChangePickColor(container);
    },
    registerEventSetCustomColor : function(element){
        var thisInstance = this;
        var element = $(element);
        var elPickColor = element.closest('td');
        elPickColor.find('.div_pick_color').hide();
        elPickColor.find('.custom-picklist-color').show();
        thisInstance.registerCustomColorPickerEvent(elPickColor);
        elPickColor.find('#collorpicker_data').hide();
    },
    registerCustomColorPickerEvent : function(container) {
        var colorPickerDiv = container.find('.customColorPicker');
        var selectedColorElement = container.find('.inputElement');
        app.helper.initializeColorPicker(colorPickerDiv, {}, function(hsb, hex, rgb) {
            var selectedColorCode = '#'+hex;
            selectedColorElement.val(selectedColorCode);
        });
        var color = selectedColorElement.val();
        if(!color) {
            color = '#212121';
            selectedColorElement.val(color);
        }
        colorPickerDiv.ColorPickerSetColor(color);
    },
    registerEventHideCustomColor : function(element){
        var thisInstance = this;
        var element = $(element);
        var elPickColor = element.closest('td');
        var container = $('.form-horizontal');
        elPickColor.find('.div_pick_color').show();
        container.find('.custom-picklist-color').hide();
        thisInstance.saveSelectedColor();
    },
    registerEventShowBoxIcons : function(element){
        var element = $(element);
        var popup_name = element.attr('popup-open');
        $('[popup-name="' + popup_name + '"]').fadeIn(300);
    },
    registerEventGaugeSelectIcons : function () {
        var container = $('.form-horizontal');
        container.find('.close-box-icons-button').on('click',function () {
            var popup_name = $(this).attr('popup-close');
            $('[popup-name="' + popup_name + '"]').fadeOut(300);
        })
        container.find('.gauge-popup-icons').on('click',function () {
            var popup_name = $(this).find('[popup-close]').attr('popup-close');
            $('[popup-name="' + popup_name + '"]').fadeOut(300);
            }).children().click(function() {
                return false;
        })
        $('span.icon-gauge').on('click',function () {
            var container = $('.form-horizontal');
            var instance = $(this);
            var classspanIcon = instance.data('info');
            var elPickIcon = instance.closest('td');
            elPickIcon.css("background", "cyan");
            container.find('[name="icon"]').val(classspanIcon);
            var spanSelected = container.find('#icon-selected');
            spanSelected.removeClass();
            spanSelected.addClass(classspanIcon);
            $('[popup-name="popup-box-icons"]').fadeOut(300);
        });
    },
    registerEventShowNomalColor : function(){
        var thisInstance = this;
        var container = $('.form-horizontal');
        container.find('label#pick_color').show();
        container.find('div#pick_color').show();
        container.find('#label-picklist-color').hide();
        container.find('#div-picklist-color').hide();
        thisInstance.saveSelectedColor(container);
        container.find('#collorpicker_802').hide();
    },

    registerFilterInitiater : function(element) {
        var element = $(element);
        var showData = element.attr('data-show');
        if (!showData) {
            showData = element.attr('data-hide');
        }
        var parentGrid = element.closest('div.dashboardWidgetGridStack');
        if (showData == 'show') {
            parentGrid.find('div.widgeticons.dashBoardWidgetFooter.panel_footer.sticky_footer').show();
            var filterContainer = parentGrid.find('.filterContainer');
            parentGrid.toggleClass('dashboardFilterExpanded');
            filterContainer.slideToggle(500);
            element.attr('data-show','show');
        }
        else {
            parentGrid.find('div.widgeticons.dashBoardWidgetFooter.panel_footer.sticky_footer').hide();
            var filterContainer = parentGrid.find('.filterContainer');
            parentGrid.toggleClass('dashboardFilterExpanded');
            filterContainer.slideToggle(500);
        }
        return false;
    },

    eventShowCount : function (element,widgetId){
        var target = $(element);
        var data = {
            'widgetId' : widgetId,
            'module' : 'VReports',
            'view' : 'ListAjax',
            'mode' : 'getAllRecordsCount',
        };
        app.request.post({data:data}).then( function(err,data) {
            if(!err){
                target.removeClass();
                target.html(data);
            }
        });
    },

    eventActionHeaderWidget : function (element) {
        var thisInstance = this;
        var element = $(element);
        var event = element.data('event');
        var parentGrid = element.closest('div.dashboardWidgetGridStack');
        var widgetType = parentGrid.data('widget-type');
        var linkId = parentGrid.attr('linkId');
        if (linkId != undefined){
            var data_record = parentGrid.attr('data-record');
            var url = 'index.php?module=VReports&action=RemoveWidget&linkid='+linkId+'&widgetid='+data_record+'';
        }
        else {
            var url = parentGrid.attr('data-url-'+event);
        }
        if(event == 'detail'){
            window.open(url,'_blank');
        }
        else if (event == 'Setting'){
            var recordId = parentGrid.data('record');
            var moduleName = parentGrid.find('table[name="miniListTable"]').attr('data-module-name');
            var header = parentGrid.find('header.panel_header');
            var urlDetail = header.data('url');
            if (urlDetail) {
                var widgetMode = urlDetail.split("=");
            }
            if (typeof recordId == 'undefined')  {
                recordId = widgetMode[4];
            }
            if(!urlDetail || widgetMode.length < 3){
                urlDetail =  parentGrid.data('url');
            }else if (widgetMode.length > 3){
                var widgetMode = widgetMode[3].split("&");
                var widgetModeName = widgetMode[0];
            }
            if (widgetModeName == 'History') {
                var urlParams = urlDetail+"&tabid="+activeTabId;
            }
            var activeTabId = jQuery(".tab-pane.active").data("tabid");
            var urlParams = urlDetail+"&tabid="+activeTabId;
            var data = {
                'record' : recordId,
                'module' : 'VReports',
                'widgetType' : widgetType,
                'view' : 'ListAjax',
                'mode' : 'settingForWidget',
                'widgetName' : widgetModeName,
                'url' : urlDetail,
                'selectedModule' : moduleName,
            };
            app.helper.showProgress();
            app.request.post({data:data}).then( function(err,data){
                app.helper.hideProgress();
                app.helper.showModal(data,{cb : function() {
                        var container = $('form[name="form-setting"]');
                        var widgetId = $('input[name="recordIdSetting"]').val();
                        // selected color when setting
                        var selected_color = $('input[name="selectedColor"]').val();
                        $('div.color-box').each(function () {
                            var color = $(this).data('value');
                            if (color == selected_color) {
                                $(this).addClass('selected-color-box');
                            }
                        });
                        $('div.s-color-box').each(function () {
                            var instance = $(this);
                            var elPickColor = instance.closest('td');
                            var selected_color = elPickColor.find('.inputElement').val();
                            var color = instance.data('value');
                            if (color == selected_color) {
                                instance.addClass('selected-color-box');
                            }
                        });
                        var orderbyField = $('select[name="orderby-field"]');
                        var orderbyField1 = $('select[name="orderby-field-1"]');

                        if (orderbyField.val() != '' && orderbyField.val() != null){
                            $('input[name="checkboxDrawLine"]').removeClass('hide');
                        } if (orderbyField1.val() != '' && orderbyField1.val() != null){
                            $('input[name="checkboxDrawLine1"]').removeClass('hide');
                        }

                        orderbyField.on('change',function () {
                            if ($(this).val() != ''){
                                $('input[name="checkboxDrawLine"]').removeClass('hide');
                            } else {
                                $('input[name="checkboxDrawLine"]').addClass('hide').prop("checked",false);
                            }
                        });
                        orderbyField1.on('change',function () {
                            if ($(this).val() != '') {
                                $('input[name="checkboxDrawLine1"]').removeClass('hide');
                            } else {
                                $('input[name="checkboxDrawLine1"]').addClass('hide').prop("checked",false);
                            }
                        });
                        if($('input[name="selected_time"]').val() > 0){
                            var time = JSON.parse($('input[name="selected_time"]').val());
                            $('select[name="refresh_time"]').val(time).trigger('change');
                        }
                        // selected field when setting
                        var data = JSON.parse($('input[name="selected_field"]').val());
                        if (widgetModeName == 'MiniList' && typeof widgetModeName != 'undefined') {
                            if ($('input[name="selected_field"]').val().length > 0) {
                                $('select[name="fields"]').val(data.fields).trigger('change');
                                thisInstance.arrangeSelectChoicesInOrder();
                            }
                        }
                        if (data.filterAssignedto){
                            $('select[name="filter_assignedto"]').val(data.filterAssignedto).trigger('change');
                        }
                        if (data.filterCreatedby){
                            $('select[name="filter_createdby"]').val(data.filterCreatedby).trigger('change');
                        }
                        if (widgetModeName == 'Gauge') {
                            var targetReportSelectDOM = jQuery('select[name="targetReport"]', container);
                            var dataReportSelectDOM = jQuery('select[name="dataReport"]', container);
                            var formatLargeNumberDOM = jQuery('select[name="formatLargeNumber"]', container);
                            var targetReportSelect2 = vtUtils.showSelect2ElementView(targetReportSelectDOM, {
                                placeholder: app.vtranslate('JS_SELECT_REPORT','VReports')
                            });
                            var dataReportSelect2 = vtUtils.showSelect2ElementView(dataReportSelectDOM, {});
                            vtUtils.showSelect2ElementView(formatLargeNumberDOM, {});
                            targetReportSelect2.change(function () {
                                if (!targetReportSelect2.val()) return;
                                var moduleNameSelect2Params = {
                                    module: 'VReports',
                                    view: 'GaugeWizard',
                                    step: 'step2',
                                    selectedReport: targetReportSelect2.val()
                                };
                                app.request.post({"data": moduleNameSelect2Params}).then(function (err, res) {
                                    dataReportSelectDOM.empty().html(res).trigger('change');
                                    dataReportSelect2.closest('tr').show();
                                });
                            });
                        }
                        $('div#collorpicker_271').hide();
                        thisInstance.registerColorPickerEvent(container);
                        thisInstance.registerEventShowNomalColor(container);
                        thisInstance.registerEventShowBoxColor(container);
                        thisInstance.registerSaveSettingWidget(widgetId,urlParams,parentGrid);
                        thisInstance.makeColumnListSortable();
                        thisInstance.registerPicklistValueDropMenu();
                    }});
            });
        }
        else if(event == 'edit'){
            window.open(url,'_blank');}
        else if(event == 'Refresh'){
            var urlParams = parentGrid.find('header').data('url');
            if (urlParams) {
                var widgetMode = urlParams.split("=");
            }
            if ((typeof urlParams != "undefined" || urlParams != '') && widgetMode.length > 3){
                var widgetMode = widgetMode[3].split("&");
                var widgetModeName = widgetMode[0];
            }
            else {
                var urlParams =parentGrid.data('url');
            }
            var activeTabId = jQuery(".tab-pane.active").data("tabid");
            urlParams += "&tabid="+activeTabId;
            app.helper.showProgress();
            app.request.post({"url":urlParams}).then(function(err,data){
                app.helper.hideProgress();
                var miniListId = $(data).find('[name="widgetId"]').val();
                if(miniListId){
                    parentGrid.attr('data-record',miniListId);
                }
                var html = parentGrid.find('div.panel.panel-default.grid-stack-item-content.ui-draggable-handle');
                html.html(data);
                thisInstance.currentInstance.registerFilter(html);
                if (widgetModeName == 'History'){
                    $('select[name="historyType"]').select2();
                }
                var makeTargetBlank = html.find('a.reference-display-value');
                if (makeTargetBlank.length>0){
                    makeTargetBlank.attr('target', '_blank');
                }
                VReports_DashBoard_Js.currentInstance.registerLoadMore();
                VReports_DashBoard_Js.currentInstance.registerPicklistValueDropMenu();
            });
        }
        else{
            var message = app.vtranslate('Are you sure remove this widget ?', 'VReports');
            app.helper.showConfirmationBox({'message' : message}).then(
                function(e) {
                    var reportId = element.attr('data-id');
                    app.helper.showProgress('Removing Widget');
                    app.request.post({url:url}).then(function(err, res) {
                        if(!err){
                            var grid = thisInstance.gridstack;
                            grid.removeWidget(parentGrid);
                            var message = app.vtranslate('VREPORT_WIDGET_REMOVE_SUCCESS', 'VReports');
                            app.helper.hideProgress();
                            app.helper.showSuccessNotification({message:message});
                            $('ul.widgetsList').find('li[data-id="'+reportId+'"]').removeClass('hide');
                        }else{
                            app.helper.hideProgress();
                            app.helper.showErrorNotification({message:err});
                        }
                    });
                }
            )
        }
    },

    arrangeSelectChoicesInOrder : function() {
        var contentsContainer = $(document).find('div#minilistWizardContainer');
        var chosenElement = this.getColumnListSelect2Element();
        var choicesContainer = chosenElement.find('ul.select2-choices');
        var choicesList = choicesContainer.find('li.select2-search-choice');
        var columnListSelectElement = $('select[name="fields"]');
        var selectedOptions = columnListSelectElement.find('option:selected');
        var selectedOrder = JSON.parse(jQuery('input[name="columnslist"]', contentsContainer).val());
        selectedOrder = selectedOrder.fields;
        for(var index=selectedOrder.length ; index > 0 ; index--) {
            var selectedValue = selectedOrder[index-1];
            var value = selectedValue.replace("'", "&#39;");
            var option = selectedOptions.filter('[value="'+value+'"]');
            choicesList.each(function(choiceListIndex,element){
                var liElement = jQuery(element);
                if(liElement.find('div').html() == option.html()){
                    choicesContainer.prepend(liElement);
                    return false;
                }
            });
        }
    },

    makeColumnListSortable : function() {
        var select2Element = this.getColumnListSelect2Element();
        var chozenChoiceElement = select2Element.find('ul.select2-choices');
        chozenChoiceElement.sortable({
            'containment': chozenChoiceElement,
            start: function() { },
            update: function() {}
        });
    },
    // show more
    registerMoreClickEvent: function (e) {
        var moreLink = jQuery(e.currentTarget);
        var linkId = moreLink.data('linkid');
        var widgetId = moreLink.data('widgetid');
        var currentPage = jQuery('#widget_' + widgetId + '_currentPage').val();
        var nextPage = parseInt(currentPage) + 1;
        var params = {
            'module': app.getModuleName(),
            'view': 'ShowWidget',
            'name': 'MiniList',
            'linkid': linkId,
            'widgetid': widgetId,
            'content': 'data',
            'currentPage': currentPage,
            'tabid': VReports_DashBoard_Js.currentInstance.getActiveTabId()
        };
        app.request.post({"data": params}).then(function (err, data) {
            var htmlData = jQuery(data);
            var htmlContent = htmlData.find('tbody tr');
            var table = moreLink.parent().siblings('table[name="miniListTable"]');
            //count
            var recordCountsNews = htmlData.find('#record-counts-listview').val();
            var recordCountsOlds = table.find('#record-counts-listview').val();
            var totalCount = parseInt(recordCountsOlds) + parseInt(recordCountsNews);
            table.find('#record-counts-listview').val(totalCount);
            table.find('tbody .miniListContent:last').after(htmlContent);
            table.closest('div:first-child').find('.page-numbers').text('1 to '+totalCount);
            //end count
            jQuery('#widget_' + widgetId + '_currentPage').val(nextPage);
            var moreExists = htmlData.siblings('.moreLinkDiv').length;
            if (!moreExists) {
                moreLink.parent().remove();
            }
        });

    }

},{
    widgetPostLoad : 'Vtiger.Widget.PostLoad',
    fullDateString : new Date(),
    init : function() {
        this.addComponent('Vtiger_Index_Js');
        VReports_DashBoard_Js.currentInstance = this;
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
            thisInstance.waitForFinalEvent(function () {
                thisInstance.resizeGrid();
            }, 300, thisInstance.fullDateString.getTime());
        });
    },

    resizeGrid : function () {
        var isBreakpoint = function(alias) {
            return $('.device-' + alias).is(':visible');
        };
        var grid = VReports_DashBoard_Js.gridstack;
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

    getDashboardContainer : function(){
        return jQuery(".dashBoardContainer");
    },

    getContainer : function(tabid) {
        if(typeof tabid == 'undefined'){
            tabid = this.getActiveTabId();
        }
        return jQuery("#tab_"+tabid).find('.dashBoardTabContainer');
    },

    getActiveTabId : function(){
        return jQuery(".tab-pane.active").data("tabid");
    },

    getActiveBoardId : function(){
        return jQuery(".tab-pane.active").parent().data("boardid");
    },

    getActiveTabName : function () {
        return jQuery(".tab-pane.active").data("tabname");
    },

    getDashboardWidgets : function() {
        return jQuery('.dashboardWidgetGridStack', jQuery('.tab-pane.active'));
    },

    registerLazyLoadWidgets : function() {
        var thisInstance = this;
        jQuery(window).bind("scroll", function() {
            var tabActive = thisInstance.getActiveTabId();
            var widgetList = $('.grid-stack-tab'+tabActive).children().not('.loadcompleted');
            if(!widgetList[0]){
                // We shouldn't unbind as we might have widgets in another tab
                //jQuery(window).unbind('scroll');
            }
            widgetList.each(function(index,widgetContainerELement){
                if(thisInstance.isScrolledIntoView(widgetContainerELement)){
                    thisInstance.loadWidget(jQuery(widgetContainerELement));
                    jQuery(widgetContainerELement).addClass('loadcompleted');
                }
            });
        });
    },

    isScrolledIntoView : function (elem) {
        var viewportHeight = jQuery(window).height(),
            documentScrollTop = jQuery(document).scrollTop(),
            minTop = documentScrollTop,
            maxTop = documentScrollTop + viewportHeight,

            $targetElement = jQuery(elem),
            elementOffset = $targetElement.offset();
        if (elementOffset.top > minTop && elementOffset.top < maxTop){
            return true;
        }
        else {
            return false;
        }
    },

    loadWidgets : function() {
        var thisInstance = this;
        var widgetList = thisInstance.getDashboardWidgets();
        widgetList.each(function(index,widgetContainerELement){
            if(thisInstance.isScrolledIntoView(widgetContainerELement)) {
                thisInstance.loadWidget(jQuery(widgetContainerELement));
                jQuery(widgetContainerELement).addClass('loadcompleted');
            }
        });
    },

    getFilterData : function() {
        return {};
    },

    registerLoadMore: function() {
        var thisInstance  = this;
        var parent = thisInstance.getContainer();
        var contentContainer = parent.find('.dashboardWidgetContent');

        $(document).on('click', 'a.load-more', function(e){
            var instance = loadMoreHandler = $(e.target);
            var parent = instance.closest('div.dashboardWidgetGridStack');
            var url = parent.data('url');
            var params = url;
            var widgetFilters = parent.find('.widgetFilter');
            if(widgetFilters.length > 0) {
                params = { url: url, data: {}};
                widgetFilters.each(function(index, domElement){
                    var widgetFilter = jQuery(domElement);
                    //Filter unselected checkbox, radio button elements
                    if((widgetFilter.is(":radio") || widgetFilter.is(":checkbox")) && !widgetFilter.is(":checked")){
                        return true;
                    }

                    if(widgetFilter.is('.dateRange')) {
                        var name = widgetFilter.attr('name');
                        var start = widgetFilter.find('input[name="start"]').val();
                        var end = widgetFilter.find('input[name="end"]').val();
                        if(start.length <= 0 || end.length <= 0  ){
                            return true;
                        }

                        params.data[name] = {};
                        params.data[name].start = start;
                        params.data[name].end = end;
                    } else {
                        var filterName = widgetFilter.attr('name');
                        var filterValue = widgetFilter.val();
                        params.data[filterName] = filterValue;
                    }
                });
            }
            var filterData = thisInstance.getFilterData();
            if(! jQuery.isEmptyObject(filterData)) {
                if(typeof params == 'string') {
                    params = { url: url, data: {}};
                }
                params.data = jQuery.extend(params.data, thisInstance.getFilterData())
            }
            if(typeof params == 'string') {
                params = { url: url, data: {}};
            }
            // Next page.
            params.data['page'] = loadMoreHandler.data('nextpage');
            params.data['content'] = 'exist';
            app.helper.showProgress();
            app.request.post(params).then(function(err,data){
                app.helper.hideProgress();
                loadMoreHandler.parent().parent().parent().replaceWith(jQuery(data).html());
                thisInstance.registerLoadMore();
            }, function(){
                app.helper.hideProgress();
            });
        });
    },

    registerFilter : function(container) {
        var dateRangeElement = container.find('div[name="modifiedtime"]');
        if(dateRangeElement.length <= 0) {
            return;
        }

        dateRangeElement.addClass('dateField');

        var pickerParams = {
            format : VReports_DashBoard_Js.getUserDateFormat(),
        };
        vtUtils.registerEventForDateFields(dateRangeElement, pickerParams);

        dateRangeElement.on("changeDate", function(e){
            var start = dateRangeElement.find('input[name="start"]').val();
            var end = dateRangeElement.find('input[name="end"]').val();
            if(start != '' && end != '' && start !== end){
                container.find('a[name="drefresh"]').trigger('click');
            }
        });
        dateRangeElement.attr('data-date-format',VReports_DashBoard_Js.getUserDateFormat());
    },

    registerFilterChangeEvent : function() {
        this.getContainer().on('change', '.widgetFilter, .reloadOnChange_checkbox, .reloadOnChange_select', function(e) {
            var target = jQuery(e.currentTarget);
            var targetParent = target.closest('div.boxSizingBorderBox');
            var checkGroupBy = $('input[name="groupAndSort"]');
            var widgetContainer = target.closest('div.dashboardWidgetGridStack');
            var urlParams = widgetContainer.find('header').data('url');
            if(checkGroupBy.is(':checked')) {
                urlParams += "&sortandgroup=1"
            }
            else {
                urlParams += "&sortandgroup=0"
            }
            var historyType  = targetParent.find('select[name="historyType"]').val();
            var type  = targetParent.find('input[name="historyType"]:checked').val();
            var start = targetParent.find('input[name="start"]').val();
            var end = targetParent.find('input[name="end"]').val();
            if(start != '' || end != '') {
                urlParams += "&start="+start+"&end="+end;
            }
            var activeTabId = jQuery(".tab-pane.active").data("tabid");
            urlParams += "&historyType="+historyType+"&type="+type+"&tabid="+activeTabId+"&content=content";
            app.helper.showProgress();
            app.request.post({"url":urlParams}).then(function(err,data){
                app.helper.hideProgress();
                var html = widgetContainer.find('div[name="historyContents"]');
                html.html(data);
                var makeTargetBlank = html.find('a.reference-display-value');
                if (makeTargetBlank.length>0){
                    makeTargetBlank.attr('target', '_blank');
                }
            });
        });
    },
    registerPicklistValueDropMenu : function(){
        $('.editPickListFieldValue .dropdown-menu').on('click','a',function(e) {
            e.stopImmediatePropagation();
            var element = jQuery(e.currentTarget);
            var liContainer = element.closest('li');
            var editPickListFieldElement = element.closest('.editPickListFieldValue');
            var relatedListEntryElement = element.closest('.minilist-column');
            var relatedListContainerItem = element.closest('.minilist-table');
            var selectedPicklistValue = liContainer.data('picklist-value');
            var relatedRecordId = element.closest('tr').data('id');
            var currentPicklistValue = relatedListEntryElement.find('.picklist-color').text();
            if(jQuery.trim(selectedPicklistValue) === jQuery.trim(currentPicklistValue)){
                editPickListFieldElement.removeClass('open');
                return false;
            }
            var params = {
                'module' : relatedListContainerItem.data('module-name'),
                'action' : 'SaveAjax',
                'record' : relatedRecordId,
                'field' : relatedListEntryElement.data('field-name'),
                'value' : selectedPicklistValue,
            }
            app.helper.showProgress();
            app.request.post({"data" : params}).then(function(err, data) {
                    if(err === null) {
                        app.helper.hideProgress();
                        relatedListEntryElement.closest('.dashboardWidgetGridStack ').find('.panel_header .fa-refresh').trigger('click');
                        editPickListFieldElement.removeClass('open');
                    }
                },
                function(textStatus, errorThrown) {}
            );
        });
    },

    loadWidget : function(widgetContainer) {
        var aDeferred = jQuery.Deferred();
        var thisInstance = this;
        var contentContainer = jQuery('.panel-default',widgetContainer);
        var urlParams = widgetContainer.data('url');
        if(urlParams.indexOf('&modeWidget=add') !== -1 ){
            urlParams = urlParams.replace('&modeWidget=add','');
        }
        if (urlParams){
            var widgetMode = urlParams.split("=");
        }
        if (urlParams && widgetMode.length > 3) {
            var activeTabId = this.getActiveTabId();
            var widgetMode = widgetMode[3].split("&");
            var widgetModeName = widgetMode[0];
        }
        if (widgetModeName == 'KeyMetrics' ||widgetModeName == 'History') {
            urlParams += "&widgetModeName="+widgetModeName+"&tabid="+activeTabId;
        }
        else {
            urlParams += "&tabid="+activeTabId;
        }
        app.helper.showProgress();
        app.request.post({"url":urlParams}).then(
            function(err,data){
                app.helper.hideProgress();
                var miniListId = $(data).find('[name="widgetId"]').val();
                contentContainer.html(data);
                if(miniListId){
                    widgetContainer.attr('data-record',miniListId);
                }
                var makeTargetBlank = contentContainer.find('a.reference-display-value');
                if (makeTargetBlank.length>0){
                    makeTargetBlank.attr('target', '_blank');
                }
                contentContainer.trigger(thisInstance.widgetPostLoad);
                var adjustedHeight = contentContainer.height()-50;
                app.helper.showVerticalScroll(contentContainer.find('.twitterContainer'),{
                    'setHeight' : adjustedHeight
                });
                aDeferred.resolve(urlParams);
                var time = contentContainer.find('header.panel_header').data('refresh-time');
                if (time > 5000) {
                    VReports_DashBoard_Js.intervalVal[miniListId] = setInterval(function () {
                        thisInstance.registerEventAutoRefresh(contentContainer);
                    },time);
                }
                if (widgetModeName == 'History'){
                    $('select[name="historyType"]').select2();
                    thisInstance.showDidePreViewHistory(contentContainer);
                }
                thisInstance.registerLoadMore();
                thisInstance.registerPreviewRecord();
                thisInstance.registerStandardButtonsPosition();
                thisInstance.registerFilterChangeEvent();
                thisInstance.registerFilter(contentContainer);
                thisInstance.registerPicklistValueDropMenu();
                if (widgetModeName != 'Gauge'){
                    VReports_DashBoard_Js.makePerfectScrollbar(contentContainer);
                }
                var minHeight = widgetContainer.data('min-height');
                var maxHeight = widgetContainer.data('max-height');
                var indexnodes = 0;
                var nodes = VReports_DashBoard_Js.gridstack.grid.nodes;
                $(nodes).each(function (index,value) {
                    if($(this.el[0]).data('record') == miniListId){
                        indexnodes = index;

                    }
                });
                if(jQuery(widgetContainer).data('widget-type') == 'Mini List VReports' || widgetModeName == 'MiniList'){
                    var currentWidgetHeight = jQuery(widgetContainer).height();
                    var tableHeight = jQuery(widgetContainer).find('table').height();
                    if(tableHeight + 29 + 10 > currentWidgetHeight){
                        var height = Math.ceil((((tableHeight + 29 + 10) - 60) / 80) + 1);
                        if(height > minHeight && height <= maxHeight){
                            VReports_DashBoard_Js.gridstack.resize(VReports_DashBoard_Js.gridstack.grid.nodes[indexnodes].el,VReports_DashBoard_Js.gridstack.grid.nodes[indexnodes].width,height)
                        }else if(height >= maxHeight){
                            VReports_DashBoard_Js.gridstack.resize(VReports_DashBoard_Js.gridstack.grid.nodes[indexnodes].el,VReports_DashBoard_Js.gridstack.grid.nodes[indexnodes].width,maxHeight)
                        }else if (height < minHeight){
                            VReports_DashBoard_Js.gridstack.resize(VReports_DashBoard_Js.gridstack.grid.nodes[indexnodes].el,VReports_DashBoard_Js.gridstack.grid.nodes[indexnodes].width,minHeight)
                        }
                    }else{
                        VReports_DashBoard_Js.gridstack.resize(VReports_DashBoard_Js.gridstack.grid.nodes[indexnodes].el,VReports_DashBoard_Js.gridstack.grid.nodes[indexnodes].width,minHeight)
                    }
                }
            },
            function(){
                aDeferred.reject();
            }
        );
        return aDeferred.promise();
    },

    registerResizeAndMove :function (){
        var thisInstance = this;
        $('.editWidgets').off('click');
        $('.editWidgets').on('click', function() {
            var widgetContainer = thisInstance.getContainer();
            app.helper.showAlertNotification({'message': app.vtranslate('JS_ABLE_TO_MOVE_AND_RESIZE_WIDGETS')});
            widgetContainer.find('.grid-stack').data('gridstack').setStatic( false );
            thisInstance.showButtonSavePositionWidgets();
        });
    },

    registerPreviewRecord:function (){
        var self = this;
        jQuery(document).off('click','.quickView');
        jQuery(document).on('click','.quickView',function (e) {
            var instance = $(this);
            var moduleName = instance.attr('data-module-name');
            var recordId = instance.attr('data-id');
            var app = instance.attr('data-app');
            self.showQuickPreviewForId(recordId,moduleName,app);
        });
        $('a.fa-refresh').off('click');
        $('a.fa-refresh').on('click', function(e) {
        jQuery(document).ajaxComplete(function(event, xhr, settings) {
        var url = settings.data;
        if (typeof url == 'undefined' && settings.url) url = settings.url;
        if (url.indexOf('view=ShowWidget') != -1) {
            $('.miniListContent .quickView').off('click');
            $('.miniListContent .quickView').on('click', function(e) {
                var instance = $(this);
                var moduleName = instance.attr('data-module-name');
                var recordId = instance.attr('data-id');
                var app = instance.attr('data-app');
                self.showQuickPreviewForId(recordId,moduleName,app);
            });
        }
        });
        });
    },

    registerStandardButtonsPosition: function (container) {
        if (typeof container === "undefined") {
            container = "#page";
        }
        $('.fake-body').on('click', '.dropdown', function (e) {
            var containerTarget = jQuery(this).closest(container);
            var content = jQuery(this).closest(".dropdown");
            var dropdown = jQuery(e.currentTarget);
            if (dropdown.find('[data-toggle]').length <= 0) {
                return;
            }
            var dropdown_menu = dropdown.find('.dropdown-menu');

            var fixed_dropdown_menu = dropdown_menu.clone(true);
            fixed_dropdown_menu.data('original-menu', dropdown_menu);
            dropdown_menu.css('position', 'relative');
            dropdown_menu.css('display', 'none');
            var currtargetTop;
            var currtargetLeft;
            var dropdownBottom;
            var ftop = 'auto';
            var fbottom = 'auto';

            if (container === "#page") {
                currtargetTop = dropdown.offset().top + dropdown.height();
                currtargetLeft = dropdown.offset().left;
                dropdownBottom = jQuery(window).height() - currtargetTop + dropdown.height();

            }
            var windowBottom = jQuery(window).height() - dropdown.offset().top;
            if (windowBottom < 100) {
                ftop = 'auto';
                fbottom = dropdownBottom + 'px';
            }
            else {
                ftop = currtargetTop + 'px';
                fbottom = "auto";
            }
            fixed_dropdown_menu.css({
                'display': 'block',
                'position': 'absolute',
                'top': ftop,
                'left': currtargetLeft + 'px',
                'bottom': fbottom
            }).appendTo(containerTarget);

            $('#table-content').scroll(function () {
                var tTop;
                var cBottom = $('#table-content').height() - content.position().top;
                var tBottom;
                if (cBottom < 100) {
                    tTop = "auto";
                    tBottom = dropdown.height();
                }
                else {
                    tTop = dropdown.height();
                    tBottom = "auto";
                }
                if (content.hasClass('open')) {
                    fixed_dropdown_menu.css({
                        'display': 'block',
                        'top': tTop,
                        'position': 'absolute',
                        'bottom': tBottom,
                        'left': 0,
                        'z-index': 100
                    }).appendTo(content);
                }
                else {
                    dropdown_menu.css('display', 'none');
                }
            });

            dropdown.on('hidden.bs.dropdown', function () {
                dropdown_menu.removeClass('invisible');
                fixed_dropdown_menu.remove();
                jQuery('.miniListContent').removeClass('dropDownOpen');
            });
        });
    },

    registerNextRecordClickEvent: function(){
        var self = this;
        $('#quickPreviewNextRecordButton').on('click',function(e){
            var element = jQuery(e.currentTarget);
            var container = $('#helpPageOverlay');
            var nextRecordId = element.data('record') || element.data('id');
            var moduleName = container.find('#sourceModuleName').val();
            var appName = element.data('app');
            var templateId, fieldList = container.find('#fieldList');
            if(fieldList.length) {
                templateId = fieldList.val();
            }
            self.showQuickPreviewForId(nextRecordId, moduleName, appName);
        });
    },

    registerPreviousRecordClickEvent: function(){
        var self = this;
        $('#quickPreviewPreviousRecordButton').on('click', function (e) {
            var element = jQuery(e.currentTarget);
            var container = $('#helpPageOverlay');
            var prevRecordId = element.data('record') || element.data('id');
            var moduleName = container.find('#sourceModuleName').val();
            var appName = element.data('app');
            var templateId, fieldList = container.find('#fieldList');
            if(fieldList.length) {
                templateId = fieldList.val();
            }
            self.showQuickPreviewForId(prevRecordId, moduleName, appName);
        });
    },

    showQuickPreviewForId: function(recordId,moduleName,parentApp) {
        var self = this;
        var params = {};
        params['module'] = moduleName;
        params['app'] = parentApp;
        params['record'] = recordId;
        params['view'] = 'ListViewQuickPreview';
        params['navigation'] = 'true';
        app.helper.showProgress();
        app.request.post({data: params}).then(function(err, response) {
            app.helper.hideProgress();
            jQuery('#helpPageOverlay').css({"width":"550px","box-shadow":"-8px 0 5px -5px lightgrey",'height':'100vh','background':'white'});
            app.helper.loadHelpPageOverlay(response);
            var params = {
                setHeight: "100%",
                alwaysShowScrollbar: 2,
                autoExpandScrollbar: true,
                setTop: 0,
                scrollInertia: 70,
                mouseWheel: {preventDefault: true}
            };
            app.helper.showVerticalScroll(jQuery('.quickPreview .modal-body'), params);
            self.registerPreviousRecordClickEvent();
            self.registerNextRecordClickEvent();
        });
    },

    registerEventAutoRefresh : function (contentContainer){
        var instance = this;
        var dataRefresh = contentContainer.find('header.panel_header');
        var makeTargetBlank = contentContainer.find('a.reference-display-value');
        if (makeTargetBlank.length>0){
            makeTargetBlank.attr('target', '_blank');
        }
        dataRefresh.each(function () {
            var parentGrid = dataRefresh.closest('div.dashboardWidgetGridStack');
            var dataTime = dataRefresh.data('refresh-time');
            var widgetId = dataRefresh.parent().parent().data('record');
            if (dataTime >= 5000){
                var dataUrl = dataRefresh.attr('data-url');
                if(!dataUrl){
                    dataUrl = parentGrid.attr('data-url');
                }
                var activeTabId = dataRefresh.data('tabid');
                dataUrl += "&tabid="+activeTabId;
                dataUrl += "&autorefresh=1"
                app.request.post({"url":dataUrl}).then(
                    function(err,data){
                        var today = new Date();
                        var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
                        console.log("reload "+time+"    widgetId : "+widgetId);
                        var miniListId = $(data).find('[name="widgetId"]').val();
                        if(miniListId){
                            parentGrid.attr('data-record',miniListId);
                        }
                        var html = parentGrid.find('div.panel.panel-default.grid-stack-item-content.ui-draggable-handle');
                        html.html(data);
                        var makeTargetBlank = html.find('a.reference-display-value');
                        if (makeTargetBlank.length>0){
                            makeTargetBlank.attr('target', '_blank');
                        }
                    });
            }
        });
    },

    showButtonSavePositionWidgets : function () {
        var thisInstance = this;
        var tabId = thisInstance.getActiveTabId();
        var widgets = $(document).find('#tab_'+tabId+' .dashboardWidgetGridStack');
        $('button#savePositionWidgets.btn.btn-success.saveFieldSequence').hide();
        widgets.off('dragstop resizestop');
        widgets.on('dragstop resizestop',function (e) {
            e.stopPropagation();
            $('button#savePositionWidgets.btn.btn-success.saveFieldSequence').show();
            app.helper.showAlertNotification({'message': app.vtranslate('JS_SAVE_THE_CHANGES_TO_UPDATE_FIELD_SEQUENCE')});
            $('#savePositionWidgets').removeClass('hide');
            var currentTarget = $(e.currentTarget);
            currentTarget.find('.widget-header-title').css('width','70%');
            currentTarget.find('.widget-header-right').css('width','30%');
        });
    },

    updatePositionWidgets : function () {
        var thisInstance = this;
        $('#savePositionWidgets').on('click',function (e) {
            app.helper.showProgress('Saving position widgets');
            $('.grid-stack').data('gridstack').setStatic( true );
            var element = $(e.currentTarget);
            var tabId = thisInstance.getActiveTabId();
            var widgets = $(document).find('#tab_'+tabId+' .dashboardWidgetGridStack');
            var objWidgets = [];
            $.each(widgets,function (key,widget) {
                widget = $(widget);
                var widthPx  = widget.width();
                var widgetId = widget.data('record');
                var x = widget.attr('data-gs-x');
                var y = widget.attr('data-gs-y');
                var width = widget.attr('data-gs-width');
                var height = widget.attr('data-gs-height');
                var data = {
                    'widgetId' : widgetId,
                    'x' : x,
                    'y' : y,
                    'width' : width,
                    'height' : height,
                    'widthPx': widthPx,
                };
                objWidgets.push(data);
            });
            var params = {
                'module' : 'VReports',
                'mode' : 'updatePositionWidgets',
                'action' : 'DashboardActions',
                'data' : objWidgets,
            };
            app.request.post({'data':params}).then(function(err,data){
                if(data == true){
                    app.helper.hideProgress();
                    app.helper.showSuccessNotification({'message': app.vtranslate('JS_CHART_POSITION_UPDATED')});
                    element.hide();
                }
            })
        });
    },

    registerDeleteDashboardTab : function(){
        var self = this;
        var dashBoardContainer = this.getDashboardContainer();
        dashBoardContainer.off("click",'.deleteTab');
        dashBoardContainer.on("click",'.deleteTab',function(e){
            // To prevent tab click event
            e.preventDefault();
            e.stopPropagation();

            var tabId = dashBoardContainer.find('li.active.dashboardTab').data("tabid");
            var tabName = dashBoardContainer.find('li.active.dashboardTab').text().trim();
            if (tabId == 1 && tabName == 'Default'){
                var message = app.vtranslate('JS_CAN_NOT_DELETE_DFAULT');
                app.helper.showErrorNotification({"message":message});
                return;
            }
            var message = app.vtranslate('JS_ARE_YOU_SURE_TO_DELETE_DASHBOARDTAB', tabName);
            app.helper.showConfirmationBox({'message' : message, 'htmlSupportEnable' : false}).then(function(e) {
                app.helper.showProgress();
                var data = {
                    'module' : 'VReports',
                    'action' : 'DashboardActions',
                    'mode' : 'deleteTab',
                    'tabid': tabId
                };

                app.request.post({"data":data}).then(function(err,data){
                    app.helper.hideProgress();
                    if(err == null){
                        jQuery('li[data-tabid="'+tabId+'"]').remove();
                        jQuery('.tab-content #tab_'+tabId).remove();

                        if(jQuery('.dashboardTab.active').length <= 0){
                            // click the first tab if none of the tabs are active
                            var firstTab = jQuery('.dashboardTab').get(0);
                            jQuery(firstTab).find('a').click();
                        }
                        app.helper.showSuccessNotification({"message":'Remove tab dashboard is successfully!!!'});
                    } else {
                        app.helper.showErrorNotification({"message":err});
                    }
                });
            });
        });
    },

    registerAddDashboardTab : function(){
        var self = this;
        var dashBoardContainer = self.getDashboardContainer();
        dashBoardContainer.off('click','.addNewDashBoard');
        dashBoardContainer.on("click",".addNewDashBoard",function(e){
            var action = $(this).data('action');
            if(jQuery('.dashboardTab').length >= VReports_DashBoard_Js.dashboardTabsLimit ){
                app.helper.showErrorNotification({"message":app.vtranslate("JS_TABS_LIMIT_EXCEEDED_1000")});
                return;
            }
            var boardId = $('[name="header-board"]').val();
            var data = {
                'module'	: 'VReports',
                'view'		: 'DashBoardTab',
                'mode'		: 'showDashBoardAddTabForm',
                'boardid'   : boardId,
                'type' : action,
            };
            if (action == 'duplicate') {
                data['tabName'] = dashBoardContainer.find('li.active.dashboardTab').text().trim();
            }

            app.request.post({"data":data}).then(function(err,res){
                if(err === null){
                    var cb = function(data){
                        var board = $('input[name="boardid"]').val();
                        $('select[name="slDashBoardBoard"]').find('option[value="'+board+'"]').attr('selected',true);
                        var form = jQuery(data).find('#AddDashBoardTab');
                        var params = {
                            submitHandler : function(form){
                                var labelEle = jQuery(form).find('[name="tabName"]');
                                var tabName = labelEle.val().trim();
                                if(tabName.length > 50) {
                                    vtUtils.showValidationMessage(labelEle, app.vtranslate('JS_TAB_LABEL_EXCEEDS_CHARS', 50), {
                                        position: {
                                            my: 'bottom left',
                                            at: 'top left',
                                            container : jQuery(form)
                                        }
                                    });
                                    return false;
                                }else {
                                    vtUtils.hideValidationMessage(labelEle);
                                }

                                var paramsForDup = jQuery(form).serializeFormData();
                                paramsForDup['tabName'] = paramsForDup['tabName'].trim();
                                if (action == 'duplicate') {
                                    paramsForDup['duplicateTabId'] = VReports_DashBoard_Js.currentInstance.getActiveTabId();
                                }
                                app.request.post({"data":paramsForDup}).then(function (err,data) {
                                    app.helper.hideModal();
                                    if(err) {
                                        app.helper.showErrorNotification({"message":err});
                                    }else if (paramsForDup['slDashBoardBoard'] == '' || paramsForDup['boardid'] == paramsForDup['slDashBoardBoard']){
                                        var tabid = data["tabid"];
                                        var tabname = data["tabname"];
                                        var tabEle = '<li class="dashboardTab" data-tabid="'+tabid+'">';
                                        tabEle += '<a data-toggle="tab" href="#tab_'+tabid+'">\n\
														<div>\n\
															<span class="name textOverflowEllipsis" style="width:10%">\n\
															<strong>'+tabname+'</strong>\n\
															</span>\n\
															<span class="editTabName hide"><input type="text" name="tabName"></span>\n\
															<i class="fa fa-bars moveTab hide"></i>\n\
														</div>\n\
														</a>';
                                        tabEle += '</li>';
                                        var tabContentEle = '<div id="tab_'+tabid+'" class="tab-pane fade" data-tabid="'+tabid+'" data-tabname="'+tabname+'"></div>';
                                        jQuery('.moreSettings').before(tabEle);
                                        jQuery('.moreSettings').prev().find('.name > strong').text(tabname);
                                        jQuery('li.dashboardTab[data-tabid="'+tabid+'"]').attr('tab-name',tabname);
                                        jQuery('li.dashboardTab[data-tabid="'+tabid+'"]').find('span.name').attr('value',tabname);
                                        dashBoardContainer.find('.tab-content').append(tabContentEle);
                                        // selecting added tab
                                        var currentTab = jQuery('li[data-tabid="'+tabid+'"]');
                                        currentTab.find('a').click();
                                    }
                                });
                            }
                        };
                        form.vtValidate(params);
                    };
                    app.helper.showModal(res,{"cb":cb});
                }
            })

        })
    },
    //Boards
    registerAddBoards : function(){
        var self = this;
        var dashBoardContainer = self.getDashboardContainer();
        dashBoardContainer.off('click','.addBoards');
        dashBoardContainer.on("click",".addBoards",function(e){
            var currentElement = jQuery(e.currentTarget);
            var data = {
                'module'	: 'VReports',
                'view'		: 'DashBoardTab',
                'mode'		: 'showBoardEditForm',
                'viewmode'  : 'addnew'
            };
            app.request.post({"data":data}).then(function(err,res){
                if(err === null){
                    var cb = function(data){
                        var form = jQuery(data).find('#EditBoard');
                        var params = {
                            submitHandler : function(form){
                                var labelEle = jQuery(form).find('[name="boardName"]');
                                var boardName = labelEle.val().trim();
                                if(boardName.length > 30) {
                                    vtUtils.showValidationMessage(labelEle, app.vtranslate('JS_TAB_LABEL_EXCEEDS_CHARS', 50), {
                                        position: {
                                            my: 'bottom left',
                                            at: 'top left',
                                            container : jQuery(form)
                                        }
                                    });
                                    return false;
                                }else {
                                    vtUtils.hideValidationMessage(labelEle);
                                }

                                var params = jQuery(form).serializeFormData();
                                params['boardName'] = params['boardName'].trim();
                                app.request.post({"data":params}).then(function (err,data) {
                                    app.helper.hideModal();
                                    if(err) {
                                        app.helper.showErrorNotification({"message":err});
                                    }else {
                                        app.helper.showSuccessNotification({'message': "Add Board : '"+data.boardname+"' Success!"});
                                        var option = '<option value="'+data.boardid+'">'+data.boardname+'</option>';
                                        $('select[name="header-board"]').find('optgroup:first').find('option:last').after(option);
                                        $('select[name="header-board"]').select2();
                                    }
                                });
                            }
                        };
                        form.vtValidate(params);
                    };
                    app.helper.showModal(res,{"cb":cb});
                }
            })

        })
    },

    registerEditBoards : function(){
        var self = this;
        var dashBoardContainer = self.getDashboardContainer();
        dashBoardContainer.off('click','.editBoards');
        dashBoardContainer.on("click",".editBoards",function(e){
            var boardId = $('[name="header-board"]').val();
            var currentElement = jQuery(e.currentTarget);
            var data = {
                'module'	: 'VReports',
                'view'		: 'DashBoardTab',
                'mode'		: 'showBoardEditForm'
            };
            app.request.post({"data":data}).then(function(err,res){
                if(err === null){
                    var cb = function(data){
                        var form = jQuery(data).find('#EditBoard');
                        var params = {
                            submitHandler : function(form){
                                var labelEle = jQuery(form).find('[name="boardName"]');
                                var boardName = labelEle.val().trim();
                                if(boardName.length > 30) {
                                    vtUtils.showValidationMessage(labelEle, app.vtranslate('JS_TAB_LABEL_EXCEEDS_CHARS', 50), {
                                        position: {
                                            my: 'bottom left',
                                            at: 'top left',
                                            container : jQuery(form)
                                        }
                                    });
                                    return false;
                                }else {
                                    vtUtils.hideValidationMessage(labelEle);
                                }

                                var params = jQuery(form).serializeFormData();
                                params['boardName'] = params['boardName'].trim();
                                app.request.post({"data":params}).then(function (err,data) {
                                    app.helper.hideModal();
                                    if(err) {
                                        app.helper.showErrorNotification({"message":err});
                                    }else {
                                        app.helper.showSuccessNotification({'message': "Edit Board : '"+data.boardname+"' Success!"});
                                        var oldBoardName = $('select[name="header-board"]').find('[value="'+data.boardid+'"]').text();
                                        $('select[name="header-board"]').find('[value="'+data.boardid+'"]').text(data.boardname);
                                        $('select[name="header-board"]').select2();
                                        if($('select[name="header-board"]').val() == data.boardid){
                                            var elmTab = dashBoardContainer.find('.dashboardTab[tab-name="Default-'+oldBoardName+'"]');
                                            elmTab.attr('tab-name','Default-'+data.boardname);
                                            elmTab.find('span.name').attr('value','Default-'+data.boardname);
                                            elmTab.find('span.name').find('strong').text('Default-'+data.boardname);
                                        }
                                    }
                                });
                            }
                        };
                        form.vtValidate(params);
                    };
                    app.helper.showModal(res,{"cb":cb});
                    var form = jQuery('.myModal');
                    form.find('[name="select-board"]').off('change');
                    form.find('[name="select-board"]').on('change',function () {
                        var params = {
                            'module'	: 'VReports',
                            'action'	: 'DashboardActions',
                            'mode'		: 'getBoardInfo',
                            id          : $(this).val(),
                        };
                        app.request.post({"data":params}).then(function(err,res){
                            if(res){
                                form.find('[name="boardName"]').val(res.boardname);
                                form.find('[name="members[]"]').val(res.shared_to.split(','));
                                form.find('[name="members[]"]').select2();
                                if (res.shared_to.indexOf("Default") > 0) {
                                    form.find('[name="defaultToEveryone"]').attr('checked', true);
                                }
                            }
                        })
                    });
                    if(boardId > 1){
                        form.find('[name="select-board"]').val(boardId);
                        form.find('[name="select-board"]').select2();
                    }
                    form.find('[name="select-board"]').trigger('change');
                }
            })

        })
    },
    registerDeleteBoards : function(){
        var self = this;
        var dashBoardContainer = self.getDashboardContainer();
        dashBoardContainer.off('click','.deleteBoard');
        dashBoardContainer.on("click",".deleteBoard",function(e){
            var boardId = $('[name="header-board"]').val();
            var currentElement = jQuery(e.currentTarget);
            var data = {
                'module'	: 'VReports',
                'view'		: 'DashBoardTab',
                'mode'		: 'showBoardEditForm',
                'viewmode'  : 'delete'
            };
            app.request.post({"data":data}).then(function(err,res){
                if(err === null){
                    var cb = function(data){
                        var form = jQuery(data).find('#EditBoard');
                        var params = {
                            submitHandler : function(form){
                                var params = jQuery(form).serializeFormData();
                                app.request.post({"data":params}).then(function (err,data) {
                                    app.helper.hideModal();
                                    if(err) {
                                        app.helper.showErrorNotification({"message":err});
                                    }else {
                                        var oldBoardName = $('select[name="header-board"]').find('[value="'+data+'"]').text();
                                        app.helper.showSuccessNotification({'message': "Delete Board "+oldBoardName+" Success!"});
                                        if($('select[name="header-board"]').val() == data){
                                            $('select[name="header-board"]').val(1);
                                            $('select[name="header-board"]').find('[value="'+data+'"]').remove();
                                            $('select[name="header-board"]').trigger('change');
                                        }else{
                                            $('select[name="header-board"]').find('[value="'+data+'"]').remove();
                                            $('select[name="header-board"]').select2();
                                        }

                                    }
                                });
                            }
                        };
                        form.vtValidate(params);
                    };
                    app.helper.showModal(res,{"cb":cb});
                    var form = jQuery('.myModal');
                    if(boardId > 1){
                        form.find('[name="select-board"]').val(boardId);
                        form.find('[name="select-board"]').select2();
                    }
                }
            })

        })
    },

    registerDashBoardTabRename : function(){
        var container = $('div.tabContainer');
        container.off('click','.renameTabs');
        container.on('click','.renameTabs',function (e) {
            var currentTarget = container.find('li.dashboardTab.active');
            var tabid = currentTarget.data("tabid");
            var tabname = currentTarget.data("tabname").toString();
            var data = {
                'module'	: 'VReports',
                'view'		: 'DashBoardTab',
                'mode'		: 'showRenameTabForm',
                'tabid'     : tabid,
                'tabname'   : tabname,
            };
            app.request.post({"data":data}).then(function(err,res){
                if(err === null){
                    var cb = function(data){
                        var form = jQuery(data).find('#renameTab');
                        form.on('click', '[name="saveButton"]', function(e){
                            e.preventDefault();
                            e.stopPropagation();
                            var newName = form.find('input#tabName').val();
                            var tabid = form.find('input[name="tabId"]').val();
                            if(newName.trim() == "") {
                                vtUtils.showValidationMessage(form, app.vtranslate('JS_TAB_NAME_SHOULD_NOT_BE_EMPTY'), {
                                    position : {
                                        my: 'top left',
                                        container: form.closest('input#tabName')
                                    }
                                });
                                return false;
                            }
                            if(newName.length > 50) {
                                vtUtils.showValidationMessage(form, app.vtranslate('JS_TAB_LABEL_EXCEEDS_CHARS', 50), {
                                    position: {
                                        my: 'top left',
                                        container: form.closest('input#tabName')
                                    }
                                });
                                return false;
                            } else {
                                vtUtils.hideValidationMessage(form);
                            }
                            if(newName != tabname){
                                var params = {
                                    'module' : 'VReports',
                                    'action' : 'DashboardActions',
                                    'mode' : 'renameTab',
                                    'tabid' : tabid,
                                    'tabname' : newName,
                                };
                                app.helper.showProgress();
                                app.request.post({data:params}).then(function(err,data){
                                    app.helper.hideProgress();
                                    app.helper.hideModal();
                                    if(err == null){
                                        app.helper.showSuccessNotification({"message":''});
                                        currentTarget.data('tabname', newName);
                                    } else {
                                        app.helper.showErrorNotification({"message":err});
                                        currentTarget.find('.name > strong').text(tabname);
                                    }
                                    currentTarget.find('.name').attr("value",newName);
                                    $('li.dashboardTab.active').find('strong').text(newName);
                                    $('head').find('title').text(newName);
                                })
                            }
                        });
                        vtUtils.hideValidationMessage(data);
                    };
                    app.helper.showModal(res,{"cb":cb});
                }
            });
        });
    },

    registerDashBoardTabClick : function(){
        var thisInstance = this;
        var container = this.getContainer();
        if(container.length == 0){
            var dashBoardContainer = $(".dashBoardContainer");
        }else{
            var dashBoardContainer = jQuery(container).closest(".dashBoardContainer");
        }
        dashBoardContainer.off("shown.bs.tab").on("shown.bs.tab",".dashboardTab",function(e){
            for(var key in VReports_DashBoard_Js.intervalVal) {
                clearInterval(VReports_DashBoard_Js.intervalVal[key]);
            }
            var currentTarget = jQuery(e.currentTarget);
            var tabid = currentTarget.data('tabid');
            var boardid = $('select[name="header-board"]').val();
            app.changeURL("index.php?module=VReports&view=DashBoard&boardid="+boardid+"&tabid="+tabid);

            // If tab is already loaded earlier then we shouldn't reload tab and register gridster
            if(typeof jQuery("#tab_"+tabid).find(".dashBoardTabContainer").val() !== 'undefined'){
                // We should overwrite gridster with current tab which is clicked
                VReports_DashBoard_Js.setGridstack(tabid);
                $('head').find('title').text(thisInstance.getActiveTabName());
                thisInstance.registerNotificationDynamic(tabid);
                return;
            }
            var data = {
                'module': 'VReports',
                'view': 'DashBoardTab',
                'mode': 'getTabContents',
                'tabid' : tabid
            };

            app.request.post({"data":data}).then(function(err,data){
                if(err === null){
                    var dashBoardModuleName = jQuery("#tab_"+tabid,".tab-content").html(data).find('[name="dashBoardModuleName"]').val();
                    if(typeof dashBoardModuleName != 'undefined' && dashBoardModuleName.length > 0 ) {
                        var dashBoardInstanceClassName = app.getModuleSpecificViewClass(app.view(),dashBoardModuleName);
                        if(dashBoardInstanceClassName != null) {
                            var dashBoardInstance = new window[dashBoardInstanceClassName]();
                        }
                    }
                    app.event.trigger("post.DashBoardTab.load", dashBoardInstance);
                    $('head').find('title').text(thisInstance.getActiveTabName());
                }
            });
        });
    },

    registerChangeBoard:function(){
        var thisInstance = this;
        var dashBoardContainer = this.getDashboardContainer();
        $('[name="header-board"]').on('change',function () {
            dashBoardContainer.find('.dashboardTab').each(function () {
                $(this).remove();
            });
            dashBoardContainer.find('.tab-pane').each(function () {
                $(this).remove();
            });
            dashBoardContainer.find('.moreSettings .saveFieldSequence').each(function () {
                $(this).addClass('hide');
            });

            var data = {
                'module': 'VReports',
                'action': 'DashboardActions',
                'mode': 'getTabsByBoardId',
                'boardid' : $(this).val()
            };
            app.request.post({"data":data}).then(function(err,data){
                app.helper.hideModal();
                if(err) {
                    app.helper.showErrorNotification({"message":err});
                }else {
                    $(data).each(function (idx,tab) {
                        var tabid = tab["id"];
                        var tabname = tab["tabname"];
                        var tabEle = '<li class="dashboardTab" data-tabid="'+tabid+'">';
                        if(tab['sharedboard']){
                            tabEle += '<a data-toggle="tab" href="#tab_'+tabid+'">\n\
														<div>\n\
															<span class="name textOverflowEllipsis" style="width:10%">\n\
															<strong>'+tabname+'</strong>\n\
															</span>\n\
															<span class="editTabName hide"><input type="text" name="tabName"></span>\n\
														</div>\n\
														</a>';
                        }else{
                            tabEle += '<a data-toggle="tab" href="#tab_'+tabid+'">\n\
														<div>\n\
															<span class="name textOverflowEllipsis" style="width:10%">\n\
															<strong>'+tabname+'</strong>\n\
															</span>\n\
															<span class="editTabName hide"><input type="text" name="tabName"></span>\n\
															<i class="fa fa-bars moveTab hide"></i>\n\
														</div>\n\
														</a>';
                        }
                        tabEle += '</li>';
                        jQuery('.moreSettings').before(tabEle);
                        jQuery('.moreSettings').prev().find('.name > strong').text(tabname);
                        var tabContentEle = '<div id="tab_'+tabid+'" class="tab-pane fade" data-tabid="'+tabid+'" data-tabname="'+tabname+'"></div>';
                        jQuery('li.dashboardTab[data-tabid="'+tabid+'"]').attr('data-tabname',tabname);
                        jQuery('li.dashboardTab[data-tabid="'+tabid+'"]').find('span.name').attr('value',tabname);
                        dashBoardContainer.find('.tab-content').append(tabContentEle);
                        if(tab['sharedboard'] && !tab['is_admin']){
                            jQuery('.moreSettings').addClass('hide');
                        }else{
                            jQuery('.moreSettings').removeClass('hide');
                        }
                    });
                    thisInstance.registerDashBoardTabClick();
                    var tabid = thisInstance.getUrlParameter('tabid');
                    if(tabid){
                        if(dashBoardContainer.find('.dashboardTab[data-tabid="'+tabid+'"]').length>0){
                            dashBoardContainer.find('.dashboardTab[data-tabid="'+tabid+'"]').find('a').click();
                            $('head').find('title').text(thisInstance.getActiveTabName());
                        }else{
                            dashBoardContainer.find('.dashboardTab:first').find('a').click();
                            $('head').find('title').text(thisInstance.getActiveTabName());
                        }
                    }else{
                        dashBoardContainer.find('.dashboardTab:first').find('a').click();
                        $('head').find('title').text(thisInstance.getActiveTabName());
                    }
                }
            });
        });
        //check if tab not exist

        var tabid = 1;
        if(thisInstance.getUrlParameter('tabid')){
            tabid = thisInstance.getUrlParameter('tabid');
        }
        var boardid = 1;
        if(thisInstance.getUrlParameter('boardid')){
            boardid = thisInstance.getUrlParameter('boardid');
        }
        if(boardid != $('[name="header-board"]').val() && thisInstance.getUrlParameter('boardid') != undefined){
            app.changeURL("index.php?module=VReports&view=DashBoard&boardid="+1+"&tabid="+tabid);
            app.helper.showAlertNotification({'message': app.vtranslate('JS_BOARD_NOT_EXIST')});
        }
        if(tabid && thisInstance.getUrlParameter('tabid') != undefined){
            if(dashBoardContainer.find('.dashboardTab[data-tabid="'+tabid+'"]').length>0){
                dashBoardContainer.find('.dashboardTab[data-tabid="'+tabid+'"]').find('a').click();
                $('head').find('title').text(thisInstance.getActiveTabName());
            }else{
                app.helper.showAlertNotification({'message': app.vtranslate('JS_TAB_NOT_EXIST')});
                dashBoardContainer.find('.dashboardTab:first').find('a').click();
                $('head').find('title').text(thisInstance.getActiveTabName());
            }
        }else{
            dashBoardContainer.find('.dashboardTab:first').find('a').click();
            $('head').find('title').text(thisInstance.getActiveTabName());
        }
        //end check
    },

    // Dynamic Filters

    registerAddDynamicFilter : function () {
        var thisInstance = this;
        var dashBoardContainer = thisInstance.getDashboardContainer();
        dashBoardContainer.off('click','.dynamicFilter');
        dashBoardContainer.on("click",".dynamicFilter",function(e){
            var activeTabId = VReports_DashBoard_Js.currentInstance.getActiveTabId();
            var data = {
                'module'	: 'VReports',
                'view'		: 'DashBoardTab',
                'mode'		: 'showDynamicFiltersEditForm',
                'dashboardId'		: activeTabId,
            };
            app.request.post({"data":data}).then(function(err,res){
                if(err === null){
                    var cb = function(data){
                        var form = jQuery(data).find('#EditDynamicFilters');
                        form.on('click', '.relatedPopup', function(e){
                            var popupInstance = Vtiger_Popup_Js.getInstance();
                            var paramsPopup = {
                                'module' : 'Accounts',
                                'src_module': '',
                                'src_field': '',
                                'src_record': '',
                                'view': 'Popup',
                            };
                            popupInstance.showPopup(paramsPopup,"post.RecordList.click");
                        });
                        app.event.on("post.RecordList.click", function(event, data) {
                            var responseData = JSON.parse(data);
                            var accountId = Object.keys(responseData)[0];
                            var displayName = responseData[accountId].name;
                            form.find('input[name="dynamic_filter_accountid"]').val(accountId);
                            form.find('input#dynamic_filter_account_display').val(displayName);
                            form.find('span#clear-related-record').show();
                        });
                        form.on('click','#clear-related-record',function () {
                            form.find('input[name="dynamic_filter_accountid"]').val('');
                            form.find('input#dynamic_filter_account_display').val('');
                            form.find('span#clear-related-record').hide();
                        });

                        var params = {
                            submitHandler : function(form){
                                var params = jQuery(form).serializeFormData();
                                app.request.post({"data":params}).then(function (err,data) {
                                    app.helper.hideModal();
                                    if(err) {
                                        app.helper.showErrorNotification({"message":err});
                                    }else {
                                        app.helper.showSuccessNotification({'message': "Save Dynamic Filter success!"});
                                        var data = {
                                            'module' : 'VReports',
                                            'action': 'DashboardActions',
                                            'mode': 'deleteOrgInUrl',
                                            'tabId': thisInstance.getActiveTabId(),
                                            'boardId': thisInstance.getActiveBoardId(),
                                        };
                                        app.request.post({"data":data}).then(function (err,data) {
                                            if (!err){
                                                if (data == 'reload'){
                                                    window.location.reload();
                                                } else {
                                                    window.location.href = data;
                                                }
                                            }
                                        })
                                        // dashBoardContainer.find('#tab_'+activeTabId).find('div[data-widget-type="Mini List VReports"]  .action-widget-header[data-event="Refresh"]').trigger('click');
                                    }
                                });
                            }
                        };
                        form.vtValidate(params);
                    };
                    app.helper.showModal(res,{"cb":cb});
                }
            })

        })
    },

    registerRearrangeTabsEvent : function(){
        var dashBoardContainer = this.getDashboardContainer();

        // on click of Rearrange button
        dashBoardContainer.on("click",'ul.moreDashBoards .reArrangeTabs',function(e){
            var currentEle = jQuery(e.currentTarget);
            dashBoardContainer.find(".dashBoardDropDown").addClass('hide');

            var sortableContainer = dashBoardContainer.find(".tabContainer");
            var sortableEle = sortableContainer.find(".sortable");

            currentEle.addClass("hide");
            dashBoardContainer.find(".deleteTab").addClass("hide");
            dashBoardContainer.find(".moveTab").removeClass("hide");
            dashBoardContainer.find(".updateSequence").removeClass("hide");

            sortableEle.sortable({
                'containment': sortableContainer,
                stop : function(){}
            });
        });
        // On click of save sequence
        dashBoardContainer.find(".updateSequence").on("click",function(e){
            var reArrangedList = {};
            var currEle = jQuery(e.currentTarget);
            jQuery(".sortable li").each(function(i,el){
                var el = jQuery(el);
                var tabid = el.data("tabid");
                reArrangedList[tabid] = ++i;
            });

            var data = {
                "module" : "VReports",
                "action" : "DashboardActions",
                "mode" : "updateTabSequence",
                "sequence" : JSON.stringify(reArrangedList)
            };

            app.request.post({"data":data}).then(function(err,data){
                if(err == null){
                    currEle.addClass("hide");
                    dashBoardContainer.find(".moveTab").addClass("hide");
                    dashBoardContainer.find(".reArrangeTabs").removeClass("hide");
                    dashBoardContainer.find(".deleteTab").removeClass("hide");
                    dashBoardContainer.find(".dashBoardDropDown").removeClass('hide');

                    var sortableEle = dashBoardContainer.find(".tabContainer").find(".sortable");
                    sortableEle.sortable('destroy');

                    app.helper.showSuccessNotification({"message":'Rearrange Tabs is successfully'});
                } else {
                    app.helper.showErrorNotification({"message":err});
                }
            });
        });

    },

    registerAddWidget : function(){
        var thisInstance = this;
        $('#openAddWidget').off('click');
        $('#openAddWidget').on('click',function () {
            var button = $(this);
            button.attr("disabled", true);
            var tabActive = thisInstance.getActiveTabId();
            var data = {
                'module' : app.getModuleName(),
                'view' : 'ListAjax',
                'mode' : 'openWidget',
                'tabid' : tabActive,
            };
            app.helper.showProgress();
            app.request.post({"data": data}).then(function (err, res) {
                app.helper.hideProgress();
                button.attr("disabled", false);
                app.helper.showModal(res,{"cb": function () {
                    $('div.table-addwidget-scroller').perfectScrollbar();
                    $('a#addWidget').off('click').on('click',function () {
                        app.helper.hideModal();
                    });
                    $('ul.chartReport').each(function () {
                        var ul = $(this);
                        var hideUl = 0;
                        ul.children().each(function () {
                            if ($(this).hasClass('hide')) {
                                hideUl++;
                            }
                        });
                        if (ul.children().length == 0 || hideUl == ul.children().length) {
                            $(this).parent().addClass('hide');
                        }
                    })
                }});
            })
        })
    },

    registerAppTriggerEvent : function() {
        jQuery('.app-menu').removeClass('hide');
        var toggleAppMenu = function(type) {
            var appMenu = jQuery('.app-menu');
            var appNav = jQuery('.app-nav');
            appMenu.appendTo('#page');
            appMenu.css({
                'top' : appNav.offset().top + appNav.height(),
                'left' : 0
            });
            if(typeof type === 'undefined') {
                type = appMenu.is(':hidden') ? 'show' : 'hide';
            }
            if(type == 'show') {
                appMenu.show(200, function() {});
            } else {
                appMenu.hide(200, function() {});
            }
        };

        jQuery('.app-trigger, .app-icon, .app-navigator').on('click',function(e){
            e.stopPropagation();
            toggleAppMenu();
        });

        jQuery('html').on('click', function() {
            toggleAppMenu('hide');
        });

        jQuery(document).keyup(function (e) {
            if (e.keyCode == 27) {
                if(!jQuery('.app-menu').is(':hidden')) {
                    toggleAppMenu('hide');
                }
            }
        });

        jQuery('.app-modules-dropdown-container').hover(function(e) {
            var dropdownContainer = jQuery(e.currentTarget);
            jQuery('.dropdown').removeClass('open');
            if(dropdownContainer.length) {
                if(dropdownContainer.hasClass('dropdown-compact')) {
                    dropdownContainer.find('.app-modules-dropdown').css('top', dropdownContainer.position().top - 8);
                } else {
                    dropdownContainer.find('.app-modules-dropdown').css('top', '');
                }
                dropdownContainer.addClass('open').find('.app-item').addClass('active-app-item');
            }
        }, function(e) {
            var dropdownContainer = jQuery(e.currentTarget);
            dropdownContainer.find('.app-item').removeClass('active-app-item');
            setTimeout(function() {
                if(dropdownContainer.find('.app-modules-dropdown').length && !dropdownContainer.find('.app-modules-dropdown').is(':hover') && !dropdownContainer.is(':hover')) {
                    dropdownContainer.removeClass('open');
                }
            }, 500);

        });

        jQuery('.app-item').on('click', function() {
            var url = jQuery(this).data('defaultUrl');
            if(url) {
                window.location.href = url;
            }
        });

        jQuery(window).resize(function() {
            jQuery(".app-modules-dropdown").mCustomScrollbar("destroy");
            app.helper.showVerticalScroll(jQuery(".app-modules-dropdown").not('.dropdown-modules-compact'), {
                setHeight: $(window).height(),
                autoExpandScrollbar: true
            });
            jQuery('.dropdown-modules-compact').each(function() {
                var element = jQuery(this);
                var heightPer = parseFloat(element.data('height'));
                app.helper.showVerticalScroll(element, {
                    setHeight: $(window).height()*heightPer - 3,
                    autoExpandScrollbar: true,
                    scrollbarPosition: 'outside'
                });
            });
        });

        app.helper.showVerticalScroll(jQuery(".app-modules-dropdown").not('.dropdown-modules-compact'), {
            setHeight: $(window).height(),
            autoExpandScrollbar: true,
            scrollbarPosition: 'outside'
        });
        jQuery('.dropdown-modules-compact').each(function() {
            var element = jQuery(this);
            var heightPer = parseFloat(element.data('height'));
            app.helper.showVerticalScroll(element, {
                setHeight: $(window).height()*heightPer - 3,
                autoExpandScrollbar: true,
                scrollbarPosition: 'outside'
            });
        });
    },

    showDidePreViewHistory : function(contentContainer){
        if(!VReports_DashBoard_Js.gridstack)
            return false;
        var row = contentContainer.find('.history_widget');
        row.on('hover', function (e) {
            var element = $(this);
            element.find('a.quickView.history-widget').show();
        }).mouseleave(function (e) {
            var element = $(this);
            element.find('a.quickView.history-widget').hide();
        });
    },

    showHideHeaderActionsWidgets : function() {
        if(!VReports_DashBoard_Js.gridstack)
            return false;
        var container = VReports_DashBoard_Js.gridstack.container;
        var widget = container.find('.dashboardWidgetGridStack');
        widget.on('hover', function (e) {
            var element = $(this);
            element.find('.action-widget-header').removeClass('hide');
            element.find('.page-number').removeClass('hide');
        }).mouseleave(function (e) {
            var element = $(this);
            element.find('.action-widget-header').addClass('hide');
            element.find('.page-number').addClass('hide');
        });
    },

    sortWidgetDiv : function () {
        var tabActive = this.getActiveTabId();
        var mylist = $('.grid-stack-tab'+tabActive);
        var dashboardWidgetGridStack = mylist.children('div.grid-stack-item').get();
        dashboardWidgetGridStack.sort(function(a,b){
            return $(a).data('gs-y') < $(b).data('gs-y') ? -1 : 1;
        });
        $.each(dashboardWidgetGridStack, function(index, item) {
            mylist.append(item);
        });
    },

    getUrlParameter : function (sParam) {
        var sPageURL = decodeURIComponent(window.location.search.substring(1)),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;

        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');

            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : sParameterName[1];
            }
        }
    },
    registerNotificationDynamic : function(tabId){
        var notificationDynamic = jQuery("#tab_"+tabId,".tab-content").find('.check-dynamicfilter').val();
        if(notificationDynamic == ''){
            $('.notification-dynamic').find('i').remove()
        }else{
            $('.notification-dynamic').append("<i class='fa fa-filter' style='color: darkgray;padding-left: 10px;vertical-align: middle;margin-top: 11px;'></i>")
        }
    },
    registerEvents : function () {
        var thisInstance = this;
        this.registerAddWidget();
        // because register Vtiger_Index_Js, we don't need function below anymore
        // this.registerAppTriggerEvent();
        this.registerDeleteDashboardTab();
        this.registerAddDashboardTab();
        this.registerEditBoards();
        this.registerAddBoards();
        this.registerDashBoardTabRename();
        this.registerDashBoardTabClick();
        this.registerChangeBoard();
        this.registerDeleteBoards();
        this.registerAddDynamicFilter();
        this.registerRearrangeTabsEvent();
        this.registerLazyLoadWidgets();
        app.event.on("post.DashBoardTab.load",function(event, dashBoardInstance){
            var instance = thisInstance;
            if(typeof dashBoardInstance != 'undefined') {
                instance = dashBoardInstance;
                instance.registerEvents();
            }
            var tabIdActive = instance.getActiveTabId();
            instance.registerNotificationDynamic(tabIdActive)
            VReports_DashBoard_Js.setGridstack(tabIdActive);
            instance.init();
            instance.resizeGridStack();
            instance.resizeGrid();
            instance.sortWidgetDiv();
            instance.loadWidgets();
            instance.showHideHeaderActionsWidgets();
        });
        this.registerResizeAndMove();
        this.updatePositionWidgets();
        this.showButtonSavePositionWidgets();

        app.event.trigger("post.DashBoardTab.load");
        app.event.on("post.DashBoardTab.registerEvent",function (event, dashBoardInstance) {
            var instance = thisInstance;
            instance.showHideHeaderActionsWidgets();
        });
        $('head').find('title').text(thisInstance.getActiveTabName());
        $('.notification-dynamic').tooltip({
            placement: 'top'
        });
    }
});
