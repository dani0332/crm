<?php

declare(strict_types=1);

namespace App\Services\Cars24;

use App\Models\InsurancePartner;
use App\Models\InsurancePartnerMapping;
use App\Services\Logger\LoggerService;

class Cars24Service
{
    public function getLookups(array $requireLookups, string $leadSource): array
    {
        $isPartnerActive = InsurancePartner::active()->forCode($leadSource)->first();

        if (! $isPartnerActive) {
            LoggerService::info('Additional Vehicle and Driver Details are not enabled for the partner', [
                'partner' => $leadSource,
            ]);

            return [];
        }

        return InsurancePartnerMapping::active()->whereIn('key', $requireLookups)
            ->get()
            ->groupBy('key')
            ->mapWithKeys(fn ($item, $key) => [str_replace('-', '_', $key) => $item])
            ->toArray();
    }
}
