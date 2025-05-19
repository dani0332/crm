<?php

namespace App\Pipes\Allocation\Car;

use Closure;

class FinalizeEligibleAdvisorPipe
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
