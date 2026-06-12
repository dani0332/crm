<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypeId;
use App\Enums\QuoteTypes;
use App\Enums\SageEmbeddedProductEnum;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\BusinessQuote;
use App\Models\CarQuote;
use App\Models\EmbeddedTransaction;
use App\Models\HealthQuote;
use App\Models\PersonalQuote;
use App\Models\SageApiLog;
use App\Models\SendUpdateLog;
use App\Models\TravelQuote;
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
        $pdo = DB::connection()->getPdo();
        $filteredSources = $this->getFilteredSources($pdo, $request);
        $oldestFailedLogsCTE = $this->getOldestFailedSageLogsCTE($pdo, $request, $filteredSources);
        $morphModelClasses = $this->morphModelClassesFromFailedLeadSources($filteredSources);
        $filteredBase = $this->baseFailedSageApiLogsQuery($request, $oldestFailedLogsCTE);
        $embeddedTransactionMorphClass = $pdo->quote(EmbeddedTransaction::class);

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
                'sage_processes.insurance_provider_id',
                'sage_processes.request',
                DB::raw(
                    "JSON_UNQUOTE(JSON_EXTRACT(sage_api_logs.response, '$.error.message.value')) AS imcrm_error"
                ),
            ]);
    }

    private function getOldestFailedSageLogsCTE(\PDO $pdo, ValidatedInput|array $request, array $filteredSources): string
    {
        $logsTable = (new SageApiLog)->getTable();
        $failStatus = $pdo->quote(SageEnum::STATUS_FAIL);
        $failedLeads = $this->getFailedLeadsUnionSubquery($pdo, $request, $filteredSources);

        return "
            SELECT
                MIN(sal.id) AS id,
                sal.model_type,
                sal.model_id
            FROM {$logsTable} sal
            INNER JOIN (
                {$failedLeads}
            ) fl
                ON fl.section_type = sal.model_type
                AND fl.section_id = sal.model_id
            WHERE sal.status = {$failStatus}
            GROUP BY sal.model_type, sal.model_id
        ";
    }

    private function getFailedLeadsUnionSubquery(\PDO $pdo, ValidatedInput|array $request, array $filteredSources): string
    {
        $queries = [];
        $startDate = $request['date_from']
            ? Carbon::parse($request['date_from'])->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request['date_to']
            ? Carbon::parse($request['date_to'])->endOfDay()
            : Carbon::now()->endOfMonth();

        $startDateSql = $pdo->quote($startDate->format(config('constants.DB_DATE_FORMAT_MATCH')));
        $endDateSql = $pdo->quote($endDate->format(config('constants.DB_DATE_FORMAT_MATCH')));

        foreach ($filteredSources as $source) {
            $model = $pdo->quote($source['model']);
            $quoteTypeFilterSql = $this->buildPersonalQuoteTypeIdFilterSql($source);

            $queries[] = "
                SELECT
                    {$model} AS section_type,
                    id AS section_id
                FROM {$source['table']}
                WHERE {$source['status_column']} IN ({$source['statuses']})
                AND created_at BETWEEN {$startDateSql} AND {$endDateSql}
                {$quoteTypeFilterSql}
            ";
        }

        if ($queries === []) {
            return $this->emptyFailedLeadsNoMatchSubquery($pdo);
        }

        return implode("\nUNION ALL\n", $queries);
    }

    private function emptyFailedLeadsNoMatchSubquery(\PDO $pdo): string
    {
        $placeholderType = $pdo->quote('__sage_failed_leads_no_match__');

        return "SELECT {$placeholderType} AS section_type, 0 AS section_id WHERE 1 = 0";
    }

    private function baseFailedSageApiLogsQuery(ValidatedInput $request, string $oldestFailedLogsCTE): Builder
    {
        return SageApiLog::query()->where('sage_api_logs.status', SageEnum::STATUS_FAIL)
            ->withExpression('oldest_logs', $oldestFailedLogsCTE)
            ->join('oldest_logs', 'sage_api_logs.id', '=', 'oldest_logs.id')
            ->leftJoin('sage_processes', function ($join) {
                $join->on('sage_processes.model_id', '=', 'sage_api_logs.model_id')
                    ->whereColumn('sage_processes.model_type', 'sage_api_logs.model_type');
            })
            ->tap(fn (Builder $query) => $this->applyFailedSageRecordsFilters($query, $request));
    }

    private function morphModelClassesFromFailedLeadSources(array $filteredSources): array
    {
        return collect($filteredSources)
            ->pluck('model')
            ->filter(fn ($type) => is_string($type) && $type !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function getFilteredSources(\PDO $pdo, ValidatedInput|array $request): array
    {
        $bookingStatuses = implode(',', [
            QuoteStatusEnum::POLICY_BOOKING_QUEUED,
            QuoteStatusEnum::POLICY_BOOKING_FAILED,
        ]);

        $updateStatuses = implode(',', [
            $pdo->quote(SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED),
            $pdo->quote(SendUpdateLogStatusEnum::UPDATE_BOOKING_QUEUED),
        ]);

        $epStatuses = implode(',', [
            SageEmbeddedProductEnum::BOOKING_FAILED->id(),
            SageEmbeddedProductEnum::BOOKING_QUEUED->id(),
        ]);

        $mainLeadsWithEPs = $this->getMainLeadSources($bookingStatuses, $epStatuses);
        $sendUpdate = [
            'send_update' => [
                'table' => 'send_update_logs',
                'model' => SendUpdateLog::class,
                'status_column' => 'status',
                'statuses' => $updateStatuses,
            ],
        ];

        $allSources = array_merge($mainLeadsWithEPs, $sendUpdate);
        $sources = $this->resolveSourcesByOption(
            $request['option'] ?? null,
            $mainLeadsWithEPs,
            $sendUpdate,
            $allSources
        );

        $quoteTypeIds = array_values(array_filter(
            $request['quote_type_id'] ?? [],
            static fn ($id): bool => $id !== null && $id !== ''
        ));

        if ($quoteTypeIds === []) {
            return array_values($sources);
        }

        return array_values(
            $this->filterSourcesByQuoteTypes($sources, $quoteTypeIds)
        );
    }

    private function getMainLeadSources(string $bookingStatuses, string $epStatuses): array
    {
        return [
            'personal_quotes' => [
                'table' => 'personal_quotes',
                'model' => PersonalQuote::class,
                'status_column' => 'quote_status_id',
                'statuses' => $bookingStatuses,
            ],

            'car' => [
                'table' => 'car_quote_request',
                'model' => CarQuote::class,
                'status_column' => 'quote_status_id',
                'statuses' => $bookingStatuses,
            ],

            'health' => [
                'table' => 'health_quote_request',
                'model' => HealthQuote::class,
                'status_column' => 'quote_status_id',
                'statuses' => $bookingStatuses,
            ],

            'travel' => [
                'table' => 'travel_quote_request',
                'model' => TravelQuote::class,
                'status_column' => 'quote_status_id',
                'statuses' => $bookingStatuses,
            ],

            'business' => [
                'table' => 'business_quote_request',
                'model' => BusinessQuote::class,
                'status_column' => 'quote_status_id',
                'statuses' => $bookingStatuses,
            ],

            'embedded_transaction' => [
                'table' => 'embedded_transactions',
                'model' => EmbeddedTransaction::class,
                'status_column' => 'sage_status_id',
                'statuses' => $epStatuses,
            ],
        ];
    }

    private function resolveSourcesByOption(?string $option, array $mainLeadsWithEPs, array $sendUpdate, array $allSources): array
    {
        return match ($option) {
            self::MODEL_TYPE_MAIN_LEAD => $mainLeadsWithEPs,
            self::MODEL_TYPE_SEND_UPDATE => $sendUpdate,
            default => $allSources,
        };
    }

    private function filterSourcesByQuoteTypes(array $sources, array $quoteTypeIds): array
    {
        $filteredSources = [];
        $personalQuoteTypeIds = getPersonalQuoteTypeIds();

        $typeMappings = [
            self::MODEL_TYPE_SEND_UPDATE => 'send_update',
            QuoteTypeId::Car => 'car',
            QuoteTypeId::Health => 'health',
            QuoteTypeId::Travel => 'travel',
            QuoteTypeId::Business => 'business',
        ];

        $requestedPersonalQuoteTypeIds = [];

        foreach ($quoteTypeIds as $type) {
            if ($type === null || $type === '') {
                continue;
            }

            if (is_numeric($type)) {
                $id = (int) $type;

                if (in_array($id, $personalQuoteTypeIds, true)) {
                    $requestedPersonalQuoteTypeIds[$id] = $id;

                    continue;
                }

                $key = $typeMappings[$id] ?? null;
            } else {
                $key = $typeMappings[$type] ?? null;
            }

            if ($key !== null) {
                $this->addSourceIfExists($filteredSources, $sources, $key);
            }
        }

        if ($requestedPersonalQuoteTypeIds !== [] && isset($sources['personal_quotes'])) {
            $filteredSources['personal_quotes'] = $sources['personal_quotes'];
            $filteredSources['personal_quotes']['quote_type_ids'] = array_values($requestedPersonalQuoteTypeIds);
        }

        return $filteredSources;
    }

    private function buildPersonalQuoteTypeIdFilterSql(array $source): string
    {
        if (! isset($source['quote_type_ids']) || ! is_array($source['quote_type_ids']) || $source['quote_type_ids'] === []) {
            return '';
        }

        $inList = $this->buildSqlInListForPositiveIntegers($source['quote_type_ids']);
        if ($inList === '') {
            return '';
        }

        return " AND quote_type_id IN ({$inList})";
    }

    private function buildSqlInListForPositiveIntegers(array $ids): string
    {
        $ints = [];
        foreach ($ids as $id) {
            if (! is_numeric($id)) {
                continue;
            }

            $value = (int) $id;

            if ($value > 0) {
                $ints[$value] = $value;
            }
        }

        if ($ints === []) {
            return '';
        }

        return implode(',', $ints);
    }

    private function addSourceIfExists(array &$filteredSources, array $sources, string $key): void
    {
        if (isset($sources[$key])) {
            $filteredSources[$key] = $sources[$key];
        }
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
                ->addSelect(DB::raw(
                    "(SELECT GROUP_CONCAT(DISTINCT ps.sage_reciept_id)
                      FROM payment_splits ps
                      WHERE ps.code = {$paymentsTable}.code) AS collected_sage_receipt_ids"
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
            });
    }
    // endregion
}
