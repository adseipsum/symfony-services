var $ = require('jquery');
// JS is equivalent to the normal "bootstrap" package
// no need to set this to a variable, just require it
require('bootstrap-sass');
// require('tabcordion');
// require('jquery-querybuilder')


// or you can include specific pieces
// require('bootstrap-sass/javascripts/bootstrap/tooltip');
// require('bootstrap-sass/javascripts/bootstrap/popover');

require('./spinblock.js');
require('./dictionary.js');

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


    // from https://codepen.io/georgeroubie/pen/dpryjp
    $.expr[':'].containsCaseInsensitive = function (n, i, m) {
        return jQuery(n).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
    };



});