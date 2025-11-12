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
use App\Models\QuoteType;
use App\Models\SageProcess;
use App\Models\SendUpdateLog;
use App\Services\Logger\LoggerService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

    /**
     * Get failed sage processes with related quote/send_update and sage api logs
     *
     * @param  bool  $isExport  Whether this is for export
     * @return LengthAwarePaginator|Collection|array
     */
    public function getFailedSageProcesses(Request $request, bool $isExport = false)
    {
        try {
            LoggerService::info('Initiating retrieval of failed Sage processes');

            $query = SageProcess::select('id', 'model_type', 'model_id', 'insurance_provider_id', 'request', 'status', 'created_at', 'updated_at')
                ->where('status', SageEnum::SAGE_PROCESS_FAILED_STATUS)
                ->whereNotIn('model_type', [PaymentSplits::class, EmbeddedTransaction::class])
                ->when($request->insurance_provider_id && count($request->insurance_provider_id) > 0, function ($query) use ($request) {
                    $query->whereIn('insurance_provider_id', $request->insurance_provider_id);
                })->when($request->quote_type_id, function ($query) use ($request) {
                    $quoteModelClasses = [];
                    foreach ($request->quote_type_id as $quote_type_id) {
                        if ($quote_type_id == self::SEND_UPDATE_MODEL_NAME) {
                            $quoteModelClasses[] = $this->sendUpdateModelClass;
                        } else {
                            $quoteModelClasses[] = QuoteTypes::getQuoteTypeIdToClass($quote_type_id);
                        }
                    }

                    $query->whereIn('model_type', $quoteModelClasses);

                })->when($request->option, function ($query) use ($request) {
                    // Filter by option: "Send Update" or "Main Lead"
                    if ($request->option === 'Send Update') {
                        // Show only SendUpdateLog records
                        $query->where('model_type', $this->sendUpdateModelClass);
                    } elseif ($request->option === 'Main Lead') {
                        // Show only Quote models (exclude SendUpdateLog)
                        $query->where('model_type', '!=', $this->sendUpdateModelClass);
                    }
                    // If no filter or "All", show everything (no additional where clause)
                })->when($request->date_from, function ($query) use ($request) {
                    $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay()->format('Y-m-d H:i:s'));
                })->when($request->date_to, function ($query) use ($request) {
                    $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay()->format('Y-m-d H:i:s'));
                })->with([
                    'model' => function ($query) {
                        $query->with([
                            'sageApiLogs' => function ($query) {
                                $query->where('status', SageEnum::STATUS_FAIL)
                                    ->select('id', 'section_type', 'section_id', 'sage_end_point', 'response', 'status', 'created_at');
                            },
                            'payments' => function ($query) {
                                $query->select('id', 'code', 'paymentable_type', 'paymentable_id', 'price_vat_applicable', 'price_vat',
                                    'discount_value', 'total_price', 'commission_vat_applicable', 'commission_vat',
                                    'commission', 'captured_at', 'invoice_description', 'insurer_tax_number', 'insurer_commmission_invoice_number',
                                    'payment_status_id', 'send_update_log_id')
                                    ->with([
                                       'paymentSplits' => function ($query) {
                                           $query->select('id', 'code', 'sage_reciept_id');
                                       },
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
                    // Check if the model's table has quote_status_id column
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

            // Load quoteStatus relation conditionally for models that have it
            $this->loadQuoteStatusConditionally($results);

            // Add collected sage receipt IDs from payment splits
            $this->addCollectedSageReceiptIds($results);

            return $results;
        } catch (\Exception $e) {
            LoggerService::error('Error fetching failed Sage processes: '.$e->getMessage(), extra: [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
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
            ['id' => 'Main Lead', 'text' => 'Main Lead'],
            ['id' => 'Send Update', 'text' => 'Send Update'],
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
     *
     * @param  mixed  $results
     */
    protected function addCollectedSageReceiptIds($results): void
    {
        $items = $results instanceof LengthAwarePaginator ? $results->items() : $results;

        foreach ($items as $item) {
            $sageReceiptIds = [];

            if ($item->model && $item->model->payments) {
                foreach ($item->model->payments as $payment) {
                    if ($payment->paymentSplits) {
                        foreach ($payment->paymentSplits as $split) {
                            if (! empty($split->sage_reciept_id)) {
                                $sageReceiptIds[] = $split->sage_reciept_id;
                            }
                        }
                    }
                }
            }

            // Add as a formatted string (comma-separated) and as an array
            $item->collected_sage_receipt_ids = ! empty($sageReceiptIds) ? implode(', ', $sageReceiptIds) : null;
            $item->collected_sage_receipt_ids_array = $sageReceiptIds;
            
            // Add IMCRM error message extracted from sage API log response
            $item->imcrm_error = $this->extractImcrmError($item);
        }
    }

    /**
     * Extract the IMCRM error message from the Sage API log response
     * This extracts the error.message.value from the JSON response
     *
     * @param  mixed  $item
     * @return string|null
     */
    protected function extractImcrmError($item): ?string
    {
        if ($item->model && $item->model->sageApiLogs && $item->model->sageApiLogs->isNotEmpty()) {
            $firstFailedLog = $item->model->sageApiLogs->first();
            if ($firstFailedLog && $firstFailedLog->response) {
                try {
                    $responseData = json_decode($firstFailedLog->response, true);
                    return $responseData['error']['message']['value'] ?? null;
                } catch (\Exception $e) {
                    LoggerService::warning(self::class.' - '.__FUNCTION__.' - Could not parse sage API response: '.$e->getMessage());
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * Format the lead create date consistently across all models
     * This handles different date formats that may come from different models
     *
     * @param  mixed  $item
     * @return string|null
     */
    protected function formatLeadCreateDate($item): ?string
    {
        if ($item->model && $item->model->created_at) {
            try {
                // Parse the date using Carbon to handle various formats
                $createdAt = $item->model->created_at;
                
                // If it's already a Carbon instance, format it
                if ($createdAt instanceof Carbon) {
                    return $createdAt->format(config('constants.DATETIME_DISPLAY_FORMAT'));
                }
                
                // If it's a string, try to parse it
                return Carbon::parse($createdAt)->format(config('constants.DATETIME_DISPLAY_FORMAT'));
            } catch (\Exception $e) {
                LoggerService::warning(self::class.' - '.__FUNCTION__.' - Could not parse date: '.$e->getMessage());
                return $item->model->created_at; // Return original if parsing fails
            }
        }

        return null;
    }

    /**
     * Load quoteStatus relation conditionally for models that have it
     * This is done after fetching results because we need to check the actual polymorphic model type
     *
     * @param  mixed  $results
     */
    protected function loadQuoteStatusConditionally($results): void
    {
        $items = $results instanceof LengthAwarePaginator ? $results->items() : $results;

        foreach ($items as $item) {
            if ($item->model) {
                // Check if the actual polymorphic model has quote_status_id column
                if (Schema::hasColumn($item->model->getTable(), 'quote_status_id')) {
                    try {
                        $item->model->load('quoteStatus:id,text');
                    } catch (\Exception $e) {
                        LoggerService::warning(self::class.' - '.__FUNCTION__.' - Could not load quoteStatus for model: '.get_class($item->model));
                    }
                }
            }
        }
    }

}
