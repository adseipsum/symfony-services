"use strict";

$(document).ready(function() {

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    let template_textarea_cur_cursor_position = -1;

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const highlight_template_editor = function(input) {
        let ret = [];

        if (template_textarea_cur_cursor_position !== -1) {
            const line = [];

            let begin = 0;
            let begin_cc = '';
            {
                let bb = 0;
                for (let i = template_textarea_cur_cursor_position - 1; i >= 0; i--) {
                    const cc = input.charAt(i);
                    if (cc === '}' || cc === ']') {
                        bb++;
                    } else if (cc === '{' || cc === '[') {
                        if (bb === 0) {
                            begin = i;
                            begin_cc = cc;
                            break;
                        }
                        bb--;
                    } else if (cc === '|' && bb === 0) {
                        line.push(i);
                    }
                }
            }

            let end = input.length;
            let end_cc = '';
            {
                let bb = 0;
                for (let i = template_textarea_cur_cursor_position; i < input.length; i++) {
                    const cc = input.charAt(i);
                    if (cc === '{' || cc === '[') {
                        bb++;
                    } else if (cc === '}' || cc === ']') {
                        if (bb === 0) {
                            end = i;
                            end_cc = cc;
                            break;
                        }
                        bb--;
                    } else if (cc === '|' && bb === 0) {
                        line.push(i);
                    }
                }
            }

            if (begin < end && (begin !== 0 || end !== input.length)) {
                const begin_end = begin + 1;
                const before_begin_cc = input.charAt(begin - 1);
                if (before_begin_cc === '$' || before_begin_cc === '@') {
                    begin--;
                }
                const class_name =
                    (
                        (begin_cc === '{' && end_cc === '}') ||
                        (begin_cc === '[' && end_cc === ']')
                    ) ? 'symbols_current' : 'symbols_current_error';
                ret = ret.concat([
                    {
                        highlight: [begin, begin_end],
                        className: class_name
                    },
                    {
                        highlight: [end, end + 1],
                        className: class_name
                    }
                ]);

                if (begin_end < end) {
                    ret.push({
                        highlight: [begin_end, end],
                        className: 'symbols_between_current'
                    });
                }

                for (let i = 0; i < line.length; i++) {
                    const line_index = line[i];
                    ret.push({
                        highlight: [line_index, line_index + 1],
                        className: 'symbols_current'
                    });
                }

                // alert(JSON.stringify(ret));
            }
        }

        if ($('#edit-template-view-all-specsymols').is(":checked")) {
            ret.push({
                highlight: /[{}\[\]$@|]/gi,
                className: 'symbols'
            });
        }

        ret = ret.concat([
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const template_textarea_check_cursor_changed = function(textarea) {
        const cursor_position = $.fn.textarea_get_cursor_pos(textarea);
        if (template_textarea_cur_cursor_position !== cursor_position) {
            template_textarea_cur_cursor_position = cursor_position;
        }
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $.fn.template_textarea_highlight_update = function(textarea) {
        if (!textarea) {
            textarea = $('#textarea-template-content');
        } else {
            if (!(textarea instanceof jQuery)) {
                textarea = $(textarea);
            }
        }
        template_textarea_check_cursor_changed(textarea);
        textarea.highlightWithinTextarea('update');
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#edit-template-view-all-specsymols').change(function() {
        $.fn.template_textarea_highlight_update();
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $("#textarea-template-content")
        .bind("keyup input mouseup textInput focusout focusin", function(textarea) {
            $.fn.template_textarea_highlight_update(textarea.currentTarget);
        })
        .highlightWithinTextarea({
            highlight: highlight_template_editor
        });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

});
