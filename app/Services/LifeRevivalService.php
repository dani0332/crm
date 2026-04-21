<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AMLStatusCode;
use App\Enums\ApplicationStorageEnums;
use App\Enums\LeadSourceEnum;
use App\Enums\quoteTypeCode;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\RolesEnum;
use App\Enums\WorkflowTypeEnum;
use App\Models\ApplicationStorage;
use App\Models\CurrencyType;
use App\Models\LifeInsuranceTenure;
use App\Models\LifePurposeOfInsurance;
use App\Models\Nationality;
use App\Models\PersonalQuote;
use App\Services\Logger\LoggerService;
use App\Traits\GenericQueriesAllLobs;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;

class LifeRevivalService
{
    use GenericQueriesAllLobs;

    public function __construct(
        protected CRUDService $crudService,
        protected DropdownSourceService $dropdownSourceService,
    ) {}

    public function getPaginatedRevivalQuotes(): Paginator
    {
        $request = request();

        $query = PersonalQuote::query()
            ->byQuoteTypeCode(QuoteTypes::LIFE->value)
            ->where('personal_quotes.source', LeadSourceEnum::REVIVAL)
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

        $this->adjustQueryByDateFilters($query, 'personal_quotes');

        if (Auth::user()->hasRole(RolesEnum::LifeAdvisor)) {
            $query->where('personal_quotes.advisor_id', Auth::id());
        }

        $query->orderBy('personal_quotes.created_at', 'desc');

        return $query->simplePaginate()->withQueryString();
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
        ];
    }

    public function updateSource(string $quoteUuid, string $source): void
    {
        $quote = PersonalQuote::where('uuid', $quoteUuid)
            ->where('quote_type_id', QuoteTypeId::Life)
            ->where('source', LeadSourceEnum::REVIVAL)
            ->with('lifeQuote')
            ->first();

        if (! $quote || ! $quote->lifeQuote) {
            LoggerService::info("LifeRevivalService - source not updated since lead not found for Quote UUID: {$quoteUuid}");

            return;
        }

        $quote->update(['source' => $source]);
        $quote->lifeQuote->update(['source' => $source]);

        LoggerService::info("LifeRevivalService - source updated to revival_replied - Quote UUID: {$quoteUuid}");
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

        // Build Bird payload
        $payload = [
            'to' => [
                [
                    'email' => $quote->email,
                    'name' => $quote->first_name.' '.$quote->last_name,
                ],
            ],
            'workflowType' => WorkflowTypeEnum::LIFE_REVIVAL_OCB,
            'quoteUID' => $quoteUuid,
            'customerEmail' => $quote->email,
            'customerName' => $quote->first_name.' '.$quote->last_name,
            'refID' => $quoteUuid,
            'subject' => $quote->first_name.' '.$quote->last_name."'s Life Policy with Alfred",
            'tag' => 'life-revival-email',
            'lob' => QuoteTypes::LIFE->id(),
            'templateId' => 1000,
            'tierRId' => $quote->tier_id,
        ];

        $workflowUrl = ApplicationStorage::where('key_name', ApplicationStorageEnums::LIFE_OCA_EMAIL_FLOW)->first();
        if (! $workflowUrl) {
            LoggerService::error('LifeRevivalService - Life revival email not sent since workflow URL not found');

            return;
        }

        // Send Bird request
        $response = app(BirdService::class)->triggerWebHookRequest($workflowUrl->value, $payload, 'post');

        LoggerService::info("LifeRevivalService - Life revival email sent for Quote UUID: {$quoteUuid} - Response: ".json_encode($response));

        if ($response->status_code !== 200) {
            LoggerService::error("LifeRevivalService - Life revival email not sent for Quote UUID: {$quoteUuid} - Response: ".json_encode($response));

            return;
        }

        LoggerService::info("LifeRevivalService - Life revival email sent for Quote UUID: {$quoteUuid}");
    }
}
