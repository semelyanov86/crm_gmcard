/* ********************************************************************************
 * The content of this file is subject to the Custom Header/Bills ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
Vtiger.Class("VTEButtons_Edit_Js",{
    instance:false,
    advanceFilterInstance: false,
    getInstance: function(){
        var thisInstance = this;
        var moduleSelected = jQuery('[name="custom_module"]').val();
        if(moduleSelected){
            var table_conditions = jQuery('#table-conditions');
            var advanceFilterContainer = jQuery('.vte-advancefilter');
            vtUtils.applyFieldElementsView(table_conditions);
        }
        if(VTEButtons_Edit_Js.instance == false){
            var instance = new VTEButtons_Edit_Js();
            VTEButtons_Edit_Js.instance = instance;
            return instance;
        }
        return VTEButtons_Edit_Js.instance;

    }
},{
    registerEventSelectModule:function(){
        var self = this;
        $('#custom_module').on('change',function(){
            var element = $(this)
            var moduleSelected = element.val();
            var params = {
                module : 'VTEButtons',
                view : 'RelatedFields',
                record : $('#record').val(),
                moduleSelected : moduleSelected
            };
            AppConnector.request(params).then(
                function (data) {
                    //picklistField
                    jQuery('#advfilterlist').val('');
                    var picklistField = $('#field_name');
                    self.addValueForPickLists(picklistField,data);
                    self.registerEventForShowModuleFilterCondition(moduleSelected);
                    self.addAutoUpdateFields();

                },
                function(error){

                }
            );
        });
    },
    registerEventForShowModuleFilterCondition: function (moduleSelected) {
        var thisInstance = this;
        var params = {
            'module': 'VTEButtons',
            'parent': 'Settings',
            'view': 'ModuleChangeAjax',
            'record': jQuery("input[name='record']").val(),
            'module_name': moduleSelected
        }
        app.helper.showProgress();
        app.request.get({data: params}).then(function (error, data) {
            if(error === null) {
                app.helper.hideProgress();
                jQuery('#EditVTEButtons .vte-advancefilter').html(data);
                var container = jQuery('#EditVTEButtons .filterContainer');
                thisInstance.advanceFilterInstance = Vtiger_AdvanceFilter_Js.getInstance(container);
                // app.changeSelectElementView(container);
                vtUtils.applyFieldElementsView(container);
            }else{
                // to do
            }
        });
    },
    addValueForPickLists:function(picklistField,data){
        var selectchosen = picklistField.siblings('div').find('.select2-choices');
        selectchosen.find('.select2-search-choice').remove();
        picklistField.siblings('div').find('.select2-chosen').html('Select an Option');
        var result = data.result;
        picklistField.html(result);
    },

    registerEventSelectIcons : function () {
        var modal = $("#ModalIcons");
        modal.find('.cell-icon').on('click',function () {
            var group = ".cell-icon";
            $(group).css("background", "#FFFFFF");
            $(group).removeClass("iconChecked");
            $(this).css("background", "cyan");
            $(this).addClass("iconChecked");
        })
        //submit icon module
        modal.find(".btn-submit").on('click', function () {
            var spanIcon  = modal.find('.iconChecked').find('span');
            var dataInfo = spanIcon.data('info');
            var classspanIcon = spanIcon.attr('class');
            var spanSelected = $('.icon-section').find('#icon-module');
            spanSelected.removeClass();
            spanSelected.addClass(classspanIcon);
            var class_icon = classspanIcon.replace('icon-module','');
            $('input[name="icon"]').val(class_icon.trim());
            $('.header-preview-section').find('span#icon-span').removeClass();
            $('.header-preview-section').find('span#icon-span').toggleClass(class_icon.trim());
            modal.modal('toggle');
        })
    },
    registerEventSetPickColor : function () {
        $('#header-colorpicker').ColorPicker({
            color: '#0000ff',
            onShow: function (colpkr) {
                $(colpkr).fadeIn(500);
                return false;
            },
            onHide: function (colpkr) {
                $(colpkr).fadeOut(500);
                return false;
            },
            onChange: function (hsb, hex, rgb) {
                //$('#header-colorpicker').find('div').css('background-color','#'+hex);
                $('#header-colorpicker p').css('backgroundColor', '#' + hex);
                $('.header-preview-section').find('span#icon-span').css('color', '#' + hex);
                //border: 2px solid #{$RECORDENTRIES['color']
                $('.header-preview-section').find('div.rcorners').css({'border':'2px solid #' + hex,'color':'#'+hex});
                $('#color').val(hex);
            }
        });
    },
    registerHeaderChange : function () {
        $('#header').on("change",function(){
            $('span.l-header').text($(this).val());
        });
    },
    /**
     * Function which will get the selected columns with order preserved
     * @return : array of selected values in order
     */
    getSelectedColumns : function() {
        var columnListSelectElement = jQuery('#field_name');
        var select2Element = app.getSelect2ElementFromSelect(columnListSelectElement);

        var selectedValuesByOrder = new Array();
        var selectedOptions = columnListSelectElement.find('option:selected');

        var orderedSelect2Options = select2Element.find('li.select2-search-choice').find('div');
        orderedSelect2Options.each(function(index,element){
            var chosenOption = jQuery(element);
            selectedOptions.each(function(optionIndex, domOption){
                var option = jQuery(domOption);
                if(option.html() == chosenOption.html()) {
                    selectedValuesByOrder.push(option.data('field-name'));
                    return false;
                }
            });
        });
        return selectedValuesByOrder;
    },
    calculateValues : function(){
        var thisInstance = this;
        var advfilterlist = this.advanceFilterInstance.getValues();
        jQuery('#advfilterlist').val(JSON.stringify(advfilterlist));
        var fields=thisInstance.getSelectedColumns();
        if (fields.length == 0) {
            return false;
        }
        m = jQuery('[name="icon"]');
        var k=m.val();
        if(k.trim()==""){
            app.helper.showErrorNotification({message: app.vtranslate('Icon does not exist')});
            return false;
        }
        jQuery('#strfieldslist').val(fields);
        return true;
    },
    registerFilterContainerEvent : function() {
        var thisInstance = this;
        thisInstance.advanceFilterInstance = Vtiger_AdvanceFilter_Js.getInstance(jQuery('.filterContainer'));
    },
    makeColumnListSortable : function() {
        var select2Element = jQuery('#s2id_field_name');
        //TODO : peform the selection operation in context this might break if you have multi select element in advance filter
        //The sorting is only available when Select2 is attached to a hidden input field.
        var chozenChoiceElement = select2Element.find('ul.select2-choices');
        chozenChoiceElement.sortable({
            'containment': chozenChoiceElement,
            start: function() { },
            update: function() {}
        });
    },
    addAutoUpdateFields:function(){
        var source_module = $('#custom_module').val();
        var params = {
            'module': 'VTEButtons',
            'action': 'ActionAjax',
            'mode': 'getPicklists',
            'source_module': source_module
        }
        app.request.get({data: params}).then(function (error, data) {
            if(error === null) {
                var result = data;
                var options = '<option value="">Select an option..</option>';
                result.forEach(function(item){
                    options = options + '<option value="'+item.value+'">'+item.display+'</option>';
                });
                $('#automated_update_picklist_field').html(options);
            }else{
                // to do
            }
        });
    },
    registerButtonSaveEvent:function(){
        var form = $('#EditVTEButtons');
        var self = this;
        $('.buttonSave').on('click',function(){
            form.on('submit',function (e) {
                var result = self.calculateValues();
            });
            var field_on_popup = $('#field_name').val();
            var silent_field_update = $('#automated_update_picklist_field').val();
            var custom_module = $('#custom_module').val();
            var header = $('#header').val();
            var icon = $('#icon').val();
            if(((field_on_popup == '' || field_on_popup == null || field_on_popup == undefined || !field_on_popup)  && (silent_field_update == '' || silent_field_update == null || silent_field_update == undefined || !silent_field_update))){
                app.helper.showAlertNotification({message:'You must select at least ONE field in "Fields on Popup" OR "Silent Field Update". You are NOT required to select both, only one of the two options is required. You can select both too.'});
            }else if(custom_module == '' || custom_module == null || custom_module == undefined){
                app.helper.showAlertNotification({message:'You must select module'});
            }else if(header == '' || header == null || header == undefined){
                app.helper.showAlertNotification({message:'Title dose not exist'});
            }else if(icon == '' || icon == null || icon == undefined){
                app.helper.showAlertNotification({message:'Icon dose not exist'});
            }else{
                form.submit();
            }
        });
    },
    registerPicklistFieldChangeEvent:function(){
        $('#automated_update_picklist_field').change(function(){
            var val = $(this).val();
            var source_module = $('#custom_module').val();
            var params = {
                'module': 'VTEButtons',
                'action': 'ActionAjax',
                'mode': 'getPicklistValues',
                'field': val,
                'source_module': source_module
            }
            app.request.get({data: params}).then(function (error, data) {
                if(error === null) {
                    var result = data;
                    var options = '<option value="">Select an option..</option>';
                    result.forEach(function(item){
                        options = options + '<option value="'+item.value+'">'+item.display+'</option>';
                    });
                    $('#automated_update_picklist_value').html(options);
                }else{
                    // to do
                }
            });
        });
    },
    memberSelectElement : false,
    getMemberSelectElement : function () {
        if(this.memberSelectElement == false) {
            this.memberSelectElement = jQuery('#memberList');
        }
        return this.memberSelectElement;
    },
    registerEventForSelect2Element : function(){
        var editViewForm = $("#EditVTEButtons");
        var selectElement = this.getMemberSelectElement();
        selectElement.addClass('select2');
        var params = {};
        params.formatSelection = function(object,container){
            var selectedId = object.id;
            var selectedOptionTag = editViewForm.find('option[value="'+selectedId+'"]');
            var selectedMemberType = selectedOptionTag.data('memberType');
            container.prevObject.addClass(selectedMemberType);
            var element = '<div>'+selectedOptionTag.text()+'</div>';
            return element;
        };
        selectElement.select2('destroy');
        this.changeSelectElementView(selectElement, 'select2',params);
    },
    changeSelectElementView : function(parent, view, viewParams){
        if(typeof parent == 'undefined') {
            parent = jQuery('body');
        }

        //If view is select2, This will convert the ui of select boxes to select2 elements.
        if(view == 'select2') {
            vtUtils.showSelect2ElementView(parent, viewParams);
            return;
        }
    },
    registerEvents: function(){
        var form = $('#EditVTEButtons');
        this.registerEventSelectModule();
        this.registerEventSelectIcons();
        this.registerEventSetPickColor();
        this.registerHeaderChange();
        this.makeColumnListSortable();
        this.registerPicklistFieldChangeEvent();
        this.registerButtonSaveEvent();
        this.registerFilterContainerEvent();
        this.registerEventForSelect2Element();
    }
});
jQuery(document).ready(function() {
    var instance = new VTEButtons_Edit_Js();
    instance.registerEvents();
    $('[data-toggle="popover"]').popover({html:true})
});