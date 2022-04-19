<script type="text/ecmascript-6">
    export default {}
</script>

<template>
    <div>
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5>Failed Queue Partitions "{{ $route.params.queue }}"</h5>
            </div>

            <router-view/>
        </div>

    </div>
</template>
