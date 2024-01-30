<?php

namespace App\Http\Middleware;

use App\Enums\RolesEnum;
use App\Enums\TeamNameEnum;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckLeadListReportPermission
{
    public function handle($request, Closure $next)
    {
        if ($this->isAllowedToShowLeadListReport()) {
            return $next($request);
        }

        abort(403, 'Unauthorized access');
    }

    public function isAllowedToShowLeadListReport()
    {

        if (Auth::user()->hasRole(RolesEnum::CarAdvisor) || Auth::user()->hasRole(RolesEnum::CarManager)) {
            if (Auth::user()->isAssignUserToTeam(Auth::user()->id, TeamNameEnum::ORGANIC)) {
                return true;
            }
        }

        return false;
    }
}
