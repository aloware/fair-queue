<?php

namespace Aloware\FairQueue\Exceptions;

class SampleNotFoundException extends FairQueueException
{
    public function __construct($queue)
    {
        parent::__construct("no sample signal found for the queue '$queue'");
    }
}
