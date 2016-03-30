(function($){

    var UserAgentRecalculator = function(id) {
        var that = this;

        this.button = $(id);
        this.status = this.button.parent().find('.status');

        this.button.on('click', function(e) {
            e.preventDefault();
            that.start();
        })
    };

    UserAgentRecalculator.prototype.setStatus = function(status) {
        this.progressbar.progressbar("value", status);
    }

    UserAgentRecalculator.prototype.start = function() {

        $(window).bind('beforeunload', function(){
            return "If you leave, \"User Agent Refresh\" will abort.";
        });

        var label = $("#progressbar .progress-label");
        progressbar = $("#progressbar");

        progressbar.progressbar({
            value: false,
            change: function() {
                label.text( progressbar.progressbar("value") + "%" );
            },
            complete: function() {
                label.text("Complete!");
            }
        });

        this.progressbar = progressbar;

        this.setStatus("0");
        this.refresh_some(0);
    }

    UserAgentRecalculator.prototype.refresh_some = function(offset) {
        var that = this;

        $.ajax({
            url: ajaxurl,
            data: {
                action: 'podlove-useragentrefresh',
                offset: offset
            },
            dataType: 'json',
            success: function(result) {
                if (result.offset && result.offset < result.total) {
                    var percent = result.offset / result.total * 100;
                    that.setStatus(Math.round(percent));
                    that.refresh_some(result.offset);
                } else {
                    that.setStatus(100);
                    $(window).unbind("beforeunload");
                }
            }
        });
    }

    $(document).ready(function() {
        var calc = new UserAgentRecalculator('#recalculate_useragents');
    });

}(jQuery));
