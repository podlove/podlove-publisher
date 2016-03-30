(function($){

    var DownloadIntentRecalculator = function(id) {
        var that = this;

        this.button = $(id);
        this.status = this.button.parent().find('.status');

        this.button.on('click', function(e) {
            e.preventDefault();
            that.start();
        })
    };

    DownloadIntentRecalculator.prototype.start = function() {

        $(window).bind('beforeunload', function(){
            return "If you leave, \"Recalculate Analytics\" will abort.";
        });

        var label = $("#progressbar-cleanup .progress-label");
        progressbar = $("#progressbar-cleanup");

        this.progressbar = progressbar;

        progressbar.progressbar({
            value: false,
            complete: function() {
                label.text("Complete!");
            }
        });

        progressbar.progressbar( "option", "value", false );

        this.do_cleanup();
    }

    DownloadIntentRecalculator.prototype.do_cleanup = function() {
        var that = this;

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'podlove-downloadintentcleanup'
            },
            dataType: 'json',
            success: function(result) {
                that.progressbar.progressbar("value", 100);
            }, error: function(e) {
                $("#progressbar-cleanup .progress-label").text("Error", e)
            }
        }).always(function() {
            $(window).unbind("beforeunload");
        });
    }

    $(document).ready(function() {
        var calc = new DownloadIntentRecalculator('#cleanup_download_intents');
    });

}(jQuery));
