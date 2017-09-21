"use strict";

$(document).ready(function() {

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $.fn.textarea_get_cursor_pos = function(input) {
        const sel = $.fn.textarea_get_select_pos(input);
        return sel.start;
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $.fn.textarea_get_select_pos = function(input) {
        if (input instanceof jQuery) {
            input = input[0];
        }
        if ("selectionStart" in input && document.activeElement === input) {
            return {
                start: input.selectionStart,
                end: input.selectionEnd
            };
        }
        else if (input.createTextRange) {
            const sel = document.selection.createRange();
            if (sel.parentElement() === input) {
                const rng = input.createTextRange();
                rng.moveToBookmark(sel.getBookmark());
                let len = 0;
                while (rng.compareEndPoints("EndToStart", rng) > 0) {
                    rng.moveEnd("character", -1);
                    len++;
                }
                rng.setEndPoint("StartToStart", input.createTextRange());
                const pos = { start: 0, end: len };
                while (rng.compareEndPoints("EndToStart", rng) > 0) {
                    rng.moveEnd("character", -1);
                    pos.start++;
                    pos.end++;
                }
                return pos;
            }
        }
        return -1;
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $.fn.textarea_set_cursor_pos = function(input, pos) {
        $.fn.textarea_set_select_pos(input, pos, pos);
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $.fn.textarea_set_select_pos = function(input, start, end) {
        if (input instanceof jQuery) {
            input = input[0];
        }
        if ("selectionStart" in input) {
            input.setSelectionRange(start, end);
        } else if (input.createTextRange) {
            const range = input.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

});
