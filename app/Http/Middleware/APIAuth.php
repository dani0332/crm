<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class APIAuth
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $authUserName = config('constants.IMCRM_BASIC_AUTH_USER_NAME');
        $authPassword = config('constants.IMCRM_BASIC_NEW_AUTH_PASSWORD');
        $hasSuppliedCredentials = ! (empty($request->getUser()) && empty($request->getPassword()));
        $isNotAuthenticated = (
            ! $hasSuppliedCredentials ||
            $request->getUser() !== $authUserName ||
            $request->getPassword() !== $authPassword
        );
        if ($isNotAuthenticated) {
            return response()->json(['Authorization Required'], 401);
        }

        return $next($request);
    }
}
