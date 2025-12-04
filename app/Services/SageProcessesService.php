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
     * @param  bool  $isExport  Whether this is for export
     * @return Paginator|Collection|array
     */
    public function getFailedSageProcesses($request, bool $isExport = false)
    {
        LoggerService::info('Initiating retrieval of failed Sage processes');

        $query = SageProcess::select('id', 'model_type', 'model_id', 'insurance_provider_id', 'request', 'status', 'created_at', 'updated_at')
            ->where('status', SageEnum::SAGE_PROCESS_FAILED_STATUS)
            ->whereNotIn('model_type', [PaymentSplits::class, EmbeddedTransaction::class])
            ->when($request->insurance_provider_id && is_array($request->insurance_provider_id) && count($request->insurance_provider_id) > 0, function ($query) use ($request) {
                $query->whereIn('insurance_provider_id', $request->insurance_provider_id);
            })->when($request->quote_type_id, function ($query) use ($request) {

                [$directModelClasses, $personalQuoteTypeIds] = $this->getDirectModelClassesAndPersonalQuoteTypeIds($request);

                $query->where(function ($subQuery) use ($directModelClasses, $personalQuoteTypeIds) {
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

            })->when($request->option, function ($query) use ($request) {
                if ($request->option === self::OPTION_SEND_UPDATE) {
                    $query->where('model_type', $this->sendUpdateModelClass);
                } elseif ($request->option === self::OPTION_MAIN_LEAD) {
                    $query->where('model_type', '!=', $this->sendUpdateModelClass);
                }
            })->when($request->date_from, function ($query) use ($request) {
                $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay()->format('Y-m-d H:i:s'));
            })->when($request->date_to, function ($query) use ($request) {
                $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay()->format('Y-m-d H:i:s'));
            })->with([
                'model' => function ($query) {
                    $query->with([
                        'sageApiLogs' => function ($query) {
                            $query->where('status', SageEnum::STATUS_FAIL)
                                ->select('id', 'section_type', 'section_id', 'sage_end_point', 'response', 'status', 'created_at')
                                ->orderBy('created_at', 'asc')
                                ->limit(1);
                        },
                        'payments' => function ($query) {
                            $query->select('id', 'code', 'paymentable_type', 'paymentable_id', 'price_vat_applicable', 'price_vat',
                                'discount_value', 'total_price', 'commission_vat_applicable', 'commission_vat',
                                'commission', 'captured_at', 'invoice_description', 'insurer_tax_number', 'insurer_commmission_invoice_number',
                                'payment_status_id', 'send_update_log_id')
                                ->with([
                                    'paymentSplits:id,code,sage_reciept_id',
                                    'paymentStatus:id,text',
                                ]);
                        },
                    ]);
                },
                'insuranceProvider:id,text',
            ])
            ->whereHas('model', function ($query) {
                $query->whereHas('sageApiLogs', function ($subQuery) {
                    $subQuery->where('status', SageEnum::STATUS_FAIL);
                });
                $modelInstance = $query->getModel();
                if ($modelInstance && Schema::hasColumn($modelInstance->getTable(), 'quote_status_id')) {
                    $query->where('quote_status_id', QuoteStatusEnum::POLICY_BOOKING_FAILED);
                } elseif ($modelInstance && Schema::hasColumn($modelInstance->getTable(), 'status')) {
                    $query->where('status', SendUpdateLogStatusEnum::UPDATE_BOOKING_FAILED);
                }
            })->orderBy('id', 'desc');

        if ($isExport) {
            $results = $query->get();
        } else {
            $results = $query->simplePaginate(10);
        }

        // Load quoteStatus relation efficiently in batches by model type
        $this->loadQuoteStatusEagerly($results);

        // Add collected sage receipt IDs from payment splits
        $this->addCollectedSageReceiptIds($results);

        return $results;
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
            $responseData = json_decode($firstFailedLog->response, true);

            // Safely extract nested error message value with validation
            return ! empty($responseData['error']['message']['value'])
                ? $responseData['error']['message']['value']
                : null;
        } catch (\Exception $e) {
            LoggerService::warning(self::class.' - '.__FUNCTION__.' - Could not parse sage API response: '.$e->getMessage(), extra: [
                'sage_api_log_id' => $firstFailedLog->id ?? null,
            ]);

            return null;
        }
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
                    LoggerService::warning(self::class.' - '.__FUNCTION__.' - Could not load quoteStatus for model type: '.$modelClass, extra: [
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

}
