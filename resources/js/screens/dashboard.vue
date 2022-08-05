<script type="text/ecmascript-6">
    import _ from 'lodash';
    import moment from 'moment';

    export default {
        components: {},


        /**
         * The component's data.
         */
        data() {
            return {
                stats: {},
                workers: [],
                workload: [],
                ready: false,
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "FairQueue - Dashboard";

            this.refreshStatsPeriodically();
        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearTimeout(this.timeout);
        },


        computed: {
            /**
             * Determine the recent job period label.
             */
            recentJobsPeriod() {
                return !this.ready
                    ? 'Jobs past hour'
                    : `Jobs past ${this.determinePeriod(this.stats.periods.recentJobs)}`;
            },

        },


        methods: {
            /**
             * Load the general stats.
             */
            loadStats() {
                return this.$http.get(FairQueue.basePath + '/api/stats')
                    .then(response => {
                        this.stats = response.data;

                        if (_.values(response.data.wait)[0]) {
                            this.stats.max_wait_time = _.values(response.data.wait)[0];
                            this.stats.max_wait_queue = _.keys(response.data.wait)[0].split(':')[1];
                        }
                    }).catch( error => {
                        this.$toasted.show('Error: ' + error.response.data.message);
                        this.ready = true;
                    });
            },


            /**
             * Load the workers stats.
             */
            loadWorkers() {
                return this.$http.get(FairQueue.basePath + '/api/masters')
                    .then(response => {
                        this.workers = response.data;
                    }).catch( error => {
                        this.$toasted.show('Error: ' + error.response.data.message);
                        this.ready = true;
                    });
            },


            /**
             * Load the workload stats.
             */
            loadWorkload() {
                return this.$http.get(FairQueue.basePath + '/api/workload')
                    .then(response => {
                        this.workload = response.data;
                    }).catch( error => {
                        this.$toasted.show('Error: ' + error.response.data.message);
                        this.ready = true;
                    });
            },


            /**
             * Refresh the stats every period of time.
             */
            refreshStatsPeriodically() {
                Promise.all([
                    this.loadStats(),
                ]).then(() => {
                    this.ready = true;

                    this.timeout = setTimeout(() => {
                        this.refreshStatsPeriodically(false);
                    }, 5000);
                });
            },


            /**
             *  Count processes for the given supervisor.
             */
            countProcesses(processes) {
                return _.chain(processes).values().sum().value().toLocaleString()
            },


            /**
             *  Format the Supervisor display name.
             */
            superVisorDisplayName(supervisor, worker) {
                return _.replace(supervisor, worker + ':', '');
            },


            /**
             *
             * @returns {string}
             */
            humanTime(time) {
                return moment.duration(time, "seconds").humanize().replace(/^(.)|\s+(.)/g, function ($1) {
                    return $1.toUpperCase();
                });
            },


            /**
             * Determine the unit for the given timeframe.
             */
            determinePeriod(minutes) {
                return moment.duration(moment().diff(moment().subtract(minutes, "minutes"))).humanize().replace(/^An?/i, '');
            }
        }
    }
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Overview</h5>
            </div>

            <div class="card-bg-secondary">
                <div class="d-flex">
                    <div class="w-50 border-right border-bottom">
                        <div class="p-4">
                            <small class="text-uppercase">Total Queues</small>

                            <h4 class="mt-4 mb-0">
                                {{ stats.totalQueues ? stats.totalQueues.toLocaleString() : 0 }}
                            </h4>
                        </div>
                    </div>

                    <div class="w-50 border-right border-bottom">
                        <div class="p-4">
                            <small class="text-uppercase">Total Jobs</small>

                            <h4 class="mt-4 mb-0">
                                {{ stats.totalJobs ? stats.totalJobs.toLocaleString() : 0 }}
                            </h4>
                        </div>
                    </div>

                    <div class="w-50 border-right border-bottom">
                        <div class="p-4">
                            <small class="text-uppercase">Total Failed Jobs</small>

                            <h4 class="mt-4 mb-0">
                                {{ stats.totalFailedJobs ? stats.totalFailedJobs.toLocaleString() : 0 }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-bg-secondary">
                <div class="d-flex">
                    <div class="w-50 border-right border-bottom">
                        <div class="p-4">
                            <small class="text-uppercase">Processed Jobs Past Minute</small>

                            <h4 class="mt-4 mb-0">
                                {{ stats.processedJobsInPastMinute ? stats.processedJobsInPastMinute.toLocaleString() : 0 }}
                            </h4>
                        </div>
                    </div>

                    <div class="w-50 border-right border-bottom">
                        <div class="p-4">
                            <small class="text-uppercase">Processed Jobs Past 20 Minutes</small>

                            <h4 class="mt-4 mb-0">
                                {{ stats.processedJobsInPast20Minutes ? stats.processedJobsInPast20Minutes.toLocaleString() : 0 }}
                            </h4>
                        </div>
                    </div>

                    <div class="w-50 border-right border-bottom">
                        <div class="p-4">
                            <small class="text-uppercase">Processed Jobs Past Hour</small>

                            <h4 class="mt-4 mb-0">
                                {{ stats.processedJobsInPastHour ? stats.processedJobsInPastHour.toLocaleString() : 0 }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4" v-if="workload.length">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Current Workload</h5>
            </div>

            <table class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Queue</th>
                    <th>Processes</th>
                    <th>Jobs</th>
                    <th class="text-right">Wait</th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="queue in workload">
                    <td>
                        <span>{{ queue.name.replace(/,/g, ', ') }}</span>
                    </td>
                    <td>{{ queue.processes ? queue.processes.toLocaleString() : 0 }}</td>
                    <td>{{ queue.length ? queue.length.toLocaleString() : 0 }}</td>
                    <td class="text-right">{{ humanTime(queue.wait) }}</td>
                </tr>
                </tbody>
            </table>
        </div>


        <div class="card mt-4" v-for="worker in workers" :key="worker.name">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>{{ worker.name }}</h5>
            </div>

            <table class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Supervisor</th>
                    <th>Processes</th>
                    <th>Queues</th>
                    <th class="text-right">Balancing</th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="supervisor in worker.supervisors">
                    <td>{{ superVisorDisplayName(supervisor.name, worker.name) }}</td>
                    <td>{{ countProcesses(supervisor.processes) }}</td>
                    <td>{{ supervisor.options.queue.replace(/,/g, ', ') }}</td>
                    <td class="text-right">
                        ({{ supervisor.options.balance.charAt(0).toUpperCase() + supervisor.options.balance.slice(1) }})
                    </td>
                </tr>
                </tbody>
            </table>
        </div>


    </div>
</template>
