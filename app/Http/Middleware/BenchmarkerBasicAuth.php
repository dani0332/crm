<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BenchmarkerBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = config('constants.BENCHMARKER_BASIC_AUTH_USERNAME');
        $pass = config('constants.BENCHMARKER_BASIC_AUTH_PASSWORD');

        $has_supplied_credentials = ! (empty($request->getUser()) && empty($request->getPassword()));

        $is_not_authenticated = (
            ! $has_supplied_credentials ||
            $request->getUser() != $user ||
            $request->getPassword() != $pass
        );
        if ($is_not_authenticated) {
            return response()->json(['Authorization Required'], 401);
        }

        return $next($request);
    }
}
