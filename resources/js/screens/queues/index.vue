<script type="text/ecmascript-6">
    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                queues: []
            };
        },


        /**
         * Prepare the component.
         */
        mounted() {
            document.title = "FairQueue - Monitoring";

            this.loadQueues();

        },


        methods: {
            /**
             * Load the monitored queues.
             */
            loadQueues() {
                this.ready = false;

                this.$http.get(Horizon.basePath + '/api/monitoring')
                    .then(response => {
                        this.queues = response.data;

                        this.ready = true;
                    });
            },

        }
    }
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Queues</h5>
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
                    <th>Queue Name</th>
                    <th>Queue Partitions</th>
                </tr>
                </thead>

                <tbody>
                <tr v-for="queue in queues" :key="queue.queue">
                    <td>
                        <router-link :to="{ name: 'queue-partitions', params: { queue:queue.queue }}" href="#">
                            {{ queue.queue }}
                        </router-link>
                    </td>
                    <td>{{ queue.count }}</td>
                </tr>
                </tbody>
            </table>
        </div>

    </div>
</template>
