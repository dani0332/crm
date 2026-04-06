<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MotorRevivalEnum;
use App\Models\CarQuote;
use App\Services\Logger\LoggerService;

class CarRevivalService
{
    /**
     * Set motor revival engagement to {@see MotorRevivalEnum::COMMS_TRIGGERED} so IMCRM can reflect it via quote sync
     * ({@see \App\Observers\CarQuoteDetailObserver} → {@see \App\Traits\PersonalQuoteSyncTrait::syncQuote}).
     */
    public function markRevivalCommsTriggered(string $quoteUuid): void
    {
        $carQuote = CarQuote::query()->where('uuid', $quoteUuid)->first();

        if (! $carQuote) {
            LoggerService::warning('CarRevivalService::markRevivalCommsTriggered - car quote not found', [
                'quote_uuid' => $quoteUuid,
            ]);

            return;
        }

        $carQuoteRequestDetail = $carQuote->carQuoteRequestDetail;

        if ($carQuoteRequestDetail) {
            $carQuoteRequestDetail->update([
                'engagement_level' => MotorRevivalEnum::COMMS_TRIGGERED->value,
                'engagement_level_updated_at' => now(),
            ]);
        } else {
            LoggerService::warning('CarRevivalService::markRevivalCommsTriggered - car quote request detail not found', [
                'quote_uuid' => $quoteUuid,
            ]);

            return;
        }
    }
}
