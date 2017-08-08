var $ = require('jquery');
// JS is equivalent to the normal "bootstrap" package
// no need to set this to a variable, just require it
require('bootstrap-sass');
// require('tabcordion');
// require('jquery-querybuilder')


// or you can include specific pieces
// require('bootstrap-sass/javascripts/bootstrap/tooltip');
// require('bootstrap-sass/javascripts/bootstrap/popover');

global.data = {
    template: {
        name:'default'
    },
    dictionary: {
        data: []
    }
}




$(document).ready(function() {
    $('[data-toggle="popover"]').popover();

    $('.dialog-progress-bar').modal({
        backdrop: 'static',
        show: false
    });

    /**
     * Note: At present, using .val() on <textarea> elements strips carriage return characters
     * from the browser-reported value.
     * When this value is sent to the server via XHR, however, carriage returns are preserved
     * (or added by browsers which do not include them in the raw value).
     * A workaround for this issue can be achieved using a valHook as follows:
     */

    $.valHooks.textarea = {
        get: function( elem ) {
            return elem.value.replace( /\r?\n/g, "\r\n" );
        }
    };


    // from https://codepen.io/georgeroubie/pen/dpryjp
    $.expr[':'].containsCaseInsensitive = function (n, i, m) {
        return jQuery(n).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
    };
});

require('./spinblock.js');
require('./dictionary.js');
require('./info.js');