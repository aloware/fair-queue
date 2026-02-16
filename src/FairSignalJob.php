<?php

namespace Aloware\FairQueue;

use Aloware\FairQueue\Events\FairJobFailed;
use Aloware\FairQueue\Events\FairJobProcessed;
use Aloware\FairQueue\Events\FairJobProcessing;
use Aloware\FairQueue\Events\FairJobQueuing;
use Aloware\FairQueue\Facades\FairQueue;
use Aloware\FairQueue\Interfaces\RepositoryInterface;
use Aloware\FairQueue\Repositories\RedisKeys;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Str;

class FairSignalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, RedisKeys;

    public $partition;

    public $originalJob;

    public function __construct($job)
    {
        if (!is_null($job)) {
            $job->uuid = Str::uuid()->toString();
        }

        $this->originalJob = $job;
    }

    public function handle()
    {
        try {
            /** @var RepositoryInterface $repository */
            $repository = app(RepositoryInterface::class);

            list($partition, $jobSerialized) = $this->pop($repository, 'pop', 'getRandomPartitionName');

//            if (is_null($jobSerialized)) {
//                list($partition, $jobSerialized) = $this->pop($repository, 'popFailed', 'failedPartitions');
//            }

            if (is_null($jobSerialized)) {
                // no jobs found neither in normal nor in failed
                return;
            }

            $data = unserialize($jobSerialized);
        } catch (\Throwable $exception) {
            dump($exception);
            throw $exception;
        }

        $job = $data instanceof FairQueueRedisJob ? $data->job : $data;

        try {
            if (isset($job->tries)) {
                $job->tries++;
            }

            if (isset($job->uuid)) {
                $repository->expectAcknowledge(
                    $this->connection,
                    $this->queue,
                    $partition,
                    $job->uuid,
                    $jobSerialized
                );
            }

            event(new FairJobProcessing($data));

            $job->handle();

            event(new FairJobProcessed($data));

            // Update Fair Queue Stats
            $this->updateStats($job->uuid);

        } catch (\Throwable $e) {

            event(new FairJobFailed($job, $e));

            printf('[%s] %s' . PHP_EOL, get_class($job), $e->getMessage());

            // this will be retried later from failed job partitions

            $job->exception = [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'error' => $e->getMessage()
            ];

            $jobSerialized = serialize($job);

            $maxTries = $this->getQueueTries($this->queue);

            if (!isset($job->tries) || $job->tries >= $maxTries) {
                $repository->pushFailed($this->queue, $partition, $jobSerialized);
            } else {
                $repository->lPush($this->queue, $partition, $jobSerialized);
                $repository->generateFakeSignals($this->queue, 1);
            }

            throw $e;
        } finally {
            if (isset($job->uuid)) {
                $repository->acknowledge($this->connection, $this->queue, $partition, $job->uuid);
            }
        }
    }

    public function getQueueTries($queue)
    {
        if(!$queue) {
            $queue = 'default';
        }

        // get queue tries from fair-queue config
        $tries = config("fair-queue.queues.{$queue}.tries");

        // sanity check
        if(!$tries) {
            $tries = config('fair-queue.queues.default.tries');
        }

        return $tries;
    }

    public function addToPartition()
    {
        $job = new FairQueueRedisJob($this->originalJob);

        event(new FairJobQueuing($job));

        /** @var RepositoryInterface $repository */
        $repository = app(RepositoryInterface::class);

        $repository->push($this->queue, $this->partition, serialize($job));

        // avoid unnecessary size allocation
        $this->originalJob = null;
    }

    public function onPartition($partition)
    {
        $this->partition = $partition;

        return $this;
    }

    private function pop($repository, $popMethod = 'pop', $partitionsMethod = 'getRandomPartitionName')
    {
        $partition = $this->selectPartition($repository, $partitionsMethod);

        if (is_null($partition)) {
            return [null, null];
        }

        $tries = 0;

        while (empty($jobSerialized = $repository->$popMethod($this->queue, $partition))) {
            // maybe this partition has run out of jobs during the
            //  random selection process, so try getting a fresh list
            //  of partitions and pick another one.

            usleep(100 * 1000); // 100ms

            $tries++;
            if ($tries >= 10) {
                // no jobs available to process (concluded after 10
                // times retry with a second delay each).

                return [null, null];
            }

            $partition = $this->selectPartition($repository, $partitionsMethod);
        }

        return [$partition, $jobSerialized];
    }

    private function selectPartition($repository, $partitionsMethod = 'getRandomPartitionName')
    {
        return $repository->$partitionsMethod($this->queue);
    }

    public function updateStats($uuid)
    {
        // if FairQueue stats config is disabled ignore updating stats
        if(!config('fair-queue.stats.enabled')) {
            return;
        }

        $redis = FairQueue::getConnection();
        $queue = $this->queue;
        $partition = $this->partition;

        $past_minute_key = $this->partitionProcessedJobsInPastMinutesKey($queue, $partition, 1);
        $past_20minute_key = $this->partitionProcessedJobsInPastMinutesKey($queue, $partition, 20);
        $past_60minute_key = $this->partitionProcessedJobsInPastMinutesKey($queue, $partition, 60);
        $redis->zadd($past_minute_key, now()->getPreciseTimestamp(3), $uuid);
        $redis->zadd($past_20minute_key, now()->getPreciseTimestamp(3), $uuid);
        $redis->zadd($past_60minute_key, now()->getPreciseTimestamp(3), $uuid);
    }
}
