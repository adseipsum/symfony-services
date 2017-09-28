"use strict";

$(document).ready(function() {

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    let template_textarea_cur_select_position = -1;

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const template_textarea_replace_select = function(before, after) {
        if (template_textarea_cur_select_position === -1) {
            return;
        }

        let start = template_textarea_cur_select_position.start;
        let end = template_textarea_cur_select_position.end;
        if (end < start) {
            [start, end] = [end, start];
        }

        const textarea = $('#textarea-template-content');
        let text = textarea.val();
        text = text.replace(/(?:\r\n|\r)/g, '\n');

        let wrap_text = '';
        if (start !== end) {
            wrap_text = text.substring(start, end);
            wrap_text += '|';
        }

        const insert_text = before + wrap_text + after;

        textarea.focus();

        /* todo undo/redo support
        if(document.createEventObject) { // http://maythesource.com/2012/06/26/simulating-keypresses-keystrokes-with-javascript-for-use-with-greesemonkey-etc/
            let event = document.createEvent('TextEvent');
            event.initTextEvent('textInput', true, true, null, insert_text);
            textarea[0].setSelectionRange(start, end);
            textarea[0].dispatchEvent(event);
        } else if (document.queryCommandSupported("insertText")) {
            textarea[0].setSelectionRange(start, end);
            document.execCommand("insertText", false, insert_text);
        } else*/ {
            text = text.substring(0, start) + insert_text + text.substring(end, text.length);
            textarea.val(text);
        }

        let new_position = start + before.length + wrap_text.length;
        $.fn.textarea_set_cursor_pos(textarea, new_position);
        template_textarea_cur_select_position = { start: new_position, end: new_position };
        $.fn.template_textarea_highlight_update(textarea);
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-edit-wrap1').click(function() {
        template_textarea_replace_select('$[', ']');
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-edit-wrap2').click(function() {
        template_textarea_replace_select('@{', '}');
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const template_textarea_check_select_changed = function(textarea) {
        const select_position = $.fn.textarea_get_select_pos(textarea);
        if (template_textarea_cur_select_position !== select_position) {
            template_textarea_cur_select_position = select_position;
        }
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $("#textarea-template-content").bind("keyup input mouseup textInput focusin", function() {
        template_textarea_check_select_changed(this);
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

});
