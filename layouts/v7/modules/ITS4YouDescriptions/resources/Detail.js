/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */
/** @var ITS4YouDescriptions_Detail_Js */
Vtiger_Detail_Js('ITS4YouDescriptions_Detail_Js', {}, {
    registerEventForCkEditor : function(){
        let self = this;

        self.textToHtml();

        app.event.on('post.relatedListLoad.click', function () {
            self.textToHtml();
        });
    },
    textToHtml: function() {
        let fieldLabel = $('#ITS4YouDescriptions_detailView_fieldLabel_description');

        fieldLabel.addClass('hide');
    },
    registerEvents: function() {
        this._super();
        this.registerEventForCkEditor();
    }
});