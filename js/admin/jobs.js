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
        var job_args = wrapper.data('args') || {}
        var timer = null;

        var spinner = $("<i class=\"podlove-icon-spinner rotate\"></i>");
        var button = $("<button>")
            .addClass('button')
            .html(button_text)
        
        var renderStatus = function(status) {

            if (status.error) {
                wrapper.html(status.error);
                return;
            }

            var percent = 100 * (status.steps_progress / status.steps_total);

            percent = Math.round(percent * 10) / 10;

            if (!percent && status.steps_total > 0) {
                wrapper
                    .html(" starting…")
                    .prepend(spinner.clone());
            } else if (percent < 100 && status.steps_total > 0) {
                wrapper
                    .html(" " + percent + "%")
                    .prepend(spinner.clone());
            } else {
                wrapper
                    .empty()
                    .append("<small class=\"podlove-recent-job-info\">Finished in " + Math.round(status.active_run_time) + " seconds <time class=\"timeago\" datetime=\"" + (new Date(status.updated_at + " UTC")).toISOString() + "\"></time></small>.")

                $("time.timeago").timeago();
                renderButton();
            }
        };

        var renderButton = function () {
            var button_clone = button.clone();
            wrapper.prepend(button_clone);
            button_clone.on('click', btnClickHandler);
        }

        var update = function() {
            PODLOVE.Jobs.getStatus(job_id, function(status) {
                renderStatus(status);

                if (status.error) {
                    console.error("job error", job_id, status.error);
                    return;
                }

                // stop when done
                if (parseInt(status.steps_progress, 10) >= parseInt(status.steps_total, 10))
                    return;

                timer = window.setTimeout(update, 2500);
            });
        };

        var btnClickHandler = function(e) {
            var job_spinner = spinner.clone();

            PODLOVE.Jobs.create(job_name.split("-").join("\\"), job_args, function(job) {
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
        } else {
            renderButton();
        }
    }

    $(document).ready(function() {
        $(".podlove-job").each(PODLOVE.Jobs.Tools.init);
    })

}(jQuery));

