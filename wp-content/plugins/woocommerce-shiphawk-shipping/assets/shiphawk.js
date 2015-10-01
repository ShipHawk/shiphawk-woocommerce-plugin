function attachtype() {

if (jQuery( "#sh_type_product" ).length){
return;
}
    //var shiphawk_type_of_product = jQuery( "input[value='pa_shiphawk_type_of_product']" ).parent().next().children().last();
    var shiphawk_type_of_product = jQuery( "#shiphawk_type_of_product" );

    //shiphawk_type_of_product.id = 'shiphawk_item_type_value';
    
    jQuery( '<div id="sh_type_product"></div>' ).insertAfter( shiphawk_type_of_product );

    var typeloader;

    shiphawk_type_of_product.keyup(function(event) {
        clearTimeout(typeloader);
        typeloader = setTimeout(function(){ respondToClick(event); }, 750);
    });

    function respondToClick(event) {

        var data = {
            'action': 'shiphawk_action', // wp_ajax_my_action
            'type_item': event.target.value      // We pass php values differently!
        };

        var shiphawk_type_value = jQuery('#sh_type_product');

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_object.ajax_url, data, function(response) {


            var obj_responce = jQuery.parseJSON(response);

            shiphawk_type_value.html(obj_responce.responce_html);
            shiphawk_type_value.show();

        });
    }
}

jQuery(document).bind('DOMSubtreeModified', function(e) {
    attachtype();
});

function setItemid(el) {
    jQuery(el).parent().parent().prev().val(el.innerHTML);

    jQuery('#sh_type_product').hide();
    var post_id = jQuery(el).parent().parent().prev().attr("post_id");
    var type_item = el.innerHTML;
    var data = {
        'action': 'set_item_type', // wp_ajax_shiphawk_action
        'type_item': type_item,
        'type_item_value': el.id,
        'post_id': post_id
    };

    jQuery.post(ajax_object.ajax_url, data, function(response) {

    //TODO add loading gif
        var obj_responce = jQuery.parseJSON(response);

    });
}

function getBolPdf(element){
    var bookid = element.id;

    var data = {
        'action': 'get_bolpdf', // wp_ajax_my_action
        'book_id': bookid
    };

    jQuery.post(ajax_object.ajax_url, data, function(response) {

        var obj_responce = jQuery.parseJSON(response);

        if(obj_responce.shiphawk_error) {

            var error_html = 'ERROR: ' + obj_responce.shiphawk_error;
            alert(error_html);
        }else{
            if(obj_responce.bol_url) {

                //window.location = responce_html.bol_url;

                window.open(
                    obj_responce.bol_url,
                    '_blank' // <- This is what makes it open in a new window.
                );

            }
        }

    });
}