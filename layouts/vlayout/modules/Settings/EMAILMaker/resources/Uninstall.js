/*******************************************************************************
 * The content of this file is subject to the EMAILMaker license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 ***************************************************************************** */
 
Settings_Vtiger_Index_Js('Settings_EMAILMaker_Uninstall_Js', {
    uninstall: function () {
        let module = app.getModuleName(),
            message = app.vtranslate('JS_UNINSTALL_CONFIRM');

        Vtiger_Helper_Js.showConfirmationBox({'message': message}).then(function() {
            let url = 'index.php?module=' + module + '&action=Uninstall&parent=Settings';

            AppConnector.request(url).then(function (data) {
                window.location.href = "index.php";
            });
        });
    },
    actions: function () {
        var thisInstance = this;

        jQuery('#ITS4YouUninstall_btn').click(function() {
            thisInstance.uninstall();
        });
    },
    init: function() {
        this.actions();
    }
});