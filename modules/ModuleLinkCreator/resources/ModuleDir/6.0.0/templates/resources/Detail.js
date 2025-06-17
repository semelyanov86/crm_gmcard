Vtiger_Detail_Js("<modulename>_Detail_Js",{},{
    /**
     * Function to register event for activity widget for adding
     * event and task from the widget
     */
    registerEventForActivityWidget : function(){
        var thisInstance = this;

        /*
         * Register click event for add button in Related Activities widget
         */
        jQuery('.createActivity').on('click', function(e){
            var referenceModuleName = "Calendar";
            var quickCreateNode = jQuery('#quickCreateModules').find('[data-name="'+ referenceModuleName +'"]');
            var recordId = thisInstance.getRecordId();
            var module = app.getModuleName();
            var element = jQuery(e.currentTarget);

            if(quickCreateNode.length <= 0) {
                Vtiger_Helper_Js.showPnotify(app.vtranslate('JS_NO_CREATE_OR_NOT_QUICK_CREATE_ENABLED'))
            }
            var fieldName = 'parent_id';

            var customParams = {};
            customParams[fieldName] = recordId;

            var fullFormUrl = element.data('url');
            var preQuickCreateSave = function(data){
                thisInstance.addElementsToQuickCreateForCreatingRelation(data,module,recordId);

                var taskGoToFullFormButton = data.find('[class^="CalendarQuikcCreateContents"]').find('#goToFullForm');
                var eventsGoToFullFormButton = data.find('[class^="EventsQuikcCreateContents"]').find('#goToFullForm');
                var taskFullFormUrl = taskGoToFullFormButton.data('editViewUrl')+"&"+fullFormUrl;
                var eventsFullFormUrl = eventsGoToFullFormButton.data('editViewUrl')+"&"+fullFormUrl;
                taskGoToFullFormButton.data('editViewUrl',taskFullFormUrl);
                eventsGoToFullFormButton.data('editViewUrl',eventsFullFormUrl);
            }

            var callbackFunction = function() {
                var params = {};
                params['record'] = recordId;
                params['view'] = 'Detail';
                params['module'] = module;
                params['mode'] = 'getActivities';

                AppConnector.request(params).then(
                    function(data) {
                        var activitiesWidget = jQuery('#relatedActivities');
                        activitiesWidget.html(data);
                        app.changeSelectElementView(activitiesWidget);
                        thisInstance.registerEventForActivityWidget();
                    }
                );

                var summaryViewContainer = thisInstance.getContentHolder();
                var updatesWidget = summaryViewContainer.find("[data-name='LBL_UPDATES']");
                thisInstance.loadWidget(updatesWidget);
            }

            var QuickCreateParams = {};
            QuickCreateParams['callbackPostShown'] = preQuickCreateSave;
            QuickCreateParams['callbackFunction'] = callbackFunction;
            QuickCreateParams['data'] = customParams;
            QuickCreateParams['noCache'] = false;
            quickCreateNode.trigger('click', QuickCreateParams);
        });
    },
});

