/* ********************************************************************************
 * The content of this file is subject to the Module & Link Creator ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

jQuery(document).ready(function () {
    var sltModule1 = jQuery("#module1");
    var sltModule2 = jQuery("#module2");
    var sltBlock = jQuery('#block');
    var txtFieldLabel = jQuery("#field_label");
    // var sltAddRelated = jQuery("#add_related");
    var txtRelatedListLabel = jQuery("#related_list_label");
    var errorNotice = jQuery("#error_notice");
    // var successMessage = jQuery("#success_message");
    // var duplicateError = jQuery("#duplicate_error");
    // var fieldAlreadyThere = jQuery("#field-already-there");
    var actionSelect = jQuery("#action_select");
    var actionAdd = jQuery("#action_add");
    var actionCreateRL = jQuery('[name="relationshipOneNone"]');


    jQuery(document).on('change', '#module1', {}, function () {
        var val1 = jQuery(this).val();
        if (val1 != '' && val1 != '-') {
            var lblfield = jQuery(this).find('option[value="' + val1 + '"]').text();
            txtRelatedListLabel.val(lblfield);
        }
        else {
            txtRelatedListLabel.val('');
        }
        var module1 = jQuery(this).val();

        sltBlock.find('option')
            .remove().end()
            .append('<option value="--">' + app.vtranslate('LBL_SELECT') + '</option>')
            .val('--').trigger('change');

        errorNotice.hide();
        var loadingMessage = jQuery('.listViewLoadingMsg').text();

        var progressIndicatorElement = jQuery.progressIndicator({
            'message': loadingMessage,
            'position': 'html',
            'blockInfo': {
                'enabled': true
            }
        });

        var dataUrl = "index.php?module=" + app.getModuleName() + "&action=ActionAjax&mode=getBlocks&module1=" + module1;
        AppConnector.request(dataUrl).then(
            function (data) {
                if (data.success) {
                    var result = data.result;
                    if (result.result == 'ok') {
                        jQuery.each(result.options, function (i, item) {
                            var o = new Option(item, i);
                            jQuery(o).html(item);
                            sltBlock.append(o);
                        });
                    } else {
                        errorNotice.append(result.message).show();
                    }
                }
            },
            function (error) {
                console.log(error)
            }
        );

        progressIndicatorElement.progressIndicator({
            'mode': 'hide'
        });
    });

    jQuery(document).on('change', '#module2', {}, function () {
        var val = jQuery(this).val();
        if (val != '' && val != '-') {
            var lblfield = jQuery(this).find('option[value="' + val + '"]').text();
            txtFieldLabel.val(lblfield);
        }
        else {
            txtFieldLabel.val('');
        }
        // sltAddRelated.find('option')
        //     .remove().end()
        //     .append('<option value="--">' + app.vtranslate('LBL_SELECT') + '</option>')
        //     .val('--').trigger('change');
        // sltAddRelated.append('<option value="new">' + app.vtranslate('LBL_ADD_NEW') + '</option>');
        // sltAddRelated.append('<option value="none">' + app.vtranslate('LBL_NONE') + '</option>');

        errorNotice.hide();
        var loadingMessage = jQuery('.listViewLoadingMsg').text();

        var progressIndicatorElement = jQuery.progressIndicator({
            'message': loadingMessage,
            'position': 'html',
            'blockInfo': {
                'enabled': true
            }
        });

        progressIndicatorElement.progressIndicator({
            'mode': 'hide'
        });
    });

    jQuery(document).on('click', '[type="submit"]', {}, function () {
        var boleanActionSelect = actionSelect.is(':checked');
        var boleanActionAdd = actionAdd.is(':checked');
        var boleanActionCreateRL = actionCreateRL.val();
        jQuery(".notices").hide();
        var loadingMessage = jQuery('.listViewLoadingMsg').text();

        var progressIndicatorElement = jQuery.progressIndicator({
            'message': loadingMessage,
            'position': 'html',
            'blockInfo': {
                'enabled': true
            }
        });

        var dataUrl = "index.php?module=" + app.getModuleName() + "&action=ActionAjax&mode=saveRelatedFields&module1=" + sltModule1.val()
            + "&module2=" + sltModule2.val() + "&field_label=" + txtFieldLabel.val() + "&block=" + sltBlock.val() + "&related_list_label=" + txtRelatedListLabel.val() + "&action_select=" + boleanActionSelect + "&action_add=" + boleanActionAdd+ "&not_related_list=" + boleanActionCreateRL;
        AppConnector.request(dataUrl).then(
            function (data) {
                if (data.success) {
                    var response = data.result;

                    if (response.result == 'ok') {
                        var params = {
                            text: response.message
                        };
                        relatedFiledJS.showNotify(params);
                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                        setTimeout(function () {
                            location.reload();
                        }, 150);

                    } else {
                        var params = {
                            text: response.message
                        };
                        relatedFiledJS.showNotify(params);
                        progressIndicatorElement.progressIndicator({'mode': 'hide'});
                    }
                }
            },
            function (error) {
                console.log(error);
                var params = {
                    text: 'Create Related Field' + ' Failed'
                };
                relatedFiledJS.showNotify(params);
                progressIndicatorElement.progressIndicator({'mode': 'hide'});

            }
        );
    });

    var tableRelations = jQuery('#table-relations');
    var tableRelationsBody = tableRelations.find('tbody');

    // get all relations
    var params = {
        module: app.getModuleName(),
        action: 'ActionAjax',
        mode: 'getRelations',
        relation_type: '1-M'
    };

    AppConnector.request(params).then(
        function (response) {
            if (response.success) {
                var data = response.result;
                var rows = '';
                var item = null;

                for (var i = 0; i < data.length; i++) {
                    item = data[i];
                    var fieldname = item.fieldname;
                    rows += '<tr class="listViewEntries" data-id="' + item.relation_id + '">';
                    rows += '<td class=" medium" nowrap="" data-column="relation_id">' + item.relation_id + '</td>';
                    rows += '<td class=" medium" nowrap="" data-column="tab">' + item.tab_tablabel + '</td>';
                    rows += '<td class=" medium" nowrap="" data-column="related_tab">' + item.related_tab_tablabel + '</td>';
                    rows += '<td nowrap="" class="medium">' +
                        '<div class="actions pull-right">';
                    if (fieldname != null && fieldname.indexOf('cf_') != -1){
                        rows += '<a href="javascript:void(0);" class="btn-remove-relations" ' +
                            'data-relation_id="' + item.relation_id + '" data-tab="' + item.tab_name + '" ' +
                            'data-related_tab="' + item.related_tab_name + '" data-relations=\'' + JSON.stringify(item.relations) + '\'>' +
                            '<i title="Delete" class="icon-trash alignMiddle"></i></a>';
                    }
                    rows += '</div>' +
                        '</td>';
                    rows += '</tr>';
                }

                tableRelationsBody.html(rows);
            }
        },
        function (error) {
            console.log(error);
        }
    );



    var tableRelationsOneNone = jQuery('#table-oneNone');
    var tblBodyOneNone = tableRelationsOneNone.find('tbody');
    // get all relation 1:None
    var params = {
        module: app.getModuleName(),
        action: 'ActionAjax',
        mode: 'getRelations',
        relation_type: '1-None'
    };

    AppConnector.request(params).then(
        function (response) {
            if (response.success) {
                var data = response.result;
                var rows = '';
                var item = null;

                for (var i = 0; i < data.length; i++) {
                    item = data[i];

                    rows += '<tr class="listViewEntries" data-id="' + item.fieldid + '">';
                    rows += '<td class=" medium" nowrap="" data-column="relation_id">' + item.fieldid + '</td>';
                    rows += '<td class=" medium" nowrap="" data-column="relation_id">' + item.fieldlabel + '</td>';
                    rows += '<td class=" medium" nowrap="" data-column="tab">' + item.module1 + '</td>';
                    rows += '<td class=" medium" nowrap="" data-column="related_tab">' + item.module2 + '</td>';
                    rows += '<td nowrap="" class="medium">' +
                        '<div class="actions pull-left">' +
                        '<a href="javascript:void(0);" disabled="disabled" ' +
                        'class="hover-tooltip btn-remove-oneNone" data-toggle="tooltip" data-placement="top" ' +
                        'data-html="true" '+
                        'title=\'' + app.vtranslate('JS_LBL_DELETE_ONE_NONE') + '\'' +
                        'data-field_id="' + item.fieldid + '">' +
                        '<i title="Delete" class="icon-trash alignMiddle"></i></a>' +
                        '</div>' +
                        '</td>';
                    rows += '</tr>';
                }

                tblBodyOneNone.html(rows);
                $('.hover-tooltip').tooltip();
            }
        },
        function (error) {
            console.log(error);
        }
    );
    // Register remove relations
    jQuery(document).on('click', '.btn-remove-relations', function () {
        var focus = jQuery(this);

        var result = confirm(app.vtranslate('Do you want to delete?'));
        if (!result) {
            // Dismiss
            return;
        }

        var loadingMessage = jQuery('.listViewLoadingMsg').text();
        var progressIndicatorElement = jQuery.progressIndicator({
            'message': loadingMessage,
            'position': 'html',
            'blockInfo': {
                'enabled': true
            }
        });
        var selectedRelations = focus.data('relations');
        var params = {
            module: app.getModuleName(),
            action: 'ActionAjax',
            mode: 'deleteRelations',
            relation_type: '1-M',
            relations: selectedRelations
        };

        AppConnector.request(params).then(
            function (response) {
                if (response.success) {
                    var params = {
                        text: app.vtranslate('JS_SUCCESS_1M')
                    };
                    relatedFiledJS.showNotify(params);
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});

                    setTimeout(function () {
                        location.reload();
                    }, 150);
                }
            },
            function (error) {
                console.log(error);
            }
        );
    });

    // Register remove relations one none
    jQuery(document).on('click', '.btn-remove-oneNone', function () {
        var focus = jQuery(this);

        $("<div></div>").html(app.vtranslate('JS_LBL_DELETE_ONE_NONE')).dialog({
            title: 'Alert',
            resizable: false,
            modal: true,
            buttons: {
                "OK": function () {
                    $(this).dialog("close");
                }
            },
        });
        return;

        var result = confirm(app.vtranslate('Do you want to delete?'));
        if (!result) {
            // Dismiss
            return;
        }

        var loadingMessage = jQuery('.listViewLoadingMsg').text();
        var progressIndicatorElement = jQuery.progressIndicator({
            'message': loadingMessage,
            'position': 'html',
            'blockInfo': {
                'enabled': true
            }
        });
        var selectedFieldId = focus.data('field_id');
        console.log(selectedFieldId, selectedFieldId);
        var params = {
            module: app.getModuleName(),
            action: 'ActionAjax',
            mode: 'deleteRelations',
            relation_type: '1-None',
            fieldid: selectedFieldId
        };

        AppConnector.request(params).then(
            function (response) {
                if (response.success) {
                    var params = {
                        text: app.vtranslate('JS_SUCCESS_1NONE')
                    };
                    relatedFiledJS.showNotify(params);
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});

                    setTimeout(function () {
                        location.reload();
                    }, 150);
                }
            },
            function (error) {
                console.log(error);
            }
        );
    });

});

var relatedFiledJS = {
    showNotify: function (customParams) {
        var params = {
            title: app.vtranslate('JS_MESSAGE'),
            text: customParams.text,
            animation: 'show',
            type: 'info'
        };
        Vtiger_Helper_Js.showPnotify(params);
    }
};
