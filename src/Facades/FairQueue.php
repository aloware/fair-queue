<?php
namespace Aloware\FairQueue\Facades;

use Illuminate\Support\Facades\Facade;

class FairQueue extends Facade {
    protected static function getFacadeAccessor()
    {
        return static::class;
    }

    public static function shouldProxyTo($class)
    {
        app()->singleton(self::getFacadeAccessor(), $class);
    }
}