window.OPB = window.hasOwnProperty('OPB') ? window.OPB : {};

/**
 * Select class
 * @param selector
 * @param config
 * @constructor
 */
window.OPB.Select = function (selector, config) {

    if ('string' === typeof selector) {
        selector = document.querySelector(selector);
    }

    this.init(selector, config);
}

/**
 * Initializes the select element
 * @param selector
 * @param config
 */
window.OPB.Select.prototype.init = function (selector, config) {
    var options = config ? config : {}
    var plugins = ['clear_button', 'dropdown_input', 'remove_button']

    if (!selector.attributes.hasOwnProperty('multiple')) {
        plugins.push('no_backspace_delete');
        plugins.maxItems = 1;
    }

    if (options.hasOwnProperty('remote')) {
        plugins.push('virtual_scroll');
        var remote = options.remote;
        options.labelField = config.hasOwnProperty('labelField') ? config.hasOwnProperty('labelField') : 'text';
        options.searchField = config.hasOwnProperty('searchField') ? config.hasOwnProperty('searchField') : 'text';
        options.valueField = config.hasOwnProperty('valueField') ? config.hasOwnProperty('valueField') : 'id';
        options.plugins = plugins;
        options.maxOptions = 200;
        options.firstUrl = function (query) {
            var url = remote.url;
            url += remote.url.indexOf('?') === -1 ? '?' : '&';
            return url + 'action=' + remote.action + '&_wpnonce=' + remote.nonce + '&type=' + remote.type + '&term=' + encodeURIComponent(query);
        }
        options.load = function (query, callback) {
            const url = this.getUrl(query);
            fetch(url)
                .then(response => response.json())
                .then(json => {
                    let data = json.data;
                    if (data && data.hasOwnProperty('pagination') && data.pagination.hasOwnProperty('more') && data.pagination.more) {
                        var next_page = data.pagination.current + 1;
                        var next_url = remote.url + (remote.url.indexOf('?') === -1 ? '?' : '&');
                        next_url += 'action=' + remote.action + '&_wpnonce=' + remote.nonce + '&type=' + remote.type + '&term=' + encodeURIComponent(query) + '&page=' + next_page
                        this.setNextUrl(query, next_url);
                    }
                    callback(data.results);
                }).catch((e) => {
                callback();
            });
        }
        options.render = {
            loading_more: function (data, escape) {
                return '<div class="loading-more-results py-2 d-flex align-items-center"><div class="spinner"></div> ' + 'Loading' + '</div>';
            }
        };
        delete options.remote;
    }
    this.select = new TomSelect(selector, options)
}
