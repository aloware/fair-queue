<script type="text/ecmascript-6">
    import Modal from '../../components/Modal.vue';
    export default {
        components: {Modal},
        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                saving: false,
                page: 1,
                perPage: 50,
                hasNewEntries: false,
                loadingNewEntries: false,
                totalPages: 10,
                queues: [],
                isRetryAllModalVisible: false,
                isModalVisible: false,
            };
        },

        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "FairQueue - Monitoring";

            this.loadQueues();

            this.refreshQueuesPeriodically()
        },

        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearInterval(this.failedQueuesInterval);
        },

        methods: {
            /**
             * Load the monitored queues.
             */
            loadQueues(starting = 0, refreshing = false) {
                if(!refreshing) {
                    this.ready = false;
                }

                this.$http.get(FairQueue.basePath + '/api/failed-queues?starting_at=' + starting + '&limit=' + this.perPage)
                    .then(response => {
                        this.queues = response.data;

                        this.ready = true;
                    });
            },
            /**
             * Refresh the failed-queues every period of time.
             */
            refreshQueuesPeriodically() {
                this.failedQueuesInterval = setInterval(() => {
                    if(this.ready) {
                        this.loadQueues(0, true);
                    }
                }, 3000);
            },
            closeModal() {
                this.isModalVisible = false;
                this.isRetryAllModalVisible = false;
            },
            showRetryAllModal() {
                this.isRetryAllModalVisible = true
            },
            showPurgeAllModal() {
                this.isModalVisible = true
            },
            submitRetryAll() {
                this.saving = true;
                this.$http.post(FairQueue.basePath + '/api/jobs/retry-failed-jobs/')
                    .then(response => {
                        this.saving = false;
                        this.closeModal();
                        this.$toasted.show(response.data.count + ' Jobs Returned to The Queue');
                    })
                    .catch(error => {
                        this.saving = false;
                    });
            },
            submitPurgeAll() {
                this.saving = true;
                this.$http.post(FairQueue.basePath + '/api/jobs/purge-failed-jobs/')
                    .then(response => {
                        this.saving = false;
                        this.closeModal();
                        this.loadQueues();
                        this.$toasted.show('Failed Jobs Purged Successfully');
                    })
                    .catch(error => {
                        this.saving = false;
                    });
            },
        }
    }
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Failed Queues</h5>

                <div>
                    <button @click="showRetryAllModal()" class="btn btn-primary btn-sm">Retry All</button>
                    <button @click="showPurgeAllModal()" class="btn btn-warning btn-sm">Purge All</button>
                </div>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>


            <div v-if="ready && queues.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>You're not monitoring any failed-queues.</span>
            </div>

            <table v-if="ready && queues.length > 0" class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Failed Queue Name</th>
                    <th>Queue Partitions</th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="queue in queues" :key="queue.queue">
                    <td>
                        <router-link :to="{ name: 'failed-queue-partitions', params: { queue:queue.queue }}" href="#">
                            {{ queue.queue }}
                        </router-link>
                    </td>
                    <td>{{ queue.count }}</td>
                </tr>
                </tbody>
            </table>
        </div>
        <Modal
            v-show="isModalVisible"
            @close="closeModal"
        >
            <template v-slot:header>
                Purge All
            </template>

            <template v-slot:body>
                <div style="width: 250px">Are you sure?</div>
            </template>

            <template v-slot:footer>
                <button @click="submitPurgeAll" :disabled="saving" class="btn btn-primary btn-sm">
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
                Retry All
            </template>

            <template v-slot:body>
                <div style="width: 250px">Are you sure?</div>
            </template>

            <template v-slot:footer>
                <button @click="submitRetryAll" :disabled="saving" class="btn btn-primary btn-sm">
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