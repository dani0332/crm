<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRouteAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $routeName = $request->route()->getName();

        if (str_contains($routeName, '.') || ! $routeName) {
            return $next($request); // we are skipping the routes without name for now but we will restrict it later
        }

        if (str_contains($routeName, '-') && auth()->user()->can($routeName)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access');
    }
}
