<?php

declare(strict_types=1);

namespace App\Services\PolicyIssuanceAutomation\Travel\Dic;

use App\Enums\ApplicationStorageEnums;
use App\Models\TravelQuote;
use App\Services\ApplicationStorageService;
use App\Services\Logger\LoggerService;

class DicValidationService
{
    public function __construct(
        private ApplicationStorageService $applicationStorage,
    ) {}

    /**
     * @return array{status: bool, error?: string, message?: string}
     */
    public function validateRequiredData(TravelQuote $quote): array
    {
        if (! $this->applicationStorage->getValueByKey(ApplicationStorageEnums::ENABLE_DIC_TRAVEL_POLICY_ISSUANCE)) {
            LoggerService::warning('DIC Travel automation is disabled', ['quote_code' => $quote->code]);

            return [
                'status' => false,
                'error' => 'DIC Travel policy issuance automation is disabled',
                'message' => 'DIC Travel policy issuance automation is disabled',
            ];
        }

        return ['status' => true];
    }
}
