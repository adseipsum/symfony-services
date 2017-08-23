$(document).ready(function() {

    if(typeof(template_page) === 'undefined')
    {
        return;
    }


    $.fn.load_template_list = function()
    {
        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "GET",
            url: "/api/template/list",
            dataType: "json",

            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');
                $('#select-template-name').empty();
                $('#select-template-name').append('<option disabled>Select template name</option>');
                for(i=0;i<data.result.values.length; i++)
                {
                    var elem = data.result.values[i];
                    $('#select-template-name').append('<option value="'+elem['id']+'">'+elem['name']+'</option>');
                }
            },
            error: function(){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error');
            }
        });
    };

    $('#button-template-new').on('click',  function(e){
        $('#input-template-count').val(0);
        $('#input-template-name').val('');
        $('#textarea-template-content').val('');
        $('#input-template-id').val('new');

        $('#button-template-save').removeClass('btn-default');
        $('#button-template-save').addClass('btn-success');
        $('#button-template-save').val('Создать');

        $('#button-template-delete').removeClass('btn-danger');
        $('#button-template-delete').addClass('btn-default');
    });


    $('#button-template-load').on('click',  function(e){
        var tplId = $('#select-template-name').val();

        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "GET",
            url: "/api/template/content/"+tplId,
            dataType: "json",
            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');

                var obj = data.result.value;

                $('#input-template-count').val(obj['count']);
                $('#input-template-name').val(obj['name']);
                $('#textarea-template-content').val(obj['template']);
                $('#input-template-id').val(obj['id']);

                $('#button-template-save').removeClass('btn-default');
                $('#button-template-save').addClass('btn-success');
                $('#button-template-delete').removeClass('btn-default');
                $('#button-template-delete').addClass('btn-danger');
                $('#button-template-save').val('Сохранить');

            },
            error: function(){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error');
            }
        });
    });


    $('#button-template-save').on('click',  function(e){
        var tplId = $('#input-template-id').val();
        var tplName = $('#input-template-name').val().trim();
        if(tplId == 'none' || tplName.length == 0)
        {
            return;
        }

        var curr = {};
        curr['id'] = tplId;
        curr['name'] = tplName;
        curr['template'] = $('#textarea-template-content').val();

        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "POST",
            url: "/api/template/content/"+tplId,
            data: JSON.stringify(curr),
            dataType: "json",
            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');

                var obj = data.result.value;

                $('#input-template-count').val(obj['count']);
                $('#input-template-name').val(obj['name']);
                $('#textarea-template-content').val(obj['template']);
                $('#input-template-id').val(obj['id']);

                $('#button-template-save').removeClass('btn-default');
                $('#button-template-save').addClass('btn-success');
                $('#button-template-delete').removeClass('btn-default');
                $('#button-template-delete').addClass('btn-danger');
                $('#button-template-save').val('Сохранить');

                $.fn.load_template_list();
            },
            error: function(){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error');
            }
        });
    });









    $.fn.load_template_list();

});