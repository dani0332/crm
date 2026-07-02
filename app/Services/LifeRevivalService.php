<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AMLStatusCode;
use App\Enums\LeadSourceEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\WorkflowTypeEnum;
use App\Models\CurrencyType;
use App\Models\LifeInsuranceTenure;
use App\Models\LifePurposeOfInsurance;
use App\Models\LifeQuote;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Services\EmailServices\WebEngageService;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LifeRevivalService
{
    use GenericQueriesAllLobs;

    public const REVIVAL_SOURCES = [
        LeadSourceEnum::REVIVAL,
        LeadSourceEnum::REVIVAL_REPLIED,
        LeadSourceEnum::REVIVAL_PAID,
    ];

    public function __construct(
        protected CRUDService $crudService,
        protected DropdownSourceService $dropdownSourceService,
    ) {}

    public function getPaginatedRevivalQuotes(): Paginator
    {
        $query = $this->buildRevivalQuotesQuery();

        if (Auth::user()->hasRole(RolesEnum::LifeAdvisor)) {
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
            ->byQuoteTypeCode(QuoteTypes::LIFE->value)
            ->whereIn('personal_quotes.source', self::REVIVAL_SOURCES)
            ->with(['advisor', 'quoteStatus', 'nationality', 'quoteDetail.lostReason', 'customer', 'lifeQuote.sumInsuredCurrency', 'lifeQuote.policySumAssuredCurrency'])
            ->filter(paginate: false)
            ->withFakeLeadCriteria();

        $this->applyRevivalRequestFilters($query);
        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        if (Auth::check() && Auth::user()->hasRole(RolesEnum::LifeAdvisor)) {
            $query->where('personal_quotes.advisor_id', Auth::id());
        }

        return $query->orderBy('personal_quotes.created_at', 'desc');
    }

    /**
     * @return Builder<PersonalQuote>
     */
    private function buildRevivalQuotesQuery(): Builder
    {
        $query = PersonalQuote::query()
            ->byQuoteTypeCode(QuoteTypes::LIFE->value)
            ->whereIn('personal_quotes.source', self::REVIVAL_SOURCES)
            ->with([
                'paymentStatus',
                'quoteStatus',
                'advisor',
                'quoteDetail.lostReason',
                'renewalBatchModel',
                'lifeQuote' => function ($q): void {
                    $q->with([
                        'purposeOfInsurance',
                        'insuranceTenure',
                        'currency',
                    ]);
                },
            ])
            ->filter(false)
            ->withFakeLeadCriteria();

        $this->applyRevivalRequestFilters($query);
        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        return $query;
    }

    private function applyRevivalRequestFilters(Builder $query): void
    {
        $request = request();

        if ($request->filled('lead_source')) {
            $leadSources = array_values(array_intersect(
                (array) $request->lead_source,
                self::REVIVAL_SOURCES
            ));

            if ($leadSources !== []) {
                $query->whereIn('personal_quotes.source', $leadSources);
            }
        }

        if ($request->filled('advisors')) {
            $query->whereIn('personal_quotes.advisor_id', (array) $request->advisors);
        }

        if ($request->filled('quote_status')) {
            $query->whereIn('personal_quotes.quote_status_id', (array) $request->quote_status);
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

        $query->when($request->filled('purpose_of_insurance_id'), function ($q) use ($request): void {
            $q->whereHas('lifeQuote', function ($sub) use ($request): void {
                $sub->whereIn('purpose_of_insurance_id', (array) $request->purpose_of_insurance_id);
            });
        });

        $query->when($request->filled('tenure_of_insurance_id'), function ($q) use ($request): void {
            $q->whereHas('lifeQuote', function ($sub) use ($request): void {
                $sub->whereIn('tenure_of_insurance_id', (array) $request->tenure_of_insurance_id);
            });
        });

        $query->when(
            $request->filled('sum_insured_range') && $request->filled('sum_insured_currency_id'),
            function ($q) use ($request): void {
                $q->whereHas('lifeQuote', function ($sub) use ($request): void {
                    $sub->where('sum_insured_currency_id', $request->sum_insured_currency_id);
                });
                switch ($request->string('sum_insured_range')->toString()) {
                    case 'lt500k':
                        $q->whereHas('lifeQuote', function ($sub): void {
                            $sub->where('sum_insured_value', '<', 500_000);
                        });
                        break;
                    case '500k-1m':
                        $q->whereHas('lifeQuote', function ($sub): void {
                            $sub->whereBetween('sum_insured_value', [500_000, 999_999]);
                        });
                        break;
                    case 'gte1m':
                        $q->whereHas('lifeQuote', function ($sub): void {
                            $sub->where('sum_insured_value', '>=', 1_000_000);
                        });
                        break;
                    default:
                        break;
                }
            }
        );

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

    /**
     * @return array<string, mixed>
     */
    public function getIndexFormOptions(): array
    {
        $insurerAmlStatuses = collect(AMLStatusCode::getStatuses())
            ->map(fn (string $label, string $code) => ['value' => $code, 'label' => $label])
            ->values()
            ->all();

        return [
            'advisors' => $this->crudService->getAdvisorsByModelType(strtolower(quoteTypeCode::Life)),
            'teams' => $this->crudService->getUserTeams(Auth::id()),
            'leadStatuses' => $this->dropdownSourceService->getDropdownSource('quote_status_id', QuoteTypeId::Life),
            'nationalities' => Nationality::query()->withActive()->select(['id', 'text'])->get(),
            'gender' => $this->crudService->getGenderOptions(),
            'purposeOfInsurance' => LifePurposeOfInsurance::query()->select(['id', 'text'])->get(),
            'insuranceTenures' => LifeInsuranceTenure::query()->select(['id', 'text'])->get(),
            'insurerAmlStatuses' => $insurerAmlStatuses,
            'currencies' => CurrencyType::query()->withActive()->select(['id', 'text'])->get(),
            'leadSources' => collect(self::REVIVAL_SOURCES)->map(fn (string $source) => [
                'value' => $source,
                'label' => str_replace('_', ' ', $source),
            ])->values()->all(),
        ];
    }

    public function updateSource(string $quoteUuid, string $source): void
    {
        LoggerService::info(self::class.' - updateSource request received',
            ['quote_uuid' => $quoteUuid, 'source' => $source]);

        $quote = PersonalQuote::where('uuid', $quoteUuid)
            ->where('quote_type_id', QuoteTypeId::Life)
            ->where('source', LeadSourceEnum::REVIVAL)
            ->with('lifeQuote')
            ->first();

        if (! $quote || ! $quote->lifeQuote) {
            LoggerService::info(self::class.' - source not updated since lead not found for Quote UUID: ',
                ['quote_uuid' => $quoteUuid, 'source' => $source]);

            return;
        }

        $quote->update(['source' => $source]);
        $quote->lifeQuote->update(['source' => $source]);

        LoggerService::info(self::class.' - source updated to revival_replied - Quote UUID: ',
            ['quote_uuid' => $quoteUuid, 'source' => $source]);
    }

    public function sendLifeRevialEmail(string $quoteUuid): void
    {
        $quote = PersonalQuote::where('uuid', $quoteUuid)
            ->where('quote_type_id', QuoteTypeId::Life)
            ->where('source', LeadSourceEnum::REVIVAL)
            ->with('lifeQuote')
            ->first();

        if (! $quote || ! $quote->lifeQuote) {
            LoggerService::info("LifeRevivalService - Life revival email not sent since lead not found for Quote UUID: {$quoteUuid}");

            return;
        }

        $payload = [
            'customerId' => $quote->customer_id ?? $quote->email,
            'firstName' => $quote->first_name ?? '',
            'lastName' => $quote->last_name ?? '',
            'customerEmail' => $quote->email,
            'customerMobile' => ! empty($quote->mobile_no) ? '+'.formatMobileNoWithoutPlus($quote->mobile_no) : '',
            'quoteUID' => $quoteUuid,
            'workflowType' => WorkflowTypeEnum::LIFE_REVIVAL_OCB,
            'customerName' => $quote->first_name.' '.$quote->last_name,
            'refID' => $quote->code,
            'lob' => QuoteTypes::LIFE->id(),
        ];

        app(WebEngageService::class)->sendEvent(WorkflowTypeEnum::LIFE_REVIVAL_OCB, $payload);

        LoggerService::info("LifeRevivalService - Life revival WebEngage event triggered for Quote UUID: {$quoteUuid}");

        $this->updateQuoteStatus($quote);

        LoggerService::info("LifeRevivalService - Life revival email sent for Quote UUID: {$quoteUuid}");
    }

    private function updateQuoteStatus(PersonalQuote $quote): void
    {
        DB::transaction(function () use ($quote): void {
            $quote->update(['quote_status_id' => QuoteStatusEnum::Quoted]);
            $quote->lifeQuote->update(['quote_status_id' => QuoteStatusEnum::Quoted]);
        });
    }

    public function getRevivalPayload(PersonalQuote $lead, LifeQuote $lifeQuote): array
    {
        $payload = [
            'firstName' => $lead->first_name,
            'lastName' => $lead->last_name,
            'email' => $lead->email,
            'mobileNo' => $lead->mobile_no ?? '',
            'source' => LeadSourceEnum::REVIVAL,
            'referenceUrl' => 'IMCRM',
            'quoteTypeId' => QuoteTypes::LIFE->id(),
            'othersInfo' => '',
            'lang' => 'EN',
            'typeOfInsurance' => 'Life Insurance',
            'whatsappConsent' => 1,
        ];

        foreach ($this->optionalLifeQuoteCapiFields($lead, $lifeQuote) as $key => $value) {
            if ($value !== null) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    private function optionalLifeQuoteCapiFields(PersonalQuote $lead, LifeQuote $lifeQuote): array
    {
        return [
            'gender' => $lead->gender,
            'dob' => $lead->dob,
            'nationalityId' => $lead->nationality_id,
            'sumInsuredValue' => $lifeQuote->sum_insured_value !== null ? (int) $lifeQuote->sum_insured_value : null,
            'sumInsuredCurrencyId' => $lifeQuote->sum_insured_currency_id,
            'maritalStatusId' => $lifeQuote->marital_status_id,
            'purposeOfInsuranceId' => $lifeQuote->purpose_of_insurance_id,
            'tenureOfInsuranceId' => $lifeQuote->tenure_of_insurance_id,
            'height' => $lifeQuote->height,
            'weight' => $lifeQuote->weight,
            'isSmoker' => $lifeQuote->is_smoker,
            'paymentStatusId' => $lead->payment_status_id,
            'quoteStatusId' => $lead->quote_status_id,
            'numberOfYearsId' => $lifeQuote->number_of_years_id,
        ];
    }
}
