/* * *******************************************************************************
 * The content of this file is subject to the Descriptions 4 You license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */
/** @var ITS4YouDescriptions_Editing_Js */

jQuery.Class('ITS4YouDescriptions_Editing_Js', {
    instance: false,
    getInstance: function () {
        if (!this.instance) {
            this.instance = new ITS4YouDescriptions_Editing_Js();
        }

        return this.instance;
    },
    showModalEditWindow: function (affected_textarea) {
        let aDeferred = jQuery.Deferred(),
            progressIndicatorElement = jQuery.progressIndicator({
                'position': 'html',
                'blockInfo': {
                    'enabled': true,
                },
            }),
            editView = jQuery('#EditView'),
            url = 'index.php?module=ITS4YouDescriptions&view=ModalEditWindow&affected_textarea=' + affected_textarea + '&formodule=' + editView.find('input[name="module"]').val() + '&affected_textarea_value=' + editView.find('textarea[name="' + affected_textarea + '"]').text();

        AppConnector.request(url).then(function (data) {
            const callBackFunction = function (data) {
            };

            progressIndicatorElement.progressIndicator({'mode': 'hide'});
            app.showModalWindow(data, function (data) {
                if (typeof callBackFunction == 'function') {
                    callBackFunction(data);
                }
            }, {'width': '780px'});
        }, function (error) {
            aDeferred.reject(error);
        });

        return aDeferred.promise();
    },
    replaceInTextarea: function (affected_textarea, formodule) {
        this.getInstance().requestForTextarea(affected_textarea, formodule, 'replace');
    },
    addToTextarea: function (affected_textarea, formodule) {
        this.getInstance().requestForTextarea(affected_textarea, formodule, 'add');
    },
}, {
    configCKE: function(fieldname) {
        let config = {
            height : 200,
            enterMode : CKEDITOR.ENTER_BR,
            shiftEnterMode : CKEDITOR.ENTER_BR
        };

        if(fieldname === 'description' || fieldname === 'terms_conditions' || fieldname === 'solution') {
            let fieldLabelTd = jQuery('[name="' + fieldname + '"]').parents('tr');
            fieldLabelTd.find('.fieldLabel').remove();
            config.height = 400;
        } else {
            if (!fieldname.indexOf("comment")) {
                config.width = 75 + '%';
            }

            config.toolbar = [
                { name: 'style', items: [ 'Source' , 'Bold', 'Italic', 'Underline', 'Strike', 'RemoveFormat',] },
                { name: 'colors', items: [ 'TextColor', 'BGColor', ] },
                { name: 'tab_image', items: [ 'Table','Image', 'Link', 'Unlink',]},
                { name: 'text', items: [ 'FontSize' ] },
                { name: 'justify', items: [ 'NumberedList', 'BulletedList', 'Outdent', 'Indent', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', ] },
            ];
            config.resize_enabled = false;
        }

        return config;
    },
    getCKE: function(id) {
        const self = this,
            textarea = jQuery('[name="' + id + '"]');

        if(!textarea.attr('id')) {
            textarea.attr('id', id);
        }

        if(self.isAllowedCKE()) {
            if (!self.isDefinedCKE(id)) {
                CKEDITOR.replace(id, self.configCKE(id));
            }

            return CKEDITOR.instances[id];
        }

        return false;
    },
    requestForTextarea: function (affected_textarea, formodule, type) {
        let self = this,
            aDeferred = jQuery.Deferred(),
            progressIndicatorElement = jQuery.progressIndicator({
                'position': 'html',
                'blockInfo': {
                    'enabled': true,
                },
            }),
            url = 'index.php?module=ITS4YouDescriptions&action=GetDescriptionContent&affected_textarea=' + affected_textarea + '&formodule=' + formodule + '&descriptionid=' + jQuery('#sel_desc4you_' + affected_textarea).val();

        AppConnector.request(url).then(function (data) {
            aDeferred.resolve(data);
            let textareaElem = jQuery('#EditView').find('[name="' + affected_textarea + '"]'),
                textareaId = textareaElem.attr('id'),
                oldValue = self.isDefinedCKE(textareaId) ? self.getCKE(textareaId).getData() : textareaElem.val(),
                newValue = jQuery('<div/>').html(data.result).text();

            if ('add' === type) {
                newValue = oldValue + newValue;
            }
            if (self.isDefinedCKE(textareaId)) {
                self.getCKE(textareaId).setData(newValue);
            }

            textareaElem.val(newValue);
            progressIndicatorElement.progressIndicator({'mode': 'hide'});
        });

        return aDeferred.promise();
    },
    isAllowedCKE: function () {
        return ('undefined' !== typeof CKEDITOR);
    },
    registerEvents: function () {
        this.registerEditView();
        this.registerDetailView();
        this.registerInventoryModule();
    },
    registerInventoryModule: function () {
        if (this.isAllowedCKE()) {
            const self = this,
                params = {
                module: 'ITS4YouDescriptions',
                action: 'ProductBlock',
            };

            AppConnector.request('index.php?' + jQuery.param(params)).then(function(data) {
                if(parseInt(data['result']['success'])) {
                    self.registerProductsCKE();
                    self.registerProducts();
                    self.registerSortable();
                }
            });
        }
    },
    productsCKE: [],
    registerProductsCKE: function () {
        const self = this,
            proDesc = jQuery('.lineItemCommentBox');

        if (proDesc.length) {
            proDesc.each(function () {
                let textArea = jQuery(this),
                    textAreaId = textArea.attr('id');

                if (textArea.is(':visible')) {
                    if (!self.isAllowedCKE()) {
                        self.getCKE(textAreaId);
                    }
                }
            });
        }
    },
    registerProducts: function () {
        const self = this;

        jQuery('.qty').on('focusout', function () {
            const row = jQuery(this).parents('tr'),
                textArea = row.find('.lineItemCommentBox'),
                textAreaId = textArea.attr('id');

            self.registerProductsCKE();
            self.registerSortable();

            if (self.isChangedLine(row.attr('id'), row.find('.selectedModuleId').val())) {
                if (self.isDefinedCKE(textAreaId)) {
                    CKEDITOR.instances[textAreaId].setData(textArea.val())
                }
            }

            self.setLines();
        });
    },
    isDefinedCKE: function (id) {
        if (!this.isAllowedCKE()) {
            return false;
        }

        return ('undefined' !== typeof CKEDITOR.instances[id]);
    },
    lineItems: [],
    isChangedLine: function (id, value) {
        return this.lineItems[id] !== value;
    },
    setLines: function () {
        const self = this,
            rows = jQuery('.lineItemRow');

        rows.each(function () {
            const row = jQuery(this);

            self.lineItems[row.attr('id')] = row.find('.selectedModuleId').val();
        });
    },
    registerSortable: function () {
        const self = this;

        jQuery('.ui-sortable').sortable({
            start: function (event, ui) {
                let id_textarea = ui.item.find('.lineItemCommentBox').attr("id");

                if (self.isDefinedCKE(id_textarea)) {
                    self.getCKE(id_textarea).destroy();
                }
            },
            stop: function (event, ui) {
                let id_textarea = ui.item.find('.lineItemCommentBox').attr("id");

                if (!self.isAllowedCKE()) {
                    self.getCKE(id_textarea);
                }
            }
        });
    },
    disabledModules: [
        'ITS4YouDescriptions', 'Documents',
    ],
    isAllowedModule: function (module) {
        return this.disabledModules.indexOf(module) === -1;
    },
    registerDetailView: function () {
        const self = this,
            detailView = jQuery('#detailView');

        if (detailView.length) {
            let module = detailView.find('#module').val();
            jQuery.ajax('index.php?module=ITS4YouDescriptions&action=GetFieldsForModule&for_module=' + module).done(function (data) {
                let textAreas = data.split(',,');

                for (let j = 0; j < textAreas.length - 1; j++) {
                    if (self.isAllowedModule(module)) {
                        let tdFieldName = module + '_detailView_fieldValue_' + textAreas[j],
                            span = jQuery('#' + tdFieldName + ' span:first-child');

                        span.html(span.text());
                    }
                }
            });
        }
    },
    registerEditView: function () {
        const self = this,
            editView = jQuery('#EditView');

        if (editView.length) {
            let module = editView.find('input[name="module"]').val();

            if (self.isAllowedModule(module)) {
                jQuery.ajax('index.php?module=ITS4YouDescriptions&action=GetFieldsForModule&for_module=' + module).done(function (data) {
                    let textAreasData = data.split(',,'),
                        textAreas = jQuery('textarea');

                    for (let i = 0; i < textAreas.length; i++) {
                        for (let j = 0; j < textAreasData.length - 1; j++) {
                            if (textAreasData[j] === textAreas[i].name) {
                                let divElem = document.createElement('div');
                                divElem.id = 'div_desc_' + textAreasData[j];

                                jQuery.ajax('index.php?module=ITS4YouDescriptions&view=GetControllsForField&formodule=' + editView.find('input[name="module"]').val() + '&field=' + textAreasData[j]).done(function (data) {
                                    let splitted = data.split('|#@#|');
                                    jQuery('#div_desc_' + splitted[0]).html(splitted[1]);
                                });

                                let textarea = editView.find('textarea[name="' + textAreasData[j] + '"]'),
                                    config = {};

                                textarea.before(divElem);

                                if (self.isAllowedCKE()) {
                                    self.getCKE(textAreasData[j]);
                                }
                            }
                        }
                    }
                });
            }

        }
    },
});

jQuery(function () {
    ITS4YouDescriptions_Editing_Js.getInstance().registerEvents();
});