(function ($) {

    var $status_wrapper = $("#podlove-import-status");

    if (!$status_wrapper) {
        return;
    }

    var refreshImportStatus = function() {
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'podlove-import-status'
            }
        }).done(function (result) {
            $("#podlove-import-status ul:first").replaceWith($(result));
            window.setTimeout(refreshImportStatus, 4000);
        });
    };

    window.setTimeout(refreshImportStatus, 100);

})(jQuery);
