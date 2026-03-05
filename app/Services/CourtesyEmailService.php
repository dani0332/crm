<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\EmirateEnum;
use App\Enums\QuoteTypeId;
use App\Models\ApplicationStorage;
use App\Models\BikeQuote;
use App\Models\CarQuote;
use App\Models\Customer;
use App\Models\EmailStatus;
use App\Models\HealthQuote;
use App\Models\HomeQuote;
use App\Models\LifeQuote;
use App\Models\PersonalQuote;
use App\Models\PetQuote;
use App\Models\TravelQuote;
use App\Models\User;
use App\Models\YachtQuote;
use App\Services\Logger\LoggerService;

class CourtesyEmailService extends BaseService
{
    private BirdService $birdService;

    /**
     * Allowed quote types for courtesy email
     */
    private const ALLOWED_QUOTE_TYPES = [
        QuoteTypeId::Bike,
        QuoteTypeId::Car,
        QuoteTypeId::Health,
        QuoteTypeId::Home,
        QuoteTypeId::Life,
        QuoteTypeId::Pet,
        QuoteTypeId::Travel,
        QuoteTypeId::Yacht,
        QuoteTypeId::Jetski,
        QuoteTypeId::Cycle,
        QuoteTypeId::Cyber,
    ];

    public function __construct(BirdService $birdService)
    {
        $this->birdService = $birdService;
    }

    /**
     * Process courtesy email workflow
     *
     * @return array{message: string, success: bool}
     */
    public function processCourtesyEmailWorkflow(string $quoteUID, int $quoteTypeId): array
    {
        try {
            LoggerService::info('CourtesyEmailService - Execute', [
                'quoteTypeId' => $quoteTypeId,
                'quoteUID' => $quoteUID,
            ]);

            // Fetch quote by quoteTypeId
            $quote = $this->getQuoteByQuoteType($quoteTypeId, $quoteUID);

            if (! $quote || ! $quote->email) {
                LoggerService::warning('CourtesyEmailService - quote not found or missing email', [
                    'quoteTypeId' => $quoteTypeId,
                    'quoteUID' => $quoteUID,
                ]);

                return [
                    'message' => 'quote not found or missing email',
                    'success' => false,
                ];
            }

            // Get customer by email or create from quote firstName/lastName
            $customer = Customer::where('email', strtolower(trim($quote->email)))->first();

            if (! $customer && ($quote->first_name || $quote->last_name)) {
                $customer = (object) [
                    'first_name' => $quote->first_name ?? '',
                    'last_name' => $quote->last_name ?? '',
                ];
            }

            if (! $customer) {
                LoggerService::warning('CourtesyEmailService - customer not found', [
                    'quoteTypeId' => $quoteTypeId,
                    'quoteUID' => $quoteUID,
                ]);

                return [
                    'message' => 'customer not found',
                    'success' => false,
                ];
            }

            // Get advisor by advisorId
            $advisor = null;
            if ($quote->advisor_id) {
                $advisor = User::find($quote->advisor_id);
            }

            if (! $advisor) {
                LoggerService::warning('CourtesyEmailService - advisor not found', [
                    'quoteTypeId' => $quoteTypeId,
                    'quoteUID' => $quoteUID,
                    'advisor_id' => $quote->advisor_id ?? null,
                ]);

                return [
                    'message' => 'advisor not assigned',
                    'success' => false,
                ];
            }

            LoggerService::info('CourtesyEmailService - customer and advisor found', [
                'customer_name' => ($customer->first_name ?? '').' '.($customer->last_name ?? ''),
                'advisor_name' => $advisor->name,
                'quoteTypeId' => $quoteTypeId,
                'quoteUID' => $quoteUID,
            ]);

            // Check/create track email record
            // Using a custom identifier to track courtesy email processing
            $trackEmail = EmailStatus::where('quote_type_id', $quoteTypeId)
                ->where('quote_id', $quote->id)
                ->where('email_subject', 'LIKE', '%Courtesy Email%')
                ->first();

            // Check if already processed by looking for existing email status with customer_replied = false
            // This mimics the CAPI logic where isCeProcessed prevents reprocessing
            $isCeProcessed = $trackEmail && $trackEmail->email_status !== null;

            if (! $trackEmail) {
                // Create email tracking record (will be updated after successful send)
                $emailStatus = new EmailStatus;
                $emailStatus->quote_type_id = $quoteTypeId;
                $emailStatus->quote_id = $quote->id;
                $emailStatus->email_address = $quote->email;
                $emailStatus->email_subject = 'Courtesy Email';
                $emailStatus->customer_id = is_object($customer) && isset($customer->id) ? $customer->id : null;
                $emailStatus->save();
            }

            // Validate quote type is allowed
            $hasAllowed = in_array($quoteTypeId, self::ALLOWED_QUOTE_TYPES);
            $processAllowed = $hasAllowed && ! $isCeProcessed;

            LoggerService::info('CourtesyEmailService - validation check', [
                'quoteStatusId' => $quote->quote_status_id ?? null,
                'quoteTypeAllowed' => $hasAllowed,
                'processAllowed' => $processAllowed,
                'quoteTypeId' => $quoteTypeId,
                'quoteUID' => $quoteUID,
            ]);

            if (! $processAllowed) {
                return [
                    'message' => 'quote status or quote type is not valid to process workflow',
                    'success' => false,
                ];
            }

            // Build Bird payload
            $eventPayload = $this->buildBirdPayload($advisor, $customer, $quoteTypeId, $quote);

            LoggerService::info('CourtesyEmailService - eventPayload', [
                'payload' => $eventPayload,
                'quoteTypeId' => $quoteTypeId,
                'quoteUID' => $quoteUID,
            ]);

            // Get Bird workflow URL
            $workflowUrl = $this->getBirdWorkflowUrl();

            if (! $workflowUrl) {
                LoggerService::error('CourtesyEmailService - Bird workflow URL not configured', [
                    'quoteTypeId' => $quoteTypeId,
                    'quoteUID' => $quoteUID,
                ]);

                return [
                    'message' => 'Bird workflow URL not configured',
                    'success' => false,
                ];
            }

            // Trigger Bird workflow
            $response = $this->birdService->triggerWebHookRequest($workflowUrl, $eventPayload);

            LoggerService::info('CourtesyEmailService - Bird workflow triggered', [
                'quoteTypeId' => $quoteTypeId,
                'quoteUID' => $quoteUID,
                'response_status' => $response->status_code ?? null,
            ]);

            // Update track email to mark as processed
            if ($trackEmail) {
                // Mark as processed (email_status will be updated by Bird webhook callback)
                $trackEmail->save();
            } else {
                // Update the newly created email status
                $emailStatus = EmailStatus::where('quote_type_id', $quoteTypeId)
                    ->where('quote_id', $quote->id)
                    ->where('email_subject', 'LIKE', '%Courtesy Email%')
                    ->first();
                if ($emailStatus) {
                    $emailStatus->save();
                }
            }

            return [
                'message' => 'processed workflow successfully',
                'success' => true,
            ];
        } catch (\Exception $e) {
            LoggerService::error('CourtesyEmailService - Error processing workflow', [
                'quoteTypeId' => $quoteTypeId,
                'quoteUID' => $quoteUID,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], $e);

            return [
                'message' => 'Error processing workflow: '.$e->getMessage(),
                'success' => false,
            ];
        }
    }

    /**
     * Get quote by quoteTypeId and UUID
     *
     * @return mixed
     */
    private function getQuoteByQuoteType(int $quoteTypeId, string $quoteUID)
    {
        return match ($quoteTypeId) {
            QuoteTypeId::Bike => BikeQuote::where('uuid', $quoteUID)->first(),
            QuoteTypeId::Car => CarQuote::where('uuid', $quoteUID)->first(),
            QuoteTypeId::Health => HealthQuote::where('uuid', $quoteUID)->first(),
            QuoteTypeId::Home => HomeQuote::where('uuid', $quoteUID)->first(),
            QuoteTypeId::Life => LifeQuote::where('uuid', $quoteUID)->first(),
            QuoteTypeId::Pet => PetQuote::where('uuid', $quoteUID)->first(),
            QuoteTypeId::Travel => TravelQuote::where('uuid', $quoteUID)->first(),
            QuoteTypeId::Yacht => YachtQuote::where('uuid', $quoteUID)->first(),
            QuoteTypeId::Jetski => PersonalQuote::where('uuid', $quoteUID)->where('quote_type_id', QuoteTypeId::Jetski)->first(),
            QuoteTypeId::Cycle => PersonalQuote::where('uuid', $quoteUID)->where('quote_type_id', QuoteTypeId::Cycle)->first(),
            QuoteTypeId::Cyber => PersonalQuote::where('uuid', $quoteUID)->where('quote_type_id', QuoteTypeId::Cyber)->first(),
            default => null,
        };
    }

    /**
     * Build Bird payload for courtesy email
     */
    private function buildBirdPayload(User $advisor, $customer, int $quoteTypeId, $quote): array
    {
        $payload = [
            'quoteUID' => $quote->uuid,
            'uuid' => $quote->uuid,
            'refId' => $quote->code ?? $quote->uuid,
            'quoteTypeId' => $quoteTypeId,
            'customer' => [
                'email' => $quote->email,
                'firstName' => is_object($customer) && isset($customer->first_name) ? $customer->first_name : ($customer->first_name ?? ''),
                'lastName' => is_object($customer) && isset($customer->last_name) ? $customer->last_name : ($customer->last_name ?? ''),
            ],
            'advisor' => [
                'id' => $advisor->id,
                'name' => $advisor->name,
                'email' => $advisor->email,
            ],
        ];

        // Add emirateOfYourVisaId for Health quotes
        if ($quoteTypeId === QuoteTypeId::Health && isset($quote->emirate_of_your_visa_id)) {
            $payload['emirateOfYourVisaId'] = $quote->emirate_of_your_visa_id;
            $payload['isHealthAUH'] = $quote->emirate_of_your_visa_id === EmirateEnum::ABU_DHABI;
        }

        return $payload;
    }

    /**
     * Get Bird workflow URL from ApplicationStorage
     */
    private function getBirdWorkflowUrl(): ?string
    {
        $workflowConfig = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_COURTESY_EMAIL_WORKFLOW_URL)->first();

        return $workflowConfig ? $workflowConfig->value : null;
    }
}
