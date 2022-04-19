<?php

namespace Aloware\FairQueue;

use Illuminate\Contracts\Bus\Dispatcher;

class PendingDispatch extends \Illuminate\Foundation\Bus\PendingDispatch
{
    public function fairConsume($partition)
    {
        $this->job->partition = $partition;

        return $this;
    }

    public function tries($number = 1)
    {
        $this->job->originalJob->maxTries = $number;

        return $this;
    }

    public function __destruct()
    {
        $this->job->addToPartition();

        app(Dispatcher::class)->dispatch($this->job);
    }
}
