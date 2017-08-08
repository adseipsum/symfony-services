$(document).ready(function() {

    $('#button-rawtext-save').on('click', function(e)
    {
        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        var content = $('#textarea-raw-text').val();
        content = content == undefined ? '':content;
        var request = {"data": content};
        var templateName = global.data.template.name;


        $.ajax({
            type: "POST",
            url: "/api/editor/rawtext/"+templateName,
            data: JSON.stringify(request),
            dataType: "json",

            success: function(response) {
                bar.removeClass('animate');
                dialog.modal('hide');
            },
            error: function(){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error, cannot save');
            }
        });
    });

    $('#button-rawtext-reset').on('click', function(e)
    {
        $('#textarea-raw-text').val('');
    });

});