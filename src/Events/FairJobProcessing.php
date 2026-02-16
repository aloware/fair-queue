<?php

namespace Aloware\FairQueue\Events;

/**
 * FairJobProcessing
 */
class FairJobProcessing
{
    public function __construct(
        public $job
    ) {}
}