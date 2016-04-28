function podlove_init_color_buttons() {
    jQuery("#widgets-right .podlove_subscribe_color, #customize-controls .podlove_subscribe_color").spectrum({
        preferredFormat: 'hex',
        showInput: true,
        palette: [ '#75ad91' ],
        showPalette: true,
        showSelectionPalette: false,
        chooseText: "Select Color",
        cancelText: "Cancel",
    });
}

jQuery(document).ready(function () {
    podlove_init_color_buttons();

    jQuery(document).on('widget-updated', podlove_init_color_buttons);
    jQuery(document).on('widget-added', podlove_init_color_buttons);

    // re-init after saving configs
    jQuery(document).on('ajaxComplete', function(e){
        podlove_init_color_buttons();
    });
})

