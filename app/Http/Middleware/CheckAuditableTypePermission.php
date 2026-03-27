<?php

namespace App\Http\Middleware;

use App\Enums\PermissionsEnum;
use App\Models\Allocation\AllocationConfiguration;
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
        $auditableType = (string) $request->input('auditableType', '');

        // Match DB behaviour (utf8mb4_unicode_ci): same logical type must be gated even if casing differs.
        if (strcasecmp($auditableType, AllocationConfiguration::class) === 0) {
            if (! Auth::user()->can(PermissionsEnum::ILA_CONFIG_ALL_LOB)) {
                abort(403, 'You do not have sufficient permission');
            }
        }

        return $next($request);
    }
}
