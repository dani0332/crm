<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $credentialPairs = array_filter(
            [
                [
                    config('constants.IMCRM_BASIC_AUTH_USER_NAME'),
                    config('constants.IMCRM_BASIC_AUTH_PASSWORD'),
                ],
                [
                    config('constants.IMCRM_API_AUTH_USER_NAME'),
                    config('constants.IMCRM_API_AUTH_PASSWORD'),
                ],
            ],
            function (array $pair) {
                [$user, $password] = $pair;

                return $user !== null && $user !== '' && $password !== null && $password !== '';
            }
        );

        $hasSuppliedCredentials = ! (empty($request->getUser()) && empty($request->getPassword()));
        $matches = false;
        if ($hasSuppliedCredentials) {
            foreach ($credentialPairs as [$user, $password]) {
                if ($request->getUser() === $user && $request->getPassword() === $password) {
                    $matches = true;
                    break;
                }
            }
        }

        if (! $matches) {
            return response()->json(['Authorization Required'], 401);
        }

        return $next($request);
    }
}
