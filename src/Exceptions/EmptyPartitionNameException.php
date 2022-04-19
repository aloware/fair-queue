<?php

namespace Aloware\FairQueue\Exceptions;

class EmptyPartitionNameException extends FairQueueException
{
    public function __construct()
    {
        parent::__construct("partition name cannot be empty");
    }
}
