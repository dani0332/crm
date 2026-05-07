<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteTypes;
use App\Enums\SageEnum;
use App\Models\EmbeddedTransaction;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use App\Models\SageApiLog;
use App\Models\SendUpdateLog;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ValidatedInput;

class SageFailedRecordsService extends BaseService
{
    const MODEL_TYPE_MAIN_LEAD = 'Main Lead';
    const MODEL_TYPE_SEND_UPDATE = 'Send Update';
    private const DROPDOWN_CACHE_TTL_HOURS = 5;
    private const CACHE_KEY_INSURANCE_PROVIDERS = 'sage_failed_records.dropdown.insurance_providers';
    private const CACHE_KEY_QUOTE_TYPES = 'sage_failed_records.dropdown.quote_types';

    // region Dropdown Data
    public function getDropDownData(): array
    {
        return [
            'insuranceProviders' => $this->getInsuranceProviders(),
            'quoteTypes' => $this->getQuoteTypes(),
            'options' => [
                ['id' => self::MODEL_TYPE_MAIN_LEAD, 'text' => self::MODEL_TYPE_MAIN_LEAD],
                ['id' => self::MODEL_TYPE_SEND_UPDATE, 'text' => self::MODEL_TYPE_SEND_UPDATE],
            ],
        ];
    }

    private function getInsuranceProviders(): array
    {
        return Cache::remember(
            self::CACHE_KEY_INSURANCE_PROVIDERS,
            now()->addHours(self::DROPDOWN_CACHE_TTL_HOURS),
            fn (): array => (new DropdownSourceService)->getDropdownSource('insurance_provider_active_list')->toArray(),
        );
    }

    private function getQuoteTypes(): array
    {
        return Cache::remember(
            self::CACHE_KEY_QUOTE_TYPES,
            now()->addHours(self::DROPDOWN_CACHE_TTL_HOURS),
            function (): array {
                $options = QuoteTypes::primaryTypesWithIds();
                $options[] = [
                    'id' => self::MODEL_TYPE_SEND_UPDATE,
                    'text' => self::MODEL_TYPE_SEND_UPDATE,
                ];

                return $options;
            },
        );
    }
    // endregion

    // region Get Failed Sage Records
    public function getFailedSageRecords(ValidatedInput $request, bool $isExport = false): Paginator|EloquentCollection
    {
        $query = $this->buildFailedSageRecordsQuery($request);

        return $isExport ? $query->get() : $query->simplePaginate(10)->withQueryString();
    }

    private function buildFailedSageRecordsQuery(ValidatedInput $request): Builder
    {
        $oldestFailedLogsCTE = $this->getOldestFailedSageLogsCTE();
        $morphModelClasses = $this->distinctMorphClassesFromOldestFailedLogsCTE($oldestFailedLogsCTE);
        $filteredBase = $this->baseFailedSageApiLogsQuery($request, $oldestFailedLogsCTE);
        $embeddedTransactionMorphClass = DB::connection()->getPdo()->quote(EmbeddedTransaction::class);

        return $filteredBase
            ->with($this->failedSageLogsMorphWith($morphModelClasses))
            ->orderByDesc('sage_api_logs.id')
            ->select([
                'sage_api_logs.id',
                'sage_api_logs.response as failed_error',
                'sage_api_logs.sage_end_point as failed_api',
                'sage_api_logs.status as sage_api_status',
                'sage_api_logs.updated_at',
                'sage_api_logs.model_type',
                'sage_api_logs.model_id',
                DB::raw("CASE WHEN sage_api_logs.section_type = {$embeddedTransactionMorphClass} THEN sage_api_logs.section_type ELSE NULL END AS section_type"),
                DB::raw("CASE WHEN sage_api_logs.section_type = {$embeddedTransactionMorphClass} THEN sage_api_logs.section_id ELSE NULL END AS section_id"),
                'oldest_logs.section_type as oldest_failed_log_section_type',
                'oldest_logs.section_id as oldest_failed_log_section_id',
                'sage_processes.insurance_provider_id',
                'sage_processes.request',
                DB::raw(
                    "JSON_UNQUOTE(JSON_EXTRACT(sage_api_logs.response, '$.error.message.value')) AS imcrm_error"
                ),
            ]);
    }

    private function getOldestFailedSageLogsCTE(): string
    {
        $logs = (new SageApiLog)->getTable();
        $pdo = DB::connection()->getPdo();
        $failStatus = $pdo->quote(SageEnum::STATUS_FAIL);

        return "
            SELECT
                MIN({$logs}.id) AS id,
                {$logs}.section_type,
                {$logs}.section_id,
                {$logs}.model_type
            FROM {$logs}
            WHERE {$logs}.status = {$failStatus}
            GROUP BY {$logs}.section_type, {$logs}.section_id
        ";
    }

    private function baseFailedSageApiLogsQuery(ValidatedInput $request, string $oldestFailedLogsCTE): Builder
    {
        return SageApiLog::query()
            ->withExpression('oldest_logs', $oldestFailedLogsCTE)
            ->join('oldest_logs', 'sage_api_logs.id', '=', 'oldest_logs.id')
            ->leftJoin('sage_processes', function ($join) {
                $join->on('sage_processes.model_id', '=', 'sage_api_logs.model_id')
                    ->whereColumn('sage_processes.model_type', 'sage_api_logs.model_type');
            })
            ->tap(fn (Builder $query) => $this->applyFailedSageRecordsFilters($query, $request));
    }

    private function distinctMorphClassesFromOldestFailedLogsCTE(string $oldestFailedLogsCTE): array
    {
        return DB::query()
            ->withExpression('oldest_logs', $oldestFailedLogsCTE)
            ->from('oldest_logs')
            ->select('model_type')
            ->distinct()
            ->pluck('model_type')
            ->filter(fn ($type) => is_string($type) && $type !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function failedSageLogsMorphWith(array $morphModelClasses): array
    {
        $modelMorphWith = $this->failedSageLogsModelMorphWith($morphModelClasses);
        $embeddedTransactionSectionMorphWith = $this->failedSageLogsEmbeddedTransactionSectionMorphWith();

        return [
            'insuranceProvider:id,text',
            'model' => function (MorphTo $morph) use ($modelMorphWith) {
                $morph->morphWith($modelMorphWith);
            },
            'section' => function (MorphTo $morph) use ($embeddedTransactionSectionMorphWith) {
                $morph->morphWith($embeddedTransactionSectionMorphWith);
            },
        ];
    }

    private function failedSageLogsModelMorphWith(array $morphModelClasses): array
    {
        $paymentsConstraint = $this->failedSageMorphPaymentsWithConstraint();
        $morphWith = [];

        foreach ($this->validMorphClasses($morphModelClasses) as $modelClass) {
            if ($modelClass === EmbeddedTransaction::class) {
                $morphWith[$modelClass] = [
                    'quoteRequest',
                    'payments' => $paymentsConstraint,
                ];

                continue;
            }

            if ($modelClass === SendUpdateLog::class) {
                $morphWith[$modelClass] = [
                    'personalQuote:id,uuid,code,policy_number,quote_type_id',
                    'payments' => $paymentsConstraint,
                ];

                continue;
            }

            $relations = $this->failedSageLogsQuoteMorphRelations($modelClass, $paymentsConstraint);
            if ($relations !== []) {
                $morphWith[$modelClass] = $relations;
            }
        }

        return $morphWith;
    }

    private function failedSageLogsEmbeddedTransactionSectionMorphWith(): array
    {
        return [
            EmbeddedTransaction::class => [
                'payments' => $this->failedSageMorphPaymentsWithConstraint(),
            ],
        ];
    }

    private function failedSageLogsQuoteMorphRelations(string $morphClass, \Closure $paymentsConstraint): array
    {
        $relations = [];

        if (method_exists($morphClass, 'quoteStatus')) {
            $relations[] = 'quoteStatus:id,text';
        }

        if (method_exists($morphClass, 'payments')) {
            $relations['payments'] = $paymentsConstraint;
        }

        return $relations;
    }

    private function validMorphClasses(array $morphClasses): array
    {
        return array_filter($morphClasses, function ($class) {
            return is_string($class) && class_exists($class);
        });
    }

    private function failedSageMorphPaymentsWithConstraint(): \Closure
    {
        return function (Relation $payment): void {
            $paymentsTable = $payment->getRelated()->getTable();

            $sageReceiptIdsByPaymentCode = PaymentSplits::query()
                ->selectRaw('code, GROUP_CONCAT(DISTINCT sage_reciept_id) as collected_sage_receipt_ids')
                ->groupBy('code');

            $payment->with([
                'paymentStatus:id,text',
            ])
                ->select([
                    "{$paymentsTable}.id",
                    "{$paymentsTable}.code",
                    "{$paymentsTable}.paymentable_type",
                    "{$paymentsTable}.paymentable_id",
                    "{$paymentsTable}.price_vat_applicable",
                    "{$paymentsTable}.price_vat",
                    "{$paymentsTable}.discount_value",
                    "{$paymentsTable}.total_price",
                    "{$paymentsTable}.commission_vat_applicable",
                    "{$paymentsTable}.commission_vat",
                    "{$paymentsTable}.commission",
                    "{$paymentsTable}.captured_at",
                    "{$paymentsTable}.invoice_description",
                    "{$paymentsTable}.insurer_tax_number",
                    "{$paymentsTable}.insurer_commmission_invoice_number",
                    "{$paymentsTable}.payment_status_id",
                    "{$paymentsTable}.send_update_log_id",
                ])
                ->leftJoinSub(
                    $sageReceiptIdsByPaymentCode,
                    'sage_reciept_ids_by_payment_code',
                    fn ($join) => $join->on(
                        'sage_reciept_ids_by_payment_code.code',
                        '=',
                        "{$paymentsTable}.code",
                    ),
                )
                ->addSelect(DB::raw(
                    'sage_reciept_ids_by_payment_code.collected_sage_receipt_ids as collected_sage_receipt_ids'
                ));
        };
    }
    // endregion

    // region Apply Failed Sage Records Filters
    private function applyFailedSageRecordsFilters(Builder $query, ValidatedInput $request): void
    {
        $query
            ->when(! empty($request->insurance_provider_id), function ($q) use ($request) {
                $q->whereIn('sage_processes.insurance_provider_id', $request->insurance_provider_id);
            })
            ->when($request->option, function ($q) use ($request) {
                if ($request->option === self::MODEL_TYPE_MAIN_LEAD) {
                    $q->where('sage_api_logs.model_type', '!=', SendUpdateLog::class);
                }

                if ($request->option === self::MODEL_TYPE_SEND_UPDATE) {
                    $q->where('sage_api_logs.model_type', SendUpdateLog::class);
                }
            })
            ->when($request->date_from, function ($q) use ($request) {
                $q->where('sage_api_logs.created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
            })
            ->when($request->date_to, function ($q) use ($request) {
                $q->where('sage_api_logs.created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
            })
            ->when(! empty($request->quote_type_id), function ($q) use ($request) {
                $this->applyQuoteTypeFilter($q, $request);
            });
    }

    private function applyQuoteTypeFilter(Builder $query, ValidatedInput $request): void
    {
        if ($request->option === self::MODEL_TYPE_SEND_UPDATE) {
            $numericQuoteTypeIds = $this->numericQuoteTypeIdsFromQuoteTypeFilter($request);
            if ($numericQuoteTypeIds === []) {
                return;
            }

            $query->whereExists(function ($existsQuery) use ($numericQuoteTypeIds) {
                $existsQuery->select(DB::raw(1))
                    ->from('send_update_logs')
                    ->whereColumn('send_update_logs.id', 'sage_api_logs.model_id')
                    ->where('sage_api_logs.model_type', SendUpdateLog::class)
                    ->where(function ($typeQuery) use ($numericQuoteTypeIds) {
                        $typeQuery->whereIn('send_update_logs.quote_type_id', $numericQuoteTypeIds)
                            ->orWhereExists(function ($pqQuery) use ($numericQuoteTypeIds) {
                                $pqQuery->select(DB::raw(1))
                                    ->from('personal_quotes')
                                    ->whereColumn('personal_quotes.id', 'send_update_logs.personal_quote_id')
                                    ->whereIn('personal_quotes.quote_type_id', $numericQuoteTypeIds);
                            });
                    });
            });

            return;
        }

        [$directModelClasses, $personalQuoteTypeIds] = $this->getDirectModelClassesAndPersonalQuoteTypeIds($request);

        $query->where(function ($subQuery) use ($directModelClasses, $personalQuoteTypeIds) {
            if (! empty($directModelClasses)) {
                $subQuery->whereIn('sage_api_logs.model_type', $directModelClasses);
            }

            if (! empty($personalQuoteTypeIds)) {
                $subQuery->orWhere(function ($personalQuery) use ($personalQuoteTypeIds) {
                    $personalQuery->where('sage_api_logs.model_type', PersonalQuote::class)
                        ->whereExists(function ($existsQuery) use ($personalQuoteTypeIds) {
                            $existsQuery->select(DB::raw(1))
                                ->from('personal_quotes')
                                ->whereColumn('personal_quotes.id', 'sage_api_logs.model_id')
                                ->whereIn('personal_quotes.quote_type_id', $personalQuoteTypeIds);
                        });
                });
            }
        });
    }

    private function numericQuoteTypeIdsFromQuoteTypeFilter(ValidatedInput $request): array
    {
        if (! $request->has('quote_type_id') || ! is_array($request->quote_type_id)) {
            return [];
        }

        $ids = [];
        foreach ($request->quote_type_id as $value) {
            if ($value === self::MODEL_TYPE_SEND_UPDATE) {
                continue;
            }
            if (is_numeric($value)) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    private function getDirectModelClassesAndPersonalQuoteTypeIds(ValidatedInput $request): array
    {
        $directModelClasses = [];
        $personalQuoteTypeIds = [];

        if (! $request->has('quote_type_id') || ! is_array($request->quote_type_id)) {
            return [$directModelClasses, $personalQuoteTypeIds];
        }

        foreach ($request->quote_type_id as $quote_type_id) {
            if ($quote_type_id == self::MODEL_TYPE_SEND_UPDATE) {
                $directModelClasses[] = SendUpdateLog::class;
            } else {
                $quoteTypeEnum = QuoteTypes::getName($quote_type_id);

                if ($quoteTypeEnum) {
                    if (checkPersonalQuotes($quoteTypeEnum->value)) {
                        $personalQuoteTypeIds[] = $quote_type_id;
                    } else {
                        $modelClass = QuoteTypes::getQuoteTypeIdToClass($quote_type_id);
                        $directModelClasses[] = $modelClass;
                    }
                }
            }
        }

        return [$directModelClasses, $personalQuoteTypeIds];
    }
    // endregion
}
