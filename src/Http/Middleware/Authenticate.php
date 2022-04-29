<?php

namespace Aloware\FairQueue\Http\Middleware;

use Aloware\FairQueue\FairQueue;

class Authenticate
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|null
     */
    public function handle($request, $next)
    {
        return FairQueue::check($request) ? $next($request) : abort(403);
    }
}
