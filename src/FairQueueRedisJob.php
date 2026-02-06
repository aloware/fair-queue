<?php

namespace Aloware\FairQueue;

/**
 * Wrapper for jobs pushed to Redis by Fair Queue when extra payload is attached.
 * Carries the real job and an arbitrary payload array so both survive serialization.
 */
class FairQueueRedisJob
{
    /**
     * The real job to run.
     *
     * @var object
     */
    public $job;

    /**
     * Extra payload to keep with the job (arbitrary keys, e.g. _causer_chain, custom data).
     *
     * @var array<string, mixed>
     */
    public $payload = [];

    public function __construct(object $job, array $payload = [])
    {
        $this->job = $job;
        $this->payload = $payload;
    }
}
