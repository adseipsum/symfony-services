$(document).ready(function() {

    var template_textarea_cur_cursor_position = -1;

    $.fn.highlight_template_editor = function(input) {
        var ret = [];

        if (template_textarea_cur_cursor_position !== -1) {
            var line = [];

            var begin = 0;
            {
                var bb = 0;
                for (var i = template_textarea_cur_cursor_position - 1; i >= 0; i--) {
                    var cc = input.charAt(i);
                    if (cc === '}' || cc === ']') {
                        bb++;
                    } else if (cc === '{' || cc === '[') {
                        if (bb === 0) {
                            begin = i;
                            break;
                        }
                        bb--;
                    } else if (cc === '|' && bb === 0) {
                        line.push(i);
                    }
                }
            }

            var end = input.length;
            {
                var bb = 0;
                for (var i = template_textarea_cur_cursor_position; i < input.length; i++) {
                    var cc = input.charAt(i);
                    if (cc === '{' || cc === '[') {
                        bb++;
                    } else if (cc === '}' || cc === ']') {
                        if (bb === 0) {
                            end = i;
                            break;
                        }
                        bb--;
                    } else if (cc === '|' && bb === 0) {
                        line.push(i);
                    }
                }
            }

            if (begin < end && (begin != 0 || end != input.length)) {
                var begin_end = begin + 1;
                var cc = input.charAt(begin - 1)
                if (cc === '$' || cc === '@') {
                    begin--;
                }
                ret = ret.concat([
                    {
                        highlight: [begin, begin_end],
                        className: 'symbols_current'
                    },
                    {
                        highlight: [end, end + 1],
                        className: 'symbols_current'
                    }
                ]);

                ret.push({
                    highlight: [begin_end, end],
                    className: 'symbols_between_current'
                });

                for (var i = 0; i < line.length; i++) {
                    var line_index = line[i];
                    ret.push({
                        highlight: [line_index, line_index + 1],
                        className: 'symbols_current'
                    });
                }

                // alert(JSON.stringify(ret));
            }
        }

        ret = ret.concat([
//            {
//                highlight: /[{}\[\]$@|]/gi,
//                className: 'symbols'
//            },
            {
                highlight: /\n%[^\n]*/gi,
                className: 'code'
            },
            {
                highlight: '<P/>',
                className: 'newline'
            },
            {
                highlight: '<N/>',
                className: 'newline'
            }
        ]);

        return ret;
    };

    $.fn.template_textarea_check_cursor_changed = function(textarea) {
        var cursor_position = $.fn.textarea_get_cursor_pos(textarea);
        if (template_textarea_cur_cursor_position !== cursor_position) {
            template_textarea_cur_cursor_position = cursor_position;
            $.fn.template_textarea_highlight_update(textarea);
        }
    };

    $.fn.template_textarea_highlight_update = function(textarea) {
        if (!textarea) {
            textarea = $('#textarea-template-content');
        } else {
            if (!(textarea instanceof jQuery)) {
                textarea = $(textarea);
            }
        }
        textarea.highlightWithinTextarea('update');
    };

    /*
    $('#textarea-template-content').select(function() {
        alert( "Handler for .select() called." );
    });
    */

    $("#textarea-template-content")
        .bind("keyup input mouseup textInput", function() {
            $.fn.template_textarea_check_cursor_changed(this);
        })
        .highlightWithinTextarea({
            highlight: $.fn.highlight_template_editor
        });

});
