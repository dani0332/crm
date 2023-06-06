<?php

namespace App\Http\Middleware;

use App\Enums\RolesEnum;
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
        if (auth()->user()->hasAnyRole([RolesEnum::Admin, RolesEnum::Engineering])) {
            return $next($request);
        }

        $routeName = $request->route()->getName();
        $methodName = $request->route()->getActionMethod();

        $methodMapping = [
            'store' => 'create',
            'update' => 'edit',
            'destroy' => 'delete',
            'show' => 'show',
            'index' => 'list',
        ];

        if (isset($methodMapping[$methodName])) {
            $routeName = str_replace($methodName, $methodMapping[$methodName], $routeName);
        }

        $routeName = $this->handleRouteMapping($routeName);

        if (auth()->user()->can($routeName)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access');
    }

    private function handleRouteMapping($routeName)
    {
        $routesForBinds = [
            'upload-create' => 'renewals-upload',
            'upload-update' => 'renewals-update',
            'batch-plans-processes' => 'batches-list',
            'batch-renewal-detail' => 'batches-list',
            'batch-fetch-plans' => 'batches-list',
            'run-batch-process' => 'batches-list'
        ];

        if(isset($routesForBinds[$routeName]))
        {
            return $routesForBinds[$routeName];
        }

        return $routeName;
    }
}
