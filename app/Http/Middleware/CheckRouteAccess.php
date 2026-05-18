<?php

namespace App\Http\Middleware;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CheckRouteAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next, ?string $permissionPrefix = null)
    {

        if (auth()->user()->hasAnyRole([RolesEnum::Admin, RolesEnum::Engineering])) {
            return $next($request);
        }

        $routeName = $request->route()->getName();
        $methodName = $request->route()->getActionMethod();
        $permissionSuffix = $this->mapMethodToPermissionSuffix($methodName);

        /**
         * If a prefix like “corpline-quotes” is supplied, build the permission by combining
         * that prefix with the mapped suffix (edit, list, etc.). When no prefix is provided
         * we continue to fall back to the legacy behavior: replace the method name in the
         * generated route name with the mapped suffix and check that permission.
         */
        $permissionName = $permissionPrefix
            ? $this->buildPrefixPermission($permissionPrefix, $permissionSuffix)
            : $this->mapRouteNameToPermission($routeName, $methodName, $permissionSuffix);

        if (auth()->user()->can($permissionName) || $this->allowedViewAllLeads($permissionName) || $this->allowedViewAllReports($permissionName)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access');
    }

    private function mapMethodToPermissionSuffix(string $methodName): string
    {
        $mapping = [
            'store' => 'create',
            'update' => 'edit',
            'destroy' => 'delete',
            'show' => 'show',
            'index' => 'list',
        ];

        return $mapping[$methodName] ?? $methodName;
    }

    private function mapRouteNameToPermission(?string $routeName, string $methodName, string $suffix): string
    {
        if (! $routeName) {
            return $suffix;
        }

        if ($methodName === $suffix) {
            return $routeName;
        }

        return Str::replaceFirst($methodName, $suffix, $routeName);
    }

    private function buildPrefixPermission(string $permissionPrefix, string $suffix): string
    {
        return trim("{$permissionPrefix}-{$suffix}");
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
