<?php

namespace App\Http\Middleware;

use App\Enums\GenericRequestEnum;
use App\Enums\PermissionsEnum;
use Closure;
use Illuminate\Support\Facades\Auth;

class CheckCarQuoteExportPermission
{
    public function handle($request, Closure $next)
    {
        if ($request->export_type == GenericRequestEnum::EXPORT_PLAN_DETAIL && Auth::user()->can(PermissionsEnum::EXPORT_PLAN_DETAIL)) {
            return $next($request);
        } elseif ($request->export_type == GenericRequestEnum::EXTRACT_CAR_LEADS_DETAILS_WITH_EMAIL_MOBILE_NUMBER && Auth::user()->can(PermissionsEnum::EXTRACT_CAR_LEADS_DETAIL_WITH_EMAIL_MOBILE_NO)) {
            return $next($request);
        } elseif ($request->export_type == GenericRequestEnum::EXTRACT_CAR_MAKE_MODEL_TRIMS && Auth::user()->can(PermissionsEnum::EXTRACT_CAR_MAKES_MODELS_TRIMS)) {
            return $next($request);
        }

        abort(403, 'Unauthorized access');
    }
}
