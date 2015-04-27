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
            'action': 'my_action', // wp_ajax_my_action где my_action часть после wp_ajax_  !
            'type_item': event.target.value      // We pass php values differently!
        };

        var shiphawk_type_value = jQuery('#sh_type_product');

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_object.ajax_url, data, function(response) {


            var obj_responce = jQuery.parseJSON(response);

            //shiphawk_type_value.text(obj_responce.responce_html);
            shiphawk_type_value.html(obj_responce.responce_html);
            shiphawk_type_value.show();
            //jQuery(shiphawk_type_of_product).show();
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
        'action': 'set_item_type', // wp_ajax_my_action где my_action часть после wp_ajax_  !
        'type_item': type_item,
        'type_item_value': el.id,
        'post_id': post_id
    };

    // We can also pass the url value separately from ajaxurl for front end AJAX implementations
    jQuery.post(ajax_object.ajax_url, data, function(response) {
//TODO add loading gif
        var obj_responce = jQuery.parseJSON(response);

    });
}
