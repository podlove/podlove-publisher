var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Create/Edit Episode screen.
 * 
 * @todo investigate: looks like there is trouble when a second UARJob is started while the first is still running.
 */
(function($){

    PODLOVE.Jobs = function() {};

    PODLOVE.Jobs.create = function(name, args, callback) {
        $.post(ajaxurl, {
            action: 'podlove-job-create',
            name: name,
            args: args
        }, 'json').done(function(job) {
            // console.log("create job done", job);

            if (callback) {
                callback(job);
            }
        });
    };

    PODLOVE.Jobs.getStatus = function(job_id, callback) {
        $.getJSON(ajaxurl, {
            action: 'podlove-job-get',
            job_id: job_id
        }).done(function(status) {
            // console.log("job status", job);

            if (callback) {
                callback(status);
            }
        });
    }

    PODLOVE.Jobs.Tools = function() {};

    PODLOVE.Jobs.Tools.init = function() {
        var wrapper = $(this)
        var job_name = wrapper.data('job')
        var button_text = wrapper.data('button-text')
        var job_id = null;
        var recent_job_id = wrapper.data('recent-job-id')
        var timer = null;

        var spinner = $("<i class=\"podlove-icon-spinner rotate\"></i>");
        var button = $("<button>")
            .addClass('button')
            .html(button_text)
            .appendTo(wrapper)
        
        var renderStatus = function(status) {
            // console.log("status", status);

            if (status.error) {
                wrapper.html(status.error);
                return;
            }

            if (status.percent < 100) {
                wrapper
                    .html(" " + status.percent + "%")
                    .prepend(spinner.clone());
            } else {
                var button_clone = button.clone();
                wrapper
                    .empty()
                    .append(button_clone)
                    .append("<small class=\"podlove-recent-job-info\">Finished in " + Math.round(status.time) + " seconds <time class=\"timeago\" datetime=\"" + (new Date(status.updated_at * 1000)).toISOString() + "\"></time></small>.")

                $("time.timeago").timeago();
                button_clone.on('click', btnClickHandler);
            }
        };

        var update = function() {
            PODLOVE.Jobs.getStatus(job_id, function(status) {
                renderStatus(status);

                if (status.error) {
                    console.error("job error", job_id, status.error);
                    return;
                }

                // stop when done
                if (status.percent >= 100)
                    return;

                timer = window.setTimeout(update, 2500);
            });
        };

        var btnClickHandler = function(e) {
            var job_spinner = spinner.clone();
            PODLOVE.Jobs.create(job_name.split("-").join("\\"), [], function(job) {
                job_id = job.job_id;
                update();
            });

            wrapper
                .empty()
                .append(spinner.clone());
        };

        if (recent_job_id) {
            job_id = recent_job_id;
            update();
        }
    }

    $(document).ready(function() {
        $(".podlove-job").each(PODLOVE.Jobs.Tools.init);
    })

}(jQuery));

