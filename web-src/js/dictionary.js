/**
 * Created by void on 8/5/17.
 */






$(document).ready(function() {

    if(typeof(page_dictonary_data) === 'undefined')
    {
        return;
    }

    //
    // Dictonary block
    //

    // Bind to modal opening to set necessary data properties to be used to make request


$('#dialog-dict-confirm-delete').on('show.bs.modal', function(e) {
    var data = $(e.relatedTarget).data();
    $('.title', this).text(data.recordTitle);
    $('.btn-ok', this).data('recordId', data.recordId);
});

$('#dialog-dict-confirm-delete').on('click', '.btn-ok', function(e) {
    var $modalDiv = $(e.delegateTarget);
    var id = $(this).data('recordId');
    $('#'+id).remove();
    $modalDiv.modal('hide')
});

$('#btn-dict-update').on('click', function(e) {

    var dictNew = {};

    $('#accordion_dictonary > .panel').each(function () {
        var word = $(this).find('.title-collapse-href').text();
        var value =  $(this).find('.dict-value').val();
        value = value == undefined ? '':value;
        dictNew[word] = value;
    });



    var dialog = $('.dialog-progress-bar');
    var bar = dialog.find('.progress-bar');

    dialog.modal('show');
    bar.addClass('animate');

    var request = {"data": dictNew};
    var templateName = global.data.template.name;


    $.ajax({
        type: "POST",
        url: "/api/editor/dictionary/"+templateName,
        data: JSON.stringify(request),
        dataType: "json",

        success: function(response) {
            bar.removeClass('animate');
            dialog.modal('hide');
        },
        error: function(){
            bar.removeClass('animate');
            dialog.modal('hide');
            alert('error cannot save!');
        }
    });
});

/**
 *
 * // $.ajax({url: '/api/record/' + id, type: 'DELETE'})
 // $.post('/api/record/' + id).then()
 $modalDiv.addClass('loading');
 setTimeout(function() {
            $modalDiv.modal('hide').removeClass('loading');
        }, 1000)
 *
 *
 */

$('#btn-dict-add-word').on('click', function(e)
{
    var name = $('#dictonary_search_bar').val().trim();
    $.fn.dict_element_add(name);
});


$('#dictonary_search_bar').on('change keyup paste click', function () {
    var searchTerm, panelContainerId;
    searchTerm = $(this).val().trim();
    console.log(searchTerm);
    $('#accordion_dictonary > .panel').each(function () {
        panelContainerId = '#' + $(this).attr('id');
        $(panelContainerId + ':not(:containsCaseInsensitive(' + searchTerm + '))').hide();
        $(panelContainerId + ':containsCaseInsensitive(' + searchTerm + ')').show();
    });
});

//
// Dynamic content manipulation
//

$.fn.dict_element_add = function(name_param, value_param)
{
    var name = name_param;
    if(name.length == 0)
    {
        return; // do nothing empty name
    }
    else {
        if($('#wrd-'+name).length)
        {
            return; // already exists
        }
    }

    var $template = $("#dictonary_element_template");
    var $newElem = $template.clone();
    $newElem.attr('id','acd_0');
    $($newElem).find('.title-collapse-href').attr('href','#dict-collapse_0');
    $($newElem).find('.title-collapse-href').text(name);
    $($newElem).find('.panel-collapse').attr('id','dict-collapse_0');
    $($newElem).find('.title-delete-href').data('record-id', 'acd_0');
    $($newElem).find('.title-delete-href').data('record-title',name);
    $($newElem).find('.dict-value').attr('id','wrd-'+name);

    if(value_param)
    {
        $($newElem).find('.dict-value').text(value_param);
    }

    var id = 1;
    // recalculate collapse-id
    $('#accordion_dictonary > .panel').each(function () {

        $(this).attr('id','acd_'+id);
        $(this).find('.title-collapse-href').attr('href','#dict-collapse_'+id);
        $(this).find('.panel-collapse').attr('id','dict-collapse_'+id);
        $(this).find('.title-delete-href').data('record-id', 'acd_'+id);
        id++;
    });
    $newElem.removeClass('hidden');
    $("#accordion_dictonary").prepend($newElem.fadeIn());
}



// Initialization Dictonary
// page_dictonary_data generated in module.dictonary.twig

$.fn.generate_dict_by_json = function(dictonary_data){
    $.each(dictonary_data, function (key, value) {
        $.fn.dict_element_add(key, value);
    });
}
$.fn.generate_dict_by_json(page_dictonary_data);


});