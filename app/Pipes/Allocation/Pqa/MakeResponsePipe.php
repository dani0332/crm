<?php

declare(strict_types=1);

namespace App\Pipes\Allocation\Pqa;

use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\PqaAllocation\PqaAllocationService;
use Closure;

class MakeResponsePipe extends BasePqaAllocationPipe
{
    public function handle(AllocationRequest $request, Closure $next)
    {
        return app(PqaAllocationService::class)->resolveAllocationResponse($request);
    }
}
