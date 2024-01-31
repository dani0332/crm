<?php

namespace App\Http\Middleware;

use App\Services\UserService;
use Closure;

class CheckLeadListReportPermission
{
    public function handle($request, Closure $next)
    {
        if (app(UserService::class)->isAllowedToShowLeadListReport()) {
            return $next($request);
        }

        abort(403, 'Unauthorized access');
    }
}
