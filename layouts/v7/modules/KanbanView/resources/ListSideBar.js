Vtiger_ListSidebar_Js('KanbanView_ListSidebar_Js',{},{
    registerFilters: function() {
        var self = this;
        var filters = jQuery('.module-filters').not('.module-extensions');
        var scrollContainers = filters.find(".scrollContainer");
        // applying scroll to filters, tags & extensions
        jQuery.each(scrollContainers,function(key,scroll){
            var scroll = jQuery(scroll);
            var listcontentHeight = scroll.find(".list-menu-content").height();
            scroll.css("height",listcontentHeight);
            scroll.perfectScrollbar({});
        })

        this.registerFilterSeach();
        filters.on('click','.listViewFilter', function(e){
            var targetElement = jQuery(e.target);
            if(targetElement.is('.dropdown-toggle') || targetElement.closest('ul').hasClass('dropdown-menu') ) return;
            var element = jQuery(e.currentTarget);
            var el = jQuery('a[data-filter-id]',element);
            self.getParentInstance().resetData();
            self.unMarkAllFilters();
            self.unMarkAllTags();
            el.closest('li').addClass('active');
            self.getParentInstance().filterClick = true;
            self.getParentInstance().loadFilter(el.data('filter-id'), {'page' : '','source_module':jQuery('#kbSourceModule').val(),'app':app.getAppName()});
            var filtername = jQuery('a[class="filterName"]',element).text();
            jQuery('.module-action-content').find('.filter-name').html('&nbsp;&nbsp;<span class="fa fa-angle-right" aria-hidden="true"></span>').text(filtername);
        });

        jQuery('#createFilter').on('click',function(e){
            var element = jQuery(e.currentTarget);
            element.trigger('post.CreateFilter.click',{'url':element.data('url')});
        });

        filters.on('click','li.editFilter,li.duplicateFilter',function(e){
            var element = jQuery(e.currentTarget);
            if(typeof element.data('url') == "undefined") return;
            element.trigger('post.CreateFilter.click',{'url':element.data('url')});
        });

        filters.on('click','li.deleteFilter',function(e){
            var element = jQuery(e.currentTarget);
            if(typeof element.data('url') == "undefined") return;
            element.trigger('post.DeleteFilter.click',{'url':element.data('url')});
        });

        filters.on('click','li.toggleDefault',function(e){
            var element = jQuery(e.currentTarget);
            element.trigger('post.ToggleDefault.click',{'url':element.data('url')});
        });

        filters.on('post.DeletedFilter',function(e){
            var element = jQuery(e.target);
            var popoverId = element.closest('.popover').attr('id');
            var ele = jQuery('.list-group' ).find("[aria-describedby='" + popoverId + "']");
            ele.closest('.listViewFilter').remove();
            element.closest('.popover').remove();
        });

        filters.on('post.ToggleDefault.saved',function(e,params){
            var element = jQuery(e.target);
            var popoverId = element.closest('.popover').attr('id');
            var ele = jQuery('.list-group').find("[aria-describedby='" + popoverId + "']");
            if (params.isdefault === "1") {
                element.data('isDefault', true);
                var check = element.closest('.popover').find('.toggleDefault i').removeAttr('class').addClass('fa fa-check-square-o');
                var class1 = ele.closest('[rel="popover"]').removeAttr('toggleClass').attr('toggleClass', 'fa fa-check-square-o');
                element.closest('.popover').html($(".popover-content").html()).css("padding", "10px");
            }

            else {
                element.data('isDefault', false);
                var check = element.closest('.popover').find('.toggleDefault i').removeAttr('class').addClass('fa fa-square-o');
                var class1 = ele.closest('[rel="popover"]').removeAttr('toggleClass').attr('toggleClass', 'fa fa-square-o');
                element.closest('.popover').html($(".popover-content").html()).css("padding", "10px");
            }
        });

        filters.find('.toggleFilterSize').on('click',function(e){
            var currentTarget = jQuery(e.currentTarget);
            currentTarget.closest('.list-group').find('li.filterHidden').toggleClass('hide');
            if(currentTarget.closest('.list-group').find('li.filterHidden').hasClass('hide')) {
                currentTarget.html(currentTarget.data('moreText'));
            }else{
                currentTarget.html(currentTarget.data('lessText'));
            }
        })

        app.event.on('ListViewFilterLoaded', function(event, container, params) {
            // TODO - Update pagination...
        });
    },
    registerEvents : function() {
        this._super();
    }
})
