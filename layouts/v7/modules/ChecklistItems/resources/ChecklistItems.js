/* ********************************************************************************
 * The content of this file is subject to the ChecklistItems ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

var Vtiger_ChecklistItems_Js = {

    showChecklistItems: function (url) {
        if(typeof url == 'undefined'){
            return;
        }
        var thisInstance = this;
        var source_record = app.getRecordId();
        var source_module = app.getModuleName();
        var params = app.convertUrlToDataParams(url);
        params['source_record'] = source_record;
        params['source_module'] = source_module;

        app.helper.showProgress();
        app.request.get({'data' : params}).then(
            function(err,data){
                app.helper.hideProgress();
                if(err === null) {
                    app.helper.showModal(data,{cb:function(container){
                        thisInstance.registerAddChecklist(container);
                    }});
                }
            }
        );
    },

    registerAddChecklist: function (container) {
        var thisInstance = this;
        jQuery('.checklist-name', container).unbind('click').on('click', function (event) {
            //event.preventDefault();
            //app.helper.hideModal();
            var source_record = app.getRecordId();
            var source_module = app.getModuleName();
            var checklistid = jQuery(this).data('record');
            var params = {};
            params['module'] = 'ChecklistItems';
            params['action'] = 'AddChecklistItems';
            params['checklistid'] = checklistid;
            params['source_record'] = source_record;
            params['source_module'] = source_module;

            app.helper.showProgress();
            app.request.post({'data' : params}).then(
                function(err,data){
                    if(err === null) {
                        if (data == 2 || data == 1) {
                            app.helper.hideProgress();
                            thisInstance.showChecklistDetails(params);
                        }else{
                            app.helper.hideProgress();
                        }
                    }else{
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },

    showChecklistDetails: function (actionParams) {
        app.helper.showProgress();
        var thisInstance = this;
        var params = {};
        params['module'] = 'ChecklistItems';
        params['view'] = 'ChecklistDetails';
        params['checklistid'] = actionParams['checklistid'];
        params['source_record'] = app.getRecordId();
        params['source_module'] = app.getModuleName();

        app.request.post({'data' : params}).then(
            function(err,data){
                if(err === null) {
                    app.helper.hideProgress();
                    app.helper.showPopup(data,{cb:function(container){
                        //vtUtils.applyFieldElementsView(container);
                        thisInstance.registerUpdateChecklistItemStatus(container);
                        thisInstance.registerShowCommentBox(container);
                        thisInstance.registerShowAllComment(container);
                        thisInstance.registerAddComment(container);
                        thisInstance.registerShowSelectFileBox(container);
                        thisInstance.registerAutoUploadFile(container);
                        thisInstance.registerAddDocument(container);
                        thisInstance.registerDeleteDocument(container);
                        thisInstance.registerDateTimeChange(container);
                    }});
                }else{
                    app.helper.hideProgress();
                }
            }
        );
    },

    registerDateTimeChange: function(container){
        var thisInstance = this;
        jQuery('input[name=checklist_item_date]', container).unbind('change').on('change', function (event) {
            //event.preventDefault();
            var parentElement = jQuery(this).closest('.checklist-item');
            var record = parentElement.data('record');
            var date = jQuery.trim(jQuery(this).val());
            var time = jQuery.trim(jQuery('input[name=checklist_item_time]', parentElement).val());
            if(date && time){
                thisInstance.updateDateTimeField(record, date+' '+time);
            }
        });
        jQuery('input[name=checklist_item_time]', container).unbind('change').on('change', function (event) {
            //event.preventDefault();
            var parentElement = jQuery(this).closest('.checklist-item');
            var record = parentElement.data('record');
            var time = jQuery.trim(jQuery(this).val());
            var date = jQuery.trim(jQuery('input[name=checklist_item_date]', parentElement).val());
            if(date && time){
                thisInstance.updateDateTimeField(record, date+' '+time);
            }
        });
    },

    registerUpdateChecklistItemStatus: function (container) {
        var thisInstance = this;
        jQuery('.checklist-item-status-btn', container).unbind('click').on('click', function (event) {
            //event.preventDefault();
            var btnElement = jQuery(this);
            var currStatus = btnElement.data('status');
            var itemElement = jQuery(this).closest('.checklist-item');
            var params = {};
            params['module'] = 'ChecklistItems';
            params['action'] = 'UpdateChecklistItem';
            params['mode'] = 'Status';
            params['record'] = itemElement.data('record');
            params['status'] = currStatus;

            app.helper.showProgress();
            app.request.post({'data' : params}).then(
                function(err,data){
                    app.helper.hideProgress();
                    if(err === null) {
                        if (data) {
                            var statusValue = data.status;
                            if (statusValue != currStatus) {
                                btnElement.data('status', statusValue);
                                btnElement.removeClass('checklist-item-status-icon'+currStatus);
                                btnElement.addClass('checklist-item-status-icon'+statusValue);
                                itemElement.find('input[name=checklist_item_date]').val(data.currDate);
                                itemElement.find('input[name=checklist_item_time]').val(data.currTime);
                            }
                        }
                    }
                }
            );
        });
    },

    registerShowCommentBox: function (container) {
        jQuery('.add-note', container).unbind('click').on('click', function (event) {
            //event.preventDefault();
            var note_box = jQuery(this).closest('.checklist-item-related').find('.item-note-add');
            if (note_box.css('display') == 'none') {
                note_box.css('display', 'block');
            } else {
                note_box.css('display', 'none');
            }
        });
    },

    registerShowAllComment: function (container) {
        jQuery('.show-all-notes', container).unbind('click').on('click', function (event) {
            //event.preventDefault();
            var note_box_list = jQuery(this).closest('.checklist-item-related').find('.item-note-list');

            if (note_box_list.hasClass('open')) {
                note_box_list.find('li').each(function(index){
                    if(index>0){
                        jQuery(this).slideUp();
                    }
                });
                note_box_list.removeClass('open');
            } else {
                note_box_list.find('li').each(function(index){
                    if(index>0){
                        jQuery(this).slideDown();
                    }
                });
                note_box_list.addClass('open');
            }
        });
    },

    registerShowSelectFileBox : function (container){
        jQuery('.upload-file', container).unbind('click').on('click', function (event) {
            //event.preventDefault();
            jQuery(this).closest('form').find('input[type=file]').trigger('click');
        });
    },

    registerAutoUploadFile: function (container) {
        jQuery('input[name=filename]', container).unbind('change').on('change', function (event) {
            var filePath = jQuery(this).val();
            if (filePath) {
                var form = jQuery(this).closest('form');
                form.find('input[name=notes_title]').val(filePath.replace(/.*(\/|\\)/, ''));
                form.submit();
            }
        });
    },

    registerAddDocument: function(container){
        var thisInstance = this;
        // attach handler to form's submit event
        jQuery('.checklist-upload-form', container).submit(function(e) {
            e.preventDefault();
            // submit the form
            var form = jQuery(this);
            form.ajaxSubmit({
                dataType: 'json',
                beforeSend: function(xhr) {
                    var percentVal = '0%';
                    form.find('.progress-bar').width(percentVal);
                    form.find('.sr-only').html(percentVal);
                    form.find('.progress').show();
                },
                uploadProgress: function(event, position, total, percentComplete) {
                    var percentVal = percentComplete + '%';
                    form.find('.progress-bar').width(percentVal);
                    form.find('.sr-only').html(percentVal);
                },
                success: function() {
                    var percentVal = '100%';
                    form.find('.progress-bar').width(percentVal);
                    form.find('.sr-only').html(percentVal);
                },
                complete: function(xhr, status) {
                    var data = jQuery.parseJSON(xhr.responseText);
                    var record = data.result._recordId;
                    var fileid = record + 1;
                    var title = data.result.notes_title.display_value;
                    var sourceRecord = form.find('input[name=sourceRecord]').val();
                    var newfile = '';
                    newfile += '<div style="height: 100%; width: 100%;margin: 4px; padding: 9px 10px; border: 1px solid #ddd; border-radius: 4px 4px 0 0;">';
                    newfile += '<a href="index.php?module=Documents&action=DownloadFile&record='+record+'&fileid='+fileid+'" style="border: none;">'+title;
                    newfile += '</a>'
                    newfile += '<span class="relationDelete pull-right" data-record="'+sourceRecord+'" data-related-record="'+record+'" style="cursor: pointer;"><i title="Delete" class="fa fa-trash alignMiddle"></i></span>';
                    newfile += '</div>'
                    form.find('ul').prepend('<li>'+newfile+'</li>');
                    form.find('input[type=file]').val('');
                    thisInstance.updateDateTimeField(sourceRecord, '');
                    setTimeout(function(){
                        form.find('.progress').hide();
                    }, 5000);
                }
            });
            // return false to prevent normal browser submit and page navigation
            return false;
        });

    },

    registerAddComment: function (container) {
        var thisInstance = this;
        jQuery('.add-comment', container).unbind('click').on('click', function (event) {
            //event.preventDefault();
            var comment_box = jQuery(this).closest('.item-note-box');
            var comment_content = comment_box.find('.item-note-content').val();
            if (jQuery.trim(comment_content) == '') {
                alert(app.vtranslate('Comment content is required'));
                return;
            }

            var aDeferred = jQuery.Deferred();
            var record = jQuery(this).data('record');
            var params = {};
            params['module'] = 'ChecklistItems';
            params['action'] = 'UpdateChecklistItem';
            params['checklistitemsid'] = record;
            params['mode'] = 'AddComment';
            params['comment'] = comment_content;

            app.helper.showProgress();
            app.request.post({'data' : params}).then(
                function(err,data){
                    if(err === null) {
                        if (data) {
                            comment_box.find('.item-note-list ul').prepend(data);
                            comment_box.find('.item-note-content').val('');
                            comment_box.find('.item-note-add').hide();
                            thisInstance.updateDateTimeField(record, '');
                        }
                        app.helper.hideProgress();
                    }else{
                        app.helper.hideProgress();
                    }
                }
            );
        });
    },

    registerDeleteDocument: function (container) {
        jQuery('.relationDelete', container).unbind('click').on('click', function (event) {
            //event.preventDefault();
            var element = jQuery(this);
            var params = {};
            params['module'] = 'ChecklistItems';
            params['action'] = 'RelationAjax';
            params['src_record'] = element.data('record');
            params['related_record_list'] = [element.data('related-record')];
            params['mode'] = 'deleteRelation';
            params['related_module'] = 'Documents';
            app.helper.showConfirmationBox({'message' : app.vtranslate('Do you want to delete this document?')}).then(
                function(e) {
                    app.helper.showProgress();
                    app.request.post({'data' : params}).then(
                        function(err,data){
                            app.helper.hideProgress();
                            if(err === null) {
                                if (data == 1) {
                                    element.closest('li').remove();
                                }
                            }
                        }
                    );
                },
                function(error, err){
                }
            );
        });
    },


    updateDateTimeField: function(record, datetime){
        var params = {};
        params['module'] = 'ChecklistItems';
        params['action'] = 'UpdateChecklistItem';
        params['mode'] = 'DateTime';
        params['record'] = record;
        params['datetime'] = datetime;

        app.helper.showProgress();
        app.request.post({'data' : params}).then(
            function(err,data){
                if(err === null) {
                    jQuery('#vte-checklist-details #checklist-item'+record).find('input[name=checklist_item_date]').val(data.currDate);
                    jQuery('#vte-checklist-details #checklist-item'+record).find('input[name=checklist_item_time]').val(data.currTime);
                    app.helper.hideProgress();
                }else{
                    app.helper.hideProgress();
                }
            }
        );
    },


    registerEvents: function () {
        var container = jQuery('#vte-checklist');
        //this.registerAddChecklist(container);
    }

}