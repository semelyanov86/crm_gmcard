/* ********************************************************************************
 * The content of this file is subject to the Module & Link Creator ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C) VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */

var ModuleLinkCreatorUtils = {

    queryString: function () {
        // This function is anonymous, is executed immediately and
        // the return value is assigned to QueryString!
        var query_string = {};
        var query = window.location.search.substring(1);
        var vars = query.split('&');
        for (var i = 0; i < vars.length; i++) {
            var pair = vars[i].split('=');
            // If first entry with this name
            if (typeof query_string[pair[0]] === 'undefined') {
                query_string[pair[0]] = decodeURIComponent(pair[1]);
                // If second entry with this name
            } else if (typeof query_string[pair[0]] === 'string') {
                query_string[pair[0]] = [query_string[pair[0]], decodeURIComponent(pair[1])];
                // If third or later entry with this name
            } else {
                query_string[pair[0]].push(decodeURIComponent(pair[1]));
            }
        }
        return query_string;
    },

    /**
     * Use encodeURIComponent to encode characters outside of the Latin1 range
     * @link http://stackoverflow.com/questions/23223718/failed-to-execute-btoa-on-window-the-string-to-be-encoded-contains-characte
     * @param string
     * @returns {string}
     */
    base64Encode: function (string) {
        if (typeof string === 'undefined' || string === null) {
            return '';
        }

        return window.btoa(unescape(encodeURIComponent(string)));
    },

    /**
     * Use encodeURIComponent to encode characters outside of the Latin1 range
     * @link http://stackoverflow.com/questions/23223718/failed-to-execute-btoa-on-window-the-string-to-be-encoded-contains-characte
     * @param string
     * @returns {string}
     */
    base64Decode: function (string) {
        if (typeof string === 'undefined' || string === null) {
            return '';
        }

        return decodeURIComponent(escape(window.atob(string)));
    },

    /**
     * @param str
     * @param find
     * @param replace
     * @returns {*}
     */
    replaceAll: function (str, find, replace) {
        return str.replace(new RegExp(find, 'g'), replace);
    },

    /**
     * @param {Array|Object} items
     * @param {string} order
     * @param {string} by
     * @returns {Array}
     */
    sortByString: function (items, order, by) {
        if (order == AppConstants.ORDER.ASC) {
            items.sort(function (a, b) {
                if (a[by] < b[by])
                    return -1;
                if (a[by] > b[by])
                    return 1;
                return 0;
            });
        }
        else if (order == AppConstants.ORDER.DESC) {
            items.sort(function (a, b) {
                if (a[by] < b[by])
                    return 1;
                if (a[by] > b[by])
                    return -1;
                return 0;
            });
        }

        return items;
    },

    /**
     * @param {Array} items
     * @param {string} order
     * @param {number} by
     * @returns {Array}
     */
    sortByNumber: function (items, order, by) {
        if (order == AppConstants.ORDER.ASC) {
            items.sort(function (a, b) {
                return a[by] - b[by];
            });
        }
        else if (order == AppConstants.ORDER.DESC) {
            items.sort(function (a, b) {
                return b[by] - a[by];
            });
        }

        return items;
    },

    getThemeSettings: function () {
        var topMenus = $('#topMenus');
        var backgroundColor = '#ffffff';
        var color = '#000000';

        var navbar = topMenus.find('.navbar-inner');
        if (navbar.length > 0) {
            backgroundColor = navbar.css('background-color');
        }

        var firstTab = navbar.find('.menuBar .tabs a').first();

        if (firstTab.length > 0) {
            color = firstTab.css('color');
        }

        return {
            'background-color': backgroundColor,
            'color': color
        };
    },

    /**
     * @link http://stackoverflow.com/questions/20864893/javascript-replace-all-non-alpha-numeric-characters-new-lines-and-multiple-whi
     * @param text
     * @returns {*}
     */
    replaceAllNonAlphaNumericCharacters: function (text, by) {
        text = text.replace(/\W+/g, by);
        return text.replace(/_/g, by);
    }

};