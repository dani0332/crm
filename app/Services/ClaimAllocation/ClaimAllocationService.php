<?php

declare(strict_types=1);

namespace App\Services\ClaimAllocation;


use Exception;
use App\Models\ClaimRequest;
use App\Enums\Logger\LoggerFeatureEnum;
use App\Services\Logger\LoggerService;

class ClaimAllocationService 
{
   

    public function execute(string $quoteUuid, int $quoteTypeId)
    {
     
        $claimRequest = ClaimRequest::where('quote_uuid', $quoteUuid)->first();
        LoggerService::startQuoteLogging($claimRequest, LoggerFeatureEnum::CLAIM_ALLOCATION);
        if (!$claimRequest) {
           return ['status' => false, 'message' => 'Claim request not found'];
        }

        

        
    }
}
