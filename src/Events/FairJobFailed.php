<?php

namespace Aloware\FairQueue\Events;

use Throwable;

/**
 * FairJobFailed
 */
class FairJobFailed
{
    public function __construct(
        public $job,
        public Throwable $exception,
    ) {}
}
