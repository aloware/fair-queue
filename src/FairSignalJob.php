<?php

namespace Aloware\FairQueue;

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

            list($partition, $jobSerialized) = $this->pop($repository, 'pop', 'partitions');

//            if (is_null($jobSerialized)) {
//                list($partition, $jobSerialized) = $this->pop($repository, 'popFailed', 'failedPartitions');
//            }

            if (is_null($jobSerialized)) {
                // no jobs found neither in normal nor in failed
                return;
            }

            $job = unserialize($jobSerialized);
        } catch (\Throwable $exception) {
            dump($exception);
            throw $exception;
        }

        try {
            $job->tries++;

            if (isset($job->uuid)) {
                $repository->expectAcknowledge(
                    $this->connection,
                    $this->queue,
                    $partition,
                    $job->uuid,
                    $jobSerialized
                );
            }

            $job->handle();

            // Update Fair Queue Stats
            $this->updateStats($job->uuid);

        } catch (\Throwable $e) {
            printf('[%s] %s' . PHP_EOL, get_class($job), $e->getMessage());

            // this will be retried later from failed job partitions

            $job->exception = [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'error' => $e->getMessage()
            ];

            $jobSerialized = serialize($job);

            $maxTries = $this->getQueueTries($this->queue);

            if ($job->tries >= $maxTries) {
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
        /** @var RepositoryInterface $repository */
        $repository = app(RepositoryInterface::class);

        $repository->push($this->queue, $this->partition, serialize($this->originalJob));

        // avoid unnecessary size allocation
        $this->originalJob = null;
    }

    public function onPartition($partition)
    {
        $this->partition = $partition;

        return $this;
    }

    private function pop($repository, $popMethod = 'pop', $partitionsMethod = 'partitions')
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

    private function selectPartition($repository, $partitionsMethod = 'partitions')
    {
        $partitions = $repository->$partitionsMethod($this->queue);

        if (empty($partitions)) {
            return null;
        }

        $partitionIndex = random_int(0, count($partitions) - 1);

        return $partitions[$partitionIndex];
    }

    public function updateStats($uuid)
    {
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
