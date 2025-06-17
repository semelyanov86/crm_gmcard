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
        var objModuleName = form.find('#' + moduleName + '_editView_fieldName_module_name');

        objModuleLabel.change(function () {
            var focus = $(this);

            var val = focus.val();
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
                objModuleName.val('');
            }
            else {
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

        if (valModuleName == '') {
            btnSubmit.attr('disabled', 'disabled');
            return;
        } else if (valModuleName.indexOf('_') >= 0){
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
        AppConnector.request(params).then(
            function (response) {
                if (response.success) {
                    var result = response.result;

                    btnSubmit.attr('disabled', 'disabled');

                    Vtiger_Helper_Js.showMessage({
                        text: result.message
                    });
                } else {
                    btnSubmit.removeAttr('disabled');
                }
            },
            function (error) {
                console.log('error =', error);
                Vtiger_Helper_Js.showMessage({
                    text: error
                });
            }
        );
    },

    /**
     * Function which will register basic events which will be used in quick create as well
     *
     */
    registerBasicEvents: function (container) {
        this._super(container);
        this.registerAutoGenerateModuleName(container);
        $('.hover-tooltip').tooltip();
    }
});
