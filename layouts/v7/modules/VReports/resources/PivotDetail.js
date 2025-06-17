
VReports_Detail_Js("VReports_PivotDetail_Js", {
    selectRow : '',
    selectDataField : '',
    /**
     * Function used to display message when there is no data from the server
     */
    displayNoDataMessage: function () {
        $('#chartcontent').html('<div>' + app.vtranslate('JS_NO_PIVOT_DATA_AVAILABLE') + '</div>').css(
            {'text-align': 'center', 'position': 'relative', 'top': '100px'});
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
}, {
    init : function() {
        this._super();
        VReports_PivotDetail_Js.currentInstance = this;
    },

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
        if (identified == 'groupbyfield_rows') {
            var selectedData = VReports_PivotDetail_Js.currentInstance.selectDataRow;
        } else if (identified == 'datafields'){
            var selectedData = VReports_PivotDetail_Js.currentInstance.selectDataField;
        }
        var data = select.val();
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
        }
        sortBy.append(valueOrder);
        if (identified == 'groupbyfield_rows') {
            VReports_PivotDetail_Js.currentInstance.selectDataRow = data;
        } else if (identified == 'datafields'){
            VReports_PivotDetail_Js.currentInstance.selectDataField = data;
        }
        var selectSort = sortBy.val();
        var valueSort = Array();
        if (selectSort) {
            $.each(selectSort, function (key, sortVal) {
                valueSort.push(sortVal)
            });
        }
        $('input[name="sort_by"]').val(JSON.stringify(valueSort));


    },

    registerUpdateSelectElementEventForRows: function() {
        var thisInstance = this;
        jQuery("#groupbyfield_rows , #sort_by").on("change", function(e) {
            var uiUl = jQuery(e.currentTarget).siblings('div').find('ul');
            thisInstance.registerPushValueSortableEvent(uiUl);
            if (jQuery(e.currentTarget).attr('id') !== 'sort_by') {
                thisInstance.registerPushValueToSortBy(uiUl);
            }
            $("#sort_by").select2({
                maximumSelectionSize: 3
            });
            var valueOption = jQuery(e.currentTarget).val();
            var fieldsColumn = jQuery("#groupbyfield_columns");
            VReports_PivotEdit3_Js.updateSelectElement(valueOption, fieldsColumn);

        });
    },

    registerUpdateSelectElementEventForColumns: function() {
        var thisInstance = this;
        jQuery("#groupbyfield_columns").on("change", function(e) {
            jQuery(e.currentTarget).siblings('div').find('ul').sortable('refresh');
            var uiUl = jQuery(e.currentTarget).siblings('div').find('ul');
            thisInstance.registerPushValueSortableEvent(uiUl);
            var valueOption = jQuery(e.currentTarget).val();
            var fieldsRow = jQuery("#groupbyfield_rows");
            VReports_PivotEdit3_Js.updateSelectElement(valueOption, fieldsRow);
        });
    },

    registerUpdateSelectElementEventForData: function() {
        var thisInstance = this;
        jQuery("#datafields").on("change", function(e) {
            var uiUl = jQuery(e.currentTarget).siblings('div').find('ul');
            thisInstance.registerPushValueSortableEvent(uiUl);
            thisInstance.registerPushValueToSortBy(uiUl);
            thisInstance.registerSortableEvent();
            var focus = $(this);
            var renameFIelds = $('.rename-field-translate');
            var valueFocus = focus.val();
            var rename_field = $('[name="rename_field"]');
            if (rename_field.length == 0) {
                var label = focus.find("option:selected").text();
                renameFIelds.append(
                    '<tr>'+
                    '<td class="fieldLabel" name="{$RENAME->fieldname}">'+label+'</td>'+
                    '<td class="fieldValue"">'+
                    '<input type="text" data-selected="'+valueFocus[0]+'" data-fieldlabel="'+label+'" data-fieldtype="string" class="inputElement" name="rename_field" value="">'+
                    '</td>'+
                    '</tr>'
                );
            }else{
                if (valueFocus != null) {
                    $.each(valueFocus, function (idx, val) {
                        var idxRenameField = $('[data-selected="' + val + '"]');
                        var label = focus.find('[value="' + val + '"]').text();
                        if (idxRenameField.length == 0) {
                            renameFIelds.append(
                                '<tr>'+
                                '<td class="fieldLabel" name="{$RENAME->fieldname}">'+label+'</td>'+
                                '<td class="fieldValue"">'+
                                '<input type="text" data-selected="'+val+'" data-fieldlabel="'+label+'" data-fieldtype="string" class="inputElement" name="rename_field" value="">'+
                                '</td>'+
                                '</tr>'
                            );
                        }
                    });
                }

                $.each(rename_field, function (idx, val) {
                    var dataSelected = $(val).data('selected');
                    if (valueFocus == null) {
                        $(val).closest('tr').remove();
                        return;
                    }
                    if (!valueFocus.includes(dataSelected)) {
                        $(val).closest('tr').remove();
                    }
                });
            }
        });
    },

    registerSaveOrGenerateReportEvent: function () {
        var thisInstance = this;
        jQuery('.generateReportPivot').on('click', function (e) {
            var advFilterCondition = thisInstance.calculateValues();
            var recordId = thisInstance.getRecordId();
            var currentMode = jQuery(e.currentTarget).data('mode');
            var groupByFieldRows = jQuery('#groupbyfield_rows').val();
            var groupByFieldColumn = jQuery('#groupbyfield_columns').val();
            var dataField = jQuery('#datafields').val();
            if(dataField == null || dataField == '') {
                vtUtils.showValidationMessage(jQuery('#datafields').parent().find('.select2-choices'), app.vtranslate('JS_REQUIRED_FIELD'));
                return false;
            } else {
                vtUtils.hideValidationMessage(jQuery('#datafields').parent().find('.select2-choices'));
            }

            if(groupByFieldRows == null || groupByFieldRows == "") {
                vtUtils.showValidationMessage(jQuery('#groupbyfield_rows').parent().find('.select2-container'), app.vtranslate('JS_REQUIRED_FIELD'));
                return false;
            } else {
                vtUtils.hideValidationMessage(jQuery('#groupbyfield_rows').parent().find('.select2-container'));
            }

            if(groupByFieldColumn == null || groupByFieldColumn == "") {
                vtUtils.showValidationMessage(jQuery('#groupbyfield_columns').parent().find('.select2-container'), app.vtranslate('JS_REQUIRED_FIELD'));
                return false;
            } else {
                vtUtils.hideValidationMessage(jQuery('#groupbyfield_columns').parent().find('.select2-container'));
            }

            //get value field rename
            var renameDataValue = {};
            var renameValues = $('.rename-field-translate').find('[name="rename_field"]');
            for(var i=0; i < renameValues.length; i++) {
                var renameVal = $(renameValues[i]).val();
                var renameSelected = $(renameValues[i]).attr('data-selected');
                var renameFieldSlect = renameSelected.split(':');
                if(renameSelected == 'count(*)'){
                    var renameField = 'record_count';
                }else{
                    var renameField = renameFieldSlect[2]+'_'+renameFieldSlect[5];
                }
                var renameLabel = $(renameValues[i]).attr('data-fieldlabel');
                renameDataValue[i]={fieldname : renameField.toLowerCase(),fieldlabel : renameLabel,translatedLabel:renameVal,renameSelected:renameSelected };
            }
            //end get value field rename
            var groupByFieldRowsVal = jQuery('input[name="groupbyfield_rows"]').val();
            var groupByFieldColumnVal = jQuery('input[name="groupbyfield_columns"]').val();
            var sortBy = jQuery('input[name="sort_by"]').val();
            var limit = jQuery('input[name="sort_limit"]').val();
            var orderBy = jQuery('select[name="order_by"]').val();
            var dataFieldVal = jQuery('input[name="datafields"]').val();
            var dataFieldChartVal = jQuery('input[name="datafields-chart"]').val();
            var groupByFieldVal = jQuery('input[name="groupbyfield"]').val();
            var legendposition = jQuery('input[name="legendposition"]').val();
            var displaygrid = jQuery('input[name="displaygrid"]').val();
            var displaylabel = jQuery('input[name="displaylabel"]').val();
            var formatlargenumber = jQuery('#formatlargenumber').val();
            var legendvalue = jQuery('#legendvalue').val();
            var drawline = jQuery('#drawline').val();
            var renamedatavalue = JSON.stringify(renameDataValue);
            var postData = {
                'advanced_filter': advFilterCondition,
                'record': recordId,
                'view': "PivotSaveAjax",
                'module': app.getModuleName(),
                'mode': currentMode,
                'groupbyfield_rows': groupByFieldRowsVal,
                'groupbyfield_columns': groupByFieldColumnVal,
                'sort_by': sortBy,
                'order_by': orderBy,
                'limit': limit,
                'datafields': dataFieldVal,
                'charttype': jQuery('input[name=charttype]').val(),
                'groupbyfield': groupByFieldVal,
                'datafields-chart': dataFieldChartVal,
                'legendposition': legendposition,
                'displaygrid': displaygrid,
                'displaylabel': displaylabel,
                'formatlargenumber': formatlargenumber,
                'legendvalue': legendvalue,
                'drawline': drawline,
                'renamedatavalue':renamedatavalue,
            };
            app.helper.showProgress();
            e.preventDefault();
            app.request.post({data: postData}).then(
                function (error, data) {
                    app.helper.hideProgress();
                    jQuery('.reportActionButtons').addClass('hide');
                    jQuery('#reportContentsDiv').html(data);
                    var dataChart = thisInstance.registerEventForChartGeneration();
                    if(dataChart == true){
                        $('#chartcontent').css('width','50%');
                        var ctx = document.getElementById('chart-area').getContext('2d');
                        window.myChart = new Chart(ctx, config);
                    }
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

    registerEventForModifyPivotFields : function() {
        jQuery('[name=modify_pivot_fields]').on('click', function(e) {
            var icon =  jQuery(e.currentTarget).find('i');
            var isClassExist = jQuery(icon).hasClass('fa-chevron-right');
            if(isClassExist) {
                jQuery(e.currentTarget).find('i').removeClass('fa-chevron-right').addClass('fa-chevron-down');
                jQuery('#modify_pivot_fields').removeClass('hide').show('slow');
            } else {
                jQuery(e.currentTarget).find('i').removeClass('fa-chevron-down').addClass('fa-chevron-right');
                jQuery('#modify_pivot_fields').addClass('hide').hide('slow');
            }
            return false;
        });
    },
    registerSortableEvent : function() {
        var thisInstance = this;
        var div = jQuery('#modify_pivot_fields');
        var rows = div.find('#groupbyfield_rows').siblings('div').find('ul');
        var columns = div.find('#groupbyfield_columns').siblings('div').find('ul');
        var data = div.find('#datafields').siblings('div').find('ul');

        rows.sortable({
            'helper' : function(e,ui){
                ui.children().each(function(index,element){
                    element = jQuery(element);
                    element.width(element.width());
                });
                return ui;
            },
            'containment' : 'parent',
            'revert' : true,
            'ui-floating': 'auto',
            scroll: true,
            stop:function (e, ui) {
                jQuery('.reportActionButtons').removeClass('hide');
                thisInstance.registerPushValueSortableEvent($(e.target));
            },
            axis : 'x',
        });
        columns.sortable({
            'helper' : function(e,ui){
                ui.children().each(function(index,element){
                    element = jQuery(element);
                    element.width(element.width());
                });
                return ui;
            },
            'containment' : columns,
            'revert' : true,
            'ui-floating': 'auto',
            scroll: true,
            stop:function (e, ui) {
                jQuery('.reportActionButtons').removeClass('hide');
                thisInstance.registerPushValueSortableEvent($(e.target));
            },
            axis : 'x',
        });
        data.sortable({
            'helper' : function(e,ui){
                ui.children().each(function(index,element){
                    element = jQuery(element);
                    element.width(element.width());
                });
                return ui;
            },
            'containment' : data,
            'revert' : true,
            'ui-floating': 'auto',
            scroll: true,
            stop:function (e, ui) {
                jQuery('.reportActionButtons').removeClass('hide');
                thisInstance.registerPushValueSortableEvent($(e.target));
            },
            axis : 'x',
        });

    },
    registerPushValueSortableEvent:function (element) {
        var parentElement = element.parent();
        var select = parentElement.siblings('select');
        var valueOrder = Array();
        var dataFieldsSelected = select.select2('data');
        $.each(dataFieldsSelected,function (key,fieldObj) {
                valueOrder.push(fieldObj.id)
        });
        var inputName = select.attr('id');
        $('input[name="'+inputName+'"]').val(JSON.stringify(valueOrder));
    },
    savePinToDashBoard : function(element,customParams) {
        var recordId = this.getRecordId();
        var widgetTitle = 'PivotReportWidget_' + recordId;
        var tabName = element.data('tab-name');
        var params = {
            module: 'VReports',
            action: 'PivotActions',
            mode: 'pinToDashboard',
            reportid: recordId,
            title: widgetTitle
        };
        params = jQuery.extend(params, customParams);
        app.request.post({data: params}).then(function (error,data) {
            if (data.duplicate) {
                var params = {
                    message: app.vtranslate('JS_PIVOT_ALREADY_PINNED_TO_DASHBOARD', 'VReports')
                };
                app.helper.showSuccessNotification(params);
            } else {
                var message = app.vtranslate('JS_PIVOT_PINNED_TO_DASHBOARD', 'VReports') +" In Tab " + tabName;
                app.helper.showSuccessNotification({message:message});
                element.find('i').removeClass('vicon-pin');
                element.find('i').addClass('vicon-unpin');
                element.removeClass('dropdown-toggle').removeAttr('data-toggle');
                element.attr('title', app.vtranslate('JSLBL_UNPIN_PIVOT_FROM_DASHBOARD'));
            }
        });
    },
    unpinFromDashboard : function (element,customParams) {
        var thisInstance = this;
        var recordId = thisInstance.getRecordId();
        var tabName = element.data('tab-name');
        var params = {
            module: 'VReports',
            action: 'PivotActions',
            mode: 'unpinFromDashboard',
            reportid: recordId
        };
        params = jQuery.extend(params, customParams);
        app.request.post({data: params}).then(function (error,data) {
            if(data.unpinned) {
                var message = app.vtranslate('JS_PIVOT_REMOVED_FROM_DASHBOARD', 'VReports') +" In Tab " + tabName;
                app.helper.showSuccessNotification({message:message});
                element.find('i').removeClass('vicon-unpin');
                element.find('i').addClass('vicon-pin');
                if(element.data('dashboardTabCount') >1) {
                    element.addClass('dropdown-toggle').attr('data-toggle','dropdown');
                }
                element.attr('title', app.vtranslate('JSLBL_PIN_PIVOT_TO_DASHBOARD'));
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
    registerEventsDeleteChart: function () {
        var thisInstance = this;
        if($('[name="datachart"]').val()){
            var containerAction = $('.detailViewButtoncontainer:last');
            var button = "<button class='btn btn-default deleteChart'>"+app.vtranslate('JSLBL_CHART_DELETE')+"</button>"
            containerAction.find('.btn-group').append(button);
            $('.deleteChart').click(function (e) {
                $('[name="charttype"]').val('');
                jQuery('.generateReportPivot').trigger('click');
                $('.deleteChart').remove();
                var message = app.vtranslate('JSLBL_CHART_DELETE_SUCCESS', 'VReports');
                app.helper.showSuccessNotification({message:message});
            })
        }
    },
    registerEvents: function () {
        this._super();
        this.init();
        this.registerEventForChartGeneration();
        this.registerUpdateSelectElementEventForRows();
        this.registerUpdateSelectElementEventForColumns();
        this.registerUpdateSelectElementEventForData();
        this.registerEventForModifyPivotFields();
        VReports_PivotEdit3_Js.registerFieldForChosen();
        VReports_PivotEdit3_Js.initSelectValues();
        this.selectDataRow = jQuery("#groupbyfield_rows").val();
        this.selectDataField = jQuery("#datafields").val();
        this.registerSortableEvent();
        this.registerEventsDeleteChart();

    }
});

jQuery(document).ready(function () {
    jQuery('.generateReportPivot').trigger('click');
});
