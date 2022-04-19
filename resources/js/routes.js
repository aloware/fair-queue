export default [
    { path: '/', redirect: '/dashboard' },

    {
        path: '/dashboard',
        name: 'dashboard',
        component: require('./screens/dashboard').default,
    },

    {
        path: '/queues',
        name: 'queues',
        component: require('./screens/queues/index').default,
    },

    {
        path: '/queues/:queue',
        component: require('./screens/queues/queue').default,
        children: [
            {
                path: 'partitions',
                name: 'queue-partitions',
                component: require('./screens/queues/queue-partitions').default
            }
        ],
    },

    {
        path: '/queues/:queue/partitions/:partition/jobs',
        name: 'partition-jobs',
        component: require('./screens/queues/partition-jobs').default,
    },

    {
        path: '/queues/:queue/partitions/:partition/jobs/:jobId',
        name: 'job-preview',
        component: require('./screens/queues/job-preview').default,
    },

    { path: '/metrics', redirect: '/metrics/jobs' },

    {
        path: '/metrics/',
        component: require('./screens/metrics/index').default,
        children: [
            {
                path: 'jobs',
                name: 'metrics-jobs',
                component: require('./screens/metrics/jobs').default,
            },
            {
                path: 'queues',
                name: 'metrics-queues',
                component: require('./screens/metrics/queues').default,
            },
        ],
    },

    {
        path: '/metrics/:type/:slug',
        name: 'metrics-preview',
        component: require('./screens/metrics/preview').default,
    },

    {
        path: '/failed-queues',
        name: 'failed-queues',
        component: require('./screens/failed-queues/index').default,
    },

    {
        path: '/failed-queues/:queue',
        component: require('./screens/failed-queues/queue').default,
        children: [
            {
                path: 'partitions',
                name: 'failed-queue-partitions',
                component: require('./screens/failed-queues/queue-partitions').default
            }
        ],
    },

    {
        path: '/failed-queues/:queue/partitions/:partition/jobs',
        name: 'failed-partition-jobs',
        component: require('./screens/failed-queues/partition-jobs').default,
    },

    {
        path: '/failed-queues/:queue/partitions/:partition/jobs/:jobId',
        name: 'failed-job-preview',
        component: require('./screens/failed-queues/job-preview').default,
    },

];
