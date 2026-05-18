<?php

namespace App\Jobs\Middleware;

use Illuminate\Http\Request;

/**
 * Replaces the container-bound HTTP request with an empty instance before the job runs.
 *
 * Queue workers are long-lived; without this, request()->merge() and similar mutations
 * can leak into the next job that uses the request helper.
 */
class FreshRequest
{
    public function handle(object $job, callable $next): mixed
    {
        app()->instance('request', Request::create('/'));

        return $next($job);
    }
}
