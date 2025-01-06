<?php

namespace App\Http\Middleware;

use App\Enums\PermissionsEnum;
use App\Enums\quoteTypeCode;
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
        $lobs = [
            quoteTypeCode::Car,
            quoteTypeCode::Bike,
            quoteTypeCode::Health,
            quoteTypeCode::Travel,
            quoteTypeCode::Pet,
            quoteTypeCode::Cycle,
            quoteTypeCode::Yacht,
            quoteTypeCode::Life,
            quoteTypeCode::Home,
            quoteTypeCode::Jetski
        ];

        $allowed = false;
        foreach ($lobs as $lob) {
            $lob = strtolower($lob);
            $allowedRoutes = [
                "$lob-quotes-list",
                "$lob-quotes-edit",
                "$lob-quotes-show",
                'main-dashboard-view',
                'lead-distribution-report-view',
                'advisor-performance-report-view',
            ];

            if(in_array($routeName, $allowedRoutes)) {
                $allowed = true;
                break;
            }
        }
        return auth()->user()->can(PermissionsEnum::VIEW_ALL_LEADS) && $allowed;
    }

    private function allowedViewAllReports($routeName)
    {
        $allowedRoutes = [
            'total-premium-leads-sales-report'
        ];

        $allowed = in_array($routeName, $allowedRoutes);
        
        return auth()->user()->can(PermissionsEnum::VIEW_ALL_REPORTS) && $allowed;
    }
}
