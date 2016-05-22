var PODLOVE = PODLOVE || {};

/**
 * Handles all logic in Create/Edit Episode screen.
 */
(function($){

    PODLOVE.Jobs = function() {};

    PODLOVE.Jobs.create = function(name, args) {
        $.post(ajaxurl, {
            action: 'podlove-job-create',
            name: name,
            args: args
        }, 'json').done(function(x) {
            console.log("create job done", x);
        });
    };

    PODLOVE.Jobs.getStatus = function(job_id) {
        $.getJSON(ajaxurl, {
            action: 'podlove-job-get',
            job_id: job_id
        }).done(function(job) {
            console.log("job status", job);
        });
    }

}(jQuery));

