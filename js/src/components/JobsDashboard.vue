<template>
    <div>

        <h4>Running</h4>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Job Name</th>
                    <th style="width: 175px">Progress</th>
                    <th>Created</th>
                    <th>Last Progress</th>
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
                            <button class="button" @click="abortJob(job)">abort</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <h4>Recently Finished</h4>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Job Name</th>
                    <th>Finished</th>
                    <th>Duration</th>
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
                        {{ job.active_run_time }} seconds
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
            $.getJSON(ajaxurl, {
                action: 'podlove-job-delete',
                job_id: job.id
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
