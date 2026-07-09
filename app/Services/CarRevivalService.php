<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LeadSourceEnum;
use App\Enums\MotorRevivalEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\CarQuote;
use App\Services\EmailServices\CarEmailService;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;

class CarRevivalService
{
    public function sendCarRevivalEmail(string $quoteUuid): ?object
    {
        $carQuote = CarQuote::query()
            ->where('uuid', $quoteUuid)
            ->where('source', LeadSourceEnum::REVIVAL)
            ->first();

        if (! $carQuote) {
            LoggerService::info(self::class." - Car revival email not sent since lead not found for Quote UUID: {$quoteUuid}");

            return null;
        }

        $previousAdvisor = null;
        if (! empty($carQuote->previous_advisor_id)) {
            $previousAdvisor = app(UserService::class)->getUserById($carQuote->previous_advisor_id);
        }

        $emailData = (new CarEmailService(app(SendEmailCustomerService::class)))->buildDttRevivalBirdEmailPayload($carQuote, $previousAdvisor);
        $emailData->workflowType = WorkflowTypeEnum::MOTOR_REVIVAL_OCB;

        app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::MOTOR_REVIVAL_OCB, (array) $emailData);

        LoggerService::info(self::class." - Car revival WebEngage event triggered for Quote UUID: {$quoteUuid}");

        $this->markRevivalCommsTriggered($quoteUuid);

        app(WebEngageService::class)->createQuoteWorkFlowDetails($carQuote->uuid, QuoteFlowType::MOTOR_REVIVAL_OCB->value, (int) QuoteTypes::CAR->id());

        LoggerService::info(self::class." - Car revival email sent for Quote UUID: {$quoteUuid}");

        return $emailData;
    }

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
