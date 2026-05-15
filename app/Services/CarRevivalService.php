<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\MotorRevivalEnum;
use App\Models\CarQuote;
use App\Services\Logger\LoggerService;

class CarRevivalService
{
    public function markRevivalCommsTriggered(string $quoteUuid): void
    {
        $carQuote = CarQuote::query()->where('uuid', $quoteUuid)
            ->where('source', LeadSourceEnum::REVIVAL)
            ->first();

        if (! $carQuote) {
            LoggerService::warning('CarRevivalService::markRevivalCommsTriggered - car quote not found', [
                'quote_uuid' => $quoteUuid,
            ]);

            return;
        }

        $carQuoteRequestDetail = $carQuote->carQuoteRequestDetail;

        if ($carQuoteRequestDetail) {
            $now = now();
            $carQuoteRequestDetail->update([
                'engagement_level' => MotorRevivalEnum::COMMS_TRIGGERED->value,
                'engagement_level_updated_at' => $now,
            ]);
            LoggerService::info('CarRevivalService::markRevivalCommsTriggered - car quote request detail updated', [
                'quote_uuid' => $quoteUuid,
                'engagement_level' => MotorRevivalEnum::COMMS_TRIGGERED->value,
                'engagement_level_updated_at' => $now,
            ]);
        } else {
            LoggerService::warning('CarRevivalService::markRevivalCommsTriggered - car quote request detail not found', [
                'quote_uuid' => $quoteUuid,
            ]);

            return;
        }
    }

    public function updateSource(string $quoteUuid, string $source): void
    {
        LoggerService::info(self::class.' - updateSource request received', [
            'quote_uuid' => $quoteUuid,
            'source' => $source,
        ]);

        $quote = CarQuote::query()
            ->where('uuid', $quoteUuid)
            ->where('source', LeadSourceEnum::REVIVAL)
            ->first();

        if (! $quote) {
            LoggerService::info(self::class.' - updateSource - source not updated (no revival car quote)', [
                'quote_uuid' => $quoteUuid,
                'source' => $source,
            ]);

            return;
        }

        $quote->update(['source' => $source]);

        LoggerService::info(self::class.' - updateSource - source updated', [
            'quote_uuid' => $quoteUuid,
            'source' => $source,
        ]);
    }
}
