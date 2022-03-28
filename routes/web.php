<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    // Dashboard Routes...
    Route::get('/abc', function() {
        return 'Fair Queue Works';
    });
    Route::get('/stats', 'DashboardStatsController@index')->name('fairqueue.stats.index');

    // Monitoring Routes...
    Route::get('/monitoring', 'MonitoringController@index')->name('fairqueue.monitoring.index');
    Route::post('/monitoring', 'MonitoringController@store')->name('fairqueue.monitoring.store');
    Route::get('/monitoring/{tag}', 'MonitoringController@paginate')->name('fairqueue.monitoring-tag.paginate');
    Route::delete('/monitoring/{tag}', 'MonitoringController@destroy')->name('fairqueue.monitoring-tag.destroy');

    // Job Metric Routes...
    Route::get('/metrics/jobs', 'JobMetricsController@index')->name('fairqueue.jobs-metrics.index');
    Route::get('/metrics/jobs/{id}', 'JobMetricsController@show')->name('fairqueue.jobs-metrics.show');

    // Queue Metric Routes...
    Route::get('/queues/{queue}/partitions', 'QueueMetricsController@queuePartitions')->name('fairqueue.queues-partitions.index');
    Route::get('/partitions/{partition}/jobs', 'QueueMetricsController@partitionJobs')->name('fairqueue.partitions-jobs.index');
    Route::get('/partitions/{partition}/jobs/{index}', 'QueueMetricsController@jobPreview')->name('fairqueue.job-preview.index');
    Route::get('/metrics/queues', 'QueueMetricsController@index')->name('fairqueue.queues-metrics.index');
    Route::get('/metrics/queues/{id}', 'QueueMetricsController@show')->name('fairqueue.queues-metrics.show');

});

// Catch-all Route...
Route::get('/{view?}', 'HomeController@index')->where('view', '(.*)')->name('fairqueue.index');