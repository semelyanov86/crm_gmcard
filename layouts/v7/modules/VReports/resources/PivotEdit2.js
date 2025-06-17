VReports_Edit3_Js("VReports_PivotEdit2_Js",{},{

    calculateValues : function(){
        var container = this.getContainer();
        this.advanceFilterInstance = VReports_AdvanceFilter_Js.getInstance(jQuery('.filterContainer:not(#conditionClone > .filterContainer)',container),false);
        //handled advanced filters saved values.
        var advfilterlist = this.advanceFilterInstance.getValues();
        jQuery('#advanced_filter').val(JSON.stringify(advfilterlist));
    },

    initialize : function(container) {
        if(typeof container == 'undefined') {
            container = jQuery('#pivot_report_step2');
        }

        if(container.is('#pivot_report_step2')) {
            this.setContainer(container);
        }else{
            this.setContainer(jQuery('#pivot_report_step2'));
        }
    },

    submit : function(){
        var thisInstance = this;
        var aDeferred = jQuery.Deferred();
        thisInstance.calculateValues();
        var form = this.getContainer();
        var formData = form.serializeFormData();
        app.helper.showProgress();
        app.request.post({data:formData}).then(
            function(error,data) {
                form.hide();
                app.helper.hideProgress();
                aDeferred.resolve(data);
            },
            function(error,err){

            }
        );
        return aDeferred.promise();
    }
});