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
                saving: false,
                loadingNewEntries: false,
                hasNewEntries: false,
                isModalVisible: false,
                isRetryAllModalVisible: false,
                amount: '',
                page: 1,
                perPage: 3,
                totalPages: 1,
                selectedPartition: null,
                partitions: [],
                selectedPartition: null,
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
                if(this.fetching) {
                    return;
                }
                this.fetching = true;

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
                        this.fetching = false;
                    }).catch( error => {
                        this.$toasted.show('Error: ' + error.response.data.message);
                        this.ready = true;
                        this.fetching = false;
                    });
            },
            closeModal() {
                this.isModalVisible = false;
                this.isRetryAllModalVisible = false;
            },
            showRetryModal(partition = null) {
                this.selectedPartition = partition;
                this.isRetryAllModalVisible = true
            },
            retryFailedJobs() {
                console.log("Partition:", this.selectedPartition);
                this.saving = true;
                this.$http.post(this.getUrl('retry-failed-jobs', this.$route.params.queue, this.selectedPartition))
                    .then(response => {
                        this.saving = false;
                        this.closeModal();
                        this.resetSelectedPartition();
                        this.$toasted.show(response.data.count + ' Jobs Returned to The Queue');
                    })
                    .catch(error => {
                        this.saving = false;
                    });
            },
            purgeFailedJobs() {
                this.saving = true;
                this.$http.post(this.getUrl('purge-failed-jobs', this.$route.params.queue, this.selectedPartition))
                    .then(response => {
                        this.saving = false;
                        this.closeModal();
                        this.resetSelectedPartition();
                        this.$toasted.show('Failed Jobs Purged Successfully');
                    })
                    .catch(error => {
                        this.saving = false;
                    });
            },
            showPurgeModal(partition = null) {
                this.selectedPartition = partition;
                this.isModalVisible = true
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

            showModal(partition) {
                this.selectedPartition = partition
                this.isModalVisible = true;
            },
            getUrl(action, queue, partition) {
                let url = FairQueue.basePath + '/api/jobs/' + action
                if(queue) {
                    url += '/' + queue;
                }
                if(partition) {
                    url += '/' + partition;
                }
                return url;
            },
            resetSelectedPartition() {
                this.selectedPartition = null;
            }
        }
    }
</script>

<template>
    <div>
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5>Failed Queue Partitions "{{ $route.params.queue }}"</h5>

            <div>
                <button @click="showRetryModal()" class="btn btn-primary btn-sm">Retry All</button>
                <button @click="showPurgeModal()" class="btn btn-warning btn-sm">Purge All</button>
            </div>
        </div>
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
                <th></th>
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
                <!-- <td>
                    <button @click="showModal(partition.name)" class="btn btn-primary btn-sm">Retry All</button>
                </td> -->
                <td class="gen-btn">
                    <button @click="showRetryModal(partition.name)" class="btn btn-primary btn-sm">Retry</button>
                    <button @click="showPurgeModal(partition.name)" class="btn btn-warning btn-sm">Purge</button>
                </td>
            </tr>
            </tbody>
        </table>

        <Modal
            v-show="isModalVisible"
            @close="closeModal"
        >
            <template v-slot:header>
                Purge {{ selectedPartition ? 'Partition' : 'All' }} Jobs
            </template>

            <template v-slot:body>
                <div style="width: 250px">Are you sure?</div>
            </template>

            <template v-slot:footer>
                <button @click="purgeFailedJobs" :disabled="saving" class="btn btn-primary btn-sm">
                    <svg v-if="saving" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                        <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                    </svg>
                    <span v-else>Confirm</span>
                </button>
            </template>
        </Modal>

        <Modal
            v-show="isRetryAllModalVisible"
            @close="closeModal"
        >
            <template v-slot:header>
                Retry {{ selectedPartition ? 'Partition' : 'All' }} Jobs
            </template>

            <template v-slot:body>
                <div style="width: 250px">Are you sure?</div>
            </template>

            <template v-slot:footer>
                <button @click="retryFailedJobs" :disabled="saving" class="btn btn-primary btn-sm">
                    <svg v-if="saving" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                        <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                    </svg>
                    <span v-else>Confirm</span>
                </button>
            </template>
        </Modal>

    </div>

</template>

<style scoped>
.gen-btn {
    text-align: right;
}
</style>