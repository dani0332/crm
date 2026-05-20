<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteFlowType;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\HomeQuote;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeRevivalService
{
    use GenericQueriesAllLobs;

    public const REVIVAL_SOURCES = [
        LeadSourceEnum::REVIVAL_SHORT,
        LeadSourceEnum::REVIVAL_ANNUAL,
        LeadSourceEnum::REVIVAL_REPLIED,
        LeadSourceEnum::REVIVAL_PAID,
    ];

    public function __construct(
        protected CRUDService $crudService,
        protected DropdownSourceService $dropdownSourceService,
    ) {}

    public function getPaginatedRevivalQuotes(): Paginator
    {
        $query = PersonalQuote::query()
            ->byQuoteTypeCode(QuoteTypes::HOME->value)
            ->whereIn('personal_quotes.source', self::REVIVAL_SOURCES)
            ->with([
                'paymentStatus',
                'quoteStatus',
                'advisor',
                'quoteDetail.lostReason',
                'renewalBatchModel',
                'homeQuote',
            ])
            ->filter(false)
            ->withFakeLeadCriteria();

        $this->applyRevivalRequestFilters($query);
        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        if (Auth::user()->hasRole(RolesEnum::HomeAdvisor)) {
            $query->where('personal_quotes.advisor_id', Auth::id());
        }

        $query->orderBy('personal_quotes.created_at', 'desc');

        return $query->simplePaginate()->withQueryString();
    }

    public function getExportQuery(array $requestParams = []): Builder
    {
        if (! Auth::check()) {
            $user = $requestParams['user'] ?? null;
            unset($requestParams['user']);
            Auth::login($user);
            DB::setDefaultConnection('mysql_read');
            request()->merge($requestParams);
        }

        $query = PersonalQuote::query()
            ->byQuoteTypeCode(QuoteTypes::HOME->value)
            ->whereIn('personal_quotes.source', self::REVIVAL_SOURCES)
            ->with([
                'advisor.primaryBranch',
                'branch',
                'quoteStatus',
                'quoteDetail',
                'homeQuote.homeQuoteRequestDetail.lostReason',
                'customer',
                'subSource',
            ])
            ->filter(paginate: false)
            ->withFakeLeadCriteria();

        $this->applyRevivalRequestFilters($query);
        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        if (Auth::check() && Auth::user()->hasRole(RolesEnum::HomeAdvisor)) {
            $query->where('personal_quotes.advisor_id', Auth::id());
        }

        return $query->orderBy('personal_quotes.created_at', 'desc');
    }

    public function getIndexFormOptions(): array
    {
        $insurerAmlStatuses = collect(AMLStatusCode::getStatuses())
            ->map(fn (string $label, string $code) => ['value' => $code, 'label' => $label])
            ->values()
            ->all();

        return [
            'advisors' => $this->crudService->getAdvisorsByModelType('home'),
            'teams' => $this->crudService->getUserTeams(Auth::id()),
            'leadStatuses' => $this->dropdownSourceService->getDropdownSource('quote_status_id', QuoteTypeId::Home),
            'leadSources' => collect(self::REVIVAL_SOURCES)->map(fn (string $source) => [
                'value' => $source,
                'label' => str_replace('_', ' ', $source),
            ])->values()->all(),
            'insurerAmlStatuses' => $insurerAmlStatuses,
        ];
    }

    public function getRevivalPayload(PersonalQuote $lead, HomeQuote $homeQuote, string $source): array
    {
        $payload = [
            'firstName' => $lead->first_name,
            'lastName' => $lead->last_name,
            'email' => $lead->email,
            'mobileNo' => $lead->mobile_no ?? '',
            'source' => $source,
            'referenceUrl' => 'IMCRM',
            'quoteTypeId' => QuoteTypeId::Home,
            'lang' => 'EN',
            'whatsappConsent' => 1,
        ];

        foreach ($this->optionalHomeQuoteCapiFields($lead, $homeQuote) as $key => $value) {
            if ($value !== null) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    private function optionalHomeQuoteCapiFields(PersonalQuote $lead, HomeQuote $homeQuote): array
    {
        return [
            'buildingValue' => $homeQuote->building_value,
            'hasBuilding' => $homeQuote->has_building !== null ? (bool) $homeQuote->has_building : null,
            'hasContents' => $homeQuote->has_contents !== null ? (bool) $homeQuote->has_contents : null,
            'hasClaimedLosses' => $homeQuote->has_claimed_losses !== null ? (bool) $homeQuote->has_claimed_losses : null,
            'contentsValueId' => $homeQuote->contents_value_id,
            'hasPersonalBelongings' => $homeQuote->has_personal_belongings !== null ? (bool) $homeQuote->has_personal_belongings : null,
            'personalBelongingsValueId' => $homeQuote->personal_belongings_value_id,
            'ownerOccupancyTypeId' => $homeQuote->owner_occupancy_type_id,
            'accommodationTypeId' => $homeQuote->accommodation_type_id,
            'possessionTypeId' => $homeQuote->possession_type_id,
            'subAreaId' => $homeQuote->sub_area_id,
            'coverageTypeId' => $homeQuote->coverage_type_id,
            'address' => $homeQuote->address,
            'nationalityId' => $lead->nationality_id,
            'gender' => $lead->gender,
            'dob' => $lead->dob,
            'companyName' => $lead->company_name,
            'companyAddress' => $lead->company_address,
        ];
    }

    public function sendHomeRevivalEmail(string $quoteUuid): ?object
    {
        $quote = PersonalQuote::where('uuid', $quoteUuid)
            ->where('quote_type_id', QuoteTypeId::Home)
            ->with('homeQuote')
            ->first();

        if (! $quote || ! $quote->homeQuote) {
            LoggerService::info(self::class.' - Home revival email not sent since lead not found for Quote UUID: '.$quoteUuid);

            return null;
        }

        $payload = (object) [
            'to' => [
                [
                    'email' => $quote->email,
                    'name' => $quote->first_name.' '.$quote->last_name,
                ],
            ],
            'workflowType' => WorkflowTypeEnum::HOME_REVIVAL_OCB,
            'quoteUID' => $quoteUuid,
            'customerEmail' => $quote->email,
            'customerName' => $quote->first_name.' '.$quote->last_name,
            'refID' => $quote->code,
            'tag' => 'home-revival-email',
            'lob' => QuoteTypes::HOME->id(),
        ];

        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::HOME_RENEWAL_OCB)->first();
        if (! $workflowUrl || ! $workflowUrl->value) {
            LoggerService::warning(self::class.' - Home revival email not sent since workflow URL not found');

            return null;
        }

        $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $payload, 'post');

        LoggerService::info(self::class.' - Home revival email sent for Quote UUID: '.$quoteUuid.' - Response: '.json_encode($response));

        if (! in_array($response->status_code, [200, 201])) {
            LoggerService::warning(self::class.' - Home revival email not sent for Quote UUID: '.$quoteUuid.' - Response: '.json_encode($response));

            return null;
        }

        DB::transaction(function () use ($quote): void {
            $quote->update(['quote_status_id' => QuoteStatusEnum::Quoted]);
        });

        app(BirdService::class)->createQuoteWorkFlowDetails(
            $quote,
            $response,
            QuoteFlowType::HOME_REVIVAL_OCB->value,
            (int) QuoteTypes::HOME->id()
        );

        LoggerService::info(self::class.' - Home revival email sent for Quote UUID: '.$quoteUuid);

        return $payload;
    }

    public function updateSource(string $quoteUuid, string $source): void
    {
        LoggerService::info(self::class.' - updateSource request received',
            ['quote_uuid' => $quoteUuid, 'source' => $source]);

        $quote = PersonalQuote::where('uuid', $quoteUuid)
            ->where('quote_type_id', QuoteTypeId::Home)
            ->whereIn('source', [LeadSourceEnum::REVIVAL_SHORT, LeadSourceEnum::REVIVAL_ANNUAL])
            ->with('homeQuote')
            ->first();

        if (! $quote || ! $quote->homeQuote) {
            LoggerService::info(self::class.' - source not updated since lead not found for Quote UUID: ',
                ['quote_uuid' => $quoteUuid, 'source' => $source]);

            return;
        }

        $quote->update(['source' => $source]);
        $quote->homeQuote->update(['source' => $source]);

        LoggerService::info(self::class.' - source updated - Quote UUID: ',
            ['quote_uuid' => $quoteUuid, 'source' => $source]);
    }

    private function applyRevivalRequestFilters(Builder $query): void
    {
        $request = request();

        if ($request->filled('advisors')) {
            $query->whereIn('personal_quotes.advisor_id', (array) $request->advisors);
        }

        if ($request->filled('quote_status')) {
            $query->whereIn('personal_quotes.quote_status_id', (array) $request->quote_status);
        }

        if ($request->filled('lead_source')) {
            $leadSources = array_values(array_intersect(
                (array) $request->lead_source,
                self::REVIVAL_SOURCES
            ));

            if ($leadSources !== []) {
                $query->whereIn('personal_quotes.source', $leadSources);
            }
        }

        if ($request->filled('previous_quote_policy_number')) {
            $query->where('personal_quotes.previous_quote_policy_number', $request->previous_quote_policy_number);
        }

        if ($request->filled('renewal_batch')) {
            $query->where('personal_quotes.renewal_batch', $request->renewal_batch);
        }

        if ($request->filled('is_renewal')) {
            if ($request->is_renewal === quoteTypeCode::yesText) {
                $query->whereNotNull('personal_quotes.previous_quote_policy_number');
            }
            if ($request->is_renewal === quoteTypeCode::noText) {
                $query->whereNull('personal_quotes.previous_quote_policy_number');
            }
        }

        $query->when(
            $request->filled('assigned_to_date'), function ($q) use ($request): void {
                $startDate = Carbon::parse((string) $request->assigned_to_date)->startOfDay()->toDateTimeString();
                $endDate = Carbon::parse((string) $request->assigned_to_date)->endOfDay()->toDateTimeString();
                $q->whereHas('quoteDetail', function ($detailQuery) use ($startDate, $endDate): void {
                    $detailQuery->whereBetween('advisor_assigned_date', [$startDate, $endDate]);
                });
            }
        );

        $query->when(
            $request->filled('private_client') && $request->string('private_client')->toString() !== 'all',
            function ($q) use ($request): void {
                $pc = $request->string('private_client')->toString();
                if ($pc === 'yes') {
                    $q->where('personal_quotes.pc_qualified', true);
                } elseif ($pc === 'no') {
                    $q->where(function ($inner): void {
                        $inner->where('personal_quotes.pc_qualified', false)
                            ->orWhereNull('personal_quotes.pc_qualified');
                    });
                }
            }
        );

        $query->when($request->filled('policy_number'), function ($q) use ($request): void {
            $q->where(function ($inner) use ($request): void {
                $inner->where('personal_quotes.policy_number', $request->policy_number)
                    ->orWhere('personal_quotes.previous_quote_policy_number', $request->policy_number);
            });
        });

        $query->when(! empty($request->authorize_date), function ($q) use ($request): void {
            $authorizeDates = $request->authorize_date;
            if (is_array($authorizeDates) && count($authorizeDates) >= 2) {
                $startDate = $authorizeDates[0];
                $endDate = $authorizeDates[1];

                if ($startDate && $endDate) {
                    $q->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate): void {
                        $paymentQuery->whereBetween('authorized_at', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay(),
                        ]);
                    });
                }
            }
        });

        $query->when(! empty($request->captured_date), function ($q) use ($request): void {
            $capturedDates = $request->captured_date;
            if (is_array($capturedDates) && count($capturedDates) >= 2) {
                $startDate = $capturedDates[0];
                $endDate = $capturedDates[1];

                if ($startDate && $endDate) {
                    $q->whereHas('payments', function ($paymentQuery) use ($startDate, $endDate): void {
                        $paymentQuery->whereBetween('captured_at', [
                            Carbon::parse($startDate)->startOfDay(),
                            Carbon::parse($endDate)->endOfDay(),
                        ]);
                    });
                }
            }
        });

        $query->when(
            $request->filled('updated_at'), function ($q) use ($request): void {
                $start = Carbon::parse((string) $request->updated_at)->startOfDay()->toDateTimeString();
                $end = Carbon::parse((string) $request->updated_at)->endOfDay()->toDateTimeString();
                $q->whereBetween('personal_quotes.updated_at', [$start, $end]);
            }
        );

        $query->when($request->filled('created_at_start') && $request->filled('created_at_end'),
            function ($q) use ($request): void {
                $start = Carbon::parse((string) $request->created_at_start)->startOfDay()->toDateTimeString();
                $end = Carbon::parse((string) $request->created_at_end)->endOfDay()->toDateTimeString();
                $q->whereBetween('personal_quotes.created_at', [$start, $end]);
            }
        );
    }
}
