/* ********************************************************************************
 * The content of this file is subject to the Kanban View("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

jQuery.Class("KanbanView_Index_Js",{},{
    quickCreateCallBacks: [],
    currentContainerElement: null,
    fieldUpdatedEvent : 'Vtiger.Field.Updated',
    fieldPreSave : 'Vtiger.Field.PreSave',
    registerHoverEditEvent: function(container) {

        var thisInstance = this;
        container.on('click','div.kbValueContainer', function(e) {
            var currentContainerElement = jQuery(e.currentTarget).closest('div.fieldValue');
            thisInstance.ajaxEditHandling(currentContainerElement);
            var fieldName=currentContainerElement.data('field-name');
            thisInstance.oldValue = currentContainerElement.find('[name="'+fieldName+'"]').val();

            var saveHandle = function(e){
                var element = jQuery(e.target);
                if(currentContainerElement == null){
                    return;
                }
                if(element.closest('.kbTaskSection1').is(currentContainerElement)){
                    return;
                }
                var tmpCurrentElement = currentContainerElement;
                currentContainerElement = null;
                var editElement = tmpCurrentElement.find('.edit');
                var detailViewValue = tmpCurrentElement.find('.value');
                var recordId=tmpCurrentElement.data('record-id');
                var fieldName=tmpCurrentElement.data('field-name');
                var fieldType=tmpCurrentElement.data('uitype');
                var fieldElement = jQuery('[name="'+ fieldName +'"]', editElement);

                if(fieldElement.attr('disabled') == 'disabled'){
                    return;
                }

                var fldValue=fieldElement.val();
                var fieldnameElement = jQuery('.fieldname', editElement);
                var fieldInfo = Vtiger_Field_Js.getInstance(fieldElement.data('fieldinfo'));
                var previousValue = fieldnameElement.data('prevValue');

                if(fieldElement.is('input:checkbox')) {
                    if(fieldElement.is(':checked')) {
                        fldValue = '1';
                    } else {
                        fldValue = '0';
                    }
                    fieldElement = fieldElement.filter('[type="checkbox"]');
                }
                var errorExists = fieldElement.validationEngine('validate');
                if(errorExists) {
                    return;
                }
                fieldElement.validationEngine('hide');
                if(previousValue == fldValue ){
                    editElement.addClass('hide');
                    detailViewValue.css("display","block");
                    return;
                }

                if(fieldType == '33'){
                    var rawFieldName = fieldName.replace('[]','');
                }else{
                    var rawFieldName = fieldName;
                }
                var sourceModule = jQuery('#kbSourceModule').val();
                params = {
                    'module':sourceModule,
                    'action':'SaveAjax',
                    'record': recordId,
                    'value':fldValue,
                    'field':rawFieldName
                }
                editElement.addClass('hide');
                tmpCurrentElement.find('.kbValueContainer').progressIndicator();
                AppConnector.request(params).then(
                    function(data){
                        var result = data.result;
                        $.each( result, function( key, field ) {
                            if(key == rawFieldName){
                                detailViewValue.html(field.display_value);
                                editElement.val(field.value);
                            }
                        });
                        detailViewValue.css("display","block");
                        tmpCurrentElement.progressIndicator({'mode':'hide'});
                        fieldElement.trigger(thisInstance.fieldUpdatedEvent,{'old':previousValue,'new':fldValue});
                        fieldnameElement.data('prevValue', fldValue);
                        fieldElement.data('selectedValue', fldValue);
                        tmpCurrentElement = null;
                    }
                );
            };
            jQuery(document).on('click','*', saveHandle);
        });
    },
    ajaxEditHandling: function(currentTdElement) {
        var thisInstance = this;
        var detailViewValue = jQuery('.value',currentTdElement);
        var editElement = jQuery('.edit',currentTdElement);
        var fieldnameElement = jQuery('.nameField', editElement);
        var fieldName = fieldnameElement.val();
        var fieldElement = jQuery('[name="'+ fieldName +'"]', editElement);

        if(editElement.length == 0) {
            return;
        }

        detailViewValue.css("display","none");
        editElement.find('div.chzn-container').css('width','100%');
        editElement.find('div.chzn-container div.chzn-drop').css('width','99%');
        editElement.find('div.chzn-container div.chzn-drop .chzn-search input').css('padding-right','0');
        editElement.find('div.chzn-container div.chzn-drop .chzn-search input').css('padding-left','0');
        editElement.removeClass('hide').show().children().filter('input[type!="hidden"]input[type!="image"],select').filter(':first').focus();
    },
    registerQuickEditEvent:function(){
        var thisIntance = this;
        jQuery('.kbQuickEdit').on('click',function(e, params){
            if (typeof params == 'undefined') {
                params = {};
            }
            if (typeof params.callbackFunction == 'undefined') {
                params.callbackFunction = function() {
                };
            }
            var requestParams = jQuery(this).data('url');
            AppConnector.request(requestParams).then(
                function(data){
                    app.showModalWindow(data,function(data){
                        var quickEditForm = data.find('form[name="frmQuickEdit"]');
                        var moduleName = quickEditForm.find('[name="module"]').val();
                        var editViewInstance = Vtiger_Edit_Js.getInstanceByModuleName(moduleName);
                        editViewInstance.registerBasicEvents(quickEditForm);
                        quickEditForm.validationEngine(app.validationEngineOptions);
                        if (typeof params.callbackPostShown != "undefined") {
                            params.callbackPostShown(quickEditForm);
                        }
                        thisIntance.registerQuickCreatePostLoadEvents(quickEditForm, params);
                        app.registerEventForDatePickerFields(quickEditForm);
                        var quickCreateContent = quickEditForm.find('.quickCreateContent');
                        var quickCreateContentHeight = quickCreateContent.height();
                        var contentHeight = parseInt(quickCreateContentHeight);
                        if (contentHeight > 300) {
                            app.showScrollBar(jQuery('.quickCreateContent'), {
                                'height': '300px'
                            });
                        }
                    });
                }
            );
        });
    },
    registerQuickCreatePostLoadEvents: function(form, params) {
        var thisInstance = this;
        var submitSuccessCallbackFunction = params.callbackFunction;
        var goToFullFormCallBack = params.goToFullFormcallback;
        if (typeof submitSuccessCallbackFunction == 'undefined') {
            submitSuccessCallbackFunction = function() {
            };
        }

        form.on('submit', function(e) {
            var form = jQuery(e.currentTarget);
            var module = form.find('[name="module"]').val();
            //Form should submit only once for multiple clicks also
            if (typeof form.data('submit') != "undefined") {
                return false;
            } else {
                var invalidFields = form.data('jqv').InvalidFields;

                if (invalidFields.length > 0) {
                    //If validation fails, form should submit again
                    form.removeData('submit');
                    form.closest('#globalmodal').find('.modal-header h3').progressIndicator({
                        'mode': 'hide'
                    });
                    e.preventDefault();
                    return;
                } else {
                    //Once the form is submiting add data attribute to that form element
                    form.data('submit', 'true');
                    form.closest('#globalmodal').find('.modal-header h3').progressIndicator({
                        smallLoadingImage: true,
                        imageContainerCss: {
                            display: 'inline',
                            'margin-left': '18%',
                            position: 'absolute'
                        }
                    });
                }

                var recordPreSaveEvent = jQuery.Event(Vtiger_Edit_Js.recordPreSave);
                form.trigger(recordPreSaveEvent, {
                    'value': 'edit',
                    'module': module
                });
                if (!(recordPreSaveEvent.isDefaultPrevented())) {
                    var targetInstance = thisInstance;
                    var moduleInstance = Vtiger_Edit_Js.getInstanceByModuleName(module);
                    if(typeof(moduleInstance.quickCreateSave) === 'function'){
                        targetInstance = moduleInstance;
                    }

                    targetInstance.quickCreateSave(form).then(
                        function(data) {
                            location.reload();
                            app.hideModalWindow();
                            //fix for Refresh list view after Quick create
                        },
                        function(error, err) {
                        }
                    );
                } else {
                    //If validation fails in recordPreSaveEvent, form should submit again
                    form.removeData('submit');
                    form.closest('#globalmodal').find('.modal-header h3').progressIndicator({
                        'mode': 'hide'
                    });
                }
                e.preventDefault();
            }
        });

        form.find('#goToFullForm').on('click', function(e) {
            var form = jQuery(e.currentTarget).closest('form');
            var editViewUrl = jQuery(e.currentTarget).data('editViewUrl');
            if (typeof goToFullFormCallBack != "undefined") {
                goToFullFormCallBack(form);
            }
            thisInstance.quickCreateGoToFullForm(form, editViewUrl);
        });

        this.registerTabEventsInQuickCreate(form);
    },
    /**
     * Function to save the quickcreate module
     * @param accepts form element as parameter
     * @return returns deferred promise
     */
    quickCreateSave: function(form) {
        var aDeferred = jQuery.Deferred();
        var quickCreateSaveUrl = form.serializeFormData();
        AppConnector.request(quickCreateSaveUrl).then(
            function(data) {
                //TODO: App Message should be shown
                aDeferred.resolve(data);
            },
            function(textStatus, errorThrown) {
                aDeferred.reject(textStatus, errorThrown);
            }
        );
        return aDeferred.promise();
    },
    quickCreateGoToFullForm: function(form, editViewUrl) {
        var formData = form.serializeFormData();
        //As formData contains information about both view and action removed action and directed to view
        delete formData.module;
        delete formData.action;
        var formDataUrl = jQuery.param(formData);
        var completeUrl = editViewUrl + "&" + formDataUrl;
        window.location.href = completeUrl;
    },
    registerTabEventsInQuickCreate: function(form) {
        var tabElements = form.find('.nav.nav-pills , .nav.nav-tabs').find('a');

        //This will remove the name attributes and assign it to data-element-name . We are doing this to avoid
        //Multiple element to send as in calendar
        var quickCreateTabOnHide = function(tabElement) {
            var container = jQuery(tabElement.attr('data-target'));

            container.find('[name]').each(function(index, element) {
                element = jQuery(element);
                element.attr('data-element-name', element.attr('name')).removeAttr('name');
            });
        }

        //This will add the name attributes and get value from data-element-name . We are doing this to avoid
        //Multiple element to send as in calendar
        var quickCreateTabOnShow = function(tabElement) {
            var container = jQuery(tabElement.attr('data-target'));

            container.find('[data-element-name]').each(function(index, element) {
                element = jQuery(element);
                element.attr('name', element.attr('data-element-name')).removeAttr('data-element-name');
            });
        }

        tabElements.on('shown', function(e) {
            var previousTab = jQuery(e.relatedTarget);
            var currentTab = jQuery(e.currentTarget);

            quickCreateTabOnHide(previousTab);
            quickCreateTabOnShow(currentTab);

            //while switching tabs we have to clear the invalid fields list
            form.data('jqv').InvalidFields = [];

        });

        //To show aleady non active element , this we are doing so that on load we can remove name attributes for other fields
        quickCreateTabOnHide(tabElements.closest('li').filter(':not(.active)').find('a'));
    },
    changeCustomFilterElementView : function() {
        var filterSelectElement = this.getFilterSelectElement();
        if(filterSelectElement.length > 0 && filterSelectElement.is("select")) {
            app.showSelect2ElementView(filterSelectElement,{
                formatSelection : function(data, contianer){
                    var resultContainer = jQuery('<span></span>');
                    resultContainer.append(jQuery(jQuery('.filterImage').clone().get(0)).show());
                    resultContainer.append(data.text);
                    return resultContainer;
                },
                customSortOptGroup : true
            });

            var select2Instance = filterSelectElement.data('select2');
            jQuery('span.filterActionsDiv').appendTo(select2Instance.dropdown).removeClass('hide');
        }
    },

    //contains the List View element.
    listViewContainer : false,

    //Contains list view top menu element
    listViewTopMenuContainer : false,

    //Contains list view content element
    listViewContentContainer : false,

    //Contains filter Block Element
    filterBlock : false,

    filterSelectElement : false,

    getFilterSelectElement : function() {

        if(this.filterSelectElement == false) {
            this.filterSelectElement = jQuery('#customFilter');
        }
        return this.filterSelectElement;
    },
    /*
     * Function to register the event for changing the custom Filter
     */
    registerChangeCustomFilterEvent : function(){
        var thisInstance = this;
        var filterSelectElement = this.getFilterSelectElement();
        filterSelectElement.change(function(e){
            var cvId = thisInstance.getCurrentCvId();
            url = 'index.php?module=KanbanView&view=Index';

            url +='&viewname='+cvId;
            url +='&source_module='+jQuery('#kbParentModule').val();
            url +='&isTicketFilter=1';
            window.location.href = url;
        });
    },
    getCurrentCvId : function(){
        return jQuery('#customFilter').find('option:selected').data('id');
    },
    /*
     * function to register the click event event for create filter
     */
    registerCreateFilterClickEvent : function(){
        var thisInstance = this;
        jQuery('#createFilter').on('click',function(event){
            //to close the dropdown
            thisInstance.getFilterSelectElement().data('select2').close();
            var currentElement = jQuery(event.currentTarget);
            var createUrl = currentElement.data('createurl');
            Vtiger_CustomView_Js.loadFilterView(createUrl);
        });
    },
    /*
     * Function to register the click event for edit filter
     */
    registerEditFilterClickEvent : function(){
        var thisInstance = this;
        var listViewFilterBlock = this.getFilterBlock();
        if(listViewFilterBlock != false){
            listViewFilterBlock.on('mouseup','li i.editFilter',function(event){
                //to close the dropdown
                thisInstance.getFilterSelectElement().data('select2').close();
                var liElement = jQuery(event.currentTarget).closest('.select2-result-selectable');
                var currentOptionElement = thisInstance.getSelectOptionFromChosenOption(liElement);
                var editUrl = currentOptionElement.data('editurl');
                Vtiger_CustomView_Js.loadFilterView(editUrl);
                event.stopPropagation();
            });
        }
    },

    getSelectOptionFromChosenOption : function(liElement){
        var classNames = liElement.attr("class");
        var classNamesArr = classNames.split(" ");
        var currentOptionId = '';
        jQuery.each(classNamesArr,function(index,element){
            if(element.match("^filterOptionId")){
                currentOptionId = element;
                return false;
            }
        });
        return jQuery('#'+currentOptionId);
    },

    /*
     * Function to register the click event for delete filter
     */
    registerDeleteFilterClickEvent: function(){
        var thisInstance = this;
        var listViewFilterBlock = this.getFilterBlock();
        if(listViewFilterBlock != false){
            //used mouseup event to stop the propagation of customfilter select change event.
            listViewFilterBlock.on('mouseup','li i.deleteFilter',function(event){
                //to close the dropdown
                thisInstance.getFilterSelectElement().data('select2').close();
                var liElement = jQuery(event.currentTarget).closest('.select2-result-selectable');
                var message = app.vtranslate('JS_LBL_ARE_YOU_SURE_YOU_WANT_TO_DELETE');
                Vtiger_Helper_Js.showConfirmationBox({'message' : message}).then(
                    function(e) {
                        var currentOptionElement = thisInstance.getSelectOptionFromChosenOption(liElement);
                        var deleteUrl = currentOptionElement.data('deleteurl');
                        var newEle = '<form action='+deleteUrl+' method="POST">'+
                            '<input type = "hidden" name ="'+csrfMagicName+'"  value=\''+csrfMagicToken+'\'>'+
                            '</form>';
                        var formElement = jQuery(newEle);
                        formElement.appendTo('body').submit();
                    },
                    function(error, err){
                    }
                );
                event.stopPropagation();
            });
        }
    },
    getFilterBlock : function(){
        if(this.filterBlock == false){
            var filterSelectElement = this.getFilterSelectElement();
            if(filterSelectElement.length <= 0) {
                this.filterBlock = jQuery();
            }else if(filterSelectElement.is('select')){
                this.filterBlock = filterSelectElement.data('select2').dropdown;
            }
        }
        return this.filterBlock;
    },
    /*
     * Function to register the click event for approve filter
     */
    registerApproveFilterClickEvent: function(){
        var thisInstance = this;
        var listViewFilterBlock = this.getFilterBlock();

        if(listViewFilterBlock != false){
            listViewFilterBlock.on('mouseup','li i.approveFilter',function(event){
                //to close the dropdown
                thisInstance.getFilterSelectElement().data('select2').close();
                var liElement = jQuery(event.currentTarget).closest('.select2-result-selectable');
                var currentOptionElement = thisInstance.getSelectOptionFromChosenOption(liElement);
                var approveUrl = currentOptionElement.data('approveurl');
                var newEle = '<form action='+approveUrl+' method="POST">'+
                    '<input type = "hidden" name ="'+csrfMagicName+'"  value=\''+csrfMagicToken+'\'>'+
                    '</form>';
                var formElement = jQuery(newEle);

                formElement.appendTo('body').submit();
                event.stopPropagation();
            });
        }
    },
    /*
     * Function to register the click event for deny filter
     */
    registerDenyFilterClickEvent: function(){
        var thisInstance = this;
        var listViewFilterBlock = this.getFilterBlock();

        if(listViewFilterBlock != false){
            listViewFilterBlock.on('mouseup','li i.denyFilter',function(event){
                //to close the dropdown
                thisInstance.getFilterSelectElement().data('select2').close();
                var liElement = jQuery(event.currentTarget).closest('.select2-result-selectable');
                var currentOptionElement = thisInstance.getSelectOptionFromChosenOption(liElement);
                var denyUrl = currentOptionElement.data('denyurl');
                var newEle = '<form action='+denyUrl+' method="POST">'+
                    '<input type = "hidden" name ="'+csrfMagicName+'"  value=\''+csrfMagicToken+'\'>'+
                    '</form>';
                var formElement = jQuery(newEle);

                formElement.appendTo('body').submit();
                event.stopPropagation();
            });
        }
    },

    /*
     * Function to register the hover event for customview filter options
     */
    registerCustomFilterOptionsHoverEvent : function(){
        var thisInstance = this;
        var listViewTopMenuDiv = this.getListViewTopMenuContainer();
        var filterBlock = this.getFilterBlock()
        if(filterBlock != false){
            filterBlock.on('hover','li.select2-result-selectable',function(event){
                var liElement = jQuery(event.currentTarget);
                var liFilterImages = liElement.find('.filterActionImgs');
                if (liElement.hasClass('group-result')){
                    return;
                }

                if( event.type === 'mouseenter' ) {
                    if(liFilterImages.length > 0){
                        liFilterImages.show();
                    }else{
                        thisInstance.performFilterImageActions(liElement);
                    }

                } else {
                    liFilterImages.hide();
                }
            });
        }
    },
    performFilterImageActions : function(liElement) {
        jQuery('.filterActionImages').clone(true,true).removeClass('filterActionImages').addClass('filterActionImgs').appendTo(liElement.find('.select2-result-label')).show();
        var currentOptionElement = this.getSelectOptionFromChosenOption(liElement);
        var deletable = currentOptionElement.data('deletable');
        if(deletable != '1'){
            liElement.find('.deleteFilter').remove();
        }
        var editable = currentOptionElement.data('editable');
        if(editable != '1'){
            liElement.find('.editFilter').remove();
        }
        var pending = currentOptionElement.data('pending');
        if(pending != '1'){
            liElement.find('.approveFilter').remove();
        }
        var approve = currentOptionElement.data('public');
        if(approve != '1'){
            liElement.find('.denyFilter').remove();
        }
    },
    getListViewTopMenuContainer : function(){
        if(this.listViewTopMenuContainer == false){
            this.listViewTopMenuContainer = jQuery('.listViewTopMenuDiv');
        }
        return this.listViewTopMenuContainer;
    },
    //This will show the notification message using pnotify
    showNotify : function(txtMessage) {
        var params = {
            title : app.vtranslate('JS_MESSAGE'),
            text: txtMessage,
            animation: 'show',
            type: 'info'
        };
        Vtiger_Helper_Js.showPnotify(params);
    },
    registerSortableEvent:function(){
        var thisInstance = this;
        var mesParams={};
        jQuery('.kbBoxContent').sortable({
            connectWith: ".kbBoxContent",
            handle: ".kbTaskHeader",
            cursor: "move",
            start: function(e,ui){
                var item = ui.item;
                mesParams.itemName = item.find('.kbTaskTitle a').text();
                mesParams.from = item.closest('.kanbanBox').find('.kbBoxTitle').text();
            },
            stop: function(event,ui){
                var item = ui.item;
                var primaryFieldName = jQuery('#primaryFieldName').val();
                var primaryFieldId = jQuery('#primaryFieldId').val();
                var recordId = item.find('input[name="recordId"]').val();

                var nextRecordId= item.next('.kbBoxTask').find('input[name="recordId"]').val();
                if(typeof nextRecordId == "undefined"){
                    nextRecordId = -1;
                }

                var prevRecordId = item.prev('.kbBoxTask').find('input[name="recordId"]').val();
                if(typeof prevRecordId == "undefined"){
                    prevRecordId = -1;
                }

                var primaryValue = item.closest('.kanbanBox').find('input[name="primaryValue"]').val;
                mesParams.to = item.closest('.kanbanBox').find('.kbBoxTitle').text();
                jQuery('.kanbanBox').each(function(){
                    var container = jQuery(this);
                    jQuery(this).find('input[name="recordId"]').each(function () {
                        if(jQuery(this).val() == recordId){
                            primaryValue = container.find('input[name="primaryValue"]').val();
                        }

                    });
                });
                var params={
                    'primaryFieldName':primaryFieldName,
                    'primaryFieldId':primaryFieldId,
                    'recordId':recordId,
                    'nextRecordId':nextRecordId,
                    'prevRecordId':prevRecordId,
                    'primaryValue':primaryValue,
                    'module':'KanbanView',
                    'action':'ActionAjax',
                    'mode':'updatePrimaryFieldValue',
                    'source_module':jQuery('#kbSourceModule').val()
                }
                AppConnector.request(params).then(
                    function(data){
                        if(mesParams.from == mesParams.to ){
                            return;
                        }
                        var txtMessage = mesParams.itemName + " updated from "+ mesParams.from +" to "+ mesParams.to;
                        thisInstance.showNotify(txtMessage);
                    }
                );
            }
        }).disableSelection();
    },
    registerEvents : function() {

        var thisInstance = this;
        var kanbanBox = jQuery('#detailView .kbParentContainer .kanbanBox');
        var containerWidth = jQuery('#detailView .kbParentContainer').width();
        kanbanBox.width((containerWidth - 80)/4);
        var kbContainer = jQuery('#detailView .kbParentContainer .kbContainer').width(kanbanBox.length *(kanbanBox.width()+15));
        var detailContentsHolder = jQuery('div.kbContainer');
        app.registerEventForDatePickerFields(detailContentsHolder);
        app.registerEventForTimeFields(detailContentsHolder);

 		jQuery('#detailView').validationEngine(app.validationEngineOptions);
        var boxRecord = jQuery('div.kbBoxTask');
        thisInstance.registerHoverEditEvent(jQuery('div.fieldValue'));
        thisInstance.registerQuickEditEvent();

        thisInstance.changeCustomFilterElementView();
        thisInstance.registerChangeCustomFilterEvent();
        thisInstance.registerCreateFilterClickEvent();
        thisInstance.registerEditFilterClickEvent();
        thisInstance.registerDeleteFilterClickEvent();
        thisInstance.registerApproveFilterClickEvent();
        thisInstance.registerDenyFilterClickEvent();
        thisInstance.registerCustomFilterOptionsHoverEvent();
        thisInstance.registerSortableEvent();

    }
});