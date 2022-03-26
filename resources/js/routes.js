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
        path: '/recent-jobs',
        name: 'recent-jobs',
        component: require('./screens/recentJobs/index').default,
    },

    {
        path: '/recent-jobs/:jobId',
        name: 'recent-jobs-preview',
        component: require('./screens/recentJobs/job').default,
    },

    {
        path: '/failed',
        name: 'failed-jobs',
        component: require('./screens/failedJobs/index').default,
    },

    {
        path: '/failed/:jobId',
        name: 'failed-jobs-preview',
        component: require('./screens/failedJobs/job').default,
    },
];
