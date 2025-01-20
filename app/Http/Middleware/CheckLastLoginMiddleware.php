<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckLastLoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $specialUsers = getAutomationUser();
        if (auth()->user()) {
            if (in_array(auth()->user()->email, $specialUsers) || app('impersonate')->isImpersonating()) {
                return $next($request);
            }

            $lastLoginDate = Carbon::parse(auth()->user()->last_login);
            if (! now()->isSameDay($lastLoginDate)) {
                auth()->logout();

                return redirect()->route('login');
            }
        } else {
            Auth::logout();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
