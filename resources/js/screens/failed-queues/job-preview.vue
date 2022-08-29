<template>
    <div>
        <div class="card mt-4" v-if="ready">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Failed Job Data</h5>
                <a data-toggle="collapse" href="#collapseData" role="button">
                    Collapse
                </a>
            </div>

            <div class="card-body">
                <hr>
                <div><strong>Queue:</strong> {{ this.$route.params.queue }}</div>
                <div><strong>Partition:</strong> {{ this.$route.params.partition }}</div>
                <div><strong>Job:</strong> {{ job.name }}</div>
                <hr>
            </div>

            <div class="card-body code-bg text-white collapse show" id="collapseData">
                <vue-json-pretty :data="prettyPrintJob(job.payload)"></vue-json-pretty>
            </div>
        </div>
    </div>
</template>

<script type="text/ecmascript-6">
    import phpunserialize from 'phpunserialize'

    export default {
        /**
         * The component's data.
         */
        data() {
            return {
                ready: false,
                job: {}
            };
        },

        computed: {
            unserialized() {
                return phpunserialize(this.job);
            },
        },

        /**
         * Prepare the component.
         */
        mounted() {
            this.loadJob(this.$route.params.jobId);

            document.title = "FairQueue - Job Detail";
        },

        methods: {
            /**
             * Load a job by the given ID.
             */
            loadJob(id) {
                this.ready = false;

                this.$http.get(FairQueue.basePath + '/api/failed-queues/' + this.$route.params.queue + '/partitions/' + encodeURIComponent(this.$route.params.partition) + '/jobs/' + id)
                    .then(response => {
                        this.job = response.data;

                        this.ready = true;
                    });
            },

            /**
             * Pretty print serialized job.
             */
            prettyPrintJob(data) {
                return phpunserialize(data)
            }
        }
    }
</script>