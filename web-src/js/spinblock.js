$(document).ready(function() {


    if(typeof(page_spinblock_data) === 'undefined')
    {
        return;
    }
    var spinblock_data = [];
    const TYPE_BLOCK_DATABASE = 'database';
    const TYPE_BLOCK_NEW = 'newblock';
    const TYPE_BLOCK_STATIC = 'static';
    const TYPE_BLOCK_SPINSENTENCE = 'spinsentence';

    const ACTION_SENTENCE_ADD = 'add';
    const ACTION_SENTENCE_UPDATE = 'update';
    const ACTION_SENTENCE_DELETE = 'delete';


    var type_mapping = [];
    type_mapping[TYPE_BLOCK_DATABASE] = {
        template_id: '#spinblock-element-database-template',
        default_data: {}
    };

    type_mapping[TYPE_BLOCK_STATIC] = {
        template_id: '#spinblock-element-statictext-template',
        default_data: {}
    };
    type_mapping[TYPE_BLOCK_SPINSENTENCE] = {
        template_id: '#spinblock-element-spinsentence-template',
        default_data: {}
    };

    // Global Save

    $.fn.save_spinblock_content = function()
    {
        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        var content = spinblock_data;
        var request = {"data":content };
        var templateName = global.data.template.name;


        $.ajax({
            type: "POST",
            url: "/api/editor/spinblock/"+templateName,
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
    };

    $.fn.handle_generator_result = function(data)
    {
        console.log(data);
        if(data.status.code == 200)
        {
            if(data.result.value.validation_status == true)
            {
                $('#textarea-out-generator').val(data.result.value.generated);
                $('#generator-error-container').empty();
                $('#a-tab-generator').trigger('click');
            }
            else {
                // Error div content
                $('#generator-error-container').empty();
                var startline = data.result.value.start_line;
                for(i=0;i<data.result.value.validation_lines.length; i++)
                {
                    var line =  data.result.value.validation_lines[i];
                    var linenum = line.linenum;
                    var text = '('+linenum+') '+line.text;
                    if(line.is_valid == true)
                    {
                        $('#generator-error-container').append('<div class="line-ok">'+text+'</div>');
                    }
                    else {
                        $('#generator-error-container').append('<div class="line-error">'+text+'</div>');
                    }
                }

                $('#textarea-out-console').val(data.result.value.validation_text);

                $('#a-tab-errors').trigger('click');



            }
        }
    };

    $.fn.generate_content_block = function(index)
    {
        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        var content = spinblock_data[index];
        var request = {"data":content };
        var templateName = global.data.template.name;

        $.ajax({
            type: "POST",
            url: "/api/editor/generateblock/"+templateName,
            data: JSON.stringify(request),
            dataType: "json",

            success: function(response) {
                bar.removeClass('animate');
                dialog.modal('hide');
                $.fn.handle_generator_result(response);
            },
            error: function(response){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error, cannot save');

                console.log(response);

            }
        });
    };



    $('#button-template-save').on('click',  function(e){
        $.fn.save_spinblock_content();
    });

    // Global Generate

    $('#button-template-generate').on('click', function(e)
    {
        var dialog = $('.dialog-progress-bar');
        var bar = dialog.find('.progress-bar');

        dialog.modal('show');
        bar.addClass('animate');

        var content = spinblock_data;
        var request = {"data":content };
        var templateName = global.data.template.name;

        $.ajax({
            type: "POST",
            url: "/api/editor/generate/"+templateName,
            data: JSON.stringify(request),
            dataType: "json",

            success: function(response) {
                bar.removeClass('animate');
                dialog.modal('hide');
                $.fn.handle_generator_result(response);
            },
            error: function(response){
                bar.removeClass('animate');
                dialog.modal('hide');
                alert('Error, cannot save');

                console.log(response);

            }
        });
    });


    $.fn.spinblock_add_recalculate_options = function()
    {
        $('#select-newblockposition').empty();

        var output = [];
        for (i=0; i<spinblock_data.length; i++)
        {
            if(spinblock_data[i].type != 'newblock')
            {
                if(i == spinblock_data.length-2)
                {
                    $('#select-newblockposition').append('<option value='+spinblock_data[i].index+' selected>'
                        + '(' + spinblock_data[i].index + ') - ' + spinblock_data[i].name + '</option>');
                }
                else {
                    $('#select-newblockposition').append('<option value='+spinblock_data[i].index+'>'
                        + '(' + spinblock_data[i].index + ') - ' + spinblock_data[i].name + '</option>');
                }
            }
        }
    };


    $('.action-spinblock-sentence-delete').on('click', function(e){
        $.fn.save_spinblock_content();
    });

    $('#button-add-new-block').on('click', function(e) {

        var name = $('#input-newblockname').val();
        var type = $('#input-newblocktype').val();
        var position = parseInt($('#select-newblockposition').val(),10);

        if($.fn.spinblock_element_add(name, type, position+1, null)) // successfuly added
        {
            $.fn.spinblock_add_recalculate_options();
            $('#spinblock-element-'+(position+1)).find('.title-collapse-href').click()
            $('#input-newblockname').val('')
        }
    });



    $.fn.spinblock_spinsentence_sentence_add = function(blockIndex, spinIndex, content) {

        var spinIndex = spinblock_data[blockIndex].data.length;
        spinblock_data[blockIndex].data[spinIndex] = content;

        var sentenceDiv = $('#spinblock-element-list-item-template').clone();

        $(sentenceDiv).data('spinIndex', spinIndex);
        $(sentenceDiv).removeClass('hidden');

        var iconSpan = $(sentenceDiv).find('.action-icon-span');
        $(sentenceDiv).find('.action-spinblock-update-sentence').text(content.substr(0, 50));
        $(sentenceDiv).find('.action-spinblock-update-sentence').append(iconSpan);

        // ON SENTENCE CLICK HEADER
        $(sentenceDiv).find('.action-spinblock-update-sentence').on('click', function(e){
            var listItem = $( this ).closest( ".list-group-item");
            var spinIndex = $(listItem).data('spinIndex');
            var content = spinblock_data[blockIndex].data[spinIndex];
            var buttonUpdate = $( this ).closest( ".element-content-row").find('.button-sentence-update');

            $(buttonUpdate).data('action', ACTION_SENTENCE_UPDATE);
            $(buttonUpdate).data('sentenceIndex', spinIndex);
            $(buttonUpdate).val('Update');

            $that = $(this);
            $that.parent().parent().find('li.active').removeClass('active');
            $that.parent().addClass('active');
            $that.addClass('active');
            $that.closest( ".element-content-row").find('.input-sentence-value').val(content);
        });
        $( '#spinblock-element-'+blockIndex).find('.list-group-item-add').after(sentenceDiv.fadeIn());
    };


    $.fn.spinblock_element_add = function(name, type, position, data)
    {
        if(name.length == 0)
        {
            alert('Имя не может быть пустым');
            return false; // do nothing empty name
        }


        // modify existing data, make space

        for(i=spinblock_data.length-1; i>=position; i--)
        {
            var currIndex = spinblock_data[i].index;
            var updatedIndex = currIndex+1;

            if(spinblock_data[i].type != TYPE_BLOCK_NEW){
                var curElem = $('#spinblock-element-'+currIndex);

                $(curElem).find('.title-collapse-href').attr('href','#blocklist-collapse-'+updatedIndex);
                $(curElem).find('.title-collapse-href').text('( '+updatedIndex +' ) '+spinblock_data[i].name);
                $(curElem).find('.panel-collapse').attr('id','blocklist-collapse-'+updatedIndex);
                $(curElem).attr('id','spinblock-element-'+updatedIndex);
                $(curElem).data('blockIndex', updatedIndex).attr('data-block-index', updatedIndex);

                spinblock_data[i].index ++;
            }
            spinblock_data[i+1] = spinblock_data[i];
        }

        var newElementIndex = position;
        // Define data in spinblock
        spinblock_data[newElementIndex] = {
            name:name,
            type:type,
            readonly:false,
            index:newElementIndex,
            indexmodifible:true,
            data: null,
        }

        var template = $(type_mapping[type].template_id);
        console.log(template);
        var newElem = template.clone();

        newElem.attr('id','spinblock-element-'+newElementIndex);
        newElem.removeClass('hidden');
        $(newElem).find('.title-collapse-href').attr('href','#blocklist-collapse-'+newElementIndex);
        $(newElem).find('.title-collapse-href').text('( '+newElementIndex + ' ) '+name);
        $(newElem).find('.panel-collapse').attr('id','blocklist-collapse-'+newElementIndex);
        $(newElem).find('.block-name').val(name);
        $(newElem).data('blockIndex', newElementIndex).attr('data-block-index', newElementIndex)

        $(newElem).find('.button-block-save').on('click', function(e) {
            $.fn.save_spinblock_content();
        });

        $(newElem).find('.button-block-generate').on('click', function(e) {
            $.fn.generate_content_block(newElementIndex);
        });



        // CONTENT BLOCK
        if(type == TYPE_BLOCK_STATIC) {

            $(newElem).find('.textarea-block-value').on('blur', function (e) {
                var content = $(this).val();
                content = content == undefined ? '':content;
                content = content == null ? '':content;
                spinblock_data[newElementIndex].data = content;
            });

            if(data != undefined && data != null)
            {
                $(newElem).find('.textarea-block-value').val(data);
            }
        }
        else if (type == TYPE_BLOCK_SPINSENTENCE)
        {
            spinblock_data[newElementIndex].data = [];

            var buttonUpdate =  $(newElem).find('.button-sentence-update');
            $(buttonUpdate).data('action', ACTION_SENTENCE_ADD).attr('data-action', ACTION_SENTENCE_ADD);
            $(buttonUpdate).data('sentenceIndex', -1);


            $(newElem).find('.action-spinblock-add-sentence').on('click', function(e){
                $(buttonUpdate).data('action', ACTION_SENTENCE_ADD);
                $(buttonUpdate).data('sentenceIndex', -1);
                $(buttonUpdate).val('Add');
                $( this ).closest( ".element-content-row").find('.input-sentence-value').val('');
                $( this ).parent().parent().find('li.active').removeClass('active');
                $( this ).parent().addClass('active');
            });


            // ONCLICK BUTTON UPDATE/ADD SPINSENTENCE

            $(buttonUpdate).on('click', function(e){
                var blockIndex = $( this ).closest( ".panel").data('blockIndex');
                var sentenceIndex = $( this ).data('sentenceIndex');
                var action = $(this).data('action');
                var eListGroup = $( this ).closest( ".panel-body").find('.list-group')
                var textarea = $( this ).closest( ".element-content-row").find('.input-sentence-value');
                var content = $( textarea ).val();

                // CREATE NEW SENTENCE ACTION
                if(action == ACTION_SENTENCE_ADD)
                {
                    $.fn.spinblock_spinsentence_sentence_add(blockIndex, spinIndex, content);
                    $( textarea ).val('');
                }
                //UPDATE SENTENCE
                else {
                    var spinIndex = $(this).data('sentenceIndex');
                    var textarea = $( this ).closest( ".element-content-row").find('.input-sentence-value');
                    var content = $( textarea ).val();
                    spinblock_data[blockIndex].data[spinIndex] = content;
                }
            });




            $(newElem).find('.action-spinblock-add-sentence').on('click', function(e) {
                var blockIndex = $( this ).closest( ".panel").data('blockIndex');
                var eListGroup = $( this ).closest( ".list-group");
                var pBody = $( this ).closest( ".panel-body");

                $(pBody).find('.input-sentence-value').text('');
                var buttonUpdate =  $(pBody).find('.button-block-update');
                $(buttonUpdate).text('Add');
                $(buttonUpdate).data('action', 'add').attr('data-action', 'add');
                $(buttonUpdate).data('sentenceIndex', 0).attr('data-sentence-index', 0);
            });


        }
        if(newElementIndex == 0)
        {
            $("#spinblock-element-panel").prepend(newElem.fadeIn());
        }
        else {
            $('#spinblock-element-'+(newElementIndex-1)).after(newElem.fadeIn());
        }


        return true;
    }


    // PROCESS EXISTING BLOCKS

    var max =  page_spinblock_data.length;
    for(ii=0;ii<max;ii++)
    {
        var obj = page_spinblock_data[ii];
        var name = page_spinblock_data[ii].name;
        var type = page_spinblock_data[ii].type;
        var position = ii;
        var data = page_spinblock_data[ii].data;
        $.fn.spinblock_element_add(name, type, position, data);

        if(type == TYPE_BLOCK_SPINSENTENCE && data != undefined)
        {
            for(j=0;j<data.length;j++)
            {
                $.fn.spinblock_spinsentence_sentence_add(position, j, data[j]);
            }
        }

        console.log(name+':'+position);
    }

    $.fn.spinblock_add_recalculate_options();
});