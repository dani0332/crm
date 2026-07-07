<?php

namespace App\Services\EmailServices;

use App\Enums\LeadSourceEnum;
use App\Enums\QuoteTypes;
use App\Models\CarQuote;
use App\Models\DttRevival;
use App\Models\HealthQuote;
use App\Models\TravelQuote;
use App\Services\BaseService;
use App\Services\Logger\LoggerService;
use App\Services\Traits\Inboundable;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Postmark\Inbound;

class IncomingEmailService extends BaseService
{
    use Inboundable;

    /**
     * Subject templates used for Health emails that carry the lead's REF-ID
     * instead of a `HEA-<uuid>` token (e.g. advisor callback requests and
     * marketing follow-up emails).
     *
     * @var array<int, string>
     */
    private const HEALTH_SIC_REF_ID_SUBJECT_PATTERNS = [
        'InstantAlfred advisor callback request',
        'Your Health Insurance with Alfred',
        "Don't Miss Out - Your Personalized Health Insurance Awaits",
        'Unlock Your Tailored Health Insurance Quotes',
        'Get the coverage you need to protect your health today',
    ];

    private const HEALTH_AUTOMATED_FOLLOWUPS_PATTERNS = [
        'Urgent: Secure your health insurance today!',
        'Urgent: Get the health coverage you deserve!',
        'Act now: Limited time to secure your peace of mind!',
        'Time-Sensitive: Secure your exclusive health insurance offer!',
        'Act Now: Your insurance coverage awaits!',
    ];
    private const MOTOR_REVIVAL_SUBJECT_PATTERNS = [
        'Car Insurance with Alfred - CAR-',
    ];

    public function process(array $payload)
    {
        try {
            LoggerService::info(self::class.' - process: Webhook Received - Verifying Auth...');

            $inbound = new Inbound(json_encode($payload['payload'] ?? $payload));

            $subject = $inbound->Subject();

            LoggerService::info(self::class." - process: Webhook Received with Subject: {$subject}");

            $data = $this->resolveQuoteTypeFromSubject($subject);

            if ($data === null) {
                LoggerService::warning('Could not resolve quote type/uuid from subject', ['subject' => $subject]);

                return; // or throw/redirect, whatever the caller expects on failure
            }

            [$quoteType, $identifier] = $data;

            switch ($quoteType) {
                case QuoteTypes::HEALTH:
                    $lead = $this->resolveHealthLeadByRefId($subject);
                    break;
            }

            return null;
        } catch (Exception $e) {
            LoggerService::error(self::class.' - process: Exception occurred', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return apiResponse([], Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }
    }

    private function resolveQuoteTypeFromSubject(string $subject): ?array
    {
        foreach (QuoteTypes::cases() as $quoteType) {
            $shortCode = $quoteType->shortCode();

            if (! $shortCode) {
                continue;
            }

            $shortCode = rtrim($shortCode, '-');

            // Matches either a full UUID or a short alphanumeric code after the prefix.
            $pattern = '/'.preg_quote($shortCode, '/').'-('
                .'[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}'  // full UUID
                .'|[0-9A-Za-z]{6,}'                                              // short code fallback
                .')/i';

            if (preg_match($pattern, $subject, $matches)) {
                return [$quoteType, $matches[1]];
            }
        }

        return null;
    }

    private function resolveLead(string $subject, ?array $data)
    {
        if ($data) {
            [$quoteType, $uuid] = $data;

            $lead = $quoteType->model()::where('uuid', $uuid)->first();

            if ($lead) {
                switch ($quoteType) {
                    case QuoteTypes::CAR:
                        return $this->handleCar($lead);
                    case QuoteTypes::TRAVEL:
                        return $this->handleTravel($lead);
                    case QuoteTypes::HEALTH:
                        return $this->handleHealth($lead);
                    default:
                        return apiResponse([], Response::HTTP_UNPROCESSABLE_ENTITY, "Unhandled quote type: {$quoteType->value}");
                }
            }

            LoggerService::warning(self::class." - resolveLead: Lead not found for uuid: {$uuid}");

            return apiResponse([], Response::HTTP_OK, "Lead not found for uuid: {$uuid}");
        }

        LoggerService::warning(self::class." - resolveLead: uuid not found in subject: {$subject}");

        return apiResponse([], Response::HTTP_OK, "UUID & Quote Type could not be extracted from subject: {$subject}");
    }

    private function resolveHealthLeadByRefId(string $subject): ?HealthQuote
    {
        $normalizedSubject = preg_replace('/\s+/', ' ', $subject);
        $isSICHealthSubject = collect(self::HEALTH_SIC_REF_ID_SUBJECT_PATTERNS)
            ->contains(fn (string $pattern) => Str::contains($normalizedSubject, $pattern, ignoreCase: true));

        if (! $isSICHealthSubject) {
            return null;
        }
        $shortCode = QuoteTypes::HEALTH->shortCode(); // e.g. "HEA"

        if (! preg_match('/HEA-([A-Za-z0-9]+)/i', $subject, $matches)) {
            LoggerService::warning(self::class." - resolveHealthLeadByRefId: REF-ID not found in subject: {$subject}");

            return null;
        }
        $code = QuoteTypes::HEALTH->shortCode().strtoupper($matches[1]);

        $lead = HealthQuote::where('code', $code)->first();

        if (! $lead) {
            LoggerService::warning(self::class." - resolveHealthLeadByRefId: Lead not found for code: {$code}");
        }
        if ($isSICHealthSubject) {
            $this->handleHealthSicReplyToILA($lead);
        }

        return $lead;
    }

    private function handleCar(CarQuote $lead)
    {
        if ($lead->source == LeadSourceEnum::REVIVAL) {
            LoggerService::info(self::class." - handleCar: Going to update Car Quote for uuid {$lead->uuid}");
            $lead->update(['source' => LeadSourceEnum::REVIVAL_REPLIED]);
            DttRevival::where('uuid', $lead->uuid)->update(['reply_received' => 1]);
            LoggerService::info(self::class." - handleCar: Car Quote Source updated for Revival for uuid {$lead->uuid}");

            $this->handleCarAllocation($lead);

            return apiResponse([], Response::HTTP_OK, 'Car Source Updated Successfully!');
        } else {
            try {
                LoggerService::info(self::class." - handleCar: Going to handle Car Quote for uuid {$lead->uuid}");

                $this->handleSicReplyToILA($lead);

                return apiResponse([], Response::HTTP_OK, 'Car Handled for SIC to ILA Successfully!');
            } catch (Exception $e) {
                LoggerService::error(self::class." - handleCar: Error occurred in SIC Reply to ILA for uuid {$lead->uuid}", [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]);

                return apiResponse([], Response::HTTP_INTERNAL_SERVER_ERROR, 'Something went wrong!');
            }
        }
    }

    private function handleTravel(TravelQuote $lead)
    {
        LoggerService::info(self::class." - handleTravel: Going to Assign Advisor to uuid: {$lead->uuid}");

        if ($lead->advisor_id) {
            LoggerService::info(self::class." - handleTravel: Lead already has an advisor assigned: {$lead->uuid} - Advisor ID: {$lead->advisor_id}");

            return apiResponse([], Response::HTTP_OK, 'Lead already has an advisor assigned!');
        }

        LoggerService::info(self::class." - handleTravel: Allocation Process Executing for lead: {$lead->uuid}");

        $response = QuoteTypes::TRAVEL->allocate($lead->uuid);
        $assignedAdvisorId = $response['advisorId'] ?? '';
        LoggerService::info(self::class." - handleTravel: Allocation Executed for lead: {$lead->uuid} and assignedAdvisorId: {$assignedAdvisorId}");

        return apiResponse([], Response::HTTP_OK, 'Lead Assigned to Advisor Successfully!');
    }

    private function handleHealth(HealthQuote $lead)
    {
        if ($lead->source == LeadSourceEnum::REVIVAL) {
            LoggerService::info(self::class." - handleHealth: Going to Assign Advisor to uuid: {$lead->uuid}");
            if ($lead->advisor_id) {
                LoggerService::info(self::class." - handleHealth: Lead already has an advisor assigned: {$lead->uuid}");

                return apiResponse([], Response::HTTP_OK, 'Lead already has an advisor assigned!');
            }

            $lead->update(['source' => LeadSourceEnum::REVIVAL_REPLIED]);
            LoggerService::info(self::class." - handleHealth: Car Quote Source updated for Revival for uuid {$lead->uuid} to REVIVAL_REPLIED");
            DttRevival::where('uuid', $lead->uuid)->update(['reply_received' => 1]);
            LoggerService::info(self::class." - handleHealth: Health Quote Source updated for Revival for uuid {$lead->uuid}");

            LoggerService::info(self::class." - handleHealth: Allocation Process Executing for lead: {$lead->uuid}");
            $response = QuoteTypes::HEALTH->allocate($lead->uuid);
            $assignedAdvisorId = $response['advisorId'] ?? '';
            LoggerService::info(self::class." - handleHealth: AllocationStrategy Executed for lead: {$lead->uuid} and assignedAdvisorId: {$assignedAdvisorId}");

            return apiResponse([], Response::HTTP_OK, 'Lead Assigned to Advisor Successfully!');
        }

        try {
            LoggerService::info(self::class." - handleHealth: Going to handle Health Quote for uuid {$lead->uuid}");

            return $this->handleHealthSicReplyToILA($lead);
        } catch (Exception $e) {
            LoggerService::error(self::class." - handleHealth: Error occurred in SIC Reply to ILA for uuid {$lead->uuid}", [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return apiResponse([], Response::HTTP_INTERNAL_SERVER_ERROR, 'Something went wrong!');
        }
    }

    private function handleHealthSicReplyToILA(HealthQuote $lead)
    {
        LoggerService::info(self::class." - handleHealthSicReplyToILA: Received: {$lead->uuid}");

        if ($lead->advisor_id) {
            LoggerService::info(self::class." - handleHealthSicReplyToILA: Lead already has an advisor assigned: {$lead->uuid} - Advisor ID: {$lead->advisor_id}");

            return apiResponse([], Response::HTTP_OK, 'Lead already has an advisor assigned!');
        }

        LoggerService::info(self::class." - handleHealthSicReplyToILA: Allocation Process Executing for lead: {$lead->uuid}");

        $response = QuoteTypes::HEALTH->allocate($lead->uuid);
        $assignedAdvisorId = $response['advisorId'] ?? '';
        LoggerService::info(self::class." - handleHealthSicReplyToILA: Allocation Executed for lead: {$lead->uuid} and assignedAdvisorId: {$assignedAdvisorId}");

        return apiResponse([], Response::HTTP_OK, 'Lead Assigned to Advisor Successfully!');
    }

}
