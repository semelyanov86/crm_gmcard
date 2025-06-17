/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

Vtiger_Edit_Js("ITS4YouDescriptions_Edit_Js", {}, {
    /**
     * Function to register event for ckeditor for description field
     */
    registerEventForCkEditor: function() {
        var form = this.getForm();
        var noteContentElement = form.find('[name="description"]');
        if (noteContentElement.length > 0) {
            var ckEditorInstance = new Vtiger_CkEditor_Js();
            ckEditorInstance.loadCkEditor(noteContentElement);
        }
    },
    registerEvents: function() {
        this._super();
        var vt4you = jQuery('#vt4you').val();
        if (vt4you != "vt4you") {
        	this.registerEventForCkEditor();
        }
    }
});


