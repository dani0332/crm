<?php

declare(strict_types=1);

namespace App\Pipes\Allocation\Pqa;

use App\Pipes\Allocation\Handlers\AllocationRequest;
use App\Services\Logger\LoggerService;
use Closure;
use Illuminate\Support\Facades\Cache;

class VerifyConcurrentAllocationPipe extends BasePqaAllocationPipe
{
    private const LOCK_SECONDS = 600;

    public function handle(AllocationRequest $request, Closure $next)
    {
        $this->setRequest($request);

        $lockKey = 'pqa-allocation:'.$this->allocationRequest->getQuoteUUID();
        $lock = Cache::lock($lockKey, self::LOCK_SECONDS);

        if (! $lock->get()) {
            LoggerService::info(self::class.' - PQA allocation already in progress for '.$this->allocationRequest->getQuoteUUID());
            $this->throw('PQA allocation is in progress', self::OK);
        }

        $this->allocationRequest->set('pqa_allocation_lock', $lock);

        return $next($request);
    }
}
