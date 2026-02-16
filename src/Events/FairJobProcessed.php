<?php

namespace Aloware\FairQueue\Events;

/**
 * FairJobProcessed
 */
class FairJobProcessed
{
    public function __construct(
        public $job
    ) {}
}
