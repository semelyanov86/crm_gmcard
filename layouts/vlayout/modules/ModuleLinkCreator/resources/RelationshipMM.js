/* ********************************************************************************
 * The content of this file is subject to the Module & Link Creator ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

jQuery(document).ready(function () {
    var sltModule1 = jQuery('#module1');
    var txtModule12 = jQuery('#txtModule12');
    var sltModule2 = jQuery("#module2");
    var txtModule21 = jQuery("#txtModule21");

    sltModule1.on("change", function () {
        var val1 = jQuery(this).val();
        if (val1 != '' && val1 != '-') {
            var sltModule1 = jQuery(this).find('option[value="' + val1 + '"]').text();
            txtModule12.val(sltModule1);
        }
        else {
            txtModule12.val('');
        }
    });

    sltModule2.on("change", function () {
        var val2 = jQuery(this).val();
        if (val2 != '' && val2 != '-') {
            var sltModule2 = jQuery(this).find('option[value="' + val2 + '"]').text();
            txtModule21.val(sltModule2);
        }
        else {
            txtModule21.val('');
        }
    });

    jQuery('[type="submit"]').on("click", function () {
        jQuery(".notices").hide();
        var loadingMessage = jQuery('.listViewLoadingMsg').text();

        var progressIndicatorElement = jQuery.progressIndicator({
            'message': loadingMessage,
            'position': 'html',
            'blockInfo': {
                'enabled': true
            }
        });

        var dataUrl = "index.php?module=" + app.getModuleName() + "&action=ActionAjax&mode=saveRelationshipMM&module1=" + sltModule1.val()
            + "&module2=" + sltModule2.val() + "&txtmodule12=" + txtModule12.val() + "&txtmodule21=" + txtModule21.val();
        AppConnector.request(dataUrl).then(
            function (data) {
                if (data.success) {
                    console.log(data);
                    var params = {
                        text: 'M:M Relationship ' + data.result.message
                    };
                    RelationshipMMJS.showNotify(params);
                    progressIndicatorElement.progressIndicator({'mode': 'hide'});

                    setTimeout(function () {
                        location.reload();
                    }, 150);
                }
            },
            function (error) {
                console.log(error);
                var params = {
                    text: app.vtranslate('JS_FAILED_MM')
                };
                RelationshipMMJS.showNotify(params);
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
        relation_type: 'M-M'
    };

    AppConnector.request(params).then(
        function (response) {
            if (response.success) {
                var data = response.result;
                var rows = '';
                var item = null;

                for (var i = 0; i < data.length; i++) {
                    item = data[i];

                    rows += '<tr class="listViewEntries" data-id="' + item.relation_id + '">';
                    rows += '<td class=" medium" nowrap="" data-column="relation_id">' + item.relation_id + '</td>';
                    rows += '<td class=" medium" nowrap="" data-column="tab">' + item.tab_tablabel + '</td>';
                    rows += '<td class=" medium" nowrap="" data-column="related_tab">' + item.related_tab_tablabel + '</td>';
                    rows += '<td nowrap="" class="medium">' +
                        '<div class="actions pull-right">' +
                        '<a href="javascript:void(0);" class="btn-remove-relations" ' +
                        'data-relation_id="' + item.relation_id + '" data-tab="' + item.tab_name + '" ' +
                        'data-related_tab="' + item.related_tab_name + '" data-relations=\'' + JSON.stringify(item.relations) + '\'>' +
                        '<i title="Delete" class="icon-trash alignMiddle"></i></a>' +
                        '</div>' +
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
            relation_type: 'M-M',
            relations: selectedRelations
        };

        AppConnector.request(params).then(
            function (response) {
                if (response.success) {
                    var params = {
                        text: app.vtranslate('JS_SUCCESS_1M')
                    };
                    RelationshipMMJS.showNotify(params);
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

var RelationshipMMJS = {
    showNotify: function (customParams) {
        var params = {
            title: app.vtranslate('JS_MESSAGE'),
            text: customParams.text,
            animation: 'show',
            type: 'info'
        };
        Vtiger_Helper_Js.showPnotify(params);
    },
};