<?php

namespace Aloware\FairQueue;

use Closure;

class FairQueue
{
    /**
     * The callback that should be used to authenticate FairQueue users.
     *
     * @var \Closure
     */
    public static $authUsing;

    /**
     * Determine if the given request can access the FairQueue dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function check($request)
    {
        return (static::$authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }

    /**
     * Set the callback that should be used to authenticate FairQueue users.
     *
     * @param  \Closure  $callback
     * @return static
     */
    public static function auth(Closure $callback)
    {
        static::$authUsing = $callback;

        return new static;
    }
}
