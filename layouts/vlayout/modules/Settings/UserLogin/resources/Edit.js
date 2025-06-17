/* * *******************************************************************************
 * The content of this file is subject to the VTE Custom User Login Page ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

var Edit_UserLogin_Js = {


    registerRemoveImage: function (container) {
        jQuery('input.remove', container).on('click', function (event) {
            event.preventDefault();
            var img_box = $(this).closest('.img_uploaded')
            img_box.remove();
        });
    },

    registerSaveForm: function (container) {
        jQuery('.saveButton', container).on('click', function (event) {
            event.preventDefault();
            container.find('input[name=action]').val('Save');
            container.submit();
        });
    },

    registerUploadImage: function (container) {
        var thisInstance = this;
        jQuery('input[type=file]', container).on('change', function (event) {
            event.preventDefault();
            var element = $(this);
            var val = $(this).val().toLowerCase();
            var regex = new RegExp("(.*?)\.(jpg|png|gif)$");
            if (!(regex.test(val))) {
                $(this).val('');
                var params = {};
                params['text']=app.vtranslate('IMG_TYPE_FORMAT_WRONG_MSG');
                Vtiger_Helper_Js.showMessage(params);
                return;
            }
            var tdElement = $(this).closest('td');
            var mode = tdElement.find('.mode').val();
            var progress = tdElement.find('.progress');
            var bar = tdElement.find('.bar');
            var percent = tdElement.find('.percent');
            var list_img = tdElement.find('.list_image');
            progress.show();

            container.ajaxSubmit({
                'url': 'index.php?module=UserLogin&parent=Settings&action=Image&mode=' + mode,
                'type': 'POST',
                beforeSend: function () {
                    var percentVal = '0%';
                    bar.width(percentVal);
                    percent.html(percentVal);
                },
                uploadProgress: function (event, position, total, percentComplete) {
                    var percentVal = percentComplete + '%';
                    bar.width(percentVal);
                    percent.html(percentVal);
                },
                complete: function (xhr) {
                    var response = $.parseJSON(xhr.responseText);
                    element.val('');
                    if(mode=='logo'){
                        list_img.html(response.result);
                    }else{
                        list_img.append(response.result);
                    }
                    thisInstance.registerRemoveImage(container);
                    setTimeout(function () {
                        progress.hide();
                    }, 5000);
                }
            });
        });
    },


    registerEvents: function () {
        var container = $('#UserLoginForm');
        this.registerUploadImage(container);
        this.registerRemoveImage(container);
        this.registerSaveForm(container);
    }

}

jQuery(document).ready(function () {
    Edit_UserLogin_Js.registerEvents();
});