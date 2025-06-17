/* * *******************************************************************************
 * The content of this file is subject to the ITS4YouDescriptions license.
 * ("License"); You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is IT-Solutions4You s.r.o.
 * Portions created by IT-Solutions4You s.r.o. are Copyright(C) IT-Solutions4You s.r.o.
 * All Rights Reserved.
 * ****************************************************************************** */

let ITS4YouDescriptions_Hs = {

    initialize: function() {
        let view = app.getViewName(),
            module = app.getModuleName(),
            instance = this;

        if ('Edit' !== view && 'Detail' !== view) {
            return;
        }

        if(module !== 'ITS4YouDecsriptions') {
            if (view === 'Edit') {
                instance.setDescForEdit();

            } else if(view === 'Detail') {
                instance.setDescForDetail();
                instance.registerPreview();
            }

            app.event.on('post.relatedListLoad.click', function (event, searchRow) {
                instance.setDescForDetail();
            });

            app.event.on('post.overlay.load', function (event, parentRecordId, params) {
                instance.setDescForDetail(params['module']);
            });

            app.event.on('post.overLayEditView.loaded', function(event, element) {
                instance.setEditContainer(jQuery(element));
                instance.setDescForEdit(instance.getEditContainer().find('[name="module"]').val());
                instance.registerOverlayEditSubmit();
            });
        }
    },
    registerOverlayEditSubmit: function () {
        jQuery('#EditView').on('submit', function () {
            for (let name in CKEDITOR.instances) {
                CKEDITOR.instances[name].updateElement();
            }
        });
    },
    editContainer: false,
    setEditContainer: function (element) {
        this.editContainer = element;
    },
    getEditContainer: function () {
        return this.editContainer;
    },
    setDescForEdit: function(moduleName) {
        this.getFieldsForModule(moduleName);
        this.setReplaceAddTextarea();
        this.getProductsServicesCKEdit();
    },
    setDescForDetail: function(moduleName = false) {
        let self = this,
            module = moduleName ? moduleName : app.getModuleName(),
            params = {
                module:'ITS4YouDescriptions',
                action: 'GetFieldsForModule',
                'for_module': module,
                'for_record': app.getRecordId(),
            };
        app.request.post({'data':params}).then( function(error, data) {

            if (error == null) {
                for(let i = 0; i < data.length; i++) {
                    let fieldName = data[i]['fieldname'],
                        fieldValue = data[i]['fieldvalue'];

                    self.getHtmlFromText(fieldName, fieldValue);
                }
            }
        });
    },
    getFieldColumn: function(fieldName) {
        let editContainer = this.getEditContainer(),
            td;

        if(editContainer) {
            td = editContainer.find('[data-name="' + fieldName + '"]').parents('td');
        } else {
            td = jQuery('[data-name="' + fieldName + '"]').parents('td');
        }

        if(!td.length) {
            td = jQuery('[id*="_fieldValue_' + fieldName + '"]');
        }

        return td;
    },
    convertToString: function (value) {
        let newValue = '';
        value = jQuery.parseHTML(value);

        jQuery.each(value, function (index, element) {
            if ('#text' !== element.nodeName) {
                newValue = newValue + element.outerHTML;
            } else {
                newValue = newValue + element.nodeValue;
            }
        });

        return newValue;
    },
    getHtmlFromText: function (fieldName, fieldValue) {
        let self = this,
            activeTab = jQuery('.tab-item.active').data('link-key'),
            td = this.getFieldColumn(fieldName),
            tr = td.parents('tr'),
            label = tr.find('.fieldLabel'),
            value = td.find('.value'),
            action = td.find('.action'),
            edit = td.find('.edit'),
            textSpan = 'text_' + fieldName;

        if ('LBL_RECORD_SUMMARY' === activeTab) {
            value.after('<a href="#" class="btn btn-default btn-sm btn_preview" value="' + fieldName + '">' + app.vtranslate('Preview') + '</a>');
        } else {
            value.after('<span class="' + textSpan + '">' + fieldValue + '</span>');

            if (!this.isVisibleFieldLabel(fieldName)) {
                label.addClass('hide');
            }
        }

        value.hide();
        action.hide();
        edit.hide();
    },
    isVisibleFieldLabel: function (value) {
        return -1 === jQuery.inArray(value, ['description', 'terms_conditions']);
    },
    registerPreview: function() {

        jQuery('.detailViewContainer').on('click', '.btn_preview', function() {

            let fieldname = jQuery(this).attr('value'),
                data = jQuery('[data-name="' + fieldname + '"]'),
                fieldlabel = data.parents('tr').find('.fieldLabel').text(),
                value = data.attr('data-value'),
                preview = jQuery('.modal_preview'),
                url = 'index.php?module=ITS4YouDescriptions&view=Preview';

            app.request.post({'url':url}).then( function(error, data) {

                if(error == null) {

                    if(preview.length === 0) {
                        data = jQuery(data);

                        data.find('.descriptions_preview').html(value);
                        data.find('.preview_title').html(fieldlabel);

                        app.helper.showModal(data);
                    }
                }
            });
        });
    },
    getTextarea: function (fieldName) {
        let editContainer = this.getEditContainer(),
            element,
            elementName = 'textarea[name="' + fieldName + '"]';

        if(editContainer.length) {
            element = editContainer.find(elementName);
        } else {
            element = jQuery(elementName);
        }

        return element;
    },
    getFieldsForModule: function(moduleName = false) {
        const self = this,
            module = moduleName ? moduleName : app.getModuleName(),
            params = {
                mode: 'getFields',
                'for_module': module,
            };
        self.getData(params).then(function (error, data) {
            if (!error) {
                for (let i = 0; i < data.length; i++) {
                    let thisData = data[i],
                        fieldName = thisData['fieldname'],
                        fieldLabel = thisData['fieldlabel'],
                        textarea = self.getTextarea(fieldName);

                    if (textarea.length) {
                        let divElement = document.createElement('div'),
                            params = {
                                module: 'ITS4YouDescriptions',
                                view: 'GetControllsForField',
                                formodule: module,
                                field: fieldName,
                                fieldlabel: fieldLabel,
                            };

                        divElement.className = 'div_desc_' + fieldName;
                        textarea.before(divElement);

                        app.request.post({data: params}).then(function (error, data) {
                            if (!error) {
                                let fieldName = jQuery(data).find('input[name="fieldname"]').val();

                                jQuery('.div_desc_' + fieldName).html(data);
                                jQuery('#sel_desc4you_' + fieldName).select2();
                            }
                        });

                        self.getCKE(fieldName, fieldLabel);
                    }
                }
            }
        });
    },
    getEditView: function() {
        return jQuery('#EditView');
    },
    setReplaceAddTextarea: function () {
        let self = this,
            editView = self.getEditView();

        editView.on('click', '.desc4you-success', function () {
            let btn = jQuery(this),
                parent = btn.parent(),
                thisVal = btn.val(),
                formodule = parent.find('[name="formodule"]').val(),
                fieldname = parent.find('[name="fieldname"]').val();

            self.requestForTextarea(fieldname, formodule, thisVal);
        });
    },
    requestForTextarea: function(fieldname, formodule, type) {
        const self = this,
            textareaval = jQuery("#sel_desc4you_" + fieldname).val(),
            params = {
                mode: 'getContent',
                'affected_textarea': fieldname,
                'formodule': formodule,
                'descriptionid': textareaval,
            };

        self.getData(params).then(function(error, data){

            if(error == null) {
                let fieldname = data.fieldname,
                    textareaelem = self.getTextarea(fieldname),
                    id = textareaelem.attr('id'),
                    result = data.result,
                    ckeInstance = CKEDITOR.instances[id],
                    textareaval = textareaelem.val();

                if(ckeInstance) {
                    textareaval = ckeInstance.getData();
                }

                if (type === 'Replace') {
                    textareaelem.val(result);
                    self.updateCKE(result, id);

                } else if (type === 'Add') {
                    let value = textareaval + result;
                    textareaelem.val(value);
                    self.updateCKE(value, id);
                }
            }
        });
    },
    configCKE: function(fieldname, fieldlabel) {
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
                { name: 'style', items: [ 'Source' , 'Bold', 'Italic', 'Underline', 'Strike', 'TextColor', 'BGColor', 'RemoveFormat',] },
                { name: 'tab_image', items: [ 'Table','Image', 'Link', 'Unlink',]},
                { name: 'text', items: [ 'FontSize' ] },
                { name: 'justify', items: [ 'NumberedList', 'BulletedList', 'Outdent', 'Indent', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', ] },

            ];
            config.resize_enabled = false;
        }

        return config;
    },
    getCKE: function(fieldname, fieldlabel) {
        let self = this,
            textarea = self.getTextarea(fieldname),
            textareaValue = textarea.val(),
            textareaHtml = $('<div>' + textareaValue + '</div>');

        if(!textarea.attr('id')) {
            textarea.attr('id', fieldname);
        }

        if (!textareaHtml.find('br').length && !textareaHtml.find('li').length) {
            textarea.val(self.nl2br(textareaValue));
        }

        if(fieldname !== null) {
            let config = self.configCKE(fieldname, fieldlabel);

            CKEDITOR.replace(fieldname, config);
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
    nl2br: function (str) {
        if (typeof str === 'undefined' || str === null) {
            return '';
        }

        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + '<br />' + '$2');
    },
    hasCKE: function (id) {
        return 'undefined' !== typeof CKEDITOR.instances[id]
    },
    updateCKE: function(value, id) {

        if(id !== null) {


            if (CKEDITOR.instances[id]) {
                CKEDITOR.instances[id].setData(value);
            }
        }
    },
    destroyCKE: function (id) {
        if (id && $('#cke_' + id).is(':visible') && CKEDITOR.instances[id]) {
            CKEDITOR.instances[id].destroy();
        }
    },
    controlCKE: function () {
        for (let name in CKEDITOR.instances) {
            let text = '_' + name;

            if (text.indexOf('comment')) {
                this.destroyCKE(name);
            }
        }

        return true;
    },
    getData: function (data) {
        const params = {
            module: 'ITS4YouDescriptions',
            action: 'Data',
        };

        jQuery.extend(params, data);

        return app.request.post({data: params});
    },
    allowedModules: {},
    setAllowedModules: function (data) {
        jQuery.extend(this.allowedModules, data);
    },
    isLineItemsAllowed: function () {
        return (this.allowedModules['Products'] || this.allowedModules['Services']);
    },
    getProductsServicesCKEdit: function () {
        const self = this,
            isInventory = jQuery('.lineitemTableContainer');

        if (isInventory.length) {
            self.getData({mode: 'getInventory'}).then(function (error, data) {
                if (!error) {
                    self.setAllowedModules(data);

                    if (self.isLineItemsAllowed()) {
                        self.registerLineItems();
                    }
                }
            });
        }
    },
    registerLineItems: function () {
        this.getFieldsForComment();
        this.registerLineItemsChange();
        this.registerLineItemsDelete();
        this.registerLineItemsComment();
    },
    removeCkeditors: function(textarea, ckeditor) {
        let self = this,
            productList = jQuery('#lineItemTab'),
            request = true;

        if(textarea.length === 1 && ckeditor.length === 1) {
            let textareaId = textarea.attr('id'),
                isCKE = ckeditor.is('#cke_' + textareaId);

            if(!isCKE) {
                let remTextareas = productList.find('textarea');

                remTextareas.each(function() {
                    let remTextarea = jQuery(this),
                        remId = remTextarea[0].id;

                    if(remTextarea.is(':hidden')) {
                        self.destroyCKE(remId);
                        request = false;
                    }
                });
            }
        }

        return request;
    },
    getLineControl: function() {
        let self = this,
            request = true,
            productLine = jQuery('.lineItemRow');

        productLine.each(function() {

            let thisLine = jQuery(this),
                thisTextarea = thisLine.find('textarea'),
                thisCkeditor = thisLine.find('.cke');

            request = self.removeCkeditors(thisTextarea, thisCkeditor);

            if(!request) {
                return request;
            }
        });

        return request;
    },
    getTextareasForComment: function() {
        let self = this,
            productList = jQuery('#lineItemTab'),
            allText = productList.find('textarea'),
            textareas = allText.filter(':visible');

        if(textareas.length !== 0) {
            textareas.each(function() {
                let textarea = jQuery(this),
                    id = textarea.attr('id');

                if(textarea.is(':visible') && !self.hasCKE(id)) {
                    self.getCKE(id);
                }
            });
        }
    },
    registerLineItemsComment: function() {
        const self = this;

        jQuery('.lineItemCommentBox').each(function() {
            let textArea = jQuery(this),
                productId = textArea.parents('.lineItemRow').find('.selectedModuleId');

            self.setUpdatedProduct(textArea.attr('id'), productId.val());
        });
    },
    registerLineItemsChange: function () {
        const self = this;

        self.getLineItemTab().on('focusout', '.qty', function () {
            let textareaNum = jQuery(this).parents('.lineItemRow').attr('data-row-num'),
                fieldName = 'comment' + textareaNum,
                descId = jQuery('#hdnProductId' + textareaNum).val(),
                ckeditor = jQuery('#cke_' + fieldName);

            if (self.getLineControl()) {
                if (ckeditor.length && descId && self.isUpdatedProduct(descId, fieldName)) {
                    self.updateLineItemTextarea(descId, fieldName);
                }

                self.getTextareasForComment();
            }

            self.setUpdatedProduct(fieldName, descId);
        });
    },
    updateLineItemTextarea: function (descId, fieldName) {
        const self = this,
            params = {
                'mode': 'getContent',
                'descriptionid': descId,
            };

        self.getData(params).then(function (error, data) {
            if (!error) {
                self.updateCKE(data.result, fieldName);
            }
        });
    },
    registerLineItemsDelete: function () {
        const self = this;

        self.getLineItemTab().on('mousedown', '.deleteRow', function () {
            let delTextarea = jQuery(this).parent().parent().find('textarea');

            self.destroyCKE(delTextarea[0].id);
        });
    },
    updatedProducts: [],
    getUpdatedProduct: function (textarea) {
        return this.updatedProducts[textarea];
    },
    setUpdatedProduct: function (textarea, value) {
        this.updatedProducts[textarea] = value;
    },
    isUpdatedProduct: function (id, textarea) {
        return this.getUpdatedProduct(textarea) !== id;
    },
    getLineItemTab: function () {
        return jQuery('#lineItemTab');
    },
    getFieldsForComment: function() {
        let self = this,
            productList = jQuery('#lineItemTab'),
            editView = jQuery('#EditView');

        if(productList) {
            editView.submit(function() {

                self.controlCKE();
            });

            productList.sortable({
                start: function (e, ui) {
                    let thisSort = jQuery(ui.item.context),
                        sortTextarea = thisSort.find('textarea'),
                        sortCkeditor = thisSort.find('.cke'),
                        sortId = sortTextarea.attr('id');

                    if(self.removeCkeditors(sortTextarea, sortCkeditor)) {
                        self.destroyCKE(sortId);
                    }
                },
                stop: function () {
                    self.getTextareasForComment();
                    self.registerLineItemsComment();
                },
            });

            self.getTextareasForComment();
        }
    },
};

jQuery(function() {
    ITS4YouDescriptions_Hs.initialize();
});
