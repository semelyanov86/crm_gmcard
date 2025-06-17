/* * *******************************************************************************
 * The content of this file is subject to the ITS4YouDescriptions license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */
/** @var ITS4YouDescriptions_AllowedModules_Js */

Vtiger_Index_Js("ITS4YouDescriptions_AllowedModules_Js", {}, {
    registerCancelClickEvent: function() {
        jQuery('.cancelLink').on('click', function() {
            location.history.back();
        });
    },
    registerSwitch: function() {
        jQuery("input.switch").bootstrapSwitch();
    },
    registerDeselectAll: function() {
        jQuery('.allowedFields').on('change', function() {
            const field = jQuery(this),
                values = field.val();

            if(null === values) {
                const selected = field.find('option[selected]');

                if(selected.length) {
                    selected.removeAttr('selected');
                    field.trigger('change');
                    field.val('');
                }
            }
        });
    },
    registerSubmit: function() {
        jQuery('#updateAllowedModulesForm').on('submit', function(e) {
            const params = jQuery(this).serializeFormData();

            app.request.post({data: params}).then(function(error, data) {
                if(!error) {
                    app.helper.showSuccessNotification({message: data['message']})
                }
            });

            e.preventDefault();
        });
    },
    registerEvents: function() {
        this._super();
        this.registerSwitch();
        this.registerCancelClickEvent();
        this.registerDeselectAll();
        this.registerSubmit();
    }
});