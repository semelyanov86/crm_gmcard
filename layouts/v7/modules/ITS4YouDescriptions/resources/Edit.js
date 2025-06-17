/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

Vtiger_Edit_Js("ITS4YouDescriptions_Edit_Js", {}, {

    registerEventForCkEditor : function(container, textarea){
        var form = this.getForm();
        var noteContentElement = form.find(textarea);
        if(noteContentElement.length > 0){
            var ckEditorInstance = new Vtiger_CkEditor_Js();
            CKEDITOR.config.height = 400;

            ckEditorInstance.loadCkEditor(noteContentElement);

            CKEDITOR.on('instanceReady', function (ev) {
                ev.editor.dataProcessor.writer.setRules( 'br',
                    {
                        indent : false,
                        breakBeforeOpen : false,
                        breakAfterOpen : false,
                        breakBeforeClose : false,
                        breakAfterClose : false
                    });
            });
        }
    },

    registerEvents: function() {
        var container = this._super();
        this.registerEventForCkEditor(container, '[name="description"]');
    }
});