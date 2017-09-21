"use strict";

$(document).ready(function() {

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    let parenthesis_state_show = true;
    let parenthesis_state_ngmc = true;
    let generated_template = '';
    let generated_marcov_chain = '';

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    if(typeof(template_page) === 'undefined')
    {
        return;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const toggle_parenthesiss_state = function()
    {
        parenthesis_state_show = !parenthesis_state_show;

        if(parenthesis_state_show)
        {
            $('#button-template-toggle-parenthesis').val('Hide (( ))');
        }
        else {
            $('#button-template-toggle-parenthesis').val('Show (( ))');
        }

        render_generated_template();
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const render_generated_template = function()
    {
        if (parenthesis_state_show) {
            $('#textarea-template-generator').val(generated_template);
        } else {
            const parsed = generated_template.replaceAll('((','').replaceAll('))','');
            $('#textarea-template-generator').val(parsed);
        }
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const toggle_ngmc_parenthesiss_state = function()
    {
        parenthesis_state_ngmc = !parenthesis_state_ngmc;

        if (parenthesis_state_ngmc) {
            $('#button-template-toggle-parenthesis').val('Hide (( ))');
        }
        else {
            $('#button-template-toggle-parenthesis').val('Show (( ))');
        }

        render_ngmc_text();
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const render_ngmc_text = function()
    {
        if (parenthesis_state_ngmc) {
            $('#textarea-ngmc-replaced').val(generated_marcov_chain);
        } else {
            const parsed = generated_marcov_chain.replaceAll('[[','').replaceAll(']]','');
            $('#textarea-ngmc-replaced').val(parsed);
        }
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const render_generate_info = function(generate_info)
    {
        if (!generate_info) {
            $('#generate_info_text').text('');
        } else {
            const generate_info_text = JSON.stringify(generate_info, null, '    ');
            $('#generate_info_text').text(generate_info_text);
        }
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const load_generated_text_list = function(selectedTemplateId)
    {
        if (!selectedTemplateId) {
            $('#generated-texts').empty();
        }

        const dialog = $('.dialog-progress-bar');
        const bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "GET",
            url: "/api/generated-text/list/" + selectedTemplateId,
            dataType: "json",

            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');
                const generated_texts = $('#generated-texts');
                generated_texts.empty();
                $('#input-template-count').val(data.result.values.length);
                for(let i=0;i<data.result.values.length; i++)
                {
                    const elem = data.result.values[i];

                    const generateTextId = elem['id'];
                    generated_texts.append($('<div><span>id: ' + generateTextId + '</span></div>'));

                    const textArea = $('<textarea rows="5" readonly class="form-control" style="resize: vertical;min-width: 100%; margin-bottom: 5px"/>');
                    textArea.text(elem['text']);
                    generated_texts.append(textArea);

                    const minusBtn = $('<input id="button-template-minus-' + generateTextId + '" type="button" class="btn button-template-minus btn-default" value="Удалить текст"/>');
                    (function(minusBtn, generateTextId) {
                        minusBtn.click(function(e) {
                            const resultConfirm = confirm("Вы уверены что данный текст нужно удалить безвозвратно?");
                            if (!resultConfirm) {
                                return;
                            }

                            const dialog = $('.dialog-progress-bar');
                            const bar = dialog.find('.progress-bar');

                            dialog.modal('show');
                            bar.addClass('animate');

                            $.ajax({
                                type: "GET",
                                url: "/api/generated-text/remove/"+generateTextId,
                                dataType: "json",
                                success: function(data) {
                                    bar.removeClass('animate');
                                    dialog.modal('hide');
                                    load_generated_text_list(selectedTemplateId);
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const load_template_list = function(selectedTemplateId)
    {
        const dialog = $('.dialog-progress-bar');
        const bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "GET",
            url: "/api/template/list",
            dataType: "json",

            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');
                const selectTemplateName = $('#select-template-name');
                selectTemplateName.empty();
                selectTemplateName.append('<option value="0">Select template name</option>');
                for(let i=0;i<data.result.values.length; i++) {
                    const elem = data.result.values[i];
                    selectTemplateName.append('<option value="' + elem['id'] + '">' + elem['name'] + '</option>');
                }
                if (!selectedTemplateId) {
                    const tplId = $('#input-template-id').val();
                    if(tplId !== 'none') {
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    const clear_content = function() {
        $('#input-template-name').val('');
        $('#textarea-template-content').val('');
        $('#input-template-id').val('new');

        $('#input-template-count').val(0);

        $('#generate_info_text').text('');

        $('#generated-texts').empty();

        $('#button-template-save').removeClass('btn-default');
        $('#button-template-save').addClass('btn-success');
        $('#button-template-save').val('Создать шаблон');

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
        $.fn.template_textarea_highlight_update();
    };

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-new').click(function(){
        clear_content();
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#select-template-name').change(function() {
        const tplId = $('#select-template-name').val();
        const tplIdSelected = $('#input-template-id').val();

        if(tplId === 0 || tplId === tplIdSelected)
        {
            return; // do nothing
        }
        const dialog = $('.dialog-progress-bar');
        const bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "GET",
            url: "/api/template/content/"+tplId,
            dataType: "json",
            success: function(data) {
                bar.removeClass('animate');
                dialog.modal('hide');

                const obj = data.result.value;
                clear_content();

                $('#input-template-name').val(obj['name']);
                $('#textarea-template-content').val(obj['template']);
                $('#input-template-id').val(obj['id']);

                $('#button-template-save').removeClass('btn-default');
                $('#button-template-save').addClass('btn-success');

                $('#button-template-delete').removeClass('btn-default');
                $('#button-template-delete').addClass('btn-danger');
                $('#button-template-delete').data('toggle','modal');
                $('#button-template-delete').data('target','#dialog-template-confirm-delete');

                $('#button-template-save').val('Сохранить шаблон');

                // console.log( $('#button-template-delete').data());

                $('#button-template-generate').removeClass('btn-default');
                $('#button-template-generate').addClass('btn-info');

                $('#button-template-plus').removeClass('btn-default');
                $('#button-template-plus').addClass('btn-success');
                $('#generator-error-container').empty();
                $('#textarea-template-generator').val('');
                generated_template = '';
                load_generated_text_list(tplId);
                $.fn.template_textarea_highlight_update();
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-save').click(function(){
        const tplId = $('#input-template-id').val();
        const tplName = $('#input-template-name').val().trim();
        if(tplId === 'none' || tplName.length === 0) {
            return;
        }

        const resultConfirm = confirm("Вы уверены что хотите сохранить этот шаблон?");
        if (!resultConfirm) {
            return;
        }

        const curr = {};
        curr['id'] = tplId;
        curr['name'] = tplName;
        curr['template'] = $('#textarea-template-content').val();

        const dialog = $('.dialog-progress-bar');
        const bar = dialog.find('.progress-bar');

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

                const obj = data.result.value;

                $('#input-template-name').val(obj['name']);
                $('#textarea-template-content').val(obj['template']);
                $('#input-template-id').val(obj['id']);

                $('#button-template-save').removeClass('btn-default');
                $('#button-template-save').addClass('btn-success');

                $('#button-template-delete').removeClass('btn-default');
                $('#button-template-delete').addClass('btn-danger');
                $('#button-template-delete').removeClass('.disabled');
                $('#button-template-save').val('Сохранить шаблон');

                $('#button-template-generate').removeClass('btn-default');
                $('#button-template-generate').addClass('btn-info');
                $('#button-template-save').removeClass('btn-default');
                $('#button-template-save').addClass('btn-success');


                $('#button-template-plus').removeClass('btn-default');
                $('#button-template-plus').addClass('btn-success');

                load_template_list(obj['id']);
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-plus').click(function() {
        const tplId = $('#input-template-id').val();
        if (tplId === 'none' || tplId === 'new') {
            return;
        }

        const resultConfirm = confirm("Вы уверены что хотите сохранить этот текст?");
        if (!resultConfirm) {
            return;
        }

        let parsedText = $('#textarea-template-generator').val();
        parsedText = parsedText.replaceAll('((','').replaceAll('))','');
        if (parsedText.length === 0 || !parsedText.trim()) {
            return;
        }

        const dialog = $('.dialog-progress-bar');
        const bar = dialog.find('.progress-bar');

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
                load_generated_text_list(tplId);
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-recalc-similarity').click(function() {
        const tplId = $('#input-template-id').val();
        if (tplId === 'none' || tplId === 'new') {
            return;
        }

        let parsedText = $('#textarea-template-generator').val();
        parsedText = parsedText.replaceAll('((','').replaceAll('))','');
        if (parsedText.length === 0 || !parsedText.trim()) {
            return;
        }

        const dialog = $('.dialog-progress-bar');
        const bar = dialog.find('.progress-bar');


        const param = {};
        param['text'] = parsedText;
        param['deviation'] = $('#input-template-deviation').val();
        param['removeStopwords'] = $('#input-template-use-stopwords').is(":checked");
        param['useStemmer'] = $('#input-template-use-porter-stemmer').is(":checked");

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
                const generate_info = data.result.value.generate_info;
                render_generate_info(generate_info);
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-generate').click(function() {
        const tplId = $('#input-template-id').val();
        if (tplId === 'none' || tplId === 'new') {
            return;
        }

        const dialog = $('.dialog-progress-bar');
        const bar = dialog.find('.progress-bar');


        const param = {};
        const drugName = $('#input-template-drug-name').val().trim();

        if(drugName.length > 0)
        {
            param['drugName'] = drugName;
        }

        param['generateLoop'] = $('#input-template-pregenerated-count').val();
        param['deviation'] = $('#input-template-deviation').val();
        param['removeStopwords'] = $('#input-template-use-stopwords').is(":checked");
        param['useStemmer'] = $('#input-template-use-porter-stemmer').is(":checked");

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

                if(data.status.code === 200) {
                    if (data.result.value.validation_status) {
                        $('#generator-error-container').empty();
                        $('#a-tab-generator').trigger('click');
                        generated_template = data.result.value.generated;
                        const generate_info = data.result.value.generate_info;

                        // console.log(generated_template);
                        render_generated_template();
                        render_generate_info(generate_info);

                        $('#button-template-move-to-ngmc').removeClass('btn-default');
                        $('#button-template-move-to-ngmc').addClass('btn-info');
                    }
                    else {
                        // Error div content
                        $('#generator-error-container').empty();
                        // const startline = data.result.value.start_line;
                        for (let i = 0; i < data.result.value.validation_lines.length; i++) {
                            const line = data.result.value.validation_lines[i];
                            const linenum = line.linenum;
                            const text = '(' + linenum + ') ' + line.text;
                            if (line.is_valid) {
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

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-delete').click(function() {
        const tplIdSelected = $('#select-template-name').val();
        if (tplIdSelected === undefined || tplIdSelected === 0) {
            return;
        }

        const resultConfirm = confirm("Вы уверены что данный шаблон нужно удалить безвозвратно?");
        if (!resultConfirm) {
            return;
        }

        const dialog = $('.dialog-progress-bar');
        const bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        $.ajax({
            type: "GET",
            url: "/api/template/remove/"+tplIdSelected,
            dataType: "json",
            success: function() {
                bar.removeClass('animate');
                dialog.modal('hide');
                load_template_list();
                clear_content();
            },
            error: function(errorMsg){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
            }
        });
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-toggle-parenthesis').click(function() {
        toggle_parenthesiss_state();
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-generate-ngmc').click(function() {
        const parsedText = $('#textarea-ngmc-orig').val();
        if (parsedText.length === 0 || !parsedText.trim()) {
            return;
        }
        const dialog = $('.dialog-progress-bar');
        const bar = dialog.find('.progress-bar');


        const param = {};
        param['text'] = parsedText;
        param['frame_size'] = $('#input-template-ngmc-framesize').val();
        param['frame_peek_probability'] = $('#input-template-ngmc-peekprob').val();
        param['mode'] = $('#input-template-ngmc-mode').val();


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
                    generated_marcov_chain = data.result.value;
                    render_ngmc_text();
                },
                error: function(errorMsg){
                    bar.removeClass('animate');
                    dialog.modal('hide');
                    alert('Error (' + errorMsg.status + '): ' + errorMsg.statusText);
                }
        });
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-toggle-parenthesis_ngmc').click(function() {
        toggle_ngmc_parenthesiss_state()
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#button-template-move-to-ngmc').click(function() {
        const content = $('#textarea-template-generator').val();
        $('#textarea-ngmc-orig').val(content);
        $('#a-tab-ngmc').trigger('click');
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    $('#dialog-dict-confirm-delete').click('.btn-ok', function(e) {
        const $modalDiv = $(e.delegateTarget);
        const id = $(this).data('recordId');
        $('#'+id).remove();
        $modalDiv.modal('hide')
    });

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    load_template_list();

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

});
