<script type="text/ecmascript-6">
    import Modal from '../../components/Modal.vue';
    export default {
        props: ['type'],
        components: {Modal},

        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                loadingNewEntries: false,
                hasNewEntries: false,
                isModalVisible: false,
                amount: '',
                page: 1,
                perPage: 3,
                totalPages: 1,
                partitions: []
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "FairQueue - Monitoring";

            this.loadPartitions(this.$route.params.queue);

            this.refreshJobsPeriodically();
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

                this.loadPartitions(this.$route.params.queue);
            }
        },


        methods: {
            /**
             * Load the partitions of the given queue.
             */
            loadPartitions(queue, starting = 0, refreshing = false) {
                if (!refreshing) {
                    this.ready = false;
                }

                this.$http.get(FairQueue.basePath + '/api/failed-queues/' + encodeURIComponent(queue) + '/partitions?starting_at='+ starting +'&limit=' + this.perPage)
                    .then(response => {
                        if (!this.$root.autoLoadsNewEntries && refreshing && this.partitions.length && _.first(response.data).id !== _.first(this.partitions).id) {
                            this.hasNewEntries = true;
                        } else {
                            this.partitions = response.data;

                            this.totalPages = Math.ceil(response.data.total / this.perPage);
                        }

                        this.ready = true;
                    });
            },

            /**
             * Refresh the jobs every period of time.
             */
            refreshJobsPeriodically() {
                this.interval = setInterval(() => {
                    if (this.page != 1) {
                        return;
                    }
                    if(this.ready) {
                        this.loadPartitions(this.$route.params.queue, 0, true);
                    }
                }, 3000);
            },

            showModal() {
                this.isModalVisible = true;
            },
            closeModal() {
                this.isModalVisible = false;
            }
        }
    }
</script>

<template>
    <div>
        <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
            </svg>

            <span>Loading...</span>
        </div>


        <div v-if="ready && partitions.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
            <span>There aren't any partitions for this queue.</span>
        </div>

        <table v-if="ready && partitions.length > 0" class="table table-hover table-sm mb-0">
            <thead>
            <tr>
                <th>Failed Partition Name</th>
                <th>Number Of Jobs</th>
            </tr>
            </thead>

            <tbody>
            <tr v-if="hasNewEntries" key="newEntries" class="dontanimate">
                <td colspan="100" class="text-center card-bg-secondary py-1">
                    <small><a href="#" v-on:click.prevent="loadNewEntries" v-if="!loadingNewEntries">Load New Entries</a></small>

                    <small v-if="loadingNewEntries">Loading...</small>
                </td>
            </tr>

            <tr v-for="partition in partitions" :key="partition.name">
                <td>
                    <router-link :title="partition.name" :to="{ name: 'failed-partition-jobs', params: { partition: partition.name, queue: $route.params.queue }}">
                        {{ jobBaseName(partition.name) }}
                    </router-link>
                </td>
                <td>
                    <span>{{ partition.count }}</span>
                </td>
            </tr>
            </tbody>
        </table>

    </div>

</template>