VReports_ChartEdit3_Js("VReports_PivotEdit4_Js",{},{
    calculateValues : function(){
        //handled advanced filters saved values.
        var advfilterlist = jQuery('#advanced_filter','#pivot_report_step2').val();// value from step2
        jQuery('#advanced_filter','#pivot_report_step4').val(advfilterlist);
        var selectcolumns = JSON.stringify($('#groupbyfield_columns').val());
        jQuery('[name="groupbyfield_columns"]','#pivot_report_step4').val(selectcolumns);
        var selectrows = JSON.stringify($('#groupbyfield_rows').val());
        jQuery('[name="groupbyfield_rows"]','#pivot_report_step4').val(selectrows);
        var datafields = JSON.stringify($('#datafields-pivot').val());
        jQuery('[name="datafields-pivot"]','#pivot_report_step4').val(datafields);
        var renamefields = jQuery('[name="renamedatavalue"]','#pivot_report_step3').val();
        jQuery('[name="renamedatavalue"]','#pivot_report_step4').val(renamefields);
    },
    registerEvents : function(){
        this._super();
        var thisInstance = this;
        this.calculateValues();
        $('#pivot_report_step4').find('select').each(function () {
            $(this).select2();
        });
        var arrSelectedField = JSON.parse(jQuery('[name="selected_columns_rows"]').val());
        $('#groupbyfield').find('option').each(function () {
            if($.inArray($(this).attr('value'),arrSelectedField) == -1){
                $(this).remove();
            }
        });
        var arrSelectData = JSON.parse(jQuery('[name="datafields-pivot"]').val());
        $('#pivot_report_step4').find('#datafields').find('option').each(function () {
            if($.inArray($(this).attr('value'),arrSelectData) == -1){
                $(this).remove();
            }
        });
        $('#pivot_report_step4').find('datafields').select2();
        $('#groupbyfield').select2();
        thisInstance.triggerRenameFieldsAction();

    },
    submit : function(){
        var thisInstance = this;
        var aDeferred = jQuery.Deferred();
        thisInstance.calculateValues();
        //get value field rename
        var renameDataValue = {};
        var renameValues = $('#pivot_report_step4 .rename-field-translate').find('[name="rename_field"]');
        for(var i=0; i < renameValues.length; i++) {
            var renameVal = $(renameValues[i]).val();
            var renameSelected = $(renameValues[i]).attr('data-selected');
            var renameFieldSlect = renameSelected.split(':');
            var renameField = renameFieldSlect[2]+'_'+renameFieldSlect[5];
            var renameLabel = $(renameValues[i]).attr('data-fieldlabel');
            renameDataValue[i]={fieldname : renameField.toLowerCase(),fieldlabel : renameLabel,translatedLabel:renameVal,renameSelected:renameSelected };
        }
        $('[name="renamedatavalue_chart"]').val(JSON.stringify(renameDataValue));
        //end get value field rename
        var form = this.getContainer();
        var formData = form.serializeFormData();
        app.helper.showProgress();
        app.request.post({data:formData}).then(
            function(error,data) {
                if(form.find('[name="action"]').length > 0){
                    aDeferred.resolve(data);
                }else{
                    form.hide();
                    app.helper.hideProgress();
                    aDeferred.resolve(data);
                }
            },
            function(error,err){

            }
        );
        return aDeferred.promise();
    },
});