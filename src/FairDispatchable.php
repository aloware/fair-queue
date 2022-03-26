<?php

namespace Aloware\FairQueue;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Fluent;

trait FairDispatchable
{
    use Dispatchable;

    public $partition;

    public static function dispatch(...$arguments)
    {
        $job = new FairSignalJob(new static(...$arguments));

        return new PendingDispatch($job);
    }

    public static function dispatchIf($boolean, ...$arguments)
    {
        $job = new FairSignalJob(new static(...$arguments));

        return $boolean ? new PendingDispatch($job) : new Fluent;
    }

    public static function dispatchUnless($boolean, ...$arguments)
    {
        $job = new FairSignalJob(new static(...$arguments));

        return !$boolean ? new PendingDispatch($job) : new Fluent;
    }

    public static function dispatchSync(...$arguments)
    {
        $job = new FairSignalJob(new static(...$arguments));

        return app(Dispatcher::class)->dispatchSync($job);
    }

    public static function dispatchNow(...$arguments)
    {
        $job = new FairSignalJob(new static(...$arguments));

        return app(Dispatcher::class)->dispatchNow($job);
    }

    public static function dispatchAfterResponse(...$arguments)
    {
        $job = new FairSignalJob(new static(...$arguments));

        return app(Dispatcher::class)->dispatchAfterResponse($job);
    }

}
