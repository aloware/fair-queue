<script type="text/ecmascript-6">
    import Modal from '../..//components/Modal.vue';
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
                isFakeSignalModalVisible: false,
                selectedQueue: '',
                fakeSignalAmount: '',
                isModalVisible: false,
                amount: 5
            };
        },

        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "FairQueue - Monitoring";

            this.loadQueues();

            this.refreshQueuesPeriodically();
        },

        /**
         * Clean after the component is destroyed.
         */
        destroyed() {
            clearInterval(this.queuesInterval);
        },

        methods: {
            /**
             * Load the monitored queues.
             */
            loadQueues(starting = 0, refreshing = false) {
                if(!refreshing) {
                    this.ready = false;
                }

                this.$http.get(FairQueue.basePath + '/api/monitoring?starting_at=' + starting + '&limit=' + this.perPage)
                    .then(response => {
                        this.queues = response.data;

                        this.ready = true;
                    });
            },
            /**
             * Refresh the queues every period of time.
             */
            refreshQueuesPeriodically() {
                this.queuesInterval = setInterval(() => {
                    if(this.ready) {
                        this.loadQueues(0, true);
                    }
                }, 3000);
            },

            closeModal() {
                this.isModalVisible = false;
                this.isFakeSignalModalVisible = false;
            },

            showFakeSignalModal(queue) {
                this.isFakeSignalModalVisible = true
                this.selectedQueue = queue
            },

            showRecoverFailedJobsModal(queue) {
                this.isModalVisible = true
            },

            generateFakeSignal() {
                this.saving = true;
                this.$http.post(FairQueue.basePath + '/api/fake-signal/' + this.selectedQueue + '?amount=' + this.fakeSignalAmount)
                    .then(response => {
                        this.saving = false;
                        this.closeModal();
                        this.$toasted.show('Fake Signals Generated Successfully');
                    })
                    .catch(error => {
                        this.$toasted.show('Error: ' + error.response.data.message);
                        this.saving = false;
                    });
            },

            recoverLostJobs() {
                this.saving = true;
                this.$http.post(FairQueue.basePath + '/api/recover-lost-jobs?amount=' + this.amount)
                    .then(response => {
                        this.saving = false;
                        this.closeModal();
                        this.$toasted.show(response.data.recovered + ' Jobs Recovered');
                    })
                    .catch(error => {
                        this.$toasted.show('Error: ' + error.response.data.message);
                        this.saving = false;
                    });
            }
        }
    }
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Queues</h5>

                <button @click="showRecoverFailedJobsModal()" class="btn btn-primary btn-sm">Recover Lost Jobs</button>
            </div>

            <div v-if="!ready" class="d-flex align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                    <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                </svg>

                <span>Loading...</span>
            </div>


            <div v-if="ready && queues.length == 0" class="d-flex flex-column align-items-center justify-content-center card-bg-secondary p-5 bottom-radius">
                <span>You're not monitoring any queues.</span>
            </div>

            <table v-if="ready && queues.length > 0" class="table table-hover table-sm mb-0">
                <thead>
                <tr>
                    <th>Queue</th>
                    <th>Partitions</th>
                    <th>Jobs</th>
                    <th>Processed In 1 Minute</th>
                    <th>Processed In 20 Minutes</th>
                    <th></th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="queue in queues" :key="queue.queue">
                    <td>
                        <router-link :to="{ name: 'queue-partitions', params: { queue:queue.queue }}" href="#">
                            {{ queue.queue }}
                        </router-link>
                    </td>
                    <td>{{ queue.partitions_count }}</td>
                    <td>{{ queue.jobs_count }}</td>
                    <td>{{ queue.processed_jobs_count_1_min }}</td>
                    <td>{{ queue.processed_jobs_count_20_min }}</td>
                    <td class="gen-btn">
                        <button @click="showFakeSignalModal(queue.queue)" class="btn btn-primary btn-sm">Fake Signal Gen</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <Modal
            v-show="isModalVisible"
            @close="closeModal"
        >
            <template v-slot:header>
                Recover Lost Jobs
            </template>

            <template v-slot:body>
                <div>
                    <label for="minutes-ago">Age (minutes)</label>
                </div>
                <input v-model="amount" type="number" id="minutes-ago" autocomplete="off" placeholder="Enter anount of minutes"/>
            </template>

            <template v-slot:footer>
                <button @click="recoverLostJobs" :disabled="!amount || saving" class="btn btn-primary btn-sm">
                    <svg v-if="saving" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                        <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                    </svg>
                    <span v-else>Recover</span>
                </button>
            </template>
        </Modal>

        <Modal
            v-show="isFakeSignalModalVisible"
            @close="closeModal"
        >
            <template v-slot:header>
                Generate Fake Signal
            </template>

            <template v-slot:body>
                <div>
                    <label for="fakeSignalAmount">Number of fake signals</label>
                </div>
                <input v-model="fakeSignalAmount" type="number" id="fakeSignalAmount" autocomplete="off" placeholder="For example: 100"/>
            </template>

            <template v-slot:footer>
                <button @click="generateFakeSignal" :disabled="!fakeSignalAmount || saving" class="btn btn-primary btn-sm">
                    <svg v-if="saving" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="icon spin mr-2 fill-text-color">
                        <path d="M12 10a2 2 0 0 1-3.41 1.41A2 2 0 0 1 10 8V0a9.97 9.97 0 0 1 10 10h-8zm7.9 1.41A10 10 0 1 1 8.59.1v2.03a8 8 0 1 0 9.29 9.29h2.02zm-4.07 0a6 6 0 1 1-7.25-7.25v2.1a3.99 3.99 0 0 0-1.4 6.57 4 4 0 0 0 6.56-1.42h2.1z"></path>
                    </svg>
                    <span v-else>Generate</span>
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