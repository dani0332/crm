<?php

namespace App\Services;

use App\Enums\ApplicationStorageEnums;
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
        QuoteTypeId::Health,
        QuoteTypeId::Home,
        QuoteTypeId::Life,
        QuoteTypeId::Pet,
        QuoteTypeId::Travel,
        QuoteTypeId::Yacht,
    ];

    /**
     * @return list<int>
     */
    public static function allowedQuoteTypeIds(): array
    {
        return self::ALLOWED_QUOTE_TYPES;
    }

    public static function isCourtesyEmailQuoteType(int $quoteTypeId): bool
    {
        return in_array($quoteTypeId, self::ALLOWED_QUOTE_TYPES, true);
    }

    public function getGoogleReviewFlowLogContext(string $quoteUuid, int $quoteTypeId, ?string $recipientEmail): array
    {
        if (! self::isCourtesyEmailQuoteType($quoteTypeId)) {
            return [
                'review_flow_status' => 'Not applicable',
                'suppression_expires_at' => '—',
            ];
        }

        $hasFlowForQuote = QuoteFlowDetails::query()
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

        if ($this->hasRecentCourtesyFlowForCustomer($normalizedEmail, $quoteTypeId)) {
            return [
                'review_flow_status' => 'Suppressed',
                'suppression_expires_at' => $this->formatLogDateTime(
                    $this->courtesyCooldownExpiresAt($normalizedEmail, $quoteTypeId)
                ),
            ];
        }

        return [
            'review_flow_status' => 'Not triggered',
            'suppression_expires_at' => '—',
        ];
    }

    public function __construct(
        private BirdService $birdService,
        private EmailStatusService $emailStatusService,
    ) {}

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
                'line_of_business' => $quoteType ? strtolower($quoteType->value) : '',
                'advisorName' => $advisor->name,
                'customerName' => $customerName,
                'whatsAppconsent' => $quoteType ? getWhatsappConsent($quoteType, $quote->uuid) : false,
                'customer' => [
                    'email' => $quote->email,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'WhatsAppNumber' => ! empty($quote->mobile_no) ? formatMobileNoWithoutPlus($quote->mobile_no) : '',
                ],
                'advisor' => [
                    'id' => $advisor->id,
                    'name' => $advisor->name,
                    'email' => $advisor->email,
                ],
            ];

            if ($quoteTypeId === QuoteTypeId::Health) {
                $payload['isAUH'] = isset($quote->emirate_of_your_visa_id) && $quote->emirate_of_your_visa_id === EmirateEnum::ABU_DHABI;
            }

            $response = $this->birdService->triggerWebHookRequest($workflowUrl, $payload);

            try {
                $headers = $response->headers ?? [];
                $runId = null;

                if (isset($headers['Run-Id'])) {
                    $runId = is_array($headers['Run-Id']) ? collect($headers['Run-Id'])->first() : $headers['Run-Id'];
                } elseif (isset($headers['run-id'])) {
                    $runId = is_array($headers['run-id']) ? collect($headers['run-id'])->first() : $headers['run-id'];
                }

                if ($runId) {
                    QuoteFlowDetails::create([
                        'quote_uuid' => $quote->uuid,
                        'quote_type_id' => $quoteTypeId,
                        'flow_type' => QuoteFlowType::COURTESY_EMAIL->value,
                        'flow_id' => $runId,
                        'started_at' => now(),
                    ]);
                } else {
                    LoggerService::warning('CourtesyEmailService - Run-Id not found in response headers', [
                        'quoteUID' => $quote->uuid,
                        'quoteTypeId' => $quoteTypeId,
                    ]);
                    $this->logCourtesyNotDispatched(
                        $quote,
                        $quoteTypeId,
                        'Bird webhook did not return Run-Id in response headers; workflow run not recorded',
                        $customer instanceof Customer ? $customer->id : null,
                    );
                }
            } catch (\Throwable $th) {
                LoggerService::error('CourtesyEmailService - Error saving quote flow details', [
                    'quoteUID' => $quote->uuid,
                    'quoteTypeId' => $quoteTypeId,
                    'error' => $th->getMessage(),
                ], $th);
            }

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

        if ($model instanceof PersonalQuote || $model instanceof BusinessQuote) {
            $query->where('quote_type_id', $quoteTypeId);
        }

        return $query->first();
    }

    private function hasRecentCourtesyFlowForCustomer(string $normalizedEmail, int $quoteTypeId): bool
    {
        $quoteUuids = $this->quoteUuidsForCustomerEmailAndLob($normalizedEmail, $quoteTypeId);

        if ($quoteUuids->isEmpty()) {
            return false;
        }

        return QuoteFlowDetails::query()
            ->whereIn('quote_uuid', $quoteUuids->all())
            ->where('quote_type_id', $quoteTypeId)
            ->where('flow_type', QuoteFlowType::COURTESY_EMAIL->value)
            ->where('started_at', '>=', now()->subDays(7))
            ->exists();
    }

    private function courtesyCooldownExpiresAt(string $normalizedEmail, int $quoteTypeId): ?Carbon
    {
        $quoteUuids = $this->quoteUuidsForCustomerEmailAndLob($normalizedEmail, $quoteTypeId);

        if ($quoteUuids->isEmpty()) {
            return null;
        }

        $latest = QuoteFlowDetails::query()
            ->whereIn('quote_uuid', $quoteUuids->all())
            ->where('quote_type_id', $quoteTypeId)
            ->where('flow_type', QuoteFlowType::COURTESY_EMAIL->value)
            ->where('started_at', '>=', now()->subDays(7))
            ->orderByDesc('started_at')
            ->first();

        if ($latest?->started_at === null) {
            return null;
        }

        return $latest->started_at->copy()->addDays(7);
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

        if ($model instanceof PersonalQuote || $model instanceof BusinessQuote) {
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
