<template>
    <div>

        <h4>{{ __('Running', 'podlove-podcasting-plugin-for-wordpress') }}</h4>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>{{ __('Job Name', 'podlove-podcasting-plugin-for-wordpress') }}</th>
                    <th style="width: 175px">{{ __('Progress', 'podlove-podcasting-plugin-for-wordpress') }}</th>
                    <th>{{ __('Created', 'podlove-podcasting-plugin-for-wordpress') }}</th>
                    <th>{{ __('Last Progress', 'podlove-podcasting-plugin-for-wordpress') }}</th>
                    <th style="width: 60px"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="job in runningJobs">
                    <td>
                        {{ job.title }}
                        <small v-if="job.mode">({{ job.mode }})</small>
                    </td>
                    <td>
                        {{ job.steps_progress }}/{{ job.steps_total }} ({{ job.steps_percent }}%)

                    </td>
                    <td>{{ job.created_relative }}</td>
                    <td>{{ job.last_progress }}</td>
                    <td>
                        <div v-if="isAborting(job)">
                            <i class="podlove-icon-spinner rotate"></i>
                        </div>
                        <div v-else>
                            <button class="button" @click="abortJob(job)">{{ __('abort', 'podlove-podcasting-plugin-for-wordpress') }}</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <h4>{{ __('Recently Finished', 'podlove-podcasting-plugin-for-wordpress') }}</h4>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>{{ __('Job Name', 'podlove-podcasting-plugin-for-wordpress') }}</th>
                    <th>{{ __('Finished', 'podlove-podcasting-plugin-for-wordpress') }}</th>
                    <th>{{ __('Duration', 'podlove-podcasting-plugin-for-wordpress') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="job in finishedJobs">
                    <td>
                        {{ job.title }}
                        <small v-if="job.mode">({{ job.mode }})</small>
                    </td>
                    <td>
                        {{ job.last_progress }}
                    </td>
                    <td>
                        {{ sprintf(_n('%d second', '%d seconds', job.active_run_time, 'podlove-podcasting-plugin-for-wordpress'), job.active_run_time) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
const $ = jQuery;
export default {
    data() {
        return {
            jobs: [],
            aborting: []
        }
    },

    methods: {
        __: wp.i18n.__,
        _n: wp.i18n._n,
        sprintf: wp.i18n.sprintf,
        fetchJobData() {
            $.getJSON(ajaxurl, {
                action: 'podlove-jobs-get'
            }).done((jobs) => {
                this.jobs = jobs.map((job) => {

                    job.steps_total = parseInt(job.steps_total, 10);
                    job.steps_progress = parseInt(job.steps_progress, 10);
                    job.steps_percent = parseFloat(job.steps_percent);
                    job.created_at_timestamp = parseInt(job.created_at_timestamp, 10);
                    job.active_run_time = parseFloat(job.active_run_time);

                    return job;
                });
            }).always(() => {
                window.setTimeout(this.fetchJobData, 3000);
            });
        },
        abortJob(job) {
            this.aborting.push(job.id)
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'podlove-job-delete',
                    job_id: job.id,
                    nonce: podlove_admin_global.nonce_ajax
                }
            }).fail(() => {
                this.aborting = this.aborting.filter((id) => id !== job.id)
                window.alert(wp.i18n.__('The job could not be aborted. Please reload the page and try again.', 'podlove-podcasting-plugin-for-wordpress'))
            })
        },
        isAborting(job) {
            return this.aborting.includes(job.id)
        }
    },

    computed: {
        runningJobs() {
            return this.jobs.filter((j) => {
                return j.steps_total > j.steps_progress;
            }).sort((a, b) => {
                return a.created_at_timestamp - b.created_at_timestamp;
            });
        },
        finishedJobs() {
            return this.jobs.filter((j) => {
                return j.steps_total <= j.steps_progress;
            }).sort((a, b) => {
                return b.created_at_timestamp - a.created_at_timestamp;
            }).slice(0, 20);
        }
    },

    mounted() {
        this.fetchJobData();
    }
}
</script>

<style>

</style>
