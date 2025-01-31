<?php

namespace App\Http\Middleware;

use App\Enums\PermissionsEnum;
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

        if (auth()->user()->can($routeName) || $this->allowedViewAllLeads($routeName) || $this->allowedViewAllReports($routeName)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access');
    }

    private function allowedViewAllLeads($routeName)
    {
        $allowed = str_ends_with($routeName, '-quotes-list') ||
           str_ends_with($routeName, '-quotes-show') ||
           str_ends_with($routeName, '-quotes-edit');

        return auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS) && $allowed;
    }

    private function allowedViewAllReports($routeName)
    {
        $allowedRoutes = [
            'total-premium-leads-sales-report',
            'advisor-performance-report-view',
            'lead-distribution-report-view',
        ];

        $allowed = in_array($routeName, $allowedRoutes);

        return auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS) && $allowed;
    }
}
