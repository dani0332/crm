<?php

namespace App\Http\Middleware;

use App\Enums\ApplicationStorageEnums;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClaimsModuleEnabled
{
    /**
     * Block access to claims routes when the Claims Module is disabled via Application Storage.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (getAppStorageValueByKey(ApplicationStorageEnums::DISABLE_CLAIMS_MODULE, false, useCache: true)) {
            abort(403, 'The Claims module is currently disabled.');
        }

        return $next($request);
    }
}
