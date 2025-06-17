/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

jQuery.Class("Descriptions4You_AllowedModules_Js", {}, {
    registerSaveAllowedModulesEvent: function() {
        return true;
        e.preventDefault();
    },
    registerCancelClickEvent: function() {
        jQuery('.cancelLink').on('click', function() {
            history.go(-1);
        });
    },
    registerEvents: function() {
        this.registerSaveAllowedModulesEvent();
        this.registerCancelClickEvent();
    }

});

jQuery(document).ready(function(e) {
    var instance = new Descriptions4You_AllowedModules_Js();
    instance.registerEvents();
})