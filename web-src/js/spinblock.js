$(document).ready(function() {

    const TYPE_BLOCK_DATABASE = 'database';
    const TYPE_BLOCK_NEW = 'newblock';
    const TYPE_BLOCK_STATIC = 'static';
    const TYPE_BLOCK_SPINSENTENCE = 'spinsentence';

    const ACTION_SENTENCE_ADD = 'add';
    const ACTION_SENTENCE_UPDATE = 'update';
    const ACTION_SENTENCE_DELETE = 'delete';



    if(typeof(page_spinblock_data) === 'undefined')
    {
        return;
    }

    var spinblock_data = page_spinblock_data;


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


    $('.action-spinblock-sentence-delete').on('click', function(e) {

    });

    $('#button-add-new-block').on('click', function(e) {

        var name = $('#input-newblockname').val();
        var type = $('#input-newblocktype').val();
        var position = parseInt($('#select-newblockposition').val(),10);

        if($.fn.spinblock_element_add(name, type, position, null)) // successfuly added
        {
            $.fn.spinblock_add_recalculate_options();
            $('#spinblock-element-'+(position+1)).find('.title-collapse-href').click()
            $('#input-newblockname').val('')
        }
    });


    $.fn.spinblock_element_add = function(name, type, position, data)
    {
        if(name.length == 0)
        {
            alert('Имя не может быть пустым');
            return false; // do nothing empty name
        }


        // modify existing data, make space

        for(i=spinblock_data.length-1; i>position; i--)
        {
            var currIndex = spinblock_data[i].index;
            var updatedIndex = currIndex+1;

            if(spinblock_data[i].type != TYPE_BLOCK_NEW){
                console.log('processing '+'#spinblock-element-'+currIndex);
                console.log('must become '+'#spinblock-element-'+updatedIndex);

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

        var newElementIndex = position+1;
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
        var newElem = template.clone();

        newElem.attr('id','spinblock-element-'+newElementIndex);
        newElem.removeClass('hidden');
        $(newElem).find('.title-collapse-href').attr('href','#blocklist-collapse-'+newElementIndex);
        $(newElem).find('.title-collapse-href').text('( '+newElementIndex + ' ) '+name);
        $(newElem).find('.panel-collapse').attr('id','blocklist-collapse-'+newElementIndex);
        $(newElem).find('.block-name').val(name);
        $(newElem).data('blockIndex', newElementIndex).attr('data-block-index', newElementIndex);



        // CONTENT BLOCK
        if(type == TYPE_BLOCK_STATIC)
        {
            // do nothing
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
                    var spinIndex = spinblock_data[blockIndex].data.length;
                    spinblock_data[blockIndex].data[spinIndex] = content;

                    var template = $('#spinblock-element-list-item-template');
                    var newSentence = template.clone();
                    $(newSentence).data('spinIndex', spinIndex);

                    alert($(newSentence).data('spinIndex'));

                    $(newSentence).removeClass('hidden');

                    var iconSpan = $(newSentence).find('.action-icon-span');
                    $(newSentence).find('.action-spinblock-update-sentence').text(content.substr(0, 50));
                    $(newSentence).find('.action-spinblock-update-sentence').append(iconSpan);



                    // ON SENTENCE CLICK HEADER
                    $(newSentence).find('.action-spinblock-update-sentence').on('click', function(e){
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


                        $( this ).closest( ".element-content-row").find('.input-sentence-value').val(content);
                    });
                    $( textarea ).val('');
                    $( this ).closest( ".panel-body").find('.list-group-item-add').after(newSentence.fadeIn());

                }
                //UPDATE SENTENCE
                else {
                    var spinIndex = $(this).data('sentenceIndex');
                    var textarea = $( this ).closest( ".element-content-row").find('.input-sentence-value');
                    var content = $( textarea ).val();
                    spinblock_data[blockIndex].data[spinIndex] = content;
                    alert(spinIndex);
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

        $('#spinblock-element-'+position).after(newElem.fadeIn());

        return true;
    }







    var type_mapping = [];
    type_mapping[TYPE_BLOCK_STATIC] = {
        template_id: '#spinblock-element-statictext-template',
        default_data: {}
    };
    type_mapping[TYPE_BLOCK_SPINSENTENCE] = {
        template_id: '#spinblock-element-spinsentence-template',
        default_data: {}
    };





    $.fn.spinblock_add_recalculate_options();
});