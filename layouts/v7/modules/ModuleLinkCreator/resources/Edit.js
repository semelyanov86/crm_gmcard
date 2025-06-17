/* ********************************************************************************
 * The content of this file is subject to the Module & Link Creator ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

/**
 * @link layouts/vlayout/modules/Accounts/resources/Edit.js
 *
 * @class ModuleLinkCreator_Edit_Js
 */
Vtiger_Edit_Js("ModuleLinkCreator_Edit_Js", {}, {
    //Stored history of account name and duplicate check result
    duplicateCheckCache: {},
    //This will store the editview form
    editViewForm: false,

    /**
     * This function will return the current form
     */
    getForm: function () {
        if (this.editViewForm == false) {
            this.editViewForm = jQuery('#EditView');
        }
        return this.editViewForm;
    },

    /**
     * This function will return the current RecordId
     */
    getRecordId: function (container) {
        return jQuery('input[name="record"]', container).val();
    },

    /**
     * This function will register before saving any record
     */
    registerAutoGenerateModuleName: function (form) {
        var thisInstance = this;

        if (typeof form == 'undefined') {
            form = this.getForm();
        }

        var moduleName = app.getModuleName();
        var objModuleLabel = form.find('#' + moduleName + '_editView_fieldName_module_label');
        var objSingularModuleLabel = form.find('#' + moduleName + '_editView_singular_module_label');
        var objModuleName = form.find('#' + moduleName + '_editView_fieldName_module_name');

        objModuleLabel.change(function () {
            var focus = $(this);

            var val = focus.val();
            objSingularModuleLabel.val(val);
            val = ModuleLinkCreatorUtils.replaceAllNonAlphaNumericCharacters(val, '');
            objModuleName.val(val);

            // Validate module
            thisInstance.checkModule(form);
        });

        // Disable before check module
        var btnSubmit = form.find('[type="submit"]');
        objModuleLabel.keydown(function () {
            // Disable by default
            btnSubmit.attr('disabled', 'disabled');
        });
        objModuleLabel.focusout(function () {
            // Validate module
            thisInstance.checkModule(form);
        });

        // Disable before check module
        objModuleLabel.keyup(function () {
            var focus = $(this);
            var val = focus.val();
            var firstchar = val.charAt(0);
            if(isNaN(firstchar)== false)
            {
                objModuleLabel.val('');
                objSingularModuleLabel.val('');
                objModuleName.val('');
            }
            else {
                objSingularModuleLabel.val(val);
                val = ModuleLinkCreatorUtils.replaceAllNonAlphaNumericCharacters(val, '');
                objModuleName.val(val);
            }
        });
    },

    /**
     * @param form
     */
    checkModule: function (form) {
        // var moduleName = app.getModuleName();

        var btnSubmit = form.find('[type="submit"]');
        var inModuleName = form.find('[name="module_name"]');
        var valModuleName = inModuleName.val().trim();
        console.log(valModuleName.length)
        if (valModuleName == '') {
            btnSubmit.attr('disabled', 'disabled');
            return;
        }
        if (valModuleName.length >=25){
            app.helper.showErrorNotification({message:app.vtranslate('JS_MODULE_NAME_CANNOT_EXCEED_25_CHARACTERS')})
            btnSubmit.attr('disabled', 'disabled');
            return;
        }
        else if (valModuleName.indexOf('_') >= 0){
            var message = app.vtranslate('JS_MODULE_NAME_CANNOT_CONTAIN_UNDERLINE');
            Vtiger_Helper_Js.showMessage({
                text: message
            });
            btnSubmit.attr('disabled', 'disabled');
            return;
        }

        var params = {
            'module': app.getModuleName(),
            'action': 'ActionAjax',
            'mode': 'checkModule',
            'source_module': valModuleName
        };
        var ajaxParams = {
            method: 'GET',
            url: 'index.php',
            data: params,
            success: function (response) {
                if (response.success == false && response.error.code == 0){
                    btnSubmit.removeAttr('disabled');
                    app.helper.showSuccessNotification({message:response.error.message})
                }else if(response.success == true){
                    btnSubmit.attr('disabled','disabled');
                    app.helper.showErrorNotification({message:response.result.message})
                }
            },
            error: function (xhr, ajaxOptions, err) {
                console.log(err);
            }
        };
        jQuery.ajax(ajaxParams);
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
            var spanSelected = $('.logo-module').find('#icon-module');
            spanSelected.removeClass();
            spanSelected.addClass(classspanIcon);
            $('input[name="data-icon-module"]').val(dataInfo);
            modal.modal('toggle');
        })
    },
    registerEventMenuPlacement:function(){
        vtUtils.showSelect2ElementView($("#Menu_Placement"));
        vtUtils.showSelect2ElementView($("#base_permissions"));
    },

    /**
     * Function which will register basic events which will be used in quick create as well
     *
     */
    registerBasicEvents: function (container) {
        this._super(container);
        this.registerAutoGenerateModuleName(container);
        this.registerEventSelectIcons();
        this.registerEventMenuPlacement();
        $('.hover-tooltip').tooltip();
        var form = this.getForm();
        form.on('submit',function (e) {
            // Check Base permission
            var fieldElement = jQuery('#base_permissions');
            var select2Element = fieldElement.parent().find('.select2-container');
            var selectedValue = fieldElement.find('option:selected').val();
            if(selectedValue == '') {
                vtUtils.showValidationMessage(select2Element, app.vtranslate('JS_REQUIRED_FIELD'));
                e.preventDefault();
            } else {
                vtUtils.hideValidationMessage(select2Element);
                jQuery('.select2',jQuery(this)).prop('disabled',false);
            }
        });
    }
});
