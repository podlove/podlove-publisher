function podlove_init_color_buttons() {
    jQuery("#widgets-right .podlove_subscribe_color").spectrum({
        preferredFormat: 'hex',
        showInput: true
    });
}

jQuery(document).ready(function () {
    podlove_init_color_buttons();

    // re-init after saving configs
    jQuery(document).on('ajaxComplete', function(e){
        podlove_init_color_buttons();
    });
})

