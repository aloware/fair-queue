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
        path: '/partitions/:partition/jobs',
        name: 'partition-jobs',
        component: require('./screens/queues/partition-jobs').default,
    },

    {
        path: '/partitions/:partition/jobs/:jobId',
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

];
