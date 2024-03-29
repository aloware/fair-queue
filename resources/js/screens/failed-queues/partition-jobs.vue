<script type="text/ecmascript-6">
    export default {
        props: ['type'],

        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                loadingNewEntries: false,
                hasNewEntries: false,
                page: 1,
                perPage: 20,
                totalPages: 1,
                partitions: [],
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "FairQueue - Monitoring";

            this.loadJobs(this.$route.params.partition);

        },


        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearInterval(this.interval);
        },


        /**
         * Watch these properties for changes.
         */
        watch: {
            '$route'() {
                this.page = 1;

                this.loadJobs(this.$route.params.partition);
            }
        },

        methods: {
            /**
             * Load the jobs of of the given partition.
             */
            loadJobs(partition, starting = 0, refreshing = false) {
                if (!refreshing) {
                    this.ready = false;
                }

                this.$http.get(FairQueue.basePath + '/api/failed-queues/' + this.$route.params.queue +'/partitions/' + encodeURIComponent(partition) + '/jobs?starting_at=' + starting + '&limit=' + this.perPage)
                    .then(response => {
                        if (!this.$root.autoLoadsNewEntries && refreshing && this.jobs.length && _.first(response.data).id !== _.first(this.jobs).id) {
                            this.hasNewEntries = true;
                        } else {
                            this.jobs = response.data.jobs;

                            this.totalPages = response.data.total;
                        }

                        this.ready = true;
                    });
            },


            /**
             * Load new entries.
             */
            loadNewEntries() {
                this.jobs = [];

                this.loadJobs(this.$route.params.tag, 0, false);

                this.hasNewEntries = false;
            },

            /**
             * Load the jobs for the previous page.
             */
            previous() {
                this.loadJobs(this.$route.params.partition,
                    (this.page - 2) * this.perPage
                );

                this.page -= 1;

                this.hasNewEntries = false;
            },

            /**
             * Load the jobs for the next page.
             */
            next() {
                this.loadJobs(this.$route.params.partition,
                    this.page * this.perPage
                );

                this.page += 1;

                this.hasNewEntries = false;
            }
        }
    }
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Partition Jobs "{{ this.$route.params.partition }}"</h5>
            </div>
        <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
            </svg>

            <span>Loading...</span>
        </div>


        <div v-if="ready && jobs.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <span>There aren't any jobs for this partition.</span>
        </div>

        <table v-if="ready && jobs.length > 0" class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Job</th>
                </tr>
            </thead>

            <tbody>
            <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                <td colspan="100" class="text-center card-bg-secondary py-1">
                    <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                    <small v-if="loadingNewEntries">Loading...</small>
                </td>
            </tr>
            <tr v-for="job in jobs" :key="job.id">
                <td>
                    <router-link :title="job.name" :to="{ name: 'failed-job-preview', params: {  queue: $route.params.queue, partition: $route.params.partition, jobId: job.id }}">
                        {{ job.name }}
                    </router-link>
                </td>
            </tr>
            </tbody>

            <div v-if="ready && jobs.length" class="p-3 d-flex justify-content-between border-top">
                <button @click="previous" class="btn btn-secondary btn-md" :disabled="page==1">Previous</button>
                <button @click="next" class="btn btn-secondary btn-md" :disabled="page>=totalPages">Next</button>
            </div>
        </table>

    </div>
    </div>

</template>