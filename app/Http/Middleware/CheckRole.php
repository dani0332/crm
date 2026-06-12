<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        if (! $request->user()->hasAnyRole($roles)) {
            abort(401, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
