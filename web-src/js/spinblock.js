$(document).ready(function() {

    const TYPE_BLOCK_DATABASE = 'database';
    const TYPE_BLOCK_NEW = 'newblock';
    const TYPE_BLOCK_STATIC = 'static';
    const TYPE_BLOCK_SPINSENTENCE = 'spinsentence';


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
        //console.log(output.join(''));
  //      $('#select-newblockposition').innerHTML = output.join('');

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

        spinblock_data[position+1] = {
            name:name,
            type:type,
            readonly:false,
            index:position+1,
            indexmodifible:true
        }

        var template = $(type_mapping[type].template_id);
        var newElem = template.clone();
        var elementIndex = position+1;

        newElem.attr('id','spinblock-element-'+elementIndex);
        newElem.removeClass('hidden');
        $(newElem).find('.title-collapse-href').attr('href','#blocklist-collapse-'+elementIndex);
        $(newElem).find('.title-collapse-href').text('( '+elementIndex + ' ) '+name);
        $(newElem).find('.panel-collapse').attr('id','blocklist-collapse-'+elementIndex);
        $(newElem).find('.block-name').val(name);
        $(newElem).data('blockIndex', elementIndex).attr('data-block-index', elementIndex);


        // CONTENT BLOCK
        if(type == TYPE_BLOCK_STATIC)
        {
            // do nothing
        }
        else if (type == TYPE_BLOCK_SPINSENTENCE)
        {
            var buttonUpdate =  $(newElem).find('.button-block-update');
            $(buttonUpdate).data('action', 'add').attr('data-action', 'add');
            $(buttonUpdate).data('sentenceIndex', 0).attr('data-sentence-index', 0);

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

            $(buttonUpdate).find('.button-block-update').on('click', function(e){
                var blockIndex = $( this ).closest( ".panel").data('blockIndex');
                var sentenceIndex = $( this ).data('sentenceIndex');
                var action = $(this).data('action');
                var eListGroup = $( this ).closest( ".panel-body").find('.list-group');

                alert('!!1');
            });
        }










        var afterBlock = $('#spinblock-element-'+position).after(newElem.fadeIn());

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