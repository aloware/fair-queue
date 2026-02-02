<?php

namespace Aloware\FairQueue\Events;

/**
 * FairJobQueuing
 */
class FairJobQueuing
{
    public function __construct(
        public $job
    ) {}
}
