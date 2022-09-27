<script type="text/ecmascript-6">
    import PageNavigationControls from '../../components/PageNavigationControls.vue'
    export default {
        components: {PageNavigationControls}
    }
</script>

<template>
    <div>
        <div class="card">
            <PageNavigationControls />
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Queue Partitions "{{ $route.params.queue }}"</h5>
            </div>

            <router-view/>
        </div>

    </div>
</template>
