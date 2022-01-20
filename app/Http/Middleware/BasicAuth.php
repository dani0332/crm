<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $AUTH_USER = \Config::get('constants.IMCRM_BASIC_AUTH_USER_NAME');
        $AUTH_PASS = \Config::get('constants.IMCRM_BASIC_AUTH_PASSWORD');
        $has_supplied_credentials = !(empty($request->getUser()) && empty($request->getPassword()));
        $is_not_authenticated = (
            !$has_supplied_credentials ||
            $request->getUser() != $AUTH_USER ||
            $request->getPassword()   != $AUTH_PASS
        );
        if ($is_not_authenticated) {
            return response()->json(['Authorization Required'], 401);
        }
        return $next($request);
    }
}
