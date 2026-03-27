<?php

namespace App\Http\Middleware;

use App\Enums\PermissionsEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAuditableTypePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $auditableType = $request->auditableType;

        if ($auditableType === 'App\Models\Allocation\AllocationConfiguration') {
            if (! Auth::user()->can(PermissionsEnum::ILA_CONFIG_ALL_LOB)) {
                abort(403, 'You do not have sufficient permission');
            }
        }

        return $next($request);
    }
}
