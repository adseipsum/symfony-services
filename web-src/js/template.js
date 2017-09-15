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

    $.fn.render_generate_info = function(generate_info)
    {
        if (!generate_info) {
            $('#generate_info_text').text('');
        } else {
            var generate_info_text = JSON.stringify(generate_info, null, '    ');
            $('#generate_info_text').text(generate_info_text);
        }
    };

    $.fn.load_generated_text_list = function(selectedTemplateId)
    {
        if (!selectedTemplateId) {
            $('#generated-texts').empty();
        }

        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "GET",
            url: "/api/generated-text/list/" + selectedTemplateId,
            dataType: "json",

            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');
                var generated_texts = $('#generated-texts')
                generated_texts.empty();
                for(var i=0;i<data.result.values.length; i++)
                {
                    var elem = data.result.values[i];

                    var generateTextId = elem['id'];
                    generated_texts.append($('<div><span>id: ' + generateTextId + '</span></div>'));

                    var textArea = $('<textarea rows="5" readonly class="form-control" style="resize: vertical;min-width: 100%; margin-bottom: 5px"/>');
                    textArea.text(elem['text']);
                    generated_texts.append(textArea);

                    var minusBtn = $('<input id="button-template-minus-' + generateTextId + '" type="button" class="btn button-template-minus btn-default" value="Удалить текст"/>');
                    (function(minusBtn, generateTextId) {
                        minusBtn.on('click',  function(e) {
                            var resultConfirm = confirm("Вы уверены что данный текст нужно удалить безвозвратно?");
                            if (!resultConfirm) {
                                return;
                            }

                            var dialog = $('.dialog-progress-bar');
                            var bar = dialog.find('.progress-bar');

                            dialog.modal('show');
                            bar.addClass('animate');

                            $.ajax({
                                type: "GET",
                                url: "/api/generated-text/remove/"+generateTextId,
                                dataType: "json",
                                success: function(data) {
                                    bar.removeClass('animate');
                                    dialog.modal('hide');
                                    $.fn.load_generated_text_list(selectedTemplateId);
                                },
                                error: function(errorMsg){
                                    bar.removeClass('animate');
                                    dialog.modal('hide');
                                    alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
                                }
                            });
                        })
                    })(minusBtn, generateTextId);
                    generated_texts.append(minusBtn);

                    generated_texts.append($('<hr/>'));
                }
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });
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
                var selectTemplateName = $('#select-template-name');
                selectTemplateName.empty();
                selectTemplateName.append('<option value="0">Select template name</option>');
                for(var i=0;i<data.result.values.length; i++) {
                    var elem = data.result.values[i];
                    selectTemplateName.append('<option value="' + elem['id'] + '">' + elem['name'] + '</option>');
                }
                if (!selectedTemplateId) {
                    var tplId = $('#input-template-id').val();
                    if(!(tplId == 'none' || tplName.length == 0)) {
                        selectedTemplateId = tplId;
                    }
                }
                if (selectedTemplateId) {
                    $('#select-template-name option').removeAttr('selected').filter('[value=' + selectedTemplateId + ']').attr('selected', true);
                }
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });
    };

    $.fn.clear_content = function() {
        $('#input-template-name').val('');
        $('#textarea-template-content').val('');
        $('#input-template-id').val('new');

        $('#generate_info_text').text('');

        $('#generated-texts').empty();

        $('#button-template-save').removeClass('btn-default');
        $('#button-template-save').addClass('btn-success');
        $('#button-template-save').val('Создать');

        $('#button-template-delete').removeClass('btn-danger');
        $('#button-template-delete').addClass('btn-default');
        $('#button-template-delete').removeData('toggle');
        $('#button-template-delete').removeData('target');

        $('#button-template-generate').addClass('btn-default');
        $('#button-template-generate').removeClass('btn-info');
        $('#button-template-plus').addClass('btn-default');
        $('#button-template-plus').removeClass('btn-success');

        $('#generator-error-container').empty();
        $('#textarea-template-generator').val('');
        generated_template = '';
    }


    $('#button-template-new').on('click',  function(e){
        $.fn.clear_content();
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
                $.fn.clear_content();

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

                // console.log( $('#button-template-delete').data());

                $('#button-template-generate').removeClass('btn-default');
                $('#button-template-generate').addClass('btn-info');

                $('#button-template-plus').removeClass('btn-default');
                $('#button-template-plus').addClass('btn-success');
                $('#generator-error-container').empty();
                $('#textarea-template-generator').val('');
                generated_template = '';
                $.fn.load_generated_text_list(tplId);
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
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
            url: "/api/template/save/"+tplId,
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify(curr),
            dataType: "json",
            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');

                var obj = data.result.value;

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
                $('#button-template-save').removeClass('btn-default');
                $('#button-template-save').addClass('btn-success');


                $('#button-template-plus').removeClass('btn-default');
                $('#button-template-plus').addClass('btn-success');

                $.fn.load_template_list(obj['id']);
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });
    });


    $('#button-template-plus').on('click',  function(e) {
        var tplId = $('#input-template-id').val();
        if (tplId == 'none' || tplId == 'new') {
            return;
        }

        var parsedText = $('#textarea-template-generator').val();
        parsedText = parsedText.replaceAll('((','').replaceAll('))','');
        if (parsedText.length === 0 || !parsedText.trim()) {
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
            contentType: "application/json; charset=utf-8",
            data: JSON.stringify({
                'text': parsedText
            }),
            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');
                $.fn.load_generated_text_list(tplId);
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });
    });


    $('#button-template-recalc-similarity').on('click',  function(e) {
        var tplId = $('#input-template-id').val();
        if (tplId == 'none' || tplId == 'new') {
            return;
        }

        var parsedText = $('#textarea-template-generator').val();
        parsedText = parsedText.replaceAll('((','').replaceAll('))','');
        if (parsedText.length === 0 || !parsedText.trim()) {
            return;
        }

        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');


        var param = {};
        param['text'] = parsedText;
        param['deviation'] = $('#input-template-deviation').val();
        param['removeStopwords'] = $('#input-template-use-stopwords').val();
        param['useStemmer'] = $('#input-template-use-porter-stemmer').val();

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "POST",
            url: "/api/template/similarity/"+tplId,
            data: JSON.stringify(param),
            dataType: "json",
            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');
                var generate_info = data.result.value.generate_info;
                $.fn.render_generate_info(generate_info);
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
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

        param['generateLoop'] = $('#input-template-pregenerated-count').val();
        param['deviation'] = $('#input-template-deviation').val();
        param['removeStopwords'] = $('#input-template-use-stopwords').val();
        param['useStemmer'] = $('#input-template-use-porter-stemmer').val();

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
                        var generate_info = data.result.value.generate_info;

                        // console.log(generated_template);
                        $.fn.render_generated_template();
                        $.fn.render_generate_info(generate_info);

                        $('#button-template-ngmc').removeClass('btn-default');
                        $('#button-template-ngmc').addClass('btn-info');

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
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });

    });

    $('#button-template-delete').on('click',  function(e) {
        var tplIdSelected = $('#select-template-name').val();
        if (tplIdSelected === undefined || tplIdSelected == 0) {
            return;
        }

        var resultConfirm = confirm("Вы уверены что данный шаблон нужно удалить безвозвратно?");
        if (!resultConfirm) {
            return;
        }

        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "GET",
            url: "/api/template/remove/"+tplIdSelected,
            dataType: "json",
            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');
                $.fn.load_template_list();
                $.fn.clear_content();
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });
    });


    $('#button-template-toggle-parenthesis').on('click',  function(e) {
        $.fn.toggle_parenthesiss_state();
    });

    $('#button-template-generate-ngmc').on('click',  function(e) {
        var parsedText = $('#textarea-ngmc-orig').val();
        if (parsedText.length === 0 || !parsedText.trim()) {
            return;
        }
        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');


        var param = {};
        param['text'] = parsedText;
        param['frame_size'] = $('#input-template-ngmc-framesize').val();
        param['frame_peek_probability'] = $('#frame_peek_probability').val();

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
                type: "POST",
                url: "/api/ngram/spin",
                data: JSON.stringify(param),
                dataType: "json",
                success: function(data) {
                    bar.removeClass('animate');
                    dialog.modal('hide');
                    $('#textarea-ngmc-replaced').val(data.result.value);
                },
                error: function(errorMsg){
                    bar.removeClass('animate');
                    dialog.modal('hide');
                    alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
                }
        });

    });



    $('#dialog-dict-confirm-delete').on('click', '.btn-ok', function(e) {
        var $modalDiv = $(e.delegateTarget);
        var id = $(this).data('recordId');
        $('#'+id).remove();
        $modalDiv.modal('hide')
    });



    $.fn.load_template_list();

});