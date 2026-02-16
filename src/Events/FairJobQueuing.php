<?php

namespace Aloware\FairQueue\Events;

use Aloware\FairQueue\FairQueueRedisJob;

/**
 * FairJobQueuing
 */
class FairJobQueuing
{
    public function __construct(
        public FairQueueRedisJob $job
    ) {}
}
