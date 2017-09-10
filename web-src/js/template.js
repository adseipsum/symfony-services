$(document).ready(function() {


    var parenthesis_state_show = true;
    var generated_template = '';


    if(typeof(template_page) === 'undefined')
    {
        return;
    }

    $.fn.toggle_parenthesiss_state = function()
    {
        parenthesis_state_show = !parenthesis_state_show;

        if(parenthesis_state_show == true)
        {
            $('#button-template-toggle-parenthesis').val('Hide (( ))');
        }
        else {
            $('#button-template-toggle-parenthesis').val('Show (( ))');
        }

        $.fn.render_generated_template();
    };

    $.fn.render_generated_template = function()
    {
        if(parenthesis_state_show == true)
        {
            $('#textarea-template-generator').val(generated_template);
        }
        else {
            var parsed = generated_template.replaceAll('((','').replaceAll('))','');
            $('#textarea-template-generator').val(parsed);
        }
    };


    $.fn.load_template_list = function(selectedTemplateId)
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
                $('#select-template-name').append('<option value="0">Select template name</option>');
                for(i=0;i<data.result.values.length; i++)
                {
                    var elem = data.result.values[i];
                    $('#select-template-name').append('<option value="'+elem['id']+'">'+elem['name']+'</option>');
                }
                if (selectedTemplateId) {
                    $('#select-template-name option').removeAttr('selected').filter('[value=' + selectedTemplateId + ']').attr('selected', true);
                }
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error: ' + errorMsg);
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
        $('#button-template-delete').removeData('toggle');
        $('#button-template-delete').removeData('target');

        $('#button-template-generate').addClass('btn-default');
        $('#button-template-generate').removeClass('btn-info');
        $('#button-template-minus').addClass('btn-default');
        $('#button-template-minus').removeClass('btn-danger');
        $('#button-template-plus').addClass('btn-default');
        $('#button-template-plus').removeClass('btn-success');

        $('#generator-error-container').empty();
        $('#textarea-template-generator').val('');
        generated_template = '';
    });



    $('#select-template-name').on('change',  function(e) {
        var tplId = $('#select-template-name').val();
        var tplIdSelected = $('#input-template-id').val();

        if(tplId == 0 || tplId == tplIdSelected)
        {
            return; // do nothing
        }
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
                $('#button-template-delete').data('toggle','modal');
                $('#button-template-delete').data('target','#dialog-template-confirm-delete');

                $('#button-template-save').val('Сохранить');


                console.log( $('#button-template-delete').data());

                $('#button-template-generate').removeClass('btn-default');
                $('#button-template-generate').addClass('btn-info');
                $('#button-template-minus').removeClass('btn-default');
                $('#button-template-minus').addClass('btn-danger');
                $('#button-template-plus').removeClass('btn-default');
                $('#button-template-plus').addClass('btn-success');
                $('#generator-error-container').empty();
                $('#textarea-template-generator').val('');
                generated_template = '';
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error: ' + errorMsg);
            }
        });
    });
/*
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

                $('#button-template-generate').removeClass('btn-default');
                $('#button-template-generate').addClass('btn-info');
                $('#button-template-minus').removeClass('btn-default');
                $('#button-template-minus').addClass('btn-danger');
                $('#button-template-plus').removeClass('btn-default');
                $('#button-template-plus').addClass('btn-success');
                $('#generator-error-container').empty();
                $('#textarea-template-generator').val('');
                generated_template = '';

            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error: ' + errorMsg);
            }
        });
    });
*/

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
            url: "/api/template/save/"+tplId,
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
                $('#button-template-delete').removeClass('.disabled');
                $('#button-template-save').val('Сохранить');

                $('#button-template-generate').removeClass('btn-default');
                $('#button-template-generate').addClass('btn-info');
                $('#button-template-minus').removeClass('btn-default');
                $('#button-template-minus').addClass('btn-danger');
                $('#button-template-plus').removeClass('btn-default');
                $('#button-template-plus').addClass('btn-success');

                $.fn.load_template_list(obj['id']);
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error: ' + errorMsg);
            }
        });
    });


    $('#button-template-plus').on('click',  function(e) {
        var tplId = $('#input-template-id').val();
        if (tplId == 'none' || tplId == 'new') {
            return;
        }

        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "POST",
            url: "/api/template/plus/"+tplId,
            dataType: "json",
            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');

                var obj = data.result.value;
                $('#input-template-count').val(obj['count']);
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error: ' + errorMsg);
            }
        });
    });

    $('#button-template-minus').on('click',  function(e) {
        var tplId = $('#input-template-id').val();
        if (tplId == 'none' || tplId == 'new') {
            return;
        }

        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "POST",
            url: "/api/template/minus/"+tplId,
            dataType: "json",
            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');

                var obj = data.result.value;
                $('#input-template-count').val(obj['count']);
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error: ' + errorMsg);
            }
        });

    });


    $('#button-template-generate').on('click',  function(e) {
        var tplId = $('#input-template-id').val();
        if (tplId == 'none' || tplId == 'new') {
            return;
        }

        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');


        var param = {};
        var drugName = $('#input-template-drug-name').val().trim();

        if(drugName.length > 0)
        {
            param['drugName'] = drugName;
        }

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "POST",
            url: "/api/template/generate/"+tplId,
            data: JSON.stringify(param),
            dataType: "json",
            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');

                if(data.status.code == 200) {
                    if (data.result.value.validation_status == true) {
                        $('#generator-error-container').empty();
                        $('#a-tab-generator').trigger('click');
                        generated_template = data.result.value.generated;

                        console.log(generated_template);
                        $.fn.render_generated_template();
                    }
                    else {
                        // Error div content
                        $('#generator-error-container').empty();
                        var startline = data.result.value.start_line;
                        for (i = 0; i < data.result.value.validation_lines.length; i++) {
                            var line = data.result.value.validation_lines[i];
                            var linenum = line.linenum;
                            var text = '(' + linenum + ') ' + line.text;
                            if (line.is_valid == true) {
                                $('#generator-error-container').append('<div class="line-ok">' + text + '</div>');
                            }
                            else {
                                $('#generator-error-container').append('<div class="line-error">' + text + '</div>');
                            }
                        }

                        $('#textarea-error-console').val(data.result.value.validation_text);

                        $('#a-tab-error').trigger('click');
                    }
                }
                else {
                    $('#textarea-error-console').val('Server respond with error:'+data.status.code);
                }

            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error: ' + errorMsg);
            }
        });

    });


    $('#button-template-toggle-parenthesis').on('click',  function(e) {
        $.fn.toggle_parenthesiss_state();
    });

    /*
    $('#button-template-delete').on('click',  function(e) {

    });
    */


    $('#dialog-template-confirm-delete').on('show.bs.modal', function(e) {
        var tplId = $('#input-template-id').val();
        var tplName = $('#select-template-name option:selected').text();

        if (tplId == 'none' || tplId == 'new') {
            e.preventDefault();
            return;
        }

        var data = $(e.relatedTarget).data();
        $('.title', this).text(tplName);
        $('.btn-ok', this).data('recordId', tplId);
    });

    $('#dialog-dict-confirm-delete').on('click', '.btn-ok', function(e) {
        var $modalDiv = $(e.delegateTarget);
        var id = $(this).data('recordId');
        $('#'+id).remove();
        $modalDiv.modal('hide')
    });



    $.fn.load_template_list();

});