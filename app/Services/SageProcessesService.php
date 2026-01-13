<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuoteStatusEnum;
use App\Enums\QuoteTypes;
use App\Enums\SageEnum;
use App\Enums\SendUpdateLogStatusEnum;
use App\Models\EmbeddedTransaction;
use App\Models\InsuranceProvider;
use App\Models\PaymentSplits;
use App\Models\PersonalQuote;
use App\Models\QuoteType;
use App\Models\SageProcess;
use App\Models\SendUpdateLog;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Service class to handle failed sage processes and their related data
 *
 * This service fetches sage processes that are failed along with their
 * related quote/send_update entries that are marked as policy booking failed
 * and the failed entries from sage api logs.
 */
class SageProcessesService extends BaseService
{
    protected $sendUpdateModelClass = SendUpdateLog::class;

    const SEND_UPDATE_MODEL_NAME = 'Send Update';
    const OPTION_SEND_UPDATE = 'Send Update';
    const OPTION_MAIN_LEAD = 'Main Lead';

    /**
     * Get failed sage processes with related quote/send_update and sage api logs
     *
     * @param  mixed  $request  Request object with filters
     * @param  bool  $isExport  Whether this is for export
     * @return Paginator|Collection|array
     */
    public function getFailedSageProcesses($request, bool $isExport = false)
    {
        LoggerService::info('Initiating retrieval of failed Sage processes');

        $query = $this->buildBaseQuery();
        $this->applyFilters($query, $request);
        $this->applyEagerLoading($query);
        $this->applyModelValidation($query);

        $results = $isExport ? $query->get() : $query->simplePaginate(10);

        // Append filter parameters to pagination URLs
        if (! $isExport && $results instanceof Paginator) {
            $results->appends($request->toArray());
        }

        $this->enrichResultsWithAdditionalData($results);

        return $results;
    }

    /**
     * Build the base query for failed sage processes
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildBaseQuery()
    {
        return SageProcess::select('id', 'model_type', 'model_id', 'insurance_provider_id', 'request', 'status', 'created_at', 'updated_at')
            ->where('status', SageEnum::SAGE_PROCESS_FAILED_STATUS)
            ->whereNotIn('model_type', [PaymentSplits::class, EmbeddedTransaction::class])
            ->orderBy('id', 'desc');
    }

    /**
     * Apply all filters to the query based on request parameters
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $request
     */
    protected function applyFilters($query, $request): void
    {
        $this->applyInsuranceProviderFilter($query, $request);
        $this->applyQuoteTypeFilter($query, $request);
        $this->applyOptionFilter($query, $request);
        $this->applyDateFilters($query, $request);
    }

    /**
     * Apply insurance provider filter
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $request
     */
    protected function applyInsuranceProviderFilter($query, $request): void
    {
        $query->when(
            $request->insurance_provider_id && is_array($request->insurance_provider_id) && count($request->insurance_provider_id) > 0,
            fn ($q) => $q->whereIn('insurance_provider_id', $request->insurance_provider_id)
        );
    }

    /**
     * Apply quote type filter with support for direct models and personal quotes
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $request
     */
    protected function applyQuoteTypeFilter($query, $request): void
    {
        $query->when($request->quote_type_id, function ($q) use ($request) {
            [$directModelClasses, $personalQuoteTypeIds] = $this->getDirectModelClassesAndPersonalQuoteTypeIds($request);

            $q->where(function ($subQuery) use ($directModelClasses, $personalQuoteTypeIds) {
                if (! empty($directModelClasses)) {
                    $subQuery->whereIn('model_type', $directModelClasses);
                }

                if (! empty($personalQuoteTypeIds)) {
                    $subQuery->orWhere(function ($personalQuery) use ($personalQuoteTypeIds) {
                        $personalQuery->where('model_type', PersonalQuote::class)
                            ->whereExists(function ($existsQuery) use ($personalQuoteTypeIds) {
                                $existsQuery->select(DB::raw(1))
                                    ->from('personal_quotes')
                                    ->whereColumn('personal_quotes.id', 'sage_processes.model_id')
                                    ->whereIn('personal_quotes.quote_type_id', $personalQuoteTypeIds);
                            });
                    });
                }
            });
        });
    }

    /**
     * Apply option filter (Main Lead vs Send Update)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $request
     */
    protected function applyOptionFilter($query, $request): void
    {
        $query->when($request->option, function ($q) use ($request) {
            if ($request->option === self::OPTION_SEND_UPDATE) {
                $q->where('model_type', $this->sendUpdateModelClass);
            } elseif ($request->option === self::OPTION_MAIN_LEAD) {
                $q->where('model_type', '!=', $this->sendUpdateModelClass);
            }
        });
    }

    /**
     * Apply date range filters
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  mixed  $request
     */
    protected function applyDateFilters($query, $request): void
    {
        $query->when(
            $request->date_from,
            fn ($q) => $q->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay())
        )->when(
            $request->date_to,
            fn ($q) => $q->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay())
        );
    }

    /**
     * Apply eager loading for related models to prevent N+1 queries
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyEagerLoading($query): void
    {
        $query->with([
            'model' => function ($query) {
                $query->with([
                    'sageApiLogs' => fn ($q) => $q->where('status', SageEnum::STATUS_FAIL)
                        ->select('id', 'section_type', 'section_id', 'sage_end_point', 'response', 'status', 'created_at')
                        ->orderBy('created_at', 'asc')
                        ->limit(1),
                    'payments' => fn ($q) => $q->select('id', 'code', 'paymentable_type', 'paymentable_id', 'price_vat_applicable', 'price_vat',
                        'discount_value', 'total_price', 'commission_vat_applicable', 'commission_vat',
                        'commission', 'captured_at', 'invoice_description', 'insurer_tax_number', 'insurer_commmission_invoice_number',
                        'payment_status_id', 'send_update_log_id')
                        ->with([
                            'paymentSplits:id,code,sage_reciept_id',
                            'paymentStatus:id,text',
                        ]),
                ]);
            },
            'insuranceProvider:id,text',
        ]);
    }

    /**
     * Apply model validation constraints
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyModelValidation($query): void
    {
        $query->whereHas('model', function ($q) {
            $q->whereHas('sageApiLogs', fn ($subQuery) => $subQuery->where('status', SageEnum::STATUS_FAIL));

            $modelInstance = $q->getModel();
            if ($modelInstance && Schema::hasColumn($modelInstance->getTable(), 'quote_status_id')) {
                $q->where('quote_status_id', QuoteStatusEnum::POLICY_BOOKING_FAILED);
            } elseif ($modelInstance && Schema::hasColumn($modelInstance->getTable(), 'status')) {
                $q->where('status', SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED);
            }
        });
    }

    /**
     * Enrich results with additional computed data
     *
     * @param  mixed  $results
     */
    protected function enrichResultsWithAdditionalData($results): void
    {
        $this->loadQuoteStatusEagerly($results);
        $this->loadPersonalQuoteEagerly($results);
        $this->addCollectedSageReceiptIds($results);
    }

    public function dropdownData()
    {
        return [
            'insuranceProviders' => $this->getInsuranceProviders(),
            'quoteTypes' => $this->getQuoteTypes(),
            'options' => $this->getOptions(),
        ];
    }

    public function getOptions()
    {
        return [
            ['id' => self::OPTION_MAIN_LEAD, 'text' => self::OPTION_MAIN_LEAD],
            ['id' => self::OPTION_SEND_UPDATE, 'text' => self::OPTION_SEND_UPDATE],
        ];
    }

    public function getInsuranceProviders()
    {
        return InsuranceProvider::select('id', 'text')->withActive()->orderBy('text')->get()->toArray();
    }

    public function getQuoteTypes()
    {
        $quoteTypes = QuoteType::select('id', 'text')->withActive()->orderBy('text')->get()->toArray();
        // Push Send Update to the last index
        $quoteTypes[] = [
            'id' => self::SEND_UPDATE_MODEL_NAME,
            'text' => self::SEND_UPDATE_MODEL_NAME,
        ];

        return $quoteTypes;
    }

    /**
     * Add collected sage receipt IDs from payment splits to each process
     * Optimized to use collection methods instead of nested loops
     *
     * @param  mixed  $results
     */
    protected function addCollectedSageReceiptIds($results): void
    {
        $items = $results instanceof Paginator ? $results->items() : $results;

        foreach ($items as $item) {
            $sageReceiptIds = [];

            if ($item->model && $item->model->payments) {
                // Use collection methods to flatten and filter in one pass
                $sageReceiptIds = $item->model->payments
                    ->pluck('paymentSplits')
                    ->flatten()
                    ->pluck('sage_reciept_id')
                    ->filter()
                    ->values()
                    ->toArray();
            }

            // Add as a formatted string (comma-separated) and as an array
            $item->collected_sage_receipt_ids = ! empty($sageReceiptIds) ? implode(', ', $sageReceiptIds) : null;
            $item->collected_sage_receipt_ids_array = $sageReceiptIds;

            // Add IMCRM error message extracted from sage API log response
            $item->imcrm_error = $this->extractSageApiError($item);
        }
    }

    /**
     * Extract the IMCRM error message from the Sage API log response
     * This extracts the error.message.value from the JSON response
     * Optimized to use null-safe operators and cleaner logic
     *
     * @param  mixed  $item
     */
    protected function extractSageApiError($item): ?string
    {
        $firstFailedLog = $item->model?->sageApiLogs?->first();

        if (! $firstFailedLog?->response) {
            return null;
        }

        try {
            $responseData = $this->decodeJsonResponse($firstFailedLog->response);

            // Safely extract nested error message value with validation
            return ! empty($responseData['error']['message']['value'])
                ? $responseData['error']['message']['value']
                : null;
        } catch (\Exception $e) {
            LoggerService::warning('Could not parse sage API response: '.$e->getMessage(), extra: [
                'sage_api_log_id' => $firstFailedLog->id ?? null,
            ]);

            return null;
        }
    }

    /**
     * Decode JSON response and handle double-encoded JSON strings
     *
     * @param  string  $jsonString
     * @return array|null
     */
    protected function decodeJsonResponse(string $jsonString): ?array
    {
        $decoded = json_decode($jsonString, true);

        // Check if response is double-encoded JSON string and decode again
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Load quoteStatus relation efficiently by grouping models by type
     * This avoids N+1 queries by loading relationships in batches
     *
     * @param  mixed  $results
     */
    protected function loadQuoteStatusEagerly($results): void
    {
        $items = $results instanceof Paginator ? $results->items() : $results;

        if (empty($items)) {
            return;
        }

        // Group models by their class type to check schema once per type
        [$modelsByType , $typesWithQuoteStatus] = $this->getModelsByTypeAndTypesWithQuoteStatus($items);

        // Load quoteStatus for each model type in batch
        foreach ($modelsByType as $modelClass => $models) {
            if (isset($typesWithQuoteStatus[$modelClass]) && ! empty($models)) {
                try {
                    // Create a collection and load the relation in one query
                    $modelCollection = collect($models);
                    $modelIds = $modelCollection->pluck('id')->toArray();

                    // Load all quoteStatus records in one query
                    $modelClass::with('quoteStatus:id,text')->whereIn('id', $modelIds)->get()
                        ->each(function ($loadedModel) use ($modelCollection) {
                            $originalModel = $modelCollection->firstWhere('id', $loadedModel->id);
                            if ($originalModel && isset($loadedModel->quoteStatus)) {
                                $originalModel->setRelation('quoteStatus', $loadedModel->quoteStatus);
                            }
                        });

                } catch (\Exception $e) {
                    LoggerService::warning('Could not load quoteStatus for model type: '.$modelClass, extra: [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Load PersonalQuote relation efficiently by grouping models by type
     * This avoids N+1 queries by loading relationships in batches
     *
     * @param  mixed  $results
     */
    protected function loadPersonalQuoteEagerly($results): void
    {
        $items = $results instanceof Paginator ? $results->items() : $results;

        if (empty($items)) {
            return;
        }

        // Group models by their class type to check schema once per type
        [$modelsByType , $typesWithPersonalQuote] = $this->getModelsByTypeAndTypesWithPersonalQuote($items);

        // Load quoteStatus for each model type in batch
        foreach ($modelsByType as $modelClass => $models) {
            if (isset($typesWithPersonalQuote[$modelClass]) && ! empty($models)) {
                try {
                    // Create a collection and load the relation in one query
                    $modelCollection = collect($models);
                    $modelIds = $modelCollection->pluck('id')->toArray();

                    // Load all personalQuote records in one query
                    $modelClass::with('personalQuote:id,uuid,code,policy_number')->whereIn('id', $modelIds)->get()
                        ->each(function ($loadedModel) use ($modelCollection) {
                            $originalModel = $modelCollection->firstWhere('id', $loadedModel->id);
                            if ($originalModel && isset($loadedModel->personalQuote)) {
                                $originalModel->setRelation('personalQuote', $loadedModel->personalQuote);
                            }
                        });

                } catch (\Exception $e) {
                    LoggerService::warning('Could not load personalQuote for model type: '.$modelClass, extra: [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function getDirectModelClassesAndPersonalQuoteTypeIds($request)
    {
        $directModelClasses = [];
        $personalQuoteTypeIds = [];

        foreach ($request->quote_type_id as $quote_type_id) {
            if ($quote_type_id == self::SEND_UPDATE_MODEL_NAME) {
                $directModelClasses[] = $this->sendUpdateModelClass;
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

    private function getModelsByTypeAndTypesWithQuoteStatus($items)
    {
        $modelsByType = [];
        $typesWithQuoteStatus = [];

        foreach ($items as $item) {
            if ($item->model) {
                $modelClass = get_class($item->model);

                if (! isset($modelsByType[$modelClass])) {
                    $modelsByType[$modelClass] = [];

                    // Check schema only once per model type
                    if (Schema::hasColumn($item->model->getTable(), 'quote_status_id')) {
                        $typesWithQuoteStatus[$modelClass] = true;
                    }
                }

                if (isset($typesWithQuoteStatus[$modelClass])) {
                    $modelsByType[$modelClass][] = $item->model;
                }
            }
        }

        return [$modelsByType, $typesWithQuoteStatus];
    }

    private function getModelsByTypeAndTypesWithPersonalQuote($items)
    {
        $modelsByType = [];
        $typesWithPersonalQuote = [];

        foreach ($items as $item) {
            if ($item->model) {
                $modelClass = get_class($item->model);

                if (! isset($modelsByType[$modelClass])) {
                    $modelsByType[$modelClass] = [];

                    // Check schema only once per model type
                    if (Schema::hasColumn($item->model->getTable(), 'personal_quote_id')) {
                        $typesWithPersonalQuote[$modelClass] = true;
                    }
                }

                if (isset($typesWithPersonalQuote[$modelClass])) {
                    $modelsByType[$modelClass][] = $item->model;
                }
            }
        }

        return [$modelsByType, $typesWithPersonalQuote];
    }

}
