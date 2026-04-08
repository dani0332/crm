<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
use App\Enums\BranchEnum;
use App\Enums\EmirateEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\BusinessQuote;
use App\Models\Customer;
use App\Models\PersonalQuote;
use App\Models\QuoteFlowDetails;
use App\Models\User;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class CourtesyEmailService extends BaseService
{
    private const ALLOWED_QUOTE_TYPES = [
        QuoteTypeId::Bike,
        QuoteTypeId::Car,
        QuoteTypeId::Cycle,
        QuoteTypeId::Corpline,
        QuoteTypeId::Cyber,
        QuoteTypeId::Health,
        QuoteTypeId::Home,
        QuoteTypeId::Life,
        QuoteTypeId::Pet,
        QuoteTypeId::Savings,
        QuoteTypeId::Travel,
        QuoteTypeId::Yacht,
        QuoteTypeId::Business,
    ];

    public function __construct(
        private BirdService $birdService,
        private EmailStatusService $emailStatusService,
    ) {}

    public static function allowedQuoteTypeIds(): array
    {
        return self::ALLOWED_QUOTE_TYPES;
    }

    public static function isCourtesyEmailQuoteType(int $quoteTypeId): bool
    {
        return in_array($quoteTypeId, self::ALLOWED_QUOTE_TYPES, true);
    }

    public function getGoogleReviewFlowLogContext(
        string $quoteUuid,
        int $quoteTypeId,
        ?string $recipientEmail,
        ?Collection $courtesyFlowsForQuote = null,
    ): array {
        if (! self::isCourtesyEmailQuoteType($quoteTypeId)) {
            return [
                'review_flow_status' => 'Not applicable',
                'suppression_expires_at' => '—',
            ];
        }

        $hasFlowForQuote = $courtesyFlowsForQuote !== null
            ? $courtesyFlowsForQuote->isNotEmpty()
            : QuoteFlowDetails::query()
                ->where('quote_uuid', $quoteUuid)
                ->where('quote_type_id', $quoteTypeId)
                ->where('flow_type', QuoteFlowType::COURTESY_EMAIL->value)
                ->exists();

        if ($hasFlowForQuote) {
            return [
                'review_flow_status' => 'Eligible',
                'suppression_expires_at' => '—',
            ];
        }

        $normalizedEmail = $recipientEmail !== null && $recipientEmail !== ''
            ? strtolower(trim($recipientEmail))
            : null;

        if ($normalizedEmail === null || $normalizedEmail === '') {
            return [
                'review_flow_status' => 'Not applicable',
                'suppression_expires_at' => '—',
            ];
        }

        $cooldown = $this->getCustomerCourtesyCooldownState($normalizedEmail, $quoteTypeId);
        if ($cooldown['has_recent']) {
            return [
                'review_flow_status' => 'Suppressed',
                'suppression_expires_at' => $this->formatLogDateTime($cooldown['cooldown_expires_at']),
            ];
        }

        return [
            'review_flow_status' => 'Not triggered',
            'suppression_expires_at' => '—',
        ];
    }

    public function processCourtesyEmailWorkflow(string $quoteUID, int $quoteTypeId): array
    {
        $quote = null;

        try {
            if (! in_array($quoteTypeId, self::ALLOWED_QUOTE_TYPES, true)) {
                LoggerService::warning('CourtesyEmailService - Quote type not allowed for courtesy email', [
                    'quoteTypeId' => $quoteTypeId,
                    'quoteUID' => $quoteUID,
                ]);

                return ['message' => 'Quote type not allowed for courtesy email', 'success' => false];
            }

            $quote = $this->getQuoteByQuoteType($quoteTypeId, $quoteUID);

            if (! $quote?->email) {
                LoggerService::warning('CourtesyEmailService - Quote not found or missing email', [
                    'quoteTypeId' => $quoteTypeId,
                    'quoteUID' => $quoteUID,
                ]);

                return ['message' => 'Quote not found or missing email', 'success' => false];
            }

            $customer = Customer::where('email', strtolower(trim($quote->email)))->first();
            if (! $customer && ($quote->first_name || $quote->last_name)) {
                $customer = (object) [
                    'first_name' => $quote->first_name ?? '',
                    'last_name' => $quote->last_name ?? '',
                ];
            }

            if (! $customer) {
                LoggerService::warning('CourtesyEmailService - Customer not found', [
                    'quoteTypeId' => $quoteTypeId,
                    'quoteUID' => $quoteUID,
                ]);
                $this->logCourtesyNotDispatched($quote, $quoteTypeId, 'Customer not found in CRM');

                return ['message' => 'Customer not found', 'success' => false];
            }

            $advisor = $quote->advisor_id ? User::find($quote->advisor_id) : null;
            if (! $advisor) {
                LoggerService::warning('CourtesyEmailService - Advisor not assigned', [
                    'quoteTypeId' => $quoteTypeId,
                    'quoteUID' => $quoteUID,
                    'advisor_id' => $quote->advisor_id,
                ]);
                $this->logCourtesyNotDispatched($quote, $quoteTypeId, 'Advisor not assigned');

                return ['message' => 'Advisor not assigned', 'success' => false];
            }

            $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::BIRD_COURTESY_EMAIL_WORKFLOW_URL)->value('value');
            if (! $workflowUrl) {
                LoggerService::error('CourtesyEmailService - Bird workflow URL not configured', [
                    'quoteTypeId' => $quoteTypeId,
                    'quoteUID' => $quoteUID,
                ]);
                $this->logCourtesyNotDispatched($quote, $quoteTypeId, 'Bird courtesy workflow URL not configured');

                return ['message' => 'Bird workflow URL not configured', 'success' => false];
            }

            $normalizedEmail = strtolower(trim($quote->email));

            if ($this->hasRecentCourtesyFlowForCustomer($normalizedEmail, $quoteTypeId)) {
                LoggerService::info('CourtesyEmailService - Flow suppressed: same customer and LoB within last 7 days', [
                    'suppressed_at' => now()->toIso8601String(),
                    'email' => $normalizedEmail,
                    'quote_type_id' => $quoteTypeId,
                    'quote_uuid' => $quote->uuid,
                ]);
                $this->logCourtesyNotDispatched(
                    $quote,
                    $quoteTypeId,
                    'Suppressed: same customer and line of business already had a courtesy flow in the last 7 days',
                    $customer instanceof Customer ? $customer->id : null,
                );

                return [
                    'message' => 'Courtesy email flow suppressed: same customer and line of business already had a flow in the last 7 days',
                    'success' => false,
                    'suppressed' => true,
                ];
            }

            $quoteType = QuoteTypes::getName($quoteTypeId);
            $firstName = is_object($customer) && isset($customer->first_name) ? $customer->first_name : ($customer->first_name ?? '');
            $lastName = is_object($customer) && isset($customer->last_name) ? $customer->last_name : ($customer->last_name ?? '');
            $customerName = trim("{$firstName} {$lastName}");
            $refId = $quote->code ?? ($quoteType ? $quoteType->refId($quote->uuid) : $quote->uuid);

            $payload = [
                'quoteUID' => $quote->uuid,
                'uuid' => $quote->uuid,
                'refId' => $refId,
                'quoteTypeId' => $quoteTypeId,
                'workflowType' => WorkflowTypeEnum::COURTESY_EMAIL_WORKFLOW,
                'flowType' => QuoteFlowType::COURTESY_EMAIL->value,
                'line_of_business' => $quoteType ? strtolower($quoteType->value) : '',
                'advisorName' => $advisor->name,
                'customerName' => $customerName,
                'whatsAppconsent' => $quoteType ? getWhatsappConsent($quoteType, $quote->uuid) : false,
                'customer' => [
                    'email' => $quote->email,
                    'firstName' => ucfirst(strtolower($firstName)),
                    'lastName' => $lastName,
                    'WhatsAppNumber' => ! empty($quote->mobile_no) ? formatMobileNoWithoutPlus($quote->mobile_no) : '',
                ],
                'advisor' => [
                    'id' => $advisor->id,
                    'name' => $advisor->name,
                    'email' => $advisor->email,
                ],
            ];

            if ($quote instanceof BusinessQuote && filled($quote->business_type_of_insurance_id)) {
                $payload['businessInsuranceTypeId'] = (int) $quote->business_type_of_insurance_id;
                $payload['businessInsuranceType'] = $quote->businessTypeOfInsurance?->text ?? '';
            }

            $payload['isAUH'] = match ($quoteTypeId) {
                QuoteTypeId::Health => (int) ($quote->emirate_of_your_visa_id ?? 0) === EmirateEnum::ABU_DHABI,
                QuoteTypeId::Business => (int) ($quote->latestInsured?->emirate_of_registration_id ?? $quote->emirate_of_registration_id ?? 0) === EmirateEnum::ABU_DHABI,
                default => (int) ($quote->branch_id ?? BranchEnum::DUBAI->value) === BranchEnum::ABU_DHABI->value,
            };

            $response = $this->birdService->triggerWebHookRequest($workflowUrl, $payload);

            LoggerService::info('CourtesyEmailService - Creating quote work flow details', [
                'quoteUID' => $quote->uuid,
                'quoteTypeId' => $quoteTypeId,
                'response' => $response,
            ]);

            $this->birdService->createQuoteWorkFlowDetails(
                $quote,
                $response,
                QuoteFlowType::COURTESY_EMAIL->value,
                $quoteTypeId,
            );

            return ['message' => 'Processed workflow successfully', 'success' => true];

        } catch (\Exception $e) {
            LoggerService::error('CourtesyEmailService - Error processing workflow', [
                'quoteTypeId' => $quoteTypeId,
                'quoteUID' => $quoteUID,
                'error' => $e->getMessage(),
            ], $e);
            if ($quote !== null && filled($quote->email)) {
                $this->logCourtesyNotDispatched(
                    $quote,
                    $quoteTypeId,
                    'Courtesy workflow error: '.$e->getMessage(),
                );
            }

            return ['message' => 'Error processing workflow: '.$e->getMessage(), 'success' => false];
        }
    }

    private function logCourtesyNotDispatched(Model $quote, int $quoteTypeId, string $reason, ?int $customerId = null): void
    {
        $this->emailStatusService->logCourtesyWorkflowNotDispatched(
            $quoteTypeId,
            (int) $quote->id,
            strtolower(trim((string) $quote->email)),
            $reason,
            $customerId,
        );
    }

    private function getQuoteByQuoteType(int $quoteTypeId, string $quoteUID)
    {
        $quoteType = QuoteTypes::getName($quoteTypeId);
        if (! $quoteType) {
            return null;
        }

        $model = $quoteType->model();
        $query = $model::where('uuid', $quoteUID);

        if ($model instanceof PersonalQuote) {
            $query->where('quote_type_id', $quoteTypeId);
        }

        return $query->first();
    }

    private function hasRecentCourtesyFlowForCustomer(string $normalizedEmail, int $quoteTypeId): bool
    {
        return $this->getCustomerCourtesyCooldownState($normalizedEmail, $quoteTypeId)['has_recent'];
    }

    /**
     * Single pass over customer quotes + flow rows: replaces separate exists() + latest() calls.
     *
     * @return array{has_recent: bool, cooldown_expires_at: ?Carbon}
     */
    private function getCustomerCourtesyCooldownState(string $normalizedEmail, int $quoteTypeId): array
    {
        $quoteUuids = $this->quoteUuidsForCustomerEmailAndLob($normalizedEmail, $quoteTypeId);

        if ($quoteUuids->isEmpty()) {
            return ['has_recent' => false, 'cooldown_expires_at' => null];
        }

        $latest = QuoteFlowDetails::query()
            ->whereIn('quote_uuid', $quoteUuids->all())
            ->where('quote_type_id', $quoteTypeId)
            ->where('flow_type', QuoteFlowType::COURTESY_EMAIL->value)
            ->where('started_at', '>=', now()->subDays(7))
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        if ($latest === null || $latest->started_at === null) {
            return ['has_recent' => false, 'cooldown_expires_at' => null];
        }

        return [
            'has_recent' => true,
            'cooldown_expires_at' => $latest->started_at->copy()->addDays(7),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function quoteUuidsForCustomerEmailAndLob(string $normalizedEmail, int $quoteTypeId): Collection
    {
        $quoteType = QuoteTypes::getName($quoteTypeId);
        if (! $quoteType) {
            return collect();
        }

        $model = $quoteType->model();
        $query = $model::query()->where('email', $normalizedEmail);

        if ($model instanceof PersonalQuote) {
            $query->where('quote_type_id', $quoteTypeId);
        }

        return $query->pluck('uuid');
    }

    private function formatLogDateTime(?Carbon $value): string
    {
        if ($value === null) {
            return '—';
        }

        return $value->timezone(config('app.timezone'))->format(config('constants.DATETIME_DISPLAY_FORMAT'));
    }
}
