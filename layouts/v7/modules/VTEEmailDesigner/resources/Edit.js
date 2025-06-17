/* ********************************************************************************
 * The content of this file is subject to the Custom Header/Bills ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
Vtiger.Class("VTEEmailDesigner_Edit_Js",{
    instance:false,
    getInstance: function(){
        if(VTEEmailDesigner_Edit_Js.instance == false){
            var instance = new VTEEmailDesigner_Edit_Js();
            VTEEmailDesigner_Edit_Js.instance = instance;
            return instance;
        }
        return VTEEmailDesigner_Edit_Js.instance;
    }
},{
    /* For License page - Begin */
    init : function() {
        this.initiate();
    },
    /*
     * Function to initiate the step 1 instance
     */
    initiate : function(){

        var step=jQuery(".installationContents").find('.step').val();
        this.initiateStep(step);
    },
    /*
     * Function to initiate all the operations for a step
     * @params step value
     */
    initiateStep : function(stepVal) {
        var step = 'step'+stepVal;
        this.activateHeader(step);
    },

    activateHeader : function(step) {
        var headersContainer = jQuery('.crumbs ');
        headersContainer.find('.active').removeClass('active');
        jQuery('#'+step,headersContainer).addClass('active');
    },

    registerActivateLicenseEvent : function() {
        var aDeferred = jQuery.Deferred();
        jQuery(".installationContents").find('[name="btnActivate"]').click(function() {
            var license_key=jQuery('#license_key');
            if(license_key.val()=='') {
                app.helper.showAlertBox({message:"License Key cannot be empty"});
                aDeferred.reject();
                return aDeferred.promise();
            }else{
                app.helper.showProgress();
                var params = {};
                params['module'] = app.getModuleName();
                params['action'] = 'Activate';
                params['mode'] = 'activate';
                params['license'] = license_key.val();

                app.request.post({data:params}).then(
                    function(err,data) {
                        app.helper.hideProgress();
                        if(err == null){
                            var message=data['message'];
                            if(message !='Valid License') {
                                app.helper.hideProgress();
                                app.helper.hideModal();
                                app.helper.showAlertNotification({'message':data['message']});
                            }else{
                                document.location.href="index.php?module=VTEEmailDesigner&view=Edit&mode=step3";
                            }
                        }
                    },
                    function(error) {
                        app.helper.hideProgress();
                    }
                );
            }
        });
    },
    registerValidEvent: function () {
        jQuery(".installationContents").find('[name="btnFinish"]').click(function() {
            app.helper.showProgress();
            var data = {};
            data['module'] = 'VTEEmailDesigner';
            data['action'] = 'Activate';
            data['mode'] = 'valid';
            app.request.post({data:data}).then(
                function (err,data) {
                    if(err == null){
                        app.helper.hideProgress();
                        if (data) {
                            document.location.href = "index.php?module=VTEEmailDesigner&view=List";
                        }
                    }
                }
            );
        });
    },
    UploadImageLocal: function() {
        $('body').on('change', 'input[name="fileToUpload"]', function(){
            var element = this;
            var formData = new FormData();
            formData.append('file', $('#fileToUpload')[0].files[0]);
            $.ajax({
                type: 'POST',
                url: "index.php?module=VTEEmailDesigner&action=ActionAjax&mode=uploadImageLocal",
                data: formData ,
                processData: false,
                contentType: false,
                success: function(data) {
                    if(data.result.data_arr.messager){
                        app.helper.showAlertNotification({'message':data.result.data_arr.messager});
                    }else{
                        jQuery(element).closest('.elements-accordion-item').find('.elements-accordion-item-content input[name="fileToUpload"]').val('');
                        jQuery(element).closest('.elements-accordion-item').find('.elements-accordion-item-content input.image-url').val(data.result.data_arr.file).keyup();
                        jQuery(element).closest('.elements-accordion-item').find('.elements-accordion-item-content input.image-url').val('');
                        if(data.result.data_arr.messager_succes){
                            app.helper.showSuccessNotification({message:data.result.data_arr.messager_succes});
                        }
                    }
                }
            });
        });
    },
    /* For License page - End */
    /**
     * Function to register event for ckeditor for description field
     */
    registerEventForCkEditor : function(){
        var templateContentElement = jQuery("#templatecontent");
        if(templateContentElement.length > 0) {
            if(jQuery('#EditView').find('.isSystemTemplate').val() == 1) {
                templateContentElement.removeAttr('data-validation-engine').addClass('ckEditorSource');
            }
            var customConfig = {
                "height":"600px"
            }
            var ckEditorInstance = new Vtiger_CkEditor_Js();
            ckEditorInstance.loadCkEditor(templateContentElement,customConfig);
        }
        //this.registerFillTemplateContentEvent();

    },




    _templateListItems:false,
    registerShowEmailDesigner: function () {
        var self = this;
        var  _emailBuilder=  $('.editor').emailBuilder({
            //new features begin
            showMobileView:true,
            onTemplateDeleteButtonClick:function (e,dataId,parent) {
                var message = app.vtranslate('LBL_DELETE_CONFIRMATION');
                app.helper.showConfirmationBox({'message' : message}).then(
                    function(e) {
                        $.ajax({
                            type: 'POST',
                            async: false,
                            url: "index.php",
                            dataType: 'json',
                            data: "module=VTEEmailDesigner&action=ActionAjax&mode=doDeleteTemplate&templateid="+dataId,
                            success: function(data) {
                                app.helper.showSuccessNotification({message:'Deleted!'});
                                parent.remove();
                                $('input[name="emailtemplateid"]').val('');
                                $('input[name="templatename"]').val('');
                                $('input[name="subject"]').val('');
                                $('select[name="modulename"]').val('');
                            },
                            error: function() {}
                        });
                    },
                    function(error, err){
                    }
                );


                /*$.ajax({
                    url: 'delete_template.php',
                    type: 'POST',
                    data: {
                        templateId: dataId
                    },
                    //	dataType: 'json',
                    success: function(data) {
                        parent.remove();
                    },
                    error: function() {}
                });*/
            },
            //new features end

            lang: 'en',
            elementsHTML:$('.elements-db').html(),
            elementsProperty:$('.elements-property').html(),
            langJsonUrl: 'layouts/v7/modules/VTEEmailDesigner/resources/assets/lang-1.json',
            loading_color1: 'red',
            loading_color2: 'green',
            showLoading: true,

            blankPageHtmlUrl: 'layouts/v7/modules/VTEEmailDesigner/resources/assets/template-blank-page.html?',
            loadPageHtmlUrl: 'layouts/v7/modules/VTEEmailDesigner/resources/assets/template-blank-page.html',

            //left menu
            showElementsTab: true,
            showPropertyTab: true,
            showDetailTab: true,
            showCollapseMenu: true,
            showBlankPageButton: false,
            showCollapseMenuinBottom: true,

            //setting items
            showSettingsBar: true,
            showSettingsPreview: false,
            showSettingsExport: false,
            showSettingsImport: false,
            showSettingsSendMail: false,
            showSettingsSave: false,
            showSettingsLoadTemplate: false,

            //show context menu
            showContextMenu: true,
            showContextMenu_FontFamily: true,
            showContextMenu_FontSize: true,
            showContextMenu_Bold: true,
            showContextMenu_Italic: true,
            showContextMenu_Underline: true,
            showContextMenu_Strikethrough: true,
            showContextMenu_Hyperlink: true,

            //show or hide elements actions
            showRowMoveButton: true,
            showRowRemoveButton: true,
            showRowDuplicateButton: true,
            showRowCodeEditorButton: true,
            showRowCodeEditorButton: true,
            onSettingsImportClick: function () {
                $('#popupimport').modal('show');
            },
            onBeforePopupBtnImportClick: function () {
                console.log('onBeforePopupBtnImportClick html');
                var file_data = $('.input-import-file').prop('files')[0];
                var form_data = new FormData();
                form_data.append('importfile', file_data);

                $.ajax({
                    url: 'template_import.php',
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_data,
                    type: 'post',
                    success: function (response) {
                        _data=response;
                        //  _data = JSON.parse(response);
                        $('.content-wrapper .email-editor-elements-sortable').html('');
                        $('#demp').html(_data.content);
                        _content = '';
                        $('#demp .main').each(function (index, item) {
                            _content += '<div class="sortable-row">' +
                                '<div class="sortable-row-container">' +
                                ' <div class="sortable-row-actions">';

                            _content += '<div class="row-move row-action">' +
                                '<i class="fa fa-arrows-alt"></i>' +
                                '</div>';

                            _content += '<div class="row-remove row-action">' +
                                '<i class="fa fa-remove"></i>' +
                                '</div>';

                            _content += '<div class="row-duplicate row-action">' +
                                '<i class="fa fa-files-o"></i>' +
                                '</div>';

                            _content += '<div class="row-code row-action">' +
                                '<i class="fa fa-code"></i>' +
                                '</div>';
                            _content += '</div>' +

                                '<div class="sortable-row-content" >' +
                                '</div></div></div>';
                            $('.content-wrapper .email-editor-elements-sortable').append(_content);
                            $('.content-wrapper .email-editor-elements-sortable .sortable-row').eq(index).find('.sortable-row-content').append(item);
                        });
                    }
                });
            },
            onElementDragStart: function(e) {
            },
            onElementDragFinished: function(e,contentHtml,dataId) {
                $.ajax({
                    type: 'POST',
                    async: false,
                    url: "index.php",
                    dataType: 'json',
                    data: "module=VTEEmailDesigner&action=ActionAjax&mode=UpdateBlockInfo&block_id="+dataId,
                    success: function(data) {
                        var email_width = $('#editorContent').attr('data-width');
                        jQuery(".sortable-row").css({ width: email_width});
                        var bgColorInner = $('[name="bg_color_inner"]').val();
                        if(bgColorInner != '') {
                            jQuery(".sortable-row").find('.sortable-row-content .main .element-content').css({'background-color': bgColorInner});
                        }
                    },
                    error: function() {}
                });
            },

            onBeforeRowRemoveButtonClick: function(e) {
                console.log('onBeforeRemoveButtonClick html');
                /*
                 if you want do not work code in plugin ,
                 you must use e.preventDefault();
                 */
                //e.preventDefault();
            },
            onAfterRowRemoveButtonClick: function(e) {
                console.log('onAfterRemoveButtonClick html');
            },
            onBeforeRowDuplicateButtonClick: function(e) {
                console.log('onBeforeRowDuplicateButtonClick html');
                //e.preventDefault();
            },
            onAfterRowDuplicateButtonClick: function(e) {
                console.log('onAfterRowDuplicateButtonClick html');
            },
            onBeforeRowEditorButtonClick: function(e) {
                console.log('onBeforeRowEditorButtonClick html');
                //e.preventDefault();
            },
            onAfterRowEditorButtonClick: function(e) {
                console.log('onAfterRowDuplicateButtonClick html');
            },
            onBeforeShowingEditorPopup: function(e) {
                console.log('onBeforeShowingEditorPopup html');
                //e.preventDefault();
            },
            onBeforeSettingsSaveButtonClick: function(e) {
                console.log('onBeforeSaveButtonClick html');
                arr=[];
                var count=0;
                $('.content-main .sortable-row-content').each(function (i,item) {
                    _dataId=$(this).attr('data-id');
                    _html=$(this).html();
                    arr[i]={id:_dataId,content:_html};
                    if (_dataId!==undefined) {
                        count++;
                    }
                });

                if (count==0) {
                    alert('Please add email blocks from the right menu, otherwise you cannot save');
                    e.preventDefault();
                    return false;
                }
                var _tabMenuItem = $('.left-menu-container .menu-item[data-tab-selector="tab-detail"]');

                var templateid = $('input[name="emailtemplateid"]').val();
                var isDuplicate = $('input[name="isDuplicate"]').val();
                var templateField = $('input[name="templatename"]');
                var templateValue = templateField.val();
                var subjectField = $('input[name="subject"]');
                var subjectValue = subjectField.val();
                var moduleField = $('select[name="modulename"]');
                var moduleValue = moduleField.val();
                var templateDesc = $('#description').val();
                if (templateValue.length == 0) {
                    app.helper.showErrorNotification({message: app.vtranslate('JS_TEMPLATE_FIELD_DOES_NOT_EXISTS')});
                    _tabMenuItem.trigger('click');
                    templateField.focus();
                    e.preventDefault();
                    return false;
                }
                if (subjectValue.length == 0) {
                    app.helper.showErrorNotification({message: app.vtranslate('JS_SUBJECT_FIELD_DOES_NOT_EXISTS')});
                    _tabMenuItem.trigger('click');
                    subjectField.focus();
                    e.preventDefault();
                    return false;
                }
                /*if (moduleValue.length == 0 && templateid.length>0) {
                    app.helper.showErrorNotification({message: app.vtranslate('JS_MODULE_FIELD_DOES_NOT_EXISTS')});
                    _tabMenuItem.trigger('click');
                    e.preventDefault();
                    return false;
                }*/
                jQuery(".email-editor-elements-sortable").css({ margin: "0 auto"});
                var useHeight = $('#editorContent').prop('scrollHeight');
                html2canvas(jQuery("#editorContent").contents().find(".email-editor-elements-sortable"), {
                    "background-color":'#E9EAEA',
                    height: useHeight+150,
                    onrendered: function(canvas) {
                        var thumbnail = canvas.toDataURL('image/jpeg', 0.5);
                        var bast64Image = jQuery('[name="base64image"]');
                        bast64Image.text(thumbnail);
                        $(function() {
                            checkImageFind();
                            function checkImageFind() {
                                myInterVal = setInterval(checkImageFind,1000);
                                if(bast64Image.text().length > 0){
                                    app.helper.hideProgress();
                                    clearInterval(myInterVal);
                                }
                            }
                        });
                    },
                    useCORS: true
                });
                var parsedUrl = app.convertUrlToDataParams(window.location.href);
                if(parsedUrl.flag){
                    flags = parsedUrl.flag;
                }else{
                    flags = '';
                }
                setTimeout(function() {
                    var params = {};
                    if(isDuplicate=='true'){
                        var tempId='';
                    }else{
                        var tempId=templateid;
                    }
                    params.data = {
                        module: 'VTEEmailDesigner',
                        action: 'ActionAjax',
                        mode: 'doSaveTemplates',
                        templateid: tempId,
                        name: templateValue,
                        subject: subjectValue,
                        description: templateDesc,
                        source_module: moduleValue,
                        flag: flags,
                        bg_color: $('.content-wrapper').css('background-color'),
                        email_width: $('.content-main').attr('data-width'),
                        bg_color_inner: $('[name="bg_color_inner"]').val(),
                        contentArr: arr,
                        base64image: jQuery('[name="base64image"]').val(),
                    }
                    app.request.post(params).then(
                        function (err, data) {
                            if (!err) {
                                $('.tab-detail .elements-accordion-item input[name="emailtemplateid"]').val(data.templateid);
                                app.helper.showSuccessNotification({message: 'Saved!'});
                                jQuery(".email-editor-elements-sortable").css({ width: "",margin: "" });
                                if(isDuplicate=='true'){
                                    $('.tab-detail .elements-accordion-item input[name="isDuplicate"]').val('');
                                    window.removeEventListener("beforeunload",confirm_exit);
                                    window.location.href = 'index.php?module=VTEEmailDesigner&view=Edit&recordid='+data.templateid;
                                }
                            }
                        },
                        function (data, err) {
                        }
                    );
                },1000);
                //  if (_is_demo) {
                //      $('#popup_demo').modal('show');
                //      e.preventDefault();//return false
                //  }
            },
            onPopupUploadImageButtonClick: function() {
                console.log('onPopupUploadImageButtonClick html');
                var file_data = $('.input-file').prop('files')[0];
                var form_data = new FormData();
                form_data.append('file', file_data);
                $.ajax({
                    url: 'upload.php', // point to server-side PHP script
                    dataType: 'text', // what to expect back from the PHP script, if anything
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_data,
                    type: 'post',
                    success: function(php_script_response) {
                        loadImages();
                    }
                });
            },
            onSettingsPreviewButtonClick: function(e, getHtml) {
                console.log('onPreviewButtonClick html');
                $.ajax({
                    url: "index.php?module=VTEEmailDesigner&action=ActionAjax&mode=doExportTemplate",
                    type: 'POST',
                    data: {
                        html: getHtml
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.code == -5) {
                            $('#popup_demo').modal('show');
                            return;
                        } else if (data.code == 0) {
                            $('#previewModalFrame').attr('src',data.preview_url);
                            $('.preview_url').html('<a href="'+data.preview_url+'" target="_blank">'+data.preview_url+'</a>');
                            $('#previewModal').modal('show');
                            var iframeHeight = $(window).height()-250;
                            var popupWidth = parseInt($('.content-wrapper').attr('data-width'))+200;
                            $('#previewModalFrame').css('height',iframeHeight+'px');
                            $('.modal-lg').css('max-width',popupWidth+'px');
                            // var win = window.open(data.preview_url, '_blank');
                            // if (win) {
                            //     //Browser has allowed it to be opened
                            //     win.focus();
                            // } else {
                            //     //Browser has blocked it
                            //     alert('Please allow popups for this website');
                            // }
                        }
                    },
                    error: function() {}
                });
                //e.preventDefault();
            },

            onSettingsExportButtonClick: function(e, getHtml) {
                console.log('onSettingsExportButtonClick html');
                $.ajax({
                    url: "index.php?module=VTEEmailDesigner&action=ActionAjax&mode=doExportTemplate",
                    type: 'POST',
                    data: {
                        html: getHtml
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.code == -5) {
                            $('#popup_demo').modal('show');
                        } else if (data.code == 0) {
                            window.location.href = data.url;
                        }
                    },
                    error: function() {}
                });
                //e.preventDefault();
            },
            onBeforeSettingsLoadTemplateButtonClick: function(e) {
                $('.template-list').html('<div style="text-align:center">Loading...</div>');
                $.ajax({
                    type: 'POST',
                    async: false,
                    url: "index.php",
                    dataType: 'json',
                    data: "module=VTEEmailDesigner&action=ActionAjax&mode=loadTemplates",
                    success: function(data) {
                        if (data.result.response.code == 0) {
                            _templateItems = '';
                            _templateListItems = data.result.response.files;
                            for (var i = 0; i < _templateListItems.length; i++) {
                                _templateItems += '<div class="template-item" style="display: content!important;" data-id="' + _templateListItems[i].templateid + '">' +
                                    '<div class="template-item-delete" data-id="' + _templateListItems[i].templateid + '">' +
                                    '<i class="fa fa-trash-o"></i>' +
                                    '</div>' +
                                    '<div class="template-item-icon">' +
                                    '<i class="fa fa-file-text-o"></i>' +
                                    '</div>' +
                                    '<div class="template-item-name">' +
                                    _templateListItems[i].templatename +
                                    '</div>' +
                                    '</div>';
                            }
                            $('.template-list').html(_templateItems);
                        } else if (data.result.response.code == 1) {
                            $('.template-list').html('<div style="text-align:center">No items</div>');
                        }
                    },
                    error: function() {}
                });
            },
            onSettingsSendMailButtonClick: function(e) {
                console.log('onSettingsSendMailButtonClick html');
                //e.preventDefault();
            },
            onPopupSendMailButtonClick: function(e, _html) {
                _email = $('.recipient-email').val();
                _subject = $('.email-title').val();
                _element = $('.btn-send-email-template');

                output = $('.popup_send_email_output');
                var file_data = $('#send_attachments').prop('files');
                var form_data = new FormData();
                //form_data.append('attachments', file_data);
                $.each(file_data,function (i,file) {
                    form_data.append('attachments['+i+']', file);
                });
                form_data.append('html', _html);
                form_data.append('mail', _email);
                form_data.append('subject', _subject);
                $.ajax({
                    url: 'send.php', // point to server-side PHP script
                    dataType: 'json', // what to expect back from the PHP script, if anything
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: form_data,
                    type: 'post',
                    success: function(data) {
                        if (data.code == 0) {
                            output.css('color', 'green');
                        } else {
                            output.css('color', 'red');
                        }

                        _element.removeClass('has-loading');
                        _element.text('Send Email');

                        output.text(data.message);
                    }
                });

            },
            onBeforeChangeImageClick: function(e) {
                console.log('onBeforeChangeImageClick html');
                loadImages();
            },
            onBeforePopupSelectTemplateButtonClick: function(dataId) {
                $.ajax({
                    type: 'POST',
                    url: "index.php?module=VTEEmailDesigner&action=ActionAjax&mode=getTemplateBlocks",
                    data: {
                        "templateid":dataId
                    },
                    success: function(data) {
                        data=data.result.response;
                        var templateName = data.template[0].templatename;
                        var subject = data.template[0].subject;
                        var module = data.template[0].module;
                        var description = data.template[0].description;
                        var templateid = data.template[0].templateid;
                        $('input[name="emailtemplateid"]').val(templateid);
                        $('input[name="templatename"]').val(templateName);
                        $('input[name="subject"]').val(subject);
                        var eleModuleName = $('select[name="modulename"]');
                        eleModuleName.val(module);
                        eleModuleName.trigger('liszt:updated');
                        eleModuleName.trigger('change');
                        $('textarea[name="description"]').val(description);

                        $('.content-wrapper .email-editor-elements-sortable').html('');
                        for (var i = 0; i < data.blocks.length; i++) {
                            _content='';
                            _content += '<div class="sortable-row" style="width: '+data.template[0].email_width+'px">' +
                                '<div class="sortable-row-container">' +
                                ' <div class="sortable-row-actions">';

                            _content += '<div class="row-move row-action">' +
                                '<i class="fa fa-arrows-alt"></i>' +
                                '</div>';


                            _content += '<div class="row-remove row-action">' +
                                '<i class="fa fa-remove"></i>' +
                                '</div>';


                            _content += '<div class="row-duplicate row-action">' +
                                '<i class="fa fa-files-o"></i>' +
                                '</div>';


                            _content += '<div class="row-code row-action">' +
                                '<i class="fa fa-code"></i>' +
                                '</div>';

                            _content += '</div>' +

                                '<div class="sortable-row-content" data-id='+	data.blocks[i].blockid+' data-types='+	data.blocks[i].property+'  data-last-type='+	data.blocks[i].property.split(',')[0]+'  >' +
                                $("<div/>").html(data.blocks[i].content).text() +
                                '</div></div></div>';
                            $('.content-wrapper .email-editor-elements-sortable').append(_content);

                        }
                        $('.content-wrapper').css('background-color', data.template[0].bg_color);
                        $('.content-wrapper').attr('data-width',data.template[0].email_width);
                        $('.content-main').attr('data-width', data.template[0].email_width);
                        jQuery('.main').css('width', data.template[0].email_width);
                        $('#bg-color-outer').css('background-color', data.template[0].bg_color);
                        $('#bg-color-inner').css('background-color', data.template[0].bg_color_inner);
                        $('[name="bg_color_inner"]').val(data.template[0].bg_color_inner);
                    },
                    error: function(error) {
                        $('.input-error').text('Internal error');
                    }
                });
                //_emailBuilder.makeSortable();
            },
            onBeforePopupSelectImageButtonClick: function(e) {
                console.log('onBeforePopupSelectImageButtonClick html');
            },
            onPopupSaveButtonClick: function() {
                var arr=[];
                var count=0;
                $('.content-main .sortable-row-content').each(function (i,item) {
                    _dataId=$(this).attr('data-id');
                    _html=$(this).html();
                    arr[i]={id:_dataId,content:_html};
                    if (_dataId!==undefined) {
                        count++;
                    }
                });
                var subject = $('.tab-detail .elements-accordion-item input[name="subject"]').val();
                alert(subject);
                if (count==0) {
                    alert('Please add email blocks from the right menu, otherwise you cannot save');
                    return false;
                }

                //

            },
            genScreenshot:function () {
                html2canvas(jQuery("#iframe-content").contents().find("#output_template > center"), {
                    onrendered: function(canvas) {
                        var thumbnail = canvas.toDataURL('image/jpeg', 0.5);
                        var bast64Image = jQuery('#EditView').find('[name="base64image"]');
                        bast64Image.text(thumbnail);
                        $(function() {
                            checkImageFind();
                            function checkImageFind() {
                                myInterVal = setInterval(checkImageFind,1000);
                                if(bast64Image.text().length > 0){
                                    app.helper.hideProgress();
                                    clearInterval(myInterVal);
                                }
                            }
                        });
                        jQuery('.btn-close').trigger('click');
                    },
                    useCORS: true
                });
            },
            onUpdateButtonClick: function() {
                var arr=[];
                $('.content-main .sortable-row-content').each(function (i,item) {
                    _dataId=$(this).attr('data-id');
                    _html=$(this).html();
                    arr[i]={id:_dataId,content:_html};
                });
                $.ajax({
                    url: 'upload_template.php',
                    type: 'POST',
                    //dataType: 'json',
                    data: {
                        name: $('.project-name').text(),
                        contentArr:arr,
                        id: $('.project-name').attr('data-id')
                    },
                    success: function(data) {
                        //  console.log(data);
                        // if (data === 'ok') {
                        // 		$('#popup_save_template').modal('hide');
                        // } else {
                        // 		$('.input-error').text('Problem in server');
                        // }
                    },
                    error: function(error) {
                        $('.input-error').text('Internal error');
                    }
                });
            },
            /**
             * Function which will register module change event
             */
            registerChangeEventForModule : function(){
                var thisInstance = this;
                var advaceFilterInstance = Vtiger_AdvanceFilter_Js.getInstance();
                var filterContainer = advaceFilterInstance.getFilterContainer();
                var fieldSelectElement = jQuery('select[name="modulename"]');
                thisInstance.registerFillTemplateContentEvent();
                filterContainer.on('change','select[name="modulename"]',function(e){
                    var ele = e.currentTarget;
                    thisInstance.loadFields(ele.value);
                    thisInstance.registerFillTemplateContentEvent();
                });
                filterContainer.closest('div').on('change','select[name="generalFields"]',function(e){
                    var ele = e.currentTarget;
                    thisInstance.registerFillTemplateContentEvent();
                });
                $("select").select2();
            },
            /**
             * Function to load condition list for the selected field
             * @params : fieldSelect - select element which will represents field list
             * @return : select element which will represent the condition element
             */
            loadFields : function(moduleName) {
                /*var moduleName = jQuery('select[name="modulename"]').val();*/
                var allFields = jQuery('[name="moduleFields"]').data('value');
                var fieldSelectElement = jQuery('select[name="templateFields"]');
                var options = '<option value="">None</option>';
                for(var key in allFields) {
                    //IE Browser consider the prototype properties also, it should consider has own properties only.
                    if(allFields.hasOwnProperty(key) && key == moduleName) {
                        var moduleSpecificFields = allFields[key];
                        var len = moduleSpecificFields.length;
                        for (var i = 0; i < len; i++) {
                            var fieldName = moduleSpecificFields[i][0].split(':');
                            options += '<option value="'+moduleSpecificFields[i][1]+'"';
                            if(fieldName[0] == moduleName) {
                                options += '>'+fieldName[1]+'</option>';
                            } else {
                                options += '>'+moduleSpecificFields[i][0]+'</option>';
                            }
                        }
                    }
                }

                fieldSelectElement.empty().html(options);
                fieldSelectElement.select2("destroy");
                fieldSelectElement.select2();

                return fieldSelectElement;

            },
            registerFillTemplateContentEvent : function() {
                var thisInstance = this;
                /*jQuery('#templateFields,#generalFields').off('change');
                jQuery('#templateFields,#generalFields').on('change',function(e){
                    var ele = jQuery(e.currentTarget);
                    var mergeTag = ele.val();
                    thisInstance.insertValueAtCursorPosition();
                    /!*$('.element-contenteditable.active').insertAtCaret(mergeTag);*!/
                    if(mergeTag !='') $('#curContentSelection').insertAtCaret(mergeTag);
                });*/
                thisInstance.insertValueAtCursorPosition();
                jQuery("#btnTemplateFields").unbind('click');
                jQuery("#btnTemplateFields").click(function() {
                    var fieldSelectElement = jQuery('select[name="templateFields"]');
                    var mergeTag = fieldSelectElement.val();
                    if(mergeTag !=''){
                        if ($('#curContentSelection img').length == 0 ){
                            $('#curContentSelection').insertAtCaret(mergeTag);
                        }else{
                            app.helper.showAlertNotification({'message':'Merge field no support for media element'});
                        }
                    }

                });
                jQuery("#btnGeneralFields").unbind('click');
                jQuery("#btnGeneralFields").click(function() {
                    var fieldSelectElement = jQuery('select[name="generalFields"]');
                    var mergeTag = fieldSelectElement.val();
                    if(mergeTag !='') $('#curContentSelection').insertAtCaret(mergeTag);
                })
            },
            insertValueAtCursorPosition: function() {
                $.fn.extend({
                    insertAtCaret: function(myValue) {
                        var obj;
                        if (typeof this[0].name !== 'undefined'){
                            obj = this[0];
                        } else {
                            obj = this;
                        }

                        // $.browser got deprecated from jQuery 1.9
                        // Inorder to know browsername, we are depending on useragent
                        var browserInfo  = navigator.userAgent.toLowerCase();
                        if (browserInfo.indexOf('msie') !== -1) {
                            obj.focus();
                            sel = document.selection.createRange();
                            sel.text = myValue;
                            obj.focus();
                        } else if (browserInfo.indexOf('mozilla') !== -1 || browserInfo.indexOf('webkit')!==-1) {
                            var selectionStart = $('input[name="contentSelectionStart"]').val().split(',');

                            var startPos = selectionStart[0];
                            var endPos = selectionStart[1];
                            var scrollTop = obj.scrollTop;
                            if($(obj).is('img')){
                                app.helper.showAlertNotification({'message':'Merge field no support for media element'});
                                return;
                            }
                            var curContent = $("<div>").text(obj.html().trim()).html();
                            obj.html($("<div />").html(curContent.substring(0, startPos) + myValue + curContent.substring(endPos, curContent.length)).text());
                            //obj.html($("<div />").html(curContent.substring(0, startPos) + myValue + curContent.substring(endPos, curContent.length)));
                            obj.focus();
                            startPos = parseInt(startPos)+parseInt(myValue.length);
                            endPos = parseInt(endPos)+parseInt(myValue.length);
                            $('input[name="contentSelectionStart"]').val(startPos+','+endPos);
                            obj.selectionStart = startPos + myValue.length;
                            obj.selectionEnd = startPos + myValue.length;
                            obj.scrollTop = scrollTop;
                        } else {
                            obj.value += myValue;
                            obj.focus();
                        }
                    }
                });
            },
            registerEventLoadTemplateById : function() {
                var params = app.convertUrlToDataParams(window.location.href);
                var dataId = params['recordid'];
                if(typeof dataId!='undefined') {
                    app.helper.showProgress();
                    $.ajax({
                        type: 'POST',
                        url: "index.php?module=VTEEmailDesigner&action=ActionAjax&mode=getTemplateBlocks",
                        data: {
                            "templateid": dataId
                        },
                        success: function (data) {
                            app.helper.hideProgress();
                            data = data.result.response;
                            var templateName = data.template[0].templatename;
                            var subject = data.template[0].subject;
                            var module = data.template[0].module;
                            var description = data.template[0].description;
                            var templateid = data.template[0].templateid;
                            $('input[name="emailtemplateid"]').val(templateid);
                            $('input[name="templatename"]').val(templateName);
                            $('input[name="subject"]').val(subject);
                            var eleModuleName = $('select[name="modulename"]');
                            eleModuleName.val(module);
                            eleModuleName.trigger('liszt:updated');
                            eleModuleName.trigger('change');
                            $('textarea[name="description"]').val(description);

                            $('.content-wrapper .email-editor-elements-sortable').html('');
                            if (data.blocks) {
                                for (var i = 0; i < data.blocks.length; i++) {
                                    _content = '';
                                    _content += '<div class="sortable-row" style="width: '+data.template[0].email_width+'px">' +
                                        '<div class="sortable-row-container">' +
                                        ' <div class="sortable-row-actions">';

                                    _content += '<div class="row-move row-action">' +
                                        '<i class="fa fa-arrows-alt"></i>' +
                                        '</div>';


                                    _content += '<div class="row-remove row-action">' +
                                        '<i class="fa fa-remove"></i>' +
                                        '</div>';


                                    _content += '<div class="row-duplicate row-action">' +
                                        '<i class="fa fa-files-o"></i>' +
                                        '</div>';


                                    _content += '<div class="row-code row-action">' +
                                        '<i class="fa fa-code"></i>' +
                                        '</div>';

                                    _content += '</div>' +

                                        '<div class="sortable-row-content" data-id=' + data.blocks[i].blockid + ' data-types=' + data.blocks[i].property + '  data-last-type=' + data.blocks[i].property.split(',')[0] + '  >' +
                                        $("<div/>").html(data.blocks[i].content).text() +
                                        '</div></div></div>';
                                    $('.content-wrapper .email-editor-elements-sortable').append(_content);
                                    if(i==data.blocks.length-1) {
                                        $('.content-wrapper').append('<div class="vteshowFullTemplate"></div>');
                                    }
                                }
                            }
                            $('.content-wrapper').css('background-color', data.template[0].bg_color);
                            $('.content-wrapper').attr('data-width',data.template[0].email_width);
                            $('.content-main').attr('data-width', data.template[0].email_width);
                            jQuery('.main').css('width', data.template[0].email_width);
                            $('#bg-color-outer').css('background-color', data.template[0].bg_color);
                            $('#bg-color-inner').css('background-color', data.template[0].bg_color_inner);
                            $('[name="bg_color_inner"]').val(data.template[0].bg_color_inner);

                        },
                        error: function (error) {
                            $('.input-error').text('Internal error');
                        }
                    });
                }else {
                    $('.content-wrapper').append('<div class="vteshowFullTemplate"></div>');
                }
            },
        });
        _emailBuilder.setAfterLoad(function(e) {
            _emailBuilder.makeSortable();
            $('.elements-db').remove();
            $('.elements-property').remove();

            setTimeout(function(){
                _emailBuilder.makeSortable();
                _emailBuilder.makeRowElements();
            },1000);
        });
    },


    registerPageLeaveEvents : function() {
        app.helper.registerLeavePageWithoutSubmit(this.getForm());
    },
    registerEvents: function(){
        var self = this;
        /* For License page - Begin */
        self.registerActivateLicenseEvent();
        self.registerValidEvent();
        /* For License page - End */
        self.registerShowEmailDesigner();
        //this.registerChangeEventForModule();
        //this.loadFields();
        self.UploadImageLocal();
        if (window.hasOwnProperty('Settings_Vtiger_Index_Js')) {
            var instance = new Settings_Vtiger_Index_Js();
            instance.registerBasicSettingsEvents();
        }
        self._super();
    }
});
var confirm_exit = function(event){
    // Cancel the event as stated by the standard.
    event.preventDefault();
    // Chrome requires returnValue to be set.
    event.returnValue = '';
    return confirm("Do you really want to close?");
}

jQuery(document).ready(function() {
    Vtiger_Index_Js.getInstance().registerEvents();
    if(app.getViewName()=='Edit'){
        // $(window).on("beforeunload", function() {
        //     return confirm("Do you really want to close?");
        // });
        window.addEventListener("beforeunload", confirm_exit);
    }
});
